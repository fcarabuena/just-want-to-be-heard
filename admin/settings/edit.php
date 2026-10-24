<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

$pdo = getDatabaseConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    header('Location: ' . APP_BASE_PATH . '/admin/settings/index.php?status=invalid');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM settings WHERE setting_id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$setting = $stmt->fetch();

if (!$setting) {
    header('Location: ' . APP_BASE_PATH . '/admin/settings/index.php?status=missing');
    exit;
}

$errors = [];
$values = [
    'setting_group' => $setting['setting_group'] ?? 'general',
    'setting_key' => $setting['setting_key'] ?? '',
    'setting_value' => $setting['setting_value'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['setting_group'] = trim((string) ($_POST['setting_group'] ?? ($setting['setting_group'] ?? 'general')));
    $values['setting_key'] = trim((string) ($_POST['setting_key'] ?? ($setting['setting_key'] ?? '')));
    $values['setting_value'] = trim((string) ($_POST['setting_value'] ?? ($setting['setting_value'] ?? '')));

    if ($values['setting_group'] === '') {
        $values['setting_group'] = 'general';
    }

    if ($values['setting_key'] === '') {
        $errors[] = 'Setting key is required.';
    } else {
        $existing = $pdo->prepare('SELECT setting_id FROM settings WHERE setting_key = :key AND setting_id != :id LIMIT 1');
        $existing->execute([':key' => $values['setting_key'], ':id' => $id]);
        if ($existing->fetch()) {
            $errors[] = 'A setting with this key already exists.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            UPDATE settings
            SET setting_group = :setting_group,
                setting_key = :setting_key,
                setting_value = :setting_value,
                updated_at = CURRENT_TIMESTAMP
            WHERE setting_id = :id
        ');

        $stmt->execute([
            ':setting_group' => $values['setting_group'],
            ':setting_key' => $values['setting_key'],
            ':setting_value' => $values['setting_value'] !== '' ? $values['setting_value'] : null,
            ':id' => $id,
        ]);

        header('Location: ' . APP_BASE_PATH . '/admin/settings/index.php?status=updated');
        exit;
    }
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">EDIT SETTING</div>
                <h2 class="section-title-large">Edit Project Setting</h2>
            </div>
            <a href="<?php echo APP_BASE_PATH; ?>/admin/settings/index.php" class="secondary-link-btn">Back to Settings</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="flash-message flash-error">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="studio-form">
            <div class="form-grid two-col">
                <div class="form-field">
                    <label for="setting_group">Group</label>
                    <input id="setting_group" name="setting_group" type="text" value="<?php echo htmlspecialchars((string) ($values['setting_group'] ?? 'general')); ?>" placeholder="general, public, studio, export...">
                </div>

                <div class="form-field">
                    <label for="setting_key">Key</label>
                    <input id="setting_key" name="setting_key" type="text" value="<?php echo htmlspecialchars((string) ($values['setting_key'] ?? '')); ?>" required>
                </div>
            </div>

            <div class="form-field">
                <label for="setting_value">Value</label>
                <textarea id="setting_value" name="setting_value" rows="6" placeholder="Enter the configuration value or JSON/text content."><?php echo htmlspecialchars((string) ($values['setting_value'] ?? '')); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-btn">Update Setting</button>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/settings/index.php" class="secondary-link-btn">Cancel</a>
            </div>
        </form>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

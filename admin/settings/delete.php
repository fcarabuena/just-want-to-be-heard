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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('DELETE FROM settings WHERE setting_id = :id');
    $stmt->execute([':id' => $id]);

    header('Location: ' . APP_BASE_PATH . '/admin/settings/index.php?status=deleted');
    exit;
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel narrow-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">DELETE SETTING</div>
                <h2 class="section-title-large">Delete Project Setting</h2>
            </div>
        </div>

        <div class="confirm-box">
            <p>Are you sure you want to delete the setting <strong><?php echo htmlspecialchars((string) ($setting['setting_key'] ?? 'Unnamed setting')); ?></strong>?</p>
            <p class="inline-note">This removes the key/value configuration entry from the project settings table.</p>

            <form method="POST" class="form-actions inline-actions">
                <button type="submit" class="danger-btn">Delete Setting</button>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/settings/index.php" class="secondary-link-btn">Cancel</a>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();
require_once __DIR__ . '/../characters/upload_helpers.php';

$pdo = getDatabaseConnection();

$errors = [];
$values = [
    'name' => '',
    'type' => 'prop',
    'description' => '',
    'image_path' => '',
    'is_active' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['name'] = trim((string) ($_POST['name'] ?? ''));
    $values['type'] = trim((string) ($_POST['type'] ?? 'prop'));
    $values['description'] = trim((string) ($_POST['description'] ?? ''));
    $values['image_path'] = trim((string) ($_POST['image_path'] ?? ''));
    $values['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($values['name'] === '') {
        $errors[] = 'Object name is required.';
    }

    if ($values['type'] === '') {
        $errors[] = 'Object type is required.';
    }

    if (isset($_FILES['object_asset']) && $_FILES['object_asset']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['object_asset']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The object asset upload failed. Please try again.';
        } else {
            try {
                $uploadedAsset = storeUploadedCharacterAsset($_FILES['object_asset']);
                $values['image_path'] = $uploadedAsset['file_path'];
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            INSERT INTO objects (name, type, description, image_path, is_active)
            VALUES (:name, :type, :description, :image_path, :is_active)
        ');

        $stmt->execute([
            ':name' => $values['name'],
            ':type' => $values['type'] !== '' ? $values['type'] : 'prop',
            ':description' => $values['description'] !== '' ? $values['description'] : null,
            ':image_path' => $values['image_path'] !== '' ? $values['image_path'] : null,
            ':is_active' => $values['is_active'],
        ]);

        header('Location: ' . APP_BASE_PATH . '/admin/objects/index.php?status=created');
        exit;
    }
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">NEW OBJECT</div>
                <h2 class="section-title-large">Create Prop / Object</h2>
            </div>
            <a href="<?php echo APP_BASE_PATH; ?>/admin/objects/index.php" class="secondary-link-btn">Back to Library</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="flash-message flash-error">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="studio-form" enctype="multipart/form-data">
            <div class="form-grid two-col">
                <div class="form-field">
                    <label for="name">Object Name</label>
                    <input id="name" name="name" type="text" value="<?php echo htmlspecialchars((string) ($values['name'] ?? '')); ?>" required>
                </div>

                <div class="form-field">
                    <label for="type">Type</label>
                    <input id="type" name="type" type="text" value="<?php echo htmlspecialchars((string) ($values['type'] ?? 'prop')); ?>" placeholder="prop, set piece, tool, landmark..." required>
                </div>
            </div>

            <div class="form-field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5" placeholder="Describe the object and how it supports the story world."><?php echo htmlspecialchars((string) ($values['description'] ?? '')); ?></textarea>
            </div>

            <div class="form-field">
                <label for="object_asset">Object Asset</label>
                <input id="object_asset" name="object_asset" type="file" accept="image/png,image/jpeg,image/gif,image/webp">
                <div class="inline-note" id="selected_object_asset">No file selected</div>
            </div>

            <div class="form-field checkbox-field">
                <label class="checkbox-wrap">
                    <input type="checkbox" name="is_active" value="1" <?php echo ((int) ($values['is_active'] ?? 1) === 1) ? 'checked' : ''; ?>>
                    <span>Object is active</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-btn">Save Object</button>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/objects/index.php" class="secondary-link-btn">Cancel</a>
            </div>
        </form>
    </div>
</section>

<script>
    (function () {
        const input = document.getElementById('object_asset');
        const selected = document.getElementById('selected_object_asset');
        if (!input || !selected) return;

        input.addEventListener('change', function () {
            const file = this.files && this.files[0];
            selected.textContent = file ? 'Selected: ' + file.name : 'No file selected';
        });
    })();
</script>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

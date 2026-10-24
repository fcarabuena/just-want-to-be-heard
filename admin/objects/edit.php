<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();
require_once __DIR__ . '/../characters/upload_helpers.php';

$pdo = getDatabaseConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    header('Location: ' . APP_BASE_PATH . '/admin/objects/index.php?status=invalid');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM objects WHERE object_id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$object = $stmt->fetch();

if (!$object) {
    header('Location: ' . APP_BASE_PATH . '/admin/objects/index.php?status=missing');
    exit;
}

$errors = [];
$values = [
    'name' => $object['name'],
    'type' => $object['type'] ?? 'prop',
    'description' => $object['description'] ?? '',
    'image_path' => $object['image_path'] ?? '',
    'is_active' => (int) $object['is_active'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['name'] = trim((string) ($_POST['name'] ?? $object['name']));
    $values['type'] = trim((string) ($_POST['type'] ?? ($object['type'] ?? 'prop')));
    $values['description'] = trim((string) ($_POST['description'] ?? ($object['description'] ?? '')));
    $values['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    $values['image_path'] = trim((string) ($object['image_path'] ?? ''));

    if ($values['name'] === '') {
        $errors[] = 'Object name is required.';
    }

    if ($values['type'] === '') {
        $errors[] = 'Object type is required.';
    }

    $oldAssetPath = $object['image_path'] ?? '';
    $newAssetPath = $oldAssetPath;

    if (isset($_FILES['object_asset']) && $_FILES['object_asset']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['object_asset']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The replacement object asset upload failed. Please try again.';
        } else {
            try {
                $uploadedAsset = storeUploadedCharacterAsset($_FILES['object_asset']);
                $newAssetPath = $uploadedAsset['file_path'];
                $values['image_path'] = $newAssetPath;
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            UPDATE objects
            SET name = :name,
                type = :type,
                description = :description,
                image_path = :image_path,
                is_active = :is_active,
                updated_at = CURRENT_TIMESTAMP
            WHERE object_id = :id
        ');

        $stmt->execute([
            ':name' => $values['name'],
            ':type' => $values['type'] !== '' ? $values['type'] : 'prop',
            ':description' => $values['description'] !== '' ? $values['description'] : null,
            ':image_path' => $newAssetPath !== '' ? $newAssetPath : null,
            ':is_active' => $values['is_active'],
            ':id' => $id,
        ]);

        if (!empty($oldAssetPath) && $oldAssetPath !== $newAssetPath) {
            deleteCharacterImageIfExists($oldAssetPath);
        }

        header('Location: ' . APP_BASE_PATH . '/admin/objects/index.php?status=updated');
        exit;
    }
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">EDIT OBJECT</div>
                <h2 class="section-title-large">Edit Prop / Object</h2>
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

        <?php if (!empty($object['image_path'])): ?>
            <div class="form-field">
                <label>Current Preview</label>
                <div class="scene-preview" style="max-width: 260px; margin-top: 12px;">
                    <img src="<?php echo htmlspecialchars(app_asset_url($object['image_path'])); ?>" alt="<?php echo htmlspecialchars((string) $object['name']); ?>" style="display:block;width:100%;height:auto;max-height:240px;object-fit:cover;" />
                </div>
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
                    <input id="type" name="type" type="text" value="<?php echo htmlspecialchars((string) ($values['type'] ?? 'prop')); ?>" required>
                </div>
            </div>

            <div class="form-field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars((string) ($values['description'] ?? '')); ?></textarea>
            </div>

            <div class="form-field">
                <label for="object_asset">Replace Asset</label>
                <input id="object_asset" name="object_asset" type="file" accept="image/png,image/jpeg,image/gif,image/webp">
                <div class="inline-note" id="selected_object_asset">No replacement selected. Existing asset will be kept unless a new file is chosen.</div>
            </div>

            <div class="form-field checkbox-field">
                <label class="checkbox-wrap">
                    <input type="checkbox" name="is_active" value="1" <?php echo ((int) ($values['is_active'] ?? 1) === 1) ? 'checked' : ''; ?>>
                    <span>Object is active</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-btn">Update Object</button>
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
            selected.textContent = file ? 'Selected: ' + file.name : 'No replacement selected. Existing asset will be kept unless a new file is chosen.';
        });
    })();
</script>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

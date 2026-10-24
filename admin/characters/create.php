<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();
require_once __DIR__ . '/upload_helpers.php';

$pdo = getDatabaseConnection();

$errors = [];
$values = [
    'name' => '',
    'role' => '',
    'description' => '',
    'emotion_profile' => '',
    'visual_notes' => '',
    'image_path' => '',
    'is_active' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['name'] = trim((string) ($_POST['name'] ?? ''));
    $values['role'] = trim((string) ($_POST['role'] ?? ''));
    $values['description'] = trim((string) ($_POST['description'] ?? ''));
    $values['emotion_profile'] = trim((string) ($_POST['emotion_profile'] ?? ''));
    $values['visual_notes'] = trim((string) ($_POST['visual_notes'] ?? ''));
    $values['image_path'] = trim((string) ($_POST['image_path'] ?? ''));
    $values['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($values['name'] === '') {
        $errors[] = 'Character name is required.';
    }

    $uploadedAsset = null;
    if (isset($_FILES['character_asset']) && $_FILES['character_asset']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['character_asset']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The character asset upload failed. Please try again.';
        } else {
            try {
                $uploadedAsset = storeUploadedCharacterAsset($_FILES['character_asset']);
                $values['image_path'] = $uploadedAsset['file_path'];
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            INSERT INTO characters (name, role, description, emotion_profile, visual_notes, image_path, is_active)
            VALUES (:name, :role, :description, :emotion_profile, :visual_notes, :image_path, :is_active)
        ');

        $stmt->execute([
            ':name' => $values['name'],
            ':role' => $values['role'] !== '' ? $values['role'] : null,
            ':description' => $values['description'] !== '' ? $values['description'] : null,
            ':emotion_profile' => $values['emotion_profile'] !== '' ? $values['emotion_profile'] : null,
            ':visual_notes' => $values['visual_notes'] !== '' ? $values['visual_notes'] : null,
            ':image_path' => $values['image_path'] !== '' ? $values['image_path'] : null,
            ':is_active' => $values['is_active'],
        ]);

        header('Location: ' . APP_BASE_PATH . '/admin/characters/index.php?status=created');
        exit;
    }
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">NEW CHARACTER</div>
                <h2 class="section-title-large">Create Character</h2>
            </div>
            <a href="<?php echo APP_BASE_PATH; ?>/admin/characters/index.php" class="secondary-link-btn">Back to Library</a>
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
                    <label for="name">Character Name</label>
                    <input id="name" name="name" type="text" value="<?php echo htmlspecialchars((string) ($values['name'] ?? '')); ?>" required>
                </div>

                <div class="form-field">
                    <label for="role">Role</label>
                    <input id="role" name="role" type="text" value="<?php echo htmlspecialchars((string) ($values['role'] ?? '')); ?>" placeholder="Lead, Mentor, Villain...">
                </div>
            </div>

            <div class="form-field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5" placeholder="Describe the character's purpose, personality, and place in the story."><?php echo htmlspecialchars((string) ($values['description'] ?? '')); ?></textarea>
            </div>

            <div class="form-grid two-col">
                <div class="form-field">
                    <label for="emotion_profile">Emotion Profile</label>
                    <textarea id="emotion_profile" name="emotion_profile" rows="4" placeholder="Outline emotional tone, triggers, and behavioral signals."><?php echo htmlspecialchars((string) ($values['emotion_profile'] ?? '')); ?></textarea>
                </div>

                <div class="form-field">
                    <label for="visual_notes">Visual Notes</label>
                    <textarea id="visual_notes" name="visual_notes" rows="4" placeholder="Add visual style, costume, gesture, or design notes."><?php echo htmlspecialchars((string) ($values['visual_notes'] ?? '')); ?></textarea>
                </div>
            </div>

            <div class="form-field">
                <label for="character_asset">Character Asset</label>
                <input id="character_asset" name="character_asset" type="file" accept="image/png,image/jpeg,image/gif,image/webp">
                <div class="inline-note" id="selected_character_asset">No file selected</div>
            </div>

            <div class="form-field checkbox-field">
                <label class="checkbox-wrap">
                    <input type="checkbox" name="is_active" value="1" <?php echo ((int) ($values['is_active'] ?? 1) === 1) ? 'checked' : ''; ?>>
                    <span>Character is active</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-btn">Save Character</button>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/characters/index.php" class="secondary-link-btn">Cancel</a>
            </div>
        </form>
    </div>
</section>

<script>
    (function () {
        const input = document.getElementById('character_asset');
        const selected = document.getElementById('selected_character_asset');
        if (!input || !selected) return;

        input.addEventListener('change', function () {
            const file = this.files && this.files[0];
            selected.textContent = file ? 'Selected: ' + file.name : 'No file selected';
        });
    })();
</script>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

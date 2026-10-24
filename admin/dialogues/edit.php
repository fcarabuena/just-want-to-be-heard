<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

$pdo = getDatabaseConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    header('Location: ' . APP_BASE_PATH . '/admin/dialogues/index.php?status=invalid');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM dialogues WHERE dialogue_id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$dialogue = $stmt->fetch();

if (!$dialogue) {
    header('Location: ' . APP_BASE_PATH . '/admin/dialogues/index.php?status=missing');
    exit;
}

$errors = [];
$values = [
    'scene_id' => (string) $dialogue['scene_id'],
    'interactive_element_id' => $dialogue['interactive_element_id'] !== null ? (string) $dialogue['interactive_element_id'] : '',
    'speaker_name' => $dialogue['speaker_name'],
    'speaker_role' => $dialogue['speaker_role'] ?? '',
    'dialogue_text' => $dialogue['dialogue_text'],
    'display_type' => $dialogue['display_type'] ?? 'panel',
    'is_primary' => (int) $dialogue['is_primary'],
    'is_active' => (int) $dialogue['is_active'],
];

$scenes = $pdo->query('SELECT scene_id, title, scene_number FROM scenes ORDER BY scene_number ASC, title ASC')->fetchAll();
$elements = $pdo->query('SELECT interactive_element_id, element_name, scene_id FROM interactive_elements ORDER BY display_order ASC, element_name ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['scene_id'] = trim((string) ($_POST['scene_id'] ?? $dialogue['scene_id']));
    $values['interactive_element_id'] = trim((string) ($_POST['interactive_element_id'] ?? ($dialogue['interactive_element_id'] !== null ? (string) $dialogue['interactive_element_id'] : '')));
    $values['speaker_name'] = trim((string) ($_POST['speaker_name'] ?? $dialogue['speaker_name']));
    $values['speaker_role'] = trim((string) ($_POST['speaker_role'] ?? ($dialogue['speaker_role'] ?? '')));
    $values['dialogue_text'] = trim((string) ($_POST['dialogue_text'] ?? $dialogue['dialogue_text']));
    $values['display_type'] = trim((string) ($_POST['display_type'] ?? ($dialogue['display_type'] ?? 'panel')));
    $values['is_primary'] = isset($_POST['is_primary']) ? 1 : 0;
    $values['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($values['scene_id'] === '' || !ctype_digit((string) $values['scene_id'])) {
        $errors[] = 'Please select a valid scene.';
    }

    if ($values['speaker_name'] === '') {
        $errors[] = 'Speaker name is required.';
    }

    if ($values['dialogue_text'] === '') {
        $errors[] = 'Dialogue text is required.';
    }

    $interactiveElementId = $values['interactive_element_id'] !== '' ? (int) $values['interactive_element_id'] : null;
    if ($interactiveElementId !== null && $interactiveElementId <= 0) {
        $errors[] = 'The interactive element selection is invalid.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            UPDATE dialogues
            SET scene_id = :scene_id,
                interactive_element_id = :interactive_element_id,
                speaker_name = :speaker_name,
                speaker_role = :speaker_role,
                dialogue_text = :dialogue_text,
                display_type = :display_type,
                is_primary = :is_primary,
                is_active = :is_active,
                updated_at = CURRENT_TIMESTAMP
            WHERE dialogue_id = :id
        ');

        $stmt->execute([
            ':scene_id' => (int) $values['scene_id'],
            ':interactive_element_id' => $interactiveElementId,
            ':speaker_name' => $values['speaker_name'],
            ':speaker_role' => $values['speaker_role'] !== '' ? $values['speaker_role'] : null,
            ':dialogue_text' => $values['dialogue_text'],
            ':display_type' => $values['display_type'] !== '' ? $values['display_type'] : 'panel',
            ':is_primary' => $values['is_primary'],
            ':is_active' => $values['is_active'],
            ':id' => $id,
        ]);

        header('Location: ' . APP_BASE_PATH . '/admin/dialogues/index.php?status=updated');
        exit;
    }
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">EDIT DIALOGUE</div>
                <h2 class="section-title-large">Edit Dialogue</h2>
            </div>
            <a href="<?php echo APP_BASE_PATH; ?>/admin/dialogues/index.php" class="secondary-link-btn">Back to Library</a>
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
                    <label for="scene_id">Scene</label>
                    <select id="scene_id" name="scene_id" required>
                        <option value="">Select a scene</option>
                        <?php foreach ($scenes as $scene): ?>
                            <option value="<?php echo (int) $scene['scene_id']; ?>" <?php echo ((string) ($values['scene_id'] ?? '') === (string) $scene['scene_id']) ? 'selected' : ''; ?>>
                                Scene <?php echo (int) ($scene['scene_number'] ?? 0); ?> - <?php echo htmlspecialchars((string) $scene['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="interactive_element_id">Associated Element</label>
                    <select id="interactive_element_id" name="interactive_element_id">
                        <option value="">None</option>
                        <?php foreach ($elements as $element): ?>
                            <option value="<?php echo (int) $element['interactive_element_id']; ?>" <?php echo ((string) ($values['interactive_element_id'] ?? '') === (string) $element['interactive_element_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) ($element['element_name'] ?? 'Untitled')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid two-col">
                <div class="form-field">
                    <label for="speaker_name">Speaker Name</label>
                    <input id="speaker_name" name="speaker_name" type="text" value="<?php echo htmlspecialchars((string) ($values['speaker_name'] ?? '')); ?>" required>
                </div>

                <div class="form-field">
                    <label for="speaker_role">Speaker Role</label>
                    <input id="speaker_role" name="speaker_role" type="text" value="<?php echo htmlspecialchars((string) ($values['speaker_role'] ?? '')); ?>" placeholder="Narrator, protagonist, mentor...">
                </div>
            </div>

            <div class="form-grid two-col">
                <div class="form-field">
                    <label for="display_type">Display Type</label>
                    <select id="display_type" name="display_type">
                        <option value="panel" <?php echo ((string) ($values['display_type'] ?? 'panel') === 'panel') ? 'selected' : ''; ?>>Panel</option>
                        <option value="modal" <?php echo ((string) ($values['display_type'] ?? 'panel') === 'modal') ? 'selected' : ''; ?>>Modal</option>
                        <option value="caption" <?php echo ((string) ($values['display_type'] ?? 'panel') === 'caption') ? 'selected' : ''; ?>>Caption</option>
                    </select>
                </div>

                <div class="form-field checkbox-field" style="padding-top: 28px;">
                    <label class="checkbox-wrap">
                        <input type="checkbox" name="is_primary" value="1" <?php echo ((int) ($values['is_primary'] ?? 0) === 1) ? 'checked' : ''; ?>>
                        <span>Primary dialogue</span>
                    </label>
                </div>
            </div>

            <div class="form-field">
                <label for="dialogue_text">Dialogue Text</label>
                <textarea id="dialogue_text" name="dialogue_text" rows="6" required><?php echo htmlspecialchars((string) ($values['dialogue_text'] ?? '')); ?></textarea>
            </div>

            <div class="form-field checkbox-field">
                <label class="checkbox-wrap">
                    <input type="checkbox" name="is_active" value="1" <?php echo ((int) ($values['is_active'] ?? 1) === 1) ? 'checked' : ''; ?>>
                    <span>Dialogue is active</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-btn">Update Dialogue</button>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/dialogues/index.php" class="secondary-link-btn">Cancel</a>
            </div>
        </form>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

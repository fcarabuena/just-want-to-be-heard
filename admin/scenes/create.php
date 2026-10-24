<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

$pdo = getDatabaseConnection();

$errors = [];
$values = [
    'scene_number' => '',
    'title' => '',
    'description' => '',
    'display_order' => 0,
    'status' => 'draft',
    'is_interactive' => 1,
    'ending_type' => 'none',
    'choice_prompt' => '',
    'video_id' => '',
    'next_scene_id' => '',
];

$videoOptions = $pdo->query("SELECT video_id, title, filename FROM videos WHERE is_deleted = 0 ORDER BY title ASC")->fetchAll();
$sceneOptions = $pdo->query("SELECT scene_id, scene_number, title FROM scenes ORDER BY display_order ASC, scene_number ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['scene_number'] = trim((string) ($_POST['scene_number'] ?? ''));
    $values['title'] = trim((string) ($_POST['title'] ?? ''));
    $values['description'] = trim((string) ($_POST['description'] ?? ''));
    $values['display_order'] = trim((string) ($_POST['display_order'] ?? '0'));
    $values['status'] = trim((string) ($_POST['status'] ?? 'draft'));
    $values['is_interactive'] = isset($_POST['is_interactive']) ? 1 : 0;
    $values['ending_type'] = in_array($_POST['ending_type'] ?? 'none', ['none', 'good', 'bad', 'neutral'], true) ? $_POST['ending_type'] : 'none';
    $values['choice_prompt'] = trim((string) ($_POST['choice_prompt'] ?? ''));
    $values['video_id'] = trim((string) ($_POST['video_id'] ?? ''));
    $values['next_scene_id'] = trim((string) ($_POST['next_scene_id'] ?? ''));

    $sceneNumber = filter_var($values['scene_number'], FILTER_VALIDATE_INT);
    $displayOrder = filter_var($values['display_order'], FILTER_VALIDATE_INT);
    $videoId = $values['video_id'] !== '' ? filter_var($values['video_id'], FILTER_VALIDATE_INT) : null;
    $nextSceneId = $values['next_scene_id'] !== '' ? filter_var($values['next_scene_id'], FILTER_VALIDATE_INT) : null;

    if ($sceneNumber === false || $sceneNumber < 1) {
        $errors[] = 'Please provide a valid scene number.';
    }

    if ($values['title'] === '') {
        $errors[] = 'Scene title is required.';
    }

    if ($displayOrder === false) {
        $errors[] = 'Display order must be a valid number.';
    }

    $allowedStatuses = ['draft', 'published', 'archived'];
    if (!in_array($values['status'], $allowedStatuses, true)) {
        $errors[] = 'Please select a valid status.';
    }

    if ($videoId !== null && $videoId <= 0) {
        $errors[] = 'Please select a valid animation video.';
    }

    if ($nextSceneId !== null && $nextSceneId <= 0) {
        $errors[] = 'Please select a valid next scene.';
    }

    if (empty($errors)) {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $values['title']), '-'));
        if ($slug === '') {
            $slug = 'scene-' . $sceneNumber;
        }

        $checkSql = 'SELECT scene_id FROM scenes WHERE scene_number = :scene_number OR slug = :slug LIMIT 1';
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([
            ':scene_number' => $sceneNumber,
            ':slug' => $slug,
        ]);

        if ($checkStmt->fetch()) {
            $errors[] = 'A scene with this number or slug already exists.';
        }
    }

    if (empty($errors)) {
        $insertSql = "
            INSERT INTO scenes (scene_number, title, slug, description, video_id, next_scene_id, is_interactive, status, display_order, ending_type, choice_prompt)
            VALUES (:scene_number, :title, :slug, :description, :video_id, :next_scene_id, :is_interactive, :status, :display_order, :ending_type, :choice_prompt)
        ";

        $stmt = $pdo->prepare($insertSql);
        $stmt->execute([
            ':scene_number' => $sceneNumber,
            ':title' => $values['title'],
            ':slug' => $slug,
            ':description' => $values['description'] !== '' ? $values['description'] : null,
            ':video_id' => $videoId,
            ':next_scene_id' => $nextSceneId,
            ':is_interactive' => $values['is_interactive'],
            ':status' => $values['status'],
            ':display_order' => $displayOrder,
            ':ending_type' => $values['ending_type'],
            ':choice_prompt' => $values['choice_prompt'] !== '' ? $values['choice_prompt'] : null,
        ]);

        header('Location: ' . APP_BASE_PATH . '/admin/scenes/index.php?status=created');
        exit;
    }
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">NEW SCENE</div>
                <h2 class="section-title-large">Create Scene</h2>
            </div>
            <a href="<?php echo APP_BASE_PATH; ?>/admin/scenes/index.php" class="secondary-link-btn">Back to Library</a>
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
                    <label for="scene_number">Scene Number</label>
                    <input id="scene_number" name="scene_number" type="number" min="1" value="<?php echo htmlspecialchars((string) ($values['scene_number'] ?? '')); ?>" required>
                </div>

                <div class="form-field">
                    <label for="display_order">Display Order</label>
                    <input id="display_order" name="display_order" type="number" min="0" value="<?php echo htmlspecialchars((string) ($values['display_order'] ?? 0)); ?>" required>
                </div>
            </div>

            <div class="form-field">
                <label for="title">Title</label>
                <input id="title" name="title" type="text" maxlength="255" value="<?php echo htmlspecialchars((string) ($values['title'] ?? '')); ?>" required>
            </div>

            <div class="form-field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5" placeholder="Describe the emotional beat, location, and production intent for this scene."><?php echo htmlspecialchars((string) ($values['description'] ?? '')); ?></textarea>
            </div>

            <div class="form-grid three-col">
                <div class="form-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="draft" <?php echo (($values['status'] ?? 'draft') === 'draft') ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo (($values['status'] ?? 'draft') === 'published') ? 'selected' : ''; ?>>Published</option>
                        <option value="archived" <?php echo (($values['status'] ?? 'draft') === 'archived') ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="video_id">Assigned Animation</label>
                    <select id="video_id" name="video_id">
                        <option value="">No video assigned</option>
                        <?php foreach ($videoOptions as $video): ?>
                            <option value="<?php echo (int) $video['video_id']; ?>" <?php echo ((string) ($values['video_id'] ?? '') === (string) $video['video_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($video['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="next_scene_id">Next Scene</label>
                    <select id="next_scene_id" name="next_scene_id">
                        <option value="">No next scene</option>
                        <?php foreach ($sceneOptions as $scene): ?>
                            <option value="<?php echo (int) $scene['scene_id']; ?>" <?php echo ((string) ($values['next_scene_id'] ?? '') === (string) $scene['scene_id']) ? 'selected' : ''; ?>>
                                Scene <?php echo (int) $scene['scene_number']; ?> - <?php echo htmlspecialchars($scene['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid two-col">
                <div class="form-field">
                    <label for="ending_type">Ending</label>
                    <select id="ending_type" name="ending_type">
                        <?php foreach (['none' => 'Not an ending', 'good' => 'Good Ending', 'bad' => 'Bad Ending', 'neutral' => 'Neutral Ending'] as $endingKey => $endingLabel): ?>
                            <option value="<?php echo $endingKey; ?>" <?php echo (($values['ending_type'] ?? 'none') === $endingKey) ? 'selected' : ''; ?>><?php echo $endingLabel; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="inline-note">When this scene finishes, the viewer sees the ending screen with their journey recap.</small>
                </div>

                <div class="form-field">
                    <label for="choice_prompt">Choice Question</label>
                    <input id="choice_prompt" name="choice_prompt" type="text" maxlength="255" value="<?php echo htmlspecialchars((string) ($values['choice_prompt'] ?? '')); ?>" placeholder="What should Riley do?">
                    <small class="inline-note">Shown above this scene's choices (add them under Story Choices).</small>
                </div>
            </div>

            <div class="form-field checkbox-field">
                <label class="checkbox-wrap">
                    <input type="checkbox" name="is_interactive" value="1" <?php echo ((int) ($values['is_interactive'] ?? 1) === 1) ? 'checked' : ''; ?>>
                    <span>Interactive Scene</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-btn">Save Scene</button>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/scenes/index.php" class="secondary-link-btn">Cancel</a>
            </div>
        </form>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

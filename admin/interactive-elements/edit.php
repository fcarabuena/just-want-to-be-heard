<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

$pdo = getDatabaseConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    header('Location: ' . APP_BASE_PATH . '/admin/interactive-elements/index.php?status=invalid');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM interactive_elements WHERE interactive_element_id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$element = $stmt->fetch();

if (!$element) {
    header('Location: ' . APP_BASE_PATH . '/admin/interactive-elements/index.php?status=missing');
    exit;
}

$scenes = $pdo->query('SELECT s.scene_id, s.title, s.scene_number, v.file_path AS video_path FROM scenes s LEFT JOIN videos v ON v.video_id = s.video_id AND v.is_deleted = 0 ORDER BY s.scene_number ASC, s.title ASC')->fetchAll();
$sceneVideos = [];
foreach ($scenes as $sceneRow) {
    if (!empty($sceneRow['video_path'])) {
        $sceneVideos[(string) $sceneRow['scene_id']] = app_asset_url($sceneRow['video_path']);
    }
}
$characters = $pdo->query('SELECT character_id, name FROM characters ORDER BY name ASC')->fetchAll();
$objects = $pdo->query('SELECT object_id, name FROM objects ORDER BY name ASC')->fetchAll();

$errors = [];
$values = [
    'scene_id' => (string) $element['scene_id'],
    'character_id' => $element['character_id'] !== null ? (string) $element['character_id'] : '',
    'object_id' => $element['object_id'] !== null ? (string) $element['object_id'] : '',
    'element_name' => $element['element_name'],
    'element_type' => $element['element_type'] ?? 'hotspot',
    'label' => $element['label'] ?? '',
    'position_x' => $element['position_x'] !== null ? (string) $element['position_x'] : '',
    'position_y' => $element['position_y'] !== null ? (string) $element['position_y'] : '',
    'width' => $element['width'] !== null ? (string) $element['width'] : '',
    'height' => $element['height'] !== null ? (string) $element['height'] : '',
    'interaction_behavior' => $element['interaction_behavior'] ?? 'info_popup',
    'is_active' => (int) $element['is_active'],
    'display_order' => (string) ($element['display_order'] ?? 0),
    'insight_title' => $element['insight_title'] ?? '',
    'insight_text' => $element['insight_text'] ?? '',
    'appear_at_seconds' => $element['appear_at_seconds'] !== null ? (string) (float) $element['appear_at_seconds'] : '',
    'pauses_video' => (int) ($element['pauses_video'] ?? 1),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['scene_id'] = trim((string) ($_POST['scene_id'] ?? $element['scene_id']));
    $values['character_id'] = trim((string) ($_POST['character_id'] ?? ($element['character_id'] !== null ? (string) $element['character_id'] : '')));
    $values['object_id'] = trim((string) ($_POST['object_id'] ?? ($element['object_id'] !== null ? (string) $element['object_id'] : '')));
    $values['element_name'] = trim((string) ($_POST['element_name'] ?? $element['element_name']));
    $values['element_type'] = trim((string) ($_POST['element_type'] ?? ($element['element_type'] ?? 'hotspot')));
    $values['label'] = trim((string) ($_POST['label'] ?? ($element['label'] ?? '')));
    $values['position_x'] = trim((string) ($_POST['position_x'] ?? ($element['position_x'] !== null ? (string) $element['position_x'] : '')));
    $values['position_y'] = trim((string) ($_POST['position_y'] ?? ($element['position_y'] !== null ? (string) $element['position_y'] : '')));
    $values['width'] = trim((string) ($_POST['width'] ?? ($element['width'] !== null ? (string) $element['width'] : '')));
    $values['height'] = trim((string) ($_POST['height'] ?? ($element['height'] !== null ? (string) $element['height'] : '')));
    $values['interaction_behavior'] = trim((string) ($_POST['interaction_behavior'] ?? ($element['interaction_behavior'] ?? 'info_popup')));
    $values['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    $values['insight_title'] = trim((string) ($_POST['insight_title'] ?? ''));
    $values['insight_text'] = trim((string) ($_POST['insight_text'] ?? ''));
    $values['appear_at_seconds'] = trim((string) ($_POST['appear_at_seconds'] ?? ''));
    $values['pauses_video'] = isset($_POST['pauses_video']) ? 1 : 0;
    $values['display_order'] = trim((string) ($_POST['display_order'] ?? ($element['display_order'] ?? '0')));

    if ($values['scene_id'] === '' || !ctype_digit((string) $values['scene_id'])) {
        $errors[] = 'Please select a valid scene.';
    }

    $appearAt = $values['appear_at_seconds'] !== '' ? filter_var($values['appear_at_seconds'], FILTER_VALIDATE_FLOAT) : null;
    if ($values['appear_at_seconds'] !== '' && ($appearAt === false || $appearAt < 0)) {
        $errors[] = 'Appears At must be a number of seconds (or empty to show the hotspot when the scene ends).';
    }

    if ($values['element_name'] === '') {
        $errors[] = 'Interactive element name is required.';
    }

    $sceneId = $values['scene_id'] !== '' ? (int) $values['scene_id'] : null;
    $characterId = $values['character_id'] !== '' ? (int) $values['character_id'] : null;
    $objectId = $values['object_id'] !== '' ? (int) $values['object_id'] : null;
    $displayOrder = $values['display_order'] !== '' ? filter_var($values['display_order'], FILTER_VALIDATE_INT) : 0;
    if ($values['display_order'] !== '' && ($displayOrder === false || $displayOrder < 0)) {
        $errors[] = 'Display order must be a valid non-negative integer.';
    }

    if ($characterId !== null && $characterId <= 0) { $errors[] = 'Character selection is invalid.'; }
    if ($objectId !== null && $objectId <= 0) { $errors[] = 'Object selection is invalid.'; }
    if ($characterId !== null && $objectId !== null && $characterId > 0 && $objectId > 0) {
        $errors[] = 'Choose either a character or an object association, not both.';
    }

    $positionX = $values['position_x'] !== '' ? filter_var($values['position_x'], FILTER_VALIDATE_INT) : null;
    $positionY = $values['position_y'] !== '' ? filter_var($values['position_y'], FILTER_VALIDATE_INT) : null;
    $widthValue = $values['width'] !== '' ? filter_var($values['width'], FILTER_VALIDATE_INT) : null;
    $heightValue = $values['height'] !== '' ? filter_var($values['height'], FILTER_VALIDATE_INT) : null;

    if ($values['position_x'] !== '' && ($positionX === false || $positionX < 0)) { $errors[] = 'Position X must be a valid non-negative integer.'; }
    if ($values['position_y'] !== '' && ($positionY === false || $positionY < 0)) { $errors[] = 'Position Y must be a valid non-negative integer.'; }
    if ($values['width'] !== '' && ($widthValue === false || $widthValue < 0)) { $errors[] = 'Width must be a valid non-negative integer.'; }
    if ($values['height'] !== '' && ($heightValue === false || $heightValue < 0)) { $errors[] = 'Height must be a valid non-negative integer.'; }

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            UPDATE interactive_elements
            SET scene_id = :scene_id,
                character_id = :character_id,
                object_id = :object_id,
                element_name = :element_name,
                element_type = :element_type,
                label = :label,
                position_x = :position_x,
                position_y = :position_y,
                width = :width,
                height = :height,
                interaction_behavior = :interaction_behavior,
                is_active = :is_active,
                display_order = :display_order,
                insight_title = :insight_title,
                insight_text = :insight_text,
                appear_at_seconds = :appear_at_seconds,
                pauses_video = :pauses_video,
                updated_at = CURRENT_TIMESTAMP
            WHERE interactive_element_id = :id
        ');

        $stmt->execute([
            ':scene_id' => $sceneId,
            ':character_id' => $characterId,
            ':object_id' => $objectId,
            ':element_name' => $values['element_name'],
            ':element_type' => in_array($values['element_type'], ['insight', 'thought', 'red_flag'], true) ? $values['element_type'] : 'insight',
            ':label' => $values['label'] !== '' ? $values['label'] : null,
            ':position_x' => $positionX,
            ':position_y' => $positionY,
            ':width' => $widthValue,
            ':height' => $heightValue,
            ':interaction_behavior' => $values['interaction_behavior'] !== '' ? $values['interaction_behavior'] : 'info_popup',
            ':is_active' => $values['is_active'],
            ':display_order' => $displayOrder !== false ? (int) $displayOrder : 0,
            ':insight_title' => $values['insight_title'] !== '' ? $values['insight_title'] : null,
            ':insight_text' => $values['insight_text'] !== '' ? $values['insight_text'] : null,
            ':appear_at_seconds' => $appearAt !== null && $appearAt !== false ? $appearAt : null,
            ':pauses_video' => $values['pauses_video'],
            ':id' => $id,
        ]);

        header('Location: ' . APP_BASE_PATH . '/admin/interactive-elements/index.php?status=updated');
        exit;
    }
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">EDIT INTERACTIVE ELEMENT</div>
                <h2 class="section-title-large">Edit Interactive Element</h2>
            </div>
            <a href="<?php echo APP_BASE_PATH; ?>/admin/interactive-elements/index.php" class="secondary-link-btn">Back to Library</a>
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
                    <label for="element_name">Element Name</label>
                    <input id="element_name" name="element_name" type="text" value="<?php echo htmlspecialchars((string) ($values['element_name'] ?? '')); ?>" required>
                </div>
            </div>

            <div class="form-grid two-col">
                <div class="form-field">
                    <label for="element_type">Element Type</label>
                    <select id="element_type" name="element_type">
                        <?php foreach (['insight' => 'Look closer (insight)', 'thought' => "Riley's thought", 'red_flag' => 'Red flag (counts toward the red-flag score)'] as $typeKey => $typeLabel): ?>
                            <option value="<?php echo $typeKey; ?>" <?php echo (($values['element_type'] ?? 'insight') === $typeKey) ? 'selected' : ''; ?>><?php echo htmlspecialchars($typeLabel); ?></option>
                        <?php endforeach; ?>
                    </select>">
                </div>

                <div class="form-field">
                    <label for="interaction_behavior">Interaction Behavior</label>
                    <input id="interaction_behavior" name="interaction_behavior" type="text" value="<?php echo htmlspecialchars((string) ($values['interaction_behavior'] ?? 'info_popup')); ?>">
                </div>
            </div>

            <div class="form-grid two-col">
                <div class="form-field">
                    <label for="character_id">Character Association</label>
                    <select id="character_id" name="character_id">
                        <option value="">None</option>
                        <?php foreach ($characters as $character): ?>
                            <option value="<?php echo (int) $character['character_id']; ?>" <?php echo ((string) ($values['character_id'] ?? '') === (string) $character['character_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) $character['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="object_id">Object Association</label>
                    <select id="object_id" name="object_id">
                        <option value="">None</option>
                        <?php foreach ($objects as $object): ?>
                            <option value="<?php echo (int) $object['object_id']; ?>" <?php echo ((string) ($values['object_id'] ?? '') === (string) $object['object_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) $object['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid four-col">
                <div class="form-field">
                    <label for="label">Label</label>
                    <input id="label" name="label" type="text" value="<?php echo htmlspecialchars((string) ($values['label'] ?? '')); ?>">
                </div>
                <div class="form-field">
                    <label for="position_x">X (px)</label>
                    <input id="position_x" name="position_x" type="number" min="0" value="<?php echo htmlspecialchars((string) ($values['position_x'] ?? '')); ?>">
                </div>
                <div class="form-field">
                    <label for="position_y">Y (px)</label>
                    <input id="position_y" name="position_y" type="number" min="0" value="<?php echo htmlspecialchars((string) ($values['position_y'] ?? '')); ?>">
                </div>
                <div class="form-field">
                    <label for="display_order">Order</label>
                    <input id="display_order" name="display_order" type="number" min="0" value="<?php echo htmlspecialchars((string) ($values['display_order'] ?? '0')); ?>">
                </div>
            </div>

            <div class="form-grid two-col">
                <div class="form-field">
                    <label for="width">Width</label>
                    <input id="width" name="width" type="number" min="0" value="<?php echo htmlspecialchars((string) ($values['width'] ?? '')); ?>">
                </div>
                <div class="form-field">
                    <label for="height">Height</label>
                    <input id="height" name="height" type="number" min="0" value="<?php echo htmlspecialchars((string) ($values['height'] ?? '')); ?>">
                </div>
            </div>

            <div class="form-grid two-col">
                <div class="form-field">
                    <label for="insight_title">Pop-up Title</label>
                    <input id="insight_title" name="insight_title" type="text" maxlength="255" value="<?php echo htmlspecialchars((string) ($values['insight_title'] ?? '')); ?>" placeholder="Red flag: Keeping secrets">
                </div>
                <div class="form-field">
                    <label for="appear_at_seconds">Appears At (seconds)</label>
                    <input id="appear_at_seconds" name="appear_at_seconds" type="number" min="0" step="0.1" value="<?php echo htmlspecialchars((string) ($values['appear_at_seconds'] ?? '')); ?>" placeholder="Leave empty = when the scene ends">
                </div>
            </div>

            <div class="form-field">
                <label for="insight_text">Pop-up Text (what the viewer learns when they click)</label>
                <textarea id="insight_text" name="insight_text" rows="4" placeholder="Asking you to keep your chats secret from your parents is a common grooming tactic. Safe people do not ask you to hide things from the adults who care about you."><?php echo htmlspecialchars((string) ($values['insight_text'] ?? '')); ?></textarea>
            </div>

            <div class="form-field checkbox-field">
                <label class="checkbox-wrap">
                    <input type="checkbox" name="pauses_video" value="1" <?php echo ((int) ($values['pauses_video'] ?? 1) === 1) ? 'checked' : ''; ?>>
                    <span>Pause the animation when this hotspot appears (recommended)</span>
                </label>
            </div>

            <div class="form-field">
                <label>Place the Hotspot on the Frame</label>
                <div class="placement-tool" id="placementTool" data-videos="<?php echo htmlspecialchars(json_encode($sceneVideos)); ?>">
                    <video id="placementVideo" muted playsinline preload="metadata"></video>
                    <div class="placement-surface" id="placementSurface">
                        <div class="placement-box" id="placementBox" hidden></div>
                    </div>
                    <p class="placement-empty" id="placementEmpty">Pick a scene that has a video to place the hotspot visually.</p>
                </div>
                <div class="placement-controls">
                    <input type="range" id="placementScrub" min="0" max="100" step="0.1" value="0" aria-label="Scrub through the scene video">
                    <span id="placementTime">0.0s</span>
                    <button type="button" class="secondary-link-btn" id="placementUseTime">Use this time for "Appears At"</button>
                </div>
                <small class="inline-note">Scrub to the right moment, then drag on the frame to draw the clickable area. X, Y, Width and Height fill in automatically, measured in 1920 x 1080 frame pixels.</small>
            </div>

            <div class="form-field checkbox-field">
                <label class="checkbox-wrap">
                    <input type="checkbox" name="is_active" value="1" <?php echo ((int) ($values['is_active'] ?? 1) === 1) ? 'checked' : ''; ?>>
                    <span>Element is active</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-btn">Update Interactive Element</button>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/interactive-elements/index.php" class="secondary-link-btn">Cancel</a>
            </div>
        </form>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

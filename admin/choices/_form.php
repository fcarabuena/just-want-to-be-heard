<?php
/** Shared by create.php and edit.php. Expects $values, $errors, $scenes, $formTitle, $submitLabel. */

function read_choice_post(): array
{
    return [
        'scene_id' => trim((string) ($_POST['scene_id'] ?? '')),
        'choice_text' => trim((string) ($_POST['choice_text'] ?? '')),
        'target_scene_id' => trim((string) ($_POST['target_scene_id'] ?? '')),
        'feedback_text' => trim((string) ($_POST['feedback_text'] ?? '')),
        'display_order' => trim((string) ($_POST['display_order'] ?? '0')),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
}

function validate_choice(array $values): array
{
    $errors = [];
    if (!ctype_digit($values['scene_id'])) {
        $errors[] = 'Please select the scene where this choice appears.';
    }
    if ($values['choice_text'] === '') {
        $errors[] = 'Choice text is required.';
    }
    if ($values['target_scene_id'] !== '' && !ctype_digit($values['target_scene_id'])) {
        $errors[] = 'Please select a valid destination scene.';
    }
    if ($values['target_scene_id'] !== '' && $values['target_scene_id'] === $values['scene_id']) {
        $errors[] = 'A choice cannot lead back to the same scene.';
    }
    if (!ctype_digit($values['display_order'])) {
        $errors[] = 'Order must be a non-negative whole number.';
    }
    return $errors;
}

function choice_params(array $values): array
{
    return [
        ':scene_id' => (int) $values['scene_id'],
        ':choice_text' => $values['choice_text'],
        ':target_scene_id' => $values['target_scene_id'] !== '' ? (int) $values['target_scene_id'] : null,
        ':feedback_text' => $values['feedback_text'] !== '' ? $values['feedback_text'] : null,
        ':display_order' => (int) $values['display_order'],
        ':is_active' => $values['is_active'],
    ];
}

function render_choice_form(array $values, array $errors, array $scenes, string $formTitle, string $submitLabel): void
{
    ?>
<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">STORY CHOICE</div>
                <h2 class="section-title-large"><?php echo htmlspecialchars($formTitle); ?></h2>
            </div>
            <a href="<?php echo APP_BASE_PATH; ?>/admin/choices/index.php" class="secondary-link-btn">Back to Choices</a>
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
                    <label for="scene_id">Appears After Scene</label>
                    <select id="scene_id" name="scene_id" required>
                        <option value="">Select a scene</option>
                        <?php foreach ($scenes as $scene): ?>
                            <option value="<?php echo (int) $scene['scene_id']; ?>" <?php echo $values['scene_id'] === (string) $scene['scene_id'] ? 'selected' : ''; ?>>
                                Scene <?php echo (int) $scene['scene_number']; ?> - <?php echo htmlspecialchars((string) $scene['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="target_scene_id">Leads To Scene</label>
                    <select id="target_scene_id" name="target_scene_id">
                        <option value="">Next scene in order</option>
                        <?php foreach ($scenes as $scene): ?>
                            <option value="<?php echo (int) $scene['scene_id']; ?>" <?php echo $values['target_scene_id'] === (string) $scene['scene_id'] ? 'selected' : ''; ?>>
                                Scene <?php echo (int) $scene['scene_number']; ?> - <?php echo htmlspecialchars((string) $scene['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-field">
                <label for="choice_text">Choice Text (what the button says)</label>
                <input id="choice_text" name="choice_text" type="text" maxlength="255" value="<?php echo htmlspecialchars($values['choice_text']); ?>" placeholder="Tell Mom about the messages" required>
            </div>

            <div class="form-field">
                <label for="feedback_text">Feedback After Choosing (optional)</label>
                <input id="feedback_text" name="feedback_text" type="text" maxlength="500" value="<?php echo htmlspecialchars($values['feedback_text']); ?>" placeholder="Riley takes a deep breath and opens the door.">
                <small class="inline-note">A short line shown for a moment before the next scene plays.</small>
            </div>

            <div class="form-grid two-col">
                <div class="form-field">
                    <label for="display_order">Order</label>
                    <input id="display_order" name="display_order" type="number" min="0" value="<?php echo htmlspecialchars($values['display_order']); ?>">
                </div>
                <div class="form-field checkbox-field">
                    <label class="checkbox-wrap">
                        <input type="checkbox" name="is_active" value="1" <?php echo (int) $values['is_active'] === 1 ? 'checked' : ''; ?>>
                        <span>Show this choice</span>
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-btn"><?php echo htmlspecialchars($submitLabel); ?></button>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/choices/index.php" class="secondary-link-btn">Cancel</a>
            </div>
        </form>
    </div>
</section>
    <?php
}

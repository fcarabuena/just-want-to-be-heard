<?php
/** Shared by create.php and edit.php. */

function read_reflection_post(): array
{
    return [
        'scene_id' => trim((string) ($_POST['scene_id'] ?? '')),
        'question_text' => trim((string) ($_POST['question_text'] ?? '')),
        'question_type' => ($_POST['question_type'] ?? '') === 'open' ? 'open' : 'multiple_choice',
        'options_text' => trim((string) ($_POST['options_text'] ?? '')),
        'display_order' => trim((string) ($_POST['display_order'] ?? '0')),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
}

function validate_reflection(array $values): array
{
    $errors = [];
    if (!ctype_digit($values['scene_id'])) {
        $errors[] = 'Please select the scene this question follows.';
    }
    if ($values['question_text'] === '') {
        $errors[] = 'Question text is required.';
    }
    if ($values['question_type'] === 'multiple_choice' && $values['options_text'] === '') {
        $errors[] = 'Add at least one answer option, or switch the question to Open answer.';
    }
    if (!ctype_digit($values['display_order'])) {
        $errors[] = 'Order must be a non-negative whole number.';
    }
    return $errors;
}

function reflection_params(array $values): array
{
    return [
        ':scene_id' => (int) $values['scene_id'],
        ':question_text' => $values['question_text'],
        ':question_type' => $values['question_type'],
        ':options_text' => $values['options_text'] !== '' ? $values['options_text'] : null,
        ':display_order' => (int) $values['display_order'],
        ':is_active' => $values['is_active'],
    ];
}

function render_reflection_form(array $values, array $errors, array $scenes, string $formTitle, string $submitLabel): void
{
    ?>
<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">PAUSE AND THINK</div>
                <h2 class="section-title-large"><?php echo htmlspecialchars($formTitle); ?></h2>
            </div>
            <a href="<?php echo APP_BASE_PATH; ?>/admin/reflections/index.php" class="secondary-link-btn">Back to Questions</a>
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
                    <label for="scene_id">Asked After Scene</label>
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
                    <label for="question_type">Answer Type</label>
                    <select id="question_type" name="question_type">
                        <option value="multiple_choice" <?php echo $values['question_type'] === 'multiple_choice' ? 'selected' : ''; ?>>Multiple choice with feedback</option>
                        <option value="open" <?php echo $values['question_type'] === 'open' ? 'selected' : ''; ?>>Open answer (private, not saved to the server)</option>
                    </select>
                </div>
            </div>

            <div class="form-field">
                <label for="question_text">Question</label>
                <input id="question_text" name="question_text" type="text" maxlength="500" value="<?php echo htmlspecialchars($values['question_text']); ?>" placeholder="How do you think Riley is feeling right now?" required>
            </div>

            <div class="form-field">
                <label for="options_text">Answer Options (one per line: answer || feedback)</label>
                <textarea id="options_text" name="options_text" rows="6" placeholder="Happy someone finally listens || That makes sense. Feeling heard is a real need, and groomers know how to use it.&#10;A little uneasy || Trust that feeling. When something feels off online, it is okay to stop and ask someone."><?php echo htmlspecialchars($values['options_text']); ?></textarea>
                <small class="inline-note">Every answer gets kind feedback. Avoid marking answers as "wrong"; explain instead.</small>
            </div>

            <div class="form-grid two-col">
                <div class="form-field">
                    <label for="display_order">Order</label>
                    <input id="display_order" name="display_order" type="number" min="0" value="<?php echo htmlspecialchars($values['display_order']); ?>">
                </div>
                <div class="form-field checkbox-field">
                    <label class="checkbox-wrap">
                        <input type="checkbox" name="is_active" value="1" <?php echo (int) $values['is_active'] === 1 ? 'checked' : ''; ?>>
                        <span>Ask this question</span>
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-btn"><?php echo htmlspecialchars($submitLabel); ?></button>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/reflections/index.php" class="secondary-link-btn">Cancel</a>
            </div>
        </form>
    </div>
</section>
    <?php
}

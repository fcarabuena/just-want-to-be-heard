<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

$pdo = getDatabaseConnection();

$questions = $pdo->query(
    "SELECT q.*, s.scene_number, s.title AS scene_title
     FROM reflection_questions q
     INNER JOIN scenes s ON s.scene_id = q.scene_id
     ORDER BY s.display_order ASC, s.scene_number ASC, q.display_order ASC, q.question_id ASC"
)->fetchAll();

$statusMessages = [
    'created' => 'Question created.',
    'updated' => 'Question updated.',
    'deleted' => 'Question deleted.',
];
$status = (string) ($_GET['status'] ?? '');

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header">
            <div>
                <div class="mini-kicker">PAUSE AND THINK</div>
                <h2 class="section-title-large">Reflection Questions</h2>
                <p class="studio-subtitle">Questions appear after a scene ends, before any choices. Every answer shows a short, supportive explanation.</p>
            </div>

            <a href="<?php echo APP_BASE_PATH; ?>/admin/reflections/create.php" class="primary-btn">+ Add Question</a>
        </div>

        <?php if (isset($statusMessages[$status])): ?>
            <div class="flash-message flash-success"><?php echo htmlspecialchars($statusMessages[$status]); ?></div>
        <?php endif; ?>

        <?php if (empty($questions)): ?>
            <div class="empty-state studio-empty">
                <div class="empty-icon">✦</div>
                <h3>No reflection questions yet</h3>
                <p>Add a question to help viewers stop and think about what Riley is going through.</p>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/reflections/create.php" class="primary-btn">+ Add Question</a>
            </div>
        <?php else: ?>
            <div class="scene-library">
                <?php foreach ($questions as $question): ?>
                    <?php $optionCount = count(array_filter(array_map('trim', explode("\n", (string) $question['options_text'])))); ?>
                    <article class="scene-card">
                        <div class="scene-card-top">
                            <div class="scene-number-block">
                                <span class="scene-kicker">SCENE <?php echo (int) $question['scene_number']; ?> · <?php echo htmlspecialchars((string) $question['scene_title']); ?></span>
                                <span class="scene-number"><?php echo htmlspecialchars((string) $question['question_text']); ?></span>
                            </div>
                            <span class="status-badge status-<?php echo (int) $question['is_active'] === 1 ? 'published' : 'draft'; ?>"><?php echo (int) $question['is_active'] === 1 ? 'ACTIVE' : 'HIDDEN'; ?></span>
                        </div>

                        <div class="scene-card-body">
                            <div class="scene-meta-row">
                                <div class="meta-item">
                                    <span class="meta-label">Type</span>
                                    <span class="meta-value"><?php echo $question['question_type'] === 'open' ? 'Open answer' : 'Multiple choice'; ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Options</span>
                                    <span class="meta-value"><?php echo $question['question_type'] === 'open' ? '-' : $optionCount; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="scene-actions">
                            <a href="<?php echo APP_BASE_PATH; ?>/admin/reflections/edit.php?id=<?php echo (int) $question['question_id']; ?>" class="action-btn secondary">Edit</a>
                            <a href="<?php echo APP_BASE_PATH; ?>/admin/reflections/delete.php?id=<?php echo (int) $question['question_id']; ?>" class="action-btn danger">Delete</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

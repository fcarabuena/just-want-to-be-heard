<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

$pdo = getDatabaseConnection();

$choices = $pdo->query(
    "SELECT c.*, s.scene_number, s.title AS scene_title, t.scene_number AS target_number, t.title AS target_title
     FROM scene_choices c
     INNER JOIN scenes s ON s.scene_id = c.scene_id
     LEFT JOIN scenes t ON t.scene_id = c.target_scene_id
     ORDER BY s.display_order ASC, s.scene_number ASC, c.display_order ASC, c.choice_id ASC"
)->fetchAll();

$statusMessages = [
    'created' => 'Choice created.',
    'updated' => 'Choice updated.',
    'deleted' => 'Choice deleted.',
];
$status = (string) ($_GET['status'] ?? '');

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header">
            <div>
                <div class="mini-kicker">BRANCHING STORY</div>
                <h2 class="section-title-large">Story Choices</h2>
                <p class="studio-subtitle">Choices appear at the end of a scene. Each one sends the viewer to a different scene, which is how the story branches into the Good and Bad endings.</p>
            </div>

            <a href="<?php echo APP_BASE_PATH; ?>/admin/choices/create.php" class="primary-btn">+ Add Choice</a>
        </div>

        <?php if (isset($statusMessages[$status])): ?>
            <div class="flash-message flash-success"><?php echo htmlspecialchars($statusMessages[$status]); ?></div>
        <?php endif; ?>

        <div class="studio-summary">
            <div class="summary-chip">
                <span class="summary-label">Choices</span>
                <strong><?php echo count($choices); ?></strong>
            </div>
            <div class="summary-chip">
                <span class="summary-label">Decision Scenes</span>
                <strong><?php echo count(array_unique(array_map(static fn ($c) => $c['scene_id'], $choices))); ?></strong>
            </div>
        </div>

        <?php if (empty($choices)): ?>
            <div class="empty-state studio-empty">
                <div class="empty-icon">✦</div>
                <h3>No choices yet</h3>
                <p>Add two or more choices to a scene to let the viewer decide what Riley does next.</p>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/choices/create.php" class="primary-btn">+ Add Choice</a>
            </div>
        <?php else: ?>
            <div class="scene-library">
                <?php foreach ($choices as $choice): ?>
                    <article class="scene-card">
                        <div class="scene-card-top">
                            <div class="scene-number-block">
                                <span class="scene-kicker">SCENE <?php echo (int) $choice['scene_number']; ?> · <?php echo htmlspecialchars((string) $choice['scene_title']); ?></span>
                                <span class="scene-number"><?php echo htmlspecialchars((string) $choice['choice_text']); ?></span>
                            </div>
                            <span class="status-badge status-<?php echo (int) $choice['is_active'] === 1 ? 'published' : 'draft'; ?>"><?php echo (int) $choice['is_active'] === 1 ? 'ACTIVE' : 'HIDDEN'; ?></span>
                        </div>

                        <div class="scene-card-body">
                            <div class="scene-meta-row">
                                <div class="meta-item">
                                    <span class="meta-label">Leads To</span>
                                    <span class="meta-value"><?php echo $choice['target_scene_id'] !== null ? 'Scene ' . (int) $choice['target_number'] . ' - ' . htmlspecialchars((string) $choice['target_title']) : 'Next scene in order'; ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Order</span>
                                    <span class="meta-value"><?php echo (int) $choice['display_order']; ?></span>
                                </div>
                            </div>
                            <?php if (!empty($choice['feedback_text'])): ?>
                                <p class="scene-description"><?php echo htmlspecialchars((string) $choice['feedback_text']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="scene-actions">
                            <a href="<?php echo APP_BASE_PATH; ?>/admin/choices/edit.php?id=<?php echo (int) $choice['choice_id']; ?>" class="action-btn secondary">Edit</a>
                            <a href="<?php echo APP_BASE_PATH; ?>/admin/choices/delete.php?id=<?php echo (int) $choice['choice_id']; ?>" class="action-btn danger">Delete</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

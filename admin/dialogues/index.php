<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

$pdo = getDatabaseConnection();

$summary = $pdo->query(
    "SELECT COUNT(*) AS total_dialogues, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count FROM dialogues"
)->fetch();

$dialogues = $pdo->query(
    "SELECT d.dialogue_id, d.speaker_name, d.speaker_role, d.dialogue_text, d.display_type, d.is_primary, d.is_active, s.title AS scene_title, ie.element_name
     FROM dialogues d
     LEFT JOIN scenes s ON s.scene_id = d.scene_id
     LEFT JOIN interactive_elements ie ON ie.interactive_element_id = d.interactive_element_id
     ORDER BY d.created_at DESC"
)->fetchAll();

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header">
            <div>
                <div class="mini-kicker">DIALOGUE LIBRARY</div>
                <h2 class="section-title-large">Dialogue Library</h2>
                <p class="studio-subtitle">Review the script voice, scene narrative beats, and story delivery flow.</p>
            </div>

            <a href="<?php echo APP_BASE_PATH; ?>/admin/dialogues/create.php" class="primary-btn">+ Create New Dialogue</a>
        </div>

        <div class="studio-summary">
            <div class="summary-chip">
                <span class="summary-label">Total</span>
                <strong><?php echo (int) ($summary['total_dialogues'] ?? 0); ?></strong>
            </div>
            <div class="summary-chip">
                <span class="summary-label">Active</span>
                <strong><?php echo (int) ($summary['active_count'] ?? 0); ?></strong>
            </div>
            <div class="summary-chip">
                <span class="summary-label">Scenes</span>
                <strong><?php echo (int) count(array_unique(array_filter(array_map(static fn ($row) => $row['scene_title'] ?? null, $dialogues)))); ?></strong>
            </div>
            <div class="summary-chip">
                <span class="summary-label">Format</span>
                <strong>Script</strong>
            </div>
        </div>

        <?php if (empty($dialogues)): ?>
            <div class="empty-state studio-empty">
                <div class="empty-icon">✦</div>
                <h3>No dialogue entries yet</h3>
                <p>Capture the story's spoken rhythm and narrative exchanges.</p>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/dialogues/create.php" class="primary-btn">+ Create New Dialogue</a>
            </div>
        <?php else: ?>
            <div class="scene-library">
                <?php foreach ($dialogues as $dialogue): ?>
                    <?php
                    $status = (int) ($dialogue['is_active'] ?? 1) === 1 ? 'published' : 'draft';
                    $statusLabel = strtoupper($status);
                    $sceneLabel = !empty($dialogue['scene_title']) ? $dialogue['scene_title'] : 'Unassigned';
                    $primaryLabel = (int) ($dialogue['is_primary'] ?? 0) === 1 ? 'Primary' : 'Secondary';
                    ?>
                    <article class="scene-card">
                        <div class="scene-card-top">
                            <div class="scene-number-block">
                                <span class="scene-kicker">DIALOGUE</span>
                                <span class="scene-number"><?php echo htmlspecialchars((string) ($dialogue['speaker_name'] ?? 'Unknown Speaker')); ?></span>
                            </div>
                            <span class="status-badge status-<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                        </div>

                        <div class="scene-card-body">
                            <div class="scene-meta-row">
                                <div class="meta-item">
                                    <span class="meta-label">Speaker Role</span>
                                    <span class="meta-value"><?php echo htmlspecialchars((string) ($dialogue['speaker_role'] ?? 'Narration')); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Display</span>
                                    <span class="meta-value"><?php echo htmlspecialchars((string) ($dialogue['display_type'] ?? 'panel')); ?></span>
                                </div>
                            </div>

                            <div class="scene-meta-row">
                                <div class="meta-item">
                                    <span class="meta-label">Scene</span>
                                    <span class="meta-value"><?php echo htmlspecialchars($sceneLabel); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Priority</span>
                                    <span class="meta-value"><?php echo htmlspecialchars($primaryLabel); ?></span>
                                </div>
                            </div>

                            <p class="scene-description"><?php echo nl2br(htmlspecialchars((string) ($dialogue['dialogue_text'] ?? ''))); ?></p>
                        </div>

                        <div class="scene-actions">
                            <a href="<?php echo APP_BASE_PATH; ?>/admin/dialogues/edit.php?id=<?php echo (int) $dialogue['dialogue_id']; ?>" class="action-btn secondary">Edit</a>
                            <a href="<?php echo APP_BASE_PATH; ?>/admin/dialogues/delete.php?id=<?php echo (int) $dialogue['dialogue_id']; ?>" class="action-btn danger">Delete</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

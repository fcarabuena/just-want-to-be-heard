<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

$pdo = getDatabaseConnection();

$summary = $pdo->query(
    "SELECT COUNT(*) AS total_elements, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count FROM interactive_elements"
)->fetch();

$elements = $pdo->query(
    "SELECT ie.interactive_element_id, ie.element_name, ie.element_type, ie.interaction_behavior, ie.is_active, ie.display_order, s.title AS scene_title, c.name AS character_name, o.name AS object_name
     FROM interactive_elements ie
     LEFT JOIN scenes s ON s.scene_id = ie.scene_id
     LEFT JOIN characters c ON c.character_id = ie.character_id
     LEFT JOIN objects o ON o.object_id = ie.object_id
     ORDER BY ie.display_order ASC, ie.created_at DESC"
)->fetchAll();

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header">
            <div>
                <div class="mini-kicker">INTERACTIVE ELEMENTS</div>
                <h2 class="section-title-large">Interactive Elements</h2>
                <p class="studio-subtitle">Track how the scene story world responds to viewer interaction and narrative flow.</p>
            </div>

            <a href="<?php echo APP_BASE_PATH; ?>/admin/interactive-elements/create.php" class="primary-btn">+ Create New Interactive Element</a>
        </div>

        <div class="studio-summary">
            <div class="summary-chip">
                <span class="summary-label">Total</span>
                <strong><?php echo (int) ($summary['total_elements'] ?? 0); ?></strong>
            </div>
            <div class="summary-chip">
                <span class="summary-label">Active</span>
                <strong><?php echo (int) ($summary['active_count'] ?? 0); ?></strong>
            </div>
            <div class="summary-chip">
                <span class="summary-label">Behavior</span>
                <strong>Hotspots</strong>
            </div>
            <div class="summary-chip">
                <span class="summary-label">Flow</span>
                <strong>Story</strong>
            </div>
        </div>

        <?php if (empty($elements)): ?>
            <div class="empty-state studio-empty">
                <div class="empty-icon">✦</div>
                <h3>No interactive elements yet</h3>
                <p>Define the story hotspots and interaction points that enrich scene engagement.</p>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/interactive-elements/create.php" class="primary-btn">+ Create New Interactive Element</a>
            </div>
        <?php else: ?>
            <div class="scene-library">
                <?php foreach ($elements as $element): ?>
                    <?php
                    $status = (int) ($element['is_active'] ?? 1) === 1 ? 'published' : 'draft';
                    $statusLabel = strtoupper($status);
                    $sceneLabel = !empty($element['scene_title']) ? $element['scene_title'] : 'Unassigned';
                    $assocLabel = !empty($element['character_name']) ? $element['character_name'] : (!empty($element['object_name']) ? $element['object_name'] : 'No association');
                    ?>
                    <article class="scene-card">
                        <div class="scene-card-top">
                            <div class="scene-number-block">
                                <span class="scene-kicker">ELEMENT</span>
                                <span class="scene-number"><?php echo htmlspecialchars((string) ($element['element_name'] ?? 'Unnamed')); ?></span>
                            </div>
                            <span class="status-badge status-<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                        </div>

                        <div class="scene-card-body">
                            <div class="scene-preview">
                                <div class="scene-preview-empty">
                                    <div class="preview-icon">✦</div>
                                    <strong><?php echo htmlspecialchars((string) ($element['element_type'] ?? 'hotspot')); ?></strong>
                                </div>
                            </div>

                            <div class="scene-meta-row">
                                <div class="meta-item">
                                    <span class="meta-label">Scene</span>
                                    <span class="meta-value"><?php echo htmlspecialchars($sceneLabel); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Order</span>
                                    <span class="meta-value"><?php echo (int) ($element['display_order'] ?? 0); ?></span>
                                </div>
                            </div>

                            <div class="scene-meta-row">
                                <div class="meta-item">
                                    <span class="meta-label">Association</span>
                                    <span class="meta-value"><?php echo htmlspecialchars($assocLabel); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Behavior</span>
                                    <span class="meta-value"><?php echo htmlspecialchars((string) ($element['interaction_behavior'] ?? 'info_popup')); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="scene-actions">
                            <a href="<?php echo APP_BASE_PATH; ?>/admin/interactive-elements/edit.php?id=<?php echo (int) $element['interactive_element_id']; ?>" class="action-btn secondary">Edit</a>
                            <a href="<?php echo APP_BASE_PATH; ?>/admin/interactive-elements/delete.php?id=<?php echo (int) $element['interactive_element_id']; ?>" class="action-btn danger">Delete</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

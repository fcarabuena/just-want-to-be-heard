<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

$pdo = getDatabaseConnection();

$summary = $pdo->query(
    "SELECT COUNT(*) AS total_objects, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count FROM objects"
)->fetch();

$objects = $pdo->query(
    "SELECT object_id, name, type, description, image_path, is_active, created_at FROM objects ORDER BY created_at DESC"
)->fetchAll();

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header">
            <div>
                <div class="mini-kicker">PROP / OBJECT LIBRARY</div>
                <h2 class="section-title-large">Prop / Object Library</h2>
                <p class="studio-subtitle">Manage story props, production objects, and visual world-building assets.</p>
            </div>

            <a href="<?php echo APP_BASE_PATH; ?>/admin/objects/create.php" class="primary-btn">+ Create New Prop / Object</a>
        </div>

        <div class="studio-summary">
            <div class="summary-chip">
                <span class="summary-label">Total Props</span>
                <strong><?php echo (int) ($summary['total_objects'] ?? 0); ?></strong>
            </div>
            <div class="summary-chip">
                <span class="summary-label">Active</span>
                <strong><?php echo (int) ($summary['active_count'] ?? 0); ?></strong>
            </div>
            <div class="summary-chip">
                <span class="summary-label">Type</span>
                <strong>Assets</strong>
            </div>
            <div class="summary-chip">
                <span class="summary-label">Library</span>
                <strong>World</strong>
            </div>
        </div>

        <?php if (empty($objects)): ?>
            <div class="empty-state studio-empty">
                <div class="empty-icon">✦</div>
                <h3>No props or objects yet</h3>
                <p>Add the first world-building asset to support the story environment.</p>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/objects/create.php" class="primary-btn">+ Create New Prop / Object</a>
            </div>
        <?php else: ?>
            <div class="scene-library">
                <?php foreach ($objects as $object): ?>
                    <?php
                    $status = (int) ($object['is_active'] ?? 1) === 1 ? 'published' : 'draft';
                    $statusLabel = strtoupper($status);
                    $preview = app_asset_url($object['image_path'] ?? '');
                    $updatedAt = !empty($object['created_at']) ? date('M d, Y', strtotime($object['created_at'])) : 'Not set';
                    ?>
                    <article class="scene-card">
                        <div class="scene-card-top">
                            <div class="scene-number-block">
                                <span class="scene-kicker">OBJECT</span>
                                <span class="scene-number"><?php echo htmlspecialchars((string) ($object['name'] ?? 'Unnamed')); ?></span>
                            </div>
                            <span class="status-badge status-<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                        </div>

                        <div class="scene-card-body">
                            <?php if (!empty($preview)): ?>
                                <div class="scene-preview">
                                    <img src="<?php echo htmlspecialchars($preview); ?>" alt="<?php echo htmlspecialchars((string) ($object['name'] ?? 'Object')); ?>" style="display:block;width:100%;height:100%;object-fit:cover;max-height:220px;" />
                                </div>
                            <?php else: ?>
                                <div class="scene-preview">
                                    <div class="scene-preview-empty">
                                        <div class="preview-icon">◈</div>
                                        <strong>PREVIEW UNAVAILABLE</strong>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="scene-meta-row">
                                <div class="meta-item">
                                    <span class="meta-label">Type</span>
                                    <span class="meta-value"><?php echo htmlspecialchars((string) ($object['type'] ?? 'prop')); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Updated</span>
                                    <span class="meta-value"><?php echo htmlspecialchars($updatedAt); ?></span>
                                </div>
                            </div>

                            <?php if (!empty($object['description'])): ?>
                                <p class="scene-description"><?php echo nl2br(htmlspecialchars((string) $object['description'])); ?></p>
                            <?php else: ?>
                                <p class="scene-description muted-copy">No object description provided yet.</p>
                            <?php endif; ?>
                        </div>

                        <div class="scene-actions">
                            <a href="<?php echo APP_BASE_PATH; ?>/admin/objects/edit.php?id=<?php echo (int) $object['object_id']; ?>" class="action-btn secondary">Edit</a>
                            <a href="<?php echo APP_BASE_PATH; ?>/admin/objects/delete.php?id=<?php echo (int) $object['object_id']; ?>" class="action-btn danger">Delete</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

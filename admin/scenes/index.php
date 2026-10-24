<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

$pdo = getDatabaseConnection();

$summarySql = "
    SELECT
        COUNT(*) AS total_scenes,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published_count,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft_count,
        SUM(CASE WHEN is_interactive = 1 THEN 1 ELSE 0 END) AS interactive_count
    FROM scenes
";

$summary = $pdo->query($summarySql)->fetch();

$sql = "
    SELECT s.*, v.title AS video_title, v.filename AS video_filename, v.file_path AS video_path
    FROM scenes s
    LEFT JOIN videos v ON v.video_id = s.video_id
    ORDER BY s.display_order ASC, s.scene_number ASC
";

$scenes = $pdo->query($sql)->fetchAll();

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header">
            <div>
                <div class="mini-kicker">SCENE LIBRARY</div>
                <h2 class="section-title-large">Scene Library</h2>
                <p class="studio-subtitle">Organize, preview, and manage the animation scenes that make up the story.</p>
            </div>

            <a href="<?php echo APP_BASE_PATH; ?>/admin/scenes/create.php" class="primary-btn">+ Create New Scene</a>
        </div>

        <div class="studio-summary">
            <div class="summary-chip">
                <span class="summary-label">Total Scenes</span>
                <strong><?php echo (int) ($summary['total_scenes'] ?? 0); ?></strong>
            </div>
            <div class="summary-chip">
                <span class="summary-label">Published</span>
                <strong><?php echo (int) ($summary['published_count'] ?? 0); ?></strong>
            </div>
            <div class="summary-chip">
                <span class="summary-label">Draft</span>
                <strong><?php echo (int) ($summary['draft_count'] ?? 0); ?></strong>
            </div>
            <div class="summary-chip">
                <span class="summary-label">Interactive</span>
                <strong><?php echo (int) ($summary['interactive_count'] ?? 0); ?></strong>
            </div>
        </div>

        <?php if (empty($scenes)): ?>
            <div class="empty-state studio-empty">
                <div class="empty-icon">✦</div>
                <h3>No scenes yet</h3>
                <p>Create your first story scene to begin building the animation sequence.</p>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/scenes/create.php" class="primary-btn">+ Create New Scene</a>
            </div>
        <?php else: ?>
            <div class="scene-library">
                <?php foreach ($scenes as $scene): ?>
                    <?php
                    $status = strtolower((string) ($scene['status'] ?? 'draft'));
                    $statusLabel = strtoupper($status);
                    $interactive = (int) ($scene['is_interactive'] ?? 0) === 1 ? 'Interactive' : 'Non-Interactive';
                    $interactiveClass = (int) ($scene['is_interactive'] ?? 0) === 1 ? 'interactive-yes' : 'interactive-no';
                    $videoLabel = !empty($scene['video_title']) ? htmlspecialchars($scene['video_title']) : 'No video assigned';
                    $videoFile = app_asset_url($scene['video_path'] ?? '');
                    $updatedAt = !empty($scene['updated_at']) ? date('M d, Y', strtotime($scene['updated_at'])) : 'Not set';
                    $sceneNumber = sprintf('%02d', (int) ($scene['scene_number'] ?? 0));
                    ?>
                    <article class="scene-card">
                        <div class="scene-card-top">
                            <div class="scene-number-block">
                                <span class="scene-kicker">SCENE</span>
                                <span class="scene-number"><?php echo $sceneNumber; ?></span>
                            </div>

                            <span class="status-badge status-<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                        </div>

                        <div class="scene-card-body">
                            <h3 class="scene-title"><?php echo htmlspecialchars($scene['title']); ?></h3>

                            <?php if (!empty($scene['description'])): ?>
                                <p class="scene-description"><?php echo nl2br(htmlspecialchars($scene['description'])); ?></p>
                            <?php else: ?>
                                <p class="scene-description muted-copy">No scene description provided yet.</p>
                            <?php endif; ?>

                            <div class="scene-preview">
                                <?php if (!empty($videoFile)): ?>
                                    <video controls preload="metadata" playsinline>
                                        <source src="<?php echo htmlspecialchars($videoFile); ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php else: ?>
                                    <div class="scene-preview-empty">
                                        <div class="preview-icon">🎬</div>
                                        <strong>ANIMATION UNASSIGNED</strong>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="scene-meta-row">
                                <div class="meta-item">
                                    <span class="meta-label">Animation</span>
                                    <span class="meta-value"><?php echo $videoLabel; ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Display Order</span>
                                    <span class="meta-value"><?php echo (int) $scene['display_order']; ?></span>
                                </div>
                            </div>

                            <div class="scene-meta-row compact-row">
                                <span class="interactive-badge <?php echo htmlspecialchars($interactiveClass); ?>"><?php echo htmlspecialchars($interactive); ?></span>
                                <span class="meta-inline">Updated <?php echo htmlspecialchars($updatedAt); ?></span>
                            </div>
                        </div>

                        <div class="scene-actions">
                            <a href="<?php echo APP_BASE_PATH; ?>/admin/scenes/edit.php?id=<?php echo (int) $scene['scene_id']; ?>" class="action-btn secondary">Edit</a>
                            <a href="<?php echo APP_BASE_PATH; ?>/admin/scenes/delete.php?id=<?php echo (int) $scene['scene_id']; ?>" class="action-btn danger">Delete</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

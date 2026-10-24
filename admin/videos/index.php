<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();
require_once __DIR__ . '/upload_helpers.php';

$pdo = getDatabaseConnection();

$summarySql = "
    SELECT
        COUNT(*) AS total_videos,
        SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) AS published_count,
        SUM(CASE WHEN is_published = 0 THEN 1 ELSE 0 END) AS draft_count,
        SUM(CASE WHEN file_path IS NOT NULL AND file_path <> '' THEN 1 ELSE 0 END) AS asset_count
    FROM videos
    WHERE is_deleted = 0
";

$summary = $pdo->query($summarySql)->fetch();

$sql = "
    SELECT v.*,
           s.scene_id,
           s.title AS scene_title,
           s.scene_number
    FROM videos v
    LEFT JOIN scenes s ON s.video_id = v.video_id
    WHERE v.is_deleted = 0
    ORDER BY v.created_at DESC, v.video_id DESC
";

$videos = $pdo->query($sql)->fetchAll();

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header">
            <div>
                <div class="mini-kicker">VIDEO LIBRARY</div>
                <h2 class="section-title-large">Video Library</h2>
                <p class="studio-subtitle">Manage the animation/video assets used by the story.</p>
            </div>

            <a href="<?php echo APP_BASE_PATH; ?>/admin/videos/create.php" class="primary-btn">+ Create New Video</a>
        </div>

        <div class="studio-summary">
            <div class="summary-chip">
                <span class="summary-label">Total Videos</span>
                <strong><?php echo (int) ($summary['total_videos'] ?? 0); ?></strong>
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
                <span class="summary-label">Assets</span>
                <strong><?php echo (int) ($summary['asset_count'] ?? 0); ?></strong>
            </div>
        </div>

        <?php if (empty($videos)): ?>
            <div class="empty-state studio-empty">
                <div class="empty-icon">✦</div>
                <h3>No videos yet</h3>
                <p>Upload the first animation asset to begin building the story sequence.</p>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/videos/create.php" class="primary-btn">+ Create New Video</a>
            </div>
        <?php else: ?>
            <div class="scene-library video-library">
                <?php foreach ($videos as $video): ?>
                    <?php
                    $status = (int) ($video['is_published'] ?? 0) === 1 ? 'published' : 'draft';
                    $statusLabel = strtoupper($status);
                    $updatedAt = !empty($video['updated_at']) ? date('M d, Y', strtotime($video['updated_at'])) : 'Not set';
                    $duration = !empty($video['duration_seconds']) ? (int) $video['duration_seconds'] : null;
                    $sizeMb = (int) ($video['file_size'] ?? 0) > 0 ? round((int) $video['file_size'] / 1048576, 2) : 0;
                    $sceneLabel = !empty($video['scene_title']) ? 'Scene ' . (int) $video['scene_number'] . ' - ' . $video['scene_title'] : 'Not assigned';
                    $previewPath = app_asset_url($video['file_path'] ?? '');
                    ?>
                    <article class="scene-card video-card">
                        <div class="scene-card-top">
                            <div class="scene-number-block">
                                <span class="scene-kicker">VIDEO</span>
                                <span class="scene-number"><?php echo htmlspecialchars((string) $video['video_id']); ?></span>
                            </div>

                            <span class="status-badge status-<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                        </div>

                        <div class="scene-card-body">
                            <?php if (!empty($previewPath)): ?>
                                <div class="scene-preview">
                                    <video controls preload="metadata" playsinline>
                                        <source src="<?php echo htmlspecialchars($previewPath); ?>" type="<?php echo htmlspecialchars($video['mime_type'] ?: 'video/mp4'); ?>">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                            <?php else: ?>
                                <div class="scene-preview">
                                    <div class="scene-preview-empty">
                                        <div class="preview-icon">▶</div>
                                        <strong>VIDEO UNAVAILABLE</strong>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="video-info-box">
                                <div class="meta-item">
                                    <span class="meta-label">Title</span>
                                    <span class="meta-value"><?php echo htmlspecialchars((string) $video['title']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Filename</span>
                                    <span class="meta-value"><?php echo htmlspecialchars((string) $video['filename']); ?></span>
                                </div>
                            </div>

                            <div class="scene-meta-row">
                                <div class="meta-item">
                                    <span class="meta-label">MIME</span>
                                    <span class="meta-value"><?php echo htmlspecialchars((string) ($video['mime_type'] ?: 'N/A')); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Resolution</span>
                                    <span class="meta-value"><?php echo htmlspecialchars((string) ($video['resolution'] ?: 'N/A')); ?></span>
                                </div>
                            </div>

                            <div class="scene-meta-row">
                                <div class="meta-item">
                                    <span class="meta-label">Duration</span>
                                    <span class="meta-value"><?php echo $duration !== null ? formatVideoDuration($duration) : 'N/A'; ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Size</span>
                                    <span class="meta-value"><?php echo $sizeMb > 0 ? $sizeMb . ' MB' : 'N/A'; ?></span>
                                </div>
                            </div>

                            <div class="scene-meta-row compact-row">
                                <span class="interactive-badge interactive-yes">Asset</span>
                                <span class="meta-inline">Updated <?php echo htmlspecialchars($updatedAt); ?></span>
                            </div>
                        </div>

                        <div class="scene-actions">
                            <a href="<?php echo APP_BASE_PATH; ?>/admin/videos/edit.php?id=<?php echo (int) $video['video_id']; ?>" class="action-btn secondary">Edit</a>
                            <a href="<?php echo APP_BASE_PATH; ?>/admin/videos/delete.php?id=<?php echo (int) $video['video_id']; ?>" class="action-btn danger">Delete</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

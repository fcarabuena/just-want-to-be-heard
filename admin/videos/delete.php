<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();
require_once __DIR__ . '/upload_helpers.php';

$pdo = getDatabaseConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    header('Location: ' . APP_BASE_PATH . '/admin/videos/index.php?status=invalid');
    exit;
}

$video = $pdo->prepare('SELECT * FROM videos WHERE video_id = :id AND is_deleted = 0 LIMIT 1');
$video->execute([':id' => $id]);
$videoData = $video->fetch();

if (!$videoData) {
    header('Location: ' . APP_BASE_PATH . '/admin/videos/index.php?status=missing');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $updateScenes = $pdo->prepare('UPDATE scenes SET video_id = NULL WHERE video_id = :id');
        $updateScenes->execute([':id' => $id]);

        $deleteVideo = $pdo->prepare('UPDATE videos SET is_deleted = 1, updated_at = CURRENT_TIMESTAMP WHERE video_id = :id');
        $deleteVideo->execute([':id' => $id]);

        $pdo->commit();

        deleteVideoFileIfExists($videoData['file_path'] ?? '');

        header('Location: ' . APP_BASE_PATH . '/admin/videos/index.php?status=deleted');
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        header('Location: ' . APP_BASE_PATH . '/admin/videos/index.php?status=delete_failed');
        exit;
    }
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel narrow-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">DELETE VIDEO</div>
                <h2 class="section-title-large">Delete Video</h2>
            </div>
        </div>

        <div class="confirm-box">
            <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($videoData['title']); ?></strong>?</p>
            <p class="inline-note">This removes the video asset while leaving scene records intact. Any scene assignment will be cleared before deletion.</p>

            <form method="POST" class="form-actions inline-actions">
                <button type="submit" class="danger-btn">Delete Video</button>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/videos/index.php" class="secondary-link-btn">Cancel</a>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

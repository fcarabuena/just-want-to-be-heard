<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

$pdo = getDatabaseConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    header('Location: ' . APP_BASE_PATH . '/admin/scenes/index.php?status=invalid');
    exit;
}

$scene = $pdo->prepare('SELECT scene_id, scene_number, title, video_id FROM scenes WHERE scene_id = :id LIMIT 1');
$scene->execute([':id' => $id]);
$sceneData = $scene->fetch();

if (!$sceneData) {
    header('Location: ' . APP_BASE_PATH . '/admin/scenes/index.php?status=missing');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare('DELETE FROM scenes WHERE scene_id = :id');
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() > 0) {
            header('Location: ' . APP_BASE_PATH . '/admin/scenes/index.php?status=deleted');
            exit;
        }

        header('Location: ' . APP_BASE_PATH . '/admin/scenes/index.php?status=delete_failed');
        exit;
    } catch (PDOException $e) {
        header('Location: ' . APP_BASE_PATH . '/admin/scenes/index.php?status=delete_failed');
        exit;
    }
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel narrow-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">DELETE SCENE</div>
                <h2 class="section-title-large">Delete Scene</h2>
            </div>
        </div>

        <div class="confirm-box">
            <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($sceneData['title']); ?></strong>?</p>
            <p class="inline-note">This will remove the scene record only. It does not delete the animation video file itself or any future related production assets.</p>

            <form method="POST" class="form-actions inline-actions">
                <button type="submit" class="danger-btn">Delete Scene</button>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/scenes/index.php" class="secondary-link-btn">Cancel</a>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

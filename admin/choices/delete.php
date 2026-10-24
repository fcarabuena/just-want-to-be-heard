<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

$pdo = getDatabaseConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare('SELECT * FROM scene_choices WHERE choice_id = :id LIMIT 1');
$stmt->execute([':id' => (int) $id]);
$choice = $stmt->fetch();

if (!$choice) {
    header('Location: ' . APP_BASE_PATH . '/admin/choices/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare('DELETE FROM scene_choices WHERE choice_id = :id')->execute([':id' => (int) $id]);
    header('Location: ' . APP_BASE_PATH . '/admin/choices/index.php?status=deleted');
    exit;
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel narrow-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">DELETE CHOICE</div>
                <h2 class="section-title-large">Delete Choice</h2>
            </div>
        </div>

        <div class="confirm-box">
            <p>Are you sure you want to delete the choice <strong><?php echo htmlspecialchars((string) $choice['choice_text']); ?></strong>?</p>
            <form method="POST" class="form-actions inline-actions">
                <button type="submit" class="danger-btn">Delete Choice</button>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/choices/index.php" class="secondary-link-btn">Cancel</a>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

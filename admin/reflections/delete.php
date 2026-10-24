<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

$pdo = getDatabaseConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare('SELECT * FROM reflection_questions WHERE question_id = :id LIMIT 1');
$stmt->execute([':id' => (int) $id]);
$question = $stmt->fetch();

if (!$question) {
    header('Location: ' . APP_BASE_PATH . '/admin/reflections/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare('DELETE FROM reflection_questions WHERE question_id = :id')->execute([':id' => (int) $id]);
    header('Location: ' . APP_BASE_PATH . '/admin/reflections/index.php?status=deleted');
    exit;
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel narrow-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">DELETE QUESTION</div>
                <h2 class="section-title-large">Delete Question</h2>
            </div>
        </div>

        <div class="confirm-box">
            <p>Are you sure you want to delete the question <strong><?php echo htmlspecialchars((string) $question['question_text']); ?></strong>?</p>
            <form method="POST" class="form-actions inline-actions">
                <button type="submit" class="danger-btn">Delete Question</button>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/reflections/index.php" class="secondary-link-btn">Cancel</a>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

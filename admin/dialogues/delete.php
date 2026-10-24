<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

$pdo = getDatabaseConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    header('Location: ' . APP_BASE_PATH . '/admin/dialogues/index.php?status=invalid');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM dialogues WHERE dialogue_id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$dialogue = $stmt->fetch();

if (!$dialogue) {
    header('Location: ' . APP_BASE_PATH . '/admin/dialogues/index.php?status=missing');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('DELETE FROM dialogues WHERE dialogue_id = :id');
    $stmt->execute([':id' => $id]);

    header('Location: ' . APP_BASE_PATH . '/admin/dialogues/index.php?status=deleted');
    exit;
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel narrow-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">DELETE DIALOGUE</div>
                <h2 class="section-title-large">Delete Dialogue</h2>
            </div>
        </div>

        <div class="confirm-box">
            <p>Are you sure you want to delete this dialogue from <strong><?php echo htmlspecialchars((string) $dialogue['speaker_name']); ?></strong>?</p>
            <p class="inline-note">This permanently removes the dialogue record.</p>

            <form method="POST" class="form-actions inline-actions">
                <button type="submit" class="danger-btn">Delete Dialogue</button>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/dialogues/index.php" class="secondary-link-btn">Cancel</a>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

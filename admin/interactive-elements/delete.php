<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

$pdo = getDatabaseConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    header('Location: ' . APP_BASE_PATH . '/admin/interactive-elements/index.php?status=invalid');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM interactive_elements WHERE interactive_element_id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$element = $stmt->fetch();

if (!$element) {
    header('Location: ' . APP_BASE_PATH . '/admin/interactive-elements/index.php?status=missing');
    exit;
}

$dialogueUsage = $pdo->prepare('SELECT COUNT(*) FROM dialogues WHERE interactive_element_id = :id');
$dialogueUsage->execute([':id' => $id]);
$dialogueLinkedCount = (int) $dialogueUsage->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($dialogueLinkedCount > 0) {
        header('Location: ' . APP_BASE_PATH . '/admin/interactive-elements/index.php?status=linked');
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM interactive_elements WHERE interactive_element_id = :id');
    $stmt->execute([':id' => $id]);

    header('Location: ' . APP_BASE_PATH . '/admin/interactive-elements/index.php?status=deleted');
    exit;
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel narrow-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">DELETE ELEMENT</div>
                <h2 class="section-title-large">Delete Interactive Element</h2>
            </div>
        </div>

        <div class="confirm-box">
            <?php if ($dialogueLinkedCount > 0): ?>
                <p><strong><?php echo htmlspecialchars((string) $element['element_name']); ?></strong> is referenced by one or more dialogue entries and cannot be deleted safely.</p>
                <p class="inline-note">Please remove or reassign those dialogue links before deleting this element.</p>
                <div class="form-actions inline-actions">
                    <a href="<?php echo APP_BASE_PATH; ?>/admin/interactive-elements/index.php" class="secondary-link-btn">Back to Library</a>
                </div>
            <?php else: ?>
                <p>Are you sure you want to delete <strong><?php echo htmlspecialchars((string) $element['element_name']); ?></strong>?</p>
                <p class="inline-note">This removes the interactive element record from the selected scene.</p>

                <form method="POST" class="form-actions inline-actions">
                    <button type="submit" class="danger-btn">Delete Element</button>
                    <a href="<?php echo APP_BASE_PATH; ?>/admin/interactive-elements/index.php" class="secondary-link-btn">Cancel</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

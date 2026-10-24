<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();
require_once __DIR__ . '/../characters/upload_helpers.php';

$pdo = getDatabaseConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    header('Location: ' . APP_BASE_PATH . '/admin/objects/index.php?status=invalid');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM objects WHERE object_id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$object = $stmt->fetch();

if (!$object) {
    header('Location: ' . APP_BASE_PATH . '/admin/objects/index.php?status=missing');
    exit;
}

$sceneUsage = $pdo->prepare('SELECT COUNT(*) FROM scene_objects WHERE object_id = :id');
$sceneUsage->execute([':id' => $id]);
$sceneLinkedCount = (int) $sceneUsage->fetchColumn();

$interactiveUsage = $pdo->prepare('SELECT COUNT(*) FROM interactive_elements WHERE object_id = :id');
$interactiveUsage->execute([':id' => $id]);
$interactiveLinkedCount = (int) $interactiveUsage->fetchColumn();

$linkedCount = $sceneLinkedCount + $interactiveLinkedCount;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($linkedCount > 0) {
        header('Location: ' . APP_BASE_PATH . '/admin/objects/index.php?status=linked');
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM objects WHERE object_id = :id');
    $stmt->execute([':id' => $id]);

    deleteCharacterImageIfExists($object['image_path'] ?? '');

    header('Location: ' . APP_BASE_PATH . '/admin/objects/index.php?status=deleted');
    exit;
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel narrow-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">DELETE OBJECT</div>
                <h2 class="section-title-large">Delete Prop / Object</h2>
            </div>
        </div>

        <div class="confirm-box">
            <?php if ($linkedCount > 0): ?>
                <p><strong><?php echo htmlspecialchars((string) $object['name']); ?></strong> is currently used in scene or interaction data and cannot be deleted safely.</p>
                <p class="inline-note">Please remove or reassign those related links before deleting this object.</p>
                <div class="form-actions inline-actions">
                    <a href="<?php echo APP_BASE_PATH; ?>/admin/objects/index.php" class="secondary-link-btn">Back to Library</a>
                </div>
            <?php else: ?>
                <p>Are you sure you want to delete <strong><?php echo htmlspecialchars((string) $object['name']); ?></strong>?</p>
                <p class="inline-note">This will permanently remove the object record from the project library.</p>

                <form method="POST" class="form-actions inline-actions">
                    <button type="submit" class="danger-btn">Delete Object</button>
                    <a href="<?php echo APP_BASE_PATH; ?>/admin/objects/index.php" class="secondary-link-btn">Cancel</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

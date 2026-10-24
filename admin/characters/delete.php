<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();
require_once __DIR__ . '/upload_helpers.php';

$pdo = getDatabaseConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    header('Location: ' . APP_BASE_PATH . '/admin/characters/index.php?status=invalid');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM characters WHERE character_id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$character = $stmt->fetch();

if (!$character) {
    header('Location: ' . APP_BASE_PATH . '/admin/characters/index.php?status=missing');
    exit;
}

$interactiveUsage = $pdo->prepare('SELECT COUNT(*) FROM interactive_elements WHERE character_id = :id');
$interactiveUsage->execute([':id' => $id]);
$interactiveLinkedCount = (int) $interactiveUsage->fetchColumn();

$sceneUsage = $pdo->prepare('SELECT COUNT(*) FROM scene_characters WHERE character_id = :id');
$sceneUsage->execute([':id' => $id]);
$sceneLinkedCount = (int) $sceneUsage->fetchColumn();

$linkedCount = $interactiveLinkedCount + $sceneLinkedCount;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($linkedCount > 0) {
        header('Location: ' . APP_BASE_PATH . '/admin/characters/index.php?status=linked');
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM characters WHERE character_id = :id');
    $stmt->execute([':id' => $id]);

    deleteCharacterImageIfExists($character['image_path'] ?? '');

    header('Location: ' . APP_BASE_PATH . '/admin/characters/index.php?status=deleted');
    exit;
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel narrow-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">DELETE CHARACTER</div>
                <h2 class="section-title-large">Delete Character</h2>
            </div>
        </div>

        <div class="confirm-box">
            <?php if ($linkedCount > 0): ?>
                <p><strong><?php echo htmlspecialchars((string) $character['name']); ?></strong> is currently linked to one or more related records and cannot be deleted safely.</p>
                <p class="inline-note">Please remove or reassign those related interactive/scenario references first before deleting this character.</p>
                <div class="form-actions inline-actions">
                    <a href="<?php echo APP_BASE_PATH; ?>/admin/characters/index.php" class="secondary-link-btn">Back to Library</a>
                </div>
            <?php else: ?>
                <p>Are you sure you want to delete <strong><?php echo htmlspecialchars((string) $character['name']); ?></strong>?</p>
                <p class="inline-note">This action removes the character record and cannot be undone.</p>

                <form method="POST" class="form-actions inline-actions">
                    <button type="submit" class="danger-btn">Delete Character</button>
                    <a href="<?php echo APP_BASE_PATH; ?>/admin/characters/index.php" class="secondary-link-btn">Cancel</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

$pdo = getDatabaseConnection();

$summary = $pdo->query(
    "SELECT COUNT(*) AS total_characters, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count FROM characters"
)->fetch();

$characters = $pdo->query(
    "SELECT character_id, name, role, description, image_path, is_active, created_at FROM characters ORDER BY created_at DESC"
)->fetchAll();

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header">
            <div>
                <div class="mini-kicker">CHARACTER LIBRARY</div>
                <h2 class="section-title-large">Character Library</h2>
                <p class="studio-subtitle">Manage the cast of characters driving the story, emotion, and interaction design.</p>
            </div>

            <a href="<?php echo APP_BASE_PATH; ?>/admin/characters/create.php" class="primary-btn">+ Create New Character</a>
        </div>

        <div class="studio-summary">
            <div class="summary-chip">
                <span class="summary-label">Total Characters</span>
                <strong><?php echo (int) ($summary['total_characters'] ?? 0); ?></strong>
            </div>
            <div class="summary-chip">
                <span class="summary-label">Active</span>
                <strong><?php echo (int) ($summary['active_count'] ?? 0); ?></strong>
            </div>
            <div class="summary-chip">
                <span class="summary-label">Status</span>
                <strong>Live</strong>
            </div>
            <div class="summary-chip">
                <span class="summary-label">Library</span>
                <strong>Cast</strong>
            </div>
        </div>

        <?php if (empty($characters)): ?>
            <div class="empty-state studio-empty">
                <div class="empty-icon">✦</div>
                <h3>No characters yet</h3>
                <p>Build the character roster for the current story world.</p>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/characters/create.php" class="primary-btn">+ Create New Character</a>
            </div>
        <?php else: ?>
            <div class="scene-library">
                <?php foreach ($characters as $character): ?>
                    <?php
                    $status = (int) ($character['is_active'] ?? 1) === 1 ? 'published' : 'draft';
                    $statusLabel = strtoupper($status);
                    $preview = app_asset_url($character['image_path'] ?? '');
                    $updatedAt = !empty($character['created_at']) ? date('M d, Y', strtotime($character['created_at'])) : 'Not set';
                    ?>
                    <article class="scene-card">
                        <div class="scene-card-top">
                            <div class="scene-number-block">
                                <span class="scene-kicker">CHARACTER</span>
                                <span class="scene-number"><?php echo htmlspecialchars((string) ($character['name'] ?? 'Unnamed')); ?></span>
                            </div>
                            <span class="status-badge status-<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                        </div>

                        <div class="scene-card-body">
                            <?php if (!empty($preview)): ?>
                                <div class="scene-preview">
                                    <img src="<?php echo htmlspecialchars($preview); ?>" alt="<?php echo htmlspecialchars((string) ($character['name'] ?? 'Character')); ?>" style="display:block;width:100%;height:100%;object-fit:cover;max-height:220px;" />
                                </div>
                            <?php else: ?>
                                <div class="scene-preview">
                                    <div class="scene-preview-empty">
                                        <div class="preview-icon">◉</div>
                                        <strong>VISUAL REFERENCE N/A</strong>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="scene-meta-row">
                                <div class="meta-item">
                                    <span class="meta-label">Role</span>
                                    <span class="meta-value"><?php echo htmlspecialchars((string) ($character['role'] ?? 'Not specified')); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Updated</span>
                                    <span class="meta-value"><?php echo htmlspecialchars($updatedAt); ?></span>
                                </div>
                            </div>

                            <?php if (!empty($character['description'])): ?>
                                <p class="scene-description"><?php echo nl2br(htmlspecialchars((string) $character['description'])); ?></p>
                            <?php else: ?>
                                <p class="scene-description muted-copy">No character description provided yet.</p>
                            <?php endif; ?>
                        </div>

                        <div class="scene-actions">
                            <a href="<?php echo APP_BASE_PATH; ?>/admin/characters/edit.php?id=<?php echo (int) $character['character_id']; ?>" class="action-btn secondary">Edit</a>
                            <a href="<?php echo APP_BASE_PATH; ?>/admin/characters/delete.php?id=<?php echo (int) $character['character_id']; ?>" class="action-btn danger">Delete</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

$pdo = getDatabaseConnection();

$countQueries = [
    'scenes' => 'SELECT COUNT(*) FROM scenes',
    'videos' => 'SELECT COUNT(*) FROM videos',
];

$stats = [];
foreach ($countQueries as $key => $query) {
    $stats[$key] = (int) $pdo->query($query)->fetchColumn();
}

$recentItems = [];
$recentQueries = [
    'scenes' => 'SELECT scene_id, title, created_at FROM scenes ORDER BY created_at DESC LIMIT 1',
    'videos' => 'SELECT video_id, title, created_at FROM videos ORDER BY created_at DESC LIMIT 1',
];

foreach ($recentQueries as $type => $query) {
    $row = $pdo->query($query)->fetch();
    if ($row) {
        $recentItems[] = [
            'type' => $type,
            'label' => $type === 'scenes' ? $row['title'] : ($type === 'videos' ? $row['title'] : ($type === 'characters' ? $row['name'] : ($type === 'objects' ? $row['name'] : $row['speaker_name']))),
            'created_at' => $row['created_at'],
        ];
    }
}

usort($recentItems, function ($a, $b) {
    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
});

$recentItems = array_slice($recentItems, 0, 5);

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel">
        <div class="section-header">
            <h2 class="section-title">Production Overview</h2>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-value"><?php echo (int) $stats['scenes']; ?></div>
                <div class="metric-label">Scenes</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo (int) $stats['videos']; ?></div>
                <div class="metric-label">Videos</div>
            </div>
        </div>
    </div>

    <div class="progress-layout">
        <div class="progress-card">
            <h3 class="progress-title">Story Production</h3>

            <div class="progress-item">
                <span class="progress-label">Scenes</span>
                <span><?php echo (int) $stats['scenes']; ?> / 9 planned</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo min(100, ($stats['scenes'] / 9) * 100); ?>%;"></div>
            </div>

            <div class="progress-item">
                <span class="progress-label">Videos</span>
                <span><?php echo (int) $stats['videos']; ?> / 9 planned</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo min(100, ($stats['videos'] / 9) * 100); ?>%;"></div>
            </div>

        </div>

        <div class="recent-card">
            <h3 class="section-title">Recently Updated</h3>

            <?php if (empty($recentItems)): ?>
                <div class="empty-state">
                    No production assets yet.<br>
                    Your animation production workspace is ready.<br>
                    Start by adding scenes and animation assets.
                </div>
            <?php else: ?>
                <ul class="recent-list">
                    <?php foreach ($recentItems as $item): ?>
                        <li class="recent-item">
                            <div>
                                <div class="recent-type"><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($item['type']))); ?></div>
                                <strong><?php echo htmlspecialchars($item['label']); ?></strong>
                            </div>
                            <span class="recent-time"><?php echo htmlspecialchars(date('M d, Y', strtotime($item['created_at']))); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

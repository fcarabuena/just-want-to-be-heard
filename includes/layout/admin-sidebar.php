<?php
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$navSections = [
    'Overview' => [
        ['dashboard', '⌂ Dashboard'],
    ],
    'Story' => [
        ['scenes', '🎬 Scenes'],
        ['videos', '▶ Videos'],
        ['characters', '☺ Characters'],
        ['objects', '◆ Objects'],
        ['dialogues', '💬 Dialogues'],
    ],
    'Interactivity' => [
        ['interactive-elements', '◎ Hotspots'],
        ['reflections', '? Reflection Questions'],
        ['choices', '⑂ Story Choices'],
    ],
    'System' => [
        ['settings', '⚙ Settings'],
    ],
];
?>
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <div class="brand-mark">JW</div>
        <div>
            <strong>ANIMATION STUDIO</strong>
        </div>
    </div>

    <nav class="sidebar-nav" aria-label="Admin navigation">
        <?php foreach ($navSections as $sectionLabel => $items): ?>
            <div class="nav-group">
                <span class="nav-label"><?php echo htmlspecialchars($sectionLabel); ?></span>
                <?php foreach ($items as [$module, $label]): ?>
                    <a href="<?php echo APP_BASE_PATH; ?>/admin/<?php echo $module; ?>/index.php" class="nav-item <?php echo strpos($requestPath, '/admin/' . $module . '/') !== false ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($label); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="nav-group">
            <span class="nav-label">Preview</span>
            <a href="<?php echo APP_BASE_PATH; ?>/viewer/scene.php?new=1" class="nav-item" target="_blank" rel="noopener">▷ Play the Story</a>
        </div>
    </nav>
</aside>

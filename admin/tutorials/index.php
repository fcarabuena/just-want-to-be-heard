<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();

// Screen recordings live in assets/tutorials/<id>.webm.
$tutorials = [
    [
        'id' => 'hotspot-add',
        'title' => 'Add a glowing hotspot',
        'length' => '2 min',
        'summary' => 'Put a clickable glowing spot on an object in a scene, then test it in the player.',
        'steps' => [
            'Open ◎ Hotspots and click "+ Create New Interactive Element".',
            'Pick the Scene first. It loads that scene\'s video further down.',
            'Type an Element Name, choose the Element Type and an optional Label.',
            'Write the Pop-up Title and Pop-up Text.',
            'Drag the slider to the right moment, then drag a box over the object.',
            'Click "Use this time for Appears At", or leave Appears At empty to show the spot when the scene ends.',
            'Click "Save Interactive Element", then ▷ Play the Story to test.',
        ],
    ],
    [
        'id' => 'hotspot-edit',
        'title' => 'Move, fix or hide a hotspot',
        'length' => '1 min',
        'summary' => 'After you swap in your own animation, redraw old hotspots so they sit on the right object.',
        'steps' => [
            'Open ◎ Hotspots and click Edit on the hotspot.',
            'Scroll to the frame, drag the slider, and draw a new box. It replaces the old one.',
            'Untick "Element is active" to hide a hotspot without deleting it.',
            'Click "Update Interactive Element".',
        ],
    ],
    [
        'id' => 'video-scene',
        'title' => 'Put your animation in a scene',
        'length' => '1 min',
        'summary' => 'Upload your MP4, then choose it as the scene\'s animation.',
        'steps' => [
            'Open ▶ Videos and click "+ Create New Video".',
            'Type a Video Title and choose your MP4 (1920 × 1080).',
            'Tick Published and click "Save Video".',
            'Open 🎬 Scenes, click Edit on the scene, and pick your video in "Assigned Animation".',
            'Click "Update Scene".',
        ],
    ],
];

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header">
            <div>
                <div class="mini-kicker">HELP</div>
                <h2 class="section-title-large">Tutorials</h2>
                <p class="studio-subtitle">Short screen recordings of the admin panel. Press play, or follow the written steps beside each video.</p>
            </div>
        </div>

        <nav class="tutorial-jump" aria-label="Jump to a tutorial">
            <?php foreach ($tutorials as $index => $tutorial): ?>
                <a href="#<?php echo htmlspecialchars($tutorial['id']); ?>"><?php echo ($index + 1) . '. ' . htmlspecialchars($tutorial['title']); ?></a>
            <?php endforeach; ?>
        </nav>

        <?php foreach ($tutorials as $index => $tutorial): ?>
            <article class="tutorial-card" id="<?php echo htmlspecialchars($tutorial['id']); ?>">
                <div class="tutorial-video">
                    <video controls preload="metadata" playsinline poster="<?php echo APP_BASE_PATH; ?>/assets/tutorials/<?php echo htmlspecialchars($tutorial['id']); ?>.png">
                        <source src="<?php echo APP_BASE_PATH; ?>/assets/tutorials/<?php echo htmlspecialchars($tutorial['id']); ?>.webm" type="video/webm">
                        Your browser cannot play this video. Try Chrome, Edge or Firefox.
                    </video>
                </div>
                <div class="tutorial-text">
                    <div class="mini-kicker">TUTORIAL <?php echo $index + 1; ?> · <?php echo htmlspecialchars($tutorial['length']); ?></div>
                    <h3><?php echo htmlspecialchars($tutorial['title']); ?></h3>
                    <p class="studio-subtitle"><?php echo htmlspecialchars($tutorial['summary']); ?></p>
                    <ol>
                        <?php foreach ($tutorial['steps'] as $step): ?>
                            <li><?php echo htmlspecialchars($step); ?></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

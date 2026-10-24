<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../admin/settings/settings_helpers.php';

$pdo = getDatabaseConnection();
ensure_default_settings($pdo);

$projectName = get_setting($pdo, 'project_name', APP_NAME);
$projectFavicon = get_setting($pdo, 'project_favicon', '');
$accentColor = get_setting($pdo, 'accent_color', '#d4af6a');
$viewerShowTitle = get_setting($pdo, 'viewer_show_title', '1', true);
$viewerShowSceneNumber = get_setting($pdo, 'viewer_show_scene_number', '1', true);
$viewerAllowFullscreen = get_setting($pdo, 'viewer_allow_fullscreen', '1', true);
$maintenanceMode = get_setting($pdo, 'maintenance_mode', '0', true);
$maintenanceMessage = get_setting($pdo, 'maintenance_message', 'The public experience is temporarily unavailable.');
$emptyStateMessage = get_setting($pdo, 'empty_state_message', 'No public scene is available yet.');
$publicExperienceEnabled = get_setting($pdo, 'public_experience_enabled', '1', true);
$helpResources = array_values(array_filter(array_map('trim', explode("\n", (string) get_setting($pdo, 'help_resources_text', '')))));
$defaultSceneId = (int) get_setting($pdo, 'default_scene_id', '0');

$scenes = $pdo->query("
    SELECT s.scene_id, s.scene_number, s.title, s.description, s.next_scene_id, s.ending_type, s.choice_prompt,
           v.file_path AS video_path, v.mime_type
    FROM scenes s
    INNER JOIN videos v ON v.video_id = s.video_id
    WHERE s.status = 'published' AND v.is_deleted = 0 AND v.is_published = 1
    ORDER BY s.display_order ASC, s.scene_number ASC
")->fetchAll();

$sceneIds = array_map(static fn ($scene) => (int) $scene['scene_id'], $scenes);

$elements = $pdo->query("
    SELECT ie.interactive_element_id, ie.scene_id, ie.element_name, ie.element_type, ie.label,
           ie.insight_title, ie.insight_text, ie.position_x, ie.position_y, ie.width, ie.height,
           ie.appear_at_seconds, ie.pauses_video
    FROM interactive_elements ie
    WHERE ie.is_active = 1
    ORDER BY ie.display_order ASC, ie.interactive_element_id ASC
")->fetchAll();

// Older hotspots may keep their text in linked dialogue rows instead of insight_text.
$linkedDialogues = [];
foreach ($pdo->query('SELECT interactive_element_id, speaker_name, dialogue_text FROM dialogues WHERE is_active = 1 AND interactive_element_id IS NOT NULL ORDER BY is_primary DESC, dialogue_id ASC')->fetchAll() as $row) {
    $linkedDialogues[(int) $row['interactive_element_id']][] = ['speaker' => $row['speaker_name'], 'text' => $row['dialogue_text']];
}

$choices = $pdo->query('SELECT choice_id, scene_id, choice_text, target_scene_id, feedback_text FROM scene_choices WHERE is_active = 1 ORDER BY display_order ASC, choice_id ASC')->fetchAll();
$reflections = $pdo->query('SELECT question_id, scene_id, question_text, question_type, options_text FROM reflection_questions WHERE is_active = 1 ORDER BY display_order ASC, question_id ASC')->fetchAll();

$story = [];
foreach ($scenes as $scene) {
    $story[(int) $scene['scene_id']] = [
        'id' => (int) $scene['scene_id'],
        'number' => (int) $scene['scene_number'],
        'title' => (string) $scene['title'],
        'video' => app_asset_url($scene['video_path']),
        'mime' => (string) ($scene['mime_type'] ?: 'video/mp4'),
        'next' => $scene['next_scene_id'] !== null ? (int) $scene['next_scene_id'] : null,
        'ending' => in_array($scene['ending_type'], ['good', 'bad', 'neutral'], true) ? $scene['ending_type'] : 'none',
        'choicePrompt' => (string) ($scene['choice_prompt'] ?: 'What should Riley do?'),
        'hotspots' => [],
        'choices' => [],
        'reflections' => [],
    ];
}

foreach ($elements as $element) {
    $sceneId = (int) $element['scene_id'];
    if (!isset($story[$sceneId])) {
        continue;
    }
    $id = (int) $element['interactive_element_id'];
    $text = (string) ($element['insight_text'] ?? '');
    if ($text === '' && isset($linkedDialogues[$id])) {
        $text = implode("\n\n", array_map(static fn ($d) => $d['speaker'] . ': ' . $d['text'], $linkedDialogues[$id]));
    }
    $type = in_array($element['element_type'], ['red_flag', 'thought', 'insight'], true) ? $element['element_type'] : 'insight';
    $story[$sceneId]['hotspots'][] = [
        'id' => $id,
        'type' => $type,
        'label' => (string) ($element['label'] ?: $element['element_name']),
        'title' => (string) ($element['insight_title'] ?: $element['element_name']),
        'text' => $text,
        // Positions are stored in 1920x1080 frame pixels (the animation's resolution).
        'x' => (int) ($element['position_x'] ?? 900),
        'y' => (int) ($element['position_y'] ?? 480),
        'w' => (int) ($element['width'] ?: 120),
        'h' => (int) ($element['height'] ?: 120),
        'at' => $element['appear_at_seconds'] !== null ? (float) $element['appear_at_seconds'] : null,
        'pauses' => (int) $element['pauses_video'] === 1,
    ];
}

foreach ($choices as $choice) {
    $sceneId = (int) $choice['scene_id'];
    if (!isset($story[$sceneId])) {
        continue;
    }
    $story[$sceneId]['choices'][] = [
        'id' => (int) $choice['choice_id'],
        'text' => (string) $choice['choice_text'],
        'target' => $choice['target_scene_id'] !== null ? (int) $choice['target_scene_id'] : null,
        'feedback' => (string) ($choice['feedback_text'] ?? ''),
    ];
}

foreach ($reflections as $question) {
    $sceneId = (int) $question['scene_id'];
    if (!isset($story[$sceneId])) {
        continue;
    }
    $options = [];
    foreach (explode("\n", (string) $question['options_text']) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('||', $line, 2));
        $options[] = ['text' => $parts[0], 'feedback' => $parts[1] ?? ''];
    }
    $story[$sceneId]['reflections'][] = [
        'id' => (int) $question['question_id'],
        'question' => (string) $question['question_text'],
        'type' => $question['question_type'] === 'open' || empty($options) ? 'open' : 'multiple_choice',
        'options' => $options,
    ];
}

$startSceneId = $sceneIds[0] ?? null;
if ($defaultSceneId > 0 && isset($story[$defaultSceneId])) {
    $startSceneId = $defaultSceneId;
}
$requestedSceneId = filter_input(INPUT_GET, 'scene_id', FILTER_VALIDATE_INT);
$initialSceneId = ($requestedSceneId && isset($story[$requestedSceneId])) ? $requestedSceneId : $startSceneId;

$playerData = [
    'scenes' => (object) $story,
    'order' => $sceneIds,
    'start' => $startSceneId,
    'initial' => $initialSceneId,
    'fresh' => isset($_GET['new']),
    'showTitle' => $viewerShowTitle,
    'showNumber' => $viewerShowSceneNumber,
    'allowFullscreen' => $viewerAllowFullscreen,
    'homeUrl' => APP_BASE_PATH . '/',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($projectName); ?></title>
    <?php if (!empty($projectFavicon)): ?>
        <link rel="icon" href="<?php echo htmlspecialchars(format_setting_asset_url($projectFavicon)); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo APP_BASE_PATH; ?>/assets/css/player.css">
    <style>:root { --accent: <?php echo htmlspecialchars($accentColor); ?>; }</style>
</head>
<body class="player-body">
<?php if (!$publicExperienceEnabled || $maintenanceMode || empty($story)): ?>
    <main class="player-message">
        <h1><?php echo htmlspecialchars($maintenanceMode ? 'Maintenance Mode' : (empty($story) ? $emptyStateMessage : 'Public Experience Disabled')); ?></h1>
        <p><?php echo htmlspecialchars($maintenanceMode ? $maintenanceMessage : 'Publish at least one scene with a published video to start the story.'); ?></p>
        <a class="btn" href="<?php echo APP_BASE_PATH; ?>/">Back</a>
    </main>
<?php else: ?>
    <main class="player" id="player">
        <div class="stage" id="stage">
            <video id="video" playsinline preload="auto"></video>
            <div class="hotspot-layer" id="hotspotLayer" aria-label="Clickable parts of the scene"></div>

            <div class="scene-title" id="sceneTitle" aria-live="polite"></div>

            <div class="explore-bar" id="exploreBar" hidden>
                <span id="exploreHint">Tap the glowing spots to look closer.</span>
                <button type="button" class="btn" id="exploreContinue">Continue</button>
            </div>

            <div class="redflag-counter" id="redflagCounter" hidden>
                <span class="flag-icon" aria-hidden="true">!</span>
                <span>Red flags found <strong id="redflagFound">0</strong> / <strong id="redflagTotal">0</strong></span>
            </div>

            <div class="overlay" id="insightOverlay" hidden>
                <div class="card insight-card" role="dialog" aria-modal="true" aria-labelledby="insightTitle">
                    <span class="tag" id="insightTag">Insight</span>
                    <h2 id="insightTitle"></h2>
                    <p id="insightText"></p>
                    <button type="button" class="btn" id="insightClose">Got it</button>
                </div>
            </div>

            <div class="overlay" id="reflectionOverlay" hidden>
                <div class="card reflection-card" role="dialog" aria-modal="true" aria-labelledby="reflectionQuestion">
                    <span class="tag">Pause and think</span>
                    <h2 id="reflectionQuestion"></h2>
                    <div class="option-list" id="reflectionOptions"></div>
                    <textarea id="reflectionAnswer" rows="3" placeholder="Write your thoughts. Only you can see this." hidden></textarea>
                    <p class="feedback" id="reflectionFeedback" hidden></p>
                    <button type="button" class="btn" id="reflectionNext" hidden>Continue</button>
                </div>
            </div>

            <div class="overlay choice-overlay" id="choiceOverlay" hidden>
                <div class="choice-panel" role="dialog" aria-modal="true" aria-labelledby="choicePrompt">
                    <h2 id="choicePrompt"></h2>
                    <div class="choice-list" id="choiceList"></div>
                    <p class="feedback choice-feedback" id="choiceFeedback" hidden></p>
                </div>
            </div>

            <div class="overlay ending-overlay" id="endingOverlay" hidden>
                <div class="card ending-card" role="dialog" aria-modal="true" aria-labelledby="endingTitle">
                    <span class="tag" id="endingTag">The End</span>
                    <h2 id="endingTitle"></h2>
                    <p id="endingText"></p>
                    <div class="journey">
                        <h3>Your journey</h3>
                        <ol id="journeyList"></ol>
                        <p id="journeyFlags"></p>
                    </div>
                    <div class="ending-actions">
                        <button type="button" class="btn" id="rewindButton" hidden>Go back to your last choice</button>
                        <button type="button" class="btn btn-ghost" id="restartButton">Watch again from the start</button>
                    </div>
                </div>
            </div>

            <div class="overlay" id="startOverlay">
                <div class="card start-card">
                    <h2><?php echo htmlspecialchars($projectName); ?></h2>
                    <p>Watch Riley's story. When the story pauses, tap the glowing spots to look closer, answer a few questions, and choose what Riley does next.</p>
                    <button type="button" class="btn" id="startButton">Play</button>
                </div>
            </div>
        </div>

        <nav class="hud" aria-label="Player controls">
            <a class="hud-btn" href="<?php echo APP_BASE_PATH; ?>/" title="Leave the story" aria-label="Leave the story">✕</a>
            <button type="button" class="hud-btn" id="playPause" aria-label="Pause">❚❚</button>
            <button type="button" class="hud-btn" id="muteToggle" aria-label="Mute">🔊</button>
            <?php if ($viewerAllowFullscreen): ?>
                <button type="button" class="hud-btn" id="fullscreenToggle" aria-label="Full screen">⛶</button>
            <?php endif; ?>
            <button type="button" class="hud-btn help-btn" id="helpButton">Need to talk?</button>
        </nav>

        <aside class="help-panel" id="helpPanel" hidden aria-labelledby="helpTitle">
            <h2 id="helpTitle">You are not alone</h2>
            <p>If anything in this story feels familiar, it is not your fault, and you deserve help.</p>
            <ul>
                <?php foreach ($helpResources as $resource): ?>
                    <li><?php echo htmlspecialchars($resource); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn" id="helpClose">Back to the story</button>
        </aside>
    </main>

    <script>window.STORY = <?php echo json_encode($playerData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;</script>
    <script src="<?php echo APP_BASE_PATH; ?>/assets/js/player.js"></script>
<?php endif; ?>
</body>
</html>

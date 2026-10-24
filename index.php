<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/admin/settings/settings_helpers.php';

$pdo = getDatabaseConnection();
ensure_default_settings($pdo);

$projectName = get_setting($pdo, 'project_name', APP_NAME);
$projectSubtitle = get_setting($pdo, 'project_subtitle', 'Interactive Animation Studio');
$projectDescription = get_setting($pdo, 'project_description', 'An intimate story of voice, memory, and the human need to be witnessed.');
$organizationName = get_setting($pdo, 'organization_name', 'I Just Want To Be Heard');
$projectFavicon = get_setting($pdo, 'project_favicon', '');
$publicTheme = get_setting($pdo, 'public_theme', 'dark');
$accentColor = get_setting($pdo, 'accent_color', '#d4af6a');
$showProjectDescription = get_setting($pdo, 'show_project_description', '1', true);
$landingWelcomeMessage = get_setting($pdo, 'landing_welcome_message', 'I Just Want To Be Heard');
$landingButtonText = get_setting($pdo, 'landing_button_text', 'Start Experience');
$publicExperienceEnabled = get_setting($pdo, 'public_experience_enabled', '1', true);
$maintenanceMode = get_setting($pdo, 'maintenance_mode', '0', true);
$maintenanceMessage = get_setting($pdo, 'maintenance_message', 'The public experience is temporarily unavailable.');

$contentNote = get_setting($pdo, 'content_note_text', '');
$defaultSceneId = (int) get_setting($pdo, 'default_scene_id', '0');

$sceneCount = (int) $pdo->query("SELECT COUNT(*) FROM scenes s LEFT JOIN videos v ON v.video_id = s.video_id WHERE s.status = 'published' AND s.video_id IS NOT NULL AND v.is_deleted = 0 AND v.is_published = 1")->fetchColumn();

$firstScene = $pdo->query("SELECT s.*, v.file_path AS video_path FROM scenes s LEFT JOIN videos v ON v.video_id = s.video_id WHERE s.status = 'published' AND s.video_id IS NOT NULL AND v.is_deleted = 0 AND v.is_published = 1 ORDER BY s.display_order ASC, s.scene_number ASC LIMIT 1")->fetch();

if ($defaultSceneId > 0) {
    $defaultScene = $pdo->query("SELECT scene_id FROM scenes WHERE scene_id = " . (int) $defaultSceneId . " AND status = 'published' LIMIT 1")->fetch();
    if ($defaultScene) {
        $firstScene = $defaultScene;
    }
}

$themeStyles = 'body.public-shell { --public-bg: #090b12; --public-surface: rgba(16, 20, 30, 0.86); --public-panel: rgba(11, 15, 23, 0.92); --public-card-bg: rgba(18, 22, 32, 0.72); --public-border: rgba(212, 175, 106, 0.18); --public-text: #f2f1ec; --public-muted: #b9c2d0; --public-button-text: #121923; --public-highlight: ' . htmlspecialchars($accentColor) . '; --public-highlight-soft: rgba(212, 175, 106, 0.22); --public-shadow: rgba(0, 0, 0, 0.38); --public-ring: rgba(212, 175, 106, 0.55); }';
if ($publicTheme === 'light') {
    $themeStyles = 'body.public-shell { background: linear-gradient(135deg, #f5f1ea 0%, #e9ecf3 100%); color: #111827; --public-bg: #f1efe5; --public-surface: rgba(255,255,255,0.9); --public-panel: rgba(255,255,255,0.84); --public-card-bg: rgba(255,255,255,0.74); --public-border: rgba(17,24,39,0.12); --public-text: #111827; --public-muted: #4b5563; --public-button-text: #111827; --public-highlight: ' . htmlspecialchars($accentColor) . '; --public-highlight-soft: rgba(212, 175, 106, 0.2); --public-accent: #7aa9ff; --public-shadow: rgba(15,23,42,0.12); --public-ring: rgba(212,175,106,0.42); }';
} elseif ($publicTheme === 'system') {
    $themeStyles = '@media (prefers-color-scheme: light) { body.public-shell { background: linear-gradient(135deg, #f5f1ea 0%, #e9ecf3 100%); color: #111827; --public-bg: #f1efe5; --public-surface: rgba(255,255,255,0.9); --public-panel: rgba(255,255,255,0.84); --public-card-bg: rgba(255,255,255,0.74); --public-border: rgba(17,24,39,0.12); --public-text: #111827; --public-muted: #4b5563; --public-button-text: #111827; --public-highlight: ' . htmlspecialchars($accentColor) . '; --public-highlight-soft: rgba(212, 175, 106, 0.2); --public-accent: #7aa9ff; --public-shadow: rgba(15,23,42,0.12); --public-ring: rgba(212,175,106,0.42); } }';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($projectName); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($projectDescription); ?>">
    <?php if (!empty($projectFavicon)): ?>
        <link rel="icon" href="<?php echo htmlspecialchars(format_setting_asset_url($projectFavicon)); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo APP_BASE_PATH; ?>/assets/css/public.css">
    <style>
        <?php echo $themeStyles; ?>
        body.public-shell { --public-highlight: <?php echo htmlspecialchars($accentColor); ?>; }
        .public-page { padding-top: 20px; }
    </style>
</head>
<body class="public-shell">
    <?php if (!$publicExperienceEnabled || $maintenanceMode): ?>
        <main class="public-page">
            <div class="public-container">
                <div class="public-empty" style="max-width: 760px; margin: 60px auto;">
                    <div class="public-kicker">Public Experience</div>
                    <h1><?php echo htmlspecialchars($maintenanceMode ? 'Maintenance Mode' : 'Public Experience Disabled'); ?></h1>
                    <p><?php echo htmlspecialchars($maintenanceMessage); ?></p>
                </div>
            </div>
        </main>
    <?php else: ?>
        <main class="public-page">
            <div class="public-container">
                <section class="public-hero">
                    <div class="public-hero-inner">
                        <span class="public-kicker"><?php echo htmlspecialchars($projectName); ?></span>
                        <h1 class="public-title"><?php echo nl2br(htmlspecialchars($landingWelcomeMessage)); ?></h1>
                        <?php if ($showProjectDescription): ?>
                            <p class="public-subtitle"><?php echo htmlspecialchars($projectDescription); ?></p>
                        <?php endif; ?>
                        <p class="public-tagline"><?php echo htmlspecialchars($projectSubtitle); ?></p>
                        <?php if (!empty($organizationName)): ?>
                            <p class="public-organization"><?php echo htmlspecialchars($organizationName); ?></p>
                        <?php endif; ?>
                        <?php if ($contentNote !== ''): ?>
                            <p class="public-content-note"><strong>Before you start:</strong> <?php echo htmlspecialchars($contentNote); ?></p>
                        <?php endif; ?>
                        <div class="public-actions">
                            <a class="public-button" href="<?php echo APP_BASE_PATH; ?>/viewer/scene.php?new=1"><?php echo htmlspecialchars($landingButtonText); ?></a>
                        </div>
                    </div>
                </section>

                <section class="public-story-intro">
                    <div class="public-story-grid">
                        <article class="public-story-card">
                            <strong>Watch</strong>
                            <p>Follow Riley, a teenager who just wants someone to listen, through <?php echo (int) $sceneCount; ?> animated scene<?php echo $sceneCount === 1 ? '' : 's'; ?>.</p>
                        </article>
                        <article class="public-story-card">
                            <strong>Explore</strong>
                            <p>When the story pauses, tap the glowing spots to read Riley's thoughts and spot the red flags in his chats.</p>
                        </article>
                        <article class="public-story-card">
                            <strong>Choose</strong>
                            <p>Decide what Riley does next. Your choices lead to different endings.</p>
                        </article>
                    </div>
                </section>
            </div>
        </main>
    <?php endif; ?>
</body>
</html>

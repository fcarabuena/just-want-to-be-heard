<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();
require_once __DIR__ . '/settings_helpers.php';

$pdo = getDatabaseConnection();
ensure_default_settings($pdo);

$settings = load_settings_map($pdo);
$projectLogoValue = setting_value($settings, 'project_logo', '');
$projectFaviconValue = setting_value($settings, 'project_favicon', '');
$projectLogoName = $projectLogoValue !== '' ? basename(parse_url($projectLogoValue, PHP_URL_PATH) ?: $projectLogoValue) : '';
$projectFaviconName = $projectFaviconValue !== '' ? basename(parse_url($projectFaviconValue, PHP_URL_PATH) ?: $projectFaviconValue) : '';

$statusMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fieldMap = [
        'project_name' => trim((string) ($_POST['project_name'] ?? APP_NAME)),
        'project_subtitle' => trim((string) ($_POST['project_subtitle'] ?? 'Interactive Animation Studio')),
        'project_description' => trim((string) ($_POST['project_description'] ?? '')),
        'organization_name' => trim((string) ($_POST['organization_name'] ?? 'I Just Want To Be Heard')),
        'public_theme' => trim((string) ($_POST['public_theme'] ?? 'dark')),
        'accent_color' => trim((string) ($_POST['accent_color'] ?? '#d4af6a')),
        'show_project_description' => isset($_POST['show_project_description']) ? '1' : '0',
        'show_scene_info' => isset($_POST['show_scene_info']) ? '1' : '0',
        'show_video_info' => isset($_POST['show_video_info']) ? '1' : '0',
        'landing_welcome_message' => trim((string) ($_POST['landing_welcome_message'] ?? 'I Just Want To Be Heard')),
        'landing_button_text' => trim((string) ($_POST['landing_button_text'] ?? 'Start Experience')),
        'viewer_autoplay' => isset($_POST['viewer_autoplay']) ? '1' : '0',
        'viewer_show_controls' => isset($_POST['viewer_show_controls']) ? '1' : '0',
        'viewer_allow_fullscreen' => isset($_POST['viewer_allow_fullscreen']) ? '1' : '0',
        'viewer_show_title' => isset($_POST['viewer_show_title']) ? '1' : '0',
        'viewer_show_description' => isset($_POST['viewer_show_description']) ? '1' : '0',
        'viewer_show_scene_number' => isset($_POST['viewer_show_scene_number']) ? '1' : '0',
        'viewer_show_nav' => isset($_POST['viewer_show_nav']) ? '1' : '0',
        'viewer_auto_advance' => isset($_POST['viewer_auto_advance']) ? '1' : '0',
        'default_video_autoplay' => isset($_POST['default_video_autoplay']) ? '1' : '0',
        'default_video_muted' => isset($_POST['default_video_muted']) ? '1' : '0',
        'default_video_controls' => isset($_POST['default_video_controls']) ? '1' : '0',
        'default_video_fullscreen' => isset($_POST['default_video_fullscreen']) ? '1' : '0',
        'public_show_nav' => isset($_POST['public_show_nav']) ? '1' : '0',
        'public_show_scene_list' => isset($_POST['public_show_scene_list']) ? '1' : '0',
        'public_show_video_metadata' => isset($_POST['public_show_video_metadata']) ? '1' : '0',
        'public_experience_enabled' => isset($_POST['public_experience_enabled']) ? '1' : '0',
        'default_scene_id' => trim((string) ($_POST['default_scene_id'] ?? '0')),
        'empty_state_message' => trim((string) ($_POST['empty_state_message'] ?? 'No public scene is available yet.')),
        'admin_display_name' => trim((string) ($_POST['admin_display_name'] ?? 'Animation Studio Administrator')),
        'organization_name_system' => trim((string) ($_POST['organization_name_system'] ?? 'I Just Want To Be Heard')),
        'system_timezone' => trim((string) ($_POST['system_timezone'] ?? 'UTC')),
        'date_format' => trim((string) ($_POST['date_format'] ?? 'M d, Y')),
        'time_format' => trim((string) ($_POST['time_format'] ?? 'H:i')),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        'maintenance_message' => trim((string) ($_POST['maintenance_message'] ?? 'The public experience is temporarily unavailable.')),
        'content_note_text' => trim((string) ($_POST['content_note_text'] ?? '')),
        'help_resources_text' => trim((string) ($_POST['help_resources_text'] ?? '')),
        'thankyou_heading' => trim((string) ($_POST['thankyou_heading'] ?? '')),
        'thankyou_message' => trim((string) ($_POST['thankyou_message'] ?? '')),
    ];

    foreach (['project_logo', 'project_favicon'] as $assetKey) {
        $uploadedPath = upload_setting_asset($assetKey, $assetKey === 'project_logo' ? 'branding' : 'branding', $assetKey === 'project_logo' ? ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'] : ['image/png', 'image/jpeg', 'image/x-icon', 'image/svg+xml', 'image/webp']);
        if ($uploadedPath !== null) {
            $fieldMap[$assetKey] = $uploadedPath;
        }
    }

    if ($fieldMap['project_name'] === '') {
        $fieldMap['project_name'] = APP_NAME;
    }

    foreach ($fieldMap as $key => $value) {
        save_setting($pdo, $key, $value, 'general');
    }

    $statusMessage = 'Settings saved successfully.';
    $settings = load_settings_map($pdo);
}

function setting_value($map, $key, $default = '') {
    return array_key_exists($key, $map) && $map[$key] !== null ? $map[$key] : $default;
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel">
        <div class="studio-header">
            <div>
                <div class="mini-kicker">APPLICATION CONFIGURATION</div>
                <h2 class="section-title-large">Application Configuration Center</h2>
                <p class="studio-subtitle">Configure the public experience, scene viewer behavior, and system defaults for the project.</p>
            </div>
        </div>

        <?php if ($statusMessage !== ''): ?>
            <div class="flash-message flash-success"><?php echo htmlspecialchars($statusMessage); ?></div>
        <?php endif; ?>

        <form method="POST" class="settings-form" enctype="multipart/form-data">
            <div class="settings-tabs" role="tablist" aria-label="Settings sections">
                <button type="button" class="settings-tab active" data-target="general">General</button>
                <button type="button" class="settings-tab" data-target="appearance">Appearance</button>
                <button type="button" class="settings-tab" data-target="experience">Public Experience</button>
                <button type="button" class="settings-tab" data-target="viewer">Scene Viewer</button>
                <button type="button" class="settings-tab" data-target="video">Video</button>
                <button type="button" class="settings-tab" data-target="system">System</button>
            </div>

            <div class="settings-panel active" id="general">
                <div class="setting-section-header">
                    <h3>General / Project Information</h3>
                    <p>Describe the project and its identity in the public-facing experience.</p>
                </div>

                <div class="form-grid two-col">
                    <div class="form-field">
                        <label for="project_name">Project / System Name</label>
                        <input id="project_name" name="project_name" type="text" value="<?php echo htmlspecialchars(setting_value($settings, 'project_name', APP_NAME)); ?>">
                    </div>
                    <div class="form-field">
                        <label for="project_subtitle">Project Subtitle</label>
                        <input id="project_subtitle" name="project_subtitle" type="text" value="<?php echo htmlspecialchars(setting_value($settings, 'project_subtitle', 'Interactive Animation Studio')); ?>">
                    </div>
                </div>

                <div class="form-field">
                    <label for="project_description">Project Description</label>
                    <textarea id="project_description" name="project_description" rows="4"><?php echo htmlspecialchars(setting_value($settings, 'project_description', 'An intimate story of voice, memory, and the human need to be witnessed.')); ?></textarea>
                </div>

                <div class="form-grid two-col">
                    <div class="form-field">
                        <label for="organization_name">Organization / School Name</label>
                        <input id="organization_name" name="organization_name" type="text" value="<?php echo htmlspecialchars(setting_value($settings, 'organization_name', 'I Just Want To Be Heard')); ?>">
                    </div>
                    <div class="form-field">
                        <label for="project_logo">Logo Upload</label>
                        <input id="project_logo" name="project_logo" type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp">
                        <?php if (!empty($projectLogoValue)): ?>
                            <div class="inline-preview"><img src="<?php echo htmlspecialchars(format_setting_asset_url($projectLogoValue)); ?>" alt="Current project logo"></div>
                            <p class="asset-meta"><strong>Current Logo:</strong> <?php echo htmlspecialchars($projectLogoName ?: 'unknown'); ?><br><strong>Location:</strong> uploads/branding/</p>
                        <?php endif; ?>
                    </div>
                    <div class="form-field">
                        <label for="project_favicon">Favicon Upload</label>
                        <input id="project_favicon" name="project_favicon" type="file" accept="image/png,image/jpeg,image/x-icon,image/svg+xml,image/webp">
                        <?php if (!empty($projectFaviconValue)): ?>
                            <div class="inline-preview small"><img src="<?php echo htmlspecialchars(format_setting_asset_url($projectFaviconValue)); ?>" alt="Current favicon"></div>
                            <p class="asset-meta"><strong>Current Favicon:</strong> <?php echo htmlspecialchars($projectFaviconName ?: 'unknown'); ?><br><strong>Location:</strong> uploads/branding/</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="settings-panel" id="appearance">
                <div class="setting-section-header">
                    <h3>Public Website / User Interface</h3>
                    <p>Control the public-facing theme and how the landing page reads to viewers.</p>
                </div>

                <div class="form-grid two-col">
                    <div class="form-field">
                        <label>Public Theme</label>
                        <div class="option-list theme-options">
                            <label class="option-card <?php echo setting_value($settings, 'public_theme', 'dark') === 'dark' ? 'selected' : ''; ?>">
                                <input type="radio" name="public_theme" value="dark" <?php echo setting_value($settings, 'public_theme', 'dark') === 'dark' ? 'checked' : ''; ?>>
                                <span>Dark Studio</span>
                            </label>
                            <label class="option-card <?php echo setting_value($settings, 'public_theme', 'dark') === 'light' ? 'selected' : ''; ?>">
                                <input type="radio" name="public_theme" value="light" <?php echo setting_value($settings, 'public_theme', 'dark') === 'light' ? 'checked' : ''; ?>>
                                <span>Light</span>
                            </label>
                            <label class="option-card <?php echo setting_value($settings, 'public_theme', 'dark') === 'system' ? 'selected' : ''; ?>">
                                <input type="radio" name="public_theme" value="system" <?php echo setting_value($settings, 'public_theme', 'dark') === 'system' ? 'checked' : ''; ?>>
                                <span>System Default</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-field">
                        <label for="accent_color">Accent Color</label>
                        <input id="accent_color" name="accent_color" type="color" value="<?php echo htmlspecialchars(setting_value($settings, 'accent_color', '#d4af6a')); ?>">
                    </div>
                </div>

                <div class="options-grid">
                    <label class="check-row"><input type="checkbox" name="show_project_description" <?php echo setting_value($settings, 'show_project_description', '1') === '1' ? 'checked' : ''; ?>> Show project description</label>
                    <label class="check-row"><input type="checkbox" name="show_scene_info" <?php echo setting_value($settings, 'show_scene_info', '1') === '1' ? 'checked' : ''; ?>> Show scene information</label>
                    <label class="check-row"><input type="checkbox" name="show_video_info" <?php echo setting_value($settings, 'show_video_info', '1') === '1' ? 'checked' : ''; ?>> Show video information</label>
                </div>

                <div class="form-grid two-col">
                    <div class="form-field">
                        <label for="landing_welcome_message">Landing Page Welcome Message</label>
                        <input id="landing_welcome_message" name="landing_welcome_message" type="text" value="<?php echo htmlspecialchars(setting_value($settings, 'landing_welcome_message', 'I Just Want To Be Heard')); ?>">
                    </div>
                    <div class="form-field">
                        <label for="landing_button_text">Landing Page Button Text</label>
                        <input id="landing_button_text" name="landing_button_text" type="text" value="<?php echo htmlspecialchars(setting_value($settings, 'landing_button_text', 'Start Experience')); ?>">
                    </div>
                </div>
                <div class="form-field">
                    <label for="content_note_text">Content Note (shown before the story starts)</label>
                    <textarea id="content_note_text" name="content_note_text" rows="3"><?php echo htmlspecialchars(setting_value($settings, 'content_note_text', '')); ?></textarea>
                </div>

                <div class="form-field">
                    <label for="help_resources_text">"Need to talk?" Help Resources (one per line)</label>
                    <textarea id="help_resources_text" name="help_resources_text" rows="5"><?php echo htmlspecialchars(setting_value($settings, 'help_resources_text', '')); ?></textarea>
                    <small class="inline-note">Double-check every hotline number with your adviser before the public showing.</small>
                </div>

                <div class="form-field">
                    <label for="thankyou_heading">Thank-you Pop-up Heading (shown when the viewer finishes the story)</label>
                    <input id="thankyou_heading" name="thankyou_heading" type="text" maxlength="255" value="<?php echo htmlspecialchars(setting_value($settings, 'thankyou_heading', "Thank you for listening to Riley's story")); ?>">
                </div>

                <div class="form-field">
                    <label for="thankyou_message">Thank-you Pop-up Message</label>
                    <textarea id="thankyou_message" name="thankyou_message" rows="3"><?php echo htmlspecialchars(setting_value($settings, 'thankyou_message', '')); ?></textarea>
                    <small class="inline-note">The pop-up also lists the help resources above that include a phone number, and has "Play again" and "Exit" buttons.</small>
                </div>
            </div>

            <div class="settings-panel" id="viewer">
                <div class="setting-section-header">
                    <h3>Scene Viewer Settings</h3>
                    <p>These options control how public scenes play and display in the viewer.</p>
                </div>

                <div class="options-grid">
                    <label class="check-row"><input type="checkbox" name="viewer_autoplay" <?php echo setting_value($settings, 'viewer_autoplay', '0') === '1' ? 'checked' : ''; ?>> Auto-play video</label>
                    <label class="check-row"><input type="checkbox" name="viewer_show_controls" <?php echo setting_value($settings, 'viewer_show_controls', '1') === '1' ? 'checked' : ''; ?>> Show video controls</label>
                    <label class="check-row"><input type="checkbox" name="viewer_allow_fullscreen" <?php echo setting_value($settings, 'viewer_allow_fullscreen', '1') === '1' ? 'checked' : ''; ?>> Allow fullscreen</label>
                    <label class="check-row"><input type="checkbox" name="viewer_show_title" <?php echo setting_value($settings, 'viewer_show_title', '1') === '1' ? 'checked' : ''; ?>> Show scene title</label>
                    <label class="check-row"><input type="checkbox" name="viewer_show_description" <?php echo setting_value($settings, 'viewer_show_description', '1') === '1' ? 'checked' : ''; ?>> Show scene description</label>
                    <label class="check-row"><input type="checkbox" name="viewer_show_scene_number" <?php echo setting_value($settings, 'viewer_show_scene_number', '1') === '1' ? 'checked' : ''; ?>> Show scene number</label>
                    <label class="check-row"><input type="checkbox" name="viewer_show_nav" <?php echo setting_value($settings, 'viewer_show_nav', '1') === '1' ? 'checked' : ''; ?>> Show navigation controls</label>
                    <label class="check-row"><input type="checkbox" name="viewer_auto_advance" <?php echo setting_value($settings, 'viewer_auto_advance', '0') === '1' ? 'checked' : ''; ?>> Enable automatic transition to the next scene</label>
                </div>
            </div>

            <div class="settings-panel" id="video">
                <div class="setting-section-header">
                    <h3>Video Settings</h3>
                    <p>Choose sensible defaults for how video content behaves in the public viewer.</p>
                </div>

                <div class="options-grid">
                    <label class="check-row"><input type="checkbox" name="default_video_autoplay" <?php echo setting_value($settings, 'default_video_autoplay', '0') === '1' ? 'checked' : ''; ?>> Auto-play</label>
                    <label class="check-row"><input type="checkbox" name="default_video_muted" <?php echo setting_value($settings, 'default_video_muted', '1') === '1' ? 'checked' : ''; ?>> Muted auto-play</label>
                    <label class="check-row"><input type="checkbox" name="default_video_controls" <?php echo setting_value($settings, 'default_video_controls', '1') === '1' ? 'checked' : ''; ?>> Show controls</label>
                    <label class="check-row"><input type="checkbox" name="default_video_fullscreen" <?php echo setting_value($settings, 'default_video_fullscreen', '1') === '1' ? 'checked' : ''; ?>> Allow fullscreen</label>
                </div>
            </div>

            <div class="settings-panel" id="experience">
                <div class="setting-section-header">
                    <h3>Public Experience</h3>
                    <p>Turn the public site on or off and decide what information the public viewer can see.</p>
                </div>

                <div class="options-grid">
                    <label class="check-row"><input type="checkbox" name="public_show_nav" <?php echo setting_value($settings, 'public_show_nav', '1') === '1' ? 'checked' : ''; ?>> Show navigation</label>
                    <label class="check-row"><input type="checkbox" name="public_show_scene_list" <?php echo setting_value($settings, 'public_show_scene_list', '1') === '1' ? 'checked' : ''; ?>> Show scene list</label>
                    <label class="check-row"><input type="checkbox" name="public_show_video_metadata" <?php echo setting_value($settings, 'public_show_video_metadata', '1') === '1' ? 'checked' : ''; ?>> Show video metadata</label>
                    <label class="check-row"><input type="checkbox" name="public_experience_enabled" <?php echo setting_value($settings, 'public_experience_enabled', '1') === '1' ? 'checked' : ''; ?>> Enable public experience</label>
                </div>

                <div class="form-grid two-col">
                    <div class="form-field">
                        <label for="default_scene_id">Default Scene</label>
                        <input id="default_scene_id" name="default_scene_id" type="number" min="0" value="<?php echo (int) setting_value($settings, 'default_scene_id', '0'); ?>">
                    </div>
                    <div class="form-field">
                        <label for="empty_state_message">Empty-State Message</label>
                        <input id="empty_state_message" name="empty_state_message" type="text" value="<?php echo htmlspecialchars(setting_value($settings, 'empty_state_message', 'No public scene is available yet.')); ?>">
                    </div>
                </div>
            </div>

            <div class="settings-panel" id="system">
                <div class="setting-section-header">
                    <h3>Administration / System</h3>
                    <p>Use these values for display naming, scheduling, and site availability.</p>
                </div>

                <div class="form-grid two-col">
                    <div class="form-field">
                        <label for="admin_display_name">Administrator Display Name</label>
                        <input id="admin_display_name" name="admin_display_name" type="text" value="<?php echo htmlspecialchars(setting_value($settings, 'admin_display_name', 'Animation Studio Administrator')); ?>">
                    </div>
                    <div class="form-field">
                        <label for="organization_name_system">Organization Name</label>
                        <input id="organization_name_system" name="organization_name_system" type="text" value="<?php echo htmlspecialchars(setting_value($settings, 'organization_name_system', 'I Just Want To Be Heard')); ?>">
                    </div>
                    <div class="form-field">
                        <label for="system_timezone">System Timezone</label>
                        <input id="system_timezone" name="system_timezone" type="text" value="<?php echo htmlspecialchars(setting_value($settings, 'system_timezone', 'UTC')); ?>">
                    </div>
                    <div class="form-field">
                        <label for="date_format">Date Format</label>
                        <input id="date_format" name="date_format" type="text" value="<?php echo htmlspecialchars(setting_value($settings, 'date_format', 'M d, Y')); ?>">
                    </div>
                    <div class="form-field">
                        <label for="time_format">Time Format</label>
                        <input id="time_format" name="time_format" type="text" value="<?php echo htmlspecialchars(setting_value($settings, 'time_format', 'H:i')); ?>">
                    </div>
                </div>

                <div class="options-grid">
                    <label class="check-row"><input type="checkbox" name="maintenance_mode" <?php echo setting_value($settings, 'maintenance_mode', '0') === '1' ? 'checked' : ''; ?>> Maintenance mode</label>
                </div>

                <div class="form-field">
                    <label for="maintenance_message">Maintenance Message</label>
                    <textarea id="maintenance_message" name="maintenance_message" rows="3"><?php echo htmlspecialchars(setting_value($settings, 'maintenance_message', 'The public experience is temporarily unavailable.')); ?></textarea>
                </div>
            </div>

            <div class="form-actions sticky-actions">
                <button type="submit" class="primary-btn">Save Settings</button>
            </div>
        </form>
    </div>
</section>

<style>
    .settings-form {
        margin-top: 24px;
    }
    .settings-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .settings-tab {
        background: rgba(255,255,255,0.02);
        color: var(--text);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 999px;
        padding: 10px 16px;
        cursor: pointer;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        font-size: 0.72rem;
    }
    .settings-tab.active {
        background: rgba(217,183,109,0.12);
        border-color: rgba(217,183,109,0.38);
        color: var(--gold-soft);
    }
    .settings-panel {
        display: none;
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 18px;
        padding: 22px;
    }
    .settings-panel.active {
        display: block;
    }
    .setting-section-header {
        margin-bottom: 18px;
    }
    .setting-section-header h3 {
        margin: 0 0 6px;
        font-size: 1rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--gold-soft);
    }
    .setting-section-header p {
        margin: 0;
        color: var(--muted);
    }
    .theme-options {
        display: grid;
        grid-template-columns: repeat(3, minmax(120px, 1fr));
        gap: 12px;
    }
    .option-card {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 70px;
        padding: 14px 12px;
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        background: rgba(255,255,255,0.02);
        text-align: center;
        cursor: pointer;
    }
    .option-card.selected {
        border-color: rgba(217,183,109,0.5);
        background: rgba(217,183,109,0.08);
    }
    .option-card input {
        margin-right: 8px;
    }
    .options-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px;
        margin: 20px 0;
    }
    .check-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 10px;
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.06);
        color: var(--text);
    }
    .inline-preview {
        margin-top: 10px;
        max-width: 220px;
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px;
        padding: 10px;
    }
    .inline-preview img {
        width: 100%;
        max-height: 120px;
        object-fit: contain;
    }
    .inline-preview.small {
        max-width: 96px;
    }
    .sticky-actions {
        margin-top: 22px;
        display: flex;
        justify-content: flex-end;
    }
</style>

<script>
    document.querySelectorAll('.settings-tab').forEach((button) => {
        button.addEventListener('click', () => {
            const target = button.dataset.target;
            document.querySelectorAll('.settings-tab').forEach((tab) => tab.classList.toggle('active', tab === button));
            document.querySelectorAll('.settings-panel').forEach((panel) => panel.classList.toggle('active', panel.id === target));
        });
    });
</script>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

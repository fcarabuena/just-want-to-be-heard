<?php

function normalize_bool($value, $default = false): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value)) {
        return $value === 1;
    }

    $string = strtolower(trim((string) $value));

    if ($string === '' || $string === '0' || $string === 'false' || $string === 'off' || $string === 'no') {
        return false;
    }

    if ($string === '1' || $string === 'true' || $string === 'on' || $string === 'yes') {
        return true;
    }

    return (bool) $default;
}

function load_settings_map(PDO $pdo): array
{
    $rows = $pdo->query('SELECT setting_key, setting_value FROM settings ORDER BY setting_key ASC')->fetchAll(PDO::FETCH_KEY_PAIR);
    return is_array($rows) ? $rows : [];
}

function get_setting(PDO $pdo, string $key, $default = '', bool $asBool = false)
{
    static $cache = null;

    if ($cache === null) {
        $cache = load_settings_map($pdo);
    }

    if (!array_key_exists($key, $cache)) {
        return $default;
    }

    $value = $cache[$key];

    if ($asBool) {
        return normalize_bool($value, (bool) $default);
    }

    return $value === null || $value === '' ? $default : $value;
}

function save_setting(PDO $pdo, string $key, $value, string $group = 'general'): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value, setting_group)
        VALUES (:setting_key, :setting_value, :setting_group)
        ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            setting_group = VALUES(setting_group),
            updated_at = CURRENT_TIMESTAMP'
    );

    $stmt->execute([
        ':setting_key' => $key,
        ':setting_value' => $value === null ? null : (string) $value,
        ':setting_group' => $group,
    ]);
}

function format_setting_asset_url(?string $value): string
{
    if ($value === null) {
        return '';
    }

    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    return app_asset_url($value);
}

function ensure_default_settings(PDO $pdo): void
{
    $defaults = [
        'project_name' => 'I Just Want To Be Heard',
        'project_subtitle' => 'Capstone Project - EMC',
        'project_description' => 'An intimate story of voice, memory, and the human need to be witnessed.',
        'organization_name' => 'Technological Institute of the Philippines',
        'project_logo' => '',
        'project_favicon' => '',
        'public_theme' => 'dark',
        'accent_color' => '#d4af6a',
        'show_project_description' => '1',
        'show_scene_info' => '1',
        'show_video_info' => '1',
        'landing_welcome_message' => 'I Just Want To Be Heard',
        'landing_button_text' => 'Launch Story',
        'viewer_autoplay' => '0',
        'viewer_show_controls' => '1',
        'viewer_allow_fullscreen' => '1',
        'viewer_show_title' => '1',
        'viewer_show_description' => '1',
        'viewer_show_scene_number' => '1',
        'viewer_show_nav' => '1',
        'viewer_auto_advance' => '0',
        'default_video_autoplay' => '0',
        'default_video_muted' => '1',
        'default_video_controls' => '1',
        'default_video_fullscreen' => '1',
        'max_upload_size' => '200',
        'allowed_video_formats' => 'mp4,webm',
        'public_show_nav' => '1',
        'public_show_scene_list' => '1',
        'public_show_video_metadata' => '1',
        'public_experience_enabled' => '1',
        'default_scene_id' => '0',
        'empty_state_message' => 'No public scene is available yet.',
        'admin_display_name' => 'Animation Studio Administrator',
        'organization_name_system' => 'I Just Want To Be Heard',
        'system_timezone' => 'UTC',
        'date_format' => 'M d, Y',
        'time_format' => 'H:i',
        'maintenance_mode' => '0',
        'maintenance_message' => 'The public experience is temporarily unavailable.',
        'content_note_text' => 'This story talks about online grooming, family conflict, and feeling alone. You can pause or leave at any time, and the "Need to talk?" button is always on screen.',
        'help_resources_text' => "Talk to a parent, guardian, teacher, or guidance counselor you trust.\nIn an emergency in the Philippines, call 911.\nBantay Bata 163: a free helpline for children and teens.\nIf someone online makes you uncomfortable: stop replying, save the messages, block, and tell an adult.",
    ];

    $existing = load_settings_map($pdo);
    foreach ($defaults as $key => $value) {
        if (!array_key_exists($key, $existing)) {
            save_setting($pdo, $key, $value, 'general');
        }
    }
}

function upload_setting_asset(string $fieldName, string $directoryName, array $allowedMimeTypes): ?string
{
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return null;
    }

    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK || empty($file['name'])) {
        return null;
    }

    $tmpName = $file['tmp_name'];
    if (!is_uploaded_file($tmpName)) {
        return null;
    }

    $mimeType = function_exists('finfo_file') ? @finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmpName) : null;
    if ($mimeType === false || $mimeType === null) {
        $mimeType = $_FILES[$fieldName]['type'] ?? '';
    }

    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        return null;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safeName = preg_replace('/[^A-Za-z0-9_.-]+/', '-', basename($file['name']));
    $safeName = trim($safeName, '.-_');
    if ($safeName === '') {
        return null;
    }

    $targetDir = APP_ROOT . '/uploads/' . $directoryName;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    $filename = time() . '-' . $safeName;
    $targetPath = $targetDir . '/' . $filename;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        return null;
    }

    return '/uploads/' . $directoryName . '/' . $filename;
}

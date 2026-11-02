<?php
/**
 * Optional story features, switched in Settings → Scene Viewer.
 * Both are off for the watch-only version of the story; the code and saved content stay in place,
 * so ticking a switch brings the feature back.
 *   hotspots: glowing spots viewers tap to read insights and red flags
 *   choices:  story choices, branching, ending cards and the journey recap
 */

require_once __DIR__ . '/../config/database.php';

function story_features(?PDO $pdo = null): array
{
    static $features = null;

    if ($features === null) {
        $pdo = $pdo ?? getDatabaseConnection();
        $rows = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('feature_hotspots', 'feature_choices')")->fetchAll(PDO::FETCH_KEY_PAIR);
        $features = [
            'hotspots' => ($rows['feature_hotspots'] ?? '0') === '1',
            'choices' => ($rows['feature_choices'] ?? '0') === '1',
        ];
    }

    return $features;
}

/** Admin pages of a switched-off feature send the admin to Settings, where the switch is. */
function require_story_feature(string $feature, ?PDO $pdo = null): void
{
    if (!story_features($pdo)[$feature]) {
        header('Location: ' . APP_BASE_PATH . '/admin/settings/index.php?feature_off=' . urlencode($feature) . '#viewer');
        exit;
    }
}

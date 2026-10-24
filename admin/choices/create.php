<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();
require_once __DIR__ . '/_form.php';

$pdo = getDatabaseConnection();
$scenes = $pdo->query('SELECT scene_id, scene_number, title FROM scenes ORDER BY display_order ASC, scene_number ASC')->fetchAll();

$errors = [];
$values = [
    'scene_id' => (string) ($_GET['scene_id'] ?? ''),
    'choice_text' => '',
    'target_scene_id' => '',
    'feedback_text' => '',
    'display_order' => '0',
    'is_active' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = read_choice_post();
    $errors = validate_choice($values);

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            INSERT INTO scene_choices (scene_id, choice_text, target_scene_id, feedback_text, display_order, is_active)
            VALUES (:scene_id, :choice_text, :target_scene_id, :feedback_text, :display_order, :is_active)
        ');
        $stmt->execute(choice_params($values));

        header('Location: ' . APP_BASE_PATH . '/admin/choices/index.php?status=created');
        exit;
    }
}

include __DIR__ . '/../../includes/layout/admin-header.php';
render_choice_form($values, $errors, $scenes, 'Add Choice', 'Save Choice');
include __DIR__ . '/../../includes/layout/admin-footer.php';

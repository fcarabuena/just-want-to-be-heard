<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();
require_once __DIR__ . '/_form.php';

$pdo = getDatabaseConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare('SELECT * FROM scene_choices WHERE choice_id = :id LIMIT 1');
$stmt->execute([':id' => (int) $id]);
$choice = $stmt->fetch();

if (!$choice) {
    header('Location: ' . APP_BASE_PATH . '/admin/choices/index.php');
    exit;
}

$scenes = $pdo->query('SELECT scene_id, scene_number, title FROM scenes ORDER BY display_order ASC, scene_number ASC')->fetchAll();

$errors = [];
$values = [
    'scene_id' => (string) $choice['scene_id'],
    'choice_text' => (string) $choice['choice_text'],
    'target_scene_id' => $choice['target_scene_id'] !== null ? (string) $choice['target_scene_id'] : '',
    'feedback_text' => (string) ($choice['feedback_text'] ?? ''),
    'display_order' => (string) $choice['display_order'],
    'is_active' => (int) $choice['is_active'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = read_choice_post();
    $errors = validate_choice($values);

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            UPDATE scene_choices
            SET scene_id = :scene_id, choice_text = :choice_text, target_scene_id = :target_scene_id,
                feedback_text = :feedback_text, display_order = :display_order, is_active = :is_active
            WHERE choice_id = :id
        ');
        $stmt->execute(choice_params($values) + [':id' => (int) $id]);

        header('Location: ' . APP_BASE_PATH . '/admin/choices/index.php?status=updated');
        exit;
    }
}

include __DIR__ . '/../../includes/layout/admin-header.php';
render_choice_form($values, $errors, $scenes, 'Edit Choice', 'Update Choice');
include __DIR__ . '/../../includes/layout/admin-footer.php';

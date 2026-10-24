<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();
require_once __DIR__ . '/_form.php';

$pdo = getDatabaseConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare('SELECT * FROM reflection_questions WHERE question_id = :id LIMIT 1');
$stmt->execute([':id' => (int) $id]);
$question = $stmt->fetch();

if (!$question) {
    header('Location: ' . APP_BASE_PATH . '/admin/reflections/index.php');
    exit;
}

$scenes = $pdo->query('SELECT scene_id, scene_number, title FROM scenes ORDER BY display_order ASC, scene_number ASC')->fetchAll();

$errors = [];
$values = [
    'scene_id' => (string) $question['scene_id'],
    'question_text' => (string) $question['question_text'],
    'question_type' => (string) $question['question_type'],
    'options_text' => (string) ($question['options_text'] ?? ''),
    'display_order' => (string) $question['display_order'],
    'is_active' => (int) $question['is_active'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = read_reflection_post();
    $errors = validate_reflection($values);

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            UPDATE reflection_questions
            SET scene_id = :scene_id, question_text = :question_text, question_type = :question_type,
                options_text = :options_text, display_order = :display_order, is_active = :is_active
            WHERE question_id = :id
        ');
        $stmt->execute(reflection_params($values) + [':id' => (int) $id]);

        header('Location: ' . APP_BASE_PATH . '/admin/reflections/index.php?status=updated');
        exit;
    }
}

include __DIR__ . '/../../includes/layout/admin-header.php';
render_reflection_form($values, $errors, $scenes, 'Edit Question', 'Update Question');
include __DIR__ . '/../../includes/layout/admin-footer.php';

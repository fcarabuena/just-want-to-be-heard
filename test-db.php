<?php
require __DIR__ . '/config/database.php';

try {
    $pdo = getDatabaseConnection();
    echo 'Database connection successful';
} catch (PDOException $exception) {
    echo 'Database connection failed: ' . htmlspecialchars($exception->getMessage());
}

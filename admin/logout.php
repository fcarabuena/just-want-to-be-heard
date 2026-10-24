<?php
require_once __DIR__ . '/../includes/auth.php';

// Logout is POST-only with a CSRF token so other sites can't sign an admin out via a link or image.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid()) {
    header('Location: ' . ADMIN_BASE_PATH . '/dashboard/index.php');
    exit;
}

auth_logout();

header('Location: ' . ADMIN_BASE_PATH . '/login.php?status=logged_out');
exit;

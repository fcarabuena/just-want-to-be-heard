<?php
require_once __DIR__ . '/../includes/auth.php';

header('Location: ' . ADMIN_BASE_PATH . (current_admin() !== null ? '/dashboard/index.php' : '/login.php'));
exit;

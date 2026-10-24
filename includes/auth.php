<?php
/** Admin authentication: sessions, login/logout, CSRF tokens and the require_admin() page guard. */

require_once __DIR__ . '/../config/database.php';

define('ADMIN_BASE_PATH', APP_BASE_PATH . '/admin');
define('ADMIN_SESSION_IDLE_SECONDS', 60 * 60 * 2);

function auth_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_name('jwtbh_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => APP_BASE_PATH . '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function current_admin(): ?array
{
    auth_start_session();

    if (empty($_SESSION['admin_id'])) {
        return null;
    }

    if (time() - ($_SESSION['last_activity'] ?? 0) > ADMIN_SESSION_IDLE_SECONDS) {
        auth_logout();
        return null;
    }

    $_SESSION['last_activity'] = time();

    return [
        'admin_id' => (int) $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'] ?? '',
        'full_name' => $_SESSION['admin_full_name'] ?? '',
        'role' => $_SESSION['admin_role'] ?? 'admin',
    ];
}

/** Call at the very top of every admin page, before any output or POST handling. */
function require_admin(): array
{
    $admin = current_admin();

    if ($admin === null) {
        $next = $_SERVER['REQUEST_METHOD'] === 'GET' ? ($_SERVER['REQUEST_URI'] ?? '') : '';
        $query = $next !== '' ? '?next=' . urlencode($next) : '';
        header('Location: ' . ADMIN_BASE_PATH . '/login.php' . $query);
        exit;
    }

    return $admin;
}

/** Returns the admin row on success, null on bad credentials or inactive account. */
function auth_attempt_login(PDO $pdo, string $login, string $password): ?array
{
    $stmt = $pdo->prepare('SELECT admin_id, username, full_name, role, password_hash, is_active FROM admins WHERE username = :login OR email = :login_email LIMIT 1');
    $stmt->execute([':login' => $login, ':login_email' => $login]);
    $admin = $stmt->fetch();

    // Verify against a dummy hash when the user is unknown so response time doesn't reveal valid usernames.
    $hash = $admin ? $admin['password_hash'] : '$2y$10$yFMQ4S.88SNrI1YW6qtQKedFXp.L.kTnzSGBFs8r2AMySDSwKaVwe';
    $valid = password_verify($password, $hash);

    if (!$admin || !$valid || (int) $admin['is_active'] !== 1) {
        return null;
    }

    if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
        $rehash = $pdo->prepare('UPDATE admins SET password_hash = :hash WHERE admin_id = :id');
        $rehash->execute([':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => $admin['admin_id']]);
    }

    $pdo->prepare('UPDATE admins SET last_login_at = NOW() WHERE admin_id = :id')->execute([':id' => $admin['admin_id']]);

    auth_start_session();
    session_regenerate_id(true);

    $_SESSION['admin_id'] = (int) $admin['admin_id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_full_name'] = $admin['full_name'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['last_activity'] = time();

    return $admin;
}

function auth_logout(): void
{
    auth_start_session();

    $_SESSION = [];

    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $params['path'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'],
    ]);

    session_destroy();
}

function csrf_token(): string
{
    auth_start_session();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_is_valid(): bool
{
    auth_start_session();

    $sent = $_POST['csrf_token'] ?? '';

    return is_string($sent) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $sent);
}

/** Only allow redirects back into the admin area, never to another site. */
function safe_admin_redirect(?string $next): string
{
    $default = ADMIN_BASE_PATH . '/dashboard/index.php';

    if (!is_string($next) || $next === '' || strpos($next, ADMIN_BASE_PATH . '/') !== 0 || strpos($next, '//') !== false || strpos($next, '\\') !== false) {
        return $default;
    }

    if (preg_match('#/(login|logout|setup)\.php#', $next)) {
        return $default;
    }

    return $next;
}

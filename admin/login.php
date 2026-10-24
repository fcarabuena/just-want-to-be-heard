<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDatabaseConnection();

if (current_admin() !== null) {
    header('Location: ' . safe_admin_redirect($_GET['next'] ?? null));
    exit;
}

$adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if ($adminCount === 0) {
    header('Location: ' . ADMIN_BASE_PATH . '/setup.php');
    exit;
}

$errors = [];
$login = '';
$next = $_POST['next'] ?? ($_GET['next'] ?? '');
$notice = ($_GET['status'] ?? '') === 'logged_out' ? 'You have been signed out.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_is_valid()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif ($login === '' || $password === '') {
        $errors[] = 'Please enter your username and password.';
    } elseif (auth_attempt_login($pdo, $login, $password) === null) {
        $errors[] = 'Invalid username or password.';
    } else {
        header('Location: ' . safe_admin_redirect($next));
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Sign In | <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo APP_BASE_PATH; ?>/assets/css/admin.css">
</head>
<body class="admin-body auth-body">
    <main class="auth-card content-panel studio-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">ANIMATION STUDIO</div>
                <h2 class="section-title-large">Admin Sign In</h2>
            </div>
        </div>

        <?php if ($notice !== ''): ?>
            <div class="flash-message flash-success"><?php echo htmlspecialchars($notice); ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="flash-message flash-error">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="studio-form" autocomplete="on">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="next" value="<?php echo htmlspecialchars((string) $next); ?>">

            <div class="form-field">
                <label for="login">Username or Email</label>
                <input type="text" id="login" name="login" value="<?php echo htmlspecialchars($login); ?>" autocomplete="username" required autofocus>
            </div>

            <div class="form-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-btn">Sign In</button>
                <a href="<?php echo APP_BASE_PATH; ?>/" class="secondary-link-btn">Back to Story</a>
            </div>
        </form>
    </main>
</body>
</html>

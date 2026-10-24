<?php
/** One-time setup: creates the first admin account. Locks itself once any admin exists. */
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDatabaseConnection();

$adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if ($adminCount > 0) {
    header('Location: ' . ADMIN_BASE_PATH . '/login.php');
    exit;
}

$errors = [];
$values = ['full_name' => '', 'username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
    $values['username'] = trim((string) ($_POST['username'] ?? ''));
    $values['email'] = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');

    if (!csrf_is_valid()) {
        $errors[] = 'Your session expired. Please try again.';
    }
    if ($values['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }
    if (!preg_match('/^[A-Za-z0-9_.-]{3,100}$/', $values['username'])) {
        $errors[] = 'Username must be 3-100 characters: letters, numbers, dot, dash or underscore.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 10) {
        $errors[] = 'Password must be at least 10 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Insert only if the table is still empty, so two simultaneous submissions can't both create an admin.
        $stmt = $pdo->prepare(
            'INSERT INTO admins (username, email, password_hash, full_name, role)
             SELECT :username, :email, :hash, :full_name, \'admin\'
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM admins)'
        );
        $stmt->execute([
            ':username' => $values['username'],
            ':email' => $values['email'],
            ':hash' => password_hash($password, PASSWORD_DEFAULT),
            ':full_name' => $values['full_name'],
        ]);

        if ($stmt->rowCount() === 0) {
            header('Location: ' . ADMIN_BASE_PATH . '/login.php');
            exit;
        }

        auth_attempt_login($pdo, $values['username'], $password);
        header('Location: ' . ADMIN_BASE_PATH . '/dashboard/index.php');
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
    <title>First-Time Setup | <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo APP_BASE_PATH; ?>/assets/css/admin.css">
</head>
<body class="admin-body auth-body">
    <main class="auth-card content-panel studio-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">FIRST-TIME SETUP</div>
                <h2 class="section-title-large">Create Admin Account</h2>
                <p class="inline-note">No admin accounts exist yet. This page disappears once the first one is created.</p>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="flash-message flash-error">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="studio-form">
            <?php echo csrf_field(); ?>

            <div class="form-field">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($values['full_name']); ?>" autocomplete="name" required>
            </div>

            <div class="form-field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($values['username']); ?>" autocomplete="username" required>
            </div>

            <div class="form-field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($values['email']); ?>" autocomplete="email" required>
            </div>

            <div class="form-field">
                <label for="password">Password (min. 10 characters)</label>
                <input type="password" id="password" name="password" autocomplete="new-password" minlength="10" required>
            </div>

            <div class="form-field">
                <label for="password_confirm">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" minlength="10" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-btn">Create Admin &amp; Sign In</button>
            </div>
        </form>
    </main>
</body>
</html>

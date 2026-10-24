<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars(APP_NAME); ?> | Animation Studio</title>
    <link rel="stylesheet" href="<?php echo APP_BASE_PATH; ?>/assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-shell">
        <?php include __DIR__ . '/admin-sidebar.php'; ?>

        <main class="admin-main">
            <header class="admin-topbar">
                <div class="topbar-title-wrap">
                    <div class="topbar-kicker">PROJECT</div>
                    <h1>I JUST WANT TO BE HEARD</h1>
                    <p>Interactive Animation Studio</p>
                </div>

                <div class="topbar-user">
                    <span>Signed in as <strong><?php echo htmlspecialchars($_SESSION['admin_full_name'] ?? $_SESSION['admin_username'] ?? 'Admin'); ?></strong></span>
                    <form method="POST" action="<?php echo APP_BASE_PATH; ?>/admin/logout.php">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="logout-link">Sign Out</button>
                    </form>
                </div>
            </header>

<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

$currentAdmin = require_admin();

$section = $_GET['section'] ?? 'This section';

include __DIR__ . '/../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel">
        <h2 class="section-title"><?php echo htmlspecialchars($section); ?></h2>
        <p class="inline-note">
            This management section will be implemented in a future phase.
        </p>
        <p class="inline-note">
            The current Phase 2 goal is to establish secure admin authentication and the Animation Studio dashboard shell.
        </p>
    </div>
</section>

<?php include __DIR__ . '/../includes/layout/admin-footer.php'; ?>

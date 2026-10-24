<?php
/**
 * Imports schema.sql and demo_seed.sql into a hosted MySQL database (e.g. Aiven) from the command line.
 * Uses PHP instead of XAMPP's MariaDB client, which cannot log in to MySQL 8 (caching_sha2_password).
 *
 * Settings come from the same environment variables as the site: DB_HOST, DB_PORT, DB_NAME, DB_USER,
 * DB_PASS, DB_SSL, DB_SSL_CA. Pass --schema-only to skip the demo story.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$config = getDatabaseConfig();
if ($config['pass'] === '' && $config['host'] !== '127.0.0.1') {
    fwrite(STDERR, "DB_PASS is empty. Set it in this terminal first.\n");
    exit(1);
}

$files = [__DIR__ . '/schema.sql'];
if (!in_array('--schema-only', $argv, true)) {
    $files[] = __DIR__ . '/demo_seed.sql';
}

try {
    // No dbname yet: schema.sql creates the database. Emulated prepares let one query() run a whole file.
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;charset=%s', $config['host'], $config['port'], $config['charset']),
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => true,
        ] + getDatabaseSslOptions($config)
    );
    echo "Connected to {$config['host']}:{$config['port']}\n";

    foreach ($files as $file) {
        // The SQL files name the XAMPP database; use DB_NAME instead so any database name works.
        $sql = str_replace('`just_want_to_be_heard`', '`' . str_replace('`', '', $config['db']) . '`', file_get_contents($file));
        $statement = $pdo->query($sql);
        // Step through every statement's result so an error in any of them is reported, not skipped.
        do {
        } while ($statement->nextRowset());
        $statement->closeCursor();
        echo 'Imported ' . basename($file) . "\n";
    }

    $pdo->exec('USE `' . str_replace('`', '', $config['db']) . '`');
    $counts = $pdo->query('SELECT (SELECT COUNT(*) FROM scenes) AS scenes, (SELECT COUNT(*) FROM videos) AS videos, (SELECT COUNT(*) FROM interactive_elements) AS hotspots')->fetch(PDO::FETCH_ASSOC);
    echo "Done. Database `{$config['db']}` has {$counts['scenes']} scenes, {$counts['videos']} videos, {$counts['hotspots']} hotspots.\n";
} catch (PDOException $exception) {
    fwrite(STDERR, 'Import failed: ' . $exception->getMessage() . "\n");
    exit(1);
}

<?php

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/../includes/schema_upgrade.php';

function getDatabaseConfig(): array
{
    return [
        'host' => env_value('DB_HOST', '127.0.0.1'),
        'port' => env_value('DB_PORT', '3306'),
        'db' => env_value('DB_NAME', 'just_want_to_be_heard'),
        'user' => env_value('DB_USER', 'root'),
        'pass' => env_value('DB_PASS', ''),
        'charset' => 'utf8mb4',
        // Hosted MySQL (e.g. Aiven) needs SSL. DB_SSL_CA is the CA certificate: its text or a file path.
        'ssl' => in_array(strtolower(env_value('DB_SSL', 'false')), ['1', 'true', 'yes', 'required'], true),
        'ssl_ca' => env_value('DB_SSL_CA', ''),
    ];
}

/** PDO wants the CA as a file, so certificate text from an environment variable is written to a temp file once. */
function getDatabaseSslOptions(array $config): array
{
    if (!$config['ssl']) {
        return [];
    }

    $ca = $config['ssl_ca'];
    if (strpos($ca, '-----BEGIN') !== false) {
        $caFile = sys_get_temp_dir() . '/db-ca-' . md5($ca) . '.pem';
        if (!is_file($caFile)) {
            file_put_contents($caFile, str_replace('\n', "\n", $ca));
        }
        $ca = $caFile;
    }

    if ($ca !== '' && is_file($ca)) {
        return [
            PDO::MYSQL_ATTR_SSL_CA => $ca,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
        ];
    }

    // No CA given: the connection is still encrypted, but the server certificate is not checked.
    return [
        PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];
}

function getDatabaseConnection(): PDO
{
    $config = getDatabaseConfig();

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['db'],
        $config['charset']
    );

    $pdo = new PDO(
        $dsn,
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
        ] + getDatabaseSslOptions($config)
    );

    ensure_interactive_schema($pdo);

    return $pdo;
}

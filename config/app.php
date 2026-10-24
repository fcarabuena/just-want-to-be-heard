<?php

/** Reads a setting from the environment (Render, Docker), falling back to the local XAMPP value. */
function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);

    return ($value === false || $value === '') ? $default : $value;
}

define('APP_NAME', 'I Just Want To Be Heard');
define('APP_ROOT', __DIR__ . '/..');
define('APP_URL', rtrim(env_value('APP_URL', 'http://localhost/just-want-to-be-heard'), '/'));
// Path part of APP_URL: "/just-want-to-be-heard" on XAMPP, "" when the site runs at the root of a domain.
define('APP_BASE_PATH', rtrim((string) parse_url(APP_URL, PHP_URL_PATH), '/'));
// Folder name older rows in the database were saved with, e.g. "/just-want-to-be-heard/uploads/videos/a.mp4".
define('LEGACY_BASE_PATH', '/just-want-to-be-heard');
define('UPLOAD_ROOT', APP_ROOT . '/uploads');
define('UPLOAD_URL', APP_BASE_PATH . '/uploads');

date_default_timezone_set('UTC');

/** Turns a stored upload path (with or without the old XAMPP folder prefix) into a link that works on this install. */
function app_asset_url(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '' || preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    $path = '/' . ltrim($path, '/');
    if (strpos($path, LEGACY_BASE_PATH . '/') === 0) {
        $path = substr($path, strlen(LEGACY_BASE_PATH));
    }

    return APP_BASE_PATH . $path;
}

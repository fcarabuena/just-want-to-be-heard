<?php

function ensureVideoUploadDirectory(): string
{
    $uploadRoot = UPLOAD_ROOT . '/videos';
    if (!is_dir($uploadRoot)) {
        if (!mkdir($uploadRoot, 0775, true) && !is_dir($uploadRoot)) {
            throw new RuntimeException('Unable to create the video upload directory.');
        }
    }

    return $uploadRoot;
}

function getVideoUploadUrlBase(): string
{
    return UPLOAD_URL . '/videos';
}

/** Stored form of a video path: "/uploads/videos/<file>", without the site folder, so it works on any install. */
function getCanonicalVideoUrlPath(string $pathOrFilename): string
{
    $normalized = trim((string) $pathOrFilename);
    if ($normalized === '') {
        return '/uploads/videos';
    }

    $candidate = substr(app_asset_url($normalized), strlen(APP_BASE_PATH));

    if (strpos($candidate, '/uploads/videos/') === 0) {
        return $candidate;
    }

    return '/uploads/videos/' . basename($normalized);
}

function getAllowedVideoMimeTypes(): array
{
    return [
        'video/mp4',
        'video/webm',
        'video/quicktime',
        'video/x-matroska',
        'video/x-msvideo',
        'video/mp2t',
        'video/3gpp',
    ];
}

function getAllowedVideoExtensions(): array
{
    return ['mp4', 'webm', 'mov', 'm4v', 'mkv', 'avi'];
}

function formatVideoFileSize(int $bytes): string
{
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB'];
    $size = (float) $bytes;
    $unitIndex = 0;

    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }

    if ($unitIndex === 0) {
        return sprintf('%d %s', (int) $size, $units[$unitIndex]);
    }

    return sprintf('%.1f %s', $size, $units[$unitIndex]);
}

function formatVideoDuration(?int $seconds): string
{
    if ($seconds === null || $seconds < 0) {
        return 'N/A';
    }

    $minutes = intdiv((int) $seconds, 60);
    $remainingSeconds = (int) $seconds % 60;

    return sprintf('%02d:%02d', $minutes, $remainingSeconds);
}

function buildSafeVideoFilename(string $originalName): string
{
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $baseName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($originalName, PATHINFO_FILENAME));
    $baseName = trim($baseName, '-_');
    $baseName = $baseName !== '' ? $baseName : 'video';

    return sprintf('%s-%s.%s', $baseName, bin2hex(random_bytes(6)), $extension);
}

function validateUploadedVideoFile(array $file): array
{
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('The uploaded video file is invalid.');
    }

    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The video upload failed. Please try again.');
    }

    $size = filesize($file['tmp_name']);
    if ($size === false || $size <= 0) {
        throw new RuntimeException('The uploaded video file is empty.');
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = getAllowedVideoExtensions();
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Unsupported video file type. Please upload MP4, WebM, MOV, MKV, or AVI.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo !== false ? finfo_file($finfo, $file['tmp_name']) : null;
    finfo_close($finfo);

    $allowedMimeTypes = getAllowedVideoMimeTypes();
    if ($mimeType === null || !in_array($mimeType, $allowedMimeTypes, true)) {
        throw new RuntimeException('The uploaded video has an unsupported MIME type.');
    }

    return [
        'tmp_name' => $file['tmp_name'],
        'size' => $size,
        'mime_type' => $mimeType,
        'extension' => $extension,
    ];
}

function storeUploadedVideoFile(array $file): array
{
    $validated = validateUploadedVideoFile($file);

    $uploadDir = ensureVideoUploadDirectory();
    $filename = buildSafeVideoFilename($file['name']);
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($validated['tmp_name'], $targetPath)) {
        throw new RuntimeException('The video could not be stored securely.');
    }

    $relativePath = getCanonicalVideoUrlPath('/uploads/videos/' . $filename);

    return [
        'filename' => $filename,
        'file_path' => $relativePath,
        'mime_type' => $validated['mime_type'],
        'file_size' => $validated['size'],
        'extension' => $validated['extension'],
        'absolute_path' => $targetPath,
    ];
}

function deleteVideoFileIfExists(?string $filePath): void
{
    if (empty($filePath)) {
        return;
    }

    $relative = ltrim((string) $filePath, '/');
    if ($relative === '') {
        return;
    }

    $legacy = ltrim(LEGACY_BASE_PATH, '/') . '/';
    if (strpos($relative, $legacy) === 0) {
        $relative = substr($relative, strlen($legacy));
    }

    if (strpos($relative, 'uploads/videos/') === false) {
        return;
    }

    $absolutePath = APP_ROOT . '/' . $relative;
    if (is_file($absolutePath)) {
        @unlink($absolutePath);
    }
}

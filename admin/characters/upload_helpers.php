<?php

function ensureCharacterUploadDirectory(): string
{
    $uploadRoot = UPLOAD_ROOT . '/characters';
    if (!is_dir($uploadRoot)) {
        if (!mkdir($uploadRoot, 0775, true) && !is_dir($uploadRoot)) {
            throw new RuntimeException('Unable to create the character upload directory.');
        }
    }

    return $uploadRoot;
}

function getAllowedCharacterMimeTypes(): array
{
    return [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
    ];
}

function getAllowedCharacterExtensions(): array
{
    return ['png', 'jpg', 'jpeg', 'gif', 'webp'];
}

function buildSafeCharacterFilename(string $originalName): string
{
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $baseName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($originalName, PATHINFO_FILENAME));
    $baseName = trim($baseName, '-_');
    $baseName = $baseName !== '' ? $baseName : 'character';

    return sprintf('%s-%s.%s', $baseName, bin2hex(random_bytes(8)), $extension);
}

function validateUploadedCharacterAsset(array $file): array
{
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('The uploaded character asset is invalid.');
    }

    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The character asset upload failed. Please try again.');
    }

    $size = filesize($file['tmp_name']);
    if ($size === false || $size <= 0) {
        throw new RuntimeException('The uploaded character asset is empty.');
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = getAllowedCharacterExtensions();
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Unsupported character asset type. Please upload a PNG, JPG, GIF, or WEBP image.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo !== false ? finfo_file($finfo, $file['tmp_name']) : null;
    finfo_close($finfo);

    $allowedMimeTypes = getAllowedCharacterMimeTypes();
    if ($mimeType === null || !in_array($mimeType, $allowedMimeTypes, true)) {
        throw new RuntimeException('The uploaded character asset has an unsupported MIME type.');
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new RuntimeException('The uploaded file is not a valid image asset.');
    }

    return [
        'tmp_name' => $file['tmp_name'],
        'size' => $size,
        'mime_type' => $mimeType,
        'extension' => $extension,
    ];
}

function storeUploadedCharacterAsset(array $file): array
{
    $validated = validateUploadedCharacterAsset($file);

    $uploadDir = ensureCharacterUploadDirectory();
    $filename = buildSafeCharacterFilename($file['name']);
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($validated['tmp_name'], $targetPath)) {
        throw new RuntimeException('The character asset could not be stored securely.');
    }

    $relativePath = '/uploads/characters/' . $filename;

    return [
        'filename' => $filename,
        'file_path' => $relativePath,
        'mime_type' => $validated['mime_type'],
        'file_size' => $validated['size'],
        'extension' => $validated['extension'],
        'absolute_path' => $targetPath,
    ];
}

function deleteCharacterImageIfExists(?string $filePath): void
{
    if (empty($filePath)) {
        return;
    }

    $relative = ltrim($filePath, '/');
    if ($relative === '' || strpos($relative, 'uploads/characters/') === false) {
        return;
    }

    $absolutePath = APP_ROOT . '/' . $relative;
    if (is_file($absolutePath)) {
        @unlink($absolutePath);
    }
}

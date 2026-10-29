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
        throw new RuntimeException(describeVideoUploadError((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)));
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

/** Explains a PHP upload error code in words the admin can act on. */
function describeVideoUploadError(int $code): string
{
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'This video is bigger than the server accepts (' . ini_get('upload_max_filesize') . '). Export a smaller file and try again.';
        case UPLOAD_ERR_PARTIAL:
            return 'The upload was interrupted before it finished. Check your connection and try again.';
        case UPLOAD_ERR_NO_FILE:
            return 'Please select a video file to upload.';
        default:
            return 'The video upload failed. Please try again.';
    }
}

/** True when PHP dropped the whole form because the request was bigger than post_max_size. */
function requestExceededPostLimit(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
}

// ---------- Cloudinary (permanent video storage for hosts with a temporary disk, such as Render) ----------

// Cloudinary's free plan accepts videos up to 100 MB per file.
define('CLOUDINARY_MAX_VIDEO_BYTES', 100 * 1024 * 1024);

/** Cloudinary account from CLOUDINARY_URL (cloudinary://API_KEY:API_SECRET@CLOUD_NAME), or null to keep files on this server. */
function getCloudinaryConfig(): ?array
{
    $url = env_value('CLOUDINARY_URL');
    if ($url === '') {
        return null;
    }

    $parts = parse_url($url);
    if (($parts['scheme'] ?? '') !== 'cloudinary' || empty($parts['user']) || empty($parts['pass']) || empty($parts['host'])) {
        throw new RuntimeException('CLOUDINARY_URL is not in the form cloudinary://API_KEY:API_SECRET@CLOUD_NAME. Copy it again from the Cloudinary dashboard.');
    }

    return ['cloud' => $parts['host'], 'key' => urldecode($parts['user']), 'secret' => urldecode($parts['pass'])];
}

function isCloudinaryUrl(?string $path): bool
{
    return is_string($path) && preg_match('#^https://res\.cloudinary\.com/#i', $path) === 1;
}

/** Turns a Cloudinary error into a clear message, especially when the account is out of space. */
function describeCloudinaryError(string $message, int $status): string
{
    if (preg_match('/quota|storage|usage|limit exceeded|exceeded|credits|plan/i', $message)) {
        return 'Cloud video storage is full, so the video was not saved. Remove videos you no longer use (Video Library → Remove video), or free up space in your Cloudinary account, then try again. (Cloudinary said: ' . $message . ')';
    }
    if (preg_match('/file size too large|too large/i', $message)) {
        return 'This video is too large for cloud storage (the free plan allows up to 100 MB per video). Export a smaller file and try again.';
    }
    if ($status === 401 || preg_match('/api_key|signature|invalid.*key|unknown api/i', $message)) {
        return 'Cloud storage rejected the login. Check that CLOUDINARY_URL on the server is copied exactly from Cloudinary. (Cloudinary said: ' . $message . ')';
    }

    return 'Cloud storage could not save the video: ' . $message;
}

/** Signed call to Cloudinary's video API ("upload" or "destroy"). */
function cloudinaryRequest(array $config, string $action, array $params, ?CURLFile $file = null): array
{
    $params['timestamp'] = (string) time();
    ksort($params);
    $pairs = [];
    foreach ($params as $key => $value) {
        $pairs[] = $key . '=' . $value;
    }
    $params['signature'] = sha1(implode('&', $pairs) . $config['secret']);
    $params['api_key'] = $config['key'];
    if ($file !== null) {
        $params['file'] = $file;
    }

    $curl = curl_init('https://api.cloudinary.com/v1_1/' . rawurlencode($config['cloud']) . '/video/' . $action);
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $params,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 600,
    ]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($body === false) {
        throw new RuntimeException('Could not reach cloud video storage (' . $curlError . '). Check the connection and try again.');
    }

    $data = json_decode((string) $body, true);
    if (!is_array($data) || $status >= 400 || isset($data['error'])) {
        $message = is_array($data) ? (string) ($data['error']['message'] ?? 'HTTP ' . $status) : 'HTTP ' . $status;
        throw new RuntimeException(describeCloudinaryError($message, $status));
    }

    return $data;
}

/** Public ID of an uploaded Cloudinary video, taken from its delivery URL. */
function cloudinaryPublicIdFromUrl(string $url): ?string
{
    return preg_match('#/video/upload/(?:v\d+/)?(.+)\.[a-z0-9]+$#i', $url, $match) === 1 ? $match[1] : null;
}

function storeUploadedVideoFile(array $file): array
{
    $validated = validateUploadedVideoFile($file);

    $cloudinary = getCloudinaryConfig();
    if ($cloudinary !== null) {
        if ($validated['size'] > CLOUDINARY_MAX_VIDEO_BYTES) {
            throw new RuntimeException(sprintf('This video is %s. Cloud storage accepts videos up to 100 MB, so export a smaller file (for example a lower bitrate) and try again.', formatVideoFileSize((int) $validated['size'])));
        }

        $filename = buildSafeVideoFilename($file['name']);
        $result = cloudinaryRequest($cloudinary, 'upload', [
            'folder' => 'just-want-to-be-heard',
            'public_id' => pathinfo($filename, PATHINFO_FILENAME),
        ], new CURLFile($validated['tmp_name'], $validated['mime_type'], $filename));

        if (empty($result['secure_url'])) {
            throw new RuntimeException('Cloud storage did not return a link for the video. Please try again.');
        }

        return [
            'filename' => $filename,
            'file_path' => (string) $result['secure_url'],
            'mime_type' => $validated['mime_type'],
            'file_size' => (int) ($result['bytes'] ?? $validated['size']),
            'extension' => $validated['extension'],
            'absolute_path' => null,
        ];
    }

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

    if (isCloudinaryUrl($filePath)) {
        // Removing the cloud copy frees storage space; a failure here must not block deleting the video record.
        $publicId = cloudinaryPublicIdFromUrl($filePath);
        try {
            $cloudinary = getCloudinaryConfig();
            if ($cloudinary !== null && $publicId !== null) {
                cloudinaryRequest($cloudinary, 'destroy', ['public_id' => $publicId, 'invalidate' => 'true']);
            }
        } catch (RuntimeException $e) {
            error_log('Cloudinary delete failed for ' . $publicId . ': ' . $e->getMessage());
        }
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

/** True when a video stored on this server no longer has its file (e.g. wiped by a host restart). */
function videoFileIsMissing(?string $filePath): bool
{
    if (empty($filePath) || preg_match('#^https?://#i', $filePath) === 1) {
        return false;
    }

    $relative = substr(app_asset_url($filePath), strlen(APP_BASE_PATH));

    return !is_file(APP_ROOT . $relative);
}

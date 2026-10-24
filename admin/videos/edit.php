<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();
require_once __DIR__ . '/upload_helpers.php';

$pdo = getDatabaseConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    header('Location: ' . APP_BASE_PATH . '/admin/videos/index.php?status=invalid');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM videos WHERE video_id = :id AND is_deleted = 0 LIMIT 1');
$stmt->execute([':id' => $id]);
$video = $stmt->fetch();

if (!$video) {
    header('Location: ' . APP_BASE_PATH . '/admin/videos/index.php?status=missing');
    exit;
}

$errors = [];
$values = [
    'title' => $video['title'],
    'mime_type' => $video['mime_type'],
    'file_size' => (string) $video['file_size'],
    'duration_seconds' => $video['duration_seconds'] !== null ? (string) $video['duration_seconds'] : '',
    'resolution' => $video['resolution'] ?? '',
    'is_published' => (int) $video['is_published'],
    'file_path' => $video['file_path'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['title'] = trim((string) ($_POST['title'] ?? $video['title']));
    $values['mime_type'] = trim((string) ($_POST['mime_type'] ?? ($video['mime_type'] ?? 'video/mp4')));
    $values['file_size'] = trim((string) ($_POST['file_size'] ?? $video['file_size']));
    $values['duration_seconds'] = trim((string) ($_POST['duration_seconds'] ?? ($video['duration_seconds'] ?? '')));
    $values['resolution'] = trim((string) ($_POST['resolution'] ?? ($video['resolution'] ?? '')));
    $values['is_published'] = isset($_POST['is_published']) ? 1 : 0;
    $replaceVideoFile = isset($_FILES['video_file']) && $_FILES['video_file']['error'] !== UPLOAD_ERR_NO_FILE;

    if ($values['title'] === '') {
        $errors[] = 'Video title is required.';
    }

    $durationSeconds = $values['duration_seconds'] !== '' ? filter_var($values['duration_seconds'], FILTER_VALIDATE_INT) : null;
    if ($values['duration_seconds'] !== '' && ($durationSeconds === false || $durationSeconds < 0)) {
        $errors[] = 'Duration must be a valid number of seconds.';
    }

    $fileSize = $values['file_size'] !== '' ? filter_var($values['file_size'], FILTER_VALIDATE_INT) : null;
    if ($values['file_size'] !== '' && ($fileSize === false || $fileSize < 0)) {
        $errors[] = 'File size must be a valid number of bytes.';
    }

    $uploadedVideo = null;
    $oldFilePath = $video['file_path'] ?? '';
    $newFilePath = $oldFilePath;

    if ($replaceVideoFile) {
        if ($_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The replacement video upload failed. Please try again.';
        } else {
            try {
                $uploadedVideo = storeUploadedVideoFile($_FILES['video_file']);
                $newFilePath = $uploadedVideo['file_path'];
                $values['file_path'] = $newFilePath;
                $values['mime_type'] = $uploadedVideo['mime_type'];
                $values['file_size'] = (string) $uploadedVideo['file_size'];
                $values['duration_seconds'] = $durationSeconds !== null ? (string) $durationSeconds : ((string) round((int) ($_POST['duration_seconds'] ?? 0)));
                $values['resolution'] = $values['resolution'] !== '' ? $values['resolution'] : null;
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    if (empty($errors)) {
        $resolvedFileSize = $values['file_size'] !== '' ? (int) $values['file_size'] : 0;
        $resolvedDuration = $values['duration_seconds'] !== '' ? (int) $values['duration_seconds'] : null;
        $resolvedResolution = $values['resolution'] !== '' ? $values['resolution'] : null;

        $updateSql = "
            UPDATE videos
            SET title = :title,
                filename = :filename,
                file_path = :file_path,
                mime_type = :mime_type,
                file_size = :file_size,
                duration_seconds = :duration_seconds,
                resolution = :resolution,
                is_published = :is_published,
                updated_at = CURRENT_TIMESTAMP
            WHERE video_id = :id
        ";

        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([
            ':title' => $values['title'],
            ':filename' => basename($newFilePath),
            ':file_path' => $newFilePath,
            ':mime_type' => $values['mime_type'],
            ':file_size' => $resolvedFileSize,
            ':duration_seconds' => $resolvedDuration,
            ':resolution' => $resolvedResolution,
            ':is_published' => $values['is_published'],
            ':id' => $id,
        ]);

        if ($uploadedVideo !== null && !empty($oldFilePath) && $oldFilePath !== $newFilePath) {
            deleteVideoFileIfExists($oldFilePath);
        }

        header('Location: ' . APP_BASE_PATH . '/admin/videos/index.php?status=updated');
        exit;
    }
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">EDIT VIDEO</div>
                <h2 class="section-title-large">Edit Video</h2>
            </div>
            <a href="<?php echo APP_BASE_PATH; ?>/admin/videos/index.php" class="secondary-link-btn">Back to Library</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="flash-message flash-error">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="video-metadata-summary">
            <div class="form-grid three-col">
                <div class="form-field">
                    <label>Current Video</label>
                    <div class="metadata-readout"><?php echo htmlspecialchars(basename($video['file_path'] ?? '')) ?: 'No file'; ?></div>
                </div>
                <div class="form-field">
                    <label>Current Size</label>
                    <div class="metadata-readout"><?php echo htmlspecialchars(formatVideoFileSize((int) ($video['file_size'] ?? 0))); ?></div>
                </div>
                <div class="form-field">
                    <label>Current Duration</label>
                    <div class="metadata-readout"><?php echo htmlspecialchars(formatVideoDuration((int) ($video['duration_seconds'] ?? 0))); ?></div>
                </div>
            </div>
        </div>

        <form method="POST" class="studio-form" enctype="multipart/form-data">
            <div class="form-grid two-col">
                <div class="form-field">
                    <label for="title">Video Title</label>
                    <input id="title" name="title" type="text" value="<?php echo htmlspecialchars((string) ($values['title'] ?? '')); ?>" required>
                </div>

                <div class="form-field">
                    <label for="video_file">Replace Video</label>
                    <input id="video_file" name="video_file" type="file" accept="video/mp4,video/webm,video/quicktime,video/x-matroska,video/x-msvideo,video/mp2t,video/3gpp">
                </div>
            </div>

            <div class="video-metadata-preview" aria-live="polite">
                <div class="form-grid three-col">
                    <div class="form-field">
                        <label>Selected File</label>
                        <div class="metadata-readout" id="selected_file_label">No replacement selected</div>
                    </div>
                    <div class="form-field">
                        <label>File Size</label>
                        <div class="metadata-readout" id="selected_file_size">—</div>
                    </div>
                    <div class="form-field">
                        <label>Duration</label>
                        <div class="metadata-readout" id="selected_duration_label">—</div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="mime_type" id="mime_type" value="<?php echo htmlspecialchars((string) ($values['mime_type'] ?? 'video/mp4')); ?>">
            <input type="hidden" name="file_size" id="file_size" value="<?php echo htmlspecialchars((string) ($values['file_size'] ?? '')); ?>">
            <input type="hidden" name="duration_seconds" id="duration_seconds" value="<?php echo htmlspecialchars((string) ($values['duration_seconds'] ?? '')); ?>">
            <input type="hidden" name="resolution" id="resolution" value="<?php echo htmlspecialchars((string) ($values['resolution'] ?? '')); ?>">

            <div class="form-field checkbox-field">
                <label class="checkbox-wrap">
                    <input type="checkbox" name="is_published" value="1" <?php echo ((int) ($values['is_published'] ?? 0) === 1) ? 'checked' : ''; ?>>
                    <span>Published</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-btn">Update Video</button>
                <a href="<?php echo APP_BASE_PATH; ?>/admin/videos/index.php" class="secondary-link-btn">Cancel</a>
            </div>
        </form>
    </div>
</section>

<script>
    (function () {
        const input = document.getElementById('video_file');
        const selectedLabel = document.getElementById('selected_file_label');
        const selectedSize = document.getElementById('selected_file_size');
        const selectedDuration = document.getElementById('selected_duration_label');
        const fileSizeHidden = document.getElementById('file_size');
        const mimeTypeHidden = document.getElementById('mime_type');
        const durationHidden = document.getElementById('duration_seconds');
        const resolutionHidden = document.getElementById('resolution');

        function formatBytes(bytes) {
            if (!bytes) return '0 B';
            const units = ['B', 'KB', 'MB', 'GB'];
            let size = bytes;
            let unitIndex = 0;
            while (size >= 1024 && unitIndex < units.length - 1) {
                size /= 1024;
                unitIndex++;
            }
            return unitIndex === 0 ? parseInt(size, 10) + ' ' + units[unitIndex] : size.toFixed(1) + ' ' + units[unitIndex];
        }

        function formatDuration(seconds) {
            if (!Number.isFinite(seconds) || seconds < 0) return '—';
            const totalSeconds = Math.max(0, Math.round(seconds));
            const minutes = Math.floor(totalSeconds / 60);
            const remainder = totalSeconds % 60;
            return String(minutes).padStart(2, '0') + ':' + String(remainder).padStart(2, '0');
        }

        input.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file) {
                selectedLabel.textContent = 'No replacement selected';
                selectedSize.textContent = '—';
                selectedDuration.textContent = '—';
                fileSizeHidden.value = document.getElementById('file_size').dataset.original || '';
                durationHidden.value = document.getElementById('duration_seconds').dataset.original || '';
                mimeTypeHidden.value = '<?php echo htmlspecialchars((string) ($values['mime_type'] ?? 'video/mp4')); ?>';
                resolutionHidden.value = '<?php echo htmlspecialchars((string) ($values['resolution'] ?? '')); ?>';
                return;
            }

            selectedLabel.textContent = file.name;
            selectedSize.textContent = formatBytes(file.size);
            fileSizeHidden.value = String(file.size);
            mimeTypeHidden.value = file.type || 'video/mp4';

            const objectUrl = URL.createObjectURL(file);
            const video = document.createElement('video');
            video.preload = 'metadata';
            video.src = objectUrl;

            video.onloadedmetadata = function () {
                const duration = Number.isFinite(video.duration) ? video.duration : 0;
                const durationSeconds = Math.round(duration);
                selectedDuration.textContent = formatDuration(duration);
                durationHidden.value = String(durationSeconds);
                if (video.videoWidth && video.videoHeight) {
                    resolutionHidden.value = video.videoWidth + ' × ' + video.videoHeight;
                }
                URL.revokeObjectURL(objectUrl);
            };

            video.onerror = function () {
                selectedDuration.textContent = '—';
                durationHidden.value = '';
                URL.revokeObjectURL(objectUrl);
            };
        });
    })();
</script>

<?php include __DIR__ . '/../../includes/layout/admin-footer.php'; ?>

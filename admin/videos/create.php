<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$currentAdmin = require_admin();
require_once __DIR__ . '/upload_helpers.php';

$pdo = getDatabaseConnection();

$errors = [];
$values = [
    'title' => '',
    'mime_type' => 'video/mp4',
    'file_size' => '',
    'duration_seconds' => '',
    'resolution' => '',
    'is_published' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['title'] = trim((string) ($_POST['title'] ?? ''));
    $values['mime_type'] = trim((string) ($_POST['mime_type'] ?? 'video/mp4'));
    $values['file_size'] = trim((string) ($_POST['file_size'] ?? ''));
    $values['duration_seconds'] = trim((string) ($_POST['duration_seconds'] ?? ''));
    $values['resolution'] = trim((string) ($_POST['resolution'] ?? ''));
    $values['is_published'] = isset($_POST['is_published']) ? 1 : 0;

    if ($values['title'] === '') {
        $errors[] = 'Video title is required.';
    }

    if (!isset($_FILES['video_file']) || $_FILES['video_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please select a video file to upload.';
    }

    $durationSeconds = $values['duration_seconds'] !== '' ? filter_var($values['duration_seconds'], FILTER_VALIDATE_INT) : null;
    if ($values['duration_seconds'] !== '' && ($durationSeconds === false || $durationSeconds < 0)) {
        $errors[] = 'Duration must be a valid number of seconds.';
    }

    $fileSize = $values['file_size'] !== '' ? filter_var($values['file_size'], FILTER_VALIDATE_INT) : null;
    if ($values['file_size'] !== '' && ($fileSize === false || $fileSize < 0)) {
        $errors[] = 'File size must be a valid number of bytes.';
    }

    if (empty($errors)) {
        try {
            $uploadResult = storeUploadedVideoFile($_FILES['video_file']);
            $resolvedMimeType = $uploadResult['mime_type'];
            $resolvedFileSize = $uploadResult['file_size'];
            $resolvedDuration = $durationSeconds !== null ? $durationSeconds : null;
            $resolvedResolution = $values['resolution'] !== '' ? $values['resolution'] : null;

            $insertSql = "
                INSERT INTO videos (title, filename, file_path, mime_type, file_size, duration_seconds, resolution, is_published, is_deleted, uploaded_by)
                VALUES (:title, :filename, :file_path, :mime_type, :file_size, :duration_seconds, :resolution, :is_published, 0, NULL)
            ";

            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                ':title' => $values['title'],
                ':filename' => $uploadResult['filename'],
                ':file_path' => $uploadResult['file_path'],
                ':mime_type' => $resolvedMimeType,
                ':file_size' => $resolvedFileSize,
                ':duration_seconds' => $resolvedDuration,
                ':resolution' => $resolvedResolution,
                ':is_published' => $values['is_published'],
            ]);

            header('Location: ' . APP_BASE_PATH . '/admin/videos/index.php?status=created');
            exit;
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

include __DIR__ . '/../../includes/layout/admin-header.php';
?>

<section class="page-content">
    <div class="content-panel studio-panel">
        <div class="studio-header compact-header">
            <div>
                <div class="mini-kicker">NEW VIDEO</div>
                <h2 class="section-title-large">Create Video</h2>
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

        <form method="POST" class="studio-form" enctype="multipart/form-data">
            <div class="form-grid two-col">
                <div class="form-field">
                    <label for="title">Video Title</label>
                    <input id="title" name="title" type="text" value="<?php echo htmlspecialchars((string) ($values['title'] ?? '')); ?>" required>
                </div>

                <div class="form-field">
                    <label for="video_file">Video File</label>
                    <input id="video_file" name="video_file" type="file" accept="video/mp4,video/webm,video/quicktime,video/x-matroska,video/x-msvideo,video/mp2t,video/3gpp" required>
                </div>
            </div>

            <div class="video-metadata-preview" aria-live="polite">
                <div class="form-grid three-col">
                    <div class="form-field">
                        <label>Selected File</label>
                        <div class="metadata-readout" id="selected_file_label">No file selected</div>
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
                <button type="submit" class="primary-btn">Save Video</button>
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
                selectedLabel.textContent = 'No file selected';
                selectedSize.textContent = '—';
                selectedDuration.textContent = '—';
                fileSizeHidden.value = '';
                durationHidden.value = '';
                mimeTypeHidden.value = 'video/mp4';
                resolutionHidden.value = '';
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

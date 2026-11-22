<?php
/**
 * Application Helper Functions
 */

/**
 * Escapes HTML output securely.
 */
function e(?string $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Retrieves configuration values.
 */
function config(?string $key = null, $default = null) {
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/config.php';
    }
    
    if ($key === null) {
        return $config;
    }
    
    $keys = explode('.', $key);
    $data = $config;
    foreach ($keys as $segment) {
        if (is_array($data) && array_key_exists($segment, $data)) {
            $data = $data[$segment];
        } else {
            return $default;
        }
    }
    return $data;
}

/**
 * Generates or returns current session CSRF token.
 */
function csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generates a hidden HTML input containing the CSRF token.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verifies a CSRF token using hash_equals.
 */
function verify_csrf(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sets a flash message in session.
 */
function flash(string $type, string $message): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Retrieves and clears all flash messages.
 */
function get_flashes(): array {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $flashes = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $flashes;
}

/**
 * Returns currently authenticated user record from session.
 */
function auth_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Checks if a user is logged in.
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user']['id']);
}

/**
 * Checks if authenticated user is dosen.
 */
function is_dosen(): bool {
    return is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'dosen';
}

/**
 * Checks if authenticated user is mahasiswa.
 */
function is_mahasiswa(): bool {
    return is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'mahasiswa';
}

/**
 * Calculates real-time task status based on timestamps.
 * 
 * time() < dibuka_pada                  => belum_dibuka
 * dibuka_pada <= time() <= ditutup_pada => terbuka
 * time() > ditutup_pada                 => ditutup
 */
function get_task_status(int $dibukaPada, int $ditutupPada, ?int $currentTime = null): string {
    $now = $currentTime ?? time();
    if ($now < $dibukaPada) {
        return 'belum_dibuka';
    } elseif ($now <= $ditutupPada) {
        return 'terbuka';
    } else {
        return 'ditutup';
    }
}

/**
 * Returns Bootstrap HTML badge for task status.
 */
function get_task_status_badge(string $status): string {
    return match ($status) {
        'terbuka' => '<span class="badge bg-success text-white px-2.5 py-1"><i class="ti ti-lock-open me-1"></i>Terbuka</span>',
        'belum_dibuka' => '<span class="badge bg-secondary text-white px-2.5 py-1"><i class="ti ti-clock me-1"></i>Belum dibuka</span>',
        'ditutup' => '<span class="badge bg-danger text-white px-2.5 py-1"><i class="ti ti-lock me-1"></i>Ditutup</span>',
        default => '<span class="badge bg-secondary text-white px-2.5 py-1">' . e($status) . '</span>'
    };
}

/**
 * Formats a unix timestamp into readable date time in Asia/Makassar.
 */
function format_datetime(int $timestamp): string {
    return date('d M Y, H:i', $timestamp) . ' WITA';
}

function format_date(int $timestamp): string {
    return date('d M Y', $timestamp);
}

function format_time(int $timestamp): string {
    return date('H:i', $timestamp) . ' WITA';
}

/**
 * Formats bytes to human-readable size.
 */
function format_filesize(int $bytes): string {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
}

/**
 * Sanitizes base filename to alphanumeric and underscores only.
 * Pattern: NIM_Nama_PertemuanN.ext
 */
function sanitize_filename_base(string $str): string {
    // Replace any non-alphanumeric with underscore
    $clean = preg_replace('/[^a-zA-Z0-9_]/', '_', $str);
    // Collapse multiple underscores
    $clean = preg_replace('/_+/', '_', $clean);
    return trim($clean, '_');
}

/**
 * Writes security log entries to storage/logs/security.log.
 * Strips any sensitive data (no passwords, session IDs, CSRF tokens, or file bodies).
 */
function log_security(string $eventType, string $result, string $route, array $extra = []): void {
    $logPath = config('paths.logs');
    $logDir = dirname($logPath);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Log rotation: max 5MB
    if (file_exists($logPath) && filesize($logPath) > 5 * 1024 * 1024) {
        rename($logPath, $logPath . '.' . date('YmdHis') . '.bak');
    }
    
    $timestamp = date('Y-m-d H:i:s') . ' WITA';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    
    $safeExtra = [];
    foreach ($extra as $key => $val) {
        // Redact any potentially sensitive keys
        if (preg_match('/pass|token|secret|session|cookie/i', (string)$key)) {
            continue;
        }
        if (is_scalar($val)) {
            $safeExtra[] = "$key=" . substr((string)$val, 0, 100);
        }
    }
    
    $extraStr = !empty($safeExtra) ? ' | ' . implode(', ', $safeExtra) : '';
    $entry = sprintf("[%s] [%s] IP: %s | Route: %s | Result: %s%s\n", $timestamp, $eventType, $ip, $route, $result, $extraStr);
    
    @file_put_contents($logPath, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Parses file_materi column (JSON array or single string) into array of filenames.
 */
function get_materi_files($fileMateri): array {
    if (empty($fileMateri)) {
        return [];
    }
    if (is_array($fileMateri)) {
        return array_values(array_filter($fileMateri));
    }
    $decoded = json_decode((string)$fileMateri, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return array_values(array_filter($decoded));
    }
    return [trim((string)$fileMateri)];
}

/**
 * Safely delete a file from the uploads storage directory.
 */
function delete_file_from_storage(?string $filename): bool {
    if (empty($filename)) {
        return false;
    }
    $cleanName = basename($filename);
    $uploadsDir = config('paths.uploads');
    $path = $uploadsDir . DIRECTORY_SEPARATOR . $cleanName;
    if (file_exists($path) && is_file($path)) {
        return @unlink($path);
    }
    return false;
}

/**
 * Scans storage/uploads directory and deletes any files no longer referenced in DB.
 */
function cleanup_orphaned_uploads(): int {
    $uploadsDir = config('paths.uploads');
    if (!is_dir($uploadsDir)) {
        return 0;
    }

    $db = get_db();
    $activeFiles = [];

    // Collect materi files from pertemuan
    $pertemuanRows = $db->query("SELECT file_materi FROM pertemuan WHERE file_materi IS NOT NULL AND file_materi != ''")->fetchAll();
    foreach ($pertemuanRows as $row) {
        foreach (get_materi_files($row['file_materi']) as $mf) {
            $activeFiles[basename($mf)] = true;
        }
    }

    // Collect submission files from pengumpulan
    $subRows = $db->query("SELECT file_simpan FROM pengumpulan WHERE file_simpan IS NOT NULL AND file_simpan != ''")->fetchAll();
    foreach ($subRows as $row) {
        $activeFiles[basename($row['file_simpan'])] = true;
    }

    $deletedCount = 0;
    $filesOnDisk = scandir($uploadsDir);
    if ($filesOnDisk === false) {
        return 0;
    }
    foreach ($filesOnDisk as $f) {
        if ($f === '.' || $f === '..' || $f === '.gitignore' || $f === 'index.html') {
            continue;
        }
        if (!isset($activeFiles[$f])) {
            $path = $uploadsDir . DIRECTORY_SEPARATOR . $f;
            if (is_file($path) && @unlink($path)) {
                $deletedCount++;
            }
        }
    }

    return $deletedCount;
}

/**
 * Returns the URL of the user's avatar, or null if none is uploaded.
 */
function user_avatar_url(?array $user): ?string {
    if (!$user || empty($user['foto'])) {
        return null;
    }
    $filename = basename($user['foto']);
    $filePath = __DIR__ . '/../public/uploads/avatars/' . $filename;
    if (file_exists($filePath)) {
        return '/uploads/avatars/' . e($filename);
    }
    return null;
}

/**
 * Renders an HTML avatar element (with photo or fallback initial initials).
 */
function render_avatar(?array $user, int $size = 40, string $extraClass = ''): string {
    if (!$user) {
        return '<span class="avatar ' . e($extraClass) . ' rounded-circle" style="width: ' . $size . 'px; height: ' . $size . 'px;">?</span>';
    }
    
    $avatarUrl = user_avatar_url($user);
    if ($avatarUrl) {
        return '<span class="avatar ' . e($extraClass) . ' rounded-circle shadow-sm flex-shrink-0" style="width: ' . $size . 'px; height: ' . $size . 'px; background-image: url(\'' . $avatarUrl . '\'); background-size: cover; background-position: center;"></span>';
    }
    
    $initials = strtoupper(substr($user['nama'] ?? 'User', 0, 2));
    $bgClass = ($user['role'] ?? '') === 'dosen' ? 'bg-primary text-white' : 'bg-primary-lt text-primary';
    $fontSize = max(0.7, round($size * 0.024, 2));
    return '<span class="avatar ' . e($bgClass) . ' ' . e($extraClass) . ' rounded-circle shadow-sm flex-shrink-0 fw-bold" style="width: ' . $size . 'px; height: ' . $size . 'px; font-size: ' . $fontSize . 'rem;">' . e($initials) . '</span>';
}

/**
 * Handles avatar file upload with security validation.
 * Returns filename string on success, or throws Exception on validation error.
 */
function handle_avatar_upload(array $file, ?string $oldFoto = null, string $prefix = 'avatar'): ?string {
    if (empty($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Gagal mengunggah foto profil (Kode Kesalahan: ' . $file['error'] . ').');
    }

    // Maksimal 2 MB
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new InvalidArgumentException('Ukuran foto profil maksimal adalah 2 MB.');
    }

    // Verifikasi MIME type menggunakan finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowedMimes[$mime])) {
        throw new InvalidArgumentException('Format foto harus berupa JPG, PNG, atau WebP.');
    }

    // Pastikan berkas adalah gambar valid
    $imgInfo = @getimagesize($file['tmp_name']);
    if ($imgInfo === false) {
        throw new InvalidArgumentException('Berkas yang diunggah bukan format gambar yang valid.');
    }

    $ext = $allowedMimes[$mime];
    $filename = $prefix . '_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    $targetDir = __DIR__ . '/../public/uploads/avatars';

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Gagal menyimpan berkas foto ke direktori server.');
    }

    // Hapus foto lama jika ada
    if ($oldFoto) {
        delete_avatar($oldFoto);
    }

    return $filename;
}

/**
 * Deletes an avatar file safely.
 */
function delete_avatar(?string $filename): void {
    if (!$filename) return;
    $targetPath = __DIR__ . '/../public/uploads/avatars/' . basename($filename);
    if (is_file($targetPath)) {
        @unlink($targetPath);
    }
}

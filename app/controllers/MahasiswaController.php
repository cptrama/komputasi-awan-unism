<?php
/**
 * Mahasiswa Controller
 * Handles Student Dashboard, Meetings, Assignment Submission, Downloads, and Grades
 */

namespace App\Controllers;

use Flight;
use PDO;

class MahasiswaController {

    /**
     * Display student dashboard with open tasks, countdown, and submission history (including missed tasks).
     */
    public static function dashboard(): void {
        $user = auth_user();
        $db = get_db();

        // Query all tasks for student's class
        $stmt = $db->prepare(
            "SELECT t.*, p.nomor as pertemuan_nomor, p.topik as pertemuan_topik, p.mata_kuliah, p.id as pertemuan_id,
                    peng.id as pengumpulan_id, peng.file_asli, peng.waktu_upload, peng.nilai, peng.feedback
             FROM tugas t
             JOIN pertemuan p ON t.pertemuan_id = p.id
             LEFT JOIN pengumpulan peng ON peng.tugas_id = t.id AND peng.user_id = :user_id
             WHERE p.kelas = :kelas
             ORDER BY t.ditutup_pada DESC"
        );
        $stmt->execute([
            ':user_id' => $user['id'],
            ':kelas' => $user['kelas']
        ]);
        $allTasks = $stmt->fetchAll();

        $openTasks = [];
        $submissionHistory = [];

        foreach ($allTasks as $task) {
            $status = get_task_status((int)$task['dibuka_pada'], (int)$task['ditutup_pada']);
            $task['status'] = $status;
            $task['tugas_judul'] = $task['judul'];

            if ($status === 'terbuka') {
                $openTasks[] = $task;
            }

            // History includes submitted tasks OR closed tasks that were missed
            if (!empty($task['pengumpulan_id'])) {
                $task['is_missed'] = false;
                $submissionHistory[] = $task;
            } elseif ($status === 'ditutup') {
                $task['is_missed'] = true;
                $submissionHistory[] = $task;
            }
        }

        // Sort history by latest activity (upload time or closing deadline)
        usort($submissionHistory, function ($a, $b) {
            $timeA = !empty($a['waktu_upload']) ? (int)$a['waktu_upload'] : (int)$a['ditutup_pada'];
            $timeB = !empty($b['waktu_upload']) ? (int)$b['waktu_upload'] : (int)$b['ditutup_pada'];
            return $timeB <=> $timeA;
        });

        Flight::render('mahasiswa/dashboard', [
            'title' => 'Dashboard Mahasiswa - ' . config('app_name'),
            'user' => $user,
            'openTasks' => $openTasks,
            'submissionHistory' => $submissionHistory
        ]);
    }

    /**
     * List meetings for student's class.
     */
    public static function pertemuanIndex(): void {
        $user = auth_user();
        $db = get_db();

        $stmt = $db->prepare(
            "SELECT p.*, 
                    (SELECT COUNT(*) FROM tugas t WHERE t.pertemuan_id = p.id) as total_tugas
             FROM pertemuan p
             WHERE p.kelas = :kelas
             ORDER BY p.nomor ASC"
        );
        $stmt->execute([':kelas' => $user['kelas']]);
        $pertemuanList = $stmt->fetchAll();

        Flight::render('mahasiswa/pertemuan-index', [
            'title' => 'Daftar Pertemuan - ' . config('app_name'),
            'user' => $user,
            'pertemuanList' => $pertemuanList
        ]);
    }

    /**
     * Detail meeting for student's class.
     */
    public static function pertemuanDetail(string $id): void {
        $user = auth_user();
        $db = get_db();

        $stmt = $db->prepare("SELECT * FROM pertemuan WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $pertemuan = $stmt->fetch();

        if (!$pertemuan || $pertemuan['kelas'] !== $user['kelas']) {
            log_security('ACCESS_DENIED', 'Student attempted to view meeting outside class or non-existent', Flight::request()->url, [
                'user_id' => $user['id'],
                'nim' => $user['nim'],
                'requested_id' => $id
            ]);
            flash('danger', 'Pertemuan tidak ditemukan atau bukan milik kelas Anda.');
            Flight::redirect('/pertemuan');
            return;
        }

        // Fetch tasks for this meeting with student submission status
        $taskStmt = $db->prepare(
            "SELECT t.*, peng.id as pengumpulan_id, peng.file_asli, peng.file_simpan, peng.waktu_upload, peng.nilai, peng.feedback
             FROM tugas t
             LEFT JOIN pengumpulan peng ON peng.tugas_id = t.id AND peng.user_id = :user_id
             WHERE t.pertemuan_id = :pertemuan_id
             ORDER BY t.id ASC"
        );
        $taskStmt->execute([
            ':user_id' => $user['id'],
            ':pertemuan_id' => $pertemuan['id']
        ]);
        $tugasList = $taskStmt->fetchAll();

        // Calculate status for each task
        foreach ($tugasList as &$tugas) {
            $tugas['status'] = get_task_status((int)$tugas['dibuka_pada'], (int)$tugas['ditutup_pada']);
        }
        unset($tugas);

        Flight::render('mahasiswa/pertemuan-detail', [
            'title' => 'Pertemuan ' . $pertemuan['nomor'] . ' - ' . config('app_name'),
            'user' => $user,
            'pertemuan' => $pertemuan,
            'tugasList' => $tugasList
        ]);
    }

    /**
     * Upload / re-upload assignment submission.
     */
    public static function uploadTugas(string $id): void {
        $user = auth_user();
        $db = get_db();

        // 1. Fetch task and verify existence and class relation
        $stmt = $db->prepare(
            "SELECT t.*, p.kelas, p.nomor as pertemuan_nomor 
             FROM tugas t 
             JOIN pertemuan p ON t.pertemuan_id = p.id 
             WHERE t.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $tugas = $stmt->fetch();

        if (!$tugas || $tugas['kelas'] !== $user['kelas']) {
            log_security('ACCESS_DENIED', 'Invalid task upload attempt', Flight::request()->url, [
                'user_id' => $user['id'],
                'task_id' => $id
            ]);
            flash('danger', 'Tugas tidak ditemukan atau bukan untuk kelas Anda.');
            Flight::redirect('/pertemuan');
            return;
        }

        $now = time();
        $status = get_task_status((int)$tugas['dibuka_pada'], (int)$tugas['ditutup_pada'], $now);

        // 2. Server-side deadline check (no tolerance)
        if ($status !== 'terbuka') {
            log_security('UPLOAD_REJECTED', 'Upload outside submission window', Flight::request()->url, [
                'user_id' => $user['id'],
                'task_id' => $id,
                'status' => $status,
                'now' => $now,
                'ditutup_pada' => $tugas['ditutup_pada']
            ]);
            flash('danger', 'Pengumpulan ditolak. Waktu pengumpulan tugas ini telah ditutup atau belum dibuka.');
            Flight::redirect('/pertemuan/' . $tugas['pertemuan_id']);
            return;
        }

        // 3. Check if submission already exists and has been graded (Read-Only rule)
        $subCheckStmt = $db->prepare("SELECT id, nilai, feedback, file_simpan FROM pengumpulan WHERE tugas_id = :tugas_id AND user_id = :user_id LIMIT 1");
        $subCheckStmt->execute([
            ':tugas_id' => $id,
            ':user_id' => $user['id']
        ]);
        $existing = $subCheckStmt->fetch();

        if ($existing && $existing['nilai'] !== null && $existing['nilai'] !== '') {
            log_security('UPLOAD_REJECTED', 'Attempt to re-upload graded submission (locked)', Flight::request()->url, [
                'user_id' => $user['id'],
                'task_id' => $id,
                'grade' => $existing['nilai']
            ]);
            flash('warning', 'Tugas ini sudah dinilai oleh dosen (Nilai: ' . number_format((float)$existing['nilai'], 1) . ') dan telah dikunci. Anda tidak dapat melakukan pengumpulan ulang.');
            Flight::redirect('/pertemuan/' . $tugas['pertemuan_id']);
            return;
        }

        // 4. Validate uploaded file
        if (empty($_FILES['file_tugas']) || $_FILES['file_tugas']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['file_tugas']['error'] ?? 'NONE';
            log_security('UPLOAD_REJECTED', "PHP upload error code: {$errCode}", Flight::request()->url, [
                'user_id' => $user['id'],
                'task_id' => $id
            ]);
            flash('danger', 'Gagal mengunggah file. Pastikan file valid dan ukuran tidak melebihi batas server.');
            Flight::redirect('/pertemuan/' . $tugas['pertemuan_id']);
            return;
        }

        $file = $_FILES['file_tugas'];
        $maxBytes = config('max_upload_tugas_bytes', 20 * 1024 * 1024);

        // Check file size (> 0 and <= 20 MB)
        if ($file['size'] <= 0) {
            log_security('UPLOAD_REJECTED', '0-byte file uploaded', Flight::request()->url, [
                'user_id' => $user['id'],
                'task_id' => $id
            ]);
            flash('danger', 'Pengumpulan ditolak: File berukuran 0 byte (kosong).');
            Flight::redirect('/pertemuan/' . $tugas['pertemuan_id']);
            return;
        }

        if ($file['size'] > $maxBytes) {
            log_security('UPLOAD_REJECTED', 'File exceeds max size limit', Flight::request()->url, [
                'user_id' => $user['id'],
                'task_id' => $id,
                'file_size' => $file['size']
            ]);
            flash('danger', 'Ukuran file melebihi batas maksimum 20 MB.');
            Flight::redirect('/pertemuan/' . $tugas['pertemuan_id']);
            return;
        }

        // Validate extension
        $origName = $file['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowedExts = config('allowed_extensions', []);

        if (!in_array($ext, $allowedExts, true)) {
            log_security('UPLOAD_REJECTED', "Extension .{$ext} not allowed", Flight::request()->url, [
                'user_id' => $user['id'],
                'task_id' => $id,
                'orig_filename' => $origName
            ]);
            flash('danger', "Ekstensi file .{$ext} tidak diizinkan. Format yang didukung: " . implode(', ', $allowedExts));
            Flight::redirect('/pertemuan/' . $tugas['pertemuan_id']);
            return;
        }

        // Validate MIME type with finfo
        $mimeMap = config('mime_map', []);
        $expectedMimes = $mimeMap[$ext] ?? [];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!empty($expectedMimes) && !in_array($detectedMime, $expectedMimes, true)) {
            log_security('UPLOAD_REJECTED', "MIME mismatch: expected for .{$ext} got {$detectedMime}", Flight::request()->url, [
                'user_id' => $user['id'],
                'task_id' => $id,
                'detected_mime' => $detectedMime
            ]);
            flash('danger', "Tipe MIME file ({$detectedMime}) tidak cocok dengan ekstensi .{$ext}.");
            Flight::redirect('/pertemuan/' . $tugas['pertemuan_id']);
            return;
        }

        // 4. Build storage filename: NIM_Nama_PertemuanN.ext
        $cleanNim = sanitize_filename_base($user['nim']);
        $cleanNama = sanitize_filename_base($user['nama']);
        $cleanPertemuan = (int)$tugas['pertemuan_nomor'];
        $baseFilename = "{$cleanNim}_{$cleanNama}_Pertemuan{$cleanPertemuan}.{$ext}";
        
        $uploadsDir = config('paths.uploads');
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $targetPath = $uploadsDir . DIRECTORY_SEPARATOR . $baseFilename;

        // 5. Move uploaded file (atomic replacement)
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            log_security('UPLOAD_REJECTED', 'Failed to move uploaded file', Flight::request()->url, [
                'user_id' => $user['id'],
                'task_id' => $id
            ]);
            flash('danger', 'Gagal menyimpan file ke server.');
            Flight::redirect('/pertemuan/' . $tugas['pertemuan_id']);
            return;
        }

        // 6. Check existing submission
        $subStmt = $db->prepare("SELECT * FROM pengumpulan WHERE tugas_id = :tugas_id AND user_id = :user_id LIMIT 1");
        $subStmt->execute([
            ':tugas_id' => $id,
            ':user_id' => $user['id']
        ]);
        $existing = $subStmt->fetch();

        if ($existing) {
            // Delete old file if different name
            if ($existing['file_simpan'] !== $baseFilename) {
                delete_file_from_storage($existing['file_simpan']);
            }
            cleanup_orphaned_uploads();

            // Update record
            $upStmt = $db->prepare(
                "UPDATE pengumpulan 
                 SET file_simpan = :simpan, file_asli = :asli, waktu_upload = :waktu 
                 WHERE id = :id"
            );
            $upStmt->execute([
                ':simpan' => $baseFilename,
                ':asli' => $origName,
                ':waktu' => $now,
                ':id' => $existing['id']
            ]);

            log_security('UPLOAD_SUCCESS', 'Re-uploaded submission updated', Flight::request()->url, [
                'user_id' => $user['id'],
                'task_id' => $id,
                'submission_id' => $existing['id']
            ]);
            flash('success', 'Tugas Anda berhasil diperbarui (diunggah ulang).');
        } else {
            // Insert new submission
            $inStmt = $db->prepare(
                "INSERT INTO pengumpulan (tugas_id, user_id, file_simpan, file_asli, waktu_upload) 
                 VALUES (:tugas_id, :user_id, :simpan, :asli, :waktu)"
            );
            $inStmt->execute([
                ':tugas_id' => $id,
                ':user_id' => $user['id'],
                ':simpan' => $baseFilename,
                ':asli' => $origName,
                ':waktu' => $now
            ]);

            log_security('UPLOAD_SUCCESS', 'New submission uploaded', Flight::request()->url, [
                'user_id' => $user['id'],
                'task_id' => $id
            ]);
            flash('success', 'Tugas berhasil dikumpulkan.');
        }

        Flight::redirect('/pertemuan/' . $tugas['pertemuan_id']);
    }

    /**
     * Protected download of student's own submission.
     */
    public static function downloadPengumpulan(string $id): void {
        $user = auth_user();
        $db = get_db();

        $stmt = $db->prepare(
            "SELECT peng.*, t.judul as tugas_judul 
             FROM pengumpulan peng 
             JOIN tugas t ON peng.tugas_id = t.id 
             WHERE peng.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $sub = $stmt->fetch();

        // Must exist and be owned by current student
        if (!$sub || (int)$sub['user_id'] !== (int)$user['id']) {
            // Fallback: check if $id was provided as tugas_id for this student
            $fallbackStmt = $db->prepare(
                "SELECT peng.*, t.judul as tugas_judul 
                 FROM pengumpulan peng 
                 JOIN tugas t ON peng.tugas_id = t.id 
                 WHERE peng.tugas_id = :task_id AND peng.user_id = :user_id LIMIT 1"
            );
            $fallbackStmt->execute([':task_id' => $id, ':user_id' => $user['id']]);
            $fallbackSub = $fallbackStmt->fetch();

            if ($fallbackSub) {
                $sub = $fallbackSub;
            } else {
                log_security('ACCESS_DENIED', 'Unauthorized submission download attempt', Flight::request()->url, [
                    'user_id' => $user['id'],
                    'requested_sub_id' => $id
                ]);
                Flight::response()->status(403);
                flash('danger', 'Akses ditolak. Anda hanya dapat mengunduh submission milik sendiri.');
                Flight::redirect('/dashboard');
                return;
            }
        }

        $filePath = config('paths.uploads') . DIRECTORY_SEPARATOR . $sub['file_simpan'];
        if (!file_exists($filePath)) {
            log_security('FILE_NOT_FOUND', 'Physical submission file missing', Flight::request()->url, [
                'sub_id' => $id,
                'file_simpan' => $sub['file_simpan']
            ]);
            flash('danger', 'File fisik pengumpulan tidak ditemukan di server.');
            Flight::redirect('/dashboard');
            return;
        }

        // Send file securely
        self::streamDownload($filePath, $sub['file_asli']);
    }

    /**
     * Protected download of meeting course material (materi).
     */
    public static function downloadMateri(string $id): void {
        $user = auth_user();
        $db = get_db();

        $stmt = $db->prepare("SELECT * FROM pertemuan WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $pertemuan = $stmt->fetch();

        // Must exist, and if accessed by a student, must belong to student's class
        if (!$pertemuan || (is_mahasiswa() && $pertemuan['kelas'] !== $user['kelas'])) {
            log_security('ACCESS_DENIED', 'Unauthorized materi download attempt', Flight::request()->url, [
                'user_id' => $user['id'] ?? null,
                'pertemuan_id' => $id
            ]);
            Flight::response()->status(403);
            flash('danger', 'Akses ditolak. Materi tidak ditemukan atau bukan milik kelas Anda.');
            Flight::redirect(is_dosen() ? '/dosen/pertemuan' : '/pertemuan');
            return;
        }

        $files = get_materi_files($pertemuan['file_materi'] ?? null);
        if (empty($files)) {
            flash('info', 'Pertemuan ini tidak memiliki lampiran materi.');
            Flight::redirect(is_dosen() ? '/dosen/pertemuan' : '/pertemuan/' . $id);
            return;
        }

        $targetFile = Flight::request()->query->file ?? null;
        if (!empty($targetFile) && in_array($targetFile, $files, true)) {
            $fileName = $targetFile;
        } else {
            $fileName = $files[0];
        }

        $filePath = config('paths.uploads') . DIRECTORY_SEPARATOR . $fileName;
        if (!file_exists($filePath)) {
            log_security('FILE_NOT_FOUND', 'Physical materi file missing', Flight::request()->url, [
                'pertemuan_id' => $id,
                'file_materi' => $fileName
            ]);
            flash('danger', 'File fisik materi tidak ditemukan di server.');
            Flight::redirect(is_dosen() ? '/dosen/pertemuan' : '/pertemuan/' . $id);
            return;
        }

        self::streamDownload($filePath, $fileName);
    }

    /**
     * Display student grades and feedback.
     */
    public static function nilaiIndex(): void {
        $user = auth_user();
        $db = get_db();

        $stmt = $db->prepare(
            "SELECT peng.*, t.judul as tugas_judul, t.ditutup_pada, 
                    p.nomor as pertemuan_nomor, p.topik as pertemuan_topik, p.mata_kuliah
             FROM pengumpulan peng
             JOIN tugas t ON peng.tugas_id = t.id
             JOIN pertemuan p ON t.pertemuan_id = p.id
             WHERE peng.user_id = :user_id
             ORDER BY p.nomor ASC, t.id ASC"
        );
        $stmt->execute([':user_id' => $user['id']]);
        $nilaiList = $stmt->fetchAll();

        Flight::render('mahasiswa/nilai', [
            'title' => 'Nilai & Feedback - ' . config('app_name'),
            'user' => $user,
            'nilaiList' => $nilaiList
        ]);
    }

    /**
     * Helper to safely stream file downloads with proper HTTP headers.
     */
    private static function streamDownload(string $filePath, string $downloadName): void {
        if (!file_exists($filePath)) {
            Flight::response()->status(404);
            echo "File not found.";
            exit;
        }

        // Clean headers and stream file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filePath) ?: 'application/octet-stream';
        finfo_close($finfo);

        $size = filesize($filePath);
        $safeName = str_replace(['"', "\r", "\n"], '', basename($downloadName));

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $safeName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $size);

        // Read in chunks
        $handle = fopen($filePath, 'rb');
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
        exit;
    }

    /**
     * Display Mahasiswa profile page.
     */
    public static function profilView(): void {
        $user = auth_user();
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND role = 'mahasiswa' LIMIT 1");
        $stmt->execute([':id' => $user['id']]);
        $userData = $stmt->fetch();

        Flight::render('mahasiswa/profil', [
            'title' => 'Pengaturan Profil - ' . config('app_name'),
            'user' => $userData ?: $user
        ]);
    }

    /**
     * Process Mahasiswa profile update (Photo upload & optional password change).
     */
    public static function profilSubmit(): void {
        $user = auth_user();
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND role = 'mahasiswa' LIMIT 1");
        $stmt->execute([':id' => $user['id']]);
        $currentMhs = $stmt->fetch();

        if (!$currentMhs) {
            flash('danger', 'Data akun mahasiswa tidak ditemukan.');
            Flight::redirect('/dashboard');
            return;
        }

        $passwordLama = (string)Flight::request()->data->password_lama;
        $passwordBaru = (string)Flight::request()->data->password_baru;
        $konfirmasiPassword = (string)Flight::request()->data->konfirmasi_password;

        // Handle photo upload / deletion
        $fotoToSave = $currentMhs['foto'] ?? null;
        if (!empty(Flight::request()->data->hapus_foto)) {
            delete_avatar($currentMhs['foto'] ?? null);
            $fotoToSave = null;
        }

        if (!empty($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $fotoToSave = handle_avatar_upload($_FILES['foto'], $currentMhs['foto'] ?? null, 'avatar_mhs');
            } catch (Throwable $e) {
                flash('danger', $e->getMessage());
                Flight::redirect('/profil');
                return;
            }
        }

        // Check if user requested password update
        if (!empty($passwordBaru)) {
            if (empty($passwordLama)) {
                flash('danger', 'Silakan masukkan password saat ini untuk mengonfirmasi perubahan password.');
                Flight::redirect('/profil');
                return;
            }

            if (!password_verify($passwordLama, $currentMhs['password_hash'])) {
                flash('danger', 'Password saat ini yang Anda masukkan salah.');
                Flight::redirect('/profil');
                return;
            }

            if (strlen($passwordBaru) < 6) {
                flash('danger', 'Password baru minimal harus 6 karakter.');
                Flight::redirect('/profil');
                return;
            }

            if ($passwordBaru !== $konfirmasiPassword) {
                flash('danger', 'Konfirmasi password baru tidak cocok.');
                Flight::redirect('/profil');
                return;
            }

            $newHash = password_hash($passwordBaru, PASSWORD_BCRYPT);
            $updateStmt = $db->prepare("UPDATE users SET foto = :foto, password_hash = :hash WHERE id = :id");
            $updateStmt->execute([
                ':foto' => $fotoToSave,
                ':hash' => $newHash,
                ':id'   => $user['id']
            ]);

            $_SESSION['user']['foto'] = $fotoToSave;

            log_security('PROFILE_UPDATED', 'Mahasiswa updated photo and password', '/profil', [
                'user_id' => $user['id'],
                'nim' => $user['nim']
            ]);

            flash('success', 'Foto profil dan password Anda berhasil diperbarui.');
            Flight::redirect('/profil');
            return;
        }

        // Update photo only
        $updateStmt = $db->prepare("UPDATE users SET foto = :foto WHERE id = :id");
        $updateStmt->execute([
            ':foto' => $fotoToSave,
            ':id'   => $user['id']
        ]);

        $_SESSION['user']['foto'] = $fotoToSave;

        log_security('PROFILE_UPDATED', 'Mahasiswa updated photo', '/profil', [
            'user_id' => $user['id'],
            'nim' => $user['nim']
        ]);

        flash('success', 'Foto profil Anda berhasil disimpan.');
        Flight::redirect('/profil');
    }
}

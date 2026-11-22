<?php
/**
 * Dosen Controller
 * Handles Teacher Dashboard, Meetings CRUD, Tasks CRUD, Submissions, Grading, ZIP Downloads, and CSV Export
 */

namespace App\Controllers;

use Flight;
use PDO;
use ZipArchive;

class DosenController {

    /**
     * Dosen dashboard with summary statistics and task tracking.
     */
    public static function dashboard(): void {
        $db = get_db();

        // 1. Overall counts
        $totalPertemuan = (int)$db->query("SELECT COUNT(*) FROM pertemuan")->fetchColumn();
        $totalTugas = (int)$db->query("SELECT COUNT(*) FROM tugas")->fetchColumn();
        $totalMahasiswa = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'mahasiswa'")->fetchColumn();

        // 2. Fetch tasks with class stats
        $stmt = $db->query(
            "SELECT t.*, p.kelas, p.nomor as pertemuan_nomor, p.topik as pertemuan_topik,
                    (SELECT COUNT(*) FROM users u WHERE u.role = 'mahasiswa' AND u.kelas = p.kelas) as total_mhs,
                    (SELECT COUNT(*) FROM pengumpulan peng WHERE peng.tugas_id = t.id) as total_kumpul
             FROM tugas t
             JOIN pertemuan p ON t.pertemuan_id = p.id
             ORDER BY t.ditutup_pada DESC"
        );
        $tasks = $stmt->fetchAll();

        foreach ($tasks as &$task) {
            $task['status'] = get_task_status((int)$task['dibuka_pada'], (int)$task['ditutup_pada']);
            $task['total_mhs'] = (int)$task['total_mhs'];
            $task['total_kumpul'] = (int)$task['total_kumpul'];
            $task['total_belum'] = max(0, $task['total_mhs'] - $task['total_kumpul']);
        }
        unset($task);

        Flight::render('dosen/dashboard', [
            'title' => 'Panel Dosen - ' . config('app_name'),
            'totalPertemuan' => $totalPertemuan,
            'totalTugas' => $totalTugas,
            'totalMahasiswa' => $totalMahasiswa,
            'tasks' => $tasks
        ]);
    }

    /**
     * List all meetings.
     */
    public static function pertemuanIndex(): void {
        $db = get_db();
        $filterKelas = Flight::request()->query->kelas;
        $allowedKelas = config('kelas_list', ['S5']);

        if ($filterKelas && in_array($filterKelas, $allowedKelas, true)) {
            $stmt = $db->prepare(
                "SELECT p.*, (SELECT COUNT(*) FROM tugas t WHERE t.pertemuan_id = p.id) as total_tugas 
                 FROM pertemuan p 
                 WHERE p.kelas = :kelas 
                 ORDER BY p.nomor ASC"
            );
            $stmt->execute([':kelas' => $filterKelas]);
        } else {
            $stmt = $db->query(
                "SELECT p.*, (SELECT COUNT(*) FROM tugas t WHERE t.pertemuan_id = p.id) as total_tugas 
                 FROM pertemuan p 
                 ORDER BY p.kelas ASC, p.nomor ASC"
            );
        }
        $pertemuanList = $stmt->fetchAll();

        Flight::render('dosen/pertemuan-index', [
            'title' => 'Kelola Pertemuan - ' . config('app_name'),
            'pertemuanList' => $pertemuanList,
            'filterKelas' => $filterKelas
        ]);
    }

    /**
     * Show form to add new meeting.
     */
    public static function pertemuanTambahView(): void {
        Flight::render('dosen/pertemuan-form', [
            'title' => 'Tambah Pertemuan - ' . config('app_name'),
            'isEdit' => false,
            'pertemuan' => null
        ]);
    }

    /**
     * Process new meeting creation.
     */
    public static function pertemuanTambahSubmit(): void {
        $req = Flight::request()->data;
        $kelas = trim((string)$req->kelas);
        $mataKuliah = trim((string)$req->mata_kuliah);
        $nomor = (int)$req->nomor;
        $topik = trim((string)$req->topik);
        $tanggal = trim((string)$req->tanggal);

        $allowedKelas = config('kelas_list', ['S5']);
        if (!in_array($kelas, $allowedKelas, true)) {
            flash('danger', 'Pilihan kelas tidak valid.');
            Flight::redirect('/dosen/pertemuan/tambah');
            return;
        }

        if ($nomor <= 0 || empty($topik) || empty($tanggal)) {
            flash('danger', 'Nomor pertemuan, topik, dan tanggal wajib diisi.');
            Flight::redirect('/dosen/pertemuan/tambah');
            return;
        }

        $fileMateriNames = [];
        if (!empty($_FILES['file_materi'])) {
            $files = $_FILES['file_materi'];
            $names = is_array($files['name']) ? $files['name'] : [$files['name']];
            $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
            $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];
            $errors = is_array($files['error']) ? $files['error'] : [$files['error']];

            $maxMateriBytes = config('max_upload_materi_bytes', 50 * 1024 * 1024);
            $uploadsDir = config('paths.uploads');

            for ($i = 0; $i < count($names); $i++) {
                if (empty($names[$i]) || $errors[$i] !== UPLOAD_ERR_OK) continue;

                if ($sizes[$i] > $maxMateriBytes) {
                    flash('danger', 'Ukuran berkas materi (' . e($names[$i]) . ') melebihi batas 50 MB.');
                    Flight::redirect('/dosen/pertemuan/tambah');
                    return;
                }
                if ($sizes[$i] <= 0) continue;

                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }

                $origName = basename($names[$i]);
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $cleanBase = sanitize_filename_base(pathinfo($origName, PATHINFO_FILENAME));
                $savedName = "materi_{$kelas}_p{$nomor}_{$cleanBase}_" . uniqid() . ".{$ext}";

                if (move_uploaded_file($tmpNames[$i], $uploadsDir . DIRECTORY_SEPARATOR . $savedName)) {
                    $fileMateriNames[] = $savedName;
                }
            }
        }

        $fileMateriString = !empty($fileMateriNames) ? json_encode($fileMateriNames, JSON_UNESCAPED_UNICODE) : null;

        $db = get_db();
        $stmt = $db->prepare(
            "INSERT INTO pertemuan (kelas, mata_kuliah, nomor, topik, tanggal, file_materi) 
             VALUES (:kelas, :mata_kuliah, :nomor, :topik, :tanggal, :file_materi)"
        );
        $stmt->execute([
            ':kelas' => $kelas,
            ':mata_kuliah' => $mataKuliah ?: null,
            ':nomor' => $nomor,
            ':topik' => $topik,
            ':tanggal' => $tanggal,
            ':file_materi' => $fileMateriString
        ]);

        log_security('PERTEMUAN_CREATED', 'New meeting created', '/dosen/pertemuan', [
            'kelas' => $kelas,
            'nomor' => $nomor
        ]);

        flash('success', 'Pertemuan berhasil ditambahkan.');
        Flight::redirect('/dosen/pertemuan');
    }

    /**
     * Show form to edit meeting.
     */
    public static function pertemuanEditView(string $id): void {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM pertemuan WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $pertemuan = $stmt->fetch();

        if (!$pertemuan) {
            flash('danger', 'Pertemuan tidak ditemukan.');
            Flight::redirect('/dosen/pertemuan');
            return;
        }

        Flight::render('dosen/pertemuan-form', [
            'title' => 'Edit Pertemuan ' . $pertemuan['nomor'] . ' - ' . config('app_name'),
            'isEdit' => true,
            'pertemuan' => $pertemuan
        ]);
    }

    /**
     * Process meeting edit.
     */
    public static function pertemuanEditSubmit(string $id): void {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM pertemuan WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $pertemuan = $stmt->fetch();

        if (!$pertemuan) {
            flash('danger', 'Pertemuan tidak ditemukan.');
            Flight::redirect('/dosen/pertemuan');
            return;
        }

        $req = Flight::request()->data;
        $kelas = trim((string)$req->kelas);
        $mataKuliah = trim((string)$req->mata_kuliah);
        $nomor = (int)$req->nomor;
        $topik = trim((string)$req->topik);
        $tanggal = trim((string)$req->tanggal);

        $allowedKelas = config('kelas_list', ['S5']);
        if (!in_array($kelas, $allowedKelas, true)) {
            flash('danger', 'Pilihan kelas tidak valid.');
            Flight::redirect('/dosen/pertemuan/' . $id . '/edit');
            return;
        }

        $existingFiles = get_materi_files($pertemuan['file_materi']);

        // Check if user requested deletion of existing files
        $hapusRaw = $_POST['hapus_file'] ?? ($req->hapus_file ?? []);
        if (!is_array($hapusRaw)) {
            $hapusRaw = $hapusRaw ? [$hapusRaw] : [];
        }
        $hapusFiles = array_map(function($f) {
            return html_entity_decode((string)$f);
        }, $hapusRaw);

        $remainingFiles = [];
        foreach ($existingFiles as $ef) {
            $decodedEf = html_entity_decode($ef);
            if (in_array($ef, $hapusFiles, true) || in_array($decodedEf, $hapusFiles, true)) {
                delete_file_from_storage($ef);
            } else {
                $remainingFiles[] = $ef;
            }
        }
        cleanup_orphaned_uploads();

        // Process newly uploaded files
        if (!empty($_FILES['file_materi'])) {
            $files = $_FILES['file_materi'];
            $names = is_array($files['name']) ? $files['name'] : [$files['name']];
            $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
            $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];
            $errors = is_array($files['error']) ? $files['error'] : [$files['error']];

            $maxMateriBytes = config('max_upload_materi_bytes', 50 * 1024 * 1024);

            for ($i = 0; $i < count($names); $i++) {
                if (empty($names[$i]) || $errors[$i] !== UPLOAD_ERR_OK) continue;

                if ($sizes[$i] > $maxMateriBytes) {
                    flash('danger', 'Ukuran berkas materi (' . e($names[$i]) . ') melebihi batas 50 MB.');
                    Flight::redirect('/dosen/pertemuan/' . $id . '/edit');
                    return;
                }
                if ($sizes[$i] <= 0) continue;

                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }

                $origName = basename($names[$i]);
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $cleanBase = sanitize_filename_base(pathinfo($origName, PATHINFO_FILENAME));
                $savedName = "materi_{$kelas}_p{$nomor}_{$cleanBase}_" . uniqid() . ".{$ext}";

                if (move_uploaded_file($tmpNames[$i], $uploadsDir . DIRECTORY_SEPARATOR . $savedName)) {
                    $remainingFiles[] = $savedName;
                }
            }
        }

        $fileMateriString = !empty($remainingFiles) ? json_encode(array_values($remainingFiles), JSON_UNESCAPED_UNICODE) : null;

        $upStmt = $db->prepare(
            "UPDATE pertemuan 
             SET kelas = :kelas, mata_kuliah = :mata_kuliah, nomor = :nomor, topik = :topik, tanggal = :tanggal, file_materi = :file_materi 
             WHERE id = :id"
        );
        $upStmt->execute([
            ':kelas' => $kelas,
            ':mata_kuliah' => $mataKuliah ?: null,
            ':nomor' => $nomor,
            ':topik' => $topik,
            ':tanggal' => $tanggal,
            ':file_materi' => $fileMateriString,
            ':id' => $id
        ]);

        flash('success', 'Pertemuan berhasil diperbarui.');
        Flight::redirect('/dosen/pertemuan');
    }

    /**
     * AJAX endpoint to delete a specific materi file from a meeting immediately.
     */
    public static function pertemuanHapusFileMateri(string $id): void {
        header('Content-Type: application/json; charset=utf-8');

        if (!is_dosen()) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $token = $_POST['csrf_token'] ?? Flight::request()->data->csrf_token ?? '';
        if (!verify_csrf($token)) {
            echo json_encode(['success' => false, 'message' => 'Token keamanan (CSRF) tidak valid.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM pertemuan WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $pertemuan = $stmt->fetch();

        if (!$pertemuan) {
            echo json_encode(['success' => false, 'message' => 'Pertemuan tidak ditemukan.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $filename = trim((string)($_POST['file_name'] ?? Flight::request()->data->file_name ?? ''));
        if (empty($filename)) {
            echo json_encode(['success' => false, 'message' => 'Nama berkas tidak valid.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $existingFiles = get_materi_files($pertemuan['file_materi']);
        $remainingFiles = [];
        $found = false;

        foreach ($existingFiles as $ef) {
            if ($ef === $filename || html_entity_decode($ef) === html_entity_decode($filename)) {
                delete_file_from_storage($ef);
                $found = true;
            } else {
                $remainingFiles[] = $ef;
            }
        }

        if ($found) {
            $fileMateriString = !empty($remainingFiles) ? json_encode(array_values($remainingFiles), JSON_UNESCAPED_UNICODE) : null;
            $up = $db->prepare("UPDATE pertemuan SET file_materi = :file_materi WHERE id = :id");
            $up->execute([
                ':file_materi' => $fileMateriString,
                ':id' => $id
            ]);
            cleanup_orphaned_uploads();

            echo json_encode([
                'success' => true,
                'message' => 'Berkas berhasil dihapus.',
                'remaining_count' => count($remainingFiles)
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Berkas tidak ditemukan dalam pertemuan ini.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Delete meeting and physical materi files.
     */
    public static function pertemuanHapus(string $id): void {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM pertemuan WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $pertemuan = $stmt->fetch();

        if ($pertemuan) {
            $files = get_materi_files($pertemuan['file_materi']);
            foreach ($files as $f) {
                delete_file_from_storage($f);
            }

            // Delete physical submissions for any tasks in this meeting
            $subFilesStmt = $db->prepare(
                "SELECT peng.file_simpan FROM pengumpulan peng 
                 JOIN tugas t ON peng.tugas_id = t.id 
                 WHERE t.pertemuan_id = :id"
            );
            $subFilesStmt->execute([':id' => $id]);
            while ($row = $subFilesStmt->fetch()) {
                delete_file_from_storage($row['file_simpan']);
            }

            $delStmt = $db->prepare("DELETE FROM pertemuan WHERE id = :id");
            $delStmt->execute([':id' => $id]);

            cleanup_orphaned_uploads();

            log_security('PERTEMUAN_DELETED', 'Meeting deleted', '/dosen/pertemuan/hapus', ['id' => $id]);
            flash('success', 'Pertemuan dan seluruh tugas/submission terkait berhasil dihapus.');
        }

        Flight::redirect('/dosen/pertemuan');
    }

    /**
     * List all tasks.
     */
    public static function tugasIndex(): void {
        $db = get_db();
        $stmt = $db->query(
            "SELECT t.*, p.kelas, p.nomor as pertemuan_nomor, p.topik as pertemuan_topik,
                    (SELECT COUNT(*) FROM pengumpulan peng WHERE peng.tugas_id = t.id) as total_kumpul
             FROM tugas t
             JOIN pertemuan p ON t.pertemuan_id = p.id
             ORDER BY p.kelas ASC, p.nomor ASC, t.id ASC"
        );
        $tugasList = $stmt->fetchAll();

        foreach ($tugasList as &$tugas) {
            $tugas['status'] = get_task_status((int)$tugas['dibuka_pada'], (int)$tugas['ditutup_pada']);
        }
        unset($tugas);

        Flight::render('dosen/tugas-index', [
            'title' => 'Kelola Tugas - ' . config('app_name'),
            'tugasList' => $tugasList
        ]);
    }

    /**
     * Show form to add new task.
     */
    public static function tugasTambahView(): void {
        $db = get_db();
        $pertemuanList = $db->query("SELECT * FROM pertemuan ORDER BY kelas ASC, nomor ASC")->fetchAll();

        Flight::render('dosen/tugas-form', [
            'title' => 'Tambah Tugas - ' . config('app_name'),
            'isEdit' => false,
            'tugas' => null,
            'pertemuanList' => $pertemuanList,
            'selectedPertemuanId' => Flight::request()->query->pertemuan_id ?? null
        ]);
    }

    /**
     * Process task creation.
     */
    public static function tugasTambahSubmit(): void {
        $req = Flight::request()->data;
        $pertemuanId = (int)$req->pertemuan_id;
        $judul = trim((string)$req->judul);
        $deskripsi = trim((string)$req->deskripsi);
        $dibukaStr = trim((string)$req->dibuka_pada);
        $ditutupMode = trim((string)$req->ditutup_mode); // 'datetime' or 'durasi'
        $ditutupStr = trim((string)$req->ditutup_pada);
        $durasiHari = (int)$req->durasi_hari;

        if ($pertemuanId <= 0 || empty($judul) || empty($deskripsi) || empty($dibukaStr)) {
            flash('danger', 'Pertemuan, judul, deskripsi, dan waktu buka wajib diisi.');
            Flight::redirect('/dosen/tugas/tambah');
            return;
        }

        // Convert times in Asia/Makassar
        $dibukaTimestamp = strtotime($dibukaStr);
        if ($dibukaTimestamp === false) {
            flash('danger', 'Format waktu buka tidak valid.');
            Flight::redirect('/dosen/tugas/tambah');
            return;
        }

        if ($ditutupMode === 'durasi') {
            if ($durasiHari <= 0) {
                flash('danger', 'Durasi hari harus lebih dari 0.');
                Flight::redirect('/dosen/tugas/tambah');
                return;
            }
            $ditutupTimestamp = $dibukaTimestamp + ($durasiHari * 86400);
        } else {
            if (empty($ditutupStr)) {
                flash('danger', 'Waktu tutup tugas wajib diisi.');
                Flight::redirect('/dosen/tugas/tambah');
                return;
            }
            $ditutupTimestamp = strtotime($ditutupStr);
            if ($ditutupTimestamp === false) {
                flash('danger', 'Format waktu tutup tidak valid.');
                Flight::redirect('/dosen/tugas/tambah');
                return;
            }
        }

        if ($ditutupTimestamp <= $dibukaTimestamp) {
            flash('danger', 'Waktu tutup harus lebih akhir daripada waktu buka.');
            Flight::redirect('/dosen/tugas/tambah');
            return;
        }

        $db = get_db();
        $stmt = $db->prepare(
            "INSERT INTO tugas (pertemuan_id, judul, deskripsi, dibuka_pada, ditutup_pada) 
             VALUES (:pertemuan_id, :judul, :deskripsi, :dibuka_pada, :ditutup_pada)"
        );
        $stmt->execute([
            ':pertemuan_id' => $pertemuanId,
            ':judul' => $judul,
            ':deskripsi' => $deskripsi,
            ':dibuka_pada' => $dibukaTimestamp,
            ':ditutup_pada' => $ditutupTimestamp
        ]);

        log_security('TUGAS_CREATED', 'New task created', '/dosen/tugas', [
            'pertemuan_id' => $pertemuanId,
            'judul' => $judul
        ]);

        flash('success', 'Tugas berhasil dibuat.');
        Flight::redirect('/dosen/tugas');
    }

    /**
     * Show form to edit task.
     */
    public static function tugasEditView(string $id): void {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM tugas WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $tugas = $stmt->fetch();

        if (!$tugas) {
            flash('danger', 'Tugas tidak ditemukan.');
            Flight::redirect('/dosen/tugas');
            return;
        }

        $pertemuanList = $db->query("SELECT * FROM pertemuan ORDER BY kelas ASC, nomor ASC")->fetchAll();

        Flight::render('dosen/tugas-form', [
            'title' => 'Edit Tugas - ' . config('app_name'),
            'isEdit' => true,
            'tugas' => $tugas,
            'pertemuanList' => $pertemuanList,
            'selectedPertemuanId' => $tugas['pertemuan_id']
        ]);
    }

    /**
     * Process task edit.
     */
    public static function tugasEditSubmit(string $id): void {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM tugas WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $tugas = $stmt->fetch();

        if (!$tugas) {
            flash('danger', 'Tugas tidak ditemukan.');
            Flight::redirect('/dosen/tugas');
            return;
        }

        $req = Flight::request()->data;
        $pertemuanId = (int)$req->pertemuan_id;
        $judul = trim((string)$req->judul);
        $deskripsi = trim((string)$req->deskripsi);
        $dibukaStr = trim((string)$req->dibuka_pada);
        $ditutupMode = trim((string)$req->ditutup_mode);
        $ditutupStr = trim((string)$req->ditutup_pada);
        $durasiHari = (int)$req->durasi_hari;

        $dibukaTimestamp = strtotime($dibukaStr);
        if ($dibukaTimestamp === false) {
            flash('danger', 'Format waktu buka tidak valid.');
            Flight::redirect('/dosen/tugas/' . $id . '/edit');
            return;
        }

        if ($ditutupMode === 'durasi') {
            $ditutupTimestamp = $dibukaTimestamp + ($durasiHari * 86400);
        } else {
            $ditutupTimestamp = strtotime($ditutupStr);
            if ($ditutupTimestamp === false) {
                flash('danger', 'Format waktu tutup tidak valid.');
                Flight::redirect('/dosen/tugas/' . $id . '/edit');
                return;
            }
        }

        if ($ditutupTimestamp <= $dibukaTimestamp) {
            flash('danger', 'Waktu tutup harus lebih akhir daripada waktu buka.');
            Flight::redirect('/dosen/tugas/' . $id . '/edit');
            return;
        }

        $upStmt = $db->prepare(
            "UPDATE tugas 
             SET pertemuan_id = :pertemuan_id, judul = :judul, deskripsi = :deskripsi, dibuka_pada = :dibuka_pada, ditutup_pada = :ditutup_pada 
             WHERE id = :id"
        );
        $upStmt->execute([
            ':pertemuan_id' => $pertemuanId,
            ':judul' => $judul,
            ':deskripsi' => $deskripsi,
            ':dibuka_pada' => $dibukaTimestamp,
            ':ditutup_pada' => $ditutupTimestamp,
            ':id' => $id
        ]);

        flash('success', 'Tugas berhasil diperbarui.');
        Flight::redirect('/dosen/tugas');
    }

    /**
     * Delete task, submission records, and physical files.
     */
    public static function tugasHapus(string $id): void {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM tugas WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $tugas = $stmt->fetch();

        if ($tugas) {
            // Remove physical submission files
            $subStmt = $db->prepare("SELECT file_simpan FROM pengumpulan WHERE tugas_id = :tugas_id");
            $subStmt->execute([':tugas_id' => $id]);
            while ($sub = $subStmt->fetch()) {
                delete_file_from_storage($sub['file_simpan']);
            }

            $delStmt = $db->prepare("DELETE FROM tugas WHERE id = :id");
            $delStmt->execute([':id' => $id]);

            cleanup_orphaned_uploads();

            log_security('TUGAS_DELETED', 'Task and submissions deleted', '/dosen/tugas/hapus', ['id' => $id]);
            flash('success', 'Tugas dan seluruh submission terkait berhasil dihapus.');
        }

        Flight::redirect('/dosen/tugas');
    }

    /**
     * View submissions for a task and grade them.
     */
    public static function pengumpulanIndex(string $id): void {
        $db = get_db();

        $taskStmt = $db->prepare(
            "SELECT t.*, p.kelas, p.nomor as pertemuan_nomor, p.topik as pertemuan_topik, p.mata_kuliah 
             FROM tugas t 
             JOIN pertemuan p ON t.pertemuan_id = p.id 
             WHERE t.id = :id LIMIT 1"
        );
        $taskStmt->execute([':id' => $id]);
        $tugas = $taskStmt->fetch();

        if (!$tugas) {
            flash('danger', 'Tugas tidak ditemukan.');
            Flight::redirect('/dosen/tugas');
            return;
        }

        $tugas['status'] = get_task_status((int)$tugas['dibuka_pada'], (int)$tugas['ditutup_pada']);

        // Query all students in that class and their submission data
        $subStmt = $db->prepare(
            "SELECT u.id as user_id, u.nim, u.nama, u.kelas, u.foto,
                    peng.id as pengumpulan_id, peng.file_simpan, peng.file_asli, peng.waktu_upload, peng.nilai, peng.feedback
             FROM users u
             LEFT JOIN pengumpulan peng ON peng.user_id = u.id AND peng.tugas_id = :tugas_id
             WHERE u.role = 'mahasiswa' AND u.kelas = :kelas
             ORDER BY u.nim ASC"
        );
        $subStmt->execute([
            ':tugas_id' => $id,
            ':kelas' => $tugas['kelas']
        ]);
        $submissions = $subStmt->fetchAll();

        $totalMhs = count($submissions);
        $totalKumpul = 0;
        foreach ($submissions as $sub) {
            if (!empty($sub['pengumpulan_id'])) {
                $totalKumpul++;
            }
        }
        $totalBelum = $totalMhs - $totalKumpul;

        Flight::render('dosen/pengumpulan-index', [
            'title' => 'Daftar Pengumpulan - ' . $tugas['judul'],
            'tugas' => $tugas,
            'submissions' => $submissions,
            'totalMhs' => $totalMhs,
            'totalKumpul' => $totalKumpul,
            'totalBelum' => $totalBelum
        ]);
    }

    /**
     * Save grade and feedback for a submission.
     */
    public static function simpanNilai(string $id): void {
        $db = get_db();
        $stmt = $db->prepare("SELECT peng.*, t.pertemuan_id FROM pengumpulan peng JOIN tugas t ON peng.tugas_id = t.id WHERE peng.id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $sub = $stmt->fetch();

        if (!$sub) {
            flash('danger', 'Submission tidak ditemukan.');
            Flight::redirect('/dosen/tugas');
            return;
        }

        $req = Flight::request()->data;
        $nilaiRaw = trim((string)$req->nilai);
        $feedback = trim((string)$req->feedback);

        $nilai = null;
        if ($nilaiRaw !== '') {
            if (!is_numeric($nilaiRaw) || (float)$nilaiRaw < 0 || (float)$nilaiRaw > 100) {
                flash('danger', 'Nilai harus berupa angka antara 0 dan 100.');
                Flight::redirect('/dosen/tugas/' . $sub['tugas_id'] . '/pengumpulan');
                return;
            }
            $nilai = (float)$nilaiRaw;
        }

        $upStmt = $db->prepare("UPDATE pengumpulan SET nilai = :nilai, feedback = :feedback WHERE id = :id");
        $upStmt->execute([
            ':nilai' => $nilai,
            ':feedback' => $feedback ?: null,
            ':id' => $id
        ]);

        flash('success', 'Nilai dan feedback berhasil disimpan.');
        Flight::redirect('/dosen/tugas/' . $sub['tugas_id'] . '/pengumpulan');
    }

    /**
     * Reopen a graded submission for a student (reset grade to NULL so student can re-upload).
     */
    public static function bukaKembaliPengumpulan(string $id): void {
        $db = get_db();
        $stmt = $db->prepare(
            "SELECT peng.*, t.pertemuan_id, u.nama, u.nim 
             FROM pengumpulan peng 
             JOIN tugas t ON peng.tugas_id = t.id 
             JOIN users u ON peng.user_id = u.id 
             WHERE peng.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $sub = $stmt->fetch();

        if (!$sub) {
            flash('danger', 'Submission tidak ditemukan.');
            Flight::redirect('/dosen/tugas');
            return;
        }

        $upStmt = $db->prepare("UPDATE pengumpulan SET nilai = NULL WHERE id = :id");
        $upStmt->execute([':id' => $id]);

        log_security('SUBMISSION_REOPENED', 'Grade removed, submission reopened for student', '/dosen/pengumpulan/buka-kembali', [
            'pengumpulan_id' => $id,
            'student_nim' => $sub['nim']
        ]);

        flash('success', "Pengumpulan tugas untuk {$sub['nama']} ({$sub['nim']}) berhasil dibuka kembali. Mahasiswa kini dapat mengunggah perbaikan tugas.");
        Flight::redirect('/dosen/tugas/' . $sub['tugas_id'] . '/pengumpulan');
    }

    /**
     * Dosen download student submission.
     */
    public static function downloadPengumpulan(string $id): void {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM pengumpulan WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $sub = $stmt->fetch();

        if (!$sub) {
            flash('danger', 'Submission tidak ditemukan.');
            Flight::redirect('/dosen/tugas');
            return;
        }

        $filePath = config('paths.uploads') . DIRECTORY_SEPARATOR . $sub['file_simpan'];
        if (!file_exists($filePath)) {
            flash('danger', 'File fisik submission tidak ditemukan di server.');
            Flight::redirect('/dosen/tugas/' . $sub['tugas_id'] . '/pengumpulan');
            return;
        }

        self::streamDownload($filePath, $sub['file_asli']);
    }

    /**
     * Download all submissions for a task as a single ZIP archive.
     */
    public static function downloadZip(string $id): void {
        $db = get_db();
        $taskStmt = $db->prepare(
            "SELECT t.*, p.kelas, p.nomor as pertemuan_nomor 
             FROM tugas t 
             JOIN pertemuan p ON t.pertemuan_id = p.id 
             WHERE t.id = :id LIMIT 1"
        );
        $taskStmt->execute([':id' => $id]);
        $tugas = $taskStmt->fetch();

        if (!$tugas) {
            flash('danger', 'Tugas tidak ditemukan.');
            Flight::redirect('/dosen/tugas');
            return;
        }

        $subStmt = $db->prepare(
            "SELECT peng.*, u.nim, u.nama 
             FROM pengumpulan peng 
             JOIN users u ON peng.user_id = u.id 
             WHERE peng.tugas_id = :tugas_id"
        );
        $subStmt->execute([':tugas_id' => $id]);
        $submissions = $subStmt->fetchAll();

        if (empty($submissions)) {
            flash('warning', 'Belum ada submission untuk tugas ini.');
            Flight::redirect('/dosen/tugas/' . $id . '/pengumpulan');
            return;
        }

        $zip = new ZipArchive();
        $tempZipPath = tempnam(sys_get_temp_dir(), 'tugas_zip_');

        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            flash('danger', 'Gagal membuat file ZIP.');
            Flight::redirect('/dosen/tugas/' . $id . '/pengumpulan');
            return;
        }

        $uploadsDir = config('paths.uploads');
        $addedCount = 0;

        foreach ($submissions as $sub) {
            $filePath = $uploadsDir . DIRECTORY_SEPARATOR . $sub['file_simpan'];
            if (file_exists($filePath)) {
                $ext = pathinfo($sub['file_simpan'], PATHINFO_EXTENSION);
                $cleanNim = sanitize_filename_base($sub['nim']);
                $cleanNama = sanitize_filename_base($sub['nama']);
                $zipEntryName = "{$cleanNim}_{$cleanNama}_P{$tugas['pertemuan_nomor']}.{$ext}";
                $zip->addFile($filePath, $zipEntryName);
                $addedCount++;
            }
        }

        $zip->close();

        if ($addedCount === 0) {
            @unlink($tempZipPath);
            flash('warning', 'Tidak ada file fisik submission yang tersedia.');
            Flight::redirect('/dosen/tugas/' . $id . '/pengumpulan');
            return;
        }

        $cleanJudul = sanitize_filename_base($tugas['judul']);
        $downloadZipName = "Submissions_{$tugas['kelas']}_P{$tugas['pertemuan_nomor']}_{$cleanJudul}.zip";

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $downloadZipName . '"');
        header('Content-Length: ' . filesize($tempZipPath));
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($tempZipPath);
        @unlink($tempZipPath);
        exit;
    }

    /**
     * Grade recap page with class filter.
     */
    public static function rekapIndex(): void {
        $db = get_db();
        $allowedKelas = config('kelas_list', ['S5']);
        $defaultKelas = $allowedKelas[0] ?? 'S5';
        $filterKelas = Flight::request()->query->kelas ?: $defaultKelas;

        if (!in_array($filterKelas, $allowedKelas, true)) {
            $filterKelas = $defaultKelas;
        }

        // 1. Get all tasks for this class
        $taskStmt = $db->prepare(
            "SELECT t.*, p.nomor as pertemuan_nomor 
             FROM tugas t 
             JOIN pertemuan p ON t.pertemuan_id = p.id 
             WHERE p.kelas = :kelas 
             ORDER BY p.nomor ASC, t.id ASC"
        );
        $taskStmt->execute([':kelas' => $filterKelas]);
        $tasks = $taskStmt->fetchAll();

        // 2. Get all students in this class
        $studentStmt = $db->prepare(
            "SELECT * FROM users 
             WHERE role = 'mahasiswa' AND kelas = :kelas 
             ORDER BY nim ASC"
        );
        $studentStmt->execute([':kelas' => $filterKelas]);
        $students = $studentStmt->fetchAll();

        // 3. Get all grades for this class
        $gradeStmt = $db->prepare(
            "SELECT peng.user_id, peng.tugas_id, peng.nilai 
             FROM pengumpulan peng 
             JOIN tugas t ON peng.tugas_id = t.id 
             JOIN pertemuan p ON t.pertemuan_id = p.id 
             WHERE p.kelas = :kelas"
        );
        $gradeStmt->execute([':kelas' => $filterKelas]);
        $gradeRows = $gradeStmt->fetchAll();

        // Map grades: [user_id][tugas_id] => nilai
        $gradesMap = [];
        foreach ($gradeRows as $g) {
            $gradesMap[$g['user_id']][$g['tugas_id']] = $g['nilai'];
        }

        Flight::render('dosen/rekap', [
            'title' => 'Rekap Nilai - ' . config('app_name'),
            'filterKelas' => $filterKelas,
            'tasks' => $tasks,
            'students' => $students,
            'gradesMap' => $gradesMap
        ]);
    }

    /**
     * Export grade recap to CSV.
     */
    public static function rekapExportCsv(): void {
        $db = get_db();
        $allowedKelas = config('kelas_list', ['S5']);
        $defaultKelas = $allowedKelas[0] ?? 'S5';
        $filterKelas = Flight::request()->query->kelas ?: $defaultKelas;

        if (!in_array($filterKelas, $allowedKelas, true)) {
            $filterKelas = $defaultKelas;
        }

        // Fetch tasks
        $taskStmt = $db->prepare(
            "SELECT t.id, t.judul, p.nomor as pertemuan_nomor 
             FROM tugas t 
             JOIN pertemuan p ON t.pertemuan_id = p.id 
             WHERE p.kelas = :kelas 
             ORDER BY p.nomor ASC, t.id ASC"
        );
        $taskStmt->execute([':kelas' => $filterKelas]);
        $tasks = $taskStmt->fetchAll();

        // Fetch students
        $studentStmt = $db->prepare(
            "SELECT id, nim, nama, kelas 
             FROM users 
             WHERE role = 'mahasiswa' AND kelas = :kelas 
             ORDER BY nim ASC"
        );
        $studentStmt->execute([':kelas' => $filterKelas]);
        $students = $studentStmt->fetchAll();

        // Fetch grades
        $gradeStmt = $db->prepare(
            "SELECT peng.user_id, peng.tugas_id, peng.nilai 
             FROM pengumpulan peng 
             JOIN tugas t ON peng.tugas_id = t.id 
             JOIN pertemuan p ON t.pertemuan_id = p.id 
             WHERE p.kelas = :kelas"
        );
        $gradeStmt->execute([':kelas' => $filterKelas]);
        $gradeRows = $gradeStmt->fetchAll();

        $gradesMap = [];
        foreach ($gradeRows as $g) {
            $gradesMap[$g['user_id']][$g['tugas_id']] = $g['nilai'];
        }

        $filename = "Rekap_Nilai_{$filterKelas}_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // Add UTF-8 BOM for Microsoft Excel compatibility
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

        // Build CSV header
        $header = ['NIM', 'Nama Mahasiswa', 'Kelas'];
        foreach ($tasks as $t) {
            $header[] = "P{$t['pertemuan_nomor']}: " . $t['judul'];
        }
        $header[] = 'Rata-Rata';
        fputcsv($out, $header);

        // Build rows
        foreach ($students as $mhs) {
            $row = [
                $mhs['nim'],
                $mhs['nama'],
                $mhs['kelas']
            ];

            $totalScore = 0;
            $gradedCount = 0;

            foreach ($tasks as $t) {
                $val = $gradesMap[$mhs['id']][$t['id']] ?? null;
                if ($val !== null && $val !== '') {
                    $row[] = number_format((float)$val, 1);
                    $totalScore += (float)$val;
                    $gradedCount++;
                } else {
                    $row[] = '-';
                }
            }

            $avg = $gradedCount > 0 ? number_format($totalScore / $gradedCount, 2) : '-';
            $row[] = $avg;

            fputcsv($out, $row);
        }

        fclose($out);
        exit;
    }

    /**
     * Safely stream file download.
     */
    private static function streamDownload(string $filePath, string $downloadName): void {
        if (!file_exists($filePath)) {
            Flight::response()->status(404);
            echo "File not found.";
            exit;
        }

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

        $handle = fopen($filePath, 'rb');
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
        exit;
    }

    /**
     * Display Dosen profile edit page.
     */
    public static function profilView(): void {
        $user = auth_user();
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND role = 'dosen' LIMIT 1");
        $stmt->execute([':id' => $user['id']]);
        $userData = $stmt->fetch();

        Flight::render('dosen/profil', [
            'title' => 'Edit Profil Dosen - ' . config('app_name'),
            'user' => $userData ?: $user
        ]);
    }

    /**
     * Process Dosen profile update (Nama Lengkap & optional Password change).
     */
    public static function profilSubmit(): void {
        $user = auth_user();
        $nama = trim((string)Flight::request()->data->nama);
        $passwordLama = (string)Flight::request()->data->password_lama;
        $passwordBaru = (string)Flight::request()->data->password_baru;
        $konfirmasiPassword = (string)Flight::request()->data->konfirmasi_password;

        if (empty($nama)) {
            flash('danger', 'Nama lengkap tidak boleh kosong.');
            Flight::redirect('/dosen/profil');
            return;
        }

        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND role = 'dosen' LIMIT 1");
        $stmt->execute([':id' => $user['id']]);
        $currentDosen = $stmt->fetch();

        if (!$currentDosen) {
            flash('danger', 'Data akun dosen tidak ditemukan.');
            Flight::redirect('/dosen');
            return;
        }

        // Handle photo upload / deletion
        $fotoToSave = $currentDosen['foto'] ?? null;
        $hapusFoto = !empty(Flight::request()->data->hapus_foto);

        if ($hapusFoto) {
            delete_avatar($currentDosen['foto'] ?? null);
            $fotoToSave = null;
        }

        if (!empty($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $fotoToSave = handle_avatar_upload($_FILES['foto'], $currentDosen['foto'] ?? null, 'avatar_dosen');
            } catch (Throwable $e) {
                flash('danger', $e->getMessage());
                Flight::redirect('/dosen/profil');
                return;
            }
        }

        // Check if user requested password update
        if (!empty($passwordBaru)) {
            if (empty($passwordLama)) {
                flash('danger', 'Silakan masukkan password saat ini untuk mengonfirmasi perubahan password.');
                Flight::redirect('/dosen/profil');
                return;
            }

            if (!password_verify($passwordLama, $currentDosen['password_hash'])) {
                flash('danger', 'Password saat ini yang Anda masukkan salah.');
                Flight::redirect('/dosen/profil');
                return;
            }

            if (strlen($passwordBaru) < 6) {
                flash('danger', 'Password baru minimal harus 6 karakter.');
                Flight::redirect('/dosen/profil');
                return;
            }

            if ($passwordBaru !== $konfirmasiPassword) {
                flash('danger', 'Konfirmasi password baru tidak cocok.');
                Flight::redirect('/dosen/profil');
                return;
            }

            $newHash = password_hash($passwordBaru, PASSWORD_BCRYPT);
            $updateStmt = $db->prepare("UPDATE users SET nama = :nama, foto = :foto, password_hash = :hash WHERE id = :id");
            $updateStmt->execute([
                ':nama' => $nama,
                ':foto' => $fotoToSave,
                ':hash' => $newHash,
                ':id'   => $user['id']
            ]);

            $_SESSION['user']['nama'] = $nama;
            $_SESSION['user']['foto'] = $fotoToSave;

            log_security('PROFILE_UPDATED', 'Dosen updated name, photo, and password', '/dosen/profil', [
                'user_id' => $user['id'],
                'nim' => $user['nim']
            ]);

            flash('success', 'Profil dan password Anda berhasil diperbarui.');
            Flight::redirect('/dosen/profil');
            return;
        }

        // Only update name & photo
        $updateStmt = $db->prepare("UPDATE users SET nama = :nama, foto = :foto WHERE id = :id");
        $updateStmt->execute([
            ':nama' => $nama,
            ':foto' => $fotoToSave,
            ':id'   => $user['id']
        ]);

        $_SESSION['user']['nama'] = $nama;
        $_SESSION['user']['foto'] = $fotoToSave;

        log_security('PROFILE_UPDATED', 'Dosen updated profile', '/dosen/profil', [
            'user_id' => $user['id'],
            'nim' => $user['nim']
        ]);

        flash('success', 'Profil Anda berhasil diperbarui.');
        Flight::redirect('/dosen/profil');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Manajemen Profil Mahasiswa (CRUD)
    // ─────────────────────────────────────────────────────────────────────


    /** List all mahasiswa with optional kelas filter. */
    public static function mahasiswaIndex(): void {
        $db = get_db();
        $filterKelas = Flight::request()->query->kelas;
        $allowedKelas = config('kelas_list', ['S5']);

        if ($filterKelas && in_array($filterKelas, $allowedKelas, true)) {
            $stmt = $db->prepare("SELECT id, nim, nama, kelas, wajib_ganti_password FROM users WHERE role = 'mahasiswa' AND kelas = :kelas ORDER BY nim ASC");
            $stmt->execute([':kelas' => $filterKelas]);
        } else {
            $filterKelas = null;
            $stmt = $db->query("SELECT id, nim, nama, kelas, wajib_ganti_password FROM users WHERE role = 'mahasiswa' ORDER BY kelas ASC, nim ASC");
        }
        $mahasiswaList = $stmt->fetchAll();

        Flight::render('dosen/mahasiswa-index', [
            'title'         => 'Kelola Mahasiswa - ' . config('app_name'),
            'mahasiswaList' => $mahasiswaList,
            'filterKelas'   => $filterKelas,
        ]);
    }

    /** Show form to add new mahasiswa. */
    public static function mahasiswaTambahView(): void {
        Flight::render('dosen/mahasiswa-form', [
            'title'      => 'Tambah Mahasiswa - ' . config('app_name'),
            'isEdit'     => false,
            'mahasiswa'  => null,
        ]);
    }

    /** Process new mahasiswa creation. */
    public static function mahasiswaTambahSubmit(): void {
        $req   = Flight::request()->data;
        $nim   = trim((string)$req->nim);
        $nama  = trim((string)$req->nama);
        $kelas = trim((string)$req->kelas);
        $pass  = (string)$req->password;

        $allowedKelas = config('kelas_list', ['S5']);
        if (empty($nim) || empty($nama) || !in_array($kelas, $allowedKelas, true) || empty($pass)) {
            flash('danger', 'NIM, nama, kelas, dan password wajib diisi.');
            Flight::redirect('/dosen/mahasiswa/tambah');
            return;
        }
        if (strlen($pass) < 6) {
            flash('danger', 'Password minimal 6 karakter.');
            Flight::redirect('/dosen/mahasiswa/tambah');
            return;
        }

        $db = get_db();
        $chk = $db->prepare("SELECT id FROM users WHERE nim = :nim LIMIT 1");
        $chk->execute([':nim' => $nim]);
        if ($chk->fetch()) {
            flash('danger', "NIM $nim sudah terdaftar.");
            Flight::redirect('/dosen/mahasiswa/tambah');
            return;
        }

        // Handle optional photo upload for student
        $fotoName = null;
        if (!empty($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $fotoName = handle_avatar_upload($_FILES['foto'], null, 'avatar_mhs');
            } catch (Throwable $e) {
                flash('danger', $e->getMessage());
                Flight::redirect('/dosen/mahasiswa/tambah');
                return;
            }
        }

        $db->prepare("INSERT INTO users (nim, nama, kelas, role, password_hash, foto, wajib_ganti_password) VALUES (:nim, :nama, :kelas, 'mahasiswa', :hash, :foto, 1)")
           ->execute([':nim' => $nim, ':nama' => $nama, ':kelas' => $kelas, ':hash' => password_hash($pass, PASSWORD_BCRYPT), ':foto' => $fotoName]);

        flash('success', "Mahasiswa $nama ($nim) berhasil ditambahkan.");
        Flight::redirect('/dosen/mahasiswa');
    }

    /** Show edit form for a mahasiswa. */
    public static function mahasiswaEditView(string $id): void {
        $db   = get_db();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND role = 'mahasiswa' LIMIT 1");
        $stmt->execute([':id' => $id]);
        $mhs  = $stmt->fetch();

        if (!$mhs) {
            flash('danger', 'Data mahasiswa tidak ditemukan.');
            Flight::redirect('/dosen/mahasiswa');
            return;
        }

        Flight::render('dosen/mahasiswa-form', [
            'title'     => 'Edit Mahasiswa - ' . config('app_name'),
            'isEdit'    => true,
            'mahasiswa' => $mhs,
        ]);
    }

    /** Process mahasiswa edit (NIM + nama only; password has its own endpoint). */
    public static function mahasiswaEditSubmit(string $id): void {
        $db   = get_db();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND role = 'mahasiswa' LIMIT 1");
        $stmt->execute([':id' => $id]);
        $mhs  = $stmt->fetch();

        if (!$mhs) {
            flash('danger', 'Data mahasiswa tidak ditemukan.');
            Flight::redirect('/dosen/mahasiswa');
            return;
        }

        $req   = Flight::request()->data;
        $nim   = trim((string)$req->nim);
        $nama  = trim((string)$req->nama);
        $kelas = trim((string)$req->kelas);

        $allowedKelas = config('kelas_list', ['S5']);
        if (empty($nim) || empty($nama) || !in_array($kelas, $allowedKelas, true)) {
            flash('danger', 'NIM, nama, dan kelas wajib diisi.');
            Flight::redirect("/dosen/mahasiswa/$id/edit");
            return;
        }

        // Check NIM uniqueness (exclude self)
        $chk = $db->prepare("SELECT id FROM users WHERE nim = :nim AND id != :id LIMIT 1");
        $chk->execute([':nim' => $nim, ':id' => $id]);
        if ($chk->fetch()) {
            flash('danger', "NIM $nim sudah digunakan oleh mahasiswa lain.");
            Flight::redirect("/dosen/mahasiswa/$id/edit");
            return;
        }

        $fotoToSave = $mhs['foto'] ?? null;
        if (!empty(Flight::request()->data->hapus_foto)) {
            delete_avatar($mhs['foto'] ?? null);
            $fotoToSave = null;
        }

        if (!empty($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $fotoToSave = handle_avatar_upload($_FILES['foto'], $mhs['foto'] ?? null, 'avatar_mhs');
            } catch (Throwable $e) {
                flash('danger', $e->getMessage());
                Flight::redirect("/dosen/mahasiswa/$id/edit");
                return;
            }
        }

        $db->prepare("UPDATE users SET nim = :nim, nama = :nama, kelas = :kelas, foto = :foto WHERE id = :id")
           ->execute([':nim' => $nim, ':nama' => $nama, ':kelas' => $kelas, ':foto' => $fotoToSave, ':id' => $id]);

        flash('success', "Data mahasiswa berhasil diperbarui.");
        Flight::redirect('/dosen/mahasiswa');
    }

    /** Reset mahasiswa password to a new one set by dosen. */
    public static function mahasiswaResetPassword(string $id): void {
        $db   = get_db();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND role = 'mahasiswa' LIMIT 1");
        $stmt->execute([':id' => $id]);
        $mhs  = $stmt->fetch();

        if (!$mhs) {
            flash('danger', 'Data mahasiswa tidak ditemukan.');
            Flight::redirect('/dosen/mahasiswa');
            return;
        }

        $pass = (string)Flight::request()->data->password_baru;
        if (strlen($pass) < 6) {
            flash('danger', 'Password baru minimal 6 karakter.');
            Flight::redirect("/dosen/mahasiswa/$id/edit");
            return;
        }

        $db->prepare("UPDATE users SET password_hash = :hash, wajib_ganti_password = 1 WHERE id = :id")
           ->execute([':hash' => password_hash($pass, PASSWORD_BCRYPT), ':id' => $id]);

        flash('success', "Password mahasiswa {$mhs['nama']} berhasil direset. Mahasiswa wajib ganti password saat login.");
        Flight::redirect('/dosen/mahasiswa');
    }

    /** Delete a mahasiswa and all their submission files. */
    public static function mahasiswaHapus(string $id): void {
        $db   = get_db();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND role = 'mahasiswa' LIMIT 1");
        $stmt->execute([':id' => $id]);
        $mhs  = $stmt->fetch();

        if ($mhs) {
            // Delete avatar image
            delete_avatar($mhs['foto'] ?? null);

            // Remove physical submission files
            $subs = $db->prepare("SELECT file_simpan FROM pengumpulan WHERE user_id = :uid");
            $subs->execute([':uid' => $id]);
            while ($sub = $subs->fetch()) {
                delete_file_from_storage($sub['file_simpan']);
            }
            $db->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $id]);
            cleanup_orphaned_uploads();
            flash('success', "Mahasiswa {$mhs['nama']} ({$mhs['nim']}) berhasil dihapus.");
        }

        Flight::redirect('/dosen/mahasiswa');
    }
}


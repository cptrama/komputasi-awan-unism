<?php
/**
 * Complete Database Reseed Script
 * Wipes database and seeds Dosen, Mahasiswa (Single Class: Semester 5), Pertemuan, Tugas, and Submissions
 */

date_default_timezone_set('Asia/Makassar');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers.php';

echo "===============================================\n";
echo "🔄 Memulai Reset & Seeding Ulang (Semester 5)\n";
echo "===============================================\n\n";

$db = get_db();

// 1. Wipe and re-apply schema
$db->exec('PRAGMA foreign_keys = OFF;');
$db->exec('DROP TABLE IF EXISTS login_attempts;');
$db->exec('DROP TABLE IF EXISTS pengumpulan;');
$db->exec('DROP TABLE IF EXISTS tugas;');
$db->exec('DROP TABLE IF EXISTS pertemuan;');
$db->exec('DROP TABLE IF EXISTS users;');
$db->exec('PRAGMA foreign_keys = ON;');

$schemaSql = file_get_contents(__DIR__ . '/../database/schema.sql');
$db->exec($schemaSql);
echo "✅ Schema database berhasil di-reset.\n";

// Ensure uploads directory exists
$uploadsDir = config('paths.uploads');
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// 2. Seed Dosen
$dosenHash = password_hash('admin123', PASSWORD_BCRYPT);
$db->prepare(
    "INSERT INTO users (nim, nama, kelas, role, password_hash, wajib_ganti_password) 
     VALUES ('1166082022231', 'Fajar Ramadhan', NULL, 'dosen', :hash, 0)"
)->execute([':hash' => $dosenHash]);
echo "✅ Akun Dosen dibuat: 1166082022231 / admin123\n";

// 3. Seed Mahasiswa Semester 5 from CSV
$csvFile = __DIR__ . '/mahasiswa.csv';
$handle = fopen($csvFile, 'r');
fgetcsv($handle); // skip header
$mhsCount = 0;

$mhsInsertStmt = $db->prepare(
    "INSERT INTO users (nim, nama, kelas, role, password_hash, wajib_ganti_password) 
     VALUES (:nim, :nama, :kelas, 'mahasiswa', :hash, 1)"
);

while (($row = fgetcsv($handle)) !== false) {
    if (count($row) < 3) continue;
    $nim = trim($row[0]);
    $nama = trim($row[1]);
    $kelas = strtoupper(trim($row[2]));
    $hash = password_hash($nim, PASSWORD_BCRYPT);

    $mhsInsertStmt->execute([
        ':nim' => $nim,
        ':nama' => $nama,
        ':kelas' => $kelas,
        ':hash' => $hash
    ]);
    $mhsCount++;
}
fclose($handle);
echo "✅ {$mhsCount} Mahasiswa Semester 5 berhasil di-seed dari CSV.\n";

// 4. Seed Sample Pertemuan for Semester 5
$pertemuanData = [
    [
        'kelas' => 'S5',
        'mk' => 'Komputasi Awan',
        'no' => 1,
        'topik' => 'Pengenalan Cloud Computing & Model Layanan (IaaS, PaaS, SaaS)',
        'tgl' => '2026-10-01',
        'file' => 'materi_s5_p1_cloud_computing.pdf'
    ],
    [
        'kelas' => 'S5',
        'mk' => 'Komputasi Awan',
        'no' => 2,
        'topik' => 'Arsitektur Virtualisasi, Hypervisor, dan Containerization',
        'tgl' => '2026-10-08',
        'file' => 'materi_s5_p2_virtualisasi.pdf'
    ],
    [
        'kelas' => 'S5',
        'mk' => 'Komputasi Awan',
        'no' => 3,
        'topik' => 'Cloud Storage & Manajemen Sumber Daya Komputasi',
        'tgl' => '2026-10-15',
        'file' => 'materi_s5_p3_cloud_storage.pdf'
    ],
    [
        'kelas' => 'S5',
        'mk' => 'Komputasi Awan',
        'no' => 4,
        'topik' => 'Keamanan, Skalabilitas, dan Monitoring pada Infrastruktur Cloud',
        'tgl' => '2026-10-22',
        'file' => null
    ],
];

$pInsertStmt = $db->prepare(
    "INSERT INTO pertemuan (kelas, mata_kuliah, nomor, topik, tanggal, file_materi) 
     VALUES (:kelas, :mk, :no, :topik, :tgl, :file)"
);

$pertemuanIds = [];
foreach ($pertemuanData as $p) {
    if ($p['file']) {
        @file_put_contents($uploadsDir . '/' . $p['file'], "Dummy Materi Content for Semester 5: {$p['topik']}");
    }
    $pInsertStmt->execute([
        ':kelas' => $p['kelas'],
        ':mk' => $p['mk'],
        ':no' => $p['no'],
        ':topik' => $p['topik'],
        ':tgl' => $p['tgl'],
        ':file' => $p['file']
    ]);
    $pertemuanIds["P{$p['no']}"] = $db->lastInsertId();
}
echo "✅ " . count($pertemuanData) . " Pertemuan berhasil di-seed untuk Semester 5.\n";

// 5. Seed Tasks for Semester 5
$now = time();
$tasksData = [
    [
        'key' => 't0',
        'p_key' => 'P1',
        'judul' => 'Tugas 0: Refleksi Pemahaman Arsitektur Cloud Computing',
        'desc' => "Kuis dan refleksi pendahuluan pemahaman arsitektur komputasi awan dan model layanannya.",
        'buka' => $now - 86400 * 10,
        'tutup' => $now - 86400 * 3   // Telah ditutup 3 hari lalu
    ],
    [
        'key' => 't1',
        'p_key' => 'P1',
        'judul' => 'Tugas 1: Studi Kasus Implementasi IaaS vs PaaS vs SaaS',
        'desc' => "1. Pilih satu studi kasus perusahaan teknologi.\n2. Analisis kebutuhan infrastruktur dan perbandingan IaaS, PaaS, dan SaaS yang digunakan.\n3. Susun laporan analisis dalam format PDF maksimal 20 MB.",
        'buka' => $now - 86400 * 2, // Buka 2 hari lalu
        'tutup' => $now + 86400 * 5  // Tutup 5 hari ke depan (Terbuka)
    ],
    [
        'key' => 't2',
        'p_key' => 'P2',
        'judul' => 'Tugas 2: Simulasi Virtual Machine & Perancangan Container Docker',
        'desc' => "Rancang instalasi container berbasis Docker atau VM dan dokumentasikan langkah konfigurasinya dalam format PDF.",
        'buka' => $now - 3600,
        'tutup' => $now + 86400 * 7  // Tutup 7 hari ke depan (Terbuka)
    ],
];

$tInsertStmt = $db->prepare(
    "INSERT INTO tugas (pertemuan_id, judul, deskripsi, dibuka_pada, ditutup_pada) 
     VALUES (:pid, :judul, :deskripsi, :buka, :tutup)"
);

$taskIds = [];
foreach ($tasksData as $t) {
    $pid = $pertemuanIds[$t['p_key']];
    $tInsertStmt->execute([
        ':pid' => $pid,
        ':judul' => $t['judul'],
        ':deskripsi' => $t['desc'],
        ':buka' => $t['buka'],
        ':tutup' => $t['tutup']
    ]);
    $taskIds[$t['key']] = $db->lastInsertId();
}
echo "✅ " . count($tasksData) . " Tugas perkuliahan berhasil dibuat untuk Semester 5.\n";

// 6. Seed Submissions
$allStudents = $db->query("SELECT id, nim, nama, kelas FROM users WHERE role = 'mahasiswa' ORDER BY id ASC")->fetchAll();

$subInsertStmt = $db->prepare(
    "INSERT INTO pengumpulan (tugas_id, user_id, file_simpan, file_asli, waktu_upload, nilai, feedback) 
     VALUES (:tid, :uid, :simpan, :asli, :waktu, :nilai, :feedback)"
);

$seededSubCount = 0;
foreach ($allStudents as $idx => $mhs) {
    // Tugas 0 (Closed): only first 8 students submitted, others missed
    if ($idx < 8) {
        $cleanNim = sanitize_filename_base($mhs['nim']);
        $cleanNama = sanitize_filename_base($mhs['nama']);
        $fileName = "{$cleanNim}_{$cleanNama}_Pertemuan0_Kuis.pdf";
        @file_put_contents($uploadsDir . '/' . $fileName, "Dummy kuis response by {$mhs['nama']} ({$mhs['nim']})");

        $subInsertStmt->execute([
            ':tid' => $taskIds['t0'],
            ':uid' => $mhs['id'],
            ':simpan' => $fileName,
            ':asli' => "Kuis_PraSyarat_{$cleanNama}.pdf",
            ':waktu' => $now - 86400 * 5,
            ':nilai' => 88.0,
            ':feedback' => "Jawaban kuis sangat tepat."
        ]);
        $seededSubCount++;
    }

    // Tugas 1 submissions (12 out of 20 submit)
    if ($idx < 12) {
        $cleanNim = sanitize_filename_base($mhs['nim']);
        $cleanNama = sanitize_filename_base($mhs['nama']);
        $fileName = "{$cleanNim}_{$cleanNama}_Pertemuan1.pdf";
        @file_put_contents($uploadsDir . '/' . $fileName, "Dummy submission report by {$mhs['nama']} ({$mhs['nim']})");

        // Grade the first 5 students
        $isGraded = ($idx < 5);
        $nilai = $isGraded ? (85.0 + ($idx * 3)) : null;
        $feedback = $isGraded ? "Analisis protokol dan identifikasi layer OSI sangat jelas dan rapi. Nilai bagus!" : null;

        $subInsertStmt->execute([
            ':tid' => $taskIds['t1'],
            ':uid' => $mhs['id'],
            ':simpan' => $fileName,
            ':asli' => "Laporan_Wireshark_{$cleanNama}.pdf",
            ':waktu' => $now - 3600 * (15 - $idx),
            ':nilai' => $nilai,
            ':feedback' => $feedback
        ]);
        $seededSubCount++;
    }

    // Tugas 2 submissions (6 students submit)
    if ($idx >= 3 && $idx <= 8) {
        $cleanNim = sanitize_filename_base($mhs['nim']);
        $cleanNama = sanitize_filename_base($mhs['nama']);
        $fileName = "{$cleanNim}_{$cleanNama}_Pertemuan2.pdf";
        @file_put_contents($uploadsDir . '/' . $fileName, "Dummy VLSM calculation report by {$mhs['nama']} ({$mhs['nim']})");

        $subInsertStmt->execute([
            ':tid' => $taskIds['t2'],
            ':uid' => $mhs['id'],
            ':simpan' => $fileName,
            ':asli' => "Perhitungan_VLSM_{$cleanNama}.pdf",
            ':waktu' => $now - 1800 * ($idx),
            ':nilai' => null,
            ':feedback' => null
        ]);
        $seededSubCount++;
    }
}

echo "✅ {$seededSubCount} Submission mahasiswa Semester 5 berhasil di-seed (termasuk tugas dinilai & belum dinilai).\n\n";
echo "===============================================\n";
echo "🎉 Seed Ulang Selesai! Data Semester 5 siap digunakan.\n";
echo "===============================================\n";

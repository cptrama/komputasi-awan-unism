<?php
/**
 * CLI Script: Seed Mahasiswa dari CSV
 * Usage:
 *   php scripts/seed.php [path/to/mahasiswa.csv]
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers.php';

$csvFile = $argv[1] ?? __DIR__ . '/mahasiswa.csv';

echo "=== Seeder Akun Mahasiswa dari CSV ===\n";
echo "Membaca file: {$csvFile}\n";

if (!file_exists($csvFile)) {
    echo "[ERROR] File CSV tidak ditemukan: {$csvFile}\n";
    echo "Contoh format header CSV: nim,nama,kelas\n";
    exit(1);
}

$handle = fopen($csvFile, 'r');
if ($handle === false) {
    echo "[ERROR] Tidak dapat membuka file CSV.\n";
    exit(1);
}

// 1. Read & Validate Header
$header = fgetcsv($handle);
if (!$header) {
    echo "[ERROR] File CSV kosong.\n";
    fclose($handle);
    exit(1);
}

// Clean BOM and trim
$header = array_map(function($h) {
    return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)));
}, $header);

if ($header !== ['nim', 'nama', 'kelas']) {
    echo "[ERROR] Header CSV tidak valid. Header harus persis: nim,nama,kelas\n";
    echo "Ditemukan: " . implode(',', $header) . "\n";
    fclose($handle);
    exit(1);
}

$db = get_db();
$allowedKelas = config('kelas_list', ['S5']);

$totalRows = 0;
$insertedCount = 0;
$duplicateCount = 0;
$invalidCount = 0;

$checkStmt = $db->prepare("SELECT id, nama FROM users WHERE nim = :nim LIMIT 1");
$insertStmt = $db->prepare(
    "INSERT INTO users (nim, nama, kelas, role, password_hash, wajib_ganti_password) 
     VALUES (:nim, :nama, :kelas, 'mahasiswa', :hash, 1)"
);

$db->beginTransaction();

while (($row = fgetcsv($handle)) !== false) {
    $totalRows++;
    if (count($row) < 3) {
        echo "[BARIS {$totalRows}] Ditolak: Kolom kurang lengkap.\n";
        $invalidCount++;
        continue;
    }

    $nim = trim((string)$row[0]);
    $nama = trim((string)$row[1]);
    $kelas = strtoupper(trim((string)$row[2]));

    if (empty($nim) || empty($nama)) {
        echo "[BARIS {$totalRows}] Ditolak: NIM atau Nama kosong.\n";
        $invalidCount++;
        continue;
    }

    if (!in_array($kelas, $allowedKelas, true)) {
        echo "[BARIS {$totalRows}] Ditolak: Kelas '{$kelas}' tidak valid. Pilihan valid: " . implode(', ', $allowedKelas) . "\n";
        $invalidCount++;
        continue;
    }

    // Check duplicate NIM
    $checkStmt->execute([':nim' => $nim]);
    $existing = $checkStmt->fetch();
    if ($existing) {
        echo "[BARIS {$totalRows}] Dilewati (Duplikat NIM): NIM {$nim} sudah terdaftar untuk {$existing['nama']}.\n";
        $duplicateCount++;
        continue;
    }

    // Default password = NIM (bcrypt hash)
    $passwordHash = password_hash($nim, PASSWORD_BCRYPT);

    $insertStmt->execute([
        ':nim' => $nim,
        ':nama' => $nama,
        ':kelas' => $kelas,
        ':hash' => $passwordHash
    ]);

    $insertedCount++;
}

$db->commit();
fclose($handle);

echo "\n=== Hasil Eksekusi Seeder ===\n";
echo "Total baris data : {$totalRows}\n";
echo "Berhasil dibuat  : {$insertedCount}\n";
echo "Duplikat dilewati: {$duplicateCount}\n";
echo "Baris tidak valid: {$invalidCount}\n";
echo "=============================\n";

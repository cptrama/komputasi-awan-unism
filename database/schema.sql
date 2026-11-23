-- Database schema for SINTA (Sistem Informasi Tugas)
-- SQLite 3 with Foreign Keys enabled

PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nim TEXT UNIQUE NOT NULL,
    nama TEXT NOT NULL,
    kelas TEXT NULL, -- 'S3', 'S5', 'S7' for mahasiswa, NULL for dosen
    role TEXT NOT NULL, -- 'dosen', 'mahasiswa'
    password_hash TEXT NOT NULL,
    foto TEXT NULL,
    wajib_ganti_password INTEGER DEFAULT 1 NOT NULL
);

CREATE TABLE IF NOT EXISTS pertemuan (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kelas TEXT NOT NULL, -- 'S3', 'S5', 'S7'
    mata_kuliah TEXT NULL,
    nomor INTEGER NOT NULL,
    topik TEXT NOT NULL,
    tanggal TEXT NOT NULL,
    file_materi TEXT NULL
);

CREATE TABLE IF NOT EXISTS tugas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pertemuan_id INTEGER NOT NULL REFERENCES pertemuan(id) ON DELETE CASCADE,
    judul TEXT NOT NULL,
    deskripsi TEXT NOT NULL,
    dibuka_pada INTEGER NOT NULL, -- Unix timestamp (INTEGER)
    ditutup_pada INTEGER NOT NULL -- Unix timestamp (INTEGER)
);

CREATE TABLE IF NOT EXISTS pengumpulan (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tugas_id INTEGER NOT NULL REFERENCES tugas(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    file_simpan TEXT NOT NULL,
    file_asli TEXT NOT NULL,
    waktu_upload INTEGER NOT NULL, -- Unix timestamp (INTEGER)
    nilai REAL NULL,
    feedback TEXT NULL,
    UNIQUE(tugas_id, user_id)
);

CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nim TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    attempt_time INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_users_nim ON users(nim);
CREATE INDEX IF NOT EXISTS idx_pertemuan_kelas ON pertemuan(kelas);
CREATE INDEX IF NOT EXISTS idx_tugas_pertemuan ON tugas(pertemuan_id);
CREATE INDEX IF NOT EXISTS idx_pengumpulan_tugas ON pengumpulan(tugas_id);
CREATE INDEX IF NOT EXISTS idx_pengumpulan_user ON pengumpulan(user_id);
CREATE INDEX IF NOT EXISTS idx_login_attempts_lookup ON login_attempts(nim, ip_address, attempt_time);

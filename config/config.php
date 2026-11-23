<?php
/**
 * Konfigurasi Umum Aplikasi
 * SIPA (Sistem Informasi Pengumpulan Tugas)
 */

return [
    'app_name' => 'SIPA (Sistem Informasi Pengumpulan Tugas)',
    'app_env' => 'production', // 'development' or 'production'
    'timezone' => 'Asia/Makassar',
    'tahun_ajar' => '2026/2027',
    'semester' => '5 (Ganjil)',
    'mata_kuliah' => 'Komputasi Awan',
    'prodi' => 'Teknologi Informasi',

    // Batas upload file (dalam bytes)
    'max_upload_tugas_bytes' => 20 * 1024 * 1024, // 20 MB
    'max_upload_materi_bytes' => 50 * 1024 * 1024, // 50 MB

    // Daftar kelas yang valid (Single Class: Semester 5)
    'kelas_list' => ['S5'],

    // Whitelist ekstensi yang diperbolehkan untuk upload tugas
    'allowed_extensions' => [
        'pdf',
        'doc',
        'docx',
        'ppt',
        'pptx'
    ],

    // Pemetaan ekstensi ke MIME type yang valid (finfo)
    'mime_map' => [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/vnd.ms-office'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/vnd.ms-office'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    ],

    // Batas rate limiting login
    'rate_limit' => [
        'max_attempts' => 5,
        'window_seconds' => 15 * 60, // 15 menit
        'block_seconds' => 15 * 60   // 15 menit
    ],

    // Path penyimpanan
    'paths' => [
        'database' => __DIR__ . '/../database/app.db',
        'uploads' => __DIR__ . '/../storage/uploads',
        'logs' => __DIR__ . '/../storage/logs/security.log',
    ]
];

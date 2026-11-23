<?php
/**
 * Front Controller
 * SIPA (Sistem Informasi Pengumpulan Tugas)
 */

// 1. Set Timezone before any date logic
date_default_timezone_set('Asia/Makassar');

// 2. Composer Autoload & Configuration
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/MahasiswaController.php';
require_once __DIR__ . '/../app/controllers/DosenController.php';

use App\Middleware\AuthMiddleware;
use App\Middleware\DosenMiddleware;
use App\Middleware\MahasiswaMiddleware;
use App\Controllers\AuthController;
use App\Controllers\MahasiswaController;
use App\Controllers\DosenController;

// 3. Configure Secure Native PHP Session
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// 4. Flight Configuration & Defense-in-depth Settings
Flight::set('flight.views.path', __DIR__ . '/../app/views');
Flight::set('flight.base_url', '/');

// CRITICAL (CVE-2026-42551 Mitigation): Disable HTTP method spoofing
Flight::set('flight.allow_method_override', false);

// 5. Global Error & Exception Handling (OWASP A10: Fail-closed & Generic Error Message)
Flight::map('error', function(Throwable $ex) {
    log_security('INTERNAL_ERROR', $ex->getMessage(), Flight::request()->url, [
        'file' => $ex->getFile(),
        'line' => $ex->getLine()
    ]);
    
    Flight::response()->status(500);
    Flight::render('layout', [
        'title' => 'Terjadi Kesalahan Sistem - 500',
        'body' => '<div class="alert alert-danger my-5 text-center shadow-sm p-4">
                    <h4><i class="bi bi-exclamation-octagon-fill me-2"></i>500 - Terjadi Kesalahan Pada Server</h4>
                    <p class="mb-3 text-muted">Sistem mengalami gangguan saat memproses permintaan Anda. Silakan coba beberapa saat lagi.</p>
                    <a href="/" class="btn btn-primary"><i class="bi bi-house-door me-1"></i>Kembali ke Beranda</a>
                   </div>'
    ]);
    exit;
});

Flight::map('notFound', function() {
    Flight::response()->status(404);
    Flight::render('layout', [
        'title' => 'Halaman Tidak Ditemukan - 404',
        'body' => '<div class="alert alert-warning my-5 text-center shadow-sm p-4">
                    <h4><i class="bi bi-question-diamond-fill me-2"></i>404 - Halaman Tidak Ditemukan</h4>
                    <p class="mb-3 text-muted">Halaman atau resource yang Anda cari tidak tersedia atau URL tidak valid.</p>
                    <a href="/" class="btn btn-primary"><i class="bi bi-house-door me-1"></i>Kembali ke Beranda</a>
                   </div>'
    ]);
    exit;
});

// 6. Global CSRF Protection on POST requests
Flight::before('start', function() {
    AuthMiddleware::verifyCsrf();
});

// ==========================================
// 7. Route Definitions
// ==========================================

// Root Redirect
Flight::route('GET /', function() {
    if (!is_logged_in()) {
        Flight::redirect('/login');
    } elseif (is_dosen()) {
        Flight::redirect('/dosen');
    } else {
        Flight::redirect('/dashboard');
    }
});

// Auth Routes (Public & Authenticated)
Flight::route('GET /login', [AuthController::class, 'loginView']);
Flight::route('POST /login', [AuthController::class, 'loginSubmit']);

Flight::route('GET /ganti-password', function() {
    AuthMiddleware::requireLogin();
    AuthController::gantiPasswordView();
});
Flight::route('POST /ganti-password', function() {
    AuthMiddleware::requireLogin();
    AuthController::gantiPasswordSubmit();
});
Flight::route('POST /logout', function() {
    AuthMiddleware::requireLogin();
    AuthController::logout();
});

// Shared Authenticated Routes (Accessible by both Dosen & Mahasiswa)
Flight::route('GET /materi/@id:[0-9]+/download', function(string $id) {
    AuthMiddleware::requireLogin();
    MahasiswaController::downloadMateri($id);
});

// Mahasiswa Routes
Flight::group('', function() {
    Flight::route('GET /dashboard', [MahasiswaController::class, 'dashboard']);
    Flight::route('GET /pertemuan', [MahasiswaController::class, 'pertemuanIndex']);
    Flight::route('GET /pertemuan/@id:[0-9]+', [MahasiswaController::class, 'pertemuanDetail']);
    Flight::route('POST /tugas/@id:[0-9]+/upload', [MahasiswaController::class, 'uploadTugas']);
    Flight::route('GET /pengumpulan/@id:[0-9]+/download', [MahasiswaController::class, 'downloadPengumpulan']);
    Flight::route('GET /nilai', [MahasiswaController::class, 'nilaiIndex']);
    Flight::route('GET /profil', [MahasiswaController::class, 'profilView']);
    Flight::route('POST /profil', [MahasiswaController::class, 'profilSubmit']);
}, [MahasiswaMiddleware::class]);

// Dosen Routes
Flight::group('/dosen', function() {
    Flight::route('GET /', [DosenController::class, 'dashboard']);
    
    // Pertemuan CRUD
    Flight::route('GET /pertemuan', [DosenController::class, 'pertemuanIndex']);
    Flight::route('GET /pertemuan/tambah', [DosenController::class, 'pertemuanTambahView']);
    Flight::route('POST /pertemuan', [DosenController::class, 'pertemuanTambahSubmit']);
    Flight::route('GET /pertemuan/@id:[0-9]+/edit', [DosenController::class, 'pertemuanEditView']);
    Flight::route('POST /pertemuan/@id:[0-9]+', [DosenController::class, 'pertemuanEditSubmit']);
    Flight::route('POST /pertemuan/@id:[0-9]+/hapus-file', [DosenController::class, 'pertemuanHapusFileMateri']);
    Flight::route('POST /pertemuan/@id:[0-9]+/hapus', [DosenController::class, 'pertemuanHapus']);
    
    // Tugas CRUD
    Flight::route('GET /tugas', [DosenController::class, 'tugasIndex']);
    Flight::route('GET /tugas/tambah', [DosenController::class, 'tugasTambahView']);
    Flight::route('POST /tugas', [DosenController::class, 'tugasTambahSubmit']);
    Flight::route('GET /tugas/@id:[0-9]+/edit', [DosenController::class, 'tugasEditView']);
    Flight::route('POST /tugas/@id:[0-9]+', [DosenController::class, 'tugasEditSubmit']);
    Flight::route('POST /tugas/@id:[0-9]+/hapus', [DosenController::class, 'tugasHapus']);
    
    // Submissions, Grading & ZIP
    Flight::route('GET /tugas/@id:[0-9]+/pengumpulan', [DosenController::class, 'pengumpulanIndex']);
    Flight::route('GET /tugas/@id:[0-9]+/download-zip', [DosenController::class, 'downloadZip']);
    Flight::route('POST /pengumpulan/@id:[0-9]+/nilai', [DosenController::class, 'simpanNilai']);
    Flight::route('POST /pengumpulan/@id:[0-9]+/buka-kembali', [DosenController::class, 'bukaKembaliPengumpulan']);
    Flight::route('GET /pengumpulan/@id:[0-9]+/download', [DosenController::class, 'downloadPengumpulan']);
    
    // Rekap & Export
    Flight::route('GET /rekap', [DosenController::class, 'rekapIndex']);
    Flight::route('GET /rekap/export', [DosenController::class, 'rekapExportCsv']);
    
    // Profil Dosen
    Flight::route('GET /profil', [DosenController::class, 'profilView']);
    Flight::route('POST /profil', [DosenController::class, 'profilSubmit']);

    // Manajemen Mahasiswa
    Flight::route('GET /mahasiswa', [DosenController::class, 'mahasiswaIndex']);
    Flight::route('GET /mahasiswa/tambah', [DosenController::class, 'mahasiswaTambahView']);
    Flight::route('POST /mahasiswa', [DosenController::class, 'mahasiswaTambahSubmit']);
    Flight::route('GET /mahasiswa/@id:[0-9]+/edit', [DosenController::class, 'mahasiswaEditView']);
    Flight::route('POST /mahasiswa/@id:[0-9]+', [DosenController::class, 'mahasiswaEditSubmit']);
    Flight::route('POST /mahasiswa/@id:[0-9]+/reset-password', [DosenController::class, 'mahasiswaResetPassword']);
    Flight::route('POST /mahasiswa/@id:[0-9]+/hapus', [DosenController::class, 'mahasiswaHapus']);
}, [DosenMiddleware::class]);

// 8. Start Flight Engine
Flight::start();

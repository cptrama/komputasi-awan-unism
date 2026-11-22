<?php
/**
 * Authentication and Authorization Middleware
 */

namespace App\Middleware;

use Flight;

class AuthMiddleware {

    /**
     * Ensures user is authenticated and handles mandatory password change.
     */
    public static function requireLogin(): void {
        if (!is_logged_in()) {
            flash('warning', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
            Flight::redirect('/login');
            exit;
        }

        $user = auth_user();
        $currentUri = Flight::request()->url;
        
        // If user must change password, allow only /ganti-password and /logout
        if (!empty($user['wajib_ganti_password']) && (int)$user['wajib_ganti_password'] === 1) {
            $allowedPaths = ['/ganti-password', '/logout'];
            $pathOnly = parse_url($currentUri, PHP_URL_PATH);
            
            if (!in_array($pathOnly, $allowedPaths, true)) {
                flash('warning', 'Anda wajib mengganti password awal sebelum dapat mengakses fitur lain.');
                Flight::redirect('/ganti-password');
                exit;
            }
        }
    }

    /**
     * Ensures authenticated user has role 'dosen'.
     */
    public static function requireDosen(): void {
        self::requireLogin();
        
        if (!is_dosen()) {
            log_security('ACCESS_DENIED', 'Forbidden: Mahasiswa attempted to access dosen route', Flight::request()->url, [
                'user_id' => auth_user()['id'] ?? null,
                'nim' => auth_user()['nim'] ?? null
            ]);
            Flight::response()->status(403);
            Flight::render('layout', [
                'title' => 'Akses Ditolak - 403',
                'body' => '<div class="alert alert-danger my-5 text-center shadow-sm p-4"><h4><i class="bi bi-shield-x me-2"></i>403 Akses Ditolak</h4><p class="text-muted">Anda tidak memiliki hak akses untuk membuka halaman ini.</p><a href="/dashboard" class="btn btn-primary mt-2"><i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard</a></div>'
            ]);
            exit;
        }
    }

    /**
     * Ensures authenticated user has role 'mahasiswa'.
     */
    public static function requireMahasiswa(): void {
        self::requireLogin();
        
        if (!is_mahasiswa()) {
            log_security('ACCESS_DENIED', 'Forbidden: Dosen attempted to access mahasiswa route', Flight::request()->url, [
                'user_id' => auth_user()['id'] ?? null,
                'nim' => auth_user()['nim'] ?? null
            ]);
            Flight::response()->status(403);
            Flight::render('layout', [
                'title' => 'Akses Ditolak - 403',
                'body' => '<div class="alert alert-danger my-5 text-center shadow-sm p-4"><h4><i class="bi bi-shield-x me-2"></i>403 Akses Ditolak</h4><p class="text-muted">Halaman ini dikhususkan untuk akun mahasiswa.</p><a href="/dosen" class="btn btn-primary mt-2"><i class="bi bi-arrow-left me-1"></i>Kembali ke Panel Dosen</a></div>'
            ]);
            exit;
        }
    }

    /**
     * Verifies CSRF token on all POST requests.
     */
    public static function verifyCsrf(): void {
        if (Flight::request()->method === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!verify_csrf($token)) {
                log_security('CSRF_FAILURE', 'Invalid or missing CSRF token', Flight::request()->url, [
                    'user_id' => auth_user()['id'] ?? null,
                    'nim' => auth_user()['nim'] ?? null
                ]);
                
                flash('danger', 'Sesi tidak valid atau token keamanan kedaluwarsa. Silakan ulangi kembali tindakan Anda.');
                $referer = $_SERVER['HTTP_REFERER'] ?? '/';
                Flight::redirect($referer);
                exit;
            }
        }
    }
}

/**
 * Middleware adapter for Flight group routing (Dosen).
 */
class DosenMiddleware {
    public function before($params): void {
        AuthMiddleware::requireDosen();
    }
}

/**
 * Middleware adapter for Flight group routing (Mahasiswa).
 */
class MahasiswaMiddleware {
    public function before($params): void {
        AuthMiddleware::requireMahasiswa();
    }
}

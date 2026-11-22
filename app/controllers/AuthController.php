<?php
/**
 * Auth Controller
 * Handles Login, Forced Password Change, Rate Limiting, and Logout
 */

namespace App\Controllers;

use Flight;
use PDO;

class AuthController {

    /**
     * Display login page.
     */
    public static function loginView(): void {
        if (is_logged_in()) {
            if (is_dosen()) {
                Flight::redirect('/dosen');
            } else {
                Flight::redirect('/dashboard');
            }
            return;
        }

        Flight::render('auth/login', [
            'title' => 'Login - ' . config('app_name')
        ]);
    }

    /**
     * Process login request with rate limiting and generic error responses.
     */
    public static function loginSubmit(): void {
        $nim = trim((string)Flight::request()->data->nim);
        $password = (string)Flight::request()->data->password;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $now = time();
        $db = get_db();

        // 1. Rate Limiting Check (5 failures in 15 minutes)
        $window = config('rate_limit.window_seconds', 900);
        $maxAttempts = config('rate_limit.max_attempts', 5);
        $blockWindowStart = $now - $window;

        // Cleanup old attempts (> 24 hours)
        $cleanupStmt = $db->prepare("DELETE FROM login_attempts WHERE attempt_time < :old");
        $cleanupStmt->execute([':old' => $now - 86400]);

        $checkStmt = $db->prepare(
            "SELECT COUNT(*) as fail_count, MAX(attempt_time) as last_time 
             FROM login_attempts 
             WHERE (nim = :nim OR ip_address = :ip) AND attempt_time > :window_start"
        );
        $checkStmt->execute([
            ':nim' => $nim,
            ':ip' => $ip,
            ':window_start' => $blockWindowStart
        ]);
        $rateData = $checkStmt->fetch();

        if ($rateData && (int)$rateData['fail_count'] >= $maxAttempts) {
            $lockoutRemaining = ($rateData['last_time'] + $window) - $now;
            if ($lockoutRemaining > 0) {
                log_security('RATE_LIMIT_BLOCKED', 'Too many failed login attempts', '/login', [
                    'nim' => $nim,
                    'ip' => $ip,
                    'fail_count' => $rateData['fail_count']
                ]);
                $minutesRemaining = ceil($lockoutRemaining / 60);
                flash('danger', "Terlalu banyak percobaan login gagal. Percobaan Anda diblokir sementara. Silakan coba lagi dalam {$minutesRemaining} menit.");
                Flight::redirect('/login');
                return;
            }
        }

        // 2. Validate input
        if (empty($nim) || empty($password)) {
            flash('danger', 'NIM dan password wajib diisi.');
            Flight::redirect('/login');
            return;
        }

        // 3. Query user by NIM
        $stmt = $db->prepare("SELECT * FROM users WHERE nim = :nim LIMIT 1");
        $stmt->execute([':nim' => $nim]);
        $user = $stmt->fetch();

        // 4. Verify password with bcrypt
        if ($user && password_verify($password, $user['password_hash'])) {
            // Clear past failed attempts for this NIM & IP
            $clearStmt = $db->prepare("DELETE FROM login_attempts WHERE nim = :nim OR ip_address = :ip");
            $clearStmt->execute([':nim' => $nim, ':ip' => $ip]);

            // OWASP A07: Regenerate session ID upon successful login
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            } else {
                session_start();
                session_regenerate_id(true);
            }

            // Save user data in session (omit password_hash)
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'nim' => $user['nim'],
                'nama' => $user['nama'],
                'kelas' => $user['kelas'],
                'role' => $user['role'],
                'wajib_ganti_password' => (int)$user['wajib_ganti_password'],
            ];

            log_security('AUTH_SUCCESS', 'User logged in successfully', '/login', [
                'user_id' => $user['id'],
                'nim' => $user['nim'],
                'role' => $user['role']
            ]);

            // If user must change password
            if ((int)$user['wajib_ganti_password'] === 1) {
                flash('info', 'Selamat datang! Demi keamanan akun, Anda wajib mengganti password awal terlebih dahulu.');
                Flight::redirect('/ganti-password');
                return;
            }

            flash('success', 'Selamat datang, ' . $user['nama'] . '!');
            $_SESSION['just_logged_in'] = true;
            if ($user['role'] === 'dosen') {
                Flight::redirect('/dosen');
            } else {
                Flight::redirect('/dashboard');
            }
            return;
        }

        // 5. Failed login
        $recordStmt = $db->prepare("INSERT INTO login_attempts (nim, ip_address, attempt_time) VALUES (:nim, :ip, :time)");
        $recordStmt->execute([
            ':nim' => $nim,
            ':ip' => $ip,
            ':time' => $now
        ]);

        log_security('AUTH_FAILURE', 'Invalid credentials', '/login', [
            'nim' => $nim,
            'ip' => $ip
        ]);

        // OWASP A07: Generic error message, do not reveal if NIM exists
        flash('danger', 'NIM atau password salah.');
        Flight::redirect('/login');
    }

    /**
     * Display forced change password page.
     */
    public static function gantiPasswordView(): void {
        $user = auth_user();
        Flight::render('auth/ganti-password', [
            'title' => 'Ganti Password - ' . config('app_name'),
            'user' => $user
        ]);
    }

    /**
     * Process password change.
     */
    public static function gantiPasswordSubmit(): void {
        $user = auth_user();
        $passwordBaru = (string)Flight::request()->data->password_baru;
        $konfirmasiPassword = (string)Flight::request()->data->konfirmasi_password;

        if (strlen($passwordBaru) < 6) {
            flash('danger', 'Password baru minimal harus 6 karakter.');
            Flight::redirect('/ganti-password');
            return;
        }

        if ($passwordBaru !== $konfirmasiPassword) {
            flash('danger', 'Konfirmasi password tidak cocok dengan password baru.');
            Flight::redirect('/ganti-password');
            return;
        }

        $passwordHash = password_hash($passwordBaru, PASSWORD_BCRYPT);
        $db = get_db();
        $stmt = $db->prepare("UPDATE users SET password_hash = :hash, wajib_ganti_password = 0 WHERE id = :id");
        $stmt->execute([
            ':hash' => $passwordHash,
            ':id' => $user['id']
        ]);

        $_SESSION['user']['wajib_ganti_password'] = 0;

        log_security('PASSWORD_CHANGED', 'Password changed successfully', '/ganti-password', [
            'user_id' => $user['id'],
            'nim' => $user['nim']
        ]);

        flash('success', 'Password Anda berhasil diganti.');
        if ($user['role'] === 'dosen') {
            Flight::redirect('/dosen');
        } else {
            Flight::redirect('/dashboard');
        }
    }

    /**
     * Process logout and session destruction.
     */
    public static function logout(): void {
        $user = auth_user();
        if ($user) {
            log_security('LOGOUT', 'User logged out', '/logout', [
                'user_id' => $user['id'],
                'nim' => $user['nim']
            ]);
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        // Start a fresh session to carry the flash message
        session_start();
        flash('info', 'Anda telah berhasil keluar dari sistem.');
        Flight::redirect('/login');
    }
}

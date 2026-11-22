<?php ob_start(); ?>

<div class="row mb-3 align-items-center">
    <div class="col-12 col-md-6">
        <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
            <i class="bi bi-person-gear text-primary"></i>
            <span>Pengaturan Profil Dosen</span>
        </h4>
        <p class="text-muted small mb-0">Ubah nama lengkap dan perbarui password akun dosen Anda.</p>
    </div>
    <div class="col-12 col-md-6 text-md-end mt-2 mt-md-0">
        <a href="/dosen" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="glass-card p-3 p-md-4">
            <div class="d-flex align-items-center gap-3 border-bottom pb-3 mb-3">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-3" style="background: rgba(0, 122, 255, 0.12); color: #007aff; width: 56px; height: 56px;">
                    <i class="bi bi-person-vcard fs-3"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0"><?= e($user['nama']) ?></h5>
                    <div class="small text-muted">
                        NIDN/NIM: <code><?= e($user['nim']) ?></code> &bull; <span class="badge bg-warning text-dark">Dosen Pengampu</span>
                    </div>
                </div>
            </div>

            <form action="/dosen/profil" method="POST" autocomplete="off">
                <?= csrf_field() ?>

                <!-- Identitas Pengguna -->
                <div class="mb-3">
                    <label for="nim" class="form-label fw-semibold small text-uppercase text-muted">NIDN / Username</label>
                    <input type="text" class="form-control" id="nim" value="<?= e($user['nim']) ?>" disabled style="background: rgba(0,0,0,0.04); cursor: not-allowed;">
                    <div class="form-text small text-muted">NIDN digunakan sebagai ID login dan tidak dapat diubah.</div>
                </div>

                <div class="mb-4">
                    <label for="nama" class="form-label fw-semibold small text-uppercase text-muted">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                        <input type="text" class="form-control" id="nama" name="nama" value="<?= e($user['nama']) ?>" required placeholder="Contoh: Dr. Fajar Ramadhan, M.Kom">
                    </div>
                </div>

                <!-- Bagian Ubah Password -->
                <div class="p-3 rounded-3 bg-white bg-opacity-60 border mb-4">
                    <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-lock-fill text-primary"></i>
                        <span>Ubah Password Akun</span>
                    </h6>
                    <p class="text-muted small mb-3">Kosongkan kolom di bawah jika Anda tidak ingin mengubah password.</p>

                    <div class="mb-3">
                        <label for="password_lama" class="form-label fw-semibold small text-uppercase text-muted">Password Saat Ini</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="password_lama" name="password_lama" placeholder="Masukkan password lama untuk konfirmasi">
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-md-6 mb-2 mb-md-0">
                            <label for="password_baru" class="form-label fw-semibold small text-uppercase text-muted">Password Baru</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password_baru" name="password_baru" placeholder="Minimal 6 karakter">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="konfirmasi_password" class="form-label fw-semibold small text-uppercase text-muted">Ulangi Password Baru</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-check2-circle"></i></span>
                                <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" placeholder="Ulangi password baru">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="/dosen" class="btn btn-outline-secondary btn-sm px-3">Batal</a>
                    <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Simpan Perubahan Profil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$body = ob_get_clean(); 
Flight::render('layout', ['body' => $body, 'title' => $title ?? 'Profil Dosen']);
?>

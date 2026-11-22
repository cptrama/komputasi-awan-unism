<?php ob_start(); ?>

<div class="row">
    <div class="col-12">

        <!-- Back Navigation Button -->
        <div class="mb-3">
            <a href="/dashboard" class="btn btn-card-header-action shadow-none">
                <i class="ti ti-arrow-left"></i>
                <span>Kembali ke Dashboard</span>
            </a>
        </div>

        <!-- Main Profile Form Card Container -->
        <div class="card shadow-sm border rounded-3 overflow-hidden mb-4">
            
            <!-- Card Header -->
            <div class="card-header bg-transparent py-3 px-4 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar bg-primary-lt text-primary rounded-circle shadow-sm flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="ti ti-user-circle fs-1"></i>
                    </span>
                    <div>
                        <h3 class="card-title fw-bold text-body mb-0 fs-3">Pengaturan Profil Mahasiswa</h3>
                        <div class="text-secondary small mt-0.5">Kelola foto profil dan perbarui kata sandi akun Anda.</div>
                    </div>
                </div>
            </div>

            <form action="/profil" method="POST" enctype="multipart/form-data" autocomplete="off" id="formProfilMahasiswa">
                <?= csrf_field() ?>

                <div class="card-body p-4">

                    <!-- Profile Overview Card Banner with Photo Upload -->
                    <div class="p-4 mb-4 rounded-3 border bg-body-tertiary">
                        <div class="row align-items-center g-3">
                            <div class="col-auto">
                                <div class="position-relative" id="avatarPreviewContainer">
                                    <?= render_avatar($user, 72, 'border border-2 border-primary-subtle') ?>
                                </div>
                            </div>
                            <div class="col">
                                <h4 class="fw-bold text-body mb-1 fs-3"><?= e($user['nama']) ?></h4>
                                <div class="text-secondary small d-flex align-items-center gap-2 flex-wrap mb-2.5">
                                    <span>NIM: <code class="px-1.5 py-0.5 rounded bg-body border text-primary font-monospace"><?= e($user['nim']) ?></code></span>
                                    <span class="text-secondary">&bull;</span>
                                    <span class="badge bg-primary-lt text-primary border border-primary-subtle fw-bold px-2 py-0.5">Kelas <?= e($user['kelas'] ?? 'S5') ?></span>
                                    <span class="text-secondary">&bull;</span>
                                    <span>Program Studi <?= e(config('prodi', 'Teknologi Informasi')) ?></span>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <label for="foto" class="btn btn-primary btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1.5 mb-0" style="cursor: pointer;">
                                        <i class="ti ti-camera fs-2"></i>
                                        <span>Unggah Foto Profil</span>
                                    </label>
                                    <input type="file" id="foto" name="foto" class="d-none" accept="image/jpeg,image/png,image/webp">

                                    <?php if (!empty($user['foto'])): ?>
                                        <label class="btn btn-outline-danger btn-sm px-3 d-inline-flex align-items-center gap-1.5 mb-0" id="btnHapusFotoLabel" style="cursor: pointer;">
                                            <input type="checkbox" name="hapus_foto" value="1" id="hapus_foto" class="d-none">
                                            <i class="ti ti-trash fs-2"></i>
                                            <span id="textHapusFoto">Hapus Foto</span>
                                        </label>
                                    <?php endif; ?>
                                </div>
                                <div id="selectedFileName" class="text-primary small fw-semibold mt-1.5" style="display: none;"></div>
                                <div class="text-secondary small mt-1" style="font-size: 0.78rem;">
                                    Format: <strong>JPG, PNG, atau WebP</strong> &bull; Maksimal <strong>2 MB</strong>.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Identitas Mahasiswa (Read-Only) -->
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small text-uppercase text-secondary">
                                NIM (Nomor Induk Mahasiswa)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-body-tertiary"><i class="ti ti-id fs-2 text-secondary"></i></span>
                                <input type="text" class="form-control bg-body-tertiary font-monospace" value="<?= e($user['nim']) ?>" readonly disabled style="cursor: not-allowed;">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small text-uppercase text-secondary">
                                Penempatan Kelas
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-body-tertiary"><i class="ti ti-school fs-2 text-secondary"></i></span>
                                <input type="text" class="form-control bg-body-tertiary" value="Kelas <?= e($user['kelas'] ?? 'S5') ?>" readonly disabled style="cursor: not-allowed;">
                            </div>
                        </div>
                    </div>

                    <!-- Bagian Ubah Password Akun -->
                    <div class="card shadow-none border rounded-3 mb-2 overflow-hidden" style="background: var(--tblr-bg-surface-secondary, rgba(0, 0, 0, 0.02));">
                        <div class="card-header bg-transparent py-2.5 px-3 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ti ti-lock-cog text-primary fs-2"></i>
                                <h4 class="card-title fw-bold text-body mb-0 fs-4">Ubah Password Akun</h4>
                            </div>
                        </div>
                        <div class="card-body p-3 p-md-4">
                            <div class="alert alert-info border border-info-subtle small mb-3 rounded-3 d-flex align-items-center gap-2.5 py-2.5 px-3">
                                <i class="ti ti-info-circle text-info fs-2 flex-shrink-0"></i>
                                <div class="lh-sm">
                                    Kosongkan seluruh kolom password di bawah jika Anda hanya ingin memperbarui foto profil.
                                </div>
                            </div>

                            <!-- Password Saat Ini -->
                            <div class="mb-3">
                                <label for="password_lama" class="form-label fw-bold small text-uppercase text-secondary">
                                    Password Saat Ini
                                </label>
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text bg-body-tertiary"><i class="ti ti-key fs-2 text-secondary"></i></span>
                                        <input type="password" class="form-control" id="password_lama" name="password_lama" 
                                               placeholder="Masukkan password saat ini untuk konfirmasi" autocomplete="current-password" style="padding-right: 2.85rem;">
                                    </div>
                                    <button type="button" class="btn btn-link link-secondary p-0 border-0 position-absolute end-0 top-50 translate-middle-y me-3 text-decoration-none shadow-none d-flex align-items-center justify-content-center" id="btn-toggle-old" title="Tampilkan / Sembunyikan Password" tabindex="-1" style="z-index: 5; background: transparent;">
                                        <i class="ti ti-eye fs-2" id="icon-toggle-old"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="password_baru" class="form-label fw-bold small text-uppercase text-secondary">
                                        Password Baru
                                    </label>
                                    <div class="position-relative">
                                        <div class="input-group">
                                            <span class="input-group-text bg-body-tertiary"><i class="ti ti-lock fs-2 text-secondary"></i></span>
                                            <input type="password" class="form-control" id="password_baru" name="password_baru" 
                                                   placeholder="Minimal 6 karakter" autocomplete="new-password" minlength="6" style="padding-right: 2.85rem;">
                                        </div>
                                        <button type="button" class="btn btn-link link-secondary p-0 border-0 position-absolute end-0 top-50 translate-middle-y me-3 text-decoration-none shadow-none d-flex align-items-center justify-content-center" id="btn-toggle-new" title="Tampilkan / Sembunyikan Password" tabindex="-1" style="z-index: 5; background: transparent;">
                                            <i class="ti ti-eye fs-2" id="icon-toggle-new"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="konfirmasi_password" class="form-label fw-bold small text-uppercase text-secondary">
                                        Ulangi Password Baru
                                    </label>
                                    <div class="position-relative">
                                        <div class="input-group">
                                            <span class="input-group-text bg-body-tertiary"><i class="ti ti-shield-check fs-2 text-secondary"></i></span>
                                            <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" 
                                                   placeholder="Ulangi password baru" autocomplete="new-password" minlength="6" style="padding-right: 2.85rem;">
                                        </div>
                                        <button type="button" class="btn btn-link link-secondary p-0 border-0 position-absolute end-0 top-50 translate-middle-y me-3 text-decoration-none shadow-none d-flex align-items-center justify-content-center" id="btn-toggle-confirm" title="Tampilkan / Sembunyikan Password" tabindex="-1" style="z-index: 5; background: transparent;">
                                            <i class="ti ti-eye fs-2" id="icon-toggle-confirm"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Card Footer Actions -->
                <div class="card-footer bg-transparent border-top py-3 px-4 d-flex justify-content-end align-items-center gap-2">
                    <a href="/dashboard" class="btn btn-outline-secondary px-4 py-2 fw-semibold rounded-2 d-inline-flex align-items-center gap-1.5 shadow-none">
                        <i class="ti ti-x"></i>
                        <span>Batal</span>
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm rounded-2 text-white d-inline-flex align-items-center gap-1.5">
                        <i class="ti ti-device-floppy fs-2"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Live photo preview
    const fotoInput = document.getElementById('foto');
    const previewContainer = document.getElementById('avatarPreviewContainer');
    const fileNameDisplay = document.getElementById('selectedFileName');
    if (fotoInput && previewContainer) {
        fotoInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                if (file.size > 2 * 1024 * 1024) {
                    alert('Ukuran file foto maksimal 2 MB.');
                    this.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewContainer.innerHTML = '<span class="avatar rounded-circle shadow-sm border border-2 border-primary-subtle flex-shrink-0" style="width: 72px; height: 72px; background-image: url(\'' + e.target.result + '\'); background-size: cover; background-position: center;"></span>';
                };
                reader.readAsDataURL(file);
                if (fileNameDisplay) {
                    fileNameDisplay.textContent = 'Foto terpilih: ' + file.name;
                    fileNameDisplay.style.display = 'block';
                }
            }
        });
    }

    // Toggle delete photo
    const hapusCheckbox = document.getElementById('hapus_foto');
    const textHapus = document.getElementById('textHapusFoto');
    const labelHapus = document.getElementById('btnHapusFotoLabel');
    if (hapusCheckbox && textHapus && labelHapus) {
        hapusCheckbox.addEventListener('change', function() {
            if (this.checked) {
                labelHapus.classList.replace('btn-outline-danger', 'btn-danger');
                textHapus.textContent = 'Batal Hapus Foto';
            } else {
                labelHapus.classList.replace('btn-danger', 'btn-outline-danger');
                textHapus.textContent = 'Hapus Foto';
            }
        });
    }

    // Password visibility toggle helper
    function setupToggle(btnId, iconId, inputId) {
        const btn   = document.getElementById(btnId);
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
        if (!btn || !input || !icon) return;

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const isPassword = (input.type === 'password');
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('ti-eye', !isPassword);
            icon.classList.toggle('ti-eye-off', isPassword);
        });
    }

    setupToggle('btn-toggle-old',     'icon-toggle-old',     'password_lama');
    setupToggle('btn-toggle-new',     'icon-toggle-new',     'password_baru');
    setupToggle('btn-toggle-confirm', 'icon-toggle-confirm', 'konfirmasi_password');

    // Client-side confirmation check
    const form = document.getElementById('formProfilMahasiswa');
    if (form) {
        form.addEventListener('submit', function (e) {
            const passBaru = document.getElementById('password_baru').value;
            const passKonf = document.getElementById('konfirmasi_password').value;
            const passLama = document.getElementById('password_lama').value;

            if (passBaru.length > 0 || passKonf.length > 0) {
                if (!passLama) {
                    e.preventDefault();
                    alert('Silakan masukkan password saat ini untuk mengonfirmasi penggantian password.');
                    document.getElementById('password_lama').focus();
                    return;
                }
                if (passBaru.length < 6) {
                    e.preventDefault();
                    alert('Password baru minimal harus 6 karakter.');
                    document.getElementById('password_baru').focus();
                    return;
                }
                if (passBaru !== passKonf) {
                    e.preventDefault();
                    alert('Konfirmasi password baru tidak cocok. Silakan periksa kembali.');
                    document.getElementById('konfirmasi_password').focus();
                    return;
                }
            }
        });
    }
});
</script>

<?php 
$body = ob_get_clean(); 
Flight::render('layout', ['body' => $body, 'title' => $title ?? 'Pengaturan Profil Mahasiswa']);
?>

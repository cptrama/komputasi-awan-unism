<?php ob_start(); ?>

<?php $allowedKelas = config('kelas_list', ['S5']); ?>

<!-- Page Title -->
<div class="row mb-3 align-items-center">
    <div class="col">
        <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
            <i class="bi bi-<?= $isEdit ? 'pencil-square' : 'person-plus-fill' ?> text-primary"></i>
            <span><?= $isEdit ? 'Edit Data Mahasiswa' : 'Tambah Mahasiswa Baru' ?></span>
        </h4>
        <p class="text-muted small mb-0"><?= $isEdit ? 'Koreksi NIM, nama, kelas, atau reset password' : 'Daftarkan mahasiswa baru ke sistem' ?></p>
    </div>
    <div class="col-auto">
        <a href="/dosen/mahasiswa" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-7">

        <!-- Main Data Form -->
        <div class="glass-card mb-3">
            <div class="card-header py-2 px-3 px-md-4">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-person-vcard text-primary"></i>
                    <?= $isEdit ? 'Data Identitas' : 'Informasi Mahasiswa' ?>
                </h6>
            </div>
            <div class="card-body p-3 p-md-4">
                <form action="<?= $isEdit ? '/dosen/mahasiswa/' . $mahasiswa['id'] : '/dosen/mahasiswa' ?>" method="POST" autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="nim" class="form-label fw-semibold small">NIM <span class="text-danger">*</span></label>
                        <input type="text" id="nim" name="nim" class="form-control"
                               value="<?= $isEdit ? e($mahasiswa['nim']) : '' ?>"
                               placeholder="Contoh: 11203362510135" required maxlength="30"
                               pattern="[0-9]+" title="NIM hanya boleh berisi angka">
                        <div class="form-text">Nomor Induk Mahasiswa (angka saja).</div>
                    </div>

                    <div class="mb-3">
                        <label for="nama" class="form-label fw-semibold small">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" id="nama" name="nama" class="form-control"
                               value="<?= $isEdit ? e($mahasiswa['nama']) : '' ?>"
                               placeholder="Nama lengkap sesuai KTM" required maxlength="100">
                    </div>

                    <div class="mb-3">
                        <label for="kelas" class="form-label fw-semibold small">Kelas <span class="text-danger">*</span></label>
                        <select id="kelas" name="kelas" class="form-select" required>
                            <option value="">— Pilih Kelas —</option>
                            <?php foreach ($allowedKelas as $k): ?>
                                <option value="<?= e($k) ?>" <?= ($isEdit && $mahasiswa['kelas'] === $k) ? 'selected' : '' ?>>
                                    <?= e($k) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (!$isEdit): ?>
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold small">Password Awal <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" id="password" name="password" class="form-control"
                                       placeholder="Min. 6 karakter" required minlength="6" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary" id="btn-toggle-pass" tabindex="-1">
                                    <i class="bi bi-eye" id="icon-toggle-pass"></i>
                                </button>
                            </div>
                            <div class="form-text">Mahasiswa wajib ganti password saat login pertama.</div>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="/dosen/mahasiswa" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-<?= $isEdit ? 'floppy' : 'person-plus' ?> me-1"></i>
                            <?= $isEdit ? 'Simpan Perubahan' : 'Tambah Mahasiswa' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($isEdit): ?>
        <!-- Reset Password Section -->
        <div class="glass-card border border-warning-subtle">
            <div class="card-header py-2 px-3 px-md-4 bg-warning bg-opacity-10">
                <h6 class="mb-0 fw-bold text-warning-emphasis d-flex align-items-center gap-2">
                    <i class="bi bi-key-fill"></i> Reset Password Mahasiswa
                </h6>
            </div>
            <div class="card-body p-3 p-md-4">
                <p class="small text-muted mb-3">
                    Set password baru untuk <strong><?= e($mahasiswa['nama']) ?></strong>. 
                    Mahasiswa akan diwajibkan ganti password saat login berikutnya.
                </p>
                <form action="/dosen/mahasiswa/<?= $mahasiswa['id'] ?>/reset-password" method="POST" autocomplete="off">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="password_baru" class="form-label fw-semibold small">Password Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="password_baru" name="password_baru" class="form-control"
                                   placeholder="Min. 6 karakter" required minlength="6" autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary" id="btn-toggle-reset" tabindex="-1">
                                <i class="bi bi-eye" id="icon-toggle-reset"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Reset password mahasiswa <?= e(addslashes($mahasiswa['nama'])) ?>?')">
                            <i class="bi bi-arrow-repeat me-1"></i> Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function togglePass(btnId, iconId, inputId) {
        const btn = document.getElementById(btnId);
        if (!btn) return;
        btn.addEventListener('click', function () {
            const input = document.getElementById(inputId);
            const icon  = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        });
    }
    togglePass('btn-toggle-pass',  'icon-toggle-pass',  'password');
    togglePass('btn-toggle-reset', 'icon-toggle-reset', 'password_baru');
});
</script>

<?php
$body = ob_get_clean();
Flight::render('layout', ['body' => $body, 'title' => $title ?? 'Mahasiswa Form']);
?>

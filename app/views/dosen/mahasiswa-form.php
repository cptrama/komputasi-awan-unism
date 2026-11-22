<?php ob_start(); ?>

<?php 
$allowedKelas = config('kelas_list', ['S5']); 
$currentKelas = $isEdit ? ($mahasiswa['kelas'] ?? '') : (count($allowedKelas) === 1 ? $allowedKelas[0] : '');
?>

<style>
.kelas-dropdown-item {
    transition: background-color 0.15s ease, color 0.15s ease;
}
.kelas-dropdown-item:hover {
    background-color: rgba(0, 0, 0, 0.04) !important;
}
.kelas-dropdown-item.is-selected {
    background-color: rgba(32, 107, 196, 0.08) !important;
}
.kelas-dropdown-item.is-selected .item-title {
    color: var(--tblr-primary, #206bc4) !important;
}
[data-bs-theme="dark"] .kelas-dropdown-item:hover,
[data-theme="dark"] .kelas-dropdown-item:hover {
    background-color: rgba(255, 255, 255, 0.06) !important;
}
[data-bs-theme="dark"] .kelas-dropdown-item.is-selected,
[data-theme="dark"] .kelas-dropdown-item.is-selected {
    background-color: rgba(32, 107, 196, 0.2) !important;
}
[data-bs-theme="dark"] .kelas-dropdown-item.is-selected .item-title,
[data-theme="dark"] .kelas-dropdown-item.is-selected .item-title {
    color: #60a5fa !important;
}
</style>

<div class="row">
    <div class="col-12">
        
        <!-- Back Navigation Button -->
        <div class="mb-3">
            <a href="/dosen/mahasiswa" class="btn btn-card-header-action shadow-none">
                <i class="ti ti-arrow-left"></i>
                <span>Kembali ke Kelola Mahasiswa</span>
            </a>
        </div>

        <!-- Main Form Card Container -->
        <div class="card shadow-sm border rounded-3 overflow-hidden mb-4">
            <div class="card-header bg-transparent py-3 px-4 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar bg-primary-lt text-primary rounded-circle shadow-sm flex-shrink-0" style="width: 42px; height: 42px;">
                        <i class="ti <?= $isEdit ? 'ti-user-edit' : 'ti-user-plus' ?> fs-1"></i>
                    </span>
                    <div>
                        <h3 class="card-title fw-bold text-body mb-0 fs-3">
                            <?= $isEdit ? 'Edit Data Mahasiswa: ' . e($mahasiswa['nama']) : 'Tambah Mahasiswa Baru' ?>
                        </h3>
                        <div class="text-secondary small mt-0.5">
                            <?= $isEdit ? 'Perbarui nomor induk, nama lengkap, penempatan kelas, atau atur ulang password akun.' : 'Lengkapi data identitas di bawah ini untuk mendaftarkan akun mahasiswa baru.' ?>
                        </div>
                    </div>
                </div>
            </div>

            <form action="<?= $isEdit ? '/dosen/mahasiswa/' . $mahasiswa['id'] : '/dosen/mahasiswa' ?>" method="POST" autocomplete="off" id="formMahasiswa">
                <?= csrf_field() ?>

                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="nim" class="form-label fw-bold small text-uppercase text-secondary">
                            NIM (Nomor Induk Mahasiswa) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-body-tertiary"><i class="ti ti-id fs-2 text-primary"></i></span>
                            <input type="text" id="nim" name="nim" class="form-control font-monospace"
                                   value="<?= $isEdit ? e($mahasiswa['nim']) : '' ?>"
                                   placeholder="Contoh: 11203362510135" required maxlength="30"
                                   pattern="[0-9]+" title="NIM hanya boleh berisi angka">
                        </div>
                        <div class="form-text small text-secondary mt-1">Nomor Induk Mahasiswa resmi (hanya angka tanpa spasi).</div>
                    </div>

                    <div class="mb-3">
                        <label for="nama" class="form-label fw-bold small text-uppercase text-secondary">
                            Nama Lengkap Mahasiswa <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-body-tertiary"><i class="ti ti-user fs-2 text-primary"></i></span>
                            <input type="text" id="nama" name="nama" class="form-control"
                                   value="<?= $isEdit ? e($mahasiswa['nama']) : '' ?>"
                                   placeholder="Nama lengkap mahasiswa sesuai KTM / Siakad" required maxlength="100">
                        </div>
                    </div>

                    <!-- Modern Custom Dropdown: Penempatan Kelas -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-secondary">
                            Penempatan Kelas <span class="text-danger">*</span>
                        </label>

                        <!-- Hidden Input for Form Submission -->
                        <input type="hidden" id="kelas" name="kelas" value="<?= e($currentKelas) ?>">

                        <!-- Input Group with Clean Dropdown -->
                        <div class="input-group dropdown" id="kelasDropdownWrapper">
                            <span class="input-group-text bg-body-tertiary">
                                <i class="ti ti-school fs-2 text-primary"></i>
                            </span>
                            <button type="button" 
                                    class="form-control form-select text-start d-flex align-items-center justify-content-between shadow-none bg-body" 
                                    id="kelasDropdownBtn" 
                                    data-bs-toggle="dropdown" 
                                    data-bs-auto-close="true"
                                    aria-expanded="false"
                                    style="cursor: pointer; min-height: 42px;">
                                <span id="kelasDropdownSelected" class="<?= !empty($currentKelas) ? 'text-body fw-medium' : 'text-secondary' ?>">
                                    <?= !empty($currentKelas) ? 'Kelas ' . e($currentKelas) : '-- Pilih Kelas Perkuliahan --' ?>
                                </span>
                            </button>

                            <ul class="dropdown-menu w-100 shadow border p-1 mt-1 rounded-3" id="kelasDropdownList" aria-labelledby="kelasDropdownBtn">
                                <?php foreach ($allowedKelas as $k): 
                                    $isSelected = ($currentKelas === $k);
                                ?>
                                    <li>
                                        <button type="button" 
                                                class="dropdown-item kelas-dropdown-item rounded-2 py-2 px-3 d-flex align-items-center justify-content-between <?= $isSelected ? 'is-selected' : '' ?>"
                                                data-kelas="<?= e($k) ?>"
                                                onclick="selectKelas('<?= e(addslashes($k)) ?>')">
                                            <span class="d-flex align-items-center gap-2">
                                                <i class="ti ti-school fs-2 text-primary"></i>
                                                <span class="fw-semibold item-title">Kelas <?= e($k) ?></span>
                                            </span>
                                            <span class="item-check-box">
                                                <?php if ($isSelected): ?>
                                                    <i class="ti ti-check text-primary fs-2 item-check"></i>
                                                <?php endif; ?>
                                            </span>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <?php if (!$isEdit): ?>
                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold small text-uppercase text-secondary">
                                Password Awal Akun <span class="text-danger">*</span>
                            </label>
                            <div class="position-relative">
                                <div class="input-group">
                                    <span class="input-group-text bg-body-tertiary"><i class="ti ti-lock fs-2 text-primary"></i></span>
                                    <input type="password" id="password" name="password" class="form-control"
                                           placeholder="Minimal 6 karakter" required minlength="6" autocomplete="new-password" style="padding-right: 2.85rem;">
                                </div>
                                <button type="button" class="btn btn-link link-secondary p-0 border-0 position-absolute end-0 top-50 translate-middle-y me-3 text-decoration-none shadow-none d-flex align-items-center justify-content-center" id="btn-toggle-pass" title="Tampilkan / Sembunyikan Password" tabindex="-1" style="z-index: 5; background: transparent;">
                                    <i class="ti ti-eye fs-2" id="icon-toggle-pass"></i>
                                </button>
                            </div>
                            <div class="form-text small text-secondary mt-1">Mahasiswa diwajibkan mengganti password ini saat pertama kali berhasil login.</div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-footer bg-transparent border-top py-3 px-4 d-flex justify-content-end align-items-center gap-2">
                    <a href="/dosen/mahasiswa" class="btn btn-outline-secondary text-body px-4 py-2 fw-semibold rounded-2 d-inline-flex align-items-center gap-1.5 shadow-none">
                        <i class="ti ti-x"></i>
                        <span>Batal</span>
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm rounded-2 text-white d-inline-flex align-items-center gap-1.5">
                        <i class="ti <?= $isEdit ? 'ti-device-floppy' : 'ti-user-plus' ?> fs-2"></i>
                        <span><?= $isEdit ? 'Simpan Perubahan' : 'Tambah Mahasiswa' ?></span>
                    </button>
                </div>
            </form>
        </div>

        <?php if ($isEdit): ?>
            <!-- Reset Password Section Card -->
            <div class="card shadow-sm border border-warning-subtle rounded-3 overflow-hidden">
                <div class="card-header bg-warning-lt py-3 px-4 border-bottom">
                    <div class="d-flex align-items-center gap-2.5">
                        <span class="avatar bg-warning text-white rounded-circle shadow-sm flex-shrink-0" style="width: 38px; height: 38px;">
                            <i class="ti ti-key fs-1"></i>
                        </span>
                        <div>
                            <h4 class="mb-0 fw-bold text-body fs-3">Reset Password Mahasiswa</h4>
                            <div class="text-secondary small mt-0.5">Atur ulang password login akun mahasiswa secara langsung.</div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-warning border border-warning-subtle small mb-3 rounded-3 d-flex align-items-start gap-2.5 p-3">
                        <i class="ti ti-info-circle text-warning fs-1 flex-shrink-0 mt-0.5"></i>
                        <div>
                            Menetapkan password baru untuk <strong><?= e($mahasiswa['nama']) ?></strong> akan mengaktifkan kembali status wajib ganti password pada saat login berikutnya.
                        </div>
                    </div>
                    <form action="/dosen/mahasiswa/<?= $mahasiswa['id'] ?>/reset-password" method="POST" autocomplete="off">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="password_baru" class="form-label fw-bold small text-uppercase text-secondary">
                                Password Baru <span class="text-danger">*</span>
                            </label>
                            <div class="position-relative">
                                <div class="input-group">
                                    <span class="input-group-text bg-body-tertiary"><i class="ti ti-lock fs-2 text-warning"></i></span>
                                    <input type="password" id="password_baru" name="password_baru" class="form-control"
                                           placeholder="Minimal 6 karakter" required minlength="6" autocomplete="new-password" style="padding-right: 2.85rem;">
                                </div>
                                <button type="button" class="btn btn-link link-secondary p-0 border-0 position-absolute end-0 top-50 translate-middle-y me-3 text-decoration-none shadow-none d-flex align-items-center justify-content-center" id="btn-toggle-reset" title="Tampilkan / Sembunyikan Password" tabindex="-1" style="z-index: 5; background: transparent;">
                                    <i class="ti ti-eye fs-2" id="icon-toggle-reset"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-warning px-4 py-2 fw-semibold shadow-sm rounded-2 text-white d-inline-flex align-items-center gap-1.5" style="background-color: #f76707 !important; border-color: #f76707 !important;" onclick="return confirm('Reset password mahasiswa <?= e(addslashes($mahasiswa['nama'])) ?>?')">
                                <i class="ti ti-rotate-clockwise fs-2 text-white"></i>
                                <span class="text-white">Reset Password</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
function selectKelas(kelasName) {
    const hiddenInput = document.getElementById('kelas');
    if (hiddenInput) hiddenInput.value = kelasName;

    const selectedContainer = document.getElementById('kelasDropdownSelected');
    if (selectedContainer) {
        selectedContainer.className = 'text-body fw-medium';
        selectedContainer.textContent = 'Kelas ' + kelasName;
    }

    const items = document.querySelectorAll('#kelasDropdownList .kelas-dropdown-item');
    items.forEach(item => {
        const isCurrent = (item.getAttribute('data-kelas') === kelasName);
        item.classList.toggle('is-selected', isCurrent);

        const checkBox = item.querySelector('.item-check-box');
        if (checkBox) {
            checkBox.innerHTML = isCurrent ? '<i class="ti ti-check text-primary fs-2 item-check"></i>' : '';
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    // Password visibility toggle helper
    function togglePass(btnId, iconId, inputId) {
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

    togglePass('btn-toggle-pass',  'icon-toggle-pass',  'password');
    togglePass('btn-toggle-reset', 'icon-toggle-reset', 'password_baru');

    // Validation check before form submit
    const mainForm = document.getElementById('formMahasiswa');
    if (mainForm) {
        mainForm.addEventListener('submit', function (e) {
            const kelasInput = document.getElementById('kelas');
            if (kelasInput && !kelasInput.value.trim()) {
                e.preventDefault();
                alert('Silakan pilih penempatan kelas perkuliahan terlebih dahulu.');
                const dropdownBtn = document.getElementById('kelasDropdownBtn');
                if (dropdownBtn) dropdownBtn.focus();
            }
        });
    }
});
</script>

<?php
$body = ob_get_clean();
Flight::render('layout', ['body' => $body, 'title' => $title ?? ($isEdit ? 'Edit Mahasiswa' : 'Tambah Mahasiswa')]);
?>

<?php ob_start(); ?>

<div class="row">
    <div class="col-12">
        
        <!-- Back Navigation Button -->
        <div class="mb-3">
            <a href="/dosen/tugas" class="btn btn-card-header-action shadow-none">
                <i class="ti ti-arrow-left"></i>
                <span>Kembali ke Kelola Tugas</span>
            </a>
        </div>

        <!-- Form Card Container -->
        <div class="card shadow-sm border rounded-3 overflow-hidden">
            <div class="card-header bg-transparent py-3 px-4 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar bg-primary-lt text-primary rounded-circle shadow-sm flex-shrink-0" style="width: 42px; height: 42px;">
                        <i class="ti <?= $isEdit ? 'ti-edit' : 'ti-clipboard-plus' ?> fs-1"></i>
                    </span>
                    <div>
                        <h3 class="card-title fw-bold text-dark mb-0 fs-3">
                            <?= $isEdit ? 'Edit Tugas: ' . e($tugas['judul']) : 'Buat Tugas Baru' ?>
                        </h3>
                        <div class="text-secondary small mt-0.5">
                            <?= $isEdit ? 'Perbarui instruksi, jadwal buka, dan tenggat waktu pengumpulan tugas.' : 'Lengkapi formulir di bawah ini untuk membuat tugas baru bagi mahasiswa.' ?>
                        </div>
                    </div>
                </div>
            </div>

            <form action="<?= $isEdit ? '/dosen/tugas/' . e($tugas['id']) : '/dosen/tugas' ?>" method="POST">
                <?= csrf_field() ?>

                <div class="card-body p-4">
                    <?php 
                        $currentPId = $isEdit ? $tugas['pertemuan_id'] : ($selectedPertemuanId ?? '');
                        $selectedMeeting = null;
                        if (!empty($currentPId)) {
                            foreach ($pertemuanList as $p) {
                                if ((string)$p['id'] === (string)$currentPId) {
                                    $selectedMeeting = $p;
                                    break;
                                }
                            }
                        }
                    ?>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-secondary">
                            Terhubung ke Pertemuan <span class="text-danger">*</span>
                        </label>
                        
                        <!-- Hidden input for standard form submission -->
                        <input type="hidden" id="pertemuan_id" name="pertemuan_id" value="<?= e($currentPId) ?>" required>

                        <!-- Custom Modern Dropdown Component -->
                        <div class="dropdown w-100" id="pertemuanDropdownWrapper">
                            <button type="button" 
                                    class="form-control form-select text-start d-flex align-items-center justify-content-between py-2 px-3 shadow-none bg-body" 
                                    id="pertemuanDropdownBtn" 
                                    data-bs-toggle="dropdown" 
                                    data-bs-auto-close="true"
                                    aria-expanded="false"
                                    style="cursor: pointer; min-height: 44px;">
                                <div class="d-flex align-items-center gap-2 text-truncate me-2" id="pertemuanDropdownSelected">
                                    <i class="ti ti-calendar-event fs-2 text-primary flex-shrink-0"></i>
                                    <?php if ($selectedMeeting): ?>
                                        <span class="badge bg-primary text-white fw-bold px-2 py-0.5 flex-shrink-0">P<?= e($selectedMeeting['nomor']) ?></span>
                                        <span class="fw-semibold text-body text-truncate">
                                            Pertemuan <?= e($selectedMeeting['nomor']) ?>: <?= e($selectedMeeting['topik']) ?>
                                        </span>
                                        <span class="badge bg-body-tertiary text-secondary border flex-shrink-0 ms-1">Kelas <?= e($selectedMeeting['kelas']) ?></span>
                                    <?php else: ?>
                                        <span class="text-secondary">-- Pilih Pertemuan Perkuliahan --</span>
                                    <?php endif; ?>
                                </div>
                            </button>

                            <ul class="dropdown-menu w-100 shadow-lg border p-2 mt-1 rounded-3" id="pertemuanDropdownList" aria-labelledby="pertemuanDropdownBtn" style="max-height: 360px; overflow-y: auto;">
                                <?php foreach ($pertemuanList as $p): 
                                    $isSelected = ((string)$currentPId === (string)$p['id']);
                                ?>
                                    <li class="mb-1">
                                        <button type="button" 
                                                class="dropdown-item pertemuan-dropdown-item rounded-2 p-2.5 d-flex align-items-center justify-content-between <?= $isSelected ? 'is-selected' : '' ?>"
                                                data-id="<?= (int)$p['id'] ?>"
                                                onclick="selectPertemuan(<?= (int)$p['id'] ?>, <?= (int)$p['nomor'] ?>, '<?= e(addslashes($p['topik'])) ?>', '<?= e(addslashes($p['kelas'])) ?>')">
                                            <div class="d-flex align-items-center gap-2.5 text-truncate me-2">
                                                <span class="avatar avatar-sm item-avatar <?= $isSelected ? 'bg-primary text-white' : 'bg-primary-lt text-primary' ?> rounded-2 fw-bold flex-shrink-0" style="width: 36px; height: 36px; font-size: 0.82rem;">
                                                    P<?= e($p['nomor']) ?>
                                                </span>
                                                <div class="text-truncate text-start">
                                                    <div class="item-title fw-bold text-dark text-truncate" style="font-size: 0.88rem;">
                                                        Pertemuan <?= e($p['nomor']) ?>: <?= e($p['topik']) ?>
                                                    </div>
                                                    <div class="item-subtitle text-secondary small" style="font-size: 0.76rem;">
                                                        Tanggal: <?= format_date(strtotime($p['tanggal'])) ?> &bull; <?= e($p['mata_kuliah'] ?? config('mata_kuliah', 'Komputasi Awan')) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-2 item-status">
                                                <span class="badge item-badge <?= $isSelected ? 'bg-primary text-white' : 'bg-secondary-lt text-secondary' ?> fw-semibold">
                                                    Kelas <?= e($p['kelas']) ?>
                                                </span>
                                                <?php if ($isSelected): ?>
                                                    <i class="ti ti-check text-primary fs-2 item-check"></i>
                                                <?php endif; ?>
                                            </div>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="judul" class="form-label fw-bold small text-uppercase text-secondary">
                            Judul Tugas <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-body-tertiary"><i class="ti ti-heading fs-2 text-secondary"></i></span>
                            <input type="text" class="form-control" id="judul" name="judul" required value="<?= e($tugas['judul'] ?? '') ?>" placeholder="Contoh: Tugas 1: Penjelasan dan Kerentanan Pada OSI Layer">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label fw-bold small text-uppercase text-secondary">
                            Deskripsi &amp; Instruksi Tugas <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" required placeholder="Tuliskan petunjuk pengerjaan, format laporan, kriteria penilaian, dan instruksi pengumpulan..."><?= e($tugas['deskripsi'] ?? '') ?></textarea>
                    </div>

                    <?php
                        $defaultDibuka = $isEdit ? date('Y-m-d\TH:i', (int)$tugas['dibuka_pada']) : date('Y-m-d\TH:i');
                        $defaultDitutup = $isEdit ? date('Y-m-d\TH:i', (int)$tugas['ditutup_pada']) : date('Y-m-d\TH:i', strtotime('+7 days'));
                    ?>

                    <div class="mb-3">
                        <label for="dibuka_pada" class="form-label fw-bold small text-uppercase text-secondary">
                            Waktu Dibuka <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-body-tertiary"><i class="ti ti-calendar-time fs-2 text-secondary"></i></span>
                            <input type="datetime-local" class="form-control" id="dibuka_pada" name="dibuka_pada" required value="<?= e($defaultDibuka) ?>">
                        </div>
                        <div class="form-text small text-secondary mt-1.5 d-flex align-items-center gap-1">
                            <i class="ti ti-info-circle text-primary flex-shrink-0"></i>
                            <span>Zona waktu server: <strong>WITA (Asia/Makassar)</strong>.</span>
                        </div>
                    </div>

                    <div class="mb-3 p-3 p-md-4 rounded-3 bg-body-tertiary border">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="ti ti-clock-pause text-danger fs-2"></i>
                            <label class="form-label fw-bold text-dark mb-0 fs-4">Pengaturan Waktu Penutupan (Deadline) <span class="text-danger">*</span></label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ditutup_mode" id="mode_datetime" value="datetime" checked onchange="toggleDeadlineMode()">
                            <label class="form-check-label fw-semibold text-dark" for="mode_datetime">
                                Tentukan Tanggal &amp; Jam Tertentu
                            </label>
                        </div>
                        <div id="wrapper_ditutup_datetime" class="ms-4 mb-3">
                            <div class="input-group" style="max-width: 320px;">
                                <span class="input-group-text bg-card"><i class="ti ti-calendar text-danger"></i></span>
                                <input type="datetime-local" class="form-control" id="ditutup_pada" name="ditutup_pada" value="<?= e($defaultDitutup) ?>">
                            </div>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ditutup_mode" id="mode_durasi" value="durasi" onchange="toggleDeadlineMode()">
                            <label class="form-check-label fw-semibold text-dark" for="mode_durasi">
                                Tentukan Berdasarkan Durasi Hari dari Waktu Buka
                            </label>
                        </div>
                        <div id="wrapper_ditutup_durasi" class="ms-4" style="display: none;">
                            <div class="input-group" style="max-width: 250px;">
                                <input type="number" class="form-control" id="durasi_hari" name="durasi_hari" value="7" min="1" max="180">
                                <span class="input-group-text bg-card fw-semibold">Hari</span>
                            </div>
                            <div class="form-text small text-secondary mt-1">Contoh: 7 hari setelah waktu buka.</div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-transparent py-3 px-4 d-flex justify-content-end align-items-center gap-2 border-top">
                    <a href="/dosen/tugas" class="btn btn-outline-secondary px-3 py-2 fw-semibold rounded-2 d-inline-flex align-items-center gap-1.5">
                        <i class="ti ti-x"></i>
                        <span>Batal</span>
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm rounded-2 d-inline-flex align-items-center gap-1.5 text-white">
                        <i class="ti <?= $isEdit ? 'ti-device-floppy' : 'ti-plus' ?> fs-2"></i>
                        <span><?= $isEdit ? 'Simpan Perubahan' : 'Buat Tugas' ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleDeadlineMode() {
    const isDatetime = document.getElementById('mode_datetime').checked;
    document.getElementById('wrapper_ditutup_datetime').style.display = isDatetime ? 'block' : 'none';
    document.getElementById('wrapper_ditutup_durasi').style.display = isDatetime ? 'none' : 'block';
}

function selectPertemuan(id, nomor, topik, kelas) {
    document.getElementById('pertemuan_id').value = id;
    
    const selectedContainer = document.getElementById('pertemuanDropdownSelected');
    if (selectedContainer) {
        selectedContainer.innerHTML = 
            '<i class="ti ti-calendar-event fs-2 text-primary flex-shrink-0"></i>' +
            '<span class="badge bg-primary text-white fw-bold px-2 py-0.5 flex-shrink-0">P' + nomor + '</span>' +
            '<span class="fw-semibold text-dark text-truncate">Pertemuan ' + nomor + ': ' + topik + '</span>' +
            '<span class="badge bg-body-tertiary text-secondary border flex-shrink-0 ms-1">Kelas ' + kelas + '</span>';
    }

    // Reset all items styling
    const listItems = document.querySelectorAll('#pertemuanDropdownList .pertemuan-dropdown-item');
    listItems.forEach(item => {
        item.classList.remove('is-selected');
        const avatar = item.querySelector('.item-avatar');
        if (avatar) avatar.className = 'avatar avatar-sm item-avatar bg-primary-lt text-primary rounded-2 fw-bold flex-shrink-0';
        const badge = item.querySelector('.item-badge');
        if (badge) badge.className = 'badge item-badge bg-secondary-lt text-secondary fw-semibold';
        const check = item.querySelector('.item-check');
        if (check) check.remove();
    });

    // Mark current item
    const currentBtn = document.querySelector('#pertemuanDropdownList .pertemuan-dropdown-item[data-id="' + id + '"]');
    if (currentBtn) {
        currentBtn.classList.add('is-selected');
        const avatar = currentBtn.querySelector('.item-avatar');
        if (avatar) avatar.className = 'avatar avatar-sm item-avatar bg-primary text-white rounded-2 fw-bold flex-shrink-0';
        const badge = currentBtn.querySelector('.item-badge');
        if (badge) badge.className = 'badge item-badge bg-primary text-white fw-semibold';
        const statusBox = currentBtn.querySelector('.item-status');
        if (statusBox && !statusBox.querySelector('.item-check')) {
            const checkIcon = document.createElement('i');
            checkIcon.className = 'ti ti-check text-primary fs-2 item-check';
            statusBox.appendChild(checkIcon);
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const formEl = document.querySelector('form');
    if (formEl) {
        formEl.addEventListener('submit', function(e) {
            const pId = document.getElementById('pertemuan_id').value;
            if (!pId) {
                e.preventDefault();
                alert('Silakan pilih pertemuan perkuliahan terlebih dahulu.');
                const btn = document.getElementById('pertemuanDropdownBtn');
                if (btn) btn.focus();
            }
        });
    }
});
</script>

<?php 
$body = ob_get_clean(); 
Flight::render('layout', ['body' => $body, 'title' => $title ?? 'Form Tugas']);
?>

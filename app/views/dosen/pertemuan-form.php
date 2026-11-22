<?php ob_start(); ?>

<div class="row">
    <div class="col-12">
        
        <!-- Back Navigation Button -->
        <div class="mb-3">
            <a href="/dosen/pertemuan" class="btn btn-card-header-action shadow-none">
                <i class="ti ti-arrow-left"></i>
                <span>Kembali ke Daftar Pertemuan</span>
            </a>
        </div>

        <!-- Form Card Container -->
        <div class="card shadow-sm border rounded-3 overflow-hidden">
            <div class="card-header bg-transparent py-3 px-4 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar bg-primary-lt text-primary rounded-circle shadow-sm flex-shrink-0" style="width: 42px; height: 42px;">
                        <i class="ti <?= $isEdit ? 'ti-edit' : 'ti-calendar-plus' ?> fs-1"></i>
                    </span>
                    <div>
                        <h3 class="card-title fw-bold text-dark mb-0 fs-3">
                            <?= $isEdit ? 'Edit Pertemuan ' . e($pertemuan['nomor']) : 'Tambah Pertemuan Baru' ?>
                        </h3>
                        <div class="text-secondary small mt-0.5">
                            <?= $isEdit ? 'Perbarui informasi sesi dan lampiran materi perkuliahan.' : 'Lengkapi formulir di bawah ini untuk membuat sesi perkuliahan baru.' ?>
                        </div>
                    </div>
                </div>
            </div>

            <form action="<?= $isEdit ? '/dosen/pertemuan/' . e($pertemuan['id']) : '/dosen/pertemuan' ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="kelas" value="<?= e($pertemuan['kelas'] ?? (config('kelas_list')[0] ?? 'S5')) ?>">

                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="nomor" class="form-label fw-bold small text-uppercase text-secondary">
                                Nomor Pertemuan <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-body-tertiary"><i class="ti ti-hash fs-2 text-secondary"></i></span>
                                <input type="number" class="form-control" id="nomor" name="nomor" required min="1" max="50" value="<?= e($pertemuan['nomor'] ?? '1') ?>" placeholder="1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="mata_kuliah" class="form-label fw-bold small text-uppercase text-secondary">Mata Kuliah</label>
                            <div class="input-group">
                                <span class="input-group-text bg-body-tertiary"><i class="ti ti-book fs-2 text-secondary"></i></span>
                                <input type="text" class="form-control" id="mata_kuliah" name="mata_kuliah" value="<?= e($pertemuan['mata_kuliah'] ?? config('mata_kuliah', 'Komputasi Awan')) ?>" placeholder="Komputasi Awan">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="topik" class="form-label fw-bold small text-uppercase text-secondary">
                            Topik / Judul Pertemuan <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-body-tertiary"><i class="ti ti-heading fs-2 text-secondary"></i></span>
                            <input type="text" class="form-control" id="topik" name="topik" required value="<?= e($pertemuan['topik'] ?? '') ?>" placeholder="Contoh: Pengenalan Komputasi Awan & Model Layanan Cloud">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="tanggal" class="form-label fw-bold small text-uppercase text-secondary">
                            Tanggal Pertemuan <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-body-tertiary"><i class="ti ti-calendar fs-2 text-secondary"></i></span>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" required value="<?= e($pertemuan['tanggal'] ?? date('Y-m-d')) ?>">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="file_materi" class="form-label fw-bold small text-uppercase text-secondary">
                            Upload Berkas Materi (Bisa Pilih Banyak File)
                        </label>
                        
                        <?php if ($isEdit): 
                            $materiFiles = get_materi_files($pertemuan['file_materi'] ?? null);
                            if (!empty($materiFiles)):
                        ?>
                            <div class="mb-3 p-3 rounded-3 bg-body-tertiary border" id="materiContainer">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="avatar avatar-md bg-primary-lt text-primary rounded-circle shadow-sm me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                                        <i class="ti ti-folders fs-2"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark fs-4">Berkas Materi Saat Ini</div>
                                        <div class="text-secondary small" id="materiCountText">
                                            <?= count($materiFiles) ?> Berkas Lampiran Tersedia
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex flex-column gap-2.5 mt-3 pt-3 border-top" id="materiList">
                                    <?php foreach ($materiFiles as $mf): 
                                        $fileId = md5($mf);
                                        $ext = strtolower(pathinfo($mf, PATHINFO_EXTENSION));
                                        $iconClass = 'ti-file-text text-primary';
                                        $extColor = 'primary';
                                        if ($ext === 'pdf') {
                                            $iconClass = 'ti-file-type-pdf text-danger';
                                            $extColor = 'danger';
                                        } elseif (in_array($ext, ['ppt', 'pptx'])) {
                                            $iconClass = 'ti-file-type-ppt text-warning';
                                            $extColor = 'warning';
                                        } elseif (in_array($ext, ['doc', 'docx'])) {
                                            $iconClass = 'ti-file-type-doc text-primary';
                                            $extColor = 'primary';
                                        } elseif (in_array($ext, ['zip', 'rar', '7z'])) {
                                            $iconClass = 'ti-file-zip text-success';
                                            $extColor = 'success';
                                        } elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) {
                                            $iconClass = 'ti-file-spreadsheet text-success';
                                            $extColor = 'success';
                                        }
                                    ?>
                                        <div class="materi-file-item d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3" id="file_row_<?= $fileId ?>">
                                            <div class="d-flex align-items-center gap-3 text-truncate me-sm-2" style="min-width: 0;">
                                                <div class="avatar avatar-md rounded-2 bg-<?= $extColor ?>-lt text-<?= $extColor ?> flex-shrink-0" style="width: 38px; height: 38px;">
                                                    <i class="ti <?= $iconClass ?>" style="font-size: 1.35rem;"></i>
                                                </div>
                                                <div class="d-flex flex-column justify-content-center text-truncate" style="min-width: 0;">
                                                    <a href="/materi/<?= e($pertemuan['id']) ?>/download?file=<?= urlencode($mf) ?>" target="_blank" class="fw-bold text-dark text-decoration-none text-truncate small mb-0.5" title="<?= e($mf) ?>" style="font-size: 0.88rem; line-height: 1.35;">
                                                        <?= e($mf) ?>
                                                    </a>
                                                    <div class="text-secondary small" style="font-size: 0.78rem; line-height: 1.25;">
                                                        Berkas Materi Perkuliahan &bull; Format <strong class="text-uppercase text-secondary"><?= e($ext) ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-sm-2">
                                                <a href="/materi/<?= e($pertemuan['id']) ?>/download?file=<?= urlencode($mf) ?>" target="_blank"
                                                   class="btn btn-materi-download btn-sm d-inline-flex align-items-center gap-1.5 shadow-none" title="Unduh Berkas">
                                                    <i class="ti ti-download fs-2"></i>
                                                    <span>Unduh</span>
                                                </a>
                                                <button type="button" class="btn btn-materi-delete btn-sm shadow-none" onclick="hapusFileMateri(<?= (int)$pertemuan['id'] ?>, '<?= e(addslashes($mf)) ?>', '<?= $fileId ?>')" title="Hapus Berkas">
                                                    <i class="ti ti-trash fs-2"></i>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; endif; ?>

                        <div class="input-group">
                            <span class="input-group-text bg-body-tertiary"><i class="ti ti-upload fs-2 text-secondary"></i></span>
                            <input class="form-control" type="file" id="file_materi" name="file_materi[]" multiple>
                        </div>
                        <div class="form-text small text-secondary mt-1.5 d-flex align-items-center gap-1">
                            <i class="ti ti-info-circle text-primary flex-shrink-0"></i>
                            <span>Dapat memilih sekaligus beberapa berkas. Maksimal: <strong>50 MB</strong> per berkas (PDF, PPTX, DOCX, ZIP, dsb.)</span>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-transparent py-3 px-4 d-flex justify-content-end align-items-center gap-2 border-top">
                    <a href="/dosen/pertemuan" class="btn btn-outline-secondary px-3 py-2 fw-semibold rounded-2 d-inline-flex align-items-center gap-1.5">
                        <i class="ti ti-x"></i>
                        <span>Batal</span>
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm rounded-2 d-inline-flex align-items-center gap-1.5 text-white">
                        <i class="ti <?= $isEdit ? 'ti-device-floppy' : 'ti-plus' ?> fs-2"></i>
                        <span><?= $isEdit ? 'Simpan Perubahan' : 'Tambah Pertemuan' ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($isEdit): ?>
<script>
function hapusFileMateri(pertemuanId, fileName, fileId) {
    if (!confirm('Apakah Anda yakin ingin menghapus berkas materi "' + fileName + '" ini?')) {
        return;
    }

    const rowEl = document.getElementById('file_row_' + fileId);
    const btn = rowEl ? rowEl.querySelector('button') : null;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menghapus...';
    }

    const csrfToken = '<?= csrf_token() ?>';
    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('file_name', fileName);

    fetch('/dosen/pertemuan/' + pertemuanId + '/hapus-file', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (rowEl) {
                rowEl.style.transition = 'all 0.3s ease';
                rowEl.style.opacity = '0';
                rowEl.style.transform = 'translateX(20px)';
                setTimeout(() => {
                    rowEl.remove();
                    if (data.remaining_count <= 0) {
                        const container = document.getElementById('materiContainer');
                        if (container) {
                            container.style.transition = 'all 0.3s ease';
                            container.style.opacity = '0';
                            setTimeout(() => container.remove(), 300);
                        }
                    } else {
                        const countEl = document.getElementById('materiCountText');
                        if (countEl) {
                            countEl.textContent = data.remaining_count + ' Berkas Lampiran Tersedia';
                        }
                    }
                }, 300);
            }
        } else {
            alert('Gagal menghapus berkas: ' + (data.message || 'Terjadi kesalahan sistem.'));
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-trash fs-2"></i> <span>Hapus</span>';
            }
        }
    })
    .catch(err => {
        console.error('Error deleting file:', err);
        alert('Terjadi kesalahan jaringan atau sesi telah kedaluwarsa.');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-trash fs-2"></i> <span>Hapus</span>';
        }
    });
}
</script>
<?php endif; ?>

<?php 
$body = ob_get_clean(); 
Flight::render('layout', ['body' => $body, 'title' => $title ?? 'Form Pertemuan']);
?>

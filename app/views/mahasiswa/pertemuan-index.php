<?php ob_start(); ?>

<div class="row mb-3 mb-md-4 align-items-center">
    <div class="col-12">
        <h2 class="fw-bold text-dark mb-1">Daftar Pertemuan & Materi</h2>
        <div class="text-secondary small d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-primary text-white px-2.5 py-0.5">Semester 5</span>
            <span>&bull;</span>
            <span>Program Studi <?= e(config('prodi')) ?></span>
        </div>
    </div>
</div>

<div class="row g-3">
    <?php if (empty($pertemuanList)): ?>
        <div class="col-12">
            <div class="card shadow-sm border py-5 text-center text-secondary px-3 rounded-3">
                <i class="ti ti-folder-off display-4 mb-3 d-block text-secondary opacity-50"></i>
                <h5 class="fw-bold text-dark">Belum ada pertemuan yang dibuat untuk Semester 5.</h5>
                <p class="small text-secondary mb-0">Silakan hubungi dosen pengampu jika jadwal perkuliahan sudah dimulai.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($pertemuanList as $p): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card shadow-sm border h-100 rounded-3 d-flex flex-column justify-content-between p-3.5 p-md-4">
                    <div>
                        <!-- Badges & Date Header -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                <span class="badge bg-primary text-white px-2.5 py-1">Pertemuan <?= e($p['nomor']) ?></span>
                                <?php if (!empty($p['mata_kuliah'])): ?>
                                    <span class="badge bg-info-lt text-info px-2.5 py-1">
                                        <i class="ti ti-school me-1"></i><?= e($p['mata_kuliah']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-secondary small d-inline-flex align-items-center gap-1.5 font-monospace" style="font-size: 0.8rem;">
                                <i class="ti ti-calendar text-primary fs-2"></i>
                                <span><?= !empty($p['tanggal']) ? format_date(strtotime($p['tanggal'])) : '-' ?></span>
                            </div>
                        </div>

                        <!-- Topic Title -->
                        <h4 class="fw-bold text-dark mb-3" style="font-size: 1.05rem; line-height: 1.45; min-height: 2.9rem;">
                            <?= e($p['topik']) ?>
                        </h4>
                        
                        <!-- Tasks & Materials Summary Box -->
                        <div class="p-3 rounded-3 bg-body-tertiary border d-flex flex-column gap-2.5 mb-3">
                            <div class="small d-flex align-items-center justify-content-between">
                                <span class="d-inline-flex align-items-center gap-2 text-secondary">
                                    <i class="ti ti-clipboard-list text-primary fs-2"></i>
                                    <span class="fw-medium text-dark">Jumlah Tugas</span>
                                </span>
                                <?php if ((int)$p['total_tugas'] > 0): ?>
                                    <span class="badge bg-primary-lt text-primary fw-bold px-2.5 py-1">
                                        <?= (int)$p['total_tugas'] ?> Tugas
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-lt text-secondary px-2.5 py-1">
                                        0 Tugas
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="small d-flex align-items-center justify-content-between">
                                <span class="d-inline-flex align-items-center gap-2 text-secondary">
                                    <i class="ti ti-files text-success fs-2"></i>
                                    <span class="fw-medium text-dark">Materi</span>
                                </span>
                                <?php 
                                    $materiFiles = get_materi_files($p['file_materi'] ?? null);
                                    if (!empty($materiFiles)): 
                                ?>
                                    <span class="badge bg-success-lt text-success fw-bold px-2.5 py-1">
                                        <?= count($materiFiles) ?> Berkas Tersedia
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-lt text-secondary px-2.5 py-1">
                                        Tidak ada berkas
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <a href="/pertemuan/<?= e($p['id']) ?>" class="btn btn-primary btn-sm w-100 fw-bold d-inline-flex align-items-center justify-content-center gap-2 py-2 shadow-sm rounded-2 mt-2">
                        <span>Buka Pertemuan & Tugas</span>
                        <i class="ti ti-arrow-right fs-2"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php 
$body = ob_get_clean(); 
Flight::render('layout', ['body' => $body, 'title' => $title ?? 'Daftar Pertemuan']);
?>

<?php ob_start(); ?>

<?php
// Compute student assignment metrics
$totalOpen = count($openTasks);
$totalSubmitted = 0;
$totalGraded = 0;

foreach ($submissionHistory as $sh) {
    if (empty($sh['is_missed'])) {
        $totalSubmitted++;
        if ($sh['nilai'] !== null && $sh['nilai'] !== '') {
            $totalGraded++;
        }
    }
}
?>

<!-- Page Header with Clean Quick Actions -->
<div class="row align-items-center mb-4">
    <div class="col-12 col-md-7">
        <h3 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i class="ti ti-layout-dashboard text-primary"></i>
            <span>Dashboard Mahasiswa</span>
        </h3>
        <p class="text-secondary small mb-0 mt-1">
            <?= e(config('mata_kuliah', 'Komputasi Awan')) ?> &bull; Semester <?= e(config('semester')) ?> (<?= e(config('tahun_ajar')) ?>) &bull; Program Studi <?= e(config('prodi')) ?>
        </p>
    </div>
    <div class="col-12 col-md-5 text-md-end mt-3 mt-md-0 d-flex gap-2 flex-wrap justify-content-md-end">
        <a href="/pertemuan" class="btn btn-outline-primary btn-sm flex-fill flex-md-grow-0 shadow-sm">
            <i class="ti ti-book me-1"></i> Materi Kuliah
        </a>
        <a href="/nilai" class="btn btn-outline-success btn-sm flex-fill flex-md-grow-0 shadow-sm">
            <i class="ti ti-certificate me-1"></i> Nilai Saya
        </a>
    </div>
</div>

<!-- Stat Cards (Tabler Metric Style) -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
        <div class="stat-pill-card d-flex flex-row justify-content-between align-items-center">
            <div>
                <div class="text-muted">Tugas Terbuka</div>
                <h4><?= $totalOpen ?></h4>
            </div>
            <div class="stat-icon-circle stat-icon-primary">
                <i class="ti ti-clock-play fs-2"></i>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="stat-pill-card d-flex flex-row justify-content-between align-items-center">
            <div>
                <div class="text-muted">Tugas Dikumpulkan</div>
                <h4><?= $totalSubmitted ?></h4>
            </div>
            <div class="stat-icon-circle stat-icon-success">
                <i class="ti ti-circle-check fs-2"></i>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="stat-pill-card d-flex flex-row justify-content-between align-items-center">
            <div>
                <div class="text-muted">Sudah Dinilai</div>
                <h4><?= $totalGraded ?></h4>
            </div>
            <div class="stat-icon-circle stat-icon-warning">
                <i class="ti ti-award fs-2"></i>
            </div>
        </div>
    </div>
</div>

<!-- Tugas Terbuka (Open Assignments) -->
<div class="row mb-4">
    <div class="col-12">
        <div class="glass-card">
            <div class="card-header d-flex justify-content-between align-items-center py-2.5 px-3 px-md-4">
                <h4 class="card-title mb-0 fw-bold d-flex align-items-center gap-2">
                    <i class="ti ti-clock-play text-primary fs-2"></i>
                    <span>Tugas Terbuka (Sedang Berlangsung)</span>
                </h4>
                <span class="badge bg-primary text-white"><?= count($openTasks) ?> Tugas</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($openTasks)): ?>
                    <div class="text-center py-5 text-secondary px-3">
                        <i class="ti ti-circle-check display-4 text-success mb-2 d-block opacity-75"></i>
                        <h4 class="fw-bold mb-1">Tidak ada tugas yang sedang dibuka saat ini.</h4>
                        <p class="small mb-0">Silakan pelajari materi perkuliahan di menu Pertemuan &amp; Materi.</p>
                    </div>
                <?php else: ?>
                    <!-- Mobile Stacked Card View (d-block d-md-none) -->
                    <div class="d-block d-md-none">
                        <div class="mobile-card-list">
                            <?php foreach ($openTasks as $tugas): 
                                $isGraded = ($tugas['nilai'] !== null && $tugas['nilai'] !== '');
                            ?>
                                <div class="mobile-card-item">
                                    <div class="item-header">
                                        <span class="badge bg-primary">Pertemuan <?= e($tugas['pertemuan_nomor']) ?></span>
                                        <?php if ($isGraded): ?>
                                            <span class="badge bg-success">
                                                <i class="ti ti-award me-1"></i>Nilai: <?= number_format((float)$tugas['nilai'], 1) ?>
                                            </span>
                                        <?php elseif (!empty($tugas['pengumpulan_id'])): ?>
                                            <span class="badge bg-info">
                                                <i class="ti ti-circle-check me-1"></i>Sudah Mengumpulkan
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="ti ti-alert-circle me-1"></i>Belum Mengumpulkan
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="item-title"><?= e($tugas['judul']) ?></div>
                                    <div class="small text-secondary mb-2"><?= e($tugas['pertemuan_topik']) ?></div>

                                    <div class="item-meta">
                                        <div class="meta-row">
                                            <span class="text-secondary"><i class="ti ti-calendar-x me-1 text-danger"></i>Batas Pengumpulan:</span>
                                            <span class="fw-bold text-danger"><?= format_datetime((int)$tugas['ditutup_pada']) ?></span>
                                        </div>
                                        <div class="meta-row">
                                            <span class="text-secondary"><i class="ti ti-clock me-1 text-primary"></i>Sisa Waktu:</span>
                                            <span class="countdown-timer" data-countdown="<?= (int)$tugas['ditutup_pada'] ?>">
                                                <i class="ti ti-clock me-1"></i>Menghitung...
                                            </span>
                                        </div>
                                        <?php if (!empty($tugas['pengumpulan_id']) && !$isGraded): ?>
                                            <div class="meta-row">
                                                <span class="text-secondary"><i class="ti ti-calendar-check me-1"></i>Dikumpulkan:</span>
                                                <span class="small text-secondary"><?= format_datetime((int)$tugas['waktu_upload']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="item-action">
                                        <?php if ($isGraded): ?>
                                            <a href="/pertemuan/<?= e($tugas['pertemuan_id']) ?>" class="btn btn-outline-secondary btn-sm btn-mobile-full">
                                                <i class="ti ti-eye me-1"></i> Lihat Nilai (Terkunci)
                                            </a>
                                        <?php else: ?>
                                            <a href="/pertemuan/<?= e($tugas['pertemuan_id']) ?>" class="btn btn-sm <?= !empty($tugas['pengumpulan_id']) ? 'btn-outline-primary' : 'btn-primary' ?> btn-mobile-full">
                                                <i class="ti <?= !empty($tugas['pengumpulan_id']) ? 'ti-arrow-back-up' : 'ti-upload' ?> me-1"></i>
                                                <?= !empty($tugas['pengumpulan_id']) ? 'Unggah Ulang Berkas' : 'Kumpulkan Tugas' ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Desktop Table View (d-none d-md-block) -->
                    <div class="d-none d-md-block">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col" style="width: 140px;" class="ps-3 ps-md-4 text-nowrap">Pertemuan</th>
                                        <th scope="col">Judul Tugas</th>
                                        <th scope="col" style="width: 180px;" class="text-center text-nowrap">Batas Waktu</th>
                                        <th scope="col" style="width: 140px;" class="text-center text-nowrap">Sisa Waktu</th>
                                        <th scope="col" style="width: 180px;" class="text-center text-nowrap">Status Submission</th>
                                        <th scope="col" style="width: 110px;" class="text-center text-nowrap">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($openTasks as $tugas): 
                                        $isGraded = ($tugas['nilai'] !== null && $tugas['nilai'] !== '');
                                    ?>
                                        <tr>
                                            <td class="ps-3 ps-md-4 text-nowrap">
                                                <span class="badge bg-primary text-white">Pertemuan <?= e($tugas['pertemuan_nomor']) ?></span>
                                                <div class="small text-secondary mt-1 text-truncate" style="max-width: 140px;" title="<?= e($tugas['pertemuan_topik']) ?>">
                                                    <?= e($tugas['pertemuan_topik']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark table-title"><?= e($tugas['judul']) ?></div>
                                                <div class="small text-secondary mt-1"><?= e($tugas['pertemuan_topik']) ?></div>
                                            </td>
                                            <td class="text-center text-nowrap">
                                                <div class="d-inline-flex flex-column align-items-center text-nowrap">
                                                    <div class="fw-bold text-danger d-flex align-items-center gap-1" style="font-size: 0.88rem;">
                                                        <i class="ti ti-calendar-event"></i>
                                                        <span><?= format_date((int)$tugas['ditutup_pada']) ?></span>
                                                    </div>
                                                    <div class="badge bg-danger-lt text-danger fw-bold px-2 py-0.5 mt-0.5 text-nowrap" style="font-size: 0.74rem;">
                                                        <i class="ti ti-clock me-1"></i><?= format_time((int)$tugas['ditutup_pada']) ?>
                                                    </div>
                                                    <div class="text-secondary small mt-1 text-nowrap" style="font-size: 0.72rem;">
                                                        Buka: <?= format_date((int)$tugas['dibuka_pada']) ?>, <?= format_time((int)$tugas['dibuka_pada']) ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center text-nowrap">
                                                <div class="countdown-timer text-nowrap" data-countdown="<?= (int)$tugas['ditutup_pada'] ?>">
                                                    <div class="d-inline-flex flex-column align-items-center text-nowrap">
                                                        <span class="badge bg-primary text-white text-nowrap px-2.5 py-1">
                                                            <i class="ti ti-clock me-1"></i>Menghitung...
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center text-nowrap">
                                                <?php if ($isGraded): ?>
                                                    <span class="badge bg-success text-white text-nowrap">
                                                        <i class="ti ti-award me-1"></i>Sudah Dinilai: <?= number_format((float)$tugas['nilai'], 1) ?>
                                                    </span>
                                                    <div class="small text-secondary mt-1 text-nowrap">
                                                        <i class="ti ti-lock me-1"></i>Terkunci (Read-Only)
                                                    </div>
                                                <?php elseif (!empty($tugas['pengumpulan_id'])): ?>
                                                    <span class="badge bg-info text-white text-nowrap">
                                                        <i class="ti ti-circle-check me-1"></i>Sudah Mengumpulkan
                                                    </span>
                                                    <div class="small text-secondary mt-1 text-nowrap" style="font-size: 0.75rem;">
                                                        <?= format_datetime((int)$tugas['waktu_upload']) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark text-nowrap">
                                                        <i class="ti ti-alert-circle me-1"></i>Belum Mengumpulkan
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center text-nowrap">
                                                <?php if ($isGraded): ?>
                                                    <a href="/pertemuan/<?= e($tugas['pertemuan_id']) ?>" class="btn btn-sm btn-action-borderless d-inline-flex flex-column align-items-center justify-content-center text-nowrap" style="font-size: 0.75rem;" title="Lihat Nilai">
                                                        <i class="ti ti-eye fs-2 mb-0.5"></i>
                                                        <span>Lihat Nilai</span>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="/pertemuan/<?= e($tugas['pertemuan_id']) ?>" class="btn btn-sm btn-action-borderless text-primary d-inline-flex flex-column align-items-center justify-content-center text-nowrap" style="font-size: 0.75rem;" title="<?= !empty($tugas['pengumpulan_id']) ? 'Unggah Ulang Berkas' : 'Kumpulkan Tugas' ?>">
                                                        <i class="ti <?= !empty($tugas['pengumpulan_id']) ? 'ti-arrow-back-up' : 'ti-upload' ?> fs-2 mb-0.5"></i>
                                                        <span><?= !empty($tugas['pengumpulan_id']) ? 'Unggah Ulang' : 'Kumpulkan' ?></span>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Riwayat Pengumpulan (Submission History) -->
<div class="row">
    <div class="col-12">
        <div class="glass-card">
            <div class="card-header d-flex justify-content-between align-items-center py-2.5 px-3 px-md-4">
                <h4 class="card-title mb-0 fw-bold d-flex align-items-center gap-2">
                    <i class="ti ti-history text-success fs-2"></i>
                    <span>Riwayat Pengumpulan Tugas Anda</span>
                </h4>
                <span class="badge bg-secondary text-white"><?= count($submissionHistory) ?> Riwayat</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($submissionHistory)): ?>
                    <div class="text-center py-5 text-secondary px-3">
                        <i class="ti ti-folder-off display-4 mb-2 d-block opacity-50"></i>
                        <h4 class="fw-bold mb-1">Belum ada riwayat pengumpulan tugas.</h4>
                        <p class="small mb-0">Tugas yang telah Anda kumpulkan akan tercatat di sini.</p>
                    </div>
                <?php else: ?>
                    <!-- Mobile Stacked Card View (d-block d-md-none) -->
                    <div class="d-block d-md-none">
                        <div class="mobile-card-list">
                            <?php foreach ($submissionHistory as $sub): 
                                $isMissed = !empty($sub['is_missed']);
                            ?>
                                <?php if ($isMissed): ?>
                                    <div class="mobile-card-item" style="border-left: 3px solid #d63939;">
                                        <div class="item-header">
                                            <span class="badge bg-secondary">Pertemuan <?= e($sub['pertemuan_nomor']) ?></span>
                                            <span class="badge bg-danger">
                                                <i class="ti ti-clock-stop me-1"></i>Terlewat
                                            </span>
                                        </div>

                                        <div class="item-title text-danger"><?= e($sub['tugas_judul']) ?></div>
                                        <div class="small text-secondary mb-2"><?= e($sub['pertemuan_topik']) ?></div>

                                        <div class="item-meta">
                                            <div class="meta-row">
                                                <span class="text-secondary"><i class="ti ti-calendar-x me-1 text-danger"></i>Ditutup Pada:</span>
                                                <span class="small text-danger fw-bold"><?= format_datetime((int)$sub['ditutup_pada']) ?></span>
                                            </div>
                                            <div class="meta-row">
                                                <span class="text-secondary"><i class="ti ti-alert-triangle me-1"></i>Status:</span>
                                                <span class="text-danger small fst-italic">Waktu pengumpulan telah habis</span>
                                            </div>
                                        </div>

                                        <div class="item-action">
                                            <a href="/pertemuan/<?= e($sub['pertemuan_id']) ?>" class="btn btn-outline-secondary btn-sm btn-mobile-full">
                                                <i class="ti ti-eye me-1"></i> Lihat Pertemuan
                                            </a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="mobile-card-item">
                                        <div class="item-header">
                                            <span class="badge bg-secondary">Pertemuan <?= e($sub['pertemuan_nomor']) ?></span>
                                            <?php if ($sub['nilai'] !== null && $sub['nilai'] !== ''): ?>
                                                <span class="badge bg-success">
                                                    <i class="ti ti-award me-1"></i>Nilai: <?= number_format((float)$sub['nilai'], 1) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Belum dinilai</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="item-title"><?= e($sub['tugas_judul']) ?></div>
                                        <div class="small text-secondary mb-2"><?= e($sub['pertemuan_topik']) ?></div>

                                        <div class="item-meta">
                                            <div class="meta-row">
                                                <span class="text-secondary"><i class="ti ti-calendar-check me-1 text-success"></i>Dikumpulkan:</span>
                                                <span class="small text-dark fw-medium"><?= format_datetime((int)$sub['waktu_upload']) ?></span>
                                            </div>
                                            <div class="meta-row">
                                                <span class="text-secondary"><i class="ti ti-file-text me-1"></i>Berkas:</span>
                                                <span class="badge bg-secondary text-truncate" style="max-width: 180px;"><?= e($sub['file_asli']) ?></span>
                                            </div>
                                        </div>

                                        <?php if (!empty($sub['feedback'])): ?>
                                            <div class="p-2.5 rounded bg-body-tertiary border mt-2 small">
                                                <div class="fw-bold text-primary mb-1"><i class="ti ti-message-dots me-1"></i>Feedback Dosen:</div>
                                                <div class="text-secondary"><?= e($sub['feedback']) ?></div>
                                            </div>
                                        <?php endif; ?>

                                        <div class="item-action">
                                            <a href="/pengumpulan/<?= e($sub['pengumpulan_id'] ?? $sub['id']) ?>/download" class="btn btn-outline-secondary btn-sm btn-mobile-full">
                                                <i class="ti ti-download me-1"></i> Unduh Berkas Anda
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Desktop Table View (d-none d-md-block) -->
                    <div class="d-none d-md-block">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col" class="ps-3 ps-md-4">Tugas / Pertemuan</th>
                                        <th scope="col" style="width: 200px;" class="text-center text-nowrap">Nama File</th>
                                        <th scope="col" style="width: 180px;" class="text-center text-nowrap">Waktu Pengumpulan</th>
                                        <th scope="col" style="width: 100px;" class="text-center text-nowrap">Nilai</th>
                                        <th scope="col">Feedback Dosen</th>
                                        <th scope="col" style="width: 100px;" class="text-center text-nowrap">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submissionHistory as $sub): 
                                        $isMissed = !empty($sub['is_missed']);
                                    ?>
                                        <?php if ($isMissed): ?>
                                            <tr style="background: rgba(214, 57, 57, 0.04);">
                                                <td class="ps-3 ps-md-4">
                                                    <div class="fw-bold text-danger table-title"><?= e($sub['tugas_judul']) ?></div>
                                                    <div class="small text-secondary mt-1">Pertemuan <?= e($sub['pertemuan_nomor']) ?>: <?= e($sub['pertemuan_topik']) ?></div>
                                                </td>
                                                <td class="text-center text-nowrap">
                                                    <span class="text-danger small fst-italic">
                                                        <i class="ti ti-file-off me-1"></i>Tidak ada berkas
                                                    </span>
                                                </td>
                                                <td class="text-center text-nowrap">
                                                    <div class="text-danger small d-inline-flex flex-column align-items-center text-nowrap" style="line-height: 1.25;">
                                                        <div class="d-flex align-items-center gap-1 fw-semibold">
                                                            <i class="ti ti-clock-stop"></i>
                                                            <span>Ditutup: <?= format_date((int)$sub['ditutup_pada']) ?>,</span>
                                                        </div>
                                                        <div class="small fw-semibold mt-0.5">
                                                            <?= format_time((int)$sub['ditutup_pada']) ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center text-nowrap">
                                                    <span class="badge bg-danger text-white text-nowrap">Terlewat</span>
                                                </td>
                                                <td>
                                                    <span class="text-secondary small fst-italic">Tidak mengumpulkan</span>
                                                </td>
                                                <td class="text-center text-nowrap">
                                                    <a href="/pertemuan/<?= e($sub['pertemuan_id']) ?>" class="btn btn-sm btn-action-borderless d-inline-flex flex-column align-items-center justify-content-center text-nowrap" style="font-size: 0.75rem;" title="Lihat detail pertemuan">
                                                        <i class="ti ti-eye fs-2 mb-0.5"></i>
                                                        <span>Detail</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td class="ps-3 ps-md-4">
                                                    <div class="fw-bold text-dark table-title"><?= e($sub['tugas_judul']) ?></div>
                                                    <div class="small text-secondary mt-1">Pertemuan <?= e($sub['pertemuan_nomor']) ?>: <?= e($sub['pertemuan_topik']) ?></div>
                                                </td>
                                                <td class="text-center text-nowrap">
                                                    <span class="badge bg-secondary text-white d-inline-flex align-items-center gap-1 px-2 py-1 text-nowrap">
                                                        <i class="ti ti-file-text"></i>
                                                        <span class="text-truncate" style="max-width: 170px;" title="<?= e($sub['file_asli']) ?>"><?= e($sub['file_asli']) ?></span>
                                                    </span>
                                                </td>
                                                <td class="text-center text-nowrap">
                                                    <div class="small d-inline-flex flex-column align-items-center text-nowrap" style="line-height: 1.25;">
                                                        <div class="d-flex align-items-center gap-1 text-dark fw-medium">
                                                            <i class="ti ti-calendar-check text-success"></i>
                                                            <span><?= format_date((int)$sub['waktu_upload']) ?>,</span>
                                                        </div>
                                                        <div class="text-secondary small mt-0.5">
                                                            <?= format_time((int)$sub['waktu_upload']) ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center text-nowrap">
                                                    <?php if ($sub['nilai'] !== null && $sub['nilai'] !== ''): ?>
                                                        <span class="badge bg-success text-white fw-bold px-2.5 py-1 text-nowrap">
                                                            <?= number_format((float)$sub['nilai'], 1) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary text-white text-nowrap">Belum dinilai</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($sub['feedback'])): ?>
                                                        <div class="small text-dark d-inline-flex align-items-center gap-1.5" style="line-height: 1.25;">
                                                            <i class="ti ti-message-dots text-primary flex-shrink-0" style="font-size: 1.15rem;"></i>
                                                            <span><?= e($sub['feedback']) ?></span>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-secondary small">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center text-nowrap">
                                                    <a href="/pengumpulan/<?= e($sub['pengumpulan_id'] ?? $sub['id']) ?>/download" class="btn btn-sm btn-action-borderless d-inline-flex flex-column align-items-center justify-content-center text-nowrap" style="font-size: 0.75rem;" title="Unduh Berkas Submission">
                                                        <i class="ti ti-download fs-2 mb-0.5"></i>
                                                        <span>Unduh</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php 
$body = ob_get_clean(); 
Flight::render('layout', ['body' => $body, 'title' => $title ?? 'Dashboard Mahasiswa']);
?>
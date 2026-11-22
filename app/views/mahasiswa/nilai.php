<?php ob_start(); 
$totalNilai = 0;
$countDinilai = 0;
foreach ($nilaiList as $n) {
    if ($n['nilai'] !== null && $n['nilai'] !== '') {
        $totalNilai += (float)$n['nilai'];
        $countDinilai++;
    }
}
$rataRata = $countDinilai > 0 ? ($totalNilai / $countDinilai) : null;
?>

<!-- Header & Average Summary Card -->
<div class="row mb-3 mb-md-4">
    <div class="col-12">
        <div class="card shadow-sm border rounded-3 p-3.5 p-md-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="avatar avatar-md rounded-circle bg-warning-lt text-warning shadow-sm flex-shrink-0" style="width: 48px; height: 48px;">
                    <i class="ti ti-award fs-1"></i>
                </span>
                <div>
                    <h3 class="mb-1 fw-bold text-dark">Daftar Nilai & Feedback</h3>
                    <div class="text-secondary small d-flex align-items-center gap-2 flex-wrap">
                        <span class="fw-medium text-dark"><?= e($user['nim']) ?></span>
                        <span>&bull;</span>
                        <span><?= e($user['nama']) ?></span>
                        <span>&bull;</span>
                        <span class="badge bg-secondary-lt text-secondary px-2 py-0.5">Semester 5</span>
                    </div>
                </div>
            </div>
            <?php if ($rataRata !== null): ?>
                <div class="d-flex align-items-center gap-3 rounded-3 bg-body-tertiary border flex-shrink-0" style="padding: 0.85rem 1.25rem !important;">
                    <span class="avatar avatar-md rounded-2 bg-primary-lt text-primary flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="ti ti-chart-bar fs-1"></i>
                    </span>
                    <div class="text-start">
                        <div class="text-secondary small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.04em; line-height: 1.2;">Rata-Rata Nilai</div>
                        <div class="fw-bold text-primary my-1 lh-1" style="font-size: 1.55rem;"><?= number_format($rataRata, 2) ?></div>
                        <div class="text-secondary small d-flex align-items-center gap-1" style="font-size: 0.76rem; line-height: 1.2;">
                            <i class="ti ti-circle-check text-success fs-3"></i>
                            <span><?= $countDinilai ?> Tugas Dinilai</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border rounded-3 overflow-hidden">
            <div class="card-header d-flex justify-content-between align-items-center py-2.5 px-3 px-md-4">
                <h4 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="ti ti-checklist text-primary fs-2"></i>
                    <span>Rekap Nilai Perkuliahan</span>
                </h4>
                <span class="badge bg-primary-lt text-primary fw-bold px-2.5 py-1"><?= count($nilaiList) ?> Submission</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($nilaiList)): ?>
                    <div class="text-center py-5 text-secondary px-3">
                        <i class="ti ti-clipboard-x display-4 text-secondary opacity-50 mb-3 d-block"></i>
                        <h5 class="fw-bold text-dark">Belum ada tugas yang dikumpulkan.</h5>
                        <p class="small text-secondary mb-0">Nilai akan tampil setelah Anda mengumpulkan tugas dan dinilai oleh dosen pengampu.</p>
                    </div>
                <?php else: ?>
                    <!-- Mobile Stacked Card View (d-block d-md-none) -->
                    <div class="d-block d-md-none p-3">
                        <div class="mobile-card-list">
                            <?php foreach ($nilaiList as $n): ?>
                                <div class="mobile-card-item card shadow-none border rounded-3 p-3 mb-2.5 bg-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-primary-lt text-primary fw-bold px-2.5 py-1">Pertemuan <?= e($n['pertemuan_nomor']) ?></span>
                                        <?php if ($n['nilai'] !== null && $n['nilai'] !== ''): ?>
                                            <span class="badge bg-success text-white px-2.5 py-1 fw-bold fs-4">
                                                Nilai: <?= number_format((float)$n['nilai'], 1) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-lt text-secondary px-2.5 py-1">Belum dinilai</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="fw-bold text-dark mb-1" style="font-size: 0.95rem;"><?= e($n['tugas_judul']) ?></div>
                                    <div class="small text-secondary mb-2">
                                        <?= e($n['pertemuan_topik']) ?>
                                        <?php if (!empty($n['mata_kuliah'])): ?>
                                            &bull; <?= e($n['mata_kuliah']) ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="p-2 px-2.5 rounded-2 bg-body-tertiary border mb-2.5 small">
                                        <div class="d-flex align-items-center gap-1.5 text-secondary">
                                            <i class="ti ti-calendar-check text-success fs-3"></i>
                                            <span>Dikumpulkan: <strong class="text-dark"><?= format_datetime((int)$n['waktu_upload']) ?></strong></span>
                                        </div>
                                    </div>

                                    <?php if (!empty($n['feedback'])): ?>
                                        <div class="p-2.5 rounded-2 bg-body-tertiary border mb-2.5" style="border-left: 3px solid #206bc4 !important;">
                                            <div class="small fw-bold text-primary mb-1 d-flex align-items-center gap-1">
                                                <i class="ti ti-message-dots fs-3"></i>
                                                <span>Feedback Dosen:</span>
                                            </div>
                                            <div class="text-secondary small" style="line-height: 1.5;"><?= nl2br(e(trim($n['feedback']))) ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <a href="/pengumpulan/<?= e($n['pengumpulan_id'] ?? $n['id']) ?>/download" class="btn btn-outline-primary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-1.5 py-1.5">
                                            <i class="ti ti-download fs-2"></i>
                                            <span>Unduh Berkas Submission</span>
                                        </a>
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
                                        <th scope="col" style="width: 140px;" class="ps-4 text-nowrap">Pertemuan</th>
                                        <th scope="col" style="min-width: 220px;">Tugas</th>
                                        <th scope="col" style="width: 190px;" class="text-center text-nowrap">Waktu Pengumpulan</th>
                                        <th scope="col" style="width: 110px;" class="text-center text-nowrap">Nilai</th>
                                        <th scope="col" style="min-width: 200px;">Feedback Dosen</th>
                                        <th scope="col" style="width: 110px;" class="text-center text-nowrap pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($nilaiList as $n): ?>
                                        <tr>
                                            <td class="ps-4 text-nowrap">
                                                <span class="badge bg-primary-lt text-primary px-2.5 py-1 fw-bold">Pertemuan <?= e($n['pertemuan_nomor']) ?></span>
                                                <div class="small text-secondary mt-1 text-truncate" style="max-width: 130px;" title="<?= e($n['pertemuan_topik']) ?>">
                                                    <?= e($n['pertemuan_topik']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark table-title mb-0.5"><?= e($n['tugas_judul']) ?></div>
                                                <?php if (!empty($n['mata_kuliah'])): ?>
                                                    <div class="small text-secondary d-flex align-items-center gap-1">
                                                        <i class="ti ti-school text-muted"></i>
                                                        <span><?= e($n['mata_kuliah']) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center text-nowrap">
                                                <div class="small d-inline-flex flex-column align-items-center text-nowrap" style="line-height: 1.25;">
                                                    <div class="d-flex align-items-center gap-1 text-dark fw-medium">
                                                        <i class="ti ti-calendar-check text-success"></i>
                                                        <span><?= format_date((int)$n['waktu_upload']) ?>,</span>
                                                    </div>
                                                    <div class="text-secondary small mt-0.5">
                                                        <?= format_time((int)$n['waktu_upload']) ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center text-nowrap">
                                                <?php if ($n['nilai'] !== null && $n['nilai'] !== ''): ?>
                                                    <span class="badge bg-success text-white px-2.5 py-1 text-nowrap fw-bold fs-4">
                                                        <?= number_format((float)$n['nilai'], 1) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-lt text-secondary px-2.5 py-1 text-nowrap">Belum dinilai</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($n['feedback'])): ?>
                                                    <div class="small text-dark d-inline-flex align-items-center gap-1.5" style="line-height: 1.25;">
                                                        <i class="ti ti-message-dots text-primary flex-shrink-0" style="font-size: 1.15rem;"></i>
                                                        <span><?= e($n['feedback']) ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-secondary small">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center text-nowrap pe-4">
                                                <a href="/pengumpulan/<?= e($n['pengumpulan_id'] ?? $n['id']) ?>/download" 
                                                   class="btn btn-sm btn-action-borderless d-inline-flex flex-column align-items-center justify-content-center text-nowrap" 
                                                   style="font-size: 0.75rem;" 
                                                   title="Unduh berkas submission">
                                                    <i class="ti ti-download fs-2 mb-0.5"></i>
                                                    <span>Unduh</span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <?php if ($countDinilai > 0): ?>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="ps-4 text-end fw-bold text-nowrap">Rata-Rata Nilai:</th>
                                            <th class="text-center text-nowrap">
                                                <span class="badge bg-primary text-white px-2.5 py-1 fs-3">
                                                    <?= number_format($rataRata, 2) ?>
                                                </span>
                                            </th>
                                            <td colspan="2" class="text-secondary small tfoot-note text-nowrap pe-4">
                                                Dari total <?= $countDinilai ?> tugas yang telah dinilai.
                                            </td>
                                        </tr>
                                    </tfoot>
                                <?php endif; ?>
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
Flight::render('layout', ['body' => $body, 'title' => $title ?? 'Nilai Mahasiswa']);
?>

<?php ob_start(); ?>

<div class="row mb-3 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i class="ti ti-table text-primary"></i>
            <span>Rekap Nilai Tugas Mahasiswa</span>
        </h3>
        <p class="text-secondary small mb-0 mt-1">Matriks nilai perkuliahan Semester 5 dan ekspor laporan ke format CSV.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <a href="/dosen/rekap/export" class="btn btn-success btn-sm shadow-sm">
            <i class="ti ti-file-spreadsheet me-1"></i> Ekspor Rekap CSV
        </a>
    </div>
</div>

<!-- Matrix Table -->
<div class="row">
    <div class="col-12">
        <div class="glass-card">
            <div class="card-header d-flex justify-content-between align-items-center py-2 px-3 px-md-4">
                <h4 class="card-title mb-0 fw-bold d-flex align-items-center gap-2">
                    <i class="ti ti-report-analytics text-primary"></i>
                    <span>Rekap Nilai Semester 5</span>
                </h4>
                <span class="badge bg-secondary"><?= count($students) ?> Mahasiswa &bull; <?= count($tasks) ?> Tugas</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($students)): ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="ti ti-users-minus display-4 mb-2 d-block opacity-50"></i>
                        <h4 class="fw-bold">Belum ada mahasiswa di Semester 5.</h4>
                        <p class="small mb-0">Jalankan seeder untuk memuat data mahasiswa.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0 text-center">
                            <thead class="align-middle">
                                <tr>
                                    <th scope="col" style="width: 50px;">No</th>
                                    <th scope="col" style="width: 140px;">NIM</th>
                                    <th scope="col" class="text-start ps-3">Nama Mahasiswa</th>
                                    <?php foreach ($tasks as $t): ?>
                                        <th scope="col" title="<?= e($t['judul']) ?>" style="min-width: 110px;">
                                            <div class="small fw-bold">P<?= e($t['pertemuan_nomor']) ?></div>
                                            <div class="small text-secondary text-truncate" style="max-width: 120px;">
                                                <?= e($t['judul']) ?>
                                            </div>
                                        </th>
                                    <?php endforeach; ?>
                                    <th scope="col" class="text-primary fw-bold" style="width: 110px;">Rata-Rata</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $taskTotals = [];
                                $taskCountGraded = [];
                                foreach ($tasks as $t) {
                                    $taskTotals[$t['id']] = 0;
                                    $taskCountGraded[$t['id']] = 0;
                                }

                                foreach ($students as $idx => $mhs): 
                                    $studentTotal = 0;
                                    $studentGraded = 0;
                                ?>
                                    <tr>
                                        <td class="text-secondary small"><?= $idx + 1 ?></td>
                                        <td><code><?= e($mhs['nim']) ?></code></td>
                                        <td class="text-start ps-3 fw-semibold"><?= e($mhs['nama']) ?></td>
                                        <?php foreach ($tasks as $t): 
                                            $val = $gradesMap[$mhs['id']][$t['id']] ?? null;
                                        ?>
                                            <td>
                                                <?php if ($val !== null && $val !== ''): 
                                                    $num = (float)$val;
                                                    $studentTotal += $num;
                                                    $studentGraded++;
                                                    $taskTotals[$t['id']] += $num;
                                                    $taskCountGraded[$t['id']]++;
                                                ?>
                                                    <span class="badge bg-success">
                                                        <?= number_format($num, 1) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-secondary small">-</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="fw-bold">
                                            <?php if ($studentGraded > 0): ?>
                                                <span class="badge bg-primary">
                                                    <?= number_format($studentTotal / $studentGraded, 2) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-secondary small">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <?php if (!empty($tasks)): ?>
                                <tfoot class="fw-bold">
                                    <tr>
                                        <td colspan="3" class="text-end ps-3">Rata-Rata Kelas:</td>
                                        <?php 
                                        $overallTotal = 0;
                                        $overallGraded = 0;
                                        foreach ($tasks as $t): 
                                            $avg = $taskCountGraded[$t['id']] > 0 ? ($taskTotals[$t['id']] / $taskCountGraded[$t['id']]) : null;
                                            if ($avg !== null) {
                                                $overallTotal += $taskTotals[$t['id']];
                                                $overallGraded += $taskCountGraded[$t['id']];
                                            }
                                        ?>
                                            <td>
                                                <?= $avg !== null ? number_format($avg, 2) : '-' ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="text-primary">
                                            <?= $overallGraded > 0 ? number_format($overallTotal / $overallGraded, 2) : '-' ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php 
$body = ob_get_clean(); 
Flight::render('layout', ['body' => $body, 'title' => $title ?? 'Rekap Nilai']);
?>

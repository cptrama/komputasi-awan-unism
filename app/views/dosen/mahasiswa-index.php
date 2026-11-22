<?php ob_start(); ?>

<!-- Page Title & Header Actions -->
<div class="row mb-3 mb-md-4 align-items-center">
    <div class="col-12 col-md-7">
        <h3 class="fw-bold mb-0 d-flex align-items-center gap-2 text-body fs-2">
            <i class="ti ti-users text-primary"></i>
            <span>Kelola Data Mahasiswa</span>
        </h3>
        <p class="text-secondary small mb-0 mt-1">Koreksi NIM, nama, kelas, dan reset status password akun mahasiswa.</p>
    </div>
    <div class="col-12 col-md-5 text-md-end mt-3 mt-md-0 d-flex gap-2 flex-wrap justify-content-md-end">
        <a href="/dosen/mahasiswa/tambah" class="btn btn-primary py-2 px-3.5 fw-semibold shadow-sm rounded-2 d-inline-flex align-items-center gap-1.5" title="Tambah akun mahasiswa baru">
            <i class="ti ti-user-plus fs-2"></i>
            <span>Tambah Mahasiswa</span>
        </a>
    </div>
</div>

<!-- Filter Kelas Pills -->
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <span class="small text-secondary fw-bold me-1">Filter Kelas:</span>
            <a href="/dosen/mahasiswa" class="btn btn-sm rounded-pill px-3 fw-semibold <?= $filterKelas === null ? 'btn-primary' : 'btn-outline-secondary text-body' ?>">Semua</a>
            <?php foreach (config('kelas_list', ['S5']) as $k): ?>
                <a href="/dosen/mahasiswa?kelas=<?= e($k) ?>" class="btn btn-sm rounded-pill px-3 fw-semibold <?= $filterKelas === $k ? 'btn-primary' : 'btn-outline-secondary text-body' ?>">Kelas <?= e($k) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border rounded-3 overflow-hidden">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-2.5 px-3 px-md-4 border-bottom">
                <h4 class="mb-0 fw-bold text-body d-flex align-items-center gap-2 fs-3">
                    <i class="ti ti-list text-primary"></i>
                    <span>Daftar Mahasiswa <?= $filterKelas ? '— Kelas ' . e($filterKelas) : '' ?></span>
                </h4>
                <span class="badge bg-primary text-white fw-bold px-2.5 py-1">
                    <?= count($mahasiswaList) ?> Mahasiswa
                </span>
            </div>

            <div class="card-body p-0">
                <?php if (empty($mahasiswaList)): ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="ti ti-users-minus display-4 mb-2 d-block opacity-50"></i>
                        <h4 class="fw-bold">Belum ada data mahasiswa.</h4>
                        <p class="small mb-3">Silakan tambahkan data mahasiswa atau jalankan seeder awal.</p>
                        <a href="/dosen/mahasiswa/tambah" class="btn btn-primary btn-sm px-3 py-1.5 rounded-2 shadow-sm d-inline-flex align-items-center gap-1">
                            <i class="ti ti-user-plus"></i> Tambah Mahasiswa
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Mobile Stacked Card View (d-block d-md-none) -->
                    <div class="d-block d-md-none p-3">
                        <div class="mobile-card-list">
                            <?php foreach ($mahasiswaList as $mhs): ?>
                                <div class="mobile-card-item">
                                    <div class="item-header">
                                        <code class="text-primary fw-bold font-monospace"><?= e($mhs['nim']) ?></code>
                                        <span class="badge bg-primary-lt text-primary fw-bold px-2 py-0.5">Kelas <?= e($mhs['kelas']) ?></span>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 my-2">
                                        <span class="avatar avatar-xs bg-primary-lt text-primary rounded-circle fw-bold fs-5 flex-shrink-0" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                            <?= strtoupper(substr($mhs['nama'], 0, 1)) ?>
                                        </span>
                                        <div class="item-title fw-bold text-body fs-4"><?= e($mhs['nama']) ?></div>
                                    </div>

                                    <div class="item-meta mb-2.5">
                                        <div class="meta-row">
                                            <span class="text-secondary small">Status Password:</span>
                                            <?php if ($mhs['wajib_ganti_password']): ?>
                                                <span class="badge bg-warning-lt text-warning fw-bold px-2 py-0.5">
                                                    <i class="ti ti-key me-1"></i>Wajib Ganti
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success-lt text-success fw-bold px-2 py-0.5">
                                                    <i class="ti ti-circle-check me-1"></i>Aktif
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="item-actions d-flex gap-2">
                                        <a href="/dosen/mahasiswa/<?= $mhs['id'] ?>/edit" class="btn btn-table-action-primary flex-fill text-center py-1.5" title="Edit Mahasiswa">
                                            <i class="ti ti-edit me-1"></i>
                                            <span>Edit</span>
                                        </a>
                                        <button type="button" class="btn btn-table-action-danger flex-fill text-center py-1.5" data-bs-toggle="modal" data-bs-target="#deleteModal<?= e($mhs['id']) ?>" title="Hapus Mahasiswa">
                                            <i class="ti ti-trash me-1"></i>
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Desktop Table View (d-none d-md-block) -->
                    <div class="d-none d-md-block">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 w-100">
                                <thead>
                                    <tr>
                                        <th scope="col" class="text-center text-secondary text-uppercase fw-bold py-3" style="width: 55px;">No</th>
                                        <th scope="col" class="text-center text-secondary text-uppercase fw-bold py-3" style="width: 150px;">NIM</th>
                                        <th scope="col" class="text-secondary text-uppercase fw-bold py-3 ps-3">Nama Mahasiswa</th>
                                        <th scope="col" class="text-center text-secondary text-uppercase fw-bold py-3" style="width: 100px;">Kelas</th>
                                        <th scope="col" class="text-center text-secondary text-uppercase fw-bold py-3" style="width: 170px;">Status Password</th>
                                        <th scope="col" class="text-center text-secondary text-uppercase fw-bold py-3 pe-3" style="width: 110px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mahasiswaList as $i => $mhs): ?>
                                        <tr>
                                            <td class="text-center text-secondary small"><?= $i + 1 ?></td>
                                            <td class="text-center">
                                                <code class="text-primary font-monospace"><?= e($mhs['nim']) ?></code>
                                            </td>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="avatar avatar-xs bg-primary-lt text-primary rounded-circle fw-bold fs-5 flex-shrink-0" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                                        <?= strtoupper(substr($mhs['nama'], 0, 1)) ?>
                                                    </span>
                                                    <span class="fw-semibold text-body"><?= e($mhs['nama']) ?></span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary-lt text-primary fw-bold px-2.5 py-1">
                                                    Kelas <?= e($mhs['kelas']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($mhs['wajib_ganti_password']): ?>
                                                    <span class="badge bg-warning-lt text-warning fw-bold px-2.5 py-1 d-inline-flex align-items-center gap-1">
                                                        <i class="ti ti-key"></i>
                                                        <span>Wajib Ganti</span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success-lt text-success fw-bold px-2.5 py-1 d-inline-flex align-items-center gap-1">
                                                        <i class="ti ti-circle-check"></i>
                                                        <span>Aktif</span>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center pe-3">
                                                <div class="d-inline-flex align-items-center gap-1.5">
                                                    <a href="/dosen/mahasiswa/<?= $mhs['id'] ?>/edit" class="btn btn-table-action-primary" title="Edit Data Mahasiswa">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-table-action-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= e($mhs['id']) ?>" title="Hapus Mahasiswa">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </div>
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

<!-- Delete Confirmation Modals -->
<?php if (!empty($mahasiswaList)): ?>
    <?php foreach ($mahasiswaList as $mhs): ?>
        <div class="modal fade" id="deleteModal<?= e($mhs['id']) ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= e($mhs['id']) ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered text-start">
                <div class="modal-content shadow-lg border rounded-3 overflow-hidden">
                    <div class="modal-header py-3 px-4 border-bottom bg-transparent">
                        <div class="d-flex align-items-center gap-2.5">
                            <span class="avatar bg-danger-lt text-danger rounded-circle shadow-sm flex-shrink-0" style="width: 38px; height: 38px;">
                                <i class="ti ti-trash fs-1"></i>
                            </span>
                            <div>
                                <h5 class="modal-title fw-bold text-body mb-0 fs-3" id="deleteModalLabel<?= e($mhs['id']) ?>">
                                    Konfirmasi Hapus Mahasiswa
                                </h5>
                                <div class="text-secondary small mt-0.5">Tindakan ini permanen dan menghapus seluruh berkas tugas mahasiswa.</div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="p-3 bg-body-tertiary rounded-3 mb-3 border">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar avatar-sm bg-primary-lt text-primary rounded-circle fw-bold">
                                    <?= strtoupper(substr($mhs['nama'], 0, 1)) ?>
                                </span>
                                <div>
                                    <div class="fw-bold text-body fs-3"><?= e($mhs['nama']) ?></div>
                                    <div class="small mt-0.5">
                                        <code class="text-primary font-monospace"><?= e($mhs['nim']) ?></code> &bull; 
                                        <span class="badge bg-primary-lt text-primary fw-semibold px-2 py-0.5">Kelas <?= e($mhs['kelas']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-danger border border-danger-subtle small mb-0 rounded-3 d-flex align-items-start gap-2.5 p-3">
                            <i class="ti ti-alert-triangle text-danger fs-1 flex-shrink-0 mt-0.5"></i>
                            <div>
                                <strong class="text-body d-block mb-1">Peringatan Penghapusan Akun</strong>
                                <span class="text-secondary">
                                    Menghapus mahasiswa ini akan menghapus akun dan seluruh <strong>riwayat pengumpulan tugas serta nilai</strong> yang bersangkutan secara permanen.
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-transparent border-top py-3 px-4 d-flex justify-content-end align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary text-body px-3 py-2 fw-semibold rounded-2 d-inline-flex align-items-center gap-1.5 shadow-none" data-bs-dismiss="modal">
                            <i class="ti ti-x"></i>
                            <span>Batal</span>
                        </button>
                        <form action="/dosen/mahasiswa/<?= e($mhs['id']) ?>/hapus" method="POST" class="m-0">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-danger px-4 py-2 fw-semibold shadow-sm rounded-2 text-white d-inline-flex align-items-center gap-1.5">
                                <i class="ti ti-trash fs-2"></i>
                                <span>Ya, Hapus Mahasiswa</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
$body = ob_get_clean();
Flight::render('layout', ['body' => $body, 'title' => $title ?? 'Kelola Mahasiswa']);
?>

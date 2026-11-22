<?php ob_start(); ?>

<!-- Page Title -->
<div class="row mb-3 align-items-center">
    <div class="col-12 col-md-7">
        <h3 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i class="ti ti-users text-primary"></i>
            <span>Kelola Data Mahasiswa</span>
        </h3>
        <p class="text-secondary small mb-0 mt-1">Koreksi NIM, nama, kelas, dan reset password mahasiswa</p>
    </div>
    <div class="col-12 col-md-5 text-md-end mt-3 mt-md-0 d-flex gap-2 flex-wrap justify-content-md-end">
        <a href="/dosen/mahasiswa/tambah" class="btn btn-primary btn-sm flex-fill flex-md-grow-0 shadow-sm">
            <i class="ti ti-user-plus me-1"></i> Tambah Mahasiswa
        </a>
    </div>
</div>

<!-- Filter Kelas -->
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <span class="small text-secondary fw-bold">Filter Kelas:</span>
            <a href="/dosen/mahasiswa" class="btn btn-sm <?= $filterKelas === null ? 'btn-primary' : 'btn-outline-secondary' ?>">Semua</a>
            <?php foreach (config('kelas_list', ['S5']) as $k): ?>
                <a href="/dosen/mahasiswa?kelas=<?= e($k) ?>" class="btn btn-sm <?= $filterKelas === $k ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= e($k) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Table -->
<div class="row">
    <div class="col-12">
        <div class="glass-card">
            <div class="card-header d-flex justify-content-between align-items-center py-2 px-3 px-md-4">
                <h4 class="card-title mb-0 fw-bold d-flex align-items-center gap-2">
                    <i class="ti ti-list text-primary"></i>
                    <span>Daftar Mahasiswa <?= $filterKelas ? '— Kelas ' . e($filterKelas) : '' ?></span>
                </h4>
                <span class="badge bg-secondary"><?= count($mahasiswaList) ?> Mahasiswa</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($mahasiswaList)): ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="ti ti-users-minus display-4 mb-2 d-block opacity-50"></i>
                        <h4 class="fw-bold">Belum ada data mahasiswa.</h4>
                        <a href="/dosen/mahasiswa/tambah" class="btn btn-primary btn-sm mt-2">
                            <i class="ti ti-user-plus me-1"></i> Tambah Mahasiswa
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Mobile Cards -->
                    <div class="d-block d-md-none">
                        <div class="mobile-card-list">
                            <?php foreach ($mahasiswaList as $mhs): ?>
                                <div class="mobile-card-item">
                                    <div class="item-header">
                                        <span class="badge bg-primary"><?= e($mhs['kelas']) ?></span>
                                        <?php if ($mhs['wajib_ganti_password']): ?>
                                            <span class="badge bg-warning"><i class="ti ti-key me-1"></i>Wajib Ganti Password</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-title"><?= e($mhs['nama']) ?></div>
                                    <div class="small text-secondary mb-2"><?= e($mhs['nim']) ?></div>
                                    <div class="item-action gap-1">
                                        <a href="/dosen/mahasiswa/<?= $mhs['id'] ?>/edit" class="btn btn-outline-primary btn-sm flex-fill">
                                            <i class="ti ti-edit me-1"></i> Edit
                                        </a>
                                        <form action="/dosen/mahasiswa/<?= $mhs['id'] ?>/hapus" method="POST" class="d-inline flex-fill" onsubmit="return confirm('Hapus mahasiswa <?= e(addslashes($mhs['nama'])) ?>? Semua data pengumpulan juga akan dihapus.')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                                <i class="ti ti-trash me-1"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Desktop Table -->
                    <div class="d-none d-md-block">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3" style="width: 50px;">#</th>
                                        <th style="width: 190px;">NIM</th>
                                        <th>Nama Mahasiswa</th>
                                        <th style="width: 100px;" class="text-center">Kelas</th>
                                        <th style="width: 170px;" class="text-center">Status Password</th>
                                        <th class="text-end pe-3" style="width: 190px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mahasiswaList as $i => $mhs): ?>
                                        <tr>
                                            <td class="ps-3 text-secondary small"><?= $i + 1 ?></td>
                                            <td><code><?= e($mhs['nim']) ?></code></td>
                                            <td><div class="fw-bold"><?= e($mhs['nama']) ?></div></td>
                                            <td class="text-center">
                                                <span class="badge bg-primary"><?= e($mhs['kelas']) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($mhs['wajib_ganti_password']): ?>
                                                    <span class="badge bg-warning"><i class="ti ti-key me-1"></i>Wajib Ganti</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><i class="ti ti-check me-1"></i>OK</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-3">
                                                <a href="/dosen/mahasiswa/<?= $mhs['id'] ?>/edit" class="btn btn-outline-primary btn-sm py-1 px-2" title="Edit data">
                                                    <i class="ti ti-edit me-1"></i> Edit
                                                </a>
                                                <form action="/dosen/mahasiswa/<?= $mhs['id'] ?>/hapus" method="POST" class="d-inline" onsubmit="return confirm('Hapus mahasiswa <?= e(addslashes($mhs['nama'])) ?>?\nSemua data pengumpulan juga akan dihapus!')">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2" title="Hapus mahasiswa">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </form>
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

<?php
$body = ob_get_clean();
Flight::render('layout', ['body' => $body, 'title' => $title ?? 'Kelola Mahasiswa']);
?>

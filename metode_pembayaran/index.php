<?php
session_start();
require_once __DIR__ . '/../koneksi.php';
$title = 'Metode Pembayaran';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';

$search = mysqli_real_escape_string($koneksi, $_GET['search'] ?? '');
$filter_tipe = mysqli_real_escape_string($koneksi, $_GET['tipe'] ?? '');
$filter_status = $_GET['status'] ?? '';

$where_clauses = ["is_active = 1"];
if (!empty($search)) {
    $where_clauses[] = "(nama_metode LIKE '%$search%' OR keterangan LIKE '%$search%')";
}
if (!empty($filter_tipe)) {
    $where_clauses[] = "tipe_metode = '$filter_tipe'";
}
if ($filter_status === 'nonaktif') {
    $where_clauses = ["is_active = 0"];
} elseif ($filter_status === 'aktif') {
    $where_clauses[] = "is_active = 1";
}
$where_sql = implode(' AND ', $where_clauses);

$query = "SELECT * FROM metode_pembayaran WHERE $where_sql ORDER BY urutan ASC, id_metode ASC";
$result = mysqli_query($koneksi, $query);
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-sm-12">
                <div class="home-tab">
                    <div class="d-sm-flex align-items-center justify-content-between border-bottom mb-3">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-selected="true">Manajemen Metode Pembayaran</a>
                            </li>
                        </ul>
                        <div>
                            <div class="btn-wrapper">
                                <a href="tambah.php" class="btn btn-primary text-white me-0">
                                    <i class="mdi mdi-plus-box"></i> Tambah Metode
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- FILTER -->
                    <div class="card card-rounded mb-3">
                        <div class="card-body py-3">
                            <form method="GET" action="index.php" class="row g-2 align-items-center">
                                <div class="col-sm-3">
                                    <input type="text" name="search" class="form-control" placeholder="Cari Nama atau Keterangan..." value="<?= htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-sm-3">
                                    <select name="tipe" class="form-select">
                                        <option value="">-- Semua Tipe --</option>
                                        <option value="QRIS" <?= $filter_tipe == 'QRIS' ? 'selected' : ''; ?>>QRIS</option>
                                        <option value="Transfer Bank" <?= $filter_tipe == 'Transfer Bank' ? 'selected' : ''; ?>>Transfer Bank</option>
                                        <option value="E-Wallet" <?= $filter_tipe == 'E-Wallet' ? 'selected' : ''; ?>>E-Wallet</option>
                                        <option value="Tunai" <?= $filter_tipe == 'Tunai' ? 'selected' : ''; ?>>Tunai</option>
                                    </select>
                                </div>
                                <div class="col-sm-2">
                                    <select name="status" class="form-select">
                                        <option value="">-- Status --</option>
                                        <option value="aktif" <?= $filter_status == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="nonaktif" <?= $filter_status == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                                    </select>
                                </div>
                                <div class="col-sm-2 d-flex gap-1">
                                    <button type="submit" class="btn btn-dark text-white w-100">Filter</button>
                                    <a href="index.php" class="btn btn-light">Reset</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- TABEL -->
                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card card-rounded shadow-sm">
                                <div class="card-body">
                                    <h4 class="card-title card-title-dash">Daftar Metode Pembayaran</h4>
                                    <div class="table-responsive mt-1">
                                        <table class="table table-hover table-sm" style="font-size:14px;">
                                            <thead>
                                                <tr>
                                                    <th style="width: 80px;">Gambar</th>
                                                    <th>Nama Metode</th>
                                                    <th>Tipe</th>
                                                    <th>Keterangan</th>
                                                    <th>Urutan</th>
                                                    <th>Status</th>
                                                    <th class="text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (mysqli_num_rows($result) > 0): ?>
                                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                                        <tr>
                                                            <td>
                                                                <?php if (!empty($row['gambar_metode'])): ?>
                                                                    <img src="../uploads/metode_pembayaran/<?= $row['gambar_metode']; ?>" 
                                                                         alt="Gambar" 
                                                                         style="width: 50px; height: 50px; object-fit: contain; border-radius: 4px; border: 1px solid #eef2f5; background-color: #fff;">
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><strong><?= htmlspecialchars($row['nama_metode']); ?></strong></td>
                                                            <td><span class="badge bg-info"><?= htmlspecialchars($row['tipe_metode']); ?></span></td>
                                                            <td><?= htmlspecialchars(substr($row['keterangan'] ?? '-', 0, 50)) . (strlen($row['keterangan'] ?? '') > 50 ? '...' : ''); ?></td>
                                                            <td><?= $row['urutan']; ?></td>
                                                            <td>
                                                                <?php if ($row['is_active'] == 1): ?>
                                                                    <span class="badge bg-success">Aktif</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-danger">Nonaktif</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <div class="d-flex justify-content-center gap-1">
                                                                    
                                                                    <a href="edit.php?id=<?= $row['id_metode']; ?>" class="btn btn-warning btn-sm text-white py-1 px-2">Edit</a>
                                                                    <?php if ($row['is_active'] == 1): ?>
                                                                        <form method="POST" action="proses.php" class="d-inline mb-0" onsubmit="return confirm('Nonaktifkan metode ini?');">
                                                                            <input type="hidden" name="action" value="delete">
                                                                            <input type="hidden" name="id_metode" value="<?= $row['id_metode']; ?>">
                                                                            <button type="submit" class="btn btn-danger btn-sm text-white py-1 px-2">Nonaktif</button>
                                                                        </form>
                                                                    <?php else: ?>
                                                                        <form method="POST" action="proses.php" class="d-inline mb-0" onsubmit="return confirm('Aktifkan kembali metode ini?');">
                                                                            <input type="hidden" name="action" value="restore">
                                                                            <input type="hidden" name="id_metode" value="<?= $row['id_metode']; ?>">
                                                                            <button type="submit" class="btn btn-success btn-sm text-white py-1 px-2">Aktifkan</button>
                                                                        </form>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center py-3">
                                                            <p class="text-muted">Belum ada metode pembayaran.</p>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
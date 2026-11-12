<?php
session_start();
require_once __DIR__ . '/../koneksi.php';
$title = 'Data Kandang';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';

$search = mysqli_real_escape_string($koneksi, $_GET['search'] ?? '');
$filter_tipe = mysqli_real_escape_string($koneksi, $_GET['tipe_hewan'] ?? '');
$filter_status = $_GET['status'] ?? '';

$where_clauses = ["is_active = 1"];
if (!empty($search)) {
    $where_clauses[] = "(kode_kandang LIKE '%$search%' OR nama_kandang LIKE '%$search%')";
}
if (!empty($filter_tipe)) {
    $where_clauses[] = "tipe_hewan = '$filter_tipe'";
}
$where_sql = implode(' AND ', $where_clauses);

$query_kandang = "SELECT * FROM kandang WHERE $where_sql ORDER BY kode_kandang ASC";
$result_kandang = mysqli_query($koneksi, $query_kandang);
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-sm-12">
                <div class="home-tab">
                    <div class="d-sm-flex align-items-center justify-content-between border-bottom mb-3">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-selected="true">Manajemen Kandang</a>
                            </li>
                        </ul>
                        <div>
                            <div class="btn-wrapper">
                                <a href="tambah.php" class="btn btn-primary text-white me-0">
                                    <i class="mdi mdi-plus-box"></i> Tambah Kandang Baru
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card card-rounded mb-3">
                        <div class="card-body py-3">
                            <form method="GET" action="index.php" class="row g-2 align-items-center">
                                <div class="col-sm-4">
                                    <input type="text" name="search" class="form-control" placeholder="Cari Kode atau Nama Kandang..." value="<?= htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-sm-3">
                                    <select name="tipe_hewan" class="form-select">
                                        <option value="">-- Semua Jenis Hewan --</option>
                                        <option value="Anjing" <?= $filter_tipe == 'Anjing' ? 'selected' : ''; ?>>Anjing</option>
                                        <option value="Kucing" <?= $filter_tipe == 'Kucing' ? 'selected' : ''; ?>>Kucing</option>
                                    </select>
                                </div>
                                <div class="col-sm-3">
                                    <select name="status" class="form-select">
                                        <option value="">-- Semua Status --</option>
                                        <option value="Tersedia" <?= $filter_status == 'Tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                                        <option value="Penuh" <?= $filter_status == 'Penuh' ? 'selected' : ''; ?>>Penuh</option>
                                    </select>
                                </div>
                                <div class="col-sm-2 d-flex gap-1">
                                    <button type="submit" class="btn btn-dark text-white w-100">Filter</button>
                                    <a href="index.php" class="btn btn-light">Reset</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card card-rounded">
                                <div class="card-body">
                                    <h4 class="card-title card-title-dash">Daftar Slot Kandang Aktif</h4>
                                    <div class="table-responsive mt-1">
                                        <table class="table table-hover table-sm" style="font-size:14px;">
                                            <thead>
                                                <tr>
                                                    <th style="width: 80px;">Foto</th>
                                                    <th>Kode</th>
                                                    <th>Nama Kandang</th>
                                                    <th>Jenis</th>
                                                    <th>Kapasitas / Stok</th>
                                                    <th>Tarif / Hari</th>
                                                    <th>Status</th>
                                                    <th class="text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                while ($row = mysqli_fetch_assoc($result_kandang)):
                                                    // Kalkulasi ketersediaan slot
                                                    $id_kd = $row['id_kandang'];
                                                    $today = date('Y-m-d');
                                                    $query_occ = "SELECT COUNT(*) as terpakai FROM penitipan_hewan ph 
                  JOIN penitipan p ON ph.id_penitipan = p.id_penitipan
                  WHERE ph.id_kandang = $id_kd 
                  AND p.status_penitipan IN ('Disetujui', 'Sedang Dititipkan')
                  AND '$today' BETWEEN p.tgl_mulai AND p.tgl_selesai";
                                                    $res_occ = mysqli_query($koneksi, $query_occ);
                                                    $occ_data = mysqli_fetch_assoc($res_occ);
                                                    $slot_terpakai = (int)($occ_data['terpakai'] ?? 0);

                                                    // Hitung total unit riil yang lu miliki
                                                    $stok_fisik = (int)$row['stok'];

                                                    // Sisa unit kandang yang belum terpakai hari ini
                                                    $sisa_stok_tampil = $stok_fisik - $slot_terpakai;
                                                    if ($sisa_stok_tampil < 0) {
                                                        $sisa_stok_tampil = 0;
                                                    }

                                                    $total_kapasitas_maksimal = $row['kapasitas'] * $stok_fisik;
                                                    $status_real = ($slot_terpakai >= $total_kapasitas_maksimal) ? 'Penuh' : 'Tersedia';

                                                    if (!empty($filter_status) && $filter_status !== $status_real) {
                                                        continue;
                                                    }

                                                    // Perbaikan Logika Path Gambar Placeholder
                                                    $gambar_path = '../uploads/kandang/' . $row['foto_kandang'];
                                                    if (!empty($row['foto_kandang'])) {
                                                        $source_img = $gambar_path;
                                                    } else {
                                                        $source_img = 'https://via.placeholder.com/60x60/f8f9fa/6c757d?text=No+Image';
                                                    }
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <img src="<?= $source_img; ?>" alt="Foto" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #eef2f5; display: block;">
                                                        </td>
                                                        <td>
                                                            <h6 class="mb-0 fw-bold"><?= htmlspecialchars($row['kode_kandang']); ?></h6>
                                                        </td>
                                                        <td>
                                                            <h6 class="mb-0"><?= htmlspecialchars($row['nama_kandang']); ?></h6>
                                                        </td>
                                                        <td><span class="text-muted"><?= htmlspecialchars($row['tipe_hewan']); ?></span></td>
                                                        <td>
                                                            <h6 class="mb-0">Kapasitas: <?= $row['kapasitas']; ?> Ekor/Unit</h6>
                                                            <small class="text-muted">Sisa Stok: <?= $sisa_stok_tampil; ?> / <?= $stok_fisik; ?> Unit</small>
                                                        </td>
                                                        <td>
                                                            <h6 class="mb-0 fw-bold text-dark">Rp <?= number_format($row['tarif_per_hari'], 0, ',', '.'); ?></h6>
                                                        </td>
                                                        <td>
                                                            <?php if ($status_real === 'Penuh'): ?>
                                                                <span class="badge bg-opacity-danger  bg-danger py-1 px-2">Penuh</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-opacity-success  bg-success py-1 px-2">Tersedia</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="d-flex justify-content-center gap-1">
                                                                <a href="detail.php?id=<?= $row['id_kandang']; ?>" class="btn btn-info btn-sm text-white py-1 px-2">Detail</a>
                                                                <a href="edit.php?id=<?= $row['id_kandang']; ?>" class="btn btn-warning btn-sm text-white py-1 px-2">Edit</a>
                                                                <form method="POST" action="proses.php" class="d-inline mb-0" onsubmit="return confirm('Apakah Anda yakin?');">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input type="hidden" name="id_kandang" value="<?= $row['id_kandang']; ?>">
                                                                    <button type="submit" class="btn btn-danger btn-sm text-white py-1 px-2">Hapus</button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
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
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';
$title = 'Detail Kandang';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';

$id_kandang = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$query = "SELECT * FROM kandang WHERE id_kandang = $id_kandang AND is_active = 1";
$res = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($res);


// --- TAMBAHKAN LOGIKA KALKULASI INI ---
$today = date('Y-m-d');
$query_occ = "SELECT COUNT(*) as terpakai FROM penitipan_hewan ph 
              JOIN penitipan p ON ph.id_penitipan = p.id_penitipan
              WHERE ph.id_kandang = $id_kandang 
              AND p.status_penitipan IN ('Disetujui', 'Sedang Dititipkan')
              AND '$today' BETWEEN p.tgl_mulai AND p.tgl_selesai";
$res_occ = mysqli_query($koneksi, $query_occ);
$occ_data = mysqli_fetch_assoc($res_occ);
$slot_terpakai = (int)($occ_data['terpakai'] ?? 0);

// Hitung sisa stok yang tersedia
$sisa_stok_tampil = (int)$data['stok'] - $slot_terpakai;
if ($sisa_stok_tampil < 0) {
    $sisa_stok_tampil = 0;
}
// --------------------------------------

if (!$data) {
    echo "<script>alert('Kandang tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

// Konfigurasi Path Gambar Pratinjau Detail
$gambar_path = '../uploads/kandang/' . $data['foto_kandang'];
if (!empty($data['foto_kandang']) && file_exists(__DIR__ . '/' . $gambar_path)) {
    $source_img = $gambar_path;
} else {
    $source_img = 'https://via.placeholder.com/500x400/f8f9fa/6c757d?text=Foto+Kandang+Belum+Ada';
}
?>
<div class="main-panel">
    <div class="content-wrapper">
        <div class="row d-flex align-items-stretch">
            <div class="col-md-5 grid-margin stretch-card">
                <div class="card card-rounded w-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h4 class="card-title card-title-dash mb-3">Foto Pratinjau</h4>
                            <div class="w-100 border rounded bg-light" style="height: 320px; overflow: hidden;">
                                <img src="<?= $source_img; ?>" alt="Gambar Unit" style="width: 100%; height: 100%; object-fit: contain; background-color: #f8f9fa;">
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="text-small text-muted d-block text-center"><i class="mdi mdi-information-outline"></i> Tampilan perspektif fisik unit kandang aktif.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-7 grid-margin stretch-card">
                <div class="card card-rounded w-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h4 class="card-title card-title-dash mb-3">Informasi Detail Spesifikasi</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0" style="table-layout: fixed; width: 100%;">
                                    <tbody>
                                        <tr>
                                            <th style="width: 35%;" class="fw-bold bg-light">Kode Kandang</th>
                                            <td class="fw-bold text-primary"><?= htmlspecialchars($data['kode_kandang']); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="fw-bold bg-light">Nama Kandang</th>
                                            <td><?= htmlspecialchars($data['nama_kandang']); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="fw-bold bg-light">Peruntukan Hewan</th>
                                            <td><?= htmlspecialchars($data['tipe_hewan']); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="fw-bold bg-light">Ukuran Kandang</th>
                                            <td><span class="badge bg-info text-white"><?= htmlspecialchars($data['ukuran']); ?></span></td>
                                        </tr>
                                        <tr>
                                            <th class="fw-bold bg-light">Kapasitas Ekor</th>
                                            <td><?= $data['kapasitas']; ?> Ekor / unit</td>
                                        </tr>
                                        <tr>
                                            <th class="fw-bold bg-light">Stok Fisik Aset</th>
                                            <td><?= $data['stok']; ?> Unit Kandang</td>
                                        </tr>
                                        <tr>
                                            <th class="fw-bold bg-light">Ketersediaan Saat Ini</th>
                                            <td>
                                                <?php if ($sisa_stok_tampil > 0): ?>
                                                    <span class="text-success fw-bold"><?= $sisa_stok_tampil; ?> unit tersedia</span>
                                                <?php else: ?>
                                                    <span class="text-danger fw-bold">Semua unit sedang terpakai</span>
                                                <?php endif; ?>
                                                <br>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="fw-bold bg-light">Tarif Sewa Harian</th>
                                            <td class="fw-bold text-dark">Rp <?= number_format($data['tarif_per_hari'], 0, ',', '.'); ?> / Hari</td>
                                        </tr>
                                        <tr>
                                            <th class="fw-bold bg-light">Fasilitas Interior</th>
                                            <td style="word-break: break-word; white-space: normal; line-height: 1.5;">
                                                <?= nl2br(htmlspecialchars($data['fasilitas'] ?: '-')); ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="mt-4 d-flex gap-2">
                            <a href="index.php" class="btn btn-light px-4">Kembali ke Daftar</a>
                            <a href="edit.php?id=<?= $data['id_kandang']; ?>" class="btn btn-warning text-white px-4">Ubah Data</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
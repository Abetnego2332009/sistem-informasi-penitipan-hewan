<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

$title = 'Detail Kandang';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';

$id_kandang = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$id_user = $_SESSION['id_user'] ?? 0;
$session_id = session_id();

// ============================================================
// HAPUS LOCK YANG EXPIRED
// ============================================================
mysqli_query($koneksi, "DELETE FROM booking_lock WHERE expires_at < NOW()");

// ============================================================
// AMBIL DATA KANDANG
// ============================================================
$query = "SELECT * FROM kandang WHERE id_kandang = $id_kandang AND is_active = 1";
$res = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($res);

if (!$data) {
    echo "<script>alert('Data kandang tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

// ============================================================
// HITUNG SISA UNIT (STOK - TOTAL LOCK SEMUA USER)
// ============================================================
$stok_fisik = (int)$data['stok'];

// Total lock semua user (SUM quantity)
$q_total = mysqli_query($koneksi, "
    SELECT SUM(quantity) AS total_lock FROM booking_lock 
    WHERE id_kandang = $id_kandang 
    AND status IN ('pending_hewan','pending_pembayaran') 
    AND expires_at > NOW()
");
$row_total = mysqli_fetch_assoc($q_total);
$total_lock = (int)($row_total['total_lock'] ?? 0);

// Lock milik user ini (SUM quantity)
$q_user = mysqli_query($koneksi, "
    SELECT SUM(quantity) AS lock_user FROM booking_lock 
    WHERE id_kandang = $id_kandang 
    AND (session_id = '$session_id' OR id_user = $id_user) 
    AND status IN ('pending_hewan','pending_pembayaran') 
    AND expires_at > NOW()
");
$row_user = mysqli_fetch_assoc($q_user);
$lock_user = (int)($row_user['lock_user'] ?? 0);

$sisa_unit = $stok_fisik - $total_lock;

$foto = !empty($data['foto_kandang']) ? '../uploads/kandang/' . $data['foto_kandang'] : '../assets/images/no-image.jpg';
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-5 grid-margin stretch-card">
                <div class="card card-rounded shadow-sm">
                    <div class="card-body text-center">
                        <h4 class="card-title text-start fw-bold">Foto Kandang</h4>
                        <div class="border rounded bg-light mb-3" style="width: 100%; height: 260px; overflow: hidden;">
                            <img src="<?= htmlspecialchars($foto); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                        <a href="index.php" class="btn btn-light w-100 py-2"><i class="mdi mdi-arrow-left"></i> Kembali ke Daftar</a>
                    </div>
                </div>
            </div>

            <div class="col-md-7 grid-margin stretch-card">
                <div class="card card-rounded shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title fw-bold">Informasi & Spesifikasi Fasilitas</h4>
                        <table class="table table-bordered">
                            <tr>
                                <th width="30%">Kode Kandang</th>
                                <td><strong class="text-primary"><?= htmlspecialchars($data['kode_kandang']); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Nama Kandang</th>
                                <td><?= htmlspecialchars($data['nama_kandang']); ?></td>
                            </tr>
                            <tr>
                                <th>Khusus Hewan</th>
                                <td><span class="badge bg-info text-white"><?= htmlspecialchars($data['tipe_hewan']); ?></span></td>
                            </tr>
                            <tr>
                                <th>Ukuran Unit</th>
                                <td><?= htmlspecialchars($data['ukuran']); ?></td>
                            </tr>
                            <tr>
                                <th>Kapasitas Tampung</th>
                                <td><?= htmlspecialchars($data['kapasitas']); ?> Ekor / Unit</td>
                            </tr>
                            <tr>
                                <th>Stok Fisik Klinik</th>
                                <td><?= htmlspecialchars($data['stok']); ?> Unit Kandang</td>
                            </tr>
                            <tr>
                                <th>Ketersediaan Hari Ini</th>
                                <td>
                                    <?php if ($sisa_unit > 0): ?>
                                        <span class="text-success fw-bold">✅ Tersedia <?= $sisa_unit; ?> Unit</span>
                                        <?php if ($total_lock > 0): ?>
                                           <br> <br><small class="text-warning">🔒 <?= $total_lock; ?> unit sedang dipesan user lain</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-danger fw-bold">❌ Full / Habis (Semua Terpakai)</span>
                                        <?php if ($total_lock > 0): ?>
                                            <br> <br><small class="text-warning">🔒 <?= $total_lock; ?> unit sedang dipesan user lain</small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($lock_user > 0): ?>
                                         <br><br><small class="text-info">🔓 Anda Sedang Memesan  untuk kandang ini</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Tarif Sewa Harian</th>
                                <td><strong class="text-success">Rp <?= number_format($data['tarif_per_hari'], 0, ',', '.'); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Deskripsi Fasilitas</th>
                                <td><?= !empty($data['fasilitas']) ? nl2br(htmlspecialchars($data['fasilitas'])) : '-'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
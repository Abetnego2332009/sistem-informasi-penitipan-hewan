<?php

/**
 * detail.php - Halaman Detail Pembayaran
 * Menampilkan informasi lengkap pembayaran berdasarkan id_penitipan
 */

session_start();
require_once __DIR__ . '/../koneksi.php';

// ============================================================
// 1. VALIDASI SESSION
// ============================================================
if (!isset($_SESSION['id_user'])) {
    echo "<script>alert('Anda harus login terlebih dahulu!'); window.location.href='../login.php';</script>";
    exit;
}
$id_user = $_SESSION['id_user'];

// ============================================================
// 2. AMBIL ID PENITIPAN DARI URL
// ============================================================
$id_penitipan = filter_var($_GET['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);

if (!$id_penitipan) {
    echo "<script>alert('ID Booking tidak valid!'); window.location.href='../bookingan_saya/index.php';</script>";
    exit;
}

// ============================================================
// 3. CEK KEPEMILIKAN BOOKING & AMBIL DATA PEMBAYARAN
// ============================================================
$query = "
    SELECT 
        p.*,
        pay.id_pembayaran,
        pay.kode_pembayaran,
        pay.tgl_bayar,
        pay.metode_bayar,
        pay.nama_pembayar,
        pay.jumlah_tagihan,
        pay.jumlah_bayar,
        pay.bukti_pembayaran,
        pay.status_bayar,
        pay.catatan,
        pay.created_at AS tgl_pembayaran_dibuat,
        u.nama_lengkap AS nama_user,
        rp.id_perpanjangan,
        rp.tgl_selesai_lama,
        rp.tgl_selesai_baru,
        rp.biaya_tambahan,
        rp.status AS status_perpanjangan,
        rp.nama_pembayar AS nama_pembayar_perpanjangan,
        rp.jumlah_bayar AS jumlah_bayar_perpanjangan,
        rp.bukti_pembayaran AS bukti_perpanjangan
    FROM penitipan p
    LEFT JOIN pembayaran pay ON p.id_penitipan = pay.id_penitipan
    LEFT JOIN users u ON p.id_user = u.id_user
    LEFT JOIN riwayat_perpanjangan rp ON p.id_penitipan = rp.id_penitipan
    WHERE p.id_penitipan = ? AND p.id_user = ?
";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, 'ii', $id_penitipan, $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data) {
    echo "<script>alert('Data pembayaran tidak ditemukan atau Anda tidak memiliki akses!'); window.location.href='../bookingan_saya/index.php';</script>";
    exit;
}

// Jika belum ada pembayaran
if (empty($data['kode_pembayaran'])) {
    echo "<script>alert('Belum ada pembayaran untuk booking ini!'); window.location.href='../bookingan_saya/index.php';</script>";
    exit;
}

// ============================================================
// 4. AMBIL DETAIL KANDANG
// ============================================================
// ============================================================
// 4. AMBIL DETAIL KANDANG (GROUP BY KANDANG)
// ============================================================
$query_kandang = "
    SELECT 
        k.id_kandang,
        k.kode_kandang,
        k.nama_kandang,
        k.tarif_per_hari,
        MIN(ph.tgl_mulai) AS tgl_mulai,
        MAX(ph.tgl_selesai) AS tgl_selesai,
        COUNT(ph.id_hewan) AS jumlah_hewan,
        GROUP_CONCAT(h.nama_hewan SEPARATOR ', ') AS daftar_hewan,
        DATEDIFF(MAX(ph.tgl_selesai), MIN(ph.tgl_mulai)) AS durasi_hari
    FROM penitipan_hewan ph
    JOIN kandang k ON ph.id_kandang = k.id_kandang
    LEFT JOIN hewan h ON ph.id_hewan = h.id_hewan
    WHERE ph.id_penitipan = ?
    GROUP BY k.id_kandang
    ORDER BY k.kode_kandang ASC
";
$stmt_kandang = mysqli_prepare($koneksi, $query_kandang);
mysqli_stmt_bind_param($stmt_kandang, 'i', $id_penitipan);
mysqli_stmt_execute($stmt_kandang);
$result_kandang = mysqli_stmt_get_result($stmt_kandang);
$detail_kandang = [];
while ($row = mysqli_fetch_assoc($result_kandang)) {
    $detail_kandang[] = $row;
}
mysqli_stmt_close($stmt_kandang);

// ============================================================
// 5. TITLE & HEADER
// ============================================================
$title = 'Detail Pembayaran';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-8 grid-margin stretch-card mx-auto">
                <div class="card card-rounded shadow-sm">
                    <div class="card-body">
                        <!-- ============================================================ -->
                        <!-- HEADER: JUDUL + TOMBOL CETAK + KEMBALI                       -->
                        <!-- ============================================================ -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title text-primary mb-0 d-flex align-items-center">
                                <i class="mdi mdi-credit-card me-2"></i> Detail Pembayaran
                                <!-- ICON CATATAN -->
                                <i class="mdi mdi-information-outline ms-2 text-muted"
                                    style="font-size: 18px; cursor: pointer;"
                                    id="infoPembayaran"
                                    data-bs-toggle="popover"
                                    data-bs-html="true"
                                    data-bs-placement="bottom"
                                    data-bs-content='
                                     <strong>KETERANGAN</strong>
                                    <ol style="margin:0; padding-left:16px;">
                                        <li>Harap Tunjukkan Pembayaran Anda Ke Admin Ketika Mau Menitipkan Hewan</li>
                                        <li>Ini Bukti Bahwa Anda Telah Melakukan Transaksi Pembayaran Yang Sah</li>
                                    </ol>
                                '>
                                </i>
                            </h4>
                            <div class="d-flex gap-2">
                                <a href="cetak.php?id_penitipan=<?= $id_penitipan ?>" target="_blank" class="btn btn-success btn-sm">
                                    <i class="mdi mdi-printer"></i> Cetak
                                </a>
                                <a href="../bookingan_saya/index.php" class="btn btn-light btn-sm">
                                    <i class="mdi mdi-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="alert <?= $data['status_bayar'] == 'Lunas' ? 'alert-success' : 'alert-warning' ?> mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">
                                    <i class="mdi mdi-information-outline me-2"></i>
                                    Status Pembayaran:
                                </span>
                                <span class="badge <?= $data['status_bayar'] == 'Lunas' ? 'bg-success' : 'bg-warning text-dark' ?> p-2">
                                    <?= htmlspecialchars($data['status_bayar'] ?? 'Menunggu Konfirmasi') ?>
                                </span>
                            </div>
                        </div>

                        <!-- Info Pembayaran -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <tr>
                                    <td class="text-muted" style="width: 35%;">Kode Pembayaran</td>
                                    <td><strong><?= htmlspecialchars($data['kode_pembayaran']) ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Kode Booking</td>
                                    <td><strong><?= htmlspecialchars($data['kode_booking']) ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nama Pembayar</td>
                                    <td><?= htmlspecialchars($data['nama_pembayar'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Metode Pembayaran</td>
                                    <td><?= htmlspecialchars($data['metode_bayar'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tanggal Bayar</td>
                                    <td><?= !empty($data['tgl_bayar']) ? date('d M Y H:i:s', strtotime($data['tgl_bayar'])) : '-' ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Jumlah Tagihan</td>
                                    <td><strong class="text-danger">Rp <?= number_format($data['jumlah_tagihan'] ?? 0, 0, ',', '.') ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Jumlah Dibayar</td>
                                    <td><strong class="text-success">Rp <?= number_format($data['jumlah_bayar'] ?? 0, 0, ',', '.') ?></strong></td>
                                </tr>
                                <!-- Detail Penitipan -->
                                <tr>
                                    <td class="text-muted" style="width: 35%; vertical-align: top;">Detail Penitipan</td>
                                    <td>
                                        <?php if (count($detail_kandang) > 0): ?>
                                            <?php foreach ($detail_kandang as $dk):
                                                $durasi = ($dk['durasi_hari'] <= 0) ? 1 : $dk['durasi_hari'];
                                                $tgl_mulai = date('d M Y', strtotime($dk['tgl_mulai']));
                                                $tgl_selesai = date('d M Y', strtotime($dk['tgl_selesai']));
                                            ?>
                                                <div class="py-1 border-bottom">
                                                    <div class="d-flex justify-content-between mt-2">
                                                        <span><strong><?= htmlspecialchars($dk['kode_kandang']) ?></strong> - <?= htmlspecialchars($dk['nama_kandang']) ?></span>
                                                        <span><?= $durasi ?> hari (<?= $tgl_mulai ?> - <?= $tgl_selesai ?>)</span>
                                                    </div>
                                                    <div class="text-muted small mt-3" style="font-size: 12px;">
                                                        <i class="mdi mdi-paw"></i> <?= $dk['jumlah_hewan'] ?> hewan: <?= htmlspecialchars($dk['daftar_hewan']) ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            <div class="text-end mt-1">
                                                <small class="text-muted">Total: <?= count($detail_kandang) ?> kandang</small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Tidak ada data kandang</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="text-muted">Catatan</td>
                                    <td><?= htmlspecialchars($data['catatan'] ?? '-') ?></td>
                                </tr>
                            </table>
                        </div>

                        <!-- Bukti Transfer -->
                        <?php if (!empty($data['bukti_pembayaran']) && file_exists(__DIR__ . '/../' . $data['bukti_pembayaran'])): ?>
                            <div class="text-center mt-3">
                                <label class="fw-bold text-dark d-block mb-2">Bukti Transfer</label>
                                <img src="../<?= $data['bukti_pembayaran'] ?>" alt="Bukti Transfer" style="max-width: 100%; max-height: 300px; margin-top: 10px; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                <br><br>
                                <a href="../<?= $data['bukti_pembayaran'] ?>" target="_blank" class="btn btn-outline-primary">
                                    <i class="mdi mdi-file-image me-2"></i> Lihat Bukti Transfer
                                </a>
                            </div>
                        <?php elseif (!empty($data['bukti_pembayaran'])): ?>
                            <div class="text-center mt-3">
                                <label class="fw-bold text-dark d-block mb-2">Bukti Transfer</label>
                                <a href="../<?= $data['bukti_pembayaran'] ?>" target="_blank" class="btn btn-outline-primary">
                                    <i class="mdi mdi-file-image me-2"></i> Lihat Bukti Transfer
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center mt-3">
                                <span class="text-muted">Tidak ada bukti transfer (pembayaran tunai)</span>
                            </div>
                        <?php endif; ?>

                        <!-- Tombol Aksi -->
                        <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                            <a href="../bookingan_saya/index.php" class="btn btn-light">
                                <i class="mdi mdi-arrow-left"></i> Kembali ke Bookingan Saya
                            </a>
                            <?php if ($data['status_bayar'] == 'Menunggu Konfirmasi'): ?>
                                <span class="text-warning">
                                    <i class="mdi mdi-clock-outline"></i> Menunggu konfirmasi admin
                                </span>
                            <?php elseif ($data['status_bayar'] == 'Lunas'): ?>
                                <span class="text-success">
                                    <i class="mdi mdi-check-circle-outline"></i> Pembayaran telah dikonfirmasi
                                </span>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
<script>
    // ============================================================
    // POPOVER UNTUK ICON CATATAN
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl, {
                trigger: 'click', // muncul saat diklik
                html: true
            });
        });
    });
</script>
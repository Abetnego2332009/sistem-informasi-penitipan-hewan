<?php
/**
 * cetak.php - Halaman Cetak Detail Perpanjangan (Auto Print & Close)
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
// 3. AMBIL DATA PERPANJANGAN
// ============================================================
$query = "
    SELECT 
        rp.*,
        rp.status AS status_perpanjangan,
        rp.catatan,
        p.kode_booking,
        u.nama_lengkap AS nama_user,
        u.no_telp AS no_telp_user,
        u.alamat AS alamat_user,
        k.kode_kandang,
        k.nama_kandang
    FROM riwayat_perpanjangan rp
    JOIN penitipan p ON rp.id_penitipan = p.id_penitipan
    JOIN users u ON p.id_user = u.id_user
    JOIN kandang k ON rp.id_kandang = k.id_kandang
    WHERE rp.id_penitipan = ?
    ORDER BY rp.id_perpanjangan DESC
";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, 'i', $id_penitipan);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data || empty($data['id_perpanjangan'])) {
    echo "<script>alert('Data perpanjangan tidak ditemukan!'); window.location.href='../bookingan_saya/index.php';</script>";
    exit;
}

// Cek apakah ada bukti transfer
$bukti_path = !empty($data['bukti_pembayaran']) ? '../' . $data['bukti_pembayaran'] : null;
$ada_bukti = $bukti_path && file_exists(__DIR__ . '/../' . $data['bukti_pembayaran']);

// Status badge
$status_class = '';
if ($data['status'] == 'Disetujui') {
    $status_class = 'success';
} elseif ($data['status'] == 'Ditolak') {
    $status_class = 'danger';
} else {
    $status_class = 'warning';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Detail Perpanjangan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .print-container {
            max-width: 700px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header { text-align: center; }
        .logo {
            max-width: 100px;
            height: auto;
            margin-bottom: 8px;
            display: inline-block;
        }
        .nama-toko {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            letter-spacing: 1px;
        }
        .alamat-toko {
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 3px;
        }
        .garis {
            border: none;
            border-top: 2px solid #2c3e50;
            margin: 12px 0 15px 0;
        }
        .judul {
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .header-info .kiri { text-align: left; }
        .header-info .kanan { text-align: right; }
        .status-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-badge.success { background: #d4edda; color: #155724; }
        .status-badge.warning { background: #fff3cd; color: #856404; }
        .status-badge.danger { background: #f8d7da; color: #721c24; }
        .table-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .table-info td {
            padding: 6px 10px 6px 0;
            border-bottom: 1px dashed #e0e0e0;
            vertical-align: top;
        }
        .table-info .label {
            font-weight: 600;
            color: #555;
            width: 38%;
        }
        .table-info .value {
            color: #2c3e50;
            width: 62%;
        }
        .table-info .value.text-danger { color: #e74c3c; font-weight: 700; }
        .table-info .value.text-success { color: #27ae60; font-weight: 700; }
        .table-info .value.text-warning { color: #f39c12; font-weight: 700; }
        .bukti-transfer {
            text-align: center;
            margin: 15px 0;
        }
        .bukti-transfer img {
            max-width: 100%;
            max-height: 200px;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 5px;
            background: #fff;
        }
        .bukti-transfer .label-bukti {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
        }
        .footer .thanks {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .footer .dicetak {
            font-size: 12px;
            color: #7f8c8d;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .print-container { box-shadow: none; padding: 20px 30px; border-radius: 0; }
            .no-print { display: none !important; }
            .table-info td { border-bottom: 1px dashed #ccc; }
        }
    </style>
</head>
<body>
<div class="print-container" id="printArea">

    <!-- HEADER -->
    <div class="header">
        <div class="logo-container">
            <img src="../assets/assets/images/faces/face1.jpg" alt="Logo" style="width: 80px; height: 80px; margin: 0 auto; background: #2c3e50; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 36px; font-weight: bold; border: 3px solid #3498db;">
        </div>
        <div class="nama-toko">Njuah Vet</div>
        <div class="alamat-toko">Jl. Contoh No. 123, Pematangsiantar | 📞 (0622) 123456</div>
    </div>

    <hr class="garis">

    <!-- JUDUL -->
    <div class="judul">Detail Perpanjangan Masa Aktif</div>

    <!-- HEADER INFO -->
    <div class="header-info">
        <div class="kiri">
            <strong>Tanggal Perpanjangan:</strong> <?= date('d M Y', strtotime($data['tgl_perpanjangan'])) ?>
        </div>
        <div class="kanan">
            <span class="status-badge <?= $status_class ?>">
                <?= htmlspecialchars($data['status'] ?? 'Menunggu Konfirmasi') ?>
            </span>
        </div>
    </div>

    <!-- TABEL INFO -->
    <table class="table-info">
        <tr>
            <td class="label">Kode Booking</td>
            <td class="value"><?= htmlspecialchars($data['kode_booking']) ?></td>
        </tr>
        <tr>
            <td class="label">Pelanggan</td>
            <td class="value"><?= htmlspecialchars($data['nama_user'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="label">No. Telepon</td>
            <td class="value"><?= htmlspecialchars($data['no_telp_user'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="label">Kandang</td>
            <td class="value"><strong><?= htmlspecialchars($data['kode_kandang']) ?></strong> - <?= htmlspecialchars($data['nama_kandang']) ?></td>
        </tr>
        <tr>
            <td class="label">Tanggal Selesai Lama</td>
            <td class="value"><?= date('d M Y', strtotime($data['tgl_selesai_lama'])) ?></td>
        </tr>
        <tr>
            <td class="label">Tanggal Selesai Baru</td>
            <td class="value text-warning"><?= date('d M Y', strtotime($data['tgl_selesai_baru'])) ?></td>
        </tr>
        <tr>
            <td class="label">Biaya Tambahan</td>
            <td class="value text-danger">Rp <?= number_format($data['biaya_tambahan'] ?? 0, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td class="label">Nama Pembayar</td>
            <td class="value"><?= htmlspecialchars($data['nama_pembayar'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="label">Jumlah Dibayar</td>
            <td class="value text-success">Rp <?= number_format($data['jumlah_bayar'] ?? 0, 0, ',', '.') ?></td>
        </tr>
        <?php if (!empty($data['catatan'])): ?>
        <tr>
            <td class="label">Catatan</td>
            <td class="value"><?= nl2br(htmlspecialchars($data['catatan'])) ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <!-- BUKTI TRANSFER -->
    <?php if ($ada_bukti): ?>
    <div class="bukti-transfer">
        <span class="label-bukti">🖼️ Bukti Transfer</span>
        <img src="<?= $bukti_path ?>" alt="Bukti Transfer">
    </div>
    <?php elseif (!empty($data['bukti_pembayaran'])): ?>
    <div class="bukti-transfer">
        <span class="label-bukti">🖼️ Bukti Transfer</span>
        <p style="color:#999;font-size:13px;">File: <a href="<?= $bukti_path ?>" target="_blank"><?= basename($data['bukti_pembayaran']) ?></a></p>
    </div>
    <?php else: ?>
    <div class="bukti-transfer">
        <span class="label-bukti">🖼️ Bukti Transfer</span>
        <p style="color:#999;font-size:13px;">Tidak ada bukti transfer</p>
    </div>
    <?php endif; ?>

    <hr class="garis">

    <!-- FOOTER -->
    <div class="footer">
        <div class="thanks">Terima Kasih atas Kepercayaan Anda 🙏</div>
        <div class="dicetak">Dicetak pada: <?= date('d M Y H:i:s') ?></div>
    </div>

</div>

<script>
    window.onload = function() { window.print(); };
    window.onafterprint = function() { window.close(); };
</script>
</body>
</html>
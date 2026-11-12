<?php
/**
 * cetak.php - Halaman Cetak Detail Pembayaran (Auto Print & Close)
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
// 3. AMBIL DATA PEMBAYARAN
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
        u.no_telp AS no_telp_user,
        u.alamat AS alamat_user
    FROM penitipan p
    LEFT JOIN pembayaran pay ON p.id_penitipan = pay.id_penitipan
    LEFT JOIN users u ON p.id_user = u.id_user
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

if (empty($data['kode_pembayaran'])) {
    echo "<script>alert('Belum ada pembayaran untuk booking ini!'); window.location.href='../bookingan_saya/index.php';</script>";
    exit;
}

// ============================================================
// 4. AMBIL DETAIL KANDANG
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

// Cek apakah ada bukti transfer
$bukti_path = !empty($data['bukti_pembayaran']) ? '../' . $data['bukti_pembayaran'] : null;
$ada_bukti = $bukti_path && file_exists(__DIR__ . '/../' . $data['bukti_pembayaran']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Detail Pembayaran</title>
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
        /* ========== HEADER ========== */
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
        /* ========== JUDUL ========== */
        .judul {
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        /* ========== HEADER INFO ========== */
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
        .status-badge.info { background: #d1ecf1; color: #0c5460; }

        /* ========== TABEL INFO ========== */
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
        .table-info .value.text-primary { color: #3498db; font-weight: 700; }

        /* ========== TABEL KANDANG ========== */
        .table-kandang {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .table-kandang th {
            text-align: left;
            padding: 6px 8px;
            background: #f8f9fa;
            border-bottom: 2px solid #2c3e50;
            font-weight: 700;
            color: #2c3e50;
        }
        .table-kandang td {
            padding: 6px 8px;
            border-bottom: 1px dashed #e0e0e0;
        }
        .table-kandang .total {
            text-align: right;
            font-weight: 600;
            padding-top: 8px;
            border-top: 2px solid #2c3e50;
        }

        /* ========== BUKTI TRANSFER ========== */
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

        /* ========== FOOTER ========== */
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
            .table-kandang td { border-bottom: 1px dashed #ccc; }
            .bukti-transfer img { max-height: 150px; }
        }
    </style>
</head>
<body>
<div class="print-container" id="printArea">

    <!-- ========================================================= -->
    <!-- LOGO (Atas Tengah)                                        -->
    <!-- ========================================================= -->
    <div class="header">
        <div class="logo-container">
            <!-- TEMPAT LOGO (GANTI DENGAN FILE LOGO ASLI) -->
            <!-- <div style="width: 80px; height: 80px; margin: 0 auto; background: #2c3e50; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 36px; font-weight: bold; border: 3px solid #3498db;">
                N
            </div> -->
            <!-- <img src="../assets/images/logo.png" alt="Logo" class="logo"> -->
             <img src="<?= isset($base_url) ? $base_url : '../'; ?>assets/assets/images/faces/face1.jpg" alt="Logo SPPG" style="width: 80px; height: 80px; margin: 0 auto; background: #2c3e50; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 36px; font-weight: bold; border: 3px solid #3498db;">
        </div>
        <!-- NAMA TOKO -->
        <div class="nama-toko">Njuah Vet</div>
        <!-- ALAMAT -->
        <div class="alamat-toko">Jl. Contoh No. 123, Pematangsiantar | 📞 (0622) 123456</div>
    </div>

    <!-- GARIS PANJANG 1 -->
    <hr class="garis">

    <!-- ========================================================= -->
    <!-- JUDUL: Bukti Pembayaran (Tengah)                          -->
    <!-- ========================================================= -->
    <div class="judul">Bukti Pembayaran Penitipan</div>

    <!-- ========================================================= -->
    <!-- HEADER: Tanggal Booking (Kiri) | Status (Kanan)          -->
    <!-- ========================================================= -->
    <div class="header-info">
        <div class="kiri">
            <strong>Tanggal Booking:</strong> <?= date('d M Y H:i', strtotime($data['created_at'])) ?>
        </div>
        <div class="kanan">
            <span class="status-badge <?= $data['status_bayar'] == 'Lunas' ? 'success' : 'warning' ?>">
                <?= htmlspecialchars($data['status_bayar'] ?? 'Menunggu Konfirmasi') ?>
            </span>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- TABEL INFO PEMBAYARAN                                    -->
    <!-- ========================================================= -->
    <table class="table-info">
        <tr>
            <td class="label">Kode Pembayaran</td>
            <td class="value text-primary"><?= htmlspecialchars($data['kode_pembayaran']) ?></td>
        </tr>
        <tr>
            <td class="label">Kode Booking</td>
            <td class="value"><?= htmlspecialchars($data['kode_booking']) ?></td>
        </tr>
        <tr>
            <td class="label">Nama Pembayar</td>
            <td class="value"><?= htmlspecialchars($data['nama_pembayar'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="label">Metode Pembayaran</td>
            <td class="value"><?= htmlspecialchars($data['metode_bayar'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="label">Tanggal Bayar</td>
            <td class="value"><?= !empty($data['tgl_bayar']) ? date('d M Y H:i:s', strtotime($data['tgl_bayar'])) : '-' ?></td>
        </tr>
        <tr>
            <td class="label">Jumlah Tagihan</td>
            <td class="value text-danger">Rp <?= number_format($data['jumlah_tagihan'] ?? 0, 0, ',', '.') ?></td>
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

    <!-- ========================================================= -->
    <!-- TABEL DETAIL KANDANG                                     -->
    <!-- ========================================================= -->
    <table class="table-kandang">
        <thead>
            <tr>
                <th>Kandang</th>
                <th style="text-align:right;">Tanggal Penitipan</th>
            </tr>
        </thead>
      <tbody>
    <?php if (count($detail_kandang) > 0): ?>
        <?php foreach ($detail_kandang as $dk): 
            $durasi = ($dk['durasi_hari'] <= 0) ? 1 : $dk['durasi_hari'];
            $tgl_mulai = date('d M Y', strtotime($dk['tgl_mulai']));
            $tgl_selesai = date('d M Y', strtotime($dk['tgl_selesai']));
        ?>
        <tr>
            <td>
                <strong><?= htmlspecialchars($dk['kode_kandang']) ?></strong> - <?= htmlspecialchars($dk['nama_kandang']) ?>
                <br><small style="color:#666;">🐾 <?= $dk['jumlah_hewan'] ?> hewan: <?= htmlspecialchars($dk['daftar_hewan']) ?></small>
            </td>
            <td style="text-align:right;"><?= $tgl_mulai ?> - <?= $tgl_selesai ?> (<?= $durasi ?> hari)</td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="2" class="total">Total: <?= count($detail_kandang) ?> kandang</td>
        </tr>
    <?php else: ?>
        <tr><td colspan="2" style="color:#999;">Tidak ada data kandang</td></tr>
    <?php endif; ?>
</tbody>
    </table>

    <!-- ========================================================= -->
    <!-- BUKTI TRANSFER (KECIL)                                   -->
    <!-- ========================================================= -->
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
        <p style="color:#999;font-size:13px;">Tidak ada bukti transfer (pembayaran tunai)</p>
    </div>
    <?php endif; ?>

    <!-- GARIS PANJANG 2 -->
    <hr class="garis">

    <!-- ========================================================= -->
    <!-- FOOTER                                                    -->
    <!-- ========================================================= -->
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
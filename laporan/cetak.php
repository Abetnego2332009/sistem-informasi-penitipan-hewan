<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

// ============================================================
// CEK LOGIN
// ============================================================
if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

// ============================================================
// AMBIL PARAMETER
// ============================================================
$jenis_laporan = isset($_GET['jenis_laporan']) ? mysqli_real_escape_string($koneksi, $_GET['jenis_laporan']) : 'pelanggan';
$tgl_mulai     = !empty($_GET['tgl_mulai']) ? mysqli_real_escape_string($koneksi, $_GET['tgl_mulai']) : date('Y-m-d');
$tgl_selesai   = !empty($_GET['tgl_selesai']) ? mysqli_real_escape_string($koneksi, $_GET['tgl_selesai']) : date('Y-m-d', strtotime('+1 day'));
$format        = isset($_GET['format']) ? $_GET['format'] : 'print';

// ============================================================
// FUNGSI GET DATA LAPORAN (SAMA DENGAN INDEX.PHP)
// ============================================================
function getDataLaporan($koneksi, $jenis, $tgl_mulai, $tgl_selesai)
{
    $data = [];
    $total_footer = 0;
    $total_pendapatan = 0;

    switch ($jenis) {
        case 'pelanggan':
            $query = "
                SELECT 
                    COALESCE(u.nama_lengkap, p.nama_lengkap) AS nama_lengkap,
                    COALESCE(u.no_telp, p.no_telp) AS no_telp,
                    COUNT(pen.id_penitipan) AS jumlah_booking,
                    SUM(pen.total_biaya) AS total_pengeluaran
                FROM (
                    SELECT id_user AS id, nama_lengkap, no_telp, 'user' AS tipe
                    FROM users 
                    WHERE role = 'Pelanggan' AND status_akun = 'Aktif'
                    UNION ALL
                    SELECT id_pelanggan AS id, nama_lengkap, no_telp, 'offline' AS tipe
                    FROM pelanggan
                ) AS semua_pelanggan
                LEFT JOIN penitipan pen ON (
                    (semua_pelanggan.tipe = 'user' AND pen.id_user = semua_pelanggan.id) OR
                    (semua_pelanggan.tipe = 'offline' AND pen.id_pelanggan = semua_pelanggan.id)
                )
                LEFT JOIN users u ON (semua_pelanggan.tipe = 'user' AND u.id_user = semua_pelanggan.id)
                LEFT JOIN pelanggan p ON (semua_pelanggan.tipe = 'offline' AND p.id_pelanggan = semua_pelanggan.id)
                WHERE pen.tgl_booking BETWEEN '$tgl_mulai' AND '$tgl_selesai'
                GROUP BY semua_pelanggan.id, semua_pelanggan.tipe
                ORDER BY total_pengeluaran DESC
            ";
            $result = mysqli_query($koneksi, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                $row['nama_lengkap'] = $row['nama_lengkap'] ?? 'Tidak Diketahui';
                $row['no_telp'] = $row['no_telp'] ?? '-';
                $data[] = $row;
                $total_footer += $row['total_pengeluaran'];
            }
            break;

        case 'hewan':
            $query = "
                SELECT 
                    h.id_hewan,
                    h.nama_hewan,
                    h.jenis_hewan,
                    h.ras,
                    COALESCE(u.nama_lengkap, p.nama_lengkap) AS pemilik,
                    COUNT(ph.id_penitipan_hewan) AS jumlah_titip
                FROM hewan h
                LEFT JOIN penitipan_hewan ph ON h.id_hewan = ph.id_hewan
                LEFT JOIN penitipan pen ON ph.id_penitipan = pen.id_penitipan
                LEFT JOIN users u ON h.id_user = u.id_user
                LEFT JOIN pelanggan p ON h.id_pelanggan = p.id_pelanggan
                WHERE pen.tgl_booking BETWEEN '$tgl_mulai' AND '$tgl_selesai'
                GROUP BY h.id_hewan
                ORDER BY jumlah_titip DESC
            ";
            $result = mysqli_query($koneksi, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                $row['pemilik'] = $row['pemilik'] ?? 'Tidak Diketahui';
                $data[] = $row;
                $total_footer += $row['jumlah_titip'];
            }
            break;

       case 'booking':
    $query = "
        SELECT 
            pen.kode_booking,
            CASE 
                WHEN pen.tipe_pelanggan = 'offline' THEN p.nama_lengkap 
                ELSE u.nama_lengkap 
            END AS pelanggan,
            pen.tgl_mulai,
            pen.tgl_selesai,
            pen.total_biaya,
            pen.status_penitipan AS status
        FROM penitipan pen
        LEFT JOIN users u ON pen.id_user = u.id_user
        LEFT JOIN pelanggan p ON pen.id_pelanggan = p.id_pelanggan
        WHERE pen.tgl_booking BETWEEN '$tgl_mulai' AND '$tgl_selesai'
        ORDER BY pen.tgl_booking DESC
    ";
    $result = mysqli_query($koneksi, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $row['pelanggan'] = $row['pelanggan'] ?? 'Tidak Diketahui';
        $data[] = $row;
        $total_footer += $row['total_biaya'];
    }
    break;

        case 'pendapatan':
            $q_booking = "
                SELECT SUM(pay.jumlah_bayar) AS total_booking
                FROM pembayaran pay
                LEFT JOIN penitipan pen ON pay.id_penitipan = pen.id_penitipan
                WHERE pay.status_bayar = 'Lunas'
                AND pay.tgl_konfirmasi BETWEEN '$tgl_mulai' AND '$tgl_selesai'
            ";
            $res_booking = mysqli_query($koneksi, $q_booking);
            $row_booking = mysqli_fetch_assoc($res_booking);
            $total_booking = $row_booking['total_booking'] ?? 0;

            $q_perpanjangan = "
                SELECT SUM(rp.biaya_tambahan) AS total_perpanjangan
                FROM riwayat_perpanjangan rp
                WHERE rp.status = 'Disetujui'
                AND rp.created_at BETWEEN '$tgl_mulai' AND '$tgl_selesai'
            ";
            $res_perpanjangan = mysqli_query($koneksi, $q_perpanjangan);
            $row_perpanjangan = mysqli_fetch_assoc($res_perpanjangan);
            $total_perpanjangan = $row_perpanjangan['total_perpanjangan'] ?? 0;

            $total_pendapatan = $total_booking + $total_perpanjangan;

            $query = "
                SELECT 
                    pay.kode_pembayaran,
                    pay.tgl_konfirmasi AS tgl_bayar,
                    mp.nama_metode AS metode,
                    u.nama_lengkap AS pelanggan,
                    pay.jumlah_bayar,
                    pay.status_bayar AS status,
                    'Booking' AS sumber
                FROM pembayaran pay
                LEFT JOIN metode_pembayaran mp ON pay.id_metode = mp.id_metode
                LEFT JOIN penitipan pen ON pay.id_penitipan = pen.id_penitipan
                LEFT JOIN users u ON pen.id_user = u.id_user
                WHERE pay.status_bayar = 'Lunas'
                AND pay.tgl_konfirmasi BETWEEN '$tgl_mulai' AND '$tgl_selesai'
                
                UNION ALL
                
                SELECT 
                    rp.kode_pembayaran AS kode_pembayaran,
                    rp.created_at AS tgl_bayar, 
                    mp.nama_metode AS metode,
                    rp.nama_pembayar AS pelanggan,
                    rp.jumlah_bayar AS jumlah_bayar,
                    'Lunas' AS status,
                    'Perpanjangan' AS sumber
                FROM riwayat_perpanjangan rp
                LEFT JOIN metode_pembayaran mp ON rp.id_metode = mp.id_metode
                WHERE rp.status = 'Disetujui'
                AND rp.created_at BETWEEN '$tgl_mulai' AND '$tgl_selesai'
                ORDER BY tgl_bayar DESC
            ";
            $result = mysqli_query($koneksi, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
                $total_footer += $row['jumlah_bayar'];
            }
            break;

        case 'perpanjangan':
            $query = "
                SELECT 
                    rp.id_perpanjangan,
                    pen.kode_booking,
                    k.nama_kandang,
                    rp.tgl_selesai_lama,
                    rp.tgl_selesai_baru,
                    rp.biaya_tambahan,
                    rp.status
                FROM riwayat_perpanjangan rp
                LEFT JOIN penitipan pen ON rp.id_penitipan = pen.id_penitipan
                LEFT JOIN kandang k ON rp.id_kandang = k.id_kandang
                WHERE rp.tgl_perpanjangan BETWEEN '$tgl_mulai' AND '$tgl_selesai'
                ORDER BY rp.tgl_perpanjangan DESC
            ";
            $result = mysqli_query($koneksi, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
                if ($row['status'] == 'Disetujui') {
                    $total_footer += $row['biaya_tambahan'];
                }
            }
            break;

        default:
            break;
    }

    return ['data' => $data, 'total' => $total_footer, 'total_pendapatan' => $total_pendapatan];
}

$laporan = getDataLaporan($koneksi, $jenis_laporan, $tgl_mulai, $tgl_selesai);
$data = $laporan['data'];
$total_footer = $laporan['total'];
$total_pendapatan = $laporan['total_pendapatan'];

// ============================================================
// FUNGSI CETAK TABEL (UNTUK PRINT/PDF)
// ============================================================
function cetakTabel($jenis, $data, $total)
{
    if (empty($data)) {
        echo '<tr><td colspan="10" class="text-center">Tidak ada data</td></tr>';
        return;
    }

    $no = 1;
    foreach ($data as $row) {
        echo '<tr>';
        switch ($jenis) {
            case 'pelanggan':
                echo '<td class="text-center">' . $no++ . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_lengkap'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['no_telp'] ?? '') . '</td>';
                echo '<td class="text-center">' . $row['jumlah_booking'] . '</td>';
                echo '<td class="text-end">Rp ' . number_format($row['total_pengeluaran'], 0, ',', '.') . '</td>';
                break;
            case 'hewan':
                echo '<td class="text-center">' . $no++ . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_hewan'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['jenis_hewan'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['ras'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['pemilik'] ?? '') . '</td>';
                echo '<td class="text-center">' . $row['jumlah_titip'] . '</td>';
                break;
            case 'booking':
                echo '<td class="text-center">' . $no++ . '</td>';
                echo '<td><strong>' . htmlspecialchars($row['kode_booking'] ?? '') . '</strong></td>';
                echo '<td>' . htmlspecialchars($row['pelanggan'] ?? '') . '</td>';
                echo '<td>' . $row['tgl_mulai'] . '</td>';
                echo '<td>' . $row['tgl_selesai'] . '</td>';
                echo '<td class="text-end">Rp ' . number_format($row['total_biaya'], 0, ',', '.') . '</td>';
                echo '<td>' . $row['status'] . '</td>';
                break;
            case 'pendapatan':
                echo '<td class="text-center">' . $no++ . '</td>';
                echo '<td><strong>' . htmlspecialchars($row['kode_pembayaran'] ?? '') . '</strong></td>';
                echo '<td>' . ($row['tgl_bayar'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($row['metode'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($row['pelanggan'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($row['sumber'] ?? '') . '</td>';
                echo '<td class="text-end">Rp ' . number_format($row['jumlah_bayar'] ?? 0, 0, ',', '.') . '</td>';
                echo '<td>Lunas</td>';
                break;
            case 'perpanjangan':
                echo '<td class="text-center">' . $no++ . '</td>';
                echo '<td><strong>' . htmlspecialchars($row['kode_booking'] ?? '') . '</strong></td>';
                echo '<td>' . htmlspecialchars($row['nama_kandang'] ?? '') . '</td>';
                echo '<td>' . $row['tgl_selesai_lama'] . '</td>';
                echo '<td>' . $row['tgl_selesai_baru'] . '</td>';
                echo '<td class="text-end">Rp ' . number_format($row['biaya_tambahan'], 0, ',', '.') . '</td>';
                echo '<td>' . $row['status'] . '</td>';
                break;
            default:
                break;
        }
        echo '</tr>';
    }

    // Footer
    $colspan = 0;
    $label = '';
    switch ($jenis) {
        case 'pelanggan':
            $colspan = 3;
            $label = 'TOTAL';
            $total_value = 'Rp ' . number_format($total, 0, ',', '.');
            echo '<tr class="table-light fw-bold">';
            echo '<td colspan="' . $colspan . '" class="text-end">' . $label . '</td>';
            echo '<td class="text-center">' . array_sum(array_column($data, 'jumlah_booking')) . '</td>';
            echo '<td class="text-end">' . $total_value . '</td>';
            echo '</tr>';
            break;
        case 'hewan':
            $colspan = 5;
            $label = 'TOTAL TITIP';
            echo '<tr class="table-light fw-bold">';
            echo '<td colspan="' . $colspan . '" class="text-end">' . $label . '</td>';
            echo '<td class="text-center">' . array_sum(array_column($data, 'jumlah_titip')) . '</td>';
            echo '</tr>';
            break;
        case 'booking':
            $colspan = 5;
            $label = 'TOTAL BIAYA BOOKING';
            echo '<tr class="table-light fw-bold">';
            echo '<td colspan="' . $colspan . '" class="text-end">' . $label . '</td>';
            echo '<td class="text-end">Rp ' . number_format($total, 0, ',', '.') . '</td>';
            echo '<td></td>';
            echo '</tr>';
            break;
        case 'pendapatan':
            $colspan = 6;
            $label = 'TOTAL PENDAPATAN';
            echo '<tr class="table-light fw-bold">';
            echo '<td colspan="' . $colspan . '" class="text-end">' . $label . '</td>';
            echo '<td class="text-end">Rp ' . number_format($total, 0, ',', '.') . '</td>';
            echo '<td></td>';
            echo '</tr>';
            break;
        case 'perpanjangan':
            $colspan = 5;
            $label = 'TOTAL BIAYA PERPANJANGAN (Disetujui)';
            echo '<tr class="table-light fw-bold">';
            echo '<td colspan="' . $colspan . '" class="text-end">' . $label . '</td>';
            echo '<td class="text-end">Rp ' . number_format($total, 0, ',', '.') . '</td>';
            echo '<td></td>';
            echo '</tr>';
            break;
        default:
            break;
    }
}

// ============================================================
// FUNGSI FOOTER PENGESAHAN
// ============================================================
function footerPengesahan()
{
    $nama_user = $_SESSION['nama_lengkap'] ?? 'Tidak Diketahui';
    $role = $_SESSION['role'] ?? 'Pelanggan';
    $tanggal = date('d F Y');

    echo '
    <div class="footer-pengesahan">
        <!-- Tanggal di tengah -->
        <div style= margin-bottom: 30px;">
            <p style="font-size: 12px; margin: 0;">Pematangsiantar, ' . $tanggal . '</p>
        </div>
            <br><br><br>
        <!-- Pembuat Laporan & Mengetahui -->
        <div style="display: flex; justify-content: center; gap: 150px; text-align: center;">
            <!-- Kiri: Pembuat Laporan -->
            <div>
                <p style="font-size: 12px; font-weight: bold; margin-bottom: 30px;">Pembuat Laporan,</p>
                <br><p style="font-size: 12px; margin-top: 30px;">(' . htmlspecialchars($nama_user) . ')</p>
                <p style="font-size: 12px; margin: 0;">' . htmlspecialchars($role) . '</p>
            </div>

            <!-- Kanan: Mengetahui -->
            <div>
                <p style="font-size: 12px; font-weight: bold; margin-bottom: 30px;">Mengetahui,</p>
                <br><p style="font-size: 12px; margin-top: 30px;">(Sarah Pakpahan)</p>
                <p style="font-size: 12px; margin: 0;">Pemilik Toko</p>
            </div>
        </div>
    </div>
    ';
}

// ============================================================
// FORMAT: PRINT (TAMPILAN LANGSUNG)
// ============================================================
if ($format == 'print' || $format == 'pdf') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Laporan <?= ucfirst($jenis_laporan) ?></title>
        <meta charset="UTF-8">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Arial, sans-serif; font-size: 12px; padding: 20px; }
            .header {
                text-align: center;
                border-bottom: 3px double #000;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }
            .header h1 { font-size: 22px; margin: 0; }
            .header p { font-size: 13px; color: #555; margin: 3px 0; }
            .header .contact { font-size: 12px; color: #777; }
            .judul-laporan {
                font-size: 16px;
                font-weight: bold;
                text-align: center;
                margin-bottom: 15px;
            }
            .periode {
                text-align: center;
                font-size: 13px;
                margin-bottom: 15px;
                color: #555;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 11px;
            }
            table th {
                background-color: #f0f0f0;
                border: 1px solid #000;
                padding: 6px 8px;
                text-align: center;
                font-weight: bold;
            }
            table td {
                border: 1px solid #000;
                padding: 5px 8px;
                vertical-align: middle;
            }
            table .table-light {
                background-color: #f8f9fa;
            }
            .text-end { text-align: right; }
            .text-center { text-align: center; }
            .text-start { text-align: left; }
            .fw-bold { font-weight: bold; }
            .text-muted { color: #888; }
            .mt-3 { margin-top: 15px; }
            .mt-4 { margin-top: 25px; }

            /* FOOTER PENGESAHAN */
            .footer-pengesahan {
                margin-top: 40px;
                border-top: 1px solid #000;
                padding-top: 20px;
                page-break-inside: avoid;
            }
            .footer-pengesahan .row {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
            }
            .footer-pengesahan .col-6 {
                width: 48%;
                display: inline-block;
                vertical-align: top;
            }
            .footer-pengesahan .col-12 {
                width: 100%;
                text-align: center;
            }
            .footer-pengesahan p {
                margin: 5px 0;
            }

            @media print {
                body { padding: 10mm; }
                .no-print { display: none; }
                .header { border-bottom: 3px double #000; }
                table th { background-color: #f0f0f0 !important; }
                .table-light { background-color: #f8f9fa !important; }
                .footer-pengesahan { margin-top: 50px; }
            }
        </style>
    </head>
    <body>
        <!-- HEADER -->
        <div class="header">
            <h1>Njuah Vet</h1>
            <p>Jl. Contoh No. 123, Pematangsiantar</p>
            <p class="contact">📞 (0622) 123456 | ✉ info@njuahvet.com</p>
        </div>

        <!-- JUDUL LAPORAN -->
        <?php
        $judul = [
            'pelanggan' => 'LAPORAN PELANGGAN',
            'hewan' => 'LAPORAN HEWAN',
            'booking' => 'LAPORAN BOOKING',
            'pendapatan' => 'LAPORAN PENDAPATAN',
            'perpanjangan' => 'LAPORAN PERPANJANGAN'
        ];
        ?>
        <div class="judul-laporan"><?= $judul[$jenis_laporan] ?? 'LAPORAN' ?></div>
        <div class="periode">Periode: <?= date('d-m-Y', strtotime($tgl_mulai)); ?> s/d <?= date('d-m-Y', strtotime($tgl_selesai)); ?></div>

        <!-- TABEL -->
        <table>
            <thead>
                <tr>
                    <?php
                    switch ($jenis_laporan) {
                        case 'pelanggan':
                            echo '<th>No</th><th>Nama Pelanggan</th><th>No Telepon</th><th class="text-center">Jumlah Booking</th><th class="text-end">Total Pengeluaran</th>';
                            break;
                        case 'hewan':
                            echo '<th>No</th><th>Nama Hewan</th><th>Jenis</th><th>Ras</th><th>Pemilik</th><th class="text-center">Jumlah Titip</th>';
                            break;
                        case 'booking':
                            echo '<th>No</th><th>Kode Booking</th><th>Pelanggan</th><th>Tanggal Mulai</th><th>Tanggal Selesai</th><th class="text-end">Total Biaya</th><th>Status</th>';
                            break;
                        case 'pendapatan':
                            echo '<th>No</th><th>Kode Pembayaran</th><th>Tanggal Bayar</th><th>Metode</th><th>Pelanggan</th><th>Sumber</th><th class="text-end">Jumlah Bayar</th><th>Status</th>';
                            break;
                        case 'perpanjangan':
                            echo '<th>No</th><th>Kode Booking</th><th>Kandang</th><th>Tgl Selesai Lama</th><th>Tgl Selesai Baru</th><th class="text-end">Biaya Tambahan</th><th>Status</th>';
                            break;
                        default:
                            break;
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php cetakTabel($jenis_laporan, $data, $total_footer); ?>
            </tbody>
        </table>

        <!-- FOOTER PENGESAHAN -->
        <?php footerPengesahan(); ?>

         <script>
    window.onload = function() {
        window.print();
    };
    window.onafterprint = function() {
        window.close();
    };
</script>
    </body>
    </html>
    <?php
    exit;
}

// ============================================================
// FORMAT: EXCEL
// ============================================================
if ($format == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Laporan_' . $jenis_laporan . '_' . date('Ymd') . '.xls"');
    ?>
    <html>
    <head><meta charset="UTF-8"></head>
    <body>
        <h2>Njuah Vet - Laporan <?= ucfirst($jenis_laporan) ?></h2>
        <p>Periode: <?= date('d-m-Y', strtotime($tgl_mulai)); ?> s/d <?= date('d-m-Y', strtotime($tgl_selesai)); ?></p>
        <table border="1">
            <thead>
                <tr>
                    <?php
                    switch ($jenis_laporan) {
                        case 'pelanggan':
                            echo '<th>No</th><th>Nama Pelanggan</th><th>No Telepon</th><th>Jumlah Booking</th><th>Total Pengeluaran</th>';
                            break;
                        case 'hewan':
                            echo '<th>No</th><th>Nama Hewan</th><th>Jenis</th><th>Ras</th><th>Pemilik</th><th>Jumlah Titip</th>';
                            break;
                        case 'booking':
                            echo '<th>No</th><th>Kode Booking</th><th>Pelanggan</th><th>Tanggal Mulai</th><th>Tanggal Selesai</th><th>Total Biaya</th><th>Status</th>';
                            break;
                        case 'pendapatan':
                            echo '<th>No</th><th>Kode Pembayaran</th><th>Tanggal Bayar</th><th>Metode</th><th>Pelanggan</th><th>Sumber</th><th>Jumlah Bayar</th><th>Status</th>';
                            break;
                        case 'perpanjangan':
                            echo '<th>No</th><th>Kode Booking</th><th>Kandang</th><th>Tgl Selesai Lama</th><th>Tgl Selesai Baru</th><th>Biaya Tambahan</th><th>Status</th>';
                            break;
                        default:
                            break;
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                foreach ($data as $row) {
                    echo '<tr>';
                    switch ($jenis_laporan) {
                        case 'pelanggan':
                            echo '<td>' . $no++ . '</td>';
                            echo '<td>' . htmlspecialchars($row['nama_lengkap'] ?? '') . '</td>';
                            echo '<td>' . htmlspecialchars($row['no_telp'] ?? '') . '</td>';
                            echo '<td>' . $row['jumlah_booking'] . '</td>';
                            echo '<td>' . number_format($row['total_pengeluaran'], 0, ',', '.') . '</td>';
                            break;
                        case 'hewan':
                            echo '<td>' . $no++ . '</td>';
                            echo '<td>' . htmlspecialchars($row['nama_hewan'] ?? '') . '</td>';
                            echo '<td>' . htmlspecialchars($row['jenis_hewan'] ?? '') . '</td>';
                            echo '<td>' . htmlspecialchars($row['ras'] ?? '') . '</td>';
                            echo '<td>' . htmlspecialchars($row['pemilik'] ?? '') . '</td>';
                            echo '<td>' . $row['jumlah_titip'] . '</td>';
                            break;
                        case 'booking':
                            echo '<td>' . $no++ . '</td>';
                            echo '<td>' . htmlspecialchars($row['kode_booking'] ?? '') . '</td>';
                            echo '<td>' . htmlspecialchars($row['pelanggan'] ?? '') . '</td>';
                            echo '<td>' . $row['tgl_mulai'] . '</td>';
                            echo '<td>' . $row['tgl_selesai'] . '</td>';
                            echo '<td>' . number_format($row['total_biaya'], 0, ',', '.') . '</td>';
                            echo '<td>' . $row['status'] . '</td>';
                            break;
                        case 'pendapatan':
                            echo '<td>' . $no++ . '</td>';
                            echo '<td>' . htmlspecialchars($row['kode_pembayaran'] ?? '') . '</td>';
                            echo '<td>' . ($row['tgl_bayar'] ?? '-') . '</td>';
                            echo '<td>' . htmlspecialchars($row['metode'] ?? '-') . '</td>';
                            echo '<td>' . htmlspecialchars($row['pelanggan'] ?? '-') . '</td>';
                            echo '<td>' . htmlspecialchars($row['sumber'] ?? '') . '</td>';
                            echo '<td>' . number_format($row['jumlah_bayar'] ?? 0, 0, ',', '.') . '</td>';
                            echo '<td>Lunas</td>';
                            break;
                        case 'perpanjangan':
                            echo '<td>' . $no++ . '</td>';
                            echo '<td>' . htmlspecialchars($row['kode_booking'] ?? '') . '</td>';
                            echo '<td>' . htmlspecialchars($row['nama_kandang'] ?? '') . '</td>';
                            echo '<td>' . $row['tgl_selesai_lama'] . '</td>';
                            echo '<td>' . $row['tgl_selesai_baru'] . '</td>';
                            echo '<td>' . number_format($row['biaya_tambahan'], 0, ',', '.') . '</td>';
                            echo '<td>' . $row['status'] . '</td>';
                            break;
                        default:
                            break;
                    }
                    echo '</tr>';
                }
                ?>
                <tr style="font-weight:bold;">
                    <?php
                    // Footer di excel
                    switch ($jenis_laporan) {
                        case 'pelanggan':
                            echo '<td colspan="3" style="text-align:right;">TOTAL</td>';
                            echo '<td>' . array_sum(array_column($data, 'jumlah_booking')) . '</td>';
                            echo '<td>' . number_format($total_footer, 0, ',', '.') . '</td>';
                            break;
                        case 'hewan':
                            echo '<td colspan="5" style="text-align:right;">TOTAL TITIP</td>';
                            echo '<td>' . array_sum(array_column($data, 'jumlah_titip')) . '</td>';
                            break;
                        case 'booking':
                            echo '<td colspan="5" style="text-align:right;">TOTAL BIAYA BOOKING</td>';
                            echo '<td>' . number_format($total_footer, 0, ',', '.') . '</td>';
                            echo '<td></td>';
                            break;
                        case 'pendapatan':
                            echo '<td colspan="6" style="text-align:right;">TOTAL PENDAPATAN</td>';
                            echo '<td>' . number_format($total_footer, 0, ',', '.') . '</td>';
                            echo '<td></td>';
                            break;
                        case 'perpanjangan':
                            echo '<td colspan="5" style="text-align:right;">TOTAL BIAYA PERPANJANGAN (Disetujui)</td>';
                            echo '<td>' . number_format($total_footer, 0, ',', '.') . '</td>';
                            echo '<td></td>';
                            break;
                        default:
                            break;
                    }
                    ?>
                </tr>
            </tbody>
        </table>
        <p style="margin-top:30px;">Dicetak oleh: <?= $_SESSION['nama_lengkap'] ?? 'Tidak Diketahui' ?> | <?= date('d-m-Y H:i:s') ?></p>
    </body>
    </html>
    <?php
    exit;
}

// ============================================================
// FORMAT: CSV
// ============================================================
if ($format == 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Laporan_' . $jenis_laporan . '_' . date('Ymd') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Laporan ' . ucfirst($jenis_laporan) . ' - Periode: ' . date('d-m-Y', strtotime($tgl_mulai)) . ' s/d ' . date('d-m-Y', strtotime($tgl_selesai))]);

    // Header
    $header = [];
    switch ($jenis_laporan) {
        case 'pelanggan': $header = ['No', 'Nama Pelanggan', 'No Telepon', 'Jumlah Booking', 'Total Pengeluaran']; break;
        case 'hewan': $header = ['No', 'Nama Hewan', 'Jenis', 'Ras', 'Pemilik', 'Jumlah Titip']; break;
        case 'booking': $header = ['No', 'Kode Booking', 'Pelanggan', 'Tanggal Mulai', 'Tanggal Selesai', 'Total Biaya', 'Status']; break;
        case 'pendapatan': $header = ['No', 'Kode Pembayaran', 'Tanggal Bayar', 'Metode', 'Pelanggan', 'Sumber', 'Jumlah Bayar', 'Status']; break;
        case 'perpanjangan': $header = ['No', 'Kode Booking', 'Kandang', 'Tgl Selesai Lama', 'Tgl Selesai Baru', 'Biaya Tambahan', 'Status']; break;
        default: $header = ['No', 'Data']; break;
    }
    fputcsv($output, $header);

    // Data
    $no = 1;
    foreach ($data as $row) {
        $baris = [];
        switch ($jenis_laporan) {
            case 'pelanggan':
                $baris = [$no++, htmlspecialchars($row['nama_lengkap'] ?? ''), htmlspecialchars($row['no_telp'] ?? ''), $row['jumlah_booking'], number_format($row['total_pengeluaran'], 0, ',', '.')];
                break;
            case 'hewan':
                $baris = [$no++, htmlspecialchars($row['nama_hewan'] ?? ''), htmlspecialchars($row['jenis_hewan'] ?? ''), htmlspecialchars($row['ras'] ?? ''), htmlspecialchars($row['pemilik'] ?? ''), $row['jumlah_titip']];
                break;
            case 'booking':
                $baris = [$no++, htmlspecialchars($row['kode_booking'] ?? ''), htmlspecialchars($row['pelanggan'] ?? ''), $row['tgl_mulai'], $row['tgl_selesai'], number_format($row['total_biaya'], 0, ',', '.'), $row['status']];
                break;
            case 'pendapatan':
                $baris = [$no++, htmlspecialchars($row['kode_pembayaran'] ?? ''), $row['tgl_bayar'] ?? '-', htmlspecialchars($row['metode'] ?? '-'), htmlspecialchars($row['pelanggan'] ?? '-'), htmlspecialchars($row['sumber'] ?? ''), number_format($row['jumlah_bayar'] ?? 0, 0, ',', '.'), 'Lunas'];
                break;
            case 'perpanjangan':
                $baris = [$no++, htmlspecialchars($row['kode_booking'] ?? ''), htmlspecialchars($row['nama_kandang'] ?? ''), $row['tgl_selesai_lama'], $row['tgl_selesai_baru'], number_format($row['biaya_tambahan'], 0, ',', '.'), $row['status']];
                break;
            default:
                break;
        }
        fputcsv($output, $baris);
    }

    // Footer total
    fputcsv($output, []);
    $footer_label = [];
    switch ($jenis_laporan) {
        case 'pelanggan': $footer_label = ['', '', 'TOTAL', array_sum(array_column($data, 'jumlah_booking')), number_format($total_footer, 0, ',', '.')]; break;
        case 'hewan': $footer_label = ['', '', '', '', 'TOTAL TITIP', array_sum(array_column($data, 'jumlah_titip'))]; break;
        case 'booking': $footer_label = ['', '', '', '', '', 'TOTAL BIAYA BOOKING', number_format($total_footer, 0, ',', '.')]; break;
        case 'pendapatan': $footer_label = ['', '', '', '', '', '', 'TOTAL PENDAPATAN', number_format($total_footer, 0, ',', '.')]; break;
        case 'perpanjangan': $footer_label = ['', '', '', '', '', 'TOTAL BIAYA PERPANJANGAN (Disetujui)', number_format($total_footer, 0, ',', '.')]; break;
        default: break;
    }
    fputcsv($output, $footer_label);

    fclose($output);
    exit;
}

// ============================================================
// DEFAULT: REDIRECT KE INDEX
// ============================================================
header('Location: index.php');
exit;
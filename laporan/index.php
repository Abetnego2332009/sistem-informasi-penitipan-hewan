<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

$title = 'Laporan Sistem';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';

// ============================================================
// 1. AMBIL PARAMETER FILTER
// ============================================================
$jenis_laporan = isset($_GET['jenis_laporan']) ? mysqli_real_escape_string($koneksi, $_GET['jenis_laporan']) : 'pelanggan';
$tgl_mulai     = !empty($_GET['tgl_mulai']) ? mysqli_real_escape_string($koneksi, $_GET['tgl_mulai']) : date('Y-m-d');
$tgl_selesai   = !empty($_GET['tgl_selesai']) ? mysqli_real_escape_string($koneksi, $_GET['tgl_selesai']) : date('Y-m-d', strtotime('+1 day'));

// ============================================================
// 2. FUNGSI UNTUK MENGAMBIL DATA BERDASARKAN JENIS LAPORAN
// ============================================================
function getDataLaporan($koneksi, $jenis, $tgl_mulai, $tgl_selesai)
{
    $data = [];
    $total_footer = 0;
    $total_pendapatan = 0; // khusus pendapatan

    switch ($jenis) {
        case 'pelanggan':
            // Subquery untuk menggabungkan pelanggan dari users (online) dan pelanggan (offline)
            $query = "
        SELECT 
            COALESCE(u.nama_lengkap, p.nama_lengkap) AS nama_lengkap,
            COALESCE(u.no_telp, p.no_telp) AS no_telp,
            COUNT(pen.id_penitipan) AS jumlah_booking,
            SUM(pen.total_biaya) AS total_pengeluaran,
             CASE 
        WHEN semua_pelanggan.tipe = 'user' THEN 'Online'
        ELSE 'Offline'
        END AS tipe_pelanggan
        FROM (
            -- Pelanggan online (dari users dengan role Pelanggan)
            SELECT id_user AS id, nama_lengkap, no_telp, 'user' AS tipe
            FROM users 
            WHERE role = 'Pelanggan' AND status_akun = 'Aktif'
            
            UNION ALL
            
            -- Pelanggan offline (dari tabel pelanggan)
            SELECT id_pelanggan AS id, nama_lengkap, no_telp, 'offline' AS tipe
            FROM pelanggan
        ) AS semua_pelanggan
        LEFT JOIN penitipan pen ON (
            (semua_pelanggan.tipe = 'user' AND pen.id_user = semua_pelanggan.id) OR
            (semua_pelanggan.tipe = 'offline' AND pen.id_pelanggan = semua_pelanggan.id)
        )
        LEFT JOIN users u ON (semua_pelanggan.tipe = 'user' AND u.id_user = semua_pelanggan.id)
        LEFT JOIN pelanggan p ON (semua_pelanggan.tipe = 'offline' AND p.id_pelanggan = semua_pelanggan.id)
       WHERE DATE(pen.tgl_booking) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
        GROUP BY semua_pelanggan.id, semua_pelanggan.tipe
        ORDER BY total_pengeluaran DESC
    ";
            $result = mysqli_query($koneksi, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                // Pastikan tidak ada null di nama atau no_telp
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
       WHERE DATE(pen.tgl_booking) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
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
        WHERE DATE(pen.tgl_booking) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
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
            // Total dari booking yang lunas
            $q_booking = "
    SELECT SUM(pay.jumlah_bayar) AS total_booking
    FROM pembayaran pay
    LEFT JOIN penitipan pen ON pay.id_penitipan = pen.id_penitipan
    WHERE pay.status_bayar = 'Lunas'
      AND DATE(pay.tgl_konfirmasi) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
";
$res_booking = mysqli_query($koneksi, $q_booking);
$row_booking = mysqli_fetch_assoc($res_booking);
$total_booking = $row_booking['total_booking'] ?? 0;

$q_perpanjangan = "
    SELECT SUM(rp.biaya_tambahan) AS total_perpanjangan
    FROM riwayat_perpanjangan rp
    WHERE rp.status = 'Disetujui'
      AND DATE(rp.created_at) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
";
            $res_perpanjangan = mysqli_query($koneksi, $q_perpanjangan);
            $row_perpanjangan = mysqli_fetch_assoc($res_perpanjangan);
            $total_perpanjangan = $row_perpanjangan['total_perpanjangan'] ?? 0;

            $total_pendapatan = $total_booking + $total_perpanjangan;

            // Detail transaksi (gabungan pembayaran + perpanjangan)
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
               AND DATE(pay.tgl_konfirmasi) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
                
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
                AND DATE(rp.created_at) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
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
$data_laporan = $laporan['data'];
$total_footer = $laporan['total'];
$total_pendapatan = isset($laporan['total_pendapatan']) ? $laporan['total_pendapatan'] : 0;

// ============================================================
// 3. FUNGSI UNTUK MENAMPILKAN TABEL (KONSISTEN)
// ============================================================
function tampilkanTabel($jenis, $data, $total)
{
    if (empty($data)) {
        echo '<div class="alert alert-info text-center">Tidak ada data untuk periode yang dipilih.</div>';
        return;
    }

    echo '<div class="table-responsive">';
    echo '<table class="table table-bordered table-hover table-sm align-middle">';

    // Header
    echo '<thead class="table-dark">';
    echo '<tr>';
    switch ($jenis) {
        case 'pelanggan':
            echo '<th>No</th><th>Nama Pelanggan</th><th>No Telepon</th><th>Tipe</th><th class="text-center">Jumlah Booking</th><th class="text-end">Total Pengeluaran</th>';
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
    echo '</tr></thead>';

    // Body
    echo '<tbody>';
    $no = 1;
    foreach ($data as $row) {
        echo '<tr>';
        switch ($jenis) {
            case 'pelanggan':
                echo '<td>' . $no++ . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_lengkap'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['no_telp'] ?? '') . '</td>';
                // Tampilkan tipe pelanggan
                $tipe = isset($row['tipe_pelanggan']) ? $row['tipe_pelanggan'] : 'Online';
                $badge_class = ($tipe == 'Online') ? 'bg-primary' : 'bg-secondary';
                echo '<td><span class="badge ' . $badge_class . '">' . htmlspecialchars($tipe) . '</span></td>';
                echo '<td class="text-center">' . $row['jumlah_booking'] . '</td>';
                echo '<td class="text-end fw-bold">Rp ' . number_format($row['total_pengeluaran'], 0, ',', '.') . '</td>';
                break;
            case 'hewan':
                echo '<td>' . $no++ . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_hewan'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['jenis_hewan'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['ras'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['pemilik'] ?? '') . '</td>';
                echo '<td class="text-center">' . $row['jumlah_titip'] . '</td>';
                break;
            case 'booking':
                echo '<td>' . $no++ . '</td>';
                echo '<td><strong>' . htmlspecialchars($row['kode_booking'] ?? '') . '</strong></td>';
                echo '<td>' . htmlspecialchars($row['pelanggan'] ?? '') . '</td>';
                echo '<td>' . $row['tgl_mulai'] . '</td>';
                echo '<td>' . $row['tgl_selesai'] . '</td>';
                echo '<td class="text-end fw-bold">Rp ' . number_format($row['total_biaya'], 0, ',', '.') . '</td>';
                echo '<td><span class="badge bg-' . (($row['status'] == 'Selesai' || $row['status'] == 'Disetujui') ? 'success' : 'warning') . '">' . $row['status'] . '</span></td>';
                break;
            case 'pendapatan':
                echo '<td>' . $no++ . '</td>';
                echo '<td><strong>' . htmlspecialchars($row['kode_pembayaran'] ?? '') . '</strong></td>';
                echo '<td>' . ($row['tgl_bayar'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($row['metode'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($row['pelanggan'] ?? '-') . '</td>';
                echo '<td><span class="badge bg-info">' . htmlspecialchars($row['sumber'] ?? 'Unknown') . '</span></td>';
                echo '<td class="text-end fw-bold text-success">Rp ' . number_format($row['jumlah_bayar'] ?? 0, 0, ',', '.') . '</td>';
                echo '<td><span class="badge bg-success">Lunas</span></td>';
                break;
            case 'perpanjangan':
                echo '<td>' . $no++ . '</td>';
                echo '<td><strong>' . htmlspecialchars($row['kode_booking'] ?? '') . '</strong></td>';
                echo '<td>' . htmlspecialchars($row['nama_kandang'] ?? '') . '</td>';
                echo '<td>' . $row['tgl_selesai_lama'] . '</td>';
                echo '<td>' . $row['tgl_selesai_baru'] . '</td>';
                echo '<td class="text-end fw-bold text-warning">Rp ' . number_format($row['biaya_tambahan'], 0, ',', '.') . '</td>';
                echo '<td><span class="badge bg-' . ($row['status'] == 'Disetujui' ? 'success' : 'warning') . '">' . $row['status'] . '</span></td>';
                break;
            default:
                break;
        }
        echo '</tr>';
    }
    echo '</tbody>';

    // Footer Total (konsisten)
    echo '<tfoot class="table-light fw-bold">';
    echo '<tr>';
    $colspan = 0;
    switch ($jenis) {
        case 'pelanggan':
            $colspan = 4; // ← ubah dari 3 menjadi 4
            echo '<td colspan="' . $colspan . '" class="text-end">TOTAL</td>';
            echo '<td class="text-center">' . array_sum(array_column($data, 'jumlah_booking')) . '</td>';
            echo '<td class="text-end text-success">Rp ' . number_format($total, 0, ',', '.') . '</td>';
            break;
        case 'hewan':
            $colspan = 5;
            echo '<td colspan="' . $colspan . '" class="text-end">TOTAL TITIP</td>';
            echo '<td class="text-center">' . array_sum(array_column($data, 'jumlah_titip')) . '</td>';
            break;
        case 'booking':
            $colspan = 5;
            echo '<td colspan="' . $colspan . '" class="text-end">TOTAL BIAYA BOOKING</td>';
            echo '<td class="text-end text-success">Rp ' . number_format($total, 0, ',', '.') . '</td>';
            echo '<td></td>';
            break;
        case 'pendapatan':
            $colspan = 6;
            echo '<td colspan="' . $colspan . '" class="text-end">TOTAL PENDAPATAN</td>';
            echo '<td class="text-end text-success">Rp ' . number_format($total, 0, ',', '.') . '</td>';
            echo '<td></td>';
            break;
        case 'perpanjangan':
            $colspan = 5;
            echo '<td colspan="' . $colspan . '" class="text-end">TOTAL BIAYA PERPANJANGAN (Disetujui)</td>';
            echo '<td class="text-end text-warning">Rp ' . number_format($total, 0, ',', '.') . '</td>';
            echo '<td></td>';
            break;
        default:
            break;
    }
    echo '</tr>';
    echo '</tfoot>';

    echo '</table></div>';
}
?>

<style>
    .laporan-card {
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .filter-label {
        font-weight: 600;
        font-size: 0.85rem;
        color: #333;
    }

    .btn-export {
        min-width: 90px;
    }

    .table-responsive {
        max-height: 550px;
        overflow-y: auto;
    }

    .card-total-pendapatan {
        background: linear-gradient(135deg, #28a745, #20c997);
        border: none;
    }
</style>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row mb-3">
            <div class="col-12">
                <h3 class="welcome-text fw-bold">📊 Laporan Sistem Penitipan</h3>
                <p class="text-muted">Pilih jenis laporan dan periode tanggal untuk melihat data yang diinginkan.</p>
            </div>
        </div>

        <!-- FILTER -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-rounded shadow-sm laporan-card">
                    <div class="card-body p-3">
                        <form method="GET" action="" class="row g-3 align-items-end">
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label filter-label">Jenis Laporan</label>
                                <select name="jenis_laporan" class="form-select form-select-sm">
                                    <option value="pelanggan" <?= $jenis_laporan == 'pelanggan' ? 'selected' : ''; ?>>Pelanggan</option>
                                    <option value="hewan" <?= $jenis_laporan == 'hewan' ? 'selected' : ''; ?>>Hewan</option>
                                    <option value="booking" <?= $jenis_laporan == 'booking' ? 'selected' : ''; ?>>Booking</option>
                                    <option value="pendapatan" <?= $jenis_laporan == 'pendapatan' ? 'selected' : ''; ?>>Pendapatan</option>
                                    <option value="perpanjangan" <?= $jenis_laporan == 'perpanjangan' ? 'selected' : ''; ?>>Perpanjangan</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label filter-label">Tanggal Mulai</label>
                                <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_mulai); ?>" required>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label filter-label">Tanggal Selesai</label>
                                <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_selesai); ?>" required>
                            </div>
                            <div class="col-md-4 col-sm-6 d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-primary btn-sm text-white py-2 px-4 fw-bold">
                                    <i class="mdi mdi-magnify"></i> Tampilkan
                                </button>
                                <a href="index.php" class="btn btn-light btn-sm py-2 px-3 text-dark border">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOMBOL EKSPOR -->
        <div class="row mb-3">
            <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                <span class="fw-bold me-2">📥 Ekspor Data :</span>
                <?php
                $base_url = "cetak.php?jenis_laporan=$jenis_laporan&tgl_mulai=$tgl_mulai&tgl_selesai=$tgl_selesai";
                ?>
                <a href="<?= $base_url; ?>&format=print" target="_blank" class="btn btn-outline-secondary btn-sm btn-export py-2">
                    <i class="mdi mdi-printer"></i> Print
                </a>
                <a href="<?= $base_url; ?>&format=pdf" target="_blank" class="btn btn-danger btn-sm btn-export py-2">
                    <i class="mdi mdi-file-pdf"></i> PDF
                </a>
                <a href="<?= $base_url; ?>&format=excel" class="btn btn-success btn-sm btn-export py-2">
                    <i class="mdi mdi-file-excel"></i> Excel
                </a>
                <a href="<?= $base_url; ?>&format=csv" class="btn btn-info btn-sm btn-export py-2 text-white">
                    <i class="mdi mdi-file-delimited"></i> CSV
                </a>
            </div>
        </div>

        <!-- CARD RINGKASAN PENDAPATAN (hanya untuk jenis pendapatan) -->
        <?php if ($jenis_laporan == 'pendapatan'): ?>
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card card-rounded shadow-sm card-total-pendapatan text-white">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0 fw-bold"><i class="mdi mdi-currency-idr me-2"></i> Total Pendapatan Bersih</h5>
                                <small class="text-light">(Booking Lunas + Perpanjangan Disetujui)</small>
                            </div>
                            <h3 class="mb-0 fw-bold">Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- TABEL LAPORAN -->
        <div class="row">
            <div class="col-12">
                <div class="card card-rounded shadow-sm laporan-card">
                    <div class="card-body p-3">
                        <h5 class="fw-bold mb-3">
                            <?php
                            $judul = [
                                'pelanggan' => 'Laporan Pelanggan',
                                'hewan' => 'Laporan Hewan',
                                'booking' => 'Laporan Booking',
                                'pendapatan' => 'Laporan Pendapatan',
                                'perpanjangan' => 'Laporan Perpanjangan'
                            ];
                            echo $judul[$jenis_laporan];
                            ?>
                            <small class="text-muted fw-normal">(Periode: <?= date('d-m-Y', strtotime($tgl_mulai)); ?> s/d <?= date('d-m-Y', strtotime($tgl_selesai)); ?>)</small>
                        </h5>
                        <?php tampilkanTabel($jenis_laporan, $data_laporan, $total_footer); ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <?php require '../templates/footer.php'; ?>
</div>
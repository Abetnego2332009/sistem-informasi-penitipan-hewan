<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

// Cek login
if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$id_user = $_SESSION['id_user'];

// ============================================================
// AMBIL FILTER
// ============================================================
$filter_tanggal_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : '';
$filter_tanggal_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : '';
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : '';

// ============================================================
// BUILD WHERE CLAUSE
// ============================================================
$where_bayar = "pay.status_bayar = 'Lunas' AND pen.id_user = $id_user";
$where_perpanjangan = "rp.status = 'Disetujui' AND pen.id_user = $id_user";

if (!empty($filter_tanggal_mulai) && !empty($filter_tanggal_selesai)) {
    $where_bayar .= " AND DATE(pay.tgl_bayar) BETWEEN '$filter_tanggal_mulai' AND '$filter_tanggal_selesai'";
    $where_perpanjangan .= " AND DATE(rp.tgl_perpanjangan) BETWEEN '$filter_tanggal_mulai' AND '$filter_tanggal_selesai'";
}

if (!empty($filter_bulan) && !empty($filter_tahun)) {
    $where_bayar .= " AND MONTH(pay.tgl_bayar) = $filter_bulan AND YEAR(pay.tgl_bayar) = $filter_tahun";
    $where_perpanjangan .= " AND MONTH(rp.tgl_perpanjangan) = $filter_bulan AND YEAR(rp.tgl_perpanjangan) = $filter_tahun";
}

// ============================================================
// QUERY GABUNGAN (PEMBAYARAN + PERPANJANGAN)
// ============================================================
$query = "
    SELECT 
        pay.kode_pembayaran,
        pay.tgl_bayar,
        mp.nama_metode AS metode,
        pay.nama_pembayar AS pelanggan,
        pay.jumlah_bayar,
        'Booking' AS sumber
    FROM pembayaran pay
    LEFT JOIN penitipan pen ON pay.id_penitipan = pen.id_penitipan
    LEFT JOIN metode_pembayaran mp ON pay.id_metode = mp.id_metode
    WHERE $where_bayar
    
    UNION ALL
    
    SELECT 
        rp.kode_pembayaran,
        rp.tgl_perpanjangan AS tgl_bayar,
        mp.nama_metode AS metode,
        rp.nama_pembayar AS pelanggan,
        rp.jumlah_bayar,
        'Perpanjangan' AS sumber
    FROM riwayat_perpanjangan rp
    LEFT JOIN penitipan pen ON rp.id_penitipan = pen.id_penitipan
    LEFT JOIN metode_pembayaran mp ON rp.id_metode = mp.id_metode
    WHERE $where_perpanjangan
    
    ORDER BY tgl_bayar DESC
";
$result = mysqli_query($koneksi, $query);

// ============================================================
// HITUNG TOTAL
// ============================================================
$total_pendapatan = 0;
$total_booking = 0;
$total_perpanjangan = 0;
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
    $total_pendapatan += $row['jumlah_bayar'];
    if ($row['sumber'] == 'Booking') {
        $total_booking++;
    } else {
        $total_perpanjangan++;
    }
}
mysqli_data_seek($result, 0);

$title = 'Laporan Pendapatan Saya';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>

<style>
    .stat-card {
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
    .stat-card .stat-icon {
        font-size: 2rem;
        opacity: 0.3;
        position: absolute;
        right: 15px;
        bottom: 15px;
    }
    .stat-card .stat-number {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 4px;
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
                <h2 class="welcome-text fw-bold">📊 Laporan Pengeluaran Saya</h2>
                <p class="text-muted">Lihat riwayat pembayaran booking dan perpanjangan Anda.</p>
            </div>
        </div>

        <!-- FILTER -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-rounded shadow-sm">
                    <div class="card-body p-3">
                        <form method="GET" action="" class="row g-3 align-items-end">
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label small fw-bold">Tanggal Mulai</label>
                                <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_tanggal_mulai); ?>">
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label small fw-bold">Tanggal Selesai</label>
                                <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_tanggal_selesai); ?>">
                            </div>
                            <div class="col-md-1 text-center pt-3">
                                <span class="text-muted">ATAU</span>
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label small fw-bold">Bulan</label>
                                <select name="bulan" class="form-select form-select-sm">
                                    <option value="">-- Pilih --</option>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>" <?= $filter_bulan == $i ? 'selected' : '' ?>>
                                            <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label small fw-bold">Tahun</label>
                                <select name="tahun" class="form-select form-select-sm">
                                    <option value="">-- Pilih --</option>
                                    <?php for ($i = date('Y'); $i >= 2020; $i--): ?>
                                        <option value="<?= $i ?>" <?= $filter_tahun == $i ? 'selected' : '' ?>>
                                            <?= $i ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-2 col-sm-6 d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm text-white py-2 px-4 fw-bold">
                                    <i class="mdi mdi-magnify"></i> Tampilkan
                                </button>
                                <a href="laporan.php" class="btn btn-light btn-sm py-2 px-3 text-dark border">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================== -->
        <!-- STATISTIK CARD - SAMA SEPERTI DASHBOARD USER                -->
        <!-- ========================================================== -->
        <div class="row">
            <!-- Total Booking -->
            <div class="col-md-4 mb-3">
                <div class="card stat-card p-3 text-center" style="min-height:120px;">
                    <p class="text-muted mb-1">Total Booking</p>
                    <h3 class="stat-number text-primary"><?= $total_booking ?></h3>
                    <p class="text-primary d-flex justify-content-center align-items-center gap-1 mb-0">
                        <i class="mdi mdi-bookmark-check"></i><span>Transaksi</span>
                    </p>
                    <span class="stat-icon mdi mdi-bookmark-check text-primary"></span>
                </div>
            </div>

            <!-- Total Perpanjangan -->
            <div class="col-md-4 mb-3">
                <div class="card stat-card p-3 text-center" style="min-height:120px;">
                    <p class="text-muted mb-1">Total Perpanjangan</p>
                    <h3 class="stat-number text-warning"><?= $total_perpanjangan ?></h3>
                    <p class="text-warning d-flex justify-content-center align-items-center gap-1 mb-0">
                        <i class="mdi mdi-clock"></i><span>Transaksi</span>
                    </p>
                    <span class="stat-icon mdi mdi-clock text-warning"></span>
                </div>
            </div>

            <!-- Total Pendapatan -->
            <div class="col-md-4 mb-3">
                <div class="card stat-card p-3 text-center" style="min-height:120px;">
                    <p class="text-muted mb-1">Total Pembayaran</p>
                    <h3 class="stat-number text-success">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></h3>
                    <p class="text-success d-flex justify-content-center align-items-center gap-1 mb-0">
                        <i class="mdi mdi-credit-card"></i><span>Pembayaran</span>
                    </p>
                    <span class="stat-icon mdi mdi-credit-card text-success"></span>
                </div>
            </div>
        </div>

        <!-- ========================================================== -->
        <!-- TABEL LAPORAN                                               -->
        <!-- ========================================================== -->
        <div class="row">
            <div class="col-12">
                <div class="card card-rounded shadow-sm">
                    <div class="card-body p-3">
                        <h5 class="fw-bold mb-3">
                            Daftar Transaksi
                            <small class="text-muted fw-normal">
                                <?php if (!empty($filter_tanggal_mulai) && !empty($filter_tanggal_selesai)): ?>
                                    (Periode: <?= date('d-m-Y', strtotime($filter_tanggal_mulai)); ?> s/d <?= date('d-m-Y', strtotime($filter_tanggal_selesai)); ?>)
                                <?php elseif (!empty($filter_bulan) && !empty($filter_tahun)): ?>
                                    (Bulan: <?= date('F', mktime(0,0,0, $filter_bulan, 1)) . ' ' . $filter_tahun ?>)
                                <?php else: ?>
                                    (Semua Data)
                                <?php endif; ?>
                            </small>
                        </h5>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Kode Pembayaran</th>
                                        <th>Tanggal Bayar</th>
                                        <th>Metode</th>
                                        <th>Pelanggan</th>
                                        <th>Sumber</th>
                                        <th class="text-end">Jumlah Bayar</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($result) > 0): ?>
                                        <?php $no = 1; ?>
                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><strong><?= htmlspecialchars($row['kode_pembayaran'] ?? '-') ?></strong></td>
                                                <td><?= date('d-m-Y H:i:s', strtotime($row['tgl_bayar'])) ?></td>
                                                <td><?= htmlspecialchars($row['metode'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($row['pelanggan'] ?? '-') ?></td>
                                                <td>
                                                    <?php if ($row['sumber'] == 'Booking'): ?>
                                                        <span class="badge bg-primary">Booking</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">Perpanjangan</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end fw-bold text-success">Rp <?= number_format($row['jumlah_bayar'] ?? 0, 0, ',', '.') ?></td>
                                                <td><span class="badge bg-success">Lunas</span></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-3 text-muted">Tidak ada data untuk periode yang dipilih.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td colspan="6" class="text-end">TOTAL Pembayarantd>
                                            <td class="text-end text-success">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <?php require '../templates/footer.php'; ?>
</div>
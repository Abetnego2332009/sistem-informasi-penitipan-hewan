<?php
// ============================================================
// START SESSION & CEK LOGIN
// ============================================================
session_start();
require_once __DIR__ . '/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

// ============================================================
// AMBIL FILTER DARI URL (GET)
// ============================================================
$filter_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

$start_date = date('Y-m-01', strtotime("$filter_tahun-$filter_bulan-01"));
$end_date   = date('Y-m-t', strtotime("$filter_tahun-$filter_bulan-01"));

// ============================================================
// TAHUN DINAMIS (dari database)
// ============================================================
$q_tahun = "SELECT MIN(YEAR(tgl_booking)) as min_tahun, MAX(YEAR(tgl_booking)) as max_tahun FROM penitipan";
$r_tahun = mysqli_query($koneksi, $q_tahun);
$d_tahun = mysqli_fetch_assoc($r_tahun);
$tahun_min = $d_tahun['min_tahun'] ?? date('Y')-5;
$tahun_max = max($d_tahun['max_tahun'] ?? date('Y'), date('Y')) + 1; // sampai tahun depan

$title = 'DASHBOARD';
require 'templates/header.php';
require 'templates/navbar.php';
require 'templates/sidebar.php';

// ============================================================
// DATA SESSION
// ============================================================
$user_id = $_SESSION['id_user'];
$role    = $_SESSION['role'];
$nama_user = $_SESSION['nama_lengkap'];

// ============================================================
// FUNGSI BANTU (semua pakai filter tanggal)
// ============================================================
function rp($n) {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

function getTotalPendapatan($conn, $start, $end) {
    $q = "SELECT (SELECT IFNULL(SUM(jumlah_bayar),0) FROM pembayaran 
                  WHERE status_bayar='Lunas' AND DATE(tgl_bayar) BETWEEN '$start' AND '$end')
                + (SELECT IFNULL(SUM(biaya_tambahan),0) FROM riwayat_perpanjangan 
                  WHERE status='Disetujui' AND DATE(tgl_perpanjangan) BETWEEN '$start' AND '$end') AS total";
    $r = mysqli_query($conn, $q);
    $d = mysqli_fetch_assoc($r);
    return (float)$d['total'];
}

function countBooking($conn, $start, $end, $status = null) {
    $where = $status ? "status_penitipan = '$status' AND" : "";
    $q = "SELECT COUNT(*) as total FROM penitipan WHERE $where DATE(tgl_booking) BETWEEN '$start' AND '$end'";
    $r = mysqli_query($conn, $q);
    $d = mysqli_fetch_assoc($r);
    return (int)$d['total'];
}

function countPerpanjangan($conn, $start, $end, $status = null) {
    $where = $status ? "status = '$status' AND" : "";
    $q = "SELECT COUNT(*) as total FROM riwayat_perpanjangan WHERE $where DATE(tgl_perpanjangan) BETWEEN '$start' AND '$end'";
    $r = mysqli_query($conn, $q);
    $d = mysqli_fetch_assoc($r);
    return (int)$d['total'];
}

function countPelangganAktif($conn, $start, $end) {
    $q = "SELECT COUNT(DISTINCT id_user) as total FROM penitipan WHERE DATE(tgl_booking) BETWEEN '$start' AND '$end'";
    $r = mysqli_query($conn, $q);
    $d = mysqli_fetch_assoc($r);
    return (int)$d['total'];
}

// ============================================================
// DATA UNTUK USER
// ============================================================
function getUserHewan($conn, $uid) {
    $r = mysqli_query($conn, "SELECT COUNT(DISTINCT ph.id_hewan) as total
                              FROM penitipan_hewan ph
                              JOIN penitipan p ON ph.id_penitipan = p.id_penitipan
                              WHERE p.id_user = $uid");
    $d = mysqli_fetch_assoc($r);
    return (int)$d['total'];
}

function getUserBooking($conn, $uid) {
    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM penitipan WHERE id_user = $uid");
    $d = mysqli_fetch_assoc($r);
    return (int)$d['total'];
}

function getUserPengeluaran($conn, $uid) {
    $q = "SELECT (SELECT IFNULL(SUM(jumlah_bayar),0) FROM pembayaran 
                  WHERE id_penitipan IN (SELECT id_penitipan FROM penitipan WHERE id_user = $uid) 
                    AND status_bayar = 'Lunas') +
                 (SELECT IFNULL(SUM(biaya_tambahan),0) FROM riwayat_perpanjangan 
                  WHERE id_penitipan IN (SELECT id_penitipan FROM penitipan WHERE id_user = $uid) 
                    AND status = 'Disetujui') AS total";
    $r = mysqli_query($conn, $q);
    $d = mysqli_fetch_assoc($r);
    return (float)$d['total'];
}

// ============================================================
// AMBIL DATA ADMIN (SEMUA PAKAI FILTER)
// ============================================================
if ($role == 'Super Admin' || $role == 'Admin') {
    $total_pendapatan   = getTotalPendapatan($koneksi, $start_date, $end_date);
    $total_pelanggan    = countPelangganAktif($koneksi, $start_date, $end_date);
    $total_booking      = countBooking($koneksi, $start_date, $end_date);
    $total_perpanjangan = countPerpanjangan($koneksi, $start_date, $end_date);

    // Penolakan (hanya untuk donut chart, tidak ditampilkan di card)
    $booking_ditolak   = countBooking($koneksi, $start_date, $end_date, 'Ditolak');
    $perpanjangan_ditolak = countPerpanjangan($koneksi, $start_date, $end_date, 'Ditolak');

    // Disetujui (termasuk sedang dititipkan & selesai)
    $booking_disetujui = countBooking($koneksi, $start_date, $end_date, 'Disetujui') 
                         + countBooking($koneksi, $start_date, $end_date, 'Sedang Dititipkan')
                         + countBooking($koneksi, $start_date, $end_date, 'Selesai');

    // Donut chart: Total Booking, Penolakan Booking, Penolakan Perpanjangan, Disetujui
    $donut_labels = ['Total Booking', 'Penolakan Booking', 'Penolakan Perpanjangan', 'Booking Disetujui'];
    $donut_data   = [$total_booking, $booking_ditolak, $perpanjangan_ditolak, $booking_disetujui];
    $donut_colors = ['#4e73df', '#e74a3b', '#f6c23e', '#1cc88a'];
}

if ($role == 'Pelanggan') {
    $total_hewan   = getUserHewan($koneksi, $user_id);
    $total_booking_user = getUserBooking($koneksi, $user_id);
    $total_pengeluaran  = getUserPengeluaran($koneksi, $user_id);
}

$label_bulan = date('F', strtotime("$filter_tahun-$filter_bulan-01")) . ' ' . $filter_tahun;
?>

<!-- ===================== CONTENT ===================== -->
<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-sm-12">
                <div class="home-tab">
                    <div class="tab-content tab-content-basic">
                        <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">

                            <?php if ($role == 'Super Admin' || $role == 'Admin'): ?>
                            <!-- STATISTIK CARD (4 CARD – TANPA TOTAL PENOLAKAN) -->
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="statistics-details d-flex align-items-center justify-content-between flex-wrap">
                                        <div>
                                            <p class="statistics-title">Total Pendapatan</p>
                                            <h3 class="rate-percentage"><?= rp($total_pendapatan) ?></h3>
                                            <p class="text-success d-flex"><i class="mdi mdi-menu-up"></i><span>+<?= number_format($total_booking) ?> booking</span></p>
                                        </div>
                                        <div>
                                            <p class="statistics-title">Total Pelanggan</p>
                                            <h3 class="rate-percentage"><?= number_format($total_pelanggan) ?></h3>
                                            <p class="text-success d-flex"><i class="mdi mdi-account"></i><span>Pelanggan aktif</span></p>
                                        </div>
                                        <div>
                                            <p class="statistics-title">Total Booking</p>
                                            <h3 class="rate-percentage"><?= number_format($total_booking) ?></h3>
                                            <p class="text-success d-flex"><i class="mdi mdi-bookmark-check"></i><span>Semua status</span></p>
                                        </div>
                                        <div>
                                            <p class="statistics-title">Total Perpanjangan</p>
                                            <h3 class="rate-percentage"><?= number_format($total_perpanjangan) ?></h3>
                                            <p class="text-success d-flex"><i class="mdi mdi-clock"></i><span>Semua status</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($role == 'Pelanggan'): ?>
                            <!-- card user tetap -->
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="d-flex flex-wrap gap-3 justify-content-between">
                                        <div class="card p-3 flex-fill text-center" style="min-width:150px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
                                            <p class="statistics-title text-muted mb-1">Hewan Dititipkan</p>
                                            <h3 class="rate-percentage fw-bold mb-1"><?= number_format($total_hewan) ?></h3>
                                            <p class="text-success d-flex justify-content-center align-items-center gap-1 mb-0">
                                                <i class="mdi mdi-paw"></i><span>Total hewan</span>
                                            </p>
                                        </div>
                                        <div class="card p-3 flex-fill text-center" style="min-width:150px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
                                            <p class="statistics-title text-muted mb-1">Total Booking</p>
                                            <h3 class="rate-percentage fw-bold mb-1"><?= number_format($total_booking_user) ?></h3>
                                            <p class="text-success d-flex justify-content-center align-items-center gap-1 mb-0">
                                                <i class="mdi mdi-bookmark-check"></i><span>Semua status</span>
                                            </p>
                                        </div>
                                        <div class="card p-3 flex-fill text-center" style="min-width:150px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
                                            <p class="statistics-title text-muted mb-1">Total Pengeluaran</p>
                                            <h3 class="rate-percentage fw-bold mb-1"><?= rp($total_pengeluaran) ?></h3>
                                            <p class="text-success d-flex justify-content-center align-items-center gap-1 mb-0">
                                                <i class="mdi mdi-credit-card"></i><span>Booking + Perpanjangan</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($role == 'Super Admin' || $role == 'Admin'): ?>
                            <div class="row">
                                <div class="col-lg-8 d-flex flex-column">
                                    <div class="row flex-grow">
                                        <div class="col-12 grid-margin stretch-card">
                                            <div class="card card-rounded">
                                                <div class="card-body">
                                                    <div class="d-sm-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h4 class="card-title card-title-dash">Pendapatan</h4>
                                                            <!-- HANYA 1 BADGE DI SUBTITLE (BAGIAN INI TETAP) -->
                                                            <p class="card-subtitle card-subtitle-dash">
                                                                <span class="badge bg-primary"><?= $label_bulan ?></span>
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <form method="GET" action="" class="d-flex gap-2 align-items-center">
                                                                <select name="bulan" class="form-select form-select-sm" style="width:120px;" onchange="this.form.submit()">
                                                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                                                        <option value="<?= $m ?>" <?= ($m == $filter_bulan) ? 'selected' : '' ?>>
                                                                            <?= date('F', mktime(0,0,0, $m, 1)) ?>
                                                                        </option>
                                                                    <?php endfor; ?>
                                                                </select>
                                                                <select name="tahun" class="form-select form-select-sm" style="width:100px;" onchange="this.form.submit()">
                                                                    <?php for ($y = $tahun_min; $y <= $tahun_max; $y++): ?>
                                                                        <option value="<?= $y ?>" <?= ($y == $filter_tahun) ? 'selected' : '' ?>>
                                                                            <?= $y ?>
                                                                        </option>
                                                                    <?php endfor; ?>
                                                                </select>
                                                                <noscript>
                                                                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                                                </noscript>
                                                            </form>
                                                            <!-- BADGE DUPLIKAT DI SINI SUDAH DIHAPUS -->
                                                        </div>
                                                    </div>
                                                    <div class="chartjs-bar-wrapper mt-3">
                                                        <canvas id="marketingOverview" style="height:200px; width:100%;"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4 d-flex flex-column">
                                    <div class="row flex-grow">
                                        <div class="col-12 grid-margin stretch-card">
                                            <div class="card card-rounded">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                                <h4 class="card-title card-title-dash">Statistik Booking</h4>
                                                            </div>
                                                            <div>
                                                                <canvas class="my-auto" id="doughnutChart" style="height:200px;"></canvas>
                                                            </div>
                                                            <div id="doughnutChart-legend" class="mt-5 text-center"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php require 'templates/footer.php'; ?>
    </div>
</div>

<!-- ========================================================== -->
<!-- SCRIPT UNTUK CHART -->
<!-- ========================================================== -->
<?php if ($role == 'Super Admin' || $role == 'Admin'): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ---------- BAR CHART ----------
    var ctxBar = document.getElementById('marketingOverview').getContext('2d');
    var barChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: [],
                backgroundColor: '#4e73df',
                borderColor: '#4e73df',
                borderWidth: 1,
                borderRadius: 4,
                barPercentage: 0.6,
                categoryPercentage: 0.8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    ticks: {
                        maxRotation: 0,
                        autoSkip: false,
                        font: { size: 10 }
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: { callback: function(value) { return 'Rp ' + value.toLocaleString('id-ID'); } }
                }
            }
        }
    });

    function loadBarChart() {
        var bulan = <?= $filter_bulan ?>;
        var tahun = <?= $filter_tahun ?>;
        fetch('get_chart_data.php?filter=bulan&bulan=' + bulan + '&tahun=' + tahun)
            .then(response => response.json())
            .then(data => {
                barChart.data.labels = data.labels;
                barChart.data.datasets[0].data = data.data;
                barChart.update();
            })
            .catch(error => console.error('Error:', error));
    }
    loadBarChart();

    // ---------- DONUT CHART ----------
    var ctxDonut = document.getElementById('doughnutChart').getContext('2d');
    new Chart(ctxDonut, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($donut_labels) ?>,
            datasets: [{
                data: <?= json_encode($donut_data) ?>,
                backgroundColor: <?= json_encode($donut_colors) ?>,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { size: 11 }, boxWidth: 12, padding: 10 }
                }
            }
        }
    });
});
</script>
<?php endif; ?>
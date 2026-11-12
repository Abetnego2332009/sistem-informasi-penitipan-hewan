<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$role = $_SESSION['role'] ?? 'Pelanggan';
if ($role != 'Super Admin' && $role != 'Admin') {
    echo "<script>alert('Akses ditolak! Hanya Admin.'); window.location.href='../dashboard.php';</script>";
    exit;
}

$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$type = $_GET['type'] ?? 'user';

if (!$id) {
    echo "<script>alert('ID tidak valid!'); window.location.href='index.php';</script>";
    exit;
}

// Ambil data pelanggan
if ($type == 'user') {
    $query = "SELECT id_user AS id, nama_lengkap AS nama, no_telp, alamat, email, 'User' AS tipe, status_akun AS status, created_at 
              FROM users WHERE id_user = $id AND role = 'Pelanggan'";
} else {
    $query = "SELECT id_pelanggan AS id, nama_lengkap AS nama, no_telp, alamat, NULL AS email, 'Offline' AS tipe, 'Aktif' AS status, created_at 
              FROM pelanggan WHERE id_pelanggan = $id";
}
$result = mysqli_query($koneksi, $query);
$pelanggan = mysqli_fetch_assoc($result);

if (!$pelanggan) {
    echo "<script>alert('Data pelanggan tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}

// Ambil daftar booking
if ($type == 'user') {
    $q_booking = "SELECT id_penitipan, kode_booking, tgl_mulai, tgl_selesai, status_penitipan, total_biaya, created_at 
                  FROM penitipan WHERE id_user = $id ORDER BY id_penitipan DESC";
} else {
    $q_booking = "SELECT id_penitipan, kode_booking, tgl_mulai, tgl_selesai, status_penitipan, total_biaya, created_at 
                  FROM penitipan WHERE id_pelanggan = $id ORDER BY id_penitipan DESC";
}
$res_booking = mysqli_query($koneksi, $q_booking);
$bookings = [];
while ($b = mysqli_fetch_assoc($res_booking)) {
    $bookings[] = $b;
}

// Ambil daftar hewan
if ($type == 'user') {
    $q_hewan = "SELECT id_hewan, nama_hewan, jenis_hewan, ras, jenis_kelamin, usia_tahun, usia_bulan, berat_badan 
                FROM hewan WHERE id_user = $id ORDER BY nama_hewan ASC";
} else {
    $q_hewan = "SELECT id_hewan, nama_hewan, jenis_hewan, ras, jenis_kelamin, usia_tahun, usia_bulan, berat_badan 
                FROM hewan WHERE id_pelanggan = $id ORDER BY nama_hewan ASC";
}
$res_hewan = mysqli_query($koneksi, $q_hewan);
$hewans = [];
while ($h = mysqli_fetch_assoc($res_hewan)) {
    $hewans[] = $h;
}

$total_booking = count($bookings);
$total_hewan = count($hewans);

$title = 'Detail Pelanggan';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        <!-- HEADER -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title"><i class="mdi mdi-account-details me-2"></i>Detail Pelanggan</h4>
                            <a href="index.php" class="btn btn-light btn-sm">
                                <i class="mdi mdi-arrow-left"></i> Kembali
                            </a>
                        </div>

                        <!-- PROFIL -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3">
                                    <h6 class="fw-bold text-primary mb-3"><i class="mdi mdi-account me-2"></i>Profil</h6>
                                    <table class="table table-borderless table-sm" style="font-size:14px;">
                                        <tr><td class="fw-bold text-muted" style="width:35%;">Nama</td><td>: <?= htmlspecialchars($pelanggan['nama'] ?? '-') ?></td></tr>
                                        <tr><td class="fw-bold text-muted">Telepon</td><td>: <?= htmlspecialchars($pelanggan['no_telp'] ?? '-') ?></td></tr>
                                        <tr><td class="fw-bold text-muted">Alamat</td><td>: <?= htmlspecialchars($pelanggan['alamat'] ?? '-') ?></td></tr>
                                        <?php if ($type == 'user'): ?>
                                        <tr><td class="fw-bold text-muted">Email</td><td>: <?= htmlspecialchars($pelanggan['email'] ?? '-') ?></td></tr>
                                        <?php endif; ?>
                                        <tr><td class="fw-bold text-muted">Tipe</td><td>: <span class="badge <?= $type == 'user' ? 'bg-primary' : 'bg-secondary' ?>"><?= $pelanggan['tipe'] ?></span></td></tr>
                                        <tr><td class="fw-bold text-muted">Status</td><td>: <span class="badge <?= $pelanggan['status'] == 'Aktif' ? 'bg-success' : 'bg-danger' ?>"><?= $pelanggan['status'] ?></span></td></tr>
                                        <tr><td class="fw-bold text-muted">Terdaftar</td><td>: <?= date('d M Y H:i', strtotime($pelanggan['created_at'])) ?></td></tr>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 h-100 d-flex align-items-center justify-content-around">
                                    <div class="text-center">
                                        <h3 class="fw-bold text-primary"><?= $total_booking ?></h3>
                                        <small class="text-muted">Total Booking</small>
                                    </div>
                                    <div class="text-center">
                                        <h3 class="fw-bold text-success"><?= $total_hewan ?></h3>
                                        <small class="text-muted">Total Hewan</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DAFTAR BOOKING -->
                        <h6 class="fw-bold text-primary mb-2"><i class="mdi mdi-book-open-page-variant me-2"></i>Daftar Booking</h6>
                        <?php if (count($bookings) > 0): ?>
                        <div class="table-responsive mb-4">
                            <table class="table table-hover table-sm" style="font-size:14px;">
                                <thead>
                                    <tr>
                                        <th>Kode Booking</th>
                                        <th>Tanggal Mulai</th>
                                        <th>Tanggal Selesai</th>
                                        <th>Status</th>
                                        <th>Total Biaya</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $b): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($b['kode_booking']) ?></strong></td>
                                        <td><?= date('d M Y', strtotime($b['tgl_mulai'])) ?></td>
                                        <td><?= date('d M Y', strtotime($b['tgl_selesai'])) ?></td>
                                        <td><span class="badge <?= 
                                            $b['status_penitipan'] == 'Selesai' ? 'bg-dark' :
                                            ($b['status_penitipan'] == 'Sedang Dititipkan' ? 'bg-success' :
                                            ($b['status_penitipan'] == 'Pending' ? 'bg-warning' : 'bg-danger'))
                                        ?>"><?= htmlspecialchars($b['status_penitipan']) ?></span></td>
                                        <td>Rp <?= number_format($b['total_biaya'] ?? 0, 0, ',', '.') ?></td>
                                        <td class="text-center">
                                            <a href="../bookingan_saya/Riwayat_bookingan.php?id_penitipan=<?= $b['id_penitipan'] ?>" class="btn btn-sm btn-outline-info" title="Lihat Riwayat">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info py-2 mb-4">Belum ada booking.</div>
                        <?php endif; ?>

                        <!-- DAFTAR HEWAN -->
                        <h6 class="fw-bold text-success mb-2"><i class="mdi mdi-paw me-2"></i>Daftar Hewan</h6>
                        <?php if (count($hewans) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm" style="font-size:14px;">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Jenis</th>
                                        <th>Ras</th>
                                        <th>Kelamin</th>
                                        <th>Usia</th>
                                        <th>Berat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hewans as $h): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($h['nama_hewan']) ?></strong></td>
                                        <td><?= htmlspecialchars($h['jenis_hewan']) ?></td>
                                        <td><?= htmlspecialchars($h['ras'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($h['jenis_kelamin']) ?></td>
                                        <td><?= ($h['usia_tahun'] ?? 0) . ' Th ' . ($h['usia_bulan'] ?? 0) . ' Bln' ?></td>
                                        <td><?= number_format($h['berat_badan'] ?? 0, 2) ?> kg</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info py-2">Belum ada hewan.</div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
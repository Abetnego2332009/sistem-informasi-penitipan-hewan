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

$search = trim($_GET['search'] ?? '');
$filter_type = $_GET['type'] ?? 'semua';

// Ambil user (role Pelanggan)
$user_where = "role = 'Pelanggan'";
if ($search) {
    $s = mysqli_real_escape_string($koneksi, $search);
    $user_where .= " AND (nama_lengkap LIKE '%$s%' OR no_telp LIKE '%$s%' OR email LIKE '%$s%')";
}
$q_user = "SELECT id_user AS id, nama_lengkap AS nama, no_telp, alamat, email, 'User' AS tipe, status_akun AS status, created_at 
           FROM users WHERE $user_where";
$res_user = mysqli_query($koneksi, $q_user);

// Ambil pelanggan offline
$pel_where = "1=1";
if ($search) {
    $s = mysqli_real_escape_string($koneksi, $search);
    $pel_where .= " AND (nama_lengkap LIKE '%$s%' OR no_telp LIKE '%$s%')";
}
$q_pel = "SELECT id_pelanggan AS id, nama_lengkap AS nama, no_telp, alamat, NULL AS email, 'Offline' AS tipe, 'Aktif' AS status, created_at 
          FROM pelanggan WHERE $pel_where";
$res_pel = mysqli_query($koneksi, $q_pel);

// Gabung & urutkan
$customers = [];
while ($r = mysqli_fetch_assoc($res_user)) $customers[] = $r;
while ($r = mysqli_fetch_assoc($res_pel)) $customers[] = $r;
usort($customers, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

// Filter tipe
if ($filter_type == 'user') $customers = array_filter($customers, fn($c) => $c['tipe'] == 'User');
if ($filter_type == 'offline') $customers = array_filter($customers, fn($c) => $c['tipe'] == 'Offline');

// Hitung total booking & hewan
foreach ($customers as &$c) {
    $id = $c['id'];
    if ($c['tipe'] == 'User') {
        $bk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM penitipan WHERE id_user = $id"));
        $hw = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM hewan WHERE id_user = $id"));
    } else {
        $bk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM penitipan WHERE id_pelanggan = $id"));
        $hw = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM hewan WHERE id_pelanggan = $id"));
    }
    $c['total_booking'] = $bk['total'] ?? 0;
    $c['total_hewan'] = $hw['total'] ?? 0;
}

$title = 'Data Pelanggan';
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
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <h4 class="card-title"><i class="mdi mdi-account-group me-2"></i>Data Pelanggan</h4>
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <select class="form-select form-select-sm" style="width:130px;" onchange="location.href='?type='+this.value+'&search=<?= htmlspecialchars($search) ?>'">
                                    <option value="semua" <?= $filter_type=='semua'?'selected':'' ?>>Semua</option>
                                    <option value="user" <?= $filter_type=='user'?'selected':'' ?>>User</option>
                                    <option value="offline" <?= $filter_type=='offline'?'selected':'' ?>>Offline</option>
                                </select>
                                <form method="GET" class="d-flex gap-1">
                                    <input type="hidden" name="type" value="<?= htmlspecialchars($filter_type) ?>">
                                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari..." value="<?= htmlspecialchars($search) ?>" style="width:160px;">
                                    <button class="btn btn-sm btn-primary"><i class="mdi mdi-magnify"></i></button>
                                    <?php if ($search || $filter_type!='semua'): ?>
                                        <a href="index.php" class="btn btn-sm btn-light">Reset</a>
                                    <?php endif; ?>
                                </form>
                                <span class="badge bg-warning text-dark"><?= count($customers) ?></span>
                            </div>
                        </div>

                        <!-- TABEL -->
                        <?php if (count($customers) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm" style="font-size:14px;">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama</th>
                                        <th>Telepon</th>
                                        <th>Alamat</th>
                                        <th>Tipe</th>
                                        <th>Status</th>
                                        <th class="text-center">Booking</th>
                                        <th class="text-center">Hewan</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no=1; foreach ($customers as $c): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><strong><?= htmlspecialchars($c['nama'] ?? '-') ?></strong></td>
                                        <td><?= htmlspecialchars($c['no_telp'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($c['alamat'] ? substr($c['alamat'],0,25).(strlen($c['alamat'])>25?'...':'') : '-') ?></td>
                                        <td><span class="badge <?= $c['tipe']=='User'?'bg-primary':'bg-secondary' ?>"><?= $c['tipe'] ?></span></td>
                                        <td><span class="badge <?= $c['status']=='Aktif'?'bg-success':'bg-danger' ?>"><?= $c['status'] ?? 'Aktif' ?></span></td>
                                        <td class="text-center"><?= $c['total_booking'] ?></td>
                                        <td class="text-center"><?= $c['total_hewan'] ?></td>
                                        <td class="text-center">
                                            <?php $type = $c['tipe'] == 'User' ? 'user' : 'offline'; ?>
                                            <a href="detail.php?id=<?= $c['id'] ?>&type=<?= $type ?>" class="btn btn-sm btn-outline-info" title="Detail"><i class="mdi mdi-eye"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="mdi mdi-inbox" style="font-size:40px;color:#ccc;"></i>
                            <p class="text-muted mt-2"><?= $search ? "Tidak ditemukan untuk \"$search\"" : "Belum ada pelanggan." ?></p>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
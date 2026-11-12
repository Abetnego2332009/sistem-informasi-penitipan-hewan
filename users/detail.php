<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] != 'Super Admin' && $_SESSION['role'] != 'Admin')) {
    header('Location: ../login.php');
    exit;
}

$id_user = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$id_user) {
    echo "<script>alert('ID tidak valid!'); window.location='index.php';</script>";
    exit;
}

$query = "SELECT * FROM users WHERE id_user = $id_user";
$res = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($res);

if (!$data) {
    echo "<script>alert('User tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

// Admin tidak bisa melihat detail Super Admin
if ($_SESSION['role'] == 'Admin' && $data['role'] == 'Super Admin') {
    echo "<script>alert('Anda tidak memiliki akses!'); window.location='index.php';</script>";
    exit;
}

$title = 'Detail User';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-8 offset-md-2 grid-margin stretch-card">
                <div class="card card-rounded shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Detail User</h4>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm">Kembali</a>
                        </div>
                        <hr>

                        <div class="row mb-3">
                            <div class="col-md-3 fw-bold text-muted">ID User</div>
                            <div class="col-md-9">: <?= $data['id_user'] ?></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3 fw-bold text-muted">Nama Lengkap</div>
                            <div class="col-md-9">: <strong><?= htmlspecialchars($data['nama_lengkap']) ?></strong></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3 fw-bold text-muted">Username</div>
                            <div class="col-md-9">: <?= htmlspecialchars($data['username'] ?? '-') ?></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3 fw-bold text-muted">Email</div>
                            <div class="col-md-9">: <?= htmlspecialchars($data['email']) ?></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3 fw-bold text-muted">No. Telepon</div>
                            <div class="col-md-9">: <?= htmlspecialchars($data['no_telp']) ?></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3 fw-bold text-muted">Alamat</div>
                            <div class="col-md-9">: <?= htmlspecialchars($data['alamat'] ?? '-') ?></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3 fw-bold text-muted">Role</div>
                            <div class="col-md-9">: 
                                <?php
                                $badge_role = 'bg-secondary';
                                if ($data['role'] == 'Super Admin') $badge_role = 'bg-danger';
                                elseif ($data['role'] == 'Admin') $badge_role = 'bg-warning text-dark';
                                elseif ($data['role'] == 'Pelanggan') $badge_role = 'bg-info';
                                ?>
                                <span class="badge <?= $badge_role; ?>"><?= $data['role']; ?></span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3 fw-bold text-muted">Status</div>
                            <div class="col-md-9">: 
                                <?php if ($data['status_akun'] == 'Aktif'): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Nonaktif</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3 fw-bold text-muted">Tanggal Daftar</div>
                            <div class="col-md-9">: <?= date('d M Y H:i:s', strtotime($data['created_at'])) ?></div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <a href="edit.php?id=<?= $data['id_user']; ?>" class="btn btn-warning text-white btn-sm">Edit</a>
                            <a href="index.php" class="btn btn-light btn-sm">Kembali ke Daftar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
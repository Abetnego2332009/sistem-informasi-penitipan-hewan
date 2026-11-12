<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

// Cek login
if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$id_user = $_SESSION['id_user'];

// Ambil data user
$query = "SELECT * FROM users WHERE id_user = $id_user";
$result = mysqli_query($koneksi, $query);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$title = 'Profile Saya';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';

// Tampilkan pesan dari session
$message = '';
$message_type = '';
if (isset($_SESSION['profile_success'])) {
    $message = $_SESSION['profile_success'];
    $message_type = $_SESSION['profile_success_type'] ?? 'success';
    unset($_SESSION['profile_success'], $_SESSION['profile_success_type']);
}
if (isset($_SESSION['profile_error'])) {
    $message = $_SESSION['profile_error'];
    $message_type = $_SESSION['profile_error_type'] ?? 'danger';
    unset($_SESSION['profile_error'], $_SESSION['profile_error_type']);
}
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-sm-12">
                <div class="home-tab">
                    <div class="d-sm-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
                        <div>
                            <h2 class="welcome-text">Profile <span class="text-black fw-bold">Saya</span></h2>
                            <p class="text-muted">Kelola data diri dan keamanan akun Anda.</p>
                        </div>
                    </div>

                    <!-- PESAN -->
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- KOLOM KIRI: FOTO PROFIL -->
                        <div class="col-md-4 grid-margin stretch-card">
                            <div class="card card-rounded shadow-sm">
                                <div class="card-body text-center">
                                    <h4 class="card-title">Foto Profil</h4>
                                    <hr>
                                    <div class="mb-3">
                                        <?php if (!empty($user['foto_profil'])): ?>
                                            <img src="../uploads/profil/<?= $user['foto_profil'] ?>" 
                                                 alt="Foto Profil" 
                                                 class="rounded-circle border" 
                                                 style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #ddd;">
                                        <?php else: ?>
                                            <div class="rounded-circle border bg-light d-flex align-items-center justify-content-center mx-auto" 
                                                 style="width: 150px; height: 150px; border: 3px solid #ddd;">
                                                <i class="mdi mdi-account" style="font-size: 80px; color: #ccc;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <form method="POST" action="proses.php" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="upload_foto">
                                        <div class="mb-2">
                                            <input type="file" name="foto_profil" class="form-control form-control-sm" accept="image/*" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm text-white w-100 mb-2">Upload Foto</button>
                                    </form>

                                    <?php if (!empty($user['foto_profil'])): ?>
                                        <form method="POST" action="proses.php" onsubmit="return confirm('Yakin hapus foto profil?');">
                                            <input type="hidden" name="action" value="hapus_foto">
                                            <button type="submit" class="btn btn-danger btn-sm text-white w-100">Hapus Foto</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- KOLOM KANAN: DATA DIRI & PASSWORD -->
                        <div class="col-md-8">
                            <!-- DATA DIRI -->
                            <div class="card card-rounded shadow-sm mb-4">
                                <div class="card-body">
                                    <h4 class="card-title">Data Diri</h4>
                                    <hr>

                                    <form method="POST" action="proses.php">
                                        <input type="hidden" name="action" value="update_profile">

                                        <div class="row">
                                            <div class="col-md-6 form-group mb-3">
                                                <label class="fw-bold small">Nama Lengkap <span class="text-danger">*</span></label>
                                                <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-6 form-group mb-3">
                                                <label class="fw-bold small">Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled readonly>
                                                <small class="text-muted">Email tidak dapat diubah.</small>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 form-group mb-3">
                                                <label class="fw-bold small">Nomor Telepon <span class="text-danger">*</span></label>
                                                <input type="text" name="no_telp" class="form-control" value="<?= htmlspecialchars($user['no_telp'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-6 form-group mb-3">
                                                <label class="fw-bold small">Role</label>
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($user['role'] ?? '') ?>" disabled readonly>
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="fw-bold small">Alamat</label>
                                            <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="fw-bold small">Status Akun</label>
                                            <span class="badge <?= ($user['status_akun'] ?? '') == 'Aktif' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $user['status_akun'] ?? 'Aktif' ?>
                                            </span>
                                        </div>

                                        <button type="submit" class="btn btn-primary text-white">Simpan Perubahan</button>
                                    </form>
                                </div>
                            </div>

                            <!-- GANTI PASSWORD -->
                            <div class="card card-rounded shadow-sm">
                                <div class="card-body">
                                    <h4 class="card-title">Ganti Password</h4>
                                    <hr>

                                    <form method="POST" action="proses.php">
                                        <input type="hidden" name="action" value="change_password">

                                        <div class="form-group mb-3">
                                            <label class="fw-bold small">Password Saat Ini <span class="text-danger">*</span></label>
                                            <input type="password" name="current_password" class="form-control" placeholder="Masukkan password saat ini" required>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 form-group mb-3">
                                                <label class="fw-bold small">Password Baru <span class="text-danger">*</span></label>
                                                <input type="password" name="new_password" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                                            </div>
                                            <div class="col-md-6 form-group mb-3">
                                                <label class="fw-bold small">Konfirmasi Password <span class="text-danger">*</span></label>
                                                <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password baru" required>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-warning text-white">Ganti Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
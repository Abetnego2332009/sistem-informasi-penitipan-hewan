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

// Super Admin tidak bisa diedit oleh Admin
if ($_SESSION['role'] == 'Admin' && $data['role'] == 'Super Admin') {
    echo "<script>alert('Anda tidak memiliki akses untuk mengedit Super Admin!'); window.location='index.php';</script>";
    exit;
}

$title = 'Edit User';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>
<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-8 grid-margin stretch-card">
                <div class="card card-rounded shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title">Edit User</h4>

                        <form class="forms-sample" method="POST" action="proses.php">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id_user" value="<?= $data['id_user']; ?>">

                            <div class="form-group mb-3">
                                <label>Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($data['nama_lengkap']); ?>" required>
                            </div>

                            <div class="form-group mb-3">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($data['username'] ?? ''); ?>">
                                <small class="text-muted">Opsional, hanya untuk Admin & Super Admin.</small>
                            </div>

                            <div class="form-group mb-3">
                                <label>Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data['email']); ?>" required>
                            </div>

                            <div class="form-group mb-3">
                                <label>Password (kosongkan jika tidak diubah)</label>
                                <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter">
                            </div>

                            <div class="form-group mb-3">
                                <label>Nomor Telepon <span class="text-danger">*</span></label>
                                <input type="text" name="no_telp" class="form-control" value="<?= htmlspecialchars($data['no_telp']); ?>" required>
                            </div>

                            <div class="form-group mb-3">
                                <label>Alamat</label>
                                <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($data['alamat'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label>Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select" required>
                                    <option value="Super Admin" <?= $data['role'] == 'Super Admin' ? 'selected' : ''; ?> <?= ($_SESSION['role'] != 'Super Admin') ? 'disabled' : ''; ?>>Super Admin</option>
                                    <option value="Admin" <?= $data['role'] == 'Admin' ? 'selected' : ''; ?> <?= ($_SESSION['role'] != 'Super Admin' && $_SESSION['role'] != 'Admin') ? 'disabled' : ''; ?>>Admin</option>
                                    <option value="Pelanggan" <?= $data['role'] == 'Pelanggan' ? 'selected' : ''; ?>>Pelanggan</option>
                                </select>
                                <?php if ($_SESSION['role'] != 'Super Admin'): ?>
                                    <small class="text-warning">* Hanya Super Admin yang bisa mengubah ke Super Admin.</small>
                                <?php endif; ?>
                            </div>

                            <div class="form-group mb-3">
                                <label>Status Akun</label>
                                <select name="status_akun" class="form-select">
                                    <option value="Aktif" <?= $data['status_akun'] == 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="Nonaktif" <?= $data['status_akun'] == 'Nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary text-white me-2">Simpan Perubahan</button>
                            <a href="index.php" class="btn btn-light">Batal</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
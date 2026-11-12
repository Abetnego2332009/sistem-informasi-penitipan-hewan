<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] != 'Super Admin' && $_SESSION['role'] != 'Admin')) {
    header('Location: ../login.php');
    exit;
}

$title = 'Tambah User';
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
                        <h4 class="card-title">Tambah User Baru</h4>

                        <form class="forms-sample" method="POST" action="proses.php" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="insert">

                            <div class="form-group mb-3">
                                <label>Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama lengkap user" required>
                            </div>

                            <div class="form-group mb-3">
                                <label>Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" placeholder="Username unik (untuk login admin)">
                                <small class="text-muted">Opsional, hanya untuk Admin & Super Admin.</small>
                            </div>

                            <div class="form-group mb-3">
                                <label>Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" placeholder="email@domain.com" required>
                            </div>

                            <div class="form-group mb-3">
                                <label>Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                            </div>

                            <div class="form-group mb-3">
                                <label>Nomor Telepon <span class="text-danger">*</span></label>
                                <input type="text" name="no_telp" class="form-control" placeholder="08xxxxxxxxxx" required>
                            </div>

                            <div class="form-group mb-3">
                                <label>Alamat</label>
                                <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat lengkap"></textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label>Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select" required>
                                    <option value="">-- Pilih Role --</option>
                                    <option value="Super Admin" <?= ($_SESSION['role'] != 'Super Admin') ? 'disabled' : ''; ?>>Super Admin</option>
                                    <option value="Admin" <?= ($_SESSION['role'] != 'Super Admin' && $_SESSION['role'] != 'Admin') ? 'disabled' : ''; ?>>Admin</option>
                                    <option value="Pelanggan">Pelanggan</option>
                                </select>
                                <?php if ($_SESSION['role'] != 'Super Admin'): ?>
                                    <small class="text-warning">* Hanya Super Admin yang bisa menambah Super Admin.</small>
                                <?php endif; ?>
                            </div>

                            <div class="form-group mb-3">
                                <label>Status Akun</label>
                                <select name="status_akun" class="form-select">
                                    <option value="Aktif">Aktif</option>
                                    <option value="Nonaktif">Nonaktif</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary text-white me-2">Simpan</button>
                            <a href="index.php" class="btn btn-light">Batal</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
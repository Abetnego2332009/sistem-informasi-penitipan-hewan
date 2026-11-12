<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

// Cek login & role (hanya Super Admin & Admin)
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] != 'Super Admin' && $_SESSION['role'] != 'Admin')) {
    header('Location: ../login.php');
    exit;
}

$title = 'Manajemen User';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';

$search = mysqli_real_escape_string($koneksi, $_GET['search'] ?? '');
$filter_role = mysqli_real_escape_string($koneksi, $_GET['role'] ?? '');
$filter_status = $_GET['status'] ?? '';

$where_clauses = ["1=1"];
if (!empty($search)) {
    $where_clauses[] = "(nama_lengkap LIKE '%$search%' OR email LIKE '%$search%' OR no_telp LIKE '%$search%')";
}
if (!empty($filter_role)) {
    $where_clauses[] = "role = '$filter_role'";
}
if ($filter_status === 'nonaktif') {
    $where_clauses[] = "status_akun = 'Nonaktif'";
} elseif ($filter_status === 'aktif') {
    $where_clauses[] = "status_akun = 'Aktif'";
}
$where_sql = implode(' AND ', $where_clauses);

$query = "SELECT id_user, nama_lengkap, username, email, no_telp, alamat, role, status_akun, foto_profil, created_at 
          FROM users 
          WHERE $where_sql 
          ORDER BY 
            CASE 
                WHEN role = 'Super Admin' THEN 1 
                WHEN role = 'Admin' THEN 2 
                ELSE 3 
            END, 
            nama_lengkap ASC";
$result = mysqli_query($koneksi, $query);
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-sm-12">
                <div class="home-tab">
                    <div class="d-sm-flex align-items-center justify-content-between border-bottom mb-3">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active ps-0" href="#overview" role="tab">Manajemen User</a>
                            </li>
                        </ul>
                        <div>
                            <div class="btn-wrapper">
                                <a href="tambah.php" class="btn btn-primary text-white me-0">
                                    <i class="mdi mdi-plus-box"></i> Tambah User
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- FILTER -->
                    <div class="card card-rounded mb-3">
                        <div class="card-body py-3">
                            <form method="GET" action="index.php" class="row g-2 align-items-center">
                                <div class="col-sm-3">
                                    <input type="text" name="search" class="form-control" placeholder="Cari Nama, Email, Telepon..." value="<?= htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-sm-2">
                                    <select name="role" class="form-select">
                                        <option value="">-- Semua Role --</option>
                                        <option value="Super Admin" <?= $filter_role == 'Super Admin' ? 'selected' : ''; ?>>Super Admin</option>
                                        <option value="Admin" <?= $filter_role == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="Pelanggan" <?= $filter_role == 'Pelanggan' ? 'selected' : ''; ?>>Pelanggan</option>
                                    </select>
                                </div>
                                <div class="col-sm-2">
                                    <select name="status" class="form-select">
                                        <option value="">-- Status --</option>
                                        <option value="aktif" <?= $filter_status == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="nonaktif" <?= $filter_status == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                                    </select>
                                </div>
                                <div class="col-sm-3 d-flex gap-1">
                                    <button type="submit" class="btn btn-dark text-white w-100">Filter</button>
                                    <a href="index.php" class="btn btn-light">Reset</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- TABEL -->
                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card card-rounded shadow-sm">
                                <div class="card-body">
                                    <h4 class="card-title card-title-dash">Daftar User</h4>
                                    <div class="table-responsive mt-1">
                                        <table class="table table-hover table-sm" style="font-size:14px;">
                                            <thead>
                                                <tr>
                                                    <th style="width: 60px;">Foto</th>
                                                    <th>Nama</th>
                                                    <th>Email</th>
                                                    <th>Telepon</th>
                                                    <th>Role</th>
                                                    <th>Status</th>
                                                    <th class="text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (mysqli_num_rows($result) > 0): ?>
                                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                                        <tr>
                                                            <td>
                                                                <?php if (!empty($row['foto_profil'])): ?>
                                                                    <img src="../uploads/profil/<?= $row['foto_profil']; ?>" 
                                                                         alt="Foto" 
                                                                         style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 1px solid #eef2f5;">
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><strong><?= htmlspecialchars($row['nama_lengkap']); ?></strong></td>
                                                            <td><?= htmlspecialchars($row['email']); ?></td>
                                                            <td><?= htmlspecialchars($row['no_telp']); ?></td>
                                                            <td>
                                                                <?php
                                                                $badge_role = 'bg-secondary';
                                                                if ($row['role'] == 'Super Admin') $badge_role = 'bg-danger';
                                                                elseif ($row['role'] == 'Admin') $badge_role = 'bg-warning text-dark';
                                                                elseif ($row['role'] == 'Pelanggan') $badge_role = 'bg-info';
                                                                ?>
                                                                <span class="badge <?= $badge_role; ?>"><?= $row['role']; ?></span>
                                                            </td>
                                                            <td>
                                                                <?php if ($row['status_akun'] == 'Aktif'): ?>
                                                                    <span class="badge bg-success">Aktif</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-danger">Nonaktif</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <div class="d-flex justify-content-center gap-1">
                                                                    <a href="detail.php?id=<?= $row['id_user']; ?>" class="btn btn-info btn-sm text-white py-1 px-2" title="Detail">
                                                                        <i class="mdi mdi-eye"></i>
                                                                    </a>
                                                                    <a href="edit.php?id=<?= $row['id_user']; ?>" class="btn btn-warning btn-sm text-white py-1 px-2" title="Edit">
                                                                        <i class="mdi mdi-pencil"></i>
                                                                    </a>
                                                                    <?php if ($_SESSION['id_user'] != $row['id_user']): ?>
                                                                        <?php if ($row['status_akun'] == 'Aktif'): ?>
                                                                            <form method="POST" action="proses.php" class="d-inline mb-0" onsubmit="return confirm('Nonaktifkan user ini?');">
                                                                                <input type="hidden" name="action" value="delete">
                                                                                <input type="hidden" name="id_user" value="<?= $row['id_user']; ?>">
                                                                                <button type="submit" class="btn btn-danger btn-sm text-white py-1 px-2" title="Nonaktif">
                                                                                    <i class="mdi mdi-delete"></i>
                                                                                </button>
                                                                            </form>
                                                                        <?php else: ?>
                                                                            <form method="POST" action="proses.php" class="d-inline mb-0" onsubmit="return confirm('Aktifkan kembali user ini?');">
                                                                                <input type="hidden" name="action" value="restore">
                                                                                <input type="hidden" name="id_user" value="<?= $row['id_user']; ?>">
                                                                                <button type="submit" class="btn btn-success btn-sm text-white py-1 px-2" title="Aktifkan">
                                                                                    <i class="mdi mdi-check"></i>
                                                                                </button>
                                                                            </form>
                                                                        <?php endif; ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted small" title="Tidak bisa menghapus diri sendiri">🔒</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center py-3">
                                                            <p class="text-muted">Belum ada user.</p>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
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
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

// Ambil semua booking yang statusnya 'Sedang Dititipkan'
$query = "
    SELECT 
        p.id_penitipan,
        p.kode_booking,
        p.tgl_mulai,
        p.tgl_selesai,
        p.status_penitipan,
        u.nama_lengkap AS nama_user,
        COUNT(ph.id_penitipan_hewan) AS jumlah_hewan
    FROM penitipan p
    LEFT JOIN users u ON p.id_user = u.id_user
    LEFT JOIN penitipan_hewan ph ON p.id_penitipan = ph.id_penitipan
    WHERE p.status_penitipan = 'Sedang Dititipkan'
    GROUP BY p.id_penitipan
    ORDER BY p.id_penitipan DESC
";
$result = mysqli_query($koneksi, $query);

$title = 'Status Harian Hewan';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-12">
                <div class="card card-rounded">
                    <div class="card-body">
                        <h4 class="card-title">
                            <i class="mdi mdi-clipboard-text me-2"></i> Status Harian Hewan
                        </h4>
                        <p class="card-description">Daftar booking yang sedang dititipkan.</p>

                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Kode Booking</th>
                                            <th>Pelanggan</th>
                                            <th>Tanggal Titip</th>
                                            <th>Hewan</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($row['kode_booking']) ?></strong></td>
                                                <td><?= htmlspecialchars($row['nama_user'] ?? '-') ?></td>
                                                <td><?= date('d M Y', strtotime($row['tgl_mulai'])) ?> - <?= date('d M Y', strtotime($row['tgl_selesai'])) ?></td>
                                                <td><?= $row['jumlah_hewan'] ?> hewan</td>
                                                <td>
                                                    <span class="badge bg-success"><?= htmlspecialchars($row['status_penitipan']) ?></span>
                                                </td>
                                                <td>
                                                    <a href="list_hewan.php?id_penitipan=<?= $row['id_penitipan'] ?>" class="btn btn-sm btn-primary text-white">
                                                        <i class="mdi mdi-format-list-bulleted"></i> Input Status Hewan
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="mdi mdi-inbox" style="font-size:48px; color:#ccc;"></i>
                                <p class="text-muted mt-3">Belum ada hewan yang sedang dititipkan.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
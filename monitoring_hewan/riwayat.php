<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$id_user = $_SESSION['id_user'];
$id_penitipan = filter_var($_GET['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);

if (!$id_penitipan) {
    echo "<script>alert('ID tidak valid!'); window.location.href='index.php';</script>";
    exit;
}

// Cek kepemilikan
$cek = mysqli_query($koneksi, "SELECT id_penitipan FROM penitipan WHERE id_penitipan = $id_penitipan AND id_user = $id_user");
if (mysqli_num_rows($cek) == 0) {
    echo "<script>alert('Akses ditolak!'); window.location.href='index.php';</script>";
    exit;
}

$query = "
    SELECT s.*, u.nama_lengkap AS nama_admin
    FROM status_harian s
    LEFT JOIN users u ON s.id_user_admin = u.id_user
    WHERE s.id_penitipan = ?
    ORDER BY s.tgl_update DESC, s.jam_update DESC
";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, 'i', $id_penitipan);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

$title = 'Riwayat Status Hewan';
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
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">
                                <i class="mdi mdi-history me-2"></i> Riwayat Status Hewan
                            </h4>
                            <a href="index.php" class="btn btn-light">Kembali</a>
                        </div>

                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Jam</th>
                                            <th>Kondisi</th>
                                            <th>Pola Makan</th>
                                            <th>Aktivitas</th>
                                            <th>Catatan</th>
                                            <th>Foto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td><?= date('d M Y', strtotime($row['tgl_update'])) ?></td>
                                                <td><?= $row['jam_update'] ?></td>
                                                <td>
                                                    <span class="badge <?= $row['kondisi_kesehatan'] == 'Sakit' ? 'bg-danger' : ($row['kondisi_kesehatan'] == 'Kurang Baik' ? 'bg-warning' : 'bg-success') ?>">
                                                        <?= htmlspecialchars($row['kondisi_kesehatan']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($row['pola_makan']) ?></td>
                                                <td><?= htmlspecialchars($row['aktivitas']) ?></td>
                                                <td><?= nl2br(htmlspecialchars($row['catatan_perawat'] ?? '-')) ?></td>
                                                <td>
                                                    <?php if (!empty($row['foto_kondisi']) && file_exists(__DIR__ . '/../' . $row['foto_kondisi'])): ?>
                                                        <a href="../<?= $row['foto_kondisi'] ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                            <i class="mdi mdi-image"></i> Lihat
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="mdi mdi-inbox" style="font-size:48px; color:#ccc;"></i>
                                <p class="text-muted mt-3">Belum ada status harian untuk penitipan ini.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
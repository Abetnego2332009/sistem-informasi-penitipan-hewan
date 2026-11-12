<?php
// ============================================================
// HALAMAN ADMIN: DAFTAR SEMUA TESTIMONI
// ============================================================
session_start();
require_once __DIR__ . '/../koneksi.php';

// ============================================================
// AMBIL SEMUA TESTIMONI DENGAN NAMA USER
// ============================================================
$query = "SELECT t.id_testimoni, t.id_penitipan, t.id_user, t.rating, t.komentar, t.foto_testimoni, t.created_at,
                 u.nama_lengkap, u.email
          FROM testimoni t
          JOIN users u ON t.id_user = u.id_user
          ORDER BY t.created_at DESC";
$result = mysqli_query($koneksi, $query);

$title = 'Semua Testimoni';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-sm-12">
                <div class="home-tab">
                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card card-rounded shadow-sm">
                                <div class="card-body">

                                    <!-- ============================================================
                                    HEADER DI DALAM CARD (KONSISTEN DENGAN HALAMAN PELANGGAN)
                                    ============================================================ -->
                                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                        <h4 class="card-title">
                                            <i class="mdi mdi-star-outline me-2"></i>Daftar Testimoni
                                        </h4>
                                        <div class="d-flex align-items-center gap-2">
                                        </div>
                                    </div>
                                    <p class="text-muted mb-3">Daftar semua testimoni yang telah diberikan oleh pelanggan.</p>

                                    <!-- ============================================================
                                    TABEL
                                    ============================================================ -->
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped table-sm" style="font-size:14px; color: #4a4a4a;">
                                            <thead style="background-color: #f8f9fa;">
                                                <tr>
                                                    <th>No</th>
                                                    <th>Nama User</th>
                                                    <th>Rating</th>
                                                    <th>Komentar</th>
                                                    <th>Foto</th>
                                                    <th>Tanggal</th>
                                                    
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (mysqli_num_rows($result) > 0): ?>
                                                    <?php $no = 1; ?>
                                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                                        <tr>
                                                            <td><?= $no++ ?></td>
                                                            <td><strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong></td>
                                                            <td>
                                                                <span class="text-warning" style="font-size: 1.2rem;">
                                                                    <?= str_repeat('⭐', $row['rating']) ?>
                                                                </span>
                                                            </td>
                                                            <td><?= htmlspecialchars($row['komentar'] ?? '-') ?></td>
                                                            <td>
                                                                <?php if (!empty($row['foto_testimoni'])): ?>
                                                                    <img src="../<?= $row['foto_testimoni'] ?>" 
                                                                         alt="Foto" 
                                                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; cursor: pointer;"
                                                                         onclick="window.open('../<?= $row['foto_testimoni'] ?>', '_blank')">
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center py-3">
                                                            <p class="text-muted">Belum ada testimoni.</p>
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
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_user']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'])) {
    header('Location: ../login.php');
    exit;
}

$title = 'Pengaturan Sistem - Admin';
require '../templates/header.php';
require '../templates/sidebar.php';

// Ambil data dari pengaturan_umum
$umum = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pengaturan_umum LIMIT 1"));
$banner = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM banner LIMIT 1"));
$keunggulan = mysqli_query($koneksi, "SELECT * FROM keunggulan ORDER BY urutan ASC");
$testimoni = mysqli_query($koneksi, "SELECT * FROM testimoni ORDER BY created_at DESC LIMIT 10");

function getVal($data, $key, $default = '') {
    return $data[$key] ?? $default;
}
?>

<style>
    .section-title { font-size:16px; font-weight:600; color:#333; margin-bottom:12px; padding-bottom:6px; border-bottom:1px solid #eee; }
    .info-box { background:#fafafa; border:1px solid #e9ecef; border-radius:8px; padding:16px; }
    .info-box table { margin:0; }
    .info-box table td { padding:6px 0; border:none; font-size:13px; }
    .info-box table .label { color:#888; width:120px; font-weight:500; }
    .info-box table .value { color:#333; }
    .badge-status { font-size:11px; padding:4px 12px; border-radius:20px; }
    .stat-mini { background:#f8f9fa; border:1px solid #e9ecef; border-radius:8px; padding:14px 16px; text-align:center; flex:1; min-width:100px; }
    .stat-mini .number { font-size:22px; font-weight:700; color:#e67e22; }
    .stat-mini .label { font-size:11px; color:#888; margin-top:2px; }
    .map-container { border-radius:8px; overflow:hidden; border:1px solid #e9ecef; }
    .map-container iframe { width:100%; height:380px; display:block; border:0; }
    .keunggulan-card { background:#fafafa; border:1px solid #e9ecef; border-radius:8px; padding:14px; text-align:center; height:100%; }
    .keunggulan-card img { width:60px; height:60px; object-fit:cover; border-radius:50%; margin-bottom:8px; }
    .keunggulan-card .judul { font-weight:600; font-size:13px; margin-bottom:4px; }
    .keunggulan-card .deskripsi { font-size:12px; color:#777; line-height:1.6; }
    /* ===== TOMBOL NATURAL ===== */
.btn {
    border-radius: 8px !important;
    padding: 8px 20px !important;
    font-weight: 500 !important;
    transition: all 0.2s ease !important;
    border: none !important;
}
.btn:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
}

.btn-primary {
    background: #4a6cf7 !important;
    color: #fff !important;
}
.btn-primary:hover {
    background: #3a5bd9 !important;
}

.btn-secondary {
    background: #f1f3f5 !important;
    border: 1px solid #dee2e6 !important;
    color: #495057 !important;
}
.btn-secondary:hover {
    background: #e9ecef !important;
}

.btn-warning {
    background: #ffc107 !important;
    color: #212529 !important;
}
.btn-warning:hover {
    background: #e0a800 !important;
}

.btn-danger {
    background: #dc3545 !important;
    color: #fff !important;
}
.btn-danger:hover {
    background: #c82333 !important;
}

.btn-success {
    background: #28a745 !important;
    color: #fff !important;
}
.btn-success:hover {
    background: #218838 !important;
}

.btn-info {
    background: #17a2b8 !important;
    color: #fff !important;
}
.btn-info:hover {
    background: #138496 !important;
}

.btn-round {
    border-radius: 50px !important;
}

.btn-lg {
    padding: 10px 28px !important;
    font-size: 16px !important;
}

.btn-sm {
    padding: 4px 12px !important;
    font-size: 12px !important;
}

/* Tombol Edit Semua di index */
.btn-primary.btn-round {
    padding: 8px 24px !important;
}
</style>

<div class="main-panel">
    <?php require '../templates/navbar.php'; ?>
    <div class="container">
        <div class="page-inner">

            <div class="d-flex align-items-center justify-content-between pt-2 pb-4">
                <div>
                    <h3 class="fw-bold mb-3">Pengaturan Sistem</h3>
                    <h6 class="op-7 mb-2">Kelola semua konten landing page</h6>
                </div>
                <div>
                    <a href="edit.php" class="btn btn-primary btn-round">
                        <i class="fas fa-edit me-1"></i> Edit Semua
                    </a>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <strong>Berhasil!</strong> Data telah diperbarui.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- ========================================================== -->
            <!-- CARD 1: PROFIL TOKO -->
            <!-- ========================================================== -->
            <div class="card card-round mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <?php if (!empty($umum['foto_toko'])): ?>
                                <img src="../uploads/profil/<?= htmlspecialchars($umum['foto_toko']) ?>"
                                    class="img-fluid rounded border"
                                    style="width:100%; max-height:200px; object-fit:cover;">
                            <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                    style="height:180px; border:1px solid #ddd;">
                                    <i class="fas fa-store fa-3x text-secondary"></i>
                                </div>
                            <?php endif; ?>
                            <div class="mt-2">
                                <span class="badge <?= getVal($umum, 'status_toko') == 'buka' ? 'bg-success' : 'bg-danger' ?> badge-status">
                                    <?= getVal($umum, 'status_toko') == 'buka' ? '● Buka' : '● Tutup' ?>
                                </span>
                                <form method="POST" action="proses.php" class="mt-2">
                                    <input type="hidden" name="aksi" value="toggle_status_toko">
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input" type="checkbox" name="status_toko" value="buka"
                                               id="toggleStatusToko" <?= getVal($umum, 'status_toko') == 'buka' ? 'checked' : '' ?>
                                               onchange="this.form.submit()" style="width:50px; height:26px; cursor:pointer;">
                                        <label class="form-check-label fw-bold" for="toggleStatusToko" style="font-size:13px;">
                                            <?= getVal($umum, 'status_toko') == 'buka' ? 'Tutup Toko' : 'Buka Toko' ?>
                                        </label>
                                    </div>
                                </form>
                            </div>
                            <h5 class="fw-bold mt-2" style="font-size:16px;"><?= htmlspecialchars(getVal($umum, 'nama_toko')) ?></h5>
                            <span class="badge bg-secondary"><?= htmlspecialchars(getVal($umum, 'alamat')) ?></span>
                        </div>
                        <div class="col-md-8">
                            <h6 class="section-title">Identitas Toko</h6>
                            <div class="info-box">
                                <table>
                                    <tr><td class="label">Nama Toko</td><td class="value"><?= htmlspecialchars(getVal($umum, 'nama_toko')) ?></td></tr>
                                    <tr><td class="label">Alamat</td><td class="value"><?= htmlspecialchars(getVal($umum, 'alamat')) ?></td></tr>
                                    <tr><td class="label">Telepon</td><td class="value"><?= htmlspecialchars(getVal($umum, 'telepon')) ?></td></tr>
                                    <tr><td class="label">Email</td><td class="value"><?= htmlspecialchars(getVal($umum, 'email')) ?></td></tr>
                                    <tr><td class="label">Website</td><td class="value"><?= htmlspecialchars(getVal($umum, 'website')) ?></td></tr>
                                    <tr><td class="label">Jam Operasional</td><td class="value"><?= htmlspecialchars(getVal($umum, 'jam_operasional')) ?></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h6 class="section-title">Deskripsi Toko</h6>
                            <div class="info-box"><?= nl2br(htmlspecialchars(getVal($umum, 'deskripsi_toko'))) ?></div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="section-title">Sejarah Toko</h6>
                            <div class="info-box"><?= nl2br(htmlspecialchars(getVal($umum, 'sejarah_toko'))) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================== -->
            <!-- CARD 2: BANNER -->
            <!-- ========================================================== -->
            <div class="card card-round mb-4">
                <div class="card-body">
                    <h6 class="section-title">Kata Sambutan (Banner)</h6>
                    <div class="info-box">
                        <table>
                            <tr><td class="label" style="width:140px;">Judul Utama</td><td class="value"><strong><?= htmlspecialchars(getVal($banner, 'judul_utama')) ?></strong></td></tr>
                            <tr><td class="label">Sub Judul</td><td class="value"><?= htmlspecialchars(getVal($banner, 'sub_judul')) ?></td></tr>
                            <tr><td class="label">Deskripsi</td><td class="value"><?= nl2br(htmlspecialchars(getVal($banner, 'deskripsi'))) ?></td></tr>
                            <tr><td class="label">Tombol Kiri</td><td class="value"><span class="badge bg-primary"><?= htmlspecialchars(getVal($banner, 'tombol_kiri_teks')) ?></span> <small class="text-muted">(Link: <?= htmlspecialchars(getVal($banner, 'tombol_kiri_link')) ?>)</small></td></tr>
                            <tr><td class="label">Tombol Kanan</td><td class="value"><span class="badge bg-secondary"><?= htmlspecialchars(getVal($banner, 'tombol_kanan_teks')) ?></span> <small class="text-muted">(Link: <?= htmlspecialchars(getVal($banner, 'tombol_kanan_link')) ?>)</small></td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================================== -->
            <!-- CARD 3: KEUNGGULAN -->
            <!-- ========================================================== -->
            <div class="card card-round mb-4">
                <div class="card-body">
                    <h6 class="section-title">Keunggulan Layanan</h6>
                    <?php if (mysqli_num_rows($keunggulan) > 0): ?>
                        <div class="row g-3">
                            <?php while ($row = mysqli_fetch_assoc($keunggulan)): ?>
                                <div class="col-md-4">
                                    <div class="keunggulan-card">
                                        <?php if (!empty($row['gambar'])): ?>
                                            <img src="../uploads/keunggulan/<?= htmlspecialchars($row['gambar']) ?>">
                                        <?php else: ?>
                                            <div style="font-size:2.2rem;">⭐</div>
                                        <?php endif; ?>
                                        <div class="judul"><?= htmlspecialchars($row['judul']) ?></div>
                                        <div class="deskripsi"><?= htmlspecialchars($row['deskripsi']) ?></div>
                                        <div class="mt-2"><span class="badge <?= $row['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $row['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">Belum ada keunggulan.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ========================================================== -->
            <!-- CARD 4: PENGATURAN LANDING (HANYA TICKER) -->
            <!-- ========================================================== -->
            <div class="card card-round mb-4">
                <div class="card-body">
                    <h6 class="section-title">Pengaturan Landing Page</h6>
                    <div class="d-flex flex-wrap gap-3">
                        <!-- Hanya toggle ticker yang tersisa -->
                        <div class="stat-mini">
                            <form method="POST" action="proses.php">
                                <input type="hidden" name="aksi" value="toggle_ticker">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="ticker_aktif" value="1"
                                           id="toggleTicker" <?= getVal($umum, 'ticker_aktif', 1) == 1 ? 'checked' : '' ?>
                                           onchange="this.form.submit()" style="width:50px; height:26px; cursor:pointer;">
                                    <label class="form-check-label fw-bold" for="toggleTicker" style="font-size:13px;">
                                        <?= getVal($umum, 'ticker_aktif', 1) == 1 ? 'Ticker Aktif' : 'Ticker Nonaktif' ?>
                                    </label>
                                </div>
                                <div class="label mt-1">Ticker Pengumuman</div>
                            </form>
                        </div>
                    </div>
                    <?php if (!empty(getVal($umum, 'ticker_teks'))): ?>
                        <div class="mt-3 alert alert-info py-2">📢 <?= htmlspecialchars(getVal($umum, 'ticker_teks')) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ========================================================== -->
            <!-- CARD 5: GOOGLE MAPS -->
            <!-- ========================================================== -->
            <div class="card card-round mb-4">
                <div class="card-body">
                    <h6 class="section-title">Google Maps</h6>
                    <?php if (!empty(getVal($umum, 'google_maps_embed')) && strpos(getVal($umum, 'google_maps_embed'), 'google.com/maps') !== false): ?>
                        <div class="map-container">
                            <iframe src="<?= htmlspecialchars(getVal($umum, 'google_maps_embed')) ?>" allowfullscreen loading="lazy"></iframe>
                        </div>
                    <?php else: ?>
                        <div class="info-box text-center py-5"><p class="text-muted">Belum ada peta Google Maps.</p></div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
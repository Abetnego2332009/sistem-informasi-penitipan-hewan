<?php
session_start();
require_once __DIR__ . '/../koneksi.php';


if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$role = $_SESSION['role'] ?? 'Pelanggan';
if ($role != 'Super Admin' && $role != 'Admin') {
    echo "<script>alert('Akses ditolak! Hanya Admin.'); window.location.href='../../dashboard.php';</script>";
    exit;
}
// ============================================================
// HITUNG JUMLAH PER STATUS
// ============================================================
$count_menunggu = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pembayaran WHERE status_bayar = 'Menunggu Konfirmasi'"))['total'] ?? 0;
$count_disetujui = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM penitipan WHERE status_penitipan = 'Disetujui'"))['total'] ?? 0;
$count_perpanjangan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM riwayat_perpanjangan WHERE status = 'Menunggu Konfirmasi'"))['total'] ?? 0;
// ============================================================
// FILTER & PENCARIAN
// ============================================================
$filter = $_GET['filter'] ?? 'menunggu';
$search = trim($_GET['search'] ?? '');

// Build WHERE clause
// Di bagian filter (baris 16-25), tambahkan:
if ($filter == 'perpanjangan') {
    $where = "rp.status = 'Menunggu Konfirmasi' AND rp.id_penitipan = p.id_penitipan";
} elseif ($filter == 'menunggu') {
    $where = "pay.status_bayar = 'Menunggu Konfirmasi'";
} elseif ($filter == 'disetujui') {
    $where = "p.status_penitipan = 'Disetujui'";
} else {
    $where = "pay.status_bayar = 'Menunggu Konfirmasi'";
}

// Tambahkan pencarian jika ada
if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($koneksi, $search);
    $where .= " AND u.nama_lengkap LIKE '%$search_escaped%'";
}

// ============================================================
// AMBIL DATA
// ============================================================
$query = "
    SELECT 
        pay.*,
        p.id_penitipan, 
        p.kode_booking,
        p.tgl_mulai,
        p.tgl_selesai,
        p.total_biaya,
        p.status_penitipan,
        u.nama_lengkap AS nama_pelanggan,
        u.no_telp AS no_telp_pelanggan,
        (SELECT COUNT(*) FROM penitipan_hewan WHERE id_penitipan = p.id_penitipan) AS jumlah_hewan,
        rp.id_perpanjangan,
        rp.id_kandang,
        rp.tgl_selesai_baru,
        rp.biaya_tambahan,
        rp.nama_pembayar AS nama_pembayar_perpanjangan,
        rp.jumlah_bayar AS jumlah_bayar_perpanjangan,
        rp.bukti_pembayaran AS bukti_perpanjangan,
        rp.status AS status_perpanjangan,           
        rp.created_at,
        k.kode_kandang,
        k.nama_kandang
    FROM pembayaran pay
    JOIN penitipan p ON pay.id_penitipan = p.id_penitipan
    JOIN users u ON p.id_user = u.id_user
    LEFT JOIN riwayat_perpanjangan rp ON p.id_penitipan = rp.id_penitipan
    LEFT JOIN kandang k ON rp.id_kandang = k.id_kandang
    WHERE $where
    ORDER BY pay.tgl_bayar ASC
";
$result = mysqli_query($koneksi, $query);

$title = 'Konfirmasi Pembayaran & Penitipan';
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

                        <!-- ============================================================ -->
                        <!-- HEADER + FILTER + SEARCH                                    -->
                        <!-- ============================================================ -->
                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                            <h4 class="card-title mb-0">
                                <i class="mdi mdi-credit-card-check me-2"></i> Konfirmasi Pembayaran & Penitipan
                            </h4>
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <!-- FILTER DROPDOWN -->
                                <div class="d-flex align-items-center gap-2">
    <label>Filter:</label>
    <select class="form-select form-select-sm" onchange="window.location.href='?filter='+this.value+'&search=<?= htmlspecialchars($search) ?>'">
        <option value="menunggu" <?= $filter=='menunggu'?'selected':'' ?>>Menunggu Konfirmasi</option>
        <option value="disetujui" <?= $filter=='disetujui'?'selected':'' ?>>Disetujui (Belum Datang)</option>
        <option value="perpanjangan" <?= $filter=='perpanjangan'?'selected':'' ?>>Perpanjangan</option>
    </select>
    <!-- BADGE DI LUAR SELECT -->
   <?php 
$badge_count = 0;
if ($filter == 'menunggu') $badge_count = $count_menunggu;
elseif ($filter == 'disetujui') $badge_count = $count_disetujui;
elseif ($filter == 'perpanjangan') $badge_count = $count_perpanjangan;
if ($badge_count > 0): ?>
    <span class="badge bg-danger rounded-pill"><?= $badge_count ?></span>
<?php endif; ?>
</div>


                                
                                <!-- SEARCH BOX -->
                                <!-- ============================================================ -->
                                <!-- SEARCH + FILTER                                              -->
                                <!-- ============================================================ -->
                                <!-- ============================================================ -->
                                <!-- SEARCH + FILTER (KAYAK KETERSEDIAAN KANDANG)                 -->
                                <!-- ============================================================ -->
                                <div class="card card-rounded shadow-sm mb-4">
                                    <div class="card-body p-3">
                                        <form method="GET" class="row g-2 align-items-end">
                                            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">

                                            <!-- Search -->
                                            <div class="col-md-8 col-sm-6">
                                                <label class="form-label small fw-bold text-dark">Cari Pelanggan</label>
                                                <input type="text" name="search" class="form-control form-control-sm" placeholder="Ketik nama pelanggan..." value="<?= htmlspecialchars($search) ?>">
                                            </div>

                                            <!-- Tombol -->
                                            <div class="col-md-4 col-sm-6 d-flex gap-2">
                                                <button type="submit" class="btn btn-primary btn-sm text-white flex-grow-1 py-2 fw-bold">
                                                    <i class="mdi mdi-magnify"></i> Filter
                                                </button>
                                                <a href="?filter=<?= htmlspecialchars($filter) ?>" class="btn btn-light btn-sm flex-grow-1 py-2 text-dark border">
                                                    <i class="mdi mdi-refresh"></i> Reset
                                                </a>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- BADGE JUMLAH -->
                                <span class="badge bg-warning text-dark"><?= mysqli_num_rows($result) ?> Data</span>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- TABEL DATA                                                  -->
                        <!-- ============================================================ -->
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Kode Pembayaran</th>
                                            <th>Kode Booking</th>
                                            <th>Pelanggan</th>
                                            <th>Metode</th>
                                            <th>Jumlah</th>
                                            <th>Tgl Bayar</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($row['kode_pembayaran']) ?></strong></td>
                                                <td><?= htmlspecialchars($row['kode_booking']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($row['nama_pelanggan']) ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($row['no_telp_pelanggan']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($row['metode_bayar']) ?></td>
                                                <td class="fw-bold text-primary">
                                                    <?php if ($filter == 'perpanjangan'): ?>
                                                        <?php
                                                        // Tampilkan jumlah bayar perpanjangan (prioritaskan jumlah_bayar, fallback ke biaya_tambahan)
                                                        $amount = $row['jumlah_bayar_perpanjangan'] ?? $row['biaya_tambahan'] ?? 0;
                                                        ?>
                                                        Rp <?= number_format($amount, 0, ',', '.') ?>
                                                    <?php else: ?>
                                                        Rp <?= number_format($row['jumlah_tagihan'], 0, ',', '.') ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($filter == 'perpanjangan'): ?>
                                                        <?php
                                                        $tgl_perpanjangan =  $row['created_at'] ?? null;
                                                        ?>
                                                        <?= !empty($tgl_perpanjangan) ? date('d M Y H:i', strtotime($tgl_perpanjangan)) : '-' ?>
                                                    <?php else: ?>
                                                        <?= date('d M Y H:i', strtotime($row['tgl_bayar'])) ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($filter == 'menunggu'): ?>
                                                        <!-- TOMBOL UNTUK MENUNGGU KONFIRMASI -->
                                                        <div class="d-flex gap-1 flex-wrap">
                                                            <a href="detail.php?id_penitipan=<?= $row['id_penitipan'] ?>" class="btn btn-sm btn-info text-white">
                                                                <i class="mdi mdi-eye"></i> Detail
                                                            </a>
                                                            <form method="POST" action="proses.php" style="display:inline;">
                                                                <input type="hidden" name="id_pembayaran" value="<?= $row['id_pembayaran'] ?>">
                                                                <input type="hidden" name="id_penitipan" value="<?= $row['id_penitipan'] ?>">
                                                                <input type="hidden" name="action" value="konfirmasi">
                                                                <button type="submit" class="btn btn-sm btn-success text-white" onclick="return confirm('Konfirmasi pembayaran ini?')">
                                                                    <i class="mdi mdi-check"></i> Setuju
                                                                </button>
                                                            </form>
                                                            <button type="button" class="btn btn-sm btn-danger text-white" data-bs-toggle="modal" data-bs-target="#modalTolak<?= $row['id_pembayaran'] ?>">
                                                                <i class="mdi mdi-close"></i> Tolak
                                                            </button>
                                                        </div>

                                                        <!-- MODAL TOLAK PEMBAYARAN -->
                                                        <div class="modal fade" id="modalTolak<?= $row['id_pembayaran'] ?>" tabindex="-1">
                                                            <div class="modal-dialog modal-dialog-centered">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title text-danger"><i class="mdi mdi-alert-circle me-2"></i> Tolak Pembayaran</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <form method="POST" action="proses.php">
                                                                        <div class="modal-body">
                                                                            <input type="hidden" name="id_pembayaran" value="<?= $row['id_pembayaran'] ?>">
                                                                            <input type="hidden" name="id_penitipan" value="<?= $row['id_penitipan'] ?>">
                                                                            <input type="hidden" name="action" value="tolak">
                                                                            <div class="mb-3">
                                                                                <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                                                                                <textarea name="alasan_penolakan" class="form-control" rows="3" placeholder="Contoh: Bukti transfer tidak jelas, nominal tidak sesuai..." required></textarea>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                                            <button type="submit" class="btn btn-danger text-white">Tolak</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    <?php elseif ($filter == 'disetujui'): ?>
                                                        <!-- TOMBOL UNTUK DISETUJUI (BELUM DATANG) -->
                                                        <div class="d-flex gap-1 flex-wrap">
                                                            <a href="detail.php?id_penitipan=<?= $row['id_penitipan'] ?>" class="btn btn-sm btn-info text-white">
                                                                <i class="mdi mdi-eye"></i> Detail
                                                            </a>
                                                            <form method="POST" action="proses.php" style="display:inline;">
                                                                <input type="hidden" name="id_penitipan" value="<?= $row['id_penitipan'] ?>">
                                                                <input type="hidden" name="action" value="mulai_titip">
                                                                <button type="submit" class="btn btn-sm btn-success text-white" onclick="return confirm('Hewan sudah diantar ke toko?')">
                                                                    <i class="mdi mdi-paw"></i> Mulai Titip
                                                                </button>
                                                            </form>
                                                        </div>

                                                    <?php elseif ($filter == 'perpanjangan'): ?>
                                                        <!-- TOMBOL UNTUK PERPANJANGAN MENUNGGU KONFIRMASI -->
                                                        <div class="d-flex gap-1 flex-wrap">
                                                            <a href="../masa_aktif/detail_pembayaran.php?id_penitipan=<?= $row['id_penitipan'] ?>&id_kandang=<?= $row['id_kandang'] ?>" class="btn btn-sm btn-info text-white">
                                                                <i class="mdi mdi-eye"></i> Detail
                                                            </a>
                                                            <form method="POST" action="proses.php" style="display:inline;">
                                                                <input type="hidden" name="id_perpanjangan" value="<?= $row['id_perpanjangan'] ?>">
                                                                <input type="hidden" name="id_penitipan" value="<?= $row['id_penitipan'] ?>">
                                                                <input type="hidden" name="action" value="konfirmasi_perpanjangan">
                                                                <button type="submit" class="btn btn-sm btn-success text-white" onclick="return confirm('Konfirmasi perpanjangan ini?')">
                                                                    <i class="mdi mdi-check"></i> Setuju
                                                                </button>
                                                            </form>
                                                            <button type="button" class="btn btn-sm btn-danger text-white" data-bs-toggle="modal" data-bs-target="#modalTolakPerpanjangan<?= $row['id_perpanjangan'] ?>">
                                                                <i class="mdi mdi-close"></i> Tolak
                                                            </button>
                                                        </div>

                                                        <!-- Modal Tolak Perpanjangan -->
                                                        <div class="modal fade" id="modalTolakPerpanjangan<?= $row['id_perpanjangan'] ?>" tabindex="-1">
                                                            <div class="modal-dialog modal-dialog-centered">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title text-danger"><i class="mdi mdi-alert-circle me-2"></i> Tolak Perpanjangan</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <form method="POST" action="proses.php">
                                                                        <div class="modal-body">
                                                                            <input type="hidden" name="id_perpanjangan" value="<?= $row['id_perpanjangan'] ?>">
                                                                            <input type="hidden" name="id_penitipan" value="<?= $row['id_penitipan'] ?>">
                                                                            <input type="hidden" name="action" value="tolak_perpanjangan">
                                                                            <div class="mb-3">
                                                                                <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                                                                                <textarea name="alasan_penolakan" class="form-control" rows="3" placeholder="Contoh: Bukti transfer tidak jelas, nominal tidak sesuai..." required></textarea>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                                            <button type="submit" class="btn btn-danger text-white">Tolak</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>

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
                                <p class="text-muted mt-3">
                                    <?php if (!empty($search)): ?>
                                        Tidak ada data untuk pelanggan "<strong><?= htmlspecialchars($search) ?></strong>"
                                    <?php else: ?>
                                        Tidak ada data untuk filter ini.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
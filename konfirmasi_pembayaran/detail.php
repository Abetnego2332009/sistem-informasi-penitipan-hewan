<?php

/**
 * detail.php - Halaman Detail Pembayaran (Admin)
 * Menampilkan informasi lengkap pembayaran berdasarkan id_penitipan
 */

session_start();
require_once __DIR__ . '/../koneksi.php';

// ============================================================
// 1. VALIDASI SESSION & ROLE
// ============================================================
if (!isset($_SESSION['id_user'])) {
    echo "<script>alert('Anda harus login terlebih dahulu!'); window.location.href='../login.php';</script>";
    exit;
}

$role = $_SESSION['role'] ?? 'Pelanggan';
if ($role != 'Super Admin' && $role != 'Admin') {
    echo "<script>alert('Akses ditolak! Hanya Admin.'); window.location.href='../../dashboard.php';</script>";
    exit;
}

$id_user = $_SESSION['id_user'];

// ============================================================
// 2. AMBIL ID PENITIPAN DARI URL
// ============================================================
$id_penitipan = filter_var($_GET['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);

if (!$id_penitipan) {
    echo "<script>alert('ID Booking tidak valid!'); window.location.href='index.php';</script>";
    exit;
}

// ============================================================
// 3. CEK KEPEMILIKAN BOOKING & AMBIL DATA PEMBAYARAN
// ============================================================
$query = "
    SELECT 
        p.*,
        pay.id_pembayaran,
        pay.kode_pembayaran,
        pay.tgl_bayar,
        pay.metode_bayar,
        pay.nama_pembayar,
        pay.jumlah_tagihan,
        pay.jumlah_bayar,
        pay.bukti_pembayaran,
        pay.status_bayar,
        pay.catatan,
        pay.created_at AS tgl_pembayaran_dibuat,
        u.nama_lengkap AS nama_user
    FROM penitipan p
    LEFT JOIN pembayaran pay ON p.id_penitipan = pay.id_penitipan
    LEFT JOIN users u ON p.id_user = u.id_user
    WHERE p.id_penitipan = ?
";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, 'i', $id_penitipan);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data) {
    echo "<script>alert('Data pembayaran tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}

// Jika belum ada pembayaran
if (empty($data['kode_pembayaran'])) {
    echo "<script>alert('Belum ada pembayaran untuk booking ini!'); window.location.href='index.php';</script>";
    exit;
}

// ============================================================
// 4. AMBIL DETAIL KANDANG
// ============================================================
$query_kandang = "
    SELECT 
        k.id_kandang,
        k.kode_kandang,
        k.nama_kandang,
        k.tarif_per_hari,
        MIN(ph.tgl_mulai) AS tgl_mulai,
        MAX(ph.tgl_selesai) AS tgl_selesai,
        COUNT(ph.id_hewan) AS jumlah_hewan,
        GROUP_CONCAT(h.nama_hewan SEPARATOR ', ') AS daftar_hewan,
        DATEDIFF(MAX(ph.tgl_selesai), MIN(ph.tgl_mulai)) AS durasi_hari
    FROM penitipan_hewan ph
    JOIN kandang k ON ph.id_kandang = k.id_kandang
    LEFT JOIN hewan h ON ph.id_hewan = h.id_hewan
    WHERE ph.id_penitipan = ?
    GROUP BY k.id_kandang
    ORDER BY k.kode_kandang ASC
";
$stmt_kandang = mysqli_prepare($koneksi, $query_kandang);
mysqli_stmt_bind_param($stmt_kandang, 'i', $id_penitipan);
mysqli_stmt_execute($stmt_kandang);
$result_kandang = mysqli_stmt_get_result($stmt_kandang);
$detail_kandang = [];
while ($row = mysqli_fetch_assoc($result_kandang)) {
    $detail_kandang[] = $row;
}
mysqli_stmt_close($stmt_kandang);

// ============================================================
// 5. TITLE & HEADER
// ============================================================
$title = 'Detail Pembayaran - Admin';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-8 grid-margin stretch-card mx-auto">
                <div class="card card-rounded shadow-sm">
                    <div class="card-body">

                        <!-- HEADER -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title text-primary mb-0">
                                <i class="mdi mdi-credit-card-outline me-2"></i> Detail Pembayaran
                                <small class="text-muted">(Admin)</small>
                            </h4>
                            <a href="index.php" class="btn btn-light btn-sm">
                                <i class="mdi mdi-arrow-left"></i> Kembali
                            </a>
                        </div>

                        <!-- Status Badge -->
                        <div class="alert <?= $data['status_bayar'] == 'Lunas' ? 'alert-success' : 'alert-warning' ?> mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">
                                    <i class="mdi mdi-information-outline me-2"></i>
                                    Status Pembayaran:
                                </span>
                                <span class="badge <?= $data['status_bayar'] == 'Lunas' ? 'bg-success' : 'bg-warning text-dark' ?> p-2">
                                    <?= htmlspecialchars($data['status_bayar'] ?? 'Menunggu Konfirmasi') ?>
                                </span>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- TABLE 1: INFORMASI PEMBAYARAN                                 -->
                        <!-- ============================================================ -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <tr>
                                    <td class="text-muted" style="width: 35%;">Kode Pembayaran</td>
                                    <td><strong><?= htmlspecialchars($data['kode_pembayaran']) ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Kode Booking</td>
                                    <td><strong><?= htmlspecialchars($data['kode_booking']) ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nama Pembayar</td>
                                    <td><?= htmlspecialchars($data['nama_pembayar'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Metode Pembayaran</td>
                                    <td><?= htmlspecialchars($data['metode_bayar'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tanggal Bayar</td>
                                    <td><?= !empty($data['tgl_bayar']) ? date('d M Y H:i:s', strtotime($data['tgl_bayar'])) : '-' ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Jumlah Tagihan</td>
                                    <td><strong class="text-danger">Rp <?= number_format($data['jumlah_tagihan'] ?? 0, 0, ',', '.') ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Jumlah Dibayar</td>
                                    <td><strong class="text-success">Rp <?= number_format($data['jumlah_bayar'] ?? 0, 0, ',', '.') ?></strong></td>
                                </tr>
                                <!-- Detail Penitipan -->
                                <tr>
                                    <td class="text-muted" style="width: 35%; vertical-align: top;">Detail Penitipan</td>
                                    <td>
                                        <?php if (count($detail_kandang) > 0): ?>
                                            <?php foreach ($detail_kandang as $dk):
                                                $durasi = ($dk['durasi_hari'] <= 0) ? 1 : $dk['durasi_hari'];
                                                $tgl_mulai = date('d M Y', strtotime($dk['tgl_mulai']));
                                                $tgl_selesai = date('d M Y', strtotime($dk['tgl_selesai']));
                                            ?>
                                                <div class="py-1 border-bottom">
                                                    <div class="d-flex justify-content-between mt-2">
                                                        <span><strong><?= htmlspecialchars($dk['kode_kandang']) ?></strong> - <?= htmlspecialchars($dk['nama_kandang']) ?></span>
                                                        <span><?= $durasi ?> hari (<?= $tgl_mulai ?> - <?= $tgl_selesai ?>)</span>
                                                    </div>
                                                    <div class="text-muted small mt-2" style="font-size: 12px;">
                                                        <i class="mdi mdi-paw"></i> <?= $dk['jumlah_hewan'] ?> hewan: <?= htmlspecialchars($dk['daftar_hewan']) ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            <div class="text-end mt-1">
                                                <small class="text-muted">Total: <?= count($detail_kandang) ?> kandang</small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Tidak ada data kandang</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Catatan</td>
                                    <td><?= htmlspecialchars($data['catatan'] ?? '-') ?></td>
                                </tr>
                            </table>
                        </div>

                        <!-- ============================================================ -->
                        <!-- TABLE 2: INFORMASI PELANGGAN                                 -->
                        <!-- ============================================================ -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <tr>
                                    <td class="text-muted" style="width: 35%;">Nama Pelanggan</td>
                                    <td><strong><?= htmlspecialchars($data['nama_user'] ?? '-') ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tanggal Mulai</td>
                                    <td><strong><?= date('d M Y', strtotime($data['tgl_mulai'])) ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tanggal Selesai</td>
                                    <td><strong><?= date('d M Y', strtotime($data['tgl_selesai'])) ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Status Penitipan</td>
                                    <td>
                                        <span class="badge <?= $data['status_penitipan'] == 'Disetujui' ? 'bg-success' : 'bg-warning' ?>">
                                            <?= htmlspecialchars($data['status_penitipan'] ?? '-') ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- ============================================================ -->
                        <!-- BUKTI TRANSFER                                              -->
                        <!-- ============================================================ -->
                        <?php if (!empty($data['bukti_pembayaran']) && file_exists(__DIR__ . '/../' . $data['bukti_pembayaran'])): ?>
                            <div class="text-center mt-3">
                                <label class="fw-bold text-dark d-block mb-2">Bukti Transfer</label>
                                <img src="../<?= $data['bukti_pembayaran'] ?>" alt="Bukti Transfer" style="max-width: 100%; max-height: 300px; margin-top: 10px; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                <br><br>
                                <a href="../<?= $data['bukti_pembayaran'] ?>" target="_blank" class="btn btn-outline-primary">
                                    <i class="mdi mdi-file-image me-2"></i> Lihat Bukti Transfer
                                </a>
                            </div>
                        <?php elseif (!empty($data['bukti_pembayaran'])): ?>
                            <div class="text-center mt-3">
                                <label class="fw-bold text-dark d-block mb-2">Bukti Transfer</label>
                                <a href="../<?= $data['bukti_pembayaran'] ?>" target="_blank" class="btn btn-outline-primary">
                                    <i class="mdi mdi-file-image me-2"></i> Lihat Bukti Transfer
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center mt-3">
                                <span class="text-muted">Tidak ada bukti transfer (pembayaran tunai)</span>
                            </div>
                        <?php endif; ?>

                        <!-- ============================================================ -->
                        <!-- TOMBOL AKSI (BERDASARKAN STATUS)                              -->
                        <!-- ============================================================ -->
                        <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                            <a href="index.php" class="btn btn-light">
                                <i class="mdi mdi-arrow-left"></i> Kembali ke Daftar
                            </a>
                            <div class="d-flex gap-2">

                                <?php if ($data['status_bayar'] == 'Menunggu Konfirmasi'): ?>
                                    <!-- TOMBOL UNTUK MENUNGGU KONFIRMASI -->
                                    <form method="POST" action="proses.php" style="display:inline;">
                                        <input type="hidden" name="id_pembayaran" value="<?= $data['id_pembayaran'] ?>">
                                        <input type="hidden" name="id_penitipan" value="<?= $id_penitipan ?>">
                                        <input type="hidden" name="action" value="konfirmasi">
                                        <button type="submit" class="btn btn-success text-white" onclick="return confirm('Konfirmasi pembayaran ini?')">
                                            <i class="mdi mdi-check"></i> Setuju
                                        </button>
                                    </form>
                                    <!-- TOMBOL TOLAK PAKE MODAL -->
                                    <button type="button" class="btn btn-danger text-white" data-bs-toggle="modal" data-bs-target="#modalTolak">
                                        <i class="mdi mdi-close"></i> Tolak
                                    </button>

                                    <!-- MODAL TOLAK -->
                                    <div class="modal fade" id="modalTolak" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title text-danger"><i class="mdi mdi-alert-circle me-2"></i> Tolak Pembayaran</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="proses.php">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id_pembayaran" value="<?= $data['id_pembayaran'] ?>">
                                                        <input type="hidden" name="id_penitipan" value="<?= $id_penitipan ?>">
                                                        <input type="hidden" name="action" value="tolak">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                                                            <textarea name="alasan_penolakan" class="form-control" rows="3" placeholder="Contoh: Bukti transfer tidak jelas, nominal tidak sesuai..." required></textarea>
                                                            <small class="text-muted">Berikan Alasan Penolakan.</small>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-danger text-white" onclick="return confirm('Yakin ingin menolak pembayaran ini?')">
                                                            <i class="mdi mdi-close"></i> Tolak
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                <?php elseif ($data['status_penitipan'] == 'Disetujui'): ?>
                                    <!-- TOMBOL UNTUK DISETUJUI (BELUM DATANG) -->
                                    <form method="POST" action="proses.php" style="display:inline;">
                                        <input type="hidden" name="id_penitipan" value="<?= $id_penitipan ?>">
                                        <input type="hidden" name="action" value="mulai_titip">
                                        <button type="submit" class="btn btn-success text-white" onclick="return confirm('Hewan sudah diantar ke toko?')">
                                            <i class="mdi mdi-paw"></i> Mulai Titip
                                        </button>
                                    </form>

                                <?php elseif ($data['status_penitipan'] == 'Sedang Dititipkan' || $data['status_penitipan'] == 'Selesai'): ?>
                                    <!-- TOMBOL UNTUK SEDANG DITITIPKAN / SELESAI (TIDAK ADA AKSI) -->
                                    <span class="text-muted">Tidak ada aksi yang tersedia</span>

                                <?php endif; ?>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
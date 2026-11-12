<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$id_user = $_SESSION['id_user'];
$id_penitipan = filter_var($_GET['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);
$id_kandang = filter_var($_GET['id_kandang'] ?? 0, FILTER_VALIDATE_INT); // <-- TAMBAHKAN INI

if (!$id_penitipan) {
    echo "<script>alert('ID tidak valid!'); window.location.href='../bookingan_saya/index.php';</script>";
    exit;
}

// Ambil data booking + perpanjangan
// Ambil data perpanjangan PER KANDANG
// Ambil data perpanjangan PER KANDANG (tanpa filter user)
$query = "
    SELECT 
        rp.*,
        rp.status AS status_perpanjangan, 
        rp.catatan,
        p.kode_booking,
        p.id_penitipan,  
        u.nama_lengkap AS nama_user,
        k.kode_kandang,
        k.nama_kandang
    FROM riwayat_perpanjangan rp
    JOIN penitipan p ON rp.id_penitipan = p.id_penitipan
    JOIN users u ON p.id_user = u.id_user
    JOIN kandang k ON rp.id_kandang = k.id_kandang
    WHERE rp.id_penitipan = ?
    AND rp.id_kandang = ? 
    ORDER BY rp.id_perpanjangan DESC
";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, 'ii', $id_penitipan, $id_kandang); 
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data || empty($data['id_perpanjangan'])) {
    echo "<script>alert('Data perpanjangan tidak ditemukan!'); window.location.href='../bookingan_saya/index.php';</script>";
    exit;
}



$title = 'Detail Perpanjangan Masa Aktif';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card card-rounded shadow-sm">
                    <div class="card-body">

                        <!-- HEADER -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title text-warning mb-0">
                                <i class="mdi mdi-calendar-plus me-2"></i> Detail Perpanjangan Masa Aktif
                            </h4>
                            <div class="d-flex gap-2">
                                <a href="cetak.php?id_penitipan=<?= $id_penitipan ?>" target="_blank" class="btn btn-success btn-sm">
                                    <i class="mdi mdi-printer"></i> Cetak
                                </a>
                             <?php
                            $role = $_SESSION['role'] ?? 'Pelanggan';
                            if ($role == 'Super Admin' || $role == 'Admin'):
                            ?>
                                <a href="../konfirmasi_pembayaran/index.php?filter=perpanjangan" class="btn btn-light">
                                    <i class="mdi mdi-arrow-left"></i> Kembali 
                                </a>
                            <?php else: ?>
                                <a href="../bookingan_saya/index.php" class="btn btn-light">
                                    <i class="mdi mdi-arrow-left"></i> Kembali 
                                </a>
                            <?php endif; ?>
                            </div>
                        </div>

                        <!-- Status Perpanjangan -->
                        <!-- Status Perpanjangan -->
                        <div class="alert <?= $data['status'] == 'Disetujui' ? 'alert-success' : ($data['status'] == 'Ditolak' ? 'alert-danger' : 'alert-warning') ?> mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">
                                    <i class="mdi mdi-information-outline me-2"></i>
                                    Status Perpanjangan:
                                </span>
                                <span class="badge <?= $data['status'] == 'Disetujui' ? 'bg-success' : ($data['status'] == 'Ditolak' ? 'bg-danger' : 'bg-warning text-dark') ?> p-2">
                                    <?= htmlspecialchars($data['status'] ?? 'Menunggu Konfirmasi') ?>
                                </span>
                            </div>
                        </div>


                        <!-- Info Booking -->
                        <!-- Info Booking & Kandang -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <tr>
                                    <td class="text-muted" style="width: 35%;">Kode Booking</td>
                                    <td><strong><?= htmlspecialchars($data['kode_booking']) ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Pelanggan</td>
                                    <td><?= htmlspecialchars($data['nama_user'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Kandang</td>
                                    <td><strong><?= htmlspecialchars($data['kode_kandang']) ?></strong> - <?= htmlspecialchars($data['nama_kandang']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tanggal Selesai Lama</td>
                                    <td><strong><?= date('d M Y', strtotime($data['tgl_selesai_lama'])) ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tanggal Selesai Baru</td>
                                    <td><strong class="text-warning"><?= date('d M Y', strtotime($data['tgl_selesai_baru'])) ?></strong></td>
                                </tr>
                            </table>
                        </div>

                        <!-- Detail Perpanjangan -->
                        <!-- Detail Perpanjangan -->
                        <div class="card bg-light p-3 mb-3 border border-warning">
                            <h6 class="fw-bold mb-3 text-warning"><i class="mdi mdi-cash me-2"></i>Detail Perpanjangan</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="text-muted" style="width: 35%;">Biaya Tambahan</td>
                                    <td><strong class="text-danger">Rp <?= number_format($data['biaya_tambahan'] ?? 0, 0, ',', '.') ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nama Pembayar</td>
                                    <td><?= htmlspecialchars($data['nama_pembayar'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Jumlah Dibayar</td>
                                    <td><strong class="text-success">Rp <?= number_format($data['jumlah_bayar'] ?? 0, 0, ',', '.') ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Catatan</td>
                                    <td><?= htmlspecialchars($data['catatan'] ?? '-') ?></td>
                                </tr>
                                <?php if (!empty($data['bukti_pembayaran'])): ?>
                                    <tr>
                                        <td class="text-muted">Bukti Pembayaran</td>
                                        <td>
                                            <a href="../<?= $data['bukti_pembayaran'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="mdi mdi-file-image"></i> Lihat Bukti
                                            </a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>

                        <!-- Detail Kandang -->
                        <!-- ============================================================ -->
                        <!-- TOMBOL AKSI ADMIN (BERDASARKAN STATUS)                        -->
                        <!-- ============================================================ -->
                        <?php
                        $role = $_SESSION['role'] ?? 'Pelanggan';
                        $isAdmin = ($role == 'Super Admin' || $role == 'Admin');

                        if ($isAdmin && $data['status'] == 'Menunggu Konfirmasi'):
                        ?>
                            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                <div></div>
                                <div class="d-flex gap-2">
                                    <form method="POST" action="../konfirmasi_pembayaran/proses.php" style="display:inline;">
                                        <input type="hidden" name="id_perpanjangan" value="<?= $data['id_perpanjangan'] ?>">
                                        <input type="hidden" name="id_penitipan" value="<?= $id_penitipan ?>">
                                        <input type="hidden" name="action" value="konfirmasi_perpanjangan">
                                        <button type="submit" class="btn btn-success text-white" onclick="return confirm('Konfirmasi perpanjangan ini?')">
                                            <i class="mdi mdi-check"></i> Setuju
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-danger text-white" data-bs-toggle="modal" data-bs-target="#modalTolakPerpanjangan">
                                        <i class="mdi mdi-close"></i> Tolak
                                    </button>
                                </div>
                            </div>

                            <!-- Modal Tolak Perpanjangan -->
                            <div class="modal fade" id="modalTolakPerpanjangan" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title text-danger"><i class="mdi mdi-alert-circle me-2"></i> Tolak Perpanjangan</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="../konfirmasi_pembayaran/proses.php">
                                            <div class="modal-body">
                                                <input type="hidden" name="id_perpanjangan" value="<?= $data['id_perpanjangan'] ?>">
                                                <input type="hidden" name="id_penitipan" value="<?= $id_penitipan ?>">
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

                        <!-- Tombol Kembali -->
                        <!-- Tombol Kembali -->
                        <div class="mt-3">
                            <?php
                            $role = $_SESSION['role'] ?? 'Pelanggan';
                            if ($role == 'Super Admin' || $role == 'Admin'):
                            ?>
                                <a href="../konfirmasi_pembayaran/index.php?filter=perpanjangan" class="btn btn-light">
                                    <i class="mdi mdi-arrow-left"></i> Kembali ke Konfirmasi Perpanjangan
                                </a>
                            <?php else: ?>
                                <a href="../bookingan_saya/index.php" class="btn btn-light">
                                    <i class="mdi mdi-arrow-left"></i> Kembali ke Bookingan Saya
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
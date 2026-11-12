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

// Ambil data booking
// Di bagian query utama, tambahkan:
$query = "
    SELECT p.*, 
           u.nama_lengkap AS nama_user,
           pay.kode_pembayaran,
           pay.status_bayar,
           pay.metode_bayar,
           pay.tgl_bayar,
           pay.jumlah_tagihan,
           pay.nama_pembayar,
           pay.bukti_pembayaran,
           rp.id_perpanjangan,
           rp.tgl_selesai_lama,
           rp.tgl_selesai_baru,
           rp.biaya_tambahan,
           rp.status AS status_perpanjangan,
           rp.nama_pembayar AS nama_pembayar_perpanjangan,
           rp.jumlah_bayar AS jumlah_bayar_perpanjangan,
           rp.bukti_pembayaran AS bukti_perpanjangan
    FROM penitipan p
    LEFT JOIN users u ON p.id_user = u.id_user
    LEFT JOIN pembayaran pay ON p.id_penitipan = pay.id_penitipan
    LEFT JOIN riwayat_perpanjangan rp ON p.id_penitipan = rp.id_penitipan
    WHERE p.id_penitipan = ? AND p.id_user = ?
";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, 'ii', $id_penitipan, $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data) {
    echo "<script>alert('Data tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}

// Ambil detail kandang
$query_kandang = "
    SELECT ph.tgl_mulai, ph.tgl_selesai, k.kode_kandang, k.nama_kandang,
           DATEDIFF(ph.tgl_selesai, ph.tgl_mulai) AS durasi_hari
    FROM penitipan_hewan ph
    JOIN kandang k ON ph.id_kandang = k.id_kandang
    WHERE ph.id_penitipan = ?
";
$stmt = mysqli_prepare($koneksi, $query_kandang);
mysqli_stmt_bind_param($stmt, 'i', $id_penitipan);
mysqli_stmt_execute($stmt);
$result_kandang = mysqli_stmt_get_result($stmt);
$detail_kandang = [];
while ($row = mysqli_fetch_assoc($result_kandang)) {
    $detail_kandang[] = $row;
}
mysqli_stmt_close($stmt);

// Cek role user
$role = $_SESSION['role'] ?? 'Pelanggan';
$isAdmin = ($role == 'Super Admin' || $role == 'Admin');

$title = 'Detail Booking';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card card-rounded shadow-sm">
                    <div class="card-body">

                        <!-- ============================================================ -->
                        <!-- HEADER                                                      -->
                        <!-- ============================================================ -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title text-primary mb-0">
                                <i class="mdi mdi-clipboard-text me-2"></i> Detail Booking
                            </h4>
                            <div class="d-flex gap-2">
                                <?php if (!empty($data['kode_pembayaran'])): ?>
                                    <a href="../pembayaran/detail.php?id_penitipan=<?= $id_penitipan ?>" class="btn btn-info btn-sm">
                                        <i class="mdi mdi-credit-card-outline"></i> Lihat Pembayaran
                                    </a>
                                <?php endif; ?>
                                <a href="index.php" class="btn btn-light btn-sm">
                                    <i class="mdi mdi-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- STATUS BADGE                                               -->
                        <!-- ============================================================ -->
                        <?php
                        $status = $data['status_penitipan'];
                        $badge_bg = 'bg-secondary';
                        if ($status == 'Pending') $badge_bg = 'bg-warning text-dark';
                        elseif ($status == 'Menunggu Konfirmasi') $badge_bg = 'bg-info text-white';
                        elseif ($status == 'Disetujui') $badge_bg = 'bg-primary text-white';
                        elseif ($status == 'Sedang Dititipkan') $badge_bg = 'bg-success text-white';
                        elseif ($status == 'Selesai') $badge_bg = 'bg-dark text-white';
                        elseif ($status == 'Ditolak' || $status == 'Dibatalkan') $badge_bg = 'bg-danger text-white';
                        ?>
                        <div class="alert <?= $status == 'Selesai' ? 'alert-success' : 'alert-info' ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="mdi mdi-information-outline me-2"></i> Status Penitipan</span>
                                <span class="badge <?= $badge_bg ?> p-2"><?= htmlspecialchars($status) ?></span>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- INFO BOOKING                                               -->
                        <!-- ============================================================ -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-light p-3 mb-3">
                                    <h6 class="fw-bold mb-3"><i class="mdi mdi-information me-2"></i>Informasi Booking</h6>
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="text-muted">Kode Booking</td>
                                            <td><strong><?= htmlspecialchars($data['kode_booking']) ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Pelanggan</td>
                                            <td><?= htmlspecialchars($data['nama_user'] ?? '-') ?></td>
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
                                            <td class="text-muted">Total Biaya</td>
                                            <td><strong class="text-danger">Rp <?= number_format($data['total_biaya'] ?? 0, 0, ',', '.') ?></strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light p-3 mb-3">
                                    <h6 class="fw-bold mb-3"><i class="mdi mdi-credit-card me-2"></i>Informasi Pembayaran</h6>
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="text-muted">Kode Pembayaran</td>
                                            <td><?= htmlspecialchars($data['kode_pembayaran'] ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Status Bayar</td>
                                            <td><?= htmlspecialchars($data['status_bayar'] ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Metode</td>
                                            <td><?= htmlspecialchars($data['metode_bayar'] ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Tanggal Bayar</td>
                                            <td><?= !empty($data['tgl_bayar']) ? date('d M Y H:i', strtotime($data['tgl_bayar'])) : '-' ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Nama Pembayar</td>
                                            <td><?= htmlspecialchars($data['nama_pembayar'] ?? '-') ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- INFORMASI PERPANJANGAN                                        -->
                        <!-- ============================================================ -->
                        <?php if (!empty($data['id_perpanjangan'])): ?>
                            <div class="card bg-light p-3 mb-3 border border-warning">
                                <h6 class="fw-bold mb-3 text-warning"><i class="mdi mdi-calendar-plus me-2"></i>Informasi Perpanjangan</h6>
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td class="text-muted" style="width: 40%;">Status Perpanjangan</td>
                                        <td>
                                            <span class="badge <?= $data['status_perpanjangan'] == 'Disetujui' ? 'bg-success' : ($data['status_perpanjangan'] == 'Ditolak' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                                <?= htmlspecialchars($data['status_perpanjangan'] ?? '-') ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Tanggal Selesai Lama</td>
                                        <td><strong><?= date('d M Y', strtotime($data['tgl_selesai_lama'])) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Tanggal Selesai Baru</td>
                                        <td><strong><?= date('d M Y', strtotime($data['tgl_selesai_baru'])) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Biaya Tambahan</td>
                                        <td><strong class="text-danger">Rp <?= number_format($data['biaya_tambahan'] ?? 0, 0, ',', '.') ?></strong></td>
                                    </tr>
                                    <?php if (!empty($data['nama_pembayar_perpanjangan'])): ?>
                                        <tr>
                                            <td class="text-muted">Nama Pembayar</td>
                                            <td><?= htmlspecialchars($data['nama_pembayar_perpanjangan']) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Jumlah Dibayar</td>
                                            <td><strong class="text-success">Rp <?= number_format($data['jumlah_bayar_perpanjangan'] ?? 0, 0, ',', '.') ?></strong></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($data['bukti_perpanjangan'])): ?>
                                        <tr>
                                            <td class="text-muted">Bukti Pembayaran</td>
                                            <td>
                                                <a href="../<?= $data['bukti_perpanjangan'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="mdi mdi-file-image"></i> Lihat Bukti
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        <?php endif; ?>

                        <!-- ============================================================ -->
                        <!-- DETAIL KANDANG                                             -->
                        <!-- ============================================================ -->
                        <?php if (!empty($detail_kandang)): ?>
                            <div class="card bg-light p-3 mb-3">
                                <h6 class="fw-bold mb-3"><i class="mdi mdi-home me-2"></i>Detail Kandang</h6>
                                <?php foreach ($detail_kandang as $dk):
                                    $durasi = ($dk['durasi_hari'] <= 0) ? 1 : $dk['durasi_hari'];
                                ?>
                                    <div class="d-flex justify-content-between py-1 border-bottom">
                                        <span><strong><?= htmlspecialchars($dk['kode_kandang']) ?></strong> - <?= htmlspecialchars($dk['nama_kandang']) ?></span>
                                        <span><?= $durasi ?> hari (<?= date('d M Y', strtotime($dk['tgl_mulai'])) ?> - <?= date('d M Y', strtotime($dk['tgl_selesai'])) ?>)</span>
                                    </div>
                                <?php endforeach; ?>
                                <div class="text-end mt-1"><small class="text-muted">Total: <?= count($detail_kandang) ?> kandang</small></div>
                            </div>
                        <?php endif; ?>

                        <!-- ============================================================ -->
                        <!-- AKSI ADMIN (HANYA UNTUK ADMIN)                               -->
                        <!-- ============================================================ -->
                        <?php if ($isAdmin): ?>

                            <!-- 1. STATUS: DISETUJUI → SEDANG DITITIPKAN -->
                            <?php if ($status == 'Disetujui'): ?>
                                <div class="card bg-light p-3 mb-3 border border-primary">
                                    <h6 class="fw-bold mb-3 text-primary"><i class="mdi mdi-paw me-2"></i>Konfirmasi Kedatangan Hewan</h6>
                                    <form method="POST" action="proses.php">
                                        <input type="hidden" name="id_penitipan" value="<?= $id_penitipan ?>">
                                        <input type="hidden" name="action" value="mulai_titip">
                                        <button type="submit" class="btn btn-success" onclick="return confirm('Hewan sudah diantar ke toko?')">
                                            <i class="mdi mdi-paw"></i> Sudah Datang / Mulai Titip
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <!-- 2. STATUS: SEDANG DITITIPKAN → SELESAI / AMBIL HEWAN / PERPANJANG -->
                            <?php if ($status == 'Sedang Dititipkan'): ?>
                                <div class="card bg-light p-3 mb-3 border border-success">
                                    <h6 class="fw-bold mb-3 text-success"><i class="mdi mdi-tools me-2"></i>Aksi Admin</h6>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <!-- Ambil Hewan (Lebih Cepat) -->
                                        <form method="POST" action="proses.php" style="display:inline;">
                                            <input type="hidden" name="id_penitipan" value="<?= $id_penitipan ?>">
                                            <input type="hidden" name="action" value="ambil_hewan">
                                            <button type="submit" class="btn btn-warning" onclick="return confirm('Yakin hewan sudah diambil (lebih cepat)?')">
                                                <i class="mdi mdi-paw"></i> Ambil Hewan (Cepat)
                                            </button>
                                        </form>

                                        <!-- Selesai (Normal) -->
                                        <form method="POST" action="proses.php" style="display:inline;">
                                            <input type="hidden" name="id_penitipan" value="<?= $id_penitipan ?>">
                                            <input type="hidden" name="action" value="selesai">
                                            <button type="submit" class="btn btn-success" onclick="return confirm('Yakin hewan sudah diambil (sesuai jadwal)?')">
                                                <i class="mdi mdi-check"></i> Selesai
                                            </button>
                                        </form>

                                        <!-- Perpanjang -->
                                        <form method="POST" action="proses.php" class="d-flex gap-2 align-items-center">
                                            <input type="hidden" name="id_penitipan" value="<?= $id_penitipan ?>">
                                            <input type="hidden" name="action" value="perpanjang_admin">
                                            <div>
                                                <label class="text-muted small">Selesai Baru</label>
                                                <input type="date" name="tgl_selesai_baru" class="form-control form-control-sm"
                                                    value="<?= date('Y-m-d', strtotime($data['tgl_selesai'] . ' +3 days')) ?>" required>
                                            </div>
                                            <div>
                                                <label class="text-muted small">Biaya Tambahan</label>
                                                <input type="number" name="biaya_tambahan" class="form-control form-control-sm"
                                                    placeholder="0" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Perpanjang masa titip?')">
                                                <i class="mdi mdi-calendar-plus"></i> Perpanjang
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php endif; ?>
                        <!-- END AKSI ADMIN -->

                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
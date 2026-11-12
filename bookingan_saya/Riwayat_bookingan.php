<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

$id_penitipan = $_GET['id_penitipan'] ?? 0;
if ($id_penitipan == 0) {
    header('Location: index.php');
    exit;
}

// ============================================================
// 1. AMBIL DATA BOOKING & PELANGGAN
// ============================================================
$query_booking = "SELECT p.*, pay.*, u.nama_lengkap AS nama_pelanggan, u.email, u.no_telp
                  FROM penitipan p
                  LEFT JOIN pembayaran pay ON p.id_penitipan = pay.id_penitipan
                  LEFT JOIN users u ON p.id_user = u.id_user
                  WHERE p.id_penitipan = $id_penitipan";
$result_booking = mysqli_query($koneksi, $query_booking);
$booking = mysqli_fetch_assoc($result_booking);
if (!$booking) {
    header('Location: index.php');
    exit;
}

// ============================================================
// 2. AMBIL DATA KANDANG + HEWAN (untuk tabel 2 & ringkasan)
// ============================================================
$query_kandang = "SELECT 
    ph.id_kandang,
    k.kode_kandang,
    k.nama_kandang,
    k.tarif_per_hari,
    k.ukuran,
    k.kapasitas,
    COUNT(DISTINCT ph.id_hewan) AS jumlah_hewan,
    GROUP_CONCAT(DISTINCT h.nama_hewan SEPARATOR ', ') AS daftar_hewan,
    MIN(ph.tgl_mulai) AS tgl_mulai_kandang,
    MAX(ph.tgl_selesai) AS tgl_selesai_kandang,
    DATEDIFF(MAX(ph.tgl_selesai), MIN(ph.tgl_mulai)) + 1 AS durasi_kandang
FROM penitipan_hewan ph
LEFT JOIN kandang k ON ph.id_kandang = k.id_kandang
LEFT JOIN hewan h ON ph.id_hewan = h.id_hewan
WHERE ph.id_penitipan = $id_penitipan
GROUP BY ph.id_kandang
ORDER BY ph.id_kandang";
$result_kandang = mysqli_query($koneksi, $query_kandang);
$kandang_list = [];
while ($kandang = mysqli_fetch_assoc($result_kandang)) {
    $kandang_list[] = $kandang;
}

// ============================================================
// 3. AMBIL SEMUA RIWAYAT PERPANJANGAN (untuk tabel 4)
// ============================================================
$query_rp = "SELECT rp.*, k.kode_kandang, k.nama_kandang
             FROM riwayat_perpanjangan rp
             JOIN kandang k ON rp.id_kandang = k.id_kandang
             WHERE rp.id_penitipan = $id_penitipan
             ORDER BY rp.id_perpanjangan ASC";
$result_rp = mysqli_query($koneksi, $query_rp);
$rp_list = [];
while ($rp = mysqli_fetch_assoc($result_rp)) {
    $rp_list[] = $rp;
}

// ============================================================
// 4. AMBIL STATUS HARIAN PER HEWAN (untuk tabel 3)
// ============================================================
$query_status = "SELECT 
    ph.id_kandang,
    h.id_hewan,
    h.nama_hewan,
    h.jenis_hewan,
    s.tgl_update,
    s.jam_update,
    s.kondisi_kesehatan,
    s.pola_makan,
    s.aktivitas,
    s.catatan_perawat,
    s.foto_kondisi
FROM penitipan_hewan ph
INNER JOIN hewan h ON ph.id_hewan = h.id_hewan
LEFT JOIN status_harian s ON ph.id_penitipan = s.id_penitipan AND ph.id_hewan = s.id_hewan
WHERE ph.id_penitipan = $id_penitipan
ORDER BY ph.id_kandang, h.nama_hewan, s.tgl_update ASC";
$result_status = mysqli_query($koneksi, $query_status);
$status_data = [];
while ($row = mysqli_fetch_assoc($result_status)) {
    $status_data[$row['id_kandang']][$row['id_hewan']]['info'] = $row;
    if (!empty($row['tgl_update'])) {
        $status_data[$row['id_kandang']][$row['id_hewan']]['status'][] = $row;
    }
}

// ============================================================
// 5. HITUNG TOTAL BIAYA UNTUK RINGKASAN
// ============================================================
$total_biaya_perpanjangan = 0;
foreach ($rp_list as $rp) {
    if ($rp['status'] == 'Disetujui') {
        $total_biaya_perpanjangan += $rp['biaya_tambahan'];
    }
}
$total_tarif_awal = 0;
$grand_total = 0;
foreach ($kandang_list as $k) {
    $total_tarif_awal += $k['durasi_kandang'] * $k['tarif_per_hari'];
}
$grand_total = $total_tarif_awal + $total_biaya_perpanjangan;

$title = 'Riwayat Lengkap Bookingan';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-sm-12">
                <div class="home-tab">
                    <div class="d-sm-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
                        <div>
                            <h2 class="welcome-text">Riwayat <span class="text-black fw-bold">Lengkap Bookingan</span></h2>
                            <p class="text-muted">Informasi lengkap semua data booking dan pembayaran.</p>
                        </div>
                        <div>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                <i class="mdi mdi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- TABLE 1: DATA DIRI PELANGGAN                                 -->
                    <!-- ============================================================ -->
                    <div class="card card-rounded shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title">1. Data Diri Pelanggan</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="text-muted small">Nama</label>
                                    <p class="fw-bold"><?= htmlspecialchars($booking['nama_pelanggan'] ?? '-') ?></p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">Email</label>
                                    <p class="fw-bold"><?= htmlspecialchars($booking['email'] ?? '-') ?></p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">No Telepon</label>
                                    <p class="fw-bold"><?= htmlspecialchars($booking['no_telp'] ?? '-') ?></p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">Status Booking</label>
                                    <p class="fw-bold">
                                        <span class="badge <?=
                                                            $booking['status_penitipan'] == 'Selesai' ? 'bg-dark' : ($booking['status_penitipan'] == 'Sedang Dititipkan' ? 'bg-success' : ($booking['status_penitipan'] == 'Pending' ? 'bg-warning' : 'bg-danger'))
                                                            ?>">
                                            <?= htmlspecialchars($booking['status_penitipan']) ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- TABLE 2: INFORMASI BOOKING AWAL (PER KANDANG)                -->
                    <!-- ============================================================ -->
                    <!-- ============================================================ -->
                    <!-- INFORMASI BOOKING & PEMBAYARAN (dengan modal bukti transfer) -->
                    <!-- ============================================================ -->
                    <div class="card card-rounded shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Informasi Booking & Pembayaran</h5>

                            <!-- Baris 1 -->
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="text-muted small">Kode Booking</label>
                                    <p class="fw-bold"><?= htmlspecialchars($booking['kode_booking']) ?></p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">Kode Pembayaran</label>
                                    <p class="fw-bold"><?= htmlspecialchars($booking['kode_pembayaran'] ?? '-') ?></p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">Nama Pembayar</label>
                                    <p class="fw-bold"><?= htmlspecialchars($booking['nama_pembayar'] ?? '-') ?></p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">Metode Pembayaran</label>
                                    <p class="fw-bold"><?= htmlspecialchars($booking['metode_bayar'] ?? '-') ?></p>
                                </div>
                            </div>

                            <!-- Baris 2 -->
                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <label class="text-muted small">Tanggal Mulai</label>
                                    <p class="fw-bold"><?= date('d M Y', strtotime($booking['tgl_mulai'])) ?></p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">Tanggal Selesai</label>
                                    <p class="fw-bold">
                                        <?php
                                        // Ambil tanggal selesai terakhir dari booking (jika ada perpanjangan, sudah terupdate di tabel penitipan)
                                        $tgl_selesai_akhir = $booking['tgl_selesai'];
                                        // Jika ada perpanjangan disetujui, ambil tanggal terakhir dari riwayat
                                        foreach ($rp_list as $rp) {
                                            if ($rp['status'] == 'Disetujui' && strtotime($rp['tgl_selesai_baru']) > strtotime($tgl_selesai_akhir)) {
                                                $tgl_selesai_akhir = $rp['tgl_selesai_baru'];
                                            }
                                        }
                                        echo date('d M Y', strtotime($tgl_selesai_akhir));
                                        ?>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">Durasi Total</label>
                                    <p class="fw-bold">
                                        <?php
                                        $durasi_total = ceil((strtotime($tgl_selesai_akhir) - strtotime($booking['tgl_mulai'])) / (60 * 60 * 24));
                                        echo max($durasi_total, 1) . ' Hari';
                                        ?>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">Tanggal Bayar</label>
                                    <p class="fw-bold"><?= !empty($booking['tgl_bayar']) ? date('d M Y H:i', strtotime($booking['tgl_bayar'])) : '-' ?></p>
                                </div>
                            </div>

                            <!-- Baris 3 – Biaya -->
                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <label class="text-muted small">Total Tarif Awal</label>
                                    <p class="fw-bold">Rp <?= number_format($total_tarif_awal, 0, ',', '.') ?></p>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-muted small">Total Biaya Perpanjangan</label>
                                    <p class="fw-bold text-warning">Rp <?= number_format($total_biaya_perpanjangan, 0, ',', '.') ?></p>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-muted small">Total Akhir</label>
                                    <p class="fw-bold text-primary">Rp <?= number_format($grand_total, 0, ',', '.') ?></p>
                                </div>
                            </div>

                            <!-- Status Pembayaran -->
                            <?php if (!empty($booking['status_bayar'])): ?>
                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <label class="text-muted small">Status Pembayaran</label>
                                        <p class="fw-bold">
                                            <span class="badge <?= $booking['status_bayar'] == 'Lunas' ? 'bg-success' : 'bg-warning' ?>">
                                                <?= htmlspecialchars($booking['status_bayar']) ?>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted small">Jumlah Tagihan</label>
                                        <p class="fw-bold">Rp <?= number_format($booking['jumlah_tagihan'] ?? 0, 0, ',', '.') ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted small">Jumlah Dibayar</label>
                                        <p class="fw-bold text-success">Rp <?= number_format($booking['jumlah_bayar'] ?? 0, 0, ',', '.') ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- ============================================================ -->
                            <!-- BUKTI TRANSFER – THUMBNAIL + MODAL                          -->
                            <!-- ============================================================ -->
                            <?php if (!empty($booking['bukti_pembayaran']) && file_exists(__DIR__ . '/../' . $booking['bukti_pembayaran'])): ?>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <label class="text-muted small">Bukti Pembayaran</label>
                                        <div>
                                            <img src="../<?= $booking['bukti_pembayaran'] ?>"
                                                alt="Bukti Pembayaran"
                                                style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 8px; cursor: pointer;"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalBuktiTransfer">
                                        </div>
                                    </div>
                                </div>
                                <!-- Modal Bukti Transfer -->
                                <div class="modal fade" id="modalBuktiTransfer" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Bukti Pembayaran</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-center">
                                                <img src="../<?= $booking['bukti_pembayaran'] ?>"
                                                    alt="Bukti Pembayaran"
                                                    style="max-width: 100%; max-height: 600px; border-radius: 8px;">
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card card-rounded shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title">2. Informasi Booking Awal (per Kandang)</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" style="font-size: 14px;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Kode Booking</th>
                                            <th>Kode Pembayaran</th>
                                            <th>Kandang</th>
                                            <th>Tanggal Mulai</th>
                                            <th>Tanggal Selesai Awal</th>
                                            <th>Harga/Hari</th>
                                            <th>Total Harga Awal</th>
                                            <th>Nama Hewan</th>
                                            <th>Kapasitas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($kandang_list as $k): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($booking['kode_booking']) ?></td>
                                                <td><?= htmlspecialchars($booking['kode_pembayaran'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($k['kode_kandang']) ?></td>
                                                <td><?= date('d M Y', strtotime($k['tgl_mulai_kandang'])) ?></td>
                                                <td><?= date('d M Y', strtotime($k['tgl_selesai_kandang'])) ?></td>
                                                <td>Rp <?= number_format($k['tarif_per_hari'], 0, ',', '.') ?></td>
                                                <td class="fw-bold text-primary">Rp <?= number_format($k['durasi_kandang'] * $k['tarif_per_hari'], 0, ',', '.') ?></td>
                                                <td><?= htmlspecialchars($k['daftar_hewan'] ?? '-') ?></td>
                                                <td><?= $k['kapasitas'] ?? 1 ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- TABLE 3: STATUS HARIAN PER KANDANG                          -->
                    <!-- ============================================================ -->
                    <div class="card card-rounded shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title">3. Status Harian Hewan (per Kandang)</h5>
                            <?php if (empty($status_data)): ?>
                                <div class="alert alert-info">Belum ada status harian.</div>
                            <?php else: ?>
                                <?php foreach ($status_data as $id_kandang => $hewan_data):
                                    // Cari nama kandang dari $kandang_list
                                    $kandang_nama = '';
                                    foreach ($kandang_list as $k) {
                                        if ($k['id_kandang'] == $id_kandang) {
                                            $kandang_nama = $k['kode_kandang'];
                                            break;
                                        }
                                    }
                                ?>
                                    <div class="card border-0 mb-3" style="background: #f8f9fa; border-radius: 8px;">
                                        <div class="card-header bg-transparent border-0 p-2">
                                            <h6 class="fw-bold text-primary"><i class="mdi mdi-home"></i> Kandang: <?= $kandang_nama ?></h6>
                                        </div>
                                        <div class="card-body p-2">
                                            <?php foreach ($hewan_data as $id_hewan => $data): ?>
                                                <h6 class="fw-bold"><i class="mdi mdi-paw"></i> <?= htmlspecialchars($data['info']['nama_hewan']) ?></h6>
                                                <div class="table-responsive mb-3">
                                                    <table class="table table-bordered table-sm" style="font-size: 13px;">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Tanggal</th>
                                                                <th>Kondisi Kesehatan</th>
                                                                <th>Pola Makan</th>
                                                                <th>Aktivitas</th>
                                                                <th>Catatan</th>
                                                                <th>Foto</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (empty($data['status'])): ?>
                                                                <tr>
                                                                    <td colspan="6" class="text-muted">Belum ada status</td>
                                                                </tr>
                                                            <?php else: ?>
                                                                <?php foreach ($data['status'] as $s): ?>
                                                                    <tr>
                                                                        <td><?= date('d M Y', strtotime($s['tgl_update'])) ?></td>
                                                                        <td><span class="badge <?=
                                                                                                $s['kondisi_kesehatan'] == 'Sakit' ? 'bg-danger' : ($s['kondisi_kesehatan'] == 'Kurang Baik' ? 'bg-warning' : ($s['kondisi_kesehatan'] == 'Sangat Baik' ? 'bg-success' : ($s['kondisi_kesehatan'] == 'Baik' ? 'bg-info' : 'bg-secondary')))
                                                                                                ?>"><?= htmlspecialchars($s['kondisi_kesehatan']) ?></span></td>
                                                                        <td><span class="badge <?=
                                                                                                $s['pola_makan'] == 'Lahap' ? 'bg-success' : ($s['pola_makan'] == 'Normal' ? 'bg-info' : ($s['pola_makan'] == 'Kurang Nafsu Makan' ? 'bg-warning' : 'bg-danger'))
                                                                                                ?>"><?= htmlspecialchars($s['pola_makan']) ?></span></td>
                                                                        <td><span class="badge <?=
                                                                                                $s['aktivitas'] == 'Sangat Aktif' ? 'bg-success' : ($s['aktivitas'] == 'Aktif' ? 'bg-info' : ($s['aktivitas'] == 'Normal' ? 'bg-primary' : ($s['aktivitas'] == 'Lesu' ? 'bg-warning' : 'bg-danger')))
                                                                                                ?>"><?= htmlspecialchars($s['aktivitas']) ?></span></td>
                                                                        <td><?= nl2br(htmlspecialchars($s['catatan_perawat'] ?? '-')) ?></td>
                                                                        <td>
                                                                            <?php if (!empty($s['foto_kondisi']) && file_exists(__DIR__ . '/../' . $s['foto_kondisi'])): ?>
                                                                                <img src="../<?= $s['foto_kondisi'] ?>" alt="Foto" style="max-width: 50px; max-height: 50px; border-radius: 5px; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#fotoModal_<?= $s['id_status'] ?>">
                                                                                <!-- Modal Foto -->
                                                                                <div class="modal fade" id="fotoModal_<?= $s['id_status'] ?>" tabindex="-1">
                                                                                    <div class="modal-dialog modal-dialog-centered">
                                                                                        <div class="modal-content">
                                                                                            <div class="modal-header">
                                                                                                <h5 class="modal-title">Foto <?= htmlspecialchars($data['info']['nama_hewan']) ?> - <?= date('d M Y', strtotime($s['tgl_update'])) ?></h5>
                                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                                            </div>
                                                                                            <div class="modal-body text-center">
                                                                                                <img src="../<?= $s['foto_kondisi'] ?>" alt="Foto" style="max-width: 100%; max-height: 500px; border-radius: 5px;">
                                                                                            </div>
                                                                                            <div class="modal-footer">
                                                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            <?php else: ?>
                                                                                <span class="text-muted">-</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- ============================================================ -->
                    <!-- TABLE 4: DATA PERPANJANGAN & PENOLAKAN (dengan Bukti Transfer) -->
                    <!-- ============================================================ -->
                    <div class="card card-rounded shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title">4. Data Perpanjangan & Penolakan</h5>
                            <?php if (empty($rp_list)): ?>
                                <div class="alert alert-info">Tidak ada riwayat perpanjangan.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" style="font-size: 14px;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Kode Perpanjangan</th>
                                                <th>Kandang</th>
                                                <th>Status</th>
                                                <th>Tanggal Perpanjangan</th>
                                                <th>Tanggal Selesai Lama</th>
                                                <th>Tanggal Selesai Baru</th>
                                                <th>Durasi Tambahan</th>
                                                <th>Biaya Tambahan</th>
                                                <th>Bukti Transfer</th>
                                                <th>Alasan (jika ditolak)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rp_list as $rp):
                                                $durasi_rp = max((strtotime($rp['tgl_selesai_baru']) - strtotime($rp['tgl_selesai_lama'])) / (60 * 60 * 24), 1);
                                                $status_badge = $rp['status'] == 'Disetujui' ? 'bg-success' : ($rp['status'] == 'Ditolak' ? 'bg-danger' : 'bg-warning');
                                                $has_bukti = !empty($rp['bukti_pembayaran']) && file_exists(__DIR__ . '/../' . $rp['bukti_pembayaran']);
                                            ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($rp['kode_pembayaran'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($rp['kode_kandang']) ?></td>
                                                    <td><span class="badge <?= $status_badge ?>"><?= htmlspecialchars($rp['status']) ?></span></td>
                                                    <td><?= date('d M Y', strtotime($rp['tgl_perpanjangan'])) ?></td>
                                                    <td><?= date('d M Y', strtotime($rp['tgl_selesai_lama'])) ?></td>
                                                    <td><?= date('d M Y', strtotime($rp['tgl_selesai_baru'])) ?></td>
                                                    <td><?= $durasi_rp ?> Hari</td>
                                                    <td class="text-danger">Rp <?= number_format($rp['biaya_tambahan'] ?? 0, 0, ',', '.') ?></td>
                                                    <td>
                                                        <?php if ($has_bukti): ?>
                                                            <img src="../<?= $rp['bukti_pembayaran'] ?>"
                                                                alt="Bukti Transfer"
                                                                style="max-width: 60px; max-height: 60px; border: 1px solid #ddd; border-radius: 5px; cursor: pointer;"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#modalBuktiPerpanjangan_<?= $rp['id_perpanjangan'] ?>">
                                                            <!-- Modal untuk bukti perpanjangan -->
                                                            <div class="modal fade" id="modalBuktiPerpanjangan_<?= $rp['id_perpanjangan'] ?>" tabindex="-1">
                                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title">Bukti Transfer Perpanjangan - <?= htmlspecialchars($rp['kode_pembayaran'] ?? 'No Kode') ?></h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                        </div>
                                                                        <div class="modal-body text-center">
                                                                            <img src="../<?= $rp['bukti_pembayaran'] ?>"
                                                                                alt="Bukti Transfer Perpanjangan"
                                                                                style="width: 100%; height: auto; min-height: 400px; max-height: 90vh; object-fit: contain; border-radius: 8px;">
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($rp['status'] == 'Ditolak' && !empty($rp['catatan_admin'])): ?>
                                                            <?= nl2br(htmlspecialchars($rp['catatan_admin'])) ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- TABLE 5: RINGKASAN PEMBAYARAN                                -->
                    <!-- ============================================================ -->
                    <div class="card card-rounded shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">5. Ringkasan Pembayaran</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" style="font-size: 14px;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Kandang</th>
                                            <th>Kode</th>
                                            <th>Durasi Awal (Hari)</th>
                                            <th>Biaya Awal</th>
                                            <th>Durasi Perpanjangan (Hari)</th>
                                            <th>Biaya Perpanjangan</th>
                                            <th>Total Biaya</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $grand_total = 0;
                                        foreach ($kandang_list as $k):
                                            // Hitung total durasi dan biaya perpanjangan untuk kandang ini
                                            $durasi_rp_total = 0;
                                            $biaya_rp_total = 0;
                                            foreach ($rp_list as $rp) {
                                                if ($rp['id_kandang'] == $k['id_kandang'] && $rp['status'] == 'Disetujui') {
                                                    $durasi = (strtotime($rp['tgl_selesai_baru']) - strtotime($rp['tgl_selesai_lama'])) / (60 * 60 * 24);
                                                    $durasi_rp_total += max($durasi, 1);
                                                    $biaya_rp_total += $rp['biaya_tambahan'];
                                                }
                                            }
                                            $durasi_awal = max($k['durasi_kandang'] - $durasi_rp_total, 1);
                                            $biaya_awal = $durasi_awal * $k['tarif_per_hari'];
                                            $total_k = $biaya_awal + $biaya_rp_total;
                                            $grand_total += $total_k;
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($k['nama_kandang']) ?></td>
                                                <td><?= htmlspecialchars($k['kode_kandang']) ?></td>
                                                <td><?= $durasi_awal ?> Hari</td>
                                                <td class="text-primary">Rp <?= number_format($biaya_awal, 0, ',', '.') ?></td>
                                                <td><?= $durasi_rp_total ?> Hari</td>
                                                <td class="text-warning">Rp <?= number_format($biaya_rp_total, 0, ',', '.') ?></td>
                                                <td class="fw-bold text-success">Rp <?= number_format($total_k, 0, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr class="fw-bold">
                                            <td colspan="6" class="text-end">Grand Total</td>
                                            <td class="text-danger">Rp <?= number_format($grand_total, 0, ',', '.') ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
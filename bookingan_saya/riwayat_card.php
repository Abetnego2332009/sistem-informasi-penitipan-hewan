<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

$id_penitipan = $_GET['id_penitipan'] ?? 0;
$id_kandang = $_GET['id_kandang'] ?? 0;

if ($id_penitipan == 0 || $id_kandang == 0) {
    header('Location: index.php');
    exit;
}

// Ambil data booking
$query_booking = "SELECT p.*, pay.* 
                  FROM penitipan p
                  LEFT JOIN pembayaran pay ON p.id_penitipan = pay.id_penitipan
                  WHERE p.id_penitipan = $id_penitipan";
$result_booking = mysqli_query($koneksi, $query_booking);
$booking = mysqli_fetch_assoc($result_booking);

if (!$booking) {
    header('Location: index.php');
    exit;
}

// Ambil data kandang
$query_kandang = "SELECT 
    ph.id_kandang,
    k.kode_kandang,
    k.nama_kandang,
    k.tarif_per_hari,
    k.ukuran,
    k.fasilitas,
    COUNT(ph.id_hewan) AS jumlah_hewan,
    GROUP_CONCAT(h.nama_hewan SEPARATOR ', ') AS daftar_hewan,
    GROUP_CONCAT(h.id_hewan SEPARATOR ',') AS id_hewan_list,
    MIN(ph.tgl_mulai) AS tgl_mulai_kandang,
    MAX(ph.tgl_selesai) AS tgl_selesai_kandang,
    GREATEST(DATEDIFF(MAX(ph.tgl_selesai), MIN(ph.tgl_mulai)), 1) AS durasi_kandang,
    rp.id_perpanjangan,
    rp.kode_pembayaran AS kode_perpanjangan,
    rp.status AS status_perpanjangan,
    rp.tgl_selesai_baru,
    rp.biaya_tambahan,
    rp.bukti_pembayaran AS bukti_perpanjangan,
    rp.catatan_admin AS alasan_penolakan_perpanjangan,
    rp.tgl_perpanjangan,
    rp.nama_pembayar AS nama_pembayar_perpanjangan,
    rp.jumlah_bayar AS jumlah_bayar_perpanjangan,
    rp.id_metode AS id_metode_perpanjangan,
    rp.catatan AS catatan_perpanjangan
FROM penitipan_hewan ph
LEFT JOIN kandang k ON ph.id_kandang = k.id_kandang
LEFT JOIN hewan h ON ph.id_hewan = h.id_hewan
LEFT JOIN riwayat_perpanjangan rp ON ph.id_penitipan = rp.id_penitipan 
    AND ph.id_kandang = rp.id_kandang
WHERE ph.id_penitipan = $id_penitipan 
    AND ph.id_kandang = $id_kandang
GROUP BY ph.id_kandang";

$result_kandang = mysqli_query($koneksi, $query_kandang);
$kandang = mysqli_fetch_assoc($result_kandang);

if (!$kandang) {
    header('Location: index.php');
    exit;
}

// Ambil data hewan
$query_hewan = "SELECT h.*, sh.* 
                FROM penitipan_hewan ph
                LEFT JOIN hewan h ON ph.id_hewan = h.id_hewan
                LEFT JOIN status_harian sh ON ph.id_penitipan = sh.id_penitipan AND ph.id_hewan = sh.id_hewan
                WHERE ph.id_penitipan = $id_penitipan 
                    AND ph.id_kandang = $id_kandang
                ORDER BY h.nama_hewan ASC, sh.tgl_update DESC";

$result_hewan = mysqli_query($koneksi, $query_hewan);
$hewan_list = [];
while ($hewan = mysqli_fetch_assoc($result_hewan)) {
    $hewan_list[] = $hewan;
}

$title = 'Detail Riwayat Kandang';
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
                            <h2 class="welcome-text">Detail <span class="text-black fw-bold">Riwayat Kandang</span></h2>
                            <p class="text-muted">Informasi lengkap riwayat penitipan per kandang.</p>
                        </div>
                        <div>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                <i class="mdi mdi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>

                    <!-- Informasi Booking -->
                    <div class="card card-rounded shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Informasi Booking</h5>
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
                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <label class="text-muted small">Tanggal Bayar</label>
                                    <p class="fw-bold"><?= !empty($booking['tgl_bayar']) ? date('d M Y H:i', strtotime($booking['tgl_bayar'])) : '-' ?></p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">Jumlah Tagihan</label>
                                    <p class="fw-bold text-primary">Rp <?= number_format($booking['jumlah_tagihan'] ?? 0, 0, ',', '.') ?></p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">Jumlah Dibayar</label>
                                    <p class="fw-bold text-success">Rp <?= number_format($booking['jumlah_bayar'] ?? 0, 0, ',', '.') ?></p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">Status Pembayaran</label>
                                    <p class="fw-bold">
                                        <span class="badge <?= $booking['status_bayar'] == 'Lunas' ? 'bg-success' : 'bg-warning' ?>">
                                            <?= htmlspecialchars($booking['status_bayar'] ?? '-') ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Kandang -->
                    <div class="card card-rounded shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Informasi Kandang</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="text-muted small">Jenis Kandang</label>
                                    <p class="fw-bold"><?= htmlspecialchars($kandang['ukuran'] ?? '-') ?></p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">Nama Kandang</label>
                                    <p class="fw-bold"><?= htmlspecialchars($kandang['kode_kandang'] . ' - ' . $kandang['nama_kandang']) ?></p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">Tanggal Mulai</label>
                                    <p class="fw-bold"><?= date('d M Y', strtotime($kandang['tgl_mulai_kandang'])) ?></p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">Tanggal Selesai</label>
                                    <p class="fw-bold">
                                        <?php
                                        $tgl_selesai = $kandang['tgl_selesai_kandang'];
                                        if (!empty($kandang['tgl_selesai_baru']) && $kandang['status_perpanjangan'] == 'Disetujui') {
                                            $tgl_selesai = $kandang['tgl_selesai_baru'];
                                        }
                                        echo date('d M Y', strtotime($tgl_selesai));
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <label class="text-muted small">Durasi</label>
                                    
                                    <p class="fw-bold"><?= $kandang['durasi_kandang'] ?> Hari</p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">Tarif per Hari</label>
                                    <p class="fw-bold">Rp <?= number_format($kandang['tarif_per_hari'], 0, ',', '.') ?></p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">Total Tarif Kandang</label>
                                    <?php
                                   $durasi_tampil = max($kandang['durasi_kandang'], 1);
                                    $total_tarif = $durasi_tampil * $kandang['tarif_per_hari'];
                                    ?>
                                    <p class="fw-bold text-primary">Rp <?= number_format($total_tarif, 0, ',', '.') ?></p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small">Jumlah Hewan</label>
                                    <p class="fw-bold"><?= $kandang['jumlah_hewan'] ?> ekor</p>
                                </div>
                            </div>
                            <?php if (!empty($kandang['fasilitas'])): ?>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <label class="text-muted small">Fasilitas</label>
                                        <p class="fw-bold"><?= htmlspecialchars($kandang['fasilitas']) ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($kandang['id_perpanjangan'])): ?>
                                <div class="row mt-2 p-2 bg-light rounded border">
                                    <div class="col-12">
                                        <h6 class="fw-bold">📋 Detail Perpanjangan</h6>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted small">Status Perpanjangan</label>
                                        <p class="fw-bold">
                                            <span class="badge <?= $kandang['status_perpanjangan'] == 'Disetujui' ? 'bg-success' : ($kandang['status_perpanjangan'] == 'Ditolak' ? 'bg-danger' : 'bg-warning') ?>">
                                                <?= htmlspecialchars($kandang['status_perpanjangan'] ?? 'Menunggu Konfirmasi') ?>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted small">Kode Perpanjangan</label>
                                        <p class="fw-bold"><?= htmlspecialchars($kandang['kode_perpanjangan'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted small">Tanggal Perpanjangan</label>
                                        <p class="fw-bold"><?= !empty($kandang['tgl_perpanjangan']) ? date('d M Y', strtotime($kandang['tgl_perpanjangan'])) : '-' ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted small">Tanggal Selesai Lama</label>
                                        <p class="fw-bold"><?= date('d M Y', strtotime($kandang['tgl_selesai_kandang'])) ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted small">Tanggal Selesai Baru</label>
                                        <p class="fw-bold"><?= !empty($kandang['tgl_selesai_baru']) ? date('d M Y', strtotime($kandang['tgl_selesai_baru'])) : '-' ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted small">Biaya Tambahan</label>
                                        <p class="fw-bold text-danger">Rp <?= number_format($kandang['biaya_tambahan'] ?? 0, 0, ',', '.') ?></p>
                                    </div>

                                    <?php if ($kandang['status_perpanjangan'] == 'Disetujui' || $kandang['status_perpanjangan'] == 'Menunggu Konfirmasi'): ?>
                                        <div class="col-12 mt-2">
                                            <hr>
                                            <h6 class="fw-bold">💳 Informasi Pembayaran Perpanjangan</h6>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="text-muted small">Nama Pembayar</label>
                                            <p class="fw-bold"><?= htmlspecialchars($kandang['nama_pembayar_perpanjangan'] ?? '-') ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="text-muted small">Jumlah Dibayar</label>
                                            <p class="fw-bold text-success">Rp <?= number_format($kandang['jumlah_bayar_perpanjangan'] ?? 0, 0, ',', '.') ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="text-muted small">Metode Pembayaran</label>
                                            <p class="fw-bold">
                                                <?php
                                                $id_metode = $kandang['id_metode_perpanjangan'] ?? null;
                                                if ($id_metode) {
                                                    $q_metode = mysqli_query($koneksi, "SELECT nama_metode FROM metode_pembayaran WHERE id_metode = $id_metode");
                                                    $metode = mysqli_fetch_assoc($q_metode);
                                                    echo htmlspecialchars($metode['nama_metode'] ?? '-');
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </p>
                                        </div>
                                        <?php if (!empty($kandang['bukti_perpanjangan'])): ?>
                                            <div class="col-md-4">
                                                <label class="text-muted small">Bukti Pembayaran</label>
                                                <p>
                                                    <a href="../<?= htmlspecialchars($kandang['bukti_perpanjangan']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="mdi mdi-file-image"></i> Lihat Bukti
                                                    </a>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if ($kandang['status_perpanjangan'] == 'Ditolak' && !empty($kandang['alasan_penolakan_perpanjangan'])): ?>
                                        <div class="col-12 mt-2">
                                            <div class="alert alert-danger p-2" style="border-left: 3px solid #dc3545;">
                                                <strong>Alasan Penolakan:</strong> <?= htmlspecialchars($kandang['alasan_penolakan_perpanjangan']) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Daftar Hewan & Status Harian -->
                    <div class="card card-rounded shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Daftar Hewan & Status Harian</h5>
                            <?php
                            $current_hewan_id = null;
                            $hewan_data = [];
                            foreach ($hewan_list as $hewan) {
                                $hewan_data[$hewan['id_hewan']]['info'] = $hewan;
                                if (!isset($hewan_data[$hewan['id_hewan']]['status'])) {
                                    $hewan_data[$hewan['id_hewan']]['status'] = [];
                                }
                                if (!empty($hewan['id_status'])) {
                                    $hewan_data[$hewan['id_hewan']]['status'][] = $hewan;
                                }
                            }
                            ?>

                            <?php foreach ($hewan_data as $id_hewan => $data): ?>
                                <div class="border-bottom pb-3 mb-3">
                                    <h6 class="fw-bold text-primary">
                                        <i class="mdi mdi-paw"></i> <?= htmlspecialchars($data['info']['nama_hewan']) ?>
                                        <span class="badge bg-secondary ms-2"><?= htmlspecialchars($data['info']['jenis_hewan']) ?></span>
                                        <span class="badge <?= $data['info']['status_vaksin'] == 'Sudah Divaksin' ? 'bg-success' : 'bg-warning' ?> ms-1">
                                            <?= htmlspecialchars($data['info']['status_vaksin']) ?>
                                        </span>
                                    </h6>
                                    <div class="row mb-2">
                                        <div class="col-md-3">
                                            <label class="text-muted small">Ras</label>
                                            <p class="mb-0"><?= htmlspecialchars($data['info']['ras'] ?? '-') ?></p>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="text-muted small">Jenis Kelamin</label>
                                            <p class="mb-0"><?= htmlspecialchars($data['info']['jenis_kelamin']) ?></p>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="text-muted small">Usia</label>
                                            <p class="mb-0"><?= ($data['info']['usia_tahun'] ?? 0) . ' Th ' . ($data['info']['usia_bulan'] ?? 0) . ' Bln' ?></p>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="text-muted small">Berat Badan</label>
                                            <p class="mb-0"><?= number_format($data['info']['berat_badan'] ?? 0, 2) ?> kg</p>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="text-muted small">Warna Bulu</label>
                                            <p class="mb-0"><?= htmlspecialchars($data['info']['warna_bulu'] ?? '-') ?></p>
                                        </div>
                                    </div>

                                    <?php if (!empty($data['info']['karakteristik'])): ?>
                                        <div class="mb-2">
                                            <label class="text-muted small">Karakteristik</label>
                                            <p class="mb-0"><?= htmlspecialchars($data['info']['karakteristik']) ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($data['info']['riwayat_kesehatan'])): ?>
                                        <div class="mb-2">
                                            <label class="text-muted small">Riwayat Kesehatan</label>
                                            <p class="mb-0"><?= htmlspecialchars($data['info']['riwayat_kesehatan']) ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Status Harian -->
                                    <?php if (!empty($data['status'])): ?>
                                        <div class="mt-2">
                                            <label class="text-muted small">Status Harian</label>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered mt-1">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Tanggal</th>
                                                            <th>Kondisi Kesehatan</th>
                                                            <th>Pola Makan</th>
                                                            <th>Aktivitas</th>
                                                            <th>Catatan</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($data['status'] as $status): ?>
                                                            <tr>
                                                                <td><?= date('d M Y', strtotime($status['tgl_update'])) ?></td>
                                                                <td>
                                                                    <span class="badge <?=
                                                                                        $status['kondisi_kesehatan'] == 'Sangat Baik' ? 'bg-success' : ($status['kondisi_kesehatan'] == 'Baik' ? 'bg-info' : ($status['kondisi_kesehatan'] == 'Cukup' ? 'bg-warning' : ($status['kondisi_kesehatan'] == 'Kurang Baik' ? 'bg-danger' : 'bg-secondary')))
                                                                                        ?>">
                                                                        <?= htmlspecialchars($status['kondisi_kesehatan']) ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="badge <?=
                                                                                        $status['pola_makan'] == 'Lahap' ? 'bg-success' : ($status['pola_makan'] == 'Normal' ? 'bg-info' : ($status['pola_makan'] == 'Kurang Nafsu Makan' ? 'bg-warning' : 'bg-danger'))
                                                                                        ?>">
                                                                        <?= htmlspecialchars($status['pola_makan']) ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="badge <?=
                                                                                        $status['aktivitas'] == 'Sangat Aktif' ? 'bg-success' : ($status['aktivitas'] == 'Aktif' ? 'bg-info' : ($status['aktivitas'] == 'Normal' ? 'bg-primary' : ($status['aktivitas'] == 'Lesu' ? 'bg-warning' : 'bg-danger')))
                                                                                        ?>">
                                                                        <?= htmlspecialchars($status['aktivitas']) ?>
                                                                    </span>
                                                                </td>
                                                                <td><?= htmlspecialchars($status['catatan_perawat'] ?? '-') ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-secondary mt-2">
                                            <i class="mdi mdi-information-outline"></i> Belum ada status harian untuk hewan ini.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
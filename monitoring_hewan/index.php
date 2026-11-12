<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$id_user = $_SESSION['id_user'];
$role = $_SESSION['role'] ?? 'Pelanggan';

// Ambil parameter filter tanggal
$filter_tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Ambil semua booking user yang statusnya 'Sedang Dititipkan'
$query_booking = "
    SELECT DISTINCT
        p.id_penitipan,
        p.kode_booking,
        p.tgl_mulai,
        p.tgl_selesai,
        p.status_penitipan
    FROM penitipan p
    INNER JOIN penitipan_hewan ph ON p.id_penitipan = ph.id_penitipan
    WHERE p.id_user = ? 
    AND p.status_penitipan = 'Sedang Dititipkan'
    ORDER BY p.id_penitipan DESC
";

$stmt = mysqli_prepare($koneksi, $query_booking);
mysqli_stmt_bind_param($stmt, 'i', $id_user);
mysqli_stmt_execute($stmt);
$result_booking = mysqli_stmt_get_result($stmt);
$booking_list = [];
while ($row = mysqli_fetch_assoc($result_booking)) {
    $booking_list[] = $row;
}
mysqli_stmt_close($stmt);

$title = 'Monitoring Hewan';
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

                        <!-- HEADER -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">
                                <i class="mdi mdi-paw me-2"></i> Monitoring Hewan
                            </h4>
                        </div>

                        <?php if (count($booking_list) == 0): ?>
                            <div class="text-center py-5">
                                <i class="mdi mdi-inbox" style="font-size: 48px; color: #ccc;"></i>
                                <p class="text-muted mt-3">Anda tidak memiliki hewan yang sedang dititipkan.</p>
                            </div>
                        <?php else: ?>

                            <?php foreach ($booking_list as $booking):
                                $id_penitipan = $booking['id_penitipan'];

                                // Ambil rentang tanggal untuk dropdown
                                $query_range = "
                                    SELECT MIN(tgl_mulai) AS tgl_mulai, MAX(tgl_selesai) AS tgl_selesai
                                    FROM penitipan_hewan
                                    WHERE id_penitipan = ?
                                ";
                                $stmt = mysqli_prepare($koneksi, $query_range);
                                mysqli_stmt_bind_param($stmt, 'i', $id_penitipan);
                                mysqli_stmt_execute($stmt);
                                $result_range = mysqli_stmt_get_result($stmt);
                                $range = mysqli_fetch_assoc($result_range);
                                mysqli_stmt_close($stmt);

                                // Generate opsi tanggal
                                $tanggal_options = [];
                                if ($range['tgl_mulai'] && $range['tgl_selesai']) {
                                    $mulai = strtotime($range['tgl_mulai']);
                                    $selesai = strtotime($range['tgl_selesai']);
                                    for ($i = $mulai; $i <= $selesai; $i += 86400) {
                                        $tanggal_options[] = date('Y-m-d', $i);
                                    }
                                }

                                // Ambil data kandang + hewan + status berdasarkan filter tanggal
                                $query_data = "
                                    SELECT 
                                        k.id_kandang,
                                        k.kode_kandang,
                                        k.nama_kandang,
                                        k.kapasitas,
                                        k.tipe_hewan,
                                        h.id_hewan,
                                        h.nama_hewan,
                                        h.jenis_hewan,
                                        s.id_status,
                                        s.tgl_update,
                                        s.jam_update,
                                        s.kondisi_kesehatan,
                                        s.pola_makan,
                                        s.aktivitas,
                                        s.catatan_perawat,
                                        s.foto_kondisi
                                    FROM penitipan_hewan ph
                                    INNER JOIN kandang k ON ph.id_kandang = k.id_kandang
                                    INNER JOIN hewan h ON ph.id_hewan = h.id_hewan
                                    LEFT JOIN status_harian s ON s.id_penitipan = ph.id_penitipan 
                                        AND s.id_hewan = h.id_hewan 
                                        AND s.tgl_update = ?
                                    WHERE ph.id_penitipan = ?
                                    ORDER BY k.kode_kandang ASC, h.nama_hewan ASC
                                ";

                                $stmt = mysqli_prepare($koneksi, $query_data);
                                mysqli_stmt_bind_param($stmt, 'si', $filter_tanggal, $id_penitipan);
                                mysqli_stmt_execute($stmt);
                                $result_data = mysqli_stmt_get_result($stmt);

                                // Group by kandang
                                $kandang_group = [];
                                while ($row = mysqli_fetch_assoc($result_data)) {
                                    $id_kandang = $row['id_kandang'];
                                    if (!isset($kandang_group[$id_kandang])) {
                                        $kandang_group[$id_kandang] = [
                                            'kode_kandang' => $row['kode_kandang'],
                                            'nama_kandang' => $row['nama_kandang'],
                                            'kapasitas' => $row['kapasitas'],
                                            'tipe_hewan' => $row['tipe_hewan'],
                                            'hewan' => []
                                        ];
                                    }
                                    $kandang_group[$id_kandang]['hewan'][] = $row;
                                }
                                mysqli_stmt_close($stmt);
                            ?>

                                <!-- INFO BOOKING + FILTER -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <strong><?= htmlspecialchars($booking['kode_booking']) ?></strong>
                                        <span class="text-muted ms-2">
                                            <?= date('d M Y', strtotime($booking['tgl_mulai'])) ?> - <?= date('d M Y', strtotime($booking['tgl_selesai'])) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <form method="GET" class="d-flex align-items-center gap-2">
                                            <label class="fw-bold me-1" style="font-size: 14px;">Filter Tanggal:</label>
                                            <select name="tanggal" class="form-select form-select-sm" style="width: auto; font-size: 13px;" onchange="this.form.submit()">
                                                <?php foreach ($tanggal_options as $tgl): ?>
                                                    <option value="<?= $tgl ?>" <?= $tgl == $filter_tanggal ? 'selected' : '' ?>>
                                                        <?= date('d M Y', strtotime($tgl)) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </div>
                                </div>

                                <!-- TABEL PER KANDANG -->
                                <?php foreach ($kandang_group as $kandang): ?>
                                    <div class="card border-0 mb-4" style="background: #f8f9fa; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                                        <div class="card-header bg-transparent border-0 p-3" style="border-bottom: 2px solid #3498db;">
                                            <h5 class="fw-bold mb-0" style="font-size: 16px;">
                                                <i class="mdi mdi-home me-2"></i>
                                                <?= htmlspecialchars($kandang['kode_kandang']) ?> - <?= htmlspecialchars($kandang['nama_kandang']) ?>
                                                <span class="badge bg-secondary ms-2" style="font-size: 11px;">Kapasitas: <?= $kandang['kapasitas'] ?></span>
                                                <span class="badge bg-light text-dark ms-1" style="font-size: 11px; border: 1px solid #ddd;"><?= htmlspecialchars($kandang['tipe_hewan']) ?></span>
                                            </h5>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-hover" style="font-size: 14px;">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="width: 15%;">Nama Hewan</th>
                                                            <th style="width: 18%;">Kondisi Kesehatan</th>
                                                            <th style="width: 15%;">Pola Makan</th>
                                                            <th style="width: 15%;">Aktivitas</th>
                                                            <th style="width: 22%;">Catatan</th>
                                                            <th style="width: 15%;">Foto</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $ada_data = false;
                                                        foreach ($kandang['hewan'] as $hewan):
                                                            if ($hewan['id_status']) {
                                                                $ada_data = true;
                                                            }
                                                        ?>
                                                            <tr>
                                                                <td>
                                                                    <strong><?= htmlspecialchars($hewan['nama_hewan']) ?></strong>
                                                                    <br><small class="text-muted"><?= htmlspecialchars($hewan['jenis_hewan']) ?></small>
                                                                </td>
                                                                <td>
                                                                    <?php if ($hewan['id_status']): ?>
                                                                        <span class="badge <?= $hewan['kondisi_kesehatan'] == 'Sakit' ? 'bg-danger' : ($hewan['kondisi_kesehatan'] == 'Kurang Baik' ? 'bg-warning' : 'bg-success') ?>">
                                                                            <?= htmlspecialchars($hewan['kondisi_kesehatan']) ?>
                                                                        </span>
                                                                        <br><br><small class="text-muted"><?= date('d M Y', strtotime($hewan['tgl_update'])) ?> <?= $hewan['jam_update'] ?></small>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">Belum ada update</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <?php if ($hewan['id_status']): ?>
                                                                        <?= htmlspecialchars($hewan['pola_makan']) ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">-</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <?php if ($hewan['id_status']): ?>
                                                                        <?= htmlspecialchars($hewan['aktivitas']) ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">-</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td style="max-width: 150px; word-wrap: break-word; white-space: normal;">
    <?php if ($hewan['id_status'] && $hewan['catatan_perawat']): 
        $catatan = htmlspecialchars($hewan['catatan_perawat']);
        $panjang = strlen($catatan);
        $batas = 50;
    ?>
        <?php if ($panjang > $batas): ?>
            <span id="catatan-preview-<?= $hewan['id_status'] ?>">
                <?= nl2br(substr($catatan, 0, $batas)) ?>...
            </span>
            <button type="button" class="btn btn-sm btn-link p-0 text-primary" data-bs-toggle="modal" data-bs-target="#catatanModal<?= $hewan['id_status'] ?>" style="font-size: 12px; text-decoration: none;">
                Selengkapnya
            </button>

            <!-- Modal Catatan -->
            <div class="modal fade" id="catatanModal<?= $hewan['id_status'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Catatan Perawat - <?= htmlspecialchars($hewan['nama_hewan']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="white-space: pre-wrap; word-wrap: break-word;">
                            <?= nl2br($catatan) ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?= nl2br($catatan) ?>
        <?php endif; ?>
    <?php else: ?>
        <span class="text-muted">-</span>
    <?php endif; ?>
</td>
                                                                <td class="text-center">
                                                                    <?php if ($hewan['id_status'] && !empty($hewan['foto_kondisi']) && file_exists(__DIR__ . '/../' . $hewan['foto_kondisi'])): ?>
                                                                        <img src="../<?= $hewan['foto_kondisi'] ?>" alt="Foto" style="max-width: 70px; max-height: 70px; border-radius: 5px; cursor: pointer; border: 1px solid #ddd;" data-bs-toggle="modal" data-bs-target="#fotoModal<?= $hewan['id_status'] ?>">
                                                                         <br><br><small class="text-muted">Tekan Gambar</small>
                                                                        <!-- Modal Foto -->
                                                                        <!-- Modal Foto -->
                                                                        <div class="modal fade" id="fotoModal<?= $hewan['id_status'] ?>" tabindex="-1">
                                                                            <div class="modal-dialog modal-xl modal-dialog-centered">
                                                                                <div class="modal-content">
                                                                                    <div class="modal-header">
                                                                                        <h5 class="modal-title">Foto <?= htmlspecialchars($hewan['nama_hewan']) ?></h5>
                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                                    </div>
                                                                                    <div class="modal-body text-center p-0">
                                                                                        <img src="../<?= $hewan['foto_kondisi'] ?>" alt="Foto" style="width: 100%; height: auto; max-height: 80vh; object-fit: contain; border-radius: 0 0 8px 8px;">
                                                                                    </div>
                                                                                    <div class="modal-footer">
                                                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
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

                                                        <?php if (!$ada_data): ?>
                                                            <tr>
                                                                <td colspan="6" class="text-center text-muted py-3">
                                                                    <i class="mdi mdi-alert-circle me-1"></i>
                                                                    Belum ada update untuk tanggal ini
                                                                </td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <hr class="my-4">

                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
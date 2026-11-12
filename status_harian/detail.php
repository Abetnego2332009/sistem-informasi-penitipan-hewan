<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$id_penitipan = filter_var($_GET['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);
$id_kandang = filter_var($_GET['id_kandang'] ?? 0, FILTER_VALIDATE_INT);

if (!$id_penitipan || !$id_kandang) {
    echo "<script>alert('Data tidak lengkap!'); window.location.href='list_hewan.php?id_penitipan=$id_penitipan';</script>";
    exit;
}

// Ambil data booking
$query = "
    SELECT 
        p.*, 
        u.nama_lengkap AS nama_user
    FROM penitipan p
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
    echo "<script>alert('Data tidak ditemukan!'); window.location.href='list_hewan.php?id_penitipan=$id_penitipan';</script>";
    exit;
}

// Ambil data kandang
$query_kandang = "
    SELECT 
        k.id_kandang,
        k.kode_kandang,
        k.nama_kandang,
        k.kapasitas,
        k.tipe_hewan
    FROM kandang k
    WHERE k.id_kandang = ?
";
$stmt = mysqli_prepare($koneksi, $query_kandang);
mysqli_stmt_bind_param($stmt, 'i', $id_kandang);
mysqli_stmt_execute($stmt);
$result_kandang = mysqli_stmt_get_result($stmt);
$kandang = mysqli_fetch_assoc($result_kandang);
mysqli_stmt_close($stmt);

// Ambil hewan di kandang ini
$query_hewan = "
    SELECT 
        h.id_hewan,
        h.nama_hewan,
        h.jenis_hewan
    FROM penitipan_hewan ph
    LEFT JOIN hewan h ON ph.id_hewan = h.id_hewan
    WHERE ph.id_penitipan = ? AND ph.id_kandang = ?
    ORDER BY h.nama_hewan ASC
";
$stmt = mysqli_prepare($koneksi, $query_hewan);
mysqli_stmt_bind_param($stmt, 'ii', $id_penitipan, $id_kandang);
mysqli_stmt_execute($stmt);
$result_hewan = mysqli_stmt_get_result($stmt);

$hewan_list = [];
while ($row = mysqli_fetch_assoc($result_hewan)) {
    $hewan_list[] = $row;
}
mysqli_stmt_close($stmt);


// ============================================================
// QUERY BARU UNTUK HEADER (AMBIL STATUS TERBARU)
// ============================================================
$query_status_header = "
    SELECT s.tgl_update, s.jam_update
    FROM status_harian s
    INNER JOIN penitipan_hewan ph ON s.id_hewan = ph.id_hewan 
        AND s.id_penitipan = ph.id_penitipan
    WHERE s.id_penitipan = ? AND ph.id_kandang = ?
    ORDER BY s.tgl_update DESC, s.jam_update DESC
    LIMIT 1
";
$stmt = mysqli_prepare($koneksi, $query_status_header);
mysqli_stmt_bind_param($stmt, 'ii', $id_penitipan, $id_kandang);
mysqli_stmt_execute($stmt);
$result_status_header = mysqli_stmt_get_result($stmt);
$status_header = mysqli_fetch_assoc($result_status_header);
mysqli_stmt_close($stmt);

$title = 'Detail Status Harian';
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
                                <i class="mdi mdi-clipboard-text me-2"></i> Detail Status Harian
                            </h4>
                            <div>
                                <?php if ($data['status_penitipan'] == 'Sedang Dititipkan'): ?>
                                    <a href="input_status.php?id_penitipan=<?= $id_penitipan ?>&id_kandang=<?= $id_kandang ?>" class="btn btn-primary text-white">
    <i class="mdi mdi-format-list-bulleted"></i> Input Status
</a>
                                <?php endif; ?>
                                <a href="list_hewan.php?id_penitipan=<?= $id_penitipan ?>" class="btn btn-light">Kembali</a>
                            </div>
                        </div>

                        <!-- INFO BOOKING -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <tr>
                                    <td class="text-muted" style="width: 15%;">Kode Booking</td>
                                    <td><strong><?= htmlspecialchars($data['kode_booking']) ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Pelanggan</td>
                                    <td><strong><?= htmlspecialchars($data['nama_user'] ?? '-') ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tanggal Titip</td>
                                    <td><strong><?= date('d M Y', strtotime($data['tgl_mulai'])) ?> - <?= date('d M Y', strtotime($data['tgl_selesai'])) ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Status Penitipan</td>
                                    <td>
                                        <span class="badge <?= $data['status_penitipan'] == 'Sedang Dititipkan' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= htmlspecialchars($data['status_penitipan']) ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- DETAIL KANDANG -->
                        <div class="card border-0 mb-4" style="background: #f8f9fa; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                            <!-- DETAIL KANDANG -->

                            <div class="card-header bg-transparent border-0 p-3" style="border-bottom: 2px solid #3498db;">
                                <h5 class="fw-bold mb-0">
                                    <i class="mdi mdi-clock me-2"></i>
                                    <?php if ($status_header): ?>
                                        Update Terakhir: <?= date('d M Y', strtotime($status_header['tgl_update'])) ?> - <?= $status_header['jam_update'] ?>
                                    <?php else: ?>
                                        Belum ada update status
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body p-3">
                                <?php if (count($hewan_list) > 0): ?>
                                    <?php
                                    // Ambil semua status per tanggal (FILTER BERDASARKAN KANDANG)
                                    $query_all = "
        SELECT s.*, h.nama_hewan, h.jenis_hewan
        FROM status_harian s
        INNER JOIN penitipan_hewan ph ON s.id_hewan = ph.id_hewan 
            AND s.id_penitipan = ph.id_penitipan
        INNER JOIN hewan h ON s.id_hewan = h.id_hewan
        WHERE s.id_penitipan = ? AND ph.id_kandang = ?
        ORDER BY s.tgl_update DESC, s.jam_update DESC
    ";
                                    $stmt = mysqli_prepare($koneksi, $query_all);
                                    mysqli_stmt_bind_param($stmt, 'ii', $id_penitipan, $id_kandang);
                                    mysqli_stmt_execute($stmt);
                                    $result_all = mysqli_stmt_get_result($stmt);

                                    $grouped = [];
                                    while ($row = mysqli_fetch_assoc($result_all)) {
                                        $tgl = $row['tgl_update'];
                                        if (!isset($grouped[$tgl])) {
                                            $grouped[$tgl] = [];
                                        }
                                        $grouped[$tgl][] = $row;
                                    }
                                    mysqli_stmt_close($stmt);

                                    if (count($grouped) > 0):
                                        foreach ($grouped as $tanggal => $statuses):
                                    ?>
                                            <!-- TABEL PER TANGGAL -->
                                            <div class="table-responsive mb-4">
                                                <h6 class="fw-bold mb-2">
                                                    <i class="mdi mdi-calendar-clock me-2"></i>
                                                    <?= date('d M Y', strtotime($tanggal)) ?> - <?= $statuses[0]['jam_update'] ?>
                                                </h6>
                                                <table class="table table-bordered table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Nama Hewan</th>
                                                            <th>Kondisi</th>
                                                            <th>Pola Makan</th>
                                                            <th>Aktivitas</th>
                                                            <th>Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($statuses as $row): ?>
                                                            <tr>
                                                                <td>
                                                                    <strong><?= htmlspecialchars($row['nama_hewan']) ?></strong>
                                                                    <br><small class="text-muted"><?= htmlspecialchars($row['jenis_hewan']) ?></small>
                                                                </td>
                                                                <td>
                                                                    <span class="badge <?= $row['kondisi_kesehatan'] == 'Sakit' ? 'bg-danger' : ($row['kondisi_kesehatan'] == 'Kurang Baik' ? 'bg-warning' : 'bg-success') ?>">
                                                                        <?= htmlspecialchars($row['kondisi_kesehatan']) ?>
                                                                    </span>
                                                                </td>
                                                                <td><?= htmlspecialchars($row['pola_makan']) ?></td>
                                                                <td><?= htmlspecialchars($row['aktivitas']) ?></td>
                                                                <td>
                                                                    <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $row['id_status'] ?>">
                                                                        <i class="mdi mdi-eye"></i> Detail
                                                                    </button>
                                                                </td>
                                                            </tr>

                                                            <!-- MODAL DETAIL -->
                                                            <!-- MODAL DETAIL -->
<div class="modal fade" id="modalDetail<?= $row['id_status'] ?>" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="mdi mdi-clipboard-text me-2"></i> Detail Status
                </h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="fw-bold text-muted small">Tanggal Input</label>
                        <p class="mb-0"><strong><?= date('d M Y', strtotime($row['tgl_update'])) ?></strong></p>
                    </div>
                    <div class="col-6">
                        <label class="fw-bold text-muted small">Jam Input</label>
                        <p class="mb-0"><strong><?= $row['jam_update'] ?></strong></p>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="fw-bold text-muted small">Nama Hewan</label>
                    <p class="mb-0"><strong><?= htmlspecialchars($row['nama_hewan']) ?></strong></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold text-muted small">Kondisi Kesehatan</label>
                    <p class="mb-0">
                        <span class="badge <?= $row['kondisi_kesehatan'] == 'Sakit' ? 'bg-danger' : ($row['kondisi_kesehatan'] == 'Kurang Baik' ? 'bg-warning' : 'bg-success') ?>">
                            <?= htmlspecialchars($row['kondisi_kesehatan']) ?>
                        </span>
                    </p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold text-muted small">Pola Makan</label>
                    <p class="mb-0"><?= htmlspecialchars($row['pola_makan']) ?></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold text-muted small">Aktivitas</label>
                    <p class="mb-0"><?= htmlspecialchars($row['aktivitas']) ?></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold text-muted small">Catatan Perawat</label>
                    <div class="alert alert-secondary p-2 mb-0" style="background-color: #f8f9fa; border-left: 3px solid #17a2b8;">
                        <?= nl2br(htmlspecialchars($row['catatan_perawat'] ?? '-')) ?>
                    </div>
                </div>
                <?php if (!empty($row['foto_kondisi']) && file_exists(__DIR__ . '/../' . $row['foto_kondisi'])): ?>
                    <div class="mt-3 text-center">
                        <label class="fw-bold text-muted small d-block mb-2">Foto Kondisi</label>
                        <img src="../<?= $row['foto_kondisi'] ?>" alt="Foto Kondisi" style="max-width: 100%; max-height: 400px; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                        <br><br>
                        <a href="../<?= $row['foto_kondisi'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="mdi mdi-file-image me-1"></i> Lihat Full Gambar
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Nama Hewan</th>
                                                        <th>Kondisi</th>
                                                        <th>Pola Makan</th>
                                                        <th>Aktivitas</th>
                                                        <th>Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($hewan_list as $hewan): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?= htmlspecialchars($hewan['nama_hewan']) ?></strong>
                                                                <br><small class="text-muted"><?= htmlspecialchars($hewan['jenis_hewan']) ?></small>
                                                            </td>
                                                            <td colspan="4" class="text-muted text-center">Belum ada status</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3">Belum ada hewan di kandang ini.</p>
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
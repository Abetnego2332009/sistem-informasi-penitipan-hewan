<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$role = $_SESSION['role'] ?? 'Pelanggan';
if ($role != 'Super Admin' && $role != 'Admin') {
    echo "<script>alert('Akses ditolak! Hanya Admin.'); window.location.href='../dashboard.php';</script>";
    exit;
}

$id_penitipan = filter_var($_GET['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);
if (!$id_penitipan) {
    echo "<script>alert('ID tidak valid!'); window.location.href='index.php';</script>";
    exit;
}

// Ambil data booking
$query_booking = "
    SELECT p.*, u.nama_lengkap AS nama_user
    FROM penitipan p
    LEFT JOIN users u ON p.id_user = u.id_user
    WHERE p.id_penitipan = ?
";
$stmt = mysqli_prepare($koneksi, $query_booking);
mysqli_stmt_bind_param($stmt, 'i', $id_penitipan);
mysqli_stmt_execute($stmt);
$result_booking = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($result_booking);
mysqli_stmt_close($stmt);

if (!$booking) {
    echo "<script>alert('Data booking tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}

// Ambil daftar kandang + hewan dalam booking ini (GROUP BY KANDANG)
$query = "
    SELECT 
        k.id_kandang,
        k.kode_kandang,
        k.nama_kandang,
        k.kapasitas,
        k.tipe_hewan,
        MIN(ph.tgl_mulai) AS tgl_mulai,
        MAX(ph.tgl_selesai) AS tgl_selesai,
        COUNT(ph.id_hewan) AS jumlah_hewan,
        GROUP_CONCAT(h.nama_hewan SEPARATOR ', ') AS daftar_hewan,
        GROUP_CONCAT(h.id_hewan SEPARATOR ', ') AS daftar_id_hewan
    FROM penitipan_hewan ph
    LEFT JOIN kandang k ON ph.id_kandang = k.id_kandang
    LEFT JOIN hewan h ON ph.id_hewan = h.id_hewan
    WHERE ph.id_penitipan = ?
    GROUP BY k.id_kandang
    ORDER BY k.kode_kandang ASC
";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, 'i', $id_penitipan);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$hewan_list = [];
while ($row = mysqli_fetch_assoc($result)) {
    $hewan_list[] = $row;
}
mysqli_stmt_close($stmt);

$title = 'Daftar Hewan - ' . $booking['kode_booking'];
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
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">
                                <i class="mdi mdi-format-list-bulleted me-2"></i> Daftar Hewan
                            </h4>
                            <div>
                                <a href="index.php" class="btn btn-light">
                                    <i class="mdi mdi-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </div>


                        <?php if (count($hewan_list) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Kode Kandang</th>
                                            <th>Nama Kandang</th>
                                            <th>Jenis</th>
                                            <th>Kapasitas</th>
                                            <th>Hewan</th>
                                            <th>Tanggal Titip</th>
                                            <th>Status Update</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($hewan_list as $row):
                                            $tgl_mulai = date('d M Y', strtotime($row['tgl_mulai']));
                                            $tgl_selesai = date('d M Y', strtotime($row['tgl_selesai']));
                                            $id_hewan_pertama = explode(',', $row['daftar_id_hewan'] ?? '')[0] ?? 0;
                                        ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($row['kode_kandang'] ?? '-') ?></strong></td>
                                                <td><?= htmlspecialchars($row['nama_kandang'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($row['tipe_hewan'] ?? '-') ?></td>
                                                <td><?= $row['kapasitas'] ?? 0 ?> hewan</td>
                                                <td>
                                                    <?php if ($row['jumlah_hewan'] > 0): ?>
                                                        <strong><?= $row['jumlah_hewan'] ?> hewan</strong>
                                                        <br><small class="text-muted">🐾 <?= htmlspecialchars($row['daftar_hewan']) ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">Belum ada hewan</span>
                                                    <?php endif; ?>
                                                </td>

                                                <td><?= $tgl_mulai ?> - <?= $tgl_selesai ?></td>
                                                <td>
                                                    <?php
                                                    // Ambil semua ID hewan dalam kandang ini
                                                    $hewan_ids = array_filter(explode(',', $row['daftar_id_hewan'] ?? ''));
                                                    $total_hewan = count($hewan_ids);
                                                    $sudah_update = 0;
                                                    $jam_terakhir = null;
                                                    $tanggal_update = null;

                                                    if ($total_hewan > 0) {
                                                        foreach ($hewan_ids as $id) {
                                                            $cek = mysqli_query($koneksi, "
                                                            SELECT tgl_update, jam_update 
                                                            FROM status_harian 
                                                            WHERE id_penitipan = $id_penitipan 
                                                            AND id_hewan = $id 
                                                            AND tgl_update = CURDATE()
                                                        ");

                                                            if (mysqli_num_rows($cek) > 0) {
                                                                $data_cek = mysqli_fetch_assoc($cek);
                                                                // HITUNG SEBAGAI UPDATE JIKA JAM >= 08:00
                                                                if ($data_cek['jam_update'] >= '08:00:00') {
                                                                    $sudah_update++;
                                                                    $jam_terakhir = $data_cek['jam_update'];
                                                                    $tanggal_update = $data_cek['tgl_update'];
                                                                }
                                                            }
                                                        }
                                                    }

                                                    // Tentukan Status
                                                    if ($total_hewan == 0) {
                                                        echo '<span class="text-muted">-</span>';
                                                    } elseif ($sudah_update == $total_hewan) {
                                                        echo '<span class="badge bg-success"><i class="mdi mdi-check-circle"></i> Sudah Update</span>';
                                                        if ($jam_terakhir) {
                                                            echo '<br><small class="text-muted">Jam: ' . $jam_terakhir . '</small>';
                                                        }
                                                    } elseif ($sudah_update > 0 && $sudah_update < $total_hewan) {
                                                        echo '<span class="badge bg-warning"><i class="mdi mdi-alert"></i> Sebagian</span>';
                                                        echo '<br><small class="text-muted">' . $sudah_update . '/' . $total_hewan . ' sudah</small>';
                                                    } else {
                                                        echo '<span class="badge bg-danger"><i class="mdi mdi-clock"></i> Belum Update</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <a href="detail.php?id_penitipan=<?= $id_penitipan ?>&id_kandang=<?= $row['id_kandang'] ?>" class="btn btn-sm btn-info text-white">
                                                        <i class="mdi mdi-eye"></i> Detail
                                                    </a>
                                                    <a href="input_status.php?id_penitipan=<?= $id_penitipan ?>&id_kandang=<?= $row['id_kandang'] ?>" class="btn btn-sm btn-success text-white">
                                                        <i class="mdi mdi-plus"></i> Input Status
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="mdi mdi-inbox" style="font-size:48px; color:#ccc;"></i>
                                <p class="text-muted mt-3">Belum ada data hewan untuk booking ini.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>
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
$id_kandang = filter_var($_GET['id_kandang'] ?? 0, FILTER_VALIDATE_INT);

if (!$id_penitipan || !$id_kandang) {
    echo "<script>alert('Data tidak lengkap!'); window.location.href='list_hewan.php?id_penitipan=$id_penitipan';</script>";
    exit;
}

// Ambil data kandang & hewan
$query = "
    SELECT 
        ph.id_penitipan_hewan,
        ph.tgl_mulai,
        ph.tgl_selesai,
        k.id_kandang,
        k.kode_kandang,
        k.nama_kandang,
        k.kapasitas,
        k.tipe_hewan,
        h.id_hewan,
        h.nama_hewan,
        h.jenis_hewan
    FROM penitipan_hewan ph
    LEFT JOIN kandang k ON ph.id_kandang = k.id_kandang
    LEFT JOIN hewan h ON ph.id_hewan = h.id_hewan
    WHERE ph.id_penitipan = ? AND ph.id_kandang = ?
    ORDER BY h.nama_hewan ASC
";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, 'ii', $id_penitipan, $id_kandang);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$hewan_list = [];
while ($row = mysqli_fetch_assoc($result)) {
    $hewan_list[] = $row;
}
mysqli_stmt_close($stmt);

if (count($hewan_list) == 0) {
    echo "<script>alert('Tidak ada hewan di kandang ini!'); window.location.href='list_hewan.php?id_penitipan=$id_penitipan';</script>";
    exit;
}

// Ambil data booking
$query_booking = "
    SELECT p.kode_booking, u.nama_lengkap AS nama_user
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

$title = 'Input Status Harian';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card card-rounded shadow-sm border">
                    <div class="card-body">

                        <!-- HEADER -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">
                                <i class="mdi mdi-plus-circle me-2"></i> Input Status Harian
                            </h4>
                            <div>
                                <a href="list_hewan.php?id_penitipan=<?= $id_penitipan ?>" class="btn btn-light btn-sm">
                                    <i class="mdi mdi-arrow-left"></i> Kembali
                                </a>
                                <button type="submit" form="formStatus" class="btn btn-primary btn-sm text-white">
                                    <i class="mdi mdi-content-save"></i> Simpan Semua
                                </button>
                            </div>
                        </div>

                        <!-- INFO BOOKING -->
                        <!-- INFO BOOKING (PAKE TABEL, KAYAK BOOKING) -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <tr>
                                    <td class="text-muted" style="width: 15%;">Kode Booking</td>
                                    <td><strong><?= htmlspecialchars($booking['kode_booking']) ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Pelanggan</td>
                                    <td><strong><?= htmlspecialchars($booking['nama_user'] ?? '-') ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Kandang</td>
                                    <td><strong><?= htmlspecialchars($hewan_list[0]['kode_kandang']) ?></strong> - <?= htmlspecialchars($hewan_list[0]['nama_kandang']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Kapasitas</td>
                                    <td><strong><?= $hewan_list[0]['kapasitas'] ?> hewan</strong></td>
                                </tr>
                            </table>
                        </div>
                        <!-- ============================================================ -->
                        <!-- FORM STATUS (PER HEWAN) - KAYAK BOOKING                     -->
                        <!-- ============================================================ -->
                        <form id="formStatus" action="proses.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id_penitipan" value="<?= $id_penitipan ?>">

                            <?php foreach ($hewan_list as $index => $hewan): ?>
                                <div class="slot-item mb-4 border-bottom pb-4">
                                    <p class="fw-bold text-secondary mb-3">
                                        <i class="mdi mdi-paw me-2"></i>
                                        Hewan <?= $index + 1 ?>: <strong><?= htmlspecialchars($hewan['nama_hewan']) ?></strong>
                                        <span class="badge bg-info ms-2"><?= htmlspecialchars($hewan['jenis_hewan']) ?></span>
                                    </p>

                                    <input type="hidden" name="hewan[<?= $index ?>][id_hewan]" value="<?= $hewan['id_hewan'] ?>">

                                    <!-- Tanggal & Jam -->
                                    <div class="row">
                                        <div class="col-md-6 form-group mb-3">
                                            <label class="fw-bold">Tanggal Update <span class="text-danger">*</span></label>
                                            <input type="date" name="hewan[<?= $index ?>][tgl_update]" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                        </div>
                                        <div class="col-md-6 form-group mb-3">
                                            <label class="fw-bold">Jam Update <span class="text-danger">*</span></label>
                                            <input type="time" name="hewan[<?= $index ?>][jam_update]" class="form-control" value="<?= date('H:i') ?>" min="08:00" max="20:00" required>
                                        </div>
                                    </div>

                                    <!-- Kondisi, Makan, Aktivitas -->
                                    <div class="row">
                                        <div class="col-md-4 form-group mb-3">
                                            <label class="fw-bold">Kondisi Kesehatan <span class="text-danger">*</span></label>
                                            <select name="hewan[<?= $index ?>][kondisi_kesehatan]" class="form-control" required>
                                                <option value="Sangat Baik">Sangat Baik</option>
                                                <option value="Baik" selected>Baik</option>
                                                <option value="Cukup">Cukup</option>
                                                <option value="Kurang Baik">Kurang Baik</option>
                                                <option value="Sakit">Sakit</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 form-group mb-3">
                                            <label class="fw-bold">Pola Makan <span class="text-danger">*</span></label>
                                            <select name="hewan[<?= $index ?>][pola_makan]" class="form-control" required>
                                                <option value="Lahap">Lahap</option>
                                                <option value="Normal" selected>Normal</option>
                                                <option value="Kurang Nafsu Makan">Kurang Nafsu Makan</option>
                                                <option value="Tidak Mau Makan">Tidak Mau Makan</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 form-group mb-3">
                                            <label class="fw-bold">Aktivitas <span class="text-danger">*</span></label>
                                            <select name="hewan[<?= $index ?>][aktivitas]" class="form-control" required>
                                                <option value="Sangat Aktif">Sangat Aktif</option>
                                                <option value="Aktif">Aktif</option>
                                                <option value="Normal" selected>Normal</option>
                                                <option value="Lesu">Lesu</option>
                                                <option value="Tidak Aktif">Tidak Aktif</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Catatan -->
                                    <div class="form-group mb-3">
                                        <label class="fw-bold">Catatan Perawat</label>
                                        <textarea
                                            name="hewan[<?= $index ?>][catatan_perawat]"
                                            class="form-control"
                                            style="min-height: 120px;"
                                            placeholder="Catatan kondisi hewan hari ini..."></textarea>

                                        <!-- Foto -->
                                        <div class="form-group mb-3">
                                            <label class="fw-bold">Foto Kondisi</label>
                                            <input type="file" name="hewan[<?= $index ?>][foto_kondisi]" class="form-control" accept="image/*" onchange="previewImage(this, <?= $index ?>)">
                                            <img id="preview_<?= $index ?>" style="display:none; margin-top:10px; max-width:200px; border:1px solid #ddd; padding:5px;">
                                            <small class="text-muted d-block mt-1">Upload foto kondisi hewan hari ini (JPG, JPEG, PNG)</small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>

<script>
    function previewImage(input, index) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('preview_' + index);
            output.src = reader.result;
            output.style.display = 'block';
        }
        if (input.files && input.files[0]) {
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

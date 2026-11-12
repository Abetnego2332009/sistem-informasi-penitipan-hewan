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

$id_user = $_SESSION['id_user'];
$id_penitipan = filter_var($_GET['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);

// Ambil data booking
$query = "
    SELECT p.*, u.nama_lengkap AS nama_user,
           (SELECT COUNT(*) FROM penitipan_hewan WHERE id_penitipan = p.id_penitipan) AS jumlah_hewan
    FROM penitipan p
    LEFT JOIN users u ON p.id_user = u.id_user
    WHERE p.id_penitipan = ? AND p.status_penitipan IN ('Sedang Dititipkan', 'Disetujui')
";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, 'i', $id_penitipan);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data) {
    echo "<script>alert('Data penitipan tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}

$title = 'Tambah Status Harian';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card card-rounded">
                    <div class="card-body">
                        <h4 class="card-title">
                            <i class="mdi mdi-plus-circle me-2"></i> Tambah Status Harian
                        </h4>
                        <p class="card-description">
                            Booking: <strong><?= htmlspecialchars($data['kode_booking']) ?></strong>
                            | Pelanggan: <strong><?= htmlspecialchars($data['nama_user'] ?? '-') ?></strong>
                            | Hewan: <strong><?= $data['jumlah_hewan'] ?></strong>
                        </p>

                        <form action="proses.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id_penitipan" value="<?= $id_penitipan ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal Update <span class="text-danger">*</span></label>
                                        <input type="date" name="tgl_update" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Jam Update <span class="text-danger">*</span></label>
                                        <input type="time" name="jam_update" class="form-control" value="<?= date('H:i') ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Kondisi Kesehatan <span class="text-danger">*</span></label>
                                        <select name="kondisi_kesehatan" class="form-control" required>
                                            <option value="Sangat Baik">Sangat Baik</option>
                                            <option value="Baik" selected>Baik</option>
                                            <option value="Cukup">Cukup</option>
                                            <option value="Kurang Baik">Kurang Baik</option>
                                            <option value="Sakit">Sakit</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Pola Makan <span class="text-danger">*</span></label>
                                        <select name="pola_makan" class="form-control" required>
                                            <option value="Lahap">Lahap</option>
                                            <option value="Normal" selected>Normal</option>
                                            <option value="Kurang Nafsu Makan">Kurang Nafsu Makan</option>
                                            <option value="Tidak Mau Makan">Tidak Mau Makan</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Aktivitas <span class="text-danger">*</span></label>
                                        <select name="aktivitas" class="form-control" required>
                                            <option value="Sangat Aktif">Sangat Aktif</option>
                                            <option value="Aktif">Aktif</option>
                                            <option value="Normal" selected>Normal</option>
                                            <option value="Lesu">Lesu</option>
                                            <option value="Tidak Aktif">Tidak Aktif</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Catatan Perawat</label>
                                <textarea name="catatan_perawat" class="form-control" rows="3" placeholder="Catatan kondisi hewan hari ini..."></textarea>
                            </div>

                            <div class="form-group">
                                <label>Foto Kondisi</label>
                                <input type="file" name="foto_kondisi" class="form-control" accept="image/*" onchange="previewImage(event)">
                                <img id="preview" style="display:none; margin-top:10px; max-width:200px; border:1px solid #ddd; padding:5px;">
                                <small class="text-muted d-block">Upload foto kondisi hewan hari ini (JPG, JPEG, PNG)</small>
                            </div>

                            <div class="d-flex justify-content-between mt-3">
                                <a href="detail.php?id_penitipan=<?= $id_penitipan ?>" class="btn btn-light">Kembali</a>
                                <button type="submit" class="btn btn-primary text-white">Simpan Status</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>

<script>
    function previewImage(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('preview');
            output.src = reader.result;
            output.style.display = 'block';
        }
        reader.readAsDataURL(event.target.files[0]);
    }
</script>
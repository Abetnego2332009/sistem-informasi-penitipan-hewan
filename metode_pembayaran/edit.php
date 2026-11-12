<?php
session_start();
require_once __DIR__ . '/../koneksi.php';
$title = 'Edit Metode Pembayaran';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';

$id_metode = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$query = "SELECT * FROM metode_pembayaran WHERE id_metode = $id_metode";
$res = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($res);

if (!$data) {
    echo "<script>alert('Data tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}
?>
<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-8 grid-margin stretch-card">
                <div class="card card-rounded shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title">Edit Metode Pembayaran</h4>

                        <form class="forms-sample" method="POST" action="proses.php" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id_metode" value="<?= $data['id_metode']; ?>">
                            <input type="hidden" name="hapus_gambar" id="hapus_gambar" value="0">

                            <div class="form-group mb-3">
                                <label>Nama Metode <span class="text-danger">*</span></label>
                                <input type="text" name="nama_metode" class="form-control" value="<?= htmlspecialchars($data['nama_metode']); ?>" required>
                            </div>

                            <div class="form-group mb-3">
                                <label>Tipe Metode <span class="text-danger">*</span></label>
                                <select name="tipe_metode" class="form-select" required>
                                    <option value="QRIS" <?= $data['tipe_metode'] == 'QRIS' ? 'selected' : ''; ?>>QRIS</option>
                                    <option value="Transfer Bank" <?= $data['tipe_metode'] == 'Transfer Bank' ? 'selected' : ''; ?>>Transfer Bank</option>
                                    <option value="E-Wallet" <?= $data['tipe_metode'] == 'E-Wallet' ? 'selected' : ''; ?>>E-Wallet</option>
                                    <option value="Tunai" <?= $data['tipe_metode'] == 'Tunai' ? 'selected' : ''; ?>>Tunai</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label>Keterangan / Instruksi</label>
                                <textarea name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($data['keterangan']); ?></textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label class="fw-bold">Gambar (Logo/QRIS)</label>
                                <?php if (!empty($data['gambar_metode'])): ?>
                                    <div id="old-image-container" class="mb-3">
                                        <small class="text-muted d-block mb-1">Gambar saat ini:</small>
                                        <div class="border rounded bg-light mb-2" style="width: 160px; height: 120px; overflow: hidden;">
                                            <img src="../uploads/metode_pembayaran/<?= $data['gambar_metode']; ?>" style="width: 100%; height: 100%; object-fit: contain; background-color: #f8f9fa;">
                                        </div>
                                        <button type="button" class="btn btn-danger btn-sm text-white" onclick="hapusGambarLama()">Hapus Gambar</button>
                                    </div>
                                <?php endif; ?>

                                <input type="file" name="gambar_metode" id="input-foto-edit" class="form-control" accept="image/*">
                                <small class="text-muted d-block mt-1">Pilih file baru untuk mengganti gambar.</small>

                                <div id="preview-container-edit" class="mt-3" style="display: none;">
                                    <div class="border rounded bg-light mb-2" style="width: 160px; height: 120px; overflow: hidden; border: 2px dashed #ffc107 !important;">
                                        <img id="live-preview-edit" src="#" alt="Pratinjau" style="width: 100%; height: 100%; object-fit: contain; background-color: #f8f9fa;">
                                    </div>
                                    <button type="button" class="btn btn-dark btn-sm text-white" onclick="batalPilihanBaru()">Batal Ganti</button>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Urutan Tampil</label>
                                <input type="number" name="urutan" class="form-control" value="<?= $data['urutan']; ?>" min="0">
                            </div>

                            <button type="submit" class="btn btn-primary text-white me-2">Simpan Perubahan</button>
                            <a href="index.php" class="btn btn-light">Batal</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function hapusGambarLama() {
            document.getElementById('old-image-container').style.display = 'none';
            document.getElementById('hapus_gambar').value = '1';
            batalPilihanBaru();
        }

        document.getElementById('input-foto-edit').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(ev) {
                    document.getElementById('live-preview-edit').src = ev.target.result;
                    document.getElementById('preview-container-edit').style.display = 'block';
                    var oldBox = document.getElementById('old-image-container');
                    if (oldBox) oldBox.style.display = 'none';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        function batalPilihanBaru() {
            document.getElementById('input-foto-edit').value = '';
            document.getElementById('preview-container-edit').style.display = 'none';
            var oldBox = document.getElementById('old-image-container');
            if (oldBox && document.getElementById('hapus_gambar').value === '0') {
                oldBox.style.display = 'block';
            }
        }
    </script>
    <?php require '../templates/footer.php'; ?>
</div>
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';
$title = 'Tambah Metode Pembayaran';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>
<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-8 grid-margin stretch-card">
                <div class="card card-rounded shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title">Tambah Metode Pembayaran</h4>

                        <form class="forms-sample" method="POST" action="proses.php" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="insert">

                            <div class="form-group mb-3">
                                <label>Nama Metode <span class="text-danger">*</span></label>
                                <input type="text" name="nama_metode" class="form-control" placeholder="Contoh: QRIS, BCA, OVO" required>
                            </div>

                            <div class="form-group mb-3">
                                <label>Tipe Metode <span class="text-danger">*</span></label>
                                <select name="tipe_metode" class="form-select" required>
                                    <option value="">-- Pilih Tipe --</option>
                                    <option value="QRIS">QRIS</option>
                                    <option value="Transfer Bank">Transfer Bank</option>
                                    <option value="E-Wallet">E-Wallet</option>
                                    <option value="Tunai">Tunai</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label>Keterangan / Instruksi</label>
                                <textarea name="keterangan" class="form-control" rows="3" placeholder="Contoh: Scan QR Code berikut untuk melakukan pembayaran."></textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label>Gambar (Logo/QRIS)</label>
                                <input type="file" name="gambar_metode" id="input-foto" class="form-control" accept="image/*">
                                <small class="text-muted d-block mt-1">Format: JPG, PNG, WEBP. Maksimal 2MB</small>
                                <div id="preview-container" class="mt-3" style="display: none;">
                                    <div class="border rounded bg-light mb-2" style="width: 160px; height: 120px; overflow: hidden;">
                                        <img id="live-preview" src="#" alt="Pratinjau" style="width: 100%; height: 100%; object-fit: contain; background-color: #f8f9fa;">
                                    </div>
                                    <button type="button" class="btn btn-danger btn-sm text-white" onclick="document.getElementById('input-foto').value='';document.getElementById('preview-container').style.display='none';">Hapus</button>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Urutan Tampil</label>
                                <input type="number" name="urutan" class="form-control" value="0" min="0">
                                <small class="text-muted">Semakin kecil angka, semakin atas tampilnya.</small>
                            </div>

                            <button type="submit" class="btn btn-primary text-white me-2">Simpan</button>
                            <a href="index.php" class="btn btn-light">Batal</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('input-foto').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(ev) {
                    document.getElementById('live-preview').src = ev.target.result;
                    document.getElementById('preview-container').style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
    <?php require '../templates/footer.php'; ?>
</div>
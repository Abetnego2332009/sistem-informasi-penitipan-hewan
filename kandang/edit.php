<?php 
session_start();
require_once __DIR__ . '/../koneksi.php';
$title = 'Edit Kandang'; 
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';

$id_kandang = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$query = "SELECT * FROM kandang WHERE id_kandang = $id_kandang AND is_active = 1";
$res = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($res);

if (!$data) {
    echo "<script>alert('Data kandang tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}
?>
<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-8 grid-margin stretch-card">
                <div class="card card-rounded">
                    <div class="card-body">
                        <h4 class="card-title">Ubah Data Kandang</h4>
                        
                        <form class="forms-sample" method="POST" action="proses.php" enctype="multipart/form-data" onsubmit="return validasiFormEdit(this)">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id_kandang" value="<?= $data['id_kandang']; ?>">
                            
                            <input type="hidden" name="hapus_foto_saja" id="hapus_foto_saja" value="0">
                            
                            <div class="form-group mb-3">
                                <label>Kode Kandang</label>
                                <input type="text" name="kode_kandang" class="form-control" value="<?= htmlspecialchars($data['kode_kandang']); ?>" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>Nama Kandang</label>
                                <input type="text" name="nama_kandang" class="form-control" value="<?= htmlspecialchars($data['nama_kandang']); ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>Jenis Hewan</label>
                                    <select name="tipe_hewan" class="form-select">
                                        <option value="Anjing" <?= $data['tipe_hewan'] == 'Anjing' ? 'selected' : ''; ?>>Anjing</option>
                                        <option value="Kucing" <?= $data['tipe_hewan'] == 'Kucing' ? 'selected' : ''; ?>>Kucing</option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>Ukuran</label>
                                    <select name="ukuran" class="form-select">
                                        <option value="Kecil" <?= $data['ukuran'] == 'Kecil' ? 'selected' : ''; ?>>Kecil</option>
                                        <option value="Sedang" <?= $data['ukuran'] == 'Sedang' ? 'selected' : ''; ?>>Sedang</option>
                                        <option value="Besar" <?= $data['ukuran'] == 'Besar' ? 'selected' : ''; ?>>Besar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>Kapasitas (Ekor)</label>
                                    <input type="number" name="kapasitas" class="form-control" value="<?= $data['kapasitas']; ?>" min="1" required>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>Stok Unit</label>
                                    <input type="number" name="stok" class="form-control" value="<?= $data['stok']; ?>" min="1" required>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label>Tarif Sewa Per Hari (Rp)</label>
                                <input type="number" name="tarif_per_hari" class="form-control" value="<?= (int)$data['tarif_per_hari']; ?>" min="0" required>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="fw-bold">Foto Unit Kandang</label>
                                
                                <?php if(!empty($data['foto_kandang'])): ?>
                                    <div class="mb-3" id="old-image-container">
                                        <small class="text-muted d-block mb-1">Foto aktif saat ini:</small>
                                        <div class="border rounded bg-light mb-2" style="width: 160px; height: 120px; overflow: hidden;">
                                            <img src="../uploads/kandang/<?= $data['foto_kandang']; ?>" style="width: 100%; height: 100%; object-fit: contain; background-color: #f8f9fa;">
                                        </div>
                                        <button type="button" class="btn btn-danger btn-sm text-white py-1" style="width: 160px;" onclick="hapusGambarLamaUI()">
                                            <i class="mdi mdi-delete"></i> Hapus Gambar
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <input type="file" name="foto_kandang" id="input-foto-edit" class="form-control" accept="image/*">
                                <small class="text-muted d-block mt-1">Pilih file baru jika ingin mengganti foto aktif di atas.</small>

                                <div id="preview-container-edit" class="mt-3" style="display: none;">
                                    <small class="text-warning d-block mb-1 fw-bold"><i class="mdi mdi-eye"></i> Pratinjau Foto Baru (Belum Disimpan):</small>
                                    <div class="border rounded bg-light mb-2" style="width: 160px; height: 120px; overflow: hidden; border: 2px dashed #ffc107 !important;">
                                       <img id="live-preview-edit" src="#" alt="Pratinjau Baru" style="width: 100%; height: 100%; object-fit: contain; background-color: #f8f9fa;">
                                    </div>
                                    <button type="button" class="btn btn-dark btn-sm text-white py-1" style="width: 160px;" onclick="batalPilihanBaru()">
                                        Batal Ganti
                                    </button>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Fasilitas</label>
                                <textarea name="fasilitas" class="form-control" rows="3"><?= htmlspecialchars($data['fasilitas']); ?></textarea>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary text-white">Simpan Perubahan</button>
                                <a href="index.php" class="btn btn-light">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // 1. Fungsi saat tombol "Hapus Gambar" aktif diklik
    function hapusGambarLamaUI() {
        // Cuma menyembunyikan container di layar tampilan
        document.getElementById('old-image-container').style.display = 'none';
        
        // Set nilai input hidden jadi 1 (menandakan gambar terhapus di UI)
        document.getElementById('hapus_foto_saja').value = '1';
        
        // Kosongkan juga input file baru jika sebelumnya sempat memilih file
        batalPilihanBaru();
    }

    // 2. Fungsi mengamati jika ada file foto baru yang dipilih
    document.getElementById('input-foto-edit').addEventListener('change', function(event) {
        var input = event.target;
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var previewImg = document.getElementById('live-preview-edit');
                var container = document.getElementById('preview-container-edit');
                
                previewImg.src = e.target.result;
                container.style.display = 'block'; // Munculkan pratinjau baru
                
                // Sembunyikan foto lama sementara karena sedang memilih foto baru
                var oldBox = document.getElementById('old-image-container');
                if(oldBox) oldBox.style.display = 'none';
            }
            reader.readAsDataURL(input.files[0]);
        }
    });

    // 3. Fungsi membatalkan pilihan file baru
    function batalPilihanBaru() {
        document.getElementById('input-foto-edit').value = ''; // Reset input file
        document.getElementById('preview-container-edit').style.display = 'none'; // Sembunyikan preview baru
        
        // Kembalikan foto lama ke tampilan KECUALI jika admin sudah klik tombol "Hapus Gambar"
        var oldBox = document.getElementById('old-image-container');
        if(oldBox && document.getElementById('hapus_foto_saja').value === '0') {
            oldBox.style.display = 'block';
        }
    }

    function validasiFormEdit(form) {
        if (parseInt(form.kapasitas.value) <= 0 || parseInt(form.stok.value) <= 0) {
            alert("Validasi Gagal: Angka minimal adalah 1!");
            return false;
        }
        if (parseFloat(form.tarif_per_hari.value) < 0) {
            alert("Validasi Gagal: Harga/Tarif tidak bernilai negatif!");
            return false;
        }
        return true;
    }
    </script>
<?php require '../templates/footer.php'; ?>
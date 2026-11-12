<?php 
session_start();
require_once __DIR__ . '/../koneksi.php';
$title = 'Tambah Kandang'; 
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>
<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-8 grid-margin stretch-card">
                <div class="card card-rounded">
                    <div class="card-body">
                        <h4 class="card-title">Tambah Kandang Baru</h4>
                        
                        <form class="forms-sample" method="POST" action="proses.php" enctype="multipart/form-data" onsubmit="return validasiFormKandang(this)">
                            <input type="hidden" name="action" value="insert">
                            
                            <div class="form-group mb-3">
                                <label for="kode_kandang">Kode Kandang</label>
                                <input type="text" name="kode_kandang" class="form-control" id="kode_kandang" placeholder="Contoh: KDG-001" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="nama_kandang">Nama Kandang</label>
                                <input type="text" name="nama_kandang" class="form-control" id="nama_kandang" placeholder="Contoh: Kandang Isolasi Besar" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>Jenis Hewan</label>
                                    <select name="tipe_hewan" class="form-select">
                                        <option value="Anjing">Anjing</option>
                                        <option value="Kucing">Kucing</option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>Ukuran</label>
                                    <select name="ukuran" class="form-select">
                                        <option value="Kecil">Kecil</option>
                                        <option value="Sedang">Sedang</option>
                                        <option value="Besar">Besar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>Kapasitas (Ekor)</label>
                                    <input type="number" name="kapasitas" class="form-control" value="1" min="1" required>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>Jumlah Unit (Stok)</label>
                                    <input type="number" name="stok" class="form-control" value="1" min="1" required>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label>Tarif Sewa Harian (Rp)</label>
                                <input type="number" name="tarif_per_hari" class="form-control" placeholder="50000" min="0" required>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="fw-bold">Upload Foto Unit Kandang</label>
                                <input type="file" name="foto_kandang" id="input-foto" class="form-control" accept="image/*">
                                <small class="text-muted d-block mt-1">Format didukung: JPG, PNG, WEBP. Maksimal 2MB</small>
                                
                                <div id="preview-container" class="mt-3" style="display: none;">
                                    <small class="text-success d-block mb-1 fw-bold"><i class="mdi mdi-eye"></i> Pratinjau Foto Pilihan:</small>
                                    <div class="border rounded bg-light mb-2" style="width: 160px; height: 120px; overflow: hidden; border: 2px dashed #007bff !important;">
                                        <img id="live-preview" src="#" alt="Pratinjau" style="width: 100%; height: 100%; object-fit: contain; background-color: #f8f9fa;">
                                    </div>
                                    <button type="button" class="btn btn-danger btn-sm text-white py-1" style="width: 160px;" onclick="hapusPreviewTambah()">
                                        <i class="mdi mdi-delete"></i> Hapus Gambar
                                    </button>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Deskripsi Fasilitas</label>
                                <textarea name="fasilitas" class="form-control" rows="3" placeholder="AC, blower, kasur..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary text-white me-2">Simpan Kandang</button>
                            <a href="index.php" class="btn btn-light">Batal</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // JS Live Preview Tambah
    document.getElementById('input-foto').addEventListener('change', function(event) {
        var input = event.target;
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var previewImg = document.getElementById('live-preview');
                var container = document.getElementById('preview-container');
                previewImg.src = e.target.result;
                container.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    });

    // Fungsi menghapus/membatalkan preview pilihan gambar (Murni UI)
    function hapusPreviewTambah() {
        document.getElementById('input-foto').value = ''; // Kosongkan input file
        document.getElementById('preview-container').style.display = 'none'; // Sembunyikan kotak preview
    }

    function validasiFormKandang(form) {
        if (parseInt(form.kapasitas.value) <= 0 || parseInt(form.stok.value) <= 0) {
            alert("Validasi Gagal: Kapasitas dan stok unit fisik minimal bernilai 1!");
            return false;
        }
        if (parseFloat(form.tarif_per_hari.value) < 0) {
            alert("Validasi Gagal: Harga sewa tidak boleh bernilai negatif!");
            return false;
        }
        return true;
    }
    </script>
<?php require '../templates/footer.php'; ?>
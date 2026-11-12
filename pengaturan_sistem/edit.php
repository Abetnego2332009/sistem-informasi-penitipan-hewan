<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_user']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'])) {
    header('Location: ../login.php');
    exit;
}

$title = 'Edit Pengaturan - Admin';
require '../templates/header.php';
require '../templates/sidebar.php';

// Ambil semua data
$umum = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pengaturan_umum LIMIT 1"));
$banner = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM banner LIMIT 1"));
$keunggulan = mysqli_query($koneksi, "SELECT * FROM keunggulan ORDER BY urutan ASC");
$kandang = mysqli_query($koneksi, "SELECT * FROM kandang ORDER BY kode_kandang ASC");
$testimoni = mysqli_query($koneksi, "SELECT * FROM testimoni ORDER BY created_at DESC");
?>

<style>
    .section-title { font-size:16px; font-weight:600; color:#333; margin-bottom:12px; padding-bottom:6px; border-bottom:1px solid #eee; }
    .form-group { margin-bottom:15px; }
    .form-control-lg { font-size:14px; padding:10px 14px; }
    .switch-container { display:flex; align-items:center; gap:10px; }
    .switch-container .form-check-input { width:50px; height:26px; cursor:pointer; }
    .switch-container .form-check-input:checked { background-color:#28a745; border-color:#28a745; }
    .table-edit td, .table-edit th { font-size:13px; vertical-align:middle; }
    .preview-img { max-width:150px; max-height:150px; border:1px solid #ddd; padding:4px; border-radius:4px; margin-top:6px; display:block; }
    .preview-thumb { max-width:60px; max-height:60px; border:1px solid #ddd; padding:2px; border-radius:4px; margin-top:4px; }

    /* ===== TOMBOL NATURAL ===== */
    .btn {
        border-radius: 8px !important;
        padding: 8px 20px !important;
        font-weight: 500 !important;
        transition: all 0.2s ease !important;
        border: none !important;
    }
    .btn:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    }

    .btn-primary {
        background: #4a6cf7 !important;
        color: #fff !important;
    }
    .btn-primary:hover {
        background: #3a5bd9 !important;
    }

    .btn-secondary {
        background: #f1f3f5 !important;
        border: 1px solid #dee2e6 !important;
        color: #495057 !important;
    }
    .btn-secondary:hover {
        background: #e9ecef !important;
    }

    .btn-warning {
        background: #ffc107 !important;
        color: #212529 !important;
    }
    .btn-warning:hover {
        background: #e0a800 !important;
    }

    .btn-danger {
        background: #dc3545 !important;
        color: #fff !important;
    }
    .btn-danger:hover {
        background: #c82333 !important;
    }

    .btn-success {
        background: #28a745 !important;
        color: #fff !important;
    }
    .btn-success:hover {
        background: #218838 !important;
    }

    .btn-info {
        background: #17a2b8 !important;
        color: #fff !important;
    }
    .btn-info:hover {
        background: #138496 !important;
    }

    .btn-round {
        border-radius: 50px !important;
    }

    .btn-lg {
        padding: 10px 28px !important;
        font-size: 16px !important;
    }

    .btn-sm {
        padding: 4px 12px !important;
        font-size: 12px !important;
    }

    .btn-primary.btn-round {
        padding: 8px 24px !important;
    }
    /* ===== END TOMBOL NATURAL ===== */
</style>

<div class="main-panel">
    <?php require '../templates/navbar.php'; ?>
    <div class="container">
        <div class="page-inner">

            <div class="d-flex align-items-center justify-content-between pt-2 pb-4">
                <div>
                    <h3 class="fw-bold mb-3">Edit Pengaturan</h3>
                    <h6 class="op-7 mb-2">Ubah semua konten landing page</h6>
                </div>
                <div><a href="index.php" class="btn btn-secondary btn-round"><i class="fas fa-arrow-left me-1"></i> Kembali</a></div>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <strong>Gagal!</strong> Terjadi kesalahan.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- ========================================================== -->
            <!-- FORM UTAMA: PROFIL, BANNER, KANDANG, TESTIMONI, PENGATURAN -->
            <!-- ========================================================== -->
            <form method="POST" action="proses.php" enctype="multipart/form-data">

                <!-- ========================================================== -->
                <!-- SECTION 1: PROFIL TOKO -->
                <!-- ========================================================== -->
                <div class="card card-round mb-4">
                    <div class="card-header"><h4 class="card-title"><i class="fas fa-store me-2 text-primary"></i>Profil Toko</h4></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Foto Toko</label>
                                    <?php if (!empty($umum['foto_toko'])): ?>
                                        <div class="mb-2"><img src="../uploads/profil/<?= htmlspecialchars($umum['foto_toko']) ?>" style="max-height:120px; border:1px solid #ddd; padding:4px; border-radius:4px;" id="foto_toko_lama"></div>
                                    <?php endif; ?>
                                    <input type="file" name="foto_toko" class="form-control" accept="image/*" onchange="previewFotoToko(this)">
                                    <input type="hidden" name="foto_lama" value="<?= htmlspecialchars($umum['foto_toko'] ?? '') ?>">
                                    <small class="text-muted">Format: JPG, PNG, WEBP. Maks. 2MB.</small>
                                    <img id="preview_foto_toko" class="preview-img" style="display:none;">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6"><div class="form-group"><label>Nama Toko *</label><input type="text" name="nama_toko" class="form-control form-control-lg" value="<?= htmlspecialchars($umum['nama_toko'] ?? '') ?>" required></div></div>
                                    <div class="col-md-6"><div class="form-group"><label>Telepon *</label><input type="text" name="telepon" class="form-control form-control-lg" value="<?= htmlspecialchars($umum['telepon'] ?? '') ?>" required></div></div>
                                    <div class="col-md-6"><div class="form-group"><label>Email</label><input type="email" name="email" class="form-control form-control-lg" value="<?= htmlspecialchars($umum['email'] ?? '') ?>"></div></div>
                                    <div class="col-md-6"><div class="form-group"><label>Alamat *</label><input type="text" name="alamat" class="form-control form-control-lg" value="<?= htmlspecialchars($umum['alamat'] ?? '') ?>" required></div></div>
                                    <div class="col-md-6"><div class="form-group"><label>Website</label><input type="url" name="website" class="form-control form-control-lg" value="<?= htmlspecialchars($umum['website'] ?? '') ?>" placeholder="https://"></div></div>
                                    <div class="col-md-6"><div class="form-group"><label>Jam Operasional</label><input type="text" name="jam_operasional" class="form-control form-control-lg" value="<?= htmlspecialchars($umum['jam_operasional'] ?? '') ?>" placeholder="Senin-Jumat: 07.00-20.00"></div></div>
                                </div>
                            </div>
                            <div class="col-12"><div class="form-group"><label>Deskripsi Toko</label><textarea name="deskripsi_toko" class="form-control form-control-lg" rows="8" style="height:200px;"><?= htmlspecialchars($umum['deskripsi_toko'] ?? '') ?></textarea></div></div>
                            <div class="col-12"><div class="form-group"><label>Sejarah Toko</label><textarea name="sejarah_toko" class="form-control form-control-lg" rows="8" style="height:200px;"><?= htmlspecialchars($umum['sejarah_toko'] ?? '') ?></textarea></div></div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================== -->
                <!-- SECTION 2: BANNER -->
                <!-- ========================================================== -->
                <div class="card card-round mb-4">
                    <div class="card-header"><h4 class="card-title"><i class="fas fa-bullhorn me-2 text-warning"></i>Kata Sambutan (Banner)</h4></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6"><div class="form-group"><label>Judul Utama</label><input type="text" name="judul_utama" class="form-control form-control-lg" value="<?= htmlspecialchars($banner['judul_utama'] ?? '') ?>"></div></div>
                            <div class="col-md-6"><div class="form-group"><label>Sub Judul</label><input type="text" name="sub_judul" class="form-control form-control-lg" value="<?= htmlspecialchars($banner['sub_judul'] ?? '') ?>"></div></div>
                            <div class="col-12"><div class="form-group"><label>Deskripsi Banner</label><textarea name="deskripsi_banner" class="form-control form-control-lg" rows="8" style="height:200px;"><?= htmlspecialchars($banner['deskripsi'] ?? '') ?></textarea></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Tombol Kiri (Teks)</label><input type="text" name="tombol_kiri_teks" class="form-control form-control-lg" value="<?= htmlspecialchars($banner['tombol_kiri_teks'] ?? '') ?>"></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Tombol Kiri (Link)</label><input type="text" name="tombol_kiri_link" class="form-control form-control-lg" value="<?= htmlspecialchars($banner['tombol_kiri_link'] ?? '') ?>"></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Tombol Kanan (Teks)</label><input type="text" name="tombol_kanan_teks" class="form-control form-control-lg" value="<?= htmlspecialchars($banner['tombol_kanan_teks'] ?? '') ?>"></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Tombol Kanan (Link)</label><input type="text" name="tombol_kanan_link" class="form-control form-control-lg" value="<?= htmlspecialchars($banner['tombol_kanan_link'] ?? '') ?>"></div></div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================== -->
                <!-- SECTION 3: KANDANG (AKTIF/NONAKTIF) -->
                <!-- ========================================================== -->
                <div class="card card-round mb-4">
                    <div class="card-header"><h4 class="card-title"><i class="fas fa-home me-2 text-success"></i>Ketersediaan Kandang</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm table-edit">
                                <thead class="table-dark"><tr><th>Kode</th><th>Nama</th><th>Tipe</th><th>Ukuran</th><th>Tarif</th><th class="text-center">Tampil</th></tr></thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($kandang)): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($row['kode_kandang']) ?></strong></td>
                                            <td><?= htmlspecialchars($row['nama_kandang']) ?></td>
                                            <td><?= htmlspecialchars($row['tipe_hewan']) ?></td>
                                            <td><?= htmlspecialchars($row['ukuran']) ?></td>
                                            <td>Rp <?= number_format($row['tarif_per_hari'], 0, ',', '.') ?></td>
                                            <td class="text-center">
                                                <div class="switch-container justify-content-center">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="kandang_active[<?= $row['id_kandang'] ?>]" value="1" id="kandang_<?= $row['id_kandang'] ?>" <?= $row['is_active'] ? 'checked' : '' ?> style="width:50px; height:26px;">
                                                        <label class="form-check-label" for="kandang_<?= $row['id_kandang'] ?>"><span class="badge <?= $row['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $row['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></label>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ========================================================== -->
                <!-- SECTION 4: TESTIMONI (AKTIF/NONAKTIF) -->
                <!-- ========================================================== -->
                <div class="card card-round mb-4">
                    <div class="card-header"><h4 class="card-title"><i class="fas fa-quote-right me-2 text-info"></i>Testimoni</h4></div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($testimoni) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm table-edit">
                                    <thead class="table-dark"><tr><th>Pelanggan</th><th>Rating</th><th>Komentar</th><th class="text-center">Tampil</th></tr></thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($testimoni)): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['id_user'] ?? 'Tidak Diketahui') ?></td>
                                                <td><?= str_repeat('★', $row['rating'] ?? 5) ?></td>
                                                <td><?= htmlspecialchars(substr($row['komentar'] ?? '', 0, 60)) ?>...</td>
                                                <td class="text-center">
                                                    <div class="switch-container justify-content-center">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="testimoni_active[<?= $row['id_testimoni'] ?>]" value="1" id="testimoni_<?= $row['id_testimoni'] ?>" <?= $row['is_active'] ? 'checked' : '' ?> style="width:50px; height:26px;">
                                                            <label class="form-check-label" for="testimoni_<?= $row['id_testimoni'] ?>"><span class="badge <?= $row['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $row['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></label>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">Belum ada testimoni.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ========================================================== -->
                <!-- SECTION 5: PENGATURAN LANDING PAGE (TANPA DROPDOWN JUMLAH) -->
                <!-- ========================================================== -->
               <!-- ========================================================== -->
<!-- SECTION 5: PENGATURAN LANDING PAGE -->
<!-- ========================================================== -->
<div class="card card-round mb-4">
    <div class="card-header"><h4 class="card-title"><i class="fas fa-sliders-h me-2 text-info"></i>Pengaturan Landing Page</h4></div>
    <div class="card-body">
       <div class="row">
    <div class="col-12">
        <div class="form-group">
            <label>Ticker Pengumuman</label>
            <textarea name="ticker_teks" class="form-control form-control-lg" rows="5" style="height:120px;" placeholder="Masukkan teks ticker..."><?= htmlspecialchars($umum['ticker_teks'] ?? '') ?></textarea>
            <small class="text-muted">Teks ini akan tampil berjalan di bagian atas landing page.</small>
        </div>
    </div>
    <div class="col-12">
        <div class="form-group">
            <label>Google Maps (iframe embed)</label>
            <textarea name="google_maps_embed" class="form-control form-control-lg" rows="8" style="height:200px;" placeholder="https://www.google.com/maps/embed?pb=..."><?= htmlspecialchars($umum['google_maps_embed'] ?? '') ?></textarea>
            <small class="text-muted">Salin URL dari atribut src="..." Google Maps.</small>
        </div>
    </div>
</div>
    </div>
</div>

                <!-- TOMBOL SIMPAN -->
                <div class="d-flex gap-2 mb-5">
                    <button type="submit" class="btn btn-primary btn-lg px-5"><i class="fas fa-save me-2"></i> Simpan Semua Pengaturan</button>
                    <a href="index.php" class="btn btn-secondary btn-lg px-4"><i class="fas fa-times me-2"></i> Batal</a>
                </div>

            </form>

            <!-- ========================================================== -->
            <!-- FORM KEUNGGULAN (TERPISAH) -->
            <!-- ========================================================== -->
            <div class="card card-round mb-4">
                <div class="card-header"><h4 class="card-title"><i class="fas fa-star me-2 text-warning"></i>Keunggulan Layanan</h4></div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($keunggulan) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm table-edit">
                                <thead class="table-dark"><tr><th style="width:60px;">Gambar</th><th>Judul</th><th>Deskripsi</th><th style="width:80px;">Urutan</th><th style="width:100px;">Status</th><th style="width:150px;">Aksi</th></tr></thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($keunggulan)): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($row['gambar'])): ?>
                                                    <img src="../uploads/keunggulan/<?= htmlspecialchars($row['gambar']) ?>" style="max-width:50px; max-height:50px; object-fit:cover; border-radius:4px;">
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['judul']) ?></td>
                                            <td><?= htmlspecialchars($row['deskripsi']) ?></td>
                                            <td><?= $row['urutan'] ?></td>
                                            <td><span class="badge <?= $row['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $row['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editKeunggulan<?= $row['id_keunggulan'] ?>">Edit</button>
                                                <form action="proses.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="aksi" value="hapus_keunggulan">
                                                    <input type="hidden" name="id_keunggulan" value="<?= $row['id_keunggulan'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <hr>
                    <h6 class="fw-bold">Tambah Keunggulan Baru</h6>
                    <form method="POST" action="proses.php" enctype="multipart/form-data">
                        <div class="row mt-3">
                            <div class="col-md-3"><input type="text" name="judul" class="form-control" placeholder="Judul" required></div>
                            <div class="col-md-3"><textarea name="deskripsi" class="form-control" placeholder="Deskripsi" rows="3"></textarea></div>
                            <div class="col-md-2"><input type="number" name="urutan" class="form-control" placeholder="Urutan" value="0"></div>
                            <div class="col-md-2">
                                <select name="is_active" class="form-control">
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="file" name="gambar" class="form-control" accept="image/*" onchange="previewKeunggulan(this, 'preview_tambah_keunggulan')">
                                <img id="preview_tambah_keunggulan" class="preview-thumb" style="display:none;">
                            </div>
                            <div class="col-12 mt-2"><button type="submit" name="aksi" value="tambah_keunggulan" class="btn btn-primary">Tambah Keunggulan</button></div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL EDIT KEUNGGULAN -->
            <?php
            mysqli_data_seek($keunggulan, 0);
            while ($row = mysqli_fetch_assoc($keunggulan)):
            ?>
                <div class="modal fade" id="editKeunggulan<?= $row['id_keunggulan'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg"><div class="modal-content">
                        <form action="proses.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="aksi" value="edit_keunggulan">
                            <input type="hidden" name="id_keunggulan" value="<?= $row['id_keunggulan'] ?>">
                            <div class="modal-header"><h5 class="modal-title">Edit Keunggulan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6"><div class="form-group"><label>Judul</label><input type="text" name="judul" class="form-control" value="<?= htmlspecialchars($row['judul']) ?>" required></div></div>
                                    <div class="col-md-6"><div class="form-group"><label>Deskripsi</label><textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($row['deskripsi']) ?></textarea></div></div>
                                    <div class="col-md-4"><div class="form-group"><label>Urutan</label><input type="number" name="urutan" class="form-control" value="<?= $row['urutan'] ?>"></div></div>
                                    <div class="col-md-4"><div class="form-group"><label>Status</label><select name="is_active" class="form-control"><option value="1" <?= $row['is_active'] ? 'selected' : '' ?>>Aktif</option><option value="0" <?= !$row['is_active'] ? 'selected' : '' ?>>Nonaktif</option></select></div></div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Gambar Baru</label>
                                            <input type="file" name="gambar" class="form-control" accept="image/*" onchange="previewKeunggulan(this, 'preview_edit_keunggulan_<?= $row['id_keunggulan'] ?>')">
                                            <?php if (!empty($row['gambar'])): ?>
                                                <img src="../uploads/keunggulan/<?= htmlspecialchars($row['gambar']) ?>" style="max-height:60px; margin-top:6px; border:1px solid #ddd; padding:4px; border-radius:4px;">
                                            <?php endif; ?>
                                            <img id="preview_edit_keunggulan_<?= $row['id_keunggulan'] ?>" class="preview-thumb" style="display:none;">
                                            <small class="text-muted">Kosongkan jika tidak diubah.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                        </form>
                    </div></div>
                </div>
            <?php endwhile; ?>

        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>

<script>
// PREVIEW FOTO TOKO
function previewFotoToko(input) {
    var preview = document.getElementById('preview_foto_toko');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}

// PREVIEW KEUNGGULAN
function previewKeunggulan(input, previewId) {
    var preview = document.getElementById(previewId);
    if (!preview) return;
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}
</script>
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

// Menangkap ID User dari session login
$id_user = $_SESSION['id_user'] ?? 1;

// Ambil role user yang login
$query_role = mysqli_query($koneksi, "SELECT role FROM users WHERE id_user = $id_user");
$data_role = mysqli_fetch_assoc($query_role);
$role_user = $data_role['role'] ?? 'Pelanggan';
$isAdmin = ($role_user == 'Super Admin' || $role_user == 'Admin');

// Menangkap angka dari URL (?id_penitipan=21)
$id_penitipan = filter_var($_GET['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);

if (!$id_penitipan) {
    echo "<script>alert('ID Booking tidak valid!'); window.location.href='../bookingan_saya/index.php';</script>";
    exit;
}

// Query untuk mengambil data booking berdasarkan id_penitipan
$query_p = mysqli_query($koneksi, "SELECT *, DATEDIFF(tgl_selesai, tgl_mulai) AS jumlah_hari FROM penitipan WHERE id_penitipan = $id_penitipan AND id_user = $id_user");
$penitipan = mysqli_fetch_assoc($query_p);

// Ambil detail per kandang (GROUP BY)
$query_detail = mysqli_query($koneksi, "
    SELECT 
        k.id_kandang,
        k.kode_kandang,
        k.nama_kandang,
        k.tarif_per_hari,
        MIN(ph.tgl_mulai) AS tgl_mulai,
        MAX(ph.tgl_selesai) AS tgl_selesai,
        COUNT(ph.id_hewan) AS jumlah_hewan,
        GROUP_CONCAT(h.nama_hewan SEPARATOR ', ') AS daftar_hewan,
        DATEDIFF(MAX(ph.tgl_selesai), MIN(ph.tgl_mulai)) AS durasi_hari
    FROM penitipan_hewan ph
    JOIN kandang k ON ph.id_kandang = k.id_kandang
    LEFT JOIN hewan h ON ph.id_hewan = h.id_hewan
    WHERE ph.id_penitipan = $id_penitipan
    GROUP BY k.id_kandang
    ORDER BY k.kode_kandang ASC
");
$detail_kandang = [];
while ($row = mysqli_fetch_assoc($query_detail)) {
    $detail_kandang[] = $row;
}

// Jika data tidak ditemukan
if (!$penitipan) {
    echo "<script>alert('Data booking tidak ditemukan atau Anda tidak memiliki akses!'); window.location.href='../bookingan_saya/index.php';</script>";
    exit;
}

// Ambil opsi metode pembayaran untuk dropdown select
if ($isAdmin) {
    // Admin & Super Admin: tampilkan semua metode (termasuk Tunai)
    $metode_list = mysqli_query($koneksi, "SELECT * FROM metode_pembayaran WHERE is_active = 1 ORDER BY urutan ASC");
} else {
    // Pelanggan: tampilkan SEMUA METODE KECUALI TUNAI
    $metode_list = mysqli_query($koneksi, "SELECT * FROM metode_pembayaran WHERE is_active = 1 AND tipe_metode != 'Tunai' ORDER BY urutan ASC");
}

$title = 'Pembayaran Booking';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-7 grid-margin stretch-card mx-auto">
                <div class="card card-rounded shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title text-primary mb-4">
                            <i class="mdi mdi-credit-card me-2"></i> Konfirmasi Pembayaran
                        </h4>
                        <p class="card-description text-muted mb-4">
                            <?php if ($isAdmin): ?>
                                <strong>Mode Admin:</strong> Pembayaran akan langsung dikonfirmasi tanpa perlu upload bukti transfer.
                            <?php else: ?>
                                Silakan lakukan transfer sesuai total biaya di bawah ini, kemudian unggah foto bukti transaksi Anda.
                            <?php endif; ?>
                        </p>

                        <form action="proses.php" method="POST" enctype="multipart/form-data" class="forms-sample">
                            <input type="hidden" name="id_penitipan" value="<?= $id_penitipan ?>">
                            <input type="hidden" name="is_admin" value="<?= $isAdmin ? '1' : '0' ?>">

                            <div class="form-group mb-3">
                                <label class="fw-bold text-dark">Kode Booking</label>
                                <input type="text" class="form-control fw-bold" value="<?= $penitipan['kode_booking'] ?>" readonly style="background-color: #f8f9fa;">
                            </div>

                            <div class="row mb-3">
                                <div class="form-group mb-3">
                                    <label class="fw-bold text-dark">Durasi Penitipan</label>
                                    <div class="bg-light p-3 rounded-3" style="border: 1px solid #dee2e6; background-color: #f8f9fa !important;">
                                        <?php if (count($detail_kandang) > 0): ?>
                                            <?php foreach ($detail_kandang as $dk): ?>
                                                <?php
                                                $durasi = ($dk['durasi_hari'] <= 0) ? 1 : $dk['durasi_hari'];
                                                $tgl_mulai = date('d M Y', strtotime($dk['tgl_mulai']));
                                                $tgl_selesai = date('d M Y', strtotime($dk['tgl_selesai']));
                                                ?>
                                                <div class="d-flex justify-content-between py-1 <?= ($dk !== end($detail_kandang)) ? 'border-bottom' : ''; ?>" style="border-color: #dee2e6;">
                                                    <span><strong><?= htmlspecialchars($dk['kode_kandang']) ?></strong> - <?= htmlspecialchars($dk['nama_kandang']) ?></span>
                                                    <span><?= $durasi ?> hari (<?= $tgl_mulai ?> - <?= $tgl_selesai ?>)</span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Belum ada data kandang</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="fw-bold text-dark">Total Tagihan</label>
                                    <input type="text" class="form-control fw-bold text-danger" value="Rp <?= number_format($penitipan['total_biaya'], 0, ',', '.') ?>" readonly style="background-color: #f8f9fa;">
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="nama_pembayar" class="fw-bold text-dark">
                                    Nama Pembayar <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="nama_pembayar" id="nama_pembayar"
                                    class="form-control"
                                    placeholder="Masukkan nama lengkap pembayar"
                                    value="<?= htmlspecialchars($_SESSION['nama_lengkap'] ?? '') ?>"
                                    required>
                                <small class="text-muted"><i class="mdi mdi-information-outline"></i> Nama sesuai dengan rekening yang digunakan untuk transfer.</small>
                            </div>

                            <div class="form-group mb-3">
                                <label class="fw-bold text-dark">
                                    Jumlah Dibayar <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-bold">Rp</span>
                                    <?php if ($isAdmin): ?>
                                        <input type="text" name="jumlah_bayar" id="jumlah_bayar"
                                            class="form-control"
                                            placeholder="Masukkan Nominal Pembayaran"
                                            value="<?= number_format($penitipan['total_biaya'], 0, ',', '.') ?>"
                                            required
                                            oninput="formatRupiah(this)">
                                    <?php else: ?>
                                        <input type="text" name="jumlah_bayar" id="jumlah_bayar"
                                            class="form-control"
                                            placeholder="Masukkan Nominal Pembayaran"
                                            value="<?= number_format($penitipan['total_biaya'], 0, ',', '.') ?>"
                                            required
                                            readonly
                                            style="background-color: #f8f9fa;">
                                    <?php endif; ?>
                                </div>
                                <?php if (!$isAdmin): ?>
                                    <small class="text-muted"><i class="mdi mdi-information-outline"></i> Jumlah harus sama dengan total tagihan.</small>
                                <?php endif; ?>
                            </div>

                            <div class="form-group mb-3">
                                <label for="catatan" class="fw-bold text-dark">
                                    Catatan <span class="text-muted">(Opsional)</span>
                                </label>
                                <textarea name="catatan" id="catatan"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Tulis catatan tambahan untuk admin<?= $isAdmin ? ' (misal: pembayaran sudah diterima langsung)' : ' (misal: transfer dari rekening atas nama ...)' ?>"></textarea>
                                <small class="text-muted d-block mt-1">
                                    <i class="mdi mdi-information-outline"></i>
                                    <?= $isAdmin ? 'Catatan ini akan tercatat sebagai informasi tambahan.' : 'Catatan ini akan membantu admin dalam verifikasi pembayaran Anda.' ?>
                                </small>
                            </div>

                            <div class="form-group mb-3">
                                <label for="id_metode" class="fw-bold text-dark">Metode Pembayaran / Transfer Tujuan <span class="text-danger">*</span></label>
                                <select name="id_metode" id="id_metode" class="form-select form-control text-dark" required onchange="tampilInfo(this)">
                                    <option value="">-- Pilih Metode Pembayaran --</option>
                                    <?php foreach ($metode_list as $m): ?>
                                        <option value="<?= htmlspecialchars($m['id_metode']) ?>"
                                            data-info="<?= htmlspecialchars($m['keterangan'] ?? '') ?>"
                                            data-gambar="<?= htmlspecialchars($m['gambar_metode'] ?? '') ?>"
                                            data-tipe="<?= htmlspecialchars($m['tipe_metode'] ?? '') ?>">
                                            <?= htmlspecialchars($m['nama_metode']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="div_info" class="mt-2 p-2 border" style="display:none; background:#f8f9fa; font-size:0.85em;"></div>
                                <div id="div_gambar" class="mt-2 text-center" style="display:none;">
                                    <img id="img_metode" src="" alt="QRIS / Logo Bank" style="max-width: 200px; border: 1px solid #ddd; padding: 10px; border-radius: 8px;">
                                </div>
                            </div>

                            <!-- ========================================= -->
                            <!-- UPLOAD BUKTI - HANYA UNTUK PELANGGAN       -->
                            <!-- ========================================= -->
                            <?php if (!$isAdmin): ?>
                            <div class="form-group mb-4" id="div_upload_bukti">
                                <label for="bukti_pembayaran" class="fw-bold text-dark">Upload Bukti Transfer <span class="text-danger">*</span></label>
                                <input type="file" name="bukti_pembayaran" id="bukti_pembayaran" class="form-control" accept="image/*" required onchange="previewImage(event)">
                                <img id="preview_bukti" style="display:none; margin-top: 15px; max-width: 200px; border: 1px solid #ddd; padding: 5px;">
                                <small class="text-muted d-block mt-2">Format file wajib gambar: <strong>JPG, JPEG, atau PNG</strong> (Maksimal 2MB).</small>
                            </div>
                            <?php else: ?>
                            <!-- Untuk Admin: hidden input bukti (tidak perlu upload) -->
                            <input type="hidden" name="bukti_pembayaran" value="">
                            <div class="alert alert-info mb-4">
                                <i class="mdi mdi-information-outline me-2"></i>
                                <strong>Mode Admin:</strong> Anda tidak perlu upload bukti transfer. Pembayaran akan langsung dikonfirmasi.
                            </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between pt-3 border-top">
                                <a href="../bookingan_saya/index.php" class="btn btn-light btn-sm py-2 px-4">
                                    Batal
                                </a>
                                <button type="submit" class="btn btn-primary text-white btn-sm py-2 px-4">
                                    <?= $isAdmin ? 'Konfirmasi Pembayaran' : 'Kirim Bukti Bayar' ?>
                                </button>
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
    function tampilInfo(sel) {
        const divInfo = document.getElementById('div_info');
        const divGambar = document.getElementById('div_gambar');
        const imgMetode = document.getElementById('img_metode');
        const divUpload = document.getElementById('div_upload_bukti');
        const inputFile = document.getElementById('bukti_pembayaran');
        const preview = document.getElementById('preview_bukti');

        const selectedOption = sel.options[sel.selectedIndex];
        const info = selectedOption.getAttribute('data-info');
        const gambar = selectedOption.getAttribute('data-gambar');
        const tipe = selectedOption.getAttribute('data-tipe');

        if (sel.value !== "") {
            divInfo.style.display = "block";
            divInfo.innerHTML = "<strong>Detail Tujuan:</strong> " + info;

            if (gambar && tipe === 'QRIS') {
                divGambar.style.display = "block";
                imgMetode.src = "../uploads/metode/" + gambar;
            } else {
                divGambar.style.display = "none";
                imgMetode.src = "";
            }

            // Cek apakah divUpload ada (hanya untuk pelanggan)
            if (divUpload) {
                if (tipe === 'Tunai') {
                    divUpload.style.display = "none";
                    if (inputFile) {
                        inputFile.removeAttribute('required');
                    }
                    if (preview) {
                        preview.style.display = "none";
                        preview.src = "";
                    }
                } else {
                    divUpload.style.display = "block";
                    if (inputFile) {
                        inputFile.setAttribute('required', 'required');
                    }
                }
            }
        } else {
            divInfo.style.display = "none";
            divGambar.style.display = "none";
            imgMetode.src = "";
            if (divUpload) {
                divUpload.style.display = "block";
                if (inputFile) {
                    inputFile.setAttribute('required', 'required');
                }
            }
        }
    }

    function previewImage(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('preview_bukti');
            if (output) {
                output.src = reader.result;
                output.style.display = "block";
            }
        }
        if (event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        }
    }

    function formatRupiah(input) {
        let value = input.value.replace(/[^0-9]/g, '');
        if (value === '') {
            input.value = '';
            return;
        }
        let formatted = new Intl.NumberFormat('id-ID').format(parseInt(value));
        input.value = formatted;
    }
</script>
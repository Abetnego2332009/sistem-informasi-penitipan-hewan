<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

$id_penitipan = filter_var($_GET['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);

if (!$id_penitipan) {
    echo "<script>alert('ID Booking tidak valid!'); window.location.href='../ketersediaan_kandang/index.php';</script>";
    exit;
}

$query_p = mysqli_query($koneksi, "SELECT * FROM penitipan WHERE id_penitipan = $id_penitipan AND status_penitipan = 'Pending'");
$penitipan = mysqli_fetch_assoc($query_p);

if (!$penitipan) {
    echo "<script>alert('Data booking tidak ditemukan atau sudah diproses!'); window.location.href='../ketersediaan_kandang/index.php';</script>";
    exit;
}

// MEMBACA SESSION ROLE SESUAI DENGAN LOGIN.PHP LU
$role_login = $_SESSION['role'] ?? 'Pelanggan';
$id_user_booking = $penitipan['id_user'];

// Cek apakah dia manajemen toko (Super Admin atau Admin)
$isAdmin = ($role_login == 'Super Admin' || $role_login == 'Admin');

$user_profile = null;
$hewan_lama = [];

// Jika yang login Pelanggan, tampilkan profilnya saja
if (!$isAdmin) {
    $query_u = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = $id_user_booking");
    $user_profile = mysqli_fetch_assoc($query_u);

    // Ambil hewan lama milik akun pelanggan ini
    $query_h = mysqli_query($koneksi, "SELECT * FROM hewan WHERE id_user = $id_user_booking");
    while ($h = mysqli_fetch_assoc($query_h)) {
        $hewan_lama[$h['id_hewan']] = $h;
    }
}

// Ambil list kandang yang dipesan
$query_ph = "SELECT ph.id_penitipan_hewan, k.* FROM penitipan_hewan ph
             JOIN kandang k ON ph.id_kandang = k.id_kandang
             WHERE ph.id_penitipan = $id_penitipan";
$res_ph = mysqli_query($koneksi, $query_ph);

$list_kandang = [];
while ($row = mysqli_fetch_assoc($res_ph)) {
    $list_kandang[] = $row;
}

$title = 'Form Data Penitipan';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card card-rounded">
                    <div class="card-body">
                        <h4 class="card-title">Form Pengisian Data Transaksi & Hewan</h4>
                        <p class="card-description text-muted">Kode Booking: <strong><?= $penitipan['kode_booking'] ?></strong></p>

                        <form class="forms-sample" method="POST" action="proses.php">
                            <input type="hidden" name="id_penitipan" value="<?= $id_penitipan ?>">
                            <input type="hidden" name="id_pelanggan_hidden" id="id_pelanggan_hidden" value="">


                            <div class="card bg-light p-4 mb-4 border-0 card-rounded text-dark">
                                <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">Informasi Pemilik / Pelanggan</h5>

                                <?php if ($isAdmin): ?>
                                    <div class="row">
                                        <div class="col-md-4 form-group mb-3">
                                            <label class="fw-bold">Nomor Telepon <span class="text-danger">*</span></label>
                                            <input type="text" name="no_telp_pelanggan" id="no_telp_pelanggan" class="form-control bg-white text-dark fw-bold" placeholder="Ketik nomor telepon lalu klik luar..." onblur="cariPelangganByPhone()" required>
                                            <small class="text-muted text-primary">Sistem akan otomatis mencari riwayat pelanggan lama.</small>
                                        </div>
                                        <div class="col-md-4 form-group mb-3">
                                            <label class="fw-bold">Nama Pelanggan <span class="text-danger">*</span></label>
                                            <input type="text" name="nama_pelanggan" id="nama_pelanggan" class="form-control bg-white" placeholder="Masukkan nama pelanggan" required>
                                        </div>
                                        <div class="col-md-4 form-group mb-3">
                                            <label class="fw-bold">Alamat Pelanggan <span class="text-danger">*</span></label>
                                            <input type="text" name="alamat_pelanggan" id="alamat_pelanggan" class="form-control bg-white" placeholder="Alamat lengkap" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 form-group mb-1">
                                            <label class="fw-bold">Catatan Kecil</label>
                                            <textarea name="catatan_opsional" class="form-control bg-white" rows="2" placeholder="Catatan opsional untuk penitipan ini..."></textarea>
                                        </div>
                                    </div>
                                <?php else: ?>

                                    <div class="row">
                                        <div class="col-md-4 form-group mb-2">
                                            <label class="text-muted small">Nama Pemilik</label>
                                            <input type="text" class="form-control text-dark fw-bold" value="<?= htmlspecialchars($user_profile['nama_lengkap'] ?? '-') ?>" readonly style="background-color: #e9ecef; pointer-events: none;">
                                        </div>
                                        <div class="col-md-4 form-group mb-2">
                                            <label class="text-muted small">Nomor Telepon</label>
                                            <input type="text" class="form-control text-dark" value="<?= htmlspecialchars($user_profile['no_telp'] ?? '-') ?>" readonly style="background-color: #e9ecef; pointer-events: none;">
                                        </div>
                                        <div class="col-md-4 form-group mb-2">
                                            <label class="text-muted small">Alamat Tinggal</label>
                                            <input type="text" class="form-control text-dark" value="<?= htmlspecialchars($user_profile['alamat'] ?? '-') ?>" readonly style="background-color: #e9ecef; pointer-events: none;">
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>





                            <?php foreach ($list_kandang as $index => $kd): ?>
                                <div class="mb-5 kandang-row" data-tarif="<?= $kd['tarif_per_hari'] ?>">
                                    <h5 class="text-dark fw-bold mb-3 border-bottom pb-2">
                                        Unit <?= $index + 1 ?>: <?= htmlspecialchars($kd['nama_kandang']) ?>
                                        <span class="badge bg-info text-white"><?= htmlspecialchars($kd['tipe_hewan']) ?></span>
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-6 form-group mb-3">
                                            <label class="fw-bold">Tanggal Mulai</label>
                                            <input type="date" name="tgl_mulai[<?= $kd['id_penitipan_hewan'] ?>]"
                                                class="form-control text-dark input-tgl-mulai"
                                                value="<?= $penitipan['tgl_mulai'] ?>">
                                        </div>
                                        <div class="col-md-6 form-group mb-3">
                                            <label class="fw-bold">Tanggal Selesai</label>
                                            <input type="date" name="tgl_selesai[<?= $kd['id_penitipan_hewan'] ?>]"
                                                class="form-control text-dark input-tgl-selesai"
                                                value="<?= $penitipan['tgl_selesai'] ?>">
                                        </div>
                                    </div>

                                    <?php for ($slot = 1; $slot <= $kd['kapasitas']; $slot++): ?>
                                        <div class="slot-item mb-4 border-bottom pb-3">
                                            <p class="fw-bold text-secondary mb-2">Slot Hewan Ke-<?= $slot ?></p>

                                            <div class="row">
                                                <div class="col-md-6 form-group mb-3">
                                                    <label>Nama Hewan</label>
                                                    <div class="container-nama-hewan">
                                                        <select name="hewan[<?= $kd['id_penitipan_hewan'] ?>][<?= $slot ?>][id_hewan]" class="form-control select-hewan" data-tipe="<?= $kd['tipe_hewan'] ?>" onchange="handleHewanChange(this, '<?= $kd['id_penitipan_hewan'] ?>', '<?= $slot ?>')" required>
                                                            <option value="">-- Pilih <?= htmlspecialchars($kd['tipe_hewan']) ?> --</option>
                                                            <option value="BARU">+ Tambah Hewan Baru</option>
                                                            <?php
                                                            foreach ($hewan_lama as $id_h => $hl):
                                                                if ($hl['jenis_hewan'] === $kd['tipe_hewan']):
                                                            ?>
                                                                    <option value="<?= $id_h ?>"><?= htmlspecialchars($hl['nama_hewan']) ?></option>
                                                            <?php
                                                                endif;
                                                            endforeach;
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-6 form-group mb-3">
                                                    <label>Jenis Hewan</label>
                                                    <select name="hewan[<?= $kd['id_penitipan_hewan'] ?>][<?= $slot ?>][jenis_hewan]" class="form-control input-jenis" readonly style="pointer-events: none; background-color: #e9ecef;">
                                                        <option value="Anjing" <?= $kd['tipe_hewan'] == 'Anjing' ? 'selected' : '' ?>>Anjing</option>
                                                        <option value="Kucing" <?= $kd['tipe_hewan'] == 'Kucing' ? 'selected' : '' ?>>Kucing</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 form-group mb-3">
                                                    <label>Ras / Varietas</label>
                                                    <input type="text" name="hewan[<?= $kd['id_penitipan_hewan'] ?>][<?= $slot ?>][ras]" class="form-control input-ras" placeholder="Contoh: Persia, Anggora">
                                                </div>
                                                <div class="col-md-6 form-group mb-3">
                                                    <label>Jenis Kelamin</label>
                                                    <select name="hewan[<?= $kd['id_penitipan_hewan'] ?>][<?= $slot ?>][jenis_kelamin]" class="form-control input-gender">
                                                        <option value="Jantan">Jantan</option>
                                                        <option value="Betina">Betina</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-4 form-group mb-3">
                                                    <label>Usia (Tahun)</label>
                                                    <input type="number" name="hewan[<?= $kd['id_penitipan_hewan'] ?>][<?= $slot ?>][usia_tahun]" class="form-control input-tahun" min="0" value="0">
                                                </div>
                                                <div class="col-md-4 form-group mb-3">
                                                    <label>Usia (Bulan)</label>
                                                    <input type="number" name="hewan[<?= $kd['id_penitipan_hewan'] ?>][<?= $slot ?>][usia_bulan]" class="form-control input-bulan" min="0" max="11" value="0">
                                                </div>
                                                <div class="col-md-4 form-group mb-3">
                                                    <label>Berat Badan (kg)</label>
                                                    <input type="number" step="0.01" name="hewan[<?= $kd['id_penitipan_hewan'] ?>][<?= $slot ?>][berat_badan]" class="form-control input-berat" placeholder="0.0">
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 form-group mb-3">
                                                    <label>Warna Bulu</label>
                                                    <input type="text" name="hewan[<?= $kd['id_penitipan_hewan'] ?>][<?= $slot ?>][warna_bulu]" class="form-control input-warna" placeholder="Warna bulu">
                                                </div>
                                                <div class="col-md-6 form-group mb-3">
                                                    <label>Status Vaksin</label>
                                                    <select name="hewan[<?= $kd['id_penitipan_hewan'] ?>][<?= $slot ?>][status_vaksin]" class="form-control input-vaksin">
                                                        <option value="Tidak Diketahui">Tidak Diketahui</option>
                                                        <option value="Sudah Divaksin">Sudah Divaksin</option>
                                                        <option value="Belum Divaksin">Belum Divaksin</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label>Karakteristik Hewan</label>
                                                <textarea name="hewan[<?= $kd['id_penitipan_hewan'] ?>][<?= $slot ?>][karakteristik]" class="form-control input-karakteristik" rows="2" placeholder="Tulis ciri fisik mencolok, sifat dominan hewan..."></textarea>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label>Riwayat Kesehatan & Alergi</label>
                                                <textarea name="hewan[<?= $kd['id_penitipan_hewan'] ?>][<?= $slot ?>][riwayat_kesehatan]" class="form-control input-riwayat" rows="3" placeholder="Tulis riwayat penyakit terdahulu atau alergi makanan jika ada..."></textarea>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            <?php endforeach; ?>
                            <!-- Box Ringkasan Biaya Otomatis -->
                            <!-- Box Ringkasan Biaya Otomatis -->
                            <div class="card bg-light p-4 mb-4 border-0 card-rounded text-dark shadow-sm">
                                <h5 class="fw-bold mb-3 text-dark">Ringkasan Biaya Penitipan</h5>
                                <div id="detail_biaya" class="mb-2">
                                    <!-- Akan diisi oleh JavaScript -->
                                </div>
                                <div class="d-flex justify-content-between align-items-center border-top pt-2">
                                    <span class="fw-bold">Total Biaya</span>
                                    <h4 class="fw-bold text-primary mb-0" id="display_total_harga">Rp 0</h4>
                                </div>
                            </div>
                            <div class="mt-4 pt-2 border-top d-flex justify-content-between align-items-center">
                                <span class="text-danger small">* Pastikan semua data terisi demi kelancaran check-in.</span>
                                <div>
                                    <a href="../bookingan_saya/" class="btn btn-light px-4 me-2">Kembali</a>
                                    <button type="submit" class="btn btn-primary text-white px-4">Lanjut Bayar</button>
                                </div>
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
    let dataHewanLama = <?= json_encode($hewan_lama) ?>;

    function cariPelangganByPhone() {
        const noTelp = document.getElementById('no_telp_pelanggan').value.trim();
        if (noTelp === '') return;

        fetch(`get_pelanggan.php?no_telp=${noTelp}`)
            .then(response => response.json())
            .then(data => {
                const inputNama = document.getElementById('nama_pelanggan');
                const inputAlamat = document.getElementById('alamat_pelanggan');
                const hiddenId = document.getElementById('id_pelanggan_hidden');

                if (data.status === 'lama') {
                    // FIX 1: Ganti nama_pelanggan menjadi nama_lengkap sesuai struktur DB lu
                    inputNama.value = data.pelanggan.nama_lengkap;
                    inputAlamat.value = data.pelanggan.alamat;
                    hiddenId.value = data.pelanggan.id_pelanggan;

                    // FIX 2: HAPUS alert() bawaan sebelumnya biar gak nge-looping!
                    console.log(`Pelanggan terdaftar ditemukan: ${data.pelanggan.nama_lengkap}`);

                    dataHewanLama = {};
                    data.hewan.forEach(h => {
                        dataHewanLama[h.id_hewan] = h;
                    });
                } else {
                    hiddenId.value = "";
                    inputNama.value = "";
                    inputAlamat.value = "";
                    dataHewanLama = {};
                    console.log("Pelanggan baru / tidak ditemukan.");
                }

                // Update dropdown opsi hewan
                document.querySelectorAll('.select-hewan').forEach(select => {
                    const tipeKandang = select.getAttribute('data-tipe');
                    const idPH = select.name.split(']')[0].split('[')[1];
                    const slot = select.name.split(']')[1].split('[')[1];

                    let optionsHtml = `
                    <option value="">-- Pilih ${tipeKandang} --</option>
                    <option value="BARU">+ Tambah Hewan Baru</option>
                `;
                    for (const id in dataHewanLama) {
                        if (dataHewanLama[id].jenis_hewan === tipeKandang) {
                            optionsHtml += `<option value="${id}">${dataHewanLama[id].nama_hewan}</option>`;
                        }
                    }
                    select.innerHTML = optionsHtml;
                    select.value = '';
                    handleHewanChange(select, idPH, slot);
                });
            })
            .catch(err => console.error("Error fetch data:", err));
    }

    function handleHewanChange(selectElement, idPH, slot) {
        const parent = selectElement.closest('.slot-item');
        const val = selectElement.value;

        const inputJenis = parent.querySelector('.input-jenis');
        const inputRas = parent.querySelector('.input-ras');
        const inputGender = parent.querySelector('.input-gender');
        const inputTahun = parent.querySelector('.input-tahun');
        const inputBulan = parent.querySelector('.input-bulan');
        const inputBerat = parent.querySelector('.input-berat');
        const inputWarna = parent.querySelector('.input-warna');
        const inputVaksin = parent.querySelector('.input-vaksin');
        const inputKarakteristik = parent.querySelector('.input-karakteristik');
        const inputRiwayat = parent.querySelector('.input-riwayat');

        if (val === 'BARU') {
            const tipeKandangSaatIni = inputJenis.value;
            const containerNama = parent.querySelector('.container-nama-hewan');
            containerNama.innerHTML = `
            <div class="input-group">
                <input type="text" name="hewan[${idPH}][${slot}][nama_hewan_baru]" class="form-control" placeholder="Ketik nama hewan..." required>
                <button type="button" class="btn btn-secondary btn-sm" onclick="resetKeDropdown(this, '${idPH}', '${slot}', '${tipeKandangSaatIni}')">Batal</button>
            </div>
            <input type="hidden" name="hewan[${idPH}][${slot}][id_hewan]" value="BARU">
        `;

            inputRas.value = '';
            inputGender.value = 'Jantan';
            inputTahun.value = '0';
            inputBulan.value = '0';
            inputBerat.value = '';
            inputWarna.value = '';
            inputVaksin.value = 'Tidak Diketahui';
            inputKarakteristik.value = '';
            inputRiwayat.value = '';

        } else if (val !== '') {
            const dataHewan = dataHewanLama[val];
            if (dataHewan) {
                inputRas.value = dataHewan.ras || '';
                inputGender.value = dataHewan.jenis_kelamin || 'Jantan';
                inputTahun.value = dataHewan.usia_tahun || dataHewan.umur_tahun || '0';
                inputBulan.value = dataHewan.usia_bulan || dataHewan.umur_bulan || '0';
                inputBerat.value = dataHewan.berat_badan || '';
                inputWarna.value = dataHewan.warna_bulu || '';
                inputVaksin.value = dataHewan.status_vaksin || 'Tidak Diketahui';
                inputKarakteristik.value = dataHewan.karakteristik || dataHewan.karakteristik_hewan || '';
                inputRiwayat.value = dataHewan.riwayat_kesehatan || '';
            }
        } else {
            inputRas.value = '';
            inputTahun.value = '0';
            inputBulan.value = '0';
            inputBerat.value = '';
            inputWarna.value = '';
            inputVaksin.value = 'Tidak Diketahui';
            inputKarakteristik.value = '';
            inputRiwayat.value = '';
        }
    }

    function resetKeDropdown(btnElement, idPH, slot, tipeKandang) {
        const containerNama = btnElement.closest('.container-nama-hewan');
        let optionsHtml = `
        <option value="">-- Pilih ${tipeKandang} --</option>
        <option value="BARU">+ Tambah Hewan Baru</option>
    `;
        for (const id in dataHewanLama) {
            if (dataHewanLama[id].jenis_hewan === tipeKandang) {
                optionsHtml += `<option value="${id}">${dataHewanLama[id].nama_hewan}</option>`;
            }
        }
        containerNama.innerHTML = `
        <select name="hewan[${idPH}][${slot}][id_hewan]" class="form-control select-hewan" data-tipe="${tipeKandang}" onchange="handleHewanChange(this, '${idPH}', '${slot}')" required>
            ${optionsHtml}
        </select>
    `;
        const select = containerNama.querySelector('.select-hewan');
        select.value = '';
        handleHewanChange(select, idPH, slot);
    }


   function hitungTotalBiaya() {
    let totalBiaya = 0;
    let detailHtml = '';

    document.querySelectorAll('.kandang-row').forEach((row, index) => {
        const tarif = parseFloat(row.getAttribute('data-tarif')) || 0;
        const mulai = row.querySelector('.input-tgl-mulai');
        const selesai = row.querySelector('.input-tgl-selesai');
        if (!mulai || !selesai || !mulai.value || !selesai.value) {
            detailHtml += `<div class="text-muted small">Kandang ${index+1}: tanggal belum lengkap</div>`;
            return;
        }

        const date1 = new Date(mulai.value);
        const date2 = new Date(selesai.value);
        let diffDays = Math.ceil((date2.getTime() - date1.getTime()) / (1000 * 3600 * 24));
        if (diffDays <= 0) diffDays = 1;

        const subtotal = tarif * diffDays;
        totalBiaya += subtotal;

        // Ambil nama kandang dari elemen h5 (misal "Unit 1: Kandang Anjing Kecil 1")
        const namaKandang = row.querySelector('h5')?.textContent?.trim() || `Kandang ${index+1}`;

        detailHtml += `
            <div class="d-flex justify-content-between small mb-1">
                <span>${namaKandang}</span>
                <span>${diffDays} hari × Rp ${new Intl.NumberFormat('id-ID').format(tarif)} = <strong>Rp ${new Intl.NumberFormat('id-ID').format(subtotal)}</strong></span>
            </div>
        `;
    });

    document.getElementById('detail_biaya').innerHTML = detailHtml;
    document.getElementById('display_total_harga').innerText = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(totalBiaya);
}

    // Jalankan tracker pendeteksi perubahan tanggal saat halaman di-load
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Set min tanggal mulai = hari ini
        setMinTanggalMulai();
        // 2. Set min tanggal selesai berdasarkan tanggal mulai awal
        updateMinTanggalSelesai();

        // 3. Event listener untuk semua input tanggal
        document.querySelectorAll('.input-tgl-mulai, .input-tgl-selesai').forEach(input => {
            input.addEventListener('change', function(e) {
                if (e.target.classList.contains('input-tgl-mulai')) {
                    const row = e.target.closest('.kandang-row');
                    const selesai = row.querySelector('.input-tgl-selesai');
                    if (selesai) selesai.setAttribute('min', e.target.value);
                }
                hitungTotalBiaya();
            });
        });
        // 4. Hitung total pertama kali
        hitungTotalBiaya();
    });

    function setMinTanggalMulai() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        const todayStr = `${year}-${month}-${day}`;
        document.querySelectorAll('.input-tgl-mulai').forEach(el => {
            el.setAttribute('min', todayStr);
        });
    }

    function updateMinTanggalSelesai() {
        const inputMulai = document.querySelector('.input-tgl-mulai');
        if (!inputMulai) return;
        const valMulai = inputMulai.value;
        document.querySelectorAll('.input-tgl-selesai').forEach(el => {
            if (valMulai) el.setAttribute('min', valMulai);
            else el.removeAttribute('min');
        });
    }
</script>
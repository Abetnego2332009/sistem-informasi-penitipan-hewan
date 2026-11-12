<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$id_user = $_SESSION['id_user'];
$role = $_SESSION['role'] ?? 'Pelanggan';
$isAdmin = ($role == 'Super Admin' || $role == 'Admin');

$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';

// ============================================================
// QUERY - Ambil data per kandang
// ============================================================
$query = "
    SELECT 
        p.id_penitipan,
        p.kode_booking,
        u.nama_lengkap AS nama_pemilik,
        u.no_telp,
        ph.tgl_mulai,
        ph.tgl_selesai,
         ph.tgl_diambil,
        k.id_kandang,
        k.kode_kandang,
        k.nama_kandang,
        k.tarif_per_hari,
        COUNT(ph.id_hewan) AS jumlah_hewan,
        GROUP_CONCAT(h.nama_hewan SEPARATOR ', ') AS daftar_hewan,
        GROUP_CONCAT(h.id_hewan SEPARATOR ', ') AS daftar_id_hewan,
        CASE 
             WHEN ph.tgl_diambil IS NOT NULL THEN 'Diambil'
        WHEN ph.tgl_selesai < CURDATE() THEN 'Berakhir'
        WHEN ph.tgl_selesai = CURDATE() THEN 'Berakhir Hari Ini'
        ELSE 'Aktif'
        END AS status_kandang
    FROM penitipan p
    INNER JOIN users u ON p.id_user = u.id_user
    INNER JOIN penitipan_hewan ph ON p.id_penitipan = ph.id_penitipan
    INNER JOIN kandang k ON ph.id_kandang = k.id_kandang
    INNER JOIN hewan h ON ph.id_hewan = h.id_hewan
    WHERE p.status_penitipan = 'Sedang Dititipkan'
      AND ph.tgl_mulai IS NOT NULL 
      AND ph.tgl_selesai IS NOT NULL
";

// ============================================================
// FILTER UNTUK USER: Tampilkan SEMUA kandang milik user
// (termasuk yang sudah berakhir, karena user bisa lupa mengambil)
// ============================================================
if (!$isAdmin) {
    $query .= " AND p.id_user = $id_user";
}

// Filter search untuk admin
if ($isAdmin && $search) {
    $query .= " AND (
        u.nama_lengkap LIKE '%$search%' 
        OR u.no_telp LIKE '%$search%' 
        OR p.kode_booking LIKE '%$search%' 
        OR h.nama_hewan LIKE '%$search%'
    )";
}
$query .= " GROUP BY p.id_penitipan, k.id_kandang ORDER BY p.kode_booking ASC, k.kode_kandang ASC";
$result = mysqli_query($koneksi, $query);

$title = 'Masa Aktif Booking';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';

// Ambil metode pembayaran (User gak boleh liat Tunai)
if ($isAdmin) {
    $metode_list = mysqli_query($koneksi, "SELECT * FROM metode_pembayaran WHERE is_active = 1 ORDER BY urutan ASC");
} else {
    $metode_list = mysqli_query($koneksi, "SELECT * FROM metode_pembayaran WHERE is_active = 1 AND tipe_metode != 'Tunai' ORDER BY urutan ASC");
}
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title"><i class="mdi mdi-clock me-2"></i> Masa Aktif Booking</h4>
                            <?php if ($isAdmin): ?>
                                <form method="GET" class="d-flex gap-2">
                                    <input type="text" name="search" class="form-control form-control-sm"
                                        placeholder="Cari nama/HP/kode/hewan..."
                                        value="<?= htmlspecialchars($search) ?>" style="width: 250px;">
                                    <button type="submit" class="btn btn-sm btn-primary"><i class="mdi mdi-magnify"></i> Cari</button>
                                    <?php if ($search): ?>
                                        <a href="index.php" class="btn btn-sm btn-light">Reset</a>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                        </div>

                        <?php if (mysqli_num_rows($result) == 0): ?>
                            <div class="text-center py-5">
                                <i class="mdi mdi-inbox" style="font-size: 48px; color: #ccc;"></i>
                                <p class="text-muted mt-3">Tidak ada data booking.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Kode Booking</th>
                                            <?php if ($isAdmin): ?>
                                                <th>Pemilik</th>
                                                <th>No HP</th>
                                            <?php endif; ?>
                                            <th>Kandang</th>
                                            <th>Hewan</th>
                                            <th>Tanggal Mulai</th>
                                            <th>Tanggal Selesai</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($result)):
                                            $tgl_selesai = strtotime($row['tgl_selesai']);
                                            $tgl_sekarang = strtotime(date('Y-m-d'));
                                            $sisa_hari = ceil(($tgl_selesai - $tgl_sekarang) / 86400);

                                            // ============================================================
                                            // LOGIKA STATUS BADGE
                                            // ============================================================
                                            $status_kandang = $row['status_kandang'];

                                            // Cek apakah kandang sudah diambil (untuk admin)
                                            // Asumsi: jika status_penitipan = 'Selesai' atau tgl_selesai < hari ini
                                            // ============================================================
                                            // PERBAIKAN: Kandang dianggap diambil jika tgl_selesai <= hari ini
                                            // ============================================================
                                            // ============================================================
                                            // PERBAIKAN: Kandang dianggap diambil jika tgl_diambil terisi
                                            // ============================================================
                                            $is_diambil = ($row['tgl_diambil'] !== null);

                                            // ============================================================
                                            // PERBAIKAN LOGIKA BADGE
                                            // ============================================================
                                            if ($is_diambil) {
                                                // Kandang sudah diambil (baik Admin maupun User)
                                                $badge_class = 'bg-secondary';
                                                $status_text = '✅ Sudah Diambil';
                                                $show_perpanjang = false;
                                                $show_ambil = false;
                                            } elseif ($row['status_kandang'] == 'Berakhir' && !$isAdmin) {
                                                // User: Masa aktif sudah berakhir (belum diambil)
                                                $badge_class = 'bg-secondary';
                                                $status_text = '⏰ Berakhir';
                                                $show_perpanjang = false;
                                                $show_ambil = false;
                                            } elseif ($row['status_kandang'] == 'Berakhir Hari Ini') {
                                                // Seharusnya tidak masuk sini karena sudah tertangkap $is_diambil
                                                // Tapi kita handle untuk jaga-jaga
                                                $badge_class = 'bg-danger';
                                                $status_text = '⚠️ Berakhir Hari Ini';
                                                $show_perpanjang = true;
                                                $show_ambil = $isAdmin;
                                            } elseif ($sisa_hari <= -1) {
                                                $badge_class = 'bg-secondary';
                                                $status_text = '⏰ Berakhir';
                                                $show_perpanjang = false;
                                                $show_ambil = $isAdmin;
                                            } elseif ($sisa_hari == 0) {
                                                $badge_class = 'bg-danger';
                                                $status_text = '⚠️ Hari ini akan berakhir';
                                                $show_perpanjang = true;
                                                $show_ambil = $isAdmin;
                                            } elseif ($sisa_hari == 1) {
                                                $badge_class = 'bg-danger';
                                                $status_text = '🔴 1 hari lagi (H-1)';
                                                $show_perpanjang = true;
                                                $show_ambil = $isAdmin;
                                            } elseif ($sisa_hari == 2) {
                                                $badge_class = 'bg-warning text-dark';
                                                $status_text = '🟡 2 hari lagi';
                                                $show_perpanjang = true;
                                                $show_ambil = $isAdmin;
                                            } elseif ($sisa_hari > 2) {
                                                $badge_class = 'bg-success';
                                                $status_text = '🟢 ' . $sisa_hari . ' hari';
                                                $show_perpanjang = true;
                                                $show_ambil = $isAdmin;
                                            }
                                        ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($row['kode_booking']) ?></strong></td>
                                                <?php if ($isAdmin): ?>
                                                    <td><?= htmlspecialchars($row['nama_pemilik']) ?></td>
                                                    <td><?= htmlspecialchars($row['no_telp']) ?></td>
                                                <?php endif; ?>
                                                <td>
                                                    <?= htmlspecialchars($row['kode_kandang']) ?>
                                                    <br><small><?= htmlspecialchars($row['nama_kandang']) ?></small>

                                                </td>
                                                <td>
                                                    <?php if ($row['jumlah_hewan'] > 0): ?>
                                                        <strong><?= $row['jumlah_hewan'] ?> hewan</strong>
                                                        <br><small class="text-muted">🐾 <?= htmlspecialchars($row['daftar_hewan']) ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('d M Y', strtotime($row['tgl_mulai'])) ?></td>
                                                <td><?= date('d M Y', strtotime($row['tgl_selesai'])) ?></td>
                                                <td><span class="badge <?= $badge_class ?> p-2"><?= $status_text ?></span></td>
                                                <td>
                                                    <?php if ($show_perpanjang): ?>
                                                        <!-- Tombol Perpanjang -->
                                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalPerpanjang<?= $row['id_kandang'] ?>">
                                                            <i class="mdi mdi-calendar-plus"></i> Perpanjang
                                                        </button>
                                                    <?php endif; ?>

                                                    <?php if ($show_ambil): ?>
                                                        <!-- Tombol Ambil (khusus admin) -->
                                                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalAmbil<?= $row['id_kandang'] ?>">
                                                            <i class="mdi mdi-check"></i> Ambil
                                                        </button>
                                                    <?php endif; ?>

                                                    <?php if (!$show_perpanjang && !$show_ambil): ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>

                                            <!-- MODAL PERPANJANG -->
                                            <?php if ($show_perpanjang): ?>
                                                <div class="modal fade" id="modalPerpanjang<?= $row['id_kandang'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <form action="proses.php" method="POST" enctype="multipart/form-data">
                                                                <input type="hidden" name="aksi" value="<?= $isAdmin ? 'perpanjang_admin' : 'perpanjang_user' ?>">
                                                                <input type="hidden" name="id_penitipan" value="<?= $row['id_penitipan'] ?>">
                                                                <input type="hidden" name="id_kandang" value="<?= $row['id_kandang'] ?>">
                                                                <input type="hidden" name="tarif_per_hari" value="<?= $row['tarif_per_hari'] ?>">
                                                                <input type="hidden" name="tgl_selesai_lama" value="<?= $row['tgl_selesai'] ?>">

                                                                <div class="modal-header bg-primary text-white">
                                                                    <h5 class="modal-title"><i class="mdi mdi-calendar-plus me-2"></i> Perpanjang Masa Aktif</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="row mb-3">
                                                                        <div class="col-6"><label class="text-muted small">Kode Booking</label>
                                                                            <p><strong><?= htmlspecialchars($row['kode_booking']) ?></strong></p>
                                                                        </div>
                                                                        <div class="col-6"><label class="text-muted small">Kandang</label>
                                                                            <p><strong><?= htmlspecialchars($row['kode_kandang']) ?></strong></p>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row mb-3">
                                                                        <div class="col-6"><label class="text-muted small">Hewan</label>
                                                                            <p><strong><?= htmlspecialchars($row['daftar_hewan']) ?></strong></p>
                                                                        </div>
                                                                        <div class="col-6"><label class="text-muted small">Tanggal Selesai Saat Ini</label>
                                                                            <p><strong><?= date('d M Y', strtotime($row['tgl_selesai'])) ?></strong></p>
                                                                        </div>
                                                                    </div>
                                                                    <hr>
                                                                    <div class="mb-3">
                                                                        <label class="fw-bold">Tanggal Selesai Baru <span class="text-danger">*</span></label>
                                                                        <input type="date" name="tgl_selesai_baru" class="form-control"
                                                                            id="tgl_baru_<?= $row['id_kandang'] ?>"
                                                                            min="<?= date('Y-m-d', strtotime($row['tgl_selesai'] . ' +1 day')) ?>"
                                                                            required
                                                                            onchange="hitungTotal(<?= $row['id_kandang'] ?>, <?= $row['tarif_per_hari'] ?>, '<?= $row['tgl_selesai'] ?>')">
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="fw-bold">Total Biaya Tambahan</label>
                                                                        <div class="bg-light p-3 rounded-3">
                                                                            <div id="total_biaya_<?= $row['id_kandang'] ?>" class="fw-bold text-danger" style="font-size:24px;">
                                                                                Rp 0
                                                                            </div>
                                                                            <small class="text-muted d-block mt-1" id="total_hari_<?= $row['id_kandang'] ?>">
                                                                                0 hari × Rp <?= number_format($row['tarif_per_hari'], 0, ',', '.') ?>
                                                                            </small>
                                                                            <input type="hidden" name="total_biaya" id="total_biaya_input_<?= $row['id_kandang'] ?>" value="0">
                                                                        </div>
                                                                        <!-- TAMBAHAN KHUSUS ADMIN: INPUT JUMLAH BAYAR -->
<?php if ($isAdmin): ?>
    <hr>
    <div class="mb-3">
        <label class="fw-bold">Jumlah Dibayar</label>
        <input type="text" name="jumlah_bayar" class="form-control jumlah-bayar" 
               placeholder="Masukkan nominal (kosongkan = auto)"
               id="jumlah_bayar_<?= $row['id_kandang'] ?>"
               oninput="autoIsiJumlahBayar(<?= $row['id_kandang'] ?>)">
        <small class="text-muted d-block mt-1">Kosongkan untuk menggunakan total biaya tambahan otomatis.</small>
    </div>
<?php endif; ?>
                                                                    </div>
                                                                    <?php if (!$isAdmin): ?>
                                                                        <hr>
                                                                        <div class="mb-3">
                                                                            <label class="fw-bold">Nama Pembayar <span class="text-danger">*</span></label>
                                                                            <input type="text" name="nama_pembayar" class="form-control" value="<?= htmlspecialchars($_SESSION['nama_lengkap'] ?? '') ?>" required>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="fw-bold">Jumlah Dibayar <span class="text-danger">*</span></label>
                                                                            <input type="text" name="jumlah_bayar" class="form-control jumlah-bayar" placeholder="Masukkan nominal" required>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label class="fw-bold">Metode Pembayaran <span class="text-danger">*</span></label>
                                                                            <select name="id_metode" id="metode_<?= $row['id_kandang'] ?>" class="form-select text-dark" required onchange="tampilMetode(this)">
                                                                                <option value="" class="text-dark">-- Pilih Metode --</option>
                                                                                <?php
                                                                                mysqli_data_seek($metode_list, 0);
                                                                                while ($m = mysqli_fetch_assoc($metode_list)):
                                                                                ?>
                                                                                    <option value="<?= $m['id_metode'] ?>"
                                                                                        class="text-dark"
                                                                                        data-tipe="<?= htmlspecialchars($m['tipe_metode']) ?>"
                                                                                        data-keterangan="<?= htmlspecialchars($m['keterangan']) ?>"
                                                                                        data-gambar="<?= htmlspecialchars($m['gambar_metode']) ?>">
                                                                                        <?= htmlspecialchars($m['nama_metode']) ?>
                                                                                    </option>
                                                                                <?php endwhile; ?>
                                                                            </select>
                                                                            <div id="info_metode_<?= $row['id_kandang'] ?>" class="mt-2"></div>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="fw-bold">Catatan</label>
                                                                            <textarea name="catatan" class="form-control" rows="2"></textarea>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="fw-bold">Upload Bukti Transfer <span class="text-danger">*</span></label>
                                                                            <input type="file" name="bukti_pembayaran" class="form-control" accept="image/*"
                                                                                id="bukti_<?= $row['id_kandang'] ?>"
                                                                                onchange="previewBukti(<?= $row['id_kandang'] ?>)" required>
                                                                            <div id="preview_container_<?= $row['id_kandang'] ?>" class="mt-2" style="display:none;">
                                                                                <img id="preview_bukti_<?= $row['id_kandang'] ?>" src="#" alt="Preview Bukti"
                                                                                    style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                                                            </div>
                                                                            <small class="text-muted d-block mt-1">Format: JPG, JPEG, PNG (Maks 2MB)</small>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                                    <button type="submit" class="btn btn-primary"><?= $isAdmin ? 'Perpanjang' : 'Ajukan' ?></button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- MODAL AMBIL HEWAN (ADMIN) -->
                                            <?php if ($show_ambil): ?>
                                                <div class="modal fade" id="modalAmbil<?= $row['id_kandang'] ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form action="proses.php" method="POST">
                                                                <input type="hidden" name="aksi" value="ambil_hewan">
                                                                <input type="hidden" name="id_penitipan" value="<?= $row['id_penitipan'] ?>">
                                                                <input type="hidden" name="id_kandang" value="<?= $row['id_kandang'] ?>">

                                                                <div class="modal-header bg-success text-white">
                                                                    <h5 class="modal-title"><i class="mdi mdi-home me-2"></i> Konfirmasi Pengambilan</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Yakin ingin mengambil hewan di kandang <strong><?= htmlspecialchars($row['kode_kandang']) ?></strong>?</p>
                                                                    <p><strong>Hewan:</strong> <?= htmlspecialchars($row['daftar_hewan']) ?></p>
                                                                    <p><strong>Pemilik:</strong> <?= htmlspecialchars($row['nama_pemilik']) ?></p>
                                                                    <p class="text-muted small">Stok kandang akan bertambah 1 dan kandang ini akan ditandai selesai.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                                    <button type="submit" class="btn btn-success">Ambil</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require '../templates/footer.php'; ?>
</div>

<script>
    function hitungTotal(id_kandang, tarif, tgl_lama) {
    const tgl_baru = document.getElementById('tgl_baru_' + id_kandang);
    if (!tgl_baru) return;

    const tgl_baru_value = tgl_baru.value;
    const span_total = document.getElementById('total_biaya_' + id_kandang);
    const span_hari = document.getElementById('total_hari_' + id_kandang);
    const input_total = document.getElementById('total_biaya_input_' + id_kandang);

    if (tgl_baru_value && tgl_lama) {
        const date1 = new Date(tgl_lama);
        const date2 = new Date(tgl_baru_value);
        const diffTime = Math.abs(date2 - date1);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays > 0) {
            const total = diffDays * tarif;
            span_total.textContent = 'Rp ' + total.toLocaleString('id-ID');
            span_hari.textContent = diffDays + ' hari × Rp ' + tarif.toLocaleString('id-ID');
            input_total.value = total;
        } else {
            span_total.textContent = 'Rp 0';
            span_hari.textContent = '0 hari × Rp ' + tarif.toLocaleString('id-ID');
            input_total.value = 0;
        }
    }

    // Panggil auto-fill untuk admin
    if (typeof autoIsiJumlahBayar === 'function') {
        autoIsiJumlahBayar(id_kandang);
    }
}



    function tampilMetode(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const infoContainer = document.getElementById('info_metode_' + selectElement.id.replace('metode_', ''));

        if (!infoContainer) return;

        const tipe = selectedOption.getAttribute('data-tipe');
        const keterangan = selectedOption.getAttribute('data-keterangan');
        const gambar = selectedOption.getAttribute('data-gambar');

        let html = '';

        if (tipe === 'QRIS' && gambar) {
            html += '<div class="mt-2 p-3 bg-light rounded-3" style="border: 1px solid #dee2e6;">';
            html += '<img src="../uploads/metode/' + gambar + '" alt="QRIS" style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; padding: 10px; border-radius: 8px;">';
            html += '<p class="text-muted small mt-2">' + keterangan + '</p>';
            html += '</div>';
        } else if (tipe === 'Transfer Bank' || tipe === 'E-Wallet') {
            html += '<div class="mt-2 p-3 bg-light rounded-3" style="border: 1px solid #dee2e6;">';
            html += '<p class="mb-0"><strong>' + keterangan + '</strong></p>';
            html += '</div>';
        } else if (tipe === 'Tunai') {
            html += '<div class="mt-2 p-3 bg-light rounded-3" style="border: 1px solid #dee2e6;">';
            html += '<p class="mb-0 text-muted">Pembayaran dilakukan secara tunai di toko.</p>';
            html += '</div>';
        }

        infoContainer.innerHTML = html;
    }

    function previewBukti(id_kandang) {
        const input = document.getElementById('bukti_' + id_kandang);
        const previewContainer = document.getElementById('preview_container_' + id_kandang);
        const previewImg = document.getElementById('preview_bukti_' + id_kandang);

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewContainer.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            previewContainer.style.display = 'none';
            previewImg.src = '#';
        }
    }

    // Format otomatis input Jumlah Dibayar menjadi Rupiah
    document.querySelectorAll('.jumlah-bayar').forEach(function(input) {
        input.addEventListener('input', function(e) {
            // Hapus semua karakter non-digit
            let value = this.value.replace(/\D/g, '');
            if (value === '') {
                this.value = '';
                return;
            }
            // Format menjadi Rupiah dengan titik sebagai pemisah ribuan
            let formatted = new Intl.NumberFormat('id-ID').format(value);
            this.value = formatted;
        });
    });
</script>
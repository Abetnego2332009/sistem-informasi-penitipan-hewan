<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

$title = 'Ketersediaan Kandang';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';

if (!isset($_SESSION['booking_kandang'])) {
    $_SESSION['booking_kandang'] = [];
}

// ============================================================
// HAPUS LOCK EXPIRED DAN AMBIL DATA LOCK
// ============================================================
mysqli_query($koneksi, "DELETE FROM booking_lock WHERE expires_at < NOW()");

$session_id = session_id();
$id_user = $_SESSION['id_user'] ?? 0;

// Total lock per kandang (dari semua user)
$total_lock_map = [];
$q = mysqli_query($koneksi, "
    SELECT id_kandang, SUM(quantity) as total_lock 
    FROM booking_lock 
    WHERE status IN ('pending_hewan','pending_pembayaran') 
      AND expires_at > NOW() 
    GROUP BY id_kandang
");
while ($row = mysqli_fetch_assoc($q)) {
    $total_lock_map[$row['id_kandang']] = (int)$row['total_lock'];
}

// Lock milik user ini saja
$lock_user_map = [];
$q2 = mysqli_query($koneksi, "
    SELECT id_kandang, SUM(quantity) as lock_user 
    FROM booking_lock 
    WHERE (session_id = '$session_id' OR id_user = $id_user) 
      AND status IN ('pending_hewan','pending_pembayaran') 
      AND expires_at > NOW() 
    GROUP BY id_kandang
");
while ($row = mysqli_fetch_assoc($q2)) {
    $lock_user_map[$row['id_kandang']] = (int)$row['lock_user'];
}

// ============================================================
// FILTER
// ============================================================
$filter_hewan = isset($_GET['jenis_hewan']) ? mysqli_real_escape_string($koneksi, $_GET['jenis_hewan']) : '';
$kapasitas    = isset($_GET['kapasitas']) && $_GET['kapasitas'] !== '' ? (int)$_GET['kapasitas'] : '';
$tgl_mulai    = !empty($_GET['tgl_mulai']) ? mysqli_real_escape_string($koneksi, $_GET['tgl_mulai']) : date('Y-m-d');
$tgl_selesai  = !empty($_GET['tgl_selesai']) ? mysqli_real_escape_string($koneksi, $_GET['tgl_selesai']) : date('Y-m-d', strtotime('+1 day'));

$total_terpilih = array_sum($_SESSION['booking_kandang']);

// ============================================================
// QUERY UTAMA
// ============================================================
$query = "SELECT k.*, k.stok AS sisa_stok
          FROM kandang k 
          WHERE k.is_active = 1";

if (!empty($filter_hewan)) {
    $query .= " AND k.tipe_hewan = '$filter_hewan'";
}
if ($kapasitas !== '') {
    $query .= " AND k.kapasitas >= $kapasitas";
}

$result = mysqli_query($koneksi, $query);
?>

<style>
    .floating-cart {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 9999;
        width: 65px;
        height: 65px;
        border-radius: 50%;
        box-shadow: 0px 4px 15px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .floating-cart:hover {
        transform: scale(1.1);
    }
    .floating-cart .badge-count {
        position: absolute;
        top: -5px;
        right: -5px;
        border-radius: 50%;
        padding: 5px 8px;
        font-size: 12px;
        font-weight: bold;
    }
</style>

<div class="main-panel">
    <div class="content-wrapper">

        <div class="row mb-3">
            <div class="col-12">
                <h3 class="welcome-text fw-bold">Daftar Ketersediaan Kandang</h3>
                <p class="text-muted">Cek dan filter ketersediaan kapasitas kandang klinik secara real-time.</p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-rounded shadow-sm">
                    <div class="card-body p-3">
                        <form method="GET" action="" class="row g-2 align-items-end">
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label small fw-bold text-dark">Jenis Hewan</label>
                                <select name="jenis_hewan" class="form-select form-select-sm">
                                    <option value="">-- Semua --</option>
                                    <option value="Anjing" <?= $filter_hewan == 'Anjing' ? 'selected' : ''; ?>>Anjing</option>
                                    <option value="Kucing" <?= $filter_hewan == 'Kucing' ? 'selected' : ''; ?>>Kucing</option>
                                </select>
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label small fw-bold text-dark">Kapasitas Min.</label>
                                <input type="number" name="kapasitas" class="form-control form-control-sm" placeholder="Contoh: 1" value="<?= htmlspecialchars($kapasitas); ?>" min="1">
                            </div>
                            <!-- <div class="col-md-3 col-sm-6">
                                <label class="form-label small fw-bold text-dark">Tanggal Mulai</label>
                                <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_mulai); ?>" required>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label small fw-bold text-dark">Tanggal Selesai</label>
                                <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_selesai); ?>" required>
                            </div> -->
                            <div class="col-md-2 col-sm-6 d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm text-white flex-grow-1 py-2 fw-bold">
                                    <i class="mdi mdi-magnify"></i> Filter
                                </button>
                                <a href="index.php" class="btn btn-light btn-sm flex-grow-1 py-2 text-dark border">
                                    Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <?php
                    $id_kandang = $row['id_kandang'];
                    $stok_fisik = (int)$row['stok'];
                    $total_lock = isset($total_lock_map[$id_kandang]) ? $total_lock_map[$id_kandang] : 0;
                    $lock_user = isset($lock_user_map[$id_kandang]) ? $lock_user_map[$id_kandang] : 0;
                    $sisa_tampil = $stok_fisik - $total_lock;

                    // Tentukan status
                    if ($sisa_tampil <= 0) {
                        $status_badge = '<span class="badge bg-danger text-white">Penuh / Habis</span>';
                        $disabled = 'disabled';
                        $btn_text = 'Habis';
                    } else {
                        $status_badge = '<span class="badge bg-success text-white">Tersedia ' . $sisa_tampil . ' Unit</span>';
                        if ($total_lock > 0) {
                            $status_badge .= ' <span class="badge bg-warning text-dark">🔒 ' . $total_lock . ' unit dipesan</span>';
                        }
                        $disabled = '';
                        $btn_text = '<i class="mdi mdi-plus-box"></i> Pilih';
                    }

                    // Jika user sendiri yang pegang lock, status "Anda memegang lock" (opsional)
                    $di_keranjang = isset($_SESSION['booking_kandang'][$id_kandang]) ? $_SESSION['booking_kandang'][$id_kandang] : 0;

                    $foto = !empty($row['foto_kandang']) ? '../uploads/kandang/' . $row['foto_kandang'] : '../assets/images/no-image.jpg';
                    ?>
                    <div class="col-md-4 col-sm-6 grid-margin stretch-card">
                        <div class="card card-rounded shadow-sm border">
                            <div style="width: 100%; height: 160px; overflow: hidden; background-color: #f8f9fa;" class="card-img-top border-bottom">
                                <img src="<?= htmlspecialchars($foto); ?>" alt="Foto" style="width: 100%; height: 100%; object-fit: contain;">
                            </div>
                            <div class="card-body d-flex flex-column justify-content-between p-3">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-light text-dark fw-bold"><?= htmlspecialchars($row['kode_kandang']); ?></span>
                                        <?= $status_badge; ?>
                                    </div>
                                    <h5 class="fw-bold mb-1 text-truncate" title="<?= htmlspecialchars($row['nama_kandang']); ?>"><?= htmlspecialchars($row['nama_kandang']); ?></h5>
                                    <p class="text-muted small mb-2">Untuk: <strong class="text-primary"><?= htmlspecialchars($row['tipe_hewan']); ?></strong> | Kapasitas: <strong><?= htmlspecialchars($row['kapasitas']); ?> Ekor</strong></p>
                                    <h5 class="text-success fw-bold mb-2">Rp <?= number_format($row['tarif_per_hari'], 0, ',', '.'); ?> <small class="text-muted">/hari</small></h5>

                                    <?php if ($di_keranjang > 0): ?>
                                        <p class="text-warning small mb-0 fw-bold">⭐ Di Keranjang: <?= $di_keranjang; ?> Unit</p>
                                    <?php endif; ?>
                                    <?php if ($lock_user > 0): ?>
                                        <p class="text-info small mb-0 fw-bold">🔒 Anda memegang lock kandang ini</p>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex gap-2 mt-3">
                                    <a href="detail.php?id=<?= $id_kandang; ?>" class="btn btn-outline-secondary btn-sm flex-grow-1 py-2">
                                        Detail
                                    </a>
                                    <button type="button" class="btn btn-primary btn-sm text-white flex-grow-1 py-2" <?= $disabled; ?> onclick="tambahKeBooking(<?= $id_kandang; ?>, this)">
                                        <?= $btn_text; ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Tidak ada kandang tersedia yang cocok dengan filter pencarian Anda.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Floating Cart -->
    <button type="button" class="btn btn-success floating-cart" data-bs-toggle="modal" data-bs-target="#modalKeranjang">
        <i class="mdi mdi-cart-outline text-white" style="font-size: 28px;"></i>
        <?php if ($total_terpilih > 0): ?>
            <span class="badge bg-danger badge-count"><?= $total_terpilih; ?></span>
        <?php endif; ?>
    </button>

    <!-- Modal Keranjang -->
    <div class="modal fade" id="modalKeranjang" tabindex="-1" aria-labelledby="modalKeranjangLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark" id="modalKeranjangLabel">
                        <i class="mdi mdi-basket text-success me-1"></i> Detail Pilihan Kandang
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <?php if (!empty($_SESSION['booking_kandang'])): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Nama Kandang</th>
                                        <th class="text-center" width="20%">Qty</th>
                                        <th class="text-end" width="30%">Subtotal</th>
                                        <th class="text-center" width="15%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $grand_total = 0;
                                    $ids = implode(',', array_map('intval', array_keys($_SESSION['booking_kandang'])));
                                    $q_sel = mysqli_query($koneksi, "SELECT * FROM kandang WHERE id_kandang IN ($ids)");
                                    while ($sel = mysqli_fetch_assoc($q_sel)):
                                        $id_k = $sel['id_kandang'];
                                        $qty = $_SESSION['booking_kandang'][$id_k];
                                        $subtotal = $sel['tarif_per_hari'] * $qty;
                                        $grand_total += $subtotal;
                                    ?>
                                        <tr>
                                            <td class="p-2">
                                                <span class="fw-bold d-block text-dark small mb-0"><?= htmlspecialchars($sel['nama_kandang']); ?></span>
                                                <small class="text-muted fs-7"><?= htmlspecialchars($sel['kode_kandang']); ?> (<?= htmlspecialchars($sel['tipe_hewan']); ?>)</small>
                                            </td>
                                            <td class="text-center bg-light fw-bold text-dark"><?= $qty; ?>x</td>
                                            <td class="text-end fw-bold text-success p-2">Rp<?= number_format($subtotal, 0, ',', '.'); ?></td>
                                            <td class="text-center">
                                                <a href="hapus_keranjang.php?id=<?= $id_k; ?>&action=minus" class="text-warning me-2" title="Kurangi 1">
                                                    <i class="mdi mdi-minus-box fs-5"></i>
                                                </a>
                                                <a href="hapus_keranjang.php?id=<?= $id_k; ?>&action=delete" class="text-danger" title="Hapus">
                                                    <i class="mdi mdi-delete fs-5"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 p-2 bg-light border rounded d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block">Total Qty:</small>
                                <strong class="text-dark"><?= $total_terpilih; ?> Unit</strong>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block">Estimasi Tarif / Hari:</small>
                                <h5 class="fw-bold text-success mb-0">Rp <?= number_format($grand_total, 0, ',', '.'); ?></h5>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="mdi mdi-cart-off d-block mb-2 text-secondary" style="font-size: 40px;"></i>
                            <p class="small mb-0">Keranjang booking kosong.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer p-2 bg-light">
                    <?php if (!empty($_SESSION['booking_kandang'])): ?>
                        <div class="w-100 d-flex gap-2">
                            <a href="hapus_keranjang.php?clear=all" class="btn btn-sm btn-outline-danger text-dark px-3">Reset</a>
                            <a href="../booking/proses_inisialisasi.php?tgl_mulai=<?= urlencode($tgl_mulai) ?>&tgl_selesai=<?= urlencode($tgl_selesai) ?>" class="btn btn-primary w-100 fw-bold py-2">
                                Lanjutkan Booking <i class="mdi mdi-arrow-right"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <button type="button" class="btn btn-sm btn-secondary w-100" data-bs-dismiss="modal">Tutup</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function tambahKeBooking(idKandang, element) {
            const originalText = element.innerHTML;
            element.disabled = true;
            element.innerHTML = "<i class='mdi mdi-loading mdi-spin'></i>";

            const formData = new FormData();
            formData.append('id_kandang', idKandang);

            fetch('ajax_tambah_keranjang.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        alert(data.message);
                        element.innerHTML = originalText;
                        element.disabled = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Terjadi masalah pada koneksi server.');
                    element.innerHTML = originalText;
                    element.disabled = false;
                });
        }
    </script>

    <?php require '../templates/footer.php'; ?>
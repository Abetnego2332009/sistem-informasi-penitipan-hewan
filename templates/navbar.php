<!-- partial:partials/_navbar.html -->
<?php
// ============================================================
// LOAD KONEKSI & FUNGSI
// ============================================================
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../functions.php';

// Tentukan sapaan berdasarkan waktu (WIB)
$jam = date('H');
if ($jam >= 4 && $jam < 11) {
    $sapaan = 'Selamat Pagi';
} elseif ($jam >= 11 && $jam < 15) {
    $sapaan = 'Selamat Siang';
} elseif ($jam >= 15 && $jam < 18) {
    $sapaan = 'Selamat Sore';
} else {
    $sapaan = 'Selamat Malam';
}

// Ambil data user dari session
$nama_user = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pengunjung';
$email_user = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$foto_profil = isset($_SESSION['foto_profil']) && !empty($_SESSION['foto_profil']) 
    ? $_SESSION['foto_profil'] 
    : null;

// ============================================================
// AMBIL NOTIFIKASI UNTUK ADMIN
// ============================================================
$notif_data = [];
$notif_total = 0;
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['Super Admin', 'Admin'])) {
    $notif_result = getNotifikasiDetail($koneksi);
    $notif_data = $notif_result['data'] ?? [];
    $notif_total = $notif_result['total'] ?? 0;
}

// ============================================================
// FUNGSI UNTUK MENDAPATKAN INISIAL NAMA (untuk avatar)
// ============================================================
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    return substr($initials, 0, 2); // maksimal 2 huruf
}
$inisial = getInitials($nama_user);
?>
<nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
        <div class="me-3">
            <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
                <span class="icon-menu"></span>
            </button>
        </div>
        <div>
            <a class="navbar-brand brand-logo" href="<?= $base_url?>dashboard.php">
                <img src="assets/images/logo.svg" alt="logo" />
            </a>
            <a class="navbar-brand brand-logo-mini" href="index.html">
                <img src="assets/images/logo-mini.svg" alt="logo" />
            </a>
        </div>
    </div>
    <div class="navbar-menu-wrapper d-flex align-items-top">
        <ul class="navbar-nav">
            <li class="nav-item fw-semibold d-none d-lg-block ms-0">
                <h1 class="welcome-text"><?= $sapaan; ?>, <span class="text-black fw-bold"><?= htmlspecialchars($nama_user); ?></span></h1>
                <h3 class="welcome-sub-text">Selamat datang di dashboard Anda</h3>
            </li>
        </ul>
        <ul class="navbar-nav ms-auto">
            <!-- ========================================================== -->
            <!-- NOTIFIKASI DINAMIS (khusus admin)                          -->
            <!-- ========================================================== -->
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['Super Admin', 'Admin'])): ?>
            <li class="nav-item dropdown">
                <a class="nav-link count-indicator" id="notificationDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="icon-bell"></i>
                    <?php if ($notif_total > 0): ?>
                        <span class="count" style="background: #e74a3b; color: #fff; border-radius: 50%; padding: 2px 6px; font-size: 10px; position: absolute; top: 5px; right: 5px;"><?= $notif_total ?></span>
                    <?php else: ?>
                        <span class="count"></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="notificationDropdown" style="min-width: 350px; max-height: 400px; overflow-y: auto;">
                    <div class="dropdown-header py-3 border-bottom">
                        <p class="mb-0 fw-medium float-start">Notifikasi</p>
                        <span class="badge badge-pill badge-primary float-end"><?= $notif_total ?></span>
                    </div>

                    <?php if (empty($notif_data)): ?>
                        <a class="dropdown-item text-center py-3 text-muted" href="#">Tidak ada notifikasi</a>
                    <?php else: ?>
                        <?php foreach ($notif_data as $item): ?>
                            <?php
                                $icon = 'mdi mdi-bell';
                                $bg = 'bg-primary';
                                if ($item['jenis'] == 'pembayaran') {
                                    $icon = 'mdi mdi-credit-card';
                                    $bg = 'bg-primary';
                                } elseif ($item['jenis'] == 'perpanjangan') {
                                    $icon = 'mdi mdi-clock';
                                    $bg = 'bg-warning';
                                } elseif ($item['jenis'] == 'booking_belum_dititip') {
                                    $icon = 'mdi mdi-paw';
                                    $bg = 'bg-danger';
                                }
                            ?>
                            <a class="dropdown-item preview-item py-2" href="<?= $base_url ?>konfirmasi_pembayaran">
                                <div class="preview-thumbnail">
                                    <span class="<?= $icon ?> m-auto <?= $bg ?> text-white p-2 rounded-circle"></span>
                                </div>
                                <div class="preview-item-content flex-grow">
                                    <h6 class="preview-subject fw-normal text-dark mb-1"><?= htmlspecialchars($item['pesan']) ?></h6>
                                    <p class="fw-light small-text mb-0"><?= $item['waktu'] ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-center py-2 text-primary fw-bold" href="<?= $base_url ?>konfirmasi_pembayaran">Lihat Semua</a>
                </div>
            </li>
            <?php endif; ?>
            <!-- ========================================================== -->

            <!-- ========================================================== -->
            <!-- AVATAR USER (di sebelah notifikasi)                        -->
            <!-- ========================================================== -->
            <li class="nav-item dropdown d-none d-lg-block user-dropdown">
                <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php if ($foto_profil): ?>
                        <img class="img-xs rounded-circle" src="../uploads/profil/<?= htmlspecialchars($foto_profil) ?>" alt="Profile image" style="width: 35px; height: 35px; object-fit: cover;">
                    <?php else: ?>
                        <span class="img-xs rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; background: #4e73df; color: #fff; font-weight: bold; font-size: 14px;">
                            <?= $inisial ?>
                        </span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown" style="min-width: 200px;">
                    <div class="dropdown-header text-center">
                        <?php if ($foto_profil): ?>
                            <img class="img-md rounded-circle" src="../uploads/profil/<?= htmlspecialchars($foto_profil) ?>" alt="Profile image" style="width: 60px; height: 60px; object-fit: cover;">
                        <?php else: ?>
                            <span class="img-md rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 60px; height: 60px; background: #4e73df; color: #fff; font-weight: bold; font-size: 24px;">
                                <?= $inisial ?>
                            </span>
                        <?php endif; ?>
                        <p class="mb-1 mt-3 fw-semibold"><?= htmlspecialchars($nama_user); ?></p>
                        <p class="fw-light text-muted mb-0"><?= htmlspecialchars($email_user); ?></p>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?= $base_url ?>profile">
                        <i class="dropdown-item-icon mdi mdi-account-outline text-primary me-2"></i> Profil
                    </a>
                    <a class="dropdown-item" href="<?= $base_url ?>logout.php" onclick="return confirm('Yakin ingin logout?')">
                        <i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i> Logout
                    </a>
                </div>
            </li>
            <!-- ========================================================== -->
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-bs-toggle="offcanvas">
            <span class="mdi mdi-menu"></span>
        </button>
    </div>
</nav>
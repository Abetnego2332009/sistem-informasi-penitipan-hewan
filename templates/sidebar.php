<?php

/**
 * sidebar.php
 * Sidebar sederhana Sistem Informasi Penitipan Hewan
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../koneksi.php';
// Include fungsi notifikasi
require_once __DIR__ . '/../functions.php';
$notif = getNotifikasiKonfirmasi($koneksi);
$currentPage = basename($_SERVER['PHP_SELF']);

?>

<div class="container-fluid page-body-wrapper">
    <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <ul class="nav">

            <!-- ================= DASHBOARD ================= -->
            <!-- ADMIN & USER -->
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>"
                    href="<?= $base_url ?>dashboard.php">
                    <i class="mdi mdi-view-dashboard menu-icon"></i>
                    <span class="menu-title">Dashboard</span>
                </a>
            </li>


            <li class="nav-item nav-category">Booking</li>
            <!-- KETERSEDIAAN KANDANG -->
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'ketersediaan_kandang.php' ? 'active' : '' ?>"
                    href="<?= $base_url ?>ketersediaan_kandang/">
                    <i class="mdi mdi-home-search menu-icon"></i>
                    <span class="menu-title">Booking</span>
                </a>
            </li>


            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'bookingan_saya' ? 'active' : '' ?>"
                    href="<?= $base_url ?>bookingan_saya">
                    <i class="mdi mdi-clipboard-text menu-icon"></i>
                    <span class="menu-title">Bookingan Saya</span>
                </a>
            </li>
            <!-- USER & ADMIN -->
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'monitoring_hewan' ? 'active' : '' ?>"
                    href="<?= $base_url ?>monitoring_hewan/">
                    <i class="mdi mdi-paw menu-icon"></i>
                    <span class="menu-title">Monitoring Hewan</span>
                </a>
            </li>

            <?php if (
                $_SESSION['role'] == 'Super Admin' ||
                $_SESSION['role'] == 'Admin'
            ): ?>


                <li class="nav-item nav-category">Master Data</li>
                <!-- ADMIN -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'kandang.php' ? 'active' : '' ?>"
                        href="<?= $base_url ?>kandang/">
                        <i class="mdi mdi-home menu-icon"></i>
                        <span class="menu-title">Data Kandang</span>
                    </a>
                </li>

                <!-- ADMIN ONLY -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'status_harian' ? 'active' : '' ?>"
                        href="<?= $base_url ?>status_harian/">
                        <i class="mdi mdi-clipboard-text menu-icon"></i>
                        <span class="menu-title">Status Harian</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'metode_pembayaran/index.php' ? 'active' : '' ?>"
                        href="<?= $base_url ?>metode_pembayaran/">
                        <i class="mdi mdi-credit-card menu-icon"></i>
                        <span class="menu-title">Metode Pembayaran</span>
                    </a>
                </li>




                <!-- ================= USER & ADMIN ================= -->

                <!-- USER & ADMIN -->

                <li class="nav-item nav-category">Transaksi</li>

                <!-- ADMIN -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'konfirmasi_pembayaran' ? 'active' : '' ?>"
                        href="<?= $base_url ?>konfirmasi_pembayaran">
                        <i class="mdi mdi-cash menu-icon"></i>
                        <span class="menu-title">Konfirmasi</span>
                        <?php if ($notif['total'] > 0): ?>
                            <span class="badge bg-danger ms-auto" style="border-radius: 50%; padding: 4px 8px; font-size: 11px;">
                                <?= $notif['total'] ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (
                    $_SESSION['role'] == 'Super Admin' ||
                    $_SESSION['role'] == 'Admin' ||
                    $_SESSION['role'] == 'Pelanggan' 
                    ): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'masa_aktif' ? 'active' : '' ?>"
                    href="<?= $base_url ?>masa_aktif">
                    <i class="mdi mdi-credit-card menu-icon"></i>
                    <span class="menu-title">Masa Aktif Bookingan</span>
                </a>
            </li>
            <?php endif; ?>

                         <?php if (
                    $_SESSION['role'] == 'Super Admin' ||
                    $_SESSION['role'] == 'Admin' 
                    ): ?>   

                <li class="nav-item nav-category">Laporan</li>
                <!-- ================= ADMIN ================= -->
                <!-- ADMIN -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'data_pelanggan' ? 'active' : '' ?>"
                        href="<?= $base_url ?>data_pelanggan">
                        <i class="mdi mdi-account-group menu-icon"></i>
                        <span class="menu-title">Daftar Pelanggan</span>
                    </a>
                </li>
                <!-- ADMIN -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'laporan.php' ? 'active' : '' ?>"
                        href="<?= $base_url ?>laporan">
                        <i class="mdi mdi-file-document menu-icon"></i>
                        <span class="menu-title">Laporan</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'testimoni' ? 'active' : '' ?>"
                        href="<?= $base_url ?>testimoni">
                        <i class="mdi mdi-star-outline menu-icon"></i>
                        <span class="menu-title">Testimoni</span>
                    </a>
                </li>
                            <?php endif; ?>
            <?php if (
                $_SESSION['role'] == 'Pelanggan'
            ): ?>
                <li class="nav-item nav-category">Laporan</li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'bookingan_saya/laporan.php' ? 'active' : '' ?>" href="<?= $base_url ?>bookingan_saya/laporan.php">
                        <i class="mdi mdi-file-chart menu-icon"></i>
                        <span class="menu-title">Laporan Booking</span>
                    </a>
                </li>

            
            <?php endif; ?>



            <!-- ================= ADMIN ================= -->





            <!-- ================= USER & ADMIN ================= -->

            <li class="nav-item nav-category">Profile</li>
            <!-- USER & ADMIN -->
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'profil.php' ? 'active' : '' ?>"
                    href="<?= $base_url ?>profile">
                    <i class="mdi mdi-account-circle menu-icon"></i>
                    <span class="menu-title">Profil</span>
                </a>
            </li>
            <?php if (
                $_SESSION['role'] == 'Super Admin' ||
                $_SESSION['role'] == 'Admin'
            ): ?>
                <li class="nav-item nav-category">Manajemen Sistem</li>
                <!-- ADMIN -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'users/index.php' ? 'active' : '' ?>" href="<?= $base_url ?>users/index.php">
                        <i class="mdi mdi-account-multiple menu-icon"></i>
                        <span class="menu-title">Manajemen User</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'pengaturan' ? 'active' : '' ?>"
                        href="<?= $base_url ?>pengaturan_sistem/">
                        <i class="mdi mdi-cog menu-icon"></i>
                        <span class="menu-title">Pengaturan Sistem</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- USER & ADMIN -->
            <li class="nav-item">
                <a class="nav-link"
                    href="<?= $base_url ?>logout.php"
                    onclick="return confirm('Yakin ingin logout?')">
                    <i class="mdi mdi-logout menu-icon"></i>
                    <span class="menu-title">Logout</span>
                </a>
            </li>

        </ul>
    </nav>
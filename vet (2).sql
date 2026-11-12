-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 04, 2026 at 05:49 AM
-- Server version: 11.4.8-MariaDB-log
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vet`
--

-- --------------------------------------------------------

--
-- Table structure for table `banner`
--

CREATE TABLE `banner` (
  `id` int(11) NOT NULL,
  `judul_utama` varchar(255) DEFAULT NULL,
  `sub_judul` varchar(255) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `tombol_kiri_teks` varchar(100) DEFAULT NULL,
  `tombol_kiri_link` varchar(255) DEFAULT NULL,
  `tombol_kanan_teks` varchar(100) DEFAULT NULL,
  `tombol_kanan_link` varchar(255) DEFAULT NULL,
  `gambar_banner` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `banner`
--

INSERT INTO `banner` (`id`, `judul_utama`, `sub_judul`, `deskripsi`, `tombol_kiri_teks`, `tombol_kiri_link`, `tombol_kanan_teks`, `tombol_kanan_link`, `gambar_banner`) VALUES
(1, 'Titipkan Hewan Kesayangan Anda dengan Aman & Nyaman', 'Penitipan Hewan Terpercaya No.1 Pematangsiantar', 'Njuah Vet Siantar menyediakan layanan penitipan khusus anjing dan kucing dengan fasilitas kandang modern, pemantauan harian, dan perawatan penuh kasih dari tim profesional kami.', 'Cek Ketersediaan', '#ketersediaan', 'Cara Penitipan', '#cara', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `booking_lock`
--

CREATE TABLE `booking_lock` (
  `id_lock` int(11) NOT NULL,
  `id_kandang` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `id_penitipan` int(11) DEFAULT NULL,
  `status` enum('pending_hewan','pending_pembayaran') DEFAULT 'pending_hewan',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cara_penitipan`
--

CREATE TABLE `cara_penitipan` (
  `id` int(11) NOT NULL,
  `urutan` int(11) NOT NULL,
  `judul` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'ðŸ“‹',
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `cara_penitipan`
--

INSERT INTO `cara_penitipan` (`id`, `urutan`, `judul`, `deskripsi`, `icon`, `is_active`) VALUES
(1, 1, 'Daftar Akun', 'Buat akun di website kami dengan mengisi data diri lengkap Anda sebagai pemilik hewan.', 'ðŸ“‹', 1),
(2, 2, 'Daftarkan Hewan', 'Tambahkan data hewan Anda (jenis, ras, usia, berat, riwayat kesehatan, status vaksin).', 'ðŸ¾', 1),
(3, 3, 'Pilih Kandang & Tanggal', 'Pilih kandang yang tersedia sesuai jenis dan ukuran hewan, lalu tentukan tanggal mulai dan selesai penitipan.', 'ðŸ“…', 1),
(4, 4, 'Bayar & Antar Hewan', 'Lakukan pembayaran sesuai tarif, unggah bukti transfer, dan antar hewan pada tanggal yang disepakati.', 'ðŸ’³', 1);

-- --------------------------------------------------------

--
-- Table structure for table `hewan`
--

CREATE TABLE `hewan` (
  `id_hewan` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_pelanggan` int(11) DEFAULT NULL,
  `nama_hewan` varchar(100) NOT NULL COMMENT 'Nama panggilan hewan',
  `jenis_hewan` enum('Anjing','Kucing') NOT NULL COMMENT 'Batasan sistem: hanya Anjing/Kucing',
  `ras` varchar(100) DEFAULT NULL COMMENT 'Ras / varietas hewan',
  `jenis_kelamin` enum('Jantan','Betina') NOT NULL,
  `usia_tahun` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Usia dalam tahun',
  `usia_bulan` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Sisa usia dalam bulan (0-11)',
  `berat_badan` decimal(5,2) DEFAULT NULL COMMENT 'Berat badan dalam kilogram',
  `warna_bulu` varchar(100) DEFAULT NULL COMMENT 'Warna / pola bulu',
  `karakteristik` text DEFAULT NULL,
  `riwayat_kesehatan` text DEFAULT NULL COMMENT 'Catatan riwayat kesehatan & alergi',
  `status_vaksin` enum('Sudah Divaksin','Belum Divaksin','Tidak Diketahui') NOT NULL DEFAULT 'Tidak Diketahui',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 = hewan sudah tidak ada (meninggal/dijual)',
  `foto_hewan` varchar(255) DEFAULT NULL COMMENT 'Path/URL foto hewan',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabel identitas hewan peliharaan (hanya Anjing & Kucing)';

--
-- Dumping data for table `hewan`
--

INSERT INTO `hewan` (`id_hewan`, `id_user`, `id_pelanggan`, `nama_hewan`, `jenis_hewan`, `ras`, `jenis_kelamin`, `usia_tahun`, `usia_bulan`, `berat_badan`, `warna_bulu`, `karakteristik`, `riwayat_kesehatan`, `status_vaksin`, `is_active`, `foto_hewan`, `created_at`, `updated_at`) VALUES
(59, 6, NULL, 'Bule', 'Anjing', 'anjing Kampung', 'Jantan', 24, 5, 50.00, 'hitam', 'Punya Tai Lalat', 'Sehat', 'Sudah Divaksin', 1, NULL, '2026-07-14 03:07:09', '2026-07-14 03:07:09'),
(60, 6, NULL, 'Almos', 'Anjing', 'Buldog', 'Jantan', 4, 5, 50.00, 'hitam', 'Punya Daki', 'Sehat', 'Sudah Divaksin', 1, NULL, '2026-07-14 03:07:09', '2026-07-14 03:07:09'),
(61, 6, NULL, 'Alkat', 'Anjing', 'Anjing Kampung', 'Jantan', 5, 2, 10.00, 'Hitam', 'ASDAS', 'asd', 'Sudah Divaksin', 1, NULL, '2026-07-14 03:07:09', '2026-07-14 03:07:09'),
(62, 6, NULL, 'sdf', 'Anjing', 'asd', 'Jantan', 20, 2, 40.00, 'saada', 'asd', 'asd', 'Tidak Diketahui', 1, NULL, '2026-07-14 03:07:09', '2026-07-14 03:07:09'),
(63, 6, NULL, 'asd', 'Anjing', 'asd', 'Jantan', 5, 4, 50.00, 'asd', 'asad', 'sdf', 'Tidak Diketahui', 1, NULL, '2026-07-14 03:07:09', '2026-07-14 03:07:09');

-- --------------------------------------------------------

--
-- Table structure for table `kandang`
--

CREATE TABLE `kandang` (
  `id_kandang` int(11) NOT NULL,
  `kode_kandang` varchar(20) NOT NULL COMMENT 'Kode unik kandang (contoh: ANJ-S-01)',
  `nama_kandang` varchar(100) NOT NULL COMMENT 'Nama deskriptif kandang',
  `tipe_hewan` enum('Anjing','Kucing') NOT NULL COMMENT 'Peruntukan jenis hewan',
  `ukuran` enum('Kecil','Sedang','Besar') NOT NULL,
  `tarif_per_hari` decimal(10,2) NOT NULL COMMENT 'Harga sewa per hari (Rupiah)',
  `fasilitas` text DEFAULT NULL COMMENT 'Deskripsi fasilitas kandang',
  `kapasitas` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Jumlah hewan yang bisa ditampung',
  `stok` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Aktif/Tersedia, 0 = Dihapus (Soft Delete)',
  `foto_kandang` varchar(255) DEFAULT NULL COMMENT 'Path/URL foto kandang',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabel unit kandang penitipan hewan';

--
-- Dumping data for table `kandang`
--

INSERT INTO `kandang` (`id_kandang`, `kode_kandang`, `nama_kandang`, `tipe_hewan`, `ukuran`, `tarif_per_hari`, `fasilitas`, `kapasitas`, `stok`, `is_active`, `foto_kandang`, `created_at`, `updated_at`) VALUES
(1, 'ANJ-K-01', 'Kandang Anjing Kecil 1', 'Anjing', 'Kecil', 75000.00, 'Alas tidur empuk, mangkuk makan & minum, kipas angin, mainan gigit', 1, 1, 1, NULL, '2026-06-16 06:55:10', '2026-07-14 03:08:00'),
(2, 'ANJ-K-02', 'Kandang Anjing Kecil 2', 'Anjing', 'Kecil', 75000.00, 'Alas tidur empuk, mangkuk makan & minum, kipas angin, mainan gigit', 3, 2, 1, 'kandang_1784261729_6a59ac61eb957.jpg', '2026-06-16 06:55:10', '2026-07-17 11:17:35'),
(3, 'ANJ-S-01', 'Kandang Anjing Sedang 1', 'Anjing', 'Sedang', 100000.00, 'Kasur orthopedic, mangkuk stainless, AC, area bermain kecil, mainan', 1, 1, 1, NULL, '2026-06-16 06:55:10', '2026-07-14 03:08:06'),
(4, 'ANJ-S-02', 'Kandang Anjing Sedang 2', 'Anjing', 'Sedang', 100000.00, 'Kasur orthopedic, mangkuk stainless, AC, area bermain kecil, mainan', 1, 1, 1, NULL, '2026-06-16 06:55:10', '2026-07-10 05:21:40'),
(5, 'ANJ-B-01', 'Kandang Anjing Besar 1', 'Anjing', 'Besar', 150000.00, 'Kasur premium, mangkuk stainless, AC, area bermain luas, mainan lengkap, CCTV 24 jam', 1, 1, 1, '', '2026-06-16 06:55:10', '2026-07-10 05:21:07'),
(6, 'KUC-K-01', 'Kandang Kucing Kecil 1', 'Kucing', 'Kecil', 60000.00, 'Tempat tidur bersih, mangkuk makan & minum, kotak pasir, mainan bulu', 1, 1, 1, NULL, '2026-06-16 06:55:10', '2026-07-10 05:21:50'),
(7, 'KUC-K-02', 'Kandang Kucing Kecil 2', 'Kucing', 'Kecil', 60000.00, 'Tempat tidur bersih, mangkuk makan & minum, kotak pasir, mainan bulu', 1, 1, 1, NULL, '2026-06-16 06:55:10', '2026-07-10 05:21:59'),
(8, 'KUC-S-01', 'Kandang Kucing Sedang 1', 'Kucing', 'Sedang', 85000.00, 'Hammock gantung, mangkuk keramik, kotak pasir, scratching post, AC', 1, 1, 1, NULL, '2026-06-16 06:55:10', '2026-06-25 18:37:59'),
(9, 'KUC-S-02', 'Kandang Kucing Sedang 2', 'Kucing', 'Sedang', 85000.00, 'Hammock gantung, mangkuk keramik, kotak pasir, scratching post, AC', 1, 1, 1, NULL, '2026-06-16 06:55:10', '2026-06-25 18:38:09'),
(10, 'KUC-B-01', 'Kandang Kucing Suite (Besar)', 'Kucing', 'Besar', 125000.00, 'Kasur mewah, mangkuk premium, kotak pasir otomatis, AC, cat tree, CCTV, penitipan eksklusif', 2, 1, 1, NULL, '2026-06-16 06:55:10', '2026-06-25 18:37:18');

-- --------------------------------------------------------

--
-- Table structure for table `keunggulan`
--

CREATE TABLE `keunggulan` (
  `id_keunggulan` int(11) NOT NULL,
  `judul` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `urutan` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `metode_pembayaran`
--

CREATE TABLE `metode_pembayaran` (
  `id_metode` int(11) NOT NULL,
  `nama_metode` varchar(50) NOT NULL COMMENT 'QRIS, BCA, Mandiri, OVO, GoPay, dll',
  `tipe_metode` enum('QRIS','Transfer Bank','E-Wallet','Tunai') NOT NULL,
  `keterangan` text DEFAULT NULL COMMENT 'Deskripsi / instruksi pembayaran',
  `gambar_metode` varchar(255) DEFAULT NULL COMMENT 'Path/URL gambar QRIS atau logo bank',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = aktif, 0 = nonaktif',
  `urutan` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `metode_pembayaran`
--

INSERT INTO `metode_pembayaran` (`id_metode`, `nama_metode`, `tipe_metode`, `keterangan`, `gambar_metode`, `is_active`, `urutan`, `created_at`, `updated_at`) VALUES
(1, 'QRIS', 'QRIS', 'Scan QR Code berikut untuk melakukan pembayaran.', '', 0, 1, '2026-06-17 11:24:29', '2026-06-27 23:36:57'),
(2, 'BCA', 'Transfer Bank', 'Transfer ke rekening BCA: 1234567890 a/n Njuah Vet', NULL, 1, 2, '2026-06-17 11:24:29', '2026-06-17 11:24:29'),
(3, 'Mandiri', 'Transfer Bank', 'Transfer ke rekening Mandiri: 9876543210 a/n Njuah Vet', NULL, 1, 3, '2026-06-17 11:24:29', '2026-06-17 11:24:29'),
(4, 'OVO', 'E-Wallet', 'Transfer ke OVO: 081234567890 a/n Njuah Vet', NULL, 1, 4, '2026-06-17 11:24:29', '2026-06-17 11:24:29'),
(5, 'GoPay', 'E-Wallet', 'Transfer ke GoPay: 081234567890 a/n Njuah Vet', NULL, 1, 5, '2026-06-17 11:24:29', '2026-06-17 11:24:29'),
(6, 'Dana', 'E-Wallet', 'Transfer ke Dana: 081234567890 a/n Njuah Vet', NULL, 1, 6, '2026-06-17 11:24:29', '2026-06-17 11:24:29'),
(7, 'Tunai', 'Tunai', 'Pembayaran Tunai', NULL, 1, 0, '2026-06-17 14:42:57', '2026-06-17 14:42:57'),
(8, 'BNI', 'Transfer Bank', '22182546646544       A/N : Njuah Vet', '', 1, 2, '2026-06-27 23:39:59', '2026-06-27 23:40:17');

-- --------------------------------------------------------

--
-- Table structure for table `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id_pelanggan` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `no_telp` varchar(15) NOT NULL,
  `alamat` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelanggan`
--

INSERT INTO `pelanggan` (`id_pelanggan`, `nama_lengkap`, `no_telp`, `alamat`, `created_at`, `updated_at`) VALUES
(7, 'Efrael', '082273816151', 'Jalan Pane', '2026-06-24 19:28:16', '2026-06-24 19:28:16');

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id_pembayaran` int(11) NOT NULL,
  `id_penitipan` int(11) NOT NULL COMMENT 'FK → penitipan.id_penitipan',
  `kode_pembayaran` varchar(30) NOT NULL COMMENT 'Kode unik transaksi pembayaran',
  `tgl_bayar` datetime DEFAULT NULL COMMENT 'Waktu pelanggan melakukan pembayaran',
  `metode_bayar` varchar(50) NOT NULL,
  `id_metode` int(11) DEFAULT NULL,
  `nama_pembayar` varchar(100) DEFAULT NULL,
  `jumlah_tagihan` decimal(12,2) NOT NULL COMMENT 'Nominal tagihan sesuai total_biaya penitipan',
  `jumlah_bayar` decimal(12,2) NOT NULL COMMENT 'Nominal yang benar-benar dibayarkan pelanggan',
  `bukti_pembayaran` varchar(255) DEFAULT NULL COMMENT 'Path/URL foto bukti transfer',
  `status_bayar` enum('Menunggu Konfirmasi','Lunas','Gagal','Dikembalikan') NOT NULL DEFAULT 'Menunggu Konfirmasi',
  `catatan` text DEFAULT NULL COMMENT 'Catatan tambahan terkait pembayaran',
  `dikonfirmasi_oleh` int(11) DEFAULT NULL COMMENT 'FK → users.id_user (role=Admin)',
  `tgl_konfirmasi` datetime DEFAULT NULL COMMENT 'Waktu admin mengkonfirmasi pembayaran',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabel transaksi pembayaran layanan penitipan';

--
-- Dumping data for table `pembayaran`
--

INSERT INTO `pembayaran` (`id_pembayaran`, `id_penitipan`, `kode_pembayaran`, `tgl_bayar`, `metode_bayar`, `id_metode`, `nama_pembayar`, `jumlah_tagihan`, `jumlah_bayar`, `bukti_pembayaran`, `status_bayar`, `catatan`, `dikonfirmasi_oleh`, `tgl_konfirmasi`, `created_at`, `updated_at`) VALUES
(93, 138, 'PAY-20260714-0001', '2026-07-14 03:07:25', 'BNI', 8, 'iccon', 250000.00, 250000.00, 'uploads/bukti_pembayaran/BK-20260714-443B_20260714_030725.png', 'Lunas', '0', 1, '2026-07-14 03:07:37', '2026-07-14 03:07:25', '2026-07-14 03:07:37');

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan_umum`
--

CREATE TABLE `pengaturan_umum` (
  `id` int(11) NOT NULL,
  `nama_toko` varchar(100) DEFAULT 'Njuah Vet Siantar',
  `alamat` varchar(255) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `jam_operasional` varchar(255) DEFAULT NULL,
  `deskripsi_toko` text DEFAULT NULL,
  `sejarah_toko` text DEFAULT NULL,
  `google_maps_embed` text DEFAULT NULL,
  `foto_toko` varchar(255) DEFAULT NULL,
  `jumlah_kandang_tampil` int(11) DEFAULT 10,
  `jumlah_testimoni_tampil` int(11) DEFAULT 3,
  `status_toko` enum('buka','tutup') DEFAULT 'buka',
  `ticker_teks` text DEFAULT NULL,
  `ticker_aktif` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengaturan_umum`
--

INSERT INTO `pengaturan_umum` (`id`, `nama_toko`, `alamat`, `telepon`, `email`, `website`, `jam_operasional`, `deskripsi_toko`, `sejarah_toko`, `google_maps_embed`, `foto_toko`, `jumlah_kandang_tampil`, `jumlah_testimoni_tampil`, `status_toko`, `ticker_teks`, `ticker_aktif`, `created_at`, `updated_at`) VALUES
(1, 'Njuah Vet Siantar', 'Jl. Veteriner No. 1, Pematangsiantar', '+62 812-3456-7890', 'info@njuahvetsiantar.com', '', 'Senin-Jumat: 07.00-20.00 WIB, Sabtu: 07.00-18.00 WIB, Minggu: 08.00-16.00 WIB', 'Tempat penitipan hewan peliharaan terpercaya di Pematangsiantar. Kami khusus melayani penitipan anjing dan kucing dengan fasilitas kandang modern, pemantauan CCTV 24 jam, dan perawatan oleh staf profesional berpengalaman.', '', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15938.133516252727!2d99.0700287049362!3d2.9493689516859427!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x303185b81829af87%3A0x26e50ee03f9e9866!2sNJUAH%20VET%20SIANTAR%20(Praktek%20Dokter%20Hewan)!5e0!3m2!1sid!2sus!4v1783631675321!5m2!1sid!2sus\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"strict-origin-when-cross-origin', 'profil_1782652089.png', 10, 3, 'buka', 'ðŸ¾ Selamat datang di Njuah Vet Siantar â€” Tempat Penitipan Hewan Terpercaya di Pematangsiantar', 1, '2026-06-28 22:56:18', '2026-07-17 11:57:37');

-- --------------------------------------------------------

--
-- Table structure for table `penitipan`
--

CREATE TABLE `penitipan` (
  `id_penitipan` int(11) NOT NULL,
  `kode_booking` varchar(25) NOT NULL COMMENT 'Kode booking unik (contoh: NVS-20240101-001)',
  `id_user` int(11) DEFAULT NULL,
  `id_pelanggan` int(11) DEFAULT NULL,
  `tipe_pelanggan` enum('online','offline') DEFAULT 'online',
  `id_user_admin` int(11) DEFAULT NULL COMMENT 'FK → users.id_user (role=Admin, yang memproses)',
  `tgl_booking` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Waktu pengajuan booking',
  `tgl_mulai` date NOT NULL COMMENT 'Tanggal mulai penitipan',
  `tgl_selesai` date NOT NULL COMMENT 'Tanggal rencana pengambilan',
  `jumlah_hari` int(11) GENERATED ALWAYS AS (to_days(`tgl_selesai`) - to_days(`tgl_mulai`)) STORED COMMENT 'Otomatis: selisih hari penitipan',
  `total_biaya` decimal(12,2) DEFAULT NULL COMMENT 'Snapshot biaya saat booking dikonfirmasi',
  `biaya_tambahan` decimal(12,2) DEFAULT 0.00,
  `catatan_pelanggan` text DEFAULT NULL COMMENT 'Instruksi khusus dari pemilik hewan',
  `status_penitipan` enum('Pending','Menunggu Konfirmasi','Disetujui','Ditolak','Sedang Dititipkan','Selesai','Dibatalkan','Perpanjangan Menunggu Konfirmasi') NOT NULL DEFAULT 'Pending',
  `tgl_disetujui` datetime DEFAULT NULL COMMENT 'Waktu admin menyetujui booking',
  `tgl_masuk_aktual` datetime DEFAULT NULL COMMENT 'Waktu nyata hewan diserahkan',
  `tgl_keluar_aktual` datetime DEFAULT NULL COMMENT 'Waktu nyata hewan diambil kembali',
  `alasan_penolakan` text DEFAULT NULL COMMENT 'Alasan admin jika booking ditolak',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `penitipan`
--

INSERT INTO `penitipan` (`id_penitipan`, `kode_booking`, `id_user`, `id_pelanggan`, `tipe_pelanggan`, `id_user_admin`, `tgl_booking`, `tgl_mulai`, `tgl_selesai`, `total_biaya`, `biaya_tambahan`, `catatan_pelanggan`, `status_penitipan`, `tgl_disetujui`, `tgl_masuk_aktual`, `tgl_keluar_aktual`, `alasan_penolakan`, `created_at`, `updated_at`) VALUES
(138, 'BK-20260714-443B', 6, NULL, 'online', 1, '2026-07-14 03:04:51', '2026-07-14', '2026-07-15', 250000.00, 0.00, NULL, 'Selesai', '2026-07-14 03:07:37', '2026-07-14 03:07:41', '2026-07-14 03:08:06', NULL, '2026-07-14 03:04:51', '2026-07-14 03:08:06');

-- --------------------------------------------------------

--
-- Table structure for table `penitipan_hewan`
--

CREATE TABLE `penitipan_hewan` (
  `id_penitipan_hewan` int(11) NOT NULL,
  `id_penitipan` int(11) NOT NULL,
  `id_hewan` int(11) DEFAULT NULL,
  `id_kandang` int(11) NOT NULL,
  `tgl_mulai` date DEFAULT NULL,
  `tgl_selesai` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `tgl_diambil` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `penitipan_hewan`
--

INSERT INTO `penitipan_hewan` (`id_penitipan_hewan`, `id_penitipan`, `id_hewan`, `id_kandang`, `tgl_mulai`, `tgl_selesai`, `created_at`, `tgl_diambil`) VALUES
(557, 138, 59, 2, '2026-07-14', '2026-07-14', '2026-07-14 03:07:09', '2026-07-14'),
(558, 138, 60, 2, '2026-07-14', '2026-07-14', '2026-07-14 03:07:09', '2026-07-14'),
(559, 138, 61, 2, '2026-07-14', '2026-07-14', '2026-07-14 03:07:09', '2026-07-14'),
(560, 138, 62, 3, '2026-07-14', '2026-07-14', '2026-07-14 03:07:09', '2026-07-14'),
(561, 138, 63, 1, '2026-07-14', '2026-07-14', '2026-07-14 03:07:09', '2026-07-14');

-- --------------------------------------------------------

--
-- Table structure for table `profil_toko`
--

CREATE TABLE `profil_toko` (
  `id_profil` int(11) NOT NULL,
  `nama_toko` varchar(100) DEFAULT 'Njuah Vet',
  `alamat` varchar(255) DEFAULT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `sejarah` text DEFAULT NULL,
  `jam_operasional` varchar(255) DEFAULT NULL,
  `google_maps` varchar(500) DEFAULT NULL,
  `foto_toko` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `profil_toko`
--

INSERT INTO `profil_toko` (`id_profil`, `nama_toko`, `alamat`, `no_telp`, `email`, `website`, `deskripsi`, `sejarah`, `jam_operasional`, `google_maps`, `foto_toko`) VALUES
(1, 'Njuah Vet Siantar', 'Jl. Veteriner No. 1, Pematangsiantar', '+62 812-3456-7890', 'info@njuahvetsiantar.com', NULL, 'Tempat penitipan hewan peliharaan terpercaya di Pematangsiantar. Kami khusus melayani penitipan anjing dan kucing dengan fasilitas kandang modern, pemantauan CCTV 24 jam, dan perawatan oleh staf profesional berpengalaman.', '', 'Senin-Jumat: 07.00-20.00 WIB, Sabtu: 07.00-18.00 WIB, Minggu: 08.00-16.00 WIB', '', 'profil_1782652089.png');

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_perpanjangan`
--

CREATE TABLE `riwayat_perpanjangan` (
  `id_perpanjangan` int(11) NOT NULL,
  `kode_pembayaran` varchar(30) DEFAULT NULL,
  `id_penitipan` int(11) NOT NULL,
  `id_kandang` int(11) NOT NULL,
  `tgl_perpanjangan` date NOT NULL,
  `tgl_selesai_lama` date NOT NULL,
  `tgl_selesai_baru` date NOT NULL,
  `biaya_tambahan` decimal(12,2) NOT NULL,
  `status` enum('Menunggu Konfirmasi','Disetujui','Ditolak') DEFAULT 'Menunggu Konfirmasi',
  `nama_pembayar` varchar(100) DEFAULT NULL,
  `jumlah_bayar` decimal(12,2) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `id_metode` int(11) DEFAULT NULL,
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `catatan_admin` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `status_harian`
--

CREATE TABLE `status_harian` (
  `id_status` int(11) NOT NULL,
  `id_penitipan` int(11) NOT NULL COMMENT 'FK → penitipan.id_penitipan',
  `id_hewan` int(11) DEFAULT NULL,
  `id_user_admin` int(11) DEFAULT NULL COMMENT 'FK → users.id_user (role=Admin, pelapor)',
  `tgl_update` date NOT NULL COMMENT 'Tanggal laporan kondisi',
  `jam_update` time NOT NULL DEFAULT '08:00:00' COMMENT 'Jam pembaruan laporan',
  `kondisi_kesehatan` enum('Sangat Baik','Baik','Cukup','Kurang Baik','Sakit') NOT NULL COMMENT 'Kondisi kesehatan hewan hari ini',
  `pola_makan` enum('Lahap','Normal','Kurang Nafsu Makan','Tidak Mau Makan') NOT NULL COMMENT 'Pola makan hewan hari ini',
  `aktivitas` enum('Sangat Aktif','Aktif','Normal','Lesu','Tidak Aktif') NOT NULL DEFAULT 'Normal' COMMENT 'Tingkat aktivitas hewan',
  `catatan_perawat` text DEFAULT NULL COMMENT 'Catatan khusus dari perawat',
  `foto_kondisi` varchar(255) DEFAULT NULL COMMENT 'Path/URL foto kondisi hewan hari ini',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabel laporan kondisi harian hewan selama penitipan';

-- --------------------------------------------------------

--
-- Table structure for table `testimoni`
--

CREATE TABLE `testimoni` (
  `id_testimoni` int(11) NOT NULL,
  `id_penitipan` int(11) NOT NULL COMMENT 'FK ke penitipan',
  `id_user` int(11) NOT NULL COMMENT 'FK ke users',
  `rating` tinyint(1) NOT NULL CHECK (`rating` between 1 and 5),
  `komentar` text DEFAULT NULL,
  `foto_testimoni` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL COMMENT 'Nama lengkap pengguna',
  `username` varchar(50) DEFAULT NULL COMMENT 'Username unik, wajib untuk Admin',
  `email` varchar(150) NOT NULL COMMENT 'Email untuk login & notifikasi',
  `password` varchar(255) NOT NULL COMMENT 'Password terenkripsi (bcrypt)',
  `no_telp` varchar(15) NOT NULL COMMENT 'Nomor WhatsApp / telepon aktif',
  `alamat` text DEFAULT NULL COMMENT 'Alamat lengkap (khusus Pelanggan)',
  `foto_profil` varchar(255) DEFAULT NULL COMMENT 'Path/URL foto profil',
  `role` enum('Super Admin','Admin','Pelanggan') NOT NULL DEFAULT 'Pelanggan' COMMENT 'Hak akses pengguna dalam sistem',
  `status_akun` enum('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif' COMMENT 'Status keaktifan akun',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabel terpadu semua pengguna sistem (Admin & Pelanggan)';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama_lengkap`, `username`, `email`, `password`, `no_telp`, `alamat`, `foto_profil`, `role`, `status_akun`, `created_at`, `updated_at`) VALUES
(1, 'Super Administrator', 'superadmin', 'superadmin@njuahvetsiantar.com', '$2y$10$R15xc4vBIfWY06cJZaKtkukKMYprU9KZ05w8ShHCUShlDsYkENvy.', '081234567890', NULL, 'profil_1_1785507803.jpg', 'Super Admin', 'Aktif', '2026-06-16 06:55:10', '2026-07-31 21:23:23'),
(2, 'Budi Santoso', 'budi.admin', 'budi.admin@njuahvetsiantar.com', '$2y$12$eImiTXuWVxfM37uY4JANjOe5XwbMjyE7d2N2Vz6FbVXzXrH5r1Zia', '082345678901', NULL, NULL, 'Admin', 'Aktif', '2026-06-16 06:55:10', '2026-06-16 06:55:10'),
(3, 'Putra', NULL, 'putra@gmail.com', '$2y$10$/k3mPT1qVE/VICTJUVoX.OzQNMyqYjHygHEWqPJyKz0SCGYffq5xC', '081298765432', 'Jl. Merdeka No. 12, Siantar Utara, Pematangsiantar', NULL, 'Pelanggan', 'Aktif', '2026-06-16 06:55:10', '2026-06-17 00:11:12'),
(4, 'Dewi Fortuna Nasution', NULL, 'dewi.fortuna@email.com', '$2y$12$eImiTXuWVxfM37uY4JANjOe5XwbMjyE7d2N2Vz6FbVXzXrH5r1Zia', '082187654321', 'Jl. Sutomo No. 45, Siantar Timur, Pematangsiantar', NULL, 'Pelanggan', 'Aktif', '2026-06-16 06:55:10', '2026-06-16 06:55:10'),
(5, 'Ahmad Fauzi Siregar', NULL, 'ahmad.fauzi@email.com', '$2y$12$eImiTXuWVxfM37uY4JANjOe5XwbMjyE7d2N2Vz6FbVXzXrH5r1Zia', '083176543210', 'Jl. Adam Malik No. 8, Siantar Barat, Pematangsiantar', NULL, 'Pelanggan', 'Aktif', '2026-06-16 06:55:10', '2026-06-16 06:55:10'),
(6, 'iccon', NULL, 'iccon@gmail.com', '$2y$12$Cp1vuJrP4cuwB/vsqg0DFeuw/zXO3vc.vZuxnxH5.yfC1fMvosbya', '08741852963', 'Huta Bayu', NULL, 'Pelanggan', 'Aktif', '2026-06-19 06:01:49', '2026-06-19 06:01:49'),
(7, 'stubby', NULL, 'stuuby@gmail.com', '$2y$12$Yy4553nbC5CfqJv8P2vmjuNB.d7Z4ihL2UBgxr4a5rCR92MYhlIGC', '0895347418737', 'Jalan Bayam', NULL, 'Pelanggan', 'Aktif', '2026-06-22 15:50:13', '2026-06-22 15:50:13'),
(8, 'ud_hana', '', 'ud_hana@gmail.com', '$2y$10$xbXkC.em5QxRNMEfhyifDeVdoInKfGtoCfQ0JFRlQ1i/tC4fIXiiG', '0899992838', 'Jalan Jalan', NULL, 'Pelanggan', 'Aktif', '2026-06-27 23:50:19', '2026-06-27 23:50:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `banner`
--
ALTER TABLE `banner`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `booking_lock`
--
ALTER TABLE `booking_lock`
  ADD PRIMARY KEY (`id_lock`),
  ADD UNIQUE KEY `uq_kandang_session_status` (`id_kandang`,`session_id`,`status`),
  ADD KEY `idx_user` (`id_user`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `cara_penitipan`
--
ALTER TABLE `cara_penitipan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hewan`
--
ALTER TABLE `hewan`
  ADD PRIMARY KEY (`id_hewan`),
  ADD KEY `idx_hewan_user` (`id_user`),
  ADD KEY `idx_hewan_jenis` (`jenis_hewan`);

--
-- Indexes for table `kandang`
--
ALTER TABLE `kandang`
  ADD PRIMARY KEY (`id_kandang`),
  ADD UNIQUE KEY `uq_kode_kandang` (`kode_kandang`),
  ADD KEY `idx_kandang_tipe` (`tipe_hewan`);

--
-- Indexes for table `keunggulan`
--
ALTER TABLE `keunggulan`
  ADD PRIMARY KEY (`id_keunggulan`);

--
-- Indexes for table `metode_pembayaran`
--
ALTER TABLE `metode_pembayaran`
  ADD PRIMARY KEY (`id_metode`);

--
-- Indexes for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id_pelanggan`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD UNIQUE KEY `uq_kode_pembayaran` (`kode_pembayaran`),
  ADD UNIQUE KEY `uq_pembayaran_penitipan` (`id_penitipan`),
  ADD KEY `idx_pembayaran_admin` (`dikonfirmasi_oleh`),
  ADD KEY `idx_pembayaran_status` (`status_bayar`),
  ADD KEY `id_metode` (`id_metode`);

--
-- Indexes for table `pengaturan_umum`
--
ALTER TABLE `pengaturan_umum`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `penitipan`
--
ALTER TABLE `penitipan`
  ADD PRIMARY KEY (`id_penitipan`),
  ADD UNIQUE KEY `uq_kode_booking` (`kode_booking`),
  ADD KEY `idx_penitipan_admin` (`id_user_admin`),
  ADD KEY `idx_penitipan_status` (`status_penitipan`),
  ADD KEY `idx_penitipan_tgl_mulai` (`tgl_mulai`),
  ADD KEY `idx_penitipan_user` (`id_user`),
  ADD KEY `idx_penitipan_pelanggan` (`id_pelanggan`);

--
-- Indexes for table `penitipan_hewan`
--
ALTER TABLE `penitipan_hewan`
  ADD PRIMARY KEY (`id_penitipan_hewan`),
  ADD KEY `id_penitipan` (`id_penitipan`),
  ADD KEY `id_hewan` (`id_hewan`),
  ADD KEY `id_kandang` (`id_kandang`);

--
-- Indexes for table `profil_toko`
--
ALTER TABLE `profil_toko`
  ADD PRIMARY KEY (`id_profil`);

--
-- Indexes for table `riwayat_perpanjangan`
--
ALTER TABLE `riwayat_perpanjangan`
  ADD PRIMARY KEY (`id_perpanjangan`),
  ADD KEY `id_penitipan` (`id_penitipan`),
  ADD KEY `id_metode` (`id_metode`),
  ADD KEY `fk_perpanjangan_kandang` (`id_kandang`);

--
-- Indexes for table `status_harian`
--
ALTER TABLE `status_harian`
  ADD PRIMARY KEY (`id_status`),
  ADD UNIQUE KEY `uq_status_per_hewan_tanggal` (`id_penitipan`,`id_hewan`,`tgl_update`),
  ADD KEY `idx_status_penitipan` (`id_penitipan`),
  ADD KEY `idx_status_admin` (`id_user_admin`),
  ADD KEY `idx_status_tgl` (`tgl_update`),
  ADD KEY `idx_status_hewan` (`id_hewan`);

--
-- Indexes for table `testimoni`
--
ALTER TABLE `testimoni`
  ADD PRIMARY KEY (`id_testimoni`),
  ADD UNIQUE KEY `uq_testimoni_penitipan` (`id_penitipan`),
  ADD KEY `idx_testimoni_user` (`id_user`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_status` (`status_akun`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `banner`
--
ALTER TABLE `banner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `booking_lock`
--
ALTER TABLE `booking_lock`
  MODIFY `id_lock` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cara_penitipan`
--
ALTER TABLE `cara_penitipan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `hewan`
--
ALTER TABLE `hewan`
  MODIFY `id_hewan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `kandang`
--
ALTER TABLE `kandang`
  MODIFY `id_kandang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `keunggulan`
--
ALTER TABLE `keunggulan`
  MODIFY `id_keunggulan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `metode_pembayaran`
--
ALTER TABLE `metode_pembayaran`
  MODIFY `id_metode` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id_pelanggan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `pengaturan_umum`
--
ALTER TABLE `pengaturan_umum`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `penitipan`
--
ALTER TABLE `penitipan`
  MODIFY `id_penitipan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `penitipan_hewan`
--
ALTER TABLE `penitipan_hewan`
  MODIFY `id_penitipan_hewan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=565;

--
-- AUTO_INCREMENT for table `profil_toko`
--
ALTER TABLE `profil_toko`
  MODIFY `id_profil` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `riwayat_perpanjangan`
--
ALTER TABLE `riwayat_perpanjangan`
  MODIFY `id_perpanjangan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `status_harian`
--
ALTER TABLE `status_harian`
  MODIFY `id_status` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `testimoni`
--
ALTER TABLE `testimoni`
  MODIFY `id_testimoni` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `hewan`
--
ALTER TABLE `hewan`
  ADD CONSTRAINT `fk_hewan_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE;

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `fk_pembayaran_admin` FOREIGN KEY (`dikonfirmasi_oleh`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pembayaran_penitipan` FOREIGN KEY (`id_penitipan`) REFERENCES `penitipan` (`id_penitipan`) ON UPDATE CASCADE,
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_metode`) REFERENCES `metode_pembayaran` (`id_metode`) ON UPDATE CASCADE;

--
-- Constraints for table `penitipan`
--
ALTER TABLE `penitipan`
  ADD CONSTRAINT `fk_penitipan_admin` FOREIGN KEY (`id_user_admin`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `penitipan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE,
  ADD CONSTRAINT `penitipan_ibfk_2` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON UPDATE CASCADE;

--
-- Constraints for table `penitipan_hewan`
--
ALTER TABLE `penitipan_hewan`
  ADD CONSTRAINT `penitipan_hewan_ibfk_1` FOREIGN KEY (`id_penitipan`) REFERENCES `penitipan` (`id_penitipan`) ON DELETE CASCADE,
  ADD CONSTRAINT `penitipan_hewan_ibfk_2` FOREIGN KEY (`id_hewan`) REFERENCES `hewan` (`id_hewan`),
  ADD CONSTRAINT `penitipan_hewan_ibfk_3` FOREIGN KEY (`id_kandang`) REFERENCES `kandang` (`id_kandang`);

--
-- Constraints for table `riwayat_perpanjangan`
--
ALTER TABLE `riwayat_perpanjangan`
  ADD CONSTRAINT `fk_perpanjangan_kandang` FOREIGN KEY (`id_kandang`) REFERENCES `kandang` (`id_kandang`),
  ADD CONSTRAINT `riwayat_perpanjangan_ibfk_1` FOREIGN KEY (`id_penitipan`) REFERENCES `penitipan` (`id_penitipan`),
  ADD CONSTRAINT `riwayat_perpanjangan_ibfk_3` FOREIGN KEY (`id_metode`) REFERENCES `metode_pembayaran` (`id_metode`);

--
-- Constraints for table `status_harian`
--
ALTER TABLE `status_harian`
  ADD CONSTRAINT `fk_status_admin` FOREIGN KEY (`id_user_admin`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_status_penitipan` FOREIGN KEY (`id_penitipan`) REFERENCES `penitipan` (`id_penitipan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `testimoni`
--
ALTER TABLE `testimoni`
  ADD CONSTRAINT `fk_testimoni_penitipan` FOREIGN KEY (`id_penitipan`) REFERENCES `penitipan` (`id_penitipan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_testimoni_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

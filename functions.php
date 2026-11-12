<?php
// ============================================================
// FILE: functions.php
// FUNGSI: Kumpulan fungsi untuk sistem notifikasi
// ============================================================
require_once __DIR__ . '/koneksi.php';

// ============================================================
// FUNGSI NOTIFIKASI UNTUK SIDEBAR (ADMIN)
// ============================================================
function getNotifikasiKonfirmasi($koneksi) {
    // 1. Hitung pembayaran yang menunggu
    $q1 = "SELECT COUNT(*) AS total FROM pembayaran WHERE status_bayar = 'Menunggu Konfirmasi'";
    $r1 = mysqli_query($koneksi, $q1);
    $d1 = mysqli_fetch_assoc($r1);
    $total_pembayaran = $d1['total'] ?? 0;

    // 2. Hitung perpanjangan yang menunggu
    $q2 = "SELECT COUNT(*) AS total FROM riwayat_perpanjangan WHERE status = 'Menunggu Konfirmasi'";
    $r2 = mysqli_query($koneksi, $q2);
    $d2 = mysqli_fetch_assoc($r2);
    $total_perpanjangan = $d2['total'] ?? 0;

    // 3. Kembalikan hasil
    return [
        'pembayaran' => $total_pembayaran,
        'perpanjangan' => $total_perpanjangan,
        'total' => $total_pembayaran + $total_perpanjangan
    ];
}

// ============================================================
// FUNGSI NOTIFIKASI DETAIL UNTUK NAVBAR (ADMIN)
// ============================================================
function getNotifikasiDetail($koneksi) {
    $notifications = [];

    // 1. Pembayaran menunggu konfirmasi
    $q1 = "SELECT id_pembayaran, kode_pembayaran, tgl_bayar, 
                  'pembayaran' AS jenis, 
                  CONCAT('Pembayaran ', kode_pembayaran, ' menunggu konfirmasi') AS pesan
           FROM pembayaran 
           WHERE status_bayar = 'Menunggu Konfirmasi'
           ORDER BY tgl_bayar DESC";
    $r1 = mysqli_query($koneksi, $q1);
    while ($row = mysqli_fetch_assoc($r1)) {
        $row['link'] = 'konfirmasi_pembayaran.php?id=' . $row['id_pembayaran'];
        $row['waktu'] = $row['tgl_bayar'];
        $notifications[] = $row;
    }

    // 2. Perpanjangan menunggu konfirmasi
    $q2 = "SELECT id_perpanjangan, kode_pembayaran, tgl_perpanjangan,
                  'perpanjangan' AS jenis,
                  CONCAT('Perpanjangan ', kode_pembayaran, ' menunggu konfirmasi') AS pesan
           FROM riwayat_perpanjangan 
           WHERE status = 'Menunggu Konfirmasi'
           ORDER BY tgl_perpanjangan DESC";
    $r2 = mysqli_query($koneksi, $q2);
    while ($row = mysqli_fetch_assoc($r2)) {
        $row['link'] = 'konfirmasi_perpanjangan.php?id=' . $row['id_perpanjangan'];
        $row['waktu'] = $row['tgl_perpanjangan'];
        $notifications[] = $row;
    }

    // 3. Booking disetujui tapi hewan belum dititip
    $q3 = "SELECT id_penitipan, kode_booking, tgl_disetujui,
                  'booking_belum_dititip' AS jenis,
                  CONCAT('Booking ', kode_booking, ' disetujui, hewan belum dititip') AS pesan
           FROM penitipan 
           WHERE status_penitipan = 'Disetujui' 
             AND tgl_masuk_aktual IS NULL
           ORDER BY tgl_disetujui DESC";
    $r3 = mysqli_query($koneksi, $q3);
    while ($row = mysqli_fetch_assoc($r3)) {
        $row['link'] = 'detail_booking.php?id=' . $row['id_penitipan'];
        $row['waktu'] = $row['tgl_disetujui'];
        $notifications[] = $row;
    }

    // Urutkan berdasarkan waktu terbaru
    usort($notifications, function($a, $b) {
        return strtotime($b['waktu']) - strtotime($a['waktu']);
    });

    // Batasi 10 notifikasi terbaru
    $notifications = array_slice($notifications, 0, 10);

    return [
        'total' => count($notifications),
        'data'  => $notifications
    ];
}
?>
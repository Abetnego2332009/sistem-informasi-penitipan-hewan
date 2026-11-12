<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

$no_telp = mysqli_real_escape_string($koneksi, trim($_GET['no_telp'] ?? ''));

$response = [
    'status' => 'baru',
    'pelanggan' => null,
    'hewan' => []
];

if (!empty($no_telp)) {
    // Cari data pelanggan berdasarkan nomor telepon
    $query_p = mysqli_query($koneksi, "SELECT * FROM pelanggan WHERE no_telp = '$no_telp' LIMIT 1");
    $pelanggan = mysqli_fetch_assoc($query_p);

    if ($pelanggan) {
        $response['status'] = 'lama';
        $response['pelanggan'] = $pelanggan;

        // Ambil data hewan milik pelanggan lama ini
        $id_pelanggan = $pelanggan['id_pelanggan'];
        $query_h = mysqli_query($koneksi, "SELECT * FROM hewan WHERE id_pelanggan = $id_pelanggan");
        while ($h = mysqli_fetch_assoc($query_h)) {
            $response['hewan'][] = $h;
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
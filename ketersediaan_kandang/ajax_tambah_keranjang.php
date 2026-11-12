<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

header('Content-Type: application/json');

if (!isset($_POST['id_kandang']) || empty($_POST['id_kandang'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID Kandang tidak valid!']);
    exit;
}

$id_kandang = (int)$_POST['id_kandang'];
$id_user = $_SESSION['id_user'] ?? 0;
$session_id = session_id();

mysqli_query($koneksi, "DELETE FROM booking_lock WHERE expires_at < NOW()");

// Ambil stok fisik
$query = "SELECT stok FROM kandang WHERE id_kandang = $id_kandang AND is_active = 1";
$res = mysqli_query($koneksi, $query);
$kandang = mysqli_fetch_assoc($res);
if (!$kandang) {
    echo json_encode(['status' => 'error', 'message' => 'Kandang tidak aktif atau tidak ditemukan!']);
    exit;
}
$stok_fisik = (int)$kandang['stok'];

// Total lock semua user (quantity)
$query_total = "SELECT SUM(quantity) AS total_lock FROM booking_lock 
                 WHERE id_kandang = $id_kandang 
                 AND status IN ('pending_hewan','pending_pembayaran') AND expires_at > NOW()";
$res_total = mysqli_query($koneksi, $query_total);
$row_total = mysqli_fetch_assoc($res_total);
$total_lock_all = (int)($row_total['total_lock'] ?? 0);

if (($total_lock_all + 1) > $stok_fisik) {
    echo json_encode(['status' => 'error', 'message' => 'Maaf, stok kandang ini sudah habis (semua unit sedang dipesan).']);
    exit;
}

// Cek lock milik user ini
$query_user = "SELECT id_lock, quantity FROM booking_lock 
                WHERE id_kandang = $id_kandang 
                AND (session_id = '$session_id' OR id_user = $id_user) 
                AND status IN ('pending_hewan','pending_pembayaran') AND expires_at > NOW()";
$res_user = mysqli_query($koneksi, $query_user);
$lock_user = mysqli_fetch_assoc($res_user);

$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

if ($lock_user) {
    $id_lock = (int)$lock_user['id_lock'];
    $new_qty = (int)$lock_user['quantity'] + 1;
    mysqli_query($koneksi, "UPDATE booking_lock SET quantity = $new_qty, expires_at = '$expires_at' WHERE id_lock = $id_lock");
} else {
    mysqli_query($koneksi, "INSERT INTO booking_lock (id_kandang, id_user, session_id, status, quantity, expires_at) 
                            VALUES ($id_kandang, $id_user, '$session_id', 'pending_hewan', 1, '$expires_at')");
}

if (isset($_SESSION['booking_kandang'][$id_kandang])) {
    $_SESSION['booking_kandang'][$id_kandang]++;
} else {
    $_SESSION['booking_kandang'][$id_kandang] = 1;
}

echo json_encode(['status' => 'success', 'message' => 'Kandang berhasil ditambahkan!']);
exit;
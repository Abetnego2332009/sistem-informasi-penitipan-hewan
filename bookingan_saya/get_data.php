<?php
// ============================================================
// AMBIL DATA TESTIMONI (JSON) - Root: vet/
// ============================================================
session_start();

// Path ke koneksi.php: naik 1 level ke folder vet/
require_once __DIR__ . '/../koneksi.php';

header('Content-Type: application/json');

$id_user = $_SESSION['id_user'] ?? 0;
if ($id_user == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Harus login.']);
    exit;
}

$id_penitipan = isset($_GET['id_penitipan']) ? intval($_GET['id_penitipan']) : 0;
if ($id_penitipan <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
    exit;
}

$query = "SELECT rating, komentar, foto_testimoni, created_at
          FROM testimoni
          WHERE id_penitipan = $id_penitipan AND id_user = $id_user";
$result = mysqli_query($koneksi, $query);
if (!$result) {
    echo json_encode(['status' => 'error', 'message' => 'Query error: ' . mysqli_error($koneksi)]);
    exit;
}
$testimoni = mysqli_fetch_assoc($result);

if ($testimoni) {
    echo json_encode(['status' => 'success', 'testimoni' => $testimoni]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Testimoni tidak ditemukan.']);
}
exit;
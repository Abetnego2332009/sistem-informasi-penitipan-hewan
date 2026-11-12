<?php
// ============================================================
// PROSES SIMPAN TESTIMONI - Root: vet/
// ============================================================
session_start();

// Path ke koneksi.php: naik 1 level ke folder vet/
require_once __DIR__ . '/../koneksi.php';

// Header JSON
header('Content-Type: application/json');

// Cek login
$id_user = $_SESSION['id_user'] ?? 0;
if ($id_user == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login.']);
    exit;
}

// Ambil data POST
$id_penitipan = isset($_POST['id_penitipan']) ? intval($_POST['id_penitipan']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$komentar = isset($_POST['komentar']) ? trim($_POST['komentar']) : '';

if ($id_penitipan <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid.']);
    exit;
}

// Validasi booking
$query_cek = "SELECT p.id_penitipan, p.status_penitipan,
                     (SELECT COUNT(*) FROM testimoni t WHERE t.id_penitipan = p.id_penitipan) AS sudah_testimoni
              FROM penitipan p
              WHERE p.id_penitipan = $id_penitipan AND p.id_user = $id_user";
$result_cek = mysqli_query($koneksi, $query_cek);
if (!$result_cek) {
    echo json_encode(['status' => 'error', 'message' => 'Query error: ' . mysqli_error($koneksi)]);
    exit;
}
$data = mysqli_fetch_assoc($result_cek);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Booking tidak ditemukan.']);
    exit;
}
if ($data['status_penitipan'] != 'Selesai') {
    echo json_encode(['status' => 'error', 'message' => 'Booking belum selesai.']);
    exit;
}
if ($data['sudah_testimoni'] > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Anda sudah memberi testimoni untuk booking ini.']);
    exit;
}

// Upload foto (opsional)
$nama_foto = null;
if (isset($_FILES['foto_testimoni']) && $_FILES['foto_testimoni']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES['foto_testimoni']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        echo json_encode(['status' => 'error', 'message' => 'Format foto tidak didukung.']);
        exit;
    }
    $nama_foto = 'testimoni_' . $id_penitipan . '_' . time() . '.' . $ext;
    $target_dir = __DIR__ . '/../uploads/testimoni/'; // vet/uploads/testimoni/
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal membuat folder uploads.']);
            exit;
        }
    }
    if (!move_uploaded_file($_FILES['foto_testimoni']['tmp_name'], $target_dir . $nama_foto)) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah foto.']);
        exit;
    }
    $nama_foto = 'uploads/testimoni/' . $nama_foto;
}

// Insert ke database
// Insert ke database
$komentar_escaped = mysqli_real_escape_string($koneksi, $komentar);
$foto_value = $nama_foto ? "'" . mysqli_real_escape_string($koneksi, $nama_foto) . "'" : "NULL";

$query_insert = "INSERT INTO testimoni (id_penitipan, id_user, rating, komentar, foto_testimoni, status, created_at, updated_at)
                 VALUES ($id_penitipan, $id_user, $rating, '$komentar_escaped', $foto_value, 'Disetujui', NOW(), NOW())";

if (mysqli_query($koneksi, $query_insert)) {
    echo json_encode(['status' => 'success', 'message' => 'Testimoni berhasil dikirim!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan testimoni: ' . mysqli_error($koneksi)]);
}
exit;
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$role = $_SESSION['role'] ?? 'Pelanggan';
if ($role != 'Super Admin' && $role != 'Admin') {
    echo "<script>alert('Akses ditolak! Hanya Admin.'); window.location.href='../dashboard.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id_user = $_SESSION['id_user'];
$id_penitipan = filter_var($_POST['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);
$hewan_data = $_POST['hewan'] ?? [];

if (!$id_penitipan) {
    echo "<script>alert('ID penitipan tidak valid!'); window.location.href='index.php';</script>";
    exit;
}

if (empty($hewan_data)) {
    echo "<script>alert('Tidak ada data hewan!'); window.location.href='index.php';</script>";
    exit;
}

$success_count = 0;
$error_count = 0;
$error_jam = 0; // <-- TAMBAHAN: counter khusus untuk gagal karena jam belum 08:00

// ============================================================
// LOOP PER HEWAN
// ============================================================
foreach ($hewan_data as $index => $data) {  // <-- TAMBAHKAN $index
    $id_hewan = filter_var($data['id_hewan'] ?? 0, FILTER_VALIDATE_INT);
    $tgl_update = $data['tgl_update'] ?? date('Y-m-d');
    $jam_update = $data['jam_update'] ?? date('H:i:s');
    $kondisi = $data['kondisi_kesehatan'] ?? 'Baik';
    $pola_makan = $data['pola_makan'] ?? 'Normal';
    $aktivitas = $data['aktivitas'] ?? 'Normal';
    $catatan = mysqli_real_escape_string($koneksi, trim($data['catatan_perawat'] ?? ''));

    if (!$id_hewan) continue;

     // ============================================================
    // VALIDASI: JAM UPDATE HARUS DI ANTARA 08:00 - 20:00 (TAMBAHAN)
    // ============================================================
    $jam_cek = strlen($jam_update) === 5 ? $jam_update . ':00' : $jam_update;
    if ($jam_cek < '08:00:00' || $jam_cek > '20:00:00') {
        $error_jam++;
        continue;
    }
 

    // Upload foto per hewan
    $foto_path = null;
    if (isset($_FILES['hewan']['name'][$index]['foto_kondisi']) && 
        $_FILES['hewan']['error'][$index]['foto_kondisi'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['hewan']['name'][$index]['foto_kondisi'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $nama_file = 'status_' . $id_penitipan . '_' . $id_hewan . '_' . date('Ymd_His') . '.' . $ext;
            $upload_dir = __DIR__ . '/../uploads/status_harian/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            if (move_uploaded_file($_FILES['hewan']['tmp_name'][$index]['foto_kondisi'], $upload_dir . $nama_file)) {
                $foto_path = 'uploads/status_harian/' . $nama_file;
            }
        }
    }

    // ============================================================
    // CEK DUPLIKAT PER HEWAN + TANGGAL (BUKAN PER BOOKING!)
    // ============================================================
    $cek = mysqli_query($koneksi, "
        SELECT id_status FROM status_harian 
        WHERE id_penitipan = $id_penitipan 
        AND id_hewan = $id_hewan 
        AND tgl_update = '$tgl_update'
    ");
    
    if (mysqli_num_rows($cek) > 0) {
        $error_count++;
        continue;
    }

    // ============================================================
    // INSERT STATUS PER HEWAN
    // ============================================================
    $query_insert = "
        INSERT INTO status_harian (
            id_penitipan, 
            id_hewan, 
            id_user_admin, 
            tgl_update, 
            jam_update,
            kondisi_kesehatan, 
            pola_makan, 
            aktivitas, 
            catatan_perawat, 
            foto_kondisi
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt = mysqli_prepare($koneksi, $query_insert);
    mysqli_stmt_bind_param($stmt, 'iiisssssss', 
        $id_penitipan, 
        $id_hewan, 
        $id_user, 
        $tgl_update, 
        $jam_update,
        $kondisi, 
        $pola_makan, 
        $aktivitas, 
        $catatan, 
        $foto_path
    );

    if (mysqli_stmt_execute($stmt)) {
        $success_count++;
    } else {
        $error_count++;
    }
    mysqli_stmt_close($stmt);
}

// ============================================================
// REDIRECT
// ============================================================
if ($success_count > 0) {
    $pesan = "$success_count status berhasil disimpan!";
    if ($error_count > 0) $pesan .= " $error_count gagal (duplikat).";
    if ($error_jam > 0) $pesan .= " $error_jam gagal (jam sebelum 08:00)."; // <-- TAMBAHAN
    echo "<script>alert('$pesan'); window.location.href='list_hewan.php?id_penitipan=$id_penitipan';</script>";
} else {
    $pesan = "Gagal menyimpan semua status!";
    if ($error_jam > 0) $pesan .= " Tidak Bisa Mengaupdate Data Sebelum 08:00."; // <-- TAMBAHAN
    echo "<script>alert('$pesan'); window.history.back();</script>";
}
exit;
<?php
session_start();
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dan bersihkan dari spasi/karakter berbahaya
    $nama     = mysqli_real_escape_string($koneksi, trim($_POST['nama']));
    $email    = mysqli_real_escape_string($koneksi, trim($_POST['email']));
    $telepon  = mysqli_real_escape_string($koneksi, trim($_POST['telepon']));
    $alamat   = mysqli_real_escape_string($koneksi, trim($_POST['alamat']));
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // 1. Validasi konfirmasi password
    if ($password !== $password_confirm) {
        echo "<script>alert('Konfirmasi password tidak cocok!'); window.history.back();</script>";
        exit;
    }

    // 2. Validasi panjang password
    if (strlen($password) < 8) {
        echo "<script>alert('Password minimal harus 8 karakter!'); window.history.back();</script>";
        exit;
    }

    // 3. Cek apakah email sudah terdaftar di database
    $cek_email = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($cek_email, "s", $email);
    mysqli_stmt_execute($cek_email);
    mysqli_stmt_store_result($cek_email);

    if (mysqli_stmt_num_rows($cek_email) > 0) {
        echo "<script>alert('Email sudah terdaftar! Gunakan email lain.'); window.history.back();</script>";
        exit;
    }
    mysqli_stmt_close($cek_email);

    // 4. Enkripsi password menggunakan BCRYPT sesuai data dump
    $password_hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // Pengaturan default untuk registrasi pelanggan baru
    $role_default = 'Pelanggan';
    $status_default = 'Aktif';
    $username_default = null; // Mengikuti struktur data dump pelanggan Anda

    // 5. Query INSERT menggunakan Prepared Statement untuk keamanan
    $query_insert = "INSERT INTO users (nama_lengkap, username, email, password, no_telp, alamat, role, status_akun) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $query_insert);
    
    mysqli_stmt_bind_param($stmt, "ssssssss", $nama, $username_default, $email, $password_hashed, $telepon, $alamat, $role_default, $status_default);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Pendaftaran akun berhasil! Silakan login.'); window.location='login.php';</script>";
        exit;
    } else {
        echo "<script>alert('Terjadi kesalahan sistem, gagal mendaftar.'); window.history.back();</script>";
        exit;
    }
    
    mysqli_stmt_close($stmt);
} else {
    // Jika file diakses langsung tanpa metode POST
    header("Location: register.php");
    exit;
}
?>
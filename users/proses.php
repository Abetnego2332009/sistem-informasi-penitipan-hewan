<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] != 'Super Admin' && $_SESSION['role'] != 'Admin')) {
    echo "<script>alert('Akses ditolak!'); window.location='../login.php';</script>";
    exit;
}

$action = $_POST['action'] ?? '';
$id_user = filter_var($_POST['id_user'] ?? null, FILTER_VALIDATE_INT);

// Sanitasi input
$nama_lengkap = mysqli_real_escape_string($koneksi, trim($_POST['nama_lengkap'] ?? ''));
$username = mysqli_real_escape_string($koneksi, trim($_POST['username'] ?? ''));
$email = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$no_telp = mysqli_real_escape_string($koneksi, trim($_POST['no_telp'] ?? ''));
$alamat = mysqli_real_escape_string($koneksi, trim($_POST['alamat'] ?? ''));
$role = mysqli_real_escape_string($koneksi, $_POST['role'] ?? '');
$status_akun = mysqli_real_escape_string($koneksi, $_POST['status_akun'] ?? 'Aktif');

// Validasi
if (in_array($action, ['insert', 'update'])) {
    if (empty($nama_lengkap) || empty($email) || empty($no_telp) || empty($role)) {
        echo "<script>alert('Nama, Email, Telepon, dan Role wajib diisi!'); window.history.back();</script>";
        exit;
    }

    // Cek email duplikat
    $check_query = "SELECT id_user FROM users WHERE email = '$email'";
    if ($action === 'update') {
        $check_query .= " AND id_user != $id_user";
    }
    $check_res = mysqli_query($koneksi, $check_query);
    if (mysqli_num_rows($check_res) > 0) {
        echo "<script>alert('Email \"$email\" sudah terdaftar!'); window.history.back();</script>";
        exit;
    }

    // Cek username duplikat (jika diisi)
    if (!empty($username)) {
        $check_username = "SELECT id_user FROM users WHERE username = '$username'";
        if ($action === 'update') {
            $check_username .= " AND id_user != $id_user";
        }
        $check_res_username = mysqli_query($koneksi, $check_username);
        if (mysqli_num_rows($check_res_username) > 0) {
            echo "<script>alert('Username \"$username\" sudah terdaftar!'); window.history.back();</script>";
            exit;
        }
    }

    // Validasi role (Super Admin hanya bisa ditambah oleh Super Admin)
    if ($role === 'Super Admin' && $_SESSION['role'] != 'Super Admin') {
        echo "<script>alert('Hanya Super Admin yang bisa menambah/mengubah role Super Admin!'); window.history.back();</script>";
        exit;
    }

    // Validasi password
    if ($action === 'insert' && empty($password)) {
        echo "<script>alert('Password wajib diisi!'); window.history.back();</script>";
        exit;
    }
    if (!empty($password) && strlen($password) < 6) {
        echo "<script>alert('Password minimal 6 karakter!'); window.history.back();</script>";
        exit;
    }
}

switch ($action) {
    case 'insert':
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (nama_lengkap, username, email, password, no_telp, alamat, role, status_akun, created_at, updated_at)
                VALUES ('$nama_lengkap', '$username', '$email', '$hashed_password', '$no_telp', '$alamat', '$role', '$status_akun', NOW(), NOW())";
        if (mysqli_query($koneksi, $sql)) {
            echo "<script>alert('User berhasil ditambahkan!'); window.location='index.php';</script>";
        } else {
            echo "<script>alert('Gagal menambahkan user: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
        }
        break;

    case 'update':
        // Cek apakah user mencoba mengedit dirinya sendiri
        if ($_SESSION['id_user'] == $id_user && $_SESSION['role'] != 'Super Admin') {
            echo "<script>alert('Anda tidak bisa mengubah role/status sendiri!'); window.history.back();</script>";
            exit;
        }

        $sql_password = "";
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql_password = ", password = '$hashed_password'";
        }

        $sql = "UPDATE users SET 
                    nama_lengkap = '$nama_lengkap',
                    username = '$username',
                    email = '$email',
                    no_telp = '$no_telp',
                    alamat = '$alamat',
                    role = '$role',
                    status_akun = '$status_akun'
                    $sql_password,
                    updated_at = NOW()
                WHERE id_user = $id_user";
        if (mysqli_query($koneksi, $sql)) {
            echo "<script>alert('User berhasil diperbarui!'); window.location='index.php';</script>";
        } else {
            echo "<script>alert('Gagal memperbarui user: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
        }
        break;

    case 'delete':
        // Soft delete (nonaktifkan)
        if ($_SESSION['id_user'] == $id_user) {
            echo "<script>alert('Anda tidak bisa menonaktifkan akun sendiri!'); window.location='index.php';</script>";
            exit;
        }
        $sql = "UPDATE users SET status_akun = 'Nonaktif' WHERE id_user = $id_user";
        if (mysqli_query($koneksi, $sql)) {
            echo "<script>alert('User berhasil dinonaktifkan.'); window.location='index.php';</script>";
        } else {
            echo "<script>alert('Gagal menonaktifkan user.'); window.location='index.php';</script>";
        }
        break;

    case 'restore':
        // Aktifkan kembali
        $sql = "UPDATE users SET status_akun = 'Aktif' WHERE id_user = $id_user";
        if (mysqli_query($koneksi, $sql)) {
            echo "<script>alert('User berhasil diaktifkan kembali.'); window.location='index.php';</script>";
        } else {
            echo "<script>alert('Gagal mengaktifkan user.'); window.location='index.php';</script>";
        }
        break;

    default:
        header('Location: index.php');
        break;
}
exit;
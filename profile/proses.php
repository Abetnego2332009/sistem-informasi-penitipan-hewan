<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

// Cek login
if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$id_user = $_SESSION['id_user'];

// ============================================================
// AMBIL DATA USER
// ============================================================
function getUserData($koneksi, $id_user) {
    $query = "SELECT * FROM users WHERE id_user = $id_user";
    $result = mysqli_query($koneksi, $query);
    return mysqli_fetch_assoc($result);
}

// ============================================================
// PROSES UPDATE PROFILE
// ============================================================
if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $nama_lengkap = mysqli_real_escape_string($koneksi, trim($_POST['nama_lengkap'] ?? ''));
    $no_telp = mysqli_real_escape_string($koneksi, trim($_POST['no_telp'] ?? ''));
    $alamat = mysqli_real_escape_string($koneksi, trim($_POST['alamat'] ?? ''));

    if (empty($nama_lengkap) || empty($no_telp)) {
        $_SESSION['profile_error'] = 'Nama lengkap dan nomor telepon wajib diisi!';
        $_SESSION['profile_error_type'] = 'danger';
        header('Location: index.php');
        exit;
    }

    $update = "UPDATE users SET nama_lengkap = '$nama_lengkap', no_telp = '$no_telp', alamat = '$alamat', updated_at = NOW() WHERE id_user = $id_user";
    if (mysqli_query($koneksi, $update)) {
        $_SESSION['profile_success'] = 'Data diri berhasil diperbarui!';
        $_SESSION['profile_success_type'] = 'success';
    } else {
        $_SESSION['profile_error'] = 'Gagal memperbarui data: ' . mysqli_error($koneksi);
        $_SESSION['profile_error_type'] = 'danger';
    }
    header('Location: index.php');
    exit;
}

// ============================================================
// PROSES GANTI PASSWORD
// ============================================================
if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $user = getUserData($koneksi, $id_user);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['profile_error'] = 'Semua field password harus diisi!';
        $_SESSION['profile_error_type'] = 'danger';
        header('Location: index.php');
        exit;
    }

    if (!password_verify($current_password, $user['password'])) {
        $_SESSION['profile_error'] = 'Password saat ini salah!';
        $_SESSION['profile_error_type'] = 'danger';
        header('Location: index.php');
        exit;
    }

    if (strlen($new_password) < 6) {
        $_SESSION['profile_error'] = 'Password baru minimal 6 karakter!';
        $_SESSION['profile_error_type'] = 'danger';
        header('Location: index.php');
        exit;
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['profile_error'] = 'Konfirmasi password tidak cocok!';
        $_SESSION['profile_error_type'] = 'danger';
        header('Location: index.php');
        exit;
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update = "UPDATE users SET password = '$hashed_password', updated_at = NOW() WHERE id_user = $id_user";
    if (mysqli_query($koneksi, $update)) {
        $_SESSION['profile_success'] = 'Password berhasil diubah!';
        $_SESSION['profile_success_type'] = 'success';
    } else {
        $_SESSION['profile_error'] = 'Gagal mengubah password: ' . mysqli_error($koneksi);
        $_SESSION['profile_error_type'] = 'danger';
    }
    header('Location: index.php');
    exit;
}

// ============================================================
// PROSES UPLOAD FOTO
// ============================================================
if (isset($_POST['action']) && $_POST['action'] === 'upload_foto') {
    $user = getUserData($koneksi, $id_user);
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $_SESSION['profile_error'] = 'Format foto tidak didukung! (jpg, jpeg, png, gif, webp)';
            $_SESSION['profile_error_type'] = 'danger';
            header('Location: index.php');
            exit;
        }
        if ($_FILES['foto_profil']['size'] > 2 * 1024 * 1024) {
            $_SESSION['profile_error'] = 'Ukuran foto maksimal 2MB!';
            $_SESSION['profile_error_type'] = 'danger';
            header('Location: index.php');
            exit;
        }
        $upload_dir = __DIR__ . '/../uploads/profil/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        // Hapus foto lama
        if (!empty($user['foto_profil']) && file_exists($upload_dir . $user['foto_profil'])) {
            unlink($upload_dir . $user['foto_profil']);
        }
        $new_file_name = 'profil_' . $id_user . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $upload_dir . $new_file_name)) {
            $update = "UPDATE users SET foto_profil = '$new_file_name', updated_at = NOW() WHERE id_user = $id_user";
            if (mysqli_query($koneksi, $update)) {
                $_SESSION['profile_success'] = 'Foto profil berhasil diupload!';
                $_SESSION['profile_success_type'] = 'success';
            } else {
                $_SESSION['profile_error'] = 'Gagal menyimpan foto ke database.';
                $_SESSION['profile_error_type'] = 'danger';
            }
        } else {
            $_SESSION['profile_error'] = 'Gagal mengupload foto.';
            $_SESSION['profile_error_type'] = 'danger';
        }
    } else {
        $_SESSION['profile_error'] = 'Pilih file foto terlebih dahulu!';
        $_SESSION['profile_error_type'] = 'danger';
    }
    header('Location: index.php');
    exit;
}

// ============================================================
// PROSES HAPUS FOTO
// ============================================================
if (isset($_POST['action']) && $_POST['action'] === 'hapus_foto') {
    $user = getUserData($koneksi, $id_user);
    $upload_dir = __DIR__ . '/../uploads/profil/';
    if (!empty($user['foto_profil']) && file_exists($upload_dir . $user['foto_profil'])) {
        unlink($upload_dir . $user['foto_profil']);
    }
    $update = "UPDATE users SET foto_profil = NULL, updated_at = NOW() WHERE id_user = $id_user";
    if (mysqli_query($koneksi, $update)) {
        $_SESSION['profile_success'] = 'Foto profil berhasil dihapus!';
        $_SESSION['profile_success_type'] = 'success';
    } else {
        $_SESSION['profile_error'] = 'Gagal menghapus foto.';
        $_SESSION['profile_error_type'] = 'danger';
    }
    header('Location: index.php');
    exit;
}

// ============================================================
// JIKA AKSI TIDAK DIKENAL
// ============================================================
header('Location: index.php');
exit;
?>
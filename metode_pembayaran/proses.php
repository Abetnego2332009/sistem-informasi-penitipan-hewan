<?php
require_once __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$action = $_POST['action'] ?? '';
$id_metode = filter_var($_POST['id_metode'] ?? null, FILTER_VALIDATE_INT);

$nama_metode = mysqli_real_escape_string($koneksi, trim($_POST['nama_metode'] ?? ''));
$tipe_metode = mysqli_real_escape_string($koneksi, $_POST['tipe_metode'] ?? '');
$keterangan = mysqli_real_escape_string($koneksi, trim($_POST['keterangan'] ?? ''));
$urutan = filter_var($_POST['urutan'] ?? 0, FILTER_VALIDATE_INT);

$upload_dir = __DIR__ . '/../uploads/metode_pembayaran/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Validasi
if (in_array($action, ['insert', 'update'])) {
    if (empty($nama_metode) || empty($tipe_metode)) {
        echo "<script>alert('Nama dan Tipe Metode wajib diisi!'); window.history.back();</script>";
        exit;
    }
    // Cek duplikasi nama
    $check_query = "SELECT id_metode FROM metode_pembayaran WHERE nama_metode = '$nama_metode' AND is_active = 1";
    if ($action === 'update') {
        $check_query .= " AND id_metode != $id_metode";
    }
    $check_res = mysqli_query($koneksi, $check_query);
    if (mysqli_num_rows($check_res) > 0) {
        echo "<script>alert('Nama metode \"$nama_metode\" sudah terdaftar!'); window.history.back();</script>";
        exit;
    }
}

// Upload gambar
$gambar_metode = null;
if (in_array($action, ['insert', 'update']) && isset($_FILES['gambar_metode']) && $_FILES['gambar_metode']['error'] === UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['gambar_metode']['tmp_name'];
    $file_name = $_FILES['gambar_metode']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($file_ext, $allowed_ext)) {
        echo "<script>alert('Format gambar harus JPG, JPEG, PNG, atau WEBP!'); window.history.back();</script>";
        exit;
    }
    if ($_FILES['gambar_metode']['size'] > 2 * 1024 * 1024) {
        echo "<script>alert('Ukuran gambar maksimal 2MB!'); window.history.back();</script>";
        exit;
    }
    $new_file_name = 'metode_' . time() . '_' . uniqid() . '.' . $file_ext;
    if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
        $gambar_metode = $new_file_name;
        // Hapus file lama jika update
        if ($action === 'update' && $id_metode) {
            $q_old = mysqli_query($koneksi, "SELECT gambar_metode FROM metode_pembayaran WHERE id_metode = $id_metode");
            $d_old = mysqli_fetch_assoc($q_old);
            if (!empty($d_old['gambar_metode']) && file_exists($upload_dir . $d_old['gambar_metode'])) {
                unlink($upload_dir . $d_old['gambar_metode']);
            }
        }
    }
}

// Hapus gambar dari database jika tombol hapus diklik
if ($action === 'update' && isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] == '1') {
    $q_old = mysqli_query($koneksi, "SELECT gambar_metode FROM metode_pembayaran WHERE id_metode = $id_metode");
    $d_old = mysqli_fetch_assoc($q_old);
    if (!empty($d_old['gambar_metode']) && file_exists($upload_dir . $d_old['gambar_metode'])) {
        unlink($upload_dir . $d_old['gambar_metode']);
    }
    $gambar_metode = ''; // set ke string kosong agar update di database menjadi NULL
}

switch ($action) {
    case 'insert':
        $sql = "INSERT INTO metode_pembayaran (nama_metode, tipe_metode, keterangan, gambar_metode, urutan, is_active, created_at, updated_at)
                VALUES ('$nama_metode', '$tipe_metode', '$keterangan', '$gambar_metode', '$urutan', 1, NOW(), NOW())";
        if (mysqli_query($koneksi, $sql)) {
            echo "<script>alert('Metode pembayaran berhasil ditambahkan!'); window.location='index.php';</script>";
        } else {
            echo "<script>alert('Gagal menambahkan: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
        }
        break;

    case 'update':
        $sql_gambar = ($gambar_metode !== null) ? ", gambar_metode = '$gambar_metode'" : "";
        if (isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] == '1' && $gambar_metode === null) {
            $sql_gambar = ", gambar_metode = ''";
        }
        $sql = "UPDATE metode_pembayaran SET
                    nama_metode = '$nama_metode',
                    tipe_metode = '$tipe_metode',
                    keterangan = '$keterangan',
                    urutan = '$urutan'
                    $sql_gambar,
                    updated_at = NOW()
                WHERE id_metode = $id_metode";
        if (mysqli_query($koneksi, $sql)) {
            echo "<script>alert('Metode pembayaran berhasil diperbarui!'); window.location='index.php';</script>";
        } else {
            echo "<script>alert('Gagal memperbarui: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
        }
        break;

    case 'delete':
        // Soft delete (nonaktifkan)
        $sql = "UPDATE metode_pembayaran SET is_active = 0 WHERE id_metode = $id_metode";
        if (mysqli_query($koneksi, $sql)) {
            echo "<script>alert('Metode pembayaran dinonaktifkan.'); window.location='index.php';</script>";
        } else {
            echo "<script>alert('Gagal menonaktifkan.'); window.location='index.php';</script>";
        }
        break;

    case 'restore':
        // Aktifkan kembali
        $sql = "UPDATE metode_pembayaran SET is_active = 1 WHERE id_metode = $id_metode";
        if (mysqli_query($koneksi, $sql)) {
            echo "<script>alert('Metode pembayaran diaktifkan kembali.'); window.location='index.php';</script>";
        } else {
            echo "<script>alert('Gagal mengaktifkan.'); window.location='index.php';</script>";
        }
        break;

    default:
        header('Location: index.php');
        break;
}
exit;
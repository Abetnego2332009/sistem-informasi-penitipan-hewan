<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_user']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'])) {
    header('Location: ../login.php');
    exit;
}

// Fungsi upload file
function uploadFile($file, $targetDir, $prefix, $allowed = ['jpg','jpeg','png','webp']) {
    if (empty($file['name'])) return '';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return '';
    $filename = $prefix . '_' . time() . '.' . $ext;
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    if (move_uploaded_file($file['tmp_name'], $targetDir . $filename)) return $filename;
    return '';
}

// ============================================================
// 1. SIMPAN SEMUA PENGATURAN (FORM UTAMA)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['aksi'])) {

    $nama_toko = mysqli_real_escape_string($koneksi, trim($_POST['nama_toko'] ?? ''));
    $alamat = mysqli_real_escape_string($koneksi, trim($_POST['alamat'] ?? ''));
    $telepon = mysqli_real_escape_string($koneksi, trim($_POST['telepon'] ?? ''));
    $email = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
    $website = mysqli_real_escape_string($koneksi, trim($_POST['website'] ?? ''));
    $jam_operasional = mysqli_real_escape_string($koneksi, trim($_POST['jam_operasional'] ?? ''));
    $deskripsi_toko = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi_toko'] ?? ''));
    $sejarah_toko = mysqli_real_escape_string($koneksi, trim($_POST['sejarah_toko'] ?? ''));
    $google_maps_embed = mysqli_real_escape_string($koneksi, trim($_POST['google_maps_embed'] ?? ''));
    $ticker_teks = mysqli_real_escape_string($koneksi, trim($_POST['ticker_teks'] ?? ''));

    // Banner
    $judul_utama = mysqli_real_escape_string($koneksi, trim($_POST['judul_utama'] ?? ''));
    $sub_judul = mysqli_real_escape_string($koneksi, trim($_POST['sub_judul'] ?? ''));
    $deskripsi_banner = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi_banner'] ?? ''));
    $tombol_kiri_teks = mysqli_real_escape_string($koneksi, trim($_POST['tombol_kiri_teks'] ?? ''));
    $tombol_kiri_link = mysqli_real_escape_string($koneksi, trim($_POST['tombol_kiri_link'] ?? ''));
    $tombol_kanan_teks = mysqli_real_escape_string($koneksi, trim($_POST['tombol_kanan_teks'] ?? ''));
    $tombol_kanan_link = mysqli_real_escape_string($koneksi, trim($_POST['tombol_kanan_link'] ?? ''));

    // Upload foto toko
    $foto_lama = $_POST['foto_lama'] ?? '';
    $foto_baru = uploadFile($_FILES['foto_toko'] ?? [], __DIR__ . '/../uploads/profil/', 'profil');
    $foto_toko = !empty($foto_baru) ? $foto_baru : $foto_lama;

    // UPDATE pengaturan_umum (TANPA jumlah_kandang_tampil & jumlah_testimoni_tampil)
    $sql = "UPDATE pengaturan_umum SET
        nama_toko = '$nama_toko',
        alamat = '$alamat',
        telepon = '$telepon',
        email = '$email',
        website = '$website',
        jam_operasional = '$jam_operasional',
        deskripsi_toko = '$deskripsi_toko',
        sejarah_toko = '$sejarah_toko',
        google_maps_embed = '$google_maps_embed',
        foto_toko = '$foto_toko',
        ticker_teks = '$ticker_teks'
        WHERE id = 1";
    mysqli_query($koneksi, $sql);

    // UPDATE banner
    $cek = mysqli_query($koneksi, "SELECT id FROM banner LIMIT 1");
    if (mysqli_num_rows($cek) > 0) {
        $sql = "UPDATE banner SET
            judul_utama = '$judul_utama',
            sub_judul = '$sub_judul',
            deskripsi = '$deskripsi_banner',
            tombol_kiri_teks = '$tombol_kiri_teks',
            tombol_kiri_link = '$tombol_kiri_link',
            tombol_kanan_teks = '$tombol_kanan_teks',
            tombol_kanan_link = '$tombol_kanan_link'
            WHERE id = 1";
    } else {
        $sql = "INSERT INTO banner (judul_utama, sub_judul, deskripsi, tombol_kiri_teks, tombol_kiri_link, tombol_kanan_teks, tombol_kanan_link)
            VALUES ('$judul_utama', '$sub_judul', '$deskripsi_banner', '$tombol_kiri_teks', '$tombol_kiri_link', '$tombol_kanan_teks', '$tombol_kanan_link')";
    }
    mysqli_query($koneksi, $sql);

    // UPDATE status kandang
    $kandang_active = $_POST['kandang_active'] ?? [];
    $res = mysqli_query($koneksi, "SELECT id_kandang FROM kandang");
    while ($row = mysqli_fetch_assoc($res)) {
        $active = isset($kandang_active[$row['id_kandang']]) ? 1 : 0;
        mysqli_query($koneksi, "UPDATE kandang SET is_active = $active WHERE id_kandang = {$row['id_kandang']}");
    }

    // UPDATE status testimoni
    $testimoni_active = $_POST['testimoni_active'] ?? [];
    $res = mysqli_query($koneksi, "SELECT id_testimoni FROM testimoni");
    while ($row = mysqli_fetch_assoc($res)) {
        $active = isset($testimoni_active[$row['id_testimoni']]) ? 1 : 0;
        mysqli_query($koneksi, "UPDATE testimoni SET is_active = $active WHERE id_testimoni = {$row['id_testimoni']}");
    }

    header('Location: index.php?success=1');
    exit;
}

// ============================================================
// 2. TAMBAH KEUNGGULAN
// ============================================================
if (isset($_POST['aksi']) && $_POST['aksi'] === 'tambah_keunggulan') {
    $judul = mysqli_real_escape_string($koneksi, trim($_POST['judul'] ?? ''));
    $deskripsi = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'] ?? ''));
    $urutan = (int) ($_POST['urutan'] ?? 0);
    $is_active = (int) ($_POST['is_active'] ?? 1);
    $gambar = uploadFile($_FILES['gambar'] ?? [], __DIR__ . '/../uploads/keunggulan/', 'keunggulan');

    if (!empty($judul)) {
        mysqli_query($koneksi, "INSERT INTO keunggulan (judul, deskripsi, urutan, is_active, gambar) VALUES ('$judul', '$deskripsi', $urutan, $is_active, '$gambar')");
    }
    header('Location: edit.php');
    exit;
}

// ============================================================
// 3. EDIT KEUNGGULAN
// ============================================================
if (isset($_POST['aksi']) && $_POST['aksi'] === 'edit_keunggulan') {
    $id = (int) ($_POST['id_keunggulan'] ?? 0);
    $judul = mysqli_real_escape_string($koneksi, trim($_POST['judul'] ?? ''));
    $deskripsi = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'] ?? ''));
    $urutan = (int) ($_POST['urutan'] ?? 0);
    $is_active = (int) ($_POST['is_active'] ?? 1);
    $gambar_baru = uploadFile($_FILES['gambar'] ?? [], __DIR__ . '/../uploads/keunggulan/', 'keunggulan');

    $sql = "UPDATE keunggulan SET judul='$judul', deskripsi='$deskripsi', urutan=$urutan, is_active=$is_active";
    if (!empty($gambar_baru)) $sql .= ", gambar='$gambar_baru'";
    $sql .= " WHERE id_keunggulan = $id";
    mysqli_query($koneksi, $sql);
    header('Location: edit.php');
    exit;
}

// ============================================================
// 4. HAPUS KEUNGGULAN
// ============================================================
if (isset($_POST['aksi']) && $_POST['aksi'] === 'hapus_keunggulan') {
    $id = (int) ($_POST['id_keunggulan'] ?? 0);
    mysqli_query($koneksi, "DELETE FROM keunggulan WHERE id_keunggulan = $id");
    header('Location: edit.php');
    exit;
}

// ============================================================
// 5. TOGGLE STATUS TOKO
// ============================================================
if (isset($_POST['aksi']) && $_POST['aksi'] === 'toggle_status_toko') {
    $status = isset($_POST['status_toko']) ? 'buka' : 'tutup';
    mysqli_query($koneksi, "UPDATE pengaturan_umum SET status_toko = '$status' WHERE id = 1");
    header('Location: index.php?success=1');
    exit;
}

// ============================================================
// 6. TOGGLE TICKER
// ============================================================
if (isset($_POST['aksi']) && $_POST['aksi'] === 'toggle_ticker') {
    $ticker_aktif = isset($_POST['ticker_aktif']) ? 1 : 0;
    mysqli_query($koneksi, "UPDATE pengaturan_umum SET ticker_aktif = $ticker_aktif WHERE id = 1");
    header('Location: index.php?success=1');
    exit;
}

// ============================================================
// DEFAULT
// ============================================================
header('Location: index.php');
exit;
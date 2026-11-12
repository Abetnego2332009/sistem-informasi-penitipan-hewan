<?php
/**
 * proses.php - Memproses pengiriman bukti pembayaran
 *              Support untuk Admin (langsung Disetujui) dan Pelanggan (Menunggu Konfirmasi)
 * 
 * LOKASI: pembayaran/proses.php
 */

session_start();
require_once __DIR__ . '/../koneksi.php';

// ============================================================
// 1. VALIDASI SESSION
// ============================================================
if (!isset($_SESSION['id_user'])) {
    echo "<script>alert('Anda harus login terlebih dahulu!'); window.location.href='../login.php';</script>";
    exit;
}
$id_user = $_SESSION['id_user'];
$role_user = $_SESSION['role'] ?? 'Pelanggan';
$isAdmin = ($role_user == 'Super Admin' || $role_user == 'Admin');

// ============================================================
// 2. VALIDASI METHOD POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('Metode tidak diizinkan!'); window.location.href='../bookingan_saya/index.php';</script>";
    exit;
}

// ============================================================
// 3. AMBIL & VALIDASI INPUT DARI FORM
// ============================================================
$id_penitipan   = filter_var($_POST['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);
$id_metode      = filter_var($_POST['id_metode'] ?? 0, FILTER_VALIDATE_INT);
$nama_pembayar  = trim($_POST['nama_pembayar'] ?? '');
$jumlah_bayar   = str_replace('.', '', $_POST['jumlah_bayar'] ?? 0);
$jumlah_bayar   = str_replace(',', '.', $jumlah_bayar);
$jumlah_bayar   = (float) $jumlah_bayar;
$catatan        = trim($_POST['catatan'] ?? '');
$is_admin_post  = filter_var($_POST['is_admin'] ?? 0, FILTER_VALIDATE_INT);

// Validasi: ID Penitipan & Metode harus ada
if (!$id_penitipan || !$id_metode) {
    echo "<script>alert('Data tidak lengkap! Silakan pilih metode pembayaran.'); window.history.back();</script>";
    exit;
}

// Validasi: Nama Pembayar wajib diisi
if (empty($nama_pembayar)) {
    echo "<script>alert('Nama pembayar wajib diisi!'); window.history.back();</script>";
    exit;
}

if ($jumlah_bayar <= 0) {
    echo "<script>alert('Jumlah bayar harus lebih dari 0!'); window.history.back();</script>";
    exit;
}

// Escape untuk keamanan
$nama_pembayar = mysqli_real_escape_string($koneksi, $nama_pembayar);
$catatan = mysqli_real_escape_string($koneksi, $catatan);

// ============================================================
// 4. CEK KEPEMILIKAN BOOKING
// ============================================================
$stmt_cek = mysqli_prepare($koneksi, "
    SELECT id_penitipan, kode_booking, total_biaya, status_penitipan
    FROM penitipan
    WHERE id_penitipan = ? AND id_user = ?
");
mysqli_stmt_bind_param($stmt_cek, 'ii', $id_penitipan, $id_user);
mysqli_stmt_execute($stmt_cek);
$result_cek = mysqli_stmt_get_result($stmt_cek);
$penitipan = mysqli_fetch_assoc($result_cek);
mysqli_stmt_close($stmt_cek);

if (!$penitipan) {
    echo "<script>alert('Data booking tidak ditemukan atau Anda tidak memiliki akses!'); window.location.href='../bookingan_saya/index.php';</script>";
    exit;
}

// Cek status booking: hanya boleh Pending
if ($penitipan['status_penitipan'] !== 'Pending') {
    echo "<script>alert('Booking ini sudah dalam proses pembayaran atau sudah selesai!'); window.location.href='../bookingan_saya/index.php';</script>";
    exit;
}

// ============================================================
// 5. CEK TIPE METODE (APAKAH TUNAI?)
// ============================================================
$stmt_tipe = mysqli_prepare($koneksi, "SELECT tipe_metode FROM metode_pembayaran WHERE id_metode = ?");
mysqli_stmt_bind_param($stmt_tipe, 'i', $id_metode);
mysqli_stmt_execute($stmt_tipe);
$result_tipe = mysqli_stmt_get_result($stmt_tipe);
$tipe_data = mysqli_fetch_assoc($result_tipe);
$tipe_metode = $tipe_data['tipe_metode'] ?? '';
mysqli_stmt_close($stmt_tipe);

$isTunai = ($tipe_metode === 'Tunai');

// ============================================================
// 6. VALIDASI & UPLOAD FILE
// ============================================================
$db_file_path = '';
$upload_path = null;

// ============================================================
// 6. VALIDASI & UPLOAD FILE (SKIP JIKA ADMIN ATAU TUNAI)
// ============================================================
if ($isAdmin) {
    // Admin langsung set status Lunas, tidak perlu upload
    $status_bayar = 'Lunas';
    $status_penitipan = 'Disetujui'; // LANGSUNG DISETUJUI!
    $dikonfirmasi_oleh = $id_user;
    $tgl_konfirmasi = date('Y-m-d H:i:s');
    $db_file_path = ''; // Kosong, tidak ada bukti
} else {
    // Pelanggan: harus upload (kecuali Tunai)
    $status_bayar = 'Menunggu Konfirmasi';
    $status_penitipan = 'Menunggu Konfirmasi';
    $dikonfirmasi_oleh = null;
    $tgl_konfirmasi = null;
    
    if (!$isTunai) {
        // Cek apakah file ada
        if (!isset($_FILES['bukti_pembayaran']) || $_FILES['bukti_pembayaran']['error'] !== UPLOAD_ERR_OK) {
            $error_msg = 'Silakan pilih file bukti pembayaran.';
            if (isset($_FILES['bukti_pembayaran']['error'])) {
                switch ($_FILES['bukti_pembayaran']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $error_msg = 'Ukuran file terlalu besar (maksimal 2MB).';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $error_msg = 'Silakan pilih file bukti pembayaran.';
                        break;
                    default:
                        $error_msg = 'Terjadi error saat upload file.';
                }
            }
            $error_msg = addslashes($error_msg);
            echo "<script>alert('$error_msg'); window.history.back();</script>";
            exit;
        }

        $file = $_FILES['bukti_pembayaran'];

        // Validasi ekstensi file
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_extensions)) {
            echo "<script>alert('Format file tidak didukung! Hanya JPG, JPEG, dan PNG.'); window.history.back();</script>";
            exit;
        }

        // Validasi ukuran file (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            echo "<script>alert('Ukuran file melebihi 2MB! Silakan kompres gambar Anda.'); window.history.back();</script>";
            exit;
        }

        // Upload file
        $base_name  = $penitipan['kode_booking'] . '_' . date('Ymd_His');
        $file_name  = $base_name . '.' . $file_extension;
        $upload_dir = __DIR__ . '/../uploads/bukti_pembayaran/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $counter   = 1;
        $final_name = $file_name;
        while (file_exists($upload_dir . $final_name)) {
            $final_name = $base_name . '_' . $counter . '.' . $file_extension;
            $counter++;
        }

        $upload_path = $upload_dir . $final_name;
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            echo "<script>alert('Gagal mengupload file. Silakan coba lagi.'); window.history.back();</script>";
            exit;
        }

        $db_file_path = 'uploads/bukti_pembayaran/' . $final_name;
    }
}

// ============================================================
// 7. GENERATE KODE PEMBAYARAN UNIK
// ============================================================
$date_prefix = date('Ymd');
$query_kode = mysqli_query($koneksi, "SELECT MAX(kode_pembayaran) AS kode_terakhir FROM pembayaran WHERE kode_pembayaran LIKE 'PAY-{$date_prefix}-%'");
$row_kode = mysqli_fetch_assoc($query_kode);

if ($row_kode && $row_kode['kode_terakhir']) {
    $last_number   = (int) substr($row_kode['kode_terakhir'], -4);
    $next_number   = str_pad($last_number + 1, 4, '0', STR_PAD_LEFT);
} else {
    $next_number   = '0001';
}
$kode_pembayaran = 'PAY-' . $date_prefix . '-' . $next_number;

// ============================================================
// 8. AMBIL NAMA METODE
// ============================================================
$stmt_metode = mysqli_prepare($koneksi, "SELECT nama_metode FROM metode_pembayaran WHERE id_metode = ?");
mysqli_stmt_bind_param($stmt_metode, 'i', $id_metode);
mysqli_stmt_execute($stmt_metode);
$result_metode = mysqli_stmt_get_result($stmt_metode);
$metode_data = mysqli_fetch_assoc($result_metode);
$nama_metode = $metode_data['nama_metode'] ?? 'Transfer';
mysqli_stmt_close($stmt_metode);

// ============================================================
// 9. MULAI TRANSAKSI DATABASE
// ============================================================
mysqli_begin_transaction($koneksi);

try {
    // ============================================================
    // 9a. INSERT DATA PEMBAYARAN
    // ============================================================
    $query_insert = "
        INSERT INTO pembayaran (
            id_penitipan,
            kode_pembayaran,
            tgl_bayar,
            id_metode,
            metode_bayar,
            nama_pembayar,
            jumlah_tagihan,
            jumlah_bayar,
            bukti_pembayaran,
            status_bayar,
            catatan,
            dikonfirmasi_oleh,
            tgl_konfirmasi
        ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt_insert = mysqli_prepare($koneksi, $query_insert);
    mysqli_stmt_bind_param(
        $stmt_insert,
        'isissssssiis',
        $id_penitipan,
        $kode_pembayaran,
        $id_metode,
        $nama_metode,
        $nama_pembayar,
        $penitipan['total_biaya'],
        $jumlah_bayar,
        $db_file_path,
        $status_bayar,
        $catatan,
        $dikonfirmasi_oleh,
        $tgl_konfirmasi
    );
    
    if (!mysqli_stmt_execute($stmt_insert)) {
        throw new Exception('Gagal menyimpan data pembayaran: ' . mysqli_error($koneksi));
    }
    mysqli_stmt_close($stmt_insert);

    // ============================================================
    // 9b. UPDATE STATUS PENITIPAN
    // ============================================================
    if ($isAdmin) {
        // Admin: langsung Disetujui
        $update_status = "UPDATE penitipan 
                          SET status_penitipan = 'Disetujui', 
                              tgl_disetujui = NOW(), 
                              id_user_admin = $id_user,
                              updated_at = NOW() 
                          WHERE id_penitipan = $id_penitipan";
        
        if (!mysqli_query($koneksi, $update_status)) {
            throw new Exception('Gagal update status penitipan: ' . mysqli_error($koneksi));
        }
        
        // ============================================================
        // 9c. KURANGI STOK KANDANG (UNTUK ADMIN)
        // ============================================================
        $query_kandang = mysqli_query($koneksi, "
            SELECT DISTINCT id_kandang 
            FROM penitipan_hewan 
            WHERE id_penitipan = $id_penitipan
        ");
        while ($row = mysqli_fetch_assoc($query_kandang)) {
            if (!mysqli_query($koneksi, "UPDATE kandang SET stok = stok - 1 WHERE id_kandang = {$row['id_kandang']}")) {
                throw new Exception('Gagal update stok kandang: ' . mysqli_error($koneksi));
            }
        }
        
        // ============================================================
        // 9d. HAPUS LOCK (KARENA SUDAH DISETUJUI)
        // ============================================================
        $query_kandang2 = mysqli_query($koneksi, "
            SELECT DISTINCT id_kandang 
            FROM penitipan_hewan 
            WHERE id_penitipan = $id_penitipan
        ");
        while ($row = mysqli_fetch_assoc($query_kandang2)) {
            if (!mysqli_query($koneksi, "DELETE FROM booking_lock WHERE id_kandang = {$row['id_kandang']}")) {
                throw new Exception('Gagal hapus lock: ' . mysqli_error($koneksi));
            }
        }
        
    } else {
        // Pelanggan: Menunggu Konfirmasi
        $update_status = "UPDATE penitipan 
                          SET status_penitipan = 'Menunggu Konfirmasi', 
                              updated_at = NOW() 
                          WHERE id_penitipan = $id_penitipan";
        
        if (!mysqli_query($koneksi, $update_status)) {
            throw new Exception('Gagal update status penitipan: ' . mysqli_error($koneksi));
        }
    }

    // ============================================================
    // 9e. KOMIT TRANSAKSI
    // ============================================================
    mysqli_commit($koneksi);

    // ============================================================
    // 10. REDIRECT
    // ============================================================
    if ($isAdmin) {
        echo "<script>alert('✅ Pembayaran berhasil dikonfirmasi! Status booking: Disetujui (Admin langsung setuju).');</script>";
        $_SESSION['toast_message'] = "Pembayaran berhasil dikonfirmasi! Status booking: Disetujui.";
        $_SESSION['toast_type']    = 'success';
    } else {
        echo "<script>alert('✅ Pembayaran berhasil dikirim! Menunggu konfirmasi admin.');</script>";
        $_SESSION['toast_message'] = "Pembayaran berhasil dikirim! Menunggu konfirmasi admin.";
        $_SESSION['toast_type']    = 'success';
    }

    echo "<script>window.location.href='../bookingan_saya/index.php';</script>";
    exit;

} catch (Exception $e) {
    // ============================================================
    // ROLLBACK JIKA ADA ERROR
    // ============================================================
    mysqli_rollback($koneksi);
    
    // Hapus file yang sudah diupload
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }
    
    $error = addslashes($e->getMessage());
    echo "<script>alert('Error: $error'); window.history.back();</script>";
    exit;
}
?>
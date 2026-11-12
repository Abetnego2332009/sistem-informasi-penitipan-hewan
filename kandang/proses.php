<?php
require_once __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_kandang = filter_var($_POST['id_kandang'] ?? null, FILTER_VALIDATE_INT);
    
    // Sanitisasi Input Tekstual
    $kode_kandang = mysqli_real_escape_string($koneksi, trim($_POST['kode_kandang'] ?? ''));
    $nama_kandang = mysqli_real_escape_string($koneksi, trim($_POST['nama_kandang'] ?? ''));
    $tipe_hewan = mysqli_real_escape_string($koneksi, $_POST['tipe_hewan'] ?? '');
    $ukuran = mysqli_real_escape_string($koneksi, $_POST['ukuran'] ?? '');
    $tarif_per_hari = filter_var($_POST['tarif_per_hari'] ?? 0, FILTER_VALIDATE_FLOAT);
    $kapasitas = filter_var($_POST['kapasitas'] ?? 1, FILTER_VALIDATE_INT);
    $stok = filter_var($_POST['stok'] ?? 1, FILTER_VALIDATE_INT);
    $fasilitas = mysqli_real_escape_string($koneksi, trim($_POST['fasilitas'] ?? ''));

    // --- MENANGKAP PARAMETER HAPUS FOTO SEMENTARA DARI UI ---
    $hapus_foto_saja = $_POST['hapus_foto_saja'] ?? '0';

    // --- VALIDASI SERVER SIDE ---
    if (in_array($action, ['insert', 'update'])) {
        if (empty($kode_kandang) || empty($nama_kandang)) {
            echo "<script>alert('Error: Kode dan Nama Kandang tidak boleh kosong!'); window.history.back();</script>";
            exit;
        }
        if ($kapasitas <= 0 || $stok <= 0) {
            echo "<script>alert('Error: Kapasitas dan Stok harus lebih dari 0!'); window.history.back();</script>";
            exit;
        }
        if ($tarif_per_hari < 0) {
            echo "<script>alert('Error: Harga/Tarif tidak boleh negatif!'); window.history.back();</script>";
            exit;
        }

        // Cek Keunikan Kode Kandang
        $query_check = "SELECT id_kandang FROM kandang WHERE kode_kandang = '$kode_kandang' AND is_active = 1";
        if ($action === 'update') {
            $query_check .= " AND id_kandang != $id_kandang";
        }
        $res_check = mysqli_query($koneksi, $query_check);
        if (mysqli_num_rows($res_check) > 0) {
            echo "<script>alert('Error: Kode Kandang [$kode_kandang] sudah terdaftar!'); window.history.back();</script>";
            exit;
        }
    }

    // --- LOGIKA UPLOAD GAMBAR ---
    $foto_kandang = null;
    // --- PERBAIKAN: Mundur 1 tingkat agar folder 'uploads' dibuat di root utama projek ---
    $upload_dir = __DIR__ . '/../uploads/kandang/';

    if (in_array($action, ['insert', 'update']) && isset($_FILES['foto_kandang']) && $_FILES['foto_kandang']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['foto_kandang']['tmp_name'];
        $file_name = $_FILES['foto_kandang']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validasi Ekstensi & Ukuran (Maks 2MB)
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($file_ext, $allowed_ext)) {
            echo "<script>alert('Error: Format gambar harus JPG, JPEG, PNG, atau WEBP!'); window.history.back();</script>";
            exit;
        }
        if ($_FILES['foto_kandang']['size'] > 2 * 1024 * 1024) {
            echo "<script>alert('Error: Ukuran gambar maksimal adalah 2MB!'); window.history.back();</script>";
            exit;
        }

        // Buat folder jika belum ada
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Penamaan file unik agar tidak bentrok
        $new_file_name = 'kandang_' . time() . '_' . uniqid() . '.' . $file_ext;
        if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
            $foto_kandang = $new_file_name;

            // Jika sedang update data dan sukses upload foto baru, bersihkan file foto lamanya dari server
            if ($action === 'update' && $id_kandang) {
                $q_old = mysqli_query($koneksi, "SELECT foto_kandang FROM kandang WHERE id_kandang = $id_kandang");
                $d_old = mysqli_fetch_assoc($q_old);
                if (!empty($d_old['foto_kandang']) && file_exists($upload_dir . $d_old['foto_kandang'])) {
                    unlink($upload_dir . $d_old['foto_kandang']);
                }
            }
        }
    }

    // --- EKSEKUSI DATABASE QUERY ---
    if ($action === 'insert') {
        $foto_db = $foto_kandang ?? '';
        $query = "INSERT INTO kandang (kode_kandang, nama_kandang, tipe_hewan, ukuran, tarif_per_hari, fasilitas, kapasitas, stok, foto_kandang, is_active) 
                  VALUES ('$kode_kandang', '$nama_kandang', '$tipe_hewan', '$ukuran', '$tarif_per_hari', '$fasilitas', '$kapasitas', '$stok', '$foto_db', 1)";
        if (mysqli_query($koneksi, $query)) {
            echo "<script>alert('Data kandang dan foto berhasil ditambahkan!'); window.location='index.php';</script>";
        } else {
            echo "<script>alert('Gagal menyimpan data ke database.'); window.history.back();</script>";
        }
        exit;

    } elseif ($action === 'update') {
        // Logika Pengondisian Nilai Kolom Gambar Baru
        if ($foto_kandang !== null) {
            // Skenario A: User mengunggah file baru
            $sql_foto = ", foto_kandang = '$foto_kandang'";
        } elseif ($hapus_foto_saja === '1') {
            // Skenario B: User tidak upload file baru, tapi klik tombol "Hapus Gambar" di UI lalu klik SIMPAN PERUBAHAN
            $sql_foto = ", foto_kandang = ''";

            // Hapus berkas fisiknya dari folder server karena admin menekan tombol Simpan
            $q_old = mysqli_query($koneksi, "SELECT foto_kandang FROM kandang WHERE id_kandang = $id_kandang");
            $d_old = mysqli_fetch_assoc($q_old);
            if (!empty($d_old['foto_kandang']) && file_exists($upload_dir . $d_old['foto_kandang'])) {
                unlink($upload_dir . $d_old['foto_kandang']);
            }
        } else {
            // Skenario C: User tidak mengubah foto atau klik batal (tetap pakai foto lama)
            $sql_foto = "";
        }

        $query = "UPDATE kandang SET 
                    kode_kandang = '$kode_kandang', nama_kandang = '$nama_kandang', 
                    tipe_hewan = '$tipe_hewan', ukuran = '$ukuran', 
                    tarif_per_hari = '$tarif_per_hari', fasilitas = '$fasilitas', 
                    kapasitas = '$kapasitas', stok = '$stok' $sql_foto
                  WHERE id_kandang = $id_kandang";
                  
        if (mysqli_query($koneksi, $query)) {
            echo "<script>alert('Data kandang berhasil diperbarui!'); window.location='index.php';</script>";
        } else {
            echo "<script>alert('Gagal memperbarui data.'); window.history.back();</script>";
        }
        exit;

    } elseif ($action === 'delete') {
        // Soft delete
        $query = "UPDATE kandang SET is_active = 0 WHERE id_kandang = $id_kandang";
        if (mysqli_query($koneksi, $query)) {
            echo "<script>alert('Kandang berhasil dinonaktifkan (Soft Delete)!'); window.location='index.php';</script>";
        } else {
            echo "<script>alert('Gagal menghapus data.'); window.location='index.php';</script>";
        }
        exit;
    }
}
echo "<script>window.location='index.php';</script>";
?>
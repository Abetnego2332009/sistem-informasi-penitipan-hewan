<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$id_user = $_SESSION['id_user'];
$role = $_SESSION['role'] ?? 'Pelanggan';
$isAdmin = ($role == 'Super Admin' || $role == 'Admin');

// ============================================================
// 1. PERPANJANGAN OLEH USER (PER KANDANG)
// ============================================================
if (isset($_POST['aksi']) && $_POST['aksi'] == 'perpanjang_user') {
    $id_penitipan = filter_var($_POST['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);
    $id_kandang = filter_var($_POST['id_kandang'] ?? 0, FILTER_VALIDATE_INT);
    $tgl_selesai_baru = $_POST['tgl_selesai_baru'] ?? '';
    $tgl_selesai_lama = $_POST['tgl_selesai_lama'] ?? '';
    $nama_pembayar = mysqli_real_escape_string($koneksi, trim($_POST['nama_pembayar'] ?? ''));
    $jumlah_bayar = str_replace('.', '', $_POST['jumlah_bayar'] ?? '0');
    $catatan = mysqli_real_escape_string($koneksi, trim($_POST['catatan'] ?? ''));
    $id_metode = filter_var($_POST['id_metode'] ?? 0, FILTER_VALIDATE_INT);
    $total_biaya = str_replace('.', '', $_POST['total_biaya'] ?? '0');

    if (!$id_penitipan || !$id_kandang || !$tgl_selesai_baru || !$nama_pembayar || !$id_metode) {
        echo "<script>alert('Data tidak lengkap!'); window.history.back();</script>";
        exit;
    }

    // CEK APAKAH BOOKING STATUSNYA 'Sedang Dititipkan'
    $cek_status = mysqli_query($koneksi, "SELECT status_penitipan FROM penitipan WHERE id_penitipan = $id_penitipan");
    $data_status = mysqli_fetch_assoc($cek_status);

    if (!$data_status || $data_status['status_penitipan'] != 'Sedang Dititipkan') {
        echo "<script>alert('Booking tidak sedang dalam masa penitipan!'); window.history.back();</script>";
        exit;
    }

    // Upload bukti
    $bukti_path = null;
    if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['bukti_pembayaran']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        if (in_array($ext, $allowed)) {
            $nama_file = 'perpanjangan_' . $id_penitipan . '_' . date('Ymd_His') . '.' . $ext;
            $upload_dir = __DIR__ . '/../uploads/bukti_pembayaran/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            if (move_uploaded_file($_FILES['bukti_pembayaran']['tmp_name'], $upload_dir . $nama_file)) {
                $bukti_path = 'uploads/bukti_pembayaran/' . $nama_file;
            }
        }
    }

    // Generate kode pembayaran untuk perpanjangan user
$kode_pembayaran = 'PERP-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

// INSERT PER KANDANG (1 KANDANG = 1 BARIS)
$insert = mysqli_query($koneksi, "
    INSERT INTO riwayat_perpanjangan (
        id_penitipan,
        id_kandang,
        tgl_perpanjangan,
        tgl_selesai_lama,
        tgl_selesai_baru,
        biaya_tambahan,
        status,
        nama_pembayar,
        jumlah_bayar,
        catatan,
        id_metode,
        bukti_pembayaran,
        kode_pembayaran
    ) VALUES (
        $id_penitipan,
        $id_kandang,
        CURDATE(),
        '$tgl_selesai_lama',
        '$tgl_selesai_baru',
        '$total_biaya',
        'Menunggu Konfirmasi',
        '$nama_pembayar',
        '$jumlah_bayar',
        '$catatan',
        $id_metode,
        '$bukti_path',
        '$kode_pembayaran'
    )
");

    // Update tanggal di tabel penitipan
    require_once __DIR__ . '/../fungsi_update_penitipan.php';
    updateTanggalPenitipan($koneksi, $id_penitipan);

    if ($insert) {
        echo "<script>
            alert('Perpanjangan berhasil diajukan! Menunggu konfirmasi admin.');
            window.location.href='index.php';
        </script>";
    } else {
        echo "<script>alert('Gagal menyimpan data perpanjangan!'); window.history.back();</script>";
    }
    exit;
}

// ============================================================
// 2. PERPANJANGAN OLEH ADMIN (LANGSUNG UPDATE - PER KANDANG)
// ============================================================
if ($isAdmin && isset($_POST['aksi']) && $_POST['aksi'] == 'perpanjang_admin') {
    $id_penitipan = filter_var($_POST['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);
    $id_kandang = filter_var($_POST['id_kandang'] ?? 0, FILTER_VALIDATE_INT);
    $tgl_selesai_baru = $_POST['tgl_selesai_baru'] ?? '';
    $tgl_selesai_lama = $_POST['tgl_selesai_lama'] ?? ''; // WAJIB ADA DI FORM

    if (!$id_penitipan || !$id_kandang || !$tgl_selesai_baru) {
        echo "<script>alert('Data tidak lengkap!'); window.history.back();</script>";
        exit;
    }

    // ============================================================
    // 1. UPDATE TANGGAL SELESAI DI PENITIPAN_HEWAN
    // ============================================================
    $update = mysqli_query($koneksi, "
        UPDATE penitipan_hewan 
        SET tgl_selesai = '$tgl_selesai_baru' 
        WHERE id_penitipan = $id_penitipan AND id_kandang = $id_kandang
    ");

    if (!$update) {
        echo "<script>alert('Gagal update tanggal selesai!'); window.history.back();</script>";
        exit;
    }

    // ============================================================
    // 2. CEK APAKAH ADA RIWAYAT PERPANJANGAN YANG MENUNGGU KONFIRMASI
    // ============================================================
    $q_cek = mysqli_query($koneksi, "
        SELECT id_perpanjangan 
        FROM riwayat_perpanjangan 
        WHERE id_penitipan = $id_penitipan 
          AND id_kandang = $id_kandang 
          AND status = 'Menunggu Konfirmasi'
    ");
    $d_cek = mysqli_fetch_assoc($q_cek);

    if ($d_cek) {
        // Ada riwayat menunggu → update status menjadi Disetujui
        mysqli_query($koneksi, "
            UPDATE riwayat_perpanjangan 
            SET status = 'Disetujui' 
            WHERE id_perpanjangan = {$d_cek['id_perpanjangan']}
        ");
    } else {
        // ============================================================
        // 3. TIDAK ADA RIWAYAT → BUAT RIWAYAT BARU LANGSUNG
        // ============================================================
        $nama_admin = $_SESSION['nama_lengkap'] ?? 'Admin';
        $catatan_admin = "Perpanjangan langsung oleh admin";

        // Ambil tarif per hari untuk menghitung biaya tambahan (opsional)
        $q_tarif = mysqli_query($koneksi, "SELECT tarif_per_hari FROM kandang WHERE id_kandang = $id_kandang");
        $d_tarif = mysqli_fetch_assoc($q_tarif);
        $tarif = $d_tarif['tarif_per_hari'] ?? 0;
        // Hitung biaya tambahan (selisih hari × tarif)
        $diff = (strtotime($tgl_selesai_baru) - strtotime($tgl_selesai_lama)) / 86400;
        $biaya_tambahan = $diff > 0 ? $diff * $tarif : 0;
        // ============================================================
        // AMBIL JUMLAH BAYAR DARI POST (ADMIN)
        // ============================================================
        $jumlah_bayar_input = str_replace('.', '', $_POST['jumlah_bayar'] ?? '0');
        if (empty($jumlah_bayar_input) || $jumlah_bayar_input == 0) {
            $jumlah_bayar_input = $biaya_tambahan;
        }
        $kode_pembayaran = 'ADMIN-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $insert = mysqli_query($koneksi, "
        INSERT INTO riwayat_perpanjangan (
            id_penitipan,
            id_kandang,
            tgl_perpanjangan,
            tgl_selesai_lama,
            tgl_selesai_baru,
            biaya_tambahan,
            status,
            nama_pembayar,
            jumlah_bayar,
            catatan,
            id_metode,
            bukti_pembayaran,
            catatan_admin,
            kode_pembayaran 
        ) VALUES (
            $id_penitipan,
            $id_kandang,
            CURDATE(),
            '$tgl_selesai_lama',
            '$tgl_selesai_baru',
            '$biaya_tambahan',
            'Disetujui',
            '$nama_admin',
            '$jumlah_bayar_input', 
            '$catatan_admin',
            NULL,
            NULL,
            'Perpanjangan manual oleh admin',
            '$kode_pembayaran'
        )
    ");


        if (!$insert) {
            echo "<script>alert('Gagal mencatat riwayat perpanjangan!'); window.history.back();</script>";
            exit;
        }
    }

    // ============================================================
    // 4. UPDATE TABEL PENITIPAN (UNTUK KONSISTENSI HEADER BOOKING)
    // ============================================================
    require_once __DIR__ . '/../fungsi_update_penitipan.php';
    updateTanggalPenitipan($koneksi, $id_penitipan);

    echo "<script>
        alert('Masa aktif berhasil diperpanjang!');
        window.location.href='index.php';
    </script>";
    exit;
}

// ============================================================
// 3. PENGAMBILAN HEWAN (ADMIN - PER KANDANG) - PERBAIKAN FINAL
// ============================================================
if ($isAdmin && isset($_POST['aksi']) && $_POST['aksi'] == 'ambil_hewan') {
    $id_penitipan = filter_var($_POST['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);
    $id_kandang = filter_var($_POST['id_kandang'] ?? 0, FILTER_VALIDATE_INT);

    if (!$id_penitipan || !$id_kandang) {
        echo "<script>alert('Data tidak lengkap!'); window.history.back();</script>";
        exit;
    }

    // ============================================================
    // CEK: Apakah kandang ini sudah diambil sebelumnya?
    // Menggunakan tgl_diambil (lebih akurat)
    // ============================================================
    $q_cek_ambil = mysqli_query($koneksi, "
        SELECT COUNT(*) AS sudah_diambil
        FROM penitipan_hewan 
        WHERE id_penitipan = $id_penitipan 
          AND id_kandang = $id_kandang 
          AND tgl_diambil IS NOT NULL
    ");
    $d_cek_ambil = mysqli_fetch_assoc($q_cek_ambil);
    if ($d_cek_ambil['sudah_diambil'] > 0) {
        echo "<script>alert('Kandang ini sudah diambil sebelumnya!'); window.location.href='index.php';</script>";
        exit;
    }

    // ============================================================
    // CEK: Apakah ada hewan yang belum diambil di kandang ini?
    // ============================================================
    $q_hewan = mysqli_query($koneksi, "
        SELECT COUNT(*) AS jumlah 
        FROM penitipan_hewan 
        WHERE id_penitipan = $id_penitipan 
          AND id_kandang = $id_kandang 
          AND tgl_diambil IS NULL
    ");
    $d_hewan = mysqli_fetch_assoc($q_hewan);
    if ($d_hewan['jumlah'] == 0) {
        echo "<script>alert('Tidak ada hewan yang tersisa di kandang ini!'); window.location.href='index.php';</script>";
        exit;
    }

    // ============================================================
    // UPDATE: Set tgl_selesai = CURDATE() dan tgl_diambil = CURDATE()
    // ============================================================
    $update1 = mysqli_query($koneksi, "
        UPDATE penitipan_hewan 
        SET tgl_selesai = CURDATE(), 
            tgl_diambil = CURDATE() 
        WHERE id_penitipan = $id_penitipan 
          AND id_kandang = $id_kandang
    ");

    // Cek apakah ada baris yang terpengaruh (memastikan update berhasil)
    if (mysqli_affected_rows($koneksi) == 0) {
        echo "<script>alert('Gagal memperbarui data! Pastikan id_kandang dan id_penitipan benar.'); window.history.back();</script>";
        exit;
    }

    // ============================================================
    // UPDATE 2: Kembalikan stok kandang +1
    // ============================================================
    $update2 = mysqli_query($koneksi, "
        UPDATE kandang 
        SET stok = stok + 1 
        WHERE id_kandang = $id_kandang
    ");

    // ============================================================
    // CEK: Apakah masih ada kandang lain yang belum diambil?
    // (Berdasarkan tgl_diambil IS NULL)
    // ============================================================
    $q_cek = mysqli_query($koneksi, "
        SELECT COUNT(*) AS total 
        FROM penitipan_hewan 
        WHERE id_penitipan = $id_penitipan 
          AND id_kandang != $id_kandang 
          AND tgl_diambil IS NULL
    ");
    $d_cek = mysqli_fetch_assoc($q_cek);
    if ($d_cek['total'] == 0) {
        mysqli_query($koneksi, "
            UPDATE penitipan 
            SET status_penitipan = 'Selesai', tgl_keluar_aktual = NOW() 
            WHERE id_penitipan = $id_penitipan
        ");
    }

    // Update tanggal di tabel penitipan (hanya untuk konsistensi)
    require_once __DIR__ . '/../fungsi_update_penitipan.php';
    updateTanggalPenitipan($koneksi, $id_penitipan);

    if ($update1 && $update2) {
        echo "<script>
            alert('Pengambilan hewan berhasil! Stok kandang bertambah 1.');
            window.location.href='index.php';
        </script>";
    } else {
        echo "<script>alert('Gagal mengambil hewan!'); window.history.back();</script>";
    }
    exit;
}
// ============================================================
// DEFAULT
// ============================================================
header('Location: index.php');
exit;

<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$role = $_SESSION['role'] ?? 'Pelanggan';
if ($role != 'Super Admin' && $role != 'Admin') {
    echo "<script>alert('Akses ditolak! Hanya Admin.'); window.location.href='../../dashboard.php';</script>";
    exit;
}

$id_admin = $_SESSION['id_user'];
$id_pembayaran = filter_var($_POST['id_pembayaran'] ?? 0, FILTER_VALIDATE_INT);
$id_penitipan = filter_var($_POST['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';

if (!$id_penitipan || !$action) {
    echo "<script>alert('Data tidak lengkap!'); window.location.href='index.php';</script>";
    exit;
}

// ============================================================
// HAPUS LOCK YANG SUDAH EXPIRED
// ============================================================
mysqli_query($koneksi, "DELETE FROM booking_lock WHERE expires_at < NOW()");

// ============================================================
// 1. KONFIRMASI PEMBAYARAN (Menunggu Konfirmasi → Disetujui)
// ============================================================
if ($action == 'konfirmasi') {
    if (!$id_pembayaran) {
        echo "<script>alert('ID Pembayaran tidak valid!'); window.location.href='index.php';</script>";
        exit;
    }

    // ============================================================
    // MULAI TRANSAKSI
    // ============================================================
    mysqli_begin_transaction($koneksi);

    try {
        // Update status pembayaran menjadi Lunas
        $query1 = "UPDATE pembayaran SET status_bayar = 'Lunas', dikonfirmasi_oleh = $id_admin, tgl_konfirmasi = NOW() WHERE id_pembayaran = $id_pembayaran";
        if (!mysqli_query($koneksi, $query1)) {
            throw new Exception('Gagal update pembayaran: ' . mysqli_error($koneksi));
        }

        // Update status penitipan menjadi Disetujui
        $query2 = "UPDATE penitipan SET status_penitipan = 'Disetujui', tgl_disetujui = NOW(), id_user_admin = $id_admin WHERE id_penitipan = $id_penitipan";
        if (!mysqli_query($koneksi, $query2)) {
            throw new Exception('Gagal update penitipan: ' . mysqli_error($koneksi));
        }

        // ============================================================
        // KURANGI STOK KANDANG
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
        // HAPUS LOCK (KARENA SUDAH DISETUJUI)
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

        mysqli_commit($koneksi);
        echo "<script>alert('Pembayaran berhasil dikonfirmasi! Status booking: Disetujui. Stok berkurang dan lock dihapus.'); window.location.href='index.php';</script>";
        exit;
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit;
    }
}

// ============================================================
// 2. TOLAK PEMBAYARAN
// ============================================================
elseif ($action == 'tolak') {
    if (!$id_pembayaran) {
        echo "<script>alert('ID Pembayaran tidak valid!'); window.location.href='index.php';</script>";
        exit;
    }

    // Ambil alasan penolakan
    $alasan_penolakan = mysqli_real_escape_string($koneksi, trim($_POST['alasan_penolakan'] ?? 'Tidak ada alasan'));

    // ============================================================
    // MULAI TRANSAKSI
    // ============================================================
    mysqli_begin_transaction($koneksi);

    try {
        // Update status pembayaran menjadi Gagal
        $query1 = "UPDATE pembayaran SET status_bayar = 'Gagal', dikonfirmasi_oleh = $id_admin, tgl_konfirmasi = NOW() WHERE id_pembayaran = $id_pembayaran";
        if (!mysqli_query($koneksi, $query1)) {
            throw new Exception('Gagal update pembayaran: ' . mysqli_error($koneksi));
        }

        // Update status penitipan menjadi Ditolak + simpan alasan
        $query2 = "UPDATE penitipan SET status_penitipan = 'Ditolak', alasan_penolakan = '$alasan_penolakan', id_user_admin = $id_admin WHERE id_penitipan = $id_penitipan";
        if (!mysqli_query($koneksi, $query2)) {
            throw new Exception('Gagal update penitipan: ' . mysqli_error($koneksi));
        }

        

        // ============================================================
        // HAPUS LOCK (KARENA DITOLAK)
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

        mysqli_commit($koneksi);
        echo "<script>alert('Pembayaran ditolak! Stok kandang dikembalikan dan lock dihapus.'); window.location.href='index.php';</script>";
        exit;
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit;
    }
}

// ============================================================
// 3. MULAI TITIP (Disetujui → Sedang Dititipkan)
// ============================================================
elseif ($action == 'mulai_titip') {
    $query = "UPDATE penitipan SET status_penitipan = 'Sedang Dititipkan', tgl_masuk_aktual = NOW() WHERE id_penitipan = $id_penitipan";

    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Hewan sudah masuk penitipan!'); window.location.href='index.php?filter=disetujui';</script>";
    } else {
        echo "<script>alert('Gagal update: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
    }
    exit;
}

// ============================================================
// 4. KONFIRMASI PERPANJANGAN (Admin)
// ============================================================
elseif ($action == 'konfirmasi_perpanjangan') {
    $id_perpanjangan = filter_var($_POST['id_perpanjangan'] ?? 0, FILTER_VALIDATE_INT);
    $id_penitipan = filter_var($_POST['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);

    if (!$id_perpanjangan || !$id_penitipan) {
        echo "<script>alert('Data tidak lengkap!'); window.history.back();</script>";
        exit;
    }

    // ============================================================
    // MULAI TRANSAKSI
    // ============================================================
    mysqli_begin_transaction($koneksi);

    try {
        // Update status perpanjangan menjadi Disetujui
        mysqli_query($koneksi, "UPDATE riwayat_perpanjangan SET status = 'Disetujui' WHERE id_perpanjangan = $id_perpanjangan");
$q = mysqli_query($koneksi, "SELECT tgl_selesai_baru, id_kandang, biaya_tambahan FROM riwayat_perpanjangan WHERE id_perpanjangan = $id_perpanjangan");
$d = mysqli_fetch_assoc($q);
mysqli_query($koneksi, "UPDATE penitipan_hewan SET tgl_selesai = '{$d['tgl_selesai_baru']}' WHERE id_penitipan = $id_penitipan AND id_kandang = {$d['id_kandang']}");

// Update biaya_tambahan di tabel penitipan (akumulasi semua perpanjangan yang disetujui)
mysqli_query($koneksi, "UPDATE penitipan SET biaya_tambahan = biaya_tambahan + {$d['biaya_tambahan']} WHERE id_penitipan = $id_penitipan");

        // Update fungsi tanggal penitipan
        require_once __DIR__ . '/../fungsi_update_penitipan.php';
        updateTanggalPenitipan($koneksi, $id_penitipan);

        // ============================================================
        // HAPUS LOCK (KARENA PERPANJANGAN SUDAH DIKONFIRMASI)
        // ============================================================
        if (!mysqli_query($koneksi, "DELETE FROM booking_lock WHERE id_kandang = {$d['id_kandang']} AND id_penitipan = $id_penitipan")) {
            throw new Exception('Gagal hapus lock: ' . mysqli_error($koneksi));
        }

        mysqli_commit($koneksi);
        echo "<script>alert('Perpanjangan berhasil dikonfirmasi! Lock dihapus.'); window.location.href='index.php?filter=perpanjangan';</script>";
        exit;
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit;
    }
}

// ============================================================
// 5. TOLAK PERPANJANGAN (Admin)
// ============================================================
elseif ($action == 'tolak_perpanjangan') {
    $id_perpanjangan = filter_var($_POST['id_perpanjangan'] ?? 0, FILTER_VALIDATE_INT);
    $alasan = mysqli_real_escape_string($koneksi, trim($_POST['alasan_penolakan'] ?? 'Tidak ada alasan'));

    if (!$id_perpanjangan) {
        echo "<script>alert('Data tidak lengkap!'); window.history.back();</script>";
        exit;
    }

    // ============================================================
    // MULAI TRANSAKSI
    // ============================================================
    mysqli_begin_transaction($koneksi);

    try {
        // Update status perpanjangan menjadi Ditolak
        mysqli_query($koneksi, "UPDATE riwayat_perpanjangan SET status = 'Ditolak', catatan_admin = '$alasan' WHERE id_perpanjangan = $id_perpanjangan");

        // ============================================================
        // HAPUS LOCK (KARENA DITOLAK)
        // ============================================================
        $q = mysqli_query($koneksi, "SELECT id_kandang FROM riwayat_perpanjangan WHERE id_perpanjangan = $id_perpanjangan");
        $d = mysqli_fetch_assoc($q);
        if ($d) {
            if (!mysqli_query($koneksi, "DELETE FROM booking_lock WHERE id_kandang = {$d['id_kandang']} AND id_penitipan = $id_penitipan")) {
                throw new Exception('Gagal hapus lock: ' . mysqli_error($koneksi));
            }
        }

        mysqli_commit($koneksi);
        echo "<script>alert('Perpanjangan ditolak! Lock dihapus.'); window.location.href='index.php?filter=perpanjangan';</script>";
        exit;
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit;
    }
}

// ============================================================
// 6. AKSI TIDAK DIKENAL
// ============================================================
else {
    echo "<script>alert('Aksi tidak valid!'); window.location.href='index.php';</script>";
    exit;
}

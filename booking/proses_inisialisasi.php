<?php
session_start();
require_once __DIR__ . '/../koneksi.php'; 

// Ambil ID User login
$id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : 1; 
$session_id = session_id();

$tgl_mulai = $_GET['tgl_mulai'] ?? '';
$tgl_selesai = $_GET['tgl_selesai'] ?? '';

if (empty($tgl_mulai) || empty($tgl_selesai) || empty($_SESSION['booking_kandang'])) {
    echo "<script>alert('Akses tidak valid atau keranjang kosong!'); window.location.href='../ketersediaan_kandang/index.php';</script>";
    exit;
}

// ============================================================
// HAPUS LOCK YANG SUDAH EXPIRED
// ============================================================
mysqli_query($koneksi, "DELETE FROM booking_lock WHERE expires_at < NOW()");

// ============================================================
// 1. CEK LOCK DAN STOK UNTUK SEMUA KANDANG DI KERANJANG
// ============================================================
foreach ($_SESSION['booking_kandang'] as $id_kandang => $qty) {
    // Cek lock oleh user lain
    $query_lock = mysqli_query($koneksi, "
        SELECT id_lock, session_id, id_user 
        FROM booking_lock 
        WHERE id_kandang = $id_kandang 
        AND status IN ('pending_hewan', 'pending_pembayaran')
        AND expires_at > NOW()
    ");
    
    if (mysqli_num_rows($query_lock) > 0) {
        $lock = mysqli_fetch_assoc($query_lock);
        // Jika lock oleh user lain
        if ($lock['session_id'] != $session_id && $lock['id_user'] != $id_user) {
            echo "<script>alert('Maaf, ada kandang yang sedang dipesan user lain! Silakan pilih ulang.'); window.location.href='../ketersediaan_kandang/index.php';</script>";
            exit;
        }
    }
    
    // Cek stok (termasuk booking yang sedang aktif)
    // Cek stok langsung dari tabel kandang
$query_stok = mysqli_query($koneksi, "
    SELECT stok AS sisa_stok
    FROM kandang k WHERE id_kandang = $id_kandang
");
    $data_stok = mysqli_fetch_assoc($query_stok);
    
    if (!$data_stok || $data_stok['sisa_stok'] < $qty) {
        echo "<script>alert('Maaf, stok kandang tidak mencukupi! Silakan pilih ulang.'); window.location.href='../ketersediaan_kandang/index.php';</script>";
        exit;
    }
}

// ============================================================
// 2. HITUNG TOTAL HARI & BIAYA
// ============================================================
$datetime1 = new DateTime($tgl_mulai);
$datetime2 = new DateTime($tgl_selesai);
$interval = $datetime1->diff($datetime2);
$jumlah_hari = $interval->days <= 0 ? 1 : $interval->days;

$total_biaya = 0;
foreach ($_SESSION['booking_kandang'] as $id_kandang => $qty) {
    $q_kandang = mysqli_query($koneksi, "SELECT tarif_per_hari FROM kandang WHERE id_kandang = $id_kandang");
    $d_kandang = mysqli_fetch_assoc($q_kandang);
    if ($d_kandang) {
        $total_biaya += ($d_kandang['tarif_per_hari'] * $qty * $jumlah_hari);
    }
}

// ============================================================
// 3. MULAI TRANSAKSI DATABASE
// ============================================================
mysqli_begin_transaction($koneksi);

try {
    // Generate kode booking otomatis
    $kode_booking = 'BK-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));

    // Insert ke tabel penitipan (Status: Pending)
    $query_p = "INSERT INTO penitipan (kode_booking, id_user, tgl_mulai, tgl_selesai, total_biaya, status_penitipan) 
                VALUES ('$kode_booking', $id_user, '$tgl_mulai', '$tgl_selesai', $total_biaya, 'Pending')";
    
    if (!mysqli_query($koneksi, $query_p)) {
        throw new Exception('Gagal membuat draf booking: ' . mysqli_error($koneksi));
    }
    
    $id_penitipan = mysqli_insert_id($koneksi);

    // Insert data kandang ke penitipan_hewan
    foreach ($_SESSION['booking_kandang'] as $id_kandang => $qty) {
        for ($i = 0; $i < $qty; $i++) {
            $query_ph = "INSERT INTO penitipan_hewan (id_penitipan, id_kandang, id_hewan, tgl_mulai, tgl_selesai) 
                         VALUES ($id_penitipan, $id_kandang, NULL, '$tgl_mulai', '$tgl_selesai')";
            if (!mysqli_query($koneksi, $query_ph)) {
                throw new Exception('Gagal insert penitipan_hewan: ' . mysqli_error($koneksi));
            }
        }
    }

    // ============================================================
    // 4. UPDATE LOCK STATUS MENJADI 'pending_pembayaran'
    // ============================================================
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    foreach ($_SESSION['booking_kandang'] as $id_kandang => $qty) {
        // Update lock untuk setiap kandang
        $query_update_lock = "
            UPDATE booking_lock 
            SET status = 'pending_pembayaran', 
                expires_at = '$expires_at',
                id_penitipan = $id_penitipan
            WHERE id_kandang = $id_kandang 
            AND (session_id = '$session_id' OR id_user = $id_user)
            AND status = 'pending_hewan'
        ";
        if (!mysqli_query($koneksi, $query_update_lock)) {
            throw new Exception('Gagal update lock: ' . mysqli_error($koneksi));
        }
    }

    // ============================================================
    // 5. KOMIT TRANSAKSI
    // ============================================================
    mysqli_commit($koneksi);

    // Bersihkan session keranjang
    unset($_SESSION['booking_kandang']);

    // Redirect ke halaman form pengisian data hewan
    header("Location: index.php?id_penitipan=" . $id_penitipan);
    exit;

} catch (Exception $e) {
    // ROLLBACK jika ada error
    mysqli_rollback($koneksi);
    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href='../ketersediaan_kandang/index.php';</script>";
    exit;
}
?>
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_penitipan = filter_var($_POST['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);
    $id_user = $_SESSION['id_user'] ?? 1;

    // ============================================================
    // HAPUS LOCK YANG SUDAH EXPIRED
    // ============================================================
    mysqli_query($koneksi, "DELETE FROM booking_lock WHERE expires_at < NOW()");

    // ============================================================
    // 1. DELETE BOOKING (Hanya untuk status Pending)
    // ============================================================
    if ($action === 'delete_booking' && $id_penitipan) {
        // Mulai transaksi
        mysqli_begin_transaction($koneksi);
        
        try {
            // Ambil semua id_kandang di booking ini
            $query_kandang = mysqli_query($koneksi, "
                SELECT DISTINCT id_kandang 
                FROM penitipan_hewan 
                WHERE id_penitipan = $id_penitipan
            ");
            
            // Hapus lock untuk setiap kandang
            while ($row = mysqli_fetch_assoc($query_kandang)) {
                mysqli_query($koneksi, "DELETE FROM booking_lock WHERE id_kandang = {$row['id_kandang']}");
            }
            
            // Hapus data penitipan_hewan
            mysqli_query($koneksi, "DELETE FROM penitipan_hewan WHERE id_penitipan = $id_penitipan");
            
            // Hapus data penitipan
            $query = "DELETE FROM penitipan 
                      WHERE id_penitipan = $id_penitipan 
                      AND id_user = $id_user 
                      AND status_penitipan = 'Pending'";
            
            if (!mysqli_query($koneksi, $query)) {
                throw new Exception('Gagal menghapus booking: ' . mysqli_error($koneksi));
            }
            
            mysqli_commit($koneksi);
            echo "<script>alert('Bookingan berhasil dihapus! Lock kandang juga dihapus.'); window.location='index.php';</script>";
            
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            echo "<script>alert('Gagal menghapus bookingan: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        }
        exit;
    }

    // ============================================================
    // 2. AKSI ADMIN: MULAI TITIP (Disetujui → Sedang Dititipkan)
    // ============================================================
    elseif ($action === 'mulai_titip' && $id_penitipan) {
        // Cek apakah user adalah Admin
        $role = $_SESSION['role'] ?? 'Pelanggan';
        if ($role != 'Super Admin' && $role != 'Admin') {
            echo "<script>alert('Akses ditolak! Hanya Admin.'); window.history.back();</script>";
            exit;
        }

        $query = "UPDATE penitipan 
                  SET status_penitipan = 'Sedang Dititipkan', 
                      tgl_masuk_aktual = NOW() 
                  WHERE id_penitipan = $id_penitipan";
        
        if (mysqli_query($koneksi, $query)) {
            echo "<script>alert('Hewan sudah masuk penitipan!'); window.location='detail.php?id_penitipan=$id_penitipan';</script>";
        } else {
            echo "<script>alert('Gagal update: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
        }
        exit;
    }

    // ============================================================
    // 3. AKSI ADMIN: AMBIL HEWAN (Lebih Cepat) → Selesai
    // ============================================================
    elseif ($action === 'ambil_hewan' && $id_penitipan) {
        $role = $_SESSION['role'] ?? 'Pelanggan';
        if ($role != 'Super Admin' && $role != 'Admin') {
            echo "<script>alert('Akses ditolak! Hanya Admin.'); window.history.back();</script>";
            exit;
        }

        // Mulai transaksi
        mysqli_begin_transaction($koneksi);
        
        try {
            // Ambil semua id_kandang di booking ini
            $query_kandang = mysqli_query($koneksi, "
                SELECT DISTINCT id_kandang 
                FROM penitipan_hewan 
                WHERE id_penitipan = $id_penitipan
            ");
            
            // Kembalikan stok kandang
            while ($row = mysqli_fetch_assoc($query_kandang)) {
                mysqli_query($koneksi, "UPDATE kandang SET stok = stok + 1 WHERE id_kandang = {$row['id_kandang']}");
            }
            
            // Hapus lock untuk setiap kandang
            $query_kandang2 = mysqli_query($koneksi, "
                SELECT DISTINCT id_kandang 
                FROM penitipan_hewan 
                WHERE id_penitipan = $id_penitipan
            ");
            while ($row = mysqli_fetch_assoc($query_kandang2)) {
                mysqli_query($koneksi, "DELETE FROM booking_lock WHERE id_kandang = {$row['id_kandang']}");
            }
            
            // Update status menjadi Selesai
            $query = "UPDATE penitipan 
                      SET status_penitipan = 'Selesai', 
                          tgl_keluar_aktual = NOW() 
                      WHERE id_penitipan = $id_penitipan";
            
            if (!mysqli_query($koneksi, $query)) {
                throw new Exception('Gagal update status: ' . mysqli_error($koneksi));
            }
            
            mysqli_commit($koneksi);
            echo "<script>alert('Hewan berhasil diambil! Stok dikembalikan dan lock dihapus.'); window.location='detail.php?id_penitipan=$id_penitipan';</script>";
            
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            echo "<script>alert('Gagal update: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        }
        exit;
    }

    // ============================================================
    // 4. AKSI ADMIN: SELESAI (Normal)
    // ============================================================
    elseif ($action === 'selesai' && $id_penitipan) {
        $role = $_SESSION['role'] ?? 'Pelanggan';
        if ($role != 'Super Admin' && $role != 'Admin') {
            echo "<script>alert('Akses ditolak! Hanya Admin.'); window.history.back();</script>";
            exit;
        }

        // Mulai transaksi
        mysqli_begin_transaction($koneksi);
        
        try {
            // Ambil semua id_kandang di booking ini
            $query_kandang = mysqli_query($koneksi, "
                SELECT DISTINCT id_kandang 
                FROM penitipan_hewan 
                WHERE id_penitipan = $id_penitipan
            ");
            
            // Kembalikan stok kandang
            while ($row = mysqli_fetch_assoc($query_kandang)) {
                mysqli_query($koneksi, "UPDATE kandang SET stok = stok + 1 WHERE id_kandang = {$row['id_kandang']}");
            }
            
            // Hapus lock untuk setiap kandang
            $query_kandang2 = mysqli_query($koneksi, "
                SELECT DISTINCT id_kandang 
                FROM penitipan_hewan 
                WHERE id_penitipan = $id_penitipan
            ");
            while ($row = mysqli_fetch_assoc($query_kandang2)) {
                mysqli_query($koneksi, "DELETE FROM booking_lock WHERE id_kandang = {$row['id_kandang']}");
            }
            
            // Update status menjadi Selesai
            $query = "UPDATE penitipan 
                      SET status_penitipan = 'Selesai', 
                          tgl_keluar_aktual = NOW() 
                      WHERE id_penitipan = $id_penitipan";
            
            if (!mysqli_query($koneksi, $query)) {
                throw new Exception('Gagal update status: ' . mysqli_error($koneksi));
            }
            
            mysqli_commit($koneksi);
            echo "<script>alert('Penitipan selesai! Stok dikembalikan dan lock dihapus.'); window.location='detail.php?id_penitipan=$id_penitipan';</script>";
            
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            echo "<script>alert('Gagal update: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        }
        exit;
    }

    // ============================================================
    // 5. AKSI ADMIN: PERPANJANG (Admin langsung update)
    // ============================================================
    elseif ($action === 'perpanjang_admin' && $id_penitipan) {
        $role = $_SESSION['role'] ?? 'Pelanggan';
        if ($role != 'Super Admin' && $role != 'Admin') {
            echo "<script>alert('Akses ditolak! Hanya Admin.'); window.history.back();</script>";
            exit;
        }

        $tgl_selesai_baru = $_POST['tgl_selesai_baru'] ?? '';
        $biaya_tambahan = filter_var($_POST['biaya_tambahan'] ?? 0, FILTER_VALIDATE_INT);

        if (!$tgl_selesai_baru || $biaya_tambahan <= 0) {
            echo "<script>alert('Data tidak lengkap! Pastikan tanggal dan biaya tambahan diisi.'); window.history.back();</script>";
            exit;
        }

        $query = "UPDATE penitipan 
                  SET tgl_selesai = '$tgl_selesai_baru', 
                      total_biaya = total_biaya + $biaya_tambahan 
                  WHERE id_penitipan = $id_penitipan";
        
        if (mysqli_query($koneksi, $query)) {
            echo "<script>alert('Masa titip berhasil diperpanjang!'); window.location='detail.php?id_penitipan=$id_penitipan';</script>";
        } else {
            echo "<script>alert('Gagal perpanjang: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
        }
        exit;
    }

    // ============================================================
    // 6. AKSI DEFAULT: Aksi tidak dikenal
    // ============================================================
    else {
        echo "<script>alert('Aksi tidak dikenal!'); window.history.back();</script>";
        exit;
    }
}
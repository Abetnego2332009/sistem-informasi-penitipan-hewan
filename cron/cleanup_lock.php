<?php
/**
 * cron/cleanup_lock.php
 * 
 * File ini dijalankan setiap 5-10 menit via cron job
 * Untuk menghapus lock yang sudah expired
 * 
 * Cara setup cron (di cPanel/terminal):
 * */

// Untuk keamanan, hanya bisa dijalankan dari CLI (command line)
// jika diakses via browser, tetap jalan tapi dengan peringatan
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    // Jika diakses via browser, tetap jalan tapi dengan header text/plain
    header('Content-Type: text/plain');
    echo "Running cleanup_lock.php...\n";
}

// Load koneksi
require_once __DIR__ . '/../koneksi.php';

// ============================================================
// HAPUS LOCK YANG SUDAH EXPIRED
// ============================================================
$query = "DELETE FROM booking_lock WHERE expires_at < NOW()";
$result = mysqli_query($koneksi, $query);

if ($result) {
    $affected = mysqli_affected_rows($koneksi);
    $message = date('Y-m-d H:i:s') . " - Cleanup lock: $affected lock(s) deleted.\n";
    
    if ($is_cli) {
        echo $message;
    } else {
        echo $message;
    }
    
    // Optional: Simpan log ke file
    // file_put_contents(__DIR__ . '/logs/cleanup_lock.log', $message, FILE_APPEND);
} else {
    $error = mysqli_error($koneksi);
    $message = date('Y-m-d H:i:s') . " - ERROR: $error\n";
    
    if ($is_cli) {
        echo $message;
    } else {
        echo $message;
    }
}

mysqli_close($koneksi);
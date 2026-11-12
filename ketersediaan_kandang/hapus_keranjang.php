<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['booking_kandang'])) {
    $_SESSION['booking_kandang'] = [];
}

mysqli_query($koneksi, "DELETE FROM booking_lock WHERE expires_at < NOW()");

$session_id = session_id();
$id_user = $_SESSION['id_user'] ?? 0;

if (isset($_GET['clear']) && $_GET['clear'] === 'all') {
    mysqli_query($koneksi, "DELETE FROM booking_lock WHERE (session_id = '$session_id' OR id_user = $id_user) AND status = 'pending_hewan'");
    $_SESSION['booking_kandang'] = [];
    header('Location: index.php');
    exit;
}

if (isset($_GET['id'])) {
    $id_kandang = (int)$_GET['id'];
    $action = $_GET['action'] ?? 'minus';

    if (isset($_SESSION['booking_kandang'][$id_kandang])) {
        if ($action === 'minus') {
            $_SESSION['booking_kandang'][$id_kandang]--;
            if ($_SESSION['booking_kandang'][$id_kandang] <= 0) {
                unset($_SESSION['booking_kandang'][$id_kandang]);
                mysqli_query($koneksi, "DELETE FROM booking_lock WHERE id_kandang = $id_kandang AND (session_id = '$session_id' OR id_user = $id_user) AND status = 'pending_hewan'");
            } else {
                $q = mysqli_query($koneksi, "SELECT id_lock, quantity FROM booking_lock WHERE id_kandang = $id_kandang AND (session_id = '$session_id' OR id_user = $id_user) AND status = 'pending_hewan' AND expires_at > NOW()");
                if ($lock = mysqli_fetch_assoc($q)) {
                    $new_qty = (int)$lock['quantity'] - 1;
                    if ($new_qty <= 0) {
                        mysqli_query($koneksi, "DELETE FROM booking_lock WHERE id_lock = " . (int)$lock['id_lock']);
                    } else {
                        mysqli_query($koneksi, "UPDATE booking_lock SET quantity = $new_qty WHERE id_lock = " . (int)$lock['id_lock']);
                    }
                }
            }
        } elseif ($action === 'delete') {
            unset($_SESSION['booking_kandang'][$id_kandang]);
            mysqli_query($koneksi, "DELETE FROM booking_lock WHERE id_kandang = $id_kandang AND (session_id = '$session_id' OR id_user = $id_user) AND status = 'pending_hewan'");
        }
    }
}

header('Location: index.php');
exit;
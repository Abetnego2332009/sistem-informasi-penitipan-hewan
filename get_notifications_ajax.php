<?php
// ============================================================
// FILE: get_notifications_ajax.php
// FUNGSI: Endpoint AJAX untuk mengambil notifikasi detail
// ============================================================
session_start();
require_once 'koneksi.php';
require_once 'functions.php';

// Hanya admin yang bisa mengakses
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Akses ditolak']));
}

$result = getNotifikasiDetail($koneksi);
header('Content-Type: application/json');
echo json_encode($result);
?>
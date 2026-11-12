<?php
// ============================================================
// FILE: get_chart_data.php
// FUNGSI: Mengambil data pendapatan untuk bar chart berdasarkan filter
// ============================================================
session_start();
require_once 'koneksi.php';

// Set zona waktu Indonesia
date_default_timezone_set('Asia/Jakarta');

// Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'])) {
    http_response_code(403);
    exit('Akses ditolak');
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'bulan';

$labels = [];
$data = [];

// ==========================================================
// FUNGSI: Ambil pendapatan per periode (day/month)
// ==========================================================
function getPendapatanPeriode($conn, $start_date, $end_date, $group_by = 'day') {
    if ($group_by == 'day') {
        $date_field = 'DATE(tgl_bayar)';
    } else {
        $date_field = 'DATE_FORMAT(tgl_bayar, "%Y-%m")';
    }

    $sql = "SELECT 
                $date_field as periode,
                (SELECT IFNULL(SUM(jumlah_bayar),0) FROM pembayaran 
                 WHERE status_bayar = 'Lunas' 
                   AND DATE(tgl_bayar) BETWEEN '$start_date' AND '$end_date'
                   AND $date_field = periode) +
                (SELECT IFNULL(SUM(jumlah_bayar),0) FROM riwayat_perpanjangan 
                 WHERE status = 'Disetujui' 
                   AND DATE(tgl_perpanjangan) BETWEEN '$start_date' AND '$end_date'
                   AND DATE(tgl_perpanjangan) = periode) AS total
            FROM pembayaran 
            WHERE status_bayar = 'Lunas' 
              AND DATE(tgl_bayar) BETWEEN '$start_date' AND '$end_date'
            GROUP BY periode
            ORDER BY periode ASC";

    $result = mysqli_query($conn, $sql);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[$row['periode']] = (float)$row['total'];
    }
    return $rows;
}

// ==========================================================
// FILTER: BULAN
// ==========================================================
if ($filter == 'bulan') {
    $bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
    $tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

    $start_date = date('Y-m-01', strtotime("$tahun-$bulan-01"));
    $end_date   = date('Y-m-t', strtotime("$tahun-$bulan-01"));

    $days_in_month = date('t', strtotime("$tahun-$bulan-01"));
    $labels = [];
    for ($d = 1; $d <= $days_in_month; $d++) {
        $labels[] = $d;
    }

    $data_raw = getPendapatanPeriode($koneksi, $start_date, $end_date, 'day');
    $data = [];
    for ($d = 1; $d <= $days_in_month; $d++) {
        $date_key = date('Y-m-', strtotime("$tahun-$bulan-01")) . str_pad($d, 2, '0', STR_PAD_LEFT);
        $data[] = isset($data_raw[$date_key]) ? $data_raw[$date_key] : 0;
    }
}

header('Content-Type: application/json');
echo json_encode(['labels' => $labels, 'data' => $data]);
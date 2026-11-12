<?php
/**
 * fungsi_update_penitipan.php
 * Update tgl_mulai dan tgl_selesai di tabel penitipan
 * berdasarkan MIN/MAX dari penitipan_hewan
 */

function updateTanggalPenitipan($koneksi, $id_penitipan) {
    if (!$id_penitipan) return false;
    
    $query = "SELECT 
                MIN(tgl_mulai) AS tgl_mulai, 
                MAX(tgl_selesai) AS tgl_selesai 
              FROM penitipan_hewan 
              WHERE id_penitipan = $id_penitipan";
    
    $result = mysqli_query($koneksi, $query);
    $data = mysqli_fetch_assoc($result);
    
    if (!$data || !$data['tgl_mulai'] || !$data['tgl_selesai']) {
        return false;
    }
    
    // ============================================================
    // PERBAIKAN: PASTIKAN tgl_selesai > tgl_mulai (minimal +1 hari)
    // ============================================================
    if ($data['tgl_selesai'] <= $data['tgl_mulai']) {
        $data['tgl_selesai'] = date('Y-m-d', strtotime($data['tgl_mulai'] . ' +1 day'));
    }
    
    $update = "UPDATE penitipan 
               SET tgl_mulai = '{$data['tgl_mulai']}', 
                   tgl_selesai = '{$data['tgl_selesai']}' 
               WHERE id_penitipan = $id_penitipan";
    
    return mysqli_query($koneksi, $update);
}
?>
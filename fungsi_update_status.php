<?php
function updateStatusBookingOtomatis($koneksi) {
        return true;
        }
        
// function updateStatusBookingOtomatis($koneksi) {
//     $query = "
//         UPDATE penitipan p
//         SET p.status_penitipan = 'Selesai', 
    // Update booking yang sudah lewat tanggal selesai
//             p.tgl_keluar_aktual = NOW()
//         WHERE p.status_penitipan IN ('Sedang Dititipkan', 'Disetujui')
//           AND p.tgl_selesai < CURDATE()
//     ";
//     mysqli_query($koneksi, $query);
// }
?>
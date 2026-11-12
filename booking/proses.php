<?php
session_start();
require_once __DIR__ . '/../koneksi.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id_penitipan = filter_var($_POST['id_penitipan'] ?? 0, FILTER_VALIDATE_INT);
$input_hewan  = $_POST['hewan'] ?? [];

if (!$id_penitipan) {
    echo "<script>alert('Data booking tidak valid!'); window.location.href='../ketersediaan_kandang/index.php';</script>";
    exit;
}

$query_p = mysqli_query($koneksi, "SELECT id_user, tgl_mulai, tgl_selesai FROM penitipan WHERE id_penitipan = $id_penitipan AND status_penitipan = 'Pending'");
$penitipan = mysqli_fetch_assoc($query_p);

if (!$penitipan) {
    echo "<script>alert('Data booking tidak ditemukan atau sudah diproses!'); window.location.href='../ketersediaan_kandang/index.php';</script>";
    exit;
}

$id_user = $penitipan['id_user'];

// Cek role
$session_id_user = $_SESSION['id_user'] ?? 1;
$cek_user = mysqli_query($koneksi, "SELECT role FROM users WHERE id_user = $session_id_user");
$data_user = mysqli_fetch_assoc($cek_user);
$role_login = $data_user['role'] ?? 'Pelanggan';
$isAdmin = ($role_login === 'Super Admin' || $role_login === 'Admin');

$id_pelanggan_final = "NULL";

if ($isAdmin) {
    $id_pelanggan_hidden = filter_var($_POST['id_pelanggan_hidden'] ?? 0, FILTER_VALIDATE_INT);
    $nama_pelanggan      = mysqli_real_escape_string($koneksi, trim($_POST['nama_pelanggan'] ?? ''));
    $no_telp_pelanggan   = mysqli_real_escape_string($koneksi, trim($_POST['no_telp_pelanggan'] ?? ''));
    $alamat_pelanggan    = mysqli_real_escape_string($koneksi, trim($_POST['alamat_pelanggan'] ?? ''));

    if ($id_pelanggan_hidden) {
        $id_pelanggan_final = $id_pelanggan_hidden;
        $update_pelanggan = "UPDATE pelanggan SET nama_lengkap='$nama_pelanggan', alamat='$alamat_pelanggan', no_telp='$no_telp_pelanggan' WHERE id_pelanggan=$id_pelanggan_final";
        mysqli_query($koneksi, $update_pelanggan);
    } else if (!empty($nama_pelanggan)) {
        $ins_pelanggan = "INSERT INTO pelanggan (nama_lengkap, no_telp, alamat) VALUES ('$nama_pelanggan', '$no_telp_pelanggan', '$alamat_pelanggan')";
        if (mysqli_query($koneksi, $ins_pelanggan)) {
            $id_pelanggan_final = mysqli_insert_id($koneksi);
        }
    }
}

// ============================================================
// 1. LOOP DATA HEWAN (SIMPAN KE ARRAY DULU)
// ============================================================
$hewan_simpan = [];

foreach ($input_hewan as $id_ph => $slots) {
    foreach ($slots as $slot_no => $val) {
        if (!empty($val['id_hewan'])) {
            $id_hewan_final = null;

            $nama_hewan        = mysqli_real_escape_string($koneksi, trim($val['nama_hewan_baru'] ?? $val['nama_hewan'] ?? ''));
            $jenis_hewan       = mysqli_real_escape_string($koneksi, $val['jenis_hewan'] ?? '');
            $ras               = mysqli_real_escape_string($koneksi, trim($val['ras'] ?? ''));
            $jenis_kelamin     = mysqli_real_escape_string($koneksi, $val['jenis_kelamin'] ?? 'Jantan');
            $usia_tahun        = filter_var($val['usia_tahun'] ?? $val['umur_tahun'] ?? 0, FILTER_VALIDATE_INT);
            $usia_bulan        = filter_var($val['usia_bulan'] ?? $val['umur_bulan'] ?? 0, FILTER_VALIDATE_INT);
            $berat_badan       = filter_var($val['berat_badan'] ?? 0, FILTER_VALIDATE_FLOAT);
            $warna_bulu        = mysqli_real_escape_string($koneksi, trim($val['warna_bulu'] ?? ''));
            $status_vaksin     = mysqli_real_escape_string($koneksi, $val['status_vaksin'] ?? 'Tidak Ada');
            $karakteristik     = mysqli_real_escape_string($koneksi, trim($val['karakteristik'] ?? $val['karakteristik_hewan'] ?? ''));
            $riwayat_kesehatan = mysqli_real_escape_string($koneksi, trim($val['riwayat_kesehatan'] ?? ''));

            if ($val['id_hewan'] === 'BARU' && !empty($nama_hewan)) {
                $field_user = $isAdmin ? "NULL" : $id_user;
                $field_pelanggan = $isAdmin ? $id_pelanggan_final : "NULL";

                $ins_hewan = "INSERT INTO hewan (id_user, id_pelanggan, nama_hewan, jenis_hewan, ras, jenis_kelamin, usia_tahun, usia_bulan, berat_badan, warna_bulu, karakteristik, riwayat_kesehatan, status_vaksin) 
                              VALUES ($field_user, $field_pelanggan, '$nama_hewan', '$jenis_hewan', '$ras', '$jenis_kelamin', $usia_tahun, $usia_bulan, $berat_badan, '$warna_bulu', '$karakteristik', '$riwayat_kesehatan', '$status_vaksin')";
                if (mysqli_query($koneksi, $ins_hewan)) {
                    $id_hewan_final = mysqli_insert_id($koneksi);
                }
            } else if ($val['id_hewan'] !== 'BARU') {
                $id_hewan_final = (int)$val['id_hewan'];
                $update_hewan = "UPDATE hewan SET ras='$ras', jenis_kelamin='$jenis_kelamin', usia_tahun=$usia_tahun, usia_bulan=$usia_bulan, berat_badan=$berat_badan, warna_bulu='$warna_bulu', karakteristik='$karakteristik', riwayat_kesehatan='$riwayat_kesehatan', status_vaksin='$status_vaksin' WHERE id_hewan=$id_hewan_final";
                mysqli_query($koneksi, $update_hewan);
            }

            // ============================================================
            // PERBAIKAN: SIMPAN KE ARRAY, BUKAN LANGSUNG UPDATE
            // ============================================================
            if ($id_hewan_final !== null) {
                // Ambil id_kandang dari id_ph
                $q_kandang_ph = mysqli_query($koneksi, "SELECT id_kandang FROM penitipan_hewan WHERE id_penitipan_hewan = $id_ph");
                $row_kandang_ph = mysqli_fetch_assoc($q_kandang_ph);
                $id_kandang = $row_kandang_ph['id_kandang'] ?? 0;

                // Ambil tanggal dari form
                $tgl_mulai   = $_POST['tgl_mulai'][$id_ph] ?? $penitipan['tgl_mulai'];
                $tgl_selesai = $_POST['tgl_selesai'][$id_ph] ?? $penitipan['tgl_selesai'];

                $hewan_simpan[] = [
                    'id_hewan'    => $id_hewan_final,
                    'id_kandang'  => $id_kandang,
                    'tgl_mulai'   => $tgl_mulai,
                    'tgl_selesai' => $tgl_selesai
                ];
            }
        }
    }
}

// ============================================================
// 2. HAPUS DATA LAMA & INSERT ULANG
// ============================================================
if (!empty($hewan_simpan)) {
    // Hapus data lama
    mysqli_query($koneksi, "DELETE FROM penitipan_hewan WHERE id_penitipan = $id_penitipan");
    
    // Insert ulang
    foreach ($hewan_simpan as $data) {
        $query_insert_ph = "INSERT INTO penitipan_hewan (id_penitipan, id_hewan, id_kandang, tgl_mulai, tgl_selesai) 
                            VALUES ($id_penitipan, {$data['id_hewan']}, {$data['id_kandang']}, '{$data['tgl_mulai']}', '{$data['tgl_selesai']}')";
        mysqli_query($koneksi, $query_insert_ph);
    }
}

require_once __DIR__ . '/../fungsi_update_penitipan.php';
updateTanggalPenitipan($koneksi, $id_penitipan);

// ============================================================
// 2.5. VALIDASI TANGGAL (TAMBAHKAN INI!)
// ============================================================
$data_pertama = $hewan_simpan[0] ?? null;
if ($data_pertama) {
    $tgl_mulai   = $data_pertama['tgl_mulai'];
    $tgl_selesai = $data_pertama['tgl_selesai'];
    
    if ($tgl_mulai < date('Y-m-d')) {
        echo "<script>alert('Tanggal mulai tidak boleh kurang dari hari ini!'); window.history.back();</script>";
        exit;
    }
    
    if ($tgl_selesai < $tgl_mulai) {
        echo "<script>alert('Tanggal selesai harus lebih besar dari tanggal mulai!'); window.history.back();</script>";
        exit;
    }
}

// ============================================================
// 3. HITUNG TOTAL BIAYA (PER KANDANG, BUKAN PER HEWAN!)
// ============================================================
$total_biaya = 0;
$kandang_dihitung = [];

foreach ($hewan_simpan as $data) {
    $id_kandang = $data['id_kandang'];
    
    // Skip kalo kandang ini udah dihitung
    if (in_array($id_kandang, $kandang_dihitung)) {
        continue;
    }
    
    // Ambil tarif dari database
    $q_tarif = mysqli_query($koneksi, "SELECT tarif_per_hari FROM kandang WHERE id_kandang = $id_kandang");
    $row_tarif = mysqli_fetch_assoc($q_tarif);
    $tarif = $row_tarif['tarif_per_hari'] ?? 0;

    // Ambil durasi dari data pertama kandang ini
    $datetime1 = new DateTime($data['tgl_mulai']);
    $datetime2 = new DateTime($data['tgl_selesai']);
    $durasi = $datetime1->diff($datetime2)->days;
    if ($durasi <= 0) $durasi = 1;

    $total_biaya += $tarif * $durasi;  // 1 KANDANG × DURASI!
    
    $kandang_dihitung[] = $id_kandang;
}

// ============================================================
// 4. UPDATE TABEL PENITIPAN
// ============================================================
$tipe_pelanggan_db = $isAdmin ? 'offline' : 'online';
$id_pelanggan_db = ($id_pelanggan_final === "NULL") ? "NULL" : $id_pelanggan_final;
$id_user_admin_db = $isAdmin ? $session_id_user : "NULL";

$update_p_query = "UPDATE penitipan SET 
    total_biaya = $total_biaya,
    status_penitipan = 'Pending', 
    id_pelanggan = $id_pelanggan_db,
    tipe_pelanggan = '$tipe_pelanggan_db',
    id_user_admin = $id_user_admin_db
    WHERE id_penitipan = $id_penitipan";

if (!mysqli_query($koneksi, $update_p_query)) {
    echo "<script>alert('Gagal update database: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
    exit;
}

echo "<script>alert('Data booking berhasil diproses!'); window.location.href='../pembayaran/index.php?id_penitipan=$id_penitipan';</script>";
exit;
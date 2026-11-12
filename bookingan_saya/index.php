<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

// Mengambil id_user dari session login user
$id_user = $_SESSION['id_user'] ?? 1;

// ============================================================
// FILTER USER
// ============================================================
$filter_user = $_GET['filter'] ?? 'semua';
$filter_where = "";

if ($filter_user == 'ditolak') {
    $filter_where = "AND (p.status_penitipan = 'Ditolak' OR EXISTS (SELECT 1 FROM riwayat_perpanjangan rp WHERE rp.id_penitipan = p.id_penitipan AND rp.status = 'Ditolak'))";
} elseif ($filter_user == 'selesai') {
    $filter_where = "AND p.status_penitipan = 'Selesai'";
} elseif ($filter_user == 'sedang_dititipkan') {
    $filter_where = "AND p.status_penitipan = 'Sedang Dititipkan'";
}

// Hapus lock yang expired
mysqli_query($koneksi, "DELETE FROM booking_lock WHERE expires_at < NOW()");

// Query utama
$query = "SELECT p.*, 
            pay.kode_pembayaran,
            pay.metode_bayar,
            pay.status_bayar,
            pay.tgl_bayar,
            pay.jumlah_tagihan,
            pay.nama_pembayar,
            pay.tgl_konfirmasi,
            p.alasan_penolakan,
            bl.expires_at,
            bl.status AS lock_status,
                 (SELECT COUNT(*) FROM testimoni t WHERE t.id_penitipan = p.id_penitipan) AS sudah_testimoni
          FROM penitipan p
          LEFT JOIN pembayaran pay ON p.id_penitipan = pay.id_penitipan
          LEFT JOIN (
              SELECT id_penitipan, MAX(expires_at) AS expires_at, MAX(status) AS status
              FROM booking_lock
              WHERE id_penitipan IS NOT NULL
              GROUP BY id_penitipan
          ) bl ON p.id_penitipan = bl.id_penitipan
          WHERE p.id_user = $id_user
          $filter_where
          GROUP BY p.id_penitipan
          ORDER BY p.id_penitipan DESC";
$result = mysqli_query($koneksi, $query);

$title = 'Bookingan Saya';
require '../templates/header.php';
require '../templates/navbar.php';
require '../templates/sidebar.php';
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-sm-12">
                <div class="home-tab">
                    <div class="d-sm-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
                        <div>
                            <h2 class="welcome-text">Daftar <span class="text-black fw-bold">Bookingan Saya</span></h2>
                            <p class="text-muted">Pantau status pemesanan dan penitipan hewan kesayangan Anda di sini.</p>
                        </div>
                        <div class="d-flex gap-2 mt-2 mt-sm-0">
                            <a href="?filter=semua" class="btn btn-sm <?= $filter_user == 'semua' ? 'btn-primary' : 'btn-outline-secondary' ?>">Semua</a>
                            <a href="?filter=sedang_dititipkan" class="btn btn-sm <?= $filter_user == 'sedang_dititipkan' ? 'btn-success' : 'btn-outline-secondary' ?>">Sedang Dititipkan</a>
                            <a href="?filter=selesai" class="btn btn-sm <?= $filter_user == 'selesai' ? 'btn-dark' : 'btn-outline-secondary' ?>">Selesai</a>
                            <a href="?filter=sudah_diambil" class="btn btn-sm <?= $filter_user == 'sudah_diambil' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Sudah Diambil</a>
                            <a href="?filter=ditolak" class="btn btn-sm <?= $filter_user == 'ditolak' ? 'btn-danger' : 'btn-outline-secondary' ?>">Ditolak</a>
                        </div>
                    </div>

                    <div class="row">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)):
                                $id_penitipan = $row['id_penitipan'];
                                $status_penitipan = $row['status_penitipan'];

                                // ========== HITUNG SISA WAKTU LOCK ==========
                                $sisa_waktu = 0;
                                $waktu_habis = false;
                                if (!empty($row['expires_at'])) {
                                    $sisa_waktu = strtotime($row['expires_at']) - time();
                                    if ($sisa_waktu <= 0) {
                                        $waktu_habis = true;
                                    }
                                }

                                // ========== AMBIL DATA PER KANDANG ==========
                                $query_kandang = "SELECT 
                                ph.id_kandang,
                                k.kode_kandang,
                                k.nama_kandang,
                                k.tarif_per_hari,
                                COUNT(ph.id_hewan) AS jumlah_hewan,
                                GROUP_CONCAT(h.nama_hewan SEPARATOR ', ') AS daftar_hewan,
                                GROUP_CONCAT(h.id_hewan SEPARATOR ',') AS id_hewan_list,
                                MIN(ph.tgl_mulai) AS tgl_mulai_kandang,
                                MAX(ph.tgl_selesai) AS tgl_selesai_kandang,
                                DATEDIFF(MAX(ph.tgl_selesai), MIN(ph.tgl_mulai)) AS durasi_kandang,
                                rp.id_perpanjangan,
                                rp.kode_pembayaran,
                                rp.status AS status_perpanjangan,
                                rp.tgl_selesai_baru,
                                rp.biaya_tambahan,
                                rp.catatan_admin AS alasan_penolakan_perpanjangan,
                                rp.tgl_perpanjangan,
                                rp.nama_pembayar,
                                rp.jumlah_bayar,
                                rp.id_metode,
                                rp.catatan
                            FROM penitipan_hewan ph
                            LEFT JOIN kandang k ON ph.id_kandang = k.id_kandang
                            LEFT JOIN hewan h ON ph.id_hewan = h.id_hewan
                            LEFT JOIN riwayat_perpanjangan rp ON ph.id_penitipan = rp.id_penitipan 
                                AND ph.id_kandang = rp.id_kandang
                            WHERE ph.id_penitipan = $id_penitipan
                            GROUP BY ph.id_kandang, rp.id_perpanjangan
                            ORDER BY ph.id_kandang, rp.id_perpanjangan DESC";

                                $result_kandang = mysqli_query($koneksi, $query_kandang);
                                $kandang_list = [];
                                $total_tarif_awal = 0;
                                $total_biaya_perpanjangan = 0;
                                $tgl_selesai_terakhir = $row['tgl_selesai'];
                                $total_hewan_terisi = 0;
                                $total_kandang_dipesan = 0;

                                while ($kandang = mysqli_fetch_assoc($result_kandang)) {
                                    $tarif_kandang = $kandang['durasi_kandang'] * $kandang['tarif_per_hari'];
                                    $total_tarif_awal += $tarif_kandang;
                                    $total_kandang_dipesan++;
                                    if ($kandang['jumlah_hewan'] > 0) {
                                        $total_hewan_terisi++;
                                    }

                                    if (!empty($kandang['id_perpanjangan'])) {
                                        if ($kandang['status_perpanjangan'] == 'Disetujui') {
                                            $total_biaya_perpanjangan += $kandang['biaya_tambahan'];
                                            if (!empty($kandang['tgl_selesai_baru'])) {
                                                if (strtotime($kandang['tgl_selesai_baru']) > strtotime($tgl_selesai_terakhir)) {
                                                    $tgl_selesai_terakhir = $kandang['tgl_selesai_baru'];
                                                }
                                            }
                                        }
                                    }
                                    $kandang_list[] = $kandang;
                                }

                                if (!empty($row['jumlah_tagihan']) && $row['jumlah_tagihan'] > 0) {
                                    $total_bayar_akhir = $row['jumlah_tagihan'];
                                } else {
                                    $total_bayar_akhir = $row['total_biaya'] + $row['biaya_tambahan'];
                                }

                                $semua_hewan_terisi = ($total_hewan_terisi >= $total_kandang_dipesan);

                                $status_display = $status_penitipan;
                                $badge_bg = 'bg-secondary';
                                if ($status_penitipan == 'Pending') $badge_bg = 'bg-warning text-dark';
                                elseif ($status_penitipan == 'Menunggu Konfirmasi') $badge_bg = 'bg-info text-white';
                                elseif ($status_penitipan == 'Disetujui') $badge_bg = 'bg-primary text-white';
                                elseif ($status_penitipan == 'Sedang Dititipkan') $badge_bg = 'bg-success text-white';
                                elseif ($status_penitipan == 'Selesai') $badge_bg = 'bg-dark text-white';
                                elseif ($status_penitipan == 'Ditolak' || $status_penitipan == 'Dibatalkan') $badge_bg = 'bg-danger text-white';

                                // ============================================================
                                // KUMPULKAN SEMUA CARD
                                // ============================================================
                                // ============================================================
                                // KUMPULKAN SEMUA CARD
                                // ============================================================
                                $cards = [];

                                // ============================================================
                                // KHUSUS PENDING: 1 CARD GABUNGAN (TIDAK PER KANDANG)
                                // ============================================================
                                if ($status_penitipan == 'Pending') {
                                    $status_card = ($semua_hewan_terisi) ? 'Pending - Siap Bayar' : 'Pending - Lengkapi Data Hewan';
                                    $badge_card = ($semua_hewan_terisi) ? 'bg-info text-white' : 'bg-warning text-dark';
                                    $link_detail = ($semua_hewan_terisi) ? "../pembayaran/index.php?id_penitipan=" . $id_penitipan : "../booking/index.php?id_penitipan=" . $id_penitipan;
                                    $tombol_text = ($semua_hewan_terisi) ? 'Lanjut Bayar' : 'Lengkapi Data';

                                    $cards[] = [
                                        'status' => $status_card,
                                        'badge' => $badge_card,
                                        'tgl_selesai' => $tgl_selesai_terakhir,
                                        'total_biaya' => $total_bayar_akhir,
                                        'kode_pembayaran' => $row['kode_pembayaran'] ?? '-',
                                        'link' => $link_detail,
                                        'tombol' => $tombol_text,
                                        'modal' => false,
                                        'kandang' => null,
                                        'durasi' => (strtotime($tgl_selesai_terakhir) - strtotime($row['tgl_mulai'])) / (60 * 60 * 24) + 1,
                                        'is_riwayat' => false
                                    ];
                                } else {
                                    // Untuk status lainnya (Menunggu Konfirmasi, Disetujui, Sedang Dititipkan)
                                    foreach ($kandang_list as $kandang) {
                                        $durasi = max($kandang['durasi_kandang'], 1);
                                        $tarif_per_hari = $kandang['tarif_per_hari'];
                                        $total_tarif_kandang = $durasi * $tarif_per_hari;
                                        $is_diambil = ($status_penitipan == 'Selesai' || $kandang['tgl_selesai_kandang'] <= date('Y-m-d'));
                                        $has_perpanjangan = !empty($kandang['id_perpanjangan']);
                                        $status_perpanjangan = $kandang['status_perpanjangan'] ?? '';

                                        // 1. CARD RIWAYAT PERPANJANGAN (Ditolak / Menunggu)
                                        if ($has_perpanjangan && in_array($status_perpanjangan, ['Ditolak', 'Menunggu Konfirmasi'])) {
                                            if ($status_perpanjangan == 'Menunggu Konfirmasi') {
                                                $status_card = 'Perpanjangan Menunggu Konfirmasi';
                                                $badge_card = 'bg-warning text-dark';
                                                $tgl_selesai_tampil = $kandang['tgl_selesai_kandang'];
                                                $total_biaya_tampil = $kandang['biaya_tambahan'] ?? 0;
                                                $kode_pay_tampil = $kandang['kode_perpanjangan'] ?? '-';
                                                $link_detail = "../masa_aktif/detail_pembayaran.php?id_penitipan=$id_penitipan&id_kandang=" . $kandang['id_kandang'];
                                                $tombol_text = 'Detail Perpanjangan';
                                                $modal_perpanjangan = false;
                                            } else {
                                                $status_card = 'Perpanjangan Ditolak';
                                                $badge_card = 'bg-danger text-white';
                                                $tgl_selesai_tampil = $kandang['tgl_selesai_kandang'];
                                                $total_biaya_tampil = $total_tarif_kandang;
                                                $kode_pay_tampil = $row['kode_pembayaran'] ?? '-';
                                                $link_detail = "#modalPerpanjanganDitolak{$kandang['id_kandang']}";
                                                $tombol_text = 'Lihat Alasan';
                                                $modal_perpanjangan = true;
                                            }
                                            $cards[] = [
                                                'status' => $status_card,
                                                'badge' => $badge_card,
                                                'tgl_selesai' => $tgl_selesai_tampil,
                                                'total_biaya' => $total_biaya_tampil,
                                                'kode_pembayaran' => $kode_pay_tampil,
                                                'link' => $link_detail,
                                                'tombol' => $tombol_text,
                                                'modal' => $modal_perpanjangan,
                                                'kandang' => $kandang,
                                                'durasi' => $durasi,
                                                'is_riwayat' => true
                                            ];
                                        }

                                        // 2. CARD STATUS TERKINI (hanya jika booking BELUM Selesai)
                                        if ($status_penitipan != 'Selesai') {
                                            if ($status_penitipan == 'Menunggu Konfirmasi') {
                                                $status_card = 'Menunggu Konfirmasi';
                                                $badge_card = 'bg-info text-white';
                                                $tgl_selesai_tampil = $kandang['tgl_selesai_kandang'];
                                                $total_biaya_tampil = $total_tarif_kandang;
                                                $link_detail = "../pembayaran/detail.php?id_penitipan=$id_penitipan&id_kandang=" . $kandang['id_kandang'];
                                                $tombol_text = 'Detail';
                                                $modal_perpanjangan = false;
                                            } elseif ($status_penitipan == 'Disetujui') {
                                                $status_card = 'Disetujui - Antar Hewan Anda Ke Toko';
                                                $badge_card = 'bg-primary text-white';
                                                $tgl_selesai_tampil = $kandang['tgl_selesai_kandang'];
                                                $total_biaya_tampil = $total_tarif_kandang;
                                                $link_detail = "../pembayaran/detail.php?id_penitipan=$id_penitipan&id_kandang=" . $kandang['id_kandang'];
                                                $tombol_text = 'Detail';
                                                $modal_perpanjangan = false;
                                            } elseif ($status_penitipan == 'Ditolak' || $status_penitipan == 'Dibatalkan') {
                                                $status_card = 'Ditolak';
                                                $badge_card = 'bg-danger text-white';
                                                $tgl_selesai_tampil = $kandang['tgl_selesai_kandang'];
                                                $total_biaya_tampil = $total_tarif_kandang;
                                                $link_detail = "riwayat_card.php?id_penitipan=$id_penitipan&id_kandang=" . $kandang['id_kandang'];
                                                $tombol_text = 'Detail';
                                                $modal_perpanjangan = false;
                                            } elseif ($is_diambil) {
                                                $status_card = 'Sudah Diambil';
                                                $badge_card = 'bg-dark text-white';
                                                $tgl_selesai_tampil = $kandang['tgl_selesai_kandang'];
                                                $total_biaya_tampil = $total_tarif_kandang;
                                                $link_detail = "riwayat_card.php?id_penitipan=$id_penitipan&id_kandang=" . $kandang['id_kandang'];
                                                $tombol_text = 'Detail';
                                                $modal_perpanjangan = false;
                                            } elseif ($has_perpanjangan && $status_perpanjangan == 'Disetujui') {
                                                $status_card = 'Sedang Dititipkan (Diperpanjang)';
                                                $badge_card = 'bg-success text-white';
                                                $tgl_selesai_tampil = $kandang['tgl_selesai_baru'] ?? $kandang['tgl_selesai_kandang'];
                                                $total_biaya_tampil = $total_tarif_kandang + ($kandang['biaya_tambahan'] ?? 0);
                                                $link_riwayat = "riwayat_card.php?id_penitipan=$id_penitipan&id_kandang=" . $kandang['id_kandang'];
                                                $link_perpanjangan = "../masa_aktif/detail_pembayaran.php?id_penitipan=$id_penitipan&id_kandang=" . $kandang['id_kandang'];
                                                $cards[] = [
                                                    'status' => $status_card,
                                                    'badge' => $badge_card,
                                                    'tgl_selesai' => $tgl_selesai_tampil,
                                                    'total_biaya' => $total_biaya_tampil,
                                                    'kode_pembayaran' => $row['kode_pembayaran'] ?? '-',
                                                    'modal' => false,
                                                    'kandang' => $kandang,
                                                    'durasi' => $durasi,
                                                    'is_riwayat' => false,
                                                    'tombol_ganda' => true,
                                                    'link_riwayat' => $link_riwayat,
                                                    'link_perpanjangan' => $link_perpanjangan
                                                ];
                                                continue; // <-- TAMBAHKAN INI
                                            } else {
                                                $status_card = 'Sedang Dititipkan';
                                                $badge_card = 'bg-success text-white';
                                                $tgl_selesai_tampil = $kandang['tgl_selesai_kandang'];
                                                $total_biaya_tampil = $total_tarif_kandang;
                                                $link_detail = "riwayat_card.php?id_penitipan=$id_penitipan&id_kandang=" . $kandang['id_kandang'];
                                                $tombol_text = 'Detail';
                                                $modal_perpanjangan = false;
                                            }

                                            $cards[] = [
                                                'status' => $status_card,
                                                'badge' => $badge_card,
                                                'tgl_selesai' => $tgl_selesai_tampil,
                                                'total_biaya' => $total_biaya_tampil,
                                                'kode_pembayaran' => $row['kode_pembayaran'] ?? '-',
                                                'link' => $link_detail,
                                                'tombol' => $tombol_text,
                                                'modal' => $modal_perpanjangan,
                                                'kandang' => $kandang,
                                                'durasi' => $durasi,
                                                'is_riwayat' => false
                                            ];
                                        }
                                    }
                                }

                                // ... lanjut ke bagian // 3. JIKA BOOKING SELESAI ATAU DITOLAK ...

                                // 3. JIKA BOOKING SELESAI ATAU DITOLAK (DIGABUNG JADI 1 CARD)
                                if (in_array($status_penitipan, ['Selesai', 'Ditolak', 'Dibatalkan'])) {
                                    // Ambil semua card riwayat (perpanjangan ditolak/menunggu)
                                    $cards = array_filter($cards, function ($c) {
                                        return $c['is_riwayat'] === true;
                                    });

                                    $durasi_gabungan = (strtotime($tgl_selesai_terakhir) - strtotime($row['tgl_mulai'])) / (60 * 60 * 24) + 1;

                                    // Tentukan status dan tombol
                                    if ($status_penitipan == 'Selesai') {
                                        $status_card = 'Selesai';
                                        $badge_card = 'bg-dark text-white';
                                        $link_card = "Riwayat_bookingan.php?id_penitipan=$id_penitipan";
                                        $tombol_card = 'Riwayat Lengkap';
                                        $modal_card = false;
                                    } else { // Ditolak atau Dibatalkan (digabung, TIDAK dipecah per kandang)
                                        $status_card = $status_penitipan;
                                        $badge_card = 'bg-danger text-white';
                                        $link_card = "#modalDitolak{$id_penitipan}";
                                        $tombol_card = 'Lihat Alasan';
                                        $modal_card = true;
                                    }
                                    $total_biaya_final = $total_bayar_akhir + $total_biaya_perpanjangan;
                                    $sudah_testimoni = $row['sudah_testimoni'] ?? 0;
                                    $cards_gabungan = [
                                        'status' => $status_card,
                                        'badge' => $badge_card,
                                        'tgl_selesai' => $tgl_selesai_terakhir,
                                        'total_biaya' => $total_biaya_final,   // ✅ BENAR
                                        'kode_pembayaran' => $row['kode_pembayaran'] ?? '-',
                                        'link' => $link_card,
                                        'tombol' => $tombol_card,
                                        'modal' => $modal_card,
                                        'kandang' => null,
                                        'durasi' => $durasi_gabungan,
                                        'is_riwayat' => false,
                                        'sudah_testimoni' => $sudah_testimoni,
                                        'id_penitipan'    => $id_penitipan
                                    ];
                                    array_unshift($cards, $cards_gabungan);
                                }
                                // 4. TAMPILKAN SEMUA CARD
                                if (empty($cards)) {
                                    echo '<div class="col-12"><div class="alert alert-info">Tidak ada data untuk filter ini.</div></div>';
                                } else {
                                    // ===== FILTER CARD =====
                                    $filtered_cards = [];
                                    foreach ($cards as $card) {
                                        $status = $card['status'];
                                        $show = false;

                                        if ($filter_user == 'semua') {
                                            $show = true;
                                        } elseif ($filter_user == 'sedang_dititipkan') {
                                            if (strpos($status, 'Sedang Dititipkan') !== false) $show = true;
                                        } elseif ($filter_user == 'menunggu_konfirmasi') {
                                            if (strpos($status, 'Menunggu Konfirmasi') !== false || strpos($status, 'Perpanjangan Menunggu Konfirmasi') !== false) $show = true;
                                        } elseif ($filter_user == 'selesai') {
                                            if ($status == 'Selesai') $show = true;
                                        } elseif ($filter_user == 'sudah_diambil') {
                                            if ($status == 'Sudah Diambil') $show = true;
                                        } elseif ($filter_user == 'ditolak') {
                                            if (strpos($status, 'Ditolak') !== false) $show = true;
                                        }

                                        if ($show) $filtered_cards[] = $card;
                                    }

                                    if (empty($filtered_cards)) {
                                        echo '<div class="col-12"><div class="alert alert-info">Tidak ada data untuk filter ini.</div></div>';
                                    } else {
                                        foreach ($filtered_cards as $card) {   // <-- PERUBAHAN: $cards → $filtered_cards
                                            $kandang = $card['kandang'];
                            ?>
                                            <div class="col-md-4 grid-margin stretch-card">
                                                <div class="card card-rounded shadow-sm border">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                                            <div>
                                                                <?php
                                                                // Tentukan judul & kode yang ditampilkan
                                                                if (!empty($row['kode_pembayaran'])) {
                                                                    $label_kode = 'Kode Pembayaran';
                                                                    $kode_tampil = $row['kode_pembayaran'];
                                                                } else {
                                                                    $label_kode = 'Kode Booking';
                                                                    $kode_tampil = $row['kode_booking'];
                                                                }
                                                                ?>
                                                                <h4 class="card-title mb-0"><?= $label_kode ?></h4>
                                                                <span class="text-success"><?= htmlspecialchars($kode_tampil) ?></span>
                                                                <br><br>
                                                                <span class="badge <?= $card['badge'] ?>"><?= htmlspecialchars($card['status']) ?></span>
                                                                <?php if ($status_penitipan == 'Pending'):
                                                                    $expires_time = strtotime($row['created_at']) + 3600; // 1 jam
                                                                    $sisa_waktu = $expires_time - time();
                                                                    if ($sisa_waktu > 0):
                                                                        $menit = floor($sisa_waktu / 60);
                                                                        $detik = $sisa_waktu % 60;
                                                                ?>
                                                                        <div class="mt-2 p-2 bg-opacity-10 rounded-3 border ">
                                                                            <small class="fw-bold text-dark">⏰ Waktu tersisa: </small>
                                                                            <span class="badge bg-warning text-dark" style="font-size: 16px; padding: 6px 12px;">
                                                                                <i class="mdi mdi-clock"></i>
                                                                                <span id="timer-<?= $id_penitipan ?>" data-expires="<?= $expires_time ?>">
                                                                                    <?= $menit ?>:<?= str_pad($detik, 2, '0', STR_PAD_LEFT) ?>
                                                                                </span>
                                                                            </span>
                                                                            <small class="text-muted d-block mt-1" style="font-size: 11px;">⚠️ Jika waktu habis, bookingan akan otomatis dibatalkan</small>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <div class="mt-2 p-2 bg-danger bg-opacity-10 rounded-3 border border-danger">
                                                                            <small class="fw-bold text-dark">⏰ Waktu Habis!</small>
                                                                            <br><span class="badge bg-danger text-dark" style="font-size: 14px;">Bookingan akan otomatis dibatalkan</span>
                                                                        </div>
                                                                <?php endif;
                                                                endif; ?>
                                                            </div>
                                                        </div>

                                                        <div class="table-responsive text-dark mb-4">
                                                            <table class="table table-borderless table-sm">
                                                                <?php if ($kandang): ?>
                                                                    <tr>
                                                                        <td class="text-muted ps-0" style="width: 40%;">Kandang</td>
                                                                        <td>: <strong><?= htmlspecialchars($kandang['kode_kandang'] . ' - ' . $kandang['nama_kandang']) ?></strong></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="text-muted ps-0">Hewan</td>
                                                                        <td>: <strong><?= htmlspecialchars($kandang['daftar_hewan'] ?? '-') ?></strong></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="text-muted ps-0">Jumlah Hewan</td>
                                                                        <td>: <strong><?= $kandang['jumlah_hewan'] ?> ekor</strong></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="text-muted ps-0">Tgl Mulai</td>
                                                                        <td>: <strong><?= date('d M Y', strtotime($kandang['tgl_mulai_kandang'])) ?></strong></td>
                                                                    </tr>
                                                                <?php else: ?>
                                                                    <tr>
                                                                        <td class="text-muted ps-0" style="width: 40%;">Tgl Mulai</td>
                                                                        <td>: <strong><?= date('d M Y', strtotime($row['tgl_mulai'])) ?></strong></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="text-muted ps-0">Total Kandang</td>
                                                                        <td>: <strong><?= count($kandang_list) ?> kandang</strong></td>
                                                                    </tr>
                                                                <?php endif; ?>
                                                                <tr>
                                                                    <td class="text-muted ps-0">Tgl Selesai</td>
                                                                    <td>: <strong><?= date('d M Y', strtotime($card['tgl_selesai'])) ?></strong></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-muted ps-0">Durasi</td>
                                                                    <td>: <strong><?= $card['durasi'] ?> Hari</strong></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-muted ps-0">Total Tarif</td>
                                                                    <td class="fw-bold text-primary">: Rp <?= number_format($card['total_biaya'], 0, ',', '.') ?></td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                        <div class="d-flex gap-2 justify-content-between pt-2 border-top">
                                                            <?php if ($status_penitipan == 'Pending'):
                                                                // Hitung ulang sisa waktu (pakai created_at)
                                                                $expires_time = strtotime($row['created_at']) + 3600;
                                                                $sisa_waktu = $expires_time - time();
                                                                $waktu_habis = ($sisa_waktu <= 0);
                                                            ?>
                                                                <?php if (!$waktu_habis): ?>
                                                                    <a href="<?= $card['link'] ?>" class="btn btn-warning btn-sm py-2 w-50 text-center">Lengkapi Data</a>
                                                                <?php else: ?>
                                                                    <span class="btn btn-secondary btn-sm py-2 w-50 text-center disabled">⏰ Waktu Habis</span>
                                                                <?php endif; ?>
                                                                <form method="POST" action="proses.php" class="w-50" onsubmit="return confirm('Yakin hapus bookingan ini?');">
                                                                    <input type="hidden" name="action" value="delete_booking">
                                                                    <input type="hidden" name="id_penitipan" value="<?= $id_penitipan ?>">
                                                                    <button type="submit" class="btn btn-danger btn-sm py-2 w-100">Hapus</button>
                                                                </form>
                                                            <?php else: ?>
    <?php if (isset($card['tombol_ganda']) && $card['tombol_ganda'] === true): ?>
        <a href="<?= $card['link_riwayat'] ?>" class="btn btn-outline-info btn-sm py-2 w-50 text-center">Riwayat Kandang</a>
        <a href="<?= $card['link_perpanjangan'] ?>" class="btn btn-outline-primary btn-sm py-2 w-50 text-center">Detail Perpanjangan</a>
    <?php else: ?>
        <?php if ($card['status'] == 'Selesai'): ?>
            <!-- KHUSUS SELESAI: 2 TOMBOL -->
            <a href="<?= $card['link'] ?>" class="btn btn-outline-info btn-sm py-2 w-50 text-center">Riwayat Lengkap</a>
            <?php if ($card['sudah_testimoni'] == 0): ?>
                <a href="#" class="btn btn-success btn-sm py-2 w-50 text-center" data-bs-toggle="modal" data-bs-target="#modalTestimoni" data-id-penitipan="<?= $card['id_penitipan'] ?>" data-sudah-testimoni="0">Beri Testimoni</a>
            <?php else: ?>
                <a href="#" class="btn btn-secondary btn-sm py-2 w-50 text-center" data-bs-toggle="modal" data-bs-target="#modalTestimoni" data-id-penitipan="<?= $card['id_penitipan'] ?>" data-sudah-testimoni="1">Lihat Testimoni</a>
            <?php endif; ?>
        <?php else: ?>
            <!-- STATUS LAINNYA: SATU TOMBOL PENUH -->
            <a href="<?= $card['link'] ?>" class="btn btn-outline-info btn-sm py-2 w-100 text-center" <?= $card['modal'] ? 'data-bs-toggle="modal"' : '' ?>>
                <?= $card['tombol'] ?>
            </a>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php if ($card['modal']): ?>
                                                <div class="modal fade" id="modalPerpanjanganDitolak<?= $kandang['id_kandang'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title"><i class="mdi mdi-close-circle-outline me-2"></i> Detail Penolakan Perpanjangan</h5>
                                                                <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3"><label class="fw-bold text-muted small">Status</label>
                                                                    <p class="mb-0"><span class="badge bg-danger">Perpanjangan Ditolak</span></p>
                                                                </div>
                                                                <div class="mb-3"><label class="fw-bold text-muted small">Kode Booking</label>
                                                                    <p class="mb-0"><strong><?= htmlspecialchars($row['kode_booking']) ?></strong></p>
                                                                </div>
                                                                <div class="mb-3"><label class="fw-bold text-muted small">Kode Pembayaran Perpanjangan</label>
                                                                    <p class="mb-0"><strong><?= htmlspecialchars($kandang['kode_perpanjangan'] ?? '-') ?></strong></p>
                                                                </div>
                                                                <div class="mb-3"><label class="fw-bold text-muted small">Kandang</label>
                                                                    <p class="mb-0"><strong><?= htmlspecialchars($kandang['kode_kandang'] . ' - ' . $kandang['nama_kandang']) ?></strong></p>
                                                                </div>
                                                                <div class="mb-3"><label class="fw-bold text-muted small">Alasan Penolakan</label>
                                                                    <div class="alert alert-secondary p-2 mb-0" style="background-color: #f8f9fa; border-left: 3px solid #dc3545;"><?= htmlspecialchars($kandang['alasan_penolakan_perpanjangan'] ?? 'Tidak ada alasan') ?></div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-6"><label class="fw-bold text-muted small">Tanggal Perpanjangan</label>
                                                                        <p class="mb-0"><?= !empty($kandang['tgl_perpanjangan']) ? date('d M Y', strtotime($kandang['tgl_perpanjangan'])) : '-' ?></p>
                                                                    </div>
                                                                    <div class="col-6"><label class="fw-bold text-muted small">Tanggal Selesai Awal</label>
                                                                        <p class="mb-0"><?= date('d M Y', strtotime($kandang['tgl_selesai_kandang'])) ?></p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                            <?php
                                        }
                                    }
                                }
                            endwhile; ?>

                            <!-- ============================================================ -->
                            <!-- MODAL DETAIL PENOLAKAN                                       -->
                            <!-- ============================================================ -->
                            <?php
                            mysqli_data_seek($result, 0);
                            while ($row = mysqli_fetch_assoc($result)):
                                if ($row['status_penitipan'] == 'Ditolak' || $row['status_penitipan'] == 'Dibatalkan'):
                            ?>
                                    <div class="modal fade" id="modalDitolak<?= $row['id_penitipan'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title">
                                                        <i class="mdi mdi-close-circle-outline me-2"></i> Detail Penolakan
                                                    </h5>
                                                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="fw-bold text-muted small">Status</label>
                                                        <p class="mb-0"><span class="badge bg-danger"><?= htmlspecialchars($row['status_penitipan']) ?></span></p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="fw-bold text-muted small">Kode Booking</label>
                                                        <p class="mb-0"><strong><?= htmlspecialchars($row['kode_booking']) ?></strong></p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="fw-bold text-muted small">Kode Pembayaran</label>
                                                        <p class="mb-0"><strong><?= htmlspecialchars($row['kode_pembayaran']) ?></strong></p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="fw-bold text-muted small">Alasan Penolakan</label>
                                                        <div class="alert alert-secondary p-2 mb-0" style="background-color: #f8f9fa; border-left: 3px solid #dc3545;">
                                                            <?= htmlspecialchars($row['alasan_penolakan'] ?? 'Tidak ada alasan') ?>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <label class="fw-bold text-muted small">Tanggal Ditolak</label>
                                                            <p class="mb-0">
                                                                <?= !empty($row['tgl_konfirmasi']) ? date('d M Y', strtotime($row['tgl_konfirmasi'])) : '-' ?>
                                                            </p>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="fw-bold text-muted small">Waktu Ditolak</label>
                                                            <p class="mb-0">
                                                                <?= !empty($row['tgl_konfirmasi']) ? date('H:i:s', strtotime($row['tgl_konfirmasi'])) : '-' ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <?php
                                endif;
                            endwhile;
                            ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-5">
                                <div class="card card-rounded">
                                    <div class="card-body">
                                        <p class="text-muted mb-3">Anda belum memiliki riwayat booking.</p>
                                        <a href="../ketersediaan_kandang/index.php" class="btn btn-primary text-white px-4">Sewa Kandang Baru</a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- ============================================================ -->
<!-- MODAL TESTIMONI (TAMBAH / LIHAT)                              -->
<!-- ============================================================ -->
<div class="modal fade" id="modalTestimoni" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalTestimoniTitle"><i class="mdi mdi-star-outline me-2"></i> Testimoni</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalTestimoniBody">
                <!-- Isi akan diisi oleh JavaScript -->
            </div>
            <div class="modal-footer" id="modalTestimoniFooter">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
    <?php require '../templates/footer.php'; ?>
</div>

<!-- ============================================================ -->
<!-- SCRIPT COUNTDOWN TIMER                                        -->
<!-- ============================================================ -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const timers = document.querySelectorAll('[id^="timer-"]');
        timers.forEach(function(el) {
            const expiresTime = parseInt(el.dataset.expires) * 1000;

            function updateTimer() {
                const now = new Date().getTime();
                const distance = expiresTime - now;
                if (distance <= 0) {
                    el.innerHTML = '⏰ Waktu habis!';
                    el.parentElement.classList.remove('bg-warning');
                    el.parentElement.classList.add('bg-danger');
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                    return;
                }
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                el.innerHTML = minutes + ':' + seconds.toString().padStart(2, '0');
                if (distance < 300000) {
                    el.style.color = 'red';
                    el.style.fontWeight = 'bold';
                }
            }
            updateTimer();
            setInterval(updateTimer, 1000);
        });
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================================
    // SAAT TOMBOL "TESTIMONI" DIKLIK, ISI MODAL SESUAI KONDISI
    // ============================================================
    const triggerButtons = document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target="#modalTestimoni"]');
    triggerButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            const idPenitipan = this.dataset.idPenitipan;
            const sudahTestimoni = parseInt(this.dataset.sudahTestimoni);
            const modalTitle = document.getElementById('modalTestimoniTitle');
            const modalBody = document.getElementById('modalTestimoniBody');
            const modalFooter = document.getElementById('modalTestimoniFooter');

            if (sudahTestimoni == 0) {
                // ===== BELUM ADA TESTIMONI: TAMPILKAN FORM =====
                modalTitle.innerHTML = '<i class="mdi mdi-star-outline me-2"></i> Beri Testimoni';
                modalBody.innerHTML = `
                    <form id="formTestimoni" action="proses_testimoni.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id_penitipan" value="${idPenitipan}">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Rating <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-3 justify-content-center align-items-center">
                                ${[5,4,3,2,1].map(i => `
                                    <div class="d-flex align-items-center gap-1">
                                        <label class="form-check-label" for="modal_rating${i}" style="font-size: 1.2rem; cursor: pointer;">${'⭐'.repeat(i)}</label>
                                        <input class="form-check-input" type="radio" name="rating" id="modal_rating${i}" value="${i}" required>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Komentar</label>
                            <textarea class="form-control" name="komentar" rows="3" placeholder="Tulis pengalaman Anda..." style="resize: vertical;"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Foto Testimoni</label>
                            <input type="file" class="form-control" name="foto_testimoni" accept="image/*" id="inputFotoTestimoni">
                            <small class="text-muted">Unggah foto (opsional).</small>
                            <!-- PREVIEW GAMBAR -->
                            <div id="previewContainer" class="mt-2" style="display:none;">
                                <img id="fotoPreview" src="#" alt="Preview Foto" style="max-width: 100%; max-height: 200px; border-radius: 8px; border: 1px solid #ddd; padding: 5px;">
                            </div>
                        </div>
                        <div id="modalTestimoniMessage" class="mt-2"></div>
                        <button type="submit" class="btn btn-success w-100 mt-3" id="btnSubmitTestimoni">Kirim Testimoni</button>
                    </form>
                `;
                modalFooter.innerHTML = `
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                `;

                // Preview gambar
                const inputFoto = document.getElementById('inputFotoTestimoni');
                if (inputFoto) {
                    inputFoto.addEventListener('change', function(e) {
                        const previewContainer = document.getElementById('previewContainer');
                        const previewImg = document.getElementById('fotoPreview');
                        const file = this.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(event) {
                                previewImg.src = event.target.result;
                                previewContainer.style.display = 'block';
                            };
                            reader.readAsDataURL(file);
                        } else {
                            previewContainer.style.display = 'none';
                            previewImg.src = '#';
                        }
                    });
                }

                // Kirim form via AJAX
                const form = document.getElementById('formTestimoni');
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const messageDiv = document.getElementById('modalTestimoniMessage');
                    const submitBtn = document.getElementById('btnSubmitTestimoni');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = 'Mengirim...';
                    fetch('proses_testimoni.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            messageDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                            setTimeout(function() {
                                $('#modalTestimoni').modal('hide');
                                location.reload();
                            }, 1500);
                        } else {
                            messageDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = 'Kirim Testimoni';
                        }
                    })
                    .catch(error => {
                        messageDiv.innerHTML = '<div class="alert alert-danger">Terjadi kesalahan. Silakan coba lagi.</div>';
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Kirim Testimoni';
                    });
                });

                // Reset preview saat modal ditutup
                $('#modalTestimoni').on('hidden.bs.modal', function() {
                    const previewContainer = document.getElementById('previewContainer');
                    const previewImg = document.getElementById('fotoPreview');
                    if (previewContainer) {
                        previewContainer.style.display = 'none';
                        previewImg.src = '#';
                    }
                });

            } else {
                // ===== SUDAH ADA TESTIMONI: TAMPILKAN DATA =====
                modalTitle.innerHTML = '<i class="mdi mdi-star me-2"></i> Lihat Testimoni';
                
                // Tampilkan loading sementara
                modalBody.innerHTML = `
                    <div class="text-center py-3">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat testimoni...</p>
                    </div>
                `;
                
                modalFooter.innerHTML = `
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                `;

                // Ambil data testimoni via AJAX
                fetch('get_data.php?id_penitipan=' + idPenitipan)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const t = data.testimoni;
                            modalBody.innerHTML = `
                                <div class="text-center">
                                    <div style="font-size: 3rem;">${'⭐'.repeat(t.rating)}</div>
                                    <p class="mt-2"><strong>${t.rating} / 5</strong></p>
                                    ${t.foto_testimoni ? `<img src="../${t.foto_testimoni}" class="img-fluid rounded mb-3" style="max-height:200px;">` : ''}
                                    <p class="text-muted">${t.komentar || 'Tidak ada komentar.'}</p>
                                    <small class="text-muted">Dikirim: ${new Date(t.created_at).toLocaleDateString('id-ID')}</small>
                                </div>
                            `;
                        } else {
                            modalBody.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                        }
                    })
                    .catch(error => {
                        modalBody.innerHTML = `<div class="alert alert-danger">Gagal memuat testimoni.</div>`;
                    });
            }
        });
    });
});
</script>
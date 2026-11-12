<?php
require_once 'koneksi.php';

// ============================================================
// AMBIL SEMUA DATA DARI DATABASE
// ============================================================

// 1. Pengaturan Umum
$umum = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pengaturan_umum LIMIT 1"));

// 2. Banner
$banner = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM banner LIMIT 1"));

// 3. Keunggulan (hanya yang aktif)
$keunggulan = mysqli_query($koneksi, "SELECT * FROM keunggulan WHERE is_active = 1 ORDER BY urutan ASC");

// 4. Cara Penitipan (hanya yang aktif)
$cara = mysqli_query($koneksi, "SELECT * FROM cara_penitipan WHERE is_active = 1 ORDER BY urutan ASC");

// 5. Kandang (hanya yang aktif)
$kandang = mysqli_query($koneksi, "SELECT * FROM kandang WHERE is_active = 1 ORDER BY kode_kandang ASC");

// 6. Testimoni (hanya yang aktif, join dengan users)
$testimoni = mysqli_query($koneksi, "
    SELECT t.*, u.nama_lengkap 
    FROM testimoni t 
    LEFT JOIN users u ON t.id_user = u.id_user 
    WHERE t.is_active = 1 
    ORDER BY t.created_at DESC 
    LIMIT 3
");

// 7. Statistik
$stat_hewan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM penitipan_hewan"));
$stat_kandang = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kandang WHERE is_active = 1"));
$stat_rating = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT AVG(rating) as avg FROM testimoni WHERE is_active = 1"));
$avg_rating = round($stat_rating['avg'] ?? 0, 1);

// Fungsi helper
function getVal($data, $key, $default = '') {
    return $data[$key] ?? $default;
}

// Tentukan status toko
$status_toko = getVal($umum, 'status_toko', 'buka');
$status_badge = $status_toko == 'buka' ? 'Sedang Buka' : 'Tutup';
$status_color = $status_toko == 'buka' ? '#4caf50' : '#e74c3c';

// Ambil data kandang dengan status realtime + foto
$kandang_data = [];
while ($row = mysqli_fetch_assoc($kandang)) {
    $id_kd = $row['id_kandang'];
    $today = date('Y-m-d');
    $query_occ = "SELECT COUNT(*) as terpakai FROM penitipan_hewan ph 
                  JOIN penitipan p ON ph.id_penitipan = p.id_penitipan
                  WHERE ph.id_kandang = $id_kd 
                  AND p.status_penitipan IN ('Disetujui', 'Sedang Dititipkan')
                  AND '$today' BETWEEN p.tgl_mulai AND p.tgl_selesai";
    $res_occ = mysqli_query($koneksi, $query_occ);
    $occ_data = mysqli_fetch_assoc($res_occ);
    $slot_terpakai = (int)($occ_data['terpakai'] ?? 0);
    $stok_fisik = (int)$row['stok'];
    $sisa_stok = $stok_fisik - $slot_terpakai;
    if ($sisa_stok < 0) $sisa_stok = 0;
    $total_kapasitas = $row['kapasitas'] * $stok_fisik;
    $status_real = ($slot_terpakai >= $total_kapasitas) ? 'penuh' : 'tersedia';
    
    $kandang_data[] = [
        'kode' => $row['kode_kandang'],
        'nama' => $row['nama_kandang'],
        'tipe' => strtolower($row['tipe_hewan']),
        'ukuran' => $row['ukuran'],
        'icon' => $row['tipe_hewan'] == 'Anjing' ? '🐕' : '🐈',
        'status' => $status_real,
        'tarif' => $row['tarif_per_hari'],
        'sisa_stok' => $sisa_stok,
        'total_stok' => $stok_fisik,
        'kapasitas' => $row['kapasitas'],
        'foto' => $row['foto_kandang'] ?? ''
    ];
}

// Grup kandang untuk tarif (dengan kapasitas)
$tarif_anjing = [];
$tarif_kucing = [];
foreach ($kandang_data as $k) {
    $item = [
        'ukuran' => $k['ukuran'],
        'kode' => $k['kode'],
        'tarif' => $k['tarif'],
        'kapasitas' => $k['kapasitas']
    ];
    if ($k['tipe'] == 'anjing') {
        $tarif_anjing[] = $item;
    } else {
        $tarif_kucing[] = $item;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Chilanka&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Montserrat',sans-serif;font-size:14px;color:#333}
.secondary-font{font-family:'Chilanka',cursive}
.text-primary{color:#e67e22!important}
.btn-primary{background:#e67e22;border-color:#e67e22}
.btn-outline-dark{border-color:#333;color:#333}
.btn-outline-dark:hover{background:#333;color:#fff}
header{border-bottom:1px solid #eee}
.main-logo img{height:50px}
.nav-link{color:#333;font-size:13px;font-weight:500;padding:8px 4px!important}
.nav-link:hover,.nav-link.active{color:#e67e22}
.navbar-toggler{border:none}

/* BANNER */
#banner{background:#F9F3EC;padding:40px 0}
.banner-title{font-size:2.2rem;line-height:1.2}
.swiper-pagination-bullet-active{background:#e67e22}

/* HERO STATS */
.stat-card{background:#fff;border:1px solid #eee;border-radius:12px;padding:20px;text-align:center}
.stat-number{font-size:2rem;font-weight:700;color:#e67e22}
.stat-label{font-size:12px;color:#777;margin-top:4px}

/* SERVICES */
#services{background:#fff;padding:60px 0}
.service-card{border:1px solid #eee;border-radius:12px;padding:28px 20px;text-align:center;transition:.2s}
.service-card:hover{border-color:#e67e22;box-shadow:0 4px 16px rgba(230,126,34,.12)}
.service-icon{font-size:2.5rem;color:#e67e22;margin-bottom:12px;display:block}

/* TARIF */
#tarif{background:#F9F3EC;padding:60px 0}
.tarif-card{background:#fff;border-radius:12px;border:1px solid #eee;overflow:hidden}
.tarif-header{padding:20px;text-align:center;color:#fff}
.tarif-header.anjing{background:#e67e22}
.tarif-header.kucing{background:#c0392b}
.tarif-body{padding:16px}
.tarif-row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:13px}
.tarif-row:last-child{border-bottom:none}
.tarif-price{font-weight:700;color:#e67e22;font-size:14px}

/* KETERSEDIAAN */
#ketersediaan{background:#fff;padding:60px 0}
.kandang-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px}
.kandang-card{border:1px solid #eee;border-radius:10px;padding:14px;text-align:center;font-size:12px}
.kandang-card.tersedia{border-color:#27ae60;background:#f0faf4}
.kandang-card.penuh{border-color:#e74c3c;background:#fdf3f3}
.kandang-icon{font-size:1.8rem;display:block;margin-bottom:6px}
.badge-tersedia{background:#27ae60;color:#fff;font-size:10px;padding:3px 8px;border-radius:20px;display:inline-block;margin-top:4px}
.badge-penuh{background:#e74c3c;color:#fff;font-size:10px;padding:3px 8px;border-radius:20px;display:inline-block;margin-top:4px}
.kandang-img{width:50px;height:50px;object-fit:cover;border-radius:8px;display:block;margin:0 auto 6px;}

/* CARA PENITIPAN */
#cara{background:#F9F3EC;padding:60px 0}
.step-card{background:#fff;border-radius:12px;padding:24px;border:1px solid #eee;position:relative}
.step-number{width:36px;height:36px;background:#e67e22;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;margin-bottom:12px}

/* AUTH TABS */
#auth{background:#fff;padding:60px 0}
.auth-tabs{display:flex;border-bottom:2px solid #eee;margin-bottom:24px}
.auth-tab{padding:10px 24px;font-size:13px;font-weight:500;cursor:pointer;border:none;background:none;color:#777;border-bottom:2px solid transparent;margin-bottom:-2px;transition:.2s}
.auth-tab.active{color:#e67e22;border-bottom-color:#e67e22}
.auth-panel{display:none}
.auth-panel.active{display:block}
.auth-panel input{width:100%;border:1px solid #ddd;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:12px;outline:none;transition:.2s}
.auth-panel input:focus{border-color:#e67e22}
.btn-auth{width:100%;background:#e67e22;color:#fff;border:none;border-radius:8px;padding:12px;font-size:14px;font-weight:600;cursor:pointer;transition:.2s}
.btn-auth:hover{background:#cf6d17}

/* TESTIMONIAL */
#testimonial{background:#F9F3EC;padding:60px 0}
.testi-card{background:#fff;border-radius:12px;padding:28px;border:1px solid #eee}
.quote-mark{font-size:3rem;color:#e67e22;line-height:1;font-family:'Georgia',serif;margin-bottom:8px}
.testi-text{font-size:14px;line-height:1.8;color:#555;font-style:italic}
.testi-author{font-weight:600;margin-top:12px;font-size:13px}

/* FOOTER */
footer{background:#2c2c2c;color:#ccc;padding:48px 0 20px}
footer h5{color:#fff;font-size:14px;margin-bottom:16px}
footer p,footer a{font-size:12px;color:#aaa;text-decoration:none}
footer a:hover{color:#e67e22}
.footer-bottom{border-top:1px solid #444;margin-top:32px;padding-top:16px;font-size:11px;color:#777}

/* NAVBAR */
.nav-wrapper{display:flex;align-items:center;justify-content:space-between;padding:12px 0}
.nav-links{display:flex;gap:24px;list-style:none}
.top-bar{background:#e67e22;color:#fff;text-align:center;padding:6px;font-size:12px}

/* TICKER BERJALAN */
.top-bar {
    background: #e67e22;
    color: #fff;
    text-align: center;
    padding: 6px 0;
    font-size: 12px;
    overflow: hidden;
    white-space: nowrap;
}
.top-bar .ticker-text {
    display: inline-block;
    animation: ticker-scroll 20s linear infinite;
}
@keyframes ticker-scroll {
    0% { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
}
</style>
</head>
<body>

<?php if (getVal($umum, 'ticker_aktif', 1) == 1): ?>
<div class="top-bar">
    <span class="ticker-text">🐾 <?= htmlspecialchars(getVal($umum, 'ticker_teks', 'Selamat datang di Njuah Vet Siantar — Tempat Penitipan Hewan Terpercaya di Pematangsiantar')) ?></span>
</div>
<?php endif; ?>
<header>
  <div class="container">
    <div class="nav-wrapper">
      <div style="font-family:'Chilanka',cursive;font-size:1.5rem;color:#e67e22;font-weight:700">
        🐾 <?= htmlspecialchars(getVal($umum, 'nama_toko', 'Njuah Vet Siantar')) ?>
      </div>
      <ul class="nav-links d-none d-md-flex">
        <li><a href="#banner" class="nav-link active">Beranda</a></li>
        <li><a href="#tarif" class="nav-link">Tarif & Layanan</a></li>
        <li><a href="#ketersediaan" class="nav-link">Cek Ketersediaan</a></li>
        <li><a href="#cara" class="nav-link">Cara Penitipan</a></li>
        
      </ul>
      <div style="display:flex;gap:12px;align-items:center">
        <a href="login.php" class="btn btn-outline-dark btn-sm" style="font-size:12px" onclick="showTab('login')">Masuk</a>
        <a href="register.php" class="btn btn-primary btn-sm text-white" style="font-size:12px" onclick="showTab('register')">Daftar</a>
      </div>
    </div>
  </div>
</header>

<section id="banner" style="background:#F9F3EC;padding:60px 0">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6 order-2 order-md-1">
        <div class="secondary-font text-primary mb-3" style="font-size:1.1rem"><?= htmlspecialchars(getVal($banner, 'sub_judul', 'Penitipan Hewan Terpercaya #1 Pematangsiantar')) ?></div>
        <h1 class="banner-title fw-normal mb-4"><?= htmlspecialchars(getVal($banner, 'judul_utama', 'Titipkan Hewan Kesayangan Anda dengan Aman & Nyaman')) ?></h1>
        <p style="color:#666;font-size:13px;line-height:1.8;margin-bottom:24px">
          <?= nl2br(htmlspecialchars(getVal($banner, 'deskripsi', 'Njuah Vet Siantar menyediakan layanan penitipan khusus anjing dan kucing dengan fasilitas kandang modern, pemantauan harian, dan perawatan penuh kasih dari tim profesional kami.'))) ?>
        </p>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <a href="#ketersediaan" class="btn btn-primary text-white px-4"><?= htmlspecialchars(getVal($banner, 'tombol_kiri_teks', 'Cek Ketersediaan')) ?></a>
          <a href="#cara" class="btn btn-outline-dark px-4"><?= htmlspecialchars(getVal($banner, 'tombol_kanan_teks', 'Cara Penitipan')) ?></a>
        </div>
      </div>
      <div class="col-md-6 order-1 order-md-2 text-center mb-4 mb-md-0">
        <div style="background:#fff;border-radius:24px;padding:32px;display:inline-block;border:1px solid #eee">
          <span style="background:<?= $status_color ?>22;color:<?= $status_color ?>;font-size:11px;padding:4px 12px;border-radius:20px;border:1px solid <?= $status_color ?>">
              ● <?= $status_badge ?>
            </span>
          <div style="font-size:5rem">🐕🐈</div>
          <p style="margin-top:12px;color:#777;font-size:12px">Anjing & Kucing Anda dalam Perawatan Terbaik</p>
        </div>
      </div>
    </div>
    <div class="row mt-5 g-3">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-number"><?= number_format($stat_hewan['total'] ?? 0) ?>+</div>
          <div class="stat-label">Hewan Dititipkan</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-number"><?= $stat_kandang['total'] ?? 0 ?></div>
          <div class="stat-label">Unit Kandang</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-number"><?= $avg_rating ?>★</div>
          <div class="stat-label">Rating Pelanggan</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-number">24/7</div>
          <div class="stat-label">Pemantauan CCTV</div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="services" style="padding:60px 0">
  <div class="container">
    <div class="text-center mb-5">
      <div class="secondary-font text-primary mb-2">Mengapa Memilih Kami?</div>
      <h2 style="font-size:1.8rem;font-weight:400">Keunggulan Layanan Njuah Vet Siantar</h2>
    </div>
    <div class="row g-4">
      <?php 
      $icons = ['🏠', '📋', '👨‍⚕️', '📱', '📹', '💊'];
      $i = 0;
      while ($row = mysqli_fetch_assoc($keunggulan)): 
        $icon = !empty($row['gambar']) ? '<img src="uploads/keunggulan/'.htmlspecialchars($row['gambar']).'" style="width:40px;height:40px;object-fit:cover;border-radius:50%;margin-bottom:8px;">' : $icons[$i % count($icons)];
      ?>
      <div class="col-md-4">
        <div class="service-card">
          <span class="service-icon"><?= $icon ?></span>
          <h5 style="font-size:14px;font-weight:600;margin-bottom:8px"><?= htmlspecialchars($row['judul']) ?></h5>
          <p style="font-size:12px;color:#777;line-height:1.7"><?= htmlspecialchars($row['deskripsi']) ?></p>
        </div>
      </div>
      <?php 
      $i++;
      endwhile; 
      ?>
    </div>
  </div>
</section>

<section id="tarif" style="background:#F9F3EC;padding:60px 0">
  <div class="container">
    <div class="text-center mb-5">
      <div class="secondary-font text-primary mb-2">Harga Transparan & Terjangkau</div>
      <h2 style="font-size:1.8rem;font-weight:400">Tarif Resmi Penitipan</h2>
      <p style="color:#777;font-size:13px;margin-top:8px">Semua tarif sudah termasuk makan 2x sehari, pembersihan kandang, dan laporan kondisi harian</p>
    </div>
    <div class="row g-4">
      <div class="col-md-6">
        <div class="tarif-card">
          <div class="tarif-header anjing">
            <div style="font-size:2rem;margin-bottom:6px">🐕</div>
            <h4 style="font-weight:600;margin:0">Kandang Anjing</h4>
            <p style="font-size:11px;opacity:.85;margin-top:4px">Khusus untuk semua ras anjing</p>
          </div>
          <div class="tarif-body">
            <?php foreach ($tarif_anjing as $t): ?>
            <div class="tarif-row">
              <div>
                <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($t['ukuran']) ?></div>
                <div style="color:#999;font-size:11px"><?= htmlspecialchars($t['kode']) ?> · Kapasitas: <?= $t['kapasitas'] ?> ekor/unit</div>
              </div>
              <div class="tarif-price">Rp <?= number_format($t['tarif'], 0, ',', '.') ?> / hari</div>
            </div>
            <?php endforeach; ?>
            <div style="background:#fff8f3;border-radius:8px;padding:10px;margin-top:12px;font-size:11px;color:#888;border:1px solid #fde8d4">
              ✅ Termasuk: makan 2x, pembersihan harian, laporan kondisi, AC / kipas angin
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="tarif-card">
          <div class="tarif-header kucing">
            <div style="font-size:2rem;margin-bottom:6px">🐈</div>
            <h4 style="font-weight:600;margin:0">Kandang Kucing</h4>
            <p style="font-size:11px;opacity:.85;margin-top:4px">Khusus untuk semua ras kucing</p>
          </div>
          <div class="tarif-body">
            <?php foreach ($tarif_kucing as $t): ?>
            <div class="tarif-row">
              <div>
                <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($t['ukuran']) ?></div>
                <div style="color:#999;font-size:11px"><?= htmlspecialchars($t['kode']) ?> · Kapasitas: <?= $t['kapasitas'] ?> ekor/unit</div>
              </div>
              <div class="tarif-price">Rp <?= number_format($t['tarif'], 0, ',', '.') ?> / hari</div>
            </div>
            <?php endforeach; ?>
            <div style="background:#fff3f3;border-radius:8px;padding:10px;margin-top:12px;font-size:11px;color:#888;border:1px solid #fdd;"> 
              ✅ Termasuk: makan 2x, kotak pasir, scratching post, laporan kondisi, AC
            </div>
          </div>
        </div>
      </div>
    </div>
    <div style="text-align:center;margin-top:32px">
      <div style="background:#fff;border-radius:12px;padding:16px 24px;display:inline-block;border:1px solid #eee;font-size:12px;color:#666">
        💡 <strong>Diskon 10%</strong> untuk penitipan lebih dari 7 hari berturut-turut &nbsp;|&nbsp; 
        💡 <strong>Diskon 15%</strong> untuk penitipan kedua hewan atau lebih dalam satu booking
      </div>
    </div>
  </div>
</section>

<section id="ketersediaan" style="background:#fff;padding:60px 0">
  <div class="container">
    <div class="text-center mb-5">
      <div class="secondary-font text-primary mb-2">Real-time</div>
      <h2 style="font-size:1.8rem;font-weight:400">Cek Ketersediaan Kandang</h2>
      <p style="color:#777;font-size:13px;margin-top:8px">Status diperbarui secara otomatis setiap kali ada booking masuk atau hewan diambil</p>
    </div>

    <div style="display:flex;gap:8px;justify-content:center;margin-bottom:28px;flex-wrap:wrap">
      <button onclick="filterKandang('all')" id="btn-all" class="btn btn-primary btn-sm text-white">Semua</button>
      <button onclick="filterKandang('anjing')" id="btn-anjing" class="btn btn-outline-dark btn-sm">🐕 Anjing</button>
      <button onclick="filterKandang('kucing')" id="btn-kucing" class="btn btn-outline-dark btn-sm">🐈 Kucing</button>
    </div>

    <div class="kandang-grid" id="kandang-grid"></div>

    <div style="display:flex;gap:16px;justify-content:center;margin-top:24px;font-size:12px;color:#666">
      <span><span style="display:inline-block;width:10px;height:10px;background:#27ae60;border-radius:50%;margin-right:4px"></span>Tersedia</span>
      <span><span style="display:inline-block;width:10px;height:10px;background:#e74c3c;border-radius:50%;margin-right:4px"></span>Penuh</span>
    </div>
    <div style="text-align:center;margin-top:20px">
      <a href="login.php" class="btn btn-primary text-white px-5" onclick="showTab('login')">Pesan Sekarang</a>
    </div>
  </div>
</section>

<section id="cara" style="background:#F9F3EC;padding:60px 0">
  <div class="container">
    <div class="text-center mb-5">
      <div class="secondary-font text-primary mb-2">Mudah & Cepat</div>
      <h2 style="font-size:1.8rem;font-weight:400">Tata Cara Penitipan</h2>
    </div>
    <div class="row g-4">
      <?php 
      mysqli_data_seek($cara, 0);
      $step = 1;
      while ($row = mysqli_fetch_assoc($cara)): 
      ?>
      <div class="col-md-3">
        <div class="step-card">
          <div class="step-number"><?= $step ?></div>
          <h5 style="font-size:13px;font-weight:600;margin-bottom:8px"><?= htmlspecialchars($row['judul']) ?></h5>
          <p style="font-size:12px;color:#777;line-height:1.7"><?= htmlspecialchars($row['deskripsi']) ?></p>
        </div>
      </div>
      <?php 
      $step++;
      endwhile; 
      ?>
    </div>
    <div style="text-align:center;margin-top:32px">
      <div style="background:#fff;border-radius:12px;padding:20px;display:inline-block;border:1px solid #eee;max-width:500px">
        <p style="font-size:12px;color:#666;margin:0">📞 Butuh bantuan? Hubungi kami di <strong style="color:#e67e22"><?= htmlspecialchars(getVal($umum, 'telepon', '+62 812-3456-7890')) ?></strong> atau WhatsApp kami</p>
      </div>
    </div>
  </div>
</section>

<!-- SECTION: PROFIL TOKO -->
<section id="profil" style="background:#fff;padding:60px 0">
  <div class="container">
    <div class="text-center mb-5">
      <div class="secondary-font text-primary mb-2">Kenali Kami</div>
      <h2 style="font-size:1.8rem;font-weight:400">Profil <?= htmlspecialchars(getVal($umum, 'nama_toko', 'Njuah Vet Siantar')) ?></h2>
    </div>

    <div style="background:#2a1f12;border-radius:20px;padding:32px">

      <!-- HEADER -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:8px">
        <div>
          <div style="font-size:11px;color:#e67e22;text-transform:uppercase;letter-spacing:.8px;margin-bottom:4px">Profil Toko</div>
          <h3 style="color:#fff;font-size:1.2rem;font-weight:500;margin:0"><?= htmlspecialchars(getVal($umum, 'nama_toko', 'Njuah Vet Siantar')) ?></h3>
        </div>
        <span style="background:<?= $status_color ?>22;color:<?= $status_color ?>;font-size:11px;padding:4px 12px;border-radius:20px;border:1px solid <?= $status_color ?>">
          ● <?= $status_badge ?>
        </span>
      </div>

      <div class="row g-4">

        <!-- KOLOM KIRI: Foto + Peta + Identitas -->
        <div class="col-md-5">

          <!-- Foto -->
          <div style="width:100%;height:140px;background:#e67e22;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:3.5rem;margin-bottom:12px">
            <?php if (!empty(getVal($umum, 'foto_toko'))): ?>
              <img src="uploads/profil/<?= htmlspecialchars(getVal($umum, 'foto_toko')) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">
            <?php else: ?>
              🐾
            <?php endif; ?>
          </div>

          <!-- Peta Google Maps -->
          <div style="width:100%;height:280px;border-radius:12px;overflow:hidden;border:1px solid rgba(255,255,255,.1);margin-bottom:10px">
            <?php if (!empty(getVal($umum, 'google_maps_embed')) && strpos(getVal($umum, 'google_maps_embed'), 'google.com/maps') !== false): ?>
              <iframe src="<?= htmlspecialchars(getVal($umum, 'google_maps_embed')) ?>" width="100%" height="100%" style="border:0;display:block" allowfullscreen loading="lazy"></iframe>
            <?php else: ?>
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#1a1a1a;color:#666;font-size:13px;">🗺️ Peta Belum Tersedia</div>
            <?php endif; ?>
          </div>
          <p style="font-size:11px;color:rgba(255,255,255,.45);margin-bottom:14px;line-height:1.5">
            📍 <?= htmlspecialchars(getVal($umum, 'alamat', 'Jl. Veteriner No. 1, Pematangsiantar')) ?>
          </p>

          <p style="color:rgba(255,255,255,.65);font-size:13px;line-height:1.85;margin-bottom:16px">
            <?= nl2br(htmlspecialchars(getVal($umum, 'deskripsi_toko', 'Njuah Vet Siantar adalah tempat penitipan hewan peliharaan terpercaya di kota Pematangsiantar.'))) ?>
          </p>

          <ul style="list-style:none;padding:0;margin:0">
            <li style="color:rgba(255,255,255,.6);font-size:12px;padding:6px 0;border-bottom:1px solid rgba(255,255,255,.07);display:flex;justify-content:space-between">
              <span>📋 Nama Toko</span><strong style="color:#fff;font-weight:500"><?= htmlspecialchars(getVal($umum, 'nama_toko', 'Njuah Vet Siantar')) ?></strong>
            </li>
            <li style="color:rgba(255,255,255,.6);font-size:12px;padding:6px 0;border-bottom:1px solid rgba(255,255,255,.07);display:flex;justify-content:space-between">
              <span>🐾 Layanan</span><strong style="color:#fff;font-weight:500">Penitipan Anjing & Kucing</strong>
            </li>
            <li style="color:rgba(255,255,255,.6);font-size:12px;padding:6px 0;border-bottom:1px solid rgba(255,255,255,.07);display:flex;justify-content:space-between">
              <span>🏠 Total Kandang</span><strong style="color:#fff;font-weight:500"><?= $stat_kandang['total'] ?? 0 ?> unit aktif</strong>
            </li>
            <li style="color:rgba(255,255,255,.6);font-size:12px;padding:6px 0;border-bottom:1px solid rgba(255,255,255,.07);display:flex;justify-content:space-between">
              <span>📞 Telepon</span><strong style="color:#fff;font-weight:500"><?= htmlspecialchars(getVal($umum, 'telepon', '+62 812-3456-7890')) ?></strong>
            </li>
            <li style="color:rgba(255,255,255,.6);font-size:12px;padding:6px 0;border-bottom:1px solid rgba(255,255,255,.07);display:flex;justify-content:space-between">
              <span>✉️ Email</span><strong style="color:#fff;font-weight:500"><?= htmlspecialchars(getVal($umum, 'email', 'info@njuahvetsiantar.com')) ?></strong>
            </li>
            <li style="color:rgba(255,255,255,.6);font-size:12px;padding:6px 0">
              <a href="https://maps.app.goo.gl/5vwS1TGBUV4ykzEz9" target="_blank" style="color:#e67e22;text-decoration:none">
                🗺️ Buka di Google Maps
              </a>
            </li>
          </ul>
        </div>

        <!-- KOLOM TENGAH: Sejarah -->
        <div class="col-md-4">
          <div style="background:rgba(255,255,255,.07);border-radius:14px;padding:1.5rem;height:100%">
            <h6 style="color:#fff;font-weight:500;margin-bottom:12px;font-size:13px">🕐 Tentang & Sejarah Toko</h6>
            <p style="color:rgba(255,255,255,.62);font-size:12.5px;line-height:1.95;margin:0">
              <?= nl2br(htmlspecialchars(getVal($umum, 'sejarah_toko', 'Belum ada sejarah toko.'))) ?>
            </p>
          </div>
        </div>

        <!-- KOLOM KANAN: Jam + Statistik -->
        <div class="col-md-3">

          <!-- Jam Operasional -->
          <div style="background:rgba(255,255,255,.07);border-radius:14px;padding:1.25rem;margin-bottom:16px">
            <h6 style="color:#fff;font-weight:500;margin-bottom:12px;font-size:13px">🕐 Jam Operasional</h6>
            <?php 
            $jam = getVal($umum, 'jam_operasional', 'Senin-Jumat: 07.00-20.00, Sabtu: 07.00-18.00, Minggu: 08.00-16.00');
            $jam_lines = explode(',', $jam);
            foreach ($jam_lines as $line): 
              $parts = explode(':', $line, 2);
              $hari = trim($parts[0] ?? '');
              $waktu = trim($parts[1] ?? '');
            ?>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid rgba(255,255,255,.07);font-size:12px;color:rgba(255,255,255,.6)">
              <span><?= htmlspecialchars($hari) ?></span><strong style="color:#e67e22"><?= htmlspecialchars($waktu) ?></strong>
            </div>
            <?php endforeach; ?>
            <div style="background:rgba(230,126,34,.12);border-radius:8px;padding:10px;margin-top:12px;font-size:11px;color:rgba(255,255,255,.55);line-height:1.6">
              ⚠️ Pengambilan di luar jam operasional dikenakan biaya tambahan Rp 25.000
            </div>
          </div>

          <!-- Statistik -->
          <div style="background:rgba(255,255,255,.07);border-radius:14px;padding:1.25rem">
            <h6 style="color:#fff;font-weight:500;margin-bottom:12px;font-size:13px">⭐ Statistik Toko</h6>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
              <div style="background:rgba(230,126,34,.12);border-radius:8px;padding:10px;text-align:center">
                <div style="font-size:18px;font-weight:600;color:#e67e22"><?= number_format($stat_hewan['total'] ?? 0) ?>+</div>
                <div style="font-size:10px;color:rgba(255,255,255,.5);margin-top:2px">Hewan Dititipkan</div>
              </div>
              <div style="background:rgba(230,126,34,.12);border-radius:8px;padding:10px;text-align:center">
                <div style="font-size:18px;font-weight:600;color:#e67e22"><?= $avg_rating ?>★</div>
                <div style="font-size:10px;color:rgba(255,255,255,.5);margin-top:2px">Rating Pelanggan</div>
              </div>
              <div style="background:rgba(230,126,34,.12);border-radius:8px;padding:10px;text-align:center">
                <div style="font-size:18px;font-weight:600;color:#e67e22"><?= $stat_kandang['total'] ?? 0 ?></div>
                <div style="font-size:10px;color:rgba(255,255,255,.5);margin-top:2px">Unit Kandang</div>
              </div>
              <div style="background:rgba(230,126,34,.12);border-radius:8px;padding:10px;text-align:center">
                <div style="font-size:18px;font-weight:600;color:#e67e22">2018</div>
                <div style="font-size:10px;color:rgba(255,255,255,.5);margin-top:2px">Tahun Berdiri</div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</section>

<section id="testimonial" style="background:#F9F3EC;padding:60px 0">
  <div class="container">
    <div class="text-center mb-5">
      <div class="secondary-font text-primary mb-2">Kata Mereka</div>
      <h2 style="font-size:1.8rem;font-weight:400">Testimoni Pelanggan</h2>
    </div>
    <div class="row g-4">
      <?php 
      mysqli_data_seek($testimoni, 0);
      if (mysqli_num_rows($testimoni) > 0):
        while ($row = mysqli_fetch_assoc($testimoni)): 
      ?>
      <div class="col-md-4">
        <div class="testi-card">
          <div class="quote-mark">"</div>
          <p class="testi-text"><?= htmlspecialchars($row['komentar']) ?></p>
          <div class="testi-author"><?= htmlspecialchars($row['nama_lengkap'] ?? 'Pelanggan') ?></div>
          <div style="font-size:11px;color:#999">Pemilik Hewan</div>
          <div style="margin-top:8px;color:#f39c12;font-size:14px"><?= str_repeat('★', round($row['rating'] ?? 5)) ?></div>
        </div>
      </div>
      <?php 
        endwhile;
      else: 
      ?>
      <div class="col-12 text-center py-4"><p class="text-muted">Belum ada testimoni.</p></div>
      <?php endif; ?>
    </div>
  </div>
</section>

<footer>
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <div style="font-family:'Chilanka',cursive;font-size:1.4rem;color:#e67e22;margin-bottom:12px">🐾 <?= htmlspecialchars(getVal($umum, 'nama_toko', 'Njuah Vet Siantar')) ?></div>
        <p style="font-size:12px;line-height:1.8"><?= htmlspecialchars(getVal($umum, 'deskripsi_toko', 'Tempat penitipan hewan peliharaan terpercaya di Pematangsiantar.')) ?></p>
        <p style="font-size:12px;margin-top:12px">📍 <?= htmlspecialchars(getVal($umum, 'alamat', 'Jl. Veteriner No. 1, Pematangsiantar')) ?><br>📞 <?= htmlspecialchars(getVal($umum, 'telepon', '+62 812-3456-7890')) ?><br>✉️ <?= htmlspecialchars(getVal($umum, 'email', 'info@njuahvetsiantar.com')) ?></p>
      </div>
      <div class="col-md-2">
        <h5>Menu</h5>
        <ul style="list-style:none;padding:0">
          <li style="margin-bottom:6px"><a href="#banner">Beranda</a></li>
          <li style="margin-bottom:6px"><a href="#tarif">Tarif Layanan</a></li>
          <li style="margin-bottom:6px"><a href="#ketersediaan">Cek Kandang</a></li>
          <li style="margin-bottom:6px"><a href="#cara">Cara Penitipan</a></li>
        </ul>
      </div>
      <div class="col-md-3">
        <h5>Jam Operasional</h5>
        <table style="font-size:12px;width:100%">
          <?php 
          $jam_lines2 = explode(',', getVal($umum, 'jam_operasional', 'Senin-Jumat: 07.00-20.00, Sabtu: 07.00-18.00, Minggu: 08.00-16.00'));
          foreach ($jam_lines2 as $line): 
            $parts = explode(':', $line, 2);
            $hari = trim($parts[0] ?? '');
            $waktu = trim($parts[1] ?? '');
          ?>
          <tr><td style="color:#aaa;padding:3px 0"><?= htmlspecialchars($hari) ?></td><td style="color:#fff"><?= htmlspecialchars($waktu) ?></td></tr>
          <?php endforeach; ?>
        </table>
        <div style="margin-top:12px;background:#3a3a3a;border-radius:8px;padding:10px;font-size:11px;color:#aaa">
          ⚠️ Pengambilan hewan di luar jam operasional dikenakan biaya tambahan
        </div>
      </div>
      <div class="col-md-3">
        <h5>Metode Pembayaran</h5>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
          <span style="background:#3a3a3a;color:#ccc;font-size:10px;padding:4px 8px;border-radius:6px">Transfer Bank</span>
          <span style="background:#3a3a3a;color:#ccc;font-size:10px;padding:4px 8px;border-radius:6px">QRIS</span>
          <span style="background:#3a3a3a;color:#ccc;font-size:10px;padding:4px 8px;border-radius:6px">GoPay</span>
          <span style="background:#3a3a3a;color:#ccc;font-size:10px;padding:4px 8px;border-radius:6px">OVO</span>
          <span style="background:#3a3a3a;color:#ccc;font-size:10px;padding:4px 8px;border-radius:6px">Dana</span>
          <span style="background:#3a3a3a;color:#ccc;font-size:10px;padding:4px 8px;border-radius:6px">Tunai</span>
        </div>
        <div style="margin-top:16px">
          <h5>Ikuti Kami</h5>
          <div style="display:flex;gap:10px">
            <a href="#" style="color:#aaa;font-size:18px">📘</a>
            <a href="#" style="color:#aaa;font-size:18px">📸</a>
            <a href="#" style="color:#aaa;font-size:18px">💬</a>
          </div>
        </div>
      </div>
    </div>
    <div class="footer-bottom text-center">
      <p>© <?= date('Y') ?> <?= htmlspecialchars(getVal($umum, 'nama_toko', 'Njuah Vet Siantar')) ?>. Hak Cipta Dilindungi. | Sistem Informasi Penitipan Hewan Peliharaan Berbasis Web</p>
    </div>
  </div>
</footer>

<script>
var kandangData = <?= json_encode($kandang_data) ?>;

function renderKandang(filter){
  var grid=document.getElementById('kandang-grid');
  var data=filter==='all'?kandangData:kandangData.filter(function(k){return k.tipe===filter});
  if (data.length === 0) {
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#777;">Tidak ada kandang yang tersedia</div>';
    return;
  }
  grid.innerHTML=data.map(function(k){
    var isTersedia=k.status==='tersedia';
    var iconHtml = '';
    if (k.foto && k.foto.trim() !== '') {
      iconHtml = '<img src="uploads/kandang/' + encodeURIComponent(k.foto) + '" class="kandang-img" alt="'+k.kode+'">';
    } else {
      iconHtml = '<span class="kandang-icon">'+k.icon+'</span>';
    }
    return '<div class="kandang-card '+(isTersedia?'tersedia':'penuh')+'">'
      + iconHtml
      +'<strong style="font-size:11px">'+k.kode+'</strong>'
      +'<div style="color:#777;margin:3px 0">'+k.ukuran+'</div>'
      +'<div style="font-size:10px;color:#888;">Kapasitas: '+k.kapasitas+' ekor/unit</div>'
      +'<span class="'+(isTersedia?'badge-tersedia':'badge-penuh')+'">'+(isTersedia?'Tersedia':'Penuh')+'</span>'
      +'</div>'
  }).join('');
}

function filterKandang(filter){
  ['all','anjing','kucing'].forEach(function(f){
    document.getElementById('btn-'+f).className='btn btn-outline-dark btn-sm';
  });
  document.getElementById('btn-'+filter).className='btn btn-primary btn-sm text-white';
  renderKandang(filter);
}

function showTab(tab){
  var loginPanel = document.getElementById('panel-login');
  var registerPanel = document.getElementById('panel-register');
  var tabLogin = document.getElementById('tab-login');
  var tabRegister = document.getElementById('tab-register');
  if (loginPanel) loginPanel.className = 'auth-panel' + (tab==='login' ? ' active' : '');
  if (registerPanel) registerPanel.className = 'auth-panel' + (tab==='register' ? ' active' : '');
  if (tabLogin) tabLogin.className = 'auth-tab' + (tab==='login' ? ' active' : '');
  if (tabRegister) tabRegister.className = 'auth-tab' + (tab==='register' ? ' active' : '');
}

renderKandang('all');
</script>
</body>
</html>
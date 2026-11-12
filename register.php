<?php 






?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Akun — Njuah Vet Siantar</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Chilanka&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Montserrat',sans-serif;font-size:14px;color:#333;background:#F9F3EC;min-height:100vh;display:flex;flex-direction:column}
.secondary-font{font-family:'Chilanka',cursive}
.text-primary{color:#e67e22!important}
.btn-primary{background:#e67e22;border-color:#e67e22}
.btn-outline-dark{border-color:#333;color:#333}
.btn-outline-dark:hover{background:#333;color:#fff}

/* TOP BAR */
.top-bar{background:#e67e22;color:#fff;text-align:center;padding:6px;font-size:12px}

/* HEADER */
header{background:#fff;border-bottom:1px solid #eee}
.nav-wrapper{display:flex;align-items:center;justify-content:space-between;padding:14px 0}
.nav-brand{font-family:'Chilanka',cursive;font-size:1.5rem;color:#e67e22;font-weight:700;text-decoration:none}

/* MAIN CONTENT */
.auth-wrapper{flex:1;display:flex;align-items:center;justify-content:center;padding:48px 16px}
.auth-card{background:#fff;border-radius:20px;padding:40px;border:1px solid #eee;width:100%;max-width:480px;box-shadow:0 4px 24px rgba(0,0,0,.06)}

/* LOGO AREA */
.auth-logo{text-align:center;margin-bottom:28px}
.auth-logo .paw{font-size:2.8rem}
.auth-logo h2{font-size:1.4rem;font-weight:400;margin-top:10px;color:#333}
.auth-logo p{font-size:12px;color:#999;margin-top:4px}

/* SECTION LABEL */
.section-label{font-size:11px;font-weight:600;color:#e67e22;text-transform:uppercase;letter-spacing:.6px;margin:20px 0 14px}

/* DIVIDER */
.section-divider{border:none;border-top:1px solid #eee;margin:20px 0}

/* FORM */
.form-group{margin-bottom:14px}
.form-group label{display:block;font-size:12px;font-weight:500;color:#555;margin-bottom:6px}
.form-control-custom{
  width:100%;border:1px solid #ddd;border-radius:10px;
  padding:11px 14px;font-size:13px;font-family:'Montserrat',sans-serif;
  outline:none;transition:.2s;color:#333;background:#fff
}
.form-control-custom:focus{border-color:#e67e22;box-shadow:0 0 0 3px rgba(230,126,34,.1)}
.form-control-custom::placeholder{color:#bbb}

/* INPUT ICON WRAPPER */
.input-icon-wrap{position:relative}
.input-icon-wrap .form-control-custom{padding-left:40px}
.input-icon-wrap .input-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);font-size:15px;color:#bbb}

/* ROW 2 COL */
.form-row-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}

/* SUBMIT BUTTON */
.btn-auth{
  width:100%;background:#e67e22;color:#fff;border:none;border-radius:10px;
  padding:13px;font-size:14px;font-weight:600;cursor:pointer;
  font-family:'Montserrat',sans-serif;transition:.2s;margin-top:8px
}
.btn-auth:hover{background:#cf6d17}
.btn-auth:active{transform:scale(.99)}

/* TOS NOTE */
.tos-note{font-size:11px;color:#999;text-align:center;margin-top:12px;line-height:1.6}
.tos-note a{color:#e67e22;text-decoration:none}
.tos-note a:hover{text-decoration:underline}

/* LOGIN LINK */
.login-link{text-align:center;margin-top:20px;font-size:12px;color:#888}
.login-link a{color:#e67e22;text-decoration:none;font-weight:500}
.login-link a:hover{text-decoration:underline}

/* ALERT */
.alert-custom{
  padding:10px 14px;border-radius:10px;font-size:12px;
  margin-bottom:18px;display:flex;align-items:flex-start;gap:8px;line-height:1.6
}
.alert-error{background:#fdf0f0;color:#c0392b;border:1px solid #fad5d5}
.alert-success{background:#f0faf4;color:#27ae60;border:1px solid #c3e6cb}

/* PASSWORD STRENGTH */
.pass-strength{height:4px;border-radius:4px;margin-top:6px;background:#eee;overflow:hidden}
.pass-strength-bar{height:100%;width:0;border-radius:4px;transition:.3s}
.pass-hint{font-size:10px;color:#bbb;margin-top:4px}

/* FOOTER */
footer{background:#2c2c2c;color:#aaa;text-align:center;padding:16px;font-size:11px}
footer a{color:#e67e22;text-decoration:none}
</style>
</head>
<body>

<div class="top-bar">
  🐾 Selamat datang di Njuah Vet Siantar — Tempat Penitipan Hewan Terpercaya di Pematangsiantar
</div>

<header>
  <div class="container">
    <div class="nav-wrapper">
      <a href="index.php" class="nav-brand">🐾 Njuah Vet</a>
      <div style="display:flex;gap:10px">
        <a href="index.php" class="btn btn-outline-dark btn-sm" style="font-size:12px">← Kembali ke Beranda</a>
        <a href="login.php" class="btn btn-primary btn-sm text-white" style="font-size:12px">Sudah Punya Akun?</a>
      </div>
    </div>
  </div>
</header>

<div class="auth-wrapper">
  <div class="auth-card">

    <div class="auth-logo">
      <div class="paw">🐾</div>
      <h2>Buat Akun Baru</h2>
      <p>Bergabung dan titipkan hewan kesayangan Anda</p>
    </div>

    <!-- ALERT ERROR (tampil jika ada error) -->
    <!-- <div class="alert-custom alert-error">⚠️ Email sudah terdaftar. Silakan gunakan email lain.</div> -->

    <!-- ALERT SUCCESS -->
    <!-- <div class="alert-custom alert-success">✅ Pendaftaran berhasil! Silakan masuk ke akun Anda.</div> -->

    <form method="POST" action="proses_register.php">

      <!-- DATA DIRI -->
      <div class="section-label">📋 Data Diri Pemilik</div>

      <div class="form-group">
        <label for="nama">Nama Lengkap</label>
        <div class="input-icon-wrap">
          <span class="input-icon">👤</span>
          <input
            type="text"
            id="nama"
            name="nama"
            class="form-control-custom"
            placeholder="Nama lengkap sesuai KTP"
            required
            autocomplete="name"
          >
        </div>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label for="email">Alamat Email</label>
          <div class="input-icon-wrap">
            <span class="input-icon">✉️</span>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control-custom"
              placeholder="contoh@email.com"
              required
              autocomplete="email"
            >
          </div>
        </div>
        <div class="form-group">
          <label for="telepon">Nomor WhatsApp</label>
          <div class="input-icon-wrap">
            <span class="input-icon">📱</span>
            <input
              type="tel"
              id="telepon"
              name="telepon"
              class="form-control-custom"
              placeholder="08xx-xxxx-xxxx"
              required
              autocomplete="tel"
            >
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="alamat">Alamat Lengkap</label>
        <div class="input-icon-wrap">
          <span class="input-icon">📍</span>
          <input
            type="text"
            id="alamat"
            name="alamat"
            class="form-control-custom"
            placeholder="Jl. Nama Jalan No. X, Kota"
            required
            autocomplete="street-address"
          >
        </div>
      </div>

      <hr class="section-divider">

      <!-- KEAMANAN AKUN -->
      <div class="section-label">🔒 Keamanan Akun</div>

      <div class="form-row-2">
        <div class="form-group">
          <label for="password">Buat Password</label>
          <div class="input-icon-wrap">
            <span class="input-icon">🔒</span>
            <input
              type="password"
              id="password"
              name="password"
              class="form-control-custom"
              placeholder="Min. 8 karakter"
              required
              autocomplete="new-password"
              oninput="checkStrength(this.value)"
            >
          </div>
          <div class="pass-strength">
            <div class="pass-strength-bar" id="strength-bar"></div>
          </div>
          <div class="pass-hint" id="strength-hint">Minimal 8 karakter</div>
        </div>
        <div class="form-group">
          <label for="password_confirm">Ulangi Password</label>
          <div class="input-icon-wrap">
            <span class="input-icon">🔒</span>
            <input
              type="password"
              id="password_confirm"
              name="password_confirm"
              class="form-control-custom"
              placeholder="Ketik ulang password"
              required
              autocomplete="new-password"
            >
          </div>
        </div>
      </div>

      <input type="hidden" name="csrf_token" value="">

      <button type="submit" class="btn-auth">Daftar Sekarang</button>

      <p class="tos-note">
        Dengan mendaftar, Anda menyetujui <a href="#">Syarat &amp; Ketentuan</a>
        dan <a href="#">Kebijakan Privasi</a> Njuah Vet Siantar
      </p>

    </form>

    <hr class="section-divider">

    <div class="login-link">
      Sudah punya akun? <a href="login.php">Masuk di sini</a>
    </div>

  </div>
</div>

<footer>
  <p>© 2024 Njuah Vet Siantar &nbsp;|&nbsp; <a href="index.php">Beranda</a> &nbsp;|&nbsp; <a href="#">Syarat & Ketentuan</a></p>
</footer>

<script>
function checkStrength(val) {
  var bar = document.getElementById('strength-bar');
  var hint = document.getElementById('strength-hint');
  var len = val.length;
  if (len === 0) {
    bar.style.width = '0'; bar.style.background = '#eee';
    hint.textContent = 'Minimal 8 karakter'; hint.style.color = '#bbb';
  } else if (len < 6) {
    bar.style.width = '25%'; bar.style.background = '#e74c3c';
    hint.textContent = 'Terlalu pendek'; hint.style.color = '#e74c3c';
  } else if (len < 8) {
    bar.style.width = '50%'; bar.style.background = '#e67e22';
    hint.textContent = 'Hampir cukup'; hint.style.color = '#e67e22';
  } else if (len < 12) {
    bar.style.width = '75%'; bar.style.background = '#f1c40f';
    hint.textContent = 'Cukup kuat'; hint.style.color = '#b7970a';
  } else {
    bar.style.width = '100%'; bar.style.background = '#27ae60';
    hint.textContent = 'Password kuat!'; hint.style.color = '#27ae60';
  }
}
</script>

</body>
</html>
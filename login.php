<?php 

require 'koneksi.php';


?>


<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — Njuah Vet Siantar</title>
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
.auth-card{background:#fff;border-radius:20px;padding:40px;border:1px solid #eee;width:100%;max-width:440px;box-shadow:0 4px 24px rgba(0,0,0,.06)}

/* LOGO AREA */
.auth-logo{text-align:center;margin-bottom:28px}
.auth-logo .paw{font-size:2.8rem}
.auth-logo h2{font-size:1.4rem;font-weight:400;margin-top:10px;color:#333}
.auth-logo p{font-size:12px;color:#999;margin-top:4px}

/* DIVIDER */
.section-divider{border:none;border-top:1px solid #eee;margin:20px 0}

/* FORM */
.form-group{margin-bottom:16px}
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

/* FORGOT PASSWORD */
.forgot-link{display:block;text-align:right;font-size:11px;color:#e67e22;text-decoration:none;margin-top:-8px;margin-bottom:16px}
.forgot-link:hover{text-decoration:underline}

/* SUBMIT BUTTON */
.btn-auth{
  width:100%;background:#e67e22;color:#fff;border:none;border-radius:10px;
  padding:13px;font-size:14px;font-weight:600;cursor:pointer;
  font-family:'Montserrat',sans-serif;transition:.2s;margin-top:4px
}
.btn-auth:hover{background:#cf6d17}
.btn-auth:active{transform:scale(.99)}

/* REGISTER LINK */
.register-link{text-align:center;margin-top:20px;font-size:12px;color:#888}
.register-link a{color:#e67e22;text-decoration:none;font-weight:500}
.register-link a:hover{text-decoration:underline}

/* ALERT */
.alert-custom{
  padding:10px 14px;border-radius:10px;font-size:12px;
  margin-bottom:18px;display:flex;align-items:center;gap:8px
}
.alert-error{background:#fdf0f0;color:#c0392b;border:1px solid #fad5d5}
.alert-success{background:#f0faf4;color:#27ae60;border:1px solid #c3e6cb}

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
        <a href="register.php" class="btn btn-primary btn-sm text-white" style="font-size:12px">Daftar Akun</a>
      </div>
    </div>
  </div>
</header>

<div class="auth-wrapper">
  <div class="auth-card">

    <div class="auth-logo">
      <div class="paw">🐾</div>
      <h2>Masuk ke Akun Anda</h2>
      <p>Kelola penitipan hewan kesayangan Anda</p>
    </div>

    <!-- ALERT ERROR (tampil jika ada error) -->
    <!-- <div class="alert-custom alert-error">⚠️ Email atau password salah. Silakan coba lagi.</div> -->

    <!-- ALERT SUCCESS -->
    <!-- <div class="alert-custom alert-success">✅ Login berhasil! Mengalihkan...</div> -->

    <form method="POST" action="proses_login.php">

  <div class="form-group">
    <label for="email">Username/email</label>
    <div class="input-icon-wrap">
      <span class="input-icon">✉️</span>
      <input type="text" id="email" name="username" class="form-control-custom" placeholder="username/email" required>
    </div>
  </div>

  <div class="form-group">
    <label for="password">Password</label>
    <div class="input-icon-wrap">
      <span class="input-icon">🔒</span>
      <input type="password" id="password" name="password" class="form-control-custom" placeholder="Masukkan password Anda" required autocomplete="current-password">
    </div>
  </div>

  <a href="reset_password.php" class="forgot-link">Lupa password?</a>

  <input type="hidden" name="csrf_token" value="">

  <button type="submit" name="login" class="btn-auth">Masuk ke Akun</button>

</form>

    <hr class="section-divider">

    <div class="register-link">
      Belum punya akun? <a href="register.php">Daftar Sekarang</a>
    </div>

  </div>
</div>

<footer>
  <p>© 2024 Njuah Vet Siantar &nbsp;|&nbsp; <a href="index.php">Beranda</a> &nbsp;|&nbsp; <a href="#">Syarat & Ketentuan</a></p>
</footer>

</body>
</html>
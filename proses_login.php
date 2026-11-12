<?php 
session_start();
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        // Mengambil input dan membersihkannya
        $identifier = mysqli_real_escape_string($koneksi, trim($_POST['username'])); // Bisa berisi username atau email
        $password   = $_POST['password'];

        // Query memeriksa tabel `users` berdasarkan username ATAU email, dan memastikan statusnya 'Aktif'
        $query = "SELECT * FROM users WHERE (username = ? OR email = ?) AND status_akun = 'Aktif' LIMIT 1";
        
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "ss", $identifier, $identifier);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $data = mysqli_fetch_assoc($result);

            // Verifikasi password menggunakan password_verify (karena data dump Anda menggunakan bcrypt hash)
            if (password_verify($password, $data['password'])) {
                
                // Menyimpan data login ke dalam Session global sistem
                $_SESSION['id_user']      = $data['id_user'];
                $_SESSION['email']        = $data['email'];
                $_SESSION['nama_lengkap']  = $data['nama_lengkap'];
                $_SESSION['role']          = $data['role']; // 'Super Admin', 'Admin', atau 'Pelanggan'
                $_SESSION['foto_profil']   = $data['foto_profil'];
                
                // Membuat session identifier cadangan untuk kebutuhan template/dashboard
                $_SESSION['username']     = (!empty($data['username'])) ? $data['username'] : $data['email'];
                $_SESSION['display_name'] = $data['nama_lengkap'];

                // Ambil nama lengkap untuk ditampilkan di dalam alert
                $nama_user = $_SESSION['nama_lengkap'];

                // --- PROSES PASCA-LOGIN BERDASARKAN ROLE DENGAN VALIDASI ALERT ---
                if ($_SESSION['role'] == 'Super Admin' || $_SESSION['role'] == 'Admin') {
                    // Jika role-nya Admin atau Super Admin
                    echo "<script>
                        alert('Selamat Datang, $nama_user! Login Berhasil sebagai Admin.');
                        window.location='dashboard.php';
                    </script>";
                    exit;
                } else if ($_SESSION['role'] == 'Pelanggan') {
                    // Jika role-nya Pelanggan (User biasa)
                    echo "<script>
                        alert('Selamat Datang, $nama_user! Login Berhasil.');
                        window.location='dashboard.php';
                    </script>";
                    exit;
                } else {
                    // Antisipasi jika ada role gaib yang tidak terdaftar
                    echo "<script>alert('Role akun Anda tidak dikenali sistem!'); window.location='login.php';</script>";
                    exit;
                }

            } else {
                echo "<script>alert('Password salah!'); window.location='login.php';</script>";
                exit;
            }
            
        } else {
            // Pesan ini muncul jika username/email salah, atau jika akun berstatus 'Nonaktif'
            echo "<script>alert('Akun tidak ditemukan atau status akun Nonaktif!'); window.location='login.php';</script>";
            exit;
        }
    }
}
?>
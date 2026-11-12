<?php 

$host = "localhost";
$user = "root";
$pass = "";
$database = "vet";

$koneksi = mysqli_connect($host, $user, $pass, $database);
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
date_default_timezone_set('Asia/Jakarta');

$base_url = "http://localhost/vet/";





?>
<?php
$host = 'localhost'; // atau 127.0.0.1
$user = 'root'; // default XAMPP
$pass = ''; // password MySQL kamu (kalau default XAMPP biasanya kosong)
$db   = 'cinematix'; // nama database kamu

$conn = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}
?>

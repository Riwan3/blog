<?php
// Konfigurasi Database
// Ganti nilai-nilai di bawah ini dengan kredensial database MySQL Anda.

define('DB_SERVER', 'localhost'); // atau alamat server database Anda
define('DB_USERNAME', 'root'); // username database Anda
define('DB_PASSWORD', 'semvaknaga'); // password database Anda
define('DB_NAME', 'blog_db'); // nama database Anda

// Membuat koneksi ke database
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Cek koneksi
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Set default timezone ke WITA (Central Indonesian Time)
date_default_timezone_set('Asia/Makassar');
?>

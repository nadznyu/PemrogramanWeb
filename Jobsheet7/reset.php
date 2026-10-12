<?php
session_start();
// Menghapus semua variabel session
$_SESSION = [];

// Menghancurkan sesi server
session_destroy();

// Redirect kembali ke halaman utama
header('Location: index.php');
exit;


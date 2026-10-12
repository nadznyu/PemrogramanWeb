<?php
session_start();
echo "<h2>Isi Sesi (Session Debugger)</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo '<br><a href="index.php">Kembali ke Beranda</a>';


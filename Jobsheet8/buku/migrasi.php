<?php
require __DIR__ . '/../includes/koneksi.php';

// Sesuaikan path jika letak file JSON berbeda (misal dari jobsheet6)
$jsonFile = __DIR__ . '/../../Jobsheet6/data/buku.json';

if (!file_exists($jsonFile)) {
    // Coba alternatif path jika file JSON berada di folder lain
    $jsonFile = __DIR__ . '/../data/buku.json';
}

if (!file_exists($jsonFile)) {
    die("File data/buku.json tidak ditemukan! Pastikan jalur foldernya sudah benar.");
}

// Baca isi file JSON
$jsonData = file_get_contents($jsonFile);
$booksArray = json_decode($jsonData, true);

if (empty($booksArray)) {
    die("File JSON kosong atau formatnya tidak valid.");
}

$stmt = $pdo->prepare(
    "INSERT INTO buku (judul, pengarang, tahun, isbn, stok, kategori) 
     VALUES (:judul, :pengarang, :tahun, :isbn, :stok, :kategori)"
);

$successCount = 0;
foreach ($booksArray as $buku) {
    // Masukkan data ke PostgreSQL
    $stmt->execute([
        'judul'     => $buku['judul'] ?? '',
        'pengarang' => $buku['pengarang'] ?? '',
        'tahun'     => (int) ($buku['tahun'] ?? 2023),
        'isbn'      => $buku['isbn'] ?? '',
        'stok'      => (int) ($buku['stok'] ?? 0),
        'kategori'  => $buku['kategori'] ?? ''
    ]);
    $successCount++;
}

echo "<h3>Berhasil melakukan migrasi $successCount data buku dari JSON ke database PostgreSQL!</h3>";
echo '<br><a href="list.php">Kembali ke Daftar Buku</a>';



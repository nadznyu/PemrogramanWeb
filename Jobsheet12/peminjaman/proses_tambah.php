<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/koneksi.php';

csrf_verify();

// Menangkap input dengan mendeteksi berbagai kemungkinan nama atribut di form HTML
$id_anggota = $_POST['id_anggota'] ?? $_POST['anggota_id'] ?? null;
$id_buku = $_POST['id_buku'] ?? $_POST['buku_id'] ?? null;

if (empty($id_anggota) || empty($id_buku)) {
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => 'Stok buku tidak tersedia.'];
    header('Location: tambah.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. VALIDASI BISNIS: Cek apakah anggota memiliki pinjaman yang terlambat > 14 hari
    $queryCekTerlambat = $pdo->prepare("
        SELECT COUNT(*) FROM peminjaman 
        WHERE anggota_id = :anggota_id 
        AND status = 'dipinjam' 
        AND (CURRENT_DATE - tanggal_pinjam) > 14
    ");
    $queryCekTerlambat->execute(['anggota_id' => $id_anggota]);
    $terlambat = $queryCekTerlambat->fetchColumn();

    if ($terlambat > 0) {
        throw new Exception('Anggota memiliki pinjaman yang terlambat lebih dari 14 hari dan tidak boleh meminjam buku baru.');
    }

    // 2. Kunci baris buku (FOR UPDATE) untuk mencegah race condition
    $cek = $pdo->prepare("SELECT stok FROM buku WHERE id = :id FOR UPDATE");
    $cek->execute(['id' => $id_buku]);
    $buku = $cek->fetch(PDO::FETCH_ASSOC);

    if (!$buku || $buku['stok'] < 1) {
        throw new Exception('Stok buku tidak tersedia.');
    }

    // 3. INSERT peminjaman dengan tanggal jatuh tempo otomatis (+14 hari)
    $insert = $pdo->prepare(
        "INSERT INTO peminjaman (buku_id, anggota_id, tanggal_pinjam, tanggal_jatuh_tempo, status)
         VALUES (:buku_id, :anggota_id, CURRENT_DATE, CURRENT_DATE + INTERVAL '14 days', 'dipinjam')"
    );
    $insert->execute(['buku_id' => $id_buku, 'anggota_id' => $id_anggota]);

    // 4. Kurangi stok buku
    $update = $pdo->prepare("UPDATE buku SET stok = stok - 1 WHERE id = :id");
    $update->execute(['id' => $id_buku]);

    $pdo->commit();

    $_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Peminjaman berhasil dicatat.'];
    header('Location: kembali.php');
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => 'Stok buku tidak tersedia.'];
    header('Location: tambah.php');
    exit;
}

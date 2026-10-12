<?php
session_start();
require __DIR__ . '/../includes/koneksi.php'; // Sesuaikan file koneksi database PostgreSQL kamu

$max_attempts = 3;          // Batas maksimal salah
$lockout_duration = 15;     // Durasi blokir dalam detik (15 detik)

// A. CEK WAKTU LOCKOUT: Jika waktu 15 detik sudah lewat, reset semuanya secara otomatis
if (isset($_SESSION['lockout_time']) && time() >= $_SESSION['lockout_time']) {
    unset($_SESSION['failed_attempts']);
    unset($_SESSION['lockout_time']);
}

// B. CEK APAKAH MASIH TERKUNCI
if (isset($_SESSION['lockout_time']) && time() < $_SESSION['lockout_time']) {
    $sisa_detik = $_SESSION['lockout_time'] - time();
    $_SESSION['flash'] = [
        'type' => 'error',
        'pesan' => "Terlalu banyak percobaan gagal. Akun diblokir sementara. Coba lagi dalam $sisa_detik detik."
    ];
    header('Location: login.php');
    exit;
}

// C. Ambil data input dari form
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validasi input kosong
if (empty($username) || empty($password)) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'pesan' => 'Username dan password tidak boleh kosong!'
    ];
    header('Location: login.php');
    exit;
}

// D. AMBIL DATA DARI DATABASE POSTGRESQL
// Sesuaikan nama tabel ('petugas' atau 'users') dan kolomnya
try {
    $stmt = $pdo->prepare("SELECT * FROM petugas WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    $login_berhasil = false;
    
    // Cek apakah user ditemukan dan verifikasi password (mendukung password_hash)
    if ($user && password_verify($password, $user['password'])) {
        $login_berhasil = true;
        $role = $user['role'] ?? 'petugas'; // Ambil role untuk latihan kontrol akses
    }
} catch (Exception $e) {
    // Jika ada error database / koneksi mati
    $_SESSION['flash'] = [
        'type' => 'error',
        'pesan' => 'Terjadi kesalahan sistem atau koneksi database terputus.'
    ];
    header('Location: login.php');
    exit;
}

// E. KEPUTUSAN LOGIN BERHASIL / GAGAL
if ($login_berhasil) {
    // Jika BERHASIL: Hapus riwayat gagal & kunci, lalu buat sesi login
    unset($_SESSION['failed_attempts']);
    unset($_SESSION['lockout_time']);

    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role; // Disimpan untuk latihan kontrol akses role (Nomor 1)

    header('Location: index.php'); // Arahkan ke halaman utama/dashboard
    exit;
} else {
    // Jika GAGAL: Inisialisasi counter jika belum ada, lalu tambahkan +1
    if (!isset($_SESSION['failed_attempts'])) {
        $_SESSION['failed_attempts'] = 0;
    }
    $_SESSION['failed_attempts']++;

    // Jika sudah mencapai batas maksimal (3 kali), aktifkan lockout 15 detik
    if ($_SESSION['failed_attempts'] >= $max_attempts) {
        $_SESSION['lockout_time'] = time() + $lockout_duration;
        $_SESSION['flash'] = [
            'type' => 'error',
            'pesan' => "Terlalu banyak percobaan gagal. Akun diblokir selama $lockout_duration detik."
        ];
    } else {
        $sisa_percobaan = $max_attempts - $_SESSION['failed_attempts'];
        $_SESSION['flash'] = [
            'type' => 'error',
            'pesan' => "Username atau password salah! Sisa percobaan: $sisa_percobaan"
        ];
    }

    header('Location: login.php');
    exit;
}
?>
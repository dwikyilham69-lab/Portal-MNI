# STIKES MNI — Sistem Informasi Akademik

## Struktur File
```
stikes_mni/
├── index.php              ← Entry point & router
├── .htaccess              ← Apache config
├── includes/
│   ├── auth.php           ← Autentikasi login/logout
│   ├── data.php           ← Data mock (ganti dengan koneksi DB)
│   ├── helpers.php        ← Fungsi badge, format, dll
│   └── layout.php         ← Layout utama (sidebar + topbar)
├── pages/
│   ├── login.php          ← Halaman login
│   ├── dashboard.php      ← Dashboard ringkasan
│   ├── mahasiswa.php      ← Data mahasiswa + filter + cari
│   ├── dosen.php          ← Data dosen + direktori
│   ├── praktik.php        ← Praktik klinik
│   ├── keuangan.php       ← Laporan keuangan
│   ├── jadwal.php         ← Jadwal kuliah
│   ├── laporan.php        ← Generator laporan
│   └── pengaturan.php     ← Pengaturan sistem
└── assets/
    ├── css/main.css       ← Semua styling
    └── js/main.js         ← Interaksi (grafik, filter, dll)
```

## Akun Demo
| Username     | Password       | Role       |
|-------------|----------------|------------|
| admin        | stikesmni2025  | Admin      |
| dosen1       | dosen123       | Dosen      |
| mahasiswa1   | mhs123         | Mahasiswa  |
| keuangan1    | keu123         | Keuangan   |

## Cara Menjalankan
1. Upload seluruh folder ke server PHP (XAMPP, Laragon, dll)
2. Pastikan `mod_rewrite` aktif (untuk .htaccess)
3. Akses: `http://localhost/stikes_mni/`
4. Login dengan salah satu akun demo di atas

## Integrasi Database (MySQL)
Ganti `includes/data.php` dengan koneksi MySQL:
```php
$pdo = new PDO('mysql:host=localhost;dbname=stikes_mni', 'root', '');
$stmt = $pdo->query('SELECT * FROM mahasiswa');
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

## Kebutuhan Server
- PHP 7.4+ (atau PHP 8.x)
- Apache dengan mod_rewrite aktif
- Session support (default aktif di PHP)

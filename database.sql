-- ============================================================
-- STIKES Medika Nurul Islam — Database Setup
-- Jalankan file ini di MySQL/phpMyAdmin untuk membuat database
-- ============================================================

CREATE DATABASE IF NOT EXISTS stikes_mni CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stikes_mni;

-- ─── TABEL USERS (Admin, Dosen, Mahasiswa, Keuangan) ───────
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    nama        VARCHAR(100) NOT NULL,
    email       VARCHAR(100),
    role        ENUM('admin','dosen','mahasiswa','keuangan') NOT NULL DEFAULT 'mahasiswa',
    foto        VARCHAR(255),
    aktif       TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ─── TABEL PROGRAM STUDI ───────────────────────────────────
CREATE TABLE IF NOT EXISTS prodi (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    kode    VARCHAR(10) NOT NULL,
    nama    VARCHAR(100) NOT NULL,
    jenjang ENUM('D3','D4','S1','S2') DEFAULT 'D3',
    aktif   TINYINT(1) DEFAULT 1
);

-- ─── TABEL MAHASISWA ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS mahasiswa (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nim         VARCHAR(20)  NOT NULL UNIQUE,
    nama        VARCHAR(100) NOT NULL,
    email       VARCHAR(100),
    telepon     VARCHAR(20),
    prodi_id    INT,
    semester    TINYINT DEFAULT 1,
    angkatan    YEAR,
    ipk         DECIMAL(3,2) DEFAULT 0.00,
    status      ENUM('aktif','cuti','lulus','do') DEFAULT 'aktif',
    alamat      TEXT,
    tgl_lahir   DATE,
    jenis_kelamin ENUM('L','P'),
    foto        VARCHAR(255),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prodi_id) REFERENCES prodi(id) ON DELETE SET NULL
);

-- ─── TABEL DOSEN ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS dosen (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nip         VARCHAR(20)  NOT NULL UNIQUE,
    nama        VARCHAR(100) NOT NULL,
    email       VARCHAR(100),
    telepon     VARCHAR(20),
    prodi_id    INT,
    jabatan     ENUM('asisten_ahli','lektor','lektor_kepala','guru_besar') DEFAULT 'lektor',
    pendidikan  ENUM('S1','S2','S3') DEFAULT 'S2',
    spesialisasi VARCHAR(100),
    status      ENUM('aktif','cuti','pensiun') DEFAULT 'aktif',
    rating      DECIMAL(2,1) DEFAULT 4.0,
    foto        VARCHAR(255),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prodi_id) REFERENCES prodi(id) ON DELETE SET NULL
);

-- ─── TABEL LOKASI PRAKTIK ──────────────────────────────────
CREATE TABLE IF NOT EXISTS lokasi_praktik (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(150) NOT NULL,
    tipe        ENUM('rs_a','rs_b','rs_c','rs_swasta','puskesmas','klinik') DEFAULT 'puskesmas',
    kota        VARCHAR(80),
    alamat      TEXT,
    telepon     VARCHAR(20),
    kapasitas   INT DEFAULT 10,
    status      ENUM('aktif','menunggu','selesai','tidak_aktif') DEFAULT 'aktif',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── TABEL PENEMPATAN PRAKTIK ──────────────────────────────
CREATE TABLE IF NOT EXISTS penempatan_praktik (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    mahasiswa_id    INT NOT NULL,
    lokasi_id       INT NOT NULL,
    dosen_pembimbing INT,
    tanggal_mulai   DATE,
    tanggal_selesai DATE,
    status          ENUM('aktif','selesai','belum_mulai') DEFAULT 'belum_mulai',
    nilai           DECIMAL(4,2),
    laporan_status  ENUM('belum','draft','dikumpulkan','diverifikasi') DEFAULT 'belum',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mahasiswa_id)    REFERENCES mahasiswa(id) ON DELETE CASCADE,
    FOREIGN KEY (lokasi_id)       REFERENCES lokasi_praktik(id) ON DELETE CASCADE,
    FOREIGN KEY (dosen_pembimbing) REFERENCES dosen(id) ON DELETE SET NULL
);

-- ─── TABEL KEUANGAN ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS transaksi_keuangan (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tanggal     DATE NOT NULL,
    deskripsi   VARCHAR(255) NOT NULL,
    kategori    ENUM('spp','beasiswa','sarana','sdm','operasional','lainnya') DEFAULT 'lainnya',
    tipe        ENUM('masuk','keluar') NOT NULL,
    nominal     DECIMAL(15,2) NOT NULL,
    metode      ENUM('cash','transfer','qris','lainnya') DEFAULT 'transfer',
    referensi   VARCHAR(100),
    mahasiswa_id INT,
    keterangan  TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id) ON DELETE SET NULL
);

-- ─── TABEL MATA KULIAH ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS mata_kuliah (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    kode        VARCHAR(15) NOT NULL,
    nama        VARCHAR(150) NOT NULL,
    sks         TINYINT DEFAULT 2,
    prodi_id    INT,
    semester    TINYINT,
    jenis       ENUM('teori','praktikum','klinik') DEFAULT 'teori',
    FOREIGN KEY (prodi_id) REFERENCES prodi(id) ON DELETE SET NULL
);

-- ─── TABEL JADWAL ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS jadwal (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    matkul_id       INT NOT NULL,
    dosen_id        INT,
    hari            ENUM('senin','selasa','rabu','kamis','jumat','sabtu') NOT NULL,
    jam_mulai       TIME NOT NULL,
    jam_selesai     TIME NOT NULL,
    ruangan         VARCHAR(50),
    semester_aktif  VARCHAR(20) DEFAULT '2024/2025 Genap',
    FOREIGN KEY (matkul_id) REFERENCES mata_kuliah(id) ON DELETE CASCADE,
    FOREIGN KEY (dosen_id)  REFERENCES dosen(id) ON DELETE SET NULL
);

-- ─── TABEL PENGUMUMAN ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS pengumuman (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    judul       VARCHAR(255) NOT NULL,
    isi         TEXT,
    tipe        ENUM('info','warning','success','danger') DEFAULT 'info',
    target      ENUM('semua','mahasiswa','dosen','keuangan') DEFAULT 'semua',
    aktif       TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- DATA DUMMY
-- ============================================================

-- Users
INSERT INTO users (username, password, nama, email, role) VALUES
('admin',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@stikesmni.ac.id', 'admin'),
('dosen01',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dr. Siti Hajar, M.Kes', 'siti.hajar@stikesmni.ac.id', 'dosen'),
('mahasiswa01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anisa Nurhayati', 'anisa@stikesmni.ac.id', 'mahasiswa'),
('keuangan01',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bagian Keuangan', 'keuangan@stikesmni.ac.id', 'keuangan');
-- Password semua akun demo: "password"

-- Prodi
INSERT INTO prodi (kode, nama, jenjang) VALUES
('KEP', 'Keperawatan', 'D3'),
('KEB', 'Kebidanan', 'D3'),
('FAR', 'Farmasi', 'D3'),
('AKL', 'Analis Kesehatan', 'D3'),
('FIS', 'Fisioterapi', 'D3');

-- Mahasiswa
INSERT INTO mahasiswa (nim, nama, email, prodi_id, semester, angkatan, ipk, status, jenis_kelamin) VALUES
('2021.01.042', 'Anisa Nurhayati',  'anisa.n@stikesmni.ac.id',    1, 6, 2021, 3.75, 'aktif',  'P'),
('2022.02.018', 'Budi Raharjo',     'budi.r@stikesmni.ac.id',     2, 4, 2022, 3.60, 'aktif',  'L'),
('2023.03.007', 'Citra Sari',       'citra.s@stikesmni.ac.id',    3, 2, 2023, 3.45, 'aktif',  'P'),
('2021.01.061', 'Dian Pratiwi',     'dian.p@stikesmni.ac.id',     1, 6, 2021, 0.00, 'cuti',   'P'),
('2020.01.033', 'Eko Wahyudi',      'eko.w@stikesmni.ac.id',      4, 8, 2020, 3.82, 'lulus',  'L'),
('2022.01.099', 'Fitriani Lubis',   'fitriani.l@stikesmni.ac.id', 1, 4, 2022, 3.52, 'aktif',  'P'),
('2023.05.012', 'Gilang Hakim',     'gilang.h@stikesmni.ac.id',   5, 2, 2023, 3.38, 'aktif',  'L'),
('2021.02.055', 'Hana Safitri',     'hana.s@stikesmni.ac.id',     2, 6, 2021, 3.70, 'aktif',  'P'),
('2022.04.031', 'Irfan Maulana',    'irfan.m@stikesmni.ac.id',    4, 4, 2022, 3.15, 'aktif',  'L'),
('2020.03.018', 'Juwita Permata',   'juwita.p@stikesmni.ac.id',   3, 8, 2020, 3.90, 'lulus',  'P');

-- Dosen
INSERT INTO dosen (nip, nama, email, prodi_id, jabatan, pendidikan, spesialisasi, status, rating) VALUES
('198501012010', 'dr. Siti Hajar, M.Kes',        'siti.hajar@stikesmni.ac.id',  1, 'lektor_kepala', 'S2', 'Keperawatan Kritis',  'aktif', 4.9),
('198703152012', 'Ahmad Riza, S.Kep., M.Kep',    'ahmad.riza@stikesmni.ac.id',  1, 'lektor',        'S2', 'Keperawatan Medikal', 'aktif', 4.8),
('197908012008', 'Rahmi Maulida, Ph.D',           'rahmi.m@stikesmni.ac.id',     3, 'guru_besar',    'S3', 'Farmakognosi',        'aktif', 4.8),
('198812202015', 'Baharuddin, M.Farm',            'baharuddin@stikesmni.ac.id',  3, 'lektor',        'S2', 'Farmasi Klinik',      'aktif', 4.7),
('199001052018', 'Zulfikar Amri, M.Fis',          'zulfikar.a@stikesmni.ac.id',  5, 'asisten_ahli',  'S2', 'Fisioterapi Ortopedi','aktif', 4.6),
('198506182011', 'Nurul Izzati, Bd., M.Keb',      'nurul.i@stikesmni.ac.id',     2, 'lektor',        'S2', 'Kebidanan Komunitas', 'cuti',  4.5),
('199205102019', 'Mira Susanti, M.Si',            'mira.s@stikesmni.ac.id',      4, 'asisten_ahli',  'S2', 'Hematologi',          'aktif', 4.7),
('198407222009', 'Hasanuddin, M.Kes',             'hasanuddin@stikesmni.ac.id',  1, 'lektor_kepala', 'S2', 'Keperawatan Jiwa',    'aktif', 4.6);

-- Lokasi Praktik
INSERT INTO lokasi_praktik (nama, tipe, kota, kapasitas, status) VALUES
('RSUD Zainal Abidin',      'rs_a',     'Banda Aceh',   60, 'aktif'),
('RS Pertamedika',          'rs_swasta','Banda Aceh',   40, 'aktif'),
('Puskesmas Garot',         'puskesmas','Sigli',         25, 'menunggu'),
('RSUD Tgk. Chik Ditiro',   'rs_b',     'Sigli',         30, 'selesai'),
('RSU Bunda Thamrin',       'rs_swasta','Medan',         35, 'aktif'),
('Klinik Pratama Sehat',    'klinik',   'Banda Aceh',   15, 'aktif'),
('Puskesmas Batuphat',      'puskesmas','Lhokseumawe',  20, 'aktif'),
('RSUD Cut Meutia',         'rs_b',     'Lhokseumawe',  40, 'aktif');

-- Transaksi Keuangan
INSERT INTO transaksi_keuangan (tanggal, deskripsi, kategori, tipe, nominal, metode, mahasiswa_id) VALUES
('2025-06-28', 'SPP Semester Genap 2025 - Anisa Nurhayati',   'spp',       'masuk',  3500000, 'transfer', 1),
('2025-06-27', 'Pembelian Alat Lab Keperawatan',               'sarana',    'keluar', 8750000, 'transfer', NULL),
('2025-06-26', 'Gaji Dosen & Staff Bulan Juni 2025',           'sdm',       'keluar',42000000, 'transfer', NULL),
('2025-06-25', 'Beasiswa Prestasi - Eko Wahyudi',              'beasiswa',  'keluar', 3500000, 'transfer', 5),
('2025-06-24', 'SPP & Uang Gedung - Fitriani Lubis',           'spp',       'masuk',  4200000, 'qris',     6),
('2025-06-23', 'Biaya Operasional Kampus Bulan Juni',          'operasional','keluar',5500000, 'transfer', NULL),
('2025-06-20', 'SPP Semester Genap - Budi Raharjo',            'spp',       'masuk',  3500000, 'transfer', 2),
('2025-06-18', 'SPP Semester Genap - Gilang Hakim',            'spp',       'masuk',  3500000, 'cash',     7),
('2025-06-15', 'Pengadaan Buku Perpustakaan',                  'sarana',    'keluar', 6200000, 'transfer', NULL),
('2025-06-10', 'SPP & Biaya Praktik - Citra Sari',             'spp',       'masuk',  4800000, 'qris',     3);

-- Mata Kuliah
INSERT INTO mata_kuliah (kode, nama, sks, prodi_id, semester, jenis) VALUES
('KEP101', 'Keperawatan Dasar',       3, 1, 1, 'teori'),
('KEP102', 'Anatomi Fisiologi',       3, 1, 1, 'teori'),
('KEP201', 'Komunikasi Keperawatan',  2, 1, 2, 'teori'),
('KEP301', 'Keperawatan Medikal Bedah',4,1, 3, 'teori'),
('KEP302', 'Keperawatan Jiwa',        3, 1, 5, 'teori'),
('KEP401', 'Praktik Klinik I',        4, 1, 4, 'klinik'),
('FAR101', 'Farmakologi',             3, 3, 3, 'teori'),
('ETK101', 'Etika Keperawatan',       2, 1, 1, 'teori');

-- Jadwal
INSERT INTO jadwal (matkul_id, dosen_id, hari, jam_mulai, jam_selesai, ruangan, semester_aktif) VALUES
(1, 1, 'senin',  '07:30', '09:10', 'Ruang A1',   '2024/2025 Genap'),
(2, 8, 'senin',  '10:00', '11:40', 'Lab Bio',    '2024/2025 Genap'),
(3, 2, 'selasa', '08:00', '09:40', 'Ruang B2',   '2024/2025 Genap'),
(6, 1, 'rabu',   '07:30', '09:10', 'RSUD ZA',    '2024/2025 Genap'),
(8, 2, 'rabu',   '13:00', '14:40', 'Ruang A3',   '2024/2025 Genap'),
(7, 4, 'kamis',  '09:00', '10:40', 'Lab Farmasi','2024/2025 Genap'),
(5, 8, 'jumat',  '07:30', '09:10', 'Ruang C1',   '2024/2025 Genap');

-- Pengumuman
INSERT INTO pengumuman (judul, isi, tipe, target) VALUES
('Batas Pengisian KRS',         'Mahasiswa semester III belum mengisi KRS. Tenggat: 15 Juli 2025', 'warning', 'mahasiswa'),
('Jadwal UAS Sudah Dirilis',    'Jadwal UAS Semester Genap tersedia di portal akademik.',           'info',    'semua'),
('Akreditasi LAM-PTKes B',      'Prodi Keperawatan berhasil mempertahankan Akreditasi B.',          'success', 'semua'),
('Laporan Praktik Tertunda',    '23 mahasiswa belum mengumpulkan laporan praktik RS Pertamedika.',  'warning', 'mahasiswa');

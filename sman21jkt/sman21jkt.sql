-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql305.byetcluster.com
-- Generation Time: Oct 06, 2026 at 09:41 AM
-- Server version: 11.4.13-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `b3_43046986_sman21jkt`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin21', 'de43afbf88aa74e31545daa74134b3f7');

-- --------------------------------------------------------

--
-- Table structure for table `agenda`
--

CREATE TABLE `agenda` (
  `id` int(11) NOT NULL,
  `judul_agenda` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'published'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `agenda`
--

INSERT INTO `agenda` (`id`, `judul_agenda`, `slug`, `deskripsi`, `tanggal_mulai`, `tanggal_selesai`, `jam_mulai`, `jam_selesai`, `lokasi`, `gambar`, `link`, `status`) VALUES
(0, 'Rapat Pleno OSIS MPK 2026', 'rapat-pleno-osis-mpk-2026', 'JJAJA', '2026-10-05', '2026-10-07', '18:23:00', '19:23:00', 'Gedung A, AULA', 'uploads/agenda/agenda_1791113013_585.png', '', 'published');

-- --------------------------------------------------------

--
-- Table structure for table `ai_kategori`
--

CREATE TABLE `ai_kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `urutan` int(11) NOT NULL DEFAULT 1,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_kategori`
--

INSERT INTO `ai_kategori` (`id`, `nama_kategori`, `deskripsi`, `urutan`, `status_aktif`) VALUES
(1, 'Profil Sekolah', 'Informasi umum dan profil sekolah', 1, 1),
(2, 'Akademik', 'Informasi program kurikulum, jurusan, dan mata pelajaran', 2, 1),
(3, 'PPDB', 'Informasi penerimaan peserta didik baru', 3, 1),
(4, 'Prestasi', 'Informasi capaian dan kejuaraan siswa', 4, 1),
(5, 'Kegiatan', 'Informasi agenda dan kegiatan sekolah', 5, 1),
(6, 'Ekstrakurikuler', 'Informasi daftar ekskul dan jadwalnya', 6, 1),
(7, 'Fasilitas', 'Informasi sarana dan prasarana sekolah', 7, 1),
(8, 'Agenda', 'Jadwal kalender akademik dan acara', 8, 1),
(9, 'Alumni', 'Informasi lulusan dan rekam jejak alumni', 9, 1),
(10, 'Kontak', 'Informasi alamat, nomor telepon, dan lokasi', 10, 1);

-- --------------------------------------------------------

--
-- Table structure for table `ai_knowledge`
--

CREATE TABLE `ai_knowledge` (
  `id` int(11) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `pertanyaan` text NOT NULL,
  `jawaban` text NOT NULL,
  `keyword` text DEFAULT NULL,
  `sumber_informasi` varchar(255) DEFAULT NULL,
  `prioritas` int(11) NOT NULL DEFAULT 1,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1,
  `tanggal_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_pengaturan`
--

CREATE TABLE `ai_pengaturan` (
  `id` int(11) NOT NULL,
  `nama_sekolah` varchar(255) NOT NULL DEFAULT 'SMAN 21 Jakarta',
  `system_prompt` text DEFAULT NULL,
  `pesan_pembuka` text DEFAULT NULL,
  `pesan_tidak_tersedia` text DEFAULT NULL,
  `model_ai` varchar(100) DEFAULT 'gpt-4o-mini',
  `temperature` decimal(3,2) NOT NULL DEFAULT 0.70,
  `max_token` int(11) NOT NULL DEFAULT 1000,
  `status_ai` tinyint(1) NOT NULL DEFAULT 1,
  `tanggal_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_pengaturan`
--

INSERT INTO `ai_pengaturan` (`id`, `nama_sekolah`, `system_prompt`, `pesan_pembuka`, `pesan_tidak_tersedia`, `model_ai`, `temperature`, `max_token`, `status_ai`, `tanggal_update`) VALUES
(1, 'SMAN 21 Jakarta', 'Anda adalah asisten AI resmi SMAN 21 Jakarta. Jawablah pertanyaan pengguna dengan sopan, akurat, dan ramah berdasarkan data resmi sekolah.', 'Halo! Saya AI Assistant SMAN 21 Jakarta. Ada yang ingin kamu ketahui tentang sekolah kami?', 'Maaf, informasi tersebut belum tersedia dalam database resmi sekolah.', 'gemini-1.5-flash', '0.70', 1000, 1, '2026-10-03 14:46:16');

-- --------------------------------------------------------

--
-- Table structure for table `alumni`
--

CREATE TABLE `alumni` (
  `id` int(11) NOT NULL,
  `nama_alumni` varchar(255) NOT NULL,
  `tahun_lulus` int(4) NOT NULL,
  `jurusan_program` varchar(100) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `universitas_instansi` varchar(255) DEFAULT NULL,
  `profesi` varchar(255) DEFAULT NULL,
  `cerita` text DEFAULT NULL,
  `prestasi` text DEFAULT NULL,
  `status_tampil` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `alumni`
--

INSERT INTO `alumni` (`id`, `nama_alumni`, `tahun_lulus`, `jurusan_program`, `foto`, `universitas_instansi`, `profesi`, `cerita`, `prestasi`, `status_tampil`) VALUES
(0, 'Gabriel', 2026, 'IPS 2', 'alumni_1791114792_7291.png', 'Zheijang University', 'Mahasiswa', 'baguss bangett 21 hebat !!', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `berita`
--

CREATE TABLE `berita` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `ringkasan` text DEFAULT NULL,
  `isi` longtext NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `gambar_utama` varchar(255) DEFAULT NULL,
  `penulis` varchar(100) DEFAULT 'Admin',
  `kategori` varchar(100) DEFAULT 'Umum',
  `tanggal_publikasi` datetime DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `jumlah_dilihat` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `berita`
--

INSERT INTO `berita` (`id`, `judul`, `slug`, `ringkasan`, `isi`, `thumbnail`, `gambar_utama`, `penulis`, `kategori`, `tanggal_publikasi`, `status`, `jumlah_dilihat`) VALUES
(0, 'aaaa', 'aaaa', 'aaaa', 'jjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjj', 'uploads/berita/berita_1791114885_942.jpg', 'uploads/berita/berita_1791114885_942.jpg', 'Admin SMAN 21', 'Umum', '2026-10-04 18:54:00', 'published', 8);

-- --------------------------------------------------------

--
-- Table structure for table `ekstrakurikuler`
--

CREATE TABLE `ekstrakurikuler` (
  `id` int(11) NOT NULL,
  `nama_ekskul` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `pembina` varchar(255) DEFAULT NULL,
  `jadwal` varchar(255) DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `logo_foto` varchar(255) DEFAULT NULL,
  `kontak` varchar(100) DEFAULT NULL,
  `urutan` int(11) NOT NULL DEFAULT 1,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ekstrakurikuler`
--

INSERT INTO `ekstrakurikuler` (`id`, `nama_ekskul`, `slug`, `deskripsi`, `pembina`, `jadwal`, `lokasi`, `logo_foto`, `kontak`, `urutan`, `status_aktif`) VALUES
(0, 'BASKET', 'gabeis', 'sassas', 'ga tau', 'senin 16.30', 'Gedung A', 'ekskul_1791036594_263.png', '@dust21', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `fasilitas`
--

CREATE TABLE `fasilitas` (
  `id` int(11) NOT NULL,
  `nama_fasilitas` varchar(255) NOT NULL,
  `kategori` varchar(100) NOT NULL DEFAULT 'Umum',
  `deskripsi` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `lokasi` varchar(255) DEFAULT NULL,
  `fasilitas_pendukung` text DEFAULT NULL,
  `urutan` int(11) NOT NULL DEFAULT 1,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `galeri`
--

CREATE TABLE `galeri` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `kategori` varchar(100) DEFAULT 'Sekolah',
  `deskripsi` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `file_media` varchar(255) DEFAULT NULL,
  `tipe_media` varchar(50) DEFAULT 'image',
  `tanggal` date DEFAULT NULL,
  `urutan` int(11) DEFAULT 1,
  `status_aktif` tinyint(1) DEFAULT 1,
  `status` varchar(50) DEFAULT 'published'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `kategori` varchar(100) NOT NULL DEFAULT 'Sekolah',
  `deskripsi` text DEFAULT NULL,
  `file_media` varchar(255) NOT NULL,
  `tipe_media` enum('image','video') NOT NULL DEFAULT 'image',
  `tanggal` date DEFAULT NULL,
  `urutan` int(11) NOT NULL DEFAULT 1,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`id`, `judul`, `kategori`, `deskripsi`, `file_media`, `tipe_media`, `tanggal`, `urutan`, `status_aktif`) VALUES
(1, 'Sambutan Kepala SMAN 21 Jakarta', 'Sekolah', 'haloo', 'galeri_1791115573_134.png', 'image', '2026-10-04', 1, 1),
(2, 'EJUWUWU', 'Sekolah', 'SSS', 'galeri_1791213443_241.jpg', 'image', '2026-10-05', 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `guru`
--

CREATE TABLE `guru` (
  `id` int(11) NOT NULL,
  `mapel_id` int(11) DEFAULT NULL,
  `nama_guru` varchar(255) NOT NULL,
  `jabatan` varchar(150) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `guru`
--

INSERT INTO `guru` (`id`, `mapel_id`, `nama_guru`, `jabatan`) VALUES
(1, 1, 'Mutia, M.Pd', 'Kepala Sekolah'),
(2, 2, 'Muhammad Irfan Septiano, S.Pd', 'Wakil Kepala Sekolah Bidang Akademik'),
(3, 3, 'Drs. Saksi Ginting', 'Staf Bidang Akademik'),
(4, 3, 'Alfredo, S.P', 'Staf Bidang Akademik'),
(5, 3, 'Dian Fitriyani S.Si, M.Pd', 'Staf Bidang Akademik'),
(6, 4, 'Suyamti, M.Pd', 'Wakil Kepala Sekolah Bidang Kesiswaan & Humas'),
(7, 5, 'Esty Wulandini, S.Pd', 'Staf Bidang Kesiswaan & Humas'),
(8, 5, 'Nur Putri Halida, S.Pd', 'Staf Bidang Kesiswaan & Humas'),
(9, 5, 'Asep Supriadi, S.Pd', 'Staf Bidang Kesiswaan & Humas'),
(10, 6, 'Ika Rahmawatie, S.Pd', 'Wakil Kepala Sekolah Bidang Sarpras & Administrasi'),
(11, 7, 'Agus Purnomo, S.Pd', 'Staf Bidang Sarpras & Administrasi'),
(12, 7, 'Sofia Caesara, S.Pd', 'Staf Bidang Sarpras & Administrasi'),
(13, 8, 'Jumardika, S.Pd', 'Guru Pendidikan Agama'),
(14, 8, 'Suherman, M.Pd', 'Guru Pendidikan Agama'),
(15, 8, 'Rosmawati, M.Pd', 'Guru Pendidikan Agama'),
(16, 8, 'Sr. M. Priscila', 'Guru Pendidikan Agama'),
(17, 9, 'Sisri Priantini, S.Pd', 'Guru Sosiologi'),
(18, 9, 'Winda Mishbah, S.Pd', 'Guru Sosiologi'),
(19, 9, 'Poppy Eka Darwiyanti, S.E', 'Guru Sosiologi'),
(20, 10, 'Mutmainah, M.Pd', 'Guru Bahasa Indonesia'),
(21, 10, 'Suyamti, M.Pd', 'Guru Bahasa Indonesia'),
(22, 11, 'Sri Retnowati, M.Pd', 'Guru Pendidikan Pancasila'),
(23, 11, 'Nur Putri Halida, S.Pd', 'Guru Pendidikan Pancasila'),
(24, 12, 'Mahatir Muhammad, S.Pd', 'Guru Penjaskes'),
(25, 12, 'Imam Hanafi, S.Pd', 'Guru Penjaskes'),
(26, 13, 'Tri Yatmi, S.Pd', 'Guru Ekonomi'),
(27, 13, 'Poppy Eka Darwiyanti, S.E', 'Guru Ekonomi'),
(28, 13, 'Agus Purnomo, S.Pd', 'Guru Ekonomi'),
(29, 13, 'Rini Yulianti, S.Pd', 'Guru Ekonomi'),
(30, 14, 'Jundan Iskandar, M.Pd', 'Guru Fisika'),
(31, 14, 'Shahnaz Nadia, M.Pd', 'Guru Fisika'),
(32, 15, 'Musngidah, S.Pd', 'Guru Geografi'),
(33, 15, 'Sepriyanta Simanjuntak, S.E', 'Guru Geografi'),
(34, 16, 'M. Irfan Septiano, S.Pd', 'Guru Biologi'),
(35, 16, 'Sofia Cesara, S.Pd', 'Guru Biologi'),
(36, 16, 'M. Dicky Alphian, S.Pd', 'Guru Biologi'),
(37, 17, 'Th. Prima Anggarini, S.Pd', 'Guru Bahasa Inggris'),
(38, 17, 'Selvia Gumay, M.Pd', 'Guru Bahasa Inggris'),
(39, 18, 'Kamil Ahmad, S.Pd', 'Guru Bimbingan & Konseling'),
(40, 18, 'Yuyun Wahyuni, S.Pd', 'Guru Bimbingan & Konseling'),
(41, 19, 'Ifna Yeni, S.Pd', 'Guru Sejarah'),
(42, 19, 'Esty Wulandini, S.Pd', 'Guru Sejarah'),
(43, 19, 'Nur Putri Halida, S.Pd', 'Guru Sejarah'),
(44, 20, 'Esty Wulandini, S.Pd', 'Guru Sejarah Lanjut'),
(45, 21, 'Asep Supriadi, S.Pd', 'Guru Prakarya'),
(46, 21, 'Sofia Cesara, S.Pd', 'Guru Prakarya'),
(47, 21, 'Poppy Eka Darwiyanti, S.E', 'Guru Prakarya'),
(48, 21, 'Rini Yulianti, S.Pd', 'Guru Prakarya'),
(49, 22, 'Asep Supriadi, M.Pd', 'Guru Kimia'),
(50, 22, 'Ika Rakhmawatie S.Pd', 'Guru Kimia'),
(51, 22, 'Christina Purba, S.Pd', 'Guru Kimia'),
(52, 23, 'Sutarmin, S.T', 'Guru Koding & KA'),
(53, 24, 'Drs. Saksi Ginting', 'Guru Matematika'),
(54, 24, 'Nani Kusmiyati, M.Pd', 'Guru Matematika'),
(55, 24, 'Alfredo, S.P', 'Guru Matematika'),
(56, 24, 'Dian Fitriyani, S.Si, M.Pd', 'Guru Matematika'),
(57, 25, 'Dra. Meilina Nainggolan', 'Guru Seni Budaya'),
(58, 25, 'Winda Mishbah, S.Pd', 'Guru Seni Budaya'),
(59, 26, 'Alfredo, S.P', 'Guru Matematika Lanjut'),
(60, 26, 'Dian Fitriyani, S.Si, M.Pd', 'Guru Matematika Lanjut');

-- --------------------------------------------------------

--
-- Table structure for table `hero_banners`
--

CREATE TABLE `hero_banners` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `background_image` varchar(255) NOT NULL,
  `button_text` varchar(100) DEFAULT NULL,
  `button_link` varchar(255) DEFAULT NULL,
  `text_align` enum('left','center','right') DEFAULT 'center',
  `font_family` varchar(100) DEFAULT 'Poppins',
  `is_bold` tinyint(1) DEFAULT 1,
  `is_italic` tinyint(1) DEFAULT 0,
  `animation_type` enum('fade','slide') DEFAULT 'fade',
  `sort_order` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `hero_banners`
--

INSERT INTO `hero_banners` (`id`, `title`, `description`, `background_image`, `button_text`, `button_link`, `text_align`, `font_family`, `is_bold`, `is_italic`, `animation_type`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'HALO', 'STUDI KASUSU', 'banner_1791097487_759.jpg', 'AYO BERSAMA KAMI', 'profil/sejarah.php', 'center', 'Poppins', 1, 0, 'fade', 1, 1, '2026-10-04 06:54:53', '2026-10-04 07:04:47'),
(2, 'SPMB 2026 SUDAH DI BUKA', 'SUB JUDUL', 'banner_1791167173_860.jpg', 'AYO BERSAMA KAMI', 'informasi/ppdb.php', 'center', 'Montserrat', 1, 0, 'fade', 1, 1, '2026-10-05 02:26:14', '2026-10-06 08:48:29');

-- --------------------------------------------------------

--
-- Table structure for table `kegiatan`
--

CREATE TABLE `kegiatan` (
  `id` int(11) NOT NULL,
  `judul_kegiatan` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `foto_utama` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'published',
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kegiatan`
--

INSERT INTO `kegiatan` (`id`, `judul_kegiatan`, `slug`, `kategori`, `deskripsi`, `tanggal_mulai`, `tanggal_selesai`, `lokasi`, `thumbnail`, `foto_utama`, `status`, `tanggal_dibuat`) VALUES
(0, 'jjj', 'jjj', 'Akademik', 'mmm', NULL, NULL, 'nnn', 'thumb_1791214389_628.jpg', 'foto_1791214389_559.jpg', 'published', '2026-10-05 15:33:10');

-- --------------------------------------------------------

--
-- Table structure for table `keterserapan_ptn`
--

CREATE TABLE `keterserapan_ptn` (
  `id` int(11) NOT NULL,
  `tahun` int(11) NOT NULL,
  `jumlah_lulusan` int(11) NOT NULL,
  `snbp` int(11) NOT NULL,
  `snbt` int(11) NOT NULL,
  `mandiri` int(11) NOT NULL,
  `ptln` int(11) NOT NULL,
  `kedinasan` int(11) NOT NULL,
  `pts` int(11) DEFAULT 0,
  `gap_year` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `keterserapan_ptn`
--

INSERT INTO `keterserapan_ptn` (`id`, `tahun`, `jumlah_lulusan`, `snbp`, `snbt`, `mandiri`, `ptln`, `kedinasan`, `pts`, `gap_year`) VALUES
(1, 2021, 254, 30, 74, 144, 0, 5, 0, 1),
(2, 2022, 251, 34, 105, 66, 4, 5, 0, 37),
(3, 2023, 288, 50, 57, 106, 10, 1, 8, 64),
(4, 2024, 252, 25, 25, 71, 2, 2, 16, 127),
(5, 2025, 251, 17, 50, 99, 5, 0, 30, 50);

-- --------------------------------------------------------

--
-- Table structure for table `kontak_sekolah`
--

CREATE TABLE `kontak_sekolah` (
  `id` int(11) NOT NULL,
  `alamat` text DEFAULT NULL,
  `kode_pos` varchar(10) DEFAULT NULL,
  `telepon` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `whatsapp` varchar(50) DEFAULT NULL,
  `google_maps_url` text DEFAULT NULL,
  `google_maps_embed` text DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `youtube` varchar(255) DEFAULT NULL,
  `tiktok` varchar(255) DEFAULT NULL,
  `jam_operasional` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kontak_sekolah`
--

INSERT INTO `kontak_sekolah` (`id`, `alamat`, `kode_pos`, `telepon`, `email`, `whatsapp`, `google_maps_url`, `google_maps_embed`, `latitude`, `longitude`, `instagram`, `facebook`, `youtube`, `tiktok`, `jam_operasional`) VALUES
(1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Senin - Jumat: 06.30 - 15.00 WIB');

-- --------------------------------------------------------

--
-- Table structure for table `mapel`
--

CREATE TABLE `mapel` (
  `id` int(11) NOT NULL,
  `nama_mapel` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `mapel`
--

INSERT INTO `mapel` (`id`, `nama_mapel`) VALUES
(1, 'Kepala Sekolah'),
(2, 'Wakil Bidang Akademik'),
(3, 'Staf Bidang Akademik'),
(4, 'Wakil Bidang Kesiswaan & Humas'),
(5, 'Staf Bidang Kesiswaan & Humas'),
(6, 'Wakil Bidang Sarpras & Administrasi'),
(7, 'Staf Bidang Sarpras & Administrasi'),
(8, 'Pendidikan Agama'),
(9, 'Sosiologi'),
(10, 'Bahasa Indonesia'),
(11, 'Pendidikan Pancasila'),
(12, 'Penjaskes'),
(13, 'Ekonomi'),
(14, 'Fisika'),
(15, 'Geografi'),
(16, 'Biologi'),
(17, 'Bahasa Inggris'),
(18, 'Bimbingan & Konseling'),
(19, 'Sejarah'),
(20, 'Sejarah Lanjut'),
(21, 'Prakarya'),
(22, 'Kimia'),
(23, 'Koding & KA'),
(24, 'Matematika'),
(25, 'Seni Budaya'),
(26, 'Matematika Lanjut');

-- --------------------------------------------------------

--
-- Table structure for table `pemeringkatan`
--

CREATE TABLE `pemeringkatan` (
  `id` int(11) NOT NULL,
  `nama_jurusan` varchar(100) NOT NULL,
  `daftar_mapel` text NOT NULL,
  `nilai_tertinggi` decimal(5,2) NOT NULL,
  `nilai_terendah` decimal(5,2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pemeringkatan`
--

INSERT INTO `pemeringkatan` (`id`, `nama_jurusan`, `daftar_mapel`, `nilai_tertinggi`, `nilai_terendah`) VALUES
(1, 'SAINTEK (Sains & Teknik)', 'Fisika, Kimia, Biologi, Matematika', '92.78', '80.25'),
(2, 'SOSHUM (Sosial & Humaniora)', 'Sosiologi, Geografi, Ekonomi, Sejarah Lanjut', '88.50', '77.00'),
(3, 'TERAPAN 1', 'Kimia, Biologi, Ekonomi, Sosiologi (2026/2027)', '88.00', '78.50'),
(4, 'TERAPAN 2', 'Fisika, Kimia, Biologi, Ekonomi (2026/2027)', '88.63', '81.00');

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan_sekolah`
--

CREATE TABLE `pengaturan_sekolah` (
  `id` int(11) NOT NULL,
  `nama_sekolah` varchar(255) NOT NULL DEFAULT 'SMAN 21 Jakarta',
  `npsn` varchar(50) DEFAULT NULL,
  `nss` varchar(50) DEFAULT NULL,
  `jenjang` varchar(50) DEFAULT 'SMA',
  `status_sekolah` varchar(50) DEFAULT 'Negeri',
  `alamat` text DEFAULT NULL,
  `kode_pos` varchar(10) DEFAULT NULL,
  `telepon` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `deskripsi_singkat` text DEFAULT NULL,
  `tahun_berdiri` varchar(10) DEFAULT NULL,
  `akreditasi` varchar(10) DEFAULT 'A',
  `google_maps_embed` text DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `youtube` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `tiktok` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengaturan_sekolah`
--

INSERT INTO `pengaturan_sekolah` (`id`, `nama_sekolah`, `npsn`, `nss`, `jenjang`, `status_sekolah`, `alamat`, `kode_pos`, `telepon`, `email`, `website`, `logo`, `favicon`, `slogan`, `deskripsi_singkat`, `tahun_berdiri`, `akreditasi`, `google_maps_embed`, `latitude`, `longitude`, `instagram`, `youtube`, `facebook`, `tiktok`) VALUES
(1, 'SMAN 21 Jakarta', NULL, NULL, 'SMA', 'Negeri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'A', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan_visimisi`
--

CREATE TABLE `pengaturan_visimisi` (
  `id` int(11) NOT NULL,
  `jenis` varchar(20) NOT NULL,
  `isi_teks` text NOT NULL,
  `urutan` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pengaturan_visimisi`
--

INSERT INTO `pengaturan_visimisi` (`id`, `jenis`, `isi_teks`, `urutan`) VALUES
(1, 'visi', 'Unggul dalam Prestasi dan Teknologi, Berkarakter Pancasila, Berbudaya Lingkungan, berdasarkan Iman dan Taqwa.', 1),
(2, 'misi', 'Mewujudkan Profil Pelajar Pancasila sebagai karakter kuat yang melekat pada seluruh peserta didik dan warga sekolah.', 1),
(3, 'misi', 'Menumbuhkan semangat berprestasi di bidang akademik, non akademik, dan teknologi.', 2),
(4, 'misi', 'Menciptakan dan menjaga lingkungan sehat.', 3),
(5, 'misi', 'Meningkatkan kualitas sumber daya manusia.', 4),
(6, 'misi', 'Mengembangkan metode pembelajaran yang up to date.', 5),
(7, 'misi', 'Menerapkan manajemen partisipatif berbasis sekolah dan berkesinambungan.', 6);

-- --------------------------------------------------------

--
-- Table structure for table `ppdb`
--

CREATE TABLE `ppdb` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `persyaratan` text DEFAULT NULL,
  `alur_pendaftaran` text DEFAULT NULL,
  `dokumen` text DEFAULT NULL,
  `link_pendaftaran` varchar(255) DEFAULT NULL,
  `kontak` varchar(100) DEFAULT NULL,
  `status` enum('buka','tutup','persiapan') NOT NULL DEFAULT 'buka'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ppdb`
--

INSERT INTO `ppdb` (`id`, `judul`, `deskripsi`, `tahun_ajaran`, `tanggal_mulai`, `tanggal_selesai`, `persyaratan`, `alur_pendaftaran`, `dokumen`, `link_pendaftaran`, `kontak`, `status`) VALUES
(0, 'SPMB TAHUN AJARAN 2027/2028', 'HALOO', '2027/2028', '2026-10-04', '2026-10-08', '1. iJAZAH\r\n2. kaU\r\n3. bAPak mu', '1. Registrasi\r\n2. Gatau\r\n3. Cetak persyaratan', 'HAERUS DI UNGGAH', 'https://spmbsman21jkt.byethost22.com', 'PANITIA SPMB (0895351440376)', 'buka');

-- --------------------------------------------------------

--
-- Table structure for table `prestasi`
--

CREATE TABLE `prestasi` (
  `id` int(11) NOT NULL,
  `judul_prestasi` varchar(255) NOT NULL,
  `kategori` varchar(100) NOT NULL DEFAULT 'Akademik',
  `tingkat` varchar(100) NOT NULL,
  `peringkat` varchar(100) NOT NULL,
  `nama_siswa` varchar(255) NOT NULL,
  `penyelenggara` varchar(255) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prestasi`
--

INSERT INTO `prestasi` (`id`, `judul_prestasi`, `kategori`, `tingkat`, `peringkat`, `nama_siswa`, `penyelenggara`, `tanggal`, `deskripsi`, `foto`, `dokumen`, `status_aktif`) VALUES
(0, 'JUARA O2SN BASKET 2026', 'Akademik', 'Kota / Kabupaten', 'Juara 1', 'GA TAU', 'KEMENDIKBUD', '2026-10-05', 'AJKM', 'prestasi_1791218765_495.png', '', 1),
(0, 'JUARA O2SN BASKET 2026', 'Akademik', 'Kota / Kabupaten', 'Juara 1', 'GA TAU', 'KEMENDIKBUD', '2026-10-05', 'ADGTHU', 'prestasi_1791218796_226.png', '', 1),
(0, 'JUARA OSN BAGYS', 'Akademik', 'Provinsi', 'Juara 1', 'GA TAU', 'KEMENDIKBUD', '2026-10-05', 'HELLO', 'prestasi_1791218888_853.png', '', 1),
(0, 'JUARA OSN BAGYS', 'Akademik', 'Nasional', 'Juara 1', 'GA TAU', 'KEMENDIKBUD', '2026-10-05', 'JJ', 'prestasi_1791219015_443.jpg', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `profil_sejarah`
--

CREATE TABLE `profil_sejarah` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `isi_sejarah` text NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `urutan` int(11) NOT NULL DEFAULT 1,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1,
  `tanggal_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `profil_sejarah`
--

INSERT INTO `profil_sejarah` (`id`, `judul`, `isi_sejarah`, `gambar`, `urutan`, `status_aktif`, `tanggal_update`) VALUES
(0, '2026', 'HALO', 'sejarah_1791044355_535.png', 1, 1, '2026-10-03 16:19:15');

-- --------------------------------------------------------

--
-- Table structure for table `program_pendidikan`
--

CREATE TABLE `program_pendidikan` (
  `id` int(11) NOT NULL,
  `nama_program` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `ikon` varchar(100) DEFAULT NULL,
  `urutan` int(11) NOT NULL DEFAULT 1,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `program_pendidikan`
--

INSERT INTO `program_pendidikan` (`id`, `nama_program`, `slug`, `deskripsi`, `gambar`, `ikon`, `urutan`, `status_aktif`) VALUES
(0, 'KURIKULUM MERDEKA', 'kurikulum-merdeka', 'SDDS', 'uploads/program/program_1791036236_883.jpg', 'fa-solid fa-graduation-cap', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `program_unggulan`
--

CREATE TABLE `program_unggulan` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `ikon` varchar(100) DEFAULT NULL,
  `urutan` int(11) NOT NULL DEFAULT 1,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sambutan_kepala_sekolah`
--

CREATE TABLE `sambutan_kepala_sekolah` (
  `id` int(11) NOT NULL,
  `nama_kepala_sekolah` varchar(255) NOT NULL,
  `jabatan` varchar(150) NOT NULL DEFAULT 'Kepala Sekolah',
  `foto` varchar(255) DEFAULT NULL,
  `judul` varchar(255) NOT NULL,
  `isi_sambutan` text NOT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1,
  `tanggal_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sambutan_kepala_sekolah`
--

INSERT INTO `sambutan_kepala_sekolah` (`id`, `nama_kepala_sekolah`, `jabatan`, `foto`, `judul`, `isi_sambutan`, `status_aktif`, `tanggal_update`) VALUES
(1, 'Mutia, M.Pd', 'Kepala Sekolah SMAN 21 Jakarta', 'kepsek_1791167731.png', 'Sambutan Kepala SMAN 21 Jakarta', '<p><span style=\"text-decoration: underline;\"><strong><em>HALO DUST!</em></strong></span><br><br>SELAMAT DATANG DI WEB SMAN 21 JAKARTA, HARI YANG BERBAHAGIA INI KAMI MERESMI KAN WEBSITE SMAN 21 JAKARTA YANG DI BANGUN OLEH TIM TechDust 2026 !<br><br><a title=\"WEB RESMI KAMI\" href=\"../../\">https://sman21jakarta.byethost3.com</a></p>', 1, '2026-10-05 02:35:32');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(10) UNSIGNED NOT NULL,
  `subject` varchar(100) NOT NULL,
  `teacher_name` varchar(160) NOT NULL,
  `subject_order` int(11) NOT NULL DEFAULT 999
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `subject`, `teacher_name`, `subject_order`) VALUES
(1, 'PENDIDIKAN AGAMA', 'Jumardika, S.Pd', 1),
(2, 'PENDIDIKAN AGAMA', 'Suherman, M.Pd', 1),
(3, 'PENDIDIKAN AGAMA', 'Rosmawati, M.Pd', 1),
(4, 'PENDIDIKAN AGAMA', 'Sr. M. Priscila', 1),
(5, 'SOSIOLOGI', 'Sisri Priantini, S.Pd', 2),
(6, 'SOSIOLOGI', 'Winda Mishbah, S.Pd', 2),
(7, 'SOSIOLOGI', 'Poppy Eka D., S.E', 2),
(8, 'BAHASA INDONESIA', 'Mutmainah, M.Pd', 3),
(9, 'BAHASA INDONESIA', 'Suyamti, M.Pd', 3),
(10, 'PENDIDIKAN PANCASILA', 'Sri Retnowati, M.Pd', 4),
(11, 'PENDIDIKAN PANCASILA', 'Nur Putri Halida, S.Pd', 4),
(12, 'PENJASKES', 'Mahatir Muhammad, S.Pd', 5),
(13, 'PENJASKES', 'Imam Hanafi, S.Pd', 5),
(14, 'EKONOMI', 'Tri Yatmi, S.Pd', 6),
(15, 'EKONOMI', 'Poppy Eka D., S.E', 6),
(16, 'EKONOMI', 'Agus Purnomo, S.Pd', 6),
(17, 'EKONOMI', 'Rini Yulianti, S.Pd', 6),
(18, 'FISIKA', 'Jundan Iskandar, M.Pd', 7),
(19, 'FISIKA', 'Shahnaz Nadia, M.Pd', 7),
(20, 'GEOGRAFI', 'Musngidah, S.Pd', 8),
(21, 'GEOGRAFI', 'Sepriyanta Simanjuntak, S.E', 8),
(22, 'BIOLOGI', 'M. Irfan Septiano, S.Pd', 9),
(23, 'BIOLOGI', 'Sofia Cesara, S.Pd', 9),
(24, 'BIOLOGI', 'M. Dicky Alphian, S.Pd', 9),
(25, 'BAHASA INGGRIS', 'Th. Prima Anggarini, S.Pd', 10),
(26, 'BAHASA INGGRIS', 'Selvia Gumay, M.Pd', 10),
(27, 'BIMBINGAN & KONSELING', 'Kamil Ahmad, S.Pd', 11),
(28, 'BIMBINGAN & KONSELING', 'Yuyun Wahyuni, S.Pd', 11),
(29, 'SEJARAH', 'Ifna Yeni, S.Pd', 12),
(30, 'SEJARAH', 'Esty Wulandini, S.Pd', 12),
(31, 'SEJARAH', 'Nur Putri Halida, S.Pd', 12),
(32, 'SEJARAH LANJUT', 'Esty Wulandini, S.Pd', 13),
(33, 'PRAKARYA', 'Asep Supriadi, S.Pd', 14),
(34, 'PRAKARYA', 'Sofia Cesara, S.Pd', 14),
(35, 'PRAKARYA', 'Poppy Eka D., S.E', 14),
(36, 'PRAKARYA', 'Rini Yulianti, S.Pd', 14),
(37, 'KIMIA', 'Asep Supriadi, M.Pd', 15),
(38, 'KIMIA', 'Ika Rakhmawatie S.Pd', 15),
(39, 'KIMIA', 'Christina Purba, S.Pd', 15),
(40, 'KODING & KA', 'Sutarmin, S.T', 16),
(41, 'MATEMATIKA', 'Drs. Saksi Ginting', 17),
(42, 'MATEMATIKA', 'Nani Kusmiyati, M.Pd', 17),
(43, 'MATEMATIKA', 'Alfredo, S.P', 17),
(44, 'MATEMATIKA', 'Dian Fitriyani, S.Si, M.Pd', 17),
(45, 'SENI BUDAYA', 'Dra. Meilina Nainggolan', 18),
(46, 'SENI BUDAYA', 'Winda Mishbah, S.Pd', 18),
(47, 'MATEMATIKA LANJUT', 'Alfredo, S.P', 19),
(48, 'MATEMATIKA LANJUT', 'Dian Fitriyani, S.Si, M.Pd', 19);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fasilitas`
--
ALTER TABLE `fasilitas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `galeri`
--
ALTER TABLE `galeri`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mapel_id` (`mapel_id`);

--
-- Indexes for table `hero_banners`
--
ALTER TABLE `hero_banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `keterserapan_ptn`
--
ALTER TABLE `keterserapan_ptn`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mapel`
--
ALTER TABLE `mapel`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pemeringkatan`
--
ALTER TABLE `pemeringkatan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengaturan_visimisi`
--
ALTER TABLE `pengaturan_visimisi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subject` (`subject`),
  ADD KEY `idx_order` (`subject_order`),
  ADD KEY `idx_teacher_name` (`teacher_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fasilitas`
--
ALTER TABLE `fasilitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `galeri`
--
ALTER TABLE `galeri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `guru`
--
ALTER TABLE `guru`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `hero_banners`
--
ALTER TABLE `hero_banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `keterserapan_ptn`
--
ALTER TABLE `keterserapan_ptn`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `mapel`
--
ALTER TABLE `mapel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `pemeringkatan`
--
ALTER TABLE `pemeringkatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pengaturan_visimisi`
--
ALTER TABLE `pengaturan_visimisi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

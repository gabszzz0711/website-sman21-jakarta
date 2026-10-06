<?php
// admin/akademik/pemeringkatan.php

// Tampilkan error PHP untuk mempermudah debugging saat pengembangan
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Cek autentikasi login
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['username'])) {
    // header('Location: ../login.php');
    // exit;
}

$admin_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'admin21';

// -----------------------------------------------------------------------------
// KONFIGURASI DATABASE BYETHOST
// -----------------------------------------------------------------------------
$host = 'sql305.byethost3.com'; // Ganti dengan Database Hostname cPanel/VistaPanel
$user = 'b3_43046986';     // Ganti dengan Username Database
$pass = 'J@karta5';      // Ganti dengan Password Database
$db   = 'b3_43046986_sman21jkt'; // Nama Database

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect($host, $user, $pass, $db);

// Notifikasi pesan
$message = '';
$message_type = '';

// -----------------------------------------------------------------------------
// PROSES CRUD (CREATE, UPDATE, DELETE)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // TAMBAH DATA
    if ($action === 'create') {
        $nama_jurusan   = trim($_POST['nama_jurusan']);
        $daftar_mapel   = trim($_POST['daftar_mapel']);
        $nilai_tertinggi = floatval($_POST['nilai_tertinggi']);
        $nilai_terendah  = floatval($_POST['nilai_terendah']);

        if (!empty($nama_jurusan) && !empty($daftar_mapel)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO `pemeringkatan` (`nama_jurusan`, `daftar_mapel`, `nilai_tertinggi`, `nilai_terendah`) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssdd", $nama_jurusan, $daftar_mapel, $nilai_tertinggi, $nilai_terendah);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['flash_msg'] = "Data pemeringkatan <strong>" . htmlspecialchars($nama_jurusan) . "</strong> berhasil ditambahkan!";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_msg'] = "Gagal menambahkan data: " . mysqli_error($conn);
                $_SESSION['flash_type'] = "danger";
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['flash_msg'] = "Mohon lengkapi seluruh kolom isian!";
            $_SESSION['flash_type'] = "warning";
        }
        header("Location: pemeringkatan.php");
        exit;
    }

    // EDIT DATA
    if ($action === 'update') {
        $id              = intval($_POST['id']);
        $nama_jurusan    = trim($_POST['nama_jurusan']);
        $daftar_mapel    = trim($_POST['daftar_mapel']);
        $nilai_tertinggi = floatval($_POST['nilai_tertinggi']);
        $nilai_terendah  = floatval($_POST['nilai_terendah']);

        if ($id > 0 && !empty($nama_jurusan) && !empty($daftar_mapel)) {
            $stmt = mysqli_prepare($conn, "UPDATE `pemeringkatan` SET `nama_jurusan` = ?, `daftar_mapel` = ?, `nilai_tertinggi` = ?, `nilai_terendah` = ? WHERE `id` = ?");
            mysqli_stmt_bind_param($stmt, "ssddi", $nama_jurusan, $daftar_mapel, $nilai_tertinggi, $nilai_terendah, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['flash_msg'] = "Data pemeringkatan berhasil diperbarui!";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_msg'] = "Gagal memperbarui data: " . mysqli_error($conn);
                $_SESSION['flash_type'] = "danger";
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['flash_msg'] = "Data tidak valid!";
            $_SESSION['flash_type'] = "warning";
        }
        header("Location: pemeringkatan.php");
        exit;
    }

    // HAPUS DATA
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        if ($id > 0) {
            $stmt = mysqli_prepare($conn, "DELETE FROM `pemeringkatan` WHERE `id` = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['flash_msg'] = "Data pemeringkatan berhasil dihapus!";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_msg'] = "Gagal menghapus data: " . mysqli_error($conn);
                $_SESSION['flash_type'] = "danger";
            }
            mysqli_stmt_close($stmt);
        }
        header("Location: pemeringkatan.php");
        exit;
    }
}

// Ambil Pesan Flash
if (isset($_SESSION['flash_msg'])) {
    $message = $_SESSION['flash_msg'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

// -----------------------------------------------------------------------------
// AMBIL DATA PEMERINGKATAN DARI DATABASE
// -----------------------------------------------------------------------------
$pemeringkatan_list = [];
$avg_tertinggi = 0;
$avg_terendah = 0;
$max_nilai = 0;
$min_nilai = 100;

if ($conn) {
    $result = @mysqli_query($conn, "SELECT * FROM `pemeringkatan` ORDER BY `id` ASC");
    if ($result) {
        $sum_high = 0;
        $sum_low = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $pemeringkatan_list[] = $row;
            $sum_high += floatval($row['nilai_tertinggi']);
            $sum_low += floatval($row['nilai_terendah']);
            if (floatval($row['nilai_tertinggi']) > $max_nilai) $max_nilai = floatval($row['nilai_tertinggi']);
            if (floatval($row['nilai_terendah']) < $min_nilai) $min_nilai = floatval($row['nilai_terendah']);
        }
        $total_item = count($pemeringkatan_list);
        if ($total_item > 0) {
            $avg_tertinggi = number_format($sum_high / $total_item, 2);
            $avg_terendah = number_format($sum_low / $total_item, 2);
        } else {
            $min_nilai = 0;
        }
    }
}

$logo_url = "https://sman21jkt.sch.id/wp-content/uploads/2025/02/1000305388-removebg-preview_image_repair_1740014356410.jpg";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pemeringkatan Akademik - SMAN 21 Jakarta</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            500: '#1d4ed8',
                            600: '#1e40af',
                            700: '#1d3557',
                            800: '#0f172a',
                            900: '#090d16',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        .sidebar-scroll::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased flex h-screen overflow-hidden">

    <!-- SIDEBAR NAVIGASI -->
    <aside id="sidebar" class="w-64 bg-slate-900 text-slate-300 flex flex-col flex-shrink-0 transition-all duration-300 z-30">
        
        <!-- Header Branding Sidebar -->
        <div class="h-20 flex items-center gap-3 px-5 border-b border-slate-800/80 bg-slate-950/40">
            <img src="<?= $logo_url ?>" alt="Logo SMAN 21 Jakarta" class="w-11 h-11 object-contain drop-shadow-md">
            <div class="leading-tight">
                <h1 class="font-bold text-white text-base tracking-wide">SMAN 21 JAKARTA</h1>
                <p class="text-xs text-blue-400 font-medium tracking-wider uppercase">Admin Panel</p>
            </div>
        </div>

        <!-- Menu Navigasi Sidebar -->
        <div class="flex-1 overflow-y-auto px-4 py-5 sidebar-scroll space-y-6">
            
            <!-- Menu Utama -->
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 mb-2">Utama</p>
                <nav class="space-y-1">
                    <a href="../dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white text-sm font-medium transition-colors">
                        <i class="fa-solid fa-house w-5 text-center text-slate-400"></i>
                        <span>Dashboard</span>
                    </a>
                </nav>
            </div>

            <!-- Kelola Konten & Informasi -->
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 mb-2">Kelola Sekolah</p>
                <nav class="space-y-1">
                    
                    <!-- Profil Submenu Dropdown -->
                    <details class="group">
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-school w-5 text-center text-slate-400 group-hover:text-white"></i>
                                Profil
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="../profil/sejarah.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Sejarah Sekolah</a>
                            <a href="../profil/sambutan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Sambutan Kepsek</a>
                            <a href="../profil/visi-misi.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Visi & Misi</a>
                        </div>
                    </details>

                    <!-- Akademik Dropdown (Aktif) -->
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg bg-blue-600/10 text-blue-400 cursor-pointer text-sm font-semibold transition-colors list-none">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-graduation-cap w-5 text-center text-blue-400"></i>
                                Akademik
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-blue-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="program.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Program Pendidikan</a>
                            <a href="program-unggulan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Program Unggulan</a>
                            <a href="mata-pelajaran.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Mata Pelajaran</a>
                            <a href="guru.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Data Guru</a>
                            <a href="pemeringkatan.php" class="block py-1.5 px-2 rounded bg-blue-600 text-white font-medium shadow-sm">Pemeringkatan</a>
                            <a href="keterserapan-ptn.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Keterserapan PTN</a>
                        </div>
                    </details>

                    <a href="../prestasi/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white text-sm font-medium transition-colors">
                        <i class="fa-solid fa-trophy w-5 text-center text-slate-400"></i>
                        <span>Prestasi</span>
                    </a>

                    <!-- Kegiatan Dropdown -->
                    <details class="group">
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-masks-theater w-5 text-center text-slate-400 group-hover:text-white"></i>
                                Kegiatan
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="../kegiatan/index.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Daftar Kegiatan</a>
                            <a href="../kegiatan/ekstrakurikuler.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Ekstrakurikuler</a>
                        </div>
                    </details>

                    <a href="../fasilitas/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white text-sm font-medium transition-colors">
                        <i class="fa-solid fa-building-columns w-5 text-center text-slate-400"></i>
                        <span>Fasilitas</span>
                    </a>

                    <a href="../gallery/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white text-sm font-medium transition-colors">
                        <i class="fa-solid fa-images w-5 text-center text-slate-400"></i>
                        <span>Galeri Media</span>
                    </a>

                    <!-- Informasi Dropdown -->
                    <details class="group">
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-newspaper w-5 text-center text-slate-400 group-hover:text-white"></i>
                                Informasi
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="../informasi/berita.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Berita & Pengumuman</a>
                            <a href="../informasi/agenda.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Agenda Kalender</a>
                            <a href="../informasi/ppdb.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Informasi PPDB</a>
                        </div>
                    </details>

                    <a href="../alumni/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white text-sm font-medium transition-colors">
                        <i class="fa-solid fa-user-graduate w-5 text-center text-slate-400"></i>
                        <span>Data Alumni</span>
                    </a>
                </nav>
            </div>

            <!-- Module AI Assistant -->
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 mb-2">Smart System</p>
                <nav class="space-y-1">
                    <details class="group">
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3 text-emerald-400">
                                <i class="fa-solid fa-robot w-5 text-center"></i>
                                <span>AI Assistant</span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="../ai-assistant/knowledge.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Knowledge Base</a>
                            <a href="../ai-assistant/kategori.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Kategori AI</a>
                            <a href="../ai-assistant/pengaturan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Pengaturan AI</a>
                        </div>
                    </details>
                </nav>
            </div>

            <!-- Pengaturan Sistem -->
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 mb-2">Pengaturan</p>
                <nav class="space-y-1">
                    <details class="group">
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-gears w-5 text-center text-slate-400 group-hover:text-white"></i>
                                System Config
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="../pengaturan/sekolah.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Identitas Sekolah</a>
                            <a href="../pengaturan/kontak.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Kontak & Medsos</a>
                            <a href="../pengaturan/ai.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Model & API AI</a>
                        </div>
                    </details>
                </nav>
            </div>

        </div>

        <!-- Footer Sidebar Link Website & Logout -->
        <div class="p-4 border-t border-slate-800 bg-slate-950/60 space-y-2">
            <a href="../../index.php" target="_blank" class="flex items-center justify-center gap-2 w-full py-2 px-3 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium transition">
                <i class="fa-solid fa-globe text-blue-400"></i>
                <span>Lihat Website</span>
            </a>
            <a href="../logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')" class="flex items-center justify-center gap-2 w-full py-2 px-3 rounded-lg bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 border border-rose-800/40 text-xs font-medium transition">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Keluar (Logout)</span>
            </a>
        </div>
    </aside>

    <!-- AREA KONTEN UTAMA -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-100">

        <!-- TOP BAR / HEADER -->
        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-10 shadow-sm">
            <div class="flex items-center gap-4">
                <button id="toggleSidebar" class="p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <div>
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Pemeringkatan Akademik</h2>
                    <p class="text-xs text-slate-500">Kelola Kriteria Jurusan, Mata Pelajaran Fokus & Rentang Nilai SNBP/SNBT</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <!-- Live Clock Status -->
                <div class="hidden md:flex items-center gap-2 bg-slate-100 px-3 py-1.5 rounded-full text-xs text-slate-600 font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span id="liveClock"><?= date('D, d M Y') ?></span>
                </div>

                <!-- Admin Profile Dropdown -->
                <div class="flex items-center gap-3 pl-3 border-l border-slate-200">
                    <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold shadow-md ring-2 ring-blue-100">
                        <?= strtoupper(substr($admin_username, 0, 1)) ?>
                    </div>
                    <div class="hidden sm:block text-left">
                        <p class="text-sm font-semibold text-slate-800 leading-tight"><?= htmlspecialchars($admin_username) ?></p>
                        <p class="text-[11px] text-slate-500">Administrator Utama</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- KONTEN SCROLLABLE -->
        <main class="flex-1 overflow-y-auto p-6 space-y-6">

            <!-- NOTIFIKASI FLASH MESSAGE -->
            <?php if (!empty($message)): ?>
                <div class="p-4 rounded-xl flex items-center justify-between shadow-sm transition-all duration-300 <?= $message_type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($message_type === 'warning' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-rose-50 text-rose-800 border border-rose-200') ?>">
                    <div class="flex items-center gap-3 text-sm font-medium">
                        <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check text-emerald-600 text-lg' : ($message_type === 'warning' ? 'fa-triangle-exclamation text-amber-600 text-lg' : 'fa-circle-xmark text-rose-600 text-lg') ?>"></i>
                        <div><?= $message ?></div>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- STATISTIK RINGKASAN -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Jurusan / Rumpun</p>
                            <h4 class="text-3xl font-extrabold text-slate-800 mt-1"><?= count($pemeringkatan_list) ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-3">Rumpun pemeringkatan aktif</p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Rata-rata Nilai Tertinggi</p>
                            <h4 class="text-3xl font-extrabold text-emerald-600 mt-1"><?= $avg_tertinggi ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-arrow-trend-up"></i>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-3">Batas rata-rata nilai teratas</p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Rata-rata Nilai Terendah</p>
                            <h4 class="text-3xl font-extrabold text-amber-600 mt-1"><?= $avg_terendah ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-arrow-trend-down"></i>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-3">Batas rata-rata passing grade minimum</p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Nilai Puncak</p>
                            <h4 class="text-3xl font-extrabold text-indigo-600 mt-1"><?= number_format($max_nilai, 2) ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-award"></i>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-3">Skor tertinggi yang tercatat</p>
                </div>

            </div>

            <!-- MAIN DATA TABLE CONTAINER -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                
                <!-- Table Header Bar -->
                <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Daftar Kriteria Pemeringkatan</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Mata pelajaran yang dihitung dan standar nilai acuan pemeringkatan siswa</p>
                    </div>
                    <button onclick="openAddModal()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-md hover:shadow-blue-200 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-plus text-sm"></i> Tambah Pemeringkatan Baru
                    </button>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50/80 text-slate-500 uppercase text-[11px] font-bold tracking-wider border-b border-slate-200/80">
                                <th class="py-3.5 px-6 w-16 text-center">ID</th>
                                <th class="py-3.5 px-6">Nama Jurusan / Kelompok</th>
                                <th class="py-3.5 px-6">Daftar Mata Pelajaran Fokus</th>
                                <th class="py-3.5 px-6 text-center">Nilai Tertinggi</th>
                                <th class="py-3.5 px-6 text-center">Nilai Terendah</th>
                                <th class="py-3.5 px-6 text-center">Selisih</th>
                                <th class="py-3.5 px-6 text-center w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            <?php if (!empty($pemeringkatan_list)): ?>
                                <?php foreach ($pemeringkatan_list as $item): ?>
                                    <?php 
                                        $selisih = number_format(floatval($item['nilai_tertinggi']) - floatval($item['nilai_terendah']), 2);
                                    ?>
                                    <tr class="hover:bg-slate-50/60 transition-colors">
                                        <td class="py-4 px-6 text-center text-slate-400 font-semibold">#<?= $item['id'] ?></td>
                                        <td class="py-4 px-6">
                                            <span class="font-bold text-slate-800 block text-base"><?= htmlspecialchars($item['nama_jurusan']) ?></span>
                                            <span class="text-[11px] text-slate-400 font-normal">Kategori Pemeringkatan</span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex flex-wrap gap-1.5 max-w-md">
                                                <?php 
                                                    $mapel_arr = explode(',', $item['daftar_mapel']);
                                                    foreach($mapel_arr as $m):
                                                ?>
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-xs font-medium border border-slate-200">
                                                        <i class="fa-solid fa-book text-[10px] text-blue-500 mr-1.5"></i> <?= htmlspecialchars(trim($m)) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200">
                                                <i class="fa-solid fa-arrow-up mr-1 text-[10px]"></i> <?= number_format($item['nilai_tertinggi'], 2) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200">
                                                <i class="fa-solid fa-arrow-down mr-1 text-[10px]"></i> <?= number_format($item['nilai_terendah'], 2) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-center text-slate-500 text-xs font-medium">
                                            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 font-semibold">
                                                <?= $selisih ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($item)) ?>)" class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition" title="Edit Data">
                                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                                </button>
                                                <button onclick="openDeleteModal(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['nama_jurusan'])) ?>')" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition" title="Hapus Data">
                                                    <i class="fa-solid fa-trash text-xs"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400">
                                        <i class="fa-solid fa-folder-open text-4xl mb-3 block text-slate-300"></i>
                                        <p class="font-medium text-slate-500">Belum ada data pemeringkatan akademik.</p>
                                        <p class="text-xs text-slate-400 mt-1">Klik tombol 'Tambah Pemeringkatan Baru' untuk memulai.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer -->
                <div class="p-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Menampilkan <strong><?= count($pemeringkatan_list) ?></strong> kelompok pemeringkatan</span>
                    <span>SMAN 21 Jakarta &copy; <?= date('Y') ?></span>
                </div>

            </div>

        </main>
    </div>

    <!-- MODAL TAMBAH & EDIT DATA -->
    <div id="dataModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 transform scale-95 transition-transform duration-300 m-4">
            
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                        <i id="modalIcon" class="fa-solid fa-plus"></i>
                    </div>
                    <div>
                        <h3 id="modalTitle" class="text-base font-bold text-slate-800">Tambah Pemeringkatan</h3>
                        <p class="text-xs text-slate-500">Isi parameter kriteria pemeringkatan jurusan</p>
                    </div>
                </div>
                <button onclick="closeModal()" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="pemeringkatanForm" action="pemeringkatan.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="formId" value="">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Nama Jurusan / Rumpun <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_jurusan" id="nama_jurusan" required placeholder="Contoh: SAINTEK (Sains & Teknik)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm text-slate-800">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Daftar Mata Pelajaran Fokus <span class="text-rose-500">*</span></label>
                    <textarea name="daftar_mapel" id="daftar_mapel" rows="3" required placeholder="Contoh: Fisika, Kimia, Biologi, Matematika (pisahkan dengan koma)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm text-slate-800"></textarea>
                    <p class="text-[11px] text-slate-400 mt-1">Gunakan koma (,) sebagai pemisah nama mata pelajaran.</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Nilai Tertinggi <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="0" max="100" name="nilai_tertinggi" id="nilai_tertinggi" required placeholder="92.78" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm text-slate-800">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Nilai Terendah <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="0" max="100" name="nilai_terendah" id="nilai_terendah" required placeholder="80.25" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm text-slate-800">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-md transition flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Data
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS -->
    <div id="deleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 text-center m-4 transform scale-95 transition-transform duration-300">
            <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-2xl mx-auto mb-4 border border-rose-100">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-1">Konfirmasi Hapus</h3>
            <p class="text-xs text-slate-500 leading-relaxed mb-6">
                Apakah Anda yakin ingin menghapus data pemeringkatan <strong id="deleteTargetName" class="text-slate-800"></strong>? Tindakan ini tidak dapat dibatalkan.
            </p>
            <form action="pemeringkatan.php" method="POST" class="flex items-center justify-center gap-3">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteFormId" value="">
                <button type="button" onclick="closeDeleteModal()" class="w-1/2 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition">
                    Batal
                </button>
                <button type="submit" class="w-1/2 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-md transition">
                    Hapus
                </button>
            </form>
        </div>
    </div>

    <!-- SCRIPT JAVASCRIPT -->
    <script>
        // Toggle Sidebar Navigasi Responsive
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleSidebar');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('-ml-64');
        });

        // Live Clock
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').textContent = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Modal Handler
        const dataModal = document.getElementById('dataModal');
        const deleteModal = document.getElementById('deleteModal');

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Pemeringkatan Baru';
            document.getElementById('modalIcon').className = 'fa-solid fa-plus';
            document.getElementById('formAction').value = 'create';
            document.getElementById('formId').value = '';
            document.getElementById('nama_jurusan').value = '';
            document.getElementById('daftar_mapel').value = '';
            document.getElementById('nilai_tertinggi').value = '';
            document.getElementById('nilai_terendah').value = '';

            dataModal.classList.remove('hidden');
            setTimeout(() => {
                dataModal.classList.remove('opacity-0');
                dataModal.querySelector('div').classList.remove('scale-95');
            }, 10);
        }

        function openEditModal(item) {
            document.getElementById('modalTitle').textContent = 'Edit Data Pemeringkatan';
            document.getElementById('modalIcon').className = 'fa-solid fa-pen-to-square';
            document.getElementById('formAction').value = 'update';
            document.getElementById('formId').value = item.id;
            document.getElementById('nama_jurusan').value = item.nama_jurusan;
            document.getElementById('daftar_mapel').value = item.daftar_mapel;
            document.getElementById('nilai_tertinggi').value = item.nilai_tertinggi;
            document.getElementById('nilai_terendah').value = item.nilai_terendah;

            dataModal.classList.remove('hidden');
            setTimeout(() => {
                dataModal.classList.remove('opacity-0');
                dataModal.querySelector('div').classList.remove('scale-95');
            }, 10);
        }

        function closeModal() {
            dataModal.classList.add('opacity-0');
            dataModal.querySelector('div').classList.add('scale-95');
            setTimeout(() => {
                dataModal.classList.add('hidden');
            }, 300);
        }

        function openDeleteModal(id, name) {
            document.getElementById('deleteFormId').value = id;
            document.getElementById('deleteTargetName').textContent = name;

            deleteModal.classList.remove('hidden');
            setTimeout(() => {
                deleteModal.classList.remove('opacity-0');
                deleteModal.querySelector('div').classList.remove('scale-95');
            }, 10);
        }

        function closeDeleteModal() {
            deleteModal.classList.add('opacity-0');
            deleteModal.querySelector('div').classList.add('scale-95');
            setTimeout(() => {
                deleteModal.classList.add('hidden');
            }, 300);
        }
    </script>
</body>
</html>
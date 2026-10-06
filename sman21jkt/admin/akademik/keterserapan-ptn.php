<?php
// admin/akademik/keterserapan-ptn.php

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

$msg = '';
$msg_type = '';

// -----------------------------------------------------------------------------
// PROSES CRUD (TAMBAH, EDIT, HAPUS)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$conn) {
        $msg = "Koneksi database gagal! Silakan periksa pengaturan database Anda.";
        $msg_type = "danger";
    } else {
        $action = $_POST['action'];

        // Sanitasi & Parsing Input Number
        $tahun          = (int)($_POST['tahun'] ?? 0);
        $jumlah_lulusan = (int)($_POST['jumlah_lulusan'] ?? 0);
        $snbp           = (int)($_POST['snbp'] ?? 0);
        $snbt           = (int)($_POST['snbt'] ?? 0);
        $mandiri        = (int)($_POST['mandiri'] ?? 0);
        $ptln           = (int)($_POST['ptln'] ?? 0);
        $kedinasan      = (int)($_POST['kedinasan'] ?? 0);
        $pts            = (int)($_POST['pts'] ?? 0);
        $gap_year       = (int)($_POST['gap_year'] ?? 0);

        if ($action === 'add') {
            $query = "INSERT INTO `keterserapan_ptn` (`tahun`, `jumlah_lulusan`, `snbp`, `snbt`, `mandiri`, `ptln`, `kedinasan`, `pts`, `gap_year`) 
                      VALUES ($tahun, $jumlah_lulusan, $snbp, $snbt, $mandiri, $ptln, $kedinasan, $pts, $gap_year)";
            if (mysqli_query($conn, $query)) {
                $msg = "Data keterserapan PTN tahun $tahun berhasil ditambahkan!";
                $msg_type = "success";
            } else {
                $msg = "Gagal menambahkan data: " . mysqli_error($conn);
                $msg_type = "danger";
            }
        } elseif ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $query = "UPDATE `keterserapan_ptn` SET 
                            `tahun` = $tahun,
                            `jumlah_lulusan` = $jumlah_lulusan,
                            `snbp` = $snbp,
                            `snbt` = $snbt,
                            `mandiri` = $mandiri,
                            `ptln` = $ptln,
                            `kedinasan` = $kedinasan,
                            `pts` = $pts,
                            `gap_year` = $gap_year
                          WHERE `id` = $id";
                if (mysqli_query($conn, $query)) {
                    $msg = "Data keterserapan PTN tahun $tahun berhasil diperbarui!";
                    $msg_type = "success";
                } else {
                    $msg = "Gagal memperbarui data: " . mysqli_error($conn);
                    $msg_type = "danger";
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $query = "DELETE FROM `keterserapan_ptn` WHERE `id` = $id";
                if (mysqli_query($conn, $query)) {
                    $msg = "Data keterserapan PTN berhasil dihapus!";
                    $msg_type = "success";
                } else {
                    $msg = "Gagal menghapus data: " . mysqli_error($conn);
                    $msg_type = "danger";
                }
            }
        }
    }
}

// -----------------------------------------------------------------------------
// AMBIL DATA KETERSERAPAN PTN
// -----------------------------------------------------------------------------
$list_data = [];
$total_rekam = 0;
$total_lulusan = 0;
$total_ptn = 0;

if ($conn) {
    $result = @mysqli_query($conn, "SELECT * FROM `keterserapan_ptn` ORDER BY `tahun` DESC");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list_data[] = $row;
            $total_lulusan += (int)$row['jumlah_lulusan'];
            $total_ptn += ((int)$row['snbp'] + (int)$row['snbt'] + (int)$row['mandiri'] + (int)$row['ptln'] + (int)$row['kedinasan']);
        }
        $total_rekam = count($list_data);
    }
}

$logo_url = "https://sman21jkt.sch.id/wp-content/uploads/2025/02/1000305388-removebg-preview_image_repair_1740014356410.jpg";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keterserapan PTN - Admin SMAN 21 Jakarta</title>
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

                    <!-- Akademik Dropdown (Open State) -->
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3 text-white">
                                <i class="fa-solid fa-graduation-cap w-5 text-center text-blue-400"></i>
                                Akademik
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="program.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Program Pendidikan</a>
                            <a href="program-unggulan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Program Unggulan</a>
                            <a href="mata-pelajaran.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Mata Pelajaran</a>
                            <a href="guru.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Data Guru</a>
                            <a href="pemeringkatan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Pemeringkatan</a>
                            <a href="keterserapan-ptn.php" class="block py-1.5 px-2 rounded bg-blue-600 text-white font-medium shadow-sm">Keterserapan PTN</a>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Keterserapan PTN</h2>
                    <p class="text-xs text-slate-500">Kelola Rekam Jejak Kelulusan & Sebaran Masuk Perguruan Tinggi SMAN 21 Jakarta</p>
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

            <!-- NOTIFIKASI PESAN -->
            <?php if (!empty($msg)): ?>
                <div class="p-4 rounded-xl text-xs font-medium flex items-center justify-between shadow-sm <?= $msg_type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid <?= $msg_type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-rose-600' ?> text-base"></i>
                        <span><?= htmlspecialchars($msg) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- HEADER ACTIONS & CARDS RINGKASAN STATISTIK -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 w-full md:w-auto flex-1">
                    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                        <div class="w-11 h-11 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-lg flex-shrink-0">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Tahun Terdata</p>
                            <h4 class="text-xl font-bold text-slate-800"><?= $total_rekam ?> Angkatan</h4>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                        <div class="w-11 h-11 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg flex-shrink-0">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Lulusan</p>
                            <h4 class="text-xl font-bold text-slate-800"><?= number_format($total_lulusan) ?> Siswa</h4>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                        <div class="w-11 h-11 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-lg flex-shrink-0">
                            <i class="fa-solid fa-building-columns"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Serap PTN</p>
                            <h4 class="text-xl font-bold text-slate-800"><?= number_format($total_ptn) ?> Siswa</h4>
                        </div>
                    </div>
                </div>

                <button onclick="openModal('addModal')" class="px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-md transition flex items-center justify-center gap-2 whitespace-nowrap self-start md:self-auto">
                    <i class="fa-solid fa-plus text-sm"></i>
                    <span>Tambah Data Angkatan</span>
                </button>
            </div>

            <!-- TABEL DATA KETERSERAPAN PTN -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Daftar Statistik Keterserapan Perguruan Tinggi</h3>
                        <p class="text-xs text-slate-500">Rekapitulasi jalur penerimaan SNBP, SNBT, Mandiri, PTLN, Kedinasan, PTS, dan Gap Year</p>
                    </div>
                    <span class="px-3 py-1 bg-slate-200/60 text-slate-700 font-semibold text-xs rounded-full">
                        Total <?= count($list_data) ?> Record
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-100/80 text-slate-700 font-semibold uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4">Tahun</th>
                                <th class="py-3.5 px-4">Total Lulusan</th>
                                <th class="py-3.5 px-4 text-center bg-blue-50/50">SNBP</th>
                                <th class="py-3.5 px-4 text-center bg-blue-50/50">SNBT</th>
                                <th class="py-3.5 px-4 text-center bg-blue-50/50">Mandiri</th>
                                <th class="py-3.5 px-4 text-center bg-blue-50/50">PTLN</th>
                                <th class="py-3.5 px-4 text-center bg-blue-50/50">Kedinasan</th>
                                <th class="py-3.5 px-4 text-center">PTS</th>
                                <th class="py-3.5 px-4 text-center">Gap Year</th>
                                <th class="py-3.5 px-4 text-center font-bold text-emerald-700 bg-emerald-50/40">Total PTN</th>
                                <th class="py-3.5 px-4 text-center font-bold text-emerald-700 bg-emerald-50/40">% PTN</th>
                                <th class="py-3.5 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($list_data)): ?>
                                <?php foreach ($list_data as $row): 
                                    $ptn_sum = (int)$row['snbp'] + (int)$row['snbt'] + (int)$row['mandiri'] + (int)$row['ptln'] + (int)$row['kedinasan'];
                                    $lulusan = (int)$row['jumlah_lulusan'];
                                    $persen_ptn = ($lulusan > 0) ? round(($ptn_sum / $lulusan) * 100, 1) : 0;
                                ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-4 px-4 font-bold text-slate-900 text-sm">
                                            <?= htmlspecialchars($row['tahun']) ?>
                                        </td>
                                        <td class="py-4 px-4 font-semibold text-slate-800">
                                            <?= number_format($lulusan) ?> <span class="text-[10px] text-slate-400 font-normal">Siswa</span>
                                        </td>
                                        <td class="py-4 px-4 text-center font-medium bg-blue-50/20 text-slate-800"><?= (int)$row['snbp'] ?></td>
                                        <td class="py-4 px-4 text-center font-medium bg-blue-50/20 text-slate-800"><?= (int)$row['snbt'] ?></td>
                                        <td class="py-4 px-4 text-center font-medium bg-blue-50/20 text-slate-800"><?= (int)$row['mandiri'] ?></td>
                                        <td class="py-4 px-4 text-center font-medium bg-blue-50/20 text-slate-800"><?= (int)$row['ptln'] ?></td>
                                        <td class="py-4 px-4 text-center font-medium bg-blue-50/20 text-slate-800"><?= (int)$row['kedinasan'] ?></td>
                                        <td class="py-4 px-4 text-center font-medium text-slate-800"><?= (int)$row['pts'] ?></td>
                                        <td class="py-4 px-4 text-center font-medium text-slate-500"><?= (int)$row['gap_year'] ?></td>
                                        <td class="py-4 px-4 text-center font-bold text-emerald-800 bg-emerald-50/30">
                                            <?= number_format($ptn_sum) ?>
                                        </td>
                                        <td class="py-4 px-4 text-center font-bold bg-emerald-50/30">
                                            <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[11px]">
                                                <?= $persen_ptn ?>%
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button onclick="editData(<?= htmlspecialchars(json_encode($row)) ?>)" class="p-2 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 transition" title="Edit Data">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <button onclick="confirmDelete(<?= $row['id'] ?>, <?= $row['tahun'] ?>)" class="p-2 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 transition" title="Hapus Data">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="12" class="py-12 text-center text-slate-400 italic">
                                        <i class="fa-solid fa-folder-open text-3xl mb-2 block"></i>
                                        Belum ada data keterserapan PTN yang tersimpan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- MODAL TAMBAH DATA -->
    <div id="addModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden transform transition-all my-8">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-graduation-cap text-blue-400 text-lg"></i>
                    <h3 class="font-bold text-base">Tambah Data Keterserapan PTN</h3>
                </div>
                <button onclick="closeModal('addModal')" class="text-slate-400 hover:text-white transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="add">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun Angkatan <span class="text-rose-500">*</span></label>
                        <input type="number" name="tahun" required placeholder="Contoh: 2026" class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Lulusan <span class="text-rose-500">*</span></label>
                        <input type="number" name="jumlah_lulusan" required placeholder="Contoh: 250" class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <hr class="border-slate-100">

                <p class="text-[11px] font-bold text-blue-600 uppercase tracking-wider">Sebaran Jalur Masuk PTN / PTN Khusus</p>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">SNBP</label>
                        <input type="number" name="snbp" value="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">SNBT</label>
                        <input type="number" name="snbt" value="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Mandiri PTN</label>
                        <input type="number" name="mandiri" value="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">PTLN (Luar Negeri)</label>
                        <input type="number" name="ptln" value="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Kedinasan</label>
                        <input type="number" name="kedinasan" value="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <hr class="border-slate-100">

                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Lainnya (PTS & Gap Year)</p>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Perguruan Tinggi Swasta (PTS)</label>
                        <input type="number" name="pts" value="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Gap Year / Belum Kuliah</label>
                        <input type="number" name="gap_year" value="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal('addModal')" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 text-xs font-semibold hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-md transition">
                        Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT DATA -->
    <div id="editModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden transform transition-all my-8">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-pen-to-square text-amber-400 text-lg"></i>
                    <h3 class="font-bold text-base">Edit Data Keterserapan PTN</h3>
                </div>
                <button onclick="closeModal('editModal')" class="text-slate-400 hover:text-white transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun Angkatan <span class="text-rose-500">*</span></label>
                        <input type="number" name="tahun" id="edit_tahun" required class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Lulusan <span class="text-rose-500">*</span></label>
                        <input type="number" name="jumlah_lulusan" id="edit_jumlah_lulusan" required class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <hr class="border-slate-100">

                <p class="text-[11px] font-bold text-blue-600 uppercase tracking-wider">Sebaran Jalur Masuk PTN / PTN Khusus</p>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">SNBP</label>
                        <input type="number" name="snbp" id="edit_snbp" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">SNBT</label>
                        <input type="number" name="snbt" id="edit_snbt" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Mandiri PTN</label>
                        <input type="number" name="mandiri" id="edit_mandiri" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">PTLN (Luar Negeri)</label>
                        <input type="number" name="ptln" id="edit_ptln" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Kedinasan</label>
                        <input type="number" name="kedinasan" id="edit_kedinasan" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <hr class="border-slate-100">

                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Lainnya (PTS & Gap Year)</p>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Perguruan Tinggi Swasta (PTS)</label>
                        <input type="number" name="pts" id="edit_pts" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Gap Year / Belum Kuliah</label>
                        <input type="number" name="gap_year" id="edit_gap_year" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal('editModal')" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 text-xs font-semibold hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold shadow-md transition">
                        Perbarui Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- FORM TERSEMBUNYI UNTUK HAPUS DATA -->
    <form id="deleteForm" method="POST" action="" class="hidden">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <!-- SCRIPT JAVASCRIPT -->
    <script>
        // Sidebar Toggle
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('-ml-64');
        });

        // Live Clock
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').textContent = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateClock, 1000);

        // Modal Handlers
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        // Edit Data Filler
        function editData(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_tahun').value = data.tahun;
            document.getElementById('edit_jumlah_lulusan').value = data.jumlah_lulusan;
            document.getElementById('edit_snbp').value = data.snbp;
            document.getElementById('edit_snbt').value = data.snbt;
            document.getElementById('edit_mandiri').value = data.mandiri;
            document.getElementById('edit_ptln').value = data.ptln;
            document.getElementById('edit_kedinasan').value = data.kedinasan;
            document.getElementById('edit_pts').value = data.pts;
            document.getElementById('edit_gap_year').value = data.gap_year;
            
            openModal('editModal');
        }

        // Confirm Delete Handler
        function confirmDelete(id, tahun) {
            if (confirm(`Apakah Anda yakin ingin menghapus data keterserapan PTN angkatan tahun ${tahun}?`)) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
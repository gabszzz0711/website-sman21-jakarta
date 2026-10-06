<?php
// admin/dashboard.php

// Tampilkan error PHP untuk mempermudah debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Cek autentikasi login
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['username'])) {
    // Jika belum login, redirect ke halaman login
    // header('Location: login.php');
    // exit;
}

$admin_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'admin21';

// -----------------------------------------------------------------------------
// KONFIGURASI DATABASE BYETHOST
// Silakan cek nilai $host, $user, dan $pass di cPanel/VistaPanel > MySQL Databases
// -----------------------------------------------------------------------------
$host = 'sqlXXX.byethost3.com'; // Ganti dengan Database Hostname dari cPanel Byethost
$user = 'b3_43046986_xxxx';     // Ganti dengan Username Database Byethost
$pass = 'PASSWORD_DB_ANDA';      // Ganti dengan Password Database Byethost
$db   = 'b3_43046986_sman21jkt'; // Nama Database Anda

// Nonaktifkan strict mode exception agar koneksi gagal tidak langsung menjadi HTTP 500
mysqli_report(MYSQLI_REPORT_OFF);

// Koneksi ke database secara aman
$conn = @mysqli_connect($host, $user, $pass, $db);

// Fungsi pembantu untuk mendapatkan total baris tabel secara aman
function get_count($conn, $table) {
    if (!$conn) return 0;
    $query = "SELECT COUNT(*) as total FROM `$table`";
    $result = @mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    return 0;
}

// Mengambil data statistik dari database
$total_berita      = get_count($conn, 'berita');
$total_prestasi    = get_count($conn, 'prestasi');
$total_agenda      = get_count($conn, 'agenda');
$total_fasilitas   = get_count($conn, 'fasilitas');
$total_kegiatan    = get_count($conn, 'kegiatan');
$total_alumni      = get_count($conn, 'alumni');
$total_ai_kb       = get_count($conn, 'ai_knowledge');
$total_guru        = get_count($conn, 'guru');

// Mengambil status AI Assistant
$ai_status = 1;
$ai_model  = 'gpt-4o-mini';
if ($conn) {
    $ai_query = @mysqli_query($conn, "SELECT status_ai, model_ai FROM ai_pengaturan LIMIT 1");
    if ($ai_query && mysqli_num_rows($ai_query) > 0) {
        $ai_data = mysqli_fetch_assoc($ai_query);
        $ai_status = $ai_data['status_ai'];
        $ai_model  = $ai_data['model_ai'];
    }
}

// Mengambil 5 berita terbaru
$recent_berita = [];
if ($conn) {
    $q_berita = @mysqli_query($conn, "SELECT judul, tanggal_publikasi, status FROM berita ORDER BY id DESC LIMIT 5");
    if ($q_berita) {
        while ($r = mysqli_fetch_assoc($q_berita)) {
            $recent_berita[] = $r;
        }
    }
}

// Mengambil 5 agenda mendatang/terbaru
$recent_agenda = [];
if ($conn) {
    $q_agenda = @mysqli_query($conn, "SELECT judul_agenda, tanggal_mulai, lokasi, status FROM agenda ORDER BY id DESC LIMIT 5");
    if ($q_agenda) {
        while ($r = mysqli_fetch_assoc($q_agenda)) {
            $recent_agenda[] = $r;
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
    <title>Dashboard Admin - SMAN 21 Jakarta</title>
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
                    <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-blue-600 text-white font-medium text-sm transition-all shadow-sm">
                        <i class="fa-solid fa-house w-5 text-center text-base"></i>
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
                            <a href="profil/sejarah.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Sejarah Sekolah</a>
                            <a href="profil/sambutan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Sambutan Kepsek</a>
                            <a href="profil/visi-misi.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Visi & Misi</a>
                        </div>
                    </details>

                    <!-- Akademik Dropdown -->
                    <details class="group">
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-graduation-cap w-5 text-center text-slate-400 group-hover:text-white"></i>
                                Akademik
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="akademik/program.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Program Pendidikan</a>
                            <a href="akademik/program-unggulan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Program Unggulan</a>
                            <a href="akademik/mata-pelajaran.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Mata Pelajaran</a>
                            <a href="akademik/guru.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Data Guru</a>
                            <a href="akademik/pemeringkatan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Pemeringkatan</a>
                            <a href="akademik/keterserapan-ptn.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Keterserapan PTN</a>
                        </div>
                    </details>

                    <a href="prestasi/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white text-sm font-medium transition-colors">
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
                            <a href="kegiatan/index.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Daftar Kegiatan</a>
                            <a href="kegiatan/ekstrakurikuler.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Ekstrakurikuler</a>
                        </div>
                    </details>

                    <a href="fasilitas/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white text-sm font-medium transition-colors">
                        <i class="fa-solid fa-building-columns w-5 text-center text-slate-400"></i>
                        <span>Fasilitas</span>
                    </a>

                    <a href="gallery/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white text-sm font-medium transition-colors">
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
                            <a href="informasi/berita.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Berita & Pengumuman</a>
                            <a href="informasi/agenda.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Agenda Kalender</a>
                            <a href="informasi/ppdb.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Informasi PPDB</a>
                        </div>
                    </details>

                    <a href="alumni/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white text-sm font-medium transition-colors">
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
                            <a href="ai-assistant/knowledge.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Knowledge Base</a>
                            <a href="ai-assistant/kategori.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Kategori AI</a>
                            <a href="ai-assistant/pengaturan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Pengaturan AI</a>
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
                            <a href="pengaturan/sekolah.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Identitas Sekolah</a>
                            <a href="pengaturan/kontak.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Kontak & Medsos</a>
                            <a href="pengaturan/ai.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Model & API AI</a>
                        </div>
                    </details>
                </nav>
            </div>

        </div>

        <!-- Footer Sidebar Link Website & Logout -->
        <div class="p-4 border-t border-slate-800 bg-slate-950/60 space-y-2">
            <a href="../index.php" target="_blank" class="flex items-center justify-center gap-2 w-full py-2 px-3 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium transition">
                <i class="fa-solid fa-globe text-blue-400"></i>
                <span>Lihat Website</span>
            </a>
            <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')" class="flex items-center justify-center gap-2 w-full py-2 px-3 rounded-lg bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 border border-rose-800/40 text-xs font-medium transition">
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Dashboard Ringkasan</h2>
                    <p class="text-xs text-slate-500">Panel Kontrol Manajemen Konten Portal SMAN 21 Jakarta</p>
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

            <!-- BANNER WELCOME CARD -->
            <div class="bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden border border-slate-800">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-2 max-w-2xl">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-300 text-xs font-semibold border border-blue-400/30">
                            <i class="fa-solid fa-shield-halved"></i> Executive Admin Control
                        </div>
                        <h3 class="text-2xl font-bold text-white tracking-tight">Selamat Datang Kembali, <span class="text-blue-400"><?= htmlspecialchars($admin_username) ?></span>!</h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Kelola seluruh informasi portal sekolah, data akademik, kegiatan siswa, hingga pangkalan pengetahuan AI Assistant SMAN 21 Jakarta dalam satu panel terintegrasi.
                        </p>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <a href="informasi/berita.php" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-xl transition shadow-lg flex items-center gap-2">
                            <i class="fa-solid fa-plus"></i> Tambah Berita
                        </a>
                        <a href="ai-assistant/knowledge.php" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700 transition flex items-center gap-2">
                            <i class="fa-solid fa-robot text-emerald-400"></i> Update AI KB
                        </a>
                    </div>
                </div>
                <!-- Background Graphic Element -->
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>
            </div>

            <!-- GRID CARDS STATISTIK UTAMA -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                
                <!-- Stat Card 1: Berita -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Artikel / Berita</p>
                            <h4 class="text-3xl font-extrabold text-slate-800 mt-1"><?= $total_berita ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-newspaper"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Total terpublikasi</span>
                        <a href="informasi/berita.php" class="text-blue-600 font-semibold hover:underline">Kelola <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
                    </div>
                </div>

                <!-- Stat Card 2: Prestasi -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Prestasi Siswa</p>
                            <h4 class="text-3xl font-extrabold text-slate-800 mt-1"><?= $total_prestasi ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-trophy"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Penghargaan dicatat</span>
                        <a href="prestasi/index.php" class="text-amber-600 font-semibold hover:underline">Kelola <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
                    </div>
                </div>

                <!-- Stat Card 3: Agenda -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Agenda Acara</p>
                            <h4 class="text-3xl font-extrabold text-slate-800 mt-1"><?= $total_agenda ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Jadwal kegiatan</span>
                        <a href="informasi/agenda.php" class="text-emerald-600 font-semibold hover:underline">Kelola <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
                    </div>
                </div>

                <!-- Stat Card 4: Fasilitas -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Fasilitas Sekolah</p>
                            <h4 class="text-3xl font-extrabold text-slate-800 mt-1"><?= $total_fasilitas ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-building-columns"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Sarana & Prasarana</span>
                        <a href="fasilitas/index.php" class="text-purple-600 font-semibold hover:underline">Kelola <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
                    </div>
                </div>

                <!-- Stat Card 5: Kegiatan -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Kegiatan Sekolah</p>
                            <h4 class="text-3xl font-extrabold text-slate-800 mt-1"><?= $total_kegiatan ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-masks-theater"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Program & Event</span>
                        <a href="kegiatan/index.php" class="text-rose-600 font-semibold hover:underline">Kelola <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
                    </div>
                </div>

                <!-- Stat Card 6: Alumni -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Data Alumni</p>
                            <h4 class="text-3xl font-extrabold text-slate-800 mt-1"><?= $total_alumni ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Lulusan terdaftar</span>
                        <a href="alumni/index.php" class="text-indigo-600 font-semibold hover:underline">Kelola <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
                    </div>
                </div>

                <!-- Stat Card 7: AI Knowledge -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">AI Knowledge</p>
                            <h4 class="text-3xl font-extrabold text-slate-800 mt-1"><?= $total_ai_kb ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-brain"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Basis Pengetahuan AI</span>
                        <a href="ai-assistant/knowledge.php" class="text-teal-600 font-semibold hover:underline">Kelola <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
                    </div>
                </div>

                <!-- Stat Card 8: Tenaga Pendidik -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Guru & Pendidik</p>
                            <h4 class="text-3xl font-extrabold text-slate-800 mt-1"><?= $total_guru ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-chalkboard-user"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Pengajar SMAN 21</span>
                        <a href="akademik/guru.php" class="text-cyan-600 font-semibold hover:underline">Kelola <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
                    </div>
                </div>

            </div>

            <!-- DUA KOLOM: AKTIVITAS TERBARU & AI STATUS WIDGET -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- TABEL BERITA TERBARU (2 KOLOM) -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col">
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-base">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-slate-800">Berita & Publikasi Terbaru</h4>
                                <p class="text-xs text-slate-500">Daftar postingan artikel teranyar di sistem</p>
                            </div>
                        </div>
                        <a href="informasi/berita.php" class="text-xs font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition">
                            Lihat Semua
                        </a>
                    </div>

                    <div class="overflow-x-auto flex-1">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                                <tr>
                                    <th class="py-3 px-3">Judul Berita</th>
                                    <th class="py-3 px-3">Tanggal</th>
                                    <th class="py-3 px-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (!empty($recent_berita)): ?>
                                    <?php foreach ($recent_berita as $b): ?>
                                        <tr class="hover:bg-slate-50/80 transition">
                                            <td class="py-3 px-3 font-medium text-slate-800 max-w-xs truncate">
                                                <?= htmlspecialchars($b['judul']) ?>
                                            </td>
                                            <td class="py-3 px-3 text-slate-500 whitespace-nowrap">
                                                <?= $b['tanggal_publikasi'] ? date('d M Y', strtotime($b['tanggal_publikasi'])) : '-' ?>
                                            </td>
                                            <td class="py-3 px-3">
                                                <?php if ($b['status'] == 'published'): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                                        Published
                                                    </span>
                                                <?php elseif ($b['status'] == 'draft'): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">
                                                        Draft
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700">
                                                        Archived
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="py-6 text-center text-slate-400 italic">
                                            Belum ada berita yang dipublikasikan.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- AI ASSISTANT WIDGET & QUICK AGENDA (1 KOLOM) -->
                <div class="space-y-6">
                    
                    <!-- Widget AI Status -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-robot text-emerald-500 text-lg"></i>
                                <h4 class="text-sm font-bold text-slate-800">AI Assistant Status</h4>
                            </div>
                            <?php if ($ai_status == 1): ?>
                                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-medium">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Aktif
                                </span>
                            <?php else: ?>
                                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-xs font-medium">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                    Nonaktif
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100 text-xs space-y-2 mb-4">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Model AI:</span>
                                <span class="font-semibold text-slate-700"><?= htmlspecialchars($ai_model) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Total Knowledge:</span>
                                <span class="font-semibold text-slate-700"><?= $total_ai_kb ?> Entri</span>
                            </div>
                        </div>

                        <a href="ai-assistant/pengaturan.php" class="block text-center py-2 px-3 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium transition">
                            Konfigurasi Assistant AI
                        </a>
                    </div>

                    <!-- Widget Agenda Singkat -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-bold text-slate-800">Agenda Mendatang</h4>
                            <a href="informasi/agenda.php" class="text-xs text-blue-600 font-semibold hover:underline">Detail</a>
                        </div>
                        <div class="space-y-3">
                            <?php if (!empty($recent_agenda)): ?>
                                <?php foreach ($recent_agenda as $ag): ?>
                                    <div class="flex items-start gap-3 p-2.5 rounded-xl bg-slate-50 hover:bg-slate-100 transition text-xs">
                                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold flex-shrink-0">
                                            <i class="fa-solid fa-calendar-day"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-semibold text-slate-800 truncate"><?= htmlspecialchars($ag['judul_agenda']) ?></p>
                                            <p class="text-slate-500 text-[11px] mt-0.5">
                                                <i class="fa-regular fa-clock mr-1"></i><?= $ag['tanggal_mulai'] ? date('d M Y', strtotime($ag['tanggal_mulai'])) : '-' ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-xs text-slate-400 italic text-center py-3">Belum ada agenda terdaftar.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            </div>

        </main>
    </div>

    <!-- SCRIPT RESPONSIF SIDEBAR TOGGLE -->
    <script>
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-ml-64');
        });

        // Dynamic Live Clock
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').textContent = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateClock, 1000);
    </script>
</body>
</html>
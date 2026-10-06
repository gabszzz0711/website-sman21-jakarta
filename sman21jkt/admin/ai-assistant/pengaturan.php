<?php
// admin/ai-assistant/pengaturan.php

// Tampilkan error PHP untuk mempermudah debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Cek autentikasi login admin
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['username'])) {
    // header('Location: ../login.php');
    // exit;
}

$admin_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'admin21';

// -----------------------------------------------------------------------------
// KONFIGURASI DATABASE BYETHOST
// -----------------------------------------------------------------------------
$host = 'sql305.byethost3.com'; // Database Hostname
$user = 'b3_43046986';         // Username Database
$pass = 'J@karta5';             // Password Database
$db   = 'b3_43046986_sman21jkt';// Nama Database

// Nonaktifkan strict mode exception agar koneksi gagal tidak langsung crash
mysqli_report(MYSQLI_REPORT_OFF);

// Koneksi ke database
$conn = @mysqli_connect($host, $user, $pass, $db);

$success_msg = '';
$error_msg   = '';

if (!$conn) {
    $error_msg = "Gagal terhubung ke database: " . mysqli_connect_error();
} else {
    mysqli_set_charset($conn, "utf8mb4");

    // Memastikan baris konfigurasi default (ID = 1) ada di tabel ai_pengaturan
    $check_q = mysqli_query($conn, "SELECT * FROM `ai_pengaturan` WHERE `id` = 1 LIMIT 1");
    if ($check_q && mysqli_num_rows($check_q) == 0) {
        mysqli_query($conn, "INSERT INTO `ai_pengaturan` (`id`, `nama_sekolah`, `system_prompt`, `pesan_pembuka`, `pesan_tidak_tersedia`, `model_ai`, `temperature`, `max_token`, `status_ai`) VALUES (1, 'SMAN 21 Jakarta', 'Anda adalah asisten AI resmi SMAN 21 Jakarta. Jawablah pertanyaan pengguna dengan sopan, akurat, dan ramah berdasarkan data resmi sekolah.', 'Halo! Saya AI Assistant SMAN 21 Jakarta. Ada yang ingin kamu ketahui tentang sekolah kami?', 'Maaf, informasi tersebut belum tersedia dalam database resmi sekolah.', 'gpt-4o-mini', 0.70, 1000, 1)");
    }

    // PROSES PERUBAHAN PENGATURAN (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_pengaturan'])) {
        $nama_sekolah         = trim($_POST['nama_sekolah']);
        $system_prompt        = trim($_POST['system_prompt']);
        $pesan_pembuka        = trim($_POST['pesan_pembuka']);
        $pesan_tidak_tersedia = trim($_POST['pesan_tidak_tersedia']);
        $model_ai             = trim($_POST['model_ai']);
        $temperature          = floatval($_POST['temperature']);
        $max_token            = intval($_POST['max_token']);
        $status_ai            = isset($_POST['status_ai']) ? intval($_POST['status_ai']) : 0;

        $update_sql = "UPDATE `ai_pengaturan` SET 
                        `nama_sekolah` = ?, 
                        `system_prompt` = ?, 
                        `pesan_pembuka` = ?, 
                        `pesan_tidak_tersedia` = ?, 
                        `model_ai` = ?, 
                        `temperature` = ?, 
                        `max_token` = ?, 
                        `status_ai` = ? 
                       WHERE `id` = 1";

        $stmt = mysqli_prepare($conn, $update_sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssssdii", $nama_sekolah, $system_prompt, $pesan_pembuka, $pesan_tidak_tersedia, $model_ai, $temperature, $max_token, $status_ai);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Konfigurasi AI Assistant berhasil disimpan dan diperbarui!";
            } else {
                $error_msg = "Gagal memperbarui database: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_msg = "Gagal mempersiapkan query database: " . mysqli_error($conn);
        }
    }

    // Ambil data terbaru dari database
    $get_data = mysqli_query($conn, "SELECT * FROM `ai_pengaturan` WHERE `id` = 1 LIMIT 1");
    $ai = mysqli_fetch_assoc($get_data);
}

// Fallback jika terjadi kegagalan pembacaan data
if (!isset($ai) || !$ai) {
    $ai = [
        'nama_sekolah' => 'SMAN 21 Jakarta',
        'system_prompt' => 'Anda adalah asisten AI resmi SMAN 21 Jakarta. Jawablah pertanyaan pengguna dengan sopan, akurat, dan ramah berdasarkan data resmi sekolah.',
        'pesan_pembuka' => 'Halo! Saya AI Assistant SMAN 21 Jakarta. Ada yang ingin kamu ketahui tentang sekolah kami?',
        'pesan_tidak_tersedia' => 'Maaf, informasi tersebut belum tersedia dalam database resmi sekolah.',
        'model_ai' => 'gpt-4o-mini',
        'temperature' => 0.70,
        'max_token' => 1000,
        'status_ai' => 1
    ];
}

$logo_url = "https://sman21jkt.sch.id/wp-content/uploads/2025/02/1000305388-removebg-preview_image_repair_1740014356410.jpg";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan AI Assistant - SMAN 21 Jakarta</title>
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
                            <a href="../akademik/program.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Program Pendidikan</a>
                            <a href="../akademik/program-unggulan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Program Unggulan</a>
                            <a href="../akademik/mata-pelajaran.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Mata Pelajaran</a>
                            <a href="../akademik/guru.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Data Guru</a>
                            <a href="../akademik/pemeringkatan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Pemeringkatan</a>
                            <a href="../akademik/keterserapan-ptn.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Keterserapan PTN</a>
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

            <!-- Module AI Assistant (OPEN & ACTIVE) -->
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 mb-2">Smart System</p>
                <nav class="space-y-1">
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg bg-slate-800 text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3 text-emerald-400">
                                <i class="fa-solid fa-robot w-5 text-center"></i>
                                <span>AI Assistant</span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="knowledge.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800 text-slate-300">Knowledge Base</a>
                            <a href="kategori.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800 text-slate-300">Kategori AI</a>
                            <a href="pengaturan.php" class="block py-1.5 px-2 rounded bg-blue-600 text-white font-medium shadow-sm">Pengaturan AI</a>
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
                    <div class="flex items-center gap-2 text-xs text-slate-400 font-medium">
                        <a href="../dashboard.php" class="hover:text-blue-600">Dashboard</a>
                        <span>/</span>
                        <span>AI Assistant</span>
                        <span>/</span>
                        <span class="text-slate-700 font-semibold">Pengaturan AI</span>
                    </div>
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight mt-0.5">Pengaturan AI Assistant</h2>
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

            <!-- NOTIFIKASI SUKSES / ERROR -->
            <?php if (!empty($success_msg)): ?>
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                        <div>
                            <p class="font-semibold">Berhasil!</p>
                            <p class="text-xs text-emerald-700 mt-0.5"><?= htmlspecialchars($success_msg) ?></p>
                        </div>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 text-xs">
                        <i class="fa-solid fa-xmark text-base"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-exclamation text-rose-600 text-lg"></i>
                        <div>
                            <p class="font-semibold">Terjadi Kesalahan!</p>
                            <p class="text-xs text-rose-700 mt-0.5"><?= htmlspecialchars($error_msg) ?></p>
                        </div>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800 text-xs">
                        <i class="fa-solid fa-xmark text-base"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- HEADER BANNER FORM -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl flex-shrink-0 border border-emerald-100">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Konfigurasi & Perilaku Assistant AI</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Atur instruksi sistem, pesan otomatis, model AI, dan batasan respons chatbot sekolah.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="knowledge.php" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition flex items-center gap-2">
                        <i class="fa-solid fa-brain text-teal-600"></i> Kelola Knowledge Base
                    </a>
                </div>
            </div>

            <!-- FORM PENGATURAN AI -->
            <form action="" method="POST" class="space-y-6">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- KOLOM KIRI (2 KOLOM): UTAMA (System Prompt & Response) -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- CARD 1: IDENTITAS & SYSTEM PROMPT -->
                        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-5">
                            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">
                                    <i class="fa-solid fa-id-card"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800">Identitas & System Prompt</h4>
                                    <p class="text-[11px] text-slate-500">Instruksi mendasar tentang bagaimana AI berperan dan menjawab pertanyaan</p>
                                </div>
                            </div>

                            <!-- Input Nama Sekolah -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Nama Instansi / Sekolah <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="nama_sekolah" required value="<?= htmlspecialchars($ai['nama_sekolah']) ?>" 
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                                <p class="text-[11px] text-slate-400 mt-1">Nama ini digunakan sebagai rujukan identitas utama bot dalam percakapan.</p>
                            </div>

                            <!-- Input System Prompt -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    System Prompt (Instruksi Perilaku AI) <span class="text-rose-500">*</span>
                                </label>
                                <textarea name="system_prompt" rows="5" required 
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-800 leading-relaxed focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"><?= htmlspecialchars($ai['system_prompt']) ?></textarea>
                                <p class="text-[11px] text-slate-400 mt-1">
                                    <i class="fa-solid fa-lightbulb text-amber-500 mr-1"></i> Tentukan nada bicara (sopan, ramah), identitas, serta aturan batas pengetahuan AI.
                                </p>
                            </div>
                        </div>

                        <!-- CARD 2: PESAN OTOMATIS & FALLBACK -->
                        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-5">
                            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">
                                    <i class="fa-solid fa-comment-dots"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800">Pesan Otomatis & Template Fallback</h4>
                                    <p class="text-[11px] text-slate-500">Pesan sapaan awal dan balasan jika informasi belum tersedia di database</p>
                                </div>
                            </div>

                            <!-- Input Pesan Pembuka -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Pesan Pembuka (Greeting Message)
                                </label>
                                <textarea name="pesan_pembuka" rows="3" 
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"><?= htmlspecialchars($ai['pesan_pembuka']) ?></textarea>
                                <p class="text-[11px] text-slate-400 mt-1">Pesan awal yang pertama kali muncul saat pengunjung membuka widget chat.</p>
                            </div>

                            <!-- Input Pesan Tidak Tersedia -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Pesan Ketika Informasi Tidak Ditemukan (Fallback Response)
                                </label>
                                <textarea name="pesan_tidak_tersedia" rows="3" 
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"><?= htmlspecialchars($ai['pesan_tidak_tersedia']) ?></textarea>
                                <p class="text-[11px] text-slate-400 mt-1">Balasan default ketika AI tidak dapat menemukan kecocokan jawaban di Knowledge Base.</p>
                            </div>
                        </div>

                    </div>

                    <!-- KOLOM KANAN (1 KOLOM): ENGINE & PARAMETER SETTINGS -->
                    <div class="space-y-6">

                        <!-- CARD 3: STATUS & KONTROL ENGINE AI -->
                        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-5">
                            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                                <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-sm font-bold">
                                    <i class="fa-solid fa-microchip"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800">Status & Model AI</h4>
                                    <p class="text-[11px] text-slate-500">Konfigurasi teknis engine pemroses</p>
                                </div>
                            </div>

                            <!-- Status Switch AI -->
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/80">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-slate-800">Status Widget AI</p>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Aktifkan atau nonaktifkan fitur AI di website publik</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="status_ai" value="1" <?= $ai['status_ai'] == 1 ? 'checked' : '' ?> class="sr-only peer">
                                        <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Model AI Dropdown -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Model AI Engine
                                </label>
                                <div class="relative">
                                    <select name="model_ai" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 appearance-none focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                                        <option value="gpt-4o-mini" <?= $ai['model_ai'] == 'gpt-4o-mini' ? 'selected' : '' ?>>GPT-4o Mini (Rekomendasi - Cepat & Hemat)</option>
                                        <option value="gpt-4o" <?= $ai['model_ai'] == 'gpt-4o' ? 'selected' : '' ?>>GPT-4o (Akurasi Lebih Tinggi)</option>
                                        <option value="gemini-1.5-flash" <?= $ai['model_ai'] == 'gemini-1.5-flash' ? 'selected' : '' ?>>Google Gemini 1.5 Flash</option>
                                        <option value="gemini-1.5-pro" <?= $ai['model_ai'] == 'gemini-1.5-pro' ? 'selected' : '' ?>>Google Gemini 1.5 Pro</option>
                                        <option value="deepseek-chat" <?= $ai['model_ai'] == 'deepseek-chat' ? 'selected' : '' ?>>DeepSeek V3 / Chat</option>
                                    </select>
                                    <i class="fa-solid fa-chevron-down absolute right-3.5 top-3 text-xs text-slate-400 pointer-events-none"></i>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1">Pilih model kecerdasan buatan yang akan digunakan untuk mengolah data.</p>
                            </div>

                            <!-- Temperature Slider -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="text-xs font-semibold text-slate-700">
                                        Temperature (Kreativitas)
                                    </label>
                                    <span id="tempValue" class="text-xs font-bold text-teal-600 bg-teal-50 px-2 py-0.5 rounded-md border border-teal-100">
                                        <?= number_format($ai['temperature'], 2) ?>
                                    </span>
                                </div>
                                <input type="range" name="temperature" id="temperatureInput" min="0" max="1" step="0.05" value="<?= $ai['temperature'] ?>" 
                                    class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-teal-600">
                                <div class="flex justify-between text-[10px] text-slate-400 mt-1 font-medium">
                                    <span>0.0 (Presisi/Kaku)</span>
                                    <span>0.7 (Faktual Seimbang)</span>
                                    <span>1.0 (Kreatif)</span>
                                </div>
                            </div>

                            <!-- Max Tokens Input -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Max Tokens (Batas Panjang Jawaban)
                                </label>
                                <input type="number" name="max_token" min="100" max="4000" step="50" value="<?= $ai['max_token'] ?>" 
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                                <p class="text-[11px] text-slate-400 mt-1">1000 token ± 750 kata. Membatasi konsumsi token per respons.</p>
                            </div>

                            <!-- Info Terakhir Diperbarui -->
                            <div class="pt-3 border-t border-slate-100 text-[11px] text-slate-400 flex items-center justify-between">
                                <span>Terakhir diperbarui:</span>
                                <span class="font-medium text-slate-600">
                                    <?= isset($ai['tanggal_update']) ? date('d M Y, H:i', strtotime($ai['tanggal_update'])) : '-' ?> WIB
                                </span>
                            </div>
                        </div>

                        <!-- SUBMIT BUTTON CARD -->
                        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
                            <button type="submit" name="submit_pengaturan" 
                                class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-blue-500/20 transition-all flex items-center justify-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-sm"></i>
                                <span>Simpan Konfigurasi AI</span>
                            </button>
                        </div>

                    </div>

                </div>

            </form>

        </main>
    </div>

    <!-- SCRIPT RESPONSIF SIDEBAR & INTERAKSI FORM -->
    <script>
        // Responsive Sidebar Toggle
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

        // Update Live Value untuk Slider Temperature
        const tempInput = document.getElementById('temperatureInput');
        const tempValue = document.getElementById('tempValue');
        if (tempInput && tempValue) {
            tempInput.addEventListener('input', function() {
                tempValue.textContent = parseFloat(this.value).toFixed(2);
            });
        }
    </script>
</body>
</html>

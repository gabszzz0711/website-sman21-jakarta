<?php
// admin/pengaturan/ai.php

// Tampilkan error PHP untuk mempermudah debugging saat pengembangan
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
// KONFIGURASI DATABASE BYETHOST / LOCALHOST
// -----------------------------------------------------------------------------
$host = 'sql305.byethost3.com'; 
$user = 'b3_43046986';          
$pass = 'J@karta5';             
$db   = 'b3_43046986_sman21jkt'; 

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect($host, $user, $pass, $db);

// Notifikasi status aksi
$alert_message = '';
$alert_type = ''; // 'success' atau 'error'

// -----------------------------------------------------------------------------
// PROCESS FORM SUBMISSION (UPDATE PENGATURAN AI)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ai_settings'])) {
    if (!$conn) {
        $alert_message = "Koneksi database gagal! Harap periksa konfigurasi database Anda.";
        $alert_type = "error";
    } else {
        // Sanitasi input POST sesuai kolom tabel ai_pengaturan
        $nama_sekolah         = mysqli_real_escape_string($conn, trim($_POST['nama_sekolah'] ?? 'SMAN 21 Jakarta'));
        $status_ai            = isset($_POST['status_ai']) ? (int)$_POST['status_ai'] : 0;
        $model_ai             = mysqli_real_escape_string($conn, trim($_POST['model_ai'] ?? 'gpt-4o-mini'));
        $temperature          = (float)($_POST['temperature'] ?? 0.70);
        $max_token            = (int)($_POST['max_token'] ?? 1000);
        $pesan_pembuka        = mysqli_real_escape_string($conn, trim($_POST['pesan_pembuka'] ?? ''));
        $pesan_tidak_tersedia = mysqli_real_escape_string($conn, trim($_POST['pesan_tidak_tersedia'] ?? ''));
        $system_prompt        = mysqli_real_escape_string($conn, trim($_POST['system_prompt'] ?? ''));

        // Cek apakah data id 1 sudah ada
        $check_q = @mysqli_query($conn, "SELECT id FROM ai_pengaturan WHERE id = 1 LIMIT 1");
        
        if ($check_q && mysqli_num_rows($check_q) > 0) {
            // Update data id = 1
            $update_sql = "UPDATE ai_pengaturan SET 
                `nama_sekolah` = '$nama_sekolah',
                `status_ai` = $status_ai,
                `model_ai` = '$model_ai',
                `temperature` = $temperature,
                `max_token` = $max_token,
                `pesan_pembuka` = '$pesan_pembuka',
                `pesan_tidak_tersedia` = '$pesan_tidak_tersedia',
                `system_prompt` = '$system_prompt'
                WHERE `id` = 1";
            
            $exec = @mysqli_query($conn, $update_sql);
        } else {
            // Insert data baru jika belum ada
            $insert_sql = "INSERT INTO ai_pengaturan 
                (`id`, `nama_sekolah`, `system_prompt`, `pesan_pembuka`, `pesan_tidak_tersedia`, `model_ai`, `temperature`, `max_token`, `status_ai`) 
                VALUES 
                (1, '$nama_sekolah', '$system_prompt', '$pesan_pembuka', '$pesan_tidak_tersedia', '$model_ai', $temperature, $max_token, $status_ai)";
            
            $exec = @mysqli_query($conn, $insert_sql);
        }

        if ($exec) {
            $alert_message = "Pengaturan AI Assistant berhasil diperbarui dan disimpan ke database.";
            $alert_type = "success";
        } else {
            $alert_message = "Gagal menyimpan pengaturan: " . mysqli_error($conn);
            $alert_type = "error";
        }
    }
}

// -----------------------------------------------------------------------------
// AMBIL DATA PENGATURAN AI SAAT INI
// -----------------------------------------------------------------------------
$ai_config = [
    'nama_sekolah'         => 'SMAN 21 Jakarta',
    'status_ai'            => 1,
    'model_ai'             => 'gpt-4o-mini',
    'temperature'          => 0.70,
    'max_token'            => 1000,
    'pesan_pembuka'        => 'Halo! Saya AI Assistant SMAN 21 Jakarta. Ada yang ingin kamu ketahui tentang sekolah kami?',
    'pesan_tidak_tersedia' => 'Maaf, informasi tersebut belum tersedia dalam database resmi sekolah.',
    'system_prompt'        => 'Anda adalah asisten AI resmi SMAN 21 Jakarta. Jawablah pertanyaan pengguna dengan sopan, akurat, dan ramah berdasarkan data resmi sekolah.',
    'tanggal_update'       => null
];

if ($conn) {
    $get_q = @mysqli_query($conn, "SELECT * FROM ai_pengaturan WHERE id = 1 LIMIT 1");
    if ($get_q && mysqli_num_rows($get_q) > 0) {
        $data = mysqli_fetch_assoc($get_q);
        $ai_config['nama_sekolah']         = $data['nama_sekolah'];
        $ai_config['status_ai']            = $data['status_ai'];
        $ai_config['model_ai']             = $data['model_ai'];
        $ai_config['temperature']          = $data['temperature'];
        $ai_config['max_token']            = $data['max_token'];
        $ai_config['pesan_pembuka']        = $data['pesan_pembuka'];
        $ai_config['pesan_tidak_tersedia'] = $data['pesan_tidak_tersedia'];
        $ai_config['system_prompt']        = $data['system_prompt'];
        $ai_config['tanggal_update']       = $data['tanggal_update'];
    }
}

// Total Knowledge Base items untuk info card
$total_kb = 0;
if ($conn) {
    $kb_q = @mysqli_query($conn, "SELECT COUNT(*) as total FROM ai_knowledge");
    if ($kb_q) {
        $row_kb = mysqli_fetch_assoc($kb_q);
        $total_kb = $row_kb['total'];
    }
}

$logo_url = "https://sman21jkt.sch.id/wp-content/uploads/2025/02/1000305388-removebg-preview_image_repair_1740014356410.jpg";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Model & API AI - SMAN 21 Jakarta</title>
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

            <!-- Module AI Assistant -->
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 mb-2">Smart System</p>
                <nav class="space-y-1">
                    <details class="group" open>
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
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg bg-slate-800 text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-gears w-5 text-center text-blue-400"></i>
                                System Config
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="sekolah.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Identitas Sekolah</a>
                            <a href="kontak.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Kontak & Medsos</a>
                            <a href="ai.php" class="block py-1.5 px-2 rounded bg-blue-600 text-white font-medium shadow-sm">Model & API AI</a>
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
                        <a href="../dashboard.php" class="hover:text-blue-600 transition">Dashboard</a>
                        <span>/</span>
                        <span>Pengaturan</span>
                        <span>/</span>
                        <span class="text-slate-700 font-semibold">Model & API AI</span>
                    </div>
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight mt-0.5">Pengaturan Engine AI Assistant</h2>
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

            <!-- NOTIFIKASI ALERT (BERHASIL / GAGAL) -->
            <?php if (!empty($alert_message)): ?>
                <?php if ($alert_type === 'success'): ?>
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start gap-3 shadow-sm">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-lg mt-0.5"></i>
                        <div class="flex-1">
                            <h5 class="text-sm font-bold">Pembaruan Berhasil</h5>
                            <p class="text-xs text-emerald-700 mt-0.5"><?= htmlspecialchars($alert_message) ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start gap-3 shadow-sm">
                        <i class="fa-solid fa-circle-xmark text-rose-500 text-lg mt-0.5"></i>
                        <div class="flex-1">
                            <h5 class="text-sm font-bold">Terjadi Kesalahan</h5>
                            <p class="text-xs text-rose-700 mt-0.5"><?= htmlspecialchars($alert_message) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- HEADER BANNER / OVERVIEW CARD -->
            <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-blue-950 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden border border-slate-800">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-1.5 max-w-2xl">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-semibold border border-emerald-400/30">
                            <i class="fa-solid fa-microchip"></i> AI Infrastructure Config
                        </div>
                        <h3 class="text-2xl font-bold text-white tracking-tight">Konfigurasi Parameter Engine AI</h3>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Atur perilaku cerdas, pesan default, batasan token, serta system prompt untuk Asisten AI SMAN 21 Jakarta. Perubahan disimpan secara otomatis ke tabel <code class="bg-slate-800 px-1.5 py-0.5 rounded text-blue-300">ai_pengaturan</code>.
                        </p>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <div class="bg-white/10 backdrop-blur-md px-4 py-3 rounded-xl border border-white/10 text-center">
                            <p class="text-[10px] uppercase tracking-wider text-slate-300 font-medium">Knowledge Base</p>
                            <p class="text-xl font-extrabold text-emerald-400"><?= $total_kb ?> Entri</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-md px-4 py-3 rounded-xl border border-white/10 text-center">
                            <p class="text-[10px] uppercase tracking-wider text-slate-300 font-medium">Model Saat Ini</p>
                            <p class="text-sm font-bold text-blue-300"><?= htmlspecialchars($ai_config['model_ai']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FORM UTAMA & SIDEBAR INFO -->
            <form action="" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- FORM KONFIGURASI (2 KOLOM) -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- CARD 1: MODEL & METRIK SENSOR ENGINE -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-bold">
                                    <i class="fa-solid fa-sliders"></i>
                                </div>
                                <div>
                                    <h4 class="text-base font-bold text-slate-800">Status & Parameter Engine AI</h4>
                                    <p class="text-xs text-slate-500">Konfigurasi status aktif, jenis model LLM, temperatur, dan batas max tokens</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            
                            <!-- Status AI -->
                            <div class="sm:col-span-2 bg-slate-50 p-4 rounded-xl border border-slate-200/80 flex items-center justify-between">
                                <div>
                                    <label class="block text-sm font-bold text-slate-800">Status Layanan AI Assistant</label>
                                    <p class="text-xs text-slate-500 mt-0.5">Aktifkan atau nonaktifkan fitur bot AI pada portal publik sekolah</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="status_ai" value="1" <?= $ai_config['status_ai'] == 1 ? 'checked' : '' ?> class="sr-only peer">
                                        <div class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-300 peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:border-emerald-600 transition">
                                            <i class="fa-solid fa-circle-check mr-1"></i> Aktif
                                        </div>
                                    </label>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="status_ai" value="0" <?= $ai_config['status_ai'] == 0 ? 'checked' : '' ?> class="sr-only peer">
                                        <div class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-300 peer-checked:bg-rose-600 peer-checked:text-white peer-checked:border-rose-600 transition">
                                            <i class="fa-solid fa-circle-xmark mr-1"></i> Nonaktif
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Model AI -->
                            <div>
                                <label for="model_ai" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                                    Pilih Model AI / LLM <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <select id="model_ai" name="model_ai" class="w-full bg-slate-50 border border-slate-300 text-slate-800 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block p-3 pr-8 transition">
                                        <option value="gpt-4o-mini" <?= $ai_config['model_ai'] === 'gpt-4o-mini' ? 'selected' : '' ?>>GPT-4o Mini (Cepat & Efisien - Default)</option>
                                        <option value="gpt-4o" <?= $ai_config['model_ai'] === 'gpt-4o' ? 'selected' : '' ?>>GPT-4o (Akurasi Tinggi & Kompleks)</option>
                                        <option value="gemini-1.5-flash" <?= $ai_config['model_ai'] === 'gemini-1.5-flash' ? 'selected' : '' ?>>Gemini 1.5 Flash (Google AI Studio)</option>
                                        <option value="gemini-1.5-pro" <?= $ai_config['model_ai'] === 'gemini-1.5-pro' ? 'selected' : '' ?>>Gemini 1.5 Pro (Google AI Studio)</option>
                                        <option value="claude-3-5-sonnet" <?= $ai_config['model_ai'] === 'claude-3-5-sonnet' ? 'selected' : '' ?>>Claude 3.5 Sonnet (Anthropic)</option>
                                    </select>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1">Model menentukan kecerdasan, kecepatan, dan konsumsi kuota API.</p>
                            </div>

                            <!-- Temperature -->
                            <div>
                                <label for="temperature" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                                    Temperature Kreativitas (0.00 - 1.00)
                                </label>
                                <div class="flex items-center gap-3">
                                    <input type="number" id="temperature" name="temperature" step="0.05" min="0" max="1" value="<?= htmlspecialchars($ai_config['temperature']) ?>" class="w-full bg-slate-50 border border-slate-300 text-slate-800 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-3 transition">
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1">0.00 = Sangat Faktual & Konsisten | 0.70 = Standar Seimbang | 1.00 = Sangat Kreatif</p>
                            </div>

                            <!-- Max Tokens -->
                            <div class="sm:col-span-2">
                                <label for="max_token" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                                    Batas Maksimum Token Output (Max Tokens)
                                </label>
                                <input type="number" id="max_token" name="max_token" min="100" max="4000" step="50" value="<?= htmlspecialchars($ai_config['max_token']) ?>" class="w-full bg-slate-50 border border-slate-300 text-slate-800 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-3 transition">
                                <p class="text-[11px] text-slate-500 mt-1">Membatasi panjang respon maksimal yang dihasilkan AI (contoh: 1000 tokens ≈ ±750 kata).</p>
                            </div>

                        </div>
                    </div>

                    <!-- CARD 2: IDENTITAS & TEKS PESAN DEFAULT -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-5">
                        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-bold">
                                <i class="fa-solid fa-comments"></i>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-slate-800">Identitas & Pesan Otomatis</h4>
                                <p class="text-xs text-slate-500">Atur pesan pembuka awal obrolan dan balasan jika data tidak ditemukan</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <!-- Nama Sekolah / AI -->
                            <div>
                                <label for="nama_sekolah" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                                    Nama Entitas Sekolah / Asisten
                                </label>
                                <input type="text" id="nama_sekolah" name="nama_sekolah" value="<?= htmlspecialchars($ai_config['nama_sekolah']) ?>" required class="w-full bg-slate-50 border border-slate-300 text-slate-800 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-3 transition">
                            </div>

                            <!-- Pesan Pembuka -->
                            <div>
                                <label for="pesan_pembuka" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                                    Pesan Pembuka Sapaan (Greeting Message)
                                </label>
                                <textarea id="pesan_pembuka" name="pesan_pembuka" rows="2" class="w-full bg-slate-50 border border-slate-300 text-slate-800 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-3 transition"><?= htmlspecialchars($ai_config['pesan_pembuka']) ?></textarea>
                                <p class="text-[11px] text-slate-500 mt-1">Pesan awal yang akan langsung tampil saat pengunjung membuka widget obrolan AI.</p>
                            </div>

                            <!-- Pesan Tidak Tersedia -->
                            <div>
                                <label for="pesan_tidak_tersedia" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                                    Pesan Ketiadaan Data (Fallback Answer)
                                </label>
                                <textarea id="pesan_tidak_tersedia" name="pesan_tidak_tersedia" rows="2" class="w-full bg-slate-50 border border-slate-300 text-slate-800 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-3 transition"><?= htmlspecialchars($ai_config['pesan_tidak_tersedia']) ?></textarea>
                                <p class="text-[11px] text-slate-500 mt-1">Pesan ramah yang dikirimkan AI jika pertanyaan tidak dapat dijawab oleh Knowledge Base.</p>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 3: SYSTEM PROMPT AI -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg font-bold">
                                    <i class="fa-solid fa-terminal"></i>
                                </div>
                                <div>
                                    <h4 class="text-base font-bold text-slate-800">Instruksi Utama (System Prompt)</h4>
                                    <p class="text-xs text-slate-500">Mengarahkan peran, nada bicara, batasan, dan kepribadian utama AI Assistant</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <textarea id="system_prompt" name="system_prompt" rows="5" class="w-full bg-slate-900 font-mono text-emerald-400 text-xs rounded-xl focus:ring-2 focus:ring-blue-500 p-4 transition leading-relaxed border border-slate-800 shadow-inner"><?= htmlspecialchars($ai_config['system_prompt']) ?></textarea>
                            <p class="text-[11px] text-slate-500 mt-2">
                                <i class="fa-solid fa-circle-info text-blue-500 mr-1"></i>
                                Pastikan System Prompt melarang AI memberikan spekulasi atau berita hoaks di luar konteks resmi SMAN 21 Jakarta.
                            </p>
                        </div>
                    </div>

                    <!-- BUTTON SIMPAN -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="reset" class="px-5 py-3 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
                            <i class="fa-solid fa-rotate-left mr-1.5"></i> Batal / Reset
                        </button>
                        <button type="submit" name="submit_ai_settings" class="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-lg shadow-blue-500/20 flex items-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan AI
                        </button>
                    </div>

                </div>

                <!-- SIDEBAR INFORMATION & LIVE PREVIEW (1 KOLOM) -->
                <div class="space-y-6">

                    <!-- PREVIEW WIDGET CHAT AI -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
                        <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                            <i class="fa-solid fa-eye text-blue-600"></i>
                            <h4 class="text-sm font-bold text-slate-800">Simulasi Tampilan Chat</h4>
                        </div>

                        <!-- Chat Mockup Box -->
                        <div class="bg-slate-100 rounded-xl p-3.5 space-y-3 text-xs border border-slate-200">
                            <!-- Bot Avatar Header -->
                            <div class="flex items-center gap-2 bg-slate-900 text-white p-2.5 rounded-lg">
                                <img src="<?= $logo_url ?>" alt="Logo SMAN 21" class="w-6 h-6 object-contain">
                                <div>
                                    <p class="font-bold text-[11px] leading-tight">AI Assistant</p>
                                    <p class="text-[9px] text-emerald-400 font-medium">● Online Official</p>
                                </div>
                            </div>

                            <!-- Greeting Bubble -->
                            <div class="flex items-start gap-2">
                                <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-[10px] flex-shrink-0">
                                    AI
                                </div>
                                <div class="bg-white p-2.5 rounded-2xl rounded-tl-none shadow-sm text-slate-700 border border-slate-200/60 leading-relaxed text-[11px]">
                                    <?= htmlspecialchars($ai_config['pesan_pembuka']) ?>
                                </div>
                            </div>

                            <!-- User Bubble Mock -->
                            <div class="flex items-end justify-end gap-2">
                                <div class="bg-blue-600 text-white p-2.5 rounded-2xl rounded-br-none shadow-sm leading-relaxed text-[11px]">
                                    Bagaimana alur pendaftaran PPDB tahun ini?
                                </div>
                            </div>

                            <!-- Bot Response Mock -->
                            <div class="flex items-start gap-2">
                                <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-[10px] flex-shrink-0">
                                    AI
                                </div>
                                <div class="bg-white p-2.5 rounded-2xl rounded-tl-none shadow-sm text-slate-700 border border-slate-200/60 leading-relaxed text-[11px]">
                                    Pendaftaran PPDB SMAN 21 Jakarta dilakukan secara daring melalui portal resmi PPDB Jakarta. Untuk syarat berkas dan jadwal lengkap dapat Anda akses di menu Informasi PPDB kami!
                                </div>
                            </div>
                        </div>

                        <p class="text-[11px] text-slate-400 italic text-center">
                            *Tampilan nyata tergantung styling widget frontend.
                        </p>
                    </div>

                    <!-- PANDUAN PENGATURAN -->
                    <div class="bg-slate-900 text-slate-300 rounded-2xl p-5 shadow-sm space-y-4 border border-slate-800">
                        <div class="flex items-center gap-2 text-amber-400 border-b border-slate-800 pb-3">
                            <i class="fa-solid fa-lightbulb"></i>
                            <h4 class="text-sm font-bold text-white">Panduan Best Practice</h4>
                        </div>

                        <ul class="space-y-3 text-xs leading-relaxed text-slate-300">
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-emerald-400 mt-1"></i>
                                <span><strong>Temperature:</strong> Gunakan angka rendah (0.10 - 0.30) jika menginginkan AI hanya menjawab fakta persis sesuai tabel Knowledge Base.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-emerald-400 mt-1"></i>
                                <span><strong>Max Token:</strong> Batasi hingga 1000 agar waktu respon pesan cepat dan penggunaan kuota API teratur.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-emerald-400 mt-1"></i>
                                <span><strong>Kelola Knowledge:</strong> Perbarui terus daftar pertanyaan dan jawaban di menu <a href="../ai-assistant/knowledge.php" class="text-blue-400 hover:underline font-semibold">Knowledge Base</a>.</span>
                            </li>
                        </ul>
                    </div>

                    <!-- LAST UPDATE CARD -->
                    <?php if ($ai_config['tanggal_update']): ?>
                        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm text-xs text-slate-500 flex items-center justify-between">
                            <span><i class="fa-solid fa-clock text-slate-400 mr-1"></i> Terakhir Diperbarui:</span>
                            <span class="font-semibold text-slate-700"><?= date('d M Y, H:i', strtotime($ai_config['tanggal_update'])) ?> WIB</span>
                        </div>
                    <?php endif; ?>

                </div>

            </form>

        </main>
    </div>

    <!-- SCRIPT RESPONSIF SIDEBAR TOGGLE & CLOCK -->
    <script>
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-ml-64');
        });

        // Live Clock
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').textContent = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateClock, 1000);
    </script>
</body>
</html>
<?php
// admin/profil/visi-misi.php

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

// Notifikasi flash session
$message = '';
$message_type = '';
if (isset($_SESSION['toast_msg'])) {
    $message = $_SESSION['toast_msg'];
    $message_type = $_SESSION['toast_type'];
    unset($_SESSION['toast_msg']);
    unset($_SESSION['toast_type']);
}

// -----------------------------------------------------------------------------
// PROCESS CRUD ACTIONS (POST REQUESTS)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // TAMBAH DATA VISI / MISI
    if ($action === 'add') {
        $jenis    = mysqli_real_escape_string($conn, trim($_POST['jenis']));
        $isi_teks = mysqli_real_escape_string($conn, trim($_POST['isi_teks']));
        $urutan   = intval($_POST['urutan']);

        if (!empty($jenis) && !empty($isi_teks)) {
            $query = "INSERT INTO pengaturan_visimisi (jenis, isi_teks, urutan) VALUES ('$jenis', '$isi_teks', $urutan)";
            if (mysqli_query($conn, $query)) {
                $_SESSION['toast_msg'] = "Data " . strtoupper($jenis) . " berhasil ditambahkan!";
                $_SESSION['toast_type'] = "success";
            } else {
                $_SESSION['toast_msg'] = "Gagal menambahkan data: " . mysqli_error($conn);
                $_SESSION['toast_type'] = "error";
            }
        } else {
            $_SESSION['toast_msg'] = "Isi teks dan jenis wajib diisi!";
            $_SESSION['toast_type'] = "warning";
        }
        header("Location: visi-misi.php");
        exit;
    }

    // EDIT DATA VISI / MISI
    if ($action === 'edit') {
        $id       = intval($_POST['id']);
        $jenis    = mysqli_real_escape_string($conn, trim($_POST['jenis']));
        $isi_teks = mysqli_real_escape_string($conn, trim($_POST['isi_teks']));
        $urutan   = intval($_POST['urutan']);

        if ($id > 0 && !empty($jenis) && !empty($isi_teks)) {
            $query = "UPDATE pengaturan_visimisi SET jenis = '$jenis', isi_teks = '$isi_teks', urutan = $urutan WHERE id = $id";
            if (mysqli_query($conn, $query)) {
                $_SESSION['toast_msg'] = "Data " . strtoupper($jenis) . " berhasil diperbarui!";
                $_SESSION['toast_type'] = "success";
            } else {
                $_SESSION['toast_msg'] = "Gagal memperbarui data: " . mysqli_error($conn);
                $_SESSION['toast_type'] = "error";
            }
        } else {
            $_SESSION['toast_msg'] = "Data tidak valid!";
            $_SESSION['toast_type'] = "warning";
        }
        header("Location: visi-misi.php");
        exit;
    }

    // HAPUS DATA VISI / MISI
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        if ($id > 0) {
            $query = "DELETE FROM pengaturan_visimisi WHERE id = $id";
            if (mysqli_query($conn, $query)) {
                $_SESSION['toast_msg'] = "Data berhasil dihapus dari sistem!";
                $_SESSION['toast_type'] = "success";
            } else {
                $_SESSION['toast_msg'] = "Gagal menghapus data: " . mysqli_error($conn);
                $_SESSION['toast_type'] = "error";
            }
        }
        header("Location: visi-misi.php");
        exit;
    }
}

// -----------------------------------------------------------------------------
// AMBIL DATA VISI DAN MISI DARI DATABASE
// -----------------------------------------------------------------------------
$data_visi = [];
$data_misi = [];

if ($conn) {
    // Ambil Visi
    $q_visi = mysqli_query($conn, "SELECT * FROM pengaturan_visimisi WHERE jenis = 'visi' ORDER BY urutan ASC, id ASC");
    if ($q_visi) {
        while ($row = mysqli_fetch_assoc($q_visi)) {
            $data_visi[] = $row;
        }
    }

    // Ambil Misi
    $q_misi = mysqli_query($conn, "SELECT * FROM pengaturan_visimisi WHERE jenis = 'misi' ORDER BY urutan ASC, id ASC");
    if ($q_misi) {
        while ($row = mysqli_fetch_assoc($q_misi)) {
            $data_misi[] = $row;
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
    <title>Kelola Visi & Misi - SMAN 21 Jakarta</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
                    <a href="../dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white text-slate-300 font-medium text-sm transition-colors">
                        <i class="fa-solid fa-house w-5 text-center text-slate-400"></i>
                        <span>Dashboard</span>
                    </a>
                </nav>
            </div>

            <!-- Kelola Konten & Informasi -->
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 mb-2">Kelola Sekolah</p>
                <nav class="space-y-1">
                    
                    <!-- Profil Submenu Dropdown (Open Active) -->
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg bg-slate-800/80 text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-school w-5 text-center text-blue-400"></i>
                                Profil
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="sejarah.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800 text-slate-400">Sejarah Sekolah</a>
                            <a href="sambutan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800 text-slate-400">Sambutan Kepsek</a>
                            <a href="visi-misi.php" class="block py-1.5 px-2 rounded bg-blue-600 text-white font-semibold shadow-sm">Visi & Misi</a>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Kelola Visi & Misi</h2>
                    <p class="text-xs text-slate-500">Pengaturan Visi dan Poin Misi SMAN 21 Jakarta</p>
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

            <!-- NOTIFIKASI FEEDBACK ALERTS -->
            <?php if (!empty($message)): ?>
                <div class="p-4 rounded-xl border shadow-sm flex items-start justify-between gap-3 transition <?= $message_type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : ($message_type === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-rose-50 border-rose-200 text-rose-800') ?>">
                    <div class="flex items-center gap-3 text-sm font-medium">
                        <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check text-emerald-500 text-lg' : ($message_type === 'warning' ? 'fa-triangle-exclamation text-amber-500 text-lg' : 'fa-circle-xmark text-rose-500 text-lg') ?>"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
                </div>
            <?php endif; ?>

            <!-- BANNER CARD KONTROL -->
            <div class="bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden border border-slate-800">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-2 max-w-2xl">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-300 text-xs font-semibold border border-blue-400/30">
                            <i class="fa-solid fa-compass"></i> Profil & Arah Kompas Sekolah
                        </div>
                        <h3 class="text-2xl font-bold text-white tracking-tight">Visi & Misi SMAN 21 Jakarta</h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Data yang dikelola pada halaman ini secara otomatis terhubung dengan tabel <code class="bg-slate-800 px-1.5 py-0.5 rounded text-blue-300 font-mono text-xs">pengaturan_visimisi</code> dan langsung tampil pada portal publik serta digunakan sebagai acuan oleh AI Assistant.
                        </p>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <button onclick="openAddModal('misi')" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-xl transition shadow-lg flex items-center gap-2">
                            <i class="fa-solid fa-plus"></i> Tambah Poin Misi
                        </button>
                        <button onclick="openAddModal('visi')" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700 transition flex items-center gap-2">
                            <i class="fa-solid fa-lightbulb text-amber-400"></i> Tambah Visi
                        </button>
                    </div>
                </div>
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>
            </div>

            <!-- BAGIAN 1: VISI SEKOLAH -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-lg">
                            <i class="fa-solid fa-eye"></i>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-slate-800">Visi Sekolah</h4>
                            <p class="text-xs text-slate-500">Cita-cita dan pandangan strategis utama sekolah</p>
                        </div>
                    </div>
                    <?php if (empty($data_visi)): ?>
                        <button onclick="openAddModal('visi')" class="text-xs font-semibold text-blue-600 hover:underline">
                            + Tambah Visi Utama
                        </button>
                    <?php endif; ?>
                </div>

                <div class="p-6">
                    <?php if (!empty($data_visi)): ?>
                        <div class="space-y-4">
                            <?php foreach ($data_visi as $v): ?>
                                <div class="p-5 rounded-2xl bg-gradient-to-br from-blue-50/60 to-slate-50 border border-blue-100/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                    <div class="space-y-2 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-blue-600 text-white">
                                                Visi Utama
                                            </span>
                                            <span class="text-xs font-semibold text-slate-400">Urutan #<?= $v['urutan'] ?></span>
                                        </div>
                                        <p class="text-base font-semibold text-slate-800 leading-relaxed italic">
                                            "<?= htmlspecialchars($v['isi_teks']) ?>"
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0 self-end sm:self-center">
                                        <button onclick='openEditModal(<?= json_encode($v) ?>)' class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold shadow-sm transition flex items-center gap-1.5">
                                            <i class="fa-solid fa-pen-to-square text-blue-600"></i> Edit
                                        </button>
                                        <button onclick="openDeleteModal(<?= $v['id'] ?>, 'Visi')" class="px-3 py-1.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 text-xs font-semibold transition flex items-center gap-1.5">
                                            <i class="fa-solid fa-trash-can"></i> Hapus
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="py-8 text-center text-slate-400 space-y-2">
                            <i class="fa-solid fa-circle-info text-3xl text-slate-300"></i>
                            <p class="text-sm">Belum ada Visi yang dikonfigurasi dalam database.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- BAGIAN 2: MISI SEKOLAH -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg">
                            <i class="fa-solid fa-bullseye"></i>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-slate-800">Misi Sekolah</h4>
                            <p class="text-xs text-slate-500">Langkah-langkah strategis untuk mencapai Visi sekolah</p>
                        </div>
                    </div>
                    <button onclick="openAddModal('misi')" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl transition shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-plus"></i> Tambah Misi
                    </button>
                </div>

                <div class="p-6">
                    <?php if (!empty($data_misi)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-600">
                                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider">
                                    <tr>
                                        <th class="py-3 px-4 w-16 text-center">No</th>
                                        <th class="py-3 px-4">Poin Misi Strategis</th>
                                        <th class="py-3 px-4 w-28 text-center">Urutan</th>
                                        <th class="py-3 px-4 w-36 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php $no = 1; foreach ($data_misi as $m): ?>
                                        <tr class="hover:bg-slate-50/80 transition">
                                            <td class="py-3.5 px-4 text-center font-bold text-slate-700">
                                                <span class="w-7 h-7 rounded-full bg-emerald-50 text-emerald-700 inline-flex items-center justify-center text-xs">
                                                    <?= $no++ ?>
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-4 font-medium text-slate-800 leading-relaxed">
                                                <?= htmlspecialchars($m['isi_teks']) ?>
                                            </td>
                                            <td class="py-3.5 px-4 text-center font-semibold text-slate-500">
                                                <span class="px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 border border-slate-200">
                                                    Ke-<?= $m['urutan'] ?>
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-4 text-center">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <button onclick='openEditModal(<?= json_encode($m) ?>)' class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition" title="Edit Misi">
                                                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                                                    </button>
                                                    <button onclick="openDeleteModal(<?= $m['id'] ?>, 'Misi')" class="p-2 rounded-lg text-rose-600 hover:bg-rose-50 transition" title="Hapus Misi">
                                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="py-12 text-center text-slate-400 space-y-3">
                            <i class="fa-solid fa-list-check text-4xl text-slate-300"></i>
                            <p class="text-sm font-medium">Belum ada poin Misi yang terdaftar.</p>
                            <button onclick="openAddModal('misi')" class="px-4 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-xl hover:bg-emerald-700 transition inline-flex items-center gap-2">
                                <i class="fa-solid fa-plus"></i> Tambah Misi Pertama
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>

    <!-- MODAL FORM TAMBAH / EDIT -->
    <div id="formModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 transform transition-all">
            
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div id="modalIcon" class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-base">
                        <i class="fa-solid fa-pen"></i>
                    </div>
                    <h3 id="modalTitle" class="text-base font-bold text-slate-800">Tambah Data</h3>
                </div>
                <button onclick="closeFormModal()" class="text-slate-400 hover:text-slate-600 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="visi-misi.php" method="POST" class="mt-4 space-y-4">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Jenis Entri</label>
                    <select name="jenis" id="formJenis" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-slate-50 font-medium">
                        <option value="visi">Visi Sekolah</option>
                        <option value="misi">Misi Sekolah</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Isi Teks / Kalimat</label>
                    <textarea name="isi_teks" id="formIsiTeks" rows="4" required placeholder="Tuliskan kalimat visi atau misi secara jelas..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-sans"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Urutan Tampilan</label>
                    <input type="number" name="urutan" id="formUrutan" value="1" min="1" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-[11px] text-slate-400 mt-1">Menentukan urutan tampilan poin di halaman website.</p>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeFormModal()" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition shadow-md">
                        Simpan Data
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS -->
    <div id="deleteModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 text-center space-y-4">
            <div class="w-14 h-14 rounded-full bg-rose-100 text-rose-600 inline-flex items-center justify-center text-2xl">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-800">Konfirmasi Hapus</h3>
                <p class="text-xs text-slate-500 mt-1" id="deleteModalText">Apakah Anda yakin ingin menghapus data ini dari sistem?</p>
            </div>
            
            <form action="visi-misi.php" method="POST" class="pt-2 flex items-center justify-center gap-3">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId" value="">
                
                <button type="button" onclick="closeDeleteModal()" class="w-full py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                    Batal
                </button>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition shadow-md">
                    Ya, Hapus
                </button>
            </form>
        </div>
    </div>

    <!-- SCRIPT RESPONSIF SIDEBAR & MODAL HANDLER -->
    <script>
        // Toggle Sidebar Navigasi
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

        // Modal Form Handlers
        function openAddModal(defaultJenis = 'misi') {
            document.getElementById('formAction').value = 'add';
            document.getElementById('formId').value = '';
            document.getElementById('modalTitle').textContent = 'Tambah ' + defaultJenis.toUpperCase();
            document.getElementById('formJenis').value = defaultJenis;
            document.getElementById('formIsiTeks').value = '';
            document.getElementById('formUrutan').value = '1';
            
            document.getElementById('formModal').classList.remove('hidden');
        }

        function openEditModal(data) {
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formId').value = data.id;
            document.getElementById('modalTitle').textContent = 'Edit ' + data.jenis.toUpperCase();
            document.getElementById('formJenis').value = data.jenis;
            document.getElementById('formIsiTeks').value = data.isi_teks;
            document.getElementById('formUrutan').value = data.urutan;

            document.getElementById('formModal').classList.remove('hidden');
        }

        function closeFormModal() {
            document.getElementById('formModal').classList.add('hidden');
        }

        // Modal Delete Handlers
        function openDeleteModal(id, jenis) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteModalText').textContent = 'Apakah Anda yakin ingin menghapus data ' + jenis + ' ini?';
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
</body>
</html>
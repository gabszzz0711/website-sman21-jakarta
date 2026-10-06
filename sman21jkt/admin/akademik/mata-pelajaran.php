<?php
// admin/akademik/mata-pelajaran.php

// Tampilkan error PHP untuk mempermudah debugging
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
// KONFIGURASI DATABASE BYETHOST / LOCALHOST
// -----------------------------------------------------------------------------
$host = 'sql305.byethost3.com'; // Ganti dengan Database Hostname cPanel/VistaPanel
$user = 'b3_43046986';     // Ganti dengan Username Database
$pass = 'J@karta5';      // Ganti dengan Password Database
$db   = 'b3_43046986_sman21jkt'; // Nama Database

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect($host, $user, $pass, $db);

// Penanganan Notifikasi Session
$message = '';
$message_type = '';

if (isset($_SESSION['msg'])) {
    $message = $_SESSION['msg'];
    $message_type = $_SESSION['msg_type'] ?? 'info';
    unset($_SESSION['msg'], $_SESSION['msg_type']);
}

// -----------------------------------------------------------------------------
// PROSES CRUD (CREATE, UPDATE, DELETE)
// -----------------------------------------------------------------------------
if ($conn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. TAMBAH MATA PELAJARAN
    if ($action === 'add') {
        $nama_mapel = trim($_POST['nama_mapel'] ?? '');

        if (!empty($nama_mapel)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO mapel (nama_mapel) VALUES (?)");
            mysqli_stmt_bind_param($stmt, "s", $nama_mapel);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['msg'] = "Mata pelajaran <strong>" . htmlspecialchars($nama_mapel) . "</strong> berhasil ditambahkan!";
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['msg'] = "Gagal menambahkan data: " . mysqli_error($conn);
                $_SESSION['msg_type'] = "error";
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['msg'] = "Nama mata pelajaran tidak boleh kosong.";
            $_SESSION['msg_type'] = "warning";
        }
        header("Location: mata-pelajaran.php");
        exit;
    }

    // 2. EDIT MATA PELAJARAN
    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $nama_mapel = trim($_POST['nama_mapel'] ?? '');

        if ($id > 0 && !empty($nama_mapel)) {
            $stmt = mysqli_prepare($conn, "UPDATE mapel SET nama_mapel = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $nama_mapel, $id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['msg'] = "Data mata pelajaran berhasil diperbarui!";
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['msg'] = "Gagal mengupdate data: " . mysqli_error($conn);
                $_SESSION['msg_type'] = "error";
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['msg'] = "Data yang dikirimkan tidak valid.";
            $_SESSION['msg_type'] = "warning";
        }
        header("Location: mata-pelajaran.php");
        exit;
    }

    // 3. HAPUS MATA PELAJARAN
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);

        if ($id > 0) {
            // Cek keterhubungan dengan tabel guru
            $check_guru = mysqli_query($conn, "SELECT COUNT(*) as total FROM guru WHERE mapel_id = $id");
            $data_guru = mysqli_fetch_assoc($check_guru);

            if ($data_guru['total'] > 0) {
                $_SESSION['msg'] = "Mata pelajaran tidak dapat dihapus karena masih terhubung dengan <strong>{$data_guru['total']} guru</strong>. Silakan ubah relasi guru terlebih dahulu.";
                $_SESSION['msg_type'] = "error";
            } else {
                $stmt = mysqli_prepare($conn, "DELETE FROM mapel WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['msg'] = "Mata pelajaran berhasil dihapus.";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $_SESSION['msg'] = "Gagal menghapus data: " . mysqli_error($conn);
                    $_SESSION['msg_type'] = "error";
                }
                mysqli_stmt_close($stmt);
            }
        }
        header("Location: mata-pelajaran.php");
        exit;
    }
}

// -----------------------------------------------------------------------------
// GET DATA MATA PELAJARAN & STATISTIK
// -----------------------------------------------------------------------------
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$mapel_list = [];
$total_mapel = 0;
$total_mapel_terisi = 0;
$total_relasi_guru = 0;

if ($conn) {
    // Query Hitung Statistik
    $q_stat = mysqli_query($conn, "SELECT COUNT(*) as total FROM mapel");
    if ($q_stat) {
        $r_stat = mysqli_fetch_assoc($q_stat);
        $total_mapel = $r_stat['total'];
    }

    $q_guru_rel = mysqli_query($conn, "SELECT COUNT(DISTINCT mapel_id) as mapel_terisi, COUNT(*) as total_guru FROM guru WHERE mapel_id IS NOT NULL");
    if ($q_guru_rel) {
        $r_guru_rel = mysqli_fetch_assoc($q_guru_rel);
        $total_mapel_terisi = $r_guru_rel['mapel_terisi'];
        $total_relasi_guru = $r_guru_rel['total_guru'];
    }

    // Query Utama Daftar Mapel dengan Jumlah Guru Terkait
    $query = "SELECT m.id, m.nama_mapel, COUNT(g.id) AS jumlah_guru 
              FROM mapel m 
              LEFT JOIN guru g ON m.id = g.mapel_id";

    if (!empty($search)) {
        $search_escaped = mysqli_real_escape_string($conn, $search);
        $query .= " WHERE m.nama_mapel LIKE '%{$search_escaped}%'";
    }

    $query .= " GROUP BY m.id, m.nama_mapel ORDER BY m.id ASC";

    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $mapel_list[] = $row;
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
    <title>Kelola Mata Pelajaran - SMAN 21 Jakarta</title>
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

                    <!-- Akademik Dropdown (ACTIVE MENU) -->
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg bg-blue-600/20 text-blue-300 cursor-pointer text-sm font-semibold transition-colors list-none border border-blue-500/30">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-graduation-cap w-5 text-center text-blue-400"></i>
                                Akademik
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-blue-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="program.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Program Pendidikan</a>
                            <a href="program-unggulan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Program Unggulan</a>
                            <a href="mata-pelajaran.php" class="block py-1.5 px-2 rounded font-semibold text-white bg-blue-600">Mata Pelajaran</a>
                            <a href="guru.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Data Guru</a>
                            <a href="pemeringkatan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Pemeringkatan</a>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Kelola Mata Pelajaran</h2>
                    <p class="text-xs text-slate-500">Manajemen Kurikulum & Daftar Mata Pelajaran SMAN 21 Jakarta</p>
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

            <!-- NOTIFIKASI PESAN ACTION -->
            <?php if (!empty($message)): ?>
                <div class="p-4 rounded-xl border flex items-center justify-between gap-3 text-sm shadow-sm transition-all <?= $message_type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : ($message_type === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-rose-50 border-rose-200 text-rose-800') ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check text-emerald-500' : ($message_type === 'warning' ? 'fa-triangle-exclamation text-amber-500' : 'fa-circle-xmark text-rose-500') ?> text-lg"></i>
                        <div><?= $message ?></div>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- STATISTIK RINGKAS MAPEL -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Mata Pelajaran</p>
                        <h4 class="text-2xl font-extrabold text-slate-800 mt-1"><?= $total_mapel ?></h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Mata pelajaran terdaftar</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-book"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Mapel Memiliki Pengajar</p>
                        <h4 class="text-2xl font-extrabold text-slate-800 mt-1"><?= $total_mapel_terisi ?></h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Memiliki minimal 1 guru</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Penugasan Guru</p>
                        <h4 class="text-2xl font-extrabold text-slate-800 mt-1"><?= $total_relasi_guru ?></h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Total pengampu mapel</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
            </div>

            <!-- BAR AKSI & PENCARIAN -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                
                <!-- Form Pencarian -->
                <form method="GET" class="flex items-center gap-2 flex-1 max-w-md">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </span>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari mata pelajaran..." class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                    </div>
                    <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-medium rounded-xl transition">
                        Cari
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="mata-pelajaran.php" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium rounded-xl transition">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <!-- Tombol Tambah Modal -->
                <button onclick="openAddModal()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-xl transition shadow-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus"></i>
                    <span>Tambah Mata Pelajaran</span>
                </button>
            </div>

            <!-- TABEL DAFTAR MATA PELAJARAN -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Daftar Mata Pelajaran</h3>
                        <p class="text-xs text-slate-500">Total <?= count($mapel_list) ?> mata pelajaran ditampilkan</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-6 w-16 text-center">ID</th>
                                <th class="py-3.5 px-6">Nama Mata Pelajaran</th>
                                <th class="py-3.5 px-6 text-center">Jumlah Guru Pengampu</th>
                                <th class="py-3.5 px-6 text-center w-40">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($mapel_list)): ?>
                                <?php foreach ($mapel_list as $mapel): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-3.5 px-6 text-center font-mono font-medium text-slate-400">
                                            #<?= sprintf("%02d", $mapel['id']) ?>
                                        </td>
                                        <td class="py-3.5 px-6 font-semibold text-slate-800">
                                            <?= htmlspecialchars($mapel['nama_mapel']) ?>
                                        </td>
                                        <td class="py-3.5 px-6 text-center">
                                            <?php if ($mapel['jumlah_guru'] > 0): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <i class="fa-solid fa-user-tie text-[10px]"></i>
                                                    <?= $mapel['jumlah_guru'] ?> Guru
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 text-slate-500">
                                                    Belum ada guru
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 px-6 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button onclick="openEditModal(<?= $mapel['id'] ?>, '<?= htmlspecialchars(addslashes($mapel['nama_mapel'])) ?>')" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit Mapel">
                                                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                                                </button>
                                                <button onclick="openDeleteModal(<?= $mapel['id'] ?>, '<?= htmlspecialchars(addslashes($mapel['nama_mapel'])) ?>', <?= $mapel['jumlah_guru'] ?>)" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Mapel">
                                                    <i class="fa-solid fa-trash-can text-sm"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="py-10 text-center text-slate-400 italic">
                                        <i class="fa-solid fa-folder-open text-3xl mb-2 text-slate-300 block"></i>
                                        Tidak ada data mata pelajaran yang ditemukan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL TAMBAH MATA PELAJARAN -->
    <!-- ========================================================================= -->
    <div id="addModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl overflow-hidden border border-slate-100 animate-in fade-in zoom-in duration-200">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
                    <i class="fa-solid fa-plus-circle text-blue-600"></i>
                    Tambah Mata Pelajaran
                </h3>
                <button onclick="closeAddModal()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form method="POST" action="mata-pelajaran.php" class="p-6 space-y-4">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Nama Mata Pelajaran <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_mapel" required placeholder="Contoh: Fisika, Matematika Lanjut" class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeAddModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-xl transition shadow-md">
                        Simpan Mapel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL EDIT MATA PELAJARAN -->
    <!-- ========================================================================= -->
    <div id="editModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl overflow-hidden border border-slate-100 animate-in fade-in zoom-in duration-200">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-blue-600"></i>
                    Edit Mata Pelajaran
                </h3>
                <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form method="POST" action="mata-pelajaran.php" class="p-6 space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Nama Mata Pelajaran <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_mapel" id="edit_nama_mapel" required class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-xl transition shadow-md">
                        Update Mapel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL HAPUS KONFIRMASI -->
    <!-- ========================================================================= -->
    <div id="deleteModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl overflow-hidden border border-slate-100 animate-in fade-in zoom-in duration-200">
            <div class="p-6 text-center space-y-4">
                <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 mx-auto flex items-center justify-center text-xl">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 text-base">Konfirmasi Hapus</h3>
                    <p class="text-xs text-slate-500 mt-1">Apakah Anda yakin ingin menghapus mata pelajaran <strong id="delete_nama_mapel" class="text-slate-800"></strong>?</p>
                </div>
                <div id="delete_warning" class="hidden p-3 rounded-xl bg-amber-50 text-amber-800 border border-amber-200 text-xs text-left">
                    <i class="fa-solid fa-circle-exclamation mr-1 text-amber-600"></i>
                    <span>Mapel ini terikat dengan <strong id="delete_guru_count">0</strong> guru pengampu. Hapus atau pindahkan guru terlebih dahulu!</span>
                </div>
                <form method="POST" action="mata-pelajaran.php" class="pt-2 flex items-center justify-center gap-3">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" id="btn_confirm_delete" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold rounded-xl transition shadow-md">
                        Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- SCRIPT RESPONSIF SIDEBAR & MODAL HANDLER -->
    <script>
        // Sidebar Responsive Toggle
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

        // Modal Handlers
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
            document.getElementById('addModal').classList.add('flex');
        }
        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
            document.getElementById('addModal').classList.remove('flex');
        }

        function openEditModal(id, nama) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama_mapel').value = nama;
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }

        function openDeleteModal(id, nama, guruCount) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_nama_mapel').textContent = nama;
            
            const warningBox = document.getElementById('delete_warning');
            const deleteBtn = document.getElementById('btn_confirm_delete');

            if (guruCount > 0) {
                document.getElementById('delete_guru_count').textContent = guruCount;
                warningBox.classList.remove('hidden');
                deleteBtn.disabled = true;
                deleteBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                warningBox.classList.add('hidden');
                deleteBtn.disabled = false;
                deleteBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }

            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
        }
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
        }
    </script>
</body>
</html>
<?php
// admin/informasi/ppdb.php

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
// KONFIGURASI DATABASE BYETHOST
// -----------------------------------------------------------------------------
$host = 'sql305.byethost3.com';
$user = 'b3_43046986';
$pass = 'J@karta5';
$db   = 'b3_43046986_sman21jkt';

// Nonaktifkan strict mode exception agar koneksi tidak error fatal
mysqli_report(MYSQLI_REPORT_OFF);

// Koneksi ke database
$conn = @mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    $db_error = "Koneksi database gagal: " . mysqli_connect_error();
} else {
    mysqli_set_charset($conn, "utf8mb4");
}

// Variable Notifikasi
$message = '';
$message_type = '';

// -----------------------------------------------------------------------------
// PROCESS CRUD ACTIONS
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // TAMBAH DATA PPDB
    if ($action === 'add') {
        $judul            = mysqli_real_escape_string($conn, trim($_POST['judul']));
        $deskripsi        = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));
        $tahun_ajaran     = mysqli_real_escape_string($conn, trim($_POST['tahun_ajaran']));
        $tanggal_mulai    = !empty($_POST['tanggal_mulai']) ? "'" . mysqli_real_escape_string($conn, $_POST['tanggal_mulai']) . "'" : "NULL";
        $tanggal_selesai  = !empty($_POST['tanggal_selesai']) ? "'" . mysqli_real_escape_string($conn, $_POST['tanggal_selesai']) . "'" : "NULL";
        $persyaratan      = mysqli_real_escape_string($conn, trim($_POST['persyaratan']));
        $alur_pendaftaran = mysqli_real_escape_string($conn, trim($_POST['alur_pendaftaran']));
        $dokumen          = mysqli_real_escape_string($conn, trim($_POST['dokumen']));
        $link_pendaftaran = mysqli_real_escape_string($conn, trim($_POST['link_pendaftaran']));
        $kontak           = mysqli_real_escape_string($conn, trim($_POST['kontak']));
        $status           = mysqli_real_escape_string($conn, $_POST['status']);

        $query = "INSERT INTO ppdb (judul, deskripsi, tahun_ajaran, tanggal_mulai, tanggal_selesai, persyaratan, alur_pendaftaran, dokumen, link_pendaftaran, kontak, status) 
                  VALUES ('$judul', '$deskripsi', '$tahun_ajaran', $tanggal_mulai, $tanggal_selesai, '$persyaratan', '$alur_pendaftaran', '$dokumen', '$link_pendaftaran', '$kontak', '$status')";

        if (mysqli_query($conn, $query)) {
            $message = "Informasi PPDB berhasil ditambahkan!";
            $message_type = "success";
        } else {
            $message = "Gagal menambahkan data: " . mysqli_error($conn);
            $message_type = "error";
        }
    }

    // EDIT DATA PPDB
    if ($action === 'edit') {
        $id               = (int)$_POST['id'];
        $judul            = mysqli_real_escape_string($conn, trim($_POST['judul']));
        $deskripsi        = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));
        $tahun_ajaran     = mysqli_real_escape_string($conn, trim($_POST['tahun_ajaran']));
        $tanggal_mulai    = !empty($_POST['tanggal_mulai']) ? "'" . mysqli_real_escape_string($conn, $_POST['tanggal_mulai']) . "'" : "NULL";
        $tanggal_selesai  = !empty($_POST['tanggal_selesai']) ? "'" . mysqli_real_escape_string($conn, $_POST['tanggal_selesai']) . "'" : "NULL";
        $persyaratan      = mysqli_real_escape_string($conn, trim($_POST['persyaratan']));
        $alur_pendaftaran = mysqli_real_escape_string($conn, trim($_POST['alur_pendaftaran']));
        $dokumen          = mysqli_real_escape_string($conn, trim($_POST['dokumen']));
        $link_pendaftaran = mysqli_real_escape_string($conn, trim($_POST['link_pendaftaran']));
        $kontak           = mysqli_real_escape_string($conn, trim($_POST['kontak']));
        $status           = mysqli_real_escape_string($conn, $_POST['status']);

        $query = "UPDATE ppdb SET 
                    judul = '$judul', 
                    deskripsi = '$deskripsi', 
                    tahun_ajaran = '$tahun_ajaran', 
                    tanggal_mulai = $tanggal_mulai, 
                    tanggal_selesai = $tanggal_selesai, 
                    persyaratan = '$persyaratan', 
                    alur_pendaftaran = '$alur_pendaftaran', 
                    dokumen = '$dokumen', 
                    link_pendaftaran = '$link_pendaftaran', 
                    kontak = '$kontak', 
                    status = '$status' 
                  WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            $message = "Informasi PPDB berhasil diperbarui!";
            $message_type = "success";
        } else {
            $message = "Gagal memperbarui data: " . mysqli_error($conn);
            $message_type = "error";
        }
    }

    // HAPUS DATA PPDB
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $query = "DELETE FROM ppdb WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            $message = "Data PPDB berhasil dihapus!";
            $message_type = "success";
        } else {
            $message = "Gagal menghapus data: " . mysqli_error($conn);
            $message_type = "error";
        }
    }

    // UBAH STATUS QUICK TOGGLE
    if ($action === 'change_status') {
        $id     = (int)$_POST['id'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $query  = "UPDATE ppdb SET status = '$status' WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            $message = "Status PPDB berhasil diubah!";
            $message_type = "success";
        } else {
            $message = "Gagal mengubah status: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

// -----------------------------------------------------------------------------
// FETCH DATA PPDB & STATISTIK
// -----------------------------------------------------------------------------
$ppdb_list = [];
$total_ppdb = 0;
$total_buka = 0;
$total_persiapan = 0;
$total_tutup = 0;

if ($conn) {
    $result = mysqli_query($conn, "SELECT * FROM ppdb ORDER BY id DESC");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ppdb_list[] = $row;
            $total_ppdb++;
            if ($row['status'] === 'buka') $total_buka++;
            elseif ($row['status'] === 'persiapan') $total_persiapan++;
            elseif ($row['status'] === 'tutup') $total_tutup++;
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
    <title>Kelola PPDB - Admin SMAN 21 Jakarta</title>
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
                    <a href="../dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white text-slate-300 font-medium text-sm transition-all">
                        <i class="fa-solid fa-house w-5 text-center text-base text-slate-400"></i>
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

                    <!-- Informasi Dropdown (Active Menu) -->
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-800 text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-newspaper w-5 text-center text-blue-400"></i>
                                Informasi
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="berita.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Berita & Pengumuman</a>
                            <a href="agenda.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Agenda Kalender</a>
                            <a href="ppdb.php" class="block py-1.5 px-2 rounded bg-blue-600 text-white font-semibold">Informasi PPDB</a>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Kelola PPDB Sekolah</h2>
                    <p class="text-xs text-slate-500">Penerimaan Peserta Didik Baru SMAN 21 Jakarta</p>
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
                <div class="p-4 rounded-xl text-sm font-medium flex items-center gap-3 border shadow-sm transition-all duration-300 <?= $message_type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?>">
                    <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check text-emerald-600 text-lg' : 'fa-triangle-exclamation text-rose-600 text-lg' ?>"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($db_error)): ?>
                <div class="p-4 rounded-xl text-sm bg-rose-50 text-rose-800 border border-rose-200 flex items-center gap-3">
                    <i class="fa-solid fa-database text-rose-600 text-lg"></i>
                    <span><?= htmlspecialchars($db_error) ?></span>
                </div>
            <?php endif; ?>

            <!-- STATISTIK RINGKAS PPDB -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Gelombang</p>
                            <h4 class="text-3xl font-extrabold text-slate-800 mt-1"><?= $total_ppdb ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-folder-open"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Status Buka</p>
                            <h4 class="text-3xl font-extrabold text-emerald-600 mt-1"><?= $total_buka ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-door-open"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Status Persiapan</p>
                            <h4 class="text-3xl font-extrabold text-amber-600 mt-1"><?= $total_persiapan ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-hourglass-half"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Status Tutup</p>
                            <h4 class="text-3xl font-extrabold text-slate-500 mt-1"><?= $total_tutup ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAIN PANEL DATA TABLES & CONTROLS -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm">
                
                <!-- Action Bar Header -->
                <div class="p-5 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-bold">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Daftar Gelombang / Jalur PPDB</h3>
                            <p class="text-xs text-slate-500">Kelola informasi, alur, persyaratan, dan link pendaftaran PPDB</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button onclick="openAddModal()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-md flex items-center gap-2">
                            <i class="fa-solid fa-plus"></i>
                            <span>Tambah Informasi PPDB</span>
                        </button>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4">Informasi & Gelombang</th>
                                <th class="py-3.5 px-4">Tahun Ajaran</th>
                                <th class="py-3.5 px-4">Jadwal Pelaksanaan</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Link Pendaftaran</th>
                                <th class="py-3.5 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($ppdb_list)): ?>
                                <?php foreach ($ppdb_list as $item): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        
                                        <!-- Judul & Deskripsi Singkat -->
                                        <td class="py-4 px-4 font-medium text-slate-800">
                                            <div class="font-bold text-sm text-slate-900"><?= htmlspecialchars($item['judul']) ?></div>
                                            <?php if (!empty($item['deskripsi'])): ?>
                                                <div class="text-slate-500 text-[11px] line-clamp-1 mt-0.5 max-w-xs">
                                                    <?= htmlspecialchars($item['deskripsi']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Tahun Ajaran -->
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 font-semibold text-xs border border-slate-200">
                                                <i class="fa-solid fa-calendar-check mr-1.5 text-blue-500"></i>
                                                <?= htmlspecialchars($item['tahun_ajaran']) ?>
                                            </span>
                                        </td>

                                        <!-- Jadwal -->
                                        <td class="py-4 px-4 whitespace-nowrap text-slate-600">
                                            <?php if (!empty($item['tanggal_mulai']) || !empty($item['tanggal_selesai'])): ?>
                                                <div class="flex flex-col gap-0.5 text-[11px]">
                                                    <span class="text-emerald-700 font-medium">
                                                        <i class="fa-regular fa-calendar-plus mr-1"></i>
                                                        <?= $item['tanggal_mulai'] ? date('d M Y', strtotime($item['tanggal_mulai'])) : '-' ?>
                                                    </span>
                                                    <span class="text-rose-700 font-medium">
                                                        <i class="fa-regular fa-calendar-minus mr-1"></i>
                                                        <?= $item['tanggal_selesai'] ? date('d M Y', strtotime($item['tanggal_selesai'])) : '-' ?>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-slate-400 italic">Belum ditentukan</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Status Badge -->
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <form method="POST" class="inline-block">
                                                <input type="hidden" name="action" value="change_status">
                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                <select name="status" onchange="this.form.submit()" class="text-[11px] font-semibold px-2.5 py-1 rounded-full cursor-pointer focus:outline-none transition border border-transparent shadow-xs
                                                    <?php 
                                                        if ($item['status'] === 'buka') echo 'bg-emerald-100 text-emerald-800 border-emerald-300 hover:bg-emerald-200';
                                                        elseif ($item['status'] === 'persiapan') echo 'bg-amber-100 text-amber-800 border-amber-300 hover:bg-amber-200';
                                                        else echo 'bg-slate-100 text-slate-700 border-slate-300 hover:bg-slate-200';
                                                    ?>">
                                                    <option value="buka" <?= $item['status'] === 'buka' ? 'selected' : '' ?>>● Buka</option>
                                                    <option value="persiapan" <?= $item['status'] === 'persiapan' ? 'selected' : '' ?>>● Persiapan</option>
                                                    <option value="tutup" <?= $item['status'] === 'tutup' ? 'selected' : '' ?>>● Tutup</option>
                                                </select>
                                            </form>
                                        </td>

                                        <!-- Link Pendaftaran -->
                                        <td class="py-4 px-4">
                                            <?php if (!empty($item['link_pendaftaran'])): ?>
                                                <a href="<?= htmlspecialchars($item['link_pendaftaran']) ?>" target="_blank" class="inline-flex items-center gap-1.5 text-blue-600 hover:text-blue-800 font-medium underline text-xs max-w-[180px] truncate">
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                                    <?= htmlspecialchars($item['link_pendaftaran']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-slate-400 italic">-</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Aksi Button -->
                                        <td class="py-4 px-4 text-center whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1.5">
                                                <!-- Detail Modal Button -->
                                                <button onclick='openDetailModal(<?= json_encode($item) ?>)' class="p-1.5 text-slate-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Lihat Detail">
                                                    <i class="fa-solid fa-eye text-sm"></i>
                                                </button>

                                                <!-- Edit Button -->
                                                <button onclick='openEditModal(<?= json_encode($item) ?>)' class="p-1.5 text-slate-600 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Edit Data">
                                                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                                                </button>

                                                <!-- Delete Form Button -->
                                                <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data PPDB ini?');" class="inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                    <button type="submit" class="p-1.5 text-slate-600 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Data">
                                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <i class="fa-solid fa-folder-open text-3xl text-slate-300"></i>
                                            <p class="text-sm font-medium">Belum ada informasi PPDB yang tersimpan.</p>
                                            <button onclick="openAddModal()" class="mt-2 text-xs text-blue-600 font-semibold hover:underline">
                                                + Tambah Data Pertama
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </main>
    </div>

    <!-- MODAL FORM (TAMBAH & EDIT) -->
    <div id="ppdbModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden overflow-y-auto p-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-3xl max-h-[90vh] flex flex-col my-auto overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            
            <!-- Header Modal -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold text-sm">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <h3 id="modalTitle" class="font-bold text-base text-white">Tambah Informasi PPDB</h3>
                </div>
                <button onclick="closePpdbModal()" class="text-slate-400 hover:text-white p-1 rounded-lg transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Body Modal Form -->
            <form id="ppdbForm" method="POST" class="flex-1 overflow-y-auto p-6 space-y-5 text-xs text-slate-700">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="ppdbId" value="">

                <!-- Baris 1: Judul & Tahun Ajaran -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2 space-y-1">
                        <label class="font-semibold text-slate-800">Judul / Nama Gelombang PPDB <span class="text-rose-500">*</span></label>
                        <input type="text" name="judul" id="inputJudul" required placeholder="Contoh: PPDB SMAN 21 Jakarta TA 2026/2027" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-800">Tahun Ajaran <span class="text-rose-500">*</span></label>
                        <input type="text" name="tahun_ajaran" id="inputTahunAjaran" required placeholder="2026/2027" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                    </div>
                </div>

                <!-- Baris 2: Tanggal Mulai, Selesai, Status -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-800">Tanggal Mulai Pendaftaran</label>
                        <input type="date" name="tanggal_mulai" id="inputTanggalMulai" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-800">Tanggal Selesai Pendaftaran</label>
                        <input type="date" name="tanggal_selesai" id="inputTanggalSelesai" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-800">Status Pendaftaran <span class="text-rose-500">*</span></label>
                        <select name="status" id="inputStatus" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                            <option value="buka">Buka (Terbuka Umum)</option>
                            <option value="persiapan">Persiapan (Coming Soon)</option>
                            <option value="tutup">Tutup (Selesai)</option>
                        </select>
                    </div>
                </div>

                <!-- Baris 3: Deskripsi Singkat -->
                <div class="space-y-1">
                    <label class="font-semibold text-slate-800">Deskripsi Ringkas</label>
                    <textarea name="deskripsi" id="inputDeskripsi" rows="2" placeholder="Tuliskan gambaran umum seputar jalur pendaftaran..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"></textarea>
                </div>

                <!-- Baris 4: Persyaratan & Alur Pendaftaran -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-800">Persyaratan Pendaftaran</label>
                        <textarea name="persyaratan" id="inputPersyaratan" rows="4" placeholder="Contoh:&#10;1. IJAZAH/SKL SMP&#10;2. Kartu Keluarga&#10;3. Akta Kelahiran" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition font-mono text-[11px]"></textarea>
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-800">Alur Pendaftaran</label>
                        <textarea name="alur_pendaftaran" id="inputAlur" rows="4" placeholder="Contoh:&#10;1. Registrasi Akun Online&#10;2. Upload Berkas Persyaratan&#10;3. Verifikasi Panitia" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition font-mono text-[11px]"></textarea>
                    </div>
                </div>

                <!-- Baris 5: Dokumen & Kontak -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-800">Dokumen / Lampiran Pendukung</label>
                        <textarea name="dokumen" id="inputDokumen" rows="2" placeholder="Keterangan dokumen yang harus diunggah/disiapkan..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"></textarea>
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-800">Kontak Person / Helpdesk PPDB</label>
                        <input type="text" name="kontak" id="inputKontak" placeholder="Contoh: Panitia PPDB (0812-XXXX-XXXX)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                    </div>
                </div>

                <!-- Baris 6: Link Pendaftaran -->
                <div class="space-y-1">
                    <label class="font-semibold text-slate-800">Link Formulir / Portal Pendaftaran (URL)</label>
                    <input type="url" name="link_pendaftaran" id="inputLink" placeholder="https://ppdb.jakarta.go.id atau link portal pendaftaran" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                </div>

                <!-- Footer Modal Buttons -->
                <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
                    <button type="button" onclick="closePpdbModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold shadow-md transition">
                        Simpan Data PPDB
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- MODAL DETAIL PREVIEW -->
    <div id="detailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden overflow-y-auto p-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-2xl max-h-[90vh] flex flex-col my-auto overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-info text-blue-400 text-lg"></i>
                    <h3 class="font-bold text-base text-white">Detail Informasi PPDB</h3>
                </div>
                <button onclick="closeDetailModal()" class="text-slate-400 hover:text-white p-1 rounded-lg transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto space-y-5 text-xs text-slate-700">
                <div>
                    <span id="detailStatus" class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider mb-2"></span>
                    <h2 id="detailJudul" class="text-lg font-bold text-slate-900"></h2>
                    <p class="text-slate-500 text-xs">Tahun Ajaran: <span id="detailTahun" class="font-semibold text-slate-800"></span></p>
                </div>

                <div class="grid grid-cols-2 gap-4 bg-slate-50 p-3.5 rounded-xl border border-slate-200/80">
                    <div>
                        <span class="text-slate-500 text-[11px] block">Mulai Pendaftaran:</span>
                        <span id="detailMulai" class="font-bold text-emerald-700"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 text-[11px] block">Selesai Pendaftaran:</span>
                        <span id="detailSelesai" class="font-bold text-rose-700"></span>
                    </div>
                </div>

                <div class="space-y-1">
                    <p class="font-bold text-slate-800 text-xs">Deskripsi:</p>
                    <p id="detailDeskripsi" class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-slate-600 whitespace-pre-line"></p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <p class="font-bold text-slate-800 text-xs">Persyaratan:</p>
                        <div id="detailPersyaratan" class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-slate-600 whitespace-pre-line font-mono text-[11px]"></div>
                    </div>
                    <div class="space-y-1">
                        <p class="font-bold text-slate-800 text-xs">Alur Pendaftaran:</p>
                        <div id="detailAlur" class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-slate-600 whitespace-pre-line font-mono text-[11px]"></div>
                    </div>
                </div>

                <div class="space-y-1">
                    <p class="font-bold text-slate-800 text-xs">Dokumen Persyaratan:</p>
                    <p id="detailDokumen" class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-slate-600 whitespace-pre-line"></p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-200">
                    <div>
                        <span class="text-slate-500 text-[11px] block">Kontak Helpdesk:</span>
                        <span id="detailKontak" class="font-semibold text-slate-800"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 text-[11px] block">Link Portal Pendaftaran:</span>
                        <a id="detailLink" href="#" target="_blank" class="text-blue-600 font-semibold underline truncate block"></a>
                    </div>
                </div>
            </div>

            <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
                <button onclick="closeDetailModal()" class="px-4 py-1.5 bg-slate-800 text-white rounded-lg text-xs font-semibold hover:bg-slate-700 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
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

        // Modal Form Handlers
        const modal = document.getElementById('ppdbModal');
        const form = document.getElementById('ppdbForm');

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Informasi PPDB Baru';
            document.getElementById('formAction').value = 'add';
            document.getElementById('ppdbId').value = '';
            form.reset();
            modal.classList.remove('hidden');
        }

        function openEditModal(data) {
            document.getElementById('modalTitle').textContent = 'Edit Informasi PPDB';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('ppdbId').value = data.id;
            
            document.getElementById('inputJudul').value = data.judul || '';
            document.getElementById('inputTahunAjaran').value = data.tahun_ajaran || '';
            document.getElementById('inputTanggalMulai').value = data.tanggal_mulai || '';
            document.getElementById('inputTanggalSelesai').value = data.tanggal_selesai || '';
            document.getElementById('inputStatus').value = data.status || 'buka';
            document.getElementById('inputDeskripsi').value = data.deskripsi || '';
            document.getElementById('inputPersyaratan').value = data.persyaratan || '';
            document.getElementById('inputAlur').value = data.alur_pendaftaran || '';
            document.getElementById('inputDokumen').value = data.dokumen || '';
            document.getElementById('inputKontak').value = data.kontak || '';
            document.getElementById('inputLink').value = data.link_pendaftaran || '';

            modal.classList.remove('hidden');
        }

        function closePpdbModal() {
            modal.classList.add('hidden');
        }

        // Modal Detail Handlers
        const detailModal = document.getElementById('detailModal');

        function openDetailModal(data) {
            document.getElementById('detailJudul').textContent = data.judul || '-';
            document.getElementById('detailTahun').textContent = data.tahun_ajaran || '-';
            document.getElementById('detailMulai').textContent = data.tanggal_mulai ? data.tanggal_mulai : 'Belum Diatur';
            document.getElementById('detailSelesai').textContent = data.tanggal_selesai ? data.tanggal_selesai : 'Belum Diatur';
            document.getElementById('detailDeskripsi').textContent = data.deskripsi || 'Tidak ada deskripsi.';
            document.getElementById('detailPersyaratan').textContent = data.persyaratan || 'Tidak ada data persyaratan.';
            document.getElementById('detailAlur').textContent = data.alur_pendaftaran || 'Tidak ada data alur pendaftaran.';
            document.getElementById('detailDokumen').textContent = data.dokumen || 'Tidak ada dokumen khusus.';
            document.getElementById('detailKontak').textContent = data.kontak || '-';

            const detailLink = document.getElementById('detailLink');
            if (data.link_pendaftaran) {
                detailLink.href = data.link_pendaftaran;
                detailLink.textContent = data.link_pendaftaran;
            } else {
                detailLink.href = '#';
                detailLink.textContent = 'Tidak ada link pendaftaran';
            }

            const statusElem = document.getElementById('detailStatus');
            if (data.status === 'buka') {
                statusElem.textContent = 'STATUS: BUKA';
                statusElem.className = 'inline-block px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider mb-2 bg-emerald-100 text-emerald-800';
            } else if (data.status === 'persiapan') {
                statusElem.textContent = 'STATUS: PERSIAPAN';
                statusElem.className = 'inline-block px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider mb-2 bg-amber-100 text-amber-800';
            } else {
                statusElem.textContent = 'STATUS: TUTUP';
                statusElem.className = 'inline-block px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider mb-2 bg-slate-100 text-slate-700';
            }

            detailModal.classList.remove('hidden');
        }

        function closeDetailModal() {
            detailModal.classList.add('hidden');
        }

        // Close Modals when clicking outside
        window.onclick = function(event) {
            if (event.target === modal) closePpdbModal();
            if (event.target === detailModal) closeDetailModal();
        }
    </script>
</body>
</html>
<?php
// admin/kegiatan/index.php

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
$host = 'sql305.byethost3.com'; // Ganti dengan Database Hostname cPanel/VistaPanel
$user = 'b3_43046986';     // Ganti dengan Username Database
$pass = 'J@karta5';      // Ganti dengan Password Database
$db   = 'b3_43046986_sman21jkt'; // Nama Database

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect($host, $user, $pass, $db);

// Notifikasi status aksi
$message = '';
$message_type = '';

// Fungsi Pembuat Slug URL
function make_slug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// Direktori Penyimpanan Gambar
$upload_dir = '../../uploads/kegiatan/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// -----------------------------------------------------------------------------
// PROSES HANDLER POST (TAMBAH, EDIT, HAPUS)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // PROSES TAMBAH / EDIT
    if ($action === 'save' || $action === 'create' || $action === 'update') {
        $id              = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $judul_kegiatan  = mysqli_real_escape_string($conn, trim($_POST['judul_kegiatan']));
        $slug            = make_slug($judul_kegiatan);
        $kategori        = mysqli_real_escape_string($conn, $_POST['kategori']);
        $deskripsi       = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        $tanggal_mulai   = !empty($_POST['tanggal_mulai']) ? "'".mysqli_real_escape_string($conn, $_POST['tanggal_mulai'])."'" : "NULL";
        $tanggal_selesai = !empty($_POST['tanggal_selesai']) ? "'".mysqli_real_escape_string($conn, $_POST['tanggal_selesai'])."'" : "NULL";
        $lokasi          = mysqli_real_escape_string($conn, $_POST['lokasi']);
        $status          = mysqli_real_escape_string($conn, $_POST['status']);

        // Upload Thumbnail
        $thumbnail_name = isset($_POST['existing_thumbnail']) ? $_POST['existing_thumbnail'] : '';
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
            $thumbnail_name = 'thumb_' . time() . '_' . rand(100, 999) . '.' . strtolower($ext);
            move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_dir . $thumbnail_name);
        }

        // Upload Foto Utama
        $foto_utama_name = isset($_POST['existing_foto_utama']) ? $_POST['existing_foto_utama'] : '';
        if (isset($_FILES['foto_utama']) && $_FILES['foto_utama']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['foto_utama']['name'], PATHINFO_EXTENSION);
            $foto_utama_name = 'foto_' . time() . '_' . rand(100, 999) . '.' . strtolower($ext);
            move_uploaded_file($_FILES['foto_utama']['tmp_name'], $upload_dir . $foto_utama_name);
        }

        if ($id === 0) {
            // INSERT BARU
            $query = "INSERT INTO kegiatan (judul_kegiatan, slug, kategori, deskripsi, tanggal_mulai, tanggal_selesai, lokasi, thumbnail, foto_utama, status) 
                      VALUES ('$judul_kegiatan', '$slug', '$kategori', '$deskripsi', $tanggal_mulai, $tanggal_selesai, '$lokasi', '$thumbnail_name', '$foto_utama_name', '$status')";
            if (mysqli_query($conn, $query)) {
                $message = "Kegiatan baru berhasil ditambahkan!";
                $message_type = "success";
            } else {
                $message = "Gagal menambah kegiatan: " . mysqli_error($conn);
                $message_type = "danger";
            }
        } else {
            // UPDATE DATA
            $sql_thumb = $thumbnail_name !== '' ? ", thumbnail='$thumbnail_name'" : "";
            $sql_foto  = $foto_utama_name !== '' ? ", foto_utama='$foto_utama_name'" : "";

            $query = "UPDATE kegiatan SET 
                        judul_kegiatan='$judul_kegiatan',
                        slug='$slug',
                        kategori='$kategori',
                        deskripsi='$deskripsi',
                        tanggal_mulai=$tanggal_mulai,
                        tanggal_selesai=$tanggal_selesai,
                        lokasi='$lokasi',
                        status='$status'
                        $sql_thumb $sql_foto
                      WHERE id=$id";
            if (mysqli_query($conn, $query)) {
                $message = "Data kegiatan berhasil diperbarui!";
                $message_type = "success";
            } else {
                $message = "Gagal memperbarui kegiatan: " . mysqli_error($conn);
                $message_type = "danger";
            }
        }
    }

    // PROSES HAPUS
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $q_img = mysqli_query($conn, "SELECT thumbnail, foto_utama FROM kegiatan WHERE id=$id");
        if ($q_img && $r_img = mysqli_fetch_assoc($q_img)) {
            if ($r_img['thumbnail'] && file_exists($upload_dir . $r_img['thumbnail'])) @unlink($upload_dir . $r_img['thumbnail']);
            if ($r_img['foto_utama'] && file_exists($upload_dir . $r_img['foto_utama'])) @unlink($upload_dir . $r_img['foto_utama']);
        }

        $query = "DELETE FROM kegiatan WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            $message = "Kegiatan berhasil dihapus!";
            $message_type = "success";
        } else {
            $message = "Gagal menghapus kegiatan: " . mysqli_error($conn);
            $message_type = "danger";
        }
    }
}

// -----------------------------------------------------------------------------
// GET DATA KEGIATAN & FILTER SEARCH
// -----------------------------------------------------------------------------
$search     = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$cat_filter = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, trim($_GET['kategori'])) : '';

$where_clause = [];
if ($search !== '') {
    $where_clause[] = "(judul_kegiatan LIKE '%$search%' OR lokasi LIKE '%$search%')";
}
if ($cat_filter !== '') {
    $where_clause[] = "kategori = '$cat_filter'";
}

$where_sql = count($where_clause) > 0 ? 'WHERE ' . implode(' AND ', $where_clause) : '';

$list_kegiatan = [];
if ($conn) {
    $q = mysqli_query($conn, "SELECT * FROM kegiatan $where_sql ORDER BY id DESC");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $list_kegiatan[] = $row;
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
    <title>Kelola Kegiatan - SMAN 21 Jakarta</title>
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
        .sidebar-scroll::-webkit-scrollbar { width: 5px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.15); border-radius: 4px; }
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
            
            <!-- Utama -->
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 mb-2">Utama</p>
                <nav class="space-y-1">
                    <a href="../dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white text-sm font-medium transition-colors">
                        <i class="fa-solid fa-house w-5 text-center text-slate-400"></i>
                        <span>Dashboard</span>
                    </a>
                </nav>
            </div>

            <!-- Kelola Sekolah -->
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

                    <!-- Kegiatan Dropdown (Active Menu) -->
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg bg-blue-600 text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-masks-theater w-5 text-center"></i>
                                Kegiatan
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-white group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="index.php" class="block py-1.5 px-2 rounded bg-slate-800 text-white font-medium">Daftar Kegiatan</a>
                            <a href="ekstrakurikuler.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800 text-slate-300">Ekstrakurikuler</a>
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

            <!-- Smart System -->
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

            <!-- Pengaturan System -->
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

        <!-- Footer Sidebar -->
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Kelola Kegiatan Sekolah</h2>
                    <p class="text-xs text-slate-500">Manajemen daftar acara, program, dan dokumentasi aktivitas SMAN 21 Jakarta</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden md:flex items-center gap-2 bg-slate-100 px-3 py-1.5 rounded-full text-xs text-slate-600 font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span id="liveClock"><?= date('D, d M Y') ?></span>
                </div>

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

            <!-- ALERT NOTIFIKASI -->
            <?php if ($message !== ''): ?>
                <div class="p-4 rounded-xl text-sm font-medium flex items-center justify-between shadow-sm <?= $message_type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-rose-600' ?> text-lg"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
                </div>
            <?php endif; ?>

            <!-- HEADER BAR & TOMBOL TAMBAH -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                
                <!-- Filter Search & Kategori -->
                <form method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                    <div class="relative min-w-[220px]">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari kegiatan..." class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>

                    <select name="kategori" onchange="this.form.submit()" class="px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                        <option value="">Semua Kategori</option>
                        <option value="Akademik" <?= $cat_filter === 'Akademik' ? 'selected' : '' ?>>Akademik</option>
                        <option value="Non-Akademik" <?= $cat_filter === 'Non-Akademik' ? 'selected' : '' ?>>Non-Akademik</option>
                        <option value="Seni & Budaya" <?= $cat_filter === 'Seni & Budaya' ? 'selected' : '' ?>>Seni & Budaya</option>
                        <option value="Olahraga" <?= $cat_filter === 'Olahraga' ? 'selected' : '' ?>>Olahraga</option>
                        <option value="Keagamaan" <?= $cat_filter === 'Keagamaan' ? 'selected' : '' ?>>Keagamaan</option>
                        <option value="Umum" <?= $cat_filter === 'Umum' ? 'selected' : '' ?>>Umum</option>
                    </select>

                    <?php if ($search !== '' || $cat_filter !== ''): ?>
                        <a href="index.php" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition">Reset</a>
                    <?php endif; ?>
                </form>

                <button onclick="openModalAdd()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-md flex items-center justify-center gap-2 flex-shrink-0">
                    <i class="fa-solid fa-plus"></i> Tambah Kegiatan
                </button>
            </div>

            <!-- TABEL DAFTAR KEGIATAN -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4 text-center w-12">No</th>
                                <th class="py-3.5 px-4">Thumbnail</th>
                                <th class="py-3.5 px-4">Judul Kegiatan</th>
                                <th class="py-3.5 px-4">Kategori</th>
                                <th class="py-3.5 px-4">Jadwal & Lokasi</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-center w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($list_kegiatan)): ?>
                                <?php $no = 1; foreach ($list_kegiatan as $row): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-3.5 px-4 text-center font-medium text-slate-400"><?= $no++ ?></td>
                                        <td class="py-3.5 px-4">
                                            <?php if (!empty($row['thumbnail']) && file_exists($upload_dir . $row['thumbnail'])): ?>
                                                <img src="../../uploads/kegiatan/<?= htmlspecialchars($row['thumbnail']) ?>" alt="Thumbnail" class="w-14 h-10 object-cover rounded-lg border border-slate-200 shadow-sm">
                                            <?php else: ?>
                                                <div class="w-14 h-10 bg-slate-100 rounded-lg border border-slate-200 flex items-center justify-center text-slate-400 text-xs">
                                                    <i class="fa-solid fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 px-4 font-semibold text-slate-800 max-w-xs">
                                            <div class="truncate text-sm"><?= htmlspecialchars($row['judul_kegiatan']) ?></div>
                                            <div class="text-[11px] text-slate-400 font-normal">Slug: <?= htmlspecialchars($row['slug']) ?></div>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="inline-flex px-2.5 py-1 rounded-md text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                                <?= htmlspecialchars($row['kategori']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 space-y-0.5">
                                            <div class="text-slate-700 font-medium">
                                                <i class="fa-regular fa-calendar text-slate-400 mr-1"></i>
                                                <?= $row['tanggal_mulai'] ? date('d M Y', strtotime($row['tanggal_mulai'])) : '-' ?>
                                                <?= $row['tanggal_selesai'] ? ' s/d ' . date('d M Y', strtotime($row['tanggal_selesai'])) : '' ?>
                                            </div>
                                            <?php if (!empty($row['lokasi'])): ?>
                                                <div class="text-slate-400 text-[11px]">
                                                    <i class="fa-solid fa-location-dot mr-1 text-rose-400"></i><?= htmlspecialchars($row['lokasi']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <?php if ($row['status'] === 'published'): ?>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Published
                                                </span>
                                            <?php elseif ($row['status'] === 'draft'): ?>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Draft
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Archived
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button onclick='openModalEdit(<?= json_encode($row) ?>)' class="p-2 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 transition" title="Edit">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kegiatan ini?');" class="inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 transition" title="Hapus">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400 italic">
                                        <i class="fa-solid fa-folder-open text-3xl mb-2 block text-slate-300"></i>
                                        Belum ada data kegiatan yang tersimpan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- MODAL FORM (TAMBAH / EDIT KEGIATAN) -->
    <div id="modalKegiatan" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden border border-slate-200">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <h3 id="modalTitle" class="text-base font-bold text-slate-800">Tambah Kegiatan Baru</h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 text-lg p-1"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <!-- Modal Body Form -->
            <form id="formKegiatan" method="POST" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-6 space-y-4 text-xs">
                <input type="hidden" name="action" value="save">
                <input type="hidden" id="field_id" name="id" value="0">

                <div class="space-y-1">
                    <label class="font-semibold text-slate-700">Judul Kegiatan <span class="text-rose-500">*</span></label>
                    <input type="text" id="field_judul" name="judul_kegiatan" required placeholder="Contoh: Peringatan Hari Pendidikan Nasional" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-xs">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-700">Kategori <span class="text-rose-500">*</span></label>
                        <select id="field_kategori" name="kategori" required class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-xs bg-white">
                            <option value="Akademik">Akademik</option>
                            <option value="Non-Akademik">Non-Akademik</option>
                            <option value="Seni & Budaya">Seni & Budaya</option>
                            <option value="Olahraga">Olahraga</option>
                            <option value="Keagamaan">Keagamaan</option>
                            <option value="Umum">Umum</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="font-semibold text-slate-700">Status Publikasi <span class="text-rose-500">*</span></label>
                        <select id="field_status" name="status" required class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-xs bg-white">
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-700">Tanggal Mulai</label>
                        <input type="date" id="field_tgl_mulai" name="tanggal_mulai" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-xs">
                    </div>

                    <div class="space-y-1">
                        <label class="font-semibold text-slate-700">Tanggal Selesai</label>
                        <input type="date" id="field_tgl_selesai" name="tanggal_selesai" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-xs">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-700">Lokasi Pelaksanaan</label>
                    <input type="text" id="field_lokasi" name="lokasi" placeholder="Contoh: Lapangan Utama SMAN 21 Jakarta" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-xs">
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-700">Deskripsi Lengkap Kegiatan</label>
                    <textarea id="field_deskripsi" name="deskripsi" rows="4" placeholder="Tuliskan rincian agenda, peserta, dan jalannya acara..." class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-xs"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-700">Thumbnail Gambar (Kecil)</label>
                        <input type="file" name="thumbnail" accept="image/*" class="w-full px-2 py-1.5 rounded-xl border border-slate-300 text-xs">
                        <p class="text-[10px] text-slate-400">Format: JPG, PNG, WEBP (Max 2MB)</p>
                    </div>

                    <div class="space-y-1">
                        <label class="font-semibold text-slate-700">Foto Utama (Banner / Dokumen)</label>
                        <input type="file" name="foto_utama" accept="image/*" class="w-full px-2 py-1.5 rounded-xl border border-slate-300 text-xs">
                        <p class="text-[10px] text-slate-400">Format: JPG, PNG, WEBP (Max 5MB)</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-md transition">
                        Simpan Data
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- SCRIPT RESPONSIF & MODAL CONTROL -->
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

        // Modal Functionality
        const modal = document.getElementById('modalKegiatan');

        function openModalAdd() {
            document.getElementById('modalTitle').textContent = 'Tambah Kegiatan Baru';
            document.getElementById('field_id').value = '0';
            document.getElementById('field_judul').value = '';
            document.getElementById('field_kategori').value = 'Akademik';
            document.getElementById('field_status').value = 'published';
            document.getElementById('field_tgl_mulai').value = '';
            document.getElementById('field_tgl_selesai').value = '';
            document.getElementById('field_lokasi').value = '';
            document.getElementById('field_deskripsi').value = '';

            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.remove('opacity-0'), 10);
        }

        function openModalEdit(data) {
            document.getElementById('modalTitle').textContent = 'Edit Data Kegiatan';
            document.getElementById('field_id').value = data.id;
            document.getElementById('field_judul').value = data.judul_kegiatan || '';
            document.getElementById('field_kategori').value = data.kategori || 'Umum';
            document.getElementById('field_status').value = data.status || 'published';
            document.getElementById('field_tgl_mulai').value = data.tanggal_mulai || '';
            document.getElementById('field_tgl_selesai').value = data.tanggal_selesai || '';
            document.getElementById('field_lokasi').value = data.lokasi || '';
            document.getElementById('field_deskripsi').value = data.deskripsi || '';

            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.remove('opacity-0'), 10);
        }

        function closeModal() {
            modal.classList.add('opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }
    </script>
</body>
</html>
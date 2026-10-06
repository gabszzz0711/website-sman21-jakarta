<?php
// admin/akademik/program-unggulan.php

// Tampilkan error PHP untuk mempermudah debugging saat pengujian
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

$logo_url = "https://sman21jkt.sch.id/wp-content/uploads/2025/02/1000305388-removebg-preview_image_repair_1740014356410.jpg";

// Direktori untuk upload gambar
$upload_dir = "../../uploads/program_unggulan/";
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// Flash Message Alert Handler
$message = '';
$message_type = '';

// -----------------------------------------------------------------------------
// PROCESS POST & GET ACTIONS (CRUD)
// -----------------------------------------------------------------------------

// 1. TAMBAH DATA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $judul        = $conn ? mysqli_real_escape_string($conn, trim($_POST['judul'])) : '';
    $deskripsi    = $conn ? mysqli_real_escape_string($conn, trim($_POST['deskripsi'])) : '';
    $ikon         = $conn ? mysqli_real_escape_string($conn, trim($_POST['ikon'])) : 'fa-solid fa-star';
    $urutan       = isset($_POST['urutan']) ? (int)$_POST['urutan'] : 1;
    $status_aktif = isset($_POST['status_aktif']) ? (int)$_POST['status_aktif'] : 1;
    $gambar_name  = '';

    // Handle Upload Gambar
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['gambar']['tmp_name'];
        $file_ext  = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

        if (in_array($file_ext, $allowed)) {
            $gambar_name = 'prog_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
            move_uploaded_file($file_tmp, $upload_dir . $gambar_name);
        }
    }

    if ($conn) {
        $query = "INSERT INTO `program_unggulan` (`judul`, `deskripsi`, `gambar`, `ikon`, `urutan`, `status_aktif`) 
                  VALUES ('$judul', '$deskripsi', '$gambar_name', '$ikon', $urutan, $status_aktif)";
        if (mysqli_query($conn, $query)) {
            $message = "Program unggulan berhasil ditambahkan!";
            $message_type = "success";
        } else {
            $message = "Gagal menyimpan data: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

// 2. EDIT DATA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id           = (int)$_POST['id'];
    $judul        = $conn ? mysqli_real_escape_string($conn, trim($_POST['judul'])) : '';
    $deskripsi    = $conn ? mysqli_real_escape_string($conn, trim($_POST['deskripsi'])) : '';
    $ikon         = $conn ? mysqli_real_escape_string($conn, trim($_POST['ikon'])) : 'fa-solid fa-star';
    $urutan       = isset($_POST['urutan']) ? (int)$_POST['urutan'] : 1;
    $status_aktif = isset($_POST['status_aktif']) ? (int)$_POST['status_aktif'] : 1;

    $gambar_sql = "";
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['gambar']['tmp_name'];
        $file_ext  = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

        if (in_array($file_ext, $allowed)) {
            $gambar_name = 'prog_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
            if (move_uploaded_file($file_tmp, $upload_dir . $gambar_name)) {
                // Hapus foto lama jika ada
                if (!empty($_POST['gambar_lama']) && file_exists($upload_dir . $_POST['gambar_lama'])) {
                    @unlink($upload_dir . $_POST['gambar_lama']);
                }
                $gambar_sql = ", `gambar` = '$gambar_name'";
            }
        }
    }

    if ($conn) {
        $query = "UPDATE `program_unggulan` 
                  SET `judul` = '$judul', `deskripsi` = '$deskripsi', `ikon` = '$ikon', 
                      `urutan` = $urutan, `status_aktif` = $status_aktif $gambar_sql 
                  WHERE `id` = $id";
        if (mysqli_query($conn, $query)) {
            $message = "Data program unggulan berhasil diperbarui!";
            $message_type = "success";
        } else {
            $message = "Gagal mengupdate data: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

// 3. TOGGLE STATUS AKTIF/NONAKTIF
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($conn) {
        $query = "UPDATE `program_unggulan` SET `status_aktif` = IF(`status_aktif` = 1, 0, 1) WHERE `id` = $id";
        if (mysqli_query($conn, $query)) {
            $message = "Status program unggulan berhasil diubah!";
            $message_type = "success";
        }
    }
}

// 4. HAPUS DATA
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($conn) {
        // Ambil info file gambar sebelum dihapus
        $q = mysqli_query($conn, "SELECT gambar FROM `program_unggulan` WHERE id = $id");
        if ($r = mysqli_fetch_assoc($q)) {
            if (!empty($r['gambar']) && file_exists($upload_dir . $r['gambar'])) {
                @unlink($upload_dir . $r['gambar']);
            }
        }
        mysqli_query($conn, "DELETE FROM `program_unggulan` WHERE id = $id");
        $message = "Program unggulan telah dihapus!";
        $message_type = "success";
    }
}

// -----------------------------------------------------------------------------
// MENGAMBIL DATA UNTUK TAMPILAN
// -----------------------------------------------------------------------------
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

$where_clauses = [];
if ($search !== '') {
    $s_escaped = $conn ? mysqli_real_escape_string($conn, $search) : '';
    $where_clauses[] = "(`judul` LIKE '%$s_escaped%' OR `deskripsi` LIKE '%$s_escaped%')";
}
if ($filter_status === '1') {
    $where_clauses[] = "`status_aktif` = 1";
} elseif ($filter_status === '0') {
    $where_clauses[] = "`status_aktif` = 0";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

$program_list = [];
$total_program = 0;
$total_aktif = 0;
$total_nonaktif = 0;

if ($conn) {
    // Hitung ringkasan statistik
    $res_stat = mysqli_query($conn, "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status_aktif = 1 THEN 1 ELSE 0 END) as aktif,
        SUM(CASE WHEN status_aktif = 0 THEN 1 ELSE 0 END) as nonaktif
        FROM `program_unggulan`");
    if ($res_stat && $st = mysqli_fetch_assoc($res_stat)) {
        $total_program  = (int)$st['total'];
        $total_aktif    = (int)$st['aktif'];
        $total_nonaktif = (int)$st['nonaktif'];
    }

    // Ambil daftar program
    $q_data = mysqli_query($conn, "SELECT * FROM `program_unggulan` $where_sql ORDER BY `urutan` ASC, `id` DESC");
    if ($q_data) {
        while ($row = mysqli_fetch_assoc($q_data)) {
            $program_list[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Unggulan - Admin SMAN 21 Jakarta</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome 6 Icons -->
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

    <!-- SIDEBAR NAVIGASI (Disesuaikan Relative Path ke Root Admin) -->
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

                    <!-- Akademik Dropdown (AK TIF) -->
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg bg-blue-600 text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-graduation-cap w-5 text-center text-white"></i>
                                Akademik
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-white group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="program.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Program Pendidikan</a>
                            <a href="program-unggulan.php" class="block py-1.5 px-2 rounded font-semibold text-blue-300 bg-slate-800">Program Unggulan</a>
                            <a href="mata-pelajaran.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Mata Pelajaran</a>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Kelola Program Unggulan</h2>
                    <p class="text-xs text-slate-500">Manajemen Inovasi, Kurikulum Khusus & Program Prioritas Sekolah</p>
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

            <!-- ALERT NOTIFIKASI -->
            <?php if (!empty($message)): ?>
                <div class="p-4 rounded-xl text-sm font-medium shadow-sm flex items-center justify-between <?= $message_type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
                    <div class="flex items-center gap-3">
                        <i class="<?= $message_type === 'success' ? 'fa-solid fa-circle-check text-emerald-600 text-lg' : 'fa-solid fa-circle-exclamation text-rose-600 text-lg' ?>"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
                </div>
            <?php endif; ?>

            <!-- STATISTIK RINGKAS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Program</p>
                        <h4 class="text-3xl font-extrabold text-slate-800 mt-1"><?= $total_program ?></h4>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-award"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Status Aktif</p>
                        <h4 class="text-3xl font-extrabold text-emerald-600 mt-1"><?= $total_aktif ?></h4>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Non-Aktif / Draft</p>
                        <h4 class="text-3xl font-extrabold text-slate-400 mt-1"><?= $total_nonaktif ?></h4>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-eye-slash"></i>
                    </div>
                </div>
            </div>

            <!-- ACTION BAR: SEARCH, FILTER & BUTTON TAMBAH -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row gap-4 items-center justify-between">
                
                <form method="GET" class="flex flex-col sm:flex-row gap-3 w-full md:w-auto flex-1">
                    <div class="relative flex-1 max-w-md">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari judul program atau deskripsi..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                    </div>

                    <select name="status" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">
                        <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="1" <?= $filter_status === '1' ? 'selected' : '' ?>>Hanya Aktif</option>
                        <option value="0" <?= $filter_status === '0' ? 'selected' : '' ?>>Hanya Non-Aktif</option>
                    </select>

                    <?php if (!empty($search) || $filter_status !== 'all'): ?>
                        <a href="program-unggulan.php" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-medium rounded-xl transition flex items-center justify-center gap-1">
                            <i class="fa-solid fa-rotate-left text-[10px]"></i> Reset
                        </a>
                    <?php endif; ?>
                </form>

                <button onclick="openAddModal()" class="w-full md:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 flex-shrink-0">
                    <i class="fa-solid fa-plus"></i> Tambah Program Unggulan
                </button>
            </div>

            <!-- TABEL LIST PROGRAM UNGGULAN -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-800 text-base">Daftar Program Unggulan</h3>
                        <p class="text-xs text-slate-500">Menampilkan seluruh daftar program unggulan yang terdaftar di database</p>
                    </div>
                    <span class="text-xs bg-slate-100 font-semibold px-2.5 py-1 rounded-md text-slate-600"><?= count($program_list) ?> Data Ditampilkan</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4 text-center w-12">Urutan</th>
                                <th class="py-3.5 px-4 text-center w-16">Ikon</th>
                                <th class="py-3.5 px-4 w-24">Gambar</th>
                                <th class="py-3.5 px-4 min-w-[200px]">Program Unggulan</th>
                                <th class="py-3.5 px-4">Deskripsi</th>
                                <th class="py-3.5 px-4 text-center w-28">Status</th>
                                <th class="py-3.5 px-4 text-center w-36">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($program_list)): ?>
                                <?php foreach ($program_list as $item): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        
                                        <!-- Urutan -->
                                        <td class="py-4 px-4 text-center">
                                            <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 font-bold inline-flex items-center justify-center text-xs">
                                                <?= (int)$item['urutan'] ?>
                                            </span>
                                        </td>

                                        <!-- Ikon -->
                                        <td class="py-4 px-4 text-center">
                                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-base shadow-sm mx-auto">
                                                <i class="<?= !empty($item['ikon']) ? htmlspecialchars($item['ikon']) : 'fa-solid fa-star' ?>"></i>
                                            </div>
                                        </td>

                                        <!-- Gambar -->
                                        <td class="py-4 px-4">
                                            <?php if (!empty($item['gambar']) && file_exists($upload_dir . $item['gambar'])): ?>
                                                <img src="<?= $upload_dir . htmlspecialchars($item['gambar']) ?>" alt="Banner" class="w-16 h-11 object-cover rounded-lg border border-slate-200 shadow-sm">
                                            <?php else: ?>
                                                <div class="w-16 h-11 bg-slate-100 rounded-lg border border-slate-200 flex flex-col items-center justify-center text-slate-400 text-[10px]">
                                                    <i class="fa-regular fa-image mb-0.5"></i>
                                                    <span>No Image</span>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Judul -->
                                        <td class="py-4 px-4 font-semibold text-slate-800">
                                            <?= htmlspecialchars($item['judul']) ?>
                                        </td>

                                        <!-- Deskripsi -->
                                        <td class="py-4 px-4 text-slate-500 max-w-xs leading-relaxed">
                                            <?= !empty($item['deskripsi']) ? nl2br(htmlspecialchars(mb_strimwidth($item['deskripsi'], 0, 110, "..."))) : '<span class="italic text-slate-300">Tidak ada deskripsi</span>' ?>
                                        </td>

                                        <!-- Status Aktif -->
                                        <td class="py-4 px-4 text-center">
                                            <?php if ($item['status_aktif'] == 1): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-200 text-slate-600">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Non-Aktif
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Aksi Button -->
                                        <td class="py-4 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                
                                                <!-- Toggle Status -->
                                                <a href="program-unggulan.php?action=toggle&id=<?= $item['id'] ?>" title="Ubah Status" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition">
                                                    <i class="fa-solid fa-power-off text-xs"></i>
                                                </a>

                                                <!-- Edit -->
                                                <button onclick='openEditModal(<?= json_encode($item) ?>)' title="Edit Data" class="p-2 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 transition">
                                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                                </button>

                                                <!-- Hapus -->
                                                <a href="program-unggulan.php?action=delete&id=<?= $item['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus program unggulan ini?')" title="Hapus Data" class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 transition">
                                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                                </a>

                                            </div>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400 italic">
                                        <i class="fa-solid fa-folder-open text-3xl mb-2 block text-slate-300"></i>
                                        Belum ada data program unggulan yang tersimpan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- MODAL FORM TAMBAH / EDIT PROGRAM UNGGULAN -->
    <div id="modalForm" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl overflow-hidden transform transition-all">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-sm font-bold">
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <h3 id="modalTitle" class="font-bold text-base">Tambah Program Unggulan</h3>
                </div>
                <button onclick="closeModal()" class="text-slate-400 hover:text-white text-lg"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <!-- Modal Body / Form -->
            <form id="programForm" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="">
                <input type="hidden" name="gambar_lama" id="formGambarLama" value="">

                <!-- Judul Program -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Judul Program Unggulan <span class="text-rose-500">*</span></label>
                    <input type="text" name="judul" id="inputJudul" required placeholder="Contoh: Digital Literacy & Coding Class" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition font-medium">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Class Ikon (FontAwesome) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Ikon FontAwesome</label>
                        <div class="relative">
                            <input type="text" name="ikon" id="inputIkon" placeholder="fa-solid fa-laptop-code" class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition font-mono">
                            <i id="ikonPreview" class="fa-solid fa-star absolute left-3 top-1/2 -translate-y-1/2 text-blue-600 text-sm"></i>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Gunakan kode icon FontAwesome v6</p>
                    </div>

                    <!-- Urutan Tampil -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Urutan Tampil</label>
                        <input type="number" name="urutan" id="inputUrutan" value="1" min="1" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition font-medium">
                    </div>
                </div>

                <!-- Deskripsi Program -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Deskripsi Singkat</label>
                    <textarea name="deskripsi" id="inputDeskripsi" rows="3" placeholder="Jelaskan secara ringkas mengenai keunggulan program ini..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"></textarea>
                </div>

                <!-- Status Aktif & File Gambar -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status Publikasi</label>
                        <select name="status_aktif" id="inputStatus" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">
                            <option value="1">Aktif (Tampilkan)</option>
                            <option value="0">Non-Aktif (Sembunyikan)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Foto / Banner</label>
                        <input type="file" name="gambar" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition shadow-md">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPTS LEAF LOGIC & INTERACTION -->
    <script>
        // Sidebar Responsive Toggle
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('-ml-64');
        });

        // Live Clock Dynamic
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').textContent = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateClock, 1000);

        // Preview Dynamic Icon Input
        document.getElementById('inputIkon').addEventListener('input', function() {
            const preview = document.getElementById('ikonPreview');
            preview.className = (this.value.trim() !== '' ? this.value.trim() : 'fa-solid fa-star') + ' absolute left-3 top-1/2 -translate-y-1/2 text-blue-600 text-sm';
        });

        // Modal Handlers
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Program Unggulan';
            document.getElementById('formAction').value = 'add';
            document.getElementById('formId').value = '';
            document.getElementById('formGambarLama').value = '';
            
            document.getElementById('inputJudul').value = '';
            document.getElementById('inputDeskripsi').value = '';
            document.getElementById('inputIkon').value = 'fa-solid fa-star';
            document.getElementById('inputUrutan').value = '1';
            document.getElementById('inputStatus').value = '1';
            document.getElementById('ikonPreview').className = 'fa-solid fa-star absolute left-3 top-1/2 -translate-y-1/2 text-blue-600 text-sm';

            document.getElementById('modalForm').classList.remove('hidden');
        }

        function openEditModal(data) {
            document.getElementById('modalTitle').textContent = 'Edit Program Unggulan';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formId').value = data.id;
            document.getElementById('formGambarLama').value = data.gambar || '';

            document.getElementById('inputJudul').value = data.judul || '';
            document.getElementById('inputDeskripsi').value = data.deskripsi || '';
            document.getElementById('inputIkon').value = data.ikon || 'fa-solid fa-star';
            document.getElementById('inputUrutan').value = data.urutan || 1;
            document.getElementById('inputStatus').value = data.status_aktif;
            document.getElementById('ikonPreview').className = (data.ikon || 'fa-solid fa-star') + ' absolute left-3 top-1/2 -translate-y-1/2 text-blue-600 text-sm';

            document.getElementById('modalForm').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('modalForm').classList.add('hidden');
        }
    </script>
</body>
</html>
<?php
// admin/alumni/index.php

// Tampilkan error PHP untuk mempermudah debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Cek autentikasi login (Sesuaikan dengan sistem autentikasi Anda)
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['username'])) {
    // header('Location: ../login.php');
    // exit;
}

$admin_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'admin21';

// -----------------------------------------------------------------------------
// KONFIGURASI DATABASE BYETHOST & KONEKSI
// -----------------------------------------------------------------------------
$host = 'sql305.byethost3.com';
$user = 'b3_43046986';
$pass = 'J@karta5';
$db   = 'b3_43046986_sman21jkt';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect($host, $user, $pass, $db);

// Set Utf-8 Charset
if ($conn) {
    mysqli_set_charset($conn, "utf8mb4");
}

$logo_url = "https://sman21jkt.sch.id/wp-content/uploads/2025/02/1000305388-removebg-preview_image_repair_1740014356410.jpg";

// Message Handler
$msg = '';
$msg_type = ''; // success | error

// Folder Penyimpanan Foto Alumni
$upload_dir = '../../uploads/alumni/';
if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// -----------------------------------------------------------------------------
// PROCESS ACTIONS (CREATE / UPDATE / DELETE / TOGGLE STATUS)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- TAMBAH / EDIT ALUMNI ---
    if ($action === 'save') {
        $id                   = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $nama_alumni          = mysqli_real_escape_string($conn, trim($_POST['nama_alumni']));
        $tahun_lulus          = intval($_POST['tahun_lulus']);
        $jurusan_program      = mysqli_real_escape_string($conn, trim($_POST['jurusan_program']));
        $universitas_instansi = mysqli_real_escape_string($conn, trim($_POST['universitas_instansi']));
        $profesi              = mysqli_real_escape_string($conn, trim($_POST['profesi']));
        $cerita               = mysqli_real_escape_string($conn, trim($_POST['cerita']));
        $prestasi             = mysqli_real_escape_string($conn, trim($_POST['prestasi']));
        $status_tampil        = isset($_POST['status_tampil']) ? 1 : 0;

        // Handle File Upload
        $foto_name = $_POST['existing_foto'] ?? '';
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $file_tmp  = $_FILES['foto']['tmp_name'];
            $original_name = $_FILES['foto']['name'];
            $file_ext  = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($file_ext, $allowed_ext)) {
                $new_filename = 'alumni_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                $destination  = $upload_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $destination)) {
                    // Hapus foto lama jika ada
                    if (!empty($foto_name) && file_exists($upload_dir . $foto_name)) {
                        @unlink($upload_dir . $foto_name);
                    }
                    $foto_name = $new_filename;
                }
            } else {
                $msg = "Format file foto tidak didukung. Harap upload format JPG, PNG, atau WEBP.";
                $msg_type = "error";
            }
        }

        if (empty($msg)) {
            if ($id > 0) {
                // Update
                $sql = "UPDATE alumni SET 
                            nama_alumni = '$nama_alumni',
                            tahun_lulus = '$tahun_lulus',
                            jurusan_program = '$jurusan_program',
                            foto = '$foto_name',
                            universitas_instansi = '$universitas_instansi',
                            profesi = '$profesi',
                            cerita = '$cerita',
                            prestasi = '$prestasi',
                            status_tampil = '$status_tampil'
                        WHERE id = $id";
                if (mysqli_query($conn, $sql)) {
                    $msg = "Data alumni berhasil diperbarui!";
                    $msg_type = "success";
                } else {
                    $msg = "Gagal memperbarui data: " . mysqli_error($conn);
                    $msg_type = "error";
                }
            } else {
                // Insert
                $sql = "INSERT INTO alumni (nama_alumni, tahun_lulus, jurusan_program, foto, universitas_instansi, profesi, cerita, prestasi, status_tampil)
                        VALUES ('$nama_alumni', '$tahun_lulus', '$jurusan_program', '$foto_name', '$universitas_instansi', '$profesi', '$cerita', '$prestasi', '$status_tampil')";
                if (mysqli_query($conn, $sql)) {
                    $msg = "Data alumni baru berhasil ditambahkan!";
                    $msg_type = "success";
                } else {
                    $msg = "Gagal menambahkan data: " . mysqli_error($conn);
                    $msg_type = "error";
                }
            }
        }
    }

    // --- HAPUS ALUMNI ---
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        // Cari foto untuk dihapus
        $q_foto = mysqli_query($conn, "SELECT foto FROM alumni WHERE id = $id");
        if ($q_foto && $r_foto = mysqli_fetch_assoc($q_foto)) {
            if (!empty($r_foto['foto']) && file_exists($upload_dir . $r_foto['foto'])) {
                @unlink($upload_dir . $r_foto['foto']);
            }
        }

        $sql = "DELETE FROM alumni WHERE id = $id";
        if (mysqli_query($conn, $sql)) {
            $msg = "Data alumni berhasil dihapus!";
            $msg_type = "success";
        } else {
            $msg = "Gagal menghapus data: " . mysqli_error($conn);
            $msg_type = "error";
        }
    }

    // --- TOGGLE STATUS TAMPIL ---
    if ($action === 'toggle_status') {
        $id = intval($_POST['id']);
        $current_status = intval($_POST['current_status']);
        $new_status = $current_status === 1 ? 0 : 1;

        $sql = "UPDATE alumni SET status_tampil = $new_status WHERE id = $id";
        if (mysqli_query($conn, $sql)) {
            $msg = "Status visibilitas alumni berhasil diubah!";
            $msg_type = "success";
        } else {
            $msg = "Gagal mengubah status: " . mysqli_error($conn);
            $msg_type = "error";
        }
    }
}

// -----------------------------------------------------------------------------
// FILTER & QUERY DATA ALUMNI
// -----------------------------------------------------------------------------
$search     = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$filter_thn = isset($_GET['tahun']) ? intval($_GET['tahun']) : 0;

$where_clauses = [];
if (!empty($search)) {
    $where_clauses[] = "(nama_alumni LIKE '%$search%' OR universitas_instansi LIKE '%$search%' OR profesi LIKE '%$search%' OR jurusan_program LIKE '%$search%')";
}
if ($filter_thn > 0) {
    $where_clauses[] = "tahun_lulus = $filter_thn";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(' AND ', $where_clauses);
}

// Get Total Records
$total_rows = 0;
if ($conn) {
    $res_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM alumni $where_sql");
    if ($res_count) {
        $total_rows = mysqli_fetch_assoc($res_count)['total'];
    }
}

// Pagination
$limit  = 10;
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_rows / $limit);

// Fetch Data
$alumni_list = [];
if ($conn) {
    $query = "SELECT * FROM alumni $where_sql ORDER BY tahun_lulus DESC, id DESC LIMIT $limit OFFSET $offset";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $alumni_list[] = $row;
        }
    }
}

// Get Distinct Years for Filter Dropdown
$tahun_options = [];
if ($conn) {
    $q_thn = mysqli_query($conn, "SELECT DISTINCT tahun_lulus FROM alumni ORDER BY tahun_lulus DESC");
    if ($q_thn) {
        while ($r_thn = mysqli_fetch_assoc($q_thn)) {
            $tahun_options[] = $r_thn['tahun_lulus'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data Alumni - SMAN 21 Jakarta</title>
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

    <!-- SIDEBAR NAVIGASI PRESISI SESUAI DASHBOARD.PHP -->
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

                    <!-- Active Menu Alumni -->
                    <a href="index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-blue-600 text-white font-medium text-sm transition-all shadow-sm">
                        <i class="fa-solid fa-user-graduate w-5 text-center text-base"></i>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Kelola Data Alumni</h2>
                    <p class="text-xs text-slate-500">Manajemen Rekam Jejak, Studi Lanjut, & Kirip Alumni SMAN 21 Jakarta</p>
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
                <div class="p-4 rounded-xl border flex items-center justify-between transition-all shadow-sm <?= $msg_type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid <?= $msg_type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-rose-600' ?> text-lg"></i>
                        <span class="text-xs font-semibold"><?= htmlspecialchars($msg) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 text-sm">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- ACTION & FILTER BAR -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                
                <form method="GET" class="flex flex-wrap items-center gap-3 flex-1">
                    <div class="relative flex-1 min-w-[220px]">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama, PTN/Instansi, profesi..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 focus:outline-none focus:border-blue-500 focus:bg-white transition">
                    </div>

                    <select name="tahun" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 focus:outline-none focus:border-blue-500 transition">
                        <option value="0">Semua Angkatan (Tahun)</option>
                        <?php foreach ($tahun_options as $th): ?>
                            <option value="<?= $th ?>" <?= $filter_thn == $th ? 'selected' : '' ?>>Lulusan <?= $th ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-xl transition shadow-sm flex items-center gap-2">
                        <i class="fa-solid fa-filter text-[11px]"></i> Filter
                    </button>

                    <?php if (!empty($search) || $filter_thn > 0): ?>
                        <a href="index.php" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-medium rounded-xl transition">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <button onclick="openModalAdd()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-md hover:shadow-lg flex items-center gap-2 justify-center">
                    <i class="fa-solid fa-user-plus"></i> Tambah Data Alumni
                </button>
            </div>

            <!-- TABEL DATA ALUMNI -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Daftar Rekam Jejak Alumni</h3>
                        <p class="text-xs text-slate-500">Menampilkan <?= count($alumni_list) ?> dari total <?= $total_rows ?> alumni terdaftar</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50/80 text-slate-500 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4 w-12 text-center">#</th>
                                <th class="py-3.5 px-4">Alumni</th>
                                <th class="py-3.5 px-4">Angkatan & Program</th>
                                <th class="py-3.5 px-4">Studi Lanjut / Instansi</th>
                                <th class="py-3.5 px-4">Profesi Saat Ini</th>
                                <th class="py-3.5 px-4 text-center">Tampil di Web</th>
                                <th class="py-3.5 px-4 text-center w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($alumni_list)): ?>
                                <?php foreach ($alumni_list as $index => $a): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-3.5 px-4 text-center font-medium text-slate-400">
                                            <?= $offset + $index + 1 ?>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-slate-100 overflow-hidden flex-shrink-0 border border-slate-200">
                                                    <?php if (!empty($a['foto']) && file_exists($upload_dir . $a['foto'])): ?>
                                                        <img src="../../uploads/alumni/<?= htmlspecialchars($a['foto']) ?>" alt="<?= htmlspecialchars($a['nama_alumni']) ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center bg-slate-200 text-slate-500 font-bold text-xs">
                                                            <?= strtoupper(substr($a['nama_alumni'], 0, 1)) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-slate-800 text-xs"><?= htmlspecialchars($a['nama_alumni']) ?></p>
                                                    <?php if(!empty($a['prestasi'])): ?>
                                                        <p class="text-[10px] text-amber-600 font-medium truncate max-w-[180px]">
                                                            <i class="fa-solid fa-award mr-1"></i><?= htmlspecialchars($a['prestasi']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                                Lulus <?= $a['tahun_lulus'] ?>
                                            </span>
                                            <p class="text-[11px] text-slate-500 mt-1"><?= htmlspecialchars($a['jurusan_program'] ?? '-') ?></p>
                                        </td>
                                        <td class="py-3.5 px-4 font-medium text-slate-700">
                                            <?= htmlspecialchars($a['universitas_instansi'] ?? '-') ?>
                                        </td>
                                        <td class="py-3.5 px-4 text-slate-600">
                                            <?= htmlspecialchars($a['profesi'] ?? '-') ?>
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <form method="POST" class="inline-block">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                                <input type="hidden" name="current_status" value="<?= $a['status_tampil'] ?>">
                                                <button type="submit" title="Klik untuk mengubah status" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold transition <?= $a['status_tampil'] == 1 ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' ?>">
                                                    <span class="w-1.5 h-1.5 rounded-full <?= $a['status_tampil'] == 1 ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
                                                    <?= $a['status_tampil'] == 1 ? 'Tampil' : 'Sembunyi' ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button onclick="openModalEdit(<?= htmlspecialchars(json_encode($a)) ?>)" class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-500 hover:text-white transition flex items-center justify-center text-xs" title="Edit Alumni">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <button onclick="openModalDelete(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['nama_alumni'])) ?>')" class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition flex items-center justify-center text-xs" title="Hapus Alumni">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400 italic">
                                        <i class="fa-solid fa-user-slash text-3xl mb-3 text-slate-300 block"></i>
                                        Tidak ada data alumni yang ditemukan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION BAR -->
                <?php if ($total_pages > 1): ?>
                    <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs bg-slate-50/50">
                        <span class="text-slate-500">Halaman <?= $page ?> dari <?= $total_pages ?></span>
                        <div class="flex items-center gap-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&tahun=<?= $filter_thn ?>" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 rounded-lg transition font-medium">Prev</a>
                            <?php endif; ?>
                            
                            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                <a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&tahun=<?= $filter_thn ?>" class="px-3 py-1.5 rounded-lg border font-semibold transition <?= $p == $page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-100' ?>">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&tahun=<?= $filter_thn ?>" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 rounded-lg transition font-medium">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        </main>
    </div>

    <!-- MODAL FORM TAMBAH / EDIT ALUMNI -->
    <div id="modalForm" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                    <div>
                        <h3 id="modalTitle" class="text-base font-bold text-slate-800">Tambah Data Alumni</h3>
                        <p class="text-xs text-slate-500">Lengkapi formulir profil lulusan sekolah</p>
                    </div>
                </div>
                <button onclick="closeModalForm()" class="text-slate-400 hover:text-slate-600 text-lg transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-6 space-y-4 text-xs">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="alumni_id" value="0">
                <input type="hidden" name="existing_foto" id="existing_foto" value="">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Alumni <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama_alumni" id="nama_alumni" required placeholder="Contoh: Ahmad Subagja, S.T." class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white transition">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tahun Lulus (Angkatan) <span class="text-rose-500">*</span></label>
                        <input type="number" name="tahun_lulus" id="tahun_lulus" required min="1970" max="2099" value="<?= date('Y') ?>" placeholder="2024" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Program / Jurusan Saat Sekolah</label>
                        <input type="text" name="jurusan_program" id="jurusan_program" placeholder="MIPA 1 / IPS 2 / Kurikulum Merdeka" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white transition">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Universitas / Perusahaan / Instansi</label>
                        <input type="text" name="universitas_instansi" id="universitas_instansi" placeholder="Universitas Indonesia / PT Telkom" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Profesi / Jabatan Saat Ini</label>
                        <input type="text" name="profesi" id="profesi" placeholder="Software Engineer / Mahasiswa / Dokter" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white transition">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Foto Profil Alumni</label>
                        <input type="file" name="foto" id="foto_input" accept="image/*" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600 file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition">
                        <span class="text-[10px] text-slate-400 mt-1 block">Format: JPG, PNG, WEBP (Maks. 2MB)</span>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Prestasi atau Kehormatan (Opsional)</label>
                    <input type="text" name="prestasi" id="prestasi" placeholder="Lulusan Terbaik SNBP 2024 / Juara OSN Fisika" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white transition">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kesan, Pesan & Cerita Alumni</label>
                    <textarea name="cerita" id="cerita" rows="3" placeholder="Tuliskan pengalaman berkesan selama bersekolah di SMAN 21 Jakarta..." class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white transition"></textarea>
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="status_tampil" id="status_tampil" value="1" checked class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                        <span class="text-xs font-semibold text-slate-700">Tampilkan data alumni ini di Halaman Depan Portal Website</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModalForm()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition shadow-md">
                        Simpan Data Alumni
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL CONFIRM DELETE -->
    <div id="modalDelete" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-md p-6 text-center space-y-4">
            <div class="w-14 h-14 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-2xl mx-auto">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-800">Konfirmasi Hapus Alumni</h3>
                <p class="text-xs text-slate-500 mt-1">Apakah Anda yakin ingin menghapus data alumni <span id="del_nama" class="font-bold text-slate-800"></span>? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <form method="POST" class="flex items-center justify-center gap-3 pt-2">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="del_id" value="0">
                <button type="button" onclick="closeModalDelete()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs transition shadow-md">
                    Ya, Hapus Sekarang
                </button>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC MODALS & SIDEBAR -->
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

        // Modal Form Handlers
        function openModalAdd() {
            document.getElementById('modalTitle').textContent = 'Tambah Data Alumni Baru';
            document.getElementById('alumni_id').value = '0';
            document.getElementById('existing_foto').value = '';
            document.getElementById('nama_alumni').value = '';
            document.getElementById('tahun_lulus').value = new Date().getFullYear();
            document.getElementById('jurusan_program').value = '';
            document.getElementById('universitas_instansi').value = '';
            document.getElementById('profesi').value = '';
            document.getElementById('prestasi').value = '';
            document.getElementById('cerita').value = '';
            document.getElementById('status_tampil').checked = true;
            document.getElementById('modalForm').classList.remove('hidden');
        }

        function openModalEdit(data) {
            document.getElementById('modalTitle').textContent = 'Edit Data Alumni';
            document.getElementById('alumni_id').value = data.id;
            document.getElementById('existing_foto').value = data.foto || '';
            document.getElementById('nama_alumni').value = data.nama_alumni || '';
            document.getElementById('tahun_lulus').value = data.tahun_lulus || new Date().getFullYear();
            document.getElementById('jurusan_program').value = data.jurusan_program || '';
            document.getElementById('universitas_instansi').value = data.universitas_instansi || '';
            document.getElementById('profesi').value = data.profesi || '';
            document.getElementById('prestasi').value = data.prestasi || '';
            document.getElementById('cerita').value = data.cerita || '';
            document.getElementById('status_tampil').checked = data.status_tampil == 1;
            document.getElementById('modalForm').classList.remove('hidden');
        }

        function closeModalForm() {
            document.getElementById('modalForm').classList.add('hidden');
        }

        // Modal Delete Handlers
        function openModalDelete(id, nama) {
            document.getElementById('del_id').value = id;
            document.getElementById('del_nama').textContent = nama;
            document.getElementById('modalDelete').classList.remove('hidden');
        }

        function closeModalDelete() {
            document.getElementById('modalDelete').classList.add('hidden');
        }
    </script>
</body>
</html>
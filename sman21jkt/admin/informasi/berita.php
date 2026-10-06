<?php
// admin/informasi/berita.php

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

// Nonaktifkan strict mode exception agar koneksi gagal tidak langsung crash
mysqli_report(MYSQLI_REPORT_OFF);

// Koneksi ke database
$conn = @mysqli_connect($host, $user, $pass, $db);

// Notifikasi Flash
$message = '';
$message_type = '';

// Helper Function: Membuat Slug URL
function generate_slug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

// -----------------------------------------------------------------------------
// PROSES FORM (CREATE, UPDATE, DELETE)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    
    // --- TAMBAH / EDIT BERITA ---
    if (isset($_POST['save_berita'])) {
        $id                = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $judul             = mysqli_real_escape_string($conn, trim($_POST['judul']));
        $slug              = !empty($_POST['slug']) ? generate_slug($_POST['slug']) : generate_slug($judul);
        $penulis           = mysqli_real_escape_string($conn, trim($_POST['penulis']));
        $kategori          = mysqli_real_escape_string($conn, trim($_POST['kategori']));
        $status            = mysqli_real_escape_string($conn, $_POST['status']);
        $tanggal_publikasi = !empty($_POST['tanggal_publikasi']) ? $_POST['tanggal_publikasi'] : date('Y-m-d H:i:s');
        $ringkasan         = mysqli_real_escape_string($conn, trim($_POST['ringkasan']));
        $isi               = mysqli_real_escape_string($conn, trim($_POST['isi']));

        // Handling File Upload Gambar
        $gambar_utama = $_POST['existing_gambar'] ?? '';
        $thumbnail    = $_POST['existing_thumbnail'] ?? '';

        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $file_tmp  = $_FILES['gambar']['tmp_name'];
            $file_name = $_FILES['gambar']['name'];
            $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed   = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $allowed)) {
                $upload_dir = '../../uploads/berita/';
                if (!is_dir($upload_dir)) {
                    @mkdir($upload_dir, 0777, true);
                }

                $new_filename = 'berita_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $target_file  = $upload_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $target_file)) {
                    $gambar_utama = 'uploads/berita/' . $new_filename;
                    $thumbnail    = 'uploads/berita/' . $new_filename;
                }
            }
        }

        if ($id > 0) {
            // UPDATE BERITA
            $query_update = "UPDATE berita SET 
                judul = '$judul', 
                slug = '$slug', 
                ringkasan = '$ringkasan', 
                isi = '$isi', 
                gambar_utama = '$gambar_utama', 
                thumbnail = '$thumbnail', 
                penulis = '$penulis', 
                kategori = '$kategori', 
                tanggal_publikasi = '$tanggal_publikasi', 
                status = '$status' 
                WHERE id = $id";

            if (mysqli_query($conn, $query_update)) {
                $message = "Berita berhasil diperbarui!";
                $message_type = "success";
            } else {
                $message = "Gagal memperbarui berita: " . mysqli_error($conn);
                $message_type = "danger";
            }
        } else {
            // INSERT BERITA BARU
            $query_insert = "INSERT INTO berita 
                (judul, slug, ringkasan, isi, thumbnail, gambar_utama, penulis, kategori, tanggal_publikasi, status, jumlah_dilihat) 
                VALUES 
                ('$judul', '$slug', '$ringkasan', '$isi', '$thumbnail', '$gambar_utama', '$penulis', '$kategori', '$tanggal_publikasi', '$status', 0)";

            if (mysqli_query($conn, $query_insert)) {
                $message = "Berita baru berhasil ditambahkan!";
                $message_type = "success";
            } else {
                $message = "Gagal menambahkan berita: " . mysqli_error($conn);
                $message_type = "danger";
            }
        }
    }

    // --- HAPUS BERITA ---
    if (isset($_POST['delete_berita'])) {
        $delete_id = intval($_POST['delete_id']);
        $query_del = "DELETE FROM berita WHERE id = $delete_id";
        if (mysqli_query($conn, $query_del)) {
            $message = "Berita berhasil dihapus!";
            $message_type = "success";
        } else {
            $message = "Gagal menghapus berita: " . mysqli_error($conn);
            $message_type = "danger";
        }
    }
}

// -----------------------------------------------------------------------------
// FILTER, SEARCH & STATISTIK
// -----------------------------------------------------------------------------
$search   = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$f_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, trim($_GET['status'])) : '';
$f_kat    = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, trim($_GET['kategori'])) : '';

$where_clauses = [];
if (!empty($search)) {
    $where_clauses[] = "(judul LIKE '%$search%' OR isi LIKE '%$search%' OR penulis LIKE '%$search%')";
}
if (!empty($f_status)) {
    $where_clauses[] = "status = '$f_status'";
}
if (!empty($f_kat)) {
    $where_clauses[] = "kategori = '$f_kat'";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : '';

// Query Data Berita
$berita_list = [];
if ($conn) {
    $q_berita = @mysqli_query($conn, "SELECT * FROM berita $where_sql ORDER BY id DESC");
    if ($q_berita) {
        while ($row = mysqli_fetch_assoc($q_berita)) {
            $berita_list[] = $row;
        }
    }
}

// Stats Ringkasan
$total_all = 0; $total_pub = 0; $total_draft = 0; $total_views = 0;
if ($conn) {
    $q_stat = @mysqli_query($conn, "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status='published' THEN 1 ELSE 0 END) as published,
        SUM(CASE WHEN status='draft' THEN 1 ELSE 0 END) as draft,
        SUM(jumlah_dilihat) as total_views
        FROM berita");
    if ($q_stat && $r_stat = mysqli_fetch_assoc($q_stat)) {
        $total_all   = $r_stat['total'] ?? 0;
        $total_pub   = $r_stat['published'] ?? 0;
        $total_draft = $r_stat['draft'] ?? 0;
        $total_views = $r_stat['total_views'] ?? 0;
    }
}

// Kategori unik untuk dropdown filter
$kategori_options = ['Umum', 'Akademik', 'Pengumuman', 'Kegiatan', 'Prestasi', 'Beasiswa'];

$logo_url = "https://sman21jkt.sch.id/wp-content/uploads/2025/02/1000305388-removebg-preview_image_repair_1740014356410.jpg";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Berita & Pengumuman - SMAN 21 Jakarta</title>
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

                    <!-- Informasi Dropdown (Active Menu) -->
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3 text-white">
                                <i class="fa-solid fa-newspaper w-5 text-center text-blue-400"></i>
                                Informasi
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="berita.php" class="block py-1.5 px-2 rounded bg-blue-600 text-white font-medium shadow-sm">Berita & Pengumuman</a>
                            <a href="agenda.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Agenda Kalender</a>
                            <a href="ppdb.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Informasi PPDB</a>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Kelola Berita & Pengumuman</h2>
                    <p class="text-xs text-slate-500">Manajemen Publikasi Artikel, Informasi & Pengumuman Sekolah</p>
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
            <?php if (!empty($message)): ?>
                <div class="p-4 rounded-xl text-xs font-semibold border flex items-center justify-between transition-all <?= $message_type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?>">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-rose-600' ?> text-base"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- STATISTIK RINGKASAN BERITA -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Artikel</p>
                        <h4 class="text-2xl font-extrabold text-slate-800 mt-1"><?= $total_all ?></h4>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-newspaper"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Terpublikasi</p>
                        <h4 class="text-2xl font-extrabold text-emerald-600 mt-1"><?= $total_pub ?></h4>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-globe"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Draf Disimpan</p>
                        <h4 class="text-2xl font-extrabold text-amber-600 mt-1"><?= $total_draft ?></h4>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-file-pen"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Pembaca</p>
                        <h4 class="text-2xl font-extrabold text-purple-600 mt-1"><?= number_format($total_views) ?></h4>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-eye"></i>
                    </div>
                </div>
            </div>

            <!-- PANEL AKSI, FILTER & PENCARIAN -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    
                    <!-- Tombol Tambah Berita -->
                    <button onclick="openModal('add')" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-md flex items-center justify-center gap-2">
                        <i class="fa-solid fa-plus"></i> Tambah Berita Baru
                    </button>

                    <!-- Form Filter & Pencarian -->
                    <form method="GET" action="berita.php" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari berita..." class="px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 w-full sm:w-48">

                        <select name="kategori" class="px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">-- Semua Kategori --</option>
                            <?php foreach ($kategori_options as $kat): ?>
                                <option value="<?= $kat ?>" <?= $f_kat === $kat ? 'selected' : '' ?>><?= $kat ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select name="status" class="px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">-- Semua Status --</option>
                            <option value="published" <?= $f_status === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= $f_status === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="archived" <?= $f_status === 'archived' ? 'selected' : '' ?>>Archived</option>
                        </select>

                        <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium rounded-xl transition">
                            <i class="fa-solid fa-filter"></i> Filter
                        </button>

                        <?php if (!empty($search) || !empty($f_status) || !empty($f_kat)): ?>
                            <a href="berita.php" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-medium rounded-xl transition">
                                Reset
                            </a>
                        <?php endif; ?>
                    </form>

                </div>
            </div>

            <!-- TABEL DATA BERITA -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4">Artikel / Berita</th>
                                <th class="py-3.5 px-4">Kategori</th>
                                <th class="py-3.5 px-4">Penulis</th>
                                <th class="py-3.5 px-4">Tanggal Publikasi</th>
                                <th class="py-3.5 px-4">Dilihat</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($berita_list)): ?>
                                <?php foreach ($berita_list as $row): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <!-- Judul & Thumbnail -->
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center gap-3 max-w-md">
                                                <?php if (!empty($row['gambar_utama'])): ?>
                                                    <img src="../../<?= htmlspecialchars($row['gambar_utama']) ?>" onerror="this.onerror=null; this.src='https://placehold.co/100x100?text=News';" class="w-12 h-12 rounded-lg object-cover flex-shrink-0 border border-slate-200">
                                                <?php else: ?>
                                                    <div class="w-12 h-12 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center font-bold text-lg flex-shrink-0">
                                                        <i class="fa-solid fa-newspaper"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="min-w-0">
                                                    <h5 class="font-bold text-slate-800 text-sm truncate leading-snug"><?= htmlspecialchars($row['judul']) ?></h5>
                                                    <p class="text-[11px] text-slate-400 truncate mt-0.5"><?= htmlspecialchars($row['slug']) ?></p>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Kategori -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <span class="px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 font-medium text-[11px]">
                                                <?= htmlspecialchars($row['kategori']) ?>
                                            </span>
                                        </td>

                                        <!-- Penulis -->
                                        <td class="py-3.5 px-4 whitespace-nowrap text-slate-700 font-medium">
                                            <i class="fa-regular fa-user mr-1 text-slate-400"></i>
                                            <?= htmlspecialchars($row['penulis']) ?>
                                        </td>

                                        <!-- Tanggal Publikasi -->
                                        <td class="py-3.5 px-4 whitespace-nowrap text-slate-500">
                                            <?= $row['tanggal_publikasi'] ? date('d M Y, H:i', strtotime($row['tanggal_publikasi'])) : '-' ?>
                                        </td>

                                        <!-- Jumlah Dilihat -->
                                        <td class="py-3.5 px-4 whitespace-nowrap font-semibold text-slate-700">
                                            <i class="fa-regular fa-eye text-slate-400 mr-1"></i>
                                            <?= number_format($row['jumlah_dilihat']) ?>
                                        </td>

                                        <!-- Status Badge -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <?php if ($row['status'] === 'published'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Published
                                                </span>
                                            <?php elseif ($row['status'] === 'draft'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Draft
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Archived
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Tombol Aksi -->
                                        <td class="py-3.5 px-4 whitespace-nowrap text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button onclick="editBerita(<?= htmlspecialchars(json_encode($row)) ?>)" class="p-2 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 transition" title="Edit Berita">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <button onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['judul'])) ?>')" class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 transition" title="Hapus Berita">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400 italic">
                                        <i class="fa-regular fa-folder-open text-3xl mb-2 block text-slate-300"></i>
                                        Belum ada data berita yang tersedia.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- MODAL FORM TAMBAH / EDIT BERITA -->
    <div id="beritaModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden border border-slate-100 my-auto">
            
            <!-- Header Modal -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <h3 id="modalTitle" class="font-bold text-base tracking-wide flex items-center gap-2">
                    <i class="fa-solid fa-newspaper text-blue-400"></i> Tambah Berita Baru
                </h3>
                <button onclick="closeModal()" class="p-1 text-slate-400 hover:text-white transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Body Form Modal -->
            <form method="POST" action="berita.php" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-6 space-y-5">
                <input type="hidden" name="id" id="form_id" value="0">
                <input type="hidden" name="existing_gambar" id="form_existing_gambar" value="">
                <input type="hidden" name="existing_thumbnail" id="form_existing_thumbnail" value="">

                <!-- Judul Berita -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Judul Berita <span class="text-rose-500">*</span></label>
                    <input type="text" name="judul" id="form_judul" required onkeyup="autoSlug()" placeholder="Contoh: SMAN 21 Jakarta Meraih Juara 1 Olimpiade Sains 2026" class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">
                </div>

                <!-- Slug & Kategori -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Slug URL</label>
                        <input type="text" name="slug" id="form_slug" placeholder="otomatis-dibuat-dari-judul" class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-slate-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kategori <span class="text-rose-500">*</span></label>
                        <select name="kategori" id="form_kategori" required class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <?php foreach ($kategori_options as $kat): ?>
                                <option value="<?= $kat ?>"><?= $kat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Penulis, Tanggal, & Status -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Penulis</label>
                        <input type="text" name="penulis" id="form_penulis" value="Admin SMAN 21" class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Publikasi</label>
                        <input type="datetime-local" name="tanggal_publikasi" id="form_tanggal_publikasi" class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Status Publikasi</label>
                        <select name="status" id="form_status" class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>

                <!-- Upload Gambar Utama -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Gambar Utama / Header</label>
                    <input type="file" name="gambar" accept="image/*" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <p class="text-[11px] text-slate-400 mt-1">Format: JPG, PNG, WEBP. Maksimal 2MB.</p>
                </div>

                <!-- Ringkasan Singkat -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Ringkasan / Resume Singkat</label>
                    <textarea name="ringkasan" id="form_ringkasan" rows="2" placeholder="Tuliskan 1-2 kalimat ringkasan berita..." class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <!-- Isi Berita Lengkap -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Isi Berita Lengkap <span class="text-rose-500">*</span></label>
                    <textarea name="isi" id="form_isi" rows="7" required placeholder="Tuliskan naskah/isi berita secara lengkap di sini..." class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <!-- Footer Submit -->
                <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-xs font-semibold transition">
                        Batal
                    </button>
                    <button type="submit" name="save_berita" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition shadow-md flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Berita
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS -->
    <div id="deleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 text-center shadow-2xl space-y-4 border border-slate-100">
            <div class="w-14 h-14 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-2xl mx-auto">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 text-base">Hapus Berita Ini?</h4>
                <p class="text-xs text-slate-500 mt-1">
                    Anda yakin ingin menghapus artikel <strong id="delete_title_text" class="text-slate-700"></strong>? Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>
            <form method="POST" action="berita.php" class="flex items-center justify-center gap-3 pt-2">
                <input type="hidden" name="delete_id" id="delete_id_input" value="0">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-xs font-semibold transition">
                    Batal
                </button>
                <button type="submit" name="delete_berita" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition shadow-md">
                    Ya, Hapus Sekarang
                </button>
            </form>
        </div>
    </div>

    <!-- SCRIPT RESPONSIF & INTERAKSI -->
    <script>
        // Toggle Sidebar
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('-ml-64');
        });

        // Dynamic Live Clock
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').textContent = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateClock, 1000);

        // Auto Generate Slug
        function autoSlug() {
            const judul = document.getElementById('form_judul').value;
            const slug = judul.toLowerCase()
                .replace(/[^a-z0-9 -]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
            document.getElementById('form_slug').value = slug;
        }

        // Open Modal Add / Edit
        function openModal(mode) {
            if (mode === 'add') {
                document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-plus text-blue-400"></i> Tambah Berita Baru';
                document.getElementById('form_id').value = '0';
                document.getElementById('form_judul').value = '';
                document.getElementById('form_slug').value = '';
                document.getElementById('form_penulis').value = 'Admin SMAN 21';
                document.getElementById('form_kategori').value = 'Umum';
                document.getElementById('form_status').value = 'published';
                document.getElementById('form_ringkasan').value = '';
                document.getElementById('form_isi').value = '';
                document.getElementById('form_existing_gambar').value = '';
                document.getElementById('form_existing_thumbnail').value = '';
                
                // Set default tanggal publikasi ke sekarang
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                document.getElementById('form_tanggal_publikasi').value = now.toISOString().slice(0,16);
            }
            document.getElementById('beritaModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('beritaModal').classList.add('hidden');
        }

        function editBerita(data) {
            document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square text-amber-400"></i> Edit Berita';
            document.getElementById('form_id').value = data.id;
            document.getElementById('form_judul').value = data.judul || '';
            document.getElementById('form_slug').value = data.slug || '';
            document.getElementById('form_penulis').value = data.penulis || 'Admin';
            document.getElementById('form_kategori').value = data.kategori || 'Umum';
            document.getElementById('form_status').value = data.status || 'published';
            document.getElementById('form_ringkasan').value = data.ringkasan || '';
            document.getElementById('form_isi').value = data.isi || '';
            document.getElementById('form_existing_gambar').value = data.gambar_utama || '';
            document.getElementById('form_existing_thumbnail').value = data.thumbnail || '';

            if (data.tanggal_publikasi) {
                const dateVal = data.tanggal_publikasi.replace(' ', 'T').slice(0,16);
                document.getElementById('form_tanggal_publikasi').value = dateVal;
            }

            document.getElementById('beritaModal').classList.remove('hidden');
        }

        // Delete Modal
        function confirmDelete(id, title) {
            document.getElementById('delete_id_input').value = id;
            document.getElementById('delete_title_text').textContent = title;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
</body>
</html>
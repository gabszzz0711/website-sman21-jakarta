<?php
// admin/kegiatan/ekstrakurikuler.php

// Tampilkan error PHP untuk mempermudah debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Cek autentikasi login
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['username'])) {
    // Jika belum login, redirect ke halaman login
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

// Nonaktifkan strict mode exception
mysqli_report(MYSQLI_REPORT_OFF);

// Koneksi ke database
$conn = @mysqli_connect($host, $user, $pass, $db);

// Helper function untuk generate slug
function create_slug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

// Direktori Upload Foto Ekskul
$upload_dir = '../../uploads/ekskul/';
if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// Notifikasi Flash
$message = '';
$message_type = '';

// -----------------------------------------------------------------------------
// PROCESS ACTION (ADD, EDIT, DELETE, TOGGLE STATUS)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'add') {
        $nama_ekskul  = mysqli_real_escape_string($conn, trim($_POST['nama_ekskul']));
        $slug         = !empty($_POST['slug']) ? create_slug($_POST['slug']) : create_slug($nama_ekskul);
        $deskripsi    = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));
        $pembina      = mysqli_real_escape_string($conn, trim($_POST['pembina']));
        $jadwal       = mysqli_real_escape_string($conn, trim($_POST['jadwal']));
        $lokasi       = mysqli_real_escape_string($conn, trim($_POST['lokasi']));
        $kontak       = mysqli_real_escape_string($conn, trim($_POST['kontak']));
        $urutan       = (int)$_POST['urutan'];
        $status_aktif = isset($_POST['status_aktif']) ? (int)$_POST['status_aktif'] : 1;

        // Process Upload Logo / Foto
        $logo_foto = '';
        if (isset($_FILES['logo_foto']) && $_FILES['logo_foto']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['logo_foto']['name'], PATHINFO_EXTENSION);
            $filename = 'ekskul_' . time() . '_' . rand(100, 999) . '.' . strtolower($ext);
            $target_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['logo_foto']['tmp_name'], $target_file)) {
                $logo_foto = $filename;
            }
        }

        $query = "INSERT INTO ekstrakurikuler (nama_ekskul, slug, deskripsi, pembina, jadwal, lokasi, logo_foto, kontak, urutan, status_aktif) 
                  VALUES ('$nama_ekskul', '$slug', '$deskripsi', '$pembina', '$jadwal', '$lokasi', '$logo_foto', '$kontak', $urutan, $status_aktif)";

        if (mysqli_query($conn, $query)) {
            $message = 'Ekstrakurikuler berhasil ditambahkan!';
            $message_type = 'success';
        } else {
            $message = 'Gagal menambahkan ekstrakurikuler: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    } 
    elseif ($action === 'edit') {
        $id           = (int)$_POST['id'];
        $nama_ekskul  = mysqli_real_escape_string($conn, trim($_POST['nama_ekskul']));
        $slug         = !empty($_POST['slug']) ? create_slug($_POST['slug']) : create_slug($nama_ekskul);
        $deskripsi    = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));
        $pembina      = mysqli_real_escape_string($conn, trim($_POST['pembina']));
        $jadwal       = mysqli_real_escape_string($conn, trim($_POST['jadwal']));
        $lokasi       = mysqli_real_escape_string($conn, trim($_POST['lokasi']));
        $kontak       = mysqli_real_escape_string($conn, trim($_POST['kontak']));
        $urutan       = (int)$_POST['urutan'];
        $status_aktif = isset($_POST['status_aktif']) ? (int)$_POST['status_aktif'] : 1;

        // Ambil foto lama
        $old_photo_query = mysqli_query($conn, "SELECT logo_foto FROM ekstrakurikuler WHERE id = $id");
        $old_data = mysqli_fetch_assoc($old_photo_query);
        $logo_foto = $old_data['logo_foto'] ?? '';

        // Upload foto baru jika ada
        if (isset($_FILES['logo_foto']) && $_FILES['logo_foto']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['logo_foto']['name'], PATHINFO_EXTENSION);
            $filename = 'ekskul_' . time() . '_' . rand(100, 999) . '.' . strtolower($ext);
            $target_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['logo_foto']['tmp_name'], $target_file)) {
                // Hapus foto lama jika ada
                if (!empty($logo_foto) && file_exists($upload_dir . $logo_foto)) {
                    @unlink($upload_dir . $logo_foto);
                }
                $logo_foto = $filename;
            }
        }

        $query = "UPDATE ekstrakurikuler SET 
                    nama_ekskul = '$nama_ekskul',
                    slug = '$slug',
                    deskripsi = '$deskripsi',
                    pembina = '$pembina',
                    jadwal = '$jadwal',
                    lokasi = '$lokasi',
                    logo_foto = '$logo_foto',
                    kontak = '$kontak',
                    urutan = $urutan,
                    status_aktif = $status_aktif
                  WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            $message = 'Data ekstrakurikuler berhasil diperbarui!';
            $message_type = 'success';
        } else {
            $message = 'Gagal memperbarui data: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    } 
    elseif ($action === 'delete') {
        $id = (int)$_POST['id'];

        // Hapus foto jika ada
        $photo_query = mysqli_query($conn, "SELECT logo_foto FROM ekstrakurikuler WHERE id = $id");
        if ($photo_query && $row = mysqli_fetch_assoc($photo_query)) {
            if (!empty($row['logo_foto']) && file_exists($upload_dir . $row['logo_foto'])) {
                @unlink($upload_dir . $row['logo_foto']);
            }
        }

        $query = "DELETE FROM ekstrakurikuler WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $message = 'Ekstrakurikuler berhasil dihapus!';
            $message_type = 'success';
        } else {
            $message = 'Gagal menghapus ekstrakurikuler: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }
    elseif ($action === 'toggle_status') {
        $id = (int)$_POST['id'];
        $current_status = (int)$_POST['current_status'];
        $new_status = $current_status === 1 ? 0 : 1;

        $query = "UPDATE ekstrakurikuler SET status_aktif = $new_status WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $message = 'Status ekstrakurikuler berhasil diperbarui!';
            $message_type = 'success';
        } else {
            $message = 'Gagal mengubah status: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }
}

// -----------------------------------------------------------------------------
// READ / AMBIL DATA EKSKUL & STATISTIK
// -----------------------------------------------------------------------------
$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

$where_clauses = [];
if (!empty($search)) {
    $where_clauses[] = "(nama_ekskul LIKE '%$search%' OR pembina LIKE '%$search%' OR lokasi LIKE '%$search%')";
}
if ($filter_status !== '') {
    $where_clauses[] = "status_aktif = " . (int)$filter_status;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(' AND ', $where_clauses) : "";

$ekskul_list = [];
$total_ekskul = 0;
$total_aktif = 0;
$total_nonaktif = 0;

if ($conn) {
    // Stat Counts
    $q_stat = mysqli_query($conn, "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status_aktif = 1 THEN 1 ELSE 0 END) as aktif,
        SUM(CASE WHEN status_aktif = 0 THEN 1 ELSE 0 END) as nonaktif
        FROM ekstrakurikuler");
    if ($q_stat) {
        $stat = mysqli_fetch_assoc($q_stat);
        $total_ekskul  = $stat['total'] ?? 0;
        $total_aktif   = $stat['aktif'] ?? 0;
        $total_nonaktif = $stat['nonaktif'] ?? 0;
    }

    // List Fetch
    $q_ekskul = mysqli_query($conn, "SELECT * FROM ekstrakurikuler $where_sql ORDER BY urutan ASC, id DESC");
    if ($q_ekskul) {
        while ($row = mysqli_fetch_assoc($q_ekskul)) {
            $ekskul_list[] = $row;
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
    <title>Kelola Ekstrakurikuler - Admin SMAN 21 Jakarta</title>
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

                    <!-- Kegiatan Dropdown (OPEN BY DEFAULT FOR THIS PAGE) -->
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3 text-white">
                                <i class="fa-solid fa-masks-theater w-5 text-center text-blue-400"></i>
                                Kegiatan
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="index.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800 text-slate-400">Daftar Kegiatan</a>
                            <a href="ekstrakurikuler.php" class="block py-1.5 px-2 rounded bg-blue-600 text-white font-semibold">Ekstrakurikuler</a>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Manajemen Ekstrakurikuler</h2>
                    <p class="text-xs text-slate-500">Kelola daftar kegiatan ekstrakurikuler, pembina, jadwal & kontak</p>
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

            <!-- NOTIFIKASI -->
            <?php if (!empty($message)): ?>
                <div class="p-4 rounded-xl text-sm font-medium flex items-center justify-between border <?= $message_type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-rose-600' ?> text-lg"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- RINGKASAN STATISTIK EKSKUL -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Ekstrakurikuler</p>
                        <h4 class="text-2xl font-extrabold text-slate-800 mt-1"><?= $total_ekskul ?></h4>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-masks-theater"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status Aktif</p>
                        <h4 class="text-2xl font-extrabold text-emerald-600 mt-1"><?= $total_aktif ?></h4>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status Non-Aktif</p>
                        <h4 class="text-2xl font-extrabold text-rose-500 mt-1"><?= $total_nonaktif ?></h4>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                </div>
            </div>

            <!-- ACTION BAR & FILTER -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <form method="GET" action="" class="flex flex-wrap items-center gap-3 flex-1">
                    <div class="relative flex-1 min-w-[200px]">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari ekstrakurikuler, pembina, lokasi..." class="w-full pl-9 pr-3 py-2 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>
                    <select name="status" onchange="this.form.submit()" class="py-2 px-3 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white">
                        <option value="">Semua Status</option>
                        <option value="1" <?= $filter_status === '1' ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= $filter_status === '0' ? 'selected' : '' ?>>Non-Aktif</option>
                    </select>
                    <?php if (!empty($search) || $filter_status !== ''): ?>
                        <a href="ekstrakurikuler.php" class="px-3 py-2 text-xs text-rose-600 hover:bg-rose-50 rounded-xl transition flex items-center gap-1 font-medium">
                            <i class="fa-solid fa-rotate-left"></i> Reset
                        </a>
                    <?php endif; ?>
                </form>

                <button onclick="openAddModal()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-md flex items-center justify-center gap-2 flex-shrink-0">
                    <i class="fa-solid fa-plus"></i> Tambah Ekstrakurikuler
                </button>
            </div>

            <!-- TABEL DATA EKSTRAKURIKULER -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider">
                            <tr>
                                <th class="py-3.5 px-4 text-center w-12">Urutan</th>
                                <th class="py-3.5 px-4">Ekstrakurikuler</th>
                                <th class="py-3.5 px-4">Pembina</th>
                                <th class="py-3.5 px-4">Jadwal & Lokasi</th>
                                <th class="py-3.5 px-4">Kontak</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-center w-36">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($ekskul_list)): ?>
                                <?php foreach ($ekskul_list as $item): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-3.5 px-4 text-center font-bold text-slate-400">
                                            <?= $item['urutan'] ?>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center gap-3">
                                                <?php if (!empty($item['logo_foto']) && file_exists($upload_dir . $item['logo_foto'])): ?>
                                                    <img src="../../uploads/ekskul/<?= htmlspecialchars($item['logo_foto']) ?>" alt="Logo" class="w-10 h-10 rounded-xl object-cover border border-slate-200 shadow-sm">
                                                <?php else: ?>
                                                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm border border-blue-100">
                                                        <i class="fa-solid fa-futbol"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <p class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($item['nama_ekskul']) ?></p>
                                                    <p class="text-[11px] text-slate-400 italic">/<?= htmlspecialchars($item['slug']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4 font-medium text-slate-700">
                                            <?= !empty($item['pembina']) ? htmlspecialchars($item['pembina']) : '<span class="text-slate-400 italic">-</span>' ?>
                                        </td>
                                        <td class="py-3.5 px-4 space-y-0.5">
                                            <p class="text-slate-700 font-medium">
                                                <i class="fa-regular fa-clock text-blue-500 mr-1.5"></i><?= !empty($item['jadwal']) ? htmlspecialchars($item['jadwal']) : '-' ?>
                                            </p>
                                            <p class="text-slate-500 text-[11px]">
                                                <i class="fa-solid fa-location-dot text-rose-500 mr-1.5"></i><?= !empty($item['lokasi']) ? htmlspecialchars($item['lokasi']) : '-' ?>
                                            </p>
                                        </td>
                                        <td class="py-3.5 px-4 text-slate-600">
                                            <?= !empty($item['kontak']) ? htmlspecialchars($item['kontak']) : '<span class="text-slate-400 italic">-</span>' ?>
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <form method="POST" action="" class="inline-block">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                <input type="hidden" name="current_status" value="<?= $item['status_aktif'] ?>">
                                                <button type="submit" class="cursor-pointer transition transform active:scale-95">
                                                    <?php if ($item['status_aktif'] == 1): ?>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200 hover:bg-emerald-200">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-800 border border-rose-200 hover:bg-rose-200">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Non-Aktif
                                                        </span>
                                                    <?php endif; ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($item)) ?>)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit">
                                                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                                                </button>
                                                <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin menghapus ekstrakurikuler ini?')" class="inline-block">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                    <button type="submit" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus">
                                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400 italic">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <i class="fa-solid fa-folder-open text-3xl text-slate-300"></i>
                                            <p>Tidak ada data ekstrakurikuler yang ditemukan.</p>
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
    <div id="ekskulModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-slate-200">
            
            <div class="p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <h3 id="modalTitle" class="text-lg font-bold text-slate-800">Tambah Ekstrakurikuler</h3>
                <button onclick="closeModal()" class="w-8 h-8 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="ekskulForm" method="POST" action="" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="ekskulId" value="">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Nama Ekskul -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Ekstrakurikuler <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama_ekskul" id="namaEkskul" required placeholder="Contoh: Paskibra, Basket, PMR..." class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>

                    <!-- Slug Custom -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Slug URL</label>
                        <input type="text" name="slug" id="slug" placeholder="Kosongkan untuk otomatis" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>

                    <!-- Pembina -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Pembina</label>
                        <input type="text" name="pembina" id="pembina" placeholder="Nama Guru Pembina" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>

                    <!-- Jadwal -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jadwal Latihan</label>
                        <input type="text" name="jadwal" id="jadwal" placeholder="Contoh: Jumat, 15.30 - 17.00 WIB" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>

                    <!-- Lokasi -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Lokasi Latihan</label>
                        <input type="text" name="lokasi" id="lokasi" placeholder="Contoh: Lapangan Utama / Aula" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>

                    <!-- Kontak -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kontak / IG / HP</label>
                        <input type="text" name="kontak" id="kontak" placeholder="Contoh: @paskibra21jkt / 0812xxx" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>

                    <!-- Urutan & Status -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Urutan Tampil</label>
                            <input type="number" name="urutan" id="urutan" value="1" min="1" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Status Tampil</label>
                            <select name="status_aktif" id="statusAktif" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white">
                                <option value="1">Aktif</option>
                                <option value="0">Non-Aktif</option>
                            </select>
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Deskripsi Kegiatan</label>
                        <textarea name="deskripsi" id="deskripsi" rows="3" placeholder="Jelaskan ringkasan mengenai ekstrakurikuler ini..." class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"></textarea>
                    </div>

                    <!-- Upload Logo / Foto -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Logo / Foto Kegiatan</label>
                        <input type="file" name="logo_foto" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-[11px] text-slate-400 mt-1">Format: JPG, PNG, WEBP. Maksimal 2MB.</p>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-md transition">
                        Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT RESPONSIF & MODAL HANDLING -->
    <script>
        // Sidebar Toggle Handler
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

        // Modal Controls
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Ekstrakurikuler Baru';
            document.getElementById('formAction').value = 'add';
            document.getElementById('ekskulId').value = '';
            document.getElementById('ekskulForm').reset();
            document.getElementById('ekskulModal').classList.remove('hidden');
        }

        function openEditModal(data) {
            document.getElementById('modalTitle').textContent = 'Edit Ekstrakurikuler';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('ekskulId').value = data.id;
            document.getElementById('namaEkskul').value = data.nama_ekskul || '';
            document.getElementById('slug').value = data.slug || '';
            document.getElementById('pembina').value = data.pembina || '';
            document.getElementById('jadwal').value = data.jadwal || '';
            document.getElementById('lokasi').value = data.lokasi || '';
            document.getElementById('kontak').value = data.kontak || '';
            document.getElementById('urutan').value = data.urutan || 1;
            document.getElementById('statusAktif').value = data.status_aktif;
            document.getElementById('deskripsi').value = data.deskripsi || '';

            document.getElementById('ekskulModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('ekskulModal').classList.add('hidden');
        }
    </script>
</body>
</html>
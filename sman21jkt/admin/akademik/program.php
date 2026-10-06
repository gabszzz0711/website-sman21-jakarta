<?php
// admin/akademik/program.php

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

// Fungsi pembantu pembuat Slug
function make_slug($string) {
    $string = preg_replace('/[^a-zA-Z0-9\s-]/', '', strtolower(trim($string)));
    return preg_replace('/[\s-]+/', '-', $string);
}

// Pesan Notifikasi Flash
$message = '';
$message_type = '';

// -----------------------------------------------------------------------------
// PEMROSESAN ACTION (CRUD)
// -----------------------------------------------------------------------------
if ($conn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ACTION: TAMBAH / EDIT PROGRAM
    if (isset($_POST['save_program'])) {
        $id            = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $nama_program  = mysqli_real_escape_string($conn, trim($_POST['nama_program']));
        $slug          = !empty($_POST['slug']) ? make_slug($_POST['slug']) : make_slug($nama_program);
        $deskripsi     = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));
        $ikon          = mysqli_real_escape_string($conn, trim($_POST['ikon']));
        $urutan        = isset($_POST['urutan']) ? intval($_POST['urutan']) : 1;
        $status_aktif  = isset($_POST['status_aktif']) ? intval($_POST['status_aktif']) : 1;
        
        // Penanganan Upload Gambar
        $gambar_name = '';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/program/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
            
            if (in_array($file_ext, $allowed_ext)) {
                $new_filename = 'program_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
                $target_file  = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
                    $gambar_name = 'uploads/program/' . $new_filename;
                }
            }
        }

        if ($id > 0) {
            // Update Data
            if (!empty($gambar_name)) {
                $query = "UPDATE program_pendidikan SET 
                            nama_program = '$nama_program', 
                            slug = '$slug', 
                            deskripsi = '$deskripsi', 
                            gambar = '$gambar_name', 
                            ikon = '$ikon', 
                            urutan = $urutan, 
                            status_aktif = $status_aktif 
                          WHERE id = $id";
            } else {
                $query = "UPDATE program_pendidikan SET 
                            nama_program = '$nama_program', 
                            slug = '$slug', 
                            deskripsi = '$deskripsi', 
                            ikon = '$ikon', 
                            urutan = $urutan, 
                            status_aktif = $status_aktif 
                          WHERE id = $id";
            }
            
            if (mysqli_query($conn, $query)) {
                $message = "Program pendidikan berhasil diperbarui!";
                $message_type = "success";
            } else {
                $message = "Gagal memperbarui program: " . mysqli_error($conn);
                $message_type = "error";
            }
        } else {
            // Tambah Data Baru
            $query = "INSERT INTO program_pendidikan (nama_program, slug, deskripsi, gambar, ikon, urutan, status_aktif) 
                      VALUES ('$nama_program', '$slug', '$deskripsi', '$gambar_name', '$ikon', $urutan, $status_aktif)";
            
            if (mysqli_query($conn, $query)) {
                $message = "Program pendidikan baru berhasil ditambahkan!";
                $message_type = "success";
            } else {
                $message = "Gagal menambahkan program: " . mysqli_error($conn);
                $message_type = "error";
            }
        }
    }

    // ACTION: HAPUS PROGRAM
    if (isset($_POST['delete_id'])) {
        $delete_id = intval($_POST['delete_id']);
        $query_del = "DELETE FROM program_pendidikan WHERE id = $delete_id";
        if (mysqli_query($conn, $query_del)) {
            $message = "Program berhasil dihapus!";
            $message_type = "success";
        } else {
            $message = "Gagal menghapus data: " . mysqli_error($conn);
            $message_type = "error";
        }
    }

    // ACTION: TOGGLE STATUS
    if (isset($_POST['toggle_id'])) {
        $toggle_id = intval($_POST['toggle_id']);
        $curr_status = intval($_POST['current_status']);
        $new_status = $curr_status === 1 ? 0 : 1;
        
        $query_toggle = "UPDATE program_pendidikan SET status_aktif = $new_status WHERE id = $toggle_id";
        if (mysqli_query($conn, $query_toggle)) {
            $message = "Status program berhasil diubah!";
            $message_type = "success";
        }
    }
}

// Ambil Seluruh Data Program
$programs = [];
if ($conn) {
    $res = mysqli_query($conn, "SELECT * FROM program_pendidikan ORDER BY urutan ASC, id DESC");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $programs[] = $row;
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
    <title>Program Pendidikan - Admin SMAN 21 Jakarta</title>
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

                    <!-- Akademik Dropdown (Active Open) -->
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg bg-slate-800 text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-graduation-cap w-5 text-center text-blue-400"></i>
                                Akademik
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="program.php" class="block py-1.5 px-2 rounded bg-blue-600 text-white font-medium">Program Pendidikan</a>
                            <a href="program-unggulan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Program Unggulan</a>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Program Pendidikan</h2>
                    <p class="text-xs text-slate-500">Kelola Kurikulum & Jurusan / Peminatan SMAN 21 Jakarta</p>
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

        <!-- KONTEN MAIN -->
        <main class="flex-1 overflow-y-auto p-6 space-y-6">

            <!-- NOTIFIKASI FLASH -->
            <?php if (!empty($message)): ?>
                <div class="p-4 rounded-xl text-xs font-medium flex items-center justify-between shadow-sm border <?= $message_type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?>">
                    <div class="flex items-center gap-2.5">
                        <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-rose-600' ?> text-base"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- BAR AKSI HEADER -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Daftar Program Pendidikan</h3>
                    <p class="text-xs text-slate-500">Total <?= count($programs) ?> program terdaftar pada sistem</p>
                </div>
                <button onclick="openModalAdd()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-md flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus"></i> Tambah Program Baru
                </button>
            </div>

            <!-- GRID CARDS / TABEL PROGRAM -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (!empty($programs)): ?>
                    <?php foreach ($programs as $prog): ?>
                        <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-sm hover:shadow-md transition flex flex-col justify-between">
                            
                            <!-- Header & Image Preview -->
                            <div>
                                <div class="relative h-40 bg-slate-800 flex items-center justify-center overflow-hidden">
                                    <?php if (!empty($prog['gambar'])): ?>
                                        <img src="../../<?= htmlspecialchars($prog['gambar']) ?>" alt="<?= htmlspecialchars($prog['nama_program']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-16 h-16 rounded-2xl bg-blue-500/20 border border-blue-400/30 flex items-center justify-center text-blue-300 text-2xl">
                                            <i class="<?= !empty($prog['ikon']) ? htmlspecialchars($prog['ikon']) : 'fa-solid fa-graduation-cap' ?>"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="absolute top-3 right-3 flex items-center gap-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="toggle_id" value="<?= $prog['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $prog['status_aktif'] ?>">
                                            <button type="submit" title="Klik untuk ubah status" class="px-2.5 py-1 rounded-full text-[10px] font-bold shadow-sm backdrop-blur-md transition <?= $prog['status_aktif'] == 1 ? 'bg-emerald-500/90 text-white hover:bg-emerald-600' : 'bg-slate-700/90 text-slate-200 hover:bg-slate-800' ?>">
                                                <i class="fa-solid <?= $prog['status_aktif'] == 1 ? 'fa-check' : 'fa-eye-slash' ?> mr-1"></i>
                                                <?= $prog['status_aktif'] == 1 ? 'Aktif' : 'Nonaktif' ?>
                                            </button>
                                        </form>
                                    </div>

                                    <div class="absolute bottom-3 left-3 bg-slate-900/80 backdrop-blur-md text-white text-[10px] font-medium px-2.5 py-1 rounded-lg border border-slate-700">
                                        Urutan: <?= $prog['urutan'] ?>
                                    </div>
                                </div>

                                <!-- Body Text -->
                                <div class="p-5 space-y-2">
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="font-bold text-slate-800 text-base leading-snug line-clamp-1">
                                            <?= htmlspecialchars($prog['nama_program']) ?>
                                        </h4>
                                    </div>
                                    <p class="text-[11px] font-mono text-blue-600 bg-blue-50 px-2 py-0.5 rounded inline-block">
                                        /<?= htmlspecialchars($prog['slug']) ?>
                                    </p>
                                    <p class="text-xs text-slate-600 line-clamp-3 leading-relaxed mt-1">
                                        <?= !empty($prog['deskripsi']) ? nl2br(htmlspecialchars($prog['deskripsi'])) : '<span class="italic text-slate-400">Tidak ada deskripsi singkat.</span>' ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2 text-xs">
                                <span class="text-slate-400 text-[11px]">
                                    <i class="<?= !empty($prog['ikon']) ? htmlspecialchars($prog['ikon']) : 'fa-solid fa-bookmark' ?> text-slate-500 mr-1"></i>
                                    <?= !empty($prog['ikon']) ? htmlspecialchars($prog['ikon']) : 'Default Icon' ?>
                                </span>
                                
                                <div class="flex items-center gap-2">
                                    <button onclick='openModalEdit(<?= json_encode($prog) ?>)' class="px-3 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200/60 font-semibold transition flex items-center gap-1.5">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus program ini?')" class="inline">
                                        <input type="hidden" name="delete_id" value="<?= $prog['id'] ?>">
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/60 font-semibold transition flex items-center gap-1.5">
                                            <i class="fa-solid fa-trash"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full bg-white rounded-2xl border border-slate-200/80 p-12 text-center text-slate-400 space-y-3">
                        <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-2xl">
                            <i class="fa-solid fa-folder-open"></i>
                        </div>
                        <p class="font-medium text-slate-600 text-sm">Belum ada program pendidikan yang ditambahkan.</p>
                        <button onclick="openModalAdd()" class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-xl hover:bg-blue-700 transition">
                            Tambah Program Sekarang
                        </button>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- MODAL FORM (TAMBAH / EDIT) -->
    <div id="modalForm" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
            
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <h3 id="modalTitle" class="font-bold text-base tracking-wide">Tambah Program Pendidikan</h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-white transition text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="id" id="form_id" value="0">
                <input type="hidden" name="save_program" value="1">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Program <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_program" id="form_nama" required placeholder="Contoh: Kurikulum Merdeka / IPA" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Slug URL (Opsional)</label>
                        <input type="text" name="slug" id="form_slug" placeholder="kurikulum-merdeka" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Ikon (Class FontAwesome)</label>
                        <input type="text" name="ikon" id="form_ikon" placeholder="fa-solid fa-book-open" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Program</label>
                    <textarea name="deskripsi" id="form_deskripsi" rows="3" placeholder="Penjelasan singkat mengenai program pendidikan ini..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none transition"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Urutan Tampil</label>
                        <input type="number" name="urutan" id="form_urutan" value="1" min="1" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Status Publikasi</label>
                        <select name="status_aktif" id="form_status" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none transition bg-white">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Gambar / Banner Program</label>
                    <input type="file" name="gambar" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition">
                    <p class="text-[10px] text-slate-400 mt-1">Format: JPG, PNG, WEBP (Max 2MB)</p>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-md transition">
                        Simpan Data
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- SCRIPT RESPONSIF & MODAL HANDLING -->
    <script>
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-ml-64');
        });

        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').textContent = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateClock, 1000);

        function openModalAdd() {
            document.getElementById('modalTitle').textContent = 'Tambah Program Pendidikan';
            document.getElementById('form_id').value = '0';
            document.getElementById('form_nama').value = '';
            document.getElementById('form_slug').value = '';
            document.getElementById('form_ikon').value = 'fa-solid fa-graduation-cap';
            document.getElementById('form_deskripsi').value = '';
            document.getElementById('form_urutan').value = '1';
            document.getElementById('form_status').value = '1';
            document.getElementById('modalForm').classList.remove('hidden');
        }

        function openModalEdit(data) {
            document.getElementById('modalTitle').textContent = 'Edit Program Pendidikan';
            document.getElementById('form_id').value = data.id;
            document.getElementById('form_nama').value = data.nama_program;
            document.getElementById('form_slug').value = data.slug;
            document.getElementById('form_ikon').value = data.ikon || '';
            document.getElementById('form_deskripsi').value = data.deskripsi || '';
            document.getElementById('form_urutan').value = data.urutan || '1';
            document.getElementById('form_status').value = data.status_aktif;
            document.getElementById('modalForm').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('modalForm').classList.add('hidden');
        }
    </script>
</body>
</html>
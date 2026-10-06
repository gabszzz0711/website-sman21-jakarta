<?php
// admin/banners/index.php

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
$host = 'sql305.byethost3.com';
$user = 'b3_43046986';
$pass = 'J@karta5';
$db   = 'b3_43046986_sman21jkt';

// Nonaktifkan strict mode exception
mysqli_report(MYSQLI_REPORT_OFF);

// Koneksi ke database
$conn = @mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

// -----------------------------------------------------------------------------
// PROCESS ACTIONS (TOGGLE STATUS, DELETE, ADD/EDIT)
// -----------------------------------------------------------------------------

// Toggle Status Banner
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "UPDATE hero_banners SET is_active = IF(is_active = 1, 0, 1) WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        $_SESSION['msg'] = 'Status banner berhasil diperbarui!';
        $_SESSION['msg_type'] = 'success';
    } else {
        $_SESSION['msg'] = 'Gagal memperbarui status: ' . mysqli_error($conn);
        $_SESSION['msg_type'] = 'danger';
    }
    header('Location: index.php');
    exit;
}

// Hapus Banner
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Ambil info gambar lama untuk dihapus dari server jika ada
    $res = mysqli_query($conn, "SELECT background_image FROM hero_banners WHERE id = $id");
    if ($res && $row = mysqli_fetch_assoc($res)) {
        $old_file = $row['background_image'];
        if (!empty($old_file) && file_exists('../../uploads/banners/' . $old_file)) {
            @unlink('../../uploads/banners/' . $old_file);
        }
    }
    
    $query = "DELETE FROM hero_banners WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        $_SESSION['msg'] = 'Banner berhasil dihapus!';
        $_SESSION['msg_type'] = 'success';
    } else {
        $_SESSION['msg'] = 'Gagal menghapus banner: ' . mysqli_error($conn);
        $_SESSION['msg_type'] = 'danger';
    }
    header('Location: index.php');
    exit;
}

// Simpan Data (Tambah / Edit Banner)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_banner'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $button_text = mysqli_real_escape_string($conn, trim($_POST['button_text']));
    $button_link = mysqli_real_escape_string($conn, trim($_POST['button_link']));
    $text_align = mysqli_real_escape_string($conn, $_POST['text_align']);
    $font_family = mysqli_real_escape_string($conn, $_POST['font_family']);
    $is_bold = isset($_POST['is_bold']) ? 1 : 0;
    $is_italic = isset($_POST['is_italic']) ? 1 : 0;
    $animation_type = mysqli_real_escape_string($conn, $_POST['animation_type']);
    $sort_order = intval($_POST['sort_order']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $background_image = '';
    
    // Upload Gambar Background jika ada file diunggah
    if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['background_image']['tmp_name'];
        $fileName = $_FILES['background_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = 'banner_' . time() . '_' . rand(100, 999) . '.' . $fileExtension;
            $uploadFileDir = '../../uploads/banners/';

            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $background_image = $newFileName;
            }
        }
    }

    if ($id > 0) {
        // Query UPDATE
        if (!empty($background_image)) {
            // Hapus gambar lama jika ada
            $old_q = mysqli_query($conn, "SELECT background_image FROM hero_banners WHERE id = $id");
            if ($old_q && $old_row = mysqli_fetch_assoc($old_q)) {
                if (!empty($old_row['background_image']) && file_exists('../../uploads/banners/' . $old_row['background_image'])) {
                    @unlink('../../uploads/banners/' . $old_row['background_image']);
                }
            }
            $img_sql = ", background_image = '$background_image'";
        } else {
            $img_sql = "";
        }

        $sql = "UPDATE hero_banners SET 
                title = '$title',
                description = '$description',
                button_text = '$button_text',
                button_link = '$button_link',
                text_align = '$text_align',
                font_family = '$font_family',
                is_bold = $is_bold,
                is_italic = $is_italic,
                animation_type = '$animation_type',
                sort_order = $sort_order,
                is_active = $is_active
                $img_sql
                WHERE id = $id";
    } else {
        // Query INSERT
        $sql = "INSERT INTO hero_banners (title, description, background_image, button_text, button_link, text_align, font_family, is_bold, is_italic, animation_type, sort_order, is_active) 
                VALUES ('$title', '$description', '$background_image', '$button_text', '$button_link', '$text_align', '$font_family', $is_bold, $is_italic, '$animation_type', $sort_order, $is_active)";
    }

    if (mysqli_query($conn, $sql)) {
        $_SESSION['msg'] = ($id > 0) ? 'Banner berhasil diperbarui!' : 'Banner baru berhasil ditambahkan!';
        $_SESSION['msg_type'] = 'success';
    } else {
        $_SESSION['msg'] = 'Terjadi kesalahan database: ' . mysqli_error($conn);
        $_SESSION['msg_type'] = 'danger';
    }

    header('Location: index.php');
    exit;
}

// -----------------------------------------------------------------------------
// AMBIL DATA BANNER UNTUK DITAMPILKAN
// -----------------------------------------------------------------------------
$banners = [];
$q_banners = mysqli_query($conn, "SELECT * FROM hero_banners ORDER BY sort_order ASC, id DESC");
if ($q_banners) {
    while ($row = mysqli_fetch_assoc($q_banners)) {
        $banners[] = $row;
    }
}

$logo_url = "https://sman21jkt.sch.id/wp-content/uploads/2025/02/1000305388-removebg-preview_image_repair_1740014356410.jpg";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Hero Banner - SMAN 21 Jakarta</title>
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

            <!-- Kelola Konten & Tampilan -->
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 mb-2">Kelola Sekolah</p>
                <nav class="space-y-1">
                    
                    <!-- Hero Banner Link (Active) -->
                    <a href="index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-blue-600 text-white font-medium text-sm transition-all shadow-sm">
                        <i class="fa-solid fa-sliders w-5 text-center text-base"></i>
                        <span>Hero Banner</span>
                    </a>

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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Kelola Hero Banner</h2>
                    <p class="text-xs text-slate-500">Pengaturan Banner Slide Utama Halaman Depan Portal SMAN 21 Jakarta</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <!-- Live Clock Status -->
                <div class="hidden md:flex items-center gap-2 bg-slate-100 px-3 py-1.5 rounded-full text-xs text-slate-600 font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span id="liveClock"><?= date('D, d M Y') ?></span>
                </div>

                <!-- Admin Profile -->
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

            <!-- NOTIFIKASI ALERT -->
            <?php if (isset($_SESSION['msg'])): ?>
                <div class="p-4 rounded-xl border flex items-center justify-between shadow-sm <?= $_SESSION['msg_type'] === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' ?>">
                    <div class="flex items-center gap-3 text-sm font-medium">
                        <i class="fa-solid <?= $_SESSION['msg_type'] === 'success' ? 'fa-circle-check text-emerald-500' : 'fa-triangle-exclamation text-rose-500' ?> text-lg"></i>
                        <span><?= htmlspecialchars($_SESSION['msg']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark text-base"></i>
                    </button>
                </div>
                <?php 
                    unset($_SESSION['msg']); 
                    unset($_SESSION['msg_type']); 
                ?>
            <?php endif; ?>

            <!-- HEADER ACTIONS & TITLE -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Daftar Banner Beranda</h3>
                    <p class="text-xs text-slate-500">Atur urutan, tampilan visual, serta konten teks tombol untuk slide hero website.</p>
                </div>
                <button onclick="openModalAdd()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-md flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus"></i> Tambah Banner Baru
                </button>
            </div>

            <!-- TABEL LIST BANNER -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider border-b border-slate-200 text-[11px]">
                            <tr>
                                <th class="py-3.5 px-4 text-center w-16">Urutan</th>
                                <th class="py-3.5 px-4 w-36">Thumbnail Background</th>
                                <th class="py-3.5 px-4">Judul & Teks Tombol</th>
                                <th class="py-3.5 px-4 text-center w-28">Status</th>
                                <th class="py-3.5 px-4 text-center w-36">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($banners)): ?>
                                <?php foreach ($banners as $b): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        
                                        <!-- KOLOM URUTAN TAMPIL -->
                                        <td class="py-4 px-4 text-center font-bold text-slate-800 text-sm">
                                            <span class="inline-block w-8 h-8 leading-8 rounded-lg bg-slate-100 text-slate-700 font-semibold border border-slate-200">
                                                <?= $b['sort_order'] ?>
                                            </span>
                                        </td>

                                        <!-- PREVIEW THUMBNAIL GAMBAR BACKGROUND -->
                                        <td class="py-4 px-4">
                                            <?php 
                                                $img_src = '';
                                                if (!empty($b['background_image'])) {
                                                    if (filter_var($b['background_image'], FILTER_VALIDATE_URL)) {
                                                        $img_src = $b['background_image'];
                                                    } else {
                                                        $img_src = '../../uploads/banners/' . $b['background_image'];
                                                    }
                                                }
                                            ?>
                                            <?php if (!empty($img_src)): ?>
                                                <div class="w-32 h-20 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 shadow-xs group relative">
                                                    <img src="<?= htmlspecialchars($img_src) ?>" alt="Banner Preview" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                                </div>
                                            <?php else: ?>
                                                <div class="w-32 h-20 rounded-xl bg-slate-100 border border-slate-200 flex flex-col items-center justify-center text-slate-400 text-[10px] gap-1">
                                                    <i class="fa-regular fa-image text-lg"></i>
                                                    <span>Tanpa Gambar</span>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- JUDUL BANNER & TEKS TOMBOL -->
                                        <td class="py-4 px-4">
                                            <div class="space-y-1">
                                                <p class="font-bold text-slate-800 text-sm leading-tight">
                                                    <?= htmlspecialchars($b['title']) ?>
                                                </p>
                                                <?php if (!empty($b['description'])): ?>
                                                    <p class="text-slate-500 line-clamp-1 text-xs">
                                                        <?= htmlspecialchars($b['description']) ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <div class="flex flex-wrap items-center gap-2 pt-1">
                                                    <?php if (!empty($b['button_text'])): ?>
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200/80 font-medium text-[11px]">
                                                            <i class="fa-solid fa-hand-pointer text-[10px]"></i>
                                                            <?= htmlspecialchars($b['button_text']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <span class="text-[10px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                                        Align: <?= ucfirst($b['text_align']) ?>
                                                    </span>
                                                    <span class="text-[10px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                                        Anim: <?= ucfirst($b['animation_type']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- BADGES STATUS (AKTIF / NON-AKTIF) -->
                                        <td class="py-4 px-4 text-center">
                                            <?php if ($b['is_active'] == 1): ?>
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                    Non-Aktif
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- AKSI: EDIT, HAPUS, TOGGLE STATUS -->
                                        <td class="py-4 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                
                                                <!-- Toggle Status -->
                                                <a href="index.php?action=toggle&id=<?= $b['id'] ?>" 
                                                   title="<?= $b['is_active'] == 1 ? 'Non-aktifkan Banner' : 'Aktifkan Banner' ?>"
                                                   class="p-2 rounded-lg border text-xs transition <?= $b['is_active'] == 1 ? 'bg-amber-50 text-amber-600 border-amber-200 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-600 border-emerald-200 hover:bg-emerald-100' ?>">
                                                    <i class="fa-solid <?= $b['is_active'] == 1 ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                                </a>

                                                <!-- Edit Banner -->
                                                <button onclick="editBanner(<?= htmlspecialchars(json_encode($b)) ?>)" 
                                                        title="Edit Banner"
                                                        class="p-2 rounded-lg bg-blue-50 text-blue-600 border border-blue-200 hover:bg-blue-100 text-xs transition">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>

                                                <!-- Hapus Banner -->
                                                <a href="index.php?action=delete&id=<?= $b['id'] ?>" 
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus banner ini?')" 
                                                   title="Hapus Banner"
                                                   class="p-2 rounded-lg bg-rose-50 text-rose-600 border border-rose-200 hover:bg-rose-100 text-xs transition">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </a>

                                            </div>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400 italic">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <i class="fa-solid fa-sliders text-3xl text-slate-300"></i>
                                            <span>Belum ada data hero banner. Silakan tambah banner baru.</span>
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

    <!-- MODAL FORM (TAMBAH & EDIT BANNER) -->
    <div id="bannerModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <h3 id="modalTitle" class="text-base font-bold text-slate-800">Tambah Banner Baru</h3>
                </div>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form action="index.php" method="POST" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-6 space-y-4">
                <input type="hidden" name="save_banner" value="1">
                <input type="hidden" name="id" id="banner_id" value="0">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- Judul Banner -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Judul Banner <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" id="banner_title" required class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="Contoh: Selamat Datang di SMAN 21 Jakarta">
                    </div>

                    <!-- Deskripsi Singkat -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Deskripsi / Teks Penjelas</label>
                        <textarea name="description" id="banner_description" rows="2" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="Teks sub-judul ringkas yang tampil di bawah judul utama..."></textarea>
                    </div>

                    <!-- Upload Gambar Background -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Gambar Background Banner</label>
                        <input type="file" name="background_image" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-300 rounded-xl p-1">
                        <p class="text-[11px] text-slate-400 mt-1">*Format: JPG, PNG, WEBP. Biarkan kosong jika tidak ingin mengubah gambar.</p>
                    </div>

                    <!-- Teks Tombol -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Teks Tombol (CTA)</label>
                        <input type="text" name="button_text" id="banner_button_text" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="Contoh: Jelajahi Profil">
                    </div>

                    <!-- Link Tombol -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Link Tombol (URL)</label>
                        <input type="text" name="button_link" id="banner_button_link" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="Contoh: profil/sejarah.php">
                    </div>

                    <!-- Text Align -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tata Letak Teks</label>
                        <select name="text_align" id="banner_text_align" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                            <option value="center">Center (Tengah)</option>
                            <option value="left">Left (Kiri)</option>
                            <option value="right">Right (Kanan)</option>
                        </select>
                    </div>

                    <!-- Font Family -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jenis Font</label>
                        <select name="font_family" id="banner_font_family" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                            <option value="Poppins">Poppins</option>
                            <option value="Inter">Inter</option>
                            <option value="Roboto">Roboto</option>
                            <option value="Montserrat">Montserrat</option>
                        </select>
                    </div>

                    <!-- Animasi Slide -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tipe Animasi</label>
                        <select name="animation_type" id="banner_animation_type" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                            <option value="fade">Fade</option>
                            <option value="slide">Slide</option>
                        </select>
                    </div>

                    <!-- Urutan Tampil (sort_order) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Urutan Tampil (Sort Order)</label>
                        <input type="number" name="sort_order" id="banner_sort_order" value="1" min="1" required class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                    </div>

                    <!-- Style Formatting Checkboxes -->
                    <div class="md:col-span-2 flex items-center gap-6 pt-2">
                        <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                            <input type="checkbox" name="is_bold" id="banner_is_bold" value="1" checked class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                            <span>Teks Cetak Tebal (Bold)</span>
                        </label>
                        <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                            <input type="checkbox" name="is_italic" id="banner_is_italic" value="1" class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                            <span>Teks Cetak Miring (Italic)</span>
                        </label>
                        <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                            <input type="checkbox" name="is_active" id="banner_is_active" value="1" checked class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                            <span class="text-emerald-700 font-semibold">Status Aktif</span>
                        </label>
                    </div>

                </div>

                <!-- Footer Action Buttons -->
                <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-xl border border-slate-300 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-md">
                        Simpan Banner
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- SCRIPT RESPONSIF SIDEBAR & MODAL INTERACTION -->
    <script>
        // Sidebar Toggle
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

        // Modal Handlers
        const bannerModal = document.getElementById('bannerModal');
        const modalTitle = document.getElementById('modalTitle');

        function openModalAdd() {
            modalTitle.textContent = "Tambah Banner Baru";
            document.getElementById('banner_id').value = "0";
            document.getElementById('banner_title').value = "";
            document.getElementById('banner_description').value = "";
            document.getElementById('banner_button_text').value = "";
            document.getElementById('banner_button_link').value = "";
            document.getElementById('banner_text_align').value = "center";
            document.getElementById('banner_font_family').value = "Poppins";
            document.getElementById('banner_animation_type').value = "fade";
            document.getElementById('banner_sort_order').value = "1";
            document.getElementById('banner_is_bold').checked = true;
            document.getElementById('banner_is_italic').checked = false;
            document.getElementById('banner_is_active').checked = true;

            bannerModal.classList.remove('hidden');
        }

        function editBanner(data) {
            modalTitle.textContent = "Edit Hero Banner";
            document.getElementById('banner_id').value = data.id;
            document.getElementById('banner_title').value = data.title;
            document.getElementById('banner_description').value = data.description || "";
            document.getElementById('banner_button_text').value = data.button_text || "";
            document.getElementById('banner_button_link').value = data.button_link || "";
            document.getElementById('banner_text_align').value = data.text_align || "center";
            document.getElementById('banner_font_family').value = data.font_family || "Poppins";
            document.getElementById('banner_animation_type').value = data.animation_type || "fade";
            document.getElementById('banner_sort_order').value = data.sort_order || 1;
            document.getElementById('banner_is_bold').checked = parseInt(data.is_bold) === 1;
            document.getElementById('banner_is_italic').checked = parseInt(data.is_italic) === 1;
            document.getElementById('banner_is_active').checked = parseInt(data.is_active) === 1;

            bannerModal.classList.remove('hidden');
        }

        function closeModal() {
            bannerModal.classList.add('hidden');
        }
    </script>
</body>
</html>
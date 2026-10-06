<?php
// admin/profil/sejarah.php

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
// KONFIGURASI DATABASE
// -----------------------------------------------------------------------------
$host = 'sql305.byethost3.com'; // Ganti dengan Database Hostname cPanel/VistaPanel
$user = 'b3_43046986';     // Ganti dengan Username Database
$pass = 'J@karta5';      // Ganti dengan Password Database
$db   = 'b3_43046986_sman21jkt'; // Nama Database

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect($host, $user, $pass, $db);

$msg_type = '';
$msg_content = '';

// Handling Upload Directory
$upload_dir = '../../uploads/sejarah/';
if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// -----------------------------------------------------------------------------
// PROSES CRUD (CREATE, UPDATE, DELETE, TOGGLE STATUS)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // 1. TAMBAH DATA SEJARAH
    if ($action === 'add') {
        $judul        = mysqli_real_escape_string($conn, trim($_POST['judul']));
        $isi_sejarah  = mysqli_real_escape_string($conn, trim($_POST['isi_sejarah']));
        $urutan       = intval($_POST['urutan']);
        $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
        $gambar_name  = '';

        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array(strtolower($ext), $allowed)) {
                $gambar_name = 'sejarah_' . time() . '_' . rand(100, 999) . '.' . $ext;
                move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $gambar_name);
            }
        }

        $query = "INSERT INTO profil_sejarah (judul, isi_sejarah, gambar, urutan, status_aktif) 
                  VALUES ('$judul', '$isi_sejarah', '$gambar_name', $urutan, $status_aktif)";
        
        if (mysqli_query($conn, $query)) {
            $msg_type = 'success';
            $msg_content = 'Data Sejarah Sekolah berhasil ditambahkan!';
        } else {
            $msg_type = 'error';
            $msg_content = 'Gagal menambahkan data: ' . mysqli_error($conn);
        }
    }

    // 2. EDIT DATA SEJARAH
    if ($action === 'edit') {
        $id           = intval($_POST['id']);
        $judul        = mysqli_real_escape_string($conn, trim($_POST['judul']));
        $isi_sejarah  = mysqli_real_escape_string($conn, trim($_POST['isi_sejarah']));
        $urutan       = intval($_POST['urutan']);
        $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;

        // Cek gambar lama
        $q_curr = mysqli_query($conn, "SELECT gambar FROM profil_sejarah WHERE id = $id");
        $r_curr = mysqli_fetch_assoc($q_curr);
        $gambar_name = $r_curr['gambar'];

        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array(strtolower($ext), $allowed)) {
                // Hapus gambar lama jika ada
                if (!empty($gambar_name) && file_exists($upload_dir . $gambar_name)) {
                    @unlink($upload_dir . $gambar_name);
                }
                $gambar_name = 'sejarah_' . time() . '_' . rand(100, 999) . '.' . $ext;
                move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $gambar_name);
            }
        }

        $query = "UPDATE profil_sejarah 
                  SET judul = '$judul', isi_sejarah = '$isi_sejarah', gambar = '$gambar_name', urutan = $urutan, status_aktif = $status_aktif 
                  WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            $msg_type = 'success';
            $msg_content = 'Data Sejarah Sekolah berhasil diperbarui!';
        } else {
            $msg_type = 'error';
            $msg_content = 'Gagal memperbarui data: ' . mysqli_error($conn);
        }
    }

    // 3. HAPUS DATA SEJARAH
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $q_curr = mysqli_query($conn, "SELECT gambar FROM profil_sejarah WHERE id = $id");
        if ($r_curr = mysqli_fetch_assoc($q_curr)) {
            if (!empty($r_curr['gambar']) && file_exists($upload_dir . $r_curr['gambar'])) {
                @unlink($upload_dir . $r_curr['gambar']);
            }
        }
        if (mysqli_query($conn, "DELETE FROM profil_sejarah WHERE id = $id")) {
            $msg_type = 'success';
            $msg_content = 'Data Sejarah berhasil dihapus!';
        } else {
            $msg_type = 'error';
            $msg_content = 'Gagal menghapus data: ' . mysqli_error($conn);
        }
    }

    // 4. TOGGLE STATUS AKTIF
    if ($action === 'toggle_status') {
        $id = intval($_POST['id']);
        $status_baru = intval($_POST['status_baru']);
        if (mysqli_query($conn, "UPDATE profil_sejarah SET status_aktif = $status_baru WHERE id = $id")) {
            $msg_type = 'success';
            $msg_content = 'Status data berhasil diperbarui!';
        }
    }
}

// -----------------------------------------------------------------------------
// AMBIL DATA PROFIL SEJARAH DARI DATABASE
// -----------------------------------------------------------------------------
$list_sejarah = [];
if ($conn) {
    $result = @mysqli_query($conn, "SELECT * FROM profil_sejarah ORDER BY urutan ASC, id DESC");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list_sejarah[] = $row;
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
    <title>Kelola Sejarah Sekolah - SMAN 21 Jakarta</title>
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
                    
                    <!-- Profil Submenu Dropdown (Open By Default) -->
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg bg-slate-800 text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-school w-5 text-center text-blue-400"></i>
                                Profil
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="sejarah.php" class="block py-1.5 px-2 rounded bg-blue-600 text-white font-medium">Sejarah Sekolah</a>
                            <a href="sambutan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800 text-slate-300">Sambutan Kepsek</a>
                            <a href="visi-misi.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800 text-slate-300">Visi & Misi</a>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Profil Sejarah Sekolah</h2>
                    <p class="text-xs text-slate-500">Kelola narasi rekam jejak dan histori pendirian SMAN 21 Jakarta</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
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

            <!-- NOTIFIKASI SUKSES / ERROR -->
            <?php if (!empty($msg_content)): ?>
                <div class="p-4 rounded-xl border flex items-center justify-between shadow-sm <?= $msg_type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' ?>">
                    <div class="flex items-center gap-3 text-sm font-medium">
                        <i class="fa-solid <?= $msg_type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-rose-600' ?> text-lg"></i>
                        <span><?= htmlspecialchars($msg_content) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
                </div>
            <?php endif; ?>

            <!-- ACTION BAR / TOOLBAR -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Daftar Konten Sejarah</h3>
                    <p class="text-xs text-slate-500">Total entri terdaftar: <span class="font-bold text-slate-700"><?= count($list_sejarah) ?></span></p>
                </div>
                <button onclick="openModalAdd()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-md flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i> Tambah Sejarah Baru
                </button>
            </div>

            <!-- TABEL DATA SEJARAH -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4 text-center w-12">Urutan</th>
                                <th class="py-3.5 px-4 w-28">Gambar</th>
                                <th class="py-3.5 px-4">Judul & Isi Sejarah</th>
                                <th class="py-3.5 px-4 w-32 text-center">Status</th>
                                <th class="py-3.5 px-4 w-36">Terakhir Update</th>
                                <th class="py-3.5 px-4 w-32 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($list_sejarah)): ?>
                                <?php foreach ($list_sejarah as $item): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        
                                        <!-- Urutan -->
                                        <td class="py-4 px-4 text-center font-bold text-slate-700 bg-slate-50/50">
                                            <?= intval($item['urutan']) ?>
                                        </td>

                                        <!-- Gambar -->
                                        <td class="py-4 px-4">
                                            <?php if (!empty($item['gambar']) && file_exists($upload_dir . $item['gambar'])): ?>
                                                <img src="<?= $upload_dir . htmlspecialchars($item['gambar']) ?>" alt="Sejarah Image" class="w-20 h-14 object-cover rounded-lg border border-slate-200 shadow-sm">
                                            <?php else: ?>
                                                <div class="w-20 h-14 bg-slate-100 rounded-lg border border-slate-200 flex items-center justify-center text-slate-400 text-xs">
                                                    <i class="fa-solid fa-image text-lg"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Judul & Isi -->
                                        <td class="py-4 px-4">
                                            <h4 class="font-bold text-sm text-slate-800 mb-1"><?= htmlspecialchars($item['judul']) ?></h4>
                                            <p class="text-slate-600 text-xs line-clamp-2 leading-relaxed">
                                                <?= htmlspecialchars(mb_strimwidth(strip_tags($item['isi_sejarah']), 0, 150, "...")) ?>
                                            </p>
                                        </td>

                                        <!-- Status Aktif -->
                                        <td class="py-4 px-4 text-center">
                                            <form method="POST" class="inline-block">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                <input type="hidden" name="status_baru" value="<?= $item['status_aktif'] == 1 ? 0 : 1 ?>">
                                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold transition <?= $item['status_aktif'] == 1 ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                                                    <span class="w-1.5 h-1.5 rounded-full <?= $item['status_aktif'] == 1 ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
                                                    <?= $item['status_aktif'] == 1 ? 'Aktif' : 'Nonaktif' ?>
                                                </button>
                                            </form>
                                        </td>

                                        <!-- Tanggal Update -->
                                        <td class="py-4 px-4 text-slate-500 whitespace-nowrap">
                                            <i class="fa-regular fa-clock text-slate-400 mr-1"></i>
                                            <?= date('d M Y, H:i', strtotime($item['tanggal_update'])) ?>
                                        </td>

                                        <!-- Action Buttons -->
                                        <td class="py-4 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button onclick='openModalEdit(<?= json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' class="p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition" title="Edit Data">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus poin sejarah ini?')" class="inline-block">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                    <button type="submit" class="p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition" title="Hapus Data">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-slate-400 italic">
                                        <i class="fa-solid fa-box-open text-3xl mb-2 block"></i>
                                        Belum ada entri sejarah sekolah yang dibuat.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- MODAL TAMBAH SEJARAH -->
    <div id="modalAdd" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden transform transition-all">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-plus text-blue-400"></i>
                    <h3 class="font-bold text-base">Tambah Poin Sejarah Sekolah</h3>
                </div>
                <button onclick="closeModalAdd()" class="text-slate-400 hover:text-white transition"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" value="add">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Poin / Periode <span class="text-rose-500">*</span></label>
                        <input type="text" name="judul" required placeholder="Contoh: Awal Berdiri SMAN 21 Jakarta (1970)" class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Urutan Tampil <span class="text-rose-500">*</span></label>
                        <input type="number" name="urutan" value="1" min="1" required class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Isi Narasi Sejarah <span class="text-rose-500">*</span></label>
                    <textarea name="isi_sejarah" rows="6" required placeholder="Tuliskan peristiwa sejarah sekolah secara rinci..." class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:outline-none leading-relaxed"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Upload Foto Pendukung (Opsional)</label>
                    <input type="file" name="gambar" accept="image/*" class="w-full text-xs p-2 rounded-lg border border-slate-300 bg-slate-50 text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                    <p class="text-[11px] text-slate-400 mt-1">Format: JPG, PNG, WEBP (Maksimal 2MB)</p>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" id="status_aktif_add" name="status_aktif" value="1" checked class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                    <label for="status_aktif_add" class="text-xs font-medium text-slate-700">Tampilkan langsung di halaman profil depan</label>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModalAdd()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-md transition">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT SEJARAH -->
    <div id="modalEdit" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden transform transition-all">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-blue-400"></i>
                    <h3 class="font-bold text-base">Edit Poin Sejarah Sekolah</h3>
                </div>
                <button onclick="closeModalEdit()" class="text-slate-400 hover:text-white transition"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Poin / Periode <span class="text-rose-500">*</span></label>
                        <input type="text" name="judul" id="edit_judul" required class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Urutan Tampil <span class="text-rose-500">*</span></label>
                        <input type="number" name="urutan" id="edit_urutan" min="1" required class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Isi Narasi Sejarah <span class="text-rose-500">*</span></label>
                    <textarea name="isi_sejarah" id="edit_isi_sejarah" rows="6" required class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:outline-none leading-relaxed"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ganti Foto Pendukung (Biarkan kosong jika tidak diubah)</label>
                    <input type="file" name="gambar" accept="image/*" class="w-full text-xs p-2 rounded-lg border border-slate-300 bg-slate-50 text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" id="edit_status_aktif" name="status_aktif" value="1" class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                    <label for="edit_status_aktif" class="text-xs font-medium text-slate-700">Tampilkan langsung di halaman profil depan</label>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModalEdit()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-md transition">Perbarui Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT RESPONSIF & MODAL CONTROL -->
    <script>
        // Sidebar Toggle
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-ml-64');
        });

        // Modal Handlers
        function openModalAdd() {
            document.getElementById('modalAdd').classList.remove('hidden');
        }
        function closeModalAdd() {
            document.getElementById('modalAdd').classList.add('hidden');
        }

        function openModalEdit(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_judul').value = data.judul;
            document.getElementById('edit_urutan').value = data.urutan;
            document.getElementById('edit_isi_sejarah').value = data.isi_sejarah;
            document.getElementById('edit_status_aktif').checked = parseInt(data.status_aktif) === 1;
            
            document.getElementById('modalEdit').classList.remove('hidden');
        }
        function closeModalEdit() {
            document.getElementById('modalEdit').classList.add('hidden');
        }

        // Live Clock
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').textContent = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateClock, 1000);
    </script>
</body>
</html>
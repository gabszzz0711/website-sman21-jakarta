<?php
// admin/profil/sambutan.php

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

$msg_success = '';
$msg_error   = '';

// Direktori untuk upload foto kepala sekolah
$upload_dir = '../../uploads/sambutan/';
if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// -----------------------------------------------------------------------------
// METODE POST: PROSES SIMPAN / UPDATE DATA SAMBUTAN
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_sambutan'])) {
    $nama_kepala_sekolah = trim($_POST['nama_kepala_sekolah'] ?? '');
    $jabatan             = trim($_POST['jabatan'] ?? '');
    $judul               = trim($_POST['judul'] ?? '');
    $isi_sambutan        = trim($_POST['isi_sambutan'] ?? '');
    $status_aktif        = isset($_POST['status_aktif']) ? 1 : 0;

    if (empty($nama_kepala_sekolah) || empty($judul) || empty($isi_sambutan)) {
        $msg_error = "Kolom Nama Kepala Sekolah, Judul Sambutan, dan Isi Sambutan tidak boleh kosong!";
    } else {
        if ($conn) {
            // Ambil data foto lama
            $q_current = mysqli_query($conn, "SELECT foto FROM sambutan_kepala_sekolah WHERE id = 1 LIMIT 1");
            $current_foto = '';
            if ($q_current && mysqli_num_rows($q_current) > 0) {
                $row_curr = mysqli_fetch_assoc($q_current);
                $current_foto = $row_curr['foto'] ?? '';
            }

            $foto_name = $current_foto;

            // Proses Upload Foto Baru jika ada file diunggah
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $file_tmp   = $_FILES['foto']['tmp_name'];
                $original_name = $_FILES['foto']['name'];
                $file_ext   = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($file_ext, $allowed_ext)) {
                    $new_filename = 'kepsek_' . time() . '.' . $file_ext;
                    $target_file  = $upload_dir . $new_filename;

                    if (move_uploaded_file($file_tmp, $target_file)) {
                        // Hapus foto lama jika ada dan file ada di direktori
                        if (!empty($current_foto) && file_exists($upload_dir . $current_foto)) {
                            @unlink($upload_dir . $current_foto);
                        }
                        $foto_name = $new_filename;
                    } else {
                        $msg_error = "Gagal mengunggah file foto ke server.";
                    }
                } else {
                    $msg_error = "Format file foto tidak valid! Gunakan JPG, JPEG, PNG, atau WEBP.";
                }
            }

            // Jika tidak ada error upload foto, jalankan query UPSERT
            if (empty($msg_error)) {
                // Cek apakah record id=1 sudah ada
                $check_exist = mysqli_query($conn, "SELECT id FROM sambutan_kepala_sekolah WHERE id = 1 LIMIT 1");
                
                if ($check_exist && mysqli_num_rows($check_exist) > 0) {
                    $stmt = mysqli_prepare($conn, "UPDATE sambutan_kepala_sekolah SET nama_kepala_sekolah = ?, jabatan = ?, foto = ?, judul = ?, isi_sambutan = ?, status_aktif = ? WHERE id = 1");
                } else {
                    $stmt = mysqli_prepare($conn, "INSERT INTO sambutan_kepala_sekolah (id, nama_kepala_sekolah, jabatan, foto, judul, isi_sambutan, status_aktif) VALUES (1, ?, ?, ?, ?, ?, ?)");
                }

                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sssssi", $nama_kepala_sekolah, $jabatan, $foto_name, $judul, $isi_sambutan, $status_aktif);
                    if (mysqli_stmt_execute($stmt)) {
                        $msg_success = "Data Sambutan Kepala Sekolah berhasil diperbarui!";
                    } else {
                        $msg_error = "Terjadi kesalahan saat menyimpan data ke database: " . mysqli_stmt_error($stmt);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $msg_error = "Terjadi kesalahan saat menyiapkan statement database: " . mysqli_error($conn);
                }
            }
        } else {
            $msg_error = "Koneksi database gagal. Silakan periksa kembali konfigurasi database Anda.";
        }
    }
}

// -----------------------------------------------------------------------------
// AMBIL DATA SAMBUTAN DARI DATABASE
// -----------------------------------------------------------------------------
$default_sambutan = [
    'nama_kepala_sekolah' => 'Mutia, M.Pd',
    'jabatan'             => 'Kepala Sekolah SMAN 21 Jakarta',
    'foto'                => '',
    'judul'               => 'Sambutan Kepala SMAN 21 Jakarta',
    'isi_sambutan'        => 'Selamat datang di website resmi SMAN 21 Jakarta.',
    'status_aktif'        => 1,
    'tanggal_update'      => date('Y-m-d H:i:s')
];

$data_sambutan = $default_sambutan;

if ($conn) {
    $q_sambutan = @mysqli_query($conn, "SELECT * FROM sambutan_kepala_sekolah WHERE id = 1 LIMIT 1");
    if ($q_sambutan && mysqli_num_rows($q_sambutan) > 0) {
        $db_data = mysqli_fetch_assoc($q_sambutan);
        if (is_array($db_data)) {
            $data_sambutan = array_merge($default_sambutan, $db_data);
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
    <title>Kelola Sambutan Kepsek - Admin SMAN 21 Jakarta</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- TinyMCE Rich Text Editor CDN -->
    <!-- BARU (Menggunakan CDNJS - Gratis Tanpa Peringatan API Key) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
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

    <!-- SIDEBAR NAVIGASI KONSISTEN DENGAN DASHBOARD -->
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
                    
                    <!-- Profil Submenu Dropdown (OPEN BY DEFAULT) -->
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-800 text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-school w-5 text-center text-blue-400"></i>
                                Profil
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="sejarah.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Sejarah Sekolah</a>
                            <a href="sambutan.php" class="block py-1.5 px-2 rounded bg-blue-600 text-white font-semibold shadow-sm">Sambutan Kepsek</a>
                            <a href="visi-misi.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Visi & Misi</a>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Sambutan Kepala Sekolah</h2>
                    <p class="text-xs text-slate-500">Kelola foto, pesan sambutan, dan identitas Kepala SMAN 21 Jakarta</p>
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

            <!-- NOTIFIKASI ALERT -->
            <?php if (!empty($msg_success)): ?>
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                        <span class="text-xs font-semibold"><?= htmlspecialchars($msg_success) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-sm">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($msg_error)): ?>
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-exclamation text-rose-600 text-lg"></i>
                        <span class="text-xs font-semibold"><?= htmlspecialchars($msg_error) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 text-sm">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- KOLOM KIRI: FOTO KEPALA SEKOLAH & STATUS (1 KOLOM) -->
                <div class="space-y-6">
                    
                    <!-- CARD PREVIEW FOTO & UPLOAD -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-image text-blue-600"></i> Foto Kepala Sekolah
                        </h3>

                        <div class="flex flex-col items-center">
                            <div class="relative w-44 h-56 rounded-2xl overflow-hidden bg-slate-100 border-2 border-slate-200 shadow-inner flex items-center justify-center group mb-4">
                                <?php 
                                    $photo_src = !empty($data_sambutan['foto']) && file_exists('../../uploads/sambutan/' . $data_sambutan['foto']) 
                                        ? '../../uploads/sambutan/' . htmlspecialchars($data_sambutan['foto'] ?? '') 
                                        : 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=400&auto=format&fit=crop';
                                ?>
                                <img id="previewImage" src="<?= $photo_src ?>" alt="Foto Kepala Sekolah" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="text-white text-xs font-medium bg-black/60 px-3 py-1.5 rounded-full backdrop-blur-sm">Pratinjau Foto</span>
                                </div>
                            </div>

                            <div class="w-full space-y-2">
                                <label class="block text-xs font-semibold text-slate-700">Unggah Foto Baru</label>
                                <input type="file" name="foto" id="fotoInput" accept="image/jpeg,image/png,image/webp"
                                    class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-slate-200 rounded-xl p-1 bg-slate-50/50">
                                <p class="text-[11px] text-slate-400">Format yang didukung: JPG, PNG, WEBP (Maks. 5 MB). Rekomendasi rasio 3:4.</p>
                            </div>
                        </div>
                    </div>

                    <!-- CARD CONFIGURASI STATUS & INFO PERUBAHAN -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-sliders text-blue-600"></i> Status & Metadata
                        </h3>

                        <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-200/80">
                            <div>
                                <p class="text-xs font-semibold text-slate-800">Status Publikasi</p>
                                <p class="text-[11px] text-slate-500">Tampilkan di halaman profil utama</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="status_aktif" value="1" class="sr-only peer" <?= (isset($data_sambutan['status_aktif']) && $data_sambutan['status_aktif'] == 1) ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 text-xs space-y-2">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Terakhir Diperbarui:</span>
                                <span class="font-medium text-slate-700">
                                    <?= !empty($data_sambutan['tanggal_update']) ? date('d M Y, H:i', strtotime($data_sambutan['tanggal_update'])) : date('d M Y, H:i') ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Target Tabel:</span>
                                <span class="font-medium text-slate-700 font-mono text-[11px]">sambutan_kepala_sekolah</span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- KOLOM KANAN: FORM ISIAN TEKS & EDITOR (2 KOLOM) -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-5">
                        
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-800">Formulir Sambutan</h3>
                                    <p class="text-xs text-slate-500">Perbarui konten sambutan resmi untuk pengunjung website</p>
                                </div>
                            </div>
                        </div>

                        <!-- Nama Kepala Sekolah & Jabatan -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Kepala Sekolah <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                                        <i class="fa-solid fa-user"></i>
                                    </span>
                                    <input type="text" name="nama_kepala_sekolah" required 
                                        value="<?= htmlspecialchars($data_sambutan['nama_kepala_sekolah'] ?? '') ?>"
                                        placeholder="Contoh: Mutia, M.Pd"
                                        class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jabatan Official</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                                        <i class="fa-solid fa-briefcase"></i>
                                    </span>
                                    <input type="text" name="jabatan" 
                                        value="<?= htmlspecialchars($data_sambutan['jabatan'] ?? '') ?>"
                                        placeholder="Contoh: Kepala SMAN 21 Jakarta"
                                        class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                                </div>
                            </div>
                        </div>

                        <!-- Judul Sambutan -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul Sambutan <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                                    <i class="fa-solid fa-heading"></i>
                                </span>
                                <input type="text" name="judul" required 
                                    value="<?= htmlspecialchars($data_sambutan['judul'] ?? '') ?>"
                                    placeholder="Contoh: Sambutan Kepala SMAN 21 Jakarta"
                                    class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                            </div>
                        </div>

                        <!-- Isi Sambutan (Textarea Editor) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Isi Pesan Sambutan <span class="text-rose-500">*</span></label>
                            <textarea name="isi_sambutan" id="isi_sambutan" rows="12" 
                                class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-normal text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                                placeholder="Tuliskan kata sambutan lengkap di sini..."><?= htmlspecialchars($data_sambutan['isi_sambutan'] ?? '') ?></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                            <a href="../dashboard.php" class="px-5 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-600 text-xs font-semibold transition">
                                Batal
                            </a>
                            <button type="submit" name="simpan_sambutan" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold shadow-lg shadow-blue-500/20 transition flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                            </button>
                        </div>

                    </div>

                </div>

            </form>

        </main>
    </div>

    <!-- SCRIPT CLIENT SIDE -->
    <script>
        // Inisialisasi TinyMCE Editor jika terhubung CDN
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: '#isi_sambutan',
                plugins: 'link image lists table code wordcount',
                toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bulletlist numlist | link image | removeformat code',
                height: 350,
                menubar: false,
                setup: function (editor) {
                    editor.on('change', function () {
                        editor.save();
                    });
                }
            });
        }

        // Toggle Sidebar Navigasi
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

        // Preview Foto Kepala Sekolah Secara Live saat File Dipilih
        document.getElementById('fotoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('previewImage').src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
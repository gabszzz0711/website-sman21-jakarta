<?php
// admin/pengaturan/sekolah.php

// Tampilkan error PHP untuk mempermudah debugging saat pengembangan
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Cek autentikasi login admin
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['username'])) {
    // Jika belum login, redirect ke halaman login
    // header('Location: ../login.php');
    // exit;
}

$admin_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'admin21';

// -----------------------------------------------------------------------------
// KONFIGURASI DATABASE BYETHOST / MYSQL
// -----------------------------------------------------------------------------
$host = 'sql305.byethost3.com'; 
$user = 'b3_43046986';          
$pass = 'J@karta5';             
$db   = 'b3_43046986_sman21jkt'; 

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect($host, $user, $pass, $db);

$success_msg = '';
$error_msg   = '';

// Handle Form Submission (Update Pengaturan Sekolah)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_pengaturan'])) {
    $nama_sekolah      = mysqli_real_escape_string($conn, $_POST['nama_sekolah'] ?? '');
    $npsn              = mysqli_real_escape_string($conn, $_POST['npsn'] ?? '');
    $nss               = mysqli_real_escape_string($conn, $_POST['nss'] ?? '');
    $jenjang           = mysqli_real_escape_string($conn, $_POST['jenjang'] ?? 'SMA');
    $status_sekolah    = mysqli_real_escape_string($conn, $_POST['status_sekolah'] ?? 'Negeri');
    $slogan            = mysqli_real_escape_string($conn, $_POST['slogan'] ?? '');
    $deskripsi_singkat = mysqli_real_escape_string($conn, $_POST['deskripsi_singkat'] ?? '');
    $tahun_berdiri     = mysqli_real_escape_string($conn, $_POST['tahun_berdiri'] ?? '');
    $akreditasi        = mysqli_real_escape_string($conn, $_POST['akreditasi'] ?? 'A');
    $email             = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $telepon           = mysqli_real_escape_string($conn, $_POST['telepon'] ?? '');
    $website           = mysqli_real_escape_string($conn, $_POST['website'] ?? '');
    $alamat            = mysqli_real_escape_string($conn, $_POST['alamat'] ?? '');
    $kode_pos          = mysqli_real_escape_string($conn, $_POST['kode_pos'] ?? '');
    
    // Default logo & favicon
    $logo_path    = $_POST['existing_logo'] ?? '';
    $favicon_path = $_POST['existing_favicon'] ?? '';

    // Folder upload
    $upload_dir = '../../uploads/';
    if (!file_exists($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    // Process Upload Logo
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'svg', 'webp'];
        if (in_array($ext, $allowed)) {
            $filename = 'logo_' . time() . '.' . $ext;
            $target = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $target)) {
                $logo_path = 'uploads/' . $filename;
            }
        }
    } elseif (!empty($_POST['logo_url'])) {
        $logo_path = mysqli_real_escape_string($conn, $_POST['logo_url']);
    }

    // Process Upload Favicon
    if (isset($_FILES['favicon_file']) && $_FILES['favicon_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['favicon_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['ico', 'png', 'jpg', 'jpeg', 'svg'];
        if (in_array($ext, $allowed)) {
            $filename = 'favicon_' . time() . '.' . $ext;
            $target = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['favicon_file']['tmp_name'], $target)) {
                $favicon_path = 'uploads/' . $filename;
            }
        }
    } elseif (!empty($_POST['favicon_url'])) {
        $favicon_path = mysqli_real_escape_string($conn, $_POST['favicon_url']);
    }

    if ($conn) {
        // Cek apakah data id = 1 sudah ada
        $check_q = mysqli_query($conn, "SELECT id FROM pengaturan_sekolah WHERE id = 1");
        if ($check_q && mysqli_num_rows($check_q) > 0) {
            $update_sql = "UPDATE pengaturan_sekolah SET 
                nama_sekolah = '$nama_sekolah',
                npsn = '$npsn',
                nss = '$nss',
                jenjang = '$jenjang',
                status_sekolah = '$status_sekolah',
                slogan = '$slogan',
                deskripsi_singkat = '$deskripsi_singkat',
                tahun_berdiri = '$tahun_berdiri',
                akreditasi = '$akreditasi',
                email = '$email',
                telepon = '$telepon',
                website = '$website',
                alamat = '$alamat',
                kode_pos = '$kode_pos',
                logo = '$logo_path',
                favicon = '$favicon_path'
                WHERE id = 1";
            
            if (mysqli_query($conn, $update_sql)) {
                $success_msg = "Pengaturan identitas sekolah berhasil diperbarui!";
            } else {
                $error_msg = "Gagal memperbarui data: " . mysqli_error($conn);
            }
        } else {
            $insert_sql = "INSERT INTO pengaturan_sekolah 
                (id, nama_sekolah, npsn, nss, jenjang, status_sekolah, slogan, deskripsi_singkat, tahun_berdiri, akreditasi, email, telepon, website, alamat, kode_pos, logo, favicon) 
                VALUES 
                (1, '$nama_sekolah', '$npsn', '$nss', '$jenjang', '$status_sekolah', '$slogan', '$deskripsi_singkat', '$tahun_berdiri', '$akreditasi', '$email', '$telepon', '$website', '$alamat', '$kode_pos', '$logo_path', '$favicon_path')";
            
            if (mysqli_query($conn, $insert_sql)) {
                $success_msg = "Pengaturan identitas sekolah berhasil disimpan!";
            } else {
                $error_msg = "Gagal menyimpan data baru: " . mysqli_error($conn);
            }
        }
    } else {
        $error_msg = "Koneksi database gagal. Silakan periksa pengaturan database.";
    }
}

// Ambil Data Pengaturan Sekolah Terbaru
$sekolah = [
    'nama_sekolah'      => 'SMAN 21 Jakarta',
    'npsn'              => '',
    'nss'               => '',
    'jenjang'           => 'SMA',
    'status_sekolah'    => 'Negeri',
    'slogan'            => 'Unggul dalam Prestasi dan Teknologi, Berkarakter Pancasila',
    'deskripsi_singkat' => 'Sekolah Menengah Atas Negeri 21 Jakarta berkomitmen mencetak generasi cerdas, berkarakter, dan berdaya saing global.',
    'tahun_berdiri'     => '1966',
    'akreditasi'        => 'A',
    'email'             => 'info@sman21jkt.sch.id',
    'telepon'           => '(021) 4892345',
    'website'           => 'https://sman21jkt.sch.id',
    'alamat'            => 'Jl. Tanah Mas Raya No. 1, Kayu Putih, Pulo Gadung, Jakarta Timur',
    'kode_pos'          => '13210',
    'logo'              => '',
    'favicon'           => ''
];

if ($conn) {
    $q_sekolah = @mysqli_query($conn, "SELECT * FROM pengaturan_sekolah WHERE id = 1 LIMIT 1");
    if ($q_sekolah && mysqli_num_rows($q_sekolah) > 0) {
        $fetched = mysqli_fetch_assoc($q_sekolah);
        foreach ($fetched as $key => $val) {
            if ($val !== null) {
                $sekolah[$key] = $val;
            }
        }
    }
}

$default_logo_url = "https://sman21jkt.sch.id/wp-content/uploads/2025/02/1000305388-removebg-preview_image_repair_1740014356410.jpg";
$display_logo = !empty($sekolah['logo']) ? $sekolah['logo'] : $default_logo_url;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Identitas Sekolah - SMAN 21 Jakarta</title>
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

    <!-- SIDEBAR NAVIGASI (Presisi dengan Dashboard Admin) -->
    <aside id="sidebar" class="w-64 bg-slate-900 text-slate-300 flex flex-col flex-shrink-0 transition-all duration-300 z-30">
        
        <!-- Header Branding Sidebar -->
        <div class="h-20 flex items-center gap-3 px-5 border-b border-slate-800/80 bg-slate-950/40">
            <img src="<?= $default_logo_url ?>" alt="Logo SMAN 21 Jakarta" class="w-11 h-11 object-contain drop-shadow-md">
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
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3 text-blue-400">
                                <i class="fa-solid fa-gears w-5 text-center"></i>
                                <span>System Config</span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="sekolah.php" class="block py-1.5 px-2 rounded text-white bg-blue-600 font-medium shadow-sm">Identitas Sekolah</a>
                            <a href="kontak.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Kontak & Medsos</a>
                            <a href="ai.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Model & API AI</a>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Pengaturan Identitas Sekolah</h2>
                    <p class="text-xs text-slate-500">Kelola Profil Sekolah, Branding, Slogan, dan Logo Resmi Portal</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <a href="kontak.php" class="hidden sm:flex items-center gap-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 px-3 py-2 rounded-lg transition">
                    <i class="fa-solid fa-address-book"></i>
                    <span>Kontak & Medsos</span>
                </a>

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

            <!-- NOTIFIKASI SUCCESS / ERROR -->
            <?php if (!empty($success_msg)): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl shadow-sm flex items-center justify-between text-sm">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                        <span><?= htmlspecialchars($success_msg) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl shadow-sm flex items-center justify-between text-sm">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-triangle-exclamation text-rose-600 text-lg"></i>
                        <span><?= htmlspecialchars($error_msg) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- BREADCRUMB NAVIGASI -->
            <div class="flex items-center justify-between text-xs text-slate-500">
                <div class="flex items-center gap-2">
                    <a href="../dashboard.php" class="hover:text-blue-600"><i class="fa-solid fa-house"></i> Admin</a>
                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    <span>Pengaturan</span>
                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    <span class="font-semibold text-slate-800">Identitas Sekolah</span>
                </div>
                <div class="text-[11px] text-slate-400">
                    <i class="fa-regular fa-clock mr-1"></i> Terakhir diperbarui: <?= date('d M Y, H:i') ?> WIB
                </div>
            </div>

            <!-- FORM PENGATURAN SEKOLAH -->
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <!-- ROW 1: IDENTITAS UTAMA & LOGO PREVIEW -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- BRANDING & LOGO (1 KOLOM) -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col items-center justify-center text-center space-y-4">
                        <h3 class="text-sm font-bold text-slate-800 self-start border-b border-slate-100 pb-2 w-full text-left">
                            <i class="fa-solid fa-image text-blue-600 mr-1.5"></i> Logo & Emblem Sekolah
                        </h3>
                        
                        <div class="relative group">
                            <div class="w-36 h-36 rounded-2xl border-2 border-dashed border-slate-200 p-3 bg-slate-50 flex items-center justify-center shadow-inner overflow-hidden">
                                <img id="logoPreview" src="<?= htmlspecialchars($display_logo) ?>" alt="Logo Sekolah" class="max-h-full max-w-full object-contain drop-shadow-md">
                            </div>
                        </div>

                        <div class="w-full space-y-3 text-left">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Unggah Logo (PNG / JPG / SVG)</label>
                                <input type="file" name="logo_file" id="logo_file" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                            </div>
                            
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Atau Gunakan URL Logo Direct</label>
                                <input type="text" name="logo_url" value="<?= htmlspecialchars($sekolah['logo'] ?? '') ?>" placeholder="https://..." class="w-full text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                                <input type="hidden" name="existing_logo" value="<?= htmlspecialchars($sekolah['logo'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 w-full text-left">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Favicon Website</label>
                            <input type="file" name="favicon_file" accept=".ico,.png,.jpg" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">
                            <input type="hidden" name="existing_favicon" value="<?= htmlspecialchars($sekolah['favicon'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- PROFIL UTAMA (2 KOLOM) -->
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                        <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2 flex items-center justify-between">
                            <span><i class="fa-solid fa-school text-blue-600 mr-1.5"></i> Identitas & Dokumen Resmi</span>
                            <span class="text-[11px] font-normal text-slate-400">Data Pokok Pendidikan</span>
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Resmi Sekolah <span class="text-rose-500">*</span></label>
                                <input type="text" name="nama_sekolah" required value="<?= htmlspecialchars($sekolah['nama_sekolah']) ?>" class="w-full text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition font-medium">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Pokok Sekolah Nasional (NPSN)</label>
                                <input type="text" name="npsn" value="<?= htmlspecialchars($sekolah['npsn']) ?>" placeholder="Contoh: 20100123" class="w-full text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Statistik Sekolah (NSS)</label>
                                <input type="text" name="nss" value="<?= htmlspecialchars($sekolah['nss']) ?>" placeholder="Contoh: 301016001234" class="w-full text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Jenjang Pendidikan</label>
                                <select name="jenjang" class="w-full text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition bg-white">
                                    <option value="SMA" <?= ($sekolah['jenjang'] == 'SMA') ? 'selected' : '' ?>>SMA (Sekolah Menengah Atas)</option>
                                    <option value="SMK" <?= ($sekolah['jenjang'] == 'SMK') ? 'selected' : '' ?>>SMK</option>
                                    <option value="MA" <?= ($sekolah['jenjang'] == 'MA') ? 'selected' : '' ?>>MA</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Status Sekolah</label>
                                <select name="status_sekolah" class="w-full text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition bg-white">
                                    <option value="Negeri" <?= ($sekolah['status_sekolah'] == 'Negeri') ? 'selected' : '' ?>>Negeri</option>
                                    <option value="Swasta" <?= ($sekolah['status_sekolah'] == 'Swasta') ? 'selected' : '' ?>>Swasta</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Akreditasi Sekolah</label>
                                <select name="akreditasi" class="w-full text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition bg-white">
                                    <option value="A" <?= ($sekolah['akreditasi'] == 'A') ? 'selected' : '' ?>>A (Unggul / Sangat Baik)</option>
                                    <option value="B" <?= ($sekolah['akreditasi'] == 'B') ? 'selected' : '' ?>>B (Baik)</option>
                                    <option value="C" <?= ($sekolah['akreditasi'] == 'C') ? 'selected' : '' ?>>C</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun Berdiri</label>
                                <input type="text" name="tahun_berdiri" value="<?= htmlspecialchars($sekolah['tahun_berdiri']) ?>" placeholder="Contoh: 1966" class="w-full text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                            </div>

                        </div>
                    </div>

                </div>

                <!-- ROW 2: SLOGAN & DESKRIPSI SINGKAT -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">
                        <i class="fa-solid fa-quote-left text-blue-600 mr-1.5"></i> Slogan & Deskripsi Profil
                    </h3>

                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Slogan / Motto Sekolah</label>
                            <input type="text" name="slogan" value="<?= htmlspecialchars($sekolah['slogan']) ?>" placeholder="Masukkan Slogan Sekolah" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Singkat Profil Sekolah (Untuk Header/Footer Web)</label>
                            <textarea name="deskripsi_singkat" rows="3" class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition" placeholder="Tuliskan gambaran umum sekolah..."><?= htmlspecialchars($sekolah['deskripsi_singkat']) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- ROW 3: KONTAK DAN ALAMAT POKOK -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <h3 class="text-sm font-bold text-slate-800">
                            <i class="fa-solid fa-location-dot text-blue-600 mr-1.5"></i> Kontak & Lokasi Utama
                        </h3>
                        <a href="kontak.php" class="text-xs font-semibold text-blue-600 hover:underline">
                            Kelola Medsos & Map Embed <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Email Resmi</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($sekolah['email']) ?>" placeholder="info@sman21jkt.sch.id" class="w-full text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Telepon</label>
                            <input type="text" name="telepon" value="<?= htmlspecialchars($sekolah['telepon']) ?>" placeholder="(021) 4892345" class="w-full text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Website Resmi URL</label>
                            <input type="url" name="website" value="<?= htmlspecialchars($sekolah['website']) ?>" placeholder="https://sman21jkt.sch.id" class="w-full text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Jalan Lengkap</label>
                            <input type="text" name="alamat" value="<?= htmlspecialchars($sekolah['alamat']) ?>" placeholder="Jl. Tanah Mas Raya..." class="w-full text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Pos</label>
                            <input type="text" name="kode_pos" value="<?= htmlspecialchars($sekolah['kode_pos']) ?>" placeholder="13210" class="w-full text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        </div>
                    </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="reset" class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-xs font-semibold transition">
                        <i class="fa-solid fa-rotate-left mr-1"></i> Reset
                    </button>
                    <button type="submit" name="save_pengaturan" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-md shadow-blue-500/20 flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan Pengaturan</span>
                    </button>
                </div>

            </form>

        </main>
    </div>

    <!-- SCRIPT PREVIEW LOGO & TOGGLE SIDEBAR -->
    <script>
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-ml-64');
        });

        // Live Preview Upload Logo
        document.getElementById('logo_file').addEventListener('change', function(e) {
            const [file] = e.target.files;
            if (file) {
                document.getElementById('logoPreview').src = URL.createObjectURL(file);
            }
        });
    </script>
</body>
</html>
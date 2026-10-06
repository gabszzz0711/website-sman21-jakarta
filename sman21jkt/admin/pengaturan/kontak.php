<?php
// admin/pengaturan/kontak.php

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
$host = 'sql305.byethost3.com'; 
$user = 'b3_43046986';          
$pass = 'J@karta5';             
$db   = 'b3_43046986_sman21jkt';   

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect($host, $user, $pass, $db);

$pesan_sukses = "";
$pesan_error  = "";

// PROSES SIMPAN DATA (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$conn) {
        $pesan_error = "Koneksi database gagal. Silakan periksa konfigurasi database Anda.";
    } else {
        // Ambil data form
        $alamat            = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';
        $kode_pos          = isset($_POST['kode_pos']) ? trim($_POST['kode_pos']) : '';
        $telepon           = isset($_POST['telepon']) ? trim($_POST['telepon']) : '';
        $email             = isset($_POST['email']) ? trim($_POST['email']) : '';
        $whatsapp          = isset($_POST['whatsapp']) ? trim($_POST['whatsapp']) : '';
        $google_maps_url   = isset($_POST['google_maps_url']) ? trim($_POST['google_maps_url']) : '';
        $google_maps_embed = isset($_POST['google_maps_embed']) ? trim($_POST['google_maps_embed']) : '';
        $latitude          = isset($_POST['latitude']) ? trim($_POST['latitude']) : '';
        $longitude         = isset($_POST['longitude']) ? trim($_POST['longitude']) : '';
        $instagram         = isset($_POST['instagram']) ? trim($_POST['instagram']) : '';
        $facebook          = isset($_POST['facebook']) ? trim($_POST['facebook']) : '';
        $youtube           = isset($_POST['youtube']) ? trim($_POST['youtube']) : '';
        $tiktok            = isset($_POST['tiktok']) ? trim($_POST['tiktok']) : '';
        $jam_operasional   = isset($_POST['jam_operasional']) ? trim($_POST['jam_operasional']) : '';

        // Cek apakah data id=1 sudah ada
        $check_q = mysqli_query($conn, "SELECT id FROM kontak_sekolah WHERE id = 1");
        
        if ($check_q && mysqli_num_rows($check_q) > 0) {
            // Update
            $stmt = mysqli_prepare($conn, "UPDATE kontak_sekolah SET alamat=?, kode_pos=?, telepon=?, email=?, whatsapp=?, google_maps_url=?, google_maps_embed=?, latitude=?, longitude=?, instagram=?, facebook=?, youtube=?, tiktok=?, jam_operasional=? WHERE id=1");
            mysqli_stmt_bind_param($stmt, "ssssssssssssss", $alamat, $kode_pos, $telepon, $email, $whatsapp, $google_maps_url, $google_maps_embed, $latitude, $longitude, $instagram, $facebook, $youtube, $tiktok, $jam_operasional);
            if (mysqli_stmt_execute($stmt)) {
                $pesan_sukses = "Data kontak dan media sosial berhasil diperbarui!";
            } else {
                $pesan_error = "Gagal memperbarui data: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            // Insert baru jika belum ada
            $stmt = mysqli_prepare($conn, "INSERT INTO kontak_sekolah (id, alamat, kode_pos, telepon, email, whatsapp, google_maps_url, google_maps_embed, latitude, longitude, instagram, facebook, youtube, tiktok, jam_operasional) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssssssssssssss", $alamat, $kode_pos, $telepon, $email, $whatsapp, $google_maps_url, $google_maps_embed, $latitude, $longitude, $instagram, $facebook, $youtube, $tiktok, $jam_operasional);
            if (mysqli_stmt_execute($stmt)) {
                $pesan_sukses = "Data kontak dan media sosial berhasil disimpan!";
            } else {
                $pesan_error = "Gagal menyimpan data: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// AMBIL DATA KONTAK SEKARANG
$data_kontak = [
    'alamat'            => '',
    'kode_pos'          => '',
    'telepon'           => '',
    'email'             => '',
    'whatsapp'          => '',
    'google_maps_url'   => '',
    'google_maps_embed' => '',
    'latitude'          => '',
    'longitude'         => '',
    'instagram'         => '',
    'facebook'          => '',
    'youtube'           => '',
    'tiktok'            => '',
    'jam_operasional'   => 'Senin - Jumat: 06.30 - 15.00 WIB'
];

if ($conn) {
    $get_q = mysqli_query($conn, "SELECT * FROM kontak_sekolah WHERE id = 1 LIMIT 1");
    if ($get_q && mysqli_num_rows($get_q) > 0) {
        $row = mysqli_fetch_assoc($get_q);
        foreach ($data_kontak as $key => $val) {
            if (isset($row[$key]) && $row[$key] !== null) {
                $data_kontak[$key] = $row[$key];
            }
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
    <title>Kontak & Media Sosial - Admin SMAN 21 Jakarta</title>
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
                            <a href="sekolah.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Identitas Sekolah</a>
                            <a href="kontak.php" class="block py-1.5 px-2 rounded bg-blue-600 text-white font-medium">Kontak & Medsos</a>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Pengaturan Kontak & Media Sosial</h2>
                    <p class="text-xs text-slate-500">Kelola informasi alamat, saluran komunikasi resmi, serta peta lokasi sekolah</p>
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

            <!-- NOTIFIKASI SUKSES / ERROR -->
            <?php if (!empty($pesan_sukses)): ?>
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm animate-fade-in">
                    <div class="flex items-center gap-3 text-sm">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                        <span><?= htmlspecialchars($pesan_sukses) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 text-xs font-bold px-2 py-1">✕</button>
                </div>
            <?php endif; ?>

            <?php if (!empty($pesan_error)): ?>
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-sm animate-fade-in">
                    <div class="flex items-center gap-3 text-sm">
                        <i class="fa-solid fa-circle-xmark text-rose-600 text-lg"></i>
                        <span><?= htmlspecialchars($pesan_error) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900 text-xs font-bold px-2 py-1">✕</button>
                </div>
            <?php endif; ?>

            <!-- FORM UTAMA -->
            <form action="" method="POST" class="space-y-6">
                
                <!-- KARTU 1: ALAMAT & KONTAK UTAMA -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Alamat & Saluran Komunikasi Utama</h3>
                            <p class="text-xs text-slate-500">Informasi lokasi fisik sekolah dan kontak resmi yang dapat dihubungi publik</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Alamat Lengkap -->
                        <div class="md:col-span-2 space-y-1.5">
                            <label for="alamat" class="block text-xs font-semibold text-slate-700">Alamat Lengkap Sekolah</label>
                            <textarea id="alamat" name="alamat" rows="3" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" placeholder="Contoh: Jl. Pemuda No. 21, Rawamangun, Pulo Gadung, Jakarta Timur"><?= htmlspecialchars($data_kontak['alamat']) ?></textarea>
                        </div>

                        <!-- Kode Pos -->
                        <div class="space-y-1.5">
                            <label for="kode_pos" class="block text-xs font-semibold text-slate-700">Kode Pos</label>
                            <input type="text" id="kode_pos" name="kode_pos" value="<?= htmlspecialchars($data_kontak['kode_pos']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" placeholder="13220">
                        </div>

                        <!-- Jam Operasional -->
                        <div class="space-y-1.5">
                            <label for="jam_operasional" class="block text-xs font-semibold text-slate-700">Jam Operasional Layanan</label>
                            <input type="text" id="jam_operasional" name="jam_operasional" value="<?= htmlspecialchars($data_kontak['jam_operasional']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" placeholder="Senin - Jumat: 06.30 - 15.00 WIB">
                        </div>

                        <!-- Nomor Telepon Kantor -->
                        <div class="space-y-1.5">
                            <label for="telepon" class="block text-xs font-semibold text-slate-700"><i class="fa-solid fa-phone mr-1 text-slate-400"></i> Telepon Kantor / Fax</label>
                            <input type="text" id="telepon" name="telepon" value="<?= htmlspecialchars($data_kontak['telepon']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" placeholder="(021) 4721234">
                        </div>

                        <!-- WhatsApp Official -->
                        <div class="space-y-1.5">
                            <label for="whatsapp" class="block text-xs font-semibold text-slate-700"><i class="fa-brands fa-whatsapp mr-1 text-emerald-500"></i> Nomor WhatsApp Official</label>
                            <input type="text" id="whatsapp" name="whatsapp" value="<?= htmlspecialchars($data_kontak['whatsapp']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" placeholder="081234567890 atau +6281234567890">
                        </div>

                        <!-- Email Resmi -->
                        <div class="md:col-span-2 space-y-1.5">
                            <label for="email" class="block text-xs font-semibold text-slate-700"><i class="fa-solid fa-envelope mr-1 text-slate-400"></i> Email Resmi Sekolah</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($data_kontak['email']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" placeholder="info@sman21jkt.sch.id">
                        </div>
                    </div>
                </div>

                <!-- KARTU 2: GOOGLE MAPS & GEOLOKASI -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                            <i class="fa-solid fa-map-location-dot"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Peta & Integrasi Google Maps</h3>
                            <p class="text-xs text-slate-500">Integrasikan peta digital sekolah agar memudahkan pengunjung menemukan lokasi</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Link Google Maps Direct -->
                        <div class="md:col-span-2 space-y-1.5">
                            <label for="google_maps_url" class="block text-xs font-semibold text-slate-700">URL Langsung Google Maps (Link Bagikan)</label>
                            <input type="url" id="google_maps_url" name="google_maps_url" value="<?= htmlspecialchars($data_kontak['google_maps_url']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" placeholder="https://maps.google.com/?q=SMAN+21+Jakarta">
                        </div>

                        <!-- Latitude -->
                        <div class="space-y-1.5">
                            <label for="latitude" class="block text-xs font-semibold text-slate-700">Latitude (Garis Lintang)</label>
                            <input type="text" id="latitude" name="latitude" value="<?= htmlspecialchars($data_kontak['latitude']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" placeholder="-6.192345">
                        </div>

                        <!-- Longitude -->
                        <div class="space-y-1.5">
                            <label for="longitude" class="block text-xs font-semibold text-slate-700">Longitude (Garis Bujur)</label>
                            <input type="text" id="longitude" name="longitude" value="<?= htmlspecialchars($data_kontak['longitude']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" placeholder="106.881234">
                        </div>

                        <!-- Embed Kode HTML Iframe -->
                        <div class="md:col-span-2 space-y-1.5">
                            <label for="google_maps_embed" class="block text-xs font-semibold text-slate-700">Kode Embed Google Maps (&lt;iframe&gt; ... &lt;/iframe&gt;)</label>
                            <textarea id="google_maps_embed" name="google_maps_embed" rows="3" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-mono text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" placeholder="<iframe src='https://www.google.com/maps/embed?...' width='600' height='450' ...></iframe>"><?= htmlspecialchars($data_kontak['google_maps_embed']) ?></textarea>
                            <p class="text-[11px] text-slate-400">Salin kode HTML dari menu "Sematkan peta" di Google Maps untuk ditempatkan pada halaman kontak utama website.</p>
                        </div>
                    </div>
                </div>

                <!-- KARTU 3: AKUN MEDIA SOSIAL RESMI -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                            <i class="fa-solid fa-share-nodes"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Media Sosial Resmi Sekolah</h3>
                            <p class="text-xs text-slate-500">Tautkan akun media sosial resmi SMAN 21 Jakarta</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Instagram -->
                        <div class="space-y-1.5">
                            <label for="instagram" class="block text-xs font-semibold text-slate-700">
                                <i class="fa-brands fa-instagram text-rose-500 mr-1.5"></i> Instagram URL
                            </label>
                            <input type="text" id="instagram" name="instagram" value="<?= htmlspecialchars($data_kontak['instagram']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" placeholder="https://instagram.com/sman21jkt">
                        </div>

                        <!-- YouTube -->
                        <div class="space-y-1.5">
                            <label for="youtube" class="block text-xs font-semibold text-slate-700">
                                <i class="fa-brands fa-youtube text-red-600 mr-1.5"></i> YouTube Channel URL
                            </label>
                            <input type="text" id="youtube" name="youtube" value="<?= htmlspecialchars($data_kontak['youtube']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" placeholder="https://youtube.com/@sman21jakarta">
                        </div>

                        <!-- Facebook -->
                        <div class="space-y-1.5">
                            <label for="facebook" class="block text-xs font-semibold text-slate-700">
                                <i class="fa-brands fa-facebook text-blue-600 mr-1.5"></i> Facebook Page URL
                            </label>
                            <input type="text" id="facebook" name="facebook" value="<?= htmlspecialchars($data_kontak['facebook']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" placeholder="https://facebook.com/sman21jakarta">
                        </div>

                        <!-- TikTok -->
                        <div class="space-y-1.5">
                            <label for="tiktok" class="block text-xs font-semibold text-slate-700">
                                <i class="fa-brands fa-tiktok text-slate-900 mr-1.5"></i> TikTok Official URL
                            </label>
                            <input type="text" id="tiktok" name="tiktok" value="<?= htmlspecialchars($data_kontak['tiktok']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" placeholder="https://tiktok.com/@sman21jkt">
                        </div>
                    </div>
                </div>

                <!-- BOTTOM TOMBOL AKSI -->
                <div class="flex items-center justify-end gap-3 pt-2 pb-10">
                    <button type="reset" class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-600 text-xs font-semibold hover:bg-slate-100 transition">
                        <i class="fa-solid fa-rotate-left mr-1.5"></i> Reset Form
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-md transition flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                    </button>
                </div>

            </form>

        </main>
    </div>

    <!-- SCRIPT RESPONSIF SIDEBAR TOGGLE & CLOCK -->
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
    </script>
</body>
</html>
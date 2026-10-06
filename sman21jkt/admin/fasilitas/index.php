<?php
// admin/fasilitas/index.php
ob_start();

// Header No-Cache untuk mencegah browser/proxy menyimpan tampilan lama setelah delete/insert di Byethost
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Tampilkan error PHP untuk mempermudah debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Cek autentikasi login (bisa diaktifkan kembali jika sistem login sudah siap)
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

$upload_dir = '../../uploads/fasilitas/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// -----------------------------------------------------------------------------
// PESAN NOTIFIKASI / ALERT
// -----------------------------------------------------------------------------
$message = '';
$message_type = '';

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message']['text'];
    $message_type = $_SESSION['flash_message']['type'];
    unset($_SESSION['flash_message']);
}

// -----------------------------------------------------------------------------
// PROSES CRUD (POST & GET ACTION)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = $_POST['action'] ?? '';

    // 1. TAMBAH / EDIT FASILITAS
    if ($action === 'save') {
        $id                  = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $nama_fasilitas     = mysqli_real_escape_string($conn, trim($_POST['nama_fasilitas'] ?? ''));
        $kategori           = mysqli_real_escape_string($conn, trim($_POST['kategori'] ?? 'Umum'));
        $deskripsi          = mysqli_real_escape_string($conn, trim($_POST['deskripsi'] ?? ''));
        $jumlah             = isset($_POST['jumlah']) ? max(1, intval($_POST['jumlah'])) : 1;
        $lokasi             = mysqli_real_escape_string($conn, trim($_POST['lokasi'] ?? ''));
        $fasilitas_pendukung = mysqli_real_escape_string($conn, trim($_POST['fasilitas_pendukung'] ?? ''));
        $urutan             = isset($_POST['urutan']) ? intval($_POST['urutan']) : 1;
        $status_aktif       = isset($_POST['status_aktif']) ? 1 : 0;

        $foto_filename = '';
        
        // Handle upload foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $file_tmp   = $_FILES['foto']['tmp_name'];
            $file_name  = $_FILES['foto']['name'];
            $file_ext   = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed    = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (in_array($file_ext, $allowed)) {
                $new_filename = 'fasilitas_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
                $target_path  = $upload_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $target_path)) {
                    $foto_filename = $new_filename;

                    // Hapus foto lama jika sedang update
                    if ($id > 0) {
                        $q_old = mysqli_query($conn, "SELECT foto FROM fasilitas WHERE id = $id");
                        if ($q_old && $r_old = mysqli_fetch_assoc($q_old)) {
                            if (!empty($r_old['foto']) && file_exists($upload_dir . $r_old['foto'])) {
                                @unlink($upload_dir . $r_old['foto']);
                            }
                        }
                    }
                }
            }
        }

        if ($id > 0) {
            // Update data
            if ($foto_filename !== '') {
                $query = "UPDATE fasilitas SET 
                            nama_fasilitas = '$nama_fasilitas',
                            kategori = '$kategori',
                            deskripsi = '$deskripsi',
                            foto = '$foto_filename',
                            jumlah = $jumlah,
                            lokasi = '$lokasi',
                            fasilitas_pendukung = '$fasilitas_pendukung',
                            urutan = $urutan,
                            status_aktif = $status_aktif 
                          WHERE id = $id";
            } else {
                $query = "UPDATE fasilitas SET 
                            nama_fasilitas = '$nama_fasilitas',
                            kategori = '$kategori',
                            deskripsi = '$deskripsi',
                            jumlah = $jumlah,
                            lokasi = '$lokasi',
                            fasilitas_pendukung = '$fasilitas_pendukung',
                            urutan = $urutan,
                            status_aktif = $status_aktif 
                          WHERE id = $id";
            }

            if (mysqli_query($conn, $query)) {
                $_SESSION['flash_message'] = ['text' => 'Data fasilitas berhasil diperbarui!', 'type' => 'success'];
            } else {
                $_SESSION['flash_message'] = ['text' => 'Gagal memperbarui fasilitas: ' . mysqli_error($conn), 'type' => 'error'];
            }
        } else {
            // Insert data baru
            $query = "INSERT INTO fasilitas 
                      (nama_fasilitas, kategori, deskripsi, foto, jumlah, lokasi, fasilitas_pendukung, urutan, status_aktif) 
                      VALUES 
                      ('$nama_fasilitas', '$kategori', '$deskripsi', '$foto_filename', $jumlah, '$lokasi', '$fasilitas_pendukung', $urutan, $status_aktif)";

            if (mysqli_query($conn, $query)) {
                $_SESSION['flash_message'] = ['text' => 'Fasilitas baru berhasil ditambahkan!', 'type' => 'success'];
            } else {
                $_SESSION['flash_message'] = ['text' => 'Gagal menambahkan fasilitas: ' . mysqli_error($conn), 'type' => 'error'];
            }
        }

        header('Location: index.php?t=' . time());
        exit;
    }

    // 2. TOGGLE STATUS (AKTIF / NONAKTIF)
    if ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $query = "UPDATE fasilitas SET status_aktif = 1 - status_aktif WHERE id = $id";
            if (mysqli_query($conn, $query)) {
                $_SESSION['flash_message'] = ['text' => 'Status fasilitas berhasil diubah!', 'type' => 'success'];
            }
        }
        header('Location: index.php?t=' . time());
        exit;
    }
}

// 3. HAPUS FASILITAS (Mendukung POST & GET)
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === 'delete' && $conn) {
    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id > 0) {
        $q_old = mysqli_query($conn, "SELECT foto FROM fasilitas WHERE id = $id");
        if ($q_old && $r_old = mysqli_fetch_assoc($q_old)) {
            if (!empty($r_old['foto']) && file_exists($upload_dir . $r_old['foto'])) {
                @unlink($upload_dir . $r_old['foto']);
            }
        }

        $query = "DELETE FROM fasilitas WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['flash_message'] = ['text' => 'Fasilitas berhasil dihapus!', 'type' => 'success'];
        } else {
            // Menampilkan error asli dari MySQL jika nama kolom salah
            $_SESSION['flash_message'] = ['text' => 'Gagal menghapus: ' . mysqli_error($conn), 'type' => 'error'];
        }
    } else {
        $_SESSION['flash_message'] = ['text' => 'Gagal menghapus: ID fasilitas bernilai 0. Silakan jalankan perintah SQL ALTER TABLE pada database.', 'type' => 'error'];
    }
    header('Location: index.php?t=' . time());
    exit;
}

// -----------------------------------------------------------------------------
// FILTER & QUERY TAMPILAN DATA
// -----------------------------------------------------------------------------
$search_query = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$filter_kat   = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, trim($_GET['kategori'])) : '';

$where_clauses = [];
if ($search_query !== '') {
    $where_clauses[] = "(nama_fasilitas LIKE '%$search_query%' OR deskripsi LIKE '%$search_query%' OR lokasi LIKE '%$search_query%')";
}
if ($filter_kat !== '') {
    $where_clauses[] = "kategori = '$filter_kat'";
}

$where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$fasilitas_list = [];
$total_fasilitas = 0;
$total_aktif = 0;
$total_unit = 0;

if ($conn) {
    // Hitung statistik
    $q_stat = @mysqli_query($conn, "SELECT COUNT(*) as total, SUM(status_aktif) as total_aktif, SUM(jumlah) as total_unit FROM fasilitas");
    if ($q_stat && $r_stat = mysqli_fetch_assoc($q_stat)) {
        $total_fasilitas = intval($r_stat['total']);
        $total_aktif     = intval($r_stat['total_aktif']);
        $total_unit      = intval($r_stat['total_unit']);
    }

    // Ambil daftar kategori unik untuk filter
    $kategori_list = [];
    $q_kat = @mysqli_query($conn, "SELECT DISTINCT kategori FROM fasilitas ORDER BY kategori ASC");
    if ($q_kat) {
        while ($rk = mysqli_fetch_assoc($q_kat)) {
            if (!empty($rk['kategori'])) $kategori_list[] = $rk['kategori'];
        }
    }

    // Query data fasilitas utama
    $q_data = @mysqli_query($conn, "SELECT * FROM fasilitas $where_sql ORDER BY urutan ASC, id DESC");
    if ($q_data) {
        while ($row = mysqli_fetch_assoc($q_data)) {
            $fasilitas_list[] = $row;
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
    <title>Kelola Fasilitas - SMAN 21 Jakarta</title>
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

                    <!-- ACTIVE MENU: FASILITAS -->
                    <a href="index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-blue-600 text-white font-medium text-sm transition-all shadow-sm">
                        <i class="fa-solid fa-building-columns w-5 text-center text-base"></i>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Kelola Fasilitas Sekolah</h2>
                    <p class="text-xs text-slate-500">Manajemen Sarana, Prasarana & Gedung SMAN 21 Jakarta</p>
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

            <!-- FLASH MESSAGE NOTIFIKASI -->
            <?php if (!empty($message)): ?>
                <div class="p-4 rounded-xl border flex items-center justify-between text-xs font-medium transition-all shadow-sm <?= $message_type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' ?>">
                    <div class="flex items-center gap-2.5">
                        <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check text-emerald-600 text-base' : 'fa-circle-exclamation text-rose-600 text-base' ?>"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- CARDS STATISTIK KILAT -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Fasilitas</p>
                        <h4 class="text-2xl font-extrabold text-slate-800 mt-1"><?= $total_fasilitas ?> <span class="text-xs font-normal text-slate-400">Jenis</span></h4>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Status Aktif</p>
                        <h4 class="text-2xl font-extrabold text-slate-800 mt-1"><?= $total_aktif ?> <span class="text-xs font-normal text-slate-400">Tampil</span></h4>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Akumulasi Unit</p>
                        <h4 class="text-2xl font-extrabold text-slate-800 mt-1"><?= $total_unit ?> <span class="text-xs font-normal text-slate-400">Ruangan / Item</span></h4>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                </div>
            </div>

            <!-- BAR FILTER & SEARCH & BUTTON TAMBAH -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <form method="GET" action="index.php" class="flex flex-col sm:flex-row items-center gap-3 flex-1">
                    <div class="relative w-full sm:w-72">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>" placeholder="Cari nama, lokasi, deskripsi..." class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <select name="kategori" onchange="this.form.submit()" class="w-full sm:w-48 py-2 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Semua Kategori --</option>
                        <option value="Umum" <?= $filter_kat === 'Umum' ? 'selected' : '' ?>>Umum</option>
                        <option value="Laboratorium" <?= $filter_kat === 'Laboratorium' ? 'selected' : '' ?>>Laboratorium</option>
                        <option value="Olahraga" <?= $filter_kat === 'Olahraga' ? 'selected' : '' ?>>Olahraga</option>
                        <option value="Pembelajaran" <?= $filter_kat === 'Pembelajaran' ? 'selected' : '' ?>>Pembelajaran</option>
                        <option value="Seni & Budaya" <?= $filter_kat === 'Seni & Budaya' ? 'selected' : '' ?>>Seni & Budaya</option>
                        <?php foreach ($kategori_list as $kat): ?>
                            <?php if (!in_array($kat, ['Umum', 'Laboratorium', 'Olahraga', 'Pembelajaran', 'Seni & Budaya'])): ?>
                                <option value="<?= htmlspecialchars($kat) ?>" <?= $filter_kat === $kat ? 'selected' : '' ?>><?= htmlspecialchars($kat) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>

                    <?php if ($search_query !== '' || $filter_kat !== ''): ?>
                        <a href="index.php" class="text-xs text-rose-600 hover:text-rose-700 font-semibold px-2 py-1">
                            <i class="fa-solid fa-rotate-left mr-1"></i> Reset
                        </a>
                    <?php endif; ?>
                </form>

                <button type="button" onclick="openModalAdd()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-md flex items-center justify-center gap-2 flex-shrink-0">
                    <i class="fa-solid fa-plus"></i> Tambah Fasilitas Baru
                </button>
            </div>

            <!-- TABEL DATA FASILITAS -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4 w-12 text-center">Urutan</th>
                                <th class="py-3.5 px-4 w-20">Foto</th>
                                <th class="py-3.5 px-4">Nama Fasilitas & Deskripsi</th>
                                <th class="py-3.5 px-4">Kategori</th>
                                <th class="py-3.5 px-4">Jumlah & Lokasi</th>
                                <th class="py-3.5 px-4">Fasilitas Pendukung</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($fasilitas_list)): ?>
                                <?php foreach ($fasilitas_list as $f): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        
                                        <!-- Urutan -->
                                        <td class="py-3.5 px-4 text-center font-bold text-slate-500">
                                            <?= $f['urutan'] ?>
                                        </td>

                                        <!-- Foto Thumbnail -->
                                        <td class="py-3.5 px-4">
                                            <?php if (!empty($f['foto']) && file_exists($upload_dir . $f['foto'])): ?>
                                                <img src="<?= $upload_dir . htmlspecialchars($f['foto']) ?>" alt="<?= htmlspecialchars($f['nama_fasilitas']) ?>" class="w-14 h-11 object-cover rounded-lg border border-slate-200 shadow-sm">
                                            <?php else: ?>
                                                <div class="w-14 h-11 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400">
                                                    <i class="fa-solid fa-image text-base"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Nama & Deskripsi -->
                                        <td class="py-3.5 px-4 max-w-xs">
                                            <p class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($f['nama_fasilitas']) ?></p>
                                            <p class="text-slate-500 line-clamp-2 mt-0.5 text-[11px] leading-relaxed">
                                                <?= htmlspecialchars($f['deskripsi'] ?? 'Tidak ada deskripsi.') ?>
                                            </p>
                                        </td>

                                        <!-- Kategori -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                                <i class="fa-solid fa-tag text-[9px] mr-1 text-slate-400"></i>
                                                <?= htmlspecialchars($f['kategori']) ?>
                                            </span>
                                        </td>

                                        <!-- Jumlah & Lokasi -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <div class="font-semibold text-slate-800">
                                                <i class="fa-solid fa-layer-group text-blue-500 mr-1"></i> <?= $f['jumlah'] ?> Unit
                                            </div>
                                            <div class="text-slate-500 text-[11px] mt-0.5">
                                                <i class="fa-solid fa-location-dot text-rose-500 mr-1"></i> <?= htmlspecialchars($f['lokasi'] ?: 'Lantai / Area Sekolah') ?>
                                            </div>
                                        </td>

                                        <!-- Fasilitas Pendukung -->
                                        <td class="py-3.5 px-4 max-w-xs text-slate-500 text-[11px]">
                                            <?= !empty($f['fasilitas_pendukung']) ? htmlspecialchars($f['fasilitas_pendukung']) : '<span class="italic text-slate-400">-</span>' ?>
                                        </td>

                                        <!-- Status Toggle -->
                                        <td class="py-3.5 px-4 text-center">
                                            <form method="POST" action="index.php" class="inline-block">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold transition <?= $f['status_aktif'] == 1 ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-rose-100 text-rose-800 hover:bg-rose-200' ?>">
                                                    <span class="w-1.5 h-1.5 rounded-full <?= $f['status_aktif'] == 1 ? 'bg-emerald-500' : 'bg-rose-500' ?>"></span>
                                                    <?= $f['status_aktif'] == 1 ? 'Aktif' : 'Nonaktif' ?>
                                                </button>
                                            </form>
                                        </td>

                                        <!-- Aksi Edit & Hapus -->
                                        <td class="py-3.5 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button type="button" onclick='openModalEdit(<?= htmlspecialchars(json_encode($f), ENT_QUOTES, "UTF-8") ?>)' class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 transition" title="Edit Fasilitas">
                                                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                                                </button>
                                                <button type="button" onclick='confirmDelete(<?= $f["id"] ?>, <?= htmlspecialchars(json_encode($f["nama_fasilitas"]), ENT_QUOTES, "UTF-8") ?>)' class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 transition" title="Hapus Fasilitas">
                                                    <i class="fa-solid fa-trash-can text-sm"></i>
                                                </button>
                                            </div>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="py-12 text-center text-slate-400">
                                        <i class="fa-solid fa-building-circle-xmark text-4xl mb-3 block text-slate-300"></i>
                                        Belum ada data fasilitas yang ditemukan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- MODAL FORM (TAMBAH / EDIT FASILITAS) -->
    <div id="modalFasilitas" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all flex flex-col max-h-[90vh]">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white text-sm">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <h3 id="modalTitle" class="text-base font-bold">Tambah Fasilitas Baru</h3>
                </div>
                <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-white transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form id="formFasilitasModal" action="index.php" method="POST" enctype="multipart/form-data" onsubmit="return handleFormSubmit(this)" class="flex-1 overflow-y-auto p-6 space-y-4 text-xs">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="form_id" value="0">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Nama Fasilitas -->
                    <div class="md:col-span-2">
                        <label class="block font-semibold text-slate-700 mb-1">Nama Fasilitas <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama_fasilitas" id="form_nama_fasilitas" required placeholder="Contoh: Laboratorium Komputer 1" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Kategori -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kategori <span class="text-rose-500">*</span></label>
                        <select name="kategori" id="form_kategori" required class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="Umum">Umum</option>
                            <option value="Laboratorium">Laboratorium</option>
                            <option value="Olahraga">Olahraga</option>
                            <option value="Pembelajaran">Pembelajaran</option>
                            <option value="Seni & Budaya">Seni & Budaya</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <!-- Jumlah Unit -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Jumlah Unit <span class="text-rose-500">*</span></label>
                        <input type="number" name="jumlah" id="form_jumlah" min="1" value="1" required class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Lokasi -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Lokasi Gedung / Ruangan</label>
                        <input type="text" name="lokasi" id="form_lokasi" placeholder="Contoh: Gedung B, Lantai 2" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Urutan Tampil -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Urutan Prioritas</label>
                        <input type="number" name="urutan" id="form_urutan" min="1" value="1" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Deskripsi Singkat -->
                    <div class="md:col-span-2">
                        <label class="block font-semibold text-slate-700 mb-1">Deskripsi Fasilitas</label>
                        <textarea name="deskripsi" id="form_deskripsi" rows="3" placeholder="Jelaskan spesifikasi atau deskripsi fasilitas ini..." class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <!-- Fasilitas Pendukung -->
                    <div class="md:col-span-2">
                        <label class="block font-semibold text-slate-700 mb-1">Fasilitas Pendukung (Fitur)</label>
                        <input type="text" name="fasilitas_pendukung" id="form_fasilitas_pendukung" placeholder="Contoh: AC 2 PK, Projector 4K, Wi-Fi 100Mbps, 36 Unit PC" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Foto Upload -->
                    <div class="md:col-span-2">
                        <label class="block font-semibold text-slate-700 mb-1">Upload Foto Fasilitas</label>
                        <input type="file" name="foto" id="form_foto" accept="image/*" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-[10px] text-slate-400 mt-1">* Format gambar: JPG, PNG, WEBP (Maksimal 2MB)</p>
                    </div>

                    <!-- Status Aktif Checkbox -->
                    <div class="md:col-span-2 flex items-center gap-2 pt-2">
                        <input type="checkbox" name="status_aktif" id="form_status_aktif" value="1" checked class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                        <label for="form_status_aktif" class="font-semibold text-slate-700">Tampilkan fasilitas ini di portal publik website</label>
                    </div>
                </div>

                <!-- Footer Modal Actions -->
                <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold transition">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitForm" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold transition shadow-md flex items-center gap-1.5">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL CONFIRM DELETE -->
    <div id="modalDelete" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden p-6 text-center space-y-4">
            <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 mx-auto flex items-center justify-center text-xl">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <h4 class="text-base font-bold text-slate-800">Hapus Fasilitas Ini?</h4>
                <p class="text-xs text-slate-500 mt-1">
                    Apakah Anda yakin ingin menghapus <span id="delete_item_name" class="font-bold text-slate-700"></span>? Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>
            <form method="POST" action="index.php" onsubmit="return handleFormSubmit(this)" class="flex items-center justify-center gap-2 pt-2">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id" value="0">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                    Batal
                </button>
                <button type="submit" id="btnDeleteSubmit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition shadow-md">
                    Ya, Hapus Data
                </button>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT CONTROL -->
    <script>
        // Sidebar Toggle Mobile / Desktop
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('-ml-64');
        });

        // Live Clock Function
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').textContent = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateClock, 1000);

        // Flag untuk cegah double submission
        let isSubmitting = false;

        function handleFormSubmit(form) {
            if (isSubmitting) return false;
            isSubmitting = true;
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                // Tunda pemutusan/disable tombol menggunakan setTimeout agar browser sempat mengirim data POST ke server
                setTimeout(function() {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> Memproses...';
                }, 10);
            }
            return true;
        }

        // Modal Add & Edit Handling
        function openModalAdd() {
            isSubmitting = false;
            document.getElementById('modalTitle').textContent = 'Tambah Fasilitas Baru';
            document.getElementById('form_id').value = '0';
            document.getElementById('form_nama_fasilitas').value = '';
            document.getElementById('form_kategori').value = 'Umum';
            document.getElementById('form_jumlah').value = '1';
            document.getElementById('form_lokasi').value = '';
            document.getElementById('form_urutan').value = '1';
            document.getElementById('form_deskripsi').value = '';
            document.getElementById('form_fasilitas_pendukung').value = '';
            document.getElementById('form_foto').value = '';
            document.getElementById('form_status_aktif').checked = true;
            
            const btn = document.getElementById('btnSubmitForm');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan Data';
            }

            document.getElementById('modalFasilitas').classList.remove('hidden');
        }

        function openModalEdit(data) {
            isSubmitting = false;
            document.getElementById('modalTitle').textContent = 'Edit Data Fasilitas';
            document.getElementById('form_id').value = data.id;
            document.getElementById('form_nama_fasilitas').value = data.nama_fasilitas || '';
            document.getElementById('form_kategori').value = data.kategori || 'Umum';
            document.getElementById('form_jumlah').value = data.jumlah || '1';
            document.getElementById('form_lokasi').value = data.lokasi || '';
            document.getElementById('form_urutan').value = data.urutan || '1';
            document.getElementById('form_deskripsi').value = data.deskripsi || '';
            document.getElementById('form_fasilitas_pendukung').value = data.fasilitas_pendukung || '';
            document.getElementById('form_foto').value = '';
            document.getElementById('form_status_aktif').checked = (parseInt(data.status_aktif) === 1);
            
            const btn = document.getElementById('btnSubmitForm');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan Data';
            }

            document.getElementById('modalFasilitas').classList.remove('hidden');
        }

        function closeModal() {
            isSubmitting = false;
            document.getElementById('modalFasilitas').classList.add('hidden');
        }

        function confirmDelete(id, nama) {
            isSubmitting = false;
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_item_name').textContent = '"' + nama + '"';
            
            const btn = document.getElementById('btnDeleteSubmit');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = 'Ya, Hapus Data';
            }

            document.getElementById('modalDelete').classList.remove('hidden');
        }

        function closeDeleteModal() {
            isSubmitting = false;
            document.getElementById('modalDelete').classList.add('hidden');
        }
    </script>
</body>
</html>
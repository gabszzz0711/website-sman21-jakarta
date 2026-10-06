<?php
// admin/ai-assistant/kategori.php

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
// KONFIGURASI DATABASE BYETHOST SMAN 21 JAKARTA
// -----------------------------------------------------------------------------
$host = 'sql305.byethost3.com'; 
$user = 'b3_43046986';          
$pass = 'J@karta5';             
$db   = 'b3_43046986_sman21jkt'; 

// Nonaktifkan strict mode exception agar koneksi gagal tidak menyebabkan HTTP 500
mysqli_report(MYSQLI_REPORT_OFF);

// Koneksi ke database
$conn = @mysqli_connect($host, $user, $pass, $db);

if ($conn) {
    mysqli_set_charset($conn, "utf8mb4");
}

// Pesan notifikasi flash
$flash_message = '';
$flash_type = ''; // 'success' atau 'danger'

// -----------------------------------------------------------------------------
// PENANGANAN AKSI POST / GET (CRUD)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. TAMBAH KATEGORI
    if ($action === 'create') {
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');
        $deskripsi     = trim($_POST['deskripsi'] ?? '');
        $urutan        = (int)($_POST['urutan'] ?? 1);
        $status_aktif  = isset($_POST['status_aktif']) ? 1 : 0;

        if (!empty($nama_kategori) && $conn) {
            $stmt = $conn->prepare("INSERT INTO ai_kategori (nama_kategori, deskripsi, urutan, status_aktif) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $nama_kategori, $deskripsi, $urutan, $status_aktif);
            if ($stmt->execute()) {
                $flash_message = "Kategori AI berhasil ditambahkan!";
                $flash_type = "success";
            } else {
                $flash_message = "Gagal menambahkan kategori: " . $conn->error;
                $flash_type = "danger";
            }
            $stmt->close();
        } else {
            $flash_message = "Nama kategori wajib diisi!";
            $flash_type = "danger";
        }
    }

    // 2. EDIT KATEGORI
    elseif ($action === 'update') {
        $id            = (int)($_POST['id'] ?? 0);
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');
        $deskripsi     = trim($_POST['deskripsi'] ?? '');
        $urutan        = (int)($_POST['urutan'] ?? 1);
        $status_aktif  = isset($_POST['status_aktif']) ? 1 : 0;

        if ($id > 0 && !empty($nama_kategori) && $conn) {
            $stmt = $conn->prepare("UPDATE ai_kategori SET nama_kategori = ?, deskripsi = ?, urutan = ?, status_aktif = ? WHERE id = ?");
            $stmt->bind_param("ssiii", $nama_kategori, $deskripsi, $urutan, $status_aktif, $id);
            if ($stmt->execute()) {
                $flash_message = "Kategori AI berhasil diperbarui!";
                $flash_type = "success";
            } else {
                $flash_message = "Gagal memperbarui kategori: " . $conn->error;
                $flash_type = "danger";
            }
            $stmt->close();
        }
    }

    // 3. TOGGLE STATUS AKTIF
    elseif ($action === 'toggle_status') {
        $id     = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);

        if ($id > 0 && $conn) {
            $stmt = $conn->prepare("UPDATE ai_kategori SET status_aktif = ? WHERE id = ?");
            $stmt->bind_param("ii", $status, $id);
            if ($stmt->execute()) {
                $flash_message = "Status kategori berhasil diubah!";
                $flash_type = "success";
            }
            $stmt->close();
        }
    }

    // 4. HAPUS KATEGORI
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0 && $conn) {
            // Cek apakah ada knowledge base yang menggunakan kategori ini
            $check_stmt = $conn->prepare("SELECT COUNT(*) as total FROM ai_knowledge WHERE kategori_id = ?");
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $res = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();

            if ($res['total'] > 0) {
                $flash_message = "Kategori tidak dapat dihapus karena masih digunakan oleh " . $res['total'] . " item Knowledge Base AI!";
                $flash_type = "danger";
            } else {
                $del_stmt = $conn->prepare("DELETE FROM ai_kategori WHERE id = ?");
                $del_stmt->bind_param("i", $id);
                if ($del_stmt->execute()) {
                    $flash_message = "Kategori AI berhasil dihapus!";
                    $flash_type = "success";
                } else {
                    $flash_message = "Gagal menghapus kategori: " . $conn->error;
                    $flash_type = "danger";
                }
                $del_stmt->close();
            }
        }
    }
}

// -----------------------------------------------------------------------------
// MENGAMBIL DATA KATEGORI DARI DATABASE
// -----------------------------------------------------------------------------
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$kategori_list = [];
$total_kategori = 0;
$total_aktif = 0;
$total_nonaktif = 0;

if ($conn) {
    // Hitung Ringkasan Statistik
    $res_stat = @mysqli_query($conn, "SELECT 
        COUNT(*) as total, 
        SUM(CASE WHEN status_aktif = 1 THEN 1 ELSE 0 END) as total_aktif,
        SUM(CASE WHEN status_aktif = 0 THEN 1 ELSE 0 END) as total_nonaktif 
        FROM ai_kategori");
    if ($res_stat) {
        $stat_row = mysqli_fetch_assoc($res_stat);
        $total_kategori = (int)$stat_row['total'];
        $total_aktif    = (int)$stat_row['total_aktif'];
        $total_nonaktif = (int)$stat_row['total_nonaktif'];
    }

    // Query Data Kategori dengan jumlah Knowledge terkait
    $sql = "SELECT k.*, COUNT(w.id) AS total_knowledge 
            FROM ai_kategori k 
            LEFT JOIN ai_knowledge w ON k.id = w.kategori_id ";
    
    if (!empty($search_query)) {
        $safe_search = mysqli_real_escape_string($conn, $search_query);
        $sql .= "WHERE k.nama_kategori LIKE '%$safe_search%' OR k.deskripsi LIKE '%$safe_search%' ";
    }
    
    $sql .= "GROUP BY k.id ORDER BY k.urutan ASC, k.id ASC";

    $result = @mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $kategori_list[] = $row;
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
    <title>Kategori AI Assistant - SMAN 21 Jakarta</title>
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

            <!-- Kelola Sekolah -->
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

            <!-- Module AI Assistant (ACTIVE MODULE) -->
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 mb-2">Smart System</p>
                <nav class="space-y-1">
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white cursor-pointer text-sm font-medium transition-colors list-none">
                            <span class="flex items-center gap-3 text-emerald-400 font-semibold">
                                <i class="fa-solid fa-robot w-5 text-center"></i>
                                <span>AI Assistant</span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="knowledge.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800 text-slate-300">Knowledge Base</a>
                            <a href="kategori.php" class="block py-1.5 px-2 rounded bg-blue-600 text-white font-medium shadow-sm">Kategori AI</a>
                            <a href="pengaturan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800 text-slate-300">Pengaturan AI</a>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Kategori AI Assistant</h2>
                    <p class="text-xs text-slate-500">Kelola Pengelompokan Pengetahuan Sistem Cerdas SMAN 21 Jakarta</p>
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

            <!-- NOTIFIKASI FLASH MESSAGE -->
            <?php if (!empty($flash_message)): ?>
                <div class="p-4 rounded-xl shadow-sm text-xs font-medium flex items-center justify-between border <?= $flash_type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?>">
                    <div class="flex items-center gap-2.5">
                        <i class="fa-solid <?= $flash_type === 'success' ? 'fa-circle-check text-emerald-500' : 'fa-circle-exclamation text-rose-500' ?> text-base"></i>
                        <span><?= htmlspecialchars($flash_message) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- CARDS STATISTIK KATEGORI AI -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Kategori</p>
                        <h4 class="text-3xl font-extrabold text-slate-800 mt-1"><?= $total_kategori ?></h4>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Kategori Aktif</p>
                        <h4 class="text-3xl font-extrabold text-emerald-600 mt-1"><?= $total_aktif ?></h4>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Kategori Nonaktif</p>
                        <h4 class="text-3xl font-extrabold text-amber-600 mt-1"><?= $total_nonaktif ?></h4>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-circle-pause"></i>
                    </div>
                </div>
            </div>

            <!-- TABEL DAN AKSI UTAMA -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm flex flex-col overflow-hidden">
                
                <!-- HEADER BAR TABEL -->
                <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Daftar Kategori AI</h3>
                        <p class="text-xs text-slate-500">Seluruh topik pengetahuannya yang digunakan oleh AI Assistant untuk klasifikasi informasi</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Form Pencarian -->
                        <form method="GET" action="kategori.php" class="relative">
                            <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>" placeholder="Cari kategori..." class="w-48 sm:w-64 pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <?php if (!empty($search_query)): ?>
                                <a href="kategori.php" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                                    <i class="fa-solid fa-circle-xmark"></i>
                                </a>
                            <?php endif; ?>
                        </form>

                        <!-- Tombol Tambah Modal -->
                        <button onclick="openAddModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-sm flex items-center gap-2">
                            <i class="fa-solid fa-plus"></i>
                            <span>Tambah Kategori</span>
                        </button>
                    </div>
                </div>

                <!-- TABEL DATA -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-100/80 text-slate-700 font-semibold uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4 w-16 text-center">Urutan</th>
                                <th class="py-3.5 px-4">Nama Kategori</th>
                                <th class="py-3.5 px-4">Deskripsi</th>
                                <th class="py-3.5 px-4 text-center">Knowledge Terkait</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-right w-36">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php if (!empty($kategori_list)): ?>
                                <?php foreach ($kategori_list as $kat): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <!-- Urutan -->
                                        <td class="py-3.5 px-4 text-center font-semibold text-slate-500">
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 text-slate-700 font-mono text-xs">
                                                <?= (int)$kat['urutan'] ?>
                                            </span>
                                        </td>

                                        <!-- Nama Kategori -->
                                        <td class="py-3.5 px-4">
                                            <p class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($kat['nama_kategori']) ?></p>
                                            <p class="text-[11px] text-slate-400">ID: #<?= $kat['id'] ?></p>
                                        </td>

                                        <!-- Deskripsi -->
                                        <td class="py-3.5 px-4 max-w-sm">
                                            <p class="text-slate-600 truncate-2-lines">
                                                <?= !empty($kat['deskripsi']) ? htmlspecialchars($kat['deskripsi']) : '<span class="italic text-slate-400">Tidak ada deskripsi</span>' ?>
                                            </p>
                                        </td>

                                        <!-- Jumlah Knowledge Base Terkait -->
                                        <td class="py-3.5 px-4 text-center">
                                            <a href="knowledge.php?kategori=<?= $kat['id'] ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 font-medium hover:bg-blue-100 transition">
                                                <i class="fa-solid fa-brain text-[10px]"></i>
                                                <span><?= (int)$kat['total_knowledge'] ?> Data</span>
                                            </a>
                                        </td>

                                        <!-- Status Aktif -->
                                        <td class="py-3.5 px-4 text-center">
                                            <form method="POST" action="kategori.php" class="inline-block">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= $kat['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $kat['status_aktif'] == 1 ? 0 : 1 ?>">
                                                <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold transition <?= $kat['status_aktif'] == 1 ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>" title="Klik untuk mengubah status">
                                                    <span class="w-2 h-2 rounded-full <?= $kat['status_aktif'] == 1 ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
                                                    <?= $kat['status_aktif'] == 1 ? 'Aktif' : 'Nonaktif' ?>
                                                </button>
                                            </form>
                                        </td>

                                        <!-- Aksi Edit & Hapus -->
                                        <td class="py-3.5 px-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button onclick='openEditModal(<?= json_encode($kat, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="p-2 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 transition" title="Edit Kategori">
                                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                                </button>

                                                <form method="POST" action="kategori.php" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')" class="inline-block">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $kat['id'] ?>">
                                                    <button type="submit" class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition" title="Hapus Kategori">
                                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-slate-400 italic">
                                        <i class="fa-solid fa-folder-open text-3xl mb-3 text-slate-300 block"></i>
                                        Tidak ada kategori AI yang ditemukan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- FOOTER INFO TABEL -->
                <div class="p-4 border-t border-slate-200 bg-slate-50/50 text-xs text-slate-500 flex justify-between items-center">
                    <span>Menampilkan <?= count($kategori_list) ?> entri kategori</span>
                    <a href="knowledge.php" class="text-blue-600 font-semibold hover:underline flex items-center gap-1">
                        <span>Kelola Knowledge Base AI</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>

            </div>

        </main>
    </div>

    <!-- MODAL TAMBAH KATEGORI -->
    <div id="addModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-5 animate-in fade-in zoom-in duration-200">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="fa-solid fa-folder-plus text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Tambah Kategori AI Baru</h3>
                        <p class="text-xs text-slate-500">Buat kategori baru untuk pengelompokan AI</p>
                    </div>
                </div>
                <button onclick="closeAddModal()" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form method="POST" action="kategori.php" class="space-y-4">
                <input type="hidden" name="action" value="create">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_kategori" required placeholder="Contoh: Kurikulum & Akademik" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Singkat</label>
                    <textarea name="deskripsi" rows="3" placeholder="Penjelasan cakupan informasi kategori ini..." class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Urutan Tampil</label>
                        <input type="number" name="urutan" value="1" min="1" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="flex items-center pt-5">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="status_aktif" value="1" checked class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                            <span class="ml-2.5 text-xs font-semibold text-slate-700">Status Aktif</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeAddModal()" class="px-4 py-2 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-md">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT KATEGORI -->
    <div id="editModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i class="fa-solid fa-pen-to-square text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Edit Kategori AI</h3>
                        <p class="text-xs text-slate-500">Ubah rincian kategori informasi AI</p>
                    </div>
                </div>
                <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form method="POST" action="kategori.php" class="space-y-4">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_kategori" id="edit_nama" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Singkat</label>
                    <textarea name="deskripsi" id="edit_deskripsi" rows="3" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Urutan Tampil</label>
                        <input type="number" name="urutan" id="edit_urutan" min="1" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="flex items-center pt-5">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="status_aktif" id="edit_status" value="1" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                            <span class="ml-2.5 text-xs font-semibold text-slate-700">Status Aktif</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-xl transition shadow-md">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT LOKAL -->
    <script>
        // Toggle responsive sidebar
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
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
        }
        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
        }

        function openEditModal(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_nama').value = data.nama_kategori;
            document.getElementById('edit_deskripsi').value = data.deskripsi || '';
            document.getElementById('edit_urutan').value = data.urutan;
            document.getElementById('edit_status').checked = (parseInt(data.status_aktif) === 1);
            document.getElementById('editModal').classList.remove('hidden');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>
</body>
</html>

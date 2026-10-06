<?php
// admin/ai-assistant/knowledge.php

// Tampilkan error PHP untuk mempermudah debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Cek autentikasi login admin
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['username'])) {
    // header('Location: ../login.php');
    // exit;
}

$admin_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'admin21';

// -----------------------------------------------------------------------------
// KONFIGURASI DATABASE
// -----------------------------------------------------------------------------
$host = 'sql305.byethost3.com';
$user = 'b3_43046986';
$pass = 'J@karta5';
$db   = 'b3_43046986_sman21jkt';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect($host, $user, $pass, $db);

// Handler Pesan Notifikasi Flash
$alert_msg = '';
$alert_type = '';
if (isset($_SESSION['flash_msg'])) {
    $alert_msg = $_SESSION['flash_msg']['text'];
    $alert_type = $_SESSION['flash_msg']['type'];
    unset($_SESSION['flash_msg']);
}

// -----------------------------------------------------------------------------
// PROSES AKSI DATABASE (POST REQUEST)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = $_POST['action'] ?? '';

    // 1. TAMBAH KNOWLEDGE BASE
    if ($action === 'add') {
        $kategori_id      = intval($_POST['kategori_id'] ?? 0);
        $pertanyaan       = trim($_POST['pertanyaan'] ?? '');
        $jawaban          = trim($_POST['jawaban'] ?? '');
        $keyword          = trim($_POST['keyword'] ?? '');
        $sumber_informasi = trim($_POST['sumber_informasi'] ?? '');
        $prioritas        = intval($_POST['prioritas'] ?? 1);
        $status_aktif     = isset($_POST['status_aktif']) ? 1 : 0;

        if ($kategori_id > 0 && !empty($pertanyaan) && !empty($jawaban)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO ai_knowledge (kategori_id, pertanyaan, jawaban, keyword, sumber_informasi, prioritas, status_aktif) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "issssii", $kategori_id, $pertanyaan, $jawaban, $keyword, $sumber_informasi, $prioritas, $status_aktif);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['flash_msg'] = ['text' => 'Data Knowledge Base berhasil ditambahkan!', 'type' => 'success'];
                } else {
                    $_SESSION['flash_msg'] = ['text' => 'Gagal menambah data: ' . mysqli_error($conn), 'type' => 'danger'];
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $_SESSION['flash_msg'] = ['text' => 'Mohon lengkapi seluruh kolom wajib (Kategori, Pertanyaan, Jawaban).', 'type' => 'danger'];
        }
        header('Location: knowledge.php');
        exit;
    }

    // 2. EDIT KNOWLEDGE BASE
    if ($action === 'edit') {
        $id               = intval($_POST['id'] ?? 0);
        $kategori_id      = intval($_POST['kategori_id'] ?? 0);
        $pertanyaan       = trim($_POST['pertanyaan'] ?? '');
        $jawaban          = trim($_POST['jawaban'] ?? '');
        $keyword          = trim($_POST['keyword'] ?? '');
        $sumber_informasi = trim($_POST['sumber_informasi'] ?? '');
        $prioritas        = intval($_POST['prioritas'] ?? 1);
        $status_aktif     = isset($_POST['status_aktif']) ? 1 : 0;

        if ($id > 0 && $kategori_id > 0 && !empty($pertanyaan) && !empty($jawaban)) {
            $stmt = mysqli_prepare($conn, "UPDATE ai_knowledge SET kategori_id = ?, pertanyaan = ?, jawaban = ?, keyword = ?, sumber_informasi = ?, prioritas = ?, status_aktif = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "issssiii", $kategori_id, $pertanyaan, $jawaban, $keyword, $sumber_informasi, $prioritas, $status_aktif, $id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['flash_msg'] = ['text' => 'Data Knowledge Base berhasil diperbarui!', 'type' => 'success'];
                } else {
                    $_SESSION['flash_msg'] = ['text' => 'Gagal memperbarui data: ' . mysqli_error($conn), 'type' => 'danger'];
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $_SESSION['flash_msg'] = ['text' => 'Mohon isi seluruh field wajib dengan benar.', 'type' => 'danger'];
        }
        header('Location: knowledge.php');
        exit;
    }

    // 3. HAPUS KNOWLEDGE BASE
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = mysqli_prepare($conn, "DELETE FROM ai_knowledge WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['flash_msg'] = ['text' => 'Data Knowledge Base berhasil dihapus.', 'type' => 'success'];
                } else {
                    $_SESSION['flash_msg'] = ['text' => 'Gagal menghapus data.', 'type' => 'danger'];
                }
                mysqli_stmt_close($stmt);
            }
        }
        header('Location: knowledge.php');
        exit;
    }

    // 4. TOGGLE STATUS AKTIF
    if ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE ai_knowledge SET status_aktif = IF(status_aktif = 1, 0, 1) WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['flash_msg'] = ['text' => 'Status aktif Knowledge Base berhasil diperbarui.', 'type' => 'success'];
                }
                mysqli_stmt_close($stmt);
            }
        }
        header('Location: knowledge.php');
        exit;
    }
}

// -----------------------------------------------------------------------------
// PENGAMBILAN DATA KATEGORI & FILTERING
// -----------------------------------------------------------------------------
$kategori_list = [];
if ($conn) {
    $q_kat = mysqli_query($conn, "SELECT id, nama_kategori FROM ai_kategori WHERE status_aktif = 1 ORDER BY urutan ASC, nama_kategori ASC");
    if ($q_kat) {
        while ($r = mysqli_fetch_assoc($q_kat)) {
            $kategori_list[] = $r;
        }
    }
}

// Param Filter & Pencarian
$search_query = trim($_GET['q'] ?? '');
$filter_kat   = intval($_GET['kategori_id'] ?? 0);
$filter_stat  = $_GET['status'] ?? '';

$where_clauses = ["1=1"];
$params = [];
$types = "";

if (!empty($search_query)) {
    $where_clauses[] = "(k.pertanyaan LIKE ? OR k.jawaban LIKE ? OR k.keyword LIKE ? OR k.sumber_informasi LIKE ?)";
    $like = '%' . $search_query . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "ssss";
}

if ($filter_kat > 0) {
    $where_clauses[] = "k.kategori_id = ?";
    $params[] = $filter_kat;
    $types .= "i";
}

if ($filter_stat !== '') {
    $where_clauses[] = "k.status_aktif = ?";
    $params[] = intval($filter_stat);
    $types .= "i";
}

$where_sql = implode(" AND ", $where_clauses);

// Metrik Statistik Ringkas
$total_knowledge = 0;
$total_active    = 0;
$total_high_prio = 0;
$total_kategori  = count($kategori_list);

if ($conn) {
    $res_tot = mysqli_query($conn, "SELECT COUNT(*) as total, SUM(CASE WHEN status_aktif=1 THEN 1 ELSE 0 END) as active, SUM(CASE WHEN prioritas >= 3 THEN 1 ELSE 0 END) as high_prio FROM ai_knowledge");
    if ($res_tot && $row_tot = mysqli_fetch_assoc($res_tot)) {
        $total_knowledge = $row_tot['total'] ?? 0;
        $total_active    = $row_tot['active'] ?? 0;
        $total_high_prio = $row_tot['high_prio'] ?? 0;
    }
}

// Query Utama Data Knowledge Base
$knowledge_data = [];
if ($conn) {
    $sql_list = "SELECT k.*, c.nama_kategori 
                 FROM ai_knowledge k 
                 LEFT JOIN ai_kategori c ON k.kategori_id = c.id 
                 WHERE $where_sql 
                 ORDER BY k.prioritas DESC, k.id DESC";

    if (!empty($params)) {
        $stmt_list = mysqli_prepare($conn, $sql_list);
        if ($stmt_list) {
            mysqli_stmt_bind_param($stmt_list, $types, ...$params);
            mysqli_stmt_execute($stmt_list);
            $res = mysqli_stmt_get_result($stmt_list);
            while ($row = mysqli_fetch_assoc($res)) {
                $knowledge_data[] = $row;
            }
            mysqli_stmt_close($stmt_list);
        }
    } else {
        $res = mysqli_query($conn, $sql_list);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $knowledge_data[] = $row;
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
    <title>Knowledge Base AI Assistant - SMAN 21 Jakarta</title>
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
        .sidebar-scroll::-webkit-scrollbar { width: 5px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.15); border-radius: 4px; }
        .modal-backdrop { background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
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
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white cursor-pointer text-sm font-medium transition-colors list-none text-emerald-400">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-robot w-5 text-center"></i>
                                <span>AI Assistant</span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="knowledge.php" class="block py-1.5 px-2 rounded bg-blue-600 text-white font-medium shadow-sm">Knowledge Base</a>
                            <a href="kategori.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Kategori AI</a>
                            <a href="pengaturan.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Pengaturan AI</a>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Knowledge Base AI</h2>
                    <p class="text-xs text-slate-500">Kelola Basis Pengetahuan & Pertanyaan Respon Asisten AI SMAN 21 Jakarta</p>
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

            <!-- NOTIFIKASI FLASH ALERT -->
            <?php if (!empty($alert_msg)): ?>
                <div class="p-4 rounded-xl text-sm font-medium flex items-center justify-between shadow-sm <?= $alert_type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid <?= $alert_type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-triangle-exclamation text-rose-600' ?> text-lg"></i>
                        <span><?= htmlspecialchars($alert_msg) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- BARIS STATISTIK RINGKAS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Knowledge</p>
                        <h4 class="text-2xl font-extrabold text-slate-800 mt-1"><?= $total_knowledge ?> <span class="text-xs font-normal text-slate-400">Entri</span></h4>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-brain"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Knowledge Aktif</p>
                        <h4 class="text-2xl font-extrabold text-slate-800 mt-1"><?= $total_active ?> <span class="text-xs font-normal text-slate-400">Ready</span></h4>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Prioritas Tinggi</p>
                        <h4 class="text-2xl font-extrabold text-slate-800 mt-1"><?= $total_high_prio ?> <span class="text-xs font-normal text-slate-400">Entri</span></h4>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-star"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Kategori AI</p>
                        <h4 class="text-2xl font-extrabold text-slate-800 mt-1"><?= $total_kategori ?> <span class="text-xs font-normal text-slate-400">Aktif</span></h4>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                </div>
            </div>

            <!-- TOOLBAR AKSI & FILTER DATA -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    
                    <!-- Form Filter & Pencarian -->
                    <form method="GET" action="knowledge.php" class="flex flex-wrap items-center gap-3 flex-1">
                        <!-- Input Cari -->
                        <div class="relative flex-1 min-w-[220px]">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>" placeholder="Cari pertanyaan, jawaban, keyword..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        </div>

                        <!-- Filter Kategori -->
                        <select name="kategori_id" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                            <option value="0">-- Semua Kategori --</option>
                            <?php foreach ($kategori_list as $kat): ?>
                                <option value="<?= $kat['id'] ?>" <?= $filter_kat == $kat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kat['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Filter Status -->
                        <select name="status" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                            <option value="">-- Semua Status --</option>
                            <option value="1" <?= $filter_stat === '1' ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= $filter_stat === '0' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>

                        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium rounded-xl transition flex items-center gap-1.5">
                            <i class="fa-solid fa-filter"></i> Filter
                        </button>

                        <?php if (!empty($search_query) || $filter_kat > 0 || $filter_stat !== ''): ?>
                            <a href="knowledge.php" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium rounded-xl transition">
                                <i class="fa-solid fa-rotate-left"></i> Reset
                            </a>
                        <?php endif; ?>
                    </form>

                    <!-- Tombol Tambah Knowledge -->
                    <button onclick="openAddModal()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-sm flex items-center justify-center gap-2 flex-shrink-0">
                        <i class="fa-solid fa-plus"></i>
                        <span>Tambah Data Knowledge</span>
                    </button>
                </div>
            </div>

            <!-- TABEL KNOWLEDGE BASE -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-900 text-slate-300 font-semibold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="py-3.5 px-4 w-12 text-center">No</th>
                                <th class="py-3.5 px-4 w-36">Kategori</th>
                                <th class="py-3.5 px-4 min-w-[220px]">Pertanyaan & Keyword</th>
                                <th class="py-3.5 px-4 min-w-[260px]">Jawaban Respon AI</th>
                                <th class="py-3.5 px-4 w-24 text-center">Prioritas</th>
                                <th class="py-3.5 px-4 w-28 text-center">Status</th>
                                <th class="py-3.5 px-4 w-32 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($knowledge_data)): ?>
                                <?php $no = 1; foreach ($knowledge_data as $item): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-3.5 px-4 text-center font-medium text-slate-400"><?= $no++ ?></td>
                                        
                                        <!-- Kategori Badge -->
                                        <td class="py-3.5 px-4">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-medium text-[11px] border border-blue-100">
                                                <i class="fa-solid fa-folder-open text-[10px]"></i>
                                                <?= htmlspecialchars($item['nama_kategori'] ?? 'Umum') ?>
                                            </span>
                                        </td>

                                        <!-- Pertanyaan & Keyword -->
                                        <td class="py-3.5 px-4">
                                            <p class="font-semibold text-slate-800 text-xs line-clamp-2"><?= htmlspecialchars($item['pertanyaan']) ?></p>
                                            <?php if (!empty($item['keyword'])): ?>
                                                <div class="flex items-center gap-1 mt-1 text-[11px] text-slate-400">
                                                    <i class="fa-solid fa-key text-[10px] text-amber-500"></i>
                                                    <span class="truncate max-w-xs"><?= htmlspecialchars($item['keyword']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Jawaban Respon AI -->
                                        <td class="py-3.5 px-4">
                                            <p class="text-slate-600 text-xs line-clamp-2 leading-relaxed"><?= htmlspecialchars($item['jawaban']) ?></p>
                                            <?php if (!empty($item['sumber_informasi'])): ?>
                                                <p class="text-[10px] text-slate-400 mt-1 italic">
                                                    <i class="fa-solid fa-link mr-0.5"></i> Sumber: <?= htmlspecialchars($item['sumber_informasi']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Prioritas Badge -->
                                        <td class="py-3.5 px-4 text-center">
                                            <?php if ($item['prioritas'] >= 3): ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                                    <i class="fa-solid fa-fire text-[9px]"></i> P<?= $item['prioritas'] ?> Tinggi
                                                </span>
                                            <?php elseif ($item['prioritas'] == 2): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-800">
                                                    P2 Sedang
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">
                                                    P1 Normal
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Status Switch -->
                                        <td class="py-3.5 px-4 text-center">
                                            <form method="POST" action="knowledge.php" class="inline-block">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                <button type="submit" title="Klik untuk mengubah status" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold transition <?= $item['status_aktif'] == 1 ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-rose-100 text-rose-800 hover:bg-rose-200' ?>">
                                                    <span class="w-1.5 h-1.5 rounded-full <?= $item['status_aktif'] == 1 ? 'bg-emerald-500' : 'bg-rose-500' ?>"></span>
                                                    <?= $item['status_aktif'] == 1 ? 'Aktif' : 'Nonaktif' ?>
                                                </button>
                                            </form>
                                        </td>

                                        <!-- Akses Aksi -->
                                        <td class="py-3.5 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <!-- Detail -->
                                                <button onclick="openDetailModal(<?= htmlspecialchars(json_encode($item)) ?>)" title="Lihat Detail" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition">
                                                    <i class="fa-solid fa-eye text-xs"></i>
                                                </button>

                                                <!-- Edit -->
                                                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($item)) ?>)" title="Edit Knowledge" class="w-7 h-7 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 flex items-center justify-center transition">
                                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                                </button>

                                                <!-- Delete -->
                                                <button onclick="openDeleteModal(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['pertanyaan'])) ?>')" title="Hapus" class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center transition">
                                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400">
                                        <i class="fa-solid fa-inbox text-3xl mb-2 text-slate-300"></i>
                                        <p class="text-xs font-medium">Belum ada data Knowledge Base yang ditemukan.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- ----------------------------------------------------------------------------- -->
    <!-- MODAL TAMBAH KNOWLEDGE -->
    <!-- ----------------------------------------------------------------------------- -->
    <div id="addModal" class="fixed inset-0 z-50 hidden modal-backdrop flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden transform transition-all">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-plus text-blue-400"></i>
                    <h3 class="text-base font-bold">Tambah Data Knowledge Base AI</h3>
                </div>
                <button onclick="closeAddModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form method="POST" action="knowledge.php" class="p-6 space-y-4 text-xs">
                <input type="hidden" name="action" value="add">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kategori AI <span class="text-rose-500">*</span></label>
                        <select name="kategori_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 transition">
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($kategori_list as $kat): ?>
                                <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tingkat Prioritas</label>
                        <select name="prioritas" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 transition">
                            <option value="1">1 - Normal (Standar)</option>
                            <option value="2">2 - Sedang (Diutamakan)</option>
                            <option value="3">3 - Tinggi (Prioritas Utama)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Pertanyaan / Input Pengguna <span class="text-rose-500">*</span></label>
                    <textarea name="pertanyaan" rows="2" required placeholder="Contoh: Apa saja syarat pendaftaran PPDB SMAN 21 Jakarta?" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 transition"></textarea>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Jawaban Respon AI <span class="text-rose-500">*</span></label>
                    <textarea name="jawaban" rows="4" required placeholder="Tuliskan jawaban lengkap resmi sekolah yang akan disampaikan oleh AI Assistant..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 transition"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kata Kunci (Keywords)</label>
                        <input type="text" name="keyword" placeholder="ppdb, pendaftaran, kuota, syarat" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 transition">
                        <p class="text-[10px] text-slate-400 mt-1">Pisahkan kata kunci dengan koma</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Sumber Informasi / Dokumen</label>
                        <input type="text" name="sumber_informasi" placeholder="Juknis PPDB 2026 / Edaran Kepala Sekolah" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" id="add_status" name="status_aktif" value="1" checked class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                    <label for="add_status" class="font-medium text-slate-700 cursor-pointer">Aktifkan data ini langsung di AI Assistant</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeAddModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-xl transition">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition shadow-md">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ----------------------------------------------------------------------------- -->
    <!-- MODAL EDIT KNOWLEDGE -->
    <!-- ----------------------------------------------------------------------------- -->
    <div id="editModal" class="fixed inset-0 z-50 hidden modal-backdrop flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden transform transition-all">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-blue-400"></i>
                    <h3 class="text-base font-bold">Edit Knowledge Base AI</h3>
                </div>
                <button onclick="closeEditModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form method="POST" action="knowledge.php" class="p-6 space-y-4 text-xs">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id" value="">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kategori AI <span class="text-rose-500">*</span></label>
                        <select id="edit_kategori_id" name="kategori_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 transition">
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($kategori_list as $kat): ?>
                                <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tingkat Prioritas</label>
                        <select id="edit_prioritas" name="prioritas" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 transition">
                            <option value="1">1 - Normal (Standar)</option>
                            <option value="2">2 - Sedang (Diutamakan)</option>
                            <option value="3">3 - Tinggi (Prioritas Utama)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Pertanyaan / Input Pengguna <span class="text-rose-500">*</span></label>
                    <textarea id="edit_pertanyaan" name="pertanyaan" rows="2" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 transition"></textarea>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Jawaban Respon AI <span class="text-rose-500">*</span></label>
                    <textarea id="edit_jawaban" name="jawaban" rows="4" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 transition"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kata Kunci (Keywords)</label>
                        <input type="text" id="edit_keyword" name="keyword" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 transition">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Sumber Informasi / Dokumen</label>
                        <input type="text" id="edit_sumber_informasi" name="sumber_informasi" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" id="edit_status_aktif" name="status_aktif" value="1" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                    <label for="edit_status_aktif" class="font-medium text-slate-700 cursor-pointer">Aktifkan data ini di AI Assistant</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-xl transition">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition shadow-md">Perbarui Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ----------------------------------------------------------------------------- -->
    <!-- MODAL DETAIL KNOWLEDGE BASE -->
    <!-- ----------------------------------------------------------------------------- -->
    <div id="detailModal" class="fixed inset-0 z-50 hidden modal-backdrop flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl overflow-hidden transform transition-all">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-eye text-blue-400"></i>
                    <h3 class="text-base font-bold">Detail Knowledge Base</h3>
                </div>
                <button onclick="closeDetailModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <div class="p-6 space-y-4 text-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <span class="text-slate-500">Kategori:</span>
                    <span id="detail_kategori" class="font-semibold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-lg"></span>
                </div>

                <div>
                    <p class="text-slate-400 font-medium mb-1">Pertanyaan / Keyword Query:</p>
                    <p id="detail_pertanyaan" class="font-bold text-slate-800 text-sm bg-slate-50 p-3 rounded-xl border border-slate-200/60 leading-snug"></p>
                </div>

                <div>
                    <p class="text-slate-400 font-medium mb-1">Jawaban Respon AI:</p>
                    <div id="detail_jawaban" class="text-slate-700 leading-relaxed bg-slate-50 p-3.5 rounded-xl border border-slate-200/60 whitespace-pre-line"></div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-2 text-[11px] text-slate-500 border-t border-slate-100">
                    <div>
                        <p class="font-semibold text-slate-600">Keywords:</p>
                        <p id="detail_keyword" class="text-slate-700 font-mono mt-0.5"></p>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-600">Sumber Informasi:</p>
                        <p id="detail_sumber" class="text-slate-700 mt-0.5"></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 text-[11px] text-slate-500">
                    <div>
                        <p class="font-semibold text-slate-600">Prioritas AI:</p>
                        <p id="detail_prioritas" class="mt-0.5"></p>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-600">Terakhir Diperbarui:</p>
                        <p id="detail_tanggal" class="text-slate-700 mt-0.5"></p>
                    </div>
                </div>
            </div>

            <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex justify-end">
                <button onclick="closeDetailModal()" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white font-semibold rounded-xl transition">Tutup</button>
            </div>
        </div>
    </div>

    <!-- ----------------------------------------------------------------------------- -->
    <!-- MODAL KONFIRMASI HAPUS -->
    <!-- ----------------------------------------------------------------------------- -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden modal-backdrop flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl overflow-hidden p-6 text-center space-y-4">
            <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto text-xl">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-800">Konfirmasi Hapus Data</h3>
                <p class="text-xs text-slate-500 mt-1">Apakah Anda yakin ingin menghapus pertanyaan knowledge ini?</p>
                <p id="delete_title_preview" class="text-xs font-semibold text-slate-700 bg-slate-50 p-2.5 rounded-xl border border-slate-200 mt-2 italic line-clamp-2"></p>
            </div>

            <form method="POST" action="knowledge.php" class="flex items-center justify-center gap-3 pt-2">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_id" name="id" value="">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-xl transition">Batal</button>
                <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl transition shadow-md">Ya, Hapus Data</button>
            </form>
        </div>
    </div>

    <!-- SCRIPT RESPONSIF SIDEBAR & MODAL CONTROL -->
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

        // Modal Controls
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
        }
        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
        }

        function openEditModal(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_kategori_id').value = data.kategori_id;
            document.getElementById('edit_prioritas').value = data.prioritas;
            document.getElementById('edit_pertanyaan').value = data.pertanyaan;
            document.getElementById('edit_jawaban').value = data.jawaban;
            document.getElementById('edit_keyword').value = data.keyword || '';
            document.getElementById('edit_sumber_informasi').value = data.sumber_informasi || '';
            document.getElementById('edit_status_aktif').checked = (data.status_aktif == 1);

            document.getElementById('editModal').classList.remove('hidden');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function openDetailModal(data) {
            document.getElementById('detail_kategori').textContent = data.nama_kategori || 'Umum';
            document.getElementById('detail_pertanyaan').textContent = data.pertanyaan;
            document.getElementById('detail_jawaban').textContent = data.jawaban;
            document.getElementById('detail_keyword').textContent = data.keyword || '-';
            document.getElementById('detail_sumber').textContent = data.sumber_informasi || '-';
            document.getElementById('detail_prioritas').textContent = 'P' + data.prioritas;
            document.getElementById('detail_tanggal').textContent = data.tanggal_update || '-';

            document.getElementById('detailModal').classList.remove('hidden');
        }
        function closeDetailModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }

        function openDeleteModal(id, title) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_title_preview').textContent = '"' + title + '"';
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
</body>
</html>
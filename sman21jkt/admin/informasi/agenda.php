<?php
// admin/informasi/agenda.php

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

if ($conn) {
    mysqli_set_charset($conn, "utf8mb4");
}

// Helper untuk membuat Slug URL yang bersih
function create_slug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

// Flash Message Notification
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// -----------------------------------------------------------------------------
// HANDLER CRUD (CREATE, UPDATE, DELETE)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = $_POST['action'] ?? '';

    // HANDLER TAMBAH / EDIT AGENDA
    if ($action === 'create' || $action === 'update') {
        $id              = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $judul_agenda    = trim($_POST['judul_agenda'] ?? '');
        $deskripsi       = trim($_POST['deskripsi'] ?? '');
        $tanggal_mulai   = $_POST['tanggal_mulai'] ?? date('Y-m-d');
        $tanggal_selesai = !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : NULL;
        $jam_mulai       = !empty($_POST['jam_mulai']) ? $_POST['jam_mulai'] : NULL;
        $jam_selesai     = !empty($_POST['jam_selesai']) ? $_POST['jam_selesai'] : NULL;
        $lokasi          = trim($_POST['lokasi'] ?? '');
        $link            = trim($_POST['link'] ?? '');
        $status          = $_POST['status'] ?? 'published';

        $slug = create_slug($judul_agenda);

        // Management Upload Gambar
        $gambar_path = $_POST['old_gambar'] ?? '';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $file_tmp  = $_FILES['gambar']['tmp_name'];
            $file_name = $_FILES['gambar']['name'];
            $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed   = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (in_array($file_ext, $allowed)) {
                $upload_dir = '../../uploads/agenda/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $new_filename = 'agenda_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
                $target_file  = $upload_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $target_file)) {
                    if (!empty($_POST['old_gambar']) && file_exists('../../' . $_POST['old_gambar'])) {
                        @unlink('../../' . $_POST['old_gambar']);
                    }
                    $gambar_path = 'uploads/agenda/' . $new_filename;
                }
            }
        }

        if ($action === 'create') {
            $stmt = $conn->prepare("INSERT INTO agenda (judul_agenda, slug, deskripsi, tanggal_mulai, tanggal_selesai, jam_mulai, jam_selesai, lokasi, gambar, link, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssss", $judul_agenda, $slug, $deskripsi, $tanggal_mulai, $tanggal_selesai, $jam_mulai, $jam_selesai, $lokasi, $gambar_path, $link, $status);

            if ($stmt->execute()) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Agenda acara baru berhasil ditambahkan!'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Gagal menyimpan agenda: ' . $conn->error];
            }
            $stmt->close();
        } elseif ($action === 'update' && $id > 0) {
            $stmt = $conn->prepare("UPDATE agenda SET judul_agenda=?, slug=?, deskripsi=?, tanggal_mulai=?, tanggal_selesai=?, jam_mulai=?, jam_selesai=?, lokasi=?, gambar=?, link=?, status=? WHERE id=?");
            $stmt->bind_param("sssssssssssi", $judul_agenda, $slug, $deskripsi, $tanggal_mulai, $tanggal_selesai, $jam_mulai, $jam_selesai, $lokasi, $gambar_path, $link, $status, $id);

            if ($stmt->execute()) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Data agenda berhasil diperbarui!'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Gagal memperbarui agenda: ' . $conn->error];
            }
            $stmt->close();
        }

        header("Location: agenda.php");
        exit;
    }

    // HANDLER HAPUS AGENDA
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            // Ambil path gambar untuk dihapus dari penyimpanan fisik server
            $q_img = $conn->prepare("SELECT gambar FROM agenda WHERE id=?");
            $q_img->bind_param("i", $id);
            $q_img->execute();
            $res_img = $q_img->get_result();
            if ($row_img = $res_img->fetch_assoc()) {
                if (!empty($row_img['gambar']) && file_exists('../../' . $row_img['gambar'])) {
                    @unlink('../../' . $row_img['gambar']);
                }
            }
            $q_img->close();

            $stmt = $conn->prepare("DELETE FROM agenda WHERE id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Agenda berhasil dihapus dari database!'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Gagal menghapus agenda!'];
            }
            $stmt->close();
        }
        header("Location: agenda.php");
        exit;
    }
}

// -----------------------------------------------------------------------------
// FILTER & SEARCH QUERY
// -----------------------------------------------------------------------------
$search        = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? '';

$where_clauses = [];
$params        = [];
$param_types   = "";

if (!empty($search)) {
    $where_clauses[] = "(judul_agenda LIKE ? OR lokasi LIKE ? OR deskripsi LIKE ?)";
    $search_param    = "%" . $search . "%";
    $params[]        = $search_param;
    $params[]        = $search_param;
    $params[]        = $search_param;
    $param_types    .= "sss";
}

if (!empty($filter_status) && in_array($filter_status, ['published', 'draft', 'archived'])) {
    $where_clauses[] = "status = ?";
    $params[]        = $filter_status;
    $param_types    .= "s";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

$agendas = [];
if ($conn) {
    $sql  = "SELECT * FROM agenda $where_sql ORDER BY tanggal_mulai DESC, id DESC";
    $stmt = $conn->prepare($sql);

    if (count($params) > 0) {
        $stmt->bind_param($param_types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $agendas[] = $row;
    }
    $stmt->close();
}

// Ringkasan Statistik
$stat_total     = 0;
$stat_published = 0;
$stat_draft     = 0;
$stat_upcoming  = 0;

if ($conn) {
    $r_tot = mysqli_query($conn, "SELECT COUNT(*) as total FROM agenda");
    if ($r_tot) $stat_total = mysqli_fetch_assoc($r_tot)['total'];

    $r_pub = mysqli_query($conn, "SELECT COUNT(*) as total FROM agenda WHERE status='published'");
    if ($r_pub) $stat_published = mysqli_fetch_assoc($r_pub)['total'];

    $r_drf = mysqli_query($conn, "SELECT COUNT(*) as total FROM agenda WHERE status='draft'");
    if ($r_drf) $stat_draft = mysqli_fetch_assoc($r_drf)['total'];

    $today = date('Y-m-d');
    $r_upc = mysqli_query($conn, "SELECT COUNT(*) as total FROM agenda WHERE tanggal_mulai >= '$today'");
    if ($r_upc) $stat_upcoming = mysqli_fetch_assoc($r_upc)['total'];
}

$logo_url = "https://sman21jkt.sch.id/wp-content/uploads/2025/02/1000305388-removebg-preview_image_repair_1740014356410.jpg";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Agenda Kalender - SMAN 21 Jakarta</title>
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

    <!-- SIDEBAR NAVIGASI (Sama seperti dashboard.php dengan penyesuaian path) -->
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

                    <!-- Informasi Dropdown (Active Menu) -->
                    <details class="group" open>
                        <summary class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white cursor-pointer text-sm font-medium transition-colors list-none text-white">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-newspaper w-5 text-center text-blue-400"></i>
                                Informasi
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="pl-9 pr-2 py-1 space-y-1 text-xs">
                            <a href="berita.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Berita & Pengumuman</a>
                            <a href="agenda.php" class="block py-1.5 px-2 rounded bg-blue-600 text-white font-medium">Agenda Kalender</a>
                            <a href="ppdb.php" class="block py-1.5 px-2 rounded hover:text-white hover:bg-slate-800">Informasi PPDB</a>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Kelola Agenda & Kalender Sekolah</h2>
                    <p class="text-xs text-slate-500">Manajemen jadwal kegiatan, seminar, ujian, dan acara resmi SMAN 21 Jakarta</p>
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

            <!-- NOTIFIKASI FLASH MESSAGE -->
            <?php if ($flash_message): ?>
                <div class="p-4 rounded-xl border <?= $flash_message['type'] === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' ?> flex items-center justify-between transition shadow-sm">
                    <div class="flex items-center gap-3 text-sm font-medium">
                        <i class="fa-solid <?= $flash_message['type'] === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-rose-600' ?> text-lg"></i>
                        <span><?= htmlspecialchars($flash_message['text']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- RINGKASAN STATISTIK QUICK CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500 font-medium">Total Agenda</p>
                        <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= $stat_total ?></h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500 font-medium">Mendatang / Aktif</p>
                        <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= $stat_upcoming ?></h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-clock font-bold"></i>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500 font-medium">Terpublikasi</p>
                        <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= $stat_published ?></h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-eye"></i>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500 font-medium">Draft / Konsep</p>
                        <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= $stat_draft ?></h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-pen-ruler"></i>
                    </div>
                </div>
            </div>

            <!-- TOOLBAR PENCARIAN, FILTER & BUTTON TAMBAH -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row gap-4 items-center justify-between">
                
                <!-- Form Filter & Cari -->
                <form method="GET" action="agenda.php" class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto flex-1">
                    <div class="relative w-full sm:w-80">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari agenda atau lokasi..." class="w-full pl-9 pr-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs focus:bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                    </div>

                    <select name="status" onchange="this.form.submit()" class="w-full sm:w-40 py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 text-xs focus:bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none transition text-slate-700">
                        <option value="">-- Semua Status --</option>
                        <option value="published" <?= $filter_status === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= $filter_status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="archived" <?= $filter_status === 'archived' ? 'selected' : '' ?>>Archived</option>
                    </select>

                    <?php if(!empty($search) || !empty($filter_status)): ?>
                        <a href="agenda.php" class="p-2 text-rose-500 hover:text-rose-700 text-xs font-medium flex items-center gap-1" title="Reset Filter">
                            <i class="fa-solid fa-rotate-left"></i> Reset
                        </a>
                    <?php endif; ?>
                </form>

                <!-- Tombol Tambah Agenda Modal -->
                <button onclick="openModalCreate()" class="w-full md:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-md shadow-blue-500/20 flex items-center justify-center gap-2 flex-shrink-0">
                    <i class="fa-solid fa-calendar-plus"></i>
                    <span>Tambah Agenda Baru</span>
                </button>
            </div>

            <!-- TABEL DATA AGENDA -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4 text-center w-12">No</th>
                                <th class="py-3.5 px-4">Poster / Banner</th>
                                <th class="py-3.5 px-4">Judul & Detail Agenda</th>
                                <th class="py-3.5 px-4">Tanggal & Waktu</th>
                                <th class="py-3.5 px-4">Lokasi Acara</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-center w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($agendas)): ?>
                                <?php foreach ($agendas as $idx => $item): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-4 px-4 text-center font-medium text-slate-400">
                                            <?= $idx + 1 ?>
                                        </td>
                                        <td class="py-4 px-4">
                                            <?php if (!empty($item['gambar']) && file_exists('../../' . $item['gambar'])): ?>
                                                <img src="../../<?= htmlspecialchars($item['gambar']) ?>" alt="Banner" class="w-14 h-14 object-cover rounded-lg border border-slate-200 shadow-sm">
                                            <?php else: ?>
                                                <div class="w-14 h-14 rounded-lg bg-slate-100 border border-slate-200 flex flex-col items-center justify-center text-slate-400 text-[10px]">
                                                    <i class="fa-regular fa-image text-base mb-0.5"></i>
                                                    <span>No Image</span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-4 max-w-xs">
                                            <p class="font-bold text-slate-800 text-sm line-clamp-1"><?= htmlspecialchars($item['judul_agenda']) ?></p>
                                            <p class="text-[11px] text-slate-400 mt-0.5 tracking-tight font-mono">/agenda/<?= htmlspecialchars($item['slug']) ?></p>
                                            <?php if(!empty($item['link'])): ?>
                                                <a href="<?= htmlspecialchars($item['link']) ?>" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-blue-600 hover:underline mt-1">
                                                    <i class="fa-solid fa-link text-[9px]"></i> Link Tautan
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <div class="flex items-center gap-1.5 font-medium text-slate-700">
                                                <i class="fa-regular fa-calendar text-blue-600"></i>
                                                <span><?= date('d M Y', strtotime($item['tanggal_mulai'])) ?></span>
                                                <?php if (!empty($item['tanggal_selesai']) && $item['tanggal_selesai'] !== $item['tanggal_mulai']): ?>
                                                    <span class="text-slate-400">- <?= date('d M Y', strtotime($item['tanggal_selesai'])) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1.5">
                                                <i class="fa-regular fa-clock text-slate-400"></i>
                                                <?php if(!empty($item['jam_mulai'])): ?>
                                                    <span><?= date('H:i', strtotime($item['jam_mulai'])) ?> WIB</span>
                                                    <?php if(!empty($item['jam_selesai'])): ?>
                                                        <span>- <?= date('H:i', strtotime($item['jam_selesai'])) ?> WIB</span>
                                                    <?php else: ?>
                                                        <span>- Selesai</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span>Waktu belum diatur</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4 max-w-xs">
                                            <?php if(!empty($item['lokasi'])): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-xs font-medium">
                                                    <i class="fa-solid fa-location-dot text-rose-500"></i>
                                                    <span class="truncate"><?= htmlspecialchars($item['lokasi']) ?></span>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-slate-400 italic text-[11px]">Tidak ditentukan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-4 text-center">
                                            <?php if ($item['status'] === 'published'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Published
                                                </span>
                                            <?php elseif ($item['status'] === 'draft'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Draft
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-200 text-slate-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span> Archived
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button onclick='openModalDetail(<?= json_encode($item) ?>)' title="Lihat Detail" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition">
                                                    <i class="fa-solid fa-eye text-xs"></i>
                                                </button>
                                                <button onclick='openModalEdit(<?= json_encode($item) ?>)' title="Edit Agenda" class="p-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 transition">
                                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                                </button>
                                                <button onclick="confirmDelete(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['judul_agenda'])) ?>')" title="Hapus Agenda" class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 transition">
                                                    <i class="fa-solid fa-trash text-xs"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400">
                                        <div class="max-w-xs mx-auto space-y-2">
                                            <i class="fa-solid fa-calendar-xmark text-4xl text-slate-300"></i>
                                            <p class="text-sm font-semibold text-slate-600">Tidak ada agenda ditemukan</p>
                                            <p class="text-xs text-slate-400">Belum ada data agenda kalender yang ditambahkan atau hasil pencarian tidak cocok.</p>
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

    <!-- ===================================================================== -->
    <!-- MODAL FORM TAMBAH / EDIT AGENDA -->
    <!-- ===================================================================== -->
    <div id="modalAgenda" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-100 overflow-hidden my-8 transform transition-all">
            
            <!-- Modal Header -->
            <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div id="modalIconBg" class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center text-white text-base">
                        <i id="modalHeaderIcon" class="fa-solid fa-calendar-plus"></i>
                    </div>
                    <div>
                        <h3 id="modalTitle" class="font-bold text-base">Tambah Agenda Baru</h3>
                        <p class="text-xs text-slate-400">Isi formulir di bawah ini dengan informasi acara secara rinci</p>
                    </div>
                </div>
                <button onclick="closeModal('modalAgenda')" class="text-slate-400 hover:text-white p-1 transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form id="formAgenda" method="POST" action="agenda.php" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="agendaId" value="0">
                <input type="hidden" name="old_gambar" id="oldGambar" value="">

                <!-- Judul Agenda -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Judul Agenda Acara <span class="text-rose-500">*</span></label>
                    <input type="text" name="judul_agenda" id="judulAgenda" required placeholder="Contoh: Rapat Pleno Orang Tua Siswa Kelas XII" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                </div>

                <!-- Grid Tanggal & Jam -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Mulai <span class="text-rose-500">*</span></label>
                        <input type="date" name="tanggal_mulai" id="tanggalMulai" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" id="tanggalSelesai" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Jam Mulai</label>
                        <input type="time" name="jam_mulai" id="jamMulai" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Jam Selesai</label>
                        <input type="time" name="jam_selesai" id="jamSelesai" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                    </div>
                </div>

                <!-- Lokasi & Link Tautan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Lokasi Tempat</label>
                        <input type="text" name="lokasi" id="lokasiAgenda" placeholder="Contoh: Aula Utama SMAN 21 Jakarta" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Link Eksternal / Pendaftaran</label>
                        <input type="url" name="link" id="linkAgenda" placeholder="https://..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                    </div>
                </div>

                <!-- Status & Gambar Banner -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status Publikasi <span class="text-rose-500">*</span></label>
                        <select name="status" id="statusAgenda" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                            <option value="published">Published (Tampil di Website)</option>
                            <option value="draft">Draft (Konsep)</option>
                            <option value="archived">Archived (Arsip)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Banner / Poster Acara</label>
                        <input type="file" name="gambar" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-slate-200 rounded-xl">
                    </div>
                </div>

                <!-- Deskripsi Agenda -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Deskripsi Lengkap Agenda</label>
                    <textarea name="deskripsi" id="deskripsiAgenda" rows="4" placeholder="Tulis rincian atau susunan acara di sini..." class="w-full p-3 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none transition"></textarea>
                </div>

                <!-- Modal Footer Buttons -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal('modalAgenda')" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-md transition flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan Data Agenda</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL DETAIL PREVIEW AGENDA -->
    <!-- ===================================================================== -->
    <div id="modalDetail" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
            <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
                <h3 class="font-bold text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-blue-400"></i>
                    Detail Informasi Agenda
                </h3>
                <button onclick="closeModal('modalDetail')" class="text-slate-400 hover:text-white p-1 transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div id="detailBannerContainer" class="hidden">
                    <img id="detailBanner" src="" alt="Banner" class="w-full h-48 object-cover rounded-xl border border-slate-200 shadow-sm">
                </div>
                <div>
                    <span id="detailBadgeStatus" class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase mb-1"></span>
                    <h4 id="detailJudul" class="text-lg font-bold text-slate-800"></h4>
                    <p id="detailSlug" class="text-xs text-slate-400 font-mono mt-0.5"></p>
                </div>
                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100 text-xs space-y-2">
                    <div class="flex items-start gap-2 text-slate-700">
                        <i class="fa-regular fa-calendar text-blue-600 mt-0.5 w-4"></i>
                        <span id="detailTanggal" class="font-medium"></span>
                    </div>
                    <div class="flex items-start gap-2 text-slate-700">
                        <i class="fa-regular fa-clock text-blue-600 mt-0.5 w-4"></i>
                        <span id="detailWaktu"></span>
                    </div>
                    <div class="flex items-start gap-2 text-slate-700">
                        <i class="fa-solid fa-location-dot text-rose-500 mt-0.5 w-4"></i>
                        <span id="detailLokasi"></span>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Deskripsi Agenda:</p>
                    <p id="detailDeskripsi" class="text-xs text-slate-600 leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-100 min-h-[60px] whitespace-pre-line"></p>
                </div>
                <div id="detailLinkContainer" class="hidden pt-2 border-t border-slate-100">
                    <a id="detailLink" href="" target="_blank" class="w-full py-2 px-3 rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-100 text-xs font-medium transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka Link Eksternal
                    </a>
                </div>
            </div>
            <div class="p-4 bg-slate-50 border-t border-slate-100 text-right">
                <button onclick="closeModal('modalDetail')" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold rounded-xl transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- FORM TERSEMBUNYI UNTUK HAPUS DATA -->
    <form id="formDelete" method="POST" action="agenda.php" class="hidden">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId" value="0">
    </form>

    <!-- SCRIPT JAVASCRIPT MODAL & INTERAKSI -->
    <script>
        // Toggle Sidebar Navigasi Responsif
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('-ml-64');
        });

        // Live Clock Header Update
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').textContent = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateClock, 1000);

        // Modal Helpers
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        // Buka Modal Tambah Agenda Baru
        function openModalCreate() {
            document.getElementById('modalTitle').textContent = 'Tambah Agenda Baru';
            document.getElementById('formAction').value = 'create';
            document.getElementById('agendaId').value = '0';
            document.getElementById('oldGambar').value = '';
            document.getElementById('formAgenda').reset();
            
            // Default Tanggal Hari Ini
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('tanggalMulai').value = today;
            
            openModal('modalAgenda');
        }

        // Buka Modal Edit Agenda
        function openModalEdit(data) {
            document.getElementById('modalTitle').textContent = 'Edit Data Agenda';
            document.getElementById('formAction').value = 'update';
            document.getElementById('agendaId').value = data.id;
            document.getElementById('oldGambar').value = data.gambar || '';
            
            document.getElementById('judulAgenda').value = data.judul_agenda || '';
            document.getElementById('tanggalMulai').value = data.tanggal_mulai || '';
            document.getElementById('tanggalSelesai').value = data.tanggal_selesai || '';
            document.getElementById('jamMulai').value = data.jam_mulai || '';
            document.getElementById('jamSelesai').value = data.jam_selesai || '';
            document.getElementById('lokasiAgenda').value = data.lokasi || '';
            document.getElementById('linkAgenda').value = data.link || '';
            document.getElementById('statusAgenda').value = data.status || 'published';
            document.getElementById('deskripsiAgenda').value = data.deskripsi || '';

            openModal('modalAgenda');
        }

        // Buka Modal Detail Preview Agenda
        function openModalDetail(data) {
            document.getElementById('detailJudul').textContent = data.judul_agenda;
            document.getElementById('detailSlug').textContent = '/agenda/' + data.slug;
            
            // Format Tanggal
            let tgl = data.tanggal_mulai;
            if (data.tanggal_selesai && data.tanggal_selesai !== data.tanggal_mulai) {
                tgl += ' s.d ' + data.tanggal_selesai;
            }
            document.getElementById('detailTanggal').textContent = tgl;

            // Format Jam
            let jam = (data.jam_mulai ? data.jam_mulai.substring(0, 5) : 'Waktu tidak ditentukan') + ' WIB';
            if (data.jam_selesai) jam += ' - ' + data.jam_selesai.substring(0, 5) + ' WIB';
            document.getElementById('detailWaktu').textContent = jam;

            document.getElementById('detailLokasi').textContent = data.lokasi ? data.lokasi : 'Tidak ditentukan';
            document.getElementById('detailDeskripsi').textContent = data.deskripsi ? data.deskripsi : 'Tidak ada deskripsi tambahan.';

            // Banner Gambar
            const imgContainer = document.getElementById('detailBannerContainer');
            if (data.gambar) {
                document.getElementById('detailBanner').src = '../../' + data.gambar;
                imgContainer.classList.remove('hidden');
            } else {
                imgContainer.classList.add('hidden');
            }

            // Link Eksternal
            const linkContainer = document.getElementById('detailLinkContainer');
            if (data.link) {
                document.getElementById('detailLink').href = data.link;
                linkContainer.classList.remove('hidden');
            } else {
                linkContainer.classList.add('hidden');
            }

            // Badge Status
            const badge = document.getElementById('detailBadgeStatus');
            if (data.status === 'published') {
                badge.className = "inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800";
                badge.textContent = "Published";
            } else if (data.status === 'draft') {
                badge.className = "inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-800";
                badge.textContent = "Draft";
            } else {
                badge.className = "inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-200 text-slate-700";
                badge.textContent = "Archived";
            }

            openModal('modalDetail');
        }

        // Konfirmasi Hapus Data Agenda
        function confirmDelete(id, judul) {
            if (confirm(`Apakah Anda benar-benar yakin ingin menghapus agenda:\n"${judul}"?`)) {
                document.getElementById('deleteId').value = id;
                document.getElementById('formDelete').submit();
            }
        }
    </script>
</body>
</html>
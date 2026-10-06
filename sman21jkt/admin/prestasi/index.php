<?php
// admin/prestasi/index.php

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

// Notifikasi Flash Message
$message = '';
$message_type = '';

// Directori Upload File Foto & Dokumen
$upload_dir_foto = '../../uploads/prestasi/';
$upload_dir_doc  = '../../uploads/dokumen_prestasi/';

if (!file_exists($upload_dir_foto)) {
    @mkdir($upload_dir_foto, 0777, true);
}
if (!file_exists($upload_dir_doc)) {
    @mkdir($upload_dir_doc, 0777, true);
}

// -----------------------------------------------------------------------------
// PROSES AKSI POST (TAMBAH / EDIT / HAPUS / TOGGLE STATUS)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // 1. TAMBAH PRESTASI
    if ($action === 'add') {
        $judul_prestasi = mysqli_real_escape_string($conn, trim($_POST['judul_prestasi']));
        $kategori       = mysqli_real_escape_string($conn, $_POST['kategori']);
        $tingkat        = mysqli_real_escape_string($conn, trim($_POST['tingkat']));
        $peringkat      = mysqli_real_escape_string($conn, trim($_POST['peringkat']));
        $nama_siswa     = mysqli_real_escape_string($conn, trim($_POST['nama_siswa']));
        $penyelenggara  = mysqli_real_escape_string($conn, trim($_POST['penyelenggara']));
        $tanggal        = !empty($_POST['tanggal']) ? $_POST['tanggal'] : NULL;
        $deskripsi      = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));
        $status_aktif   = isset($_POST['status_aktif']) ? 1 : 0;

        $foto_filename = '';
        $doc_filename  = '';

        // Handle Upload Foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $foto_filename = 'prestasi_' . time() . '_' . rand(100, 999) . '.' . strtolower($ext);
            move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir_foto . $foto_filename);
        }

        // Handle Upload Dokumen
        if (isset($_FILES['dokumen']) && $_FILES['dokumen']['error'] === UPLOAD_ERR_OK) {
            $ext_doc = pathinfo($_FILES['dokumen']['name'], PATHINFO_EXTENSION);
            $doc_filename = 'doc_prestasi_' . time() . '_' . rand(100, 999) . '.' . strtolower($ext_doc);
            move_uploaded_file($_FILES['dokumen']['tmp_name'], $upload_dir_doc . $doc_filename);
        }

        $query = "INSERT INTO prestasi (judul_prestasi, kategori, tingkat, peringkat, nama_siswa, penyelenggara, tanggal, deskripsi, foto, dokumen, status_aktif) 
                  VALUES ('$judul_prestasi', '$kategori', '$tingkat', '$peringkat', '$nama_siswa', '$penyelenggara', " . ($tanggal ? "'$tanggal'" : "NULL") . ", '$deskripsi', '$foto_filename', '$doc_filename', $status_aktif)";

        if (mysqli_query($conn, $query)) {
            $message = 'Prestasi berhasil ditambahkan!';
            $message_type = 'success';
        } else {
            $message = 'Gagal menambahkan data: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }

    // 2. EDIT PRESTASI
    elseif ($action === 'edit') {
        $id             = intval($_POST['id']);
        $judul_prestasi = mysqli_real_escape_string($conn, trim($_POST['judul_prestasi']));
        $kategori       = mysqli_real_escape_string($conn, $_POST['kategori']);
        $tingkat        = mysqli_real_escape_string($conn, trim($_POST['tingkat']));
        $peringkat      = mysqli_real_escape_string($conn, trim($_POST['peringkat']));
        $nama_siswa     = mysqli_real_escape_string($conn, trim($_POST['nama_siswa']));
        $penyelenggara  = mysqli_real_escape_string($conn, trim($_POST['penyelenggara']));
        $tanggal        = !empty($_POST['tanggal']) ? $_POST['tanggal'] : NULL;
        $deskripsi      = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));
        $status_aktif   = isset($_POST['status_aktif']) ? 1 : 0;

        // Ambil data lama untuk mempertahankan file jika tidak diupload baru
        $q_old = mysqli_query($conn, "SELECT foto, dokumen FROM prestasi WHERE id = $id");
        $old_data = mysqli_fetch_assoc($q_old);
        $foto_filename = $old_data['foto'];
        $doc_filename  = $old_data['dokumen'];

        // Upload Foto Baru
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            if (!empty($old_data['foto']) && file_exists($upload_dir_foto . $old_data['foto'])) {
                @unlink($upload_dir_foto . $old_data['foto']);
            }
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $foto_filename = 'prestasi_' . time() . '_' . rand(100, 999) . '.' . strtolower($ext);
            move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir_foto . $foto_filename);
        }

        // Upload Dokumen Baru
        if (isset($_FILES['dokumen']) && $_FILES['dokumen']['error'] === UPLOAD_ERR_OK) {
            if (!empty($old_data['dokumen']) && file_exists($upload_dir_doc . $old_data['dokumen'])) {
                @unlink($upload_dir_doc . $old_data['dokumen']);
            }
            $ext_doc = pathinfo($_FILES['dokumen']['name'], PATHINFO_EXTENSION);
            $doc_filename = 'doc_prestasi_' . time() . '_' . rand(100, 999) . '.' . strtolower($ext_doc);
            move_uploaded_file($_FILES['dokumen']['tmp_name'], $upload_dir_doc . $doc_filename);
        }

        $query = "UPDATE prestasi SET 
                    judul_prestasi = '$judul_prestasi',
                    kategori       = '$kategori',
                    tingkat        = '$tingkat',
                    peringkat      = '$peringkat',
                    nama_siswa     = '$nama_siswa',
                    penyelenggara  = '$penyelenggara',
                    tanggal        = " . ($tanggal ? "'$tanggal'" : "NULL") . ",
                    deskripsi      = '$deskripsi',
                    foto           = '$foto_filename',
                    dokumen        = '$doc_filename',
                    status_aktif   = $status_aktif
                  WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            $message = 'Data prestasi berhasil diperbarui!';
            $message_type = 'success';
        } else {
            $message = 'Gagal memperbarui data: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }

    // 3. HAPUS PRESTASI
    elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        
        // Hapus file terkait
        $q_old = mysqli_query($conn, "SELECT foto, dokumen FROM prestasi WHERE id = $id");
        if ($old_data = mysqli_fetch_assoc($q_old)) {
            if (!empty($old_data['foto']) && file_exists($upload_dir_foto . $old_data['foto'])) {
                @unlink($upload_dir_foto . $old_data['foto']);
            }
            if (!empty($old_data['dokumen']) && file_exists($upload_dir_doc . $old_data['dokumen'])) {
                @unlink($upload_dir_doc . $old_data['dokumen']);
            }
        }

        if (mysqli_query($conn, "DELETE FROM prestasi WHERE id = $id")) {
            $message = 'Data prestasi berhasil dihapus!';
            $message_type = 'success';
        } else {
            $message = 'Gagal menghapus data: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }

    // 4. TOGGLE STATUS AKTIF
    elseif ($action === 'toggle_status') {
        $id     = intval($_POST['id']);
        $status = intval($_POST['status']) === 1 ? 0 : 1;

        if (mysqli_query($conn, "UPDATE prestasi SET status_aktif = $status WHERE id = $id")) {
            $message = 'Status prestasi berhasil diubah!';
            $message_type = 'success';
        } else {
            $message = 'Gagal mengubah status: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }
}

// -----------------------------------------------------------------------------
// GET & FILTERING DATA
// -----------------------------------------------------------------------------
$selected_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : 'Semua';
$search_query      = isset($_GET['q']) ? trim($_GET['q']) : '';

$where_clause = "WHERE 1=1";
if ($selected_kategori !== 'Semua' && !empty($selected_kategori)) {
    $kat_clean = mysqli_real_escape_string($conn, $selected_kategori);
    $where_clause .= " AND kategori = '$kat_clean'";
}

if (!empty($search_query)) {
    $sq = mysqli_real_escape_string($conn, $search_query);
    $where_clause .= " AND (judul_prestasi LIKE '%$sq%' OR nama_siswa LIKE '%$sq%' OR penyelenggara LIKE '%$sq%')";
}

$prestasi_list = [];
if ($conn) {
    $sql_get = "SELECT * FROM prestasi $where_clause ORDER BY tanggal DESC, id DESC";
    $res = mysqli_query($conn, $sql_get);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $prestasi_list[] = $row;
        }
    }
}

$kategori_options = ['Akademik', 'Non-Akademik', 'Olahraga', 'Seni', 'Teknologi'];
$logo_url = "https://sman21jkt.sch.id/wp-content/uploads/2025/02/1000305388-removebg-preview_image_repair_1740014356410.jpg";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Prestasi Siswa - SMAN 21 Jakarta</title>
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

    <!-- SIDEBAR NAVIGASI (Persis seperti dashboard.php dengan penyesuaian path relatif) -->
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

                    <!-- Menu Active: Prestasi -->
                    <a href="index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-blue-600 text-white font-medium text-sm transition-all shadow-sm">
                        <i class="fa-solid fa-trophy w-5 text-center text-base"></i>
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
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Manajemen Prestasi Siswa</h2>
                    <p class="text-xs text-slate-500">Kelola daftar pencapaian, kejuaraan, dan penghargaan siswa SMAN 21 Jakarta</p>
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

            <!-- NOTIFIKASI PESAN FLASH -->
            <?php if (!empty($message)): ?>
                <div class="p-4 rounded-xl text-sm font-medium flex items-center justify-between shadow-sm border <?= $message_type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-rose-600' ?> text-lg"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- ACTION BAR: PENCARIAN, FILTER KATEGORI, & TOMBOL TAMBAH -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <!-- Filter Pill Button Kategori -->
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0 scrollbar-none">
                        <a href="index.php<?= !empty($search_query) ? '?q=' . urlencode($search_query) : '' ?>" 
                           class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition <?= $selected_kategori === 'Semua' ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                            Semua Kategori
                        </a>
                        <?php foreach ($kategori_options as $kat): ?>
                            <a href="index.php?kategori=<?= urlencode($kat) ?><?= !empty($search_query) ? '&q=' . urlencode($search_query) : '' ?>" 
                               class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition <?= $selected_kategori === $kat ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                                <?= $kat ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Tombol Tambah Prestasi Baru -->
                    <button onclick="openModalAdd()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-md flex items-center gap-2 flex-shrink-0">
                        <i class="fa-solid fa-plus text-sm"></i> Tambah Prestasi
                    </button>
                </div>

                <!-- Form Cari Keyword -->
                <form method="GET" action="index.php" class="flex items-center gap-2 pt-2 border-t border-slate-100">
                    <?php if ($selected_kategori !== 'Semua'): ?>
                        <input type="hidden" name="kategori" value="<?= htmlspecialchars($selected_kategori) ?>">
                    <?php endif; ?>
                    <div class="relative flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>" placeholder="Cari berdasarkan judul kejuaraan, nama siswa, atau penyelenggara..." class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium rounded-xl transition">
                        Cari
                    </button>
                    <?php if (!empty($search_query)): ?>
                        <a href="index.php<?= $selected_kategori !== 'Semua' ? '?kategori=' . urlencode($selected_kategori) : '' ?>" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-medium rounded-xl transition">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

            </div>

            <!-- TABEL DAFTAR PRESTASI -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Daftar Prestasi & Penghargaan</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Total ditemukan: <span class="font-semibold text-slate-700"><?= count($prestasi_list) ?></span> data</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50/80 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="py-3.5 px-4 text-center w-12">No</th>
                                <th class="py-3.5 px-4">Foto</th>
                                <th class="py-3.5 px-4">Prestasi & Kejuaraan</th>
                                <th class="py-3.5 px-4">Siswa / Peserta</th>
                                <th class="py-3.5 px-4">Kategori & Tingkat</th>
                                <th class="py-3.5 px-4">Penyelenggara & Tanggal</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($prestasi_list)): ?>
                                <?php $no = 1; foreach ($prestasi_list as $row): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-3.5 px-4 text-center font-medium text-slate-400"><?= $no++ ?></td>
                                        
                                        <!-- Thumbnail Foto -->
                                        <td class="py-3.5 px-4">
                                            <?php if (!empty($row['foto'])): ?>
                                                <img src="../../uploads/prestasi/<?= htmlspecialchars($row['foto']) ?>" alt="Foto" class="w-12 h-12 object-cover rounded-xl shadow-sm border border-slate-200">
                                            <?php else: ?>
                                                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-base border border-amber-100">
                                                    <i class="fa-solid fa-trophy"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Judul & Peringkat -->
                                        <td class="py-3.5 px-4 max-w-xs">
                                            <div class="font-bold text-slate-800 text-sm mb-0.5"><?= htmlspecialchars($row['judul_prestasi']) ?></div>
                                            <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-amber-100 text-amber-900 font-bold text-[11px]">
                                                <i class="fa-solid fa-award text-amber-600"></i>
                                                <?= htmlspecialchars($row['peringkat']) ?>
                                            </div>
                                        </td>

                                        <!-- Nama Siswa -->
                                        <td class="py-3.5 px-4 font-semibold text-slate-700">
                                            <div class="flex items-center gap-1.5">
                                                <i class="fa-solid fa-user-graduate text-slate-400 text-xs"></i>
                                                <?= htmlspecialchars($row['nama_siswa']) ?>
                                            </div>
                                        </td>

                                        <!-- Kategori & Tingkat -->
                                        <td class="py-3.5 px-4">
                                            <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100 mb-1">
                                                <?= htmlspecialchars($row['kategori']) ?>
                                            </span>
                                            <div class="text-[11px] text-slate-500 font-medium">
                                                Tingkat: <span class="text-slate-700 font-semibold"><?= htmlspecialchars($row['tingkat']) ?></span>
                                            </div>
                                        </td>

                                        <!-- Penyelenggara & Tanggal -->
                                        <td class="py-3.5 px-4 text-slate-500">
                                            <div class="font-medium text-slate-700"><?= !empty($row['penyelenggara']) ? htmlspecialchars($row['penyelenggara']) : '-' ?></div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">
                                                <i class="fa-regular fa-calendar mr-1"></i><?= !empty($row['tanggal']) ? date('d M Y', strtotime($row['tanggal'])) : '-' ?>
                                            </div>
                                        </td>

                                        <!-- Status Aktif (Toggle Clickable) -->
                                        <td class="py-3.5 px-4 text-center">
                                            <form method="POST" action="index.php" class="inline-block">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $row['status_aktif'] ?>">
                                                <button type="submit" class="transition" title="Klik untuk mengubah status">
                                                    <?php if ($row['status_aktif'] == 1): ?>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Sembunyi
                                                        </span>
                                                    <?php endif; ?>
                                                </button>
                                            </form>
                                        </td>

                                        <!-- Tombol Aksi (Edit, Hapus, Detail) -->
                                        <td class="py-3.5 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <!-- Edit Button -->
                                                <button onclick='openModalEdit(<?= json_encode($row) ?>)' class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit Data">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>

                                                <!-- Delete Button -->
                                                <form method="POST" action="index.php" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data prestasi ini?');" class="inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Data">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="py-12 text-center text-slate-400 italic">
                                        <i class="fa-solid fa-trophy text-3xl mb-2 block text-slate-300"></i>
                                        Belum ada data prestasi yang tercatat dalam database.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- MODAL FORM (TAMBAH & EDIT PRESTASI) -->
    <div id="modalForm" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden my-8">
            
            <!-- Header Modal -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-trophy text-amber-400 text-lg"></i>
                    <h3 id="modalTitle" class="font-bold text-base">Tambah Data Prestasi Baru</h3>
                </div>
                <button onclick="closeModal()" class="text-slate-400 hover:text-white transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Form Body -->
            <form id="formPrestasi" method="POST" action="index.php" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Judul Prestasi -->
                    <div class="md:col-span-2 space-y-1">
                        <label class="text-xs font-bold text-slate-700">Nama Kejuaraan / Prestasi <span class="text-rose-500">*</span></label>
                        <input type="text" name="judul_prestasi" id="judul_prestasi" required placeholder="Contoh: Juara 1 Olimpiade Sains Nasional Fisika" class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                    </div>

                    <!-- Kategori -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Kategori <span class="text-rose-500">*</span></label>
                        <select name="kategori" id="kategori" required class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                            <?php foreach ($kategori_options as $kat): ?>
                                <option value="<?= $kat ?>"><?= $kat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Peringkat -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Peringkat / Capaian <span class="text-rose-500">*</span></label>
                        <input type="text" name="peringkat" id="peringkat" required placeholder="Contoh: Juara 1 / Gold Medal" class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                    </div>

                    <!-- Tingkat -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Tingkat Kejuaraan <span class="text-rose-500">*</span></label>
                        <select name="tingkat" id="tingkat" required class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                            <option value="Kota / Kabupaten">Kota / Kabupaten</option>
                            <option value="Provinsi">Provinsi</option>
                            <option value="Nasional">Nasional</option>
                            <option value="Internasional">Internasional</option>
                        </select>
                    </div>

                    <!-- Nama Siswa -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Nama Siswa / Tim <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama_siswa" id="nama_siswa" required placeholder="Contoh: Ahmad Rizky & Tim" class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                    </div>

                    <!-- Penyelenggara -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Penyelenggara</label>
                        <input type="text" name="penyelenggara" id="penyelenggara" placeholder="Contoh: Kemendikbudristek" class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                    </div>

                    <!-- Tanggal Kejuaraan -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Tanggal Pelaksanaan</label>
                        <input type="date" name="tanggal" id="tanggal" class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                    </div>

                    <!-- File Foto Upload -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Foto Dokumentasi (.JPG, .PNG)</label>
                        <input type="file" name="foto" id="foto" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>

                    <!-- File Dokumen Upload -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Sertifikat / Dokumen Pendukung (.PDF)</label>
                        <input type="file" name="dokumen" id="dokumen" accept=".pdf,.doc,.docx" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    </div>

                    <!-- Deskripsi Catatan -->
                    <div class="md:col-span-2 space-y-1">
                        <label class="text-xs font-bold text-slate-700">Deskripsi / Detail Kemenangan</label>
                        <textarea name="deskripsi" id="deskripsi" rows="3" placeholder="Tulis rincian jalannya lomba atau sertifikat..." class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition"></textarea>
                    </div>

                    <!-- Checkbox Status Aktif -->
                    <div class="md:col-span-2 flex items-center gap-2 pt-2">
                        <input type="checkbox" name="status_aktif" id="status_aktif" value="1" checked class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                        <label for="status_aktif" class="text-xs font-medium text-slate-700">Tampilkan prestasi ini secara publik di portal sekolah</label>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-md">
                        Simpan Prestasi
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- SCRIPT RESPONSIF & MODAL HANDLING -->
    <script>
        // Toggle Sidebar Navigasi
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

        // Modal Functions
        function openModalAdd() {
            document.getElementById('modalTitle').textContent = 'Tambah Data Prestasi Baru';
            document.getElementById('formAction').value = 'add';
            document.getElementById('formId').value = '';
            document.getElementById('formPrestasi').reset();
            document.getElementById('status_aktif').checked = true;
            document.getElementById('modalForm').classList.remove('hidden');
        }

        function openModalEdit(data) {
            document.getElementById('modalTitle').textContent = 'Edit Data Prestasi';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formId').value = data.id;
            
            document.getElementById('judul_prestasi').value = data.judul_prestasi || '';
            document.getElementById('kategori').value = data.kategori || 'Akademik';
            document.getElementById('peringkat').value = data.peringkat || '';
            document.getElementById('tingkat').value = data.tingkat || 'Kota / Kabupaten';
            document.getElementById('nama_siswa').value = data.nama_siswa || '';
            document.getElementById('penyelenggara').value = data.penyelenggara || '';
            document.getElementById('tanggal').value = data.tanggal || '';
            document.getElementById('deskripsi').value = data.deskripsi || '';
            document.getElementById('status_aktif').checked = parseInt(data.status_aktif) === 1;

            document.getElementById('modalForm').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('modalForm').classList.add('hidden');
        }

        // Close Modal via Escape Key
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>

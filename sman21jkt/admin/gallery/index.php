<?php
session_start();

// Check Autentikasi Admin
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['admin'])) {
    header('Location: ../login.php');
    exit;
}

// =============================================================================
// KONFIGURASI DATABASE & KONEKSI PDO
// =============================================================================
$host = 'sql305.byethost3.com';
$user = 'b3_43046986';
$pass = 'J@karta5';
$db   = 'b3_43046986_sman21jkt';

$conn = null;
try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
} catch (PDOException $e) {
    try {
        // Fallback Localhost
        $conn = new PDO("mysql:host=localhost;dbname=b3_43046986_sman21jkt;charset=utf8mb4", "root", "", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e2) {
        die("Koneksi Database Gagal: " . $e2->getMessage());
    }
}

// Otomatis perbaiki struktur tabel 'gallery' jika AUTO_INCREMENT / PRIMARY KEY belum diset
try {
    $conn->exec("ALTER TABLE `gallery` ADD PRIMARY KEY (`id`)");
} catch (PDOException $e) {}
try {
    $conn->exec("ALTER TABLE `gallery` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT");
} catch (PDOException $e) {}

// Pastikan direktori upload tersedia
$uploadDir = '../../uploads/galeri/';
if (!file_exists($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

// Flash Message System
$message = '';
$messageType = '';

// =============================================================================
// PROCESS FORM ACTIONS (ADD, EDIT, DELETE, TOGGLE, UPDATE ORDER)
// =============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // -------------------------------------------------------------------------
    // 1. TAMBAH MEDIA GALERI (FOTO / VIDEO)
    // -------------------------------------------------------------------------
    if ($action === 'add') {
        $judul        = trim($_POST['judul'] ?? '');
        $kategori     = trim($_POST['kategori'] ?? 'Sekolah');
        $deskripsi    = trim($_POST['deskripsi'] ?? '');
        $tanggal      = !empty($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d');
        $urutan       = (int)($_POST['urutan'] ?? 1);
        $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
        $tipe_media   = $_POST['tipe_media'] ?? 'image';

        if (empty($judul)) {
            $message = "Judul media galeri tidak boleh kosong!";
            $messageType = "error";
        } elseif (!isset($_FILES['file_media']) || $_FILES['file_media']['error'] !== UPLOAD_ERR_OK) {
            $message = "Silakan pilih berkas foto atau video yang akan diupload!";
            $messageType = "error";
        } else {
            $file = $_FILES['file_media'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            $allowedImg = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $allowedVid = ['mp4', 'webm', 'ogg', 'mov', 'mkv'];

            // OTO-DETEKSI TIPE MEDIA
            if (in_array($ext, $allowedImg)) {
                $tipe_media = 'image';
            } elseif (in_array($ext, $allowedVid)) {
                $tipe_media = 'video';
            } else {
                $message = "Format berkas tidak didukung! Gunakan format foto (JPG, PNG, WEBP) atau video (MP4, WEBM, MOV).";
                $messageType = "error";
            }

            if (empty($message)) {
                $newFilename = 'galeri_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $targetFile  = $uploadDir . $newFilename;

                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $stmt = $conn->prepare("INSERT INTO gallery (judul, kategori, deskripsi, file_media, tipe_media, tanggal, urutan, status_aktif) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$judul, $kategori, $deskripsi, $newFilename, $tipe_media, $tanggal, $urutan, $status_aktif]);

                    $message = "Media galeri berhasil ditambahkan!";
                    $messageType = "success";
                } else {
                    $message = "Gagal mengunggah berkas ke server.";
                    $messageType = "error";
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // 2. EDIT / UPDATE MEDIA GALERI
    // -------------------------------------------------------------------------
    elseif ($action === 'edit') {
        $id           = (int)($_POST['id'] ?? 0);
        $judul        = trim($_POST['judul'] ?? '');
        $kategori     = trim($_POST['kategori'] ?? 'Sekolah');
        $deskripsi    = trim($_POST['deskripsi'] ?? '');
        $tanggal      = !empty($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d');
        $urutan       = (int)($_POST['urutan'] ?? 1);
        $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;

        if ($id <= 0 || empty($judul)) {
            $message = "Data galeri tidak valid!";
            $messageType = "error";
        } else {
            // Ambil data lama
            $stmtOld = $conn->prepare("SELECT file_media, tipe_media FROM gallery WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldData = $stmtOld->fetch();

            if (!$oldData) {
                $message = "Data galeri tidak ditemukan!";
                $messageType = "error";
            } else {
                $filenameToSave = $oldData['file_media'];
                $tipe_media     = $oldData['tipe_media'];

                // Jika ada file baru yang diupload
                if (isset($_FILES['file_media']) && $_FILES['file_media']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['file_media'];
                    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                    $allowedImg = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    $allowedVid = ['mp4', 'webm', 'ogg', 'mov', 'mkv'];

                    if (in_array($ext, $allowedImg)) {
                        $tipe_media = 'image';
                    } elseif (in_array($ext, $allowedVid)) {
                        $tipe_media = 'video';
                    }

                    $newFilename = 'galeri_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    $targetFile  = $uploadDir . $newFilename;

                    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                        // Hapus file lama jika ada
                        if (!empty($oldData['file_media']) && file_exists($uploadDir . $oldData['file_media'])) {
                            @unlink($uploadDir . $oldData['file_media']);
                        }
                        $filenameToSave = $newFilename;
                    }
                }

                $stmt = $conn->prepare("UPDATE gallery SET judul = ?, kategori = ?, deskripsi = ?, file_media = ?, tipe_media = ?, tanggal = ?, urutan = ?, status_aktif = ? WHERE id = ?");
                $stmt->execute([$judul, $kategori, $deskripsi, $filenameToSave, $tipe_media, $tanggal, $urutan, $status_aktif, $id]);

                $message = "Data galeri berhasil diperbarui!";
                $messageType = "success";
            }
        }
    }

    // -------------------------------------------------------------------------
    // 3. HAPUS MEDIA GALERI
    // -------------------------------------------------------------------------
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT file_media FROM gallery WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();

            if ($row) {
                if (!empty($row['file_media']) && file_exists($uploadDir . $row['file_media'])) {
                    @unlink($uploadDir . $row['file_media']);
                }
                $stmtDel = $conn->prepare("DELETE FROM gallery WHERE id = ?");
                $stmtDel->execute([$id]);

                $message = "Media galeri berhasil dihapus!";
                $messageType = "success";
            }
        }
    }

    // -------------------------------------------------------------------------
    // 4. TOGGLE STATUS AKTIF
    // -------------------------------------------------------------------------
    elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE gallery SET status_aktif = NOT status_aktif WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Status media galeri berhasil diubah!";
            $messageType = "success";
        }
    }

    // -------------------------------------------------------------------------
    // 5. UPDATE QUICK URUTAN
    // -------------------------------------------------------------------------
    elseif ($action === 'update_urutan') {
        $id = (int)($_POST['id'] ?? 0);
        $urutan = (int)($_POST['urutan'] ?? 1);
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE gallery SET urutan = ? WHERE id = ?");
            $stmt->execute([$urutan, $id]);
            $message = "Urutan berhasil diperbarui!";
            $messageType = "success";
        }
    }
}

// =============================================================================
// FETCH DATA GALERI
// =============================================================================
$filterKat = $_GET['filter_kategori'] ?? 'Semua';
$searchKey = trim($_GET['search'] ?? '');

$query = "SELECT * FROM gallery WHERE 1=1";
$params = [];

if ($filterKat !== 'Semua' && !empty($filterKat)) {
    $query .= " AND kategori = ?";
    $params[] = $filterKat;
}

if (!empty($searchKey)) {
    $query .= " AND (judul LIKE ? OR deskripsi LIKE ?)";
    $params[] = "%$searchKey%";
    $params[] = "%$searchKey%";
}

$query .= " ORDER BY urutan ASC, id DESC";

$stmtG = $conn->prepare($query);
$stmtG->execute($params);
$galleryItems = $stmtG->fetchAll();

// Statistik Ringkas
$totalMedia = count($galleryItems);
$totalFoto  = 0;
$totalVideo = 0;
foreach ($galleryItems as $gi) {
    if ($gi['tipe_media'] === 'video') $totalVideo++;
    else $totalFoto++;
}

// Daftar Kategori Resmi
$kategoriList = ['Sekolah', 'Kegiatan', 'Prestasi', 'Fasilitas', 'Siswa', 'Event'];
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Galeri & Dokumentasi — Admin SMAN 21 Jakarta</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../../uploads/logo/sman21jakarta.png">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            navy: '#0B1E36',
                            darkNavy: '#061224',
                            cyan: '#00A8CC',
                            cyanHover: '#0086A3',
                            lightCyan: '#E0F2FE',
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #061224; }
        ::-webkit-scrollbar-thumb { background: #00A8CC; border-radius: 3px; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex flex-col antialiased">

    <!-- ===================================================================== -->
    <!-- 🧭 TOP HEADER BAR                                                     -->
    <!-- ===================================================================== -->
    <header class="bg-brand-darkNavy text-white sticky top-0 z-50 shadow-md border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg bg-slate-800 text-slate-300 hover:text-white">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <a href="../dashboard.php" class="flex items-center gap-3">
                    <img src="../../uploads/logo/sman21jakarta.png" alt="Logo" class="h-9 w-9 object-contain" onerror="this.src='https://via.placeholder.com/40';">
                    <div>
                        <span class="font-extrabold text-sm sm:text-base text-white block leading-none">PANEL ADMIN</span>
                        <span class="text-[10px] text-cyan-400 font-semibold tracking-wider block mt-0.5">SMAN 21 JAKARTA</span>
                    </div>
                </a>
            </div>

            <div class="flex items-center gap-4">
                <a href="../../galeri/index.php" target="_blank" class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-cyan-300 text-xs font-semibold border border-slate-700 transition-colors">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Lihat Website Publik</span>
                </a>
                <div class="flex items-center gap-2 pl-3 border-l border-slate-700">
                    <div class="w-8 h-8 rounded-full bg-cyan-600 text-white font-bold flex items-center justify-center text-xs shadow-inner">
                        A
                    </div>
                    <span class="hidden md:inline text-xs font-semibold text-slate-200">Administrator</span>
                    <a href="../logout.php" class="ml-2 text-rose-400 hover:text-rose-300 text-xs font-semibold p-1.5 rounded hover:bg-rose-500/10 transition-colors" title="Logout">
                        <i class="fa-solid fa-power-off"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex-grow flex max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 gap-6">

        <!-- ===================================================================== -->
        <!-- 📂 NAVIGATION SIDEBAR                                                -->
        <!-- ===================================================================== -->
        <aside id="adminSidebar" class="hidden lg:block w-64 flex-shrink-0 space-y-2">
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 space-y-1">
                <div class="px-3 py-2 text-[10px] uppercase font-extrabold text-slate-400 tracking-wider">
                    Menu Utama
                </div>
                <a href="../dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                    <i class="fa-solid fa-gauge-high w-4 text-center text-slate-400"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../berita/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                    <i class="fa-solid fa-newspaper w-4 text-center text-slate-400"></i>
                    <span>Berita & Artikel</span>
                </a>
                <a href="../agenda/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                    <i class="fa-solid fa-calendar-days w-4 text-center text-slate-400"></i>
                    <span>Agenda Sekolah</span>
                </a>
                <a href="index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold text-white bg-brand-navy shadow-md transition-colors">
                    <i class="fa-solid fa-images w-4 text-center text-cyan-400"></i>
                    <span>Galeri & Media</span>
                </a>
                <a href="../prestasi/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                    <i class="fa-solid fa-trophy w-4 text-center text-slate-400"></i>
                    <span>Prestasi Siswa</span>
                </a>
                <a href="../fasilitas/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                    <i class="fa-solid fa-building w-4 text-center text-slate-400"></i>
                    <span>Sarana & Fasilitas</span>
                </a>

                <div class="px-3 pt-4 pb-2 text-[10px] uppercase font-extrabold text-slate-400 tracking-wider">
                    Pengaturan
                </div>
                <a href="../pengaturan/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                    <i class="fa-solid fa-sliders w-4 text-center text-slate-400"></i>
                    <span>Pengaturan Website</span>
                </a>
            </div>
        </aside>

        <!-- ===================================================================== -->
        <!-- 🖼️ MAIN CONTENT AREA                                                -->
        <!-- ===================================================================== -->
        <main class="flex-grow space-y-6 min-w-0">

            <!-- Notification Toast -->
            <?php if (!empty($message)): ?>
            <div class="p-4 rounded-xl text-xs font-semibold border flex items-center justify-between <?= $messageType === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200'; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-triangle-exclamation text-rose-600'; ?> text-base"></i>
                    <span><?= htmlspecialchars($message); ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <?php endif; ?>

            <!-- Page Title & Stat Summary -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-brand-darkNavy flex items-center gap-2.5">
                        <i class="fa-solid fa-images text-brand-cyan"></i>
                        <span>Kelola Galeri & Dokumentasi</span>
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Kelola koleksi foto dan video kegiatan, prestasi, sarana, dan acara SMAN 21 Jakarta.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button onclick="openAddModal()" class="px-4 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs shadow-md transition-all flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i>
                        <span>Tambah Media Baru</span>
                    </button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-600 flex items-center justify-center text-lg font-bold">
                        <i class="fa-solid fa-photo-film"></i>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 font-medium block">Total Media</span>
                        <span class="text-lg font-extrabold text-brand-darkNavy"><?= $totalMedia; ?></span>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center text-lg font-bold">
                        <i class="fa-solid fa-image"></i>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 font-medium block">Foto</span>
                        <span class="text-lg font-extrabold text-brand-darkNavy"><?= $totalFoto; ?></span>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-600 flex items-center justify-center text-lg font-bold">
                        <i class="fa-solid fa-video"></i>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 font-medium block">Video</span>
                        <span class="text-lg font-extrabold text-brand-darkNavy"><?= $totalVideo; ?></span>
                    </div>
                </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                <form method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2 overflow-x-auto pb-2 sm:pb-0 scrollbar-none">
                        <a href="index.php" class="px-3 py-1.5 rounded-lg text-xs font-bold border <?= $filterKat === 'Semua' ? 'bg-cyan-600 text-white border-cyan-600' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'; ?>">
                            Semua
                        </a>
                        <?php foreach ($kategoriList as $kat): ?>
                        <a href="index.php?filter_kategori=<?= urlencode($kat); ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold border whitespace-nowrap <?= $filterKat === $kat ? 'bg-cyan-600 text-white border-cyan-600' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'; ?>">
                            <?= htmlspecialchars($kat); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="relative min-w-[220px]">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($searchKey); ?>" placeholder="Cari judul galeri..." class="w-full pl-8 pr-3 py-1.5 rounded-xl text-xs bg-slate-50 border border-slate-200 focus:outline-none focus:border-cyan-500">
                    </div>
                </form>
            </div>

            <!-- ================================================================= -->
            <!-- 📊 DATA TABLE GALERI                                              -->
            <!-- ================================================================= -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200 text-[10px]">
                                <th class="p-4 w-12 text-center">Urutan</th>
                                <th class="p-4 w-28">Preview</th>
                                <th class="p-4">Judul & Deskripsi</th>
                                <th class="p-4 w-28">Kategori</th>
                                <th class="p-4 w-28">Tanggal</th>
                                <th class="p-4 w-24 text-center">Status</th>
                                <th class="p-4 w-32 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($galleryItems)): ?>
                            <tr>
                                <td colspan="7" class="p-8 text-center text-slate-400">
                                    <i class="fa-solid fa-camera-retro text-3xl mb-2 block"></i>
                                    <span>Belum ada data media galeri. Silakan tambahkan media baru.</span>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($galleryItems as $item): 
                                $isVid = ($item['tipe_media'] === 'video');
                                $fileUrl = '../../uploads/galeri/' . htmlspecialchars($item['file_media']);
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <!-- Urutan Form -->
                                <td class="p-4 text-center">
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="action" value="update_urutan">
                                        <input type="hidden" name="id" value="<?= $item['id']; ?>">
                                        <input type="number" name="urutan" value="<?= $item['urutan']; ?>" onchange="this.form.submit()" class="w-12 py-1 px-1 text-center bg-slate-100 border border-slate-200 rounded-lg text-xs font-bold focus:outline-none focus:border-cyan-500">
                                    </form>
                                </td>

                                <!-- Preview Media -->
                                <td class="p-4">
                                    <div class="w-20 h-14 rounded-xl overflow-hidden bg-slate-900 relative border border-slate-200 group">
                                        <?php if ($isVid): ?>
                                            <video src="<?= $fileUrl; ?>" class="w-full h-full object-cover muted opacity-80"></video>
                                            <span class="absolute inset-0 flex items-center justify-center text-white bg-black/40 text-xs">
                                                <i class="fa-solid fa-play-circle text-lg text-purple-400"></i>
                                            </span>
                                        <?php else: ?>
                                            <img src="<?= $fileUrl; ?>" alt="Media" class="w-full h-full object-cover" onerror="this.src='https://via.placeholder.com/80?text=No+Image';">
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Judul & Deskripsi -->
                                <td class="p-4">
                                    <div class="font-bold text-brand-darkNavy text-xs leading-snug">
                                        <?= htmlspecialchars($item['judul']); ?>
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-1 line-clamp-1">
                                        <?= htmlspecialchars($item['deskripsi'] ?: 'Tidak ada deskripsi'); ?>
                                    </div>
                                </td>

                                <!-- Kategori -->
                                <td class="p-4">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-extrabold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                                        <?= htmlspecialchars($item['kategori']); ?>
                                    </span>
                                </td>

                                <!-- Tanggal -->
                                <td class="p-4 text-slate-500 whitespace-nowrap">
                                    <?= date('d M Y', strtotime($item['tanggal'])); ?>
                                </td>

                                <!-- Status Toggle -->
                                <td class="p-4 text-center">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $item['id']; ?>">
                                        <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase transition-all <?= $item['status_aktif'] ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-slate-100 text-slate-400 border border-slate-200'; ?>">
                                            <?= $item['status_aktif'] ? 'Aktif' : 'Draft'; ?>
                                        </button>
                                    </form>
                                </td>

                                <!-- Action Buttons -->
                                <td class="p-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button onclick='openEditModal(<?= json_encode($item); ?>)' class="p-2 rounded-lg bg-amber-500/10 text-amber-600 hover:bg-amber-500 hover:text-white transition-colors" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus media ini?');" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $item['id']; ?>">
                                            <button type="submit" class="p-2 rounded-lg bg-rose-500/10 text-rose-600 hover:bg-rose-600 hover:text-white transition-colors" title="Hapus">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- ===================================================================== -->
    <!-- ➕ MODAL TAMBAH & EDIT GALERI                                         -->
    <!-- ===================================================================== -->
    <div id="mediaModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl overflow-hidden border border-slate-200 transform transition-all">
            
            <div class="bg-brand-darkNavy px-6 py-4 text-white flex items-center justify-between">
                <h3 id="modalTitle" class="text-sm font-bold flex items-center gap-2">
                    <i class="fa-solid fa-image text-cyan-400"></i>
                    <span>Tambah Media Galeri</span>
                </h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="">

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Judul Media <span class="text-rose-500">*</span></label>
                    <input type="text" name="judul" id="formJudul" required placeholder="Contoh: Upacara Bendera HUT RI Ke-81" class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 border border-slate-200 focus:outline-none focus:border-cyan-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Kategori</label>
                        <select name="kategori" id="formKategori" class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 border border-slate-200 focus:outline-none focus:border-cyan-500">
                            <?php foreach ($kategoriList as $kat): ?>
                            <option value="<?= $kat; ?>"><?= $kat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Dokumen</label>
                        <input type="date" name="tanggal" id="formTanggal" value="<?= date('Y-m-d'); ?>" class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 border border-slate-200 focus:outline-none focus:border-cyan-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Singkat</label>
                    <textarea name="deskripsi" id="formDeskripsi" rows="3" placeholder="Tuliskan keterangan ringkas seputar foto atau video ini..." class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 border border-slate-200 focus:outline-none focus:border-cyan-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Upload Berkas Foto / Video</label>
                        <input type="file" name="file_media" id="formFile" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100">
                        <span id="fileNotice" class="text-[10px] text-slate-400 block mt-1">
                            Format: JPG, PNG, WEBP, MP4, WEBM
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Urutan Tampil</label>
                        <input type="number" name="urutan" id="formUrutan" value="1" min="1" class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 border border-slate-200 focus:outline-none focus:border-cyan-500">
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-between border-t border-slate-100">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="status_aktif" id="formStatus" value="1" checked class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                        <span class="text-xs font-bold text-slate-700">Publikasikan Langsung</span>
                    </label>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs shadow-md transition-colors">
                            Simpan Media
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript Interactions -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            sidebar.classList.toggle('hidden');
        }

        function openAddModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-image text-cyan-400"></i><span>Tambah Media Galeri Baru</span>';
            document.getElementById('formAction').value = 'add';
            document.getElementById('formId').value = '';
            document.getElementById('formJudul').value = '';
            document.getElementById('formKategori').value = 'Sekolah';
            document.getElementById('formDeskripsi').value = '';
            document.getElementById('formTanggal').value = new Date().toISOString().split('T')[0];
            document.getElementById('formUrutan').value = '1';
            document.getElementById('formStatus').checked = true;
            document.getElementById('formFile').required = true;
            document.getElementById('fileNotice').innerText = 'Format: JPG, PNG, WEBP, MP4, WEBM';

            const modal = document.getElementById('mediaModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function openEditModal(data) {
            document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square text-cyan-400"></i><span>Edit Media Galeri</span>';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formId').value = data.id;
            document.getElementById('formJudul').value = data.judul;
            document.getElementById('formKategori').value = data.kategori || 'Sekolah';
            document.getElementById('formDeskripsi').value = data.deskripsi || '';
            document.getElementById('formTanggal').value = data.tanggal || '';
            document.getElementById('formUrutan').value = data.urutan || '1';
            document.getElementById('formStatus').checked = (parseInt(data.status_aktif) === 1);
            document.getElementById('formFile').required = false;
            document.getElementById('fileNotice').innerText = 'Kosongkan jika tidak ingin mengganti berkas.';

            const modal = document.getElementById('mediaModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            const modal = document.getElementById('mediaModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    </script>
</body>
</html>
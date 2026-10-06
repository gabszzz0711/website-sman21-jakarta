<?php
session_start();

// =============================================================================
// KONFIGURASI DATABASE & KONEKSI PDO (SINKRON DENGAN SMAN 21)
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
        $conn = null;
    }
}

// Helper Format Path Gambar (Disesuaikan dengan direktori upload admin: ../uploads/galeri/)
function formatImgPath($path, $default = '../uploads/tempat/gedungA1.jpg') {
    if (empty($path)) return $default;
    $path = trim($path);
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) return $path;
    if (strpos($path, '../') === 0) return $path;
    if (strpos($path, 'uploads/') === 0) return '../' . $path;
    return '../uploads/galeri/' . ltrim($path, '/');
}

// =============================================================================
// ENDPOINT AJAX UNTUK S21 AI ASSISTANT
// =============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'ai_query') {
    header('Content-Type: application/json; charset=utf-8');
    $userQuery = trim($_POST['query'] ?? $_GET['q'] ?? '');
    
    if (empty($userQuery)) {
        echo json_encode(['status' => 'error', 'message' => 'Pertanyaan tidak boleh kosong.']);
        exit;
    }

    $responseAnswer = "";
    $sumberInfo = "Basis Informasi SMAN 21 Jakarta";
    $relatedLink = "";

    if ($conn) {
        $stmtAiCfg = $conn->query("SELECT pesan_tidak_tersedia FROM ai_pengaturan LIMIT 1");
        $aiCfg = $stmtAiCfg ? $stmtAiCfg->fetch() : null;
        $fallbackMsg = $aiCfg['pesan_tidak_tersedia'] ?? "Maaf, informasi tersebut belum tersedia dalam basis data resmi SMAN 21 Jakarta.";

        $stmtKnw = $conn->prepare("SELECT * FROM ai_knowledge WHERE status_aktif = 1 ORDER BY prioritas DESC");
        $stmtKnw->execute();
        $knowledges = $stmtKnw->fetchAll();

        $matched = false;
        $qLower = strtolower($userQuery);

        foreach ($knowledges as $k) {
            $keywords = array_filter(array_map('trim', explode(',', strtolower($k['keyword'] ?? ''))));
            $pertanyaanLower = strtolower($k['pertanyaan']);

            if (!empty($keywords)) {
                foreach ($keywords as $kw) {
                    if (!empty($kw) && strpos($qLower, $kw) !== false) {
                        $responseAnswer = $k['jawaban'];
                        $sumberInfo = $k['sumber_informasi'] ?: "Database AI SMAN 21 Jakarta";
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched && (strpos($qLower, $pertanyaanLower) !== false || strpos($pertanyaanLower, $qLower) !== false)) {
                $responseAnswer = $k['jawaban'];
                $sumberInfo = $k['sumber_informasi'] ?: "Database AI SMAN 21 Jakarta";
                $matched = true;
                break;
            }

            if ($matched) break;
        }

        if (!$matched) {
            if (strpos($qLower, 'ppdb') !== false || strpos($qLower, 'daftar') !== false) {
                $responseAnswer = "Informasi PPDB SMAN 21 Jakarta dapat diakses melalui menu SPMB Online.";
                $sumberInfo = "Informasi PPDB Resmi";
                $relatedLink = "../informasi/ppdb.php";
                $matched = true;
            } elseif (strpos($qLower, 'galeri') !== false || strpos($qLower, 'foto') !== false || strpos($qLower, 'dokumentasi') !== false) {
                $responseAnswer = "Halaman Galeri berisi kumpulan foto kegiatan, fasilitas, dan prestasi SMAN 21 Jakarta.";
                $sumberInfo = "Dokumentasi Galeri";
                $matched = true;
            }
        }

        if (!$matched) {
            $responseAnswer = $fallbackMsg;
            $sumberInfo = "Sistem Verifikasi SMAN 21";
        }
    } else {
        $responseAnswer = "Selamat datang di Layanan Informasi SMAN 21 Jakarta. Silakan ajukan pertanyaan seputar Profil, Akademik, PPDB, Galeri, atau Prestasi.";
        $sumberInfo = "Sistem SMAN 21";
    }

    echo json_encode([
        'status' => 'success',
        'answer' => $responseAnswer,
        'source' => $sumberInfo,
        'related_link' => $relatedLink
    ]);
    exit;
}

// =============================================================================
// FETCH PROFIL SEKOLAH & DATA GALERI
// =============================================================================
$sekolah = [
    'nama_sekolah' => 'SMAN 21 Jakarta',
    'slogan' => 'Where Future Begins • Unggul, Berkarakter, Berteknologi',
    'akreditasi' => 'A',
    'alamat' => 'Jl. Tanah Mas Raya No.1, RT.3/RW.1, Kayu Putih, Kec. Pulo Gadung, Kota Jakarta Timur',
    'telepon' => '(021) 4892346',
    'email' => 'info@sman21jkt.sch.id',
    'instagram' => 'https://instagram.com/sman21jakarta',
    'youtube' => 'https://youtube.com/sman21jkt',
    'logo' => '../uploads/logo/sman21jakarta.png',
    'hero_bg' => '../uploads/tempat/gedungA1.jpg'
];

if ($conn) {
    $stmtSek = $conn->query("SELECT * FROM pengaturan_sekolah LIMIT 1");
    if ($stmtSek && $rowSek = $stmtSek->fetch()) {
        if (!empty($rowSek['nama_sekolah'])) $sekolah['nama_sekolah'] = $rowSek['nama_sekolah'];
        if (!empty($rowSek['slogan'])) $sekolah['slogan'] = $rowSek['slogan'];
        if (!empty($rowSek['telepon'])) $sekolah['telepon'] = $rowSek['telepon'];
        if (!empty($rowSek['email'])) $sekolah['email'] = $rowSek['email'];
        if (!empty($rowSek['instagram'])) $sekolah['instagram'] = $rowSek['instagram'];
    }
}

// Filter & Query Data Galeri
$filterKat = trim($_GET['kategori'] ?? 'Semua');
$searchKey = trim($_GET['search'] ?? '');

$galleryItems = [];

if ($conn) {
    try {
        // Utamakan tabel 'gallery' yang dioperasikan oleh Admin CRUD
        $tableName = 'gallery';
        $check = $conn->query("SHOW TABLES LIKE 'gallery'");
        if (!$check || $check->rowCount() == 0) {
            $check2 = $conn->query("SHOW TABLES LIKE 'galeri'");
            if ($check2 && $check2->rowCount() > 0) {
                $tableName = 'galeri';
            }
        }

        // Cek struktur kolom tabel secara dinamis
        $columns = [];
        $colStmt = $conn->query("DESCRIBE {$tableName}");
        if ($colStmt) {
            $colRows = $colStmt->fetchAll(PDO::FETCH_COLUMN);
            $columns = array_map('strtolower', $colRows);
        }

        $query = "SELECT * FROM {$tableName} WHERE 1=1";
        $params = [];

        // Filter status aktif (Menampilkan media aktif)
        if (in_array('status_aktif', $columns)) {
            $query .= " AND status_aktif = 1";
        } elseif (in_array('status', $columns)) {
            $query .= " AND status = 'published'";
        }

        if ($filterKat !== 'Semua' && !empty($filterKat)) {
            if (in_array('kategori', $columns)) {
                $query .= " AND kategori = ?";
                $params[] = $filterKat;
            }
        }

        if (!empty($searchKey)) {
            $query .= " AND (judul LIKE ? OR deskripsi LIKE ?)";
            $params[] = "%{$searchKey}%";
            $params[] = "%{$searchKey}%";
        }

        if (in_array('urutan', $columns)) {
            $query .= " ORDER BY urutan ASC, id DESC";
        } else {
            $query .= " ORDER BY id DESC";
        }

        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $galleryItems = $stmt->fetchAll();
    } catch (PDOException $e) {
        $galleryItems = [];
    }
}

// Fallback dummy jika database kosong
if (empty($galleryItems)) {
    $galleryItems = [
        [
            'id' => 1,
            'judul' => 'Gedung Utama & Halaman SMAN 21 Jakarta',
            'kategori' => 'Sekolah',
            'file_media' => '../uploads/tempat/gedungA1.jpg',
            'deskripsi' => 'Fasilitas gedung utama dengan lingkungan yang asri, kondusif, dan mendukung kegiatan pembelajaran modern.'
        ],
        [
            'id' => 2,
            'judul' => 'Kegiatan Upacara Bendera & Kedisiplinan Siswa',
            'kategori' => 'Kegiatan',
            'file_media' => '../uploads/tempat/gedungA.jpg',
            'deskripsi' => 'Pelaksanaan upacara rutin dalam rangka menanamkan nilai patriotisme dan Profil Pelajar Pancasila.'
        ],
        [
            'id' => 3,
            'judul' => 'Penghargaan Olimpiade & Prestasi Siswa',
            'kategori' => 'Prestasi',
            'file_media' => '../uploads/tempat/gedungA1.jpg',
            'deskripsi' => 'Apresiasi atas keberhasilan para siswa SMAN 21 Jakarta dalam meraih juara di tingkat regional dan nasional.'
        ]
    ];
}

$kategoriList = ['Semua', 'Sekolah', 'Kegiatan', 'Prestasi', 'Fasilitas', 'Siswa', 'Event'];
$logoPath = formatImgPath($sekolah['logo'], '../uploads/logo/sman21jakarta.png');
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Galeri & Dokumentasi — SMAN 21 Jakarta</title>
    <meta name="description" content="Galeri foto dan dokumentasi kegiatan, fasilitas, serta prestasi SMA Negeri 21 Jakarta.">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($logoPath); ?>">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
    
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
                            red: '#DC2626',
                            slate: '#F8FAFC'
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        html, body {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            background-color: #F8FAFC;
            color: #1E293B;
            width: 100%;
            max-width: 100vw;
            overflow-x: hidden !important;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        *, *:before, *:after {
            box-sizing: inherit;
        }

        /* 🔮 REVEAL ANIMATION PER SECTION */
        .reveal-section {
            opacity: 0;
            transform: translateY(32px) scale(0.985);
            transition: opacity 0.85s cubic-bezier(0.16, 1, 0.3, 1), transform 0.85s cubic-bezier(0.16, 1, 0.3, 1);
            will-change: opacity, transform;
        }

        .reveal-section.is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .delay-100 { transition-delay: 100ms; }
        .delay-200 { transition-delay: 200ms; }
        .delay-300 { transition-delay: 300ms; }
        .delay-400 { transition-delay: 400ms; }

        /* 🏬 ULTRA-MODERN SCHOOL CARD UI */
        .school-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }

        .school-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #00A8CC, #0284C7);
            opacity: 0;
            transition: opacity 0.35s ease;
        }

        .school-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 35px -8px rgba(15, 23, 42, 0.12), 0 0 20px rgba(0, 168, 204, 0.08);
            border-color: #CBD5E1;
        }

        .school-card:hover::before {
            opacity: 1;
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar { width: 7px; height: 6px; }
        ::-webkit-scrollbar-track { background: #061224; }
        ::-webkit-scrollbar-thumb { background: #00A8CC; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #0086A3; }

        @keyframes glowPulse {
            0%, 100% { box-shadow: 0 0 15px rgba(0, 168, 204, 0.4); }
            50% { box-shadow: 0 0 30px rgba(0, 168, 204, 0.8); }
        }
        .pulse-glow { animation: glowPulse 2.5s infinite ease-in-out; }
    </style>
</head>
<body class="relative min-h-screen text-slate-800 bg-slate-50 selection:bg-cyan-500 selection:text-white max-w-full overflow-x-hidden flex flex-col justify-between">

    <div>
        <!-- ===================================================================== -->
        <!-- 🧭 GLOBAL HEADER & NAVBAR                                             -->
        <!-- ===================================================================== -->
        <div class="bg-brand-darkNavy text-slate-300 text-xs py-2 border-b border-slate-800 hidden sm:block">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                <div class="flex items-center gap-6">
                    <span><i class="fa-solid fa-location-dot text-cyan-400 mr-2"></i>Pulogadung, Jakarta Timur</span>
                    <span><i class="fa-solid fa-phone text-cyan-400 mr-2"></i><?= htmlspecialchars($sekolah['telepon']); ?></span>
                    <span><i class="fa-solid fa-envelope text-cyan-400 mr-2"></i><?= htmlspecialchars($sekolah['email']); ?></span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-[11px] text-cyan-400 font-semibold bg-cyan-500/10 px-2.5 py-0.5 rounded border border-cyan-500/20">Akreditasi A</span>
                    <a href="<?= htmlspecialchars($sekolah['instagram']); ?>" target="_blank" class="hover:text-cyan-400 transition-colors"><i class="fa-brands fa-instagram"></i></a>
                    <a href="<?= htmlspecialchars($sekolah['youtube']); ?>" target="_blank" class="hover:text-cyan-400 transition-colors"><i class="fa-brands fa-youtube"></i></a>
                </div>
            </div>
        </div>

        <!-- Main Navigation Header -->
        <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    
                    <a href="../index.php" class="flex items-center gap-3.5 group">
                        <img src="<?= htmlspecialchars($logoPath); ?>" alt="SMAN 21 Jakarta Logo" class="h-11 w-11 object-contain group-hover:scale-105 transition-transform">
                        <div>
                            <span class="text-base sm:text-lg font-bold text-brand-darkNavy block leading-tight group-hover:text-cyan-600 transition-colors">
                                SMAN 21 JAKARTA
                            </span>
                            <span class="text-[10px] tracking-wider text-slate-500 block uppercase font-semibold">
                                Official School Portal
                            </span>
                        </div>
                    </a>

                    <nav class="hidden lg:flex items-center gap-1 font-semibold text-xs text-slate-700">
                        <a href="../index.php" class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 transition-all">Beranda</a>

                        <div class="relative group">
                            <button class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 flex items-center gap-1.5 transition-all">
                                <span>Profil</span>
                                <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                            </button>
                            <div class="absolute top-full left-0 w-56 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                                <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                    <a href="../profil/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Tentang Sekolah</a>
                                    <a href="../profil/sejarah.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Sejarah Sekolah</a>
                                    <a href="../profil/visi-misi.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Visi & Misi</a>
                                    <a href="../profil/sambutan.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Sambutan Kepala Sekolah</a>
                                </div>
                            </div>
                        </div>

                        <div class="relative group">
                            <button class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 flex items-center gap-1.5 transition-all">
                                <span>Akademik</span>
                                <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                            </button>
                            <div class="absolute top-full left-0 w-60 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                                <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                    <a href="../akademik/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Overview Akademik</a>
                                    <a href="../akademik/program.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Program Pendidikan</a>
                                    <a href="../akademik/guru.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Daftar Guru & Staf</a>
                                    <a href="../akademik/pemeringkatan.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Pemeringkatan Jurusan</a>
                                    <a href="../akademik/keterserapan-ptn.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Keterserapan PTN</a>
                                </div>
                            </div>
                        </div>

                        <div class="relative group">
                            <button class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 flex items-center gap-1.5 transition-all">
                                <span>Kegiatan</span>
                                <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                            </button>
                            <div class="absolute top-full left-0 w-52 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                                <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                    <a href="../kegiatan/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Semua Kegiatan</a>
                                    <a href="../kegiatan/ekstrakurikuler.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Ekstrakurikuler</a>
                                </div>
                            </div>
                        </div>

                        <a href="../prestasi/index.php" class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 transition-all">Prestasi</a>
                        <a href="../fasilitas/index.php" class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 transition-all">Fasilitas</a>

                        <div class="relative group">
                            <button class="px-3.5 py-2 rounded-lg text-cyan-600 bg-cyan-50 font-bold border border-cyan-200 flex items-center gap-1.5 transition-all">
                                <span>Informasi</span>
                                <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                            </button>
                            <div class="absolute top-full right-0 w-52 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                                <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                    <a href="../informasi/berita.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Berita Sekolah</a>
                                    <a href="../informasi/agenda.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Agenda & Kalender</a>
                                    <a href="../informasi/ppdb.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">PPDB Info</a>
                                    <a href="index.php" class="block px-3 py-2 rounded-lg bg-cyan-50 text-cyan-600 font-bold transition-colors">Galeri Foto</a>
                                    <a href="../kontak/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Kontak & Lokasi</a>
                                </div>
                            </div>
                        </div>

                        <a href="../informasi/ppdb.php" class="ml-2 px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs flex items-center gap-2 shadow-sm transition-all">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span>SPMB Online</span>
                        </a>
                    </nav>

                    <button onclick="toggleMobileMenu()" class="lg:hidden p-2 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200 transition-colors">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                </div>
            </div>

            <div id="mobileMenu" class="hidden lg:hidden bg-white border-t border-slate-200 px-6 py-6 space-y-4 max-h-[80vh] overflow-y-auto">
                <a href="../index.php" class="block text-slate-700 font-semibold text-xs">Beranda</a>
                <div class="space-y-1">
                    <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Profil</span>
                    <a href="../profil/index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Profil</a>
                    <a href="../profil/sejarah.php" class="block pl-3 py-1 text-xs text-slate-700">Sejarah</a>
                    <a href="../profil/visi-misi.php" class="block pl-3 py-1 text-xs text-slate-700">Visi & Misi</a>
                </div>
                <div class="space-y-1">
                    <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Informasi & Galeri</span>
                    <a href="index.php" class="block pl-3 py-1 text-xs text-cyan-600 font-bold">Galeri Foto</a>
                    <a href="../informasi/berita.php" class="block pl-3 py-1 text-xs text-slate-700">Berita</a>
                    <a href="../informasi/ppdb.php" class="block pl-3 py-1 text-xs text-slate-700">PPDB Info</a>
                </div>
                <a href="../kontak/index.php" class="block text-xs font-semibold text-slate-700">Kontak</a>
            </div>
        </header>

        <!-- ===================================================================== -->
        <!-- 🖼️ PAGE HERO BANNER                                                   -->
        <!-- ===================================================================== -->
        <section class="relative bg-brand-darkNavy text-white py-16 sm:py-20 overflow-hidden">
            <div class="absolute inset-0 bg-cover bg-center opacity-20" style="background-image: url('<?= htmlspecialchars(formatImgPath($sekolah['hero_bg'])); ?>');"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-brand-darkNavy via-brand-darkNavy/90 to-brand-navy/80"></div>
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center space-y-4 reveal-section">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-cyan-500/20 border border-cyan-400/30 text-cyan-300 text-xs font-bold tracking-wide uppercase backdrop-blur-md">
                    <i class="fa-solid fa-camera-retro text-cyan-400"></i>
                    <span>DOKUMENTASI RESMI SMAN 21 JAKARTA</span>
                </div>
                <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight">
                    Galeri & Dokumentasi
                </h1>
                <p class="text-xs sm:text-base text-slate-300 max-w-2xl mx-auto font-normal leading-relaxed">
                    Kumpulan momen terbaik, sarana prasarana, serta rekam jejak kegiatan akademis dan ekstrakurikuler di SMAN 21 Jakarta.
                </p>
            </div>
        </section>

        <!-- ===================================================================== -->
        <!-- 🔍 FILTER & SEARCH GALERI SECTION                                     -->
        <!-- ===================================================================== -->
        <section class="py-10 bg-white border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 reveal-section">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    
                    <!-- Filter Kategori Tabs -->
                    <div class="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0 scrollbar-none">
                        <?php foreach ($kategoriList as $kat): 
                            $isActive = ($filterKat === $kat);
                        ?>
                        <a href="?kategori=<?= urlencode($kat); ?><?= !empty($searchKey) ? '&search='.urlencode($searchKey) : ''; ?>" 
                           class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all border <?= $isActive ? 'bg-cyan-600 text-white border-cyan-600 shadow-md' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200 hover:text-slate-900'; ?>">
                            <?= htmlspecialchars($kat); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Search Box -->
                    <form action="" method="GET" class="flex items-center gap-2 w-full md:w-72">
                        <?php if ($filterKat !== 'Semua'): ?>
                        <input type="hidden" name="kategori" value="<?= htmlspecialchars($filterKat); ?>">
                        <?php endif; ?>
                        <div class="relative w-full">
                            <input type="text" name="search" value="<?= htmlspecialchars($searchKey); ?>" placeholder="Cari foto / kegiatan..." class="w-full pl-9 pr-4 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 focus:outline-none focus:border-cyan-600 focus:bg-white transition-all">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-brand-darkNavy text-white hover:bg-slate-800 text-xs font-bold transition-all shadow-sm">
                            Cari
                        </button>
                    </form>

                </div>
            </div>
        </section>

        <!-- ===================================================================== -->
        <!-- 🖼️ GALLERY GRID DISPLAY SECTION                                       -->
        <!-- ===================================================================== -->
        <section class="py-16 bg-slate-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <?php if (!empty($galleryItems)): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php 
                    $delay = 100;
                    foreach ($galleryItems as $item): 
                        $mediaFile = $item['file_media'] ?? $item['file_path'] ?? $item['gambar'] ?? $item['foto'] ?? '';
                        $imgPath = formatImgPath($mediaFile);
                        $judul = $item['judul'] ?? $item['title'] ?? 'Dokumentasi SMAN 21';
                        $kategori = $item['kategori'] ?? 'Sekolah';
                        $deskripsi = $item['deskripsi'] ?? $item['caption'] ?? '';
                        $tipeMedia = $item['tipe_media'] ?? 'image';
                        $isVid = ($tipeMedia === 'video' || preg_match('/\.(mp4|webm|ogg|mov|mkv)$/i', $mediaFile));
                    ?>
                    <div class="school-card rounded-2xl overflow-hidden group cursor-pointer reveal-section delay-<?= $delay; ?>"
                         onclick="openLightbox('<?= htmlspecialchars(addslashes($imgPath)); ?>', '<?= htmlspecialchars(addslashes($judul)); ?>', '<?= htmlspecialchars(addslashes($kategori)); ?>', '<?= htmlspecialchars(addslashes($deskripsi)); ?>', <?= $isVid ? 'true' : 'false'; ?>)">
                        
                        <div class="relative aspect-[4/3] bg-slate-900 overflow-hidden">
                            <?php if ($isVid): ?>
                            <video src="<?= htmlspecialchars($imgPath); ?>" class="w-full h-full object-cover opacity-80" muted></video>
                            <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                                <div class="w-10 h-10 rounded-full bg-cyan-600 text-white flex items-center justify-center text-sm shadow-lg">
                                    <i class="fa-solid fa-play"></i>
                                </div>
                            </div>
                            <?php else: ?>
                            <img src="<?= htmlspecialchars($imgPath); ?>" 
                                 alt="<?= htmlspecialchars($judul); ?>" 
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                 onerror="this.onerror=null; this.src='../uploads/tempat/gedungA1.jpg';">
                            <?php endif; ?>
                            
                            <div class="absolute inset-0 bg-gradient-to-t from-brand-darkNavy/90 via-brand-darkNavy/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-4">
                                <span class="text-[10px] font-bold text-cyan-300 uppercase tracking-wider bg-cyan-950/80 px-2.5 py-1 rounded-md border border-cyan-500/30 w-fit mb-1.5">
                                    <?= htmlspecialchars($kategori); ?>
                                </span>
                                <h3 class="text-xs font-bold text-white line-clamp-2 leading-snug">
                                    <?= htmlspecialchars($judul); ?>
                                </h3>
                            </div>
                            
                            <div class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/40 text-white backdrop-blur-md flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fa-solid fa-expand text-xs"></i>
                            </div>
                        </div>

                        <div class="p-4 bg-white border-t border-slate-100">
                            <span class="text-[10px] font-bold text-cyan-600 uppercase tracking-wider block mb-1">
                                <?= htmlspecialchars($kategori); ?>
                            </span>
                            <h3 class="text-xs font-bold text-brand-darkNavy group-hover:text-cyan-600 transition-colors line-clamp-1">
                                <?= htmlspecialchars($judul); ?>
                            </h3>
                            <?php if (!empty($deskripsi)): ?>
                            <p class="text-[11px] text-slate-500 mt-1 line-clamp-2 leading-relaxed">
                                <?= htmlspecialchars($deskripsi); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php 
                    $delay = ($delay >= 400) ? 100 : $delay + 100;
                    endforeach; 
                    ?>
                </div>

                <?php else: ?>
                <div class="school-card p-12 rounded-2xl text-center max-w-md mx-auto space-y-4 bg-white border border-slate-200 reveal-section">
                    <div class="w-16 h-16 rounded-full bg-cyan-100 text-cyan-600 flex items-center justify-center mx-auto text-2xl">
                        <i class="fa-solid fa-images"></i>
                    </div>
                    <h3 class="text-base font-bold text-brand-darkNavy">Dokumentasi Tidak Ditemukan</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Belum ada foto yang tersedia untuk kategori atau kata kunci pencarian tersebut.
                    </p>
                    <a href="index.php" class="inline-block px-5 py-2.5 rounded-xl bg-cyan-600 text-white text-xs font-bold shadow hover:bg-cyan-700 transition-all">
                        Tampilkan Semua Galeri
                    </a>
                </div>
                <?php endif; ?>

            </div>
        </section>
    </div>

    <!-- ===================================================================== -->
    <!-- 🔍 LIGHTBOX PREVIEW MODAL                                             -->
    <!-- ===================================================================== -->
    <div id="lightboxModal" class="fixed inset-0 z-[9995] bg-black/90 backdrop-blur-md hidden flex items-center justify-center p-4 sm:p-6 transition-all duration-300">
        <button onclick="closeLightbox()" class="absolute top-5 right-5 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>

        <div class="max-w-4xl w-full bg-brand-darkNavy border border-slate-700 rounded-2xl overflow-hidden shadow-2xl flex flex-col md:flex-row">
            <div id="lightboxMediaContainer" class="w-full md:w-2/3 bg-black flex items-center justify-center max-h-[70vh]">
                <img id="lightboxImg" src="" alt="Preview" class="max-w-full max-h-[70vh] object-contain">
            </div>
            <div class="w-full md:w-1/3 p-6 text-white flex flex-col justify-between space-y-4">
                <div class="space-y-3">
                    <span id="lightboxKat" class="px-2.5 py-1 rounded bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 text-[10px] font-bold uppercase tracking-wider inline-block">
                        Kategori
                    </span>
                    <h3 id="lightboxTitle" class="text-base font-bold text-white leading-snug">
                        Judul foto
                    </h3>
                    <p id="lightboxDesc" class="text-xs text-slate-300 leading-relaxed font-normal">
                        Deskripsi foto...
                    </p>
                </div>
                <button onclick="closeLightbox()" class="w-full py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs border border-white/20 transition-all">
                    Tutup Preview
                </button>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- 🦶 FOOTER SECTION                                                      -->
    <!-- ===================================================================== -->
    <footer class="bg-brand-darkNavy text-slate-400 text-xs border-t border-slate-800 pt-16 pb-12 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 pb-12 border-b border-slate-800">
                
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <img src="<?= htmlspecialchars($logoPath); ?>" alt="Logo SMAN 21" class="h-10 w-10 object-contain">
                        <div>
                            <span class="text-sm font-bold text-white block">SMAN 21 JAKARTA</span>
                            <span class="text-[10px] text-cyan-400 font-semibold uppercase">Official School Portal</span>
                        </div>
                    </div>
                    <p class="text-slate-400 leading-relaxed text-[11px]">
                        Pusat keunggulan pendidikan menengah atas berkarakter Pancasila, berprestasi nasional, dan berwawasan teknologi modern di DKI Jakarta.
                    </p>
                    <div class="flex items-center gap-3 pt-2">
                        <a href="<?= htmlspecialchars($sekolah['instagram']); ?>" target="_blank" class="w-8 h-8 rounded-full bg-white/5 hover:bg-cyan-600 text-white flex items-center justify-center transition-colors">
                            <i class="fa-brands fa-instagram text-xs"></i>
                        </a>
                        <a href="<?= htmlspecialchars($sekolah['youtube']); ?>" target="_blank" class="w-8 h-8 rounded-full bg-white/5 hover:bg-cyan-600 text-white flex items-center justify-center transition-colors">
                            <i class="fa-brands fa-youtube text-xs"></i>
                        </a>
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Navigasi Utama</h3>
                    <ul class="space-y-2 text-[11px]">
                        <li><a href="../profil/index.php" class="hover:text-cyan-400 transition-colors">Profil Sekolah</a></li>
                        <li><a href="../akademik/index.php" class="hover:text-cyan-400 transition-colors">Kurikulum & Akademik</a></li>
                        <li><a href="../akademik/guru.php" class="hover:text-cyan-400 transition-colors">Daftar Guru & Staf</a></li>
                        <li><a href="../prestasi/index.php" class="hover:text-cyan-400 transition-colors">Galeri Prestasi Siswa</a></li>
                        <li><a href="../fasilitas/index.php" class="hover:text-cyan-400 transition-colors">Sarana & Fasilitas</a></li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Layanan Informasi</h3>
                    <ul class="space-y-2 text-[11px]">
                        <li><a href="../informasi/ppdb.php" class="hover:text-cyan-400 transition-colors">Informasi SPMB / PPDB</a></li>
                        <li><a href="../informasi/berita.php" class="hover:text-cyan-400 transition-colors">Berita & Pengumuman</a></li>
                        <li><a href="../informasi/agenda.php" class="hover:text-cyan-400 transition-colors">Agenda Kalender Akademik</a></li>
                        <li><a href="../akademik/keterserapan-ptn.php" class="hover:text-cyan-400 transition-colors">Keterserapan PTN Alumni</a></li>
                        <li><a href="../kontak/index.php" class="hover:text-cyan-400 transition-colors">Kontak & Pusat Bantuan</a></li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Jam Layanan Sekolah</h3>
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        Senin - Jumat: 06.30 - 15.00 WIB <br>
                        Sabtu - Minggu & Hari Libur: Tutup
                    </p>
                    <div class="pt-2">
                        <span class="inline-block px-3 py-1 rounded-md bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 text-[10px] font-bold">
                            Akreditasi "A" BAN-S/M
                        </span>
                    </div>
                </div>

            </div>

            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-500 gap-4">
                <p>© <?= date('Y'); ?> SMA Negeri 21 Jakarta. Hak Cipta Dilindungi Undang-Undang.</p>
                <div class="flex items-center gap-4">
                    <a href="../admin/login.php" class="hover:text-cyan-400 transition-colors"><i class="fa-solid fa-lock text-[10px] mr-1"></i>Portal Admin</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- ===================================================================== -->
    <!-- 💬 AI ASSISTANT DRAWER PANEL                                          -->
    <!-- ===================================================================== -->
    <div id="aiPanel" class="fixed inset-y-0 right-0 z-[9990] w-full max-w-md bg-white shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col border-l border-slate-200">
        <div class="p-4 bg-brand-darkNavy text-white flex items-center justify-between border-b border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-cyan-500/20 text-cyan-400 flex items-center justify-center border border-cyan-500/30">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-white">S21 AI Assistant</h3>
                    <span class="text-[10px] text-cyan-400 block font-semibold">Pusat Layanan Informasi SMAN 21</span>
                </div>
            </div>
            <button onclick="toggleAiPanel()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="chatBox" class="flex-1 p-4 overflow-y-auto space-y-4 bg-slate-50 text-xs">
            <div class="p-3.5 rounded-xl bg-white border border-slate-200 text-slate-700 shadow-sm leading-relaxed space-y-1">
                <p class="font-bold text-brand-darkNavy">Halo! Saya S21 AI Assistant. 👋</p>
                <p>Ada yang bisa saya bantu terkait Galeri, SPMB/PPDB, Kurikulum, atau Kegiatan SMAN 21 Jakarta?</p>
            </div>
        </div>

        <div class="p-3 bg-white border-t border-slate-200">
            <form onsubmit="submitAiChat(event)" class="flex gap-2">
                <input type="text" id="aiInput" placeholder="Tanyakan seputar SMAN 21..." class="flex-1 px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50">
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs transition-colors">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Floating Chat Button -->
    <button onclick="toggleAiPanel()" class="fixed bottom-6 right-6 z-[9000] w-14 h-14 rounded-full bg-cyan-600 hover:bg-cyan-500 text-white shadow-2xl border-2 border-white flex items-center justify-center text-xl transition-all hover:scale-110 active:scale-95 pulse-glow group">
        <i class="fa-solid fa-comments"></i>
        <span class="absolute right-full mr-3 bg-brand-darkNavy text-white text-[10px] font-bold px-2.5 py-1 rounded-md shadow whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity">
            Tanyakan AI Assistant
        </span>
    </button>

    <!-- ===================================================================== -->
    <!-- 📜 JAVASCRIPT LOGIC                                                   -->
    <!-- ===================================================================== -->
    <script>
        // Mobile Menu
        function toggleMobileMenu() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        }

        // Scroll Reveal Observer
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, { threshold: 0.08 });

        document.querySelectorAll('.reveal-section').forEach(el => observer.observe(el));

        // Lightbox Handler
        function openLightbox(img, title, category, desc, isVideo = false) {
            const container = document.getElementById('lightboxMediaContainer');
            if (isVideo) {
                container.innerHTML = `<video src="${img}" controls autoplay class="max-w-full max-h-[70vh] object-contain"></video>`;
            } else {
                container.innerHTML = `<img id="lightboxImg" src="${img}" alt="Preview" class="max-w-full max-h-[70vh] object-contain">`;
            }
            document.getElementById('lightboxTitle').innerText = title || 'Dokumentasi SMAN 21';
            document.getElementById('lightboxKat').innerText = category || 'Sekolah';
            document.getElementById('lightboxDesc').innerText = desc || 'Dokumentasi resmi kegiatan SMAN 21 Jakarta.';
            document.getElementById('lightboxModal').classList.remove('hidden');
        }

        function closeLightbox() {
            const container = document.getElementById('lightboxMediaContainer');
            container.innerHTML = '';
            document.getElementById('lightboxModal').classList.add('hidden');
        }

        // AI Assistant Drawer & Chat
        function toggleAiPanel() {
            document.getElementById('aiPanel').classList.toggle('translate-x-full');
        }

        function submitAiChat(e) {
            e.preventDefault();
            const input = document.getElementById('aiInput');
            const q = input.value.trim();
            if (!q) return;

            const chatBox = document.getElementById('chatBox');
            
            const userMsg = document.createElement('div');
            userMsg.className = 'p-3 rounded-xl bg-cyan-600 text-white ml-auto max-w-[85%] text-xs font-medium shadow-sm';
            userMsg.innerText = q;
            chatBox.appendChild(userMsg);

            input.value = '';
            chatBox.scrollTop = chatBox.scrollHeight;

            const loadingMsg = document.createElement('div');
            loadingMsg.className = 'p-3 rounded-xl bg-white border border-slate-200 text-slate-500 max-w-[85%] text-xs italic shadow-sm';
            loadingMsg.innerText = 'Mencari jawaban resmi...';
            chatBox.appendChild(loadingMsg);
            chatBox.scrollTop = chatBox.scrollHeight;

            fetch('index.php?action=ai_query', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'query=' + encodeURIComponent(q)
            })
            .then(res => res.json())
            .then(data => {
                loadingMsg.remove();
                const aiMsg = document.createElement('div');
                aiMsg.className = 'p-3.5 rounded-xl bg-white border border-slate-200 text-slate-800 max-w-[90%] text-xs shadow-sm space-y-1.5';
                
                let linkHtml = '';
                if (data.related_link) {
                    linkHtml = `<div class="pt-1"><a href="${data.related_link}" class="text-cyan-600 font-bold hover:underline inline-flex items-center gap-1">Pelajari lebih lanjut <i class="fa-solid fa-arrow-right text-[9px]"></i></a></div>`;
                }

                aiMsg.innerHTML = `<div>${data.answer}</div><div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider pt-1 border-t border-slate-100">Sumber: ${data.source}</div>${linkHtml}`;
                chatBox.appendChild(aiMsg);
                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(() => {
                loadingMsg.innerText = 'Maaf, terjadi gangguan koneksi.';
            });
        }
    </script>
</body>
</html>
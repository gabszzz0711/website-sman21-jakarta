<?php
// =============================================================================
// KONFIGURASI DATABASE & KONEKSI BYETHOST
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
    $conn = null;
}

// =============================================================================
// HELPER PERBAIKAN PATH GAMBAR
// =============================================================================
function formatImgPath($img, $default = '../uploads/tempat/gedungA1.jpg') {
    if (empty($img)) return $default;
    $img = trim($img);
    if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
        return $img;
    }
    if (strpos($img, 'uploads/') === 0) {
        return '../' . $img;
    }
    return '../uploads/kegiatan/' . ltrim($img, '/');
}

// =============================================================================
// ENDPOINT AJAX UNTUK S21 AI ASSISTANT
// =============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'ai_query') {
    header('Content-Type: application/json; charset=utf-8');
    $userQuery = trim($_POST['query'] ?? $_GET['q'] ?? '');
    
    if (empty($userQuery)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Pertanyaan tidak boleh kosong.'
        ]);
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
            if (strpos($qLower, 'ekskul') !== false || strpos($qLower, 'ekstrakurikuler') !== false) {
                $responseAnswer = "SMAN 21 Jakarta memiliki beragam ekstrakurikuler unggulan di bidang Olahraga, Seni, Sains, dan Keagamaan seperti Basket, Paskibra, PMR, Karya Ilmiah Remaja, dan Rohis.";
                $sumberInfo = "Informasi Kegiatan & Ekskul";
                $relatedLink = "ekstrakurikuler.php";
                $matched = true;
            } elseif (strpos($qLower, 'kegiatan') !== false || strpos($qLower, 'agenda') !== false) {
                $responseAnswer = "Kegiatan SMAN 21 Jakarta mencakup peringatan hari besar, pentas seni, olimpiade sains, perkemahan pramuka, serta Classmeeting berkala.";
                $sumberInfo = "Agenda Sekolah";
                $relatedLink = "index.php";
                $matched = true;
            }
        }

        if (!$matched) {
            $responseAnswer = $fallbackMsg;
            $sumberInfo = "Sistem Verifikasi SMAN 21";
        }

    } else {
        $responseAnswer = "SMAN 21 Jakarta menyelenggarakan berbagai kegiatan akademis dan non-akademis untuk mendukung pembentukan karakter dan prestasi siswa.";
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
// FETCH DATA SEKOLAH, KEGIATAN, AGENDA & EKSKUL DARI DATABASE
// =============================================================================
$sekolah = [
    'nama_sekolah' => 'SMAN 21 Jakarta',
    'slogan' => 'Where Future Begins • Unggul, Berkarakter, Berteknologi',
    'telepon' => '(021) 4892346',
    'email' => 'info@sman21jkt.sch.id',
    'instagram' => 'https://instagram.com/sman21jakarta',
    'youtube' => 'https://youtube.com/sman21jkt',
    'logo' => '../uploads/logo/sman21jakarta.png'
];

if ($conn) {
    try {
        $stmtSek = $conn->query("SELECT * FROM pengaturan_sekolah LIMIT 1");
        if ($stmtSek && $rowSek = $stmtSek->fetch()) {
            if (!empty($rowSek['nama_sekolah'])) $sekolah['nama_sekolah'] = $rowSek['nama_sekolah'];
            if (!empty($rowSek['telepon'])) $sekolah['telepon'] = $rowSek['telepon'];
            if (!empty($rowSek['email'])) $sekolah['email'] = $rowSek['email'];
            if (!empty($rowSek['instagram'])) $sekolah['instagram'] = $rowSek['instagram'];
            if (!empty($rowSek['youtube'])) $sekolah['youtube'] = $rowSek['youtube'];
        }
    } catch (PDOException $e) {}
}

// 1. Ambil Data Agenda / Kegiatan Sekolah
$kegiatanList = [];
if ($conn) {
    try {
        // Coba ambil dari tabel agenda
        $stmtAgd = $conn->query("SELECT *, 'Agenda Sekolah' AS kategori_nama FROM agenda WHERE status='published' ORDER BY tanggal_mulai DESC");
        if ($stmtAgd) {
            $kegiatanList = $stmtAgd->fetchAll();
        }
    } catch (PDOException $e) {
        $kegiatanList = [];
    }
}

// Fallback Data Kegiatan jika DB kosong
if (empty($kegiatanList)) {
    $kegiatanList = [
        [
            'id' => 1,
            'judul_agenda' => 'Peringatan Hari Guru Nasional & Pentas Seni Siswa',
            'slug' => 'peringatan-hari-guru-nasional',
            'deskripsi' => 'Rangkaian apresiasi tenaga pendidik yang diselingi dengan pertunjukan seni musik, tari tradisional, dan pameran karya kreasi siswa SMAN 21 Jakarta.',
            'tanggal_mulai' => '2026-11-25',
            'lokasi' => 'Lapangan Utama SMAN 21 Jakarta',
            'gambar' => 'uploads/tempat/gedungA.jpg',
            'kategori_nama' => 'Seni & Budaya'
        ],
        [
            'id' => 2,
            'judul_agenda' => 'Pramuka Camp & Pelatihan Kepemimpinan Pelajar',
            'slug' => 'pramuka-camp-kepemimpinan',
            'deskripsi' => 'Kegiatan rutin tahunan kedisiplinan dan pembentukan jiwa kepemimpinan Pancasila bagi seluruh peserta didik kelas X.',
            'tanggal_mulai' => '2026-10-18',
            'lokasi' => 'Bumi Perkemahan Cibubur',
            'gambar' => 'uploads/tempat/gedungA1.jpg',
            'kategori_nama' => 'Karakter'
        ],
        [
            'id' => 3,
            'judul_agenda' => 'Olimpiade Sains & Kebangsaan Internal SMAN 21',
            'slug' => 'olimpiade-sains-internal',
            'deskripsi' => 'Ajang kompetisi sains, matematika, dan wawasan kebangsaan dalam rangka menjaring perwakilan siswa terbaik untuk OSK/OSP.',
            'tanggal_mulai' => '2026-09-12',
            'lokasi' => 'Laboratorium Komputer & Saintek',
            'gambar' => 'uploads/tempat/gedungA.jpg',
            'kategori_nama' => 'Akademik'
        ]
    ];
}

// 2. Ambil Data Ekstrakurikuler
$ekskulList = [];
if ($conn) {
    try {
        $stmtEks = $conn->query("SELECT * FROM ekstrakurikuler WHERE status_aktif=1 ORDER BY urutan ASC, id DESC LIMIT 6");
        if ($stmtEks) {
            $ekskulList = $stmtEks->fetchAll();
        }
    } catch (PDOException $e) {
        $ekskulList = [];
    }
}

$logoPath = $sekolah['logo'];
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kegiatan & Agendaku — SMAN 21 Jakarta</title>
    <meta name="description" content="Daftar Kegiatan, Agenda Sekolah, dan Ekstrakurikuler SMAN 21 Jakarta. Wadah Pembentukan Karakter, Kreativitas, dan Prestasi Siswa.">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= $logoPath; ?>">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
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

        .reveal-section {
            opacity: 0;
            transform: translateY(28px) scale(0.99);
            transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
            will-change: opacity, transform;
        }

        .reveal-section.is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .delay-100 { transition-delay: 100ms; }
        .delay-200 { transition-delay: 200ms; }
        .delay-300 { transition-delay: 300ms; }

        .school-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04);
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .school-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 30px -4px rgba(15, 23, 42, 0.1);
            border-color: #CBD5E1;
        }

        ::-webkit-scrollbar { width: 7px; }
        ::-webkit-scrollbar-track { background: #061224; }
        ::-webkit-scrollbar-thumb { background: #00A8CC; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #0086A3; }
    </style>
</head>
<body class="relative min-h-screen text-slate-800 bg-slate-50 selection:bg-cyan-500 selection:text-white max-w-full overflow-x-hidden">

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
                    <img src="<?= $logoPath; ?>" alt="SMAN 21 Jakarta Logo" class="h-11 w-11 object-contain group-hover:scale-105 transition-transform">
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

                    <!-- Active Kegiatan Menu -->
                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-lg text-cyan-600 bg-cyan-50 font-bold border border-cyan-200 flex items-center gap-1.5 transition-all">
                            <span>Kegiatan</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full left-0 w-52 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="index.php" class="block px-3 py-2 rounded-lg bg-cyan-50 text-cyan-600 font-bold">Semua Kegiatan</a>
                                <a href="ekstrakurikuler.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Ekstrakurikuler</a>
                            </div>
                        </div>
                    </div>

                    <a href="../prestasi/index.php" class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 transition-all">Prestasi</a>
                    <a href="../fasilitas/index.php" class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 transition-all">Fasilitas</a>

                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 flex items-center gap-1.5 transition-all">
                            <span>Informasi</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full right-0 w-52 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="../informasi/berita.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Berita Sekolah</a>
                                <a href="../informasi/agenda.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Agenda & Kalender</a>
                                <a href="../informasi/ppdb.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">PPDB Info</a>
                                <a href="../gallery/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Galeri Foto</a>
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
                <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Kegiatan & Ekskul</span>
                <a href="index.php" class="block pl-3 py-1 text-xs text-cyan-600 font-bold">Semua Kegiatan</a>
                <a href="ekstrakurikuler.php" class="block pl-3 py-1 text-xs text-slate-700">Ekstrakurikuler</a>
            </div>
            <a href="../prestasi/index.php" class="block text-xs font-semibold text-slate-700">Prestasi</a>
            <a href="../fasilitas/index.php" class="block text-xs font-semibold text-slate-700">Fasilitas</a>
            <a href="../informasi/ppdb.php" class="block text-xs font-semibold text-slate-700">PPDB Info</a>
            <a href="../kontak/index.php" class="block text-xs font-semibold text-slate-700">Kontak</a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 🚀 PAGE HERO SECTION BANNER                                           -->
    <!-- ===================================================================== -->
    <section class="relative bg-brand-darkNavy text-white py-16 lg:py-24 overflow-hidden border-b border-slate-800">
        <div class="absolute inset-0 bg-cover bg-center opacity-20" style="background-image: url('../uploads/tempat/gedungA1.jpg');"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-brand-darkNavy via-brand-darkNavy/90 to-brand-navy/80"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl space-y-4 reveal-section is-visible">
                
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-500/20 border border-cyan-400/30 text-cyan-300 text-[11px] font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-calendar-check text-cyan-400"></i>
                    <span>KEGIATAN & AGENDA SISWA</span>
                </div>

                <h1 class="text-3xl sm:text-5xl font-extrabold text-white leading-tight">
                    Membangun Potensi Melalui <br>
                    <span class="text-cyan-400">Aktivitas Kolaboratif & Kreatif</span>
                </h1>

                <p class="text-xs sm:text-base text-slate-300 leading-relaxed max-w-2xl">
                    SMAN 21 Jakarta mendukung pembentukan Pelajar Pancasila berwawasan global melalui beragam agenda kesiswaan, acara seni, keagamaan, sains, serta organisasi ekstrakurikuler.
                </p>

                <!-- Breadcrumb -->
                <div class="pt-4 flex items-center gap-2 text-xs text-slate-400 font-semibold">
                    <a href="../index.php" class="hover:text-white transition-colors">Beranda</a>
                    <i class="fa-solid fa-chevron-right text-[9px] text-slate-600"></i>
                    <span class="text-cyan-400">Kegiatan Sekolah</span>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🔍 FILTER & SEARCH BAR SECTION                                         -->
    <!-- ===================================================================== -->
    <section class="py-8 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                
                <!-- Category Tabs -->
                <div class="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0 scrollbar-none">
                    <button onclick="filterKegiatan('all')" class="filter-btn px-4 py-2 rounded-lg bg-cyan-600 text-white text-xs font-bold whitespace-nowrap transition-all shadow-sm" data-category="all">
                        Semua Agenda
                    </button>
                    <button onclick="filterKegiatan('Akademik')" class="filter-btn px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold whitespace-nowrap transition-all" data-category="Akademik">
                        Akademik & Saintek
                    </button>
                    <button onclick="filterKegiatan('Seni & Budaya')" class="filter-btn px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold whitespace-nowrap transition-all" data-category="Seni & Budaya">
                        Seni & Budaya
                    </button>
                    <button onclick="filterKegiatan('Karakter')" class="filter-btn px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold whitespace-nowrap transition-all" data-category="Karakter">
                        Karakter & Keagamaan
                    </button>
                </div>

                <!-- Live Search Box -->
                <div class="relative min-w-[260px]">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="searchInput" onkeyup="searchKegiatan()" placeholder="Cari kegiatan..." class="w-full pl-9 pr-4 py-2 rounded-lg bg-slate-50 border border-slate-200 text-xs focus:outline-none focus:border-cyan-600 focus:bg-white transition-all">
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📅 KEGIATAN & AGENDA GRID SECTION                                     -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div id="kegiatanContainer" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($kegiatanList as $idx => $item): 
                    $judul = $item['judul_agenda'] ?? $item['judul'] ?? 'Kegiatan SMAN 21';
                    $deskripsi = $item['deskripsi'] ?? $item['ringkasan'] ?? 'Kegiatan seru dan bermakna siswa SMAN 21 Jakarta.';
                    $tgl = $item['tanggal_mulai'] ?? $item['tanggal'] ?? date('Y-m-d');
                    $lokasi = $item['lokasi'] ?? 'SMAN 21 Jakarta';
                    $kategori = $item['kategori_nama'] ?? 'Umum';
                    $rawImg = $item['gambar'] ?? $item['thumbnail'] ?? '';
                    $imgUrl = formatImgPath($rawImg);
                    $slug = $item['slug'] ?? ('detail.php?id=' . ($item['id'] ?? $idx));
                ?>
                <div class="school-card rounded-2xl overflow-hidden bg-white border border-slate-200 flex flex-col justify-between kegiatan-item reveal-section" data-title="<?= strtolower(htmlspecialchars($judul)); ?>" data-category="<?= htmlspecialchars($kategori); ?>">
                    <div>
                        <div class="relative h-48 overflow-hidden bg-slate-100">
                            <img src="<?= htmlspecialchars($imgUrl); ?>" alt="<?= htmlspecialchars($judul); ?>" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500" onerror="this.src='../uploads/logo/sman21jakarta.png'; this.className='w-1/2 h-1/2 object-contain mx-auto my-12 opacity-40';">
                            
                            <div class="absolute top-3 left-3 bg-brand-darkNavy/80 backdrop-blur-md text-cyan-400 font-bold text-[10px] uppercase px-2.5 py-1 rounded border border-cyan-500/30">
                                <?= htmlspecialchars($kategori); ?>
                            </div>
                        </div>

                        <div class="p-6 space-y-3">
                            <div class="flex items-center gap-2 text-[11px] text-slate-500 font-medium">
                                <i class="fa-regular fa-calendar-check text-cyan-600"></i>
                                <span><?= date('d M Y', strtotime($tgl)); ?></span>
                                <span>•</span>
                                <i class="fa-solid fa-location-dot text-red-500"></i>
                                <span class="line-clamp-1"><?= htmlspecialchars($lokasi); ?></span>
                            </div>

                            <h3 class="text-base font-bold text-brand-darkNavy leading-snug hover:text-cyan-600 transition-colors">
                                <?= htmlspecialchars($judul); ?>
                            </h3>

                            <p class="text-xs text-slate-600 leading-relaxed line-clamp-3">
                                <?= htmlspecialchars($deskripsi); ?>
                            </p>
                        </div>
                    </div>

                    <div class="p-6 pt-0">
                        <a href="detail.php?slug=<?= htmlspecialchars($item['slug'] ?? ''); ?>&id=<?= $item['id'] ?? $idx; ?>" class="w-full py-2.5 rounded-lg bg-slate-100 hover:bg-cyan-600 hover:text-white text-slate-700 font-bold text-xs flex items-center justify-center gap-2 transition-all group">
                            <span>Detail Kegiatan</span>
                            <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Empty Search Result Alert -->
            <div id="noResults" class="hidden text-center py-16 school-card rounded-2xl bg-white p-8">
                <i class="fa-solid fa-magnifying-glass text-4xl text-slate-300 mb-3"></i>
                <h3 class="text-base font-bold text-slate-700">Kegiatan tidak ditemukan</h3>
                <p class="text-xs text-slate-500 mt-1">Coba gunakan kata kunci pencarian atau kategori lain.</p>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🏆 BANNER HIGHLIGHT EKSTRAKURIKULER                                   -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-white border-t border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-12 items-center">
                
                <div class="lg:col-span-5 space-y-5 reveal-section">
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-600 bg-cyan-50 px-3 py-1 rounded border border-cyan-200">
                        WADAH TALENTA SISWA
                    </span>

                    <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy leading-tight">
                        Ekstrakurikuler Unggulan SMAN 21 Jakarta
                    </h2>

                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Pengembangan minat, bakat, kepemimpinan, serta mental juara siswa diwadahi dalam lebih dari 15 organisasi ekstrakurikuler di bidang Olahraga, Seni, Kebangsaan, Kebudayaan, dan Kerohanian.
                    </p>

                    <div class="pt-2">
                        <a href="ekstrakurikuler.php" class="px-7 py-3 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs inline-flex items-center gap-2.5 shadow-md transition-all">
                            <span>Eksplorasi Semua Ekstrakurikuler</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-7 reveal-section delay-200">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <?php if (!empty($ekskulList)): foreach ($ekskulList as $eItem): 
                            $eNama = $eItem['nama_ekskul'] ?? 'Ekskul SMAN 21';
                            $eJadwal = $eItem['jadwal'] ?? 'Jadwal Rutin';
                            $eLokasi = $eItem['lokasi'] ?? 'Gedung SMAN 21';
                        ?>
                        <div class="school-card p-5 rounded-xl bg-white border border-slate-200 space-y-2">
                            <div class="w-10 h-10 rounded-lg bg-cyan-500/10 text-cyan-600 flex items-center justify-center font-bold">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <h3 class="text-sm font-bold text-brand-darkNavy"><?= htmlspecialchars($eNama); ?></h3>
                            <p class="text-[11px] text-slate-500"><i class="fa-regular fa-clock mr-1 text-cyan-600"></i><?= htmlspecialchars($eJadwal); ?></p>
                            <span class="text-[10px] text-slate-400 block"><i class="fa-solid fa-location-dot mr-1"></i><?= htmlspecialchars($eLokasi); ?></span>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="col-span-2 school-card p-6 rounded-xl text-center text-xs text-slate-500 bg-white">
                            Daftar ekstrakurikuler lengkap tersedia pada menu Ekstrakurikuler.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🦶 FOOTER SECTION                                                      -->
    <!-- ===================================================================== -->
    <footer class="bg-brand-darkNavy text-slate-400 text-xs border-t border-slate-800 pt-16 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 pb-12 border-b border-slate-800">
                
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <img src="<?= $logoPath; ?>" alt="Logo SMAN 21" class="h-10 w-10 object-contain">
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
                        <span class="inline-block px-3 py-1 rounded bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 text-[10px] font-bold">
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
                <p>Ada yang bisa saya bantu terkait Kegiatan Sekolah, Agenda, Ekstrakurikuler, atau SPMB SMAN 21 Jakarta?</p>
            </div>
        </div>

        <div class="p-3 bg-white border-t border-slate-200">
            <form onsubmit="submitAiChat(event)" class="flex gap-2">
                <input type="text" id="aiInput" placeholder="Tanyakan seputar SMAN 21..." class="flex-1 px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50">
                <button type="submit" class="px-4 py-2.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs transition-colors">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Floating Chat Button -->
    <button onclick="toggleAiPanel()" class="fixed bottom-6 right-6 z-[9000] w-14 h-14 rounded-full bg-cyan-600 hover:bg-cyan-500 text-white shadow-2xl border-2 border-white flex items-center justify-center text-xl transition-all hover:scale-110 active:scale-95 group">
        <i class="fa-solid fa-comments"></i>
        <span class="absolute right-full mr-3 bg-brand-darkNavy text-white text-[10px] font-bold px-2.5 py-1 rounded shadow whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity">
            Tanyakan AI Assistant
        </span>
    </button>

    <!-- ===================================================================== -->
    <!-- 📜 JAVASCRIPT LOGIC                                                   -->
    <!-- ===================================================================== -->
    <script>
        // 1. MOBILE MENU TOGGLE
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }

        // 2. SCROLL REVEAL OBSERVER
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.reveal-section').forEach(el => observer.observe(el));

        // 3. CATEGORY FILTER
        function filterKegiatan(category) {
            const items = document.querySelectorAll('.kegiatan-item');
            const buttons = document.querySelectorAll('.filter-btn');
            let visibleCount = 0;

            buttons.forEach(btn => {
                if (btn.getAttribute('data-category') === category) {
                    btn.className = 'filter-btn px-4 py-2 rounded-lg bg-cyan-600 text-white text-xs font-bold whitespace-nowrap transition-all shadow-sm';
                } else {
                    btn.className = 'filter-btn px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold whitespace-nowrap transition-all';
                }
            });

            items.forEach(item => {
                const itemCat = item.getAttribute('data-category');
                if (category === 'all' || itemCat.toLowerCase().includes(category.toLowerCase())) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            document.getElementById('noResults').classList.toggle('hidden', visibleCount > 0);
        }

        // 4. LIVE SEARCH KEGIATAN
        function searchKegiatan() {
            const q = document.getElementById('searchInput').value.toLowerCase().trim();
            const items = document.querySelectorAll('.kegiatan-item');
            let visibleCount = 0;

            items.forEach(item => {
                const title = item.getAttribute('data-title');
                if (title.includes(q)) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            document.getElementById('noResults').classList.toggle('hidden', visibleCount > 0);
        }

        // 5. AI ASSISTANT PANEL TOGGLE & CHAT AJAX
        function toggleAiPanel() {
            const panel = document.getElementById('aiPanel');
            panel.classList.toggle('translate-x-full');
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
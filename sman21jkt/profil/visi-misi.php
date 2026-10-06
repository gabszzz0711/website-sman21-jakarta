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
// ENDPOINT AJAX UNTUK S21 AI ASSISTANT (CONTEXT-AWARE: VISI & MISI)
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
    $sumberInfo = "Database Visi & Misi SMAN 21 Jakarta";
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
                        $sumberInfo = $k['sumber_informasi'] ?: "Database AI SMAN 21";
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched && (strpos($qLower, $pertanyaanLower) !== false || strpos($pertanyaanLower, $qLower) !== false)) {
                $responseAnswer = $k['jawaban'];
                $sumberInfo = $k['sumber_informasi'] ?: "Database AI SMAN 21";
                $matched = true;
                break;
            }

            if ($matched) break;
        }

        if (!$matched) {
            if (strpos($qLower, 'visi') !== false) {
                $stmtVisi = $conn->query("SELECT isi_teks FROM pengaturan_visimisi WHERE jenis='visi' LIMIT 1");
                $rowVisi = $stmtVisi ? $stmtVisi->fetch() : null;
                $vText = $rowVisi['isi_teks'] ?? "Unggul dalam Prestasi dan Teknologi, Berkarakter Pancasila, Berbudaya Lingkungan, berdasarkan Iman dan Taqwa.";
                $responseAnswer = "Visi SMAN 21 Jakarta adalah: \"" . $vText . "\"";
                $sumberInfo = "Dokumen Visi Sekolah";
                $matched = true;
            } elseif (strpos($qLower, 'misi') !== false) {
                $stmtMisi = $conn->query("SELECT isi_teks FROM pengaturan_visimisi WHERE jenis='misi' ORDER BY urutan ASC");
                $misiItems = $stmtMisi ? $stmtMisi->fetchAll() : [];
                if (!empty($misiItems)) {
                    $mList = [];
                    foreach ($misiItems as $idx => $m) {
                        $mList[] = ($idx + 1) . ". " . $m['isi_teks'];
                    }
                    $responseAnswer = "Misi SMAN 21 Jakarta meliputi:\n" . implode("\n", $mList);
                } else {
                    $responseAnswer = "Misi SMAN 21 Jakarta berfokus pada penguatan karakter Profil Pelajar Pancasila, prestasi akademik, serta integrasi sains dan teknologi.";
                }
                $sumberInfo = "Dokumen Misi Sekolah";
                $matched = true;
            } elseif (strpos($qLower, 'pancasila') !== false || strpos($qLower, 'karakter') !== false) {
                $responseAnswer = "SMAN 21 Jakarta berkomitmen mewujudkan Profil Pelajar Pancasila (Beriman & Bertakwa, Berkebinekaan Global, Gotong Royong, Mandiri, Bernalar Kritis, dan Kreatif).";
                $sumberInfo = "Kurikulum Karakter SMAN 21";
                $matched = true;
            }
        }

        if (!$matched) {
            $responseAnswer = $fallbackMsg;
            $sumberInfo = "Sistem Verifikasi SMAN 21";
        }

    } else {
        if (strpos(strtolower($userQuery), 'visi') !== false) {
            $responseAnswer = "Visi SMAN 21 Jakarta: Unggul dalam Prestasi dan Teknologi, Berkarakter Pancasila, Berbudaya Lingkungan, berdasarkan Iman dan Taqwa.";
            $sumberInfo = "Dokumen Resmi Visi Misi";
        } else {
            $responseAnswer = "Maaf, informasi tersebut belum tersedia dalam basis data resmi SMAN 21 Jakarta. Silakan tanyakan seputar Visi, Misi, atau Program Utama Sekolah.";
            $sumberInfo = "Sistem SMAN 21";
        }
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
// FETCH DATA PENGATURAN SEKOLAH & VISI MISI DARI DATABASE
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
    'banner_bg' => '../uploads/banners/bannerprofil.jpg'
];

if ($conn) {
    $stmtSek = $conn->query("SELECT * FROM pengaturan_sekolah LIMIT 1");
    if ($stmtSek && $rowSek = $stmtSek->fetch()) {
        if (!empty($rowSek['nama_sekolah'])) $sekolah['nama_sekolah'] = $rowSek['nama_sekolah'];
        if (!empty($rowSek['slogan'])) $sekolah['slogan'] = $rowSek['slogan'];
        if (!empty($rowSek['akreditasi'])) $sekolah['akreditasi'] = $rowSek['akreditasi'];
        if (!empty($rowSek['alamat'])) $sekolah['alamat'] = $rowSek['alamat'];
        if (!empty($rowSek['telepon'])) $sekolah['telepon'] = $rowSek['telepon'];
        if (!empty($rowSek['email'])) $sekolah['email'] = $rowSek['email'];
        if (!empty($rowSek['instagram'])) $sekolah['instagram'] = $rowSek['instagram'];
    }
}

// Fetch Visi & Misi dari Tabel pengaturan_visimisi
$visiText = "Unggul dalam Prestasi dan Teknologi, Berkarakter Pancasila, Berbudaya Lingkungan, berdasarkan Iman dan Taqwa.";
$misiList = [];

if ($conn) {
    try {
        // Query Visi
        $stmtV = $conn->query("SELECT isi_teks FROM pengaturan_visimisi WHERE jenis='visi' LIMIT 1");
        if ($stmtV && $rowV = $stmtV->fetch()) {
            if (!empty($rowV['isi_teks'])) $visiText = $rowV['isi_teks'];
        }

        // Query Misi
        $stmtM = $conn->query("SELECT isi_teks FROM pengaturan_visimisi WHERE jenis='misi' ORDER BY urutan ASC");
        if ($stmtM) {
            $misiList = $stmtM->fetchAll();
        }
    } catch (PDOException $e) {
        $misiList = [];
    }
}

// Fallback jika Misi di DB Kosong
if (empty($misiList)) {
    $misiList = [
        ['isi_teks' => 'Mewujudkan Profil Pelajar Pancasila sebagai karakter kuat yang melekat pada seluruh peserta didik dan warga sekolah.'],
        ['isi_teks' => 'Menumbuhkan semangat berprestasi di bidang akademik, non akademik, dan teknologi.'],
        ['isi_teks' => 'Menciptakan dan menjaga lingkungan sekolah yang bersih, sehat, dan berbudaya lingkungan (Adiwiyata).'],
        ['isi_teks' => 'Meningkatkan kualitas sumber daya manusia pendidik dan tenaga kependidikan berstandar nasional.'],
        ['isi_teks' => 'Mengembangkan metode pembelajaran yang up to date, berbasis digital, dan berwawasan masa depan.'],
        ['isi_teks' => 'Menerapkan manajemen partisipatif berbasis sekolah yang transparan, akuntabel, dan berkesinambungan.']
    ];
}

$logoPath = $sekolah['logo'];
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Visi & Misi — SMA NEGERI 21 JAKARTA</title>
    <meta name="description" content="Visi dan Misi Resmi SMA Negeri 21 Jakarta dalam membentuk generasi unggul berprestasi, berkarakter Pancasila, dan berbasis teknologi.">
    
    <!-- Favicon Logo Sekolah -->
    <link rel="icon" type="image/png" href="<?= $logoPath; ?>">

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
                            amber: '#D97706',
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

        *, *:before, *:after { box-sizing: inherit; }

        /* Smooth Reveal Animation Observer */
        .reveal-section {
            opacity: 0;
            transform: translateY(32px) scale(0.99);
            transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
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

        .hero-overlay {
            background: linear-gradient(135deg, rgba(6, 18, 36, 0.90) 0%, rgba(11, 30, 54, 0.82) 60%, rgba(15, 23, 42, 0.88) 100%);
        }

        ::-webkit-scrollbar { width: 7px; }
        ::-webkit-scrollbar-track { background: #061224; }
        ::-webkit-scrollbar-thumb { background: #00A8CC; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #0086A3; }
    </style>
</head>
<body class="relative min-h-screen text-slate-800 bg-slate-50 selection:bg-cyan-500 selection:text-white max-w-full overflow-x-hidden">

    <!-- ===================================================================== -->
    <!-- 🧭 GLOBAL HEADER & NAVBAR (SAMA DENGAN INDEX.PHP)                      -->
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
                
                <!-- Brand Logo & Title -->
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

                <!-- Desktop Navigation Menu -->
                <nav class="hidden lg:flex items-center gap-1 font-semibold text-xs text-slate-700">
                    <a href="../index.php" class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 transition-all">Beranda</a>

                    <!-- Dropdown Profil (Active: Visi & Misi) -->
                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-lg text-cyan-600 bg-cyan-50 font-bold border border-cyan-200 flex items-center gap-1.5 transition-all">
                            <span>Profil</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full left-0 w-56 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Tentang Sekolah</a>
                                <a href="sejarah.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Sejarah Sekolah</a>
                                <a href="visi-misi.php" class="block px-3 py-2 rounded-lg text-cyan-600 font-bold bg-cyan-50/50 transition-colors">Visi & Misi</a>
                                <a href="sambutan.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Sambutan Kepala Sekolah</a>
                            </div>
                        </div>
                    </div>

                    <!-- Dropdown Akademik -->
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

                    <!-- Dropdown Kegiatan -->
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

                    <!-- Dropdown Informasi -->
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

                <!-- Mobile Menu Button -->
                <button onclick="toggleMobileMenu()" class="lg:hidden p-2 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200 transition-colors">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Navigation Menu Drawer -->
        <div id="mobileMenu" class="hidden lg:hidden bg-white border-t border-slate-200 px-6 py-6 space-y-4 max-h-[80vh] overflow-y-auto">
            <a href="../index.php" class="block text-slate-700 font-semibold text-xs">Beranda</a>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-cyan-600 font-bold tracking-wider">Profil</span>
                <a href="index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Profil</a>
                <a href="sejarah.php" class="block pl-3 py-1 text-xs text-slate-700">Sejarah</a>
                <a href="visi-misi.php" class="block pl-3 py-1 text-xs text-cyan-600 font-bold">Visi & Misi</a>
                <a href="sambutan.php" class="block pl-3 py-1 text-xs text-slate-700">Sambutan Kepala Sekolah</a>
            </div>
            <a href="../akademik/index.php" class="block text-xs font-semibold text-slate-700">Akademik</a>
            <a href="../prestasi/index.php" class="block text-xs font-semibold text-slate-700">Prestasi</a>
            <a href="../kegiatan/index.php" class="block text-xs font-semibold text-slate-700">Kegiatan & Ekskul</a>
            <a href="../informasi/ppdb.php" class="block text-xs font-semibold text-slate-700">PPDB Info</a>
            <a href="../kontak/index.php" class="block text-xs font-semibold text-slate-700">Kontak</a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 🏛️ HERO BANNER VISI & MISI (BACKGROUND uploads/banners/bannerprofil.jpg) -->
    <!-- ===================================================================== -->
    <section class="relative bg-brand-darkNavy text-white py-20 lg:py-28 overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-1000 scale-105" 
             style="background-image: url('<?= htmlspecialchars($sekolah['banner_bg']); ?>');">
        </div>
        <div class="absolute inset-0 hero-overlay"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl space-y-4 reveal-section">
                <!-- Breadcrumb Navigation -->
                <div class="flex items-center gap-2 text-xs text-amber-400 font-semibold tracking-wide uppercase">
                    <a href="../index.php" class="hover:underline">BERANDA</a>
                    <i class="fa-solid fa-chevron-right text-[9px]"></i>
                    <a href="index.php" class="hover:underline">PROFIL SEKOLAH</a>
                    <i class="fa-solid fa-chevron-right text-[9px]"></i>
                    <span class="text-white">VISI & MISI</span>
                </div>

                <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight">
                    Visi & Misi <span class="text-amber-400">SMAN 21 Jakarta</span>
                </h1>
                
                <p class="text-slate-200 text-sm sm:text-base leading-relaxed">
                    Landasan filosofis, cita-cita unggul, dan langkah strategis SMA Negeri 21 Jakarta dalam membentuk insan berprestasi, berkarakter Pancasila, dan berwawasan teknologi modern.
                </p>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🎯 SECTION VISI SEKOLAH                                              -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto reveal-section">
                
                <div class="school-card p-8 sm:p-12 rounded-3xl bg-gradient-to-br from-brand-darkNavy via-slate-900 to-brand-navy text-white relative overflow-hidden shadow-2xl border border-slate-800">
                    <div class="absolute -top-12 -right-12 w-48 h-48 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -bottom-12 -left-12 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="relative z-10 text-center space-y-6">
                        <div class="w-16 h-16 rounded-2xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center mx-auto text-3xl border border-cyan-500/30 shadow-inner">
                            <i class="fa-solid fa-compass"></i>
                        </div>

                        <span class="inline-block px-4 py-1.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-bold uppercase tracking-widest">
                            VISI SMAN 21 JAKARTA
                        </span>

                        <blockquote class="text-xl sm:text-3xl font-extrabold text-white leading-relaxed italic tracking-tight font-sans">
                            "<?= htmlspecialchars($visiText); ?>"
                        </blockquote>

                        <p class="text-xs sm:text-sm text-slate-300 max-w-2xl mx-auto leading-relaxed pt-2">
                            Visi ini menjadi pilar utama seluruh program intrakulikuler, kokurikuler, dan ekstrakurikuler dalam membentuk peserta didik berdaya saing global.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📋 SECTION MISI SEKOLAH                                              -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-2xl mx-auto mb-16 space-y-3 reveal-section">
                <span class="text-xs font-bold uppercase tracking-widest text-cyan-600 bg-cyan-50 px-3.5 py-1.5 rounded-md border border-cyan-200">
                    LANGKAH STRATEGIS
                </span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy">Misi SMAN 21 Jakarta</h2>
                <p class="text-xs sm:text-sm text-slate-500">Komitmen nyata yang dijalankan secara terencana dan berkesinambungan untuk mencapai visi sekolah.</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                <?php 
                    $misiBadges = ['bg-cyan-600', 'bg-blue-600', 'bg-emerald-600', 'bg-purple-600', 'bg-amber-600', 'bg-rose-600'];
                    $misiIcons = ['fa-user-shield', 'fa-trophy', 'fa-leaf', 'fa-award', 'fa-laptop-code', 'fa-diagram-project'];
                    
                    $mCount = 1;
                    foreach ($misiList as $mItem): 
                        $badgeColor = $misiBadges[($mCount - 1) % count($misiBadges)];
                        $iconClass  = $misiIcons[($mCount - 1) % count($misiIcons)];
                ?>
                <div class="school-card p-8 rounded-2xl bg-white border border-slate-200 flex flex-col justify-between space-y-4 reveal-section delay-<?= ($mCount % 3 + 1) * 100; ?>">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="w-10 h-10 rounded-xl <?= $badgeColor; ?> text-white font-extrabold text-sm flex items-center justify-center shadow-md">
                                0<?= $mCount; ?>
                            </span>
                            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-base">
                                <i class="fa-solid <?= $iconClass; ?>"></i>
                            </div>
                        </div>

                        <p class="text-xs sm:text-sm text-slate-700 font-semibold leading-relaxed">
                            <?= htmlspecialchars($mItem['isi_teks']); ?>
                        </p>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center gap-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <i class="fa-solid fa-circle-check text-cyan-600"></i>
                        <span>Komitmen Pendidikan</span>
                    </div>
                </div>
                <?php $mCount++; endforeach; ?>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🌟 SECTION IMPLEMENTASI PROFIL PELAJAR PANCASILA                      -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16 space-y-3 reveal-section">
                <span class="text-xs font-bold uppercase tracking-widest text-amber-600 bg-amber-50 px-3.5 py-1.5 rounded-md border border-amber-200">
                    KARAKTER UNGGUL
                </span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy">Pilar Profil Pelajar Pancasila</h2>
                <p class="text-xs sm:text-sm text-slate-500">Nilai-nilai utama yang ditanamkan dalam setiap ikhtiar pembelajaran peserta didik SMAN 21 Jakarta.</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                
                <div class="school-card p-6 rounded-2xl text-center space-y-3 bg-white border border-slate-200 reveal-section delay-100">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mx-auto text-xl">
                        <i class="fa-solid fa-hands-praying"></i>
                    </div>
                    <h3 class="text-xs font-bold text-brand-darkNavy">Beriman & Bertakwa</h3>
                    <p class="text-[10px] text-slate-500 leading-snug">Berakhlak mulia kepada Tuhan, sesama, dan alam.</p>
                </div>

                <div class="school-card p-6 rounded-2xl text-center space-y-3 bg-white border border-slate-200 reveal-section delay-200">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center mx-auto text-xl">
                        <i class="fa-solid fa-earth-americas"></i>
                    </div>
                    <h3 class="text-xs font-bold text-brand-darkNavy">Berkebinekaan Global</h3>
                    <p class="text-[10px] text-slate-500 leading-snug">Menghargai budaya dan berwawasan internasional.</p>
                </div>

                <div class="school-card p-6 rounded-2xl text-center space-y-3 bg-white border border-slate-200 reveal-section delay-300">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto text-xl">
                        <i class="fa-solid fa-people-carry-box"></i>
                    </div>
                    <h3 class="text-xs font-bold text-brand-darkNavy">Gotong Royong</h3>
                    <p class="text-[10px] text-slate-500 leading-snug">Kolaborasi aktif dan peduli terhadap sesama.</p>
                </div>

                <div class="school-card p-6 rounded-2xl text-center space-y-3 bg-white border border-slate-200 reveal-section delay-400">
                    <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center mx-auto text-xl">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <h3 class="text-xs font-bold text-brand-darkNavy">Mandiri</h3>
                    <p class="text-[10px] text-slate-500 leading-snug">Bertanggung jawab atas proses dan hasil belajar.</p>
                </div>

                <div class="school-card p-6 rounded-2xl text-center space-y-3 bg-white border border-slate-200 reveal-section delay-100">
                    <div class="w-12 h-12 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center mx-auto text-xl">
                        <i class="fa-solid fa-brain"></i>
                    </div>
                    <h3 class="text-xs font-bold text-brand-darkNavy">Bernalar Kritis</h3>
                    <p class="text-[10px] text-slate-500 leading-snug">Mampu memproses informasi dan memecahkan masalah.</p>
                </div>

                <div class="school-card p-6 rounded-2xl text-center space-y-3 bg-white border border-slate-200 reveal-section delay-200">
                    <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center mx-auto text-xl">
                        <i class="fa-solid fa-lightbulb"></i>
                    </div>
                    <h3 class="text-xs font-bold text-brand-darkNavy">Kreatif</h3>
                    <p class="text-[10px] text-slate-500 leading-snug">Menghasilkan karya orisinal bernilai guna tinggi.</p>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🤖 BANNER AI ASSISTANT PROFIL                                         -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-slate-900 text-white relative overflow-hidden">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 reveal-section">
            <div class="p-8 sm:p-12 rounded-2xl bg-gradient-to-r from-brand-darkNavy to-slate-800 border border-slate-700 text-center space-y-5 shadow-2xl">
                <div class="w-14 h-14 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center mx-auto text-2xl border border-cyan-500/30">
                    <i class="fa-solid fa-circle-question"></i>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white">
                    Punya Pertanyaan seputar Visi & Misi Sekolah?
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                    Sistem Pusat Informasi SMAN 21 siap membantu memberikan penjelasan terperinci mengenai implementasi Visi Misi dan Kurikulum Merdeka secara instant.
                </p>
                <div class="pt-2">
                    <button onclick="toggleAiPanel()" class="px-7 py-3 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-lg transition-all inline-flex items-center gap-2.5 uppercase tracking-wider">
                        <i class="fa-solid fa-comments text-sm"></i>
                        <span>TANYAKAN LAYANAN INFORMASI</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🦶 FOOTER RESMI SEKOLAH                                              -->
    <!-- ===================================================================== -->
    <footer class="bg-brand-darkNavy text-slate-400 text-xs py-12 border-t border-slate-800 max-w-full overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <img src="<?= $logoPath; ?>" alt="Logo SMAN 21" class="h-10 w-10 object-contain">
                        <span class="font-extrabold text-white text-base">SMAN 21 JAKARTA</span>
                    </div>
                    <p class="text-[11px] leading-relaxed text-slate-400">
                        Sekolah Menengah Atas Berprestasi, Berkarakter Pancasila, Berbudaya Lingkungan, dan Berbasis Teknologi Modern.
                    </p>
                </div>

                <div>
                    <h4 class="text-white font-bold mb-3">Navigasi Profil</h4>
                    <ul class="space-y-2 text-[11px]">
                        <li><a href="index.php" class="hover:text-amber-400 transition-colors">Tentang Sekolah</a></li>
                        <li><a href="sejarah.php" class="hover:text-amber-400 transition-colors">Sejarah Sekolah</a></li>
                        <li><a href="visi-misi.php" class="text-amber-400 font-bold">Visi & Misi</a></li>
                        <li><a href="sambutan.php" class="hover:text-amber-400 transition-colors">Sambutan Kepala Sekolah</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-bold mb-3">Layanan Informasi</h4>
                    <ul class="space-y-2 text-[11px]">
                        <li><a href="../akademik/index.php" class="hover:text-amber-400 transition-colors">Akademik & Guru</a></li>
                        <li><a href="../prestasi/index.php" class="hover:text-amber-400 transition-colors">Prestasi Siswa</a></li>
                        <li><a href="../informasi/ppdb.php" class="hover:text-amber-400 transition-colors">PPDB Info</a></li>
                        <li><a href="../kontak/index.php" class="hover:text-amber-400 transition-colors">Kontak Resmi</a></li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <h4 class="text-white font-bold">Media Sosial</h4>
                    <div class="flex gap-2">
                        <a href="<?= htmlspecialchars($sekolah['instagram']); ?>" target="_blank" class="w-8 h-8 rounded bg-slate-800 border border-slate-700 flex items-center justify-center hover:text-amber-400 transition-colors"><i class="fa-brands fa-instagram"></i></a>
                        <a href="<?= htmlspecialchars($sekolah['youtube']); ?>" target="_blank" class="w-8 h-8 rounded bg-slate-800 border border-slate-700 flex items-center justify-center hover:text-amber-400 transition-colors"><i class="fa-brands fa-youtube"></i></a>
                    </div>
                </div>

            </div>

            <div class="pt-8 border-t border-slate-800 flex flex-col sm:flex-row justify-between items-center text-[10px] text-slate-500">
                <p>&copy; <?= date('Y'); ?> SMA Negeri 21 Jakarta. Hak Cipta Dilindungi Undang-Undang.</p>
                <p class="mt-2 sm:mt-0">Portal Resmi Informasi SMA Negeri 21 Jakarta</p>
            </div>
        </div>
    </footer>

    <!-- ===================================================================== -->
    <!-- 💬 AI ASSISTANT DRAWER PANEL (DISESUAIKAN PERSIS DENGAN INDEX.PHP)     -->
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
                <p>Ada yang bisa saya bantu terkait **Visi, Misi, dan Kurikulum Karakter** SMAN 21 Jakarta?</p>
            </div>
        </div>

        <div class="p-3 bg-white border-t border-slate-200">
            <form onsubmit="submitAiChat(event)" class="flex gap-2">
                <input type="text" id="aiInput" placeholder="Tanyakan seputar Visi Misi SMAN 21..." class="flex-1 px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50">
                <button type="submit" class="px-4 py-2.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs transition-colors">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Floating Chat Button (Disamakan dengan index.php) -->
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
        // 1. SCROLL REVEAL OBSERVER
        document.addEventListener('DOMContentLoaded', () => {
            const observerOptions = {
                root: null,
                rootMargin: '0px 0px -50px 0px',
                threshold: 0.12
            };

            const revealObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.reveal-section').forEach(el => {
                revealObserver.observe(el);
            });
        });

        // 2. MOBILE MENU TOGGLE
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            if (menu) menu.classList.toggle('hidden');
        }

        // 3. AI ASSISTANT PANEL TOGGLE & CHAT AJAX (KONSISTEN DENGAN INDEX.PHP)
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
            
            // Render User Bubble
            const userMsg = document.createElement('div');
            userMsg.className = 'p-3 rounded-xl bg-cyan-600 text-white ml-auto max-w-[85%] text-xs font-medium shadow-sm';
            userMsg.innerText = q;
            chatBox.appendChild(userMsg);

            input.value = '';
            chatBox.scrollTop = chatBox.scrollHeight;

            // Render Loading Indicator
            const loadingMsg = document.createElement('div');
            loadingMsg.className = 'p-3 rounded-xl bg-white border border-slate-200 text-slate-500 max-w-[85%] text-xs italic shadow-sm';
            loadingMsg.innerText = 'Mencari jawaban resmi...';
            chatBox.appendChild(loadingMsg);
            chatBox.scrollTop = chatBox.scrollHeight;

            fetch('visi-misi.php?action=ai_query', {
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
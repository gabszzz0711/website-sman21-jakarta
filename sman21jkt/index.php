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
// HELPER PERBAIKAN PATH GAMBAR BANNER
// =============================================================================
function formatBannerImg($img, $default = 'uploads/tempat/gedungA1.jpg') {
    if (empty($img)) return $default;
    $img = trim($img);
    
    if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
        return $img;
    }
    
    if (strpos($img, 'uploads/banners/') === 0) {
        return $img;
    }
    
    if (strpos($img, 'uploads/') === 0) {
        return $img;
    }
    
    return 'uploads/banners/' . ltrim($img, '/');
}

// =============================================================================
// ENDPOINT AJAX UNTUK S21 AI ASSISTANT (FALLBACK ENDPOINT LOKAL)
// =============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'ai_query') {
    header('Content-Type: application/json; charset=utf-8');
    $userQuery = trim($_POST['query'] ?? $_POST['message'] ?? $_GET['q'] ?? '');
    
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
            if (strpos($qLower, 'ppdb') !== false || strpos($qLower, 'daftar') !== false) {
                $stmtPpdb = $conn->query("SELECT * FROM ppdb WHERE status='buka' LIMIT 1");
                if ($ppdbData = $stmtPpdb->fetch()) {
                    $responseAnswer = "Penerimaan Peserta Didik Baru (PPDB) T.A. " . htmlspecialchars($ppdbData['tahun_ajaran']) . " saat ini statusnya BUKA. " . htmlspecialchars($ppdbData['deskripsi']);
                    $sumberInfo = "Informasi PPDB Resmi";
                    $relatedLink = "informasi/ppdb.php";
                    $matched = true;
                }
            } elseif (strpos($qLower, 'alamat') !== false || strpos($qLower, 'kontak') !== false || strpos($qLower, 'lokasi') !== false) {
                $stmtKtk = $conn->query("SELECT * FROM kontak_sekolah LIMIT 1");
                if ($ktkData = $stmtKtk->fetch()) {
                    $responseAnswer = "SMAN 21 Jakarta berlokasi di " . ($ktkData['alamat'] ?: "Jl. Tanah Mas Raya No.1, Kayu Putih, Pulogadung, Jakarta Timur") . ". Telepon: " . ($ktkData['telepon'] ?: "(021) 4892346") . ". Jam operasional: " . ($ktkData['jam_operasional'] ?: "Senin - Jumat 06.30 - 15.00 WIB");
                    $sumberInfo = "Kontak Sekolah";
                    $relatedLink = "kontak/index.php";
                    $matched = true;
                }
            }
        }

        if (!$matched) {
            $responseAnswer = $fallbackMsg;
            $sumberInfo = "Sistem Verifikasi SMAN 21";
        }

    } else {
        if (strpos(strtolower($userQuery), 'visi') !== false) {
            $responseAnswer = "Visi SMAN 21 Jakarta: Unggul dalam Prestasi dan Teknologi, Berkarakter Pancasila, Berbudaya Lingkungan, berdasarkan Iman dan Taqwa.";
            $sumberInfo = "Profil Visi Misi";
            $relatedLink = "profil/visi-misi.php";
        } else {
            $responseAnswer = "Maaf, informasi tersebut belum tersedia dalam basis data resmi SMAN 21 Jakarta. Silakan ajukan pertanyaan seputar Profil, Akademik, PPDB, atau Prestasi.";
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
// FETCH DATA RESMI SEKOLAH DARI DATABASE
// =============================================================================

// 1. Profil & Pengaturan Sekolah
$sekolah = [
    'nama_sekolah' => 'SMAN 21 Jakarta',
    'slogan' => 'Where Future Begins • Unggul, Berkarakter, Berteknologi',
    'akreditasi' => 'A',
    'alamat' => 'Jl. Tanah Mas Raya No.1, RT.3/RW.1, Kayu Putih, Kec. Pulo Gadung, Kota Jakarta Timur',
    'telepon' => '(021) 4892346',
    'email' => 'info@sman21jkt.sch.id',
    'instagram' => 'https://instagram.com/sman21jakarta',
    'youtube' => 'https://youtube.com/sman21jkt',
    'logo' => 'uploads/logo/sman21jakarta.png',
    'hero_bg' => 'uploads/tempat/gedungA1.jpg',
    'sambutan_bg' => 'uploads/tempat/gedungA.jpg'
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

// 2. HERO BANNERS
$heroBanners = [];
if ($conn) {
    try {
        $stmtHero = $conn->query("SELECT * FROM hero_banners WHERE is_active = 1 ORDER BY sort_order ASC, id DESC");
        if ($stmtHero) {
            $heroBanners = $stmtHero->fetchAll();
        }
    } catch (PDOException $e) {
        $heroBanners = [];
    }
}

if (empty($heroBanners)) {
    $heroBanners = [
        [
            'id' => 0,
            'title' => 'Membentuk Generasi <br><span class="text-cyan-400 drop-shadow">Unggul & Berteknologi</span>',
            'description' => $sekolah['slogan'] . '. Pusat keunggulan pendidikan menengah atas yang memadukan profil Pelajar Pancasila, prestasi akademik nasional, serta penguasaan sains digital.',
            'background_image' => $sekolah['hero_bg'],
            'button_text' => 'PELAJARI LEBIH JAUH',
            'button_link' => 'informasi/ppdb.php',
            'text_align' => 'left',
            'font_family' => 'Plus Jakarta Sans',
            'is_bold' => 1,
            'is_italic' => 0,
            'animation_type' => 'fade'
        ]
    ];
}

// 3. Sambutan Kepala Sekolah
$sambutan = [
    'nama' => 'Mutia, M.Pd',
    'jabatan' => 'Kepala SMAN 21 Jakarta',
    'judul' => 'Sambutan Kepala SMAN 21 Jakarta',
    'isi' => '<p><strong>Selamat datang di website resmi SMAN 21 Jakarta.</strong></p><p>Kami berkomitmen memberikan pendidikan berstandar tinggi yang mengintegrasikan akademik unggul, karakter Pancasila, serta kepemimpinan berbasis teknologi modern.</p>',
    'foto' => 'uploads/sambutan/kepsek.jpg'
];

if ($conn) {
    $stmtSam = $conn->query("SELECT * FROM sambutan_kepala_sekolah WHERE status_aktif=1 LIMIT 1");
    if ($stmtSam && $rowSam = $stmtSam->fetch()) {
        $sambutan['nama'] = $rowSam['nama_kepala_sekolah'];
        $sambutan['jabatan'] = $rowSam['jabatan'];
        $sambutan['judul'] = $rowSam['judul'];
        $sambutan['isi'] = $rowSam['isi_sambutan'];
        if (!empty($rowSam['foto'])) {
            $fotoVal = trim($rowSam['foto']);
            if (strpos($fotoVal, 'uploads/') === false && strpos($fotoVal, 'http') === false) {
                $sambutan['foto'] = 'uploads/sambutan/' . ltrim($fotoVal, '/');
            } else {
                $sambutan['foto'] = $fotoVal;
            }
        }
    }
}

// 4. Program Pendidikan
$programs = [];
if ($conn) {
    $stmtProg = $conn->query("SELECT * FROM program_pendidikan WHERE status_aktif=1 ORDER BY urutan ASC LIMIT 6");
    if ($stmtProg) $programs = $stmtProg->fetchAll();
}
if (empty($programs)) {
    $programs = [
        ['nama_program' => 'Kurikulum Merdeka Unggulan', 'deskripsi' => 'Pengembangan minat bakat berbasis kolaborasi interdisipliner, literasi digital, dan koding.', 'ikon' => 'fa-graduation-cap'],
        ['nama_program' => 'Sains & Teknologi Terapan', 'deskripsi' => 'Penguatan materi Fisika, Kimia, Biologi, Koding & Kecerdasan Artifisial (KA).', 'ikon' => 'fa-laptop-code'],
        ['nama_program' => 'Sosial, Humaniora & Kepemimpinan', 'deskripsi' => 'Membentuk jiwa kepemimpinan kritis, Sosiologi, Ekonomi, dan kewirausahaan digital.', 'ikon' => 'fa-users-cog']
    ];
}

// 5. Data Keterserapan PTN
$ptnData = [];
if ($conn) {
    $stmtPtn = $conn->query("SELECT * FROM keterserapan_ptn ORDER BY tahun ASC LIMIT 5");
    if ($stmtPtn) $ptnData = $stmtPtn->fetchAll();
}
if (empty($ptnData)) {
    $ptnData = [
        ['tahun' => '2023', 'jumlah_lulusan' => 280, 'snbp' => 65, 'snbt' => 140, 'mandiri' => 50, 'kedinasan' => 15],
        ['tahun' => '2024', 'jumlah_lulusan' => 285, 'snbp' => 72, 'snbt' => 148, 'mandiri' => 48, 'kedinasan' => 12],
        ['tahun' => '2025', 'jumlah_lulusan' => 290, 'snbp' => 80, 'snbt' => 152, 'mandiri' => 45, 'kedinasan' => 13]
    ];
}

// 6. Prestasi Terbaru
$prestasiList = [];
if ($conn) {
    $stmtPres = $conn->query("SELECT * FROM prestasi WHERE status_aktif=1 ORDER BY id DESC LIMIT 3");
    if ($stmtPres) $prestasiList = $stmtPres->fetchAll();
}

// 7. Berita & Agenda Terbaru
$beritaList = [];
if ($conn) {
    $stmtBrt = $conn->query("SELECT * FROM berita WHERE status='published' ORDER BY id DESC LIMIT 3");
    if ($stmtBrt) $beritaList = $stmtBrt->fetchAll();
}

$agendaList = [];
if ($conn) {
    $stmtAgd = $conn->query("SELECT * FROM agenda WHERE status='published' ORDER BY tanggal_mulai ASC LIMIT 3");
    if ($stmtAgd) $agendaList = $stmtAgd->fetchAll();
}

$logoPath = $sekolah['logo'];
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SMA NEGERI 21 JAKARTA — Official School Portal</title>
    <meta name="description" content="Official Website SMA Negeri 21 Jakarta. Sekolah Menengah Atas Berprestasi, Berkarakter Pancasila, dan Berbasis Teknologi Modern di Jakarta.">
    
    <!-- Favicon -->
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
                            lightCyan: '#E0F2FE',
                            red: '#DC2626',
                            redHover: '#B91C1C',
                            slate: '#F8FAFC'
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        serif: ['Plus Jakarta Sans', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome & Chart.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

        /* 🎬 SMOOTH CINEMATIC INTRO FADE-IN & FADE-OUT */
        #cinematicIntro {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            will-change: opacity, visibility;
        }

        #cinematicIntro.intro-visible {
            opacity: 1;
            visibility: visible;
        }

        #cinematicIntro.intro-fade-out {
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }

        .cinematic-act {
            opacity: 0;
            transform: scale(0.95) translateY(16px);
            transition: opacity 1.2s cubic-bezier(0.16, 1, 0.3, 1), transform 1.2s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .cinematic-act.active {
            opacity: 1;
            transform: scale(1) translateY(0);
            pointer-events: auto;
        }

        /* Progress Bar Otomatis Intro */
        @keyframes introProgress {
            0% { width: 0%; }
            100% { width: 100%; }
        }
        .intro-progress-bar {
            animation: introProgress 9.5s linear forwards;
        }

        /* 🔮 ANIMASI REVEAL PER SECTION SAAT SCROLL */
        .reveal-section {
            opacity: 0;
            transform: translateY(36px) scale(0.985);
            transition: opacity 0.85s cubic-bezier(0.16, 1, 0.3, 1), transform 0.85s cubic-bezier(0.16, 1, 0.3, 1);
            will-change: opacity, transform;
        }

        .reveal-section.is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        /* Staggered Delays */
        .delay-100 { transition-delay: 100ms; }
        .delay-200 { transition-delay: 200ms; }
        .delay-300 { transition-delay: 300ms; }
        .delay-400 { transition-delay: 400ms; }
        .delay-500 { transition-delay: 500ms; }

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
            transform: translateY(-7px);
            box-shadow: 0 20px 35px -8px rgba(15, 23, 42, 0.12), 0 0 20px rgba(0, 168, 204, 0.08);
            border-color: #CBD5E1;
        }

        .school-card:hover::before {
            opacity: 1;
        }

        .hero-overlay {
            background: linear-gradient(135deg, rgba(6, 18, 36, 0.92) 0%, rgba(11, 30, 54, 0.84) 60%, rgba(15, 23, 42, 0.88) 100%);
        }

        @keyframes heroKenBurns {
            0% { transform: scale(1) translate(0%, 0%); }
            50% { transform: scale(1.1) translate(-1%, -1%); }
            100% { transform: scale(1) translate(0%, 0%); }
        }

        .hero-bg-anim {
            animation: heroKenBurns 20s ease-in-out infinite alternate;
            will-change: transform;
        }

        .sambutan-overlay {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 249, 255, 0.93) 100%);
        }

        /* 📝 FORMAT ISI SAMBUTAN TINYMCE */
        .sambutan-content {
            color: #334155;
            font-size: 0.95rem;
            line-height: 1.8;
        }
        .sambutan-content p {
            margin-bottom: 0.9rem;
        }
        .sambutan-content p:last-child {
            margin-bottom: 0;
        }
        .sambutan-content strong, .sambutan-content b {
            font-weight: 700;
            color: #061224;
        }
        .sambutan-content em, .sambutan-content i {
            font-style: italic;
        }
        .sambutan-content ul {
            list-style-type: disc;
            padding-left: 1.25rem;
            margin-bottom: 0.85rem;
        }
        .sambutan-content ol {
            list-style-type: decimal;
            padding-left: 1.25rem;
            margin-bottom: 0.85rem;
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar { width: 7px; }
        ::-webkit-scrollbar-track { background: #061224; }
        ::-webkit-scrollbar-thumb { background: #00A8CC; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #0086A3; }

        /* Glowing Pulse FX */
        @keyframes glowPulse {
            0%, 100% { box-shadow: 0 0 15px rgba(0, 168, 204, 0.4); }
            50% { box-shadow: 0 0 30px rgba(0, 168, 204, 0.8); }
        }
        .pulse-glow {
            animation: glowPulse 2.5s infinite ease-in-out;
        }
    </style>
</head>
<body class="relative min-h-screen text-slate-800 bg-slate-50 selection:bg-cyan-500 selection:text-white max-w-full overflow-x-hidden">

    <!-- ===================================================================== -->
    <!-- 🎬 CINEMATIC OPENING OVERLAY                                           -->
    <!-- ===================================================================== -->
    <div id="cinematicIntro" class="fixed inset-0 z-[9999] bg-brand-darkNavy text-white overflow-hidden flex flex-col justify-between">
        
        <!-- Ambient Background Overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-brand-darkNavy via-brand-navy/85 to-brand-darkNavy z-10"></div>
        <div id="cinematicBg" class="absolute inset-0 bg-cover bg-center transition-all duration-1000 opacity-25 scale-105" style="background-image: url('<?= htmlspecialchars($sekolah['hero_bg']); ?>');"></div>

        <!-- ⏩ TOMBOL LEWATI INTRO -->
        <button onclick="skipIntro()" type="button" class="absolute top-5 right-5 z-30 px-4 py-2 rounded-full bg-white/10 hover:bg-white/20 text-white/90 hover:text-white border border-white/20 text-xs font-semibold backdrop-blur-md transition-all flex items-center gap-2 group cursor-pointer shadow-lg active:scale-95">
            <span>Lewati Intro</span>
            <i class="fa-solid fa-forward text-[10px] group-hover:translate-x-0.5 transition-transform"></i>
        </button>

        <div class="relative z-20 w-full flex-1 flex items-center justify-center">
            
            <!-- ACT 1: LOGO & IDENTITY -->
            <div id="act1" class="cinematic-act active z-20 px-4 text-center">
                <div class="relative mb-6">
                    <div class="absolute -inset-4 rounded-full bg-cyan-500/20 blur-xl"></div>
                    <img src="<?= $logoPath; ?>" alt="Logo SMAN 21 Jakarta" class="relative w-28 h-28 md:w-36 md:h-36 object-contain mx-auto drop-shadow-2xl">
                </div>
                <h1 class="text-2xl md:text-4xl font-extrabold text-cyan-400 tracking-wider mb-2 drop-shadow-md">
                    SMA NEGERI 21 JAKARTA
                </h1>
                <p class="text-xs md:text-sm font-medium tracking-[0.3em] text-slate-300 uppercase">
                    Where Future Begins
                </p>
            </div>

            <!-- ACT 2: SLOGAN & PHILOSOPHY -->
            <div id="act2" class="cinematic-act z-20 px-6 text-center">
                <div class="max-w-2xl">
                    <span class="inline-block px-3 py-1 rounded-full bg-cyan-500/20 border border-cyan-400/30 text-cyan-300 text-[10px] md:text-xs font-bold tracking-widest uppercase mb-4">
                        Pendidikan Berstandar Nasional
                    </span>
                    <span id="act2Word" class="text-3xl md:text-5xl font-extrabold text-white uppercase block mb-3 leading-tight tracking-tight drop-shadow-lg">
                        UNGGUL & BERKARAKTER
                    </span>
                    <p id="act2Sub" class="text-xs md:text-base text-cyan-200/90 font-normal tracking-wide max-w-lg mx-auto">
                        Mewujudkan Generasi Berkarakter Pancasila & Menguasai Sains Teknologi Digital
                    </p>
                </div>
            </div>

            <!-- ACT 3: WELCOME MESSAGE -->
            <div id="act3" class="cinematic-act z-20 px-6 text-center">
                <div class="max-w-2xl space-y-3">
                    <span class="text-xs uppercase tracking-[0.35em] text-cyan-400 font-bold block">PORTAL RESMI SEKOLAH</span>
                    <h2 class="text-3xl md:text-5xl font-extrabold text-white leading-tight drop-shadow-xl">
                        SELAMAT DATANG DI <br>
                        <span class="text-cyan-400">SMAN 21 JAKARTA</span>
                    </h2>
                    <p class="text-xs md:text-sm text-slate-300 font-medium tracking-wider">
                        Memuat Pengalaman Digital Terbaik Anda...
                    </p>
                </div>
            </div>

        </div>

        <!-- Automatic Progress Bar Indikator Sinematik -->
        <div class="relative z-20 w-full h-1 bg-white/10">
            <div id="introProgressBar" class="intro-progress-bar h-full bg-gradient-to-r from-cyan-500 to-blue-400"></div>
        </div>

    </div>

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
                
                <a href="index.php" class="flex items-center gap-3.5 group">
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
                    <a href="index.php" class="px-3.5 py-2 rounded-lg text-cyan-600 bg-cyan-50 font-bold border border-cyan-200 shadow-sm">Beranda</a>

                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 flex items-center gap-1.5 transition-all">
                            <span>Profil</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full left-0 w-56 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="profil/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Tentang Sekolah</a>
                                <a href="profil/sejarah.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Sejarah Sekolah</a>
                                <a href="profil/visi-misi.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Visi & Misi</a>
                                <a href="profil/sambutan.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Sambutan Kepala Sekolah</a>
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
                                <a href="akademik/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Overview Akademik</a>
                                <a href="akademik/program.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Program Pendidikan</a>
                                <a href="akademik/guru.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Daftar Guru & Staf</a>
                                <a href="akademik/pemeringkatan.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Pemeringkatan Jurusan</a>
                                <a href="akademik/keterserapan-ptn.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Keterserapan PTN</a>
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
                                <a href="kegiatan/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Semua Kegiatan</a>
                                <a href="kegiatan/ekstrakurikuler.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Ekstrakurikuler</a>
                            </div>
                        </div>
                    </div>

                    <a href="prestasi/index.php" class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 transition-all">Prestasi</a>
                    <a href="fasilitas/index.php" class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 transition-all">Fasilitas</a>

                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 flex items-center gap-1.5 transition-all">
                            <span>Informasi</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full right-0 w-52 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="informasi/berita.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Berita Sekolah</a>
                                <a href="informasi/agenda.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Agenda & Kalender</a>
                                <a href="informasi/ppdb.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">PPDB Info</a>
                                <a href="gallery/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Galeri Foto</a>
                                <a href="kontak/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Kontak & Lokasi</a>
                            </div>
                        </div>
                    </div>

                    <a href="informasi/ppdb.php" class="ml-2 px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs flex items-center gap-2 shadow-sm transition-all">
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
            <a href="index.php" class="block text-cyan-600 font-bold">Beranda</a>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Profil</span>
                <a href="profil/index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Profil</a>
                <a href="profil/sejarah.php" class="block pl-3 py-1 text-xs text-slate-700">Sejarah</a>
                <a href="profil/visi-misi.php" class="block pl-3 py-1 text-xs text-slate-700">Visi & Misi</a>
                <a href="profil/sambutan.php" class="block pl-3 py-1 text-xs text-slate-700">Sambutan Kepala Sekolah</a>
            </div>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Akademik</span>
                <a href="akademik/index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Akademik</a>
                <a href="akademik/guru.php" class="block pl-3 py-1 text-xs text-slate-700">Daftar Guru</a>
                <a href="akademik/keterserapan-ptn.php" class="block pl-3 py-1 text-xs text-slate-700">Keterserapan PTN</a>
            </div>
            <a href="prestasi/index.php" class="block text-xs font-semibold text-slate-700">Prestasi</a>
            <a href="kegiatan/index.php" class="block text-xs font-semibold text-slate-700">Kegiatan & Ekskul</a>
            <a href="informasi/ppdb.php" class="block text-xs font-semibold text-slate-700">PPDB Info</a>
            <a href="kontak/index.php" class="block text-xs font-semibold text-slate-700">Kontak</a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 🚀 HERO SECTION                                                       -->
    <!-- ===================================================================== -->
    <section class="relative bg-brand-darkNavy text-white min-h-[600px] sm:min-h-[650px] lg:min-h-[720px] flex items-center overflow-hidden max-w-full group/hero">
        <div id="heroSlider" class="relative w-full h-full min-h-[600px] sm:min-h-[650px] lg:min-h-[720px] flex items-center">
            <?php foreach ($heroBanners as $bIdx => $banner): 
                $rawImg = $banner['background_image'] ?? $banner['gambar'] ?? $banner['image'] ?? $banner['foto'] ?? $banner['bg_image'] ?? '';
                $bgUrl = formatBannerImg($rawImg, $sekolah['hero_bg']);
                
                $textAlign = $banner['text_align'] ?? 'center';
                
                $alignClass = 'text-left items-start';
                $containerAlign = 'mr-auto';
                if ($textAlign === 'center') {
                    $alignClass = 'text-center items-center';
                    $containerAlign = 'mx-auto';
                } elseif ($textAlign === 'right') {
                    $alignClass = 'text-right items-end';
                    $containerAlign = 'ml-auto';
                }

                $titleStyle = (!empty($banner['is_italic'])) ? 'italic' : '';
                $titleWeight = (!empty($banner['is_bold'])) ? 'font-extrabold' : 'font-semibold';
                $fontFamily = !empty($banner['font_family']) ? $banner['font_family'] : 'Plus Jakarta Sans';
            ?>
            <div class="hero-slide absolute inset-0 w-full h-full flex items-center transition-opacity duration-1000 ease-in-out <?= $bIdx === 0 ? 'opacity-100 z-10 pointer-events-auto' : 'opacity-0 z-0 pointer-events-none'; ?>" data-index="<?= $bIdx; ?>">
                
                <div class="absolute inset-0 bg-cover bg-center bg-no-repeat hero-bg-anim" 
                     style="background-image: url('<?= htmlspecialchars($bgUrl); ?>');">
                </div>
                <div class="absolute inset-0 hero-overlay"></div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 py-16 sm:py-20 lg:py-28 w-full">
                    <div class="max-w-3xl space-y-5 sm:space-y-6 flex flex-col <?= $alignClass; ?> <?= $containerAlign; ?> reveal-section is-visible">
                        
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-cyan-500/20 border border-cyan-400/40 text-cyan-300 text-[11px] sm:text-xs font-bold tracking-wide uppercase backdrop-blur-md shadow-lg">
                            <i class="fa-solid fa-award text-cyan-400"></i>
                            <span>AKREDITASI A • SMA NEGERI 21 JAKARTA</span>
                        </div>

                        <h1 class="text-3xl sm:text-5xl lg:text-6xl <?= $titleWeight; ?> <?= $titleStyle; ?> text-white leading-snug sm:leading-tight tracking-tight drop-shadow-md" style="font-family: '<?= htmlspecialchars($fontFamily); ?>', sans-serif;">
                            <?= $banner['title']; ?>
                        </h1>

                        <?php if (!empty($banner['description'])): ?>
                        <p class="text-xs sm:text-base text-slate-200 font-normal leading-relaxed max-w-2xl">
                            <?= nl2br(htmlspecialchars($banner['description'])); ?>
                        </p>
                        <?php endif; ?>

                        <div class="pt-2 flex flex-wrap items-center gap-3 sm:gap-4">
                            <?php if (!empty($banner['button_text'])): ?>
                            <a href="<?= htmlspecialchars($banner['button_link'] ?: 'informasi/ppdb.php'); ?>" class="px-6 sm:px-7 py-3 sm:py-3.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg hover:shadow-cyan-600/40 transition-all flex items-center gap-2.5">
                                <span><?= htmlspecialchars($banner['button_text']); ?></span>
                                <i class="fa-solid fa-arrow-right text-xs"></i>
                            </a>
                            <?php endif; ?>
                            <a href="profil/index.php" class="px-6 sm:px-7 py-3 sm:py-3.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs uppercase tracking-wider border border-white/30 backdrop-blur-md transition-all flex items-center gap-2.5">
                                <span>PROFIL SEKOLAH</span>
                                <i class="fa-solid fa-compass text-xs"></i>
                            </a>
                        </div>

                        <p class="text-[11px] sm:text-xs text-cyan-300/80 tracking-widest uppercase font-semibold pt-1 sm:pt-2">
                            SMAN 21 JAKARTA T.A. 2026/2027
                        </p>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($heroBanners) > 1): ?>
        <button onclick="prevHeroSlide()" class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-black/30 hover:bg-cyan-600 text-white border border-white/20 flex items-center justify-center transition-all opacity-80 sm:opacity-0 group-hover/hero:opacity-100">
            <i class="fa-solid fa-chevron-left text-sm"></i>
        </button>
        <button onclick="nextHeroSlide()" class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-black/30 hover:bg-cyan-600 text-white border border-white/20 flex items-center justify-center transition-all opacity-80 sm:opacity-0 group-hover/hero:opacity-100">
            <i class="fa-solid fa-chevron-right text-sm"></i>
        </button>

        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2">
            <?php foreach ($heroBanners as $bIdx => $banner): ?>
            <button onclick="goToHeroSlide(<?= $bIdx; ?>)" class="hero-dot w-3 h-3 rounded-full transition-all <?= $bIdx === 0 ? 'bg-cyan-400 w-8' : 'bg-white/40 hover:bg-white'; ?>" data-dot="<?= $bIdx; ?>"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- ===================================================================== -->
    <!-- ⚡ SECTION HIGHLIGHTS / KEUNGGULAN                                    -->
    <!-- ===================================================================== -->
    <section class="relative z-20 -mt-10 lg:-mt-14 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            
            <a href="informasi/ppdb.php" class="school-card p-6 rounded-2xl border-t-4 border-cyan-500 group flex flex-col justify-between reveal-section delay-100">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center mb-4 group-hover:scale-110 group-hover:bg-cyan-600 group-hover:text-white transition-all duration-300 shadow-sm">
                        <i class="fa-solid fa-shield-halved text-2xl"></i>
                    </div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 group-hover:text-cyan-600 transition-colors">
                        NO BULLY, NO SENIORITAS
                    </h3>
                    <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                        Lingkungan belajar aman, ramah anak, inklusif, tanpa pungutan liar.
                    </p>
                </div>
                <span class="text-[10px] font-bold text-cyan-600 uppercase tracking-wider mt-4 flex items-center gap-1">
                    Info PPDB <i class="fa-solid fa-chevron-right text-[8px] group-hover:translate-x-1 transition-transform"></i>
                </span>
            </a>

            <a href="akademik/program.php" class="school-card p-6 rounded-2xl border-t-4 border-blue-600 group flex flex-col justify-between reveal-section delay-200">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center mb-4 group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white transition-all duration-300 shadow-sm">
                        <i class="fa-solid fa-book-bookmark text-2xl"></i>
                    </div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 group-hover:text-cyan-600 transition-colors">
                        KURIKULUM MERDEKA
                    </h3>
                    <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                        Penguatan literasi, koding, sains terapan, dan proyek Pancasila.
                    </p>
                </div>
                <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider mt-4 flex items-center gap-1">
                    Lihat Program <i class="fa-solid fa-chevron-right text-[8px] group-hover:translate-x-1 transition-transform"></i>
                </span>
            </a>

            <a href="prestasi/index.php" class="school-card p-6 rounded-2xl border-t-4 border-emerald-600 group flex flex-col justify-between reveal-section delay-300">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center mb-4 group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300 shadow-sm">
                        <i class="fa-solid fa-trophy text-2xl"></i>
                    </div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 group-hover:text-cyan-600 transition-colors">
                        PRESTASI NASIONAL
                    </h3>
                    <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                        Juara Olimpiade Sains, Seni, dan Olahraga tingkat Provinsi & Nasional.
                    </p>
                </div>
                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider mt-4 flex items-center gap-1">
                    Daftar Prestasi <i class="fa-solid fa-chevron-right text-[8px] group-hover:translate-x-1 transition-transform"></i>
                </span>
            </a>

            <a href="akademik/guru.php" class="school-card p-6 rounded-2xl border-t-4 border-purple-600 group flex flex-col justify-between reveal-section delay-400">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center mb-4 group-hover:scale-110 group-hover:bg-purple-600 group-hover:text-white transition-all duration-300 shadow-sm">
                        <i class="fa-solid fa-chalkboard-user text-2xl"></i>
                    </div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 group-hover:text-cyan-600 transition-colors">
                        GURU TERSERTIFIKASI
                    </h3>
                    <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                        Tenaga pendidik profesional berpendidikan S1/S2 berdedikasi tinggi.
                    </p>
                </div>
                <span class="text-[10px] font-bold text-purple-600 uppercase tracking-wider mt-4 flex items-center gap-1">
                    Profil Guru <i class="fa-solid fa-chevron-right text-[8px] group-hover:translate-x-1 transition-transform"></i>
                </span>
            </a>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🏫 SECTION SAMBUTAN KEPALA SEKOLAH                                    -->
    <!-- ===================================================================== -->
    <section class="relative py-24 bg-cover bg-center border-b border-slate-200 overflow-hidden" 
             style="background-image: url('<?= htmlspecialchars($sekolah['sambutan_bg']); ?>');">
        <div class="absolute inset-0 sambutan-overlay"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid lg:grid-cols-12 gap-12 items-center">
                
                <!-- Card Kepsek & Foto -->
                <div class="lg:col-span-5 reveal-section">
                    <div class="relative max-w-md mx-auto">
                        <div class="absolute -inset-3 rounded-3xl bg-gradient-to-r from-cyan-500/30 to-brand-navy/30 blur-xl"></div>
                        <div class="school-card p-6 rounded-2xl relative z-10 text-center bg-white/95 backdrop-blur-md border border-cyan-100 shadow-2xl">
                            <div class="relative w-full aspect-[4/5] rounded-xl overflow-hidden mb-5 bg-gradient-to-br from-cyan-50 to-blue-100 border border-cyan-200/60 p-2 flex items-center justify-center shadow-inner">
                                <?php 
                                    $fotoKepsek = !empty($sambutan['foto']) ? $sambutan['foto'] : $logoPath;
                                ?>
                                <img src="<?= htmlspecialchars($fotoKepsek); ?>" 
                                     alt="Foto Kepala Sekolah" 
                                     class="w-full h-full object-cover rounded-lg drop-shadow-md hover:scale-105 transition-transform duration-500"
                                     onerror="this.onerror=null; this.src='<?= $logoPath; ?>'; this.className='max-h-full max-w-full object-contain p-4';">
                            </div>
                            <h3 class="text-lg font-bold text-brand-darkNavy"><?= htmlspecialchars($sambutan['nama']); ?></h3>
                            <p class="text-xs text-cyan-600 font-semibold mt-1 uppercase tracking-wider"><?= htmlspecialchars($sambutan['jabatan']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Text Sambutan Resmi -->
                <div class="lg:col-span-7 space-y-6 reveal-section delay-200">
                    <div class="inline-flex items-center gap-2 text-cyan-700 text-xs font-bold uppercase tracking-widest bg-cyan-100/80 px-3.5 py-1.5 rounded-md border border-cyan-300/80 backdrop-blur-sm">
                        <i class="fa-solid fa-quote-left text-cyan-600"></i>
                        <span>SAMBUTAN KEPALA SEKOLAH</span>
                    </div>

                    <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy leading-tight">
                        <?= htmlspecialchars($sambutan['judul']); ?>
                    </h2>

                    <!-- Container format HTML TinyMCE -->
                    <div class="sambutan-content font-normal">
                        <?= $sambutan['isi']; ?>
                    </div>

                    <div class="pt-4 flex items-center gap-4">
                        <a href="profil/sambutan.php" class="px-7 py-3 rounded-xl bg-brand-darkNavy hover:bg-slate-800 text-white font-bold text-xs tracking-wide transition-all shadow-md flex items-center gap-2">
                            <span>Baca Sambutan Lengkap</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📚 PROGRAM PENDIDIKAN & UNGGULAN                                      -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-slate-50 border-b border-slate-200 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-2xl mx-auto mb-14 space-y-2 reveal-section">
                <span class="text-xs font-bold uppercase tracking-widest text-cyan-600 bg-cyan-100 px-3 py-1 rounded-full border border-cyan-200">AKADEMIK UNGGULAN</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy pt-2">Program Pendidikan Terbaik</h2>
                <p class="text-xs sm:text-sm text-slate-500">Dirancang khusus untuk mengasah potensi akademik, kecakapan digital, serta karakter Pancasila.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <?php $pIndex = 1; foreach ($programs as $prog): ?>
                <div class="school-card p-8 rounded-2xl space-y-4 bg-white border border-slate-200 reveal-section delay-<?= $pIndex * 100; ?>">
                    <div class="w-12 h-12 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-600 flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid <?= htmlspecialchars($prog['ikon'] ?? 'fa-graduation-cap'); ?>"></i>
                    </div>
                    <h3 class="text-base font-bold text-brand-darkNavy"><?= htmlspecialchars($prog['nama_program']); ?></h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        <?= htmlspecialchars($prog['deskripsi']); ?>
                    </p>
                    <a href="akademik/program.php" class="inline-flex items-center gap-1.5 text-xs font-bold text-cyan-600 hover:text-cyan-700 pt-2 group">
                        <span>Detail Kurikulum</span>
                        <i class="fa-solid fa-chevron-right text-[9px] group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
                <?php $pIndex++; endforeach; ?>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📊 REKAPITULASI LULUSAN PTN SECTION                                   -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-white border-b border-slate-200 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-12 items-center">
                
                <div class="lg:col-span-5 space-y-6 reveal-section">
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-600 bg-cyan-50 px-3 py-1 rounded-md border border-cyan-200">
                        REKAPITULASI LULUSAN
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy leading-tight">
                        Keterserapan Alumni di Perguruan Tinggi Negeri
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Lulusan SMAN 21 Jakarta secara konsisten diterima di berbagai PTN Favorit Indonesia (UI, ITB, UGM, IPB, Unpad, ITS) melalui jalur SNBP, SNBT, Kedinasan, serta Jalur Mandiri.
                    </p>

                    <div class="grid grid-cols-2 gap-4 pt-2">
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 shadow-sm">
                            <span class="text-2xl font-extrabold text-brand-darkNavy block">100%</span>
                            <span class="text-[11px] text-slate-500 font-medium">Tingkat Kelulusan</span>
                        </div>
                        <div class="p-4 rounded-xl bg-cyan-50 border border-cyan-200 shadow-sm">
                            <span class="text-2xl font-extrabold text-cyan-700 block">85%+</span>
                            <span class="text-[11px] text-cyan-800 font-medium">Diterima di PTN</span>
                        </div>
                    </div>

                    <a href="akademik/keterserapan-ptn.php" class="inline-flex items-center gap-2 text-xs font-bold text-cyan-600 hover:underline pt-2">
                        <span>Lihat Detail Keterserapan Alumni</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>

                <div class="lg:col-span-7 reveal-section delay-200">
                    <div class="school-card p-6 sm:p-8 rounded-2xl bg-white border border-slate-200 shadow-xl">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                            <div>
                                <h3 class="text-sm font-bold text-brand-darkNavy">Grafik Kelulusan PTN per Tahun</h3>
                                <p class="text-[11px] text-slate-500">Statistik penerimaan siswa di Perguruan Tinggi Negeri</p>
                            </div>
                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-200">Data Resmi</span>
                        </div>

                        <div class="relative w-full h-72">
                            <canvas id="ptnChart"></canvas>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🏆 PRESTASI SEKOLAH                                                   -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-slate-50 border-b border-slate-200 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-4 reveal-section">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-600">CAPAIAN SISWA</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-darkNavy mt-1">Prestasi Terbaru</h2>
                </div>
                <a href="prestasi/index.php" class="text-xs font-bold text-cyan-600 hover:underline flex items-center gap-1.5">
                    <span>Lihat Semua Prestasi</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <?php if (!empty($prestasiList)): $prIndex = 1; foreach ($prestasiList as $pres): ?>
                <div class="school-card rounded-2xl p-6 bg-white border border-slate-200 space-y-3 reveal-section delay-<?= $prIndex * 100; ?>">
                    <span class="px-2.5 py-1 rounded bg-cyan-100 text-cyan-800 text-[10px] font-bold tracking-wider uppercase inline-block">
                        <?= htmlspecialchars($pres['kategori']); ?> • <?= htmlspecialchars($pres['tingkat']); ?>
                    </span>
                    <h3 class="text-sm font-bold text-brand-darkNavy leading-snug"><?= htmlspecialchars($pres['judul_prestasi']); ?></h3>
                    <p class="text-xs text-cyan-700 font-medium"><?= htmlspecialchars($pres['nama_siswa']); ?> (<?= htmlspecialchars($pres['peringkat']); ?>)</p>
                    <p class="text-[11px] text-slate-500 line-clamp-2"><?= htmlspecialchars($pres['deskripsi']); ?></p>
                </div>
                <?php $prIndex++; endforeach; else: ?>
                <div class="col-span-3 school-card p-8 rounded-2xl text-center text-slate-500 text-xs bg-white reveal-section">
                    Siswa-siswi SMAN 21 Jakarta aktif menorehkan prestasi dalam Olimpiade Sains, Olahraga, dan Seni.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📰 BERITA & AGENDA MENDATANG                                          -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-white border-b border-slate-200 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-12">
                
                <div class="lg:col-span-7 space-y-6 reveal-section">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                        <h2 class="text-xl font-extrabold text-brand-darkNavy">Berita & Pengumuman</h2>
                        <a href="informasi/berita.php" class="text-xs text-cyan-600 font-semibold hover:underline">Semua Berita →</a>
                    </div>

                    <div class="space-y-4">
                        <?php if (!empty($beritaList)): foreach ($beritaList as $brt): ?>
                        <a href="informasi/berita-detail.php?slug=<?= htmlspecialchars($brt['slug']); ?>" class="school-card p-4 rounded-2xl flex gap-4 items-center group block bg-white">
                            <div class="w-20 h-20 rounded-xl bg-slate-100 overflow-hidden flex-shrink-0 border border-slate-200">
                                <img src="<?= htmlspecialchars($brt['thumbnail'] ?: $logoPath); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                            </div>
                            <div>
                                <span class="text-[10px] text-cyan-600 font-bold uppercase tracking-wider block mb-1"><?= htmlspecialchars($brt['kategori']); ?></span>
                                <h3 class="text-xs sm:text-sm font-bold text-brand-darkNavy group-hover:text-cyan-600 transition-colors line-clamp-2"><?= htmlspecialchars($brt['judul']); ?></h3>
                                <p class="text-[11px] text-slate-500 mt-1 line-clamp-1"><?= htmlspecialchars($brt['ringkasan']); ?></p>
                            </div>
                        </a>
                        <?php endforeach; else: ?>
                        <p class="text-xs text-slate-500 italic">Belum ada berita dipublikasikan.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="lg:col-span-5 space-y-6 reveal-section delay-200">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                        <h2 class="text-xl font-extrabold text-brand-darkNavy">Agenda Sekolah</h2>
                        <a href="informasi/agenda.php" class="text-xs text-cyan-600 font-semibold hover:underline">Kalender →</a>
                    </div>

                    <div class="space-y-4">
                        <?php if (!empty($agendaList)): foreach ($agendaList as $agd): ?>
                        <div class="school-card p-4 rounded-2xl flex items-center gap-4 bg-white border border-slate-200">
                            <div class="w-14 h-14 rounded-xl bg-cyan-50 text-cyan-700 border border-cyan-200 flex flex-col items-center justify-center flex-shrink-0 font-bold">
                                <span class="text-[10px] uppercase"><?= date('M', strtotime($agd['tanggal_mulai'])); ?></span>
                                <span class="text-base leading-none"><?= date('d', strtotime($agd['tanggal_mulai'])); ?></span>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-brand-darkNavy"><?= htmlspecialchars($agd['judul_agenda']); ?></h4>
                                <p class="text-[11px] text-slate-500 mt-1"><i class="fa-regular fa-clock mr-1 text-cyan-600"></i><?= htmlspecialchars($agd['lokasi'] ?: 'Lingkungan SMAN 21'); ?></p>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="school-card p-6 rounded-2xl text-center text-xs text-slate-500 bg-white">
                            Agenda sekolah terbaru akan diperbarui secara berkala.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🤖 SECTION AI ASSISTANT BANNER                                        -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-slate-900 text-white relative overflow-hidden">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 reveal-section">
            <div class="p-8 sm:p-12 rounded-3xl bg-gradient-to-r from-brand-darkNavy to-slate-800 border border-slate-700 text-center space-y-5 shadow-2xl relative overflow-hidden">
                <div class="w-14 h-14 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center mx-auto text-2xl border border-cyan-500/30 pulse-glow">
                    <i class="fa-solid fa-circle-question"></i>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white">
                    Punya Pertanyaan tentang SMAN 21 Jakarta?
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                    Layanan Pusat Informasi SMAN 21 siap membantu menjawab pertanyaan seputar Syarat PPDB, Kurikulum, Daftar Guru, Pemeringkatan, dan Fasilitas Sekolah secara cepat.
                </p>
                <div class="pt-2">
                    <button onclick="toggleAiPanel()" class="px-7 py-3 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-lg transition-all inline-flex items-center gap-2.5 uppercase tracking-wider">
                        <i class="fa-solid fa-comments text-sm"></i>
                        <span>TANYAKAN LAYANAN INFORMASI</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📍 SECTION KUNJUNGI SMAN 21 JAKARTA                                  -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-slate-100 border-t border-slate-200 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-12 items-center">
                
                <div class="lg:col-span-5 space-y-6 reveal-section">
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-600 bg-cyan-50 px-3 py-1 rounded-md border border-cyan-200">
                        LOKASI & KONTAK
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy leading-tight">
                        Kunjungi SMAN 21 Jakarta
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Silakan hubungi kami atau berkunjung langsung ke lokasi kampus SMAN 21 Jakarta untuk pelayanan administrasi, PPDB, maupun konsultasi pendidikan.
                    </p>

                    <div class="space-y-4 pt-2 text-xs text-slate-700">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0 mt-0.5 shadow-sm border border-cyan-200">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <div>
                                <span class="font-bold text-brand-darkNavy block">Alamat Sekolah</span>
                                <span><?= htmlspecialchars($sekolah['alamat']); ?></span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0 mt-0.5 shadow-sm border border-cyan-200">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <div>
                                <span class="font-bold text-brand-darkNavy block">Telepon / Fax</span>
                                <span><?= htmlspecialchars($sekolah['telepon']); ?></span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0 mt-0.5 shadow-sm border border-cyan-200">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <div>
                                <span class="font-bold text-brand-darkNavy block">Email Resmi</span>
                                <span><?= htmlspecialchars($sekolah['email']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <a href="kontak/index.php" class="px-6 py-3 rounded-xl bg-brand-darkNavy hover:bg-slate-800 text-white font-bold text-xs inline-flex items-center gap-2 shadow transition-all">
                            <span>Buka Peta & Detail Kontak</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-7 reveal-section delay-200">
                    <div class="school-card p-2 rounded-2xl bg-white border border-slate-200 shadow-xl overflow-hidden h-96">
                        <iframe 
                            class="w-full h-full rounded-xl border-0"
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.602283183597!2d106.8770143!3d-6.1839542!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f4e242a5bb01%3A0x6b44319409890251!2sSMA%20Negeri%2021%20Jakarta!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
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
                        <li><a href="profil/index.php" class="hover:text-cyan-400 transition-colors">Profil Sekolah</a></li>
                        <li><a href="akademik/index.php" class="hover:text-cyan-400 transition-colors">Kurikulum & Akademik</a></li>
                        <li><a href="akademik/guru.php" class="hover:text-cyan-400 transition-colors">Daftar Guru & Staf</a></li>
                        <li><a href="prestasi/index.php" class="hover:text-cyan-400 transition-colors">Galeri Prestasi Siswa</a></li>
                        <li><a href="fasilitas/index.php" class="hover:text-cyan-400 transition-colors">Sarana & Fasilitas</a></li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Layanan Informasi</h3>
                    <ul class="space-y-2 text-[11px]">
                        <li><a href="informasi/ppdb.php" class="hover:text-cyan-400 transition-colors">Informasi SPMB / PPDB</a></li>
                        <li><a href="informasi/berita.php" class="hover:text-cyan-400 transition-colors">Berita & Pengumuman</a></li>
                        <li><a href="informasi/agenda.php" class="hover:text-cyan-400 transition-colors">Agenda Kalender Akademik</a></li>
                        <li><a href="akademik/keterserapan-ptn.php" class="hover:text-cyan-400 transition-colors">Keterserapan PTN Alumni</a></li>
                        <li><a href="kontak/index.php" class="hover:text-cyan-400 transition-colors">Kontak & Pusat Bantuan</a></li>
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
                    <a href="admin/login.php" class="hover:text-cyan-400 transition-colors"><i class="fa-solid fa-lock text-[10px] mr-1"></i>Portal Admin</a>
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
                <p>Ada yang bisa saya bantu terkait SPMB/PPDB, Kurikulum, Guru, Pemeringkatan, atau Kegiatan SMAN 21 Jakarta?</p>
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
        // Global Timer Handles untuk Intro
        let act1Timer, act2Timer, fadeOutTimer, removeTimer;

        // Fungsi Lewati Intro (Skip Intro)
        function skipIntro() {
            const intro = document.getElementById('cinematicIntro');
            if (!intro) return;

            clearTimeout(act1Timer);
            clearTimeout(act2Timer);
            clearTimeout(fadeOutTimer);
            clearTimeout(removeTimer);

            intro.classList.remove('intro-visible');
            intro.classList.add('intro-fade-out');

            setTimeout(() => {
                intro.remove();
            }, 800);
        }

        // 1. CINEMATIC INTRO AUTOMATIC CONTROLLER
        window.addEventListener('DOMContentLoaded', () => {
            const intro = document.getElementById('cinematicIntro');
            if (!intro) return;

            requestAnimationFrame(() => {
                intro.classList.add('intro-visible');
            });

            act1Timer = setTimeout(() => {
                const act1 = document.getElementById('act1');
                const act2 = document.getElementById('act2');
                if (act1) act1.classList.remove('active');
                if (act2) act2.classList.add('active');
            }, 3200);

            act2Timer = setTimeout(() => {
                const act2 = document.getElementById('act2');
                const act3 = document.getElementById('act3');
                if (act2) act2.classList.remove('active');
                if (act3) act3.classList.add('active');
            }, 6400);

            fadeOutTimer = setTimeout(() => {
                intro.classList.remove('intro-visible');
                intro.classList.add('intro-fade-out');
                
                removeTimer = setTimeout(() => {
                    intro.remove();
                }, 800);
            }, 9500);
        });

        // 2. MOBILE MENU TOGGLE
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }

        // 3. HERO SLIDER CONTROLLER
        let currentHeroSlide = 0;
        const heroSlides = document.querySelectorAll('.hero-slide');
        const heroDots = document.querySelectorAll('.hero-dot');
        let heroSlideInterval;

        function showHeroSlide(index) {
            if (!heroSlides.length) return;
            if (index >= heroSlides.length) currentHeroSlide = 0;
            else if (index < 0) currentHeroSlide = heroSlides.length - 1;
            else currentHeroSlide = index;

            heroSlides.forEach((slide, idx) => {
                if (idx === currentHeroSlide) {
                    slide.classList.remove('opacity-0', 'z-0', 'pointer-events-none');
                    slide.classList.add('opacity-100', 'z-10', 'pointer-events-auto');
                } else {
                    slide.classList.remove('opacity-100', 'z-10', 'pointer-events-auto');
                    slide.classList.add('opacity-0', 'z-0', 'pointer-events-none');
                }
            });

            heroDots.forEach((dot, idx) => {
                if (idx === currentHeroSlide) {
                    dot.className = 'hero-dot w-8 h-3 rounded-full transition-all bg-cyan-400';
                } else {
                    dot.className = 'hero-dot w-3 h-3 rounded-full transition-all bg-white/40 hover:bg-white';
                }
            });
        }

        function nextHeroSlide() {
            showHeroSlide(currentHeroSlide + 1);
            resetHeroTimer();
        }

        function prevHeroSlide() {
            showHeroSlide(currentHeroSlide - 1);
            resetHeroTimer();
        }

        function goToHeroSlide(idx) {
            showHeroSlide(idx);
            resetHeroTimer();
        }

        function resetHeroTimer() {
            clearInterval(heroSlideInterval);
            if (heroSlides.length > 1) {
                heroSlideInterval = setInterval(() => {
                    showHeroSlide(currentHeroSlide + 1);
                }, 6000);
            }
        }

        if (heroSlides.length > 1) {
            resetHeroTimer();
        }

        // 4. ANIMASI REVEAL OBSERVER PER SECTION
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, { threshold: 0.08 });

        document.querySelectorAll('.reveal-section').forEach(el => observer.observe(el));

        // 5. CHART.JS PTN STATS
        const ptnRawData = <?= json_encode($ptnData); ?>;
        const years = ptnRawData.map(item => item.tahun);
        const snbpData = ptnRawData.map(item => parseInt(item.snbp || 0));
        const snbtData = ptnRawData.map(item => parseInt(item.snbt || 0));
        const mandiriData = ptnRawData.map(item => parseInt(item.mandiri || 0) + parseInt(item.ptln || 0) + parseInt(item.kedinasan || 0));

        const ctx = document.getElementById('ptnChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: years,
                    datasets: [
                        {
                            label: 'SNBP (Prestasi)',
                            data: snbpData,
                            backgroundColor: '#00A8CC',
                            borderRadius: 6
                        },
                        {
                            label: 'SNBT (UTBK)',
                            data: snbtData,
                            backgroundColor: '#0284C7',
                            borderRadius: 6
                        },
                        {
                            label: 'Mandiri / Kedinasan',
                            data: mandiriData,
                            backgroundColor: '#0B1E36',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { font: { family: 'Plus Jakarta Sans', size: 11 } } }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { grid: { color: '#F1F5F9' } }
                    }
                }
            });
        }

        // 6. AI ASSISTANT PANEL TOGGLE & CHAT AJAX (TERHUBUNG KE api/ai-chat.php)
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

            // KONEKSI DIHUBUNGKAN KE api/ai-chat.php DENGAN FALLBACK OTOMATIS
            const formData = new URLSearchParams();
            formData.append('query', q);
            formData.append('message', q);

            function handleAiResponse(data) {
                loadingMsg.remove();
                const aiMsg = document.createElement('div');
                aiMsg.className = 'p-3.5 rounded-xl bg-white border border-slate-200 text-slate-800 max-w-[90%] text-xs shadow-sm space-y-1.5';
                
                let answerText = data.answer || data.reply || data.message || data.response || (data.data && data.data.answer) || 'Tidak ada tanggapan.';
                let sourceText = data.source || data.sumber || 'Database SMAN 21 Jakarta';
                let linkHtml = '';

                let linkUrl = data.related_link || data.link || (data.data && data.data.related_link);
                if (linkUrl) {
                    linkHtml = `<div class="pt-1"><a href="${linkUrl}" class="text-cyan-600 font-bold hover:underline inline-flex items-center gap-1">Pelajari lebih lanjut <i class="fa-solid fa-arrow-right text-[9px]"></i></a></div>`;
                }

                aiMsg.innerHTML = `<div>${answerText}</div><div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider pt-1 border-t border-slate-100">Sumber: ${sourceText}</div>${linkHtml}`;
                chatBox.appendChild(aiMsg);
                chatBox.scrollTop = chatBox.scrollHeight;
            }

            fetch('api/ai-chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: formData.toString()
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP Status ' + res.status);
                return res.json();
            })
            .then(data => handleAiResponse(data))
            .catch(() => {
                // Fallback otomatis ke endpoint lokal jika api/ai-chat.php belum ada / error 404
                fetch('?action=ai_query', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: formData.toString()
                })
                .then(res => res.json())
                .then(data => handleAiResponse(data))
                .catch(() => {
                    loadingMsg.className = 'p-3 rounded-xl bg-red-50 border border-red-200 text-red-600 max-w-[85%] text-xs shadow-sm';
                    loadingMsg.innerText = 'Maaf, terjadi gangguan koneksi ke API AI.';
                });
            });
        }
    </script>
</body>
</html>
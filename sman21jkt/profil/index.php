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
// ENDPOINT AJAX UNTUK S21 AI ASSISTANT (CONTEXT-AWARE: PROFIL)
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
    $sumberInfo = "Basis Informasi Profil SMAN 21 Jakarta";
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
                        $sumberInfo = $k['sumber_informasi'] ?: "Database AI Profil SMAN 21";
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched && (strpos($qLower, $pertanyaanLower) !== false || strpos($pertanyaanLower, $qLower) !== false)) {
                $responseAnswer = $k['jawaban'];
                $sumberInfo = $k['sumber_informasi'] ?: "Database AI Profil SMAN 21";
                $matched = true;
                break;
            }

            if ($matched) break;
        }

        if (!$matched) {
            if (strpos($qLower, 'sejarah') !== false) {
                $responseAnswer = "SMAN 21 Jakarta didirikan untuk mencetak generasi berprestasi dan berkarakter Pancasila. Detail riwayat pendirian dapat diakses pada menu Sejarah Sekolah.";
                $sumberInfo = "Profil Sejarah";
                $relatedLink = "sejarah.php";
                $matched = true;
            } elseif (strpos($qLower, 'visi') !== false || strpos($qLower, 'misi') !== false) {
                $responseAnswer = "Visi SMAN 21 Jakarta: Unggul dalam Prestasi dan Teknologi, Berkarakter Pancasila, Berbudaya Lingkungan, berdasarkan Iman dan Taqwa.";
                $sumberInfo = "Profil Visi Misi";
                $relatedLink = "visi-misi.php";
                $matched = true;
            } elseif (strpos($qLower, 'kepala sekolah') !== false || strpos($qLower, 'sambutan') !== false) {
                $responseAnswer = "Kepala SMAN 21 Jakarta saat ini berkomitmen menghadirkan pendidikan inklusif, modern, dan berbasis teknologi. Baca sambutan selengkapnya pada halaman Sambutan.";
                $sumberInfo = "Profil Kepala Sekolah";
                $relatedLink = "sambutan.php";
                $matched = true;
            }
        }

        if (!$matched) {
            $responseAnswer = $fallbackMsg;
            $sumberInfo = "Sistem Verifikasi Profil SMAN 21";
        }

    } else {
        if (strpos(strtolower($userQuery), 'visi') !== false) {
            $responseAnswer = "Visi SMAN 21 Jakarta: Unggul dalam Prestasi dan Teknologi, Berkarakter Pancasila, Berbudaya Lingkungan, berdasarkan Iman dan Taqwa.";
            $sumberInfo = "Profil Visi Misi";
            $relatedLink = "visi-misi.php";
        } else {
            $responseAnswer = "Maaf, informasi tersebut belum tersedia dalam basis data resmi SMAN 21 Jakarta. Silakan ajukan pertanyaan seputar Sejarah, Visi Misi, atau Sambutan.";
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
// FETCH DATA PROFIL & SEKOLAH DARI DATABASE
// =============================================================================
$sekolah = [
    'nama_sekolah' => 'SMAN 21 Jakarta',
    'npsn' => '20103310',
    'slogan' => 'Where Future Begins • Unggul, Berkarakter, Berteknologi',
    'akreditasi' => 'A (Sangat Baik)',
    'kurikulum' => 'Kurikulum Merdeka',
    'alamat' => 'Jl. Tanah Mas Raya No.1, RT.3/RW.1, Kayu Putih, Kec. Pulo Gadung, Kota Jakarta Timur',
    'telepon' => '(021) 4892346',
    'email' => 'info@sman21jkt.sch.id',
    'instagram' => 'https://instagram.com/sman21jakarta',
    'youtube' => 'https://youtube.com/sman21jkt',
    'logo' => '../uploads/logo/sman21jakarta.png',
    'banner_profil' => '../uploads/banners/bannerprofil.jpg'
];

if ($conn) {
    $stmtSek = $conn->query("SELECT * FROM pengaturan_sekolah LIMIT 1");
    if ($stmtSek && $rowSek = $stmtSek->fetch()) {
        if (!empty($rowSek['nama_sekolah'])) $sekolah['nama_sekolah'] = $rowSek['nama_sekolah'];
        if (!empty($rowSek['npsn'])) $sekolah['npsn'] = $rowSek['npsn'];
        if (!empty($rowSek['slogan'])) $sekolah['slogan'] = $rowSek['slogan'];
        if (!empty($rowSek['akreditasi'])) $sekolah['akreditasi'] = $rowSek['akreditasi'];
        if (!empty($rowSek['alamat'])) $sekolah['alamat'] = $rowSek['alamat'];
        if (!empty($rowSek['telepon'])) $sekolah['telepon'] = $rowSek['telepon'];
        if (!empty($rowSek['email'])) $sekolah['email'] = $rowSek['email'];
        if (!empty($rowSek['instagram'])) $sekolah['instagram'] = $rowSek['instagram'];
    }
}

// Data Sambutan Ringkas
$sambutan = [
    'nama' => 'Mutia, M.Pd',
    'jabatan' => 'Kepala SMAN 21 Jakarta',
    'judul' => 'Sambutan Kepala SMAN 21 Jakarta',
    'isi' => '<p><strong>Selamat datang di official digital portal SMAN 21 Jakarta.</strong></p><p>Kami berkomitmen memberikan pendidikan berstandar tinggi yang mengintegrasikan akademik unggul, karakter Pancasila, serta kepemimpinan berbasis teknologi modern.</p>',
    'foto' => '../uploads/sambutan/kepsek.jpg'
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
                $sambutan['foto'] = '../uploads/sambutan/' . ltrim($fotoVal, '/');
            } else {
                $sambutan['foto'] = (strpos($fotoVal, 'http') === 0) ? $fotoVal : '../' . ltrim($fotoVal, '/');
            }
        }
    }
}

$logoPath = $sekolah['logo'];
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Profil Sekolah — SMA NEGERI 21 JAKARTA</title>
    <meta name="description" content="Gambaran Umum Profil, Sejarah, Visi Misi, dan Sambutan Kepala Sekolah SMAN 21 Jakarta.">
    
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
                            blue: '#1E40AF',
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

        /* Smooth Per-Section Animation */
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

        /* 📝 STYLING ISI SAMBUTAN AGAR RENDER FORMAT HTML TINYMCE DENGAN INDAH */
        .sambutan-content {
            color: #334155;
            font-size: 0.9rem;
            line-height: 1.7;
        }
        .sambutan-content p {
            margin-bottom: 0.75rem;
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
            margin-bottom: 0.75rem;
        }
        .sambutan-content ol {
            list-style-type: decimal;
            padding-left: 1.25rem;
            margin-bottom: 0.75rem;
        }

        /* Cinematic Intro Screen Overlay */
        .cinematic-act {
            opacity: 0;
            transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
            transform: scale(0.97);
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
            transform: scale(1);
            pointer-events: auto;
        }

        ::-webkit-scrollbar { width: 7px; }
        ::-webkit-scrollbar-track { background: #061224; }
        ::-webkit-scrollbar-thumb { background: #00A8CC; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #0086A3; }
    </style>
</head>
<body class="relative min-h-screen text-slate-800 bg-slate-50 selection:bg-cyan-500 selection:text-white max-w-full overflow-x-hidden">

    <!-- ===================================================================== -->
    <!-- 🎬 CINEMATIC OPENING OVERLAY                                           -->
    <!-- ===================================================================== -->
    <div id="cinematicIntro" class="fixed inset-0 z-[9999] bg-brand-darkNavy text-white overflow-hidden transition-opacity duration-700">
        <button onclick="skipIntro()" class="absolute top-6 right-6 z-[10000] px-4 py-2 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 text-xs font-semibold tracking-wider text-slate-200 transition-all flex items-center gap-2">
            <span>LEWATI INTRO</span>
            <i class="fa-solid fa-forward text-[10px]"></i>
        </button>

        <div class="absolute inset-0 bg-gradient-to-t from-brand-darkNavy via-brand-navy/80 to-brand-darkNavy z-10"></div>
        <div id="cinematicBg" class="absolute inset-0 bg-cover bg-center transition-all duration-1000 opacity-25 scale-105" style="background-image: url('<?= htmlspecialchars($sekolah['banner_profil']); ?>');"></div>

        <!-- ACT 1 -->
        <div id="act1" class="cinematic-act active z-20 px-4 text-center">
            <img src="<?= $logoPath; ?>" alt="Logo SMAN 21 Jakarta" class="w-28 h-28 md:w-36 md:h-36 object-contain mb-6 drop-shadow-lg">
            <h1 class="text-2xl md:text-4xl font-extrabold text-amber-400 tracking-wide mb-2">
                SMA NEGERI 21 JAKARTA
            </h1>
            <p class="text-xs md:text-sm font-medium tracking-[0.25em] text-slate-300 uppercase">
                Where Future Begins
            </p>
        </div>

        <!-- ACT 2 -->
        <div id="act2" class="cinematic-act z-20 px-6 text-center">
            <div class="max-w-2xl">
                <span id="act2Word" class="text-3xl md:text-5xl font-extrabold text-white uppercase block mb-3 leading-tight">
                    PROFIL & IDENTITAS
                </span>
                <p id="act2Sub" class="text-xs md:text-base text-amber-300 font-normal tracking-wide">
                    Mengenal Lebih Dekat Lembaga Pendidikan Berprestasi & Berkarakter
                </p>
            </div>
        </div>

        <!-- ACT 3 -->
        <div id="act3" class="cinematic-act z-20 px-6 text-center">
            <p class="text-xs uppercase tracking-[0.3em] text-amber-400 font-bold mb-2">PROFIL RESMI SEKOLAH</p>
            <h2 class="text-2xl md:text-4xl font-bold text-white mb-6 leading-tight">
                SELAMAT DATANG DI <br>
                <span class="text-amber-400">PROFIL SMAN 21 JAKARTA</span>
            </h2>
            <button onclick="skipIntro()" class="px-8 py-3 rounded-full bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-lg transition-all flex items-center gap-2 mx-auto">
                <span>LIHAT PROFIL SEKOLAH</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- 🧭 GLOBAL HEADER & NAVBAR                                             -->
    <!-- ===================================================================== -->
    <div class="bg-brand-darkNavy text-slate-300 text-xs py-2 border-b border-slate-800 hidden sm:block">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <div class="flex items-center gap-6">
                <span><i class="fa-solid fa-location-dot text-amber-500 mr-2"></i>Pulogadung, Jakarta Timur</span>
                <span><i class="fa-solid fa-phone text-amber-500 mr-2"></i><?= htmlspecialchars($sekolah['telepon']); ?></span>
                <span><i class="fa-solid fa-envelope text-amber-500 mr-2"></i><?= htmlspecialchars($sekolah['email']); ?></span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[11px] text-amber-400 font-semibold bg-amber-500/10 px-2.5 py-0.5 rounded border border-amber-500/20">Akreditasi A</span>
                <a href="<?= htmlspecialchars($sekolah['instagram']); ?>" target="_blank" class="hover:text-amber-400 transition-colors"><i class="fa-brands fa-instagram"></i></a>
                <a href="<?= htmlspecialchars($sekolah['youtube']); ?>" target="_blank" class="hover:text-amber-400 transition-colors"><i class="fa-brands fa-youtube"></i></a>
            </div>
        </div>
    </div>

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

                    <!-- Dropdown Profil (Active) -->
                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-lg text-cyan-600 bg-cyan-50 font-bold border border-cyan-200 flex items-center gap-1.5 transition-all">
                            <span>Profil</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full left-0 w-56 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="index.php" class="block px-3 py-2 rounded-lg text-cyan-600 font-bold bg-cyan-50/50 transition-colors">Tentang Sekolah</a>
                                <a href="sejarah.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Sejarah Sekolah</a>
                                <a href="visi-misi.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Visi & Misi</a>
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

                <button onclick="toggleMobileMenu()" class="lg:hidden p-2 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200 transition-colors">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
            </div>
        </div>

        <div id="mobileMenu" class="hidden lg:hidden bg-white border-t border-slate-200 px-6 py-6 space-y-4 max-h-[80vh] overflow-y-auto">
            <a href="../index.php" class="block text-slate-700 font-semibold text-xs">Beranda</a>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-cyan-600 font-bold tracking-wider">Profil</span>
                <a href="index.php" class="block pl-3 py-1 text-xs text-cyan-600 font-bold">Overview Profil</a>
                <a href="sejarah.php" class="block pl-3 py-1 text-xs text-slate-700">Sejarah</a>
                <a href="visi-misi.php" class="block pl-3 py-1 text-xs text-slate-700">Visi & Misi</a>
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
    <!-- 🏛️ HERO BANNER PROFIL (DENGAN BACKGROUND GAMBAR uploads/banners/bannerprofil.jpg) -->
    <!-- ===================================================================== -->
    <section class="relative bg-brand-darkNavy text-white py-20 lg:py-28 overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-1000 scale-105" 
             style="background-image: url('<?= htmlspecialchars($sekolah['banner_profil']); ?>');">
        </div>
        <div class="absolute inset-0 hero-overlay"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl space-y-4 reveal-section">
                <!-- Breadcrumb -->
                <div class="flex items-center gap-2 text-xs text-amber-400 font-semibold tracking-wide uppercase">
                    <a href="../index.php" class="hover:underline">BERANDA</a>
                    <i class="fa-solid fa-chevron-right text-[9px]"></i>
                    <span class="text-white">PROFIL SEKOLAH</span>
                </div>

                <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight">
                    Profil <span class="text-amber-400">SMAN 21 Jakarta</span>
                </h1>
                
                <p class="text-slate-200 text-sm sm:text-base leading-relaxed">
                    Mengenal sejarah, identitas, visi misi, serta komitmen SMAN 21 Jakarta dalam membentuk generasi unggul, berkarakter Pancasila, dan menguasai teknologi modern.
                </p>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📋 IDENTITAS RESMI SEKOLAH                                           -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-8 items-center">
                
                <div class="lg:col-span-5 reveal-section">
                    <div class="school-card p-8 rounded-2xl bg-gradient-to-br from-brand-darkNavy to-slate-900 text-white relative overflow-hidden shadow-xl">
                        <div class="absolute top-0 right-0 transform translate-x-6 -translate-y-6 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl"></div>
                        <img src="<?= $logoPath; ?>" alt="Logo Sekolah" class="w-20 h-20 object-contain mb-6">
                        <h3 class="text-2xl font-extrabold text-white mb-2"><?= htmlspecialchars($sekolah['nama_sekolah']); ?></h3>
                        <p class="text-xs text-amber-400 font-medium mb-6 uppercase tracking-widest">Where Future Begins</p>
                        
                        <div class="space-y-3 text-xs border-t border-slate-800 pt-6">
                            <div class="flex justify-between py-1 border-b border-slate-800/60">
                                <span class="text-slate-400">NPSN:</span>
                                <span class="font-bold text-white"><?= htmlspecialchars($sekolah['npsn']); ?></span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-800/60">
                                <span class="text-slate-400">Akreditasi:</span>
                                <span class="font-bold text-amber-400"><?= htmlspecialchars($sekolah['akreditasi']); ?></span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-800/60">
                                <span class="text-slate-400">Kurikulum:</span>
                                <span class="font-bold text-white"><?= htmlspecialchars($sekolah['kurikulum']); ?></span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-slate-400">Status Sekolah:</span>
                                <span class="font-bold text-emerald-400">Negeri (Pemerintah DKI Jakarta)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-7 space-y-6 reveal-section delay-200">
                    <span class="text-xs font-bold uppercase tracking-widest text-amber-600 bg-amber-50 px-3 py-1 rounded border border-amber-200">
                        TENTANG SEKOLAH
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy leading-tight">
                        Pusat Keunggulan Pendidikan di Pulogadung, Jakarta Timur
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        SMA Negeri 21 Jakarta adalah salah satu sekolah menengah atas berprestasi tinggi di DKI Jakarta yang terus konsisten melahirkan lulusan berintegritas, kreatif, dan siap bersaing di tingkat nasional maupun internasional.
                    </p>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Dengan dukungan fasilitas belajar modern, lingkungan inklusif tanpa bullying dan senioritas, serta tenaga pendidik tersertifikasi, SMAN 21 Jakarta mengintegrasikan nilai Profil Pelajar Pancasila dalam setiap proses pembelajaran digital.
                    </p>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-4">
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center">
                            <i class="fa-solid fa-users text-amber-600 text-lg mb-1"></i>
                            <span class="text-xs font-bold text-brand-darkNavy block">Inklusif</span>
                            <span class="text-[10px] text-slate-500">Ramah Anak</span>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center">
                            <i class="fa-solid fa-laptop-code text-blue-600 text-lg mb-1"></i>
                            <span class="text-xs font-bold text-brand-darkNavy block">Digital Hub</span>
                            <span class="text-[10px] text-slate-500">Berbasis Teknologi</span>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center col-span-2 sm:col-span-1">
                            <i class="fa-solid fa-leaf text-emerald-600 text-lg mb-1"></i>
                            <span class="text-xs font-bold text-brand-darkNavy block">Adiwiyata</span>
                            <span class="text-[10px] text-slate-500">Berbudaya Lingkungan</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🧭 NAVIGASI CARDS PROFIL SEKOLAH                                     -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-14 reveal-section">
                <span class="text-xs font-bold uppercase tracking-widest text-amber-600">EKSPLORASI PROFIL</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy mt-1">Informasi Detail Profil</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-2">Pilih bagian profil di bawah ini untuk mempelajari sejarah, visi-misi, serta pesan Kepala Sekolah.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                
                <!-- Card Sejarah -->
                <a href="sejarah.php" class="school-card p-8 rounded-2xl bg-white border border-slate-200 flex flex-col justify-between group reveal-section delay-100">
                    <div class="space-y-4">
                        <div class="w-14 h-14 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-landmark"></i>
                        </div>
                        <h3 class="text-lg font-bold text-brand-darkNavy group-hover:text-amber-600 transition-colors">
                            Sejarah Sekolah
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Perjalanan panjang berdirinya SMAN 21 Jakarta dari masa ke masa dalam membentuk generasi berprestasi hingga saat ini.
                        </p>
                    </div>
                    <div class="pt-6 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-amber-600">
                        <span>Baca Sejarah Lengkap</span>
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </a>

                <!-- Card Visi Misi -->
                <a href="visi-misi.php" class="school-card p-8 rounded-2xl bg-white border border-slate-200 flex flex-col justify-between group reveal-section delay-200">
                    <div class="space-y-4">
                        <div class="w-14 h-14 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-compass"></i>
                        </div>
                        <h3 class="text-lg font-bold text-brand-darkNavy group-hover:text-amber-600 transition-colors">
                            Visi & Misi
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Arah dan komitmen strategis SMAN 21 Jakarta dalam mewujudkan lulusan berkarakter Pancasila dan berteknologi.
                        </p>
                    </div>
                    <div class="pt-6 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-blue-600">
                        <span>Lihat Visi & Misi</span>
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </a>

                <!-- Card Sambutan -->
                <a href="sambutan.php" class="school-card p-8 rounded-2xl bg-white border border-slate-200 flex flex-col justify-between group reveal-section delay-300">
                    <div class="space-y-4">
                        <div class="w-14 h-14 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-user-tie"></i>
                        </div>
                        <h3 class="text-lg font-bold text-brand-darkNavy group-hover:text-amber-600 transition-colors">
                            Sambutan Kepala Sekolah
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Pesan resmi pimpinan SMAN 21 Jakarta mengenai arah pengembangan dan pelayanan pendidikan bagi para siswa.
                        </p>
                    </div>
                    <div class="pt-6 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-emerald-600">
                        <span>Baca Sambutan</span>
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </a>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🏫 SECTION PREVIEW SAMBUTAN KEPALA SEKOLAH (DIPERBAIKI RENDER HTML)   -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-12 items-center">
                
                <div class="lg:col-span-4 reveal-section">
                    <div class="school-card p-4 rounded-2xl bg-white text-center border border-slate-200">
                        <div class="relative w-full aspect-[4/5] rounded-xl overflow-hidden mb-4 bg-slate-100">
                            <img src="<?= htmlspecialchars($sambutan['foto']); ?>" 
                                 alt="<?= htmlspecialchars($sambutan['nama']); ?>" 
                                 class="w-full h-full object-cover object-top hover:scale-105 transition-transform duration-500"
                                 onerror="this.onerror=null; this.src='<?= $logoPath; ?>';">
                        </div>
                        <h3 class="text-base font-bold text-brand-darkNavy"><?= htmlspecialchars($sambutan['nama']); ?></h3>
                        <p class="text-xs text-amber-600 font-semibold mt-0.5"><?= htmlspecialchars($sambutan['jabatan']); ?></p>
                    </div>
                </div>

                <div class="lg:col-span-8 space-y-6 reveal-section delay-200">
                    <div class="inline-flex items-center gap-2 text-amber-700 text-xs font-bold uppercase tracking-widest bg-amber-50 px-3 py-1 rounded border border-amber-200">
                        <i class="fa-solid fa-quote-left"></i>
                        <span>SAMBUTAN PIMPINAN</span>
                    </div>

                    <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-darkNavy">
                        <?= htmlspecialchars($sambutan['judul']); ?>
                    </h2>

                    <!-- Container rendering format HTML dari TinyMCE (Tanpa tag mentah htmlspecialchars) -->
                    <div class="sambutan-content font-normal line-clamp-6">
                        <?= $sambutan['isi']; ?>
                    </div>

                    <div class="pt-2">
                        <a href="sambutan.php" class="px-6 py-2.5 rounded-lg bg-brand-darkNavy hover:bg-slate-800 text-white font-semibold text-xs transition-all inline-flex items-center gap-2">
                            <span>Baca Selengkapnya</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
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
                    Tanyakan Seputar Profil SMAN 21 Jakarta
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                    Sistem Pusat Informasi SMAN 21 siap memberikan jawaban seputar Sejarah, Akreditasi, Visi & Misi, serta pimpinan sekolah secara instant.
                </p>
                <div class="pt-2">
                    <button onclick="toggleAiPanel()" class="px-7 py-3 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-lg transition-all inline-flex items-center gap-2.5 uppercase tracking-wider">
                        <i class="fa-solid fa-comments text-sm"></i>
                        <span>TANYAKAN PROFIL SEKOLAH</span>
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
                        <li><a href="index.php" class="text-amber-400 font-bold">Tentang Sekolah</a></li>
                        <li><a href="sejarah.php" class="hover:text-amber-400 transition-colors">Sejarah Sekolah</a></li>
                        <li><a href="visi-misi.php" class="hover:text-amber-400 transition-colors">Visi & Misi</a></li>
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
                    <button onclick="replayIntro()" class="text-[10px] text-slate-500 hover:text-amber-400 underline block pt-2">
                        🎬 Putar Ulang Intro Website
                    </button>
                </div>

            </div>

            <div class="pt-8 border-t border-slate-800 flex flex-col sm:flex-row justify-between items-center text-[10px] text-slate-500">
                <p>&copy; <?= date('Y'); ?> SMA Negeri 21 Jakarta. Hak Cipta Dilindungi Undang-Undang.</p>
                <p class="mt-2 sm:mt-0">Portal Resmi Informasi SMA Negeri 21 Jakarta</p>
            </div>
        </div>
    </footer>

    <!-- ===================================================================== -->
    <!-- 💬 AI ASSISTANT DRAWER PANEL (DISESUAIKAN SAMA KEK INDEX.PHP)         -->
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
                <p>Ada yang bisa saya bantu terkait SPMB/PPDB, Sejarah, Visi Misi, Guru, atau Sambutan Kepala SMAN 21 Jakarta?</p>
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

    <!-- Floating Chat Button (Disamakan dengan index.php) -->
    <button onclick="toggleAiPanel()" class="fixed bottom-6 right-6 z-[9000] w-14 h-14 rounded-full bg-cyan-600 hover:bg-cyan-500 text-white shadow-2xl border-2 border-white flex items-center justify-center text-xl transition-all hover:scale-110 active:scale-95 group">
        <i class="fa-solid fa-comments"></i>
        <span class="absolute right-full mr-3 bg-brand-darkNavy text-white text-[10px] font-bold px-2.5 py-1 rounded shadow whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity">
            Tanyakan AI Assistant
        </span>
    </button>

    <!-- ===================================================================== -->
    <!-- 📜 JAVASCRIPT LOGIC & SCROLL REVEAL OBSERVER                          -->
    <!-- ===================================================================== -->
    <script>
        // 1. SCROLL REVEAL ANIMATION OBSERVER
        document.addEventListener('DOMContentLoaded', () => {
            const observerOptions = {
                root: null,
                rootMargin: '0px 0px -50px 0px',
                threshold: 0.12
            };

            const revealObserver = new IntersectionObserver((entries, observer) => {
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

        // 2. INTRO CINEMATIC LOGIC
        const acts = ['act1', 'act2', 'act3'];
        const wordList = [
            { word: "PROFIL & IDENTITAS", sub: "Mengenal Lebih Dekat Lembaga Pendidikan Berprestasi & Berkarakter" },
            { word: "UNGGUL & BERBUDAYA", sub: "Profil Pelajar Pancasila Berbasis Teknologi Modern" },
            { word: "WHERE FUTURE BEGINS", sub: "Membentuk Pemimpin Masa Depan Berintegritas" }
        ];

        function startCinematicIntro() {
            if (localStorage.getItem('s21_intro_seen') === 'true') {
                const intro = document.getElementById('cinematicIntro');
                if (intro) intro.style.display = 'none';
                return;
            }

            let wordIdx = 0;
            setTimeout(() => {
                setAct('act2');
                const wordElem = document.getElementById('act2Word');
                const subElem = document.getElementById('act2Sub');

                let wordInterval = setInterval(() => {
                    wordIdx++;
                    if (wordIdx < wordList.length) {
                        if (wordElem) wordElem.innerText = wordList[wordIdx].word;
                        if (subElem) subElem.innerText = wordList[wordIdx].sub;
                    } else {
                        clearInterval(wordInterval);
                        setTimeout(() => { setAct('act3'); }, 1000);
                    }
                }, 1400);

            }, 2200);
        }

        function setAct(actId) {
            acts.forEach(a => {
                const el = document.getElementById(a);
                if (el) el.classList.remove('active');
            });
            const activeEl = document.getElementById(actId);
            if (activeEl) activeEl.classList.add('active');
        }

        function skipIntro() {
            localStorage.setItem('s21_intro_seen', 'true');
            const intro = document.getElementById('cinematicIntro');
            if (intro) {
                intro.style.opacity = '0';
                setTimeout(() => { intro.style.display = 'none'; }, 700);
            }
        }

        function replayIntro() {
            localStorage.removeItem('s21_intro_seen');
            location.reload();
        }

        window.addEventListener('DOMContentLoaded', startCinematicIntro);

        // 3. MOBILE MENU TOGGLE
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            if (menu) menu.classList.toggle('hidden');
        }

        // 4. AI ASSISTANT PANEL TOGGLE & CHAT AJAX (DISAMAKAN DENGAN INDEX.PHP)
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
            
            // Render user bubble
            const userMsg = document.createElement('div');
            userMsg.className = 'p-3 rounded-xl bg-cyan-600 text-white ml-auto max-w-[85%] text-xs font-medium shadow-sm';
            userMsg.innerText = q;
            chatBox.appendChild(userMsg);

            input.value = '';
            chatBox.scrollTop = chatBox.scrollHeight;

            // Render loading indicator
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
<?php
// =============================================================================
// KONFIGURASI DATABASE & KONEKSI BYETHOST (SMAN 21 JAKARTA)
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
} catch (Throwable $e) {
    $conn = null;
}

// =============================================================================
// ENDPOINT AJAX UNTUK S21 AI ASSISTANT (CONTEXT: SEJARAH SEKOLAH)
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
    $sumberInfo = "Basis Informasi Sejarah SMAN 21 Jakarta";
    $relatedLink = "";

    if ($conn) {
        try {
            $stmtAiCfg = $conn->query("SELECT pesan_tidak_tersedia FROM ai_pengaturan LIMIT 1");
            $aiCfg = $stmtAiCfg ? $stmtAiCfg->fetch() : null;
            $fallbackMsg = $aiCfg['pesan_tidak_tersedia'] ?? "Maaf, informasi tersebut belum tersedia dalam basis data resmi SMAN 21 Jakarta.";

            $stmtKnw = $conn->prepare("SELECT * FROM ai_knowledge WHERE status_aktif = 1 ORDER BY prioritas DESC");
            $stmtKnw->execute();
            $knowledges = $stmtKnw->fetchAll() ?: [];

            $matched = false;
            $qLower = strtolower($userQuery);

            foreach ($knowledges as $k) {
                $kwString = (string)($k['keyword'] ?? '');
                $keywords = array_filter(array_map('trim', explode(',', strtolower($kwString))));
                $pertanyaanLower = strtolower((string)($k['pertanyaan'] ?? ''));

                if (!empty($keywords)) {
                    foreach ($keywords as $kw) {
                        if (!empty($kw) && strpos($qLower, $kw) !== false) {
                            $responseAnswer = $k['jawaban'] ?? '';
                            $sumberInfo = !empty($k['sumber_informasi']) ? $k['sumber_informasi'] : "Database AI SMAN 21 Jakarta";
                            $matched = true;
                            break;
                        }
                    }
                }

                if (!$matched && !empty($pertanyaanLower) && (strpos($qLower, $pertanyaanLower) !== false || strpos($pertanyaanLower, $qLower) !== false)) {
                    $responseAnswer = $k['jawaban'] ?? '';
                    $sumberInfo = !empty($k['sumber_informasi']) ? $k['sumber_informasi'] : "Database AI SMAN 21 Jakarta";
                    $matched = true;
                    break;
                }

                if ($matched) break;
            }

            if (!$matched) {
                if (strpos($qLower, 'sejarah') !== false || strpos($qLower, 'berdiri') !== false || strpos($qLower, 'tahun') !== false || strpos($qLower, 'asal') !== false) {
                    $responseAnswer = "SMA Negeri 21 Jakarta didirikan pada tahun 1966 di Kawasan Kayu Putih, Pulogadung, Jakarta Timur. Berawal dari kelas filial, SMAN 21 berkembang pesat menjadi sekolah unggulan berakreditasi A dengan segudang prestasi nasional serta fasilitas digital terdepan.";
                    $sumberInfo = "Arsip Sejarah Sekolah";
                    $relatedLink = "profil/sejarah.php";
                    $matched = true;
                } elseif (strpos($qLower, 'kepala sekolah') !== false || strpos($qLower, 'kepsek') !== false) {
                    $responseAnswer = "Saat ini SMAN 21 Jakarta dipimpin oleh Ibu Mutia, M.Pd. Sepanjang sejarahnya, SMAN 21 terus berinovasi mengintegrasikan pendidikan berkarakter Pancasila dan sains berteknologi modern.";
                    $sumberInfo = "Profil Kepemimpinan";
                    $relatedLink = "profil/sambutan.php";
                    $matched = true;
                }
            }

            if (!$matched) {
                $responseAnswer = $fallbackMsg;
                $sumberInfo = "Sistem Verifikasi SMAN 21";
            }
        } catch (Throwable $e) {
            if (strpos(strtolower($userQuery), 'sejarah') !== false || strpos(strtolower($userQuery), 'berdiri') !== false) {
                $responseAnswer = "SMAN 21 Jakarta berdiri sejak tahun 1966 dan terus tumbuh menjadi salah satu sekolah menengah atas unggulan di DKI Jakarta yang mencetak lulusan berprestasi tinggi di berbagai PTN ternama.";
                $sumberInfo = "Sejarah SMAN 21";
                $relatedLink = "profil/sejarah.php";
            } else {
                $responseAnswer = "Maaf, informasi tersebut belum tersedia dalam basis data resmi SMAN 21 Jakarta. Silakan ajukan pertanyaan seputar Sejarah, Visi Misi, atau Akademik sekolah.";
                $sumberInfo = "Sistem SMAN 21";
            }
        }
    } else {
        if (strpos(strtolower($userQuery), 'sejarah') !== false || strpos(strtolower($userQuery), 'berdiri') !== false) {
            $responseAnswer = "SMAN 21 Jakarta berdiri sejak tahun 1966 dan terus tumbuh menjadi salah satu sekolah menengah atas unggulan di DKI Jakarta yang mencetak lulusan berprestasi tinggi di berbagai PTN ternama.";
            $sumberInfo = "Sejarah SMAN 21";
            $relatedLink = "profil/sejarah.php";
        } else {
            $responseAnswer = "Maaf, informasi tersebut belum tersedia dalam basis data resmi SMAN 21 Jakarta. Silakan ajukan pertanyaan seputar Sejarah, Visi Misi, atau Akademik sekolah.";
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
// FETCH DATA PROFIL & SEJARAH SEKOLAH
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
    'gedung_bg' => '../uploads/gedung.jpg'
];

if ($conn) {
    try {
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
    } catch (Throwable $e) {
        // Abaikan error jika tabel pengaturan_sekolah belum ada
    }
}

// Dynamic Fetching Sejarah
$sejarahData = [
    'judul' => 'Jejak Langkah & Perkembangan SMAN 21 Jakarta',
    'tahun_berdiri' => '1966',
    'ringkasan' => 'Perjalanan panjang lebih dari 5 dekade dalam mengabdi bagi kemajuan pendidikan nasional, dari awal pendirian hingga era transformasi digital modern.',
    'isi_lengkap' => 'SMA Negeri 21 Jakarta didirikan secara resmi pada tahun 1966 di kawasan strategis Kayu Putih, Pulogadung, Jakarta Timur. Bermula dari semangat tinggi para tokoh pendidikan dan masyarakat setempat untuk menghadirkan sarana belajar yang berkualitas, SMAN 21 tumbuh dari bangunan sederhana menjadi salah satu SMA Negeri unggulan di DKI Jakarta.<br><br>Seiring berjalannya waktu, SMAN 21 Jakarta tidak hanya memperluas sarana dan prasarana penunjang pembelajaran, tetapi juga secara konsisten mencetak prestasi akademik dan non-akademik di tingkat regional, nasional, hingga internasional. Komitmen terhadap kedisiplinan, penanaman karakter Pelajar Pancasila, dan integrasi teknologi informasi menjadi pilar utama transformasi SMAN 21 hingga hari ini.'
];

if ($conn) {
    try {
        $stmtSej = $conn->query("SELECT * FROM sejarah_sekolah ORDER BY id DESC LIMIT 1");
        if ($stmtSej && $rowSej = $stmtSej->fetch()) {
            if (!empty($rowSej['judul'])) $sejarahData['judul'] = $rowSej['judul'];
            if (!empty($rowSej['tahun_berdiri'])) $sejarahData['tahun_berdiri'] = $rowSej['tahun_berdiri'];
            if (!empty($rowSej['ringkasan'])) $sejarahData['ringkasan'] = $rowSej['ringkasan'];
            if (!empty($rowSej['isi_lengkap'])) $sejarahData['isi_lengkap'] = $rowSej['isi_lengkap'];
        }
    } catch (Throwable $e) {
        // Abaikan error jika tabel sejarah_sekolah belum ada
    }
}

// Timeline Milestone Sejarah
$milestones = [
    [
        'tahun' => '1966',
        'judul' => 'Awal Berdirinya Sekolah',
        'deskripsi' => '1 Agustus 1966: Sekolah ini semula didirikan dengan nama SMA Yayasan Senen yang berlokasi di Jalan Gunung Sahari IV No. 14, Jakarta Pusat. Karena berbagai kendala operasional, Kantor Daerah Departemen Pendidikan & Kebudayaan (P&K) DKI Jakarta mengambil alih tata kelolanya dan resmi mengubah statusnya menjadi Sekolah Negeri terhitung sejak tanggal tersebut.'
    ],
    [
        'tahun' => '1972',
        'judul' => 'Perpindahan Lokasi ke Jakarta Timur',
        'deskripsi' => '1 Januari 1972: Atas arahan dari Gubernur DKI Jakarta saat itu, SMA Negeri 21 Jakarta resmi menempati gedung baru di lokasi yang ditempati saat ini, yaitu Jalan Tanah Mas Raya No. 1, Kayu Putih, Pulo Gadung, Jakarta Timur. Langkah ini menandai babak baru sekolah dalam melayani siswa di area timur Jakarta.'
    ],
    [
        'tahun' => '1994 - 1997',
        'judul' => 'Era Renovasi Total Gedung',
        'deskripsi' => '7 November 1994: Proses pembangunan dan renovasi besar-besaran dimulai. Demi kelancaran proyek, aktivitas belajar mengajar sempat menumpang; siswa kelas XII belajar pagi hari di SMPN 99, sedangkan kelas X dan XI menumpang di SDN Jalan Kunci.<br><br>Tahun 1997: Pembangunan gedung baru rampung sepenuhnya. Memasuki tahun ajaran 1997/1998, seluruh siswa kelas X, XI, dan XII sudah bisa menikmati fasilitas gedung baru secara bersamaan pada waktu pagi hari.'
    ],
    [
        'tahun' => 'Era 2000-an',
        'judul' => 'Status RSBI (Rintisan Sekolah Bertaraf Internasional)',
        'deskripsi' => 'Pada era ini, SMAN 21 Jakarta berhasil menembus jajaran sekolah elit di Jakarta dengan meraih status Rintisan Sekolah Bertaraf Internasional (RSBI). Hal ini mendongkrak mutu akademik dan fasilitas teknologi sekolah secara masif, hingga melahirkan tradisi lulusan yang bersaing ketat di perguruan tinggi top nasional maupun luar negeri. Status RSBI ini berjalan hingga program tersebut dihapus serentak oleh MK pada tahun 2013.'
    ],
    [
        'tahun' => '2026',
        'judul' => 'Sekolah Penggerak & Model Koding/AI',
        'deskripsi' => '12 Maret 2026: Melalui SK Dirjen Pauddikdasmen Nomor 0010 Tahun 2026, SMAN 21 Jakarta resmi ditetapkan sebagai Sekolah Model Implementasi Pembelajaran Mendalam (PM) serta Koding & Kecerdasan Artifisial (KKA).<br><br>Saat ini SMAN 21 aktif beroperasi sebagai Sekolah Penggerak berakreditasi A yang mengawinkan kurikulum modern dengan adaptasi teknologi masa depan.'
    ]
];

$logoPath = $sekolah['logo'];
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sejarah Sekolah — SMA NEGERI 21 JAKARTA</title>
    <meta name="description" content="Sejarah dan Jejak Perjalanan SMA Negeri 21 Jakarta. Dedikasi Pendidikan Sejak 1966 dalam Mencetak Generasi Berprestasi dan Berkarakter.">
    
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
                            blue: '#1E40AF',
                            gold: '#D97706',
                            goldHover: '#B45309',
                            lightGold: '#FEF3C7',
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
            background: linear-gradient(135deg, rgba(6, 18, 36, 0.92) 0%, rgba(11, 30, 54, 0.85) 60%, rgba(15, 23, 42, 0.90) 100%);
        }

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
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #D97706; }
    </style>
</head>
<body class="relative min-h-screen text-slate-800 bg-slate-50 selection:bg-amber-500 selection:text-white max-w-full overflow-x-hidden">

    <!-- ===================================================================== -->
    <!-- 🎬 CINEMATIC OPENING OVERLAY (SEJARAH STORYLINE)                      -->
    <!-- ===================================================================== -->
    <div id="cinematicIntro" class="fixed inset-0 z-[9999] bg-brand-darkNavy text-white overflow-hidden transition-opacity duration-700">
        <button onclick="skipIntro()" class="absolute top-6 right-6 z-[10000] px-4 py-2 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 text-xs font-semibold tracking-wider text-slate-200 transition-all flex items-center gap-2">
            <span>LEWATI INTRO</span>
            <i class="fa-solid fa-forward text-[10px]"></i>
        </button>

        <div class="absolute inset-0 bg-gradient-to-t from-brand-darkNavy via-brand-navy/80 to-brand-darkNavy z-10"></div>
        <div id="cinematicBg" class="absolute inset-0 bg-cover bg-center transition-all duration-1000 opacity-25 scale-105" style="background-image: url('<?= htmlspecialchars($sekolah['gedung_bg']); ?>'), url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=1600&auto=format&fit=crop');"></div>

        <!-- ACT 1 -->
        <div id="act1" class="cinematic-act active z-20 px-4 text-center">
            <img src="<?= $logoPath; ?>" alt="Logo SMAN 21 Jakarta" class="w-28 h-28 md:w-36 md:h-36 object-contain mb-6 drop-shadow-lg">
            <h1 class="text-2xl md:text-4xl font-extrabold text-amber-400 tracking-wide mb-2">
                SMA NEGERI 21 JAKARTA
            </h1>
            <p class="text-xs md:text-sm font-medium tracking-[0.25em] text-slate-300 uppercase">
                Sejak Tahun 1966 • Melayani & Menginspirasi
            </p>
        </div>

        <!-- ACT 2 -->
        <div id="act2" class="cinematic-act z-20 px-6 text-center">
            <div class="max-w-2xl">
                <span id="act2Word" class="text-3xl md:text-5xl font-extrabold text-white uppercase block mb-3 leading-tight">
                    60 TAHUN DEDIKASI
                </span>
                <p id="act2Sub" class="text-xs md:text-base text-amber-300 font-normal tracking-wide">
                    Dari Generasi ke Generasi Mencetak Pemimpin Bangsa
                </p>
            </div>
        </div>

        <!-- ACT 3 -->
        <div id="act3" class="cinematic-act z-20 px-6 text-center">
            <p class="text-xs uppercase tracking-[0.3em] text-amber-400 font-bold mb-2">PROFIL & SEJARAH</p>
            <h2 class="text-2xl md:text-4xl font-bold text-white mb-6 leading-tight">
                JELAJAHI JEJAK LANGKAH <br>
                <span class="text-amber-400">SMAN 21 JAKARTA</span>
            </h2>
            <button onclick="skipIntro()" class="px-8 py-3 rounded-full bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-lg transition-all flex items-center gap-2 mx-auto">
                <span>BACA SEJARAH LENGKAP</span>
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

    <!-- Main Header -->
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                
                <a href="../index.php" class="flex items-center gap-3.5 group">
                    <img src="<?= $logoPath; ?>" alt="SMAN 21 Jakarta Logo" class="h-11 w-11 object-contain group-hover:scale-105 transition-transform">
                    <div>
                        <span class="text-base sm:text-lg font-bold text-brand-darkNavy block leading-tight group-hover:text-amber-600 transition-colors">
                            SMAN 21 JAKARTA
                        </span>
                        <span class="text-[10px] tracking-wider text-slate-500 block uppercase font-semibold">
                            Official School Portal
                        </span>
                    </div>
                </a>

                <!-- Desktop Navigation -->
                <nav class="hidden lg:flex items-center gap-1 font-semibold text-xs text-slate-700">
                    <a href="../index.php" class="px-3.5 py-2 rounded-lg hover:text-amber-600 hover:bg-slate-100 transition-all">Beranda</a>

                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-lg text-amber-600 bg-amber-50 font-bold border border-amber-200 flex items-center gap-1.5 transition-all">
                            <span>Profil</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full left-0 w-56 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-amber-600 transition-colors">Tentang Sekolah</a>
                                <a href="sejarah.php" class="block px-3 py-2 rounded-lg bg-amber-50 text-amber-600 font-bold transition-colors">Sejarah Sekolah</a>
                                <a href="visi-misi.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-amber-600 transition-colors">Visi & Misi</a>
                                <a href="sambutan.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-amber-600 transition-colors">Sambutan Kepala Sekolah</a>
                            </div>
                        </div>
                    </div>

                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-lg hover:text-amber-600 hover:bg-slate-100 flex items-center gap-1.5 transition-all">
                            <span>Akademik</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full left-0 w-60 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="../akademik/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-amber-600 transition-colors">Overview Akademik</a>
                                <a href="../akademik/program.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-amber-600 transition-colors">Program Pendidikan</a>
                                <a href="../akademik/guru.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-amber-600 transition-colors">Daftar Guru & Staf</a>
                                <a href="../akademik/pemeringkatan.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-amber-600 transition-colors">Pemeringkatan Jurusan</a>
                                <a href="../akademik/keterserapan-ptn.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-amber-600 transition-colors">Keterserapan PTN</a>
                            </div>
                        </div>
                    </div>

                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-lg hover:text-amber-600 hover:bg-slate-100 flex items-center gap-1.5 transition-all">
                            <span>Kegiatan</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full left-0 w-52 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="../kegiatan/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-amber-600 transition-colors">Semua Kegiatan</a>
                                <a href="../kegiatan/ekstrakurikuler.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-amber-600 transition-colors">Ekstrakurikuler</a>
                            </div>
                        </div>
                    </div>

                    <a href="../prestasi/index.php" class="px-3.5 py-2 rounded-lg hover:text-amber-600 hover:bg-slate-100 transition-all">Prestasi</a>
                    <a href="../fasilitas/index.php" class="px-3.5 py-2 rounded-lg hover:text-amber-600 hover:bg-slate-100 transition-all">Fasilitas</a>

                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-lg hover:text-amber-600 hover:bg-slate-100 flex items-center gap-1.5 transition-all">
                            <span>Informasi</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full right-0 w-52 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="../informasi/berita.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-amber-600 transition-colors">Berita Sekolah</a>
                                <a href="../informasi/agenda.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-amber-600 transition-colors">Agenda & Kalender</a>
                                <a href="../informasi/ppdb.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-amber-600 transition-colors">PPDB Info</a>
                                <a href="../gallery/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-amber-600 transition-colors">Galeri Foto</a>
                                <a href="../kontak/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-amber-600 transition-colors">Kontak & Lokasi</a>
                            </div>
                        </div>
                    </div>

                    <a href="../informasi/ppdb.php" class="ml-2 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs flex items-center gap-2 shadow-sm transition-all">
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
            <a href="../index.php" class="block font-bold text-slate-700">Beranda</a>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-amber-600 font-bold tracking-wider">Profil</span>
                <a href="index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Profil</a>
                <a href="sejarah.php" class="block pl-3 py-1 text-xs font-bold text-amber-600">Sejarah Sekolah</a>
                <a href="visi-misi.php" class="block pl-3 py-1 text-xs text-slate-700">Visi & Misi</a>
                <a href="sambutan.php" class="block pl-3 py-1 text-xs text-slate-700">Sambutan Kepala Sekolah</a>
            </div>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Akademik</span>
                <a href="../akademik/index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Akademik</a>
                <a href="../akademik/guru.php" class="block pl-3 py-1 text-xs text-slate-700">Daftar Guru</a>
                <a href="../akademik/keterserapan-ptn.php" class="block pl-3 py-1 text-xs text-slate-700">Keterserapan PTN</a>
            </div>
            <a href="../prestasi/index.php" class="block text-xs font-semibold text-slate-700">Prestasi</a>
            <a href="../kegiatan/index.php" class="block text-xs font-semibold text-slate-700">Kegiatan & Ekskul</a>
            <a href="../informasi/ppdb.php" class="block text-xs font-semibold text-slate-700">PPDB Info</a>
            <a href="../kontak/index.php" class="block text-xs font-semibold text-slate-700">Kontak</a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 🏛️ HERO BREADCRUMB & PAGE HEADER                                    -->
    <!-- ===================================================================== -->
    <section class="relative bg-brand-darkNavy text-white py-16 lg:py-20 overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-20" style="background-image: url('<?= htmlspecialchars($sekolah['gedung_bg']); ?>');"></div>
        <div class="absolute inset-0 hero-overlay"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center reveal-section">
            
            <nav class="inline-flex items-center gap-2 text-xs text-slate-300 bg-white/10 px-4 py-1.5 rounded-full border border-white/20 backdrop-blur-sm mb-4">
                <a href="../index.php" class="hover:text-amber-400 transition-colors"><i class="fa-solid fa-house"></i> Beranda</a>
                <span class="text-slate-500">/</span>
                <a href="index.php" class="hover:text-amber-400 transition-colors">Profil</a>
                <span class="text-slate-500">/</span>
                <span class="text-amber-400 font-semibold">Sejarah Sekolah</span>
            </nav>

            <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight">
                Sejarah & Perjalanan Sekolah
            </h1>
            <p class="text-xs sm:text-sm text-slate-300 max-w-2xl mx-auto mt-3 leading-relaxed font-normal">
                Mengenal rekam jejak, sejarah berdiri, serta perjalanan transformasi SMAN 21 Jakarta dari masa ke masa dalam mencetak generasi unggul bangsa.
            </p>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📜 MAIN CONTENT: HISTORIAL STORY                                       -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-12 items-center">
                
                <div class="lg:col-span-5 reveal-section">
                    <div class="relative max-w-md mx-auto">
                        <div class="absolute -inset-3 rounded-2xl bg-gradient-to-tr from-amber-500 to-blue-900 opacity-20 blur-md"></div>
                        <div class="school-card p-4 rounded-2xl relative z-10 bg-white text-center">
                            <div class="relative w-full aspect-[4/3] rounded-xl overflow-hidden mb-4 bg-slate-100 border border-slate-200">
                                <img src="<?= htmlspecialchars($sekolah['gedung_bg']); ?>" 
                                     alt="Gedung SMAN 21 Jakarta" 
                                     class="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
                                     onerror="this.onerror=null; this.src='../uploads/logo/sman21jakarta.png';">
                            </div>
                            <span class="inline-block px-3 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-bold uppercase tracking-wider mb-2">
                                Berdiri Sejak Tahun <?= htmlspecialchars($sejarahData['tahun_berdiri']); ?>
                            </span>
                            <h3 class="text-sm font-bold text-brand-darkNavy">SMAN 21 Jakarta</h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">Pulogadung, Jakarta Timur</p>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-7 space-y-6 reveal-section delay-200">
                    <div class="inline-flex items-center gap-2 text-amber-700 text-xs font-bold uppercase tracking-widest bg-amber-50 px-3 py-1 rounded border border-amber-200">
                        <i class="fa-solid fa-landmark"></i>
                        <span>REKAM JEJAK DEDIKASI</span>
                    </div>

                    <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy leading-tight">
                        <?= htmlspecialchars($sejarahData['judul']); ?>
                    </h2>

                    <p class="text-sm font-semibold text-amber-700 bg-amber-50/60 p-4 rounded-xl border-l-4 border-amber-500 italic leading-relaxed">
                        "<?= htmlspecialchars($sejarahData['ringkasan']); ?>"
                    </p>

                    <div class="text-slate-600 text-xs sm:text-sm leading-relaxed space-y-4 font-normal">
                        <?= $sejarahData['isi_lengkap']; ?>
                    </div>

                    <div class="pt-4 grid grid-cols-3 gap-4 border-t border-slate-100 text-center">
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                            <span class="text-xl sm:text-2xl font-extrabold text-brand-darkNavy block">60+</span>
                            <span class="text-[10px] text-slate-500 font-medium">Tahun Mengabdi</span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                            <span class="text-xl sm:text-2xl font-extrabold text-amber-600 block">15.000+</span>
                            <span class="text-[10px] text-slate-500 font-medium">Alumni Dihasilkan</span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                            <span class="text-xl sm:text-2xl font-extrabold text-emerald-600 block">Akreditasi A</span>
                            <span class="text-[10px] text-slate-500 font-medium">Predikat Unggul</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- ⏳ TIMELINE PERJALANAN / MILESTONES                                  -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-slate-50 border-b border-slate-200 overflow-hidden">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-2xl mx-auto mb-16 space-y-2 reveal-section">
                <span class="text-xs font-bold uppercase tracking-widest text-amber-600">LINIMASA SEJARAH</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy">Milestone Penting Sekolah</h2>
                <p class="text-xs sm:text-sm text-slate-500">Tahapan penting perkembangan SMAN 21 Jakarta dalam membangun pilar pendidikan masa depan.</p>
            </div>

            <div class="relative border-l-2 border-amber-500/40 ml-4 md:ml-32 space-y-10">
                <?php $mIndex = 1; foreach ($milestones as $ms): ?>
                <div class="relative pl-8 md:pl-12 reveal-section delay-<?= $mIndex * 100; ?>">
                    
                    <div class="hidden md:flex absolute -left-32 top-0 w-24 justify-end">
                        <span class="px-3 py-1 rounded-lg bg-brand-darkNavy text-amber-400 font-extrabold text-xs shadow-sm">
                            <?= htmlspecialchars($ms['tahun']); ?>
                        </span>
                    </div>

                    <div class="absolute -left-[17px] top-1.5 w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold text-xs ring-4 ring-amber-100 shadow-md">
                        <i class="fa-solid fa-check text-[10px]"></i>
                    </div>

                    <div class="school-card p-6 rounded-xl bg-white border border-slate-200 space-y-2">
                        <div class="flex items-center justify-between md:justify-start gap-3">
                            <span class="md:hidden px-2.5 py-0.5 rounded bg-amber-100 text-amber-800 font-bold text-[10px]">
                                <?= htmlspecialchars($ms['tahun']); ?>
                            </span>
                            <h3 class="text-sm font-bold text-brand-darkNavy"><?= htmlspecialchars($ms['judul']); ?></h3>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed font-normal">
                            <?= $ms['deskripsi']; ?>
                        </p>
                    </div>

                </div>
                <?php $mIndex++; endforeach; ?>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 💡 PILAR KEUNGGULAN SMAN 21 JAKARTA                                  -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-14 space-y-2 reveal-section">
                <span class="text-xs font-bold uppercase tracking-widest text-amber-600">NILAI UTAMA</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-darkNavy">Pilar Budaya & Karakter</h2>
                <p class="text-xs sm:text-sm text-slate-500">Nilai-nilai luhur yang terus diwariskan kepada seluruh civitas akademika SMAN 21 Jakarta.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                
                <div class="school-card p-8 rounded-xl bg-white border border-slate-200 text-center space-y-4 reveal-section delay-100">
                    <div class="w-14 h-14 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center mx-auto text-2xl">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <h3 class="text-base font-bold text-brand-darkNavy">Akademik Unggul</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Mendorong standar pembelajaran tinggi yang adaptif dengan perkembangan ilmu pengetahuan dan persaingan perguruan tinggi.
                    </p>
                </div>

                <div class="school-card p-8 rounded-xl bg-white border border-slate-200 text-center space-y-4 reveal-section delay-200">
                    <div class="w-14 h-14 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center mx-auto text-2xl">
                        <i class="fa-solid fa-heart-pulse"></i>
                    </div>
                    <h3 class="text-base font-bold text-brand-darkNavy">Karakter Pancasila</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Menanamkan etika, integritas, toleransi, empati sosial, serta semangat kebangsaan yang kokoh dalam setiap aktivitas.
                    </p>
                </div>

                <div class="school-card p-8 rounded-xl bg-white border border-slate-200 text-center space-y-4 reveal-section delay-300">
                    <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto text-2xl">
                        <i class="fa-solid fa-laptop-code"></i>
                    </div>
                    <h3 class="text-base font-bold text-brand-darkNavy">Inovasi Digital</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Mengintegrasikan teknologi informasi modern dan kecerdasan buatan dalam mendukung ekosistem smart campus yang efektif.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🤖 BANNER AI CHAT ASSISTANT                                           -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-slate-900 text-white relative overflow-hidden">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 reveal-section">
            <div class="p-8 sm:p-12 rounded-2xl bg-gradient-to-r from-brand-darkNavy to-slate-800 border border-slate-700 text-center space-y-5 shadow-2xl">
                <div class="w-14 h-14 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center mx-auto text-2xl border border-amber-500/30">
                    <i class="fa-solid fa-landmark"></i>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white">
                    Ingin Tahu Lebih Banyak Tentang Sejarah SMAN 21?
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                    Tanyakan langsung kepada Layanan Informasi Digital SMAN 21 mengenai rekam jejak, kepala sekolah dari masa ke masa, atau prestasi bersejarah sekolah.
                </p>
                <div class="pt-2">
                    <button onclick="toggleAiPanel()" class="px-7 py-3 rounded-lg bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-lg transition-all inline-flex items-center gap-2.5 uppercase tracking-wider">
                        <i class="fa-solid fa-comments text-sm"></i>
                        <span>TANYAKAN SEJARAH SEKOLAH</span>
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
                    <h4 class="text-white font-bold mb-3">Navigasi Utama</h4>
                    <ul class="space-y-2 text-[11px]">
                        <li><a href="index.php" class="hover:text-amber-400 transition-colors">Profil Sekolah</a></li>
                        <li><a href="sejarah.php" class="text-amber-400 font-bold">Sejarah Sekolah</a></li>
                        <li><a href="visi-misi.php" class="hover:text-amber-400 transition-colors">Visi & Misi</a></li>
                        <li><a href="sambutan.php" class="hover:text-amber-400 transition-colors">Sambutan Kepala Sekolah</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-bold mb-3">Layanan Informasi</h4>
                    <ul class="space-y-2 text-[11px]">
                        <li><a href="../informasi/berita.php" class="hover:text-amber-400 transition-colors">Berita Terkini</a></li>
                        <li><a href="../informasi/agenda.php" class="hover:text-amber-400 transition-colors">Agenda Kalender</a></li>
                        <li><a href="../gallery/index.php" class="hover:text-amber-400 transition-colors">Galeri Media</a></li>
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
    <!-- 🤖 FLOATING AI TOGGLE & CHAT PANEL                                    -->
    <!-- ===================================================================== -->
    <div class="fixed right-4 sm:right-6 bottom-4 sm:bottom-6 z-[990]">
        
        <button id="aiToggleBtn" onclick="toggleAiPanel()" class="flex items-center gap-2.5 px-4 py-3 rounded-full bg-brand-darkNavy text-white border border-amber-500/40 shadow-xl hover:bg-slate-800 transition-all">
            <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></div>
            <span class="text-xs font-bold tracking-wide">Pusat Informasi SMAN 21</span>
            <i class="fa-solid fa-headset text-amber-400 ml-1"></i>
        </button>

        <div id="aiPanel" class="hidden fixed bottom-16 sm:bottom-20 right-3 sm:right-6 w-[calc(100vw-1.5rem)] sm:w-[360px] max-w-[360px] h-[490px] bg-white rounded-2xl border border-slate-300 shadow-2xl flex flex-col overflow-hidden z-[995] transition-all">
            
            <div class="p-4 bg-brand-darkNavy text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center border border-amber-500/30">
                        <i class="fa-solid fa-landmark text-xs"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold">Layanan Informasi Sejarah</h4>
                        <span class="text-[9px] text-emerald-400 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Context: Sejarah SMAN 21
                        </span>
                    </div>
                </div>
                <button onclick="toggleAiPanel()" class="w-7 h-7 rounded-full bg-white/10 text-slate-300 hover:text-white flex items-center justify-center text-xs">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div id="aiChatBody" class="flex-1 p-4 overflow-y-auto space-y-3.5 text-xs bg-slate-50">
                
                <div class="flex items-start gap-2">
                    <div class="w-6 h-6 rounded-full bg-brand-darkNavy text-amber-400 flex items-center justify-center text-[10px] flex-shrink-0">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <div class="p-3 rounded-xl bg-white border border-slate-200 text-slate-700 space-y-2 max-w-[85%] shadow-sm">
                        <p>Halo! Kamu sedang membuka halaman **Sejarah SMAN 21 Jakarta**. Ada yang ingin kamu ketahui tentang rekam jejak sekolah?</p>
                        <div class="pt-1 space-y-1">
                            <p class="text-[10px] text-slate-400 font-bold uppercase">Pertanyaan Populer:</p>
                            <button onclick="askPreset('Tahun berapa SMAN 21 Jakarta berdiri?')" class="w-full text-left p-1.5 rounded bg-slate-100 hover:bg-amber-50 text-[10px] text-slate-700 transition-colors border border-slate-200">🏛️ Tahun Berdiri Sekolah</button>
                            <button onclick="askPreset('Siapa Kepala Sekolah SMAN 21 Jakarta?')" class="w-full text-left p-1.5 rounded bg-slate-100 hover:bg-amber-50 text-[10px] text-slate-700 transition-colors border border-slate-200">👩‍🏫 Profil Kepemimpinan</button>
                            <button onclick="askPreset('Apa saja pilar keunggulan SMAN 21?')" class="w-full text-left p-1.5 rounded bg-slate-100 hover:bg-amber-50 text-[10px] text-slate-700 transition-colors border border-slate-200">🌟 Pilar Utama Sekolah</button>
                        </div>
                    </div>
                </div>

            </div>

            <form onsubmit="submitAiChat(event)" class="p-3 bg-white border-t border-slate-200 flex items-center gap-2">
                <input type="text" id="aiInput" placeholder="Ketik pertanyaan seputar sejarah..." class="flex-1 bg-slate-100 border border-slate-300 rounded-lg px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-amber-600">
                <button type="submit" class="w-9 h-9 rounded-lg bg-brand-darkNavy hover:bg-slate-800 text-white flex items-center justify-center text-xs font-bold transition-transform">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                </button>
            </form>

        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- 📜 JAVASCRIPT LOGIC & SCROLL REVEAL                                  -->
    <!-- ===================================================================== -->
    <script>
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

        const acts = ['act1', 'act2', 'act3'];
        const wordList = [
            { word: "60 TAHUN DEDIKASI", sub: "Dari Generasi ke Generasi Mencetak Pemimpin Bangsa" },
            { word: "INOVATIF & PRESTATIF", sub: "Menjaga Tradisi Keunggulan Akademik dan Karakter" },
            { word: "BERBUDAYA LINGKUNGAN", sub: "Membentuk Generasi Berintegritas dan Berteknologi" }
        ];

        function startCinematicIntro() {
            if (localStorage.getItem('s21_intro_sejarah_seen') === 'true') {
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
            localStorage.setItem('s21_intro_sejarah_seen', 'true');
            const intro = document.getElementById('cinematicIntro');
            if (intro) {
                intro.style.opacity = '0';
                setTimeout(() => { intro.style.display = 'none'; }, 700);
            }
        }

        function replayIntro() {
            localStorage.removeItem('s21_intro_sejarah_seen');
            location.reload();
        }

        window.addEventListener('DOMContentLoaded', startCinematicIntro);

        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            if (menu) menu.classList.toggle('hidden');
        }

        function toggleAiPanel() {
            const panel = document.getElementById('aiPanel');
            if (panel) panel.classList.toggle('hidden');
        }

        function askPreset(question) {
            document.getElementById('aiInput').value = question;
            submitAiChat();
        }

        function submitAiChat(e) {
            if (e) e.preventDefault();
            const input = document.getElementById('aiInput');
            const query = input.value.trim();
            if (!query) return;

            const chatBody = document.getElementById('aiChatBody');

            const userBubble = document.createElement('div');
            userBubble.className = "flex items-start justify-end gap-2";
            userBubble.innerHTML = `
                <div class="p-3 rounded-xl bg-brand-darkNavy text-white font-medium max-w-[85%] shadow-sm">
                    ${escapeHtml(query)}
                </div>
            `;
            chatBody.appendChild(userBubble);
            input.value = '';
            chatBody.scrollTop = chatBody.scrollHeight;

            const loadingBubble = document.createElement('div');
            loadingBubble.id = "aiLoading";
            loadingBubble.className = "flex items-start gap-2";
            loadingBubble.innerHTML = `
                <div class="w-6 h-6 rounded-full bg-brand-darkNavy text-amber-400 flex items-center justify-center text-[10px] flex-shrink-0">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                </div>
                <div class="p-3 rounded-xl bg-white border border-slate-200 text-slate-500 italic">
                    Memproses informasi sejarah...
                </div>
            `;
            chatBody.appendChild(loadingBubble);
            chatBody.scrollTop = chatBody.scrollHeight;

            const formData = new FormData();
            formData.append('query', query);

            fetch('sejarah.php?action=ai_query', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                const loader = document.getElementById('aiLoading');
                if (loader) loader.remove();

                const aiReply = document.createElement('div');
                aiReply.className = "flex items-start gap-2";
                
                let linkBtn = '';
                if (data.related_link) {
                    linkBtn = `<a href="../${data.related_link}" class="inline-block mt-2 px-2.5 py-1 rounded bg-amber-50 text-amber-700 text-[10px] font-bold border border-amber-200 hover:bg-amber-100">Buka Halaman Terkait →</a>`;
                }

                aiReply.innerHTML = `
                    <div class="w-6 h-6 rounded-full bg-brand-darkNavy text-amber-400 flex items-center justify-center text-[10px] flex-shrink-0">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <div class="p-3 rounded-xl bg-white border border-slate-200 text-slate-700 space-y-1.5 max-w-[85%] shadow-sm">
                        <p class="leading-relaxed">${data.answer}</p>
                        <p class="text-[9px] text-slate-400">Sumber: ${data.source}</p>
                        ${linkBtn}
                    </div>
                `;
                chatBody.appendChild(aiReply);
                chatBody.scrollTop = chatBody.scrollHeight;
            })
            .catch(err => {
                const loader = document.getElementById('aiLoading');
                if (loader) loader.remove();

                const errReply = document.createElement('div');
                errReply.className = "flex items-start gap-2";
                errReply.innerHTML = `
                    <div class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center text-[10px] flex-shrink-0">!</div>
                    <div class="p-3 rounded-xl bg-white border border-rose-200 text-rose-600 max-w-[85%]">
                        Terjadi gangguan koneksi server. Silakan coba kembali.
                    </div>
                `;
                chatBody.appendChild(errReply);
                chatBody.scrollTop = chatBody.scrollHeight;
            });
        }

        function escapeHtml(text) {
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>
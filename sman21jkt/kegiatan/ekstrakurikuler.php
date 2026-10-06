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
} catch (PDOException $e) {
    $conn = null;
}

// =============================================================================
// HELPER FORMAT GAMBAR EKSKUL & LOGO
// =============================================================================
function formatEkskulImg($img, $default = '../uploads/logo/sman21jakarta.png') {
    if (empty($img)) return $default;
    $img = trim($img);
    
    if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
        return $img;
    }
    
    if (strpos($img, 'uploads/') === 0) {
        return '../' . $img;
    }
    
    return '../uploads/ekskul/' . ltrim($img, '/');
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
                $responseAnswer = "SMAN 21 Jakarta menyediakan beragam kegiatan ekstrakurikuler di bidang Olahraga, Seni & Budaya, Sains & Teknologi, Keagamaan, serta Kepemimpinan. Kamu dapat memilih ekskul sesuai minat dan bakatmu!";
                $sumberInfo = "Layanan Ekstrakurikuler SMAN 21";
                $relatedLink = "ekstrakurikuler.php";
                $matched = true;
            } elseif (strpos($qLower, 'ppdb') !== false || strpos($qLower, 'daftar') !== false) {
                $responseAnswer = "Penerimaan Peserta Didik Baru (PPDB) SMAN 21 Jakarta dilaksanakan sesuai juknis resmi Dinas Pendidikan DKI Jakarta melalui jalur SNBP/Prestasi, Zonasi, dan Afirmasi.";
                $sumberInfo = "Informasi PPDB Resmi";
                $relatedLink = "../informasi/ppdb.php";
                $matched = true;
            } elseif (strpos($qLower, 'alamat') !== false || strpos($qLower, 'kontak') !== false) {
                $responseAnswer = "SMAN 21 Jakarta berlokasi di Jl. Tanah Mas Raya No.1, Kayu Putih, Pulogadung, Jakarta Timur. Telepon: (021) 4892346.";
                $sumberInfo = "Kontak Sekolah";
                $relatedLink = "../kontak/index.php";
                $matched = true;
            }
        }

        if (!$matched) {
            $responseAnswer = $fallbackMsg;
            $sumberInfo = "Sistem Verifikasi SMAN 21";
        }

    } else {
        $responseAnswer = "SMAN 21 Jakarta mendukung pengembangan potensi siswa melalui berbagai ekstrakurikuler pilihan yang dibina oleh tenaga profesional.";
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
// FETCH DATA PROFIL SEKOLAH & EKSTRAKURIKULER DARI DATABASE
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
    'logo' => '../uploads/logo/sman21jakarta.png'
];

if ($conn) {
    $stmtSek = $conn->query("SELECT * FROM pengaturan_sekolah LIMIT 1");
    if ($stmtSek && $rowSek = $stmtSek->fetch()) {
        if (!empty($rowSek['nama_sekolah'])) $sekolah['nama_sekolah'] = $rowSek['nama_sekolah'];
        if (!empty($rowSek['slogan'])) $sekolah['slogan'] = $rowSek['slogan'];
        if (!empty($rowSek['telepon'])) $sekolah['telepon'] = $rowSek['telepon'];
        if (!empty($rowSek['email'])) $sekolah['email'] = $rowSek['email'];
        if (!empty($rowSek['instagram'])) $sekolah['instagram'] = $rowSek['instagram'];
        if (!empty($rowSek['youtube'])) $sekolah['youtube'] = $rowSek['youtube'];
    }
}

// Query Data Ekstrakurikuler (Tabel: `ekstrakurikuler`)
$ekskulList = [];
if ($conn) {
    try {
        $stmtEks = $conn->query("SELECT * FROM ekstrakurikuler WHERE status_aktif = 1 ORDER BY urutan ASC, id DESC");
        if ($stmtEks) {
            $ekskulList = $stmtEks->fetchAll();
        }
    } catch (PDOException $e) {
        $ekskulList = [];
    }
}

// Fallback dummy jika database masih kosong
if (empty($ekskulList)) {
    $ekskulList = [
        [
            'id' => 1,
            'nama_ekskul' => 'BASKETBALL CLUB (DUST 21)',
            'slug' => 'basket',
            'deskripsi' => 'Wadah pembinaan bakat olahraga bola basket SMAN 21 Jakarta. Berpengalaman menjuarai berbagai kompetisi regional dan DBL Jakarta.',
            'pembina' => 'Tim Pelatih Profesional & Guru Penjaskes',
            'jadwal' => 'Senin & Kamis, 15.30 - 17.30 WIB',
            'lokasi' => 'Lap. Basket Utama SMAN 21',
            'logo_foto' => '',
            'kontak' => '@dust21_official'
        ],
        [
            'id' => 2,
            'nama_ekskul' => 'PASKIBRA (PAS21)',
            'slug' => 'paskibra',
            'deskripsi' => 'Membentuk kedisiplinan, jiwa kepemimpinan, kekompakan, dan ketahanan fisik melalui latihan baris-berbaris dan keorganisasian.',
            'pembina' => 'Pembina Paskibra SMAN 21',
            'jadwal' => 'Rabu & Sabtu, 15.30 WIB',
            'lokasi' => 'Lap. Dalam Sekolah',
            'logo_foto' => '',
            'kontak' => '@paskibra21jkt'
        ],
        [
            'id' => 3,
            'nama_ekskul' => 'ROBOTIK & IT CLUB',
            'slug' => 'robotik-it',
            'deskripsi' => 'Mengembangkan minat siswa di bidang pemrograman, kecerdasan buatan, perakitan robotik, dan kompetisi Olimpiade Sains Informatika.',
            'pembina' => 'Guru Informatika & Trainer IT',
            'jadwal' => 'Jumat, 15.30 - 17.00 WIB',
            'lokasi' => 'Lab Komputer A',
            'logo_foto' => '',
            'kontak' => '@itclub21'
        ]
    ];
}

$logoPath = $sekolah['logo'];
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ekstrakurikuler — SMAN 21 Jakarta</title>
    <meta name="description" content="Daftar Ekstrakurikuler SMAN 21 Jakarta. Wadah pembinaan minat, bakat, kepemimpinan, keolahragaan, seni, dan sains teknologi siswa.">
    
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
                            lightCyan: '#E0F2FE',
                            red: '#DC2626',
                            redHover: '#B91C1C',
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
            transition: opacity 0.75s cubic-bezier(0.16, 1, 0.3, 1), transform 0.75s cubic-bezier(0.16, 1, 0.3, 1);
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

                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-lg text-cyan-600 bg-cyan-50 font-bold border border-cyan-200 flex items-center gap-1.5 transition-all">
                            <span>Kegiatan</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full left-0 w-52 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Semua Kegiatan</a>
                                <a href="ekstrakurikuler.php" class="block px-3 py-2 rounded-lg bg-cyan-50 text-cyan-600 font-bold transition-colors">Ekstrakurikuler</a>
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
            <a href="../index.php" class="block text-xs font-semibold text-slate-700">Beranda</a>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Profil</span>
                <a href="../profil/index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Profil</a>
                <a href="../profil/sejarah.php" class="block pl-3 py-1 text-xs text-slate-700">Sejarah</a>
                <a href="../profil/visi-misi.php" class="block pl-3 py-1 text-xs text-slate-700">Visi & Misi</a>
                <a href="../profil/sambutan.php" class="block pl-3 py-1 text-xs text-slate-700">Sambutan Kepala Sekolah</a>
            </div>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Kegiatan</span>
                <a href="index.php" class="block pl-3 py-1 text-xs text-slate-700">Semua Kegiatan</a>
                <a href="ekstrakurikuler.php" class="block pl-3 py-1 text-xs text-cyan-600 font-bold">Ekstrakurikuler</a>
            </div>
            <a href="../prestasi/index.php" class="block text-xs font-semibold text-slate-700">Prestasi</a>
            <a href="../fasilitas/index.php" class="block text-xs font-semibold text-slate-700">Fasilitas</a>
            <a href="../informasi/ppdb.php" class="block text-xs font-semibold text-slate-700">PPDB Info</a>
            <a href="../kontak/index.php" class="block text-xs font-semibold text-slate-700">Kontak</a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 🚩 PAGE HEADER BANNER                                                 -->
    <!-- ===================================================================== -->
    <section class="relative bg-brand-darkNavy text-white py-16 sm:py-20 overflow-hidden border-b border-slate-800">
        <div class="absolute inset-0 bg-gradient-to-r from-brand-darkNavy via-brand-navy to-slate-900 opacity-90"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <!-- Breadcrumb Navigation -->
            <nav class="flex items-center gap-2 text-xs text-cyan-300/80 mb-4 font-semibold">
                <a href="../index.php" class="hover:text-white transition-colors">Beranda</a>
                <i class="fa-solid fa-chevron-right text-[9px]"></i>
                <a href="index.php" class="hover:text-white transition-colors">Kegiatan</a>
                <i class="fa-solid fa-chevron-right text-[9px]"></i>
                <span class="text-white">Ekstrakurikuler</span>
            </nav>

            <div class="max-w-3xl space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-500/20 border border-cyan-400/30 text-cyan-300 text-[11px] font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-people-group"></i>
                    <span>PENGEMBANGAN MINAT & BAKAT SISWA</span>
                </div>
                <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight">
                    Ekstrakurikuler <span class="text-cyan-400">SMAN 21 Jakarta</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-normal">
                    Wadah pembentukan karakter, kepemimpinan, jiwa kompetitif, serta tempat lahirnya talenta berbakat di bidang Olahraga, Seni & Budaya, Sains & Teknologi, serta Keagamaan.
                </p>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🔍 SEARCH & FILTER BAR                                                -->
    <!-- ===================================================================== -->
    <section class="relative z-20 -mt-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl p-4 sm:p-6 border border-slate-200 shadow-xl flex flex-col md:flex-row items-center justify-between gap-4">
            
            <div class="relative w-full md:w-96">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" id="searchEkskul" onkeyup="filterEkskul()" placeholder="Cari nama ekstrakurikuler atau pembina..." class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50 transition-colors">
            </div>

            <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-1 md:pb-0">
                <button onclick="filterCategory('all', this)" class="category-btn px-4 py-2 rounded-xl bg-cyan-600 text-white font-bold text-xs shadow-sm transition-all whitespace-nowrap">
                    Semua Ekskul
                </button>
                <button onclick="filterCategory('olahraga', this)" class="category-btn px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition-all whitespace-nowrap">
                    Olahraga
                </button>
                <button onclick="filterCategory('seni', this)" class="category-btn px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition-all whitespace-nowrap">
                    Seni & Budaya
                </button>
                <button onclick="filterCategory('sains', this)" class="category-btn px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition-all whitespace-nowrap">
                    Sains & IT
                </button>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🏆 GRID EKSTRAKURIKULER SECTION                                       -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div id="ekskulGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php 
                $ekIdx = 1;
                foreach ($ekskulList as $ekskul): 
                    $imgUrl = formatEkskulImg($ekskul['logo_foto'] ?? '');
                    $nama = $ekskul['nama_ekskul'] ?? 'Ekstrakurikuler';
                    $pembina = $ekskul['pembina'] ?? 'Pembina Sekolah';
                    $jadwal = $ekskul['jadwal'] ?? 'Jadwal Reguler';
                    $lokasi = $ekskul['lokasi'] ?? 'Lingkungan SMAN 21';
                    $kontak = $ekskul['kontak'] ?? '-';
                    $deskripsi = $ekskul['deskripsi'] ?? 'Kegiatan pembinaan minat dan bakat siswa.';
                ?>
                <div class="ekskul-item school-card rounded-2xl p-6 bg-white border border-slate-200 flex flex-col justify-between reveal-section delay-<?= ($ekIdx % 3 + 1) * 100; ?>"
                     data-name="<?= strtolower(htmlspecialchars($nama)); ?>"
                     data-pembina="<?= strtolower(htmlspecialchars($pembina)); ?>">
                    
                    <div>
                        <!-- Header Card dengan Logo Ekskul -->
                        <div class="flex items-center gap-4 mb-5 pb-4 border-b border-slate-100">
                            <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-cyan-50 to-slate-100 border border-slate-200 p-2 flex items-center justify-center flex-shrink-0 shadow-sm overflow-hidden">
                                <img src="<?= htmlspecialchars($imgUrl); ?>" 
                                     alt="Logo <?= htmlspecialchars($nama); ?>" 
                                     class="max-h-full max-w-full object-contain"
                                     onerror="this.onerror=null; this.src='<?= $logoPath; ?>';">
                            </div>
                            <div>
                                <span class="text-[10px] font-extrabold text-cyan-600 bg-cyan-50 px-2.5 py-0.5 rounded border border-cyan-200 uppercase tracking-wider block w-fit mb-1">
                                    SMAN 21 EKSKUL
                                </span>
                                <h3 class="text-base font-extrabold text-brand-darkNavy leading-snug line-clamp-1">
                                    <?= htmlspecialchars($nama); ?>
                                </h3>
                            </div>
                        </div>

                        <!-- Info Detail Quick View -->
                        <div class="space-y-2.5 text-xs text-slate-600 mb-5">
                            <div class="flex items-start gap-2.5">
                                <i class="fa-solid fa-user-tie text-cyan-600 w-4 mt-0.5"></i>
                                <div>
                                    <span class="font-bold text-slate-800 block">Pembina / Pelatih:</span>
                                    <span class="text-slate-600"><?= htmlspecialchars($pembina); ?></span>
                                </div>
                            </div>

                            <div class="flex items-start gap-2.5">
                                <i class="fa-solid fa-calendar-days text-cyan-600 w-4 mt-0.5"></i>
                                <div>
                                    <span class="font-bold text-slate-800 block">Jadwal Latihan:</span>
                                    <span class="text-slate-600"><?= htmlspecialchars($jadwal); ?></span>
                                </div>
                            </div>

                            <div class="flex items-start gap-2.5">
                                <i class="fa-solid fa-location-dot text-cyan-600 w-4 mt-0.5"></i>
                                <div>
                                    <span class="font-bold text-slate-800 block">Lokasi:</span>
                                    <span class="text-slate-600"><?= htmlspecialchars($lokasi); ?></span>
                                </div>
                            </div>
                        </div>

                        <p class="text-xs text-slate-500 leading-relaxed line-clamp-3 mb-6">
                            <?= htmlspecialchars($deskripsi); ?>
                        </p>
                    </div>

                    <!-- Action Footer -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-slate-500 flex items-center gap-1">
                            <i class="fa-brands fa-instagram text-cyan-600"></i>
                            <?= htmlspecialchars($kontak); ?>
                        </span>
                        <button onclick="openDetailModal('<?= addslashes(htmlspecialchars($nama)); ?>', '<?= addslashes(htmlspecialchars($pembina)); ?>', '<?= addslashes(htmlspecialchars($jadwal)); ?>', '<?= addslashes(htmlspecialchars($lokasi)); ?>', '<?= addslashes(htmlspecialchars($kontak)); ?>', '<?= addslashes(htmlspecialchars($deskripsi)); ?>', '<?= addslashes(htmlspecialchars($imgUrl)); ?>')" 
                                class="px-4 py-2 rounded-lg bg-brand-darkNavy hover:bg-cyan-600 text-white font-bold text-xs transition-colors flex items-center gap-1.5 shadow-sm">
                            <span>Detail</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </button>
                    </div>

                </div>
                <?php $ekIdx++; endforeach; ?>
            </div>

            <!-- Empty State Search Result -->
            <div id="noResult" class="hidden text-center py-16 bg-white rounded-2xl border border-slate-200 mt-6 space-y-3">
                <i class="fa-solid fa-folder-open text-4xl text-slate-300"></i>
                <h3 class="text-base font-bold text-slate-700">Ekstrakurikuler Tidak Ditemukan</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">Coba masukkan kata kunci pencarian yang berbeda atau pilih kategori lainnya.</p>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📢 CALL TO ACTION REGISTRASI EKSKUL                                   -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-gradient-to-r from-brand-darkNavy to-slate-900 text-white border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-gradient-to-r from-cyan-600/20 to-blue-600/20 border border-cyan-500/30 rounded-3xl p-8 sm:p-12 text-center space-y-6 reveal-section">
                <div class="w-16 h-16 rounded-2xl bg-cyan-500/20 border border-cyan-400/30 text-cyan-400 flex items-center justify-center text-3xl mx-auto shadow-inner">
                    <i class="fa-solid fa-star"></i>
                </div>
                <div class="max-w-2xl mx-auto space-y-2">
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white">Ingin Bergabung dengan Ekskul Pilihanmu?</h2>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        Pendaftaran anggota ekstrakurikuler dibuka secara resmi pada saat Masa Pengenalan Lingkungan Sekolah (MPLS) & Ekskul Fair SMAN 21 Jakarta.
                    </p>
                </div>
                <div class="pt-2 flex flex-wrap justify-center gap-4">
                    <a href="../kontak/index.php" class="px-7 py-3.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg transition-all flex items-center gap-2">
                        <i class="fa-solid fa-headset"></i>
                        <span>HUBUNGI PEMBINA OSIS / EKSKUL</span>
                    </a>
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
    <!-- 🖼️ MODAL DETAIL EKSTRAKURIKULER                                       -->
    <!-- ===================================================================== -->
    <div id="detailModal" class="fixed inset-0 z-[9995] bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4 transition-opacity">
        <div class="bg-white rounded-2xl max-w-xl w-full p-6 sm:p-8 shadow-2xl relative border border-slate-200 transform transition-transform scale-95 duration-200" id="modalBox">
            <button onclick="closeDetailModal()" class="absolute top-4 right-4 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="flex items-center gap-4 mb-6 pb-4 border-b border-slate-100">
                <div class="w-16 h-16 rounded-xl bg-slate-100 border border-slate-200 p-2 flex items-center justify-center flex-shrink-0">
                    <img id="modalImg" src="" alt="Logo Ekskul" class="max-h-full max-w-full object-contain">
                </div>
                <div>
                    <span class="text-[10px] font-bold text-cyan-600 uppercase tracking-wider block">DETAIL EKSKUL SMAN 21</span>
                    <h3 id="modalTitle" class="text-lg font-extrabold text-brand-darkNavy leading-tight"></h3>
                </div>
            </div>

            <div class="space-y-4 text-xs text-slate-700 mb-6">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-user-tie text-cyan-600"></i>
                        <span class="font-bold">Pembina:</span>
                        <span id="modalPembina" class="text-slate-600"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-calendar-days text-cyan-600"></i>
                        <span class="font-bold">Jadwal:</span>
                        <span id="modalJadwal" class="text-slate-600"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-location-dot text-cyan-600"></i>
                        <span class="font-bold">Lokasi:</span>
                        <span id="modalLokasi" class="text-slate-600"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-brands fa-instagram text-cyan-600"></i>
                        <span class="font-bold">Kontak / IG:</span>
                        <span id="modalKontak" class="text-slate-600"></span>
                    </div>
                </div>

                <div>
                    <h4 class="font-bold text-brand-darkNavy text-xs uppercase tracking-wider mb-1">Deskripsi Kegiatan</h4>
                    <p id="modalDeskripsi" class="text-slate-600 leading-relaxed text-xs"></p>
                </div>
            </div>

            <div class="pt-2">
                <button onclick="closeDetailModal()" class="w-full py-3 rounded-xl bg-brand-darkNavy hover:bg-cyan-600 text-white font-bold text-xs transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>

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
                <p>Ada yang bisa saya bantu terkait Ekstrakurikuler, Jadwal, Pembina, atau kegiatan lainnya di SMAN 21 Jakarta?</p>
            </div>
        </div>

        <div class="p-3 bg-white border-t border-slate-200">
            <form onsubmit="submitAiChat(event)" class="flex gap-2">
                <input type="text" id="aiInput" placeholder="Tanyakan seputar ekskul..." class="flex-1 px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50">
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
        }, { threshold: 0.08 });

        document.querySelectorAll('.reveal-section').forEach(el => observer.observe(el));

        // 3. SEARCH & CATEGORY FILTER LOGIC
        function filterEkskul() {
            const input = document.getElementById('searchEkskul').value.toLowerCase();
            const items = document.querySelectorAll('.ekskul-item');
            let visibleCount = 0;

            items.forEach(item => {
                const name = item.getAttribute('data-name') || '';
                const pembina = item.getAttribute('data-pembina') || '';

                if (name.includes(input) || pembina.includes(input)) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            const noResult = document.getElementById('noResult');
            if (visibleCount === 0) {
                noResult.classList.remove('hidden');
            } else {
                noResult.classList.add('hidden');
            }
        }

        function filterCategory(cat, btn) {
            document.querySelectorAll('.category-btn').forEach(b => {
                b.className = 'category-btn px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition-all whitespace-nowrap';
            });
            btn.className = 'category-btn px-4 py-2 rounded-xl bg-cyan-600 text-white font-bold text-xs shadow-sm transition-all whitespace-nowrap';

            const items = document.querySelectorAll('.ekskul-item');
            let visibleCount = 0;

            items.forEach(item => {
                const name = item.getAttribute('data-name') || '';
                if (cat === 'all') {
                    item.style.display = 'flex';
                    visibleCount++;
                } else if (cat === 'olahraga' && (name.includes('basket') || name.includes('futsal') || name.includes('volli') || name.includes('paskibra') || name.includes('pramuka'))) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else if (cat === 'seni' && (name.includes('tari') || name.includes('musik') || name.includes('paduan') || name.includes('teater') || name.includes('seni'))) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else if (cat === 'sains' && (name.includes('robot') || name.includes('it') || name.includes('kIR') || name.includes('sains') || name.includes('komputer'))) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            const noResult = document.getElementById('noResult');
            if (visibleCount === 0) {
                noResult.classList.remove('hidden');
            } else {
                noResult.classList.add('hidden');
            }
        }

        // 4. MODAL DETAIL EKSKUL
        function openDetailModal(nama, pembina, jadwal, lokasi, kontak, deskripsi, img) {
            document.getElementById('modalTitle').innerText = nama;
            document.getElementById('modalPembina').innerText = pembina;
            document.getElementById('modalJadwal').innerText = jadwal;
            document.getElementById('modalLokasi').innerText = lokasi;
            document.getElementById('modalKontak').innerText = kontak;
            document.getElementById('modalDeskripsi').innerText = deskripsi;
            document.getElementById('modalImg').src = img;

            const modal = document.getElementById('detailModal');
            modal.classList.remove('hidden');
        }

        function closeDetailModal() {
            const modal = document.getElementById('detailModal');
            modal.classList.add('hidden');
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

            fetch('ekstrakurikuler.php?action=ai_query', {
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

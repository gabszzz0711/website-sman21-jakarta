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

// Helper untuk format path gambar relatif
function formatImgPath($img, $default = '../uploads/tempat/gedungA1.jpg') {
    if (empty($img)) return $default;
    $img = trim($img);
    if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
        return $img;
    }
    if (strpos($img, 'uploads/') === 0) {
        return '../' . $img;
    }
    return '../uploads/' . ltrim($img, '/');
}

// =============================================================================
// ENDPOINT AJAX UNTUK S21 AI ASSISTANT (CONTEXT-AWARE: AKADEMIK)
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
    $sumberInfo = "Basis Data Akademik SMAN 21 Jakarta";
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
            if (strpos($qLower, 'guru') !== false || strpos($qLower, 'pengajar') !== false || strpos($qLower, 'staf') !== false) {
                $stmtG = $conn->query("SELECT COUNT(*) as total FROM teachers");
                $totG = $stmtG ? $stmtG->fetch()['total'] : 48;
                $responseAnswer = "SMAN 21 Jakarta didukung oleh " . $totG . "+ tenaga pendidik tersertifikasi S1/S2 yang ahli di bidang studi masing-masing.";
                $sumberInfo = "Data Guru & Staf Pendidik";
                $relatedLink = "guru.php";
                $matched = true;
            } elseif (strpos($qLower, 'pemeringkatan') !== false || strpos($qLower, 'saintek') !== false || strpos($qLower, 'soshum') !== false) {
                $responseAnswer = "Pemeringkatan jurusan di SMAN 21 Jakarta mencakup SAINTEK, SOSHUM, TERAPAN 1, dan TERAPAN 2 dengan standar rata-rata nilai di atas 80.00.";
                $sumberInfo = "Data Pemeringkatan Jurusan";
                $relatedLink = "pemeringkatan.php";
                $matched = true;
            } elseif (strpos($qLower, 'ptn') !== false || strpos($qLower, 'lulusan') !== false || strpos($qLower, 'kuliah') !== false) {
                $responseAnswer = "Tingkat keterserapan alumni SMAN 21 Jakarta di PTN Favorit (UI, ITB, UGM, IPB, Unpad, ITS) mencapai lebih dari 85% setiap tahunnya melalui SNBP, SNBT, dan Jalur Mandiri.";
                $sumberInfo = "Data Keterserapan PTN";
                $relatedLink = "keterserapan-ptn.php";
                $matched = true;
            }
        }

        if (!$matched) {
            $responseAnswer = $fallbackMsg;
            $sumberInfo = "Sistem Verifikasi SMAN 21";
        }

    } else {
        $responseAnswer = "SMAN 21 Jakarta menerapkan Kurikulum Merdeka yang berfokus pada penguatan Literasi Digital, Sains Terapan, Koding & Kecerdasan Artifisial, serta Proyek Karakter Pancasila.";
        $sumberInfo = "Informasi Kurikulum SMAN 21";
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
// FETCH DATA RESMI SEKOLAH & AKADEMIK DARI DATABASE
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
        if (!empty($rowSek['akreditasi'])) $sekolah['akreditasi'] = $rowSek['akreditasi'];
        if (!empty($rowSek['alamat'])) $sekolah['alamat'] = $rowSek['alamat'];
        if (!empty($rowSek['telepon'])) $sekolah['telepon'] = $rowSek['telepon'];
        if (!empty($rowSek['email'])) $sekolah['email'] = $rowSek['email'];
        if (!empty($rowSek['instagram'])) $sekolah['instagram'] = $rowSek['instagram'];
    }
}

// 1. Fetch Program Pendidikan
$programList = [];
if ($conn) {
    try {
        $stmtProg = $conn->query("SELECT * FROM program_pendidikan WHERE status_aktif=1 ORDER BY urutan ASC");
        if ($stmtProg) $programList = $stmtProg->fetchAll();
    } catch (PDOException $e) { $programList = []; }
}
if (empty($programList)) {
    $programList = [
        [
            'nama_program' => 'Kurikulum Merdeka Unggulan',
            'slug' => 'kurikulum-merdeka',
            'deskripsi' => 'Pengembangan minat bakat fleksibel berbasis kolaborasi interdisipliner, literasi digital, dan penguatan karakter Profil Pelajar Pancasila.',
            'ikon' => 'fa-graduation-cap'
        ],
        [
            'nama_program' => 'Sains, Teknologi & KA',
            'slug' => 'sains-teknologi',
            'deskripsi' => 'Penguatan materi Fisika, Kimia, Biologi terapan, Koding Komputer, serta dasar Kecerdasan Artifisial (KA) untuk era digital.',
            'ikon' => 'fa-laptop-code'
        ],
        [
            'nama_program' => 'Sosial, Humaniora & Kepemimpinan',
            'slug' => 'sosial-humaniora',
            'deskripsi' => 'Membentuk jiwa kepemimpinan kritis, penalaran Sosiologi, Ekonomi Digital, Geografi Lingkungan, dan kewirausahaan siswa.',
            'ikon' => 'fa-users-cog'
        ]
    ];
}

// 2. Fetch Pemeringkatan Jurusan
$pemeringkatanList = [];
if ($conn) {
    try {
        $stmtPem = $conn->query("SELECT * FROM pemeringkatan ORDER BY id ASC");
        if ($stmtPem) $pemeringkatanList = $stmtPem->fetchAll();
    } catch (PDOException $e) { $pemeringkatanList = []; }
}

// 3. Fetch Data Guru & Mapel
$totalGuru = 48;
$mapelList = [];
if ($conn) {
    try {
        $stmtTotG = $conn->query("SELECT COUNT(*) as tot FROM teachers");
        if ($stmtTotG) $totalGuru = $stmtTotG->fetch()['tot'] ?: 48;

        $stmtM = $conn->query("SELECT * FROM mapel ORDER BY id ASC LIMIT 12");
        if ($stmtM) $mapelList = $stmtM->fetchAll();
    } catch (PDOException $e) {}
}

// 4. Fetch Data Keterserapan PTN
$ptnData = [];
if ($conn) {
    try {
        $stmtPtn = $conn->query("SELECT * FROM keterserapan_ptn ORDER BY tahun ASC LIMIT 5");
        if ($stmtPtn) $ptnData = $stmtPtn->fetchAll();
    } catch (PDOException $e) {}
}
if (empty($ptnData)) {
    $ptnData = [
        ['tahun' => '2023', 'jumlah_lulusan' => 288, 'snbp' => 50, 'snbt' => 57, 'mandiri' => 106, 'kedinasan' => 1],
        ['tahun' => '2024', 'jumlah_lulusan' => 252, 'snbp' => 25, 'snbt' => 25, 'mandiri' => 71, 'kedinasan' => 2],
        ['tahun' => '2025', 'jumlah_lulusan' => 251, 'snbp' => 17, 'snbt' => 50, 'mandiri' => 99, 'kedinasan' => 0]
    ];
}

$logoPath = $sekolah['logo'];
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Akademik & Kurikulum — SMA NEGERI 21 JAKARTA</title>
    <meta name="description" content="Portal Akademik SMAN 21 Jakarta. Program Pendidikan Kurikulum Merdeka, Daftar Guru Tersertifikasi, Pemeringkatan Jurusan, dan Rekapitulasi Keterserapan Alumni di PTN.">
    
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
                            amber: '#D97706',
                            emerald: '#059669',
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

        *, *:before, *:after { box-sizing: inherit; }

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
            background: linear-gradient(135deg, rgba(6, 18, 36, 0.92) 0%, rgba(11, 30, 54, 0.84) 60%, rgba(15, 23, 42, 0.88) 100%);
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

                    <!-- Dropdown Profil -->
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

                    <!-- Dropdown Akademik (ACTIVE) -->
                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-lg text-cyan-600 bg-cyan-50 font-bold border border-cyan-200 flex items-center gap-1.5 transition-all">
                            <span>Akademik</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full left-0 w-60 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="index.php" class="block px-3 py-2 rounded-lg text-cyan-600 font-bold bg-cyan-50/50 transition-colors">Overview Akademik</a>
                                <a href="program.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Program Pendidikan</a>
                                <a href="guru.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Daftar Guru & Staf</a>
                                <a href="pemeringkatan.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Pemeringkatan Jurusan</a>
                                <a href="keterserapan-ptn.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Keterserapan PTN</a>
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
                <span class="text-[11px] uppercase text-cyan-600 font-bold tracking-wider">Akademik</span>
                <a href="index.php" class="block pl-3 py-1 text-xs text-cyan-600 font-bold">Overview Akademik</a>
                <a href="program.php" class="block pl-3 py-1 text-xs text-slate-700">Program Pendidikan</a>
                <a href="guru.php" class="block pl-3 py-1 text-xs text-slate-700">Daftar Guru & Staf</a>
                <a href="pemeringkatan.php" class="block pl-3 py-1 text-xs text-slate-700">Pemeringkatan Jurusan</a>
                <a href="keterserapan-ptn.php" class="block pl-3 py-1 text-xs text-slate-700">Keterserapan PTN</a>
            </div>
            <a href="../profil/index.php" class="block text-xs font-semibold text-slate-700">Profil</a>
            <a href="../prestasi/index.php" class="block text-xs font-semibold text-slate-700">Prestasi</a>
            <a href="../kegiatan/index.php" class="block text-xs font-semibold text-slate-700">Kegiatan & Ekskul</a>
            <a href="../informasi/ppdb.php" class="block text-xs font-semibold text-slate-700">PPDB Info</a>
            <a href="../kontak/index.php" class="block text-xs font-semibold text-slate-700">Kontak</a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 🎓 HERO BANNER AKADEMIK OVERVIEW                                      -->
    <!-- ===================================================================== -->
    <section class="relative bg-brand-darkNavy text-white py-20 lg:py-28 overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-1000 scale-105" 
             style="background-image: url('<?= htmlspecialchars($sekolah['hero_bg']); ?>');">
        </div>
        <div class="absolute inset-0 hero-overlay"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl space-y-4 reveal-section">
                <!-- Breadcrumb -->
                <div class="flex items-center gap-2 text-xs text-cyan-400 font-semibold tracking-wide uppercase">
                    <a href="../index.php" class="hover:underline">BERANDA</a>
                    <i class="fa-solid fa-chevron-right text-[9px]"></i>
                    <span class="text-white">OVERVIEW AKADEMIK</span>
                </div>

                <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight">
                    Pusat Keunggulan <br><span class="text-cyan-400">Akademik & Kurikulum Merdeka</span>
                </h1>
                
                <p class="text-slate-200 text-sm sm:text-base leading-relaxed">
                    SMAN 21 Jakarta menyelenggarakan ekosistem pembelajaran modern yang mengintegrasikan literasi digital, koding, sains terapan, serta penguatan Profil Pelajar Pancasila.
                </p>

                <!-- Key Stat Badges -->
                <div class="pt-4 flex flex-wrap gap-3">
                    <span class="px-3.5 py-1.5 rounded-full bg-cyan-500/20 border border-cyan-400/30 text-cyan-300 text-xs font-bold flex items-center gap-2">
                        <i class="fa-solid fa-award"></i> Akreditasi A BAN-S/M
                    </span>
                    <span class="px-3.5 py-1.5 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-bold flex items-center gap-2">
                        <i class="fa-solid fa-user-graduate"></i> 85%+ Diterima PTN Favorit
                    </span>
                    <span class="px-3.5 py-1.5 rounded-full bg-amber-500/20 border border-amber-400/30 text-amber-300 text-xs font-bold flex items-center gap-2">
                        <i class="fa-solid fa-chalkboard-user"></i> <?= $totalGuru; ?>+ Guru Tersertifikasi
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🧩 QUICK ACCESS HUB AKADEMIK (4 CORE SECTIONS)                         -->
    <!-- ===================================================================== -->
    <section class="relative z-20 -mt-10 lg:-mt-12 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            
            <a href="program.php" class="school-card p-6 rounded-2xl border-t-4 border-cyan-500 group flex flex-col justify-between reveal-section delay-100">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-graduation-cap text-2xl"></i>
                    </div>
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 group-hover:text-cyan-600 transition-colors">
                        Program Pendidikan
                    </h3>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                        Kurikulum Merdeka, Sains Terapan, Koding & Kecerdasan Artifisial.
                    </p>
                </div>
                <span class="text-xs font-bold text-cyan-600 uppercase tracking-wider mt-4 flex items-center gap-1.5">
                    Lihat Program <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </span>
            </a>

            <a href="guru.php" class="school-card p-6 rounded-2xl border-t-4 border-blue-600 group flex flex-col justify-between reveal-section delay-200">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-chalkboard-user text-2xl"></i>
                    </div>
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 group-hover:text-cyan-600 transition-colors">
                        Daftar Guru & Staf
                    </h3>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                        Profil <?= $totalGuru; ?>+ pengajar profesional berpendidikan S1/S2 tersertifikasi.
                    </p>
                </div>
                <span class="text-xs font-bold text-blue-600 uppercase tracking-wider mt-4 flex items-center gap-1.5">
                    Direktori Guru <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </span>
            </a>

            <a href="pemeringkatan.php" class="school-card p-6 rounded-2xl border-t-4 border-amber-500 group flex flex-col justify-between reveal-section delay-300">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-chart-line text-2xl"></i>
                    </div>
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 group-hover:text-cyan-600 transition-colors">
                        Pemeringkatan Jurusan
                    </h3>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                        Standar nilai tertinggi & terendah Sainteks, Soshum, dan Terapan.
                    </p>
                </div>
                <span class="text-xs font-bold text-amber-600 uppercase tracking-wider mt-4 flex items-center gap-1.5">
                    Data Nilai <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </span>
            </a>

            <a href="keterserapan-ptn.php" class="school-card p-6 rounded-2xl border-t-4 border-emerald-600 group flex flex-col justify-between reveal-section delay-400">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-university text-2xl"></i>
                    </div>
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 group-hover:text-cyan-600 transition-colors">
                        Keterserapan PTN
                    </h3>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                        Statistik lulusan diterima di UI, ITB, UGM, IPB, ITS & Kedinasan.
                    </p>
                </div>
                <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider mt-4 flex items-center gap-1.5">
                    Statistik PTN <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </span>
            </a>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📚 SECTION 1: PROGRAM PENDIDIKAN UNGGULAN                             -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 reveal-section">
                <div class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-600 bg-cyan-50 px-3 py-1 rounded border border-cyan-200">
                        KURIKULUM UTAMA
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy">Program Pendidikan Sekolah</h2>
                    <p class="text-xs sm:text-sm text-slate-500 max-w-xl">
                        Mendorong perkembangan akademis dan karakter peserta didik melalui eksplorasi minat, koding digital, serta proyek kolaborasi.
                    </p>
                </div>
                <a href="program.php" class="px-5 py-2.5 rounded-lg bg-slate-100 hover:bg-cyan-50 hover:text-cyan-700 text-slate-700 font-bold text-xs transition-all border border-slate-200 inline-flex items-center gap-2 self-start md:self-auto">
                    <span>Lihat Semua Kurikulum</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <?php $pIdx = 1; foreach ($programList as $prog): ?>
                <div class="school-card p-8 rounded-2xl bg-white border border-slate-200 space-y-4 reveal-section delay-<?= $pIdx * 100; ?>">
                    <div class="w-12 h-12 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-600 flex items-center justify-center text-xl">
                        <i class="fa-solid <?= htmlspecialchars($prog['ikon'] ?? 'fa-graduation-cap'); ?>"></i>
                    </div>
                    <h3 class="text-lg font-bold text-brand-darkNavy"><?= htmlspecialchars($prog['nama_program']); ?></h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        <?= htmlspecialchars($prog['deskripsi']); ?>
                    </p>
                    <div class="pt-2">
                        <a href="program.php" class="text-xs font-bold text-cyan-600 hover:underline inline-flex items-center gap-1.5">
                            <span>Detail Pembelajaran</span>
                            <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        </a>
                    </div>
                </div>
                <?php $pIdx++; endforeach; ?>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📊 SECTION 2: STANDAR PEMERINGKATAN JURUSAN                           -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            
            <div class="text-center max-w-2xl mx-auto space-y-2 reveal-section">
                <span class="text-xs font-bold uppercase tracking-widest text-cyan-600">STANDAR KELULUSAN</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy">Pemeringkatan Jurusan & Nilai</h2>
                <p class="text-xs sm:text-sm text-slate-500">
                    Acuan pemeringkatan akademik internal SMAN 21 Jakarta berdasarkan gabungan nilai kelompok mata pelajaran utama.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php if (!empty($pemeringkatanList)): $pemIdx = 1; foreach ($pemeringkatanList as $pem): ?>
                <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 space-y-4 reveal-section delay-<?= $pemIdx * 100; ?>">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <span class="text-[10px] font-bold text-cyan-700 bg-cyan-50 px-2.5 py-1 rounded border border-cyan-200 uppercase">
                            Kelompok <?= $pemIdx; ?>
                        </span>
                        <i class="fa-solid fa-award text-amber-500 text-base"></i>
                    </div>

                    <h3 class="text-base font-extrabold text-brand-darkNavy leading-snug">
                        <?= htmlspecialchars($pem['nama_jurusan']); ?>
                    </h3>

                    <p class="text-xs text-slate-500 line-clamp-2">
                        <strong class="text-slate-700">Mata Pelajaran:</strong> <?= htmlspecialchars($pem['daftar_mapel']); ?>
                    </p>

                    <div class="pt-2 grid grid-cols-2 gap-2 text-center border-t border-slate-100">
                        <div class="p-2 rounded-lg bg-emerald-50 border border-emerald-200">
                            <span class="text-[10px] text-emerald-800 font-semibold block uppercase">Tertinggi</span>
                            <span class="text-sm font-extrabold text-emerald-700"><?= htmlspecialchars($pem['nilai_tertinggi']); ?></span>
                        </div>
                        <div class="p-2 rounded-lg bg-slate-100 border border-slate-200">
                            <span class="text-[10px] text-slate-500 font-semibold block uppercase">Terendah</span>
                            <span class="text-sm font-extrabold text-slate-700"><?= htmlspecialchars($pem['nilai_terendah']); ?></span>
                        </div>
                    </div>
                </div>
                <?php $pemIdx++; endforeach; else: ?>
                <div class="col-span-4 school-card p-8 rounded-2xl text-center text-xs text-slate-500 bg-white">
                    Data pemeringkatan akademik dapat diakses selengkapnya di halaman pemeringkatan.
                </div>
                <?php endif; ?>
            </div>

            <div class="text-center reveal-section">
                <a href="pemeringkatan.php" class="px-7 py-3 rounded-lg bg-brand-darkNavy hover:bg-slate-800 text-white font-bold text-xs inline-flex items-center gap-2 shadow-md transition-all">
                    <span>Lihat Tabel Pemeringkatan Lengkap</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 👨‍🏫 SECTION 3: RINGKASAN GURU & MATA PELAJARAN                       -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-12 items-center">
                
                <div class="lg:col-span-5 space-y-6 reveal-section">
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-600 bg-cyan-50 px-3 py-1 rounded border border-cyan-200">
                        TENAGA PENDIDIK
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy leading-tight">
                        Didukung Pendidik Berdedikasi & Profesional
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        SMAN 21 Jakarta memiliki <?= $totalGuru; ?>+ guru dan staf pendidik yang berpengalaman di bidang studi masing-masing, tersertifikasi nasional, serta aktif membimbing siswa mencapai prestasi tingkat provinsi hingga nasional.
                    </p>

                    <div class="grid grid-cols-2 gap-4 pt-2">
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="text-2xl font-extrabold text-brand-darkNavy block"><?= $totalGuru; ?>+</span>
                            <span class="text-[11px] text-slate-500 font-medium">Guru & Staf</span>
                        </div>
                        <div class="p-4 rounded-xl bg-cyan-50 border border-cyan-200">
                            <span class="text-2xl font-extrabold text-cyan-700 block">26</span>
                            <span class="text-[11px] text-cyan-800 font-medium">Mata Pelajaran</span>
                        </div>
                    </div>

                    <a href="guru.php" class="inline-flex items-center gap-2 text-xs font-bold text-cyan-600 hover:underline pt-2">
                        <span>Buka Direktori Lengkap Guru & Staf</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>

                <!-- Preview Grid Mata Pelajaran -->
                <div class="lg:col-span-7 reveal-section delay-200">
                    <div class="school-card p-6 sm:p-8 rounded-2xl bg-white border border-slate-200 shadow-lg">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                            <div>
                                <h3 class="text-sm font-bold text-brand-darkNavy">Bidang Studi Akademik</h3>
                                <p class="text-[11px] text-slate-500">Mata pelajaran utama dalam Kurikulum Merdeka</p>
                            </div>
                            <span class="text-[10px] font-bold text-cyan-700 bg-cyan-50 px-2.5 py-1 rounded border border-cyan-200">26 Mapel</span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <?php if (!empty($mapelList)): foreach ($mapelList as $mp): ?>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-2.5 hover:border-cyan-400 transition-colors">
                                <i class="fa-solid fa-book-bookmark text-cyan-600 text-xs"></i>
                                <span class="text-xs font-semibold text-slate-800 truncate"><?= htmlspecialchars($mp['nama_mapel']); ?></span>
                            </div>
                            <?php endforeach; else: ?>
                            <div class="col-span-3 text-xs text-slate-500">Mata pelajaran mencakup Matematika, Fisika, Kimia, Biologi, Sosiologi, Ekonomi, Bahasa Inggris, Koding & KA.</div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 text-center">
                            <a href="guru.php" class="text-xs font-bold text-cyan-600 hover:underline">
                                Lihat daftar nama guru pengajar tiap mata pelajaran →
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📈 SECTION 4: GRAFIK KETERSERAPAN ALUMNI DI PTN                        -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            
            <div class="grid lg:grid-cols-12 gap-12 items-center">
                
                <div class="lg:col-span-7 reveal-section">
                    <div class="school-card p-6 sm:p-8 rounded-2xl bg-white border border-slate-200 shadow-lg">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                            <div>
                                <h3 class="text-sm font-bold text-brand-darkNavy">Tren Kelulusan PTN Alumni SMAN 21</h3>
                                <p class="text-[11px] text-slate-500">Penerimaan via SNBP, SNBT, Kedinasan, dan Mandiri</p>
                            </div>
                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded border border-emerald-200">Data Terverifikasi</span>
                        </div>

                        <div class="relative w-full h-72">
                            <canvas id="ptnChartAkademik"></canvas>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5 space-y-6 reveal-section delay-200">
                    <span class="text-xs font-bold uppercase tracking-widest text-emerald-700 bg-emerald-50 px-3 py-1 rounded border border-emerald-200">
                        REKAPITULASI ALUMNI
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy leading-tight">
                        Konsistensi Capaian PTN Favorit
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Setiap tahun, ratusan lulusan SMAN 21 Jakarta berhasil menembus perguruan tinggi negeri terkemuka di Indonesia seperti Universitas Indonesia (UI), Institut Teknologi Bandung (ITB), Universitas Gadjah Mada (UGM), IPB University, Universitas Padjadjaran (Unpad), dan ITS Surabaya.
                    </p>

                    <div class="pt-2">
                        <a href="keterserapan-ptn.php" class="px-6 py-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs inline-flex items-center gap-2 shadow transition-all">
                            <span>Detail Rincian Alumni per PTN</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🤖 BANNER AI ASSISTANT AKADEMIK                                       -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-slate-900 text-white relative overflow-hidden">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 reveal-section">
            <div class="p-8 sm:p-12 rounded-2xl bg-gradient-to-r from-brand-darkNavy to-slate-800 border border-slate-700 text-center space-y-5 shadow-2xl">
                <div class="w-14 h-14 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center mx-auto text-2xl border border-cyan-500/30">
                    <i class="fa-solid fa-circle-question"></i>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white">
                    Ingin Konsultasi Akademik & Kurikulum?
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                    S21 AI Assistant siap membantu Anda menjawab pertanyaan seputar Struktur Kurikulum, Jadwal Belajar, Daftar Guru Pengajar, Pemeringkatan, dan Informasi PTN.
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
                    <h4 class="text-white font-bold mb-3">Navigasi Akademik</h4>
                    <ul class="space-y-2 text-[11px]">
                        <li><a href="index.php" class="text-cyan-400 font-bold">Overview Akademik</a></li>
                        <li><a href="program.php" class="hover:text-cyan-400 transition-colors">Program Pendidikan</a></li>
                        <li><a href="guru.php" class="hover:text-cyan-400 transition-colors">Daftar Guru & Staf</a></li>
                        <li><a href="pemeringkatan.php" class="hover:text-cyan-400 transition-colors">Pemeringkatan Jurusan</a></li>
                        <li><a href="keterserapan-ptn.php" class="hover:text-cyan-400 transition-colors">Keterserapan PTN</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-bold mb-3">Layanan Informasi</h4>
                    <ul class="space-y-2 text-[11px]">
                        <li><a href="../profil/index.php" class="hover:text-cyan-400 transition-colors">Profil Sekolah</a></li>
                        <li><a href="../prestasi/index.php" class="hover:text-cyan-400 transition-colors">Prestasi Siswa</a></li>
                        <li><a href="../informasi/ppdb.php" class="hover:text-cyan-400 transition-colors">PPDB Info</a></li>
                        <li><a href="../kontak/index.php" class="hover:text-cyan-400 transition-colors">Kontak Resmi</a></li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <h4 class="text-white font-bold">Media Sosial</h4>
                    <div class="flex gap-2">
                        <a href="<?= htmlspecialchars($sekolah['instagram']); ?>" target="_blank" class="w-8 h-8 rounded bg-slate-800 border border-slate-700 flex items-center justify-center hover:text-cyan-400 transition-colors"><i class="fa-brands fa-instagram"></i></a>
                        <a href="<?= htmlspecialchars($sekolah['youtube']); ?>" target="_blank" class="w-8 h-8 rounded bg-slate-800 border border-slate-700 flex items-center justify-center hover:text-cyan-400 transition-colors"><i class="fa-brands fa-youtube"></i></a>
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
                <p>Ada yang bisa saya bantu terkait **Kurikulum, Program Pendidikan, Guru, Pemeringkatan, atau PTN** di SMAN 21 Jakarta?</p>
            </div>
        </div>

        <div class="p-3 bg-white border-t border-slate-200">
            <form onsubmit="submitAiChat(event)" class="flex gap-2">
                <input type="text" id="aiInput" placeholder="Tanyakan seputar Akademik..." class="flex-1 px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50">
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

        // 3. CHART.JS KETERSERAPAN PTN AKADEMIK
        const ptnRawData = <?= json_encode($ptnData); ?>;
        const years = ptnRawData.map(item => item.tahun);
        const snbpData = ptnRawData.map(item => parseInt(item.snbp || 0));
        const snbtData = ptnRawData.map(item => parseInt(item.snbt || 0));
        const mandiriData = ptnRawData.map(item => parseInt(item.mandiri || 0) + parseInt(item.ptln || 0) + parseInt(item.kedinasan || 0));

        const ctx = document.getElementById('ptnChartAkademik');
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
                            label: 'SNBT (Tes / UTBK)',
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

        // 4. AI ASSISTANT PANEL TOGGLE & CHAT AJAX
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
            loadingMsg.innerText = 'Mencari jawaban akademik resmi...';
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
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

// Helper untuk format path gambar relatif ke root
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
// ENDPOINT AJAX UNTUK S21 AI ASSISTANT (CONTEXT-AWARE: PROGRAM PENDIDIKAN)
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
    $sumberInfo = "Program Pendidikan SMAN 21 Jakarta";
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
                        $sumberInfo = $k['sumber_informasi'] ?: "Database Kurikulum SMAN 21";
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched && (strpos($qLower, $pertanyaanLower) !== false || strpos($pertanyaanLower, $qLower) !== false)) {
                $responseAnswer = $k['jawaban'];
                $sumberInfo = $k['sumber_informasi'] ?: "Database Kurikulum SMAN 21";
                $matched = true;
                break;
            }

            if ($matched) break;
        }

        if (!$matched) {
            if (strpos($qLower, 'merdeka') !== false || strpos($qLower, 'kurikulum') !== false) {
                $responseAnswer = "SMAN 21 Jakarta menerapkan Kurikulum Merdeka secara penuh. Fokus utama adalah penguatan literasi digital, penalaran kritis, Profil Pelajar Pancasila, serta pembelajaran berbasis proyek.";
                $sumberInfo = "Kurikulum Merdeka SMAN 21";
                $relatedLink = "program.php";
                $matched = true;
            } elseif (strpos($qLower, 'sains') !== false || strpos($qLower, 'koding') !== false || strpos($qLower, 'ka') !== false || strpos($qLower, 'ai') !== false) {
                $responseAnswer = "Program Sains, Koding & Kecerdasan Artifisial (KA) membekali siswa dengan pemahaman logika pemrograman, sains terapan, dan penggunaan AI secara etis dan produktif.";
                $sumberInfo = "Program Sains & Koding";
                $relatedLink = "program.php";
                $matched = true;
            } elseif (strpos($qLower, 'p5') !== false || strpos($qLower, 'pancasila') !== false) {
                $responseAnswer = "Proyek Penguatan Profil Pelajar Pancasila (P5) di SMAN 21 dilakukan melalui kegiatan interdisipliner seperti kewirausahaan, keberlanjutan lingkungan, dan inovasi teknologi.";
                $sumberInfo = "Proyek P5 SMAN 21";
                $relatedLink = "program.php";
                $matched = true;
            } elseif (strpos($qLower, 'unggulan') !== false) {
                $responseAnswer = "Program Unggulan SMAN 21 meliputi Bimbingan Intensif SNBP/UTBK, Innovation Lab Koding & AI, Proyek P5, serta Showcase Kewirausahaan Digital.";
                $sumberInfo = "Program Unggulan SMAN 21";
                $relatedLink = "program.php";
                $matched = true;
            }
        }

        if (!$matched) {
            $responseAnswer = $fallbackMsg;
            $sumberInfo = "Sistem Verifikasi SMAN 21";
        }

    } else {
        $responseAnswer = "SMAN 21 Jakarta menyelenggarakan Kurikulum Merdeka Unggulan yang memadukan keunggulan akademis, penguasaan koding, sains terapan, dan penguatan karakter Profil Pelajar Pancasila.";
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
// FETCH DATA RESMI SEKOLAH DARI DATABASE
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

// Fetch Data Program Pendidikan
$programPendidikan = [];
if ($conn) {
    try {
        $stmtProg = $conn->query("SELECT * FROM program_pendidikan WHERE status_aktif=1 ORDER BY urutan ASC");
        if ($stmtProg) $programPendidikan = $stmtProg->fetchAll();
    } catch (PDOException $e) {
        $programPendidikan = [];
    }
}

if (empty($programPendidikan)) {
    $programPendidikan = [
        [
            'id' => 1,
            'nama_program' => 'Kurikulum Merdeka Unggulan',
            'slug' => 'kurikulum-merdeka',
            'deskripsi' => 'Penerapan Kurikulum Merdeka secara utuh yang berpusat pada minat dan potensi peserta didik, mengutamakan kedalaman konsep, numerasi, literasi digital, serta Projek Penguatan Profil Pelajar Pancasila (P5).',
            'gambar' => '../uploads/tempat/gedungA1.jpg',
            'ikon' => 'fa-graduation-cap'
        ],
        [
            'id' => 2,
            'nama_program' => 'Sains, Koding & Kecerdasan Artifisial (KA)',
            'slug' => 'sains-koding-ka',
            'deskripsi' => 'Penguatan materi Fisika, Kimia, Biologi Terapan, Logika Koding Komputer, serta pengenalan Kecerdasan Artifisial (KA) untuk membangun fondasi teknologi siswa di masa depan.',
            'gambar' => '../uploads/tempat/gedungA.jpg',
            'ikon' => 'fa-laptop-code'
        ],
        [
            'id' => 3,
            'nama_program' => 'Sosial, Humaniora & Kepemimpinan Digital',
            'slug' => 'sosial-humaniora',
            'deskripsi' => 'Pengembangan naluri kritis Sosiologi, Geografi Lingkungan, Ekonomi Digital, dan Sejarah Lanjut yang membentuk kepemimpinan beretika dan wawasan kebangsaan modern.',
            'gambar' => '../uploads/tempat/gedungA1.jpg',
            'ikon' => 'fa-users-cog'
        ]
    ];
}

// Fetch Data Program Unggulan
$programUnggulan = [];
if ($conn) {
    try {
        $stmtUng = $conn->query("SELECT * FROM program_unggulan WHERE status_aktif=1 ORDER BY urutan ASC");
        if ($stmtUng) $programUnggulan = $stmtUng->fetchAll();
    } catch (PDOException $e) {
        $programUnggulan = [];
    }
}

if (empty($programUnggulan)) {
    $programUnggulan = [
        [
            'judul' => 'Pembinaan Intensif SNBP & UTBK-SNBT',
            'deskripsi' => 'Bimbingan akademis terstruktur, pemetaan minat universitas, tryout berkala, serta pemeringkatan untuk memaksimalkan keterserapan alumni di PTN Favorit.',
            'ikon' => 'fa-chart-line',
            'gambar' => '../uploads/tempat/gedungA1.jpg'
        ],
        [
            'judul' => 'Projek Penguatan Profil Pelajar Pancasila (P5)',
            'deskripsi' => 'Kegiatan kolaboratif interdisipliner dengan fokus pada keberlanjutan lingkungan, kearifan lokal, kewirausahaan, serta inovasi teknologi ramah lingkungan.',
            'ikon' => 'fa-hands-holding-circle',
            'gambar' => '../uploads/tempat/gedungA.jpg'
        ],
        [
            'judul' => 'Digital Koding & AI Innovation Lab',
            'deskripsi' => 'Pembelajaran komputer berbasis proyek praktis yang mengenalkan dasar logika pemrograman, pengembangan aplikasi web, serta etika penggunaan AI.',
            'ikon' => 'fa-brain',
            'gambar' => '../uploads/tempat/gedungA1.jpg'
        ],
        [
            'judul' => 'Showcase Kewirausahaan & Karya Digital',
            'deskripsi' => 'Ajang pameran tahunan karya kreatif, produk inovasi siswa, karya seni digital, dan rancangan bisnis rintisan yang melatih jiwa wirausaha muda.',
            'ikon' => 'fa-lightbulb',
            'gambar' => '../uploads/tempat/gedungA.jpg'
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
    <title>Program Pendidikan & Kurikulum — SMA NEGERI 21 JAKARTA</title>
    <meta name="description" content="Program Pendidikan & Kurikulum Merdeka SMAN 21 Jakarta. Pembelajaran Sains Terapan, Koding & Kecerdasan Artifisial, Soshum, serta Program Unggulan Sukses PTN.">
    
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
            background: linear-gradient(135deg, rgba(6, 18, 36, 0.92) 0%, rgba(11, 30, 54, 0.85) 60%, rgba(15, 23, 42, 0.88) 100%);
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
                                <a href="index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Overview Akademik</a>
                                <a href="program.php" class="block px-3 py-2 rounded-lg text-cyan-600 font-bold bg-cyan-50/50 transition-colors">Program Pendidikan</a>
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
                <a href="index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Akademik</a>
                <a href="program.php" class="block pl-3 py-1 text-xs text-cyan-600 font-bold">Program Pendidikan</a>
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
    <!-- 🎓 HERO BANNER PROGRAM PENDIDIKAN                                     -->
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
                    <a href="index.php" class="hover:underline">AKADEMIK</a>
                    <i class="fa-solid fa-chevron-right text-[9px]"></i>
                    <span class="text-white">PROGRAM PENDIDIKAN</span>
                </div>

                <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight">
                    Program Pendidikan <br><span class="text-cyan-400">& Kurikulum Merdeka</span>
                </h1>
                
                <p class="text-slate-200 text-sm sm:text-base leading-relaxed">
                    Struktur kurikulum komprehensif SMAN 21 Jakarta dirancang untuk mengembangkan kecerdasan intelektual, keterampilan koding digital, penalaran kritis, dan Profil Pelajar Pancasila.
                </p>

                <!-- Badges -->
                <div class="pt-2 flex flex-wrap gap-2.5">
                    <span class="px-3 py-1 rounded-full bg-cyan-500/20 border border-cyan-400/30 text-cyan-300 text-xs font-bold flex items-center gap-2">
                        <i class="fa-solid fa-certificate"></i> Kurikulum Merdeka
                    </span>
                    <span class="px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-bold flex items-center gap-2">
                        <i class="fa-solid fa-laptop-code"></i> Koding & AI Ready
                    </span>
                    <span class="px-3 py-1 rounded-full bg-amber-500/20 border border-amber-400/30 text-amber-300 text-xs font-bold flex items-center gap-2">
                        <i class="fa-solid fa-shapes"></i> Projek P5 Interdisipliner
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📚 SECTION 1: PROGRAM PENDIDIKAN UTAMA                                -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            
            <div class="text-center max-w-3xl mx-auto space-y-2 reveal-section">
                <span class="text-xs font-bold uppercase tracking-widest text-cyan-600 bg-cyan-50 px-3 py-1 rounded border border-cyan-200">
                    PILAR PEMBELAJARAN
                </span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy">Fokus Kelompok Peminatan</h2>
                <p class="text-xs sm:text-sm text-slate-500">
                    Memandu setiap peserta didik menemukan potensi terbaiknya melalui kurikulum yang adaptif, berbasis minat, dan relevan dengan tantangan global.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <?php $progIdx = 1; foreach ($programPendidikan as $prog): 
                    $imgSrc = formatImgPath($prog['gambar'] ?? '');
                ?>
                <div class="school-card rounded-2xl bg-white border border-slate-200 overflow-hidden flex flex-col justify-between reveal-section delay-<?= $progIdx * 100; ?>">
                    <div>
                        <!-- Image Cover -->
                        <div class="relative h-48 overflow-hidden bg-slate-900">
                            <img src="<?= htmlspecialchars($imgSrc); ?>" alt="<?= htmlspecialchars($prog['nama_program']); ?>" class="w-full h-full object-cover opacity-90 hover:scale-105 transition-transform duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-brand-darkNavy/80 via-transparent to-transparent"></div>
                            <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between">
                                <span class="px-3 py-1 rounded-lg bg-cyan-600 text-white text-[10px] font-bold uppercase tracking-wider">
                                    Fase E & F
                                </span>
                                <div class="w-10 h-10 rounded-lg bg-white/90 backdrop-blur text-cyan-700 flex items-center justify-center text-lg shadow">
                                    <i class="fa-solid <?= htmlspecialchars($prog['ikon'] ?? 'fa-graduation-cap'); ?>"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Content Body -->
                        <div class="p-6 space-y-3">
                            <h3 class="text-lg font-extrabold text-brand-darkNavy leading-snug">
                                <?= htmlspecialchars($prog['nama_program']); ?>
                            </h3>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                <?= htmlspecialchars($prog['deskripsi']); ?>
                            </p>
                        </div>
                    </div>

                    <div class="p-6 pt-0">
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-cyan-600">
                            <span>Sesuai Standar Kemendikbudristek</span>
                            <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                        </div>
                    </div>
                </div>
                <?php $progIdx++; endforeach; ?>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🚀 SECTION 2: PROGRAM UNGGULAN SEKOLAH                                -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 reveal-section">
                <div class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-widest text-emerald-700 bg-emerald-50 px-3 py-1 rounded border border-emerald-200">
                        FLAGSHIP PROGRAMS
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy">Program Unggulan SMAN 21</h2>
                    <p class="text-xs sm:text-sm text-slate-500 max-w-xl">
                        Inisiatif inovatif yang memberikan nilai tambah bagi kesiapan akademik, karakter Pancasila, serta keberhasilan studi lanjut siswa.
                    </p>
                </div>
                <button onclick="toggleAiPanel()" class="px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition-all shadow inline-flex items-center gap-2 self-start md:self-auto">
                    <i class="fa-solid fa-comments text-xs"></i>
                    <span>Konsultasi Program via AI</span>
                </button>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php $ungIdx = 1; foreach ($programUnggulan as $ung): ?>
                <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 space-y-4 reveal-section delay-<?= $ungIdx * 100; ?>">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl">
                        <i class="fa-solid <?= htmlspecialchars($ung['ikon'] ?? 'fa-star'); ?>"></i>
                    </div>
                    <h3 class="text-base font-extrabold text-brand-darkNavy leading-snug">
                        <?= htmlspecialchars($ung['judul']); ?>
                    </h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        <?= htmlspecialchars($ung['deskripsi']); ?>
                    </p>
                </div>
                <?php $ungIdx++; endforeach; ?>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🔄 SECTION 3: SKEMA TAHAPAN KURIKULUM MERDEKA                       -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            
            <div class="text-center max-w-2xl mx-auto space-y-2 reveal-section">
                <span class="text-xs font-bold uppercase tracking-widest text-cyan-600">STAGIING PEMBELAJARAN</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy">Tahapan Fase Pembelajaran</h2>
                <p class="text-xs sm:text-sm text-slate-500">
                    Alur perkembangan peserta didik dari kelas X (Fase E) hingga kelas XI & XII (Fase F).
                </p>
            </div>

            <div class="grid lg:grid-cols-2 gap-8">
                
                <!-- Fase E (Kelas X) -->
                <div class="school-card p-8 rounded-2xl bg-slate-50 border border-slate-200 space-y-5 reveal-section">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                        <div>
                            <span class="text-[10px] font-bold text-cyan-700 bg-cyan-100 px-2.5 py-1 rounded border border-cyan-300 uppercase">FASE E</span>
                            <h3 class="text-xl font-extrabold text-brand-darkNavy mt-1">Kelas X (Sepuluh)</h3>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-cyan-600 text-white flex items-center justify-center font-bold text-sm">
                            10
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 leading-relaxed">
                        Masa fondasi dan eksplorasi umum. Siswa mempelajari seluruh mata pelajaran umum untuk menemukan minat utama sebelum menentukan pilihan kelompok tingkat lanjut.
                    </p>

                    <div class="space-y-2 pt-2">
                        <div class="flex items-center gap-2.5 text-xs text-slate-700">
                            <i class="fa-solid fa-check-circle text-cyan-600"></i>
                            <span>Eksplorasi seluruh rumpun mata pelajaran dasar</span>
                        </div>
                        <div class="flex items-center gap-2.5 text-xs text-slate-700">
                            <i class="fa-solid fa-check-circle text-cyan-600"></i>
                            <span>Asesmen bakat & minat karir oleh Bimbingan Konseling</span>
                        </div>
                        <div class="flex items-center gap-2.5 text-xs text-slate-700">
                            <i class="fa-solid fa-check-circle text-cyan-600"></i>
                            <span>Projek P5 (Penguatan Profil Pelajar Pancasila) Blok 1 & 2</span>
                        </div>
                    </div>
                </div>

                <!-- Fase F (Kelas XI & XII) -->
                <div class="school-card p-8 rounded-2xl bg-brand-darkNavy text-white space-y-5 reveal-section delay-200">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                        <div>
                            <span class="text-[10px] font-bold text-cyan-300 bg-cyan-500/20 px-2.5 py-1 rounded border border-cyan-400/30 uppercase">FASE F</span>
                            <h3 class="text-xl font-extrabold text-white mt-1">Kelas XI & XII</h3>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-cyan-500 text-slate-900 flex items-center justify-center font-bold text-sm">
                            11-12
                        </div>
                    </div>

                    <p class="text-xs text-slate-300 leading-relaxed">
                        Masa pemanduan minat karir. Siswa memilih kombinasi mata pelajaran pilihan (Saintek, Soshum, Terapan) yang disesuaikan dengan target Jurusan Perguruan Tinggi Negeri.
                    </p>

                    <div class="space-y-2 pt-2">
                        <div class="flex items-center gap-2.5 text-xs text-slate-200">
                            <i class="fa-solid fa-check-circle text-cyan-400"></i>
                            <span>Pendalaman mata pelajaran Pilihan Utama & Lanjutan</span>
                        </div>
                        <div class="flex items-center gap-2.5 text-xs text-slate-200">
                            <i class="fa-solid fa-check-circle text-cyan-400"></i>
                            <span>Simulasi Tryout & Klinik UTBK-SNBT berkala</span>
                        </div>
                        <div class="flex items-center gap-2.5 text-xs text-slate-200">
                            <i class="fa-solid fa-check-circle text-cyan-400"></i>
                            <span>Praktikum Laboratorium Koding, AI, & Riset Ilmiah</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🤖 BANNER AI ASSISTANT                                                 -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-slate-900 text-white relative overflow-hidden">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 reveal-section">
            <div class="p-8 sm:p-12 rounded-2xl bg-gradient-to-r from-brand-darkNavy to-slate-800 border border-slate-700 text-center space-y-5 shadow-2xl">
                <div class="w-14 h-14 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center mx-auto text-2xl border border-cyan-500/30">
                    <i class="fa-solid fa-circle-question"></i>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white">
                    Tanyakan Seputar Kurikulum SMAN 21
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                    S21 AI Assistant dapat membantu Anda memberikan informasi mendalam mengenai Struktur Kurikulum, Mapel Pilihan, dan Bimbingan Sukses PTN.
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
                        <li><a href="index.php" class="hover:text-cyan-400 transition-colors">Overview Akademik</a></li>
                        <li><a href="program.php" class="text-cyan-400 font-bold">Program Pendidikan</a></li>
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
                <p>Kamu sedang melihat **Program Pendidikan**. Ada yang ingin ditanyakan seputar Kurikulum Merdeka, Sains Terapan, Koding & AI, atau Projek P5?</p>
            </div>
        </div>

        <div class="p-3 bg-white border-t border-slate-200">
            <form onsubmit="submitAiChat(event)" class="flex gap-2">
                <input type="text" id="aiInput" placeholder="Tanyakan seputar Program Pendidikan..." class="flex-1 px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50">
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

        // 3. AI ASSISTANT PANEL TOGGLE & CHAT AJAX
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
            loadingMsg.innerText = 'Mencari jawaban kurikulum...';
            chatBox.appendChild(loadingMsg);
            chatBox.scrollTop = chatBox.scrollHeight;

            fetch('program.php?action=ai_query', {
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
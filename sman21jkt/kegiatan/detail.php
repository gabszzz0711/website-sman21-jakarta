<?php
// =============================================================================
// KONFIGURASI DATABASE & KONEKSI BYETHOST SMAN 21 JAKARTA
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
// HELPER PERBAIKAN PATH GAMBAR PRESTASI
// =============================================================================
function formatPrestasiImg($img, $default = '../uploads/tempat/gedungA1.jpg') {
    if (empty($img)) return $default;
    $img = trim($img);
    
    if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
        return $img;
    }
    
    if (strpos($img, 'uploads/') === 0) {
        return '../' . $img;
    }
    
    return '../uploads/prestasi/' . ltrim($img, '/');
}

// =============================================================================
// FETCH DATA DETAIL PRESTASI
// =============================================================================
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

$prestasi = null;

if ($conn) {
    try {
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT * FROM prestasi WHERE id = :id AND (status_aktif = 1 OR status_aktif IS NULL) LIMIT 1");
            $stmt->execute([':id' => $id]);
            $prestasi = $stmt->fetch();
        } elseif (!empty($slug)) {
            $stmt = $conn->prepare("SELECT * FROM prestasi WHERE slug = :slug AND (status_aktif = 1 OR status_aktif IS NULL) LIMIT 1");
            $stmt->execute([':slug' => $slug]);
            $prestasi = $stmt->fetch();
        }

        // Jika tidak ditemukan ID/slug tertentu, ambil prestasi terbaru sebagai default
        if (!$prestasi) {
            $stmt = $conn->query("SELECT * FROM prestasi WHERE (status_aktif = 1 OR status_aktif IS NULL) ORDER BY id DESC LIMIT 1");
            if ($stmt) $prestasi = $stmt->fetch();
        }
    } catch (PDOException $e) {
        $prestasi = null;
    }
}

// Fallback data simulasi jika database belum terisi / offline
if (!$prestasi) {
    $prestasi = [
        'id' => 1,
        'judul_prestasi' => 'Juara 1 Olimpiade Sains Nasional (OSN) Bidang Informatika & Komputer',
        'slug' => 'juara-1-osn-informatika-2026',
        'kategori' => 'Akademik',
        'tingkat' => 'Nasional',
        'nama_siswa' => 'Ahmad Raihan & Tim Informatika SMAN 21',
        'peringkat' => 'Juara 1 (Medali Emas)',
        'penyelenggara' => 'Pusat Prestasi Nasional (Puspresnas) Kemendikbudristek',
        'pembimbing' => 'Drs. H. Mulyadi, M.Kom',
        'tahun' => '2026',
        'tanggal' => '2026-08-15',
        'deskripsi' => '<p>Tim Olimpiade Komputer SMAN 21 Jakarta kembali mengukir prestasi gemilang tingkat nasional dengan meraih Medali Emas dalam ajang Olimpiade Sains Nasional (OSN) Bidang Informatika tahun 2026.</p><p>Kompetisi yang berlangsung ketat selama lima hari di Yogyakarta ini diikuti oleh lebih dari 100 perwakilan sekolah terbaik dari seluruh provinsi di Indonesia. Peserta diuji dalam pemecahan masalah algoritma kompleks, pemrograman dinamis, serta efisiensi struktur data.</p><p>Keberhasilan ini menjadi bukti nyata komitmen SMAN 21 Jakarta dalam mengembangkan bakat sains digital dan teknologi unggulan bagi para peserta didik.</p>',
        'foto' => 'uploads/tempat/gedungA1.jpg',
        'status_aktif' => 1
    ];
}

// Fetch Prestasi Lainnya untuk Sidebar
$prestasiLainnya = [];
if ($conn) {
    try {
        $currentId = $prestasi['id'] ?? 0;
        $stmtLain = $conn->prepare("SELECT * FROM prestasi WHERE id != :id AND (status_aktif = 1 OR status_aktif IS NULL) ORDER BY id DESC LIMIT 4");
        $stmtLain->execute([':id' => $currentId]);
        $prestasiLainnya = $stmtLain->fetchAll();
    } catch (PDOException $e) {
        $prestasiLainnya = [];
    }
}

// Profil & Informasi Sekolah
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
    try {
        $stmtSek = $conn->query("SELECT * FROM pengaturan_sekolah LIMIT 1");
        if ($stmtSek && $rowSek = $stmtSek->fetch()) {
            if (!empty($rowSek['nama_sekolah'])) $sekolah['nama_sekolah'] = $rowSek['nama_sekolah'];
            if (!empty($rowSek['alamat'])) $sekolah['alamat'] = $rowSek['alamat'];
            if (!empty($rowSek['telepon'])) $sekolah['telepon'] = $rowSek['telepon'];
            if (!empty($rowSek['email'])) $sekolah['email'] = $rowSek['email'];
            if (!empty($rowSek['instagram'])) $sekolah['instagram'] = $rowSek['instagram'];
        }
    } catch (PDOException $e) {
        // Fallback default
    }
}

$logoPath = '../uploads/logo/sman21jakarta.png';
$mainImg = formatPrestasiImg($prestasi['foto'] ?? $prestasi['gambar'] ?? '', '../uploads/tempat/gedungA1.jpg');
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($prestasi['judul_prestasi']); ?> — SMAN 21 Jakarta</title>
    <meta name="description" content="Detail prestasi siswa SMAN 21 Jakarta: <?= htmlspecialchars($prestasi['judul_prestasi']); ?>. Tingkat <?= htmlspecialchars($prestasi['tingkat'] ?? 'Nasional'); ?>.">
    
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

    <!-- Font Awesome Icons -->
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

        .school-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04);
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .school-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 30px -4px rgba(15, 23, 42, 0.09);
            border-color: #CBD5E1;
        }

        /* Format HTML Content Styling */
        .prestasi-body {
            color: #334155;
            font-size: 0.95rem;
            line-height: 1.8;
        }
        .prestasi-body p {
            margin-bottom: 1.1rem;
        }
        .prestasi-body p:last-child {
            margin-bottom: 0;
        }
        .prestasi-body strong, .prestasi-body b {
            font-weight: 700;
            color: #061224;
        }
        .prestasi-body ul {
            list-style-type: disc;
            padding-left: 1.3rem;
            margin-bottom: 1rem;
        }
        .prestasi-body ol {
            list-style-type: decimal;
            padding-left: 1.3rem;
            margin-bottom: 1rem;
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

                    <a href="index.php" class="px-3.5 py-2 rounded-lg text-cyan-600 bg-cyan-50 font-bold border border-cyan-200 transition-all">Prestasi</a>
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
                <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Profil</span>
                <a href="../profil/index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Profil</a>
                <a href="../profil/sejarah.php" class="block pl-3 py-1 text-xs text-slate-700">Sejarah</a>
                <a href="../profil/visi-misi.php" class="block pl-3 py-1 text-xs text-slate-700">Visi & Misi</a>
                <a href="../profil/sambutan.php" class="block pl-3 py-1 text-xs text-slate-700">Sambutan Kepala Sekolah</a>
            </div>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Akademik</span>
                <a href="../akademik/index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Akademik</a>
                <a href="../akademik/guru.php" class="block pl-3 py-1 text-xs text-slate-700">Daftar Guru</a>
                <a href="../akademik/keterserapan-ptn.php" class="block pl-3 py-1 text-xs text-slate-700">Keterserapan PTN</a>
            </div>
            <a href="index.php" class="block text-cyan-600 font-bold text-xs">Prestasi Siswa</a>
            <a href="../kegiatan/index.php" class="block text-xs font-semibold text-slate-700">Kegiatan & Ekskul</a>
            <a href="../informasi/ppdb.php" class="block text-xs font-semibold text-slate-700">PPDB Info</a>
            <a href="../kontak/index.php" class="block text-xs font-semibold text-slate-700">Kontak</a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 🏆 HERO BANNER & BREADCRUMB                                           -->
    <!-- ===================================================================== -->
    <section class="bg-brand-darkNavy text-white py-12 sm:py-16 relative overflow-hidden border-b border-slate-800">
        <div class="absolute inset-0 opacity-10 bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($mainImg); ?>');"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-brand-darkNavy via-brand-navy/95 to-brand-darkNavy"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <!-- Breadcrumb Navigation -->
            <nav class="flex items-center gap-2 text-xs text-slate-400 mb-6 flex-wrap">
                <a href="../index.php" class="hover:text-cyan-400 transition-colors flex items-center gap-1">
                    <i class="fa-solid fa-house text-[10px]"></i>
                    <span>Beranda</span>
                </a>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-600"></i>
                <a href="index.php" class="hover:text-cyan-400 transition-colors">Prestasi</a>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-600"></i>
                <span class="text-cyan-400 font-medium truncate max-w-xs sm:max-w-md">Detail Prestasi</span>
            </nav>

            <div class="max-w-4xl space-y-4 reveal-section is-visible">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="px-3 py-1 rounded-md bg-cyan-500/20 border border-cyan-400/30 text-cyan-300 text-xs font-bold uppercase tracking-wider">
                        <i class="fa-solid fa-trophy mr-1 text-cyan-400"></i>
                        <?= htmlspecialchars($prestasi['kategori'] ?? 'Akademik'); ?>
                    </span>
                    <span class="px-3 py-1 rounded-md bg-white/10 border border-white/20 text-slate-200 text-xs font-semibold uppercase tracking-wider">
                        Tingkat <?= htmlspecialchars($prestasi['tingkat'] ?? 'Nasional'); ?>
                    </span>
                    <?php if (!empty($prestasi['tahun'])): ?>
                    <span class="px-3 py-1 rounded-md bg-white/5 text-slate-300 text-xs font-medium">
                        <i class="fa-regular fa-calendar mr-1"></i>Tahun <?= htmlspecialchars($prestasi['tahun']); ?>
                    </span>
                    <?php endif; ?>
                </div>

                <h1 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight">
                    <?= htmlspecialchars($prestasi['judul_prestasi']); ?>
                </h1>

                <div class="pt-2 flex items-center gap-4 text-xs sm:text-sm text-cyan-300 font-medium">
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-medal text-amber-400"></i>
                        <strong><?= htmlspecialchars($prestasi['peringkat'] ?? 'Juara 1'); ?></strong>
                    </span>
                    <span>•</span>
                    <span class="flex items-center gap-1.5 text-slate-300">
                        <i class="fa-solid fa-user-graduate text-cyan-400"></i>
                        <?= htmlspecialchars($prestasi['nama_siswa'] ?? 'Siswa SMAN 21'); ?>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📄 KONTEN DETAIL PRESTASI & SIDEBAR                                   -->
    <!-- ===================================================================== -->
    <section class="py-12 sm:py-16 bg-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-8 lg:gap-12">
                
                <!-- KOLOM UTAMA (8 COLS) -->
                <div class="lg:col-span-8 space-y-8">
                    
                    <!-- Foto Utam / Dokumentasi Prestasi -->
                    <div class="school-card rounded-2xl overflow-hidden bg-white border border-slate-200 p-2 reveal-section">
                        <div class="relative w-full aspect-[16/9] sm:aspect-[16/9] rounded-xl overflow-hidden bg-slate-900 group">
                            <img src="<?= htmlspecialchars($mainImg); ?>" 
                                 alt="<?= htmlspecialchars($prestasi['judul_prestasi']); ?>" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                                 onerror="this.onerror=null; this.src='../uploads/tempat/gedungA1.jpg';">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-60"></div>
                            <div class="absolute bottom-4 left-4 right-4 text-white text-xs font-semibold drop-shadow flex justify-between items-center">
                                <span><i class="fa-solid fa-camera mr-1.5 text-cyan-400"></i>Dokumentasi SMAN 21 Jakarta</span>
                                <span class="bg-black/40 backdrop-blur px-2.5 py-1 rounded text-[10px] uppercase font-bold text-cyan-300">Resmi</span>
                            </div>
                        </div>
                    </div>

                    <!-- Highlight Ringkasan Metrik Prestasi -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 reveal-section delay-100">
                        <div class="school-card p-4 rounded-xl bg-white border border-slate-200 text-center">
                            <i class="fa-solid fa-award text-xl text-cyan-600 mb-1"></i>
                            <span class="text-[10px] text-slate-400 uppercase font-bold block">Peringkat</span>
                            <span class="text-xs font-extrabold text-brand-darkNavy leading-tight block mt-0.5"><?= htmlspecialchars($prestasi['peringkat'] ?? 'Juara'); ?></span>
                        </div>
                        <div class="school-card p-4 rounded-xl bg-white border border-slate-200 text-center">
                            <i class="fa-solid fa-globe text-xl text-blue-600 mb-1"></i>
                            <span class="text-[10px] text-slate-400 uppercase font-bold block">Tingkat</span>
                            <span class="text-xs font-extrabold text-brand-darkNavy leading-tight block mt-0.5"><?= htmlspecialchars($prestasi['tingkat'] ?? 'Nasional'); ?></span>
                        </div>
                        <div class="school-card p-4 rounded-xl bg-white border border-slate-200 text-center">
                            <i class="fa-solid fa-layer-group text-xl text-emerald-600 mb-1"></i>
                            <span class="text-[10px] text-slate-400 uppercase font-bold block">Kategori</span>
                            <span class="text-xs font-extrabold text-brand-darkNavy leading-tight block mt-0.5"><?= htmlspecialchars($prestasi['kategori'] ?? 'Umum'); ?></span>
                        </div>
                        <div class="school-card p-4 rounded-xl bg-white border border-slate-200 text-center">
                            <i class="fa-solid fa-calendar-check text-xl text-purple-600 mb-1"></i>
                            <span class="text-[10px] text-slate-400 uppercase font-bold block">Tahun</span>
                            <span class="text-xs font-extrabold text-brand-darkNavy leading-tight block mt-0.5"><?= htmlspecialchars($prestasi['tahun'] ?? '2026'); ?></span>
                        </div>
                    </div>

                    <!-- Deskripsi & Rincian Prestasi -->
                    <div class="school-card p-6 sm:p-8 rounded-2xl bg-white border border-slate-200 space-y-6 reveal-section delay-200">
                        <div class="border-b border-slate-100 pb-4 flex items-center justify-between">
                            <h2 class="text-lg font-extrabold text-brand-darkNavy flex items-center gap-2">
                                <i class="fa-solid fa-file-lines text-cyan-600"></i>
                                <span>Deskripsi Lengkap Kejuaraan</span>
                            </h2>
                            <span class="text-xs text-slate-400">SMAN 21 Jakarta</span>
                        </div>

                        <div class="prestasi-body font-normal">
                            <?= $prestasi['deskripsi']; ?>
                        </div>

                        <!-- Informasi Tambahan / Penyelenggara & Pembimbing -->
                        <div class="pt-4 border-t border-slate-100 grid sm:grid-cols-2 gap-4 text-xs bg-slate-50 p-4 rounded-xl border border-slate-200">
                            <div>
                                <span class="text-[10px] text-slate-400 uppercase font-bold block">Penyelenggara</span>
                                <span class="font-bold text-brand-darkNavy block mt-0.5"><?= htmlspecialchars($prestasi['penyelenggara'] ?? 'Kementerian Pendidikan & Kebudayaan'); ?></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 uppercase font-bold block">Pembimbing / Pelatih</span>
                                <span class="font-bold text-brand-darkNavy block mt-0.5"><?= htmlspecialchars($prestasi['pembimbing'] ?? 'Tim Pembina SMAN 21'); ?></span>
                            </div>
                        </div>

                        <!-- Tombol Bagikan -->
                        <div class="pt-2 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100">
                            <span class="text-xs font-bold text-slate-600">Bagikan Prestasi Ini:</span>
                            <div class="flex items-center gap-2">
                                <a href="https://api.whatsapp.com/send?text=<?= urlencode($prestasi['judul_prestasi'] . ' - SMAN 21 Jakarta'); ?>" target="_blank" class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 hover:bg-emerald-600 hover:text-white flex items-center justify-center transition-colors text-xs">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </a>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 hover:bg-blue-600 hover:text-white flex items-center justify-center transition-colors text-xs">
                                    <i class="fa-brands fa-facebook-f"></i>
                                </a>
                                <button onclick="copyToClipboard()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-700 hover:bg-cyan-600 hover:text-white flex items-center justify-center transition-colors text-xs" title="Salin Tautan">
                                    <i class="fa-solid fa-link"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Kembali -->
                    <div class="pt-2">
                        <a href="index.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold transition-all shadow-sm">
                            <i class="fa-solid fa-arrow-left"></i>
                            <span>Kembali ke Daftar Prestasi</span>
                        </a>
                    </div>

                </div>

                <!-- SIDEBAR KANAN (4 COLS) -->
                <div class="lg:col-span-4 space-y-6">
                    
                    <!-- Card Informasi Pemenang -->
                    <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 space-y-4 reveal-section">
                        <h3 class="text-sm font-extrabold text-brand-darkNavy border-b border-slate-100 pb-3 flex items-center gap-2">
                            <i class="fa-solid fa-user-check text-cyan-600"></i>
                            <span>Profil Peraih Prestasi</span>
                        </h3>
                        <div class="space-y-3 text-xs">
                            <div>
                                <span class="text-slate-400 font-semibold block text-[10px] uppercase">Nama Peserta / Tim</span>
                                <span class="font-extrabold text-brand-darkNavy text-sm block mt-0.5"><?= htmlspecialchars($prestasi['nama_siswa'] ?? 'Siswa SMAN 21'); ?></span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-semibold block text-[10px] uppercase">Capaian</span>
                                <span class="font-bold text-cyan-700 block mt-0.5"><?= htmlspecialchars($prestasi['peringkat'] ?? 'Juara'); ?></span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-semibold block text-[10px] uppercase">Tingkat Kompetisi</span>
                                <span class="font-bold text-slate-800 block mt-0.5">Tingkat <?= htmlspecialchars($prestasi['tingkat'] ?? 'Nasional'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Prestasi Lainnya -->
                    <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 space-y-4 reveal-section delay-100">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h3 class="text-sm font-extrabold text-brand-darkNavy flex items-center gap-2">
                                <i class="fa-solid fa-trophy text-amber-500"></i>
                                <span>Prestasi Lainnya</span>
                            </h3>
                            <a href="index.php" class="text-[11px] font-bold text-cyan-600 hover:underline">Lihat Semua</a>
                        </div>

                        <div class="space-y-4">
                            <?php if (!empty($prestasiLainnya)): foreach ($prestasiLainnya as $pLain): 
                                $pImg = formatPrestasiImg($pLain['foto'] ?? $pLain['gambar'] ?? '', '../uploads/tempat/gedungA1.jpg');
                                $pLink = 'detail.php?id=' . $pLain['id'];
                                if (!empty($pLain['slug'])) {
                                    $pLink = 'detail.php?slug=' . htmlspecialchars($pLain['slug']);
                                }
                            ?>
                            <a href="<?= $pLink; ?>" class="flex gap-3 group items-center">
                                <div class="w-14 h-14 rounded-lg bg-slate-100 overflow-hidden flex-shrink-0 border border-slate-200">
                                    <img src="<?= htmlspecialchars($pImg); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" onerror="this.onerror=null; this.src='../uploads/tempat/gedungA1.jpg';">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <span class="text-[9px] font-bold text-cyan-600 uppercase tracking-wider block"><?= htmlspecialchars($pLain['kategori'] ?? 'Prestasi'); ?> • <?= htmlspecialchars($pLain['tingkat'] ?? 'Provinsi'); ?></span>
                                    <h4 class="text-xs font-bold text-brand-darkNavy group-hover:text-cyan-600 transition-colors truncate mt-0.5"><?= htmlspecialchars($pLain['judul_prestasi']); ?></h4>
                                    <p class="text-[10px] text-slate-500 truncate mt-0.5"><?= htmlspecialchars($pLain['nama_siswa'] ?? ''); ?></p>
                                </div>
                            </a>
                            <?php endforeach; else: ?>
                            <p class="text-xs text-slate-500 italic">Belum ada data prestasi lainnya.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Banner Tanya AI Assistant -->
                    <div class="p-6 rounded-2xl bg-gradient-to-br from-brand-darkNavy to-slate-900 text-white space-y-3 shadow-lg border border-slate-800 reveal-section delay-200">
                        <div class="w-10 h-10 rounded-lg bg-cyan-500/20 text-cyan-400 flex items-center justify-center text-lg border border-cyan-500/30">
                            <i class="fa-solid fa-robot"></i>
                        </div>
                        <h4 class="text-sm font-extrabold text-white">S21 AI Assistant</h4>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Ingin tahu info PPDB, Kurikulum, atau Beasiswa Jalur Prestasi di SMAN 21 Jakarta?
                        </p>
                        <button onclick="toggleAiPanel()" class="w-full py-2.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs uppercase tracking-wider transition-colors flex items-center justify-center gap-2">
                            <i class="fa-solid fa-comments"></i>
                            <span>Tanyakan AI</span>
                        </button>
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
                        <li><a href="index.php" class="hover:text-cyan-400 transition-colors">Galeri Prestasi Siswa</a></li>
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
                <p>Ada yang bisa saya bantu terkait SPMB/PPDB, Jalur Prestasi, Kurikulum, atau Kegiatan SMAN 21 Jakarta?</p>
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

        // 3. COPY LINK TO CLIPBOARD
        function copyToClipboard() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('Tautan halaman berhasil disalin!');
            }).catch(() => {
                alert('Gagal menyalin tautan.');
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
            loadingMsg.innerText = 'Mencari jawaban resmi...';
            chatBox.appendChild(loadingMsg);
            chatBox.scrollTop = chatBox.scrollHeight;

            fetch('../index.php?action=ai_query', {
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
                    linkHtml = `<div class="pt-1"><a href="../${data.related_link}" class="text-cyan-600 font-bold hover:underline inline-flex items-center gap-1">Pelajari lebih lanjut <i class="fa-solid fa-arrow-right text-[9px]"></i></a></div>`;
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
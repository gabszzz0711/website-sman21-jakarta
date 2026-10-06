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
// FETCH DATA PROFIL SEKOLAH
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
            if (!empty($rowSek['youtube'])) $sekolah['youtube'] = $rowSek['youtube'];
        }
    } catch (PDOException $e) {
        // Fallback default
    }
}

$logoPath = $sekolah['logo'];

// =============================================================================
// FETCH DATA PEMERINGKATAN SISWA / ELIGIBLE SNBP
// =============================================================================
$pemeringkatanList = [];
if ($conn) {
    try {
        $stmtP = $conn->query("SELECT * FROM pemeringkatan ORDER BY ranking ASC, rata_rata DESC");
        if ($stmtP) {
            $pemeringkatanList = $stmtP->fetchAll();
        }
    } catch (PDOException $e) {
        $pemeringkatanList = [];
    }
}

// Fallback data simulasi pemeringkatan jika tabel belum diisi
if (empty($pemeringkatanList)) {
    $pemeringkatanList = [
        [
            'id' => 1,
            'nisn' => '0061289011',
            'nama_siswa' => 'Ahmad Fauzi',
            'jurusan' => 'MIPA',
            'kelas' => 'XII MIPA 1',
            'rata_rata' => 93.85,
            'ranking' => 1,
            'status_eligible' => 'Eligible SNBP (Top 40%)',
            'ptn_tujuan' => 'Universitas Indonesia (UI) - Teknik Informatika',
            'tahun_ajaran' => '2025/2026'
        ],
        [
            'id' => 2,
            'nisn' => '0062345678',
            'nama_siswa' => 'Siti Nurhaliza',
            'jurusan' => 'MIPA',
            'kelas' => 'XII MIPA 2',
            'rata_rata' => 92.90,
            'ranking' => 2,
            'status_eligible' => 'Eligible SNBP (Top 40%)',
            'ptn_tujuan' => 'Institut Teknologi Bandung (ITB) - STEI',
            'tahun_ajaran' => '2025/2026'
        ],
        [
            'id' => 3,
            'nisn' => '0063456789',
            'nama_siswa' => 'Budi Santoso',
            'jurusan' => 'IPS',
            'kelas' => 'XII IPS 1',
            'rata_rata' => 92.40,
            'ranking' => 1,
            'status_eligible' => 'Eligible SNBP (Top 40%)',
            'ptn_tujuan' => 'Universitas Gadjah Mada (UGM) - Akuntansi',
            'tahun_ajaran' => '2025/2026'
        ],
        [
            'id' => 4,
            'nisn' => '0064567890',
            'nama_siswa' => 'Nabila Putri',
            'jurusan' => 'IPS',
            'kelas' => 'XII IPS 2',
            'rata_rata' => 91.75,
            'ranking' => 2,
            'status_eligible' => 'Eligible SNBP (Top 40%)',
            'ptn_tujuan' => 'Universitas Indonesia (UI) - Ilmu Hukum',
            'tahun_ajaran' => '2025/2026'
        ],
        [
            'id' => 5,
            'nisn' => '0065678901',
            'nama_siswa' => 'Rizky Febrian',
            'jurusan' => 'Kurikulum Merdeka',
            'kelas' => 'XII F-1',
            'rata_rata' => 91.20,
            'ranking' => 1,
            'status_eligible' => 'Eligible SNBP (Top 40%)',
            'ptn_tujuan' => 'IPB University - Ilmu Komputer',
            'tahun_ajaran' => '2025/2026'
        ],
        [
            'id' => 6,
            'nisn' => '0066789012',
            'nama_siswa' => 'Dewi Anggraini',
            'jurusan' => 'MIPA',
            'kelas' => 'XII MIPA 3',
            'rata_rata' => 90.80,
            'ranking' => 3,
            'status_eligible' => 'Eligible SNBP (Top 40%)',
            'ptn_tujuan' => 'Universitas Padjadjaran (UNPAD) - Kedokteran',
            'tahun_ajaran' => '2025/2026'
        ],
        [
            'id' => 7,
            'nisn' => '0067890123',
            'nama_siswa' => 'Farhan Pratama',
            'jurusan' => 'IPS',
            'kelas' => 'XII IPS 1',
            'rata_rata' => 90.15,
            'ranking' => 3,
            'status_eligible' => 'Eligible SNBP (Top 40%)',
            'ptn_tujuan' => 'Universitas Airlangga (UNAIR) - Manajemen',
            'tahun_ajaran' => '2025/2026'
        ],
        [
            'id' => 8,
            'nisn' => '0068901234',
            'nama_siswa' => 'Anisa Rahma',
            'jurusan' => 'Kurikulum Merdeka',
            'kelas' => 'XII F-2',
            'rata_rata' => 89.90,
            'ranking' => 2,
            'status_eligible' => 'Eligible SNBP (Top 40%)',
            'ptn_tujuan' => 'Institut Teknologi Sepuluh Nopember (ITS) - Teknik Sipil',
            'tahun_ajaran' => '2025/2026'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pemeringkatan Jurusan & Kuota SNBP — SMAN 21 Jakarta</title>
    <meta name="description" content="Sistem Pemeringkatan Evaluasi Nilai Rapor & Kuota Eligible SNBP SMAN 21 Jakarta. Transparan, Objektif, dan Berbasis Prestasi Akademik.">
    
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
            transform: translateY(-4px);
            box-shadow: 0 14px 30px -4px rgba(15, 23, 42, 0.09);
            border-color: #CBD5E1;
        }

        .tab-btn.active {
            background-color: #00A8CC;
            color: #FFFFFF;
            border-color: #00A8CC;
            box-shadow: 0 4px 14px rgba(0, 168, 204, 0.3);
        }

        ::-webkit-scrollbar { width: 7px; height: 7px; }
        ::-webkit-scrollbar-track { background: #061224; }
        ::-webkit-scrollbar-thumb { background: #00A8CC; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #0086A3; }
    </style>
</head>
<body class="relative min-h-screen text-slate-800 bg-slate-50 selection:bg-cyan-500 selection:text-white max-w-full overflow-x-hidden">

    <!-- ===================================================================== -->
    <!-- 🧭 TOP HEADER BAR                                                      -->
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

    <!-- ===================================================================== -->
    <!-- 🧭 MAIN NAVIGATION HEADER                                             -->
    <!-- ===================================================================== -->
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

                    <!-- Profil Dropdown -->
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

                    <!-- Akademik Dropdown (Active Menu) -->
                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-lg text-cyan-600 bg-cyan-50 font-bold border border-cyan-200 flex items-center gap-1.5 transition-all">
                            <span>Akademik</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full left-0 w-60 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Overview Akademik</a>
                                <a href="program.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Program Pendidikan</a>
                                <a href="guru.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Daftar Guru & Staf</a>
                                <a href="pemeringkatan.php" class="block px-3 py-2 rounded-lg bg-cyan-50 font-bold text-cyan-600 transition-colors">Pemeringkatan Jurusan</a>
                                <a href="keterserapan-ptn.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Keterserapan PTN</a>
                            </div>
                        </div>
                    </div>

                    <!-- Kegiatan Dropdown -->
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

                    <!-- Informasi Dropdown -->
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

        <!-- Mobile Menu Drawer -->
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
                <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Akademik</span>
                <a href="index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Akademik</a>
                <a href="program.php" class="block pl-3 py-1 text-xs text-slate-700">Program Pendidikan</a>
                <a href="guru.php" class="block pl-3 py-1 text-xs text-slate-700">Daftar Guru</a>
                <a href="pemeringkatan.php" class="block pl-3 py-1 text-xs font-bold text-cyan-600">Pemeringkatan Jurusan</a>
                <a href="keterserapan-ptn.php" class="block pl-3 py-1 text-xs text-slate-700">Keterserapan PTN</a>
            </div>
            <a href="../prestasi/index.php" class="block text-xs font-semibold text-slate-700">Prestasi</a>
            <a href="../kegiatan/index.php" class="block text-xs font-semibold text-slate-700">Kegiatan & Ekskul</a>
            <a href="../informasi/ppdb.php" class="block text-xs font-semibold text-slate-700">PPDB Info</a>
            <a href="../kontak/index.php" class="block text-xs font-semibold text-slate-700">Kontak</a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 🚀 PAGE HERO BANNER                                                   -->
    <!-- ===================================================================== -->
    <section class="relative bg-brand-darkNavy text-white py-16 sm:py-20 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-brand-darkNavy via-brand-navy to-slate-900 opacity-95"></div>
        <div class="absolute -right-20 -bottom-20 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-[11px] text-cyan-300 font-semibold mb-4 uppercase tracking-wider">
                <a href="../index.php" class="hover:underline">Beranda</a>
                <span>/</span>
                <a href="index.php" class="hover:underline">Akademik</a>
                <span>/</span>
                <span class="text-white">Pemeringkatan Jurusan</span>
            </nav>

            <div class="max-w-3xl space-y-4">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-cyan-500/20 border border-cyan-400/30 text-cyan-300 text-xs font-bold tracking-wide">
                    <i class="fa-solid fa-ranking-star text-cyan-400"></i>
                    <span>EVALUASI AKADEMIK & ELIGIBLE SNBP</span>
                </div>

                <h1 class="text-3xl sm:text-5xl font-extrabold text-white leading-tight">
                    Pemeringkatan Jurusan & <br><span class="text-cyan-400">Kuota Eligible SNBP</span>
                </h1>

                <p class="text-xs sm:text-base text-slate-300 font-normal leading-relaxed">
                    Sistem pemeringkatan nilai rapor dan capaian prestasi siswa SMAN 21 Jakarta secara transparan, objektif, serta terstruktur untuk penentuan kuota 40% siswa Eligible Seleksi Nasional Berdasarkan Prestasi (SNBP) T.A. 2025/2026.
                </p>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📊 REKAPITULASI STATISTIK METRICS                                     -->
    <!-- ===================================================================== -->
    <section class="relative z-20 -mt-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            
            <div class="school-card p-6 rounded-xl border-l-4 border-cyan-500 flex items-center justify-between reveal-section delay-100">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Kuota Eligible SNBP</span>
                    <span class="text-3xl font-extrabold text-brand-darkNavy mt-1 block">40%</span>
                    <span class="text-[10px] text-cyan-600 font-semibold mt-1 block"><i class="fa-solid fa-circle-check mr-1"></i>Akreditasi "A" BAN-S/M</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-award"></i>
                </div>
            </div>

            <div class="school-card p-6 rounded-xl border-l-4 border-blue-600 flex items-center justify-between reveal-section delay-200">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Siswa Eligible</span>
                    <span class="text-3xl font-extrabold text-brand-darkNavy mt-1 block">116</span>
                    <span class="text-[10px] text-blue-600 font-semibold mt-1 block"><i class="fa-solid fa-users mr-1"></i>Siswa Angkatan XII</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
            </div>

            <div class="school-card p-6 rounded-xl border-l-4 border-emerald-600 flex items-center justify-between reveal-section delay-300">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Rata-Rapor Tertinggi</span>
                    <span class="text-3xl font-extrabold text-brand-darkNavy mt-1 block">93.85</span>
                    <span class="text-[10px] text-emerald-600 font-semibold mt-1 block"><i class="fa-solid fa-chart-line mr-1"></i>MIPA & Kurikulum Merdeka</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-star"></i>
                </div>
            </div>

            <div class="school-card p-6 rounded-xl border-l-4 border-purple-600 flex items-center justify-between reveal-section delay-400">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Semester Evaluasi</span>
                    <span class="text-3xl font-extrabold text-brand-darkNavy mt-1 block">Sem 1 - 5</span>
                    <span class="text-[10px] text-purple-600 font-semibold mt-1 block"><i class="fa-solid fa-check-double mr-1"></i>Verifikasi Pangkalan Data</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📈 GRAFIK DISTRIBUSI RATA-RATA NILAI                                 -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-white border-b border-slate-200 overflow-hidden mt-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-10 items-center">
                
                <div class="lg:col-span-5 space-y-5 reveal-section">
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-600 bg-cyan-50 px-3 py-1 rounded border border-cyan-200">
                        ANALISIS DISTRIBUSI NILAI
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-darkNavy leading-tight">
                        Rata-Rata Rapor Top Ranks per Program Keahlian
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Grafik berikut menampilkan komparasi rata-rata nilai rapor siswa peringkat teratas di setiap jurusan/program keahlian (MIPA, IPS, dan Kurikulum Merdeka) untuk tahun ajaran 2025/2026.
                    </p>

                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between text-xs p-3 rounded-lg bg-slate-50 border border-slate-200">
                            <span class="font-bold text-slate-700"><i class="fa-solid fa-atom text-cyan-600 mr-2"></i>MIPA / IPA</span>
                            <span class="font-extrabold text-cyan-700">Rata-Rata Top 10: 92.15</span>
                        </div>
                        <div class="flex items-center justify-between text-xs p-3 rounded-lg bg-slate-50 border border-slate-200">
                            <span class="font-bold text-slate-700"><i class="fa-solid fa-landmark text-blue-600 mr-2"></i>IPS</span>
                            <span class="font-extrabold text-blue-700">Rata-Rata Top 10: 91.40</span>
                        </div>
                        <div class="flex items-center justify-between text-xs p-3 rounded-lg bg-slate-50 border border-slate-200">
                            <span class="font-bold text-slate-700"><i class="fa-solid fa-shapes text-emerald-600 mr-2"></i>Kurikulum Merdeka</span>
                            <span class="font-extrabold text-emerald-700">Rata-Rata Top 10: 90.85</span>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-7 reveal-section delay-200">
                    <div class="school-card p-6 sm:p-8 rounded-2xl bg-white border border-slate-200 shadow-lg">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                            <div>
                                <h3 class="text-sm font-bold text-brand-darkNavy">Visualisasi Nilai Teratas Siswa Eligible</h3>
                                <p class="text-[11px] text-slate-500">Perbandingan rata-rata nilai rapor peringkat 1-3 per jurusan</p>
                            </div>
                            <span class="text-[10px] font-bold text-cyan-700 bg-cyan-50 px-2.5 py-1 rounded border border-cyan-200">SNBP T.A. 2025/2026</span>
                        </div>

                        <div class="relative w-full h-72">
                            <canvas id="rankingChart"></canvas>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📋 TABEL PEMERINGKATAN SISWA & FILTER                                -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-slate-50 border-b border-slate-200 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 reveal-section">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-600">DAFTAR PEMERINGKATAN</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-darkNavy mt-1">Siswa Eligible SNBP & Top Ranks</h2>
                    <p class="text-xs text-slate-500 mt-1">Gunakan pencarian atau filter jurusan untuk meninjau data pemeringkatan siswa.</p>
                </div>

                <!-- Live Search Box -->
                <div class="relative w-full md:w-72">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Cari nama siswa / NISN..." class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-slate-300 text-xs bg-white focus:outline-none focus:border-cyan-600 shadow-sm transition-all">
                </div>
            </div>

            <!-- Filter Category Buttons -->
            <div class="flex flex-wrap items-center gap-2 reveal-section">
                <button onclick="filterJurusan('semua')" class="tab-btn active px-4 py-2 rounded-lg text-xs font-bold border border-slate-300 bg-white text-slate-700 transition-all">
                    Semua Jurusan
                </button>
                <button onclick="filterJurusan('MIPA')" class="tab-btn px-4 py-2 rounded-lg text-xs font-bold border border-slate-300 bg-white text-slate-700 transition-all">
                    <i class="fa-solid fa-flask mr-1 text-cyan-600"></i> MIPA / IPA
                </button>
                <button onclick="filterJurusan('IPS')" class="tab-btn px-4 py-2 rounded-lg text-xs font-bold border border-slate-300 bg-white text-slate-700 transition-all">
                    <i class="fa-solid fa-book-open mr-1 text-blue-600"></i> IPS
                </button>
                <button onclick="filterJurusan('Kurikulum Merdeka')" class="tab-btn px-4 py-2 rounded-lg text-xs font-bold border border-slate-300 bg-white text-slate-700 transition-all">
                    <i class="fa-solid fa-graduation-cap mr-1 text-emerald-600"></i> Kurikulum Merdeka
                </button>
            </div>

            <!-- Table Container -->
            <div class="school-card rounded-2xl bg-white border border-slate-200 overflow-hidden shadow-md reveal-section">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700" id="rankingTable">
                        <thead class="bg-brand-darkNavy text-white uppercase text-[10px] font-bold tracking-wider">
                            <tr>
                                <th class="py-4 px-4 text-center">Rank</th>
                                <th class="py-4 px-4">NISN</th>
                                <th class="py-4 px-4">Nama Siswa</th>
                                <th class="py-4 px-4">Jurusan / Program</th>
                                <th class="py-4 px-4">Kelas</th>
                                <th class="py-4 px-4 text-center">Rata-Rata Rapor</th>
                                <th class="py-4 px-4 text-center">Status Eligible</th>
                                <th class="py-4 px-4">Rekomendasi PTN / Program</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($pemeringkatanList as $p): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors row-item" data-jurusan="<?= htmlspecialchars($p['jurusan']); ?>">
                                <td class="py-3.5 px-4 text-center font-extrabold text-brand-darkNavy">
                                    <?php if ($p['ranking'] == 1): ?>
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-100 text-amber-700 font-bold border border-amber-300 shadow-sm"><i class="fa-solid fa-crown text-amber-500 text-xs mr-0.5"></i>1</span>
                                    <?php elseif ($p['ranking'] == 2): ?>
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-200 text-slate-700 font-bold border border-slate-300">2</span>
                                    <?php elseif ($p['ranking'] == 3): ?>
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-700/10 text-amber-800 font-bold border border-amber-600/20">3</span>
                                    <?php else: ?>
                                        <span class="font-bold text-slate-600"><?= $p['ranking']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-500 text-[11px]"><?= htmlspecialchars($p['nisn']); ?></td>
                                <td class="py-3.5 px-4 font-bold text-brand-darkNavy search-name"><?= htmlspecialchars($p['nama_siswa']); ?></td>
                                <td class="py-3.5 px-4 font-semibold text-slate-600">
                                    <span class="inline-block px-2.5 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-bold border border-slate-200">
                                        <?= htmlspecialchars($p['jurusan']); ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-medium text-slate-600"><?= htmlspecialchars($p['kelas']); ?></td>
                                <td class="py-3.5 px-4 text-center font-extrabold text-cyan-700 text-sm">
                                    <?= number_format($p['rata_rata'], 2); ?>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-block px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-extrabold border border-emerald-300">
                                        <i class="fa-solid fa-check-circle mr-1"></i><?= htmlspecialchars($p['status_eligible']); ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-medium text-slate-700 text-[11px]">
                                    <?= htmlspecialchars($p['ptn_tujuan']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                    <span>Menampilkan <strong id="visibleCount"><?= count($pemeringkatanList); ?></strong> dari total <strong id="totalCount"><?= count($pemeringkatanList); ?></strong> siswa terdaftar dalam sistem.</span>
                    <span class="text-[11px] italic">Data resmi diverifikasi oleh Tim Kurikulum SMAN 21 Jakarta.</span>
                </div>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- ℹ️ KRITERIA & KETENTUAN PEMERINGKATAN                                 -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-white border-b border-slate-200 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            
            <div class="text-center max-w-2xl mx-auto space-y-2 reveal-section">
                <span class="text-xs font-bold uppercase tracking-widest text-cyan-600">PEDOMAN RESMI</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-darkNavy">Kriteria Pemeringkatan SNBP</h2>
                <p class="text-xs sm:text-sm text-slate-500">Penentuan kuota eligible mengacu pada regulasi Tim Penyelenggara SNPMB Kemendikbudristek.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                
                <div class="school-card p-6 rounded-xl space-y-3 bg-white border border-slate-200 reveal-section delay-100">
                    <div class="w-10 h-10 rounded-lg bg-cyan-100 text-cyan-700 flex items-center justify-center font-bold text-lg">
                        1
                    </div>
                    <h3 class="text-sm font-bold text-brand-darkNavy">Nilai Rapor Semester 1 - 5</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Perhitungan nilai rata-rata seluruh mata pelajaran dari semester 1 hingga semester 5 sesuai bobot Kurikulum Merdeka / 2013 yang berlaku di SMAN 21 Jakarta.
                    </p>
                </div>

                <div class="school-card p-6 rounded-xl space-y-3 bg-white border border-slate-200 reveal-section delay-200">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-lg">
                        2
                    </div>
                    <h3 class="text-sm font-bold text-brand-darkNavy">Mata Pelajaran Pendukung Program</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Pertimbangan nilai komponen mata pelajaran pendukung yang relevan dengan Program Studi PTN yang dituju oleh calon mahasiswa.
                    </p>
                </div>

                <div class="school-card p-6 rounded-xl space-y-3 bg-white border border-slate-200 reveal-section delay-300">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg">
                        3
                    </div>
                    <h3 class="text-sm font-bold text-brand-darkNavy">Prestasi Tambahan & Sertifikat</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Sertifikat kejuaraan resmi yang diselenggarakan Kemendikbudristek (OSN, FLS2N, O2SN) sebagai poin pendukung kualifikasi pemeringkatan.
                    </p>
                </div>

            </div>

            <!-- Warning Box Tie-breaker -->
            <div class="p-5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-start gap-3.5 shadow-sm reveal-section">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg flex-shrink-0 mt-0.5"></i>
                <div class="space-y-1">
                    <strong class="font-bold block text-amber-950">Catatan Pemutus Nilai Sama (Tie-Breaker Rule):</strong>
                    <p class="leading-relaxed">
                        Jika terdapat siswa dengan nilai rata-rata sama pada batas kuota eligible (40%), pemeringkatan pemutus akan mempertimbangkan nilai mata pelajaran unggulan pendukung jurusan (Matematika, Bahasa Indonesia, Bahasa Inggris, Fisika/Sosiologi) serta rekam jejak prestasi non-akademik siswa.
                    </p>
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
                        <li><a href="index.php" class="hover:text-cyan-400 transition-colors">Kurikulum & Akademik</a></li>
                        <li><a href="guru.php" class="hover:text-cyan-400 transition-colors">Daftar Guru & Staf</a></li>
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
                        <li><a href="keterserapan-ptn.php" class="hover:text-cyan-400 transition-colors">Keterserapan PTN Alumni</a></li>
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
                <p>Ada yang bisa saya bantu terkait Pemeringkatan Jurusan, Kuota Eligible SNBP, atau Kurikulum SMAN 21 Jakarta?</p>
            </div>
        </div>

        <div class="p-3 bg-white border-t border-slate-200">
            <form onsubmit="submitAiChat(event)" class="flex gap-2">
                <input type="text" id="aiInput" placeholder="Tanyakan seputar pemeringkatan..." class="flex-1 px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50">
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

        // 3. CHART.JS PEMERINGKATAN STATS
        const ctxRank = document.getElementById('rankingChart');
        if (ctxRank) {
            new Chart(ctxRank, {
                type: 'bar',
                data: {
                    labels: ['Rank 1 MIPA', 'Rank 2 MIPA', 'Rank 1 IPS', 'Rank 2 IPS', 'Rank 1 Merdeka', 'Rank 2 Merdeka'],
                    datasets: [{
                        label: 'Rata-Rata Nilai Rapor',
                        data: [93.85, 92.90, 92.40, 91.75, 91.20, 89.90],
                        backgroundColor: [
                            '#00A8CC',
                            '#0284C7',
                            '#2563EB',
                            '#3B82F6',
                            '#10B981',
                            '#34D399'
                        ],
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { 
                            min: 85,
                            max: 100,
                            grid: { color: '#F1F5F9' } 
                        }
                    }
                }
            });
        }

        // 4. FILTER TABLE BY JURUSAN TABS
        function filterJurusan(jurusan) {
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));

            event.currentTarget.classList.add('active');

            const rows = document.querySelectorAll('#rankingTable tbody tr');
            let visible = 0;

            rows.forEach(row => {
                const rowJurusan = row.getAttribute('data-jurusan');
                if (jurusan === 'semua' || rowJurusan.toLowerCase().includes(jurusan.toLowerCase())) {
                    row.style.display = '';
                    visible++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('visibleCount').innerText = visible;
        }

        // 5. LIVE SEARCH FILTER BY NAME / NISN
        function filterTable() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#rankingTable tbody tr');
            let visible = 0;

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                    visible++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('visibleCount').innerText = visible;
        }

        // 6. AI ASSISTANT PANEL TOGGLE & AJAX
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

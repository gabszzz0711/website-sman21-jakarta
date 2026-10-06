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
// HELPER PERBAIKAN PATH FOTO ALUMNI
// =============================================================================
function formatFotoAlumni($img, $defaultLogo = '../uploads/logo/sman21jakarta.png') {
    if (empty($img)) return $defaultLogo;
    $img = trim($img);
    if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
        return $img;
    }
    if (strpos($img, 'uploads/') === 0 || strpos($img, '../uploads/') === 0) {
        return $img;
    }
    return '../uploads/alumni/' . ltrim($img, '/');
}

// =============================================================================
// FETCH DATA RESMI SEKOLAH & ALUMNI DARI DATABASE
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
            if (!empty($rowSek['telepon'])) $sekolah['telepon'] = $rowSek['telepon'];
            if (!empty($rowSek['email'])) $sekolah['email'] = $rowSek['email'];
            if (!empty($rowSek['instagram'])) $sekolah['instagram'] = $rowSek['instagram'];
            if (!empty($rowSek['youtube'])) $sekolah['youtube'] = $rowSek['youtube'];
        }
    } catch (PDOException $e) {}
}

$logoPath = $sekolah['logo'];

// Parameter Filter Alumni
$searchQuery = trim($_GET['q'] ?? '');
$filterTahun = trim($_GET['tahun'] ?? '');

// Fetch Data Alumni dari Database
$alumniList = [];
$tahunOptions = [];

if ($conn) {
    try {
        // Ambil daftar tahun lulus unik untuk dropdown filter
        $stmtTahun = $conn->query("SELECT DISTINCT tahun_lulus FROM alumni WHERE status_tampil = 1 ORDER BY tahun_lulus DESC");
        if ($stmtTahun) {
            $tahunOptions = $stmtTahun->fetchAll(PDO::FETCH_COLUMN);
        }

        // Query Alumni dengan Filter
        $sql = "SELECT * FROM alumni WHERE status_tampil = 1";
        $params = [];

        if (!empty($searchQuery)) {
            $sql .= " AND (nama_alumni LIKE :q OR universitas_instansi LIKE :q OR profesi LIKE :q OR cerita LIKE :q OR prestasi LIKE :q)";
            $params[':q'] = "%$searchQuery%";
        }

        if (!empty($filterTahun)) {
            $sql .= " AND tahun_lulus = :tahun";
            $params[':tahun'] = $filterTahun;
        }

        $sql .= " ORDER BY tahun_lulus DESC, id DESC";
        $stmtAlumni = $conn->prepare($sql);
        $stmtAlumni->execute($params);
        $alumniList = $stmtAlumni->fetchAll();
    } catch (PDOException $e) {}
}

// Fallback Data Alumni Jika Database Masih Kosong
if (empty($alumniList) && empty($searchQuery) && empty($filterTahun)) {
    $alumniList = [
        [
            'id' => 1,
            'nama_alumni' => 'Dr. Aditya Pratama, S.T., M.Sc.',
            'tahun_lulus' => 2015,
            'jurusan_program' => 'MIPA (Saintek)',
            'foto' => '',
            'universitas_instansi' => 'Institut Teknologi Bandung (ITB) / MIT',
            'profesi' => 'AI Research Scientist & Technology Consultant',
            'cerita' => 'Sistem pembelajaran disiplin dan penguatan dasar sains di SMAN 21 Jakarta menjadi fondasi paling kokoh dalam perjalanan akademis saya hingga menembus riset teknologi internasional.',
            'prestasi' => 'Juara 1 OSN Fisika & Penerima Beasiswa LPDP Luar Negeri',
            'status_tampil' => 1
        ],
        [
            'id' => 2,
            'nama_alumni' => 'Nabila Salsabila, S.H., LL.M.',
            'tahun_lulus 📊' => 2018,
            'tahun_lulus' => 2018,
            'jurusan_program' => 'IPS (Soshum)',
            'foto' => '',
            'universitas_instansi' => 'Universitas Indonesia (UI) / Harvard Law',
            'profesi' => 'Corporate Legal Counsel & Human Rights Advocate',
            'cerita' => 'SMAN 21 Jakarta memberikan ruang luas untuk berorganisasi dan mengasah cara berpikir kritis. Nilai kepemimpinan Pelajar Pancasila sangat terasa manfaatnya di dunia kerja profesional.',
            'prestasi' => 'Best Delegate National Moot Court Competition',
            'status_tampil' => 1
        ],
        [
            'id' => 3,
            'nama_alumni' => 'Rian Hidayat, S.Ked.',
            'tahun_lulus' => 2020,
            'jurusan_program' => 'MIPA (Saintek)',
            'foto' => '',
            'universitas_instansi' => 'Universitas Gadjah Mada (UGM)',
            'profesi' => 'Dokter Muda & Founder HealthTech Start-up',
            'cerita' => 'Dukungan bimbingan guru-guru SMAN 21 dalam persiapan SNBP/SNBT sangat luar biasa. Lingkungan sekolah yang suportif membentuk daya juang yang tinggi.',
            'prestasi' => 'Gold Medalist Indonesian Medical Student Olympiad',
            'status_tampil' => 1
        ]
    ];
    $tahunOptions = [2022, 2021, 2020, 2019, 2018, 2017, 2016, 2015];
}

// =============================================================================
// ENDPOINT AJAX UNTUK S21 AI ASSISTANT (CONTEXT: ALUMNI & REKAM JEJAK)
// =============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'ai_query') {
    header('Content-Type: application/json; charset=utf-8');
    $userQuery = trim($_POST['query'] ?? $_GET['q'] ?? '');
    
    if (empty($userQuery)) {
        echo json_encode(['status' => 'error', 'message' => 'Pertanyaan tidak boleh kosong.']);
        exit;
    }

    $responseAnswer = "";
    $sumberInfo = "Database Alumni SMAN 21 Jakarta";
    $relatedLink = "";

    $qLower = strtolower($userQuery);

    if (strpos($qLower, 'alumni') !== false || strpos($qLower, 'lulusan') !== false || strpos($qLower, 'ptn') !== false || strpos($qLower, 'kuliah') !== false) {
        $responseAnswer = "Alumni SMAN 21 Jakarta tersebar di berbagai PTN ternama Indonesia seperti UI, ITB, UGM, IPB, Unpad, ITS, serta perguruan tinggi internasional. Banyak alumni yang sukses berkarir sebagai akademisi, dokter, insinyur, hukum, hingga praktisi AI.";
        $relatedLink = "../akademik/keterserapan-ptn.php";
    } elseif (strpos($qLower, 'ikatan') !== false || strpos($qLower, 'ika') !== false || strpos($qLower, 'wadah') !== false) {
        $responseAnswer = "Ikatan Alumni SMAN 21 Jakarta (IKA S21) menjadi wadah silaturahmi, jejaring profesional, serta penyedia program mentoring dan beasiswa alumni bagi adik-adik siswa SMAN 21.";
        $relatedLink = "../kontak/index.php";
    } else {
        if ($conn) {
            $stmtKnw = $conn->prepare("SELECT * FROM ai_knowledge WHERE status_aktif = 1 ORDER BY prioritas DESC");
            $stmtKnw->execute();
            $knowledges = $stmtKnw->fetchAll();

            $matched = false;
            foreach ($knowledges as $k) {
                if (strpos($qLower, strtolower($k['pertanyaan'])) !== false || (isset($k['keyword']) && strpos($qLower, strtolower($k['keyword'])) !== false)) {
                    $responseAnswer = $k['jawaban'];
                    $sumberInfo = $k['sumber_informasi'] ?: "Database AI SMAN 21";
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $responseAnswer = "SMAN 21 Jakarta bangga atas capaian para alumni yang tersebar di berbagai instansi pemerintah, BUMN, swasta nasional, dan internasional. Silakan gunakan fitur pencarian pada halaman ini untuk menemukan rekam jejak alumni tertentu.";
            }
        } else {
            $responseAnswer = "Alumni SMAN 21 Jakarta berkontribusi aktif di berbagai sektor industri dan akademisi di seluruh Indonesia. Informasi lebih lanjut mengenai rekam jejak keterserapan alumni dapat diakses melalui menu Akademik.";
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
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Direktori & Kisah Alumni — SMAN 21 JAKARTA</title>
    <meta name="description" content="Direktori Resmi, Rekam Jejak, dan Kisah Inspiratif Alumni SMAN 21 Jakarta. Menjalin Jejaring Profesional dan Inspirasi Bagi Generasi Penerus.">
    
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

        .school-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04);
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .school-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 30px -4px rgba(15, 23, 42, 0.08);
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
    <!-- 🧭 TOPBAR & HEADER NAVBAR                                             -->
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

                    <a href="../prestasi/index.php" class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 transition-all">Prestasi</a>
                    <a href="../fasilitas/index.php" class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 transition-all">Fasilitas</a>

                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-lg text-cyan-600 bg-cyan-50 font-bold border border-cyan-200 flex items-center gap-1.5 transition-all">
                            <span>Informasi</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full right-0 w-52 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="../informasi/berita.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Berita Sekolah</a>
                                <a href="../informasi/agenda.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Agenda & Kalender</a>
                                <a href="../informasi/ppdb.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">PPDB Info</a>
                                <a href="index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 font-bold transition-colors">Jejak Alumni</a>
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
                <span class="text-[11px] uppercase text-cyan-600 font-bold tracking-wider">Informasi & Alumni</span>
                <a href="../informasi/berita.php" class="block pl-3 py-1 text-xs text-slate-700">Berita Sekolah</a>
                <a href="../informasi/agenda.php" class="block pl-3 py-1 text-xs text-slate-700">Agenda & Kalender</a>
                <a href="index.php" class="block pl-3 py-1 text-xs font-bold text-cyan-600">Jejak Alumni</a>
                <a href="../informasi/ppdb.php" class="block pl-3 py-1 text-xs text-slate-700">PPDB Info</a>
            </div>
            <a href="../prestasi/index.php" class="block text-xs font-semibold text-slate-700">Prestasi</a>
            <a href="../kegiatan/index.php" class="block text-xs font-semibold text-slate-700">Kegiatan & Ekskul</a>
            <a href="../kontak/index.php" class="block text-xs font-semibold text-slate-700">Kontak</a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 🏙️ BANNER HEADER SECTION                                              -->
    <!-- ===================================================================== -->
    <section class="bg-brand-darkNavy text-white py-14 sm:py-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 bg-cover bg-center" style="background-image: url('../uploads/tempat/gedungA1.jpg');"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-brand-darkNavy via-brand-navy/95 to-brand-darkNavy"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 space-y-6 text-center">
            
            <nav class="flex items-center justify-center gap-2 text-xs text-slate-400">
                <a href="../index.php" class="hover:text-cyan-400 transition-colors">Beranda</a>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-600"></i>
                <a href="../informasi/berita.php" class="hover:text-cyan-400 transition-colors">Informasi</a>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-600"></i>
                <span class="text-cyan-400 font-bold">Direktori Alumni</span>
            </nav>

            <div class="max-w-3xl mx-auto space-y-4 reveal-section is-visible">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 text-xs font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-user-graduate text-cyan-400"></i>
                    <span>ALUMNI STORIES & REKAM JEJAK</span>
                </div>

                <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight">
                    Jejak & Kisah Alumni
                </h1>

                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed max-w-2xl mx-auto">
                    Mengenal kiprah, perguruan tinggi, profesi, serta kisah inspiratif lulusan SMAN 21 Jakarta yang berkarya untuk negeri.
                </p>
            </div>

            <div class="pt-2 flex flex-wrap items-center justify-center gap-3 reveal-section delay-100">
                <a href="../akademik/keterserapan-ptn.php" class="px-5 py-2.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow transition-all flex items-center gap-2">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Statistik Keterserapan PTN</span>
                </a>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🔍 FILTER & PENCARIAN ALUMNI                                          -->
    <!-- ===================================================================== -->
    <section class="py-12 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            
            <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 shadow-md reveal-section">
                <form method="GET" action="index.php" class="grid sm:grid-cols-12 gap-4 items-center">
                    
                    <div class="sm:col-span-6 relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($searchQuery); ?>" placeholder="Cari nama alumni, universitas, profesi, atau prestasi..." class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-cyan-600 bg-slate-50/50">
                    </div>

                    <div class="sm:col-span-4">
                        <select name="tahun" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-cyan-600 bg-slate-50/50 text-slate-700">
                            <option value="">Semua Angkatan / Tahun Lulus</option>
                            <?php foreach ($tahunOptions as $th): ?>
                            <option value="<?= $th; ?>" <?= $filterTahun == $th ? 'selected' : ''; ?>>
                                Angkatan Lulus <?= $th; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="sm:col-span-2 flex gap-2">
                        <button type="submit" class="w-full py-3 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs shadow transition-all">
                            Filter
                        </button>
                        <?php if (!empty($searchQuery) || !empty($filterTahun)): ?>
                        <a href="index.php" class="px-3.5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs transition-colors flex items-center justify-center">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                        <?php endif; ?>
                    </div>

                </form>
            </div>

            <!-- ===================================================================== -->
            <!-- 👨‍🎓 DAFTAR ALUMNI STORIES CARD GRID                                  -->
            <!-- ===================================================================== -->
            <div class="space-y-6">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3 reveal-section">
                    <div>
                        <h2 class="text-xl font-extrabold text-brand-darkNavy">Profil Alumni Terpilih</h2>
                        <p class="text-xs text-slate-500">Menampilkan rekam jejak lulusan SMAN 21 Jakarta</p>
                    </div>
                    <span class="text-xs font-bold text-cyan-600 bg-cyan-50 px-3 py-1 rounded border border-cyan-200">
                        Total <?= count($alumniList); ?> Alumni
                    </span>
                </div>

                <?php if (!empty($alumniList)): ?>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php $aIndex = 1; foreach ($alumniList as $alm): 
                        $fotoUrl = formatFotoAlumni($alm['foto'], $logoPath);
                    ?>
                    <div class="school-card rounded-2xl bg-white border border-slate-200 p-6 space-y-5 flex flex-col justify-between reveal-section delay-<?= min($aIndex * 100, 300); ?>">
                        <div class="space-y-4">
                            
                            <!-- Header Foto & Info Dasar -->
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-xl bg-slate-100 overflow-hidden flex-shrink-0 border border-slate-200 shadow-sm relative">
                                    <img src="<?= htmlspecialchars($fotoUrl); ?>" 
                                         alt="<?= htmlspecialchars($alm['nama_alumni']); ?>" 
                                         class="w-full h-full object-cover"
                                         onerror="this.onerror=null; this.src='<?= $logoPath; ?>'; this.className='w-full h-full object-contain p-2';">
                                </div>
                                <div>
                                    <span class="px-2.5 py-0.5 rounded bg-cyan-100 text-cyan-800 font-bold text-[10px] tracking-wider uppercase inline-block mb-1">
                                        Lulus Tahun <?= htmlspecialchars($alm['tahun_lulus']); ?>
                                    </span>
                                    <h3 class="text-sm font-bold text-brand-darkNavy leading-snug">
                                        <?= htmlspecialchars($alm['nama_alumni']); ?>
                                    </h3>
                                    <span class="text-[11px] text-slate-500 block font-medium">
                                        <?= htmlspecialchars($alm['jurusan_program'] ?: 'MIPA / IPS'); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Instansi / Universitas & Profesi -->
                            <div class="space-y-2 border-t border-slate-100 pt-3 text-xs">
                                <?php if (!empty($alm['universitas_instansi'])): ?>
                                <div class="flex items-start gap-2 text-slate-700 font-semibold">
                                    <i class="fa-solid fa-building-columns text-cyan-600 mt-0.5 flex-shrink-0"></i>
                                    <span><?= htmlspecialchars($alm['universitas_instansi']); ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($alm['profesi'])): ?>
                                <div class="flex items-start gap-2 text-slate-600">
                                    <i class="fa-solid fa-briefcase text-emerald-600 mt-0.5 flex-shrink-0"></i>
                                    <span><?= htmlspecialchars($alm['profesi']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Cerita / Quote Alumni -->
                            <?php if (!empty($alm['cerita'])): ?>
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-[11px] text-slate-600 italic leading-relaxed relative">
                                <i class="fa-solid fa-quote-left text-cyan-300 mr-1"></i>
                                <?= htmlspecialchars($alm['cerita']); ?>
                            </div>
                            <?php endif; ?>

                        </div>

                        <!-- Footer Prestasi Alumni -->
                        <?php if (!empty($alm['prestasi'])): ?>
                        <div class="pt-3 border-t border-slate-100 text-[10px] text-amber-700 font-bold flex items-center gap-1.5">
                            <i class="fa-solid fa-award text-amber-500"></i>
                            <span class="truncate"><?= htmlspecialchars($alm['prestasi']); ?></span>
                        </div>
                        <?php endif; ?>

                    </div>
                    <?php $aIndex++; endforeach; ?>
                </div>
                <?php else: ?>
                <div class="school-card p-12 rounded-2xl text-center bg-white border border-slate-200 space-y-4">
                    <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-2xl">
                        <i class="fa-solid fa-user-slash"></i>
                    </div>
                    <h3 class="text-base font-bold text-brand-darkNavy">Data Alumni Tidak Ditemukan</h3>
                    <p class="text-xs text-slate-500 max-w-md mx-auto">
                        Tidak ada data alumni yang sesuai dengan kata kunci pencarian atau filter tahun yang dipilih.
                    </p>
                    <a href="index.php" class="inline-block px-5 py-2.5 rounded-lg bg-cyan-600 text-white font-bold text-xs">
                        Tampilkan Semua Alumni
                    </a>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🤖 AI ASSISTANT BANNER SECTION                                        -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-slate-900 text-white relative overflow-hidden">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 reveal-section">
            <div class="p-8 sm:p-12 rounded-2xl bg-gradient-to-r from-brand-darkNavy to-slate-800 border border-slate-700 text-center space-y-5 shadow-2xl">
                <div class="w-14 h-14 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center mx-auto text-2xl border border-cyan-500/30">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white">
                    Ingin Tahu Keterserapan PTN Alumni SMAN 21?
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                    S21 AI Assistant dapat membantu Anda memberikan informasi persebaran alumni di PTN Favorit (UI, ITB, UGM) serta jejaring Ikatan Alumni SMAN 21.
                </p>
                <div class="pt-2">
                    <button onclick="toggleAiPanel()" class="px-7 py-3 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-lg transition-all inline-flex items-center gap-2.5 uppercase tracking-wider">
                        <i class="fa-solid fa-comments text-sm"></i>
                        <span>TANYAKAN SEPUTAR ALUMNI KE AI</span>
                    </button>
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
                        <li><a href="index.php" class="text-cyan-400 font-bold">Jejak & Direktori Alumni</a></li>
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
                    <span class="text-[10px] text-cyan-400 block font-semibold">Context: Alumni & Rekam Jejak</span>
                </div>
            </div>
            <button onclick="toggleAiPanel()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="chatBox" class="flex-1 p-4 overflow-y-auto space-y-4 bg-slate-50 text-xs">
            <div class="p-3.5 rounded-xl bg-white border border-slate-200 text-slate-700 shadow-sm leading-relaxed space-y-2">
                <p class="font-bold text-brand-darkNavy">Halo! Saya S21 AI Assistant. 👋</p>
                <p>🎓 Kamu sedang melihat **Direktori Alumni**. Ada yang ingin kamu tanyakan mengenai keterserapan alumni di PTN atau jejaring ikatan alumni?</p>
                <div class="pt-2 flex flex-wrap gap-1.5">
                    <button onclick="quickAsk('Bagaimana keterserapan alumni SMAN 21 di PTN?')" class="px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 text-[10px] font-semibold transition-colors">
                        📊 Keterserapan PTN
                    </button>
                    <button onclick="quickAsk('Informasi Ikatan Alumni SMAN 21')" class="px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 text-[10px] font-semibold transition-colors">
                        🤝 Ikatan Alumni
                    </button>
                </div>
            </div>
        </div>

        <div class="p-3 bg-white border-t border-slate-200">
            <form onsubmit="submitAiChat(event)" class="flex gap-2">
                <input type="text" id="aiInput" placeholder="Tanyakan seputar alumni SMAN 21..." class="flex-1 px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50">
                <button type="submit" class="px-4 py-2.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs transition-colors">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Floating AI Toggle Button -->
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

        // 3. AI ASSISTANT PANEL TOGGLE & CHAT AJAX
        function toggleAiPanel() {
            const panel = document.getElementById('aiPanel');
            panel.classList.toggle('translate-x-full');
        }

        function quickAsk(qText) {
            document.getElementById('aiInput').value = qText;
            submitAiChat(new Event('submit'));
        }

        function submitAiChat(e) {
            if (e) e.preventDefault();
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
            loadingMsg.innerText = 'Mencari informasi alumni...';
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
                    linkHtml = `<div class="pt-1"><a href="${data.related_link}" class="text-cyan-600 font-bold hover:underline inline-flex items-center gap-1">Lihat Halaman Terkait <i class="fa-solid fa-arrow-right text-[9px]"></i></a></div>`;
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
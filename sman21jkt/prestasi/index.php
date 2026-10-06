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
// HELPER PERBAIKAN PATH GAMBAR & LOGO
// =============================================================================
function formatImgPath($path, $default = '../uploads/logo/sman21jakarta.png') {
    if (empty($path)) return $default;
    $path = trim($path);
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }
    if (strpos($path, '../uploads/') === 0) {
        return $path;
    }
    if (strpos($path, 'uploads/') === 0) {
        return '../' . $path;
    }
    return '../uploads/prestasi/' . ltrim($path, '/');
}

$logoPath = '../uploads/logo/sman21jakarta.png';

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
    $sumberInfo = "Basis Informasi Prestasi SMAN 21 Jakarta";
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
            if (strpos($qLower, 'prestasi') !== false || strpos($qLower, 'juara') !== false || strpos($qLower, 'olimpiade') !== false) {
                $stmtPresAi = $conn->query("SELECT COUNT(*) as total FROM prestasi WHERE status_aktif=1");
                $totPres = $stmtPresAi ? $stmtPresAi->fetch()['total'] : 0;
                $responseAnswer = "SMAN 21 Jakarta secara konsisten mencetak prestasi gemilang di tingkat Kota, Provinsi, Nasional, dan Internasional. Saat ini tercatat puluhan kejuaraan di bidang OSN, FLS2N, Olahraga, dan Seni.";
                $sumberInfo = "Database Prestasi SMAN 21";
                $relatedLink = "index.php";
                $matched = true;
            }
        }

        if (!$matched) {
            $responseAnswer = $fallbackMsg;
            $sumberInfo = "Sistem Verifikasi SMAN 21";
        }

    } else {
        if (strpos(strtolower($userQuery), 'prestasi') !== false) {
            $responseAnswer = "Siswa SMAN 21 Jakarta meraih berbagai prestasi bergengsi seperti Medali Emas OSN Informatika, Juara LKIR BRIN, serta Juara FLS2N dan Olahraga Gubernur Cup.";
            $sumberInfo = "Sistem SMAN 21";
            $relatedLink = "index.php";
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
// FETCH DATA PROFIL & PENGATURAN SEKOLAH
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
    'logo' => $logoPath
];

if ($conn) {
    try {
        $stmtSek = $conn->query("SELECT * FROM pengaturan_sekolah LIMIT 1");
        if ($stmtSek && $rowSek = $stmtSek->fetch()) {
            if (!empty($rowSek['nama_sekolah'])) $sekolah['nama_sekolah'] = $rowSek['nama_sekolah'];
            if (!empty($rowSek['slogan'])) $sekolah['slogan'] = $rowSek['slogan'];
            if (!empty($rowSek['alamat'])) $sekolah['alamat'] = $rowSek['alamat'];
            if (!empty($rowSek['telepon'])) $sekolah['telepon'] = $rowSek['telepon'];
            if (!empty($rowSek['email'])) $sekolah['email'] = $rowSek['email'];
        }
    } catch (PDOException $e) {}
}

// =============================================================================
// FETCH DATA PRESTASI & FILTERING
// =============================================================================
$search = trim($_GET['q'] ?? '');
$filterKategori = trim($_GET['kategori'] ?? 'Semua');
$filterTingkat = trim($_GET['tingkat'] ?? 'Semua');

$prestasiList = [];
$stats = [
    'total' => 0,
    'nasional' => 0,
    'provinsi' => 0,
    'internasional' => 0
];

if ($conn) {
    try {
        // Fetch All Active Items for Statistics
        $stmtAll = $conn->query("SELECT * FROM prestasi WHERE status_aktif = 1 ORDER BY id DESC");
        $allPrestasi = $stmtAll ? $stmtAll->fetchAll() : [];
        $stats['total'] = count($allPrestasi);

        foreach ($allPrestasi as $r) {
            $t = strtolower($r['tingkat'] ?? '');
            if (strpos($t, 'internasional') !== false) {
                $stats['internasional']++;
            } elseif (strpos($t, 'nasional') !== false) {
                $stats['nasional']++;
            } elseif (strpos($t, 'provinsi') !== false || strpos($t, 'dki') !== false) {
                $stats['provinsi']++;
            }
        }

        // Apply Filters Query
        $sql = "SELECT * FROM prestasi WHERE status_aktif = 1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (judul_prestasi LIKE :q OR nama_siswa LIKE :q OR deskripsi LIKE :q OR penyelenggara LIKE :q)";
            $params[':q'] = "%$search%";
        }

        if (!empty($filterKategori) && $filterKategori !== 'Semua') {
            $sql .= " AND kategori = :kat";
            $params[':kat'] = $filterKategori;
        }

        if (!empty($filterTingkat) && $filterTingkat !== 'Semua') {
            $sql .= " AND tingkat = :tingkat";
            $params[':tingkat'] = $filterTingkat;
        }

        $sql .= " ORDER BY id DESC";

        $stmtFilter = $conn->prepare($sql);
        $stmtFilter->execute($params);
        $prestasiList = $stmtFilter->fetchAll();

    } catch (PDOException $e) {
        $prestasiList = [];
    }
}

// Fallback jika database kosong / belum terisi
if (empty($prestasiList) && empty($search) && $filterKategori === 'Semua' && $filterTingkat === 'Semua') {
    $prestasiList = [
        [
            'id' => 101,
            'judul_prestasi' => 'Juara 1 Olimpiade Sains Nasional (OSN) Bidang Komputer',
            'kategori' => 'Akademik',
            'tingkat' => 'Nasional',
            'nama_siswa' => 'Muhammad Rizky Pratama',
            'peringkat' => 'Medali Emas (Juara 1)',
            'tahun' => '2025',
            'penyelenggara' => 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi',
            'deskripsi' => 'Berhasil memboyong Medali Emas dalam ajang Olimpiade Sains Nasional (OSN) tingkat SMA/MA bidang Informatika/Komputer dengan merancang algoritma optimasi tingkat tinggi.',
            'foto' => '../uploads/tempat/gedungA1.jpg'
        ],
        [
            'id' => 102,
            'judul_prestasi' => 'Juara 1 Lomba Karya Tulis Ilmiah Remaja (LKIR) LIPI/BRIN',
            'kategori' => 'Akademik',
            'tingkat' => 'Nasional',
            'nama_siswa' => 'Anisa Rahmawati & Tim Riset 21',
            'peringkat' => 'Juara 1 & Best Presentation',
            'tahun' => '2025',
            'penyelenggara' => 'Badan Riset dan Inovasi Nasional (BRIN)',
            'deskripsi' => 'Inovasi pengolahan limbah plastik menjadi material mikro-komposit ramah lingkungan mendapatkan penghargaan tertinggi dan paten riset siswa.',
            'foto' => '../uploads/tempat/gedungA.jpg'
        ],
        [
            'id' => 103,
            'judul_prestasi' => 'Medali Perak International High School Mathematics Contest',
            'kategori' => 'Akademik',
            'tingkat' => 'Internasional',
            'nama_siswa' => 'Kevin Sanjaya',
            'peringkat' => 'Medali Perak (Silver Medal)',
            'tahun' => '2024',
            'penyelenggara' => 'Asian Mathematics Association',
            'deskripsi' => 'Mewakili Indonesia dalam kejuaraan matematika internasional tingkat remaja di Singapura dan berhasil meraih Medali Perak.',
            'foto' => '../uploads/tempat/lapangan.jpg'
        ],
        [
            'id' => 104,
            'judul_prestasi' => 'Juara 1 Kejuaraan Basket Pelajar DKI Jakarta (Gubernur Cup)',
            'kategori' => 'Olahraga',
            'tingkat' => 'Provinsi',
            'nama_siswa' => 'Tim Basket Putra SMAN 21',
            'peringkat' => 'Juara 1 (Gold Champions)',
            'tahun' => '2025',
            'penyelenggara' => 'Dispora DKI Jakarta',
            'deskripsi' => 'Tim Basket Putra SMAN 21 Jakarta meraih trofi bergilir Gubernur Cup setelah mengalahkan tim favorit di partai final.',
            'foto' => '../uploads/tempat/gedungA1.jpg'
        ],
        [
            'id' => 105,
            'judul_prestasi' => 'Juara 1 Festival & Lomba Seni Siswa Nasional (FLS2N) Tari Kreasi',
            'kategori' => 'Seni',
            'tingkat' => 'Nasional',
            'nama_siswa' => 'Siti Nurhaliza & Sanggar Tari 21',
            'peringkat' => 'Juara 1 Nasional',
            'tahun' => '2024',
            'penyelenggara' => 'Pusat Prestasi Nasional (Puspresnas)',
            'deskripsi' => 'Menampilkan karya tari kolaborasi kebudayaan Betawi-Nusantara yang memukau dewan juri dan meraih trofi Juara 1 FLS2N.',
            'foto' => '../uploads/tempat/gedungA.jpg'
        ],
        [
            'id' => 106,
            'judul_prestasi' => 'Juara 1 Debat Bahasa Inggris (NSDC) Tingkat Provinsi DKI Jakarta',
            'kategori' => 'Akademik',
            'tingkat' => 'Provinsi',
            'nama_siswa' => 'Ahmad Fathir & Clara Salsabila',
            'peringkat' => 'Juara 1 & Best Speaker',
            'tahun' => '2025',
            'penyelenggara' => 'Dinas Pendidikan DKI Jakarta',
            'deskripsi' => 'Tim Debat Bahasa Inggris SMAN 21 Jakarta berhasil menundukkan 32 sekolah unggulan se-DKI Jakarta dan mewakili DKI ke tingkat nasional.',
            'foto' => '../uploads/tempat/lapangan.jpg'
        ]
    ];
    $stats = ['total' => 38, 'nasional' => 18, 'provinsi' => 14, 'internasional' => 6];
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Galeri Prestasi Siswa — SMA NEGERI 21 JAKARTA</title>
    <meta name="description" content="Galeri Kebanggaan dan Prestasi Siswa SMAN 21 Jakarta di Bidang Akademik, Sains, Seni, dan Olahraga Tingkat Kota, Provinsi, Nasional, hingga Internasional.">
    
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
                            gold: '#F59E0B',
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
        .delay-400 { transition-delay: 400ms; }

        .hero-banner-bg {
            background: linear-gradient(135deg, rgba(6, 18, 36, 0.92) 0%, rgba(11, 30, 54, 0.88) 60%, rgba(15, 23, 42, 0.92) 100%), url('../uploads/tempat/gedungA1.jpg') center/cover no-repeat;
        }

        /* Keyframe Animasi Berjalan Dari Kiri ke Kanan */
        @keyframes marqueeRight {
            0% {
                transform: translateX(-50%);
            }
            100% {
                transform: translateX(0%);
            }
        }

        .animate-marquee-right {
            display: flex;
            width: max-content;
            animation: marqueeRight 35s linear infinite;
            will-change: transform;
        }

        /* Pause animasi ketika diposisikan kursor mouse */
        .animate-marquee-right:hover {
            animation-play-state: paused;
        }

        ::-webkit-scrollbar { width: 7px; }
        ::-webkit-scrollbar-track { background: #061224; }
        ::-webkit-scrollbar-thumb { background: #00A8CC; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #0086A3; }
    </style>
</head>
<body class="relative min-h-screen text-slate-800 bg-slate-50 selection:bg-cyan-500 selection:text-white max-w-full overflow-x-hidden">

    <!-- ===================================================================== -->
    <!-- 🧭 TOPBAR & NAVIGATION HEADER                                         -->
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

                    <a href="index.php" class="px-3.5 py-2 rounded-lg text-cyan-600 bg-cyan-50 font-bold border border-cyan-200">Prestasi</a>
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
            <a href="../index.php" class="block text-slate-700 font-semibold">Beranda</a>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Profil</span>
                <a href="../profil/index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Profil</a>
                <a href="../profil/sejarah.php" class="block pl-3 py-1 text-xs text-slate-700">Sejarah</a>
                <a href="../profil/visi-misi.php" class="block pl-3 py-1 text-xs text-slate-700">Visi & Misi</a>
                <a href="../profil/sambutan.php" class="block pl-3 py-1 text-xs text-slate-700">Sambutan Kepala Sekolah</a>
            </div>
            <a href="index.php" class="block text-cyan-600 font-bold">Prestasi Siswa</a>
            <a href="../kegiatan/index.php" class="block text-xs font-semibold text-slate-700">Kegiatan & Ekskul</a>
            <a href="../informasi/ppdb.php" class="block text-xs font-semibold text-slate-700">PPDB Info</a>
            <a href="../kontak/index.php" class="block text-xs font-semibold text-slate-700">Kontak</a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 🏆 HERO BANNERS PRESTASI SEKOLAH                                      -->
    <!-- ===================================================================== -->
    <section class="hero-banner-bg text-white py-20 lg:py-28 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl space-y-4 reveal-section is-visible">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-cyan-500/20 border border-cyan-400/40 text-cyan-300 text-xs font-bold tracking-wide uppercase backdrop-blur-sm">
                    <i class="fa-solid fa-trophy text-amber-400"></i>
                    <span>HALL OF FAME • SMAN 21 JAKARTA</span>
                </div>
                <h1 class="text-3xl sm:text-5xl font-extrabold text-white leading-tight tracking-tight">
                    Galeri Kebanggaan & <br><span class="text-cyan-400">Prestasi Siswa</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-200 leading-relaxed">
                    Jejak gemilang civitas akademika SMAN 21 Jakarta dalam menorehkan keunggulan di bidang Akademik, Sains & Teknologi, Olahraga, Seni Budaya, serta Kepemimpinan dari Tingkat Kota hingga Internasional.
                </p>
            </div>

            <!-- Stats Bar Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-12 pt-8 border-t border-white/10 reveal-section delay-200">
                <div class="p-4 rounded-xl bg-white/5 border border-white/10 backdrop-blur-md">
                    <span class="text-2xl sm:text-3xl font-extrabold text-amber-400 block"><?= $stats['total']; ?>+</span>
                    <span class="text-[11px] text-slate-300 uppercase tracking-wider font-semibold">Total Kejuaraan</span>
                </div>
                <div class="p-4 rounded-xl bg-white/5 border border-white/10 backdrop-blur-md">
                    <span class="text-2xl sm:text-3xl font-extrabold text-cyan-400 block"><?= $stats['nasional']; ?>+</span>
                    <span class="text-[11px] text-slate-300 uppercase tracking-wider font-semibold">Tingkat Nasional</span>
                </div>
                <div class="p-4 rounded-xl bg-white/5 border border-white/10 backdrop-blur-md">
                    <span class="text-2xl sm:text-3xl font-extrabold text-emerald-400 block"><?= $stats['provinsi']; ?>+</span>
                    <span class="text-[11px] text-slate-300 uppercase tracking-wider font-semibold">Tingkat Provinsi</span>
                </div>
                <div class="p-4 rounded-xl bg-white/5 border border-white/10 backdrop-blur-md">
                    <span class="text-2xl sm:text-3xl font-extrabold text-purple-400 block"><?= $stats['internasional']; ?>+</span>
                    <span class="text-[11px] text-slate-300 uppercase tracking-wider font-semibold">Tingkat Internasional</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🔍 FILTER & PENCARIAN PRESTASI                                         -->
    <!-- ===================================================================== -->
    <section class="py-10 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="GET" action="index.php" class="space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                    
                    <!-- Input Search Keyword -->
                    <div class="md:col-span-5 relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search); ?>" placeholder="Cari nama siswa, kejuaraan, atau kata kunci..." class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-xs text-slate-800">
                    </div>

                    <!-- Dropdown Tingkat -->
                    <div class="md:col-span-4">
                        <select name="tingkat" onchange="this.form.submit()" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs text-slate-700 bg-white">
                            <option value="Semua" <?= $filterTingkat === 'Semua' ? 'selected' : ''; ?>>-- Semua Tingkat Kejuaraan --</option>
                            <option value="Kota" <?= $filterTingkat === 'Kota' ? 'selected' : ''; ?>>Tingkat Kota / Kabupaten</option>
                            <option value="Provinsi" <?= $filterTingkat === 'Provinsi' ? 'selected' : ''; ?>>Tingkat Provinsi DKI Jakarta</option>
                            <option value="Nasional" <?= $filterTingkat === 'Nasional' ? 'selected' : ''; ?>>Tingkat Nasional</option>
                            <option value="Internasional" <?= $filterTingkat === 'Internasional' ? 'selected' : ''; ?>>Tingkat Internasional</option>
                        </select>
                    </div>

                    <!-- Submit Button & Reset -->
                    <div class="md:col-span-3 flex items-center gap-2">
                        <button type="submit" class="flex-1 py-3 bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-filter"></i>
                            <span>Terapkan Filter</span>
                        </button>
                        <?php if(!empty($search) || $filterKategori !== 'Semua' || $filterTingkat !== 'Semua'): ?>
                        <a href="index.php" class="p-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition-colors text-xs font-bold" title="Reset Filter">
                            <i class="fa-solid fa-rotate-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- Kategori Filter Pills -->
                <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none text-xs font-semibold">
                    <span class="text-slate-400 uppercase text-[10px] tracking-wider font-bold mr-2">Kategori:</span>
                    <?php 
                        $kategoriPills = ['Semua', 'Akademik', 'Sains', 'Olahraga', 'Seni', 'Lainnya'];
                        foreach ($kategoriPills as $kat):
                            $isActive = ($filterKategori === $kat);
                            $pillUrl = "?kategori=" . urlencode($kat) . ($filterTingkat !== 'Semua' ? "&tingkat=" . urlencode($filterTingkat) : "") . (!empty($search) ? "&q=" . urlencode($search) : "");
                    ?>
                    <a href="<?= $pillUrl; ?>" class="px-4 py-2 rounded-full transition-all flex-shrink-0 <?= $isActive ? 'bg-brand-darkNavy text-white font-bold shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                        <?= $kat; ?>
                    </a>
                    <?php endforeach; ?>
                </div>

            </form>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🖼️ SHOWCASE CARD PRESTASI (PENUH, PERSEGI, PER-BARIS, SLIDER KONTINU)  -->
    <!-- ===================================================================== -->
    <section class="py-12 bg-slate-900 overflow-hidden min-h-[500px]">
        <div class="w-full">
            
            <?php if (!empty($prestasiList)): 
                // Pecah data prestasi menjadi baris-baris (maksimal 10 item per baris)
                $rowsOfPrestasi = array_chunk($prestasiList, 10);
            ?>
            
            <div class="space-y-6">
                <?php foreach ($rowsOfPrestasi as $rowIndex => $rowItems): 
                    // Gandakan array agar loop animasi berjalan terus-menerus secara mulus tanpa celah
                    $loopItems = $rowItems;
                    while (count($loopItems) < 10) {
                        $loopItems = array_merge($loopItems, $rowItems);
                    }
                    $loopItems = array_merge($loopItems, $loopItems);
                ?>
                
                <!-- Container Baris Ke-<?= $rowIndex + 1; ?> -->
                <div class="relative w-full overflow-hidden py-1 border-y border-slate-800/80 bg-slate-950/40">
                    <div class="animate-marquee-right flex gap-2 sm:gap-3">
                        <?php foreach ($loopItems as $pres): 
                            $imgUrl = formatImgPath($pres['foto'] ?? $pres['gambar'] ?? '', '../uploads/logo/sman21jakarta.png');
                            $tingkat = $pres['tingkat'] ?? 'Nasional';
                            $kategori = $pres['kategori'] ?? 'Akademik';
                        ?>
                        
                        <!-- Card Prestasi: Persegi Penuh, Tanpa Lengkungan, Tanpa Judul -->
                        <div onclick="openPrestasiModal(<?= htmlspecialchars(json_encode([
                            'judul' => $pres['judul_prestasi'],
                            'siswa' => $pres['nama_siswa'] ?? '-',
                            'peringkat' => $pres['peringkat'] ?? '-',
                            'kategori' => $kategori,
                            'tingkat' => $tingkat,
                            'tahun' => $pres['tahun'] ?? '-',
                            'penyelenggara' => $pres['penyelenggara'] ?? 'SMAN 21 Jakarta',
                            'deskripsi' => $pres['deskripsi'] ?? 'Prestasi resmi siswa SMA Negeri 21 Jakarta.',
                            'foto' => $imgUrl
                        ]), ENT_QUOTES, 'UTF-8'); ?>)" 
                        class="w-60 h-60 sm:w-72 sm:h-72 md:w-80 md:h-80 flex-shrink-0 cursor-pointer overflow-hidden relative group bg-slate-800 rounded-none border border-slate-800/50">
                            
                            <!-- Foto Upload Admin Utama -->
                            <img src="<?= htmlspecialchars($imgUrl); ?>" 
                                 alt="<?= htmlspecialchars($pres['judul_prestasi']); ?>" 
                                 class="w-full h-full object-cover rounded-none group-hover:scale-110 transition-transform duration-700 ease-out" 
                                 onerror="this.onerror=null; this.src='<?= $logoPath; ?>'; this.className='w-full h-full object-contain p-6 bg-slate-900';">
                            
                            <!-- Hover Effect Indicator (Saat Kursor Diarahkan ke Gambar) -->
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors duration-300 flex items-center justify-center">
                                <div class="opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0 bg-cyan-600/90 text-white px-4 py-2 rounded-lg text-xs font-bold backdrop-blur-md shadow-xl flex items-center gap-2">
                                    <i class="fa-solid fa-expand text-sm"></i>
                                    <span>Lihat Detail</span>
                                </div>
                            </div>
                        </div>

                        <?php endforeach; ?>
                    </div>
                </div>

                <?php endforeach; ?>
            </div>

            <?php else: ?>
            
            <div class="max-w-xl mx-auto p-12 text-center bg-slate-800/60 rounded-2xl border border-slate-700 text-white space-y-4 my-10">
                <div class="w-16 h-16 rounded-full bg-slate-700 text-slate-400 flex items-center justify-center mx-auto text-2xl">
                    <i class="fa-solid fa-trophy-slash"></i>
                </div>
                <h3 class="text-base font-bold text-white">Tidak Ada Data Prestasi Ditemukan</h3>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Maaf, data prestasi tidak ditemukan untuk kata kunci atau filter yang Anda pilih. Silakan gunakan kata kunci lain atau reset filter.
                </p>
                <div>
                    <a href="index.php" class="px-6 py-2.5 bg-cyan-600 text-white font-bold text-xs rounded-xl inline-block hover:bg-cyan-500 transition-colors">
                        Reset Pencarian
                    </a>
                </div>
            </div>

            <?php endif; ?>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🤖 SECTION AI ASSISTANT BANNER                                        -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-slate-900 text-white relative overflow-hidden border-t border-slate-800">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 reveal-section">
            <div class="p-8 sm:p-12 rounded-2xl bg-gradient-to-r from-brand-darkNavy to-slate-800 border border-slate-700 text-center space-y-5 shadow-2xl">
                <div class="w-14 h-14 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center mx-auto text-2xl border border-cyan-500/30">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white">
                    Ingin Tahu Info Pembinaan Prestasi SMAN 21?
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                    Manfaatkan Layanan Informasi S21 AI untuk mendapatkan informasi seputar Ekstrakurikuler, Olimpiade Sains (OSN), FLS2N, mau pun Pembinaan Bakat Siswa.
                </p>
                <div class="pt-2">
                    <button onclick="toggleAiPanel()" class="px-7 py-3 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-lg transition-all inline-flex items-center gap-2.5 uppercase tracking-wider">
                        <i class="fa-solid fa-comments text-sm"></i>
                        <span>TANYAKAN LAYANAN AI</span>
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
                        <li><a href="index.php" class="hover:text-cyan-400 transition-colors text-cyan-400 font-bold">Galeri Prestasi Siswa</a></li>
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
    <!-- 🖼️ MODAL DETAIL PRESTASI                                              -->
    <!-- ===================================================================== -->
    <div id="prestasiModal" class="fixed inset-0 z-[9995] bg-black/80 backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-300 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform scale-95 transition-all duration-300 max-h-[90vh] flex flex-col">
            <div class="p-5 bg-brand-darkNavy text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center border border-amber-500/30">
                        <i class="fa-solid fa-trophy text-xs"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider">Detail Prestasi Resmi</h3>
                        <span id="modalTingkat" class="text-[10px] text-cyan-400 block font-semibold">Tingkat Nasional</span>
                    </div>
                </div>
                <button onclick="closePrestasiModal()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto space-y-5">
                <div class="w-full h-64 bg-slate-900 rounded-xl overflow-hidden border border-slate-200 flex items-center justify-center">
                    <img id="modalFoto" src="" alt="Foto Prestasi" class="w-full h-full object-cover">
                </div>

                <div class="space-y-2">
                    <h2 id="modalJudul" class="text-lg font-extrabold text-brand-darkNavy leading-snug"></h2>
                    <p id="modalPeringkat" class="text-xs font-bold text-cyan-700"></p>
                </div>

                <div class="grid grid-cols-2 gap-3 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                    <div>
                        <span class="text-[10px] uppercase text-slate-400 font-bold block">Pemenang / Peraih:</span>
                        <span id="modalSiswa" class="font-bold text-slate-800"></span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase text-slate-400 font-bold block">Tahun Pelaksanaan:</span>
                        <span id="modalTahun" class="font-bold text-slate-800"></span>
                    </div>
                    <div class="col-span-2 pt-2 border-t border-slate-200">
                        <span class="text-[10px] uppercase text-slate-400 font-bold block">Penyelenggara:</span>
                        <span id="modalPenyelenggara" class="font-semibold text-slate-700"></span>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-bold text-brand-darkNavy uppercase tracking-wider mb-1">Keterangan / Ringkasan Riset</h4>
                    <p id="modalDeskripsi" class="text-xs text-slate-600 leading-relaxed"></p>
                </div>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-200 text-right">
                <button onclick="closePrestasiModal()" class="px-5 py-2 rounded-xl bg-brand-darkNavy hover:bg-slate-800 text-white font-bold text-xs transition-colors">
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

        <div id="aiMessages" class="flex-1 p-4 overflow-y-auto space-y-4 bg-slate-50 text-xs">
            <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-sm text-slate-700 space-y-1">
                <p class="font-bold text-brand-darkNavy">Halo! Saya S21 AI Assistant. 👋</p>
                <p>Silakan tanyakan seputar Prestasi Siswa, Pembinaan OSN, Ekstrakurikuler, PPDB, atau Kurikulum SMAN 21 Jakarta.</p>
            </div>
        </div>

        <form onsubmit="submitAiQuery(event)" class="p-3 bg-white border-t border-slate-200 flex gap-2">
            <input type="text" id="aiInput" placeholder="Tulis pertanyaan..." class="flex-1 px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs">
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs transition-colors flex items-center justify-center">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>

    <!-- Floating Toggle Button for AI -->
    <button onclick="toggleAiPanel()" class="fixed bottom-6 right-6 z-[9980] w-14 h-14 rounded-full bg-cyan-600 hover:bg-cyan-500 text-white shadow-2xl flex items-center justify-center transition-transform hover:scale-110 group border-2 border-white">
        <i class="fa-solid fa-robot text-xl"></i>
    </button>

    <!-- ===================================================================== -->
    <!-- 📜 JAVASCRIPT LOGIC & ANIMATION                                       -->
    <!-- ===================================================================== -->
    <script>
        // Smooth Reveal Scroll Intersection Observer
        document.addEventListener("DOMContentLoaded", function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.reveal-section').forEach(el => observer.observe(el));
        });

        // Mobile Navigation Toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }

        // Modal Prestasi Detail Logic
        function openPrestasiModal(data) {
            document.getElementById('modalJudul').innerText = data.judul || '-';
            document.getElementById('modalSiswa').innerText = data.siswa || '-';
            document.getElementById('modalPeringkat').innerText = data.peringkat || '-';
            document.getElementById('modalTingkat').innerText = 'Tingkat ' + (data.tingkat || 'Nasional') + ' • ' + (data.kategori || 'Akademik');
            document.getElementById('modalTahun').innerText = data.tahun || '-';
            document.getElementById('modalPenyelenggara').innerText = data.penyelenggara || 'SMAN 21 Jakarta';
            document.getElementById('modalDeskripsi').innerText = data.deskripsi || '-';
            
            const imgElem = document.getElementById('modalFoto');
            imgElem.src = data.foto || '<?= $logoPath; ?>';

            const modal = document.getElementById('prestasiModal');
            modal.classList.remove('opacity-0', 'pointer-events-none');
            modal.querySelector('div').classList.remove('scale-95');
            modal.querySelector('div').classList.add('scale-100');
        }

        function closePrestasiModal() {
            const modal = document.getElementById('prestasiModal');
            modal.querySelector('div').classList.remove('scale-100');
            modal.querySelector('div').classList.add('scale-95');
            modal.classList.add('opacity-0', 'pointer-events-none');
        }

        // AI Assistant Drawer Toggle & Interaction
        function toggleAiPanel() {
            const panel = document.getElementById('aiPanel');
            panel.classList.toggle('translate-x-full');
        }

        function submitAiQuery(e) {
            e.preventDefault();
            const input = document.getElementById('aiInput');
            const q = input.value.trim();
            if (!q) return;

            const msgContainer = document.getElementById('aiMessages');

            // User Chat Bubble
            const userBubble = document.createElement('div');
            userBubble.className = "p-3 rounded-xl bg-cyan-600 text-white ml-auto max-w-[85%] font-medium";
            userBubble.innerText = q;
            msgContainer.appendChild(userBubble);

            input.value = '';
            msgContainer.scrollTop = msgContainer.scrollHeight;

            // Loading Indicator
            const loadingBubble = document.createElement('div');
            loadingBubble.className = "p-3 rounded-xl bg-white border border-slate-200 text-slate-500 max-w-[85%] italic";
            loadingBubble.id = "aiLoading";
            loadingBubble.innerText = "Memproses jawaban AI...";
            msgContainer.appendChild(loadingBubble);
            msgContainer.scrollTop = msgContainer.scrollHeight;

            // Send AJAX Request to backend
            fetch('index.php?action=ai_query', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'query=' + encodeURIComponent(q)
            })
            .then(res => res.json())
            .then(data => {
                const loader = document.getElementById('aiLoading');
                if (loader) loader.remove();

                const aiBubble = document.createElement('div');
                aiBubble.className = "p-3.5 rounded-xl bg-white border border-slate-200 shadow-sm text-slate-700 max-w-[85%] space-y-1.5";
                
                let htmlAns = `<p class="leading-relaxed">${data.answer}</p>`;
                if (data.source) {
                    htmlAns += `<span class="inline-block text-[10px] text-cyan-600 bg-cyan-50 px-2 py-0.5 rounded font-semibold border border-cyan-200">Sumber: ${data.source}</span>`;
                }
                aiBubble.innerHTML = htmlAns;
                msgContainer.appendChild(aiBubble);
                msgContainer.scrollTop = msgContainer.scrollHeight;
            })
            .catch(() => {
                const loader = document.getElementById('aiLoading');
                if (loader) loader.remove();

                const errBubble = document.createElement('div');
                errBubble.className = "p-3 rounded-xl bg-red-50 text-red-700 border border-red-200 max-w-[85%]";
                errBubble.innerText = "Maaf, terjadi kendala jaringan saat menghubungi sistem AI.";
                msgContainer.appendChild(errBubble);
                msgContainer.scrollTop = msgContainer.scrollHeight;
            });
        }
    </script>
</body>
</html>
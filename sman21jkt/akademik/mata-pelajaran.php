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
// HELPER PERBAIKAN PATH ASSET GAMBAR (SUBFOLDER AKADEMIK)
// =============================================================================
function fixAssetPath($path, $default = '../uploads/logo/sman21jakarta.png') {
    if (empty($path)) return $default;
    $path = trim($path);
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }
    if (strpos($path, '../') === 0) {
        return $path;
    }
    return '../' . ltrim($path, '/');
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
    $sumberInfo = "Basis Informasi Akademik SMAN 21 Jakarta";
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
            if (strpos($qLower, 'mapel') !== false || strpos($qLower, 'mata pelajaran') !== false || strpos($qLower, 'kurikulum') !== false) {
                $responseAnswer = "SMAN 21 Jakarta menerapkan Kurikulum Merdeka yang terbagi atas Mata Pelajaran Umum (Wajib), Kelompok Pilihan MIPA, Kelompok Pilihan IPS/Soshum, Bahasa, serta Muatan Lokal PLBJ.";
                $sumberInfo = "Struktur Kurikulum SMAN 21";
                $relatedLink = "akademik/mata-pelajaran.php";
                $matched = true;
            } elseif (strpos($qLower, 'ppdb') !== false || strpos($qLower, 'daftar') !== false) {
                $stmtPpdb = $conn->query("SELECT * FROM ppdb WHERE status='buka' LIMIT 1");
                if ($ppdbData = $stmtPpdb->fetch()) {
                    $responseAnswer = "PPDB T.A. " . htmlspecialchars($ppdbData['tahun_ajaran']) . " sedang dibuka. " . htmlspecialchars($ppdbData['deskripsi']);
                    $sumberInfo = "Informasi PPDB Resmi";
                    $relatedLink = "informasi/ppdb.php";
                    $matched = true;
                }
            }
        }

        if (!$matched) {
            $responseAnswer = $fallbackMsg;
            $sumberInfo = "Sistem Verifikasi SMAN 21";
        }

    } else {
        if (strpos(strtolower($userQuery), 'mapel') !== false || strpos(strtolower($userQuery), 'pelajaran') !== false) {
            $responseAnswer = "Mata pelajaran di SMAN 21 Jakarta mencakup Kurikulum Merdeka Fase E (Kelas X) dan Fase F (Kelas XI & XII) dengan pilihan MIPA, IPS, dan Bahasa.";
            $sumberInfo = "Kurikulum Merdeka SMAN 21";
            $relatedLink = "akademik/mata-pelajaran.php";
        } else {
            $responseAnswer = "Maaf, informasi tersebut belum tersedia dalam basis data resmi SMAN 21 Jakarta.";
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
// FETCH PROFIL SEKOLAH & DATA MATA PELAJARAN DARI DATABASE
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
    'logo' => 'uploads/logo/sman21jakarta.png'
];

if ($conn) {
    try {
        $stmtSek = $conn->query("SELECT * FROM pengaturan_sekolah LIMIT 1");
        if ($stmtSek && $rowSek = $stmtSek->fetch()) {
            if (!empty($rowSek['nama_sekolah'])) $sekolah['nama_sekolah'] = $rowSek['nama_sekolah'];
            if (!empty($rowSek['slogan'])) $sekolah['slogan'] = $rowSek['slogan'];
            if (!empty($rowSek['akreditasi'])) $sekolah['akreditasi'] = $rowSek['akreditasi'];
            if (!empty($rowSek['telepon'])) $sekolah['telepon'] = $rowSek['telepon'];
            if (!empty($rowSek['email'])) $sekolah['email'] = $rowSek['email'];
            if (!empty($rowSek['instagram'])) $sekolah['instagram'] = $rowSek['instagram'];
            if (!empty($rowSek['youtube'])) $sekolah['youtube'] = $rowSek['youtube'];
        }
    } catch (PDOException $e) {}
}

// Query Mata Pelajaran dari DB
$mapelList = [];
if ($conn) {
    try {
        $stmtMapel = $conn->query("SELECT * FROM mata_pelajaran WHERE status_aktif = 1 ORDER BY tingkat_kelas ASC, kelompok ASC, nama_mapel ASC");
        if ($stmtMapel) {
            $mapelList = $stmtMapel->fetchAll();
        }
    } catch (PDOException $e) {
        // Fallback jika nama tabel atau kolom di database berbeda
        try {
            $stmtMapelAlt = $conn->query("SELECT * FROM mapel ORDER BY id ASC");
            if ($stmtMapelAlt) {
                $mapelList = $stmtMapelAlt->fetchAll();
            }
        } catch (PDOException $e2) {
            $mapelList = [];
        }
    }
}

// Fallback Data jika database belum terisi atau belum memiliki tabel mata_pelajaran
if (empty($mapelList)) {
    $mapelList = [
        [
            'kode_mapel' => 'MP-AGM-10',
            'nama_mapel' => 'Pendidikan Agama & Budi Pekerti',
            'kelompok' => 'Umum',
            'tingkat_kelas' => 'X, XI, XII',
            'fase' => 'Fase E & F',
            'jam_per_minggu' => 3,
            'kkm' => 78,
            'ikon' => 'fa-book-quran',
            'deskripsi' => 'Penguatan akhlak mulia, toleransi antar umat beragama, dan pemahaman kitab suci sesuai ajaran agama masing-masing.'
        ],
        [
            'kode_mapel' => 'MP-PPKN-10',
            'nama_mapel' => 'Pancasila & Kewarganegaraan',
            'kelompok' => 'Umum',
            'tingkat_kelas' => 'X, XI, XII',
            'fase' => 'Fase E & F',
            'jam_per_minggu' => 2,
            'kkm' => 78,
            'ikon' => 'fa-landmark-flag',
            'deskripsi' => 'Membentuk wawasan kebangsaan, kesadaran hukum, hak asasi manusia, dan jiwa kepemimpinan Pancasila.'
        ],
        [
            'kode_mapel' => 'MP-BIN-10',
            'nama_mapel' => 'Bahasa Indonesia',
            'kelompok' => 'Umum',
            'tingkat_kelas' => 'X, XI, XII',
            'fase' => 'Fase E & F',
            'jam_per_minggu' => 4,
            'kkm' => 80,
            'ikon' => 'fa-pen-nib',
            'deskripsi' => 'Pengembangan kemampuan berbahasa, analisis teks ilmiah, kebudayaan sastra, serta kemahiran tata tulisan ilmiah.'
        ],
        [
            'kode_mapel' => 'MP-MTK-10',
            'nama_mapel' => 'Matematika Utama',
            'kelompok' => 'Umum',
            'tingkat_kelas' => 'X, XI, XII',
            'fase' => 'Fase E & F',
            'jam_per_minggu' => 4,
            'kkm' => 78,
            'ikon' => 'fa-calculator',
            'deskripsi' => 'Penalaran logis, aljabar, trigonometri, statistika, dan pemecahan masalah matematis kehidupan nyata.'
        ],
        [
            'kode_mapel' => 'MP-BIG-10',
            'nama_mapel' => 'Bahasa Inggris',
            'kelompok' => 'Umum',
            'tingkat_kelas' => 'X, XI, XII',
            'fase' => 'Fase E & F',
            'jam_per_minggu' => 3,
            'kkm' => 80,
            'ikon' => 'fa-language',
            'deskripsi' => 'Komunikasi internasional, analisis teks akademis, public speaking, dan persiapan studi kejenjang tinggi.'
        ],
        [
            'kode_mapel' => 'MP-FIS-11',
            'nama_mapel' => 'Fisika Terapan & Digital',
            'kelompok' => 'MIPA',
            'tingkat_kelas' => 'X, XI, XII',
            'fase' => 'Fase E & F',
            'jam_per_minggu' => 5,
            'kkm' => 78,
            'ikon' => 'fa-atom',
            'deskripsi' => 'Mekanika, termodinamika, gelombang elektromagnetik, elektronika, serta eksperimen laboratorium berbasis sensor.'
        ],
        [
            'kode_mapel' => 'MP-KIM-11',
            'nama_mapel' => 'Kimia & Material Modern',
            'kelompok' => 'MIPA',
            'tingkat_kelas' => 'X, XI, XII',
            'fase' => 'Fase E & F',
            'jam_per_minggu' => 5,
            'kkm' => 78,
            'ikon' => 'fa-vial-virus',
            'deskripsi' => 'Struktur atom, ikatan kimia, stoikiometri, reaksi redoks, dan aplikasi ilmu material terapan.'
        ],
        [
            'kode_mapel' => 'MP-BIO-11',
            'nama_mapel' => 'Biologi & Bioteknologi',
            'kelompok' => 'MIPA',
            'tingkat_kelas' => 'X, XI, XII',
            'fase' => 'Fase E & F',
            'jam_per_minggu' => 5,
            'kkm' => 78,
            'ikon' => 'fa-dna',
            'deskripsi' => 'Anatomi makhluk hidup, genetika molekuler, ekosistem lingkungan, dan bioteknologi modern.'
        ],
        [
            'kode_mapel' => 'MP-INF-10',
            'nama_mapel' => 'Informatika & Koding AI',
            'kelompok' => 'MIPA',
            'tingkat_kelas' => 'X, XI, XII',
            'fase' => 'Fase E & F',
            'jam_per_minggu' => 4,
            'kkm' => 80,
            'ikon' => 'fa-code',
            'deskripsi' => 'Berpikir komputasional, algoritma pemograman Python/Web, jaringan komputer, dan dasar kecerdasan buatan.'
        ],
        [
            'kode_mapel' => 'MP-EKO-11',
            'nama_mapel' => 'Ekonomi & Finansial',
            'kelompok' => 'IPS',
            'tingkat_kelas' => 'X, XI, XII',
            'fase' => 'Fase E & F',
            'jam_per_minggu' => 5,
            'kkm' => 78,
            'ikon' => 'fa-chart-line',
            'deskripsi' => 'Prinsip ekonomi makro-mikro, akuntansi keuangan, literasi investasi, serta bisnis digital modern.'
        ],
        [
            'kode_mapel' => 'MP-SOS-11',
            'nama_mapel' => 'Sosiologi Masyarakat',
            'kelompok' => 'IPS',
            'tingkat_kelas' => 'X, XI, XII',
            'fase' => 'Fase E & F',
            'jam_per_minggu' => 5,
            'kkm' => 78,
            'ikon' => 'fa-users-between-lines',
            'deskripsi' => 'Struktur sosial, perubahan dinamika masyarakat, metode penelitian sosial, serta penyelesaian konflik.'
        ],
        [
            'kode_mapel' => 'MP-GEO-11',
            'nama_mapel' => 'Geografi & Geosparsial',
            'kelompok' => 'IPS',
            'tingkat_kelas' => 'X, XI, XII',
            'fase' => 'Fase E & F',
            'jam_per_minggu' => 5,
            'kkm' => 78,
            'ikon' => 'fa-earth-asia',
            'deskripsi' => 'Litosfer, hidrosfer, pemetaan digital Sistem Informasi Geografis (SIG), serta pengelolaan bencana alam.'
        ],
        [
            'kode_mapel' => 'MP-PLBJ-10',
            'nama_mapel' => 'Seni & Budaya Betawi (PLBJ)',
            'kelompok' => 'Muatan Lokal',
            'tingkat_kelas' => 'X, XI, XII',
            'fase' => 'Fase E & F',
            'jam_per_minggu' => 2,
            'kkm' => 80,
            'ikon' => 'fa-masks-theater',
            'deskripsi' => 'Pelestarian kebudayaan lokal DKI Jakarta, tata krama daerah, kesenian tradisional Betawi, dan lingkungan Jakarta.'
        ]
    ];
}

$logoPath = fixAssetPath($sekolah['logo']);
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Mata Pelajaran & Kurikulum — SMA NEGERI 21 JAKARTA</title>
    <meta name="description" content="Daftar Mata Pelajaran & Struktur Kurikulum Merdeka di SMA Negeri 21 Jakarta. Sajian mata pelajaran unggulan umum, MIPA, IPS, dan Muatan Lokal.">
    
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
            transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
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
            box-shadow: 0 14px 28px -4px rgba(15, 23, 42, 0.09);
            border-color: #CBD5E1;
        }

        .hero-banner-overlay {
            background: linear-gradient(135deg, rgba(6, 18, 36, 0.92) 0%, rgba(11, 30, 54, 0.85) 60%, rgba(15, 23, 42, 0.90) 100%);
        }

        .filter-btn-active {
            background-color: #00A8CC !important;
            color: #FFFFFF !important;
            border-color: #00A8CC !important;
            box-shadow: 0 4px 12px rgba(0, 168, 204, 0.3);
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
                        <button class="px-3.5 py-2 rounded-lg text-cyan-600 bg-cyan-50 font-bold border border-cyan-200 flex items-center gap-1.5 transition-all">
                            <span>Akademik</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full left-0 w-60 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Overview Akademik</a>
                                <a href="program.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Program Pendidikan</a>
                                <a href="guru.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Daftar Guru & Staf</a>
                                <a href="mata-pelajaran.php" class="block px-3 py-2 rounded-lg bg-cyan-50 font-bold text-cyan-600 transition-colors">Mata Pelajaran</a>
                                <a href="pemeringkatan.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Pemeringkatan Jurusan</a>
                                <a href="keterserapan-ptn.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Keterserapan PTN</a>
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
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Akademik</span>
                <a href="index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Akademik</a>
                <a href="program.php" class="block pl-3 py-1 text-xs text-slate-700">Program Pendidikan</a>
                <a href="guru.php" class="block pl-3 py-1 text-xs text-slate-700">Daftar Guru</a>
                <a href="mata-pelajaran.php" class="block pl-3 py-1 text-xs text-cyan-600 font-bold">Mata Pelajaran</a>
                <a href="keterserapan-ptn.php" class="block pl-3 py-1 text-xs text-slate-700">Keterserapan PTN</a>
            </div>
            <a href="../prestasi/index.php" class="block text-xs font-semibold text-slate-700">Prestasi</a>
            <a href="../kegiatan/index.php" class="block text-xs font-semibold text-slate-700">Kegiatan & Ekskul</a>
            <a href="../informasi/ppdb.php" class="block text-xs font-semibold text-slate-700">PPDB Info</a>
            <a href="../kontak/index.php" class="block text-xs font-semibold text-slate-700">Kontak</a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 📖 PAGE HERO BANNER & BREADCRUMB                                      -->
    <!-- ===================================================================== -->
    <section class="relative bg-brand-darkNavy text-white py-16 sm:py-20 overflow-hidden border-b border-slate-800">
        <div class="absolute inset-0 hero-banner-overlay"></div>
        <div class="absolute inset-0 bg-cover bg-center opacity-20" style="background-image: url('../uploads/tempat/gedungA1.jpg');"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl space-y-4">
                
                <!-- Breadcrumb Navigation -->
                <nav class="flex items-center gap-2 text-xs text-cyan-300 font-medium">
                    <a href="../index.php" class="hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-house text-[10px]"></i>
                        <span>Beranda</span>
                    </a>
                    <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                    <span>Akademik</span>
                    <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                    <span class="text-white font-bold">Mata Pelajaran</span>
                </nav>

                <h1 class="text-3xl sm:text-5xl font-extrabold text-white leading-tight tracking-tight">
                    Mata Pelajaran & <span class="text-cyan-400">Kurikulum Merdeka</span>
                </h1>

                <p class="text-xs sm:text-base text-slate-300 leading-relaxed max-w-2xl">
                    Sajian materi kurikulum nasional terintegrasi di SMAN 21 Jakarta. Dirancang secara fleksibel untuk membentuk daya nalar kritis, kecakapan digital, serta profil Pelajar Pancasila.
                </p>

                <div class="pt-2 flex flex-wrap gap-3 text-xs">
                    <span class="px-3.5 py-1.5 rounded-md bg-white/10 border border-white/20 font-medium flex items-center gap-2">
                        <i class="fa-solid fa-book-open text-cyan-400"></i>
                        <span>Fase E (Kelas X) & Fase F (Kelas XI & XII)</span>
                    </span>
                    <span class="px-3.5 py-1.5 rounded-md bg-cyan-500/20 border border-cyan-400/30 text-cyan-300 font-medium flex items-center gap-2">
                        <i class="fa-solid fa-award text-cyan-400"></i>
                        <span>Akreditasi "A" BAN-S/M</span>
                    </span>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🎛️ FILTER & SEARCH SECTION                                          -->
    <!-- ===================================================================== -->
    <section class="py-8 bg-white border-b border-slate-200 sticky top-20 z-40 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                
                <!-- Filter Buttons -->
                <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-2 md:pb-0 scrollbar-none">
                    <button onclick="filterGroup('semua')" class="filter-btn filter-btn-active px-4 py-2 rounded-lg text-xs font-bold border border-slate-200 text-slate-700 whitespace-nowrap transition-all">
                        <i class="fa-solid fa-layer-group mr-1.5"></i>Semua Kelompok
                    </button>
                    <button onclick="filterGroup('Umum')" class="filter-btn px-4 py-2 rounded-lg text-xs font-bold border border-slate-200 text-slate-700 bg-slate-50 hover:bg-slate-100 whitespace-nowrap transition-all">
                        <i class="fa-solid fa-book-bookmark mr-1.5 text-blue-600"></i>Mata Pelajaran Umum
                    </button>
                    <button onclick="filterGroup('MIPA')" class="filter-btn px-4 py-2 rounded-lg text-xs font-bold border border-slate-200 text-slate-700 bg-slate-50 hover:bg-slate-100 whitespace-nowrap transition-all">
                        <i class="fa-solid fa-flask mr-1.5 text-cyan-600"></i>Pilihan MIPA
                    </button>
                    <button onclick="filterGroup('IPS')" class="filter-btn px-4 py-2 rounded-lg text-xs font-bold border border-slate-200 text-slate-700 bg-slate-50 hover:bg-slate-100 whitespace-nowrap transition-all">
                        <i class="fa-solid fa-chart-pie mr-1.5 text-emerald-600"></i>Pilihan IPS / Soshum
                    </button>
                    <button onclick="filterGroup('Muatan Lokal')" class="filter-btn px-4 py-2 rounded-lg text-xs font-bold border border-slate-200 text-slate-700 bg-slate-50 hover:bg-slate-100 whitespace-nowrap transition-all">
                        <i class="fa-solid fa-icons mr-1.5 text-purple-600"></i>Muatan Lokal
                    </button>
                </div>

                <!-- Live Search Box -->
                <div class="relative w-full md:w-72 flex-shrink-0">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="searchInput" onkeyup="searchSubjects()" placeholder="Cari nama atau kode mapel..." class="w-full pl-9 pr-4 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all">
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📚 DAFTAR MATA PELAJARAN GRID                                         -->
    <!-- ===================================================================== -->
    <section class="py-14 bg-slate-50 min-h-[500px]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div id="subjectGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <?php foreach ($mapelList as $idx => $m): 
                    $kelompok = $m['kelompok'] ?? 'Umum';
                    $badgeBg = 'bg-blue-100 text-blue-800 border-blue-200';
                    $borderTop = 'border-t-4 border-blue-600';
                    $iconClass = $m['ikon'] ?? 'fa-book';

                    if (stripos($kelompok, 'MIPA') !== false || stripos($kelompok, 'Sains') !== false) {
                        $badgeBg = 'bg-cyan-100 text-cyan-800 border-cyan-200';
                        $borderTop = 'border-t-4 border-cyan-500';
                    } elseif (stripos($kelompok, 'IPS') !== false || stripos($kelompok, 'Sosial') !== false) {
                        $badgeBg = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                        $borderTop = 'border-t-4 border-emerald-600';
                    } elseif (stripos($kelompok, 'Lokal') !== false || stripos($kelompok, 'Seni') !== false) {
                        $badgeBg = 'bg-purple-100 text-purple-800 border-purple-200';
                        $borderTop = 'border-t-4 border-purple-600';
                    }
                ?>

                <div class="mapel-card school-card rounded-xl p-6 flex flex-col justify-between <?= $borderTop; ?> reveal-section" data-group="<?= htmlspecialchars($kelompok); ?>">
                    <div>
                        <!-- Header Card -->
                        <div class="flex items-center justify-between gap-2 mb-4">
                            <span class="px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-wider border <?= $badgeBg; ?>">
                                <?= htmlspecialchars($kelompok); ?>
                            </span>
                            <span class="text-[11px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                <?= htmlspecialchars($m['kode_mapel'] ?? 'MP-21'); ?>
                            </span>
                        </div>

                        <!-- Mapel Title & Icon -->
                        <div class="flex items-start gap-3.5 mb-3">
                            <div class="w-10 h-10 rounded-lg bg-slate-100 text-brand-darkNavy border border-slate-200 flex items-center justify-center flex-shrink-0 text-base">
                                <i class="fa-solid <?= htmlspecialchars($iconClass); ?>"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-brand-darkNavy leading-snug">
                                    <?= htmlspecialchars($m['nama_mapel']); ?>
                                </h3>
                                <p class="text-[11px] text-cyan-600 font-semibold mt-0.5">
                                    Tingkat: <?= htmlspecialchars($m['tingkat_kelas'] ?? 'X, XI, XII'); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <p class="text-xs text-slate-600 leading-relaxed mb-4 line-clamp-3">
                            <?= htmlspecialchars($m['deskripsi'] ?? 'Mata pelajaran unggulan dalam struktur kurikulum nasional SMAN 21 Jakarta.'); ?>
                        </p>
                    </div>

                    <!-- Card Footer Details -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500 font-medium">
                        <span class="flex items-center gap-1.5">
                            <i class="fa-regular fa-clock text-cyan-600"></i>
                            <span><?= htmlspecialchars($m['jam_per_minggu'] ?? '3'); ?> Jam / Minggu</span>
                        </span>
                        <?php if (!empty($m['kkm'])): ?>
                        <span class="flex items-center gap-1">
                            <i class="fa-solid fa-bullseye text-emerald-600"></i>
                            <span>KKM: <strong><?= htmlspecialchars($m['kkm']); ?></strong></span>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php endforeach; ?>

            </div>

            <!-- Empty State bila pencarian tidak ditemukan -->
            <div id="noResult" class="hidden text-center py-16 bg-white rounded-xl border border-slate-200 shadow-xs">
                <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-2xl mb-4">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <h3 class="text-base font-bold text-brand-darkNavy">Mata Pelajaran Tidak Ditemukan</h3>
                <p class="text-xs text-slate-500 mt-1">Coba gunakan kata kunci pencarian lain atau pilih filter kelompok mata pelajaran yang sesuai.</p>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🎓 STRUKTUR ALOKASI WAKTU KURIKULUM MERDEKA                             -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-white border-t border-slate-200 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-10 items-center">
                
                <div class="lg:col-span-5 space-y-5 reveal-section">
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-600 bg-cyan-50 px-3 py-1 rounded border border-cyan-200">
                        IMPLEMENTASI KURIKULUM
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-darkNavy leading-tight">
                        Struktur & Alokasi Waktu Pembelajaran
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Pembelajaran di SMAN 21 Jakarta terbagi atas dua komponen utama sesuai standar Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi:
                    </p>

                    <ul class="space-y-3 text-xs text-slate-700 font-medium">
                        <li class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded bg-cyan-100 text-cyan-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-xs font-bold">1</div>
                            <div>
                                <strong class="text-brand-darkNavy block">Intrakurikuler (Tatap Muka Sesi Kelas)</strong>
                                <span>Penguasaan materi mendalam, diskusi interaktif, serta latihan soal berbasis penalaran kritis (HOTS).</span>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-xs font-bold">2</div>
                            <div>
                                <strong class="text-brand-darkNavy block">Kokurikuler (Projek Penguatan Profil Pelajar Pancasila - P5)</strong>
                                <span>Projek ilmiah, gaya hidup berkelanjutan, kewirausahaan, serta kepemimpinan berbasis kolaborasi.</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="lg:col-span-7 reveal-section delay-200">
                    <div class="school-card p-6 rounded-2xl bg-slate-50 border border-slate-200">
                        <h3 class="text-sm font-bold text-brand-darkNavy mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-table-cells text-cyan-600"></i>
                            <span>Ringkasan Fase Kelas Kurikulum Merdeka</span>
                        </h3>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-700">
                                <thead>
                                    <tr class="bg-brand-darkNavy text-white">
                                        <th class="p-3 rounded-tl-lg">Fase & Kelas</th>
                                        <th class="p-3">Fokus Pembelajaran</th>
                                        <th class="p-3 rounded-tr-lg">Total Jam / Minggu</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <tr class="bg-white">
                                        <td class="p-3 font-bold text-brand-darkNavy">Fase E (Kelas X)</td>
                                        <td class="p-3">Fondasi ilmu umum, eksplorasi minat bakat, & pengenalan rumpun MIPA/IPS.</td>
                                        <td class="p-3 font-semibold text-cyan-600">44 Jam Pelajaran</td>
                                    </tr>
                                    <tr class="bg-slate-50">
                                        <td class="p-3 font-bold text-brand-darkNavy">Fase F (Kelas XI)</td>
                                        <td class="p-3">Pengalaman spesialisasi mata pelajaran pilihan utama MIPA, IPS, atau Bahasa.</td>
                                        <td class="p-3 font-semibold text-cyan-600">48 Jam Pelajaran</td>
                                    </tr>
                                    <tr class="bg-white">
                                        <td class="p-3 font-bold text-brand-darkNavy">Fase F (Kelas XII)</td>
                                        <td class="p-3">Pendalaman materi intensif, riset ilmiah, serta persiapan persaingan SNBP & SNBT PTN.</td>
                                        <td class="p-3 font-semibold text-cyan-600">48 Jam Pelajaran</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
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
            <div class="p-8 sm:p-12 rounded-2xl bg-gradient-to-r from-brand-darkNavy to-slate-800 border border-slate-700 text-center space-y-5 shadow-2xl">
                <div class="w-14 h-14 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center mx-auto text-2xl border border-cyan-500/30">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white">
                    Pertanyaan Seputar Kurikulum SMAN 21?
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                    S21 AI Assistant siap membantu Anda memberikan informasi cepat mengenai silabus, beban jam pelajaran, dan program akademik sekolah.
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
    <!-- 💬 AI ASSISTANT DRAWER PANEL & TOGGLE                                 -->
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
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div id="aiChatBox" class="flex-1 p-4 overflow-y-auto space-y-4 bg-slate-50 text-xs">
            <div class="flex gap-3">
                <div class="w-8 h-8 rounded-full bg-cyan-600 text-white flex items-center justify-center flex-shrink-0 font-bold text-xs shadow-sm">
                    21
                </div>
                <div class="bg-white p-3.5 rounded-2xl rounded-tl-none border border-slate-200 text-slate-700 max-w-[85%] space-y-2 shadow-xs">
                    <p>Halo! Saya <strong>S21 AI Assistant</strong>. Silakan ajukan pertanyaan seputar Mata Pelajaran, Kurikulum Merdeka, Jadwal Belajar, PPDB, atau Informasi Akademik SMAN 21 Jakarta.</p>
                </div>
            </div>
        </div>

        <form onsubmit="sendAiQuery(event)" class="p-3 bg-white border-t border-slate-200 flex gap-2">
            <input type="text" id="aiInput" placeholder="Tanyakan seputar mata pelajaran / kurikulum..." class="flex-1 px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none">
            <button type="submit" class="px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs rounded-lg transition-colors flex items-center justify-center">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>

    <button onclick="toggleAiPanel()" class="fixed bottom-6 right-6 z-[9980] w-14 h-14 rounded-full bg-cyan-600 hover:bg-cyan-500 text-white shadow-2xl flex items-center justify-center text-xl transition-all hover:scale-110 group border-2 border-white">
        <i class="fa-solid fa-robot group-hover:rotate-12 transition-transform"></i>
        <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-emerald-500 rounded-full border-2 border-white"></span>
    </button>

    <!-- ===================================================================== -->
    <!-- 📜 JAVASCRIPT & REVEAL ANIMATION                                      -->
    <!-- ===================================================================== -->
    <script>
        // Mobile Menu Toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }

        // AI Assistant Toggle & Messaging
        function toggleAiPanel() {
            const panel = document.getElementById('aiPanel');
            panel.classList.toggle('translate-x-full');
        }

        function sendAiQuery(e) {
            e.preventDefault();
            const input = document.getElementById('aiInput');
            const query = input.value.trim();
            if (!query) return;

            const chatBox = document.getElementById('aiChatBox');

            // Append User Message
            const userHtml = `
                <div class="flex gap-3 justify-end">
                    <div class="bg-cyan-600 text-white p-3.5 rounded-2xl rounded-tr-none text-xs max-w-[85%] shadow-xs">
                        <p>${escapeHtml(query)}</p>
                    </div>
                </div>
            `;
            chatBox.insertAdjacentHTML('beforeend', userHtml);
            input.value = '';
            chatBox.scrollTop = chatBox.scrollHeight;

            // Loading state
            const loadingId = 'loading-' + Date.now();
            const loadingHtml = `
                <div id="${loadingId}" class="flex gap-3">
                    <div class="w-8 h-8 rounded-full bg-cyan-600 text-white flex items-center justify-center flex-shrink-0 font-bold text-xs">21</div>
                    <div class="bg-white p-3 rounded-2xl rounded-tl-none border border-slate-200 text-slate-500 text-xs">
                        <i class="fa-solid fa-circle-notch fa-spin mr-2"></i>Mencari jawaban resmi...
                    </div>
                </div>
            `;
            chatBox.insertAdjacentHTML('beforeend', loadingHtml);
            chatBox.scrollTop = chatBox.scrollHeight;

            // Fetch AJAX to endpoint
            fetch('mata-pelajaran.php?action=ai_query', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'query=' + encodeURIComponent(query)
            })
            .then(res => res.json())
            .then(data => {
                const loader = document.getElementById(loadingId);
                if (loader) loader.remove();

                let linkBtn = '';
                if (data.related_link) {
                    linkBtn = `<a href="../${data.related_link}" class="inline-block mt-2 text-[11px] text-cyan-600 font-bold hover:underline">Buka Halaman Terkait →</a>`;
                }

                const aiHtml = `
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-cyan-600 text-white flex items-center justify-center flex-shrink-0 font-bold text-xs shadow-sm">21</div>
                        <div class="bg-white p-3.5 rounded-2xl rounded-tl-none border border-slate-200 text-slate-700 max-w-[85%] shadow-xs space-y-1">
                            <p>${data.answer}</p>
                            ${linkBtn}
                            <span class="block text-[9px] text-slate-400 mt-1 font-semibold">Sumber: ${data.source}</span>
                        </div>
                    </div>
                `;
                chatBox.insertAdjacentHTML('beforeend', aiHtml);
                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(() => {
                const loader = document.getElementById(loadingId);
                if (loader) loader.remove();
                const errHtml = `
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-cyan-600 text-white flex items-center justify-center flex-shrink-0 font-bold text-xs">21</div>
                        <div class="bg-white p-3.5 rounded-2xl rounded-tl-none border border-slate-200 text-slate-700 max-w-[85%] text-xs">
                            Maaf, terjadi kendala koneksi ke server. Silakan coba kembali.
                        </div>
                    </div>
                `;
                chatBox.insertAdjacentHTML('beforeend', errHtml);
                chatBox.scrollTop = chatBox.scrollHeight;
            });
        }

        function escapeHtml(text) {
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        // Filter Group Mata Pelajaran
        let currentFilter = 'semua';
        function filterGroup(group) {
            currentFilter = group;
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => btn.classList.remove('filter-btn-active'));
            event.currentTarget.classList.add('filter-btn-active');
            applyFilters();
        }

        // Search Subjects
        function searchSubjects() {
            applyFilters();
        }

        function applyFilters() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase().trim();
            const cards = document.querySelectorAll('.mapel-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const cardGroup = card.getAttribute('data-group') || '';
                const cardText = card.textContent.toLowerCase();

                const matchesFilter = (currentFilter === 'semua') || (cardGroup.toLowerCase().includes(currentFilter.toLowerCase()));
                const matchesSearch = (cardText.includes(searchValue));

                if (matchesFilter && matchesSearch) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            const noResult = document.getElementById('noResult');
            if (visibleCount === 0) {
                noResult.classList.remove('hidden');
            } else {
                noResult.classList.add('hidden');
            }
        }

        // Scroll Reveal Animations
        document.addEventListener('DOMContentLoaded', () => {
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.reveal-section').forEach(el => observer.observe(el));
        });
    </script>

</body>
</html>
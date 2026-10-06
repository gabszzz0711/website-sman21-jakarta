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
// HELPER FORMATTING & PATH UTILITIES
// =============================================================================
function formatBeritaImg($img, $default = '../uploads/logo/sman21jakarta.png') {
    if (empty($img)) return $default;
    $img = trim($img);
    if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
        return $img;
    }
    if (strpos($img, '../') === 0) {
        return $img;
    }
    if (strpos($img, 'uploads/') === 0) {
        return '../' . $img;
    }
    return '../uploads/berita/' . ltrim($img, '/');
}

function formatTanggalIndo($datetime) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00' || $datetime === '0000-00-00') {
        return 'Terbaru';
    }
    $time = strtotime($datetime);
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $d = date('j', $time);
    $m = $bulan[(int)date('n', $time)] ?? date('M', $time);
    $y = date('Y', $time);
    return "$d $m $y";
}

// =============================================================================
// ENDPOINT AJAX UNTUK S21 AI ASSISTANT (CONTEXT-AWARE BERITA)
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
    $sumberInfo = "Portal Berita SMAN 21 Jakarta";
    $relatedLink = "";

    if ($conn) {
        $stmtAiCfg = $conn->query("SELECT pesan_tidak_tersedia FROM ai_pengaturan LIMIT 1");
        $aiCfg = $stmtAiCfg ? $stmtAiCfg->fetch() : null;
        $fallbackMsg = $aiCfg['pesan_tidak_tersedia'] ?? "Maaf, informasi tersebut belum tersedia dalam basis data resmi SMAN 21 Jakarta.";

        $matched = false;
        $qLower = strtolower($userQuery);

        // 1. Cari langsung di tabel Berita
        $stmtSearchBrt = $conn->prepare("SELECT * FROM berita WHERE status='published' AND (LOWER(judul) LIKE :q OR LOWER(ringkasan) LIKE :q OR LOWER(kategori) LIKE :q) ORDER BY id DESC LIMIT 1");
        $stmtSearchBrt->execute([':q' => "%$qLower%"]);
        if ($foundBrt = $stmtSearchBrt->fetch()) {
            $responseAnswer = "Berita Ditemukan: \"" . htmlspecialchars($foundBrt['judul']) . "\". " . htmlspecialchars($foundBrt['ringkasan']);
            $sumberInfo = "Artikel Berita SMAN 21 (" . htmlspecialchars($foundBrt['kategori']) . ")";
            $relatedLink = "berita-detail.php?slug=" . urlencode($foundBrt['slug']);
            $matched = true;
        }

        // 2. Jika tidak ada di berita, cari di AI Knowledge Base
        if (!$matched) {
            $stmtKnw = $conn->prepare("SELECT * FROM ai_knowledge WHERE status_aktif = 1 ORDER BY prioritas DESC");
            $stmtKnw->execute();
            $knowledges = $stmtKnw->fetchAll();

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
        }

        if (!$matched) {
            $responseAnswer = $fallbackMsg;
            $sumberInfo = "Pusat Layanan Informasi SMAN 21";
        }

    } else {
        $responseAnswer = "Terima kasih telah bertanya. Informasi mengenai berita dan agenda terbaru SMAN 21 Jakarta diperbarui secara berkala.";
        $sumberInfo = "Portal Informasi SMAN 21";
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
// FETCH DATA PENGATURAN SEKOLAH & PROFIL
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
    } catch (PDOException $e) {}
}

$logoPath = $sekolah['logo'];

// =============================================================================
// FILTER & PAGINASI DATA BERITA
// =============================================================================
$searchKeyword = trim($_GET['q'] ?? '');
$filterKategori = trim($_GET['kategori'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 6;
$offset = ($page - 1) * $limit;

$whereClause = ["status = 'published'"];
$params = [];

if (!empty($searchKeyword)) {
    $whereClause[] = "(judul LIKE :q OR ringkasan LIKE :q OR isi LIKE :q)";
    $params[':q'] = "%$searchKeyword%";
}

if (!empty($filterKategori) && $filterKategori !== 'semua') {
    $whereClause[] = "kategori = :kategori";
    $params[':kategori'] = $filterKategori;
}

$whereSQL = "WHERE " . implode(" AND ", $whereClause);

// Fetch Daftar Berita
$beritaList = [];
$totalBerita = 0;
$totalPages = 1;

// Fetch Headlines / Popular
$headlineBerita = null;
$popularBerita = [];
$agendaList = [];
$kategoriList = [];

if ($conn) {
    try {
        // Total Count
        $stmtCount = $conn->prepare("SELECT COUNT(*) FROM berita $whereSQL");
        $stmtCount->execute($params);
        $totalBerita = (int)$stmtCount->fetchColumn();
        $totalPages = max(1, ceil($totalBerita / $limit));

        // Get Berita List
        $stmtBrt = $conn->prepare("SELECT * FROM berita $whereSQL ORDER BY id DESC LIMIT :limit OFFSET :offset");
        foreach ($params as $k => $v) {
            $stmtBrt->bindValue($k, $v);
        }
        $stmtBrt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmtBrt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmtBrt->execute();
        $beritaList = $stmtBrt->fetchAll();

        // Get Headline News (Berita Terbaru Utama)
        $stmtHead = $conn->query("SELECT * FROM berita WHERE status='published' ORDER BY id DESC LIMIT 1");
        if ($stmtHead) $headlineBerita = $stmtHead->fetch();

        // Get Popular News
        $stmtPop = $conn->query("SELECT * FROM berita WHERE status='published' ORDER BY jumlah_dilihat DESC, id DESC LIMIT 5");
        if ($stmtPop) $popularBerita = $stmtPop->fetchAll();

        // Get Categories
        $stmtKat = $conn->query("SELECT kategori, COUNT(*) as total FROM berita WHERE status='published' GROUP BY kategori ORDER BY total DESC");
        if ($stmtKat) $kategoriList = $stmtKat->fetchAll();

        // Get Upcoming Agenda
        $stmtAgd = $conn->query("SELECT * FROM agenda WHERE status='published' ORDER BY tanggal_mulai ASC LIMIT 3");
        if ($stmtAgd) $agendaList = $stmtAgd->fetchAll();

    } catch (PDOException $e) {}
}

// Fallback Mock Data jika DB belum terisi
if (empty($beritaList) && empty($searchKeyword) && empty($filterKategori)) {
    $beritaList = [
        [
            'id' => 1,
            'judul' => 'Pengumuman Pelaksanaan PPDB SMAN 21 Jakarta Tahun Ajaran 2026/2027',
            'slug' => 'pengumuman-ppdb-sman-21-jakarta-2026-2027',
            'ringkasan' => 'SMAN 21 Jakarta secara resmi membuka tahapan sosialisasi dan persiapan Penerimaan Peserta Didik Baru (PPDB) T.A. 2026/2027 berbasis sistem online terintegrasi.',
            'isi' => 'Konten lengkap pengumuman PPDB...',
            'thumbnail' => '../uploads/tempat/gedungA1.jpg',
            'penulis' => 'Humas SMAN 21',
            'kategori' => 'PPDB',
            'tanggal_publikasi' => date('Y-m-d H:i:s'),
            'jumlah_dilihat' => 450
        ],
        [
            'id' => 2,
            'judul' => 'Siswa SMAN 21 Jakarta Raih Medali Emas Olimpiade Sains Nasional (OSN) 2026',
            'slug' => 'siswa-sman-21-raih-medali-emas-osn-2026',
            'ringkasan' => 'Prestasi membanggakan kembali diukir oleh kontingen peserta didik SMAN 21 Jakarta pada ajang Olimpiade Sains Nasional bidang Fisika dan Informatika.',
            'isi' => 'Konten lengkap liputan prestasi...',
            'thumbnail' => '../uploads/tempat/gedungA.jpg',
            'penulis' => 'Tim Kesiswaan',
            'kategori' => 'Prestasi',
            'tanggal_publikasi' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'jumlah_dilihat' => 320
        ],
        [
            'id' => 3,
            'judul' => 'Implementasi Proyek Profil Pelajar Pancasila (P5) Berbasis Digital & Lingkungan',
            'slug' => 'implementasi-proyek-p5-berbasis-digital-lingkungan',
            'ringkasan' => 'Kegiatan gelar karya P5 SMAN 21 Jakarta mengusung tema kewirausahaan digital dan pengelolaan lingkungan berkelanjutan.',
            'isi' => 'Konten lengkap kegiatan P5...',
            'thumbnail' => '../uploads/tempat/gedungA1.jpg',
            'penulis' => 'Tim Kurikulum',
            'kategori' => 'Akademik',
            'tanggal_publikasi' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'jumlah_dilihat' => 210
        ]
    ];
    $totalBerita = count($beritaList);
    $headlineBerita = $beritaList[0];
    $popularBerita = $beritaList;
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Berita & Informasi Resmi — SMA NEGERI 21 JAKARTA</title>
    <meta name="description" content="Kumpulan berita resmi, artikel akademik, kabar prestasi, dan pengumuman terbaru SMAN 21 Jakarta.">
    
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
                                <a href="berita.php" class="block px-3 py-2 rounded-lg bg-cyan-50 text-cyan-600 font-bold transition-colors">Berita Sekolah</a>
                                <a href="agenda.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Agenda & Kalender</a>
                                <a href="ppdb.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">PPDB Info</a>
                                <a href="../gallery/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Galeri Foto</a>
                                <a href="../kontak/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Kontak & Lokasi</a>
                            </div>
                        </div>
                    </div>

                    <a href="ppdb.php" class="ml-2 px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs flex items-center gap-2 shadow-sm transition-all">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>SPMB Online</span>
                    </a>
                </nav>

                <button onclick="toggleMobileMenu()" class="lg:hidden p-2 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200 transition-colors">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Drawer -->
        <div id="mobileMenu" class="hidden lg:hidden bg-white border-t border-slate-200 px-6 py-6 space-y-4 max-h-[80vh] overflow-y-auto">
            <a href="../index.php" class="block text-xs font-semibold text-slate-700">Beranda</a>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Profil</span>
                <a href="../profil/index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Profil</a>
                <a href="../profil/sejarah.php" class="block pl-3 py-1 text-xs text-slate-700">Sejarah</a>
                <a href="../profil/visi-misi.php" class="block pl-3 py-1 text-xs text-slate-700">Visi & Misi</a>
                <a href="../profil/sambutan.php" class="block pl-3 py-1 text-xs text-slate-700">Sambutan Kepsek</a>
            </div>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-cyan-600 font-bold tracking-wider">Informasi</span>
                <a href="berita.php" class="block pl-3 py-1 text-xs font-bold text-cyan-600">Berita Sekolah</a>
                <a href="agenda.php" class="block pl-3 py-1 text-xs text-slate-700">Agenda & Kalender</a>
                <a href="ppdb.php" class="block pl-3 py-1 text-xs text-slate-700">PPDB Info</a>
            </div>
            <a href="../prestasi/index.php" class="block text-xs font-semibold text-slate-700">Prestasi</a>
            <a href="../kegiatan/index.php" class="block text-xs font-semibold text-slate-700">Kegiatan & Ekskul</a>
            <a href="../kontak/index.php" class="block text-xs font-semibold text-slate-700">Kontak</a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 🏙️ PAGE HERO BANNER                                                   -->
    <!-- ===================================================================== -->
    <section class="bg-brand-darkNavy text-white py-14 sm:py-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-20 bg-cover bg-center" style="background-image: url('../uploads/tempat/gedungA1.jpg');"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-brand-darkNavy via-brand-navy/90 to-brand-darkNavy"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-xs text-slate-400 mb-4">
                <a href="../index.php" class="hover:text-cyan-400 transition-colors">Beranda</a>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-600"></i>
                <span class="text-slate-300">Informasi</span>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-600"></i>
                <span class="text-cyan-400 font-bold">Berita Sekolah</span>
            </nav>

            <div class="max-w-3xl space-y-3 reveal-section is-visible">
                <span class="inline-block px-3 py-1 rounded bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 text-[11px] font-bold uppercase tracking-wider">
                    Pusat Kabar & Pengumuman
                </span>
                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white leading-tight">
                    Berita <span class="text-cyan-400">SMAN 21 Jakarta</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    Dapatkan sajian kabar terbaru seputar kegiatan sekolah, pengumuman akademik, prestasi siswa, sosialisasi PPDB, serta liputan acara secara transparan dan akurat.
                </p>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📰 FEATURED / HEADLINE BERITA TERBARU                                 -->
    <!-- ===================================================================== -->
    <?php if (!empty($headlineBerita) && empty($searchKeyword) && (empty($filterKategori) || $filterKategori === 'semua') && $page === 1): 
        $headImg = formatBeritaImg($headlineBerita['thumbnail'] ?: $headlineBerita['gambar_utama']);
    ?>
    <section class="py-12 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="school-card rounded-2xl overflow-hidden bg-white border border-slate-200 grid lg:grid-cols-12 gap-0 shadow-lg reveal-section">
                
                <div class="lg:col-span-7 relative min-h-[260px] sm:min-h-[360px] overflow-hidden group">
                    <img src="<?= htmlspecialchars($headImg); ?>" alt="<?= htmlspecialchars($headlineBerita['judul']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent lg:hidden"></div>
                    <span class="absolute top-4 left-4 px-3 py-1 bg-cyan-600 text-white text-[10px] font-extrabold tracking-wider uppercase rounded shadow">
                        HEADLINE UTAMA
                    </span>
                </div>

                <div class="lg:col-span-5 p-6 sm:p-8 flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-[11px] text-slate-500 font-semibold">
                            <span class="px-2.5 py-0.5 rounded bg-cyan-100 text-cyan-800 uppercase text-[10px] font-bold">
                                <?= htmlspecialchars($headlineBerita['kategori'] ?? 'Umum'); ?>
                            </span>
                            <span><i class="fa-regular fa-calendar text-cyan-600 mr-1"></i><?= formatTanggalIndo($headlineBerita['tanggal_publikasi']); ?></span>
                        </div>

                        <h2 class="text-xl sm:text-2xl font-extrabold text-brand-darkNavy hover:text-cyan-600 transition-colors leading-snug">
                            <a href="berita-detail.php?slug=<?= htmlspecialchars($headlineBerita['slug']); ?>">
                                <?= htmlspecialchars($headlineBerita['judul']); ?>
                            </a>
                        </h2>

                        <p class="text-xs text-slate-600 leading-relaxed line-clamp-3">
                            <?= htmlspecialchars($headlineBerita['ringkasan'] ?: strip_tags($headlineBerita['isi'])); ?>
                        </p>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500 font-medium">
                            <i class="fa-regular fa-user mr-1 text-cyan-600"></i><?= htmlspecialchars($headlineBerita['penulis'] ?: 'Admin SMAN 21'); ?>
                        </span>
                        <a href="berita-detail.php?slug=<?= htmlspecialchars($headlineBerita['slug']); ?>" class="px-4 py-2 rounded-lg bg-brand-darkNavy hover:bg-cyan-600 text-white font-bold text-xs transition-colors flex items-center gap-1.5 shadow">
                            <span>Baca Selengkapnya</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===================================================================== -->
    <!-- 🔍 SEARCH BAR & KATEGORI FILTER                                       -->
    <!-- ===================================================================== -->
    <section class="py-8 bg-slate-100 border-b border-slate-200 sticky top-20 z-40 backdrop-blur-md bg-slate-100/90">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                
                <!-- Category Pills -->
                <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-2 md:pb-0 scrollbar-none">
                    <a href="berita.php" class="px-4 py-2 rounded-full text-xs font-bold transition-all whitespace-nowrap <?= (empty($filterKategori) || $filterKategori === 'semua') ? 'bg-cyan-600 text-white shadow' : 'bg-white text-slate-700 hover:bg-slate-200 border border-slate-200'; ?>">
                        Semua Kategori
                    </a>
                    <?php if (!empty($kategoriList)): foreach ($kategoriList as $kat): $katName = $kat['kategori']; ?>
                    <a href="berita.php?kategori=<?= urlencode($katName); ?>&q=<?= urlencode($searchKeyword); ?>" class="px-4 py-2 rounded-full text-xs font-bold transition-all whitespace-nowrap <?= ($filterKategori === $katName) ? 'bg-cyan-600 text-white shadow' : 'bg-white text-slate-700 hover:bg-slate-200 border border-slate-200'; ?>">
                        <?= htmlspecialchars($katName); ?>
                    </a>
                    <?php endforeach; else: ?>
                    <a href="berita.php?kategori=PPDB" class="px-4 py-2 rounded-full text-xs font-bold bg-white text-slate-700 hover:bg-slate-200 border border-slate-200 whitespace-nowrap">PPDB</a>
                    <a href="berita.php?kategori=Akademik" class="px-4 py-2 rounded-full text-xs font-bold bg-white text-slate-700 hover:bg-slate-200 border border-slate-200 whitespace-nowrap">Akademik</a>
                    <a href="berita.php?kategori=Prestasi" class="px-4 py-2 rounded-full text-xs font-bold bg-white text-slate-700 hover:bg-slate-200 border border-slate-200 whitespace-nowrap">Prestasi</a>
                    <?php endif; ?>
                </div>

                <!-- Search Form -->
                <form action="berita.php" method="GET" class="w-full md:w-80 flex items-center gap-2">
                    <?php if (!empty($filterKategori)): ?>
                        <input type="hidden" name="kategori" value="<?= htmlspecialchars($filterKategori); ?>">
                    <?php endif; ?>
                    <div class="relative w-full">
                        <input type="text" name="q" value="<?= htmlspecialchars($searchKeyword); ?>" placeholder="Cari berita atau pengumuman..." class="w-full pl-9 pr-8 py-2 rounded-xl border border-slate-300 text-xs focus:outline-none focus:border-cyan-600 bg-white shadow-sm">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <?php if (!empty($searchKeyword)): ?>
                        <a href="berita.php" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-brand-darkNavy hover:bg-slate-800 text-white text-xs font-bold transition-colors">
                        Cari
                    </button>
                </form>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📚 CONTENT GRID & SIDEBAR                                             -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-10">
                
                <!-- MAIN ARTICLES LIST (8 COLS) -->
                <div class="lg:col-span-8 space-y-8">
                    
                    <?php if (!empty($searchKeyword) || (!empty($filterKategori) && $filterKategori !== 'semua')): ?>
                    <div class="p-4 rounded-xl bg-cyan-50 border border-cyan-200 text-xs text-slate-700 flex items-center justify-between">
                        <div>
                            <span>Menampilkan hasil untuk: </span>
                            <?php if (!empty($searchKeyword)): ?>
                                <strong class="text-cyan-700">"<?= htmlspecialchars($searchKeyword); ?>"</strong>
                            <?php endif; ?>
                            <?php if (!empty($filterKategori) && $filterKategori !== 'semua'): ?>
                                <span class="ml-1 bg-cyan-600 text-white px-2 py-0.5 rounded text-[10px] font-bold uppercase"><?= htmlspecialchars($filterKategori); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="berita.php" class="text-cyan-700 font-bold hover:underline">Reset Filter</a>
                    </div>
                    <?php endif; ?>

                    <!-- BERITA CARDS GRID -->
                    <?php if (!empty($beritaList)): ?>
                    <div class="grid md:grid-cols-2 gap-6">
                        <?php foreach ($beritaList as $index => $brt): 
                            $bImg = formatBeritaImg($brt['thumbnail'] ?: $brt['gambar_utama']);
                            $bSlug = htmlspecialchars($brt['slug']);
                        ?>
                        <article class="school-card rounded-xl overflow-hidden bg-white border border-slate-200 flex flex-col justify-between reveal-section delay-<?= ($index % 3) * 100; ?>">
                            <div>
                                <div class="relative aspect-video overflow-hidden bg-slate-100 group">
                                    <img src="<?= htmlspecialchars($bImg); ?>" alt="<?= htmlspecialchars($brt['judul']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                    <span class="absolute top-3 left-3 px-2.5 py-1 rounded bg-brand-darkNavy/80 backdrop-blur-sm text-cyan-300 text-[10px] font-bold uppercase tracking-wider">
                                        <?= htmlspecialchars($brt['kategori'] ?? 'Umum'); ?>
                                    </span>
                                </div>
                                <div class="p-5 space-y-2">
                                    <div class="flex items-center gap-3 text-[11px] text-slate-400 font-medium">
                                        <span><i class="fa-regular fa-calendar text-cyan-600 mr-1"></i><?= formatTanggalIndo($brt['tanggal_publikasi']); ?></span>
                                        <span><i class="fa-regular fa-eye text-cyan-600 mr-1"></i><?= (int)($brt['jumlah_dilihat'] ?? 0); ?> views</span>
                                    </div>

                                    <h3 class="text-sm font-bold text-brand-darkNavy hover:text-cyan-600 transition-colors leading-snug line-clamp-2">
                                        <a href="berita-detail.php?slug=<?= $bSlug; ?>">
                                            <?= htmlspecialchars($brt['judul']); ?>
                                        </a>
                                    </h3>

                                    <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                        <?= htmlspecialchars($brt['ringkasan'] ?: strip_tags($brt['isi'])); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="px-5 pb-5 pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                                <span class="text-[11px] text-slate-400 font-medium">
                                    <i class="fa-regular fa-user text-cyan-600 mr-1"></i><?= htmlspecialchars($brt['penulis'] ?: 'Admin'); ?>
                                </span>
                                <a href="berita-detail.php?slug=<?= $bSlug; ?>" class="text-xs font-bold text-cyan-600 hover:text-cyan-700 flex items-center gap-1 group">
                                    <span>Selengkapnya</span>
                                    <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition-transform"></i>
                                </a>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- PAGINASI -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pt-8 flex justify-center items-center gap-2">
                        <?php if ($page > 1): ?>
                        <a href="berita.php?page=<?= $page - 1; ?>&q=<?= urlencode($searchKeyword); ?>&kategori=<?= urlencode($filterKategori); ?>" class="w-9 h-9 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-cyan-600 hover:text-white flex items-center justify-center transition-colors text-xs font-bold shadow-sm">
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="berita.php?page=<?= $p; ?>&q=<?= urlencode($searchKeyword); ?>&kategori=<?= urlencode($filterKategori); ?>" class="w-9 h-9 rounded-lg border text-xs font-bold flex items-center justify-center transition-all shadow-sm <?= $p === $page ? 'bg-cyan-600 border-cyan-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-100'; ?>">
                            <?= $p; ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                        <a href="berita.php?page=<?= $page + 1; ?>&q=<?= urlencode($searchKeyword); ?>&kategori=<?= urlencode($filterKategori); ?>" class="w-9 h-9 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-cyan-600 hover:text-white flex items-center justify-center transition-colors text-xs font-bold shadow-sm">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php else: ?>
                    <div class="school-card p-12 rounded-2xl text-center space-y-4 bg-white border border-slate-200">
                        <div class="w-16 h-16 rounded-full bg-cyan-100 text-cyan-600 flex items-center justify-center mx-auto text-2xl">
                            <i class="fa-solid fa-newspaper"></i>
                        </div>
                        <h3 class="text-base font-bold text-brand-darkNavy">Berita Tidak Ditemukan</h3>
                        <p class="text-xs text-slate-500 max-w-md mx-auto">
                            Maaf, artikel atau pengumuman yang Anda cari belum tersedia. Silakan gunakan kata kunci lain atau pilih kategori yang berbeda.
                        </p>
                        <a href="berita.php" class="px-6 py-2.5 rounded-lg bg-cyan-600 text-white font-bold text-xs inline-block shadow">
                            Lihat Semua Berita
                        </a>
                    </div>
                    <?php endif; ?>

                </div>

                <!-- SIDEBAR WIDGETS (4 COLS) -->
                <div class="lg:col-span-4 space-y-8">
                    
                    <!-- Widget: Berita Populer -->
                    <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 space-y-4 reveal-section">
                        <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                            <i class="fa-solid fa-fire text-cyan-600"></i>
                            <h3 class="text-sm font-bold text-brand-darkNavy uppercase tracking-wider">Berita Populer</h3>
                        </div>

                        <div class="space-y-3">
                            <?php if (!empty($popularBerita)): foreach ($popularBerita as $popIndex => $pop): 
                                $popImg = formatBeritaImg($pop['thumbnail'] ?: $pop['gambar_utama']);
                            ?>
                            <a href="berita-detail.php?slug=<?= htmlspecialchars($pop['slug']); ?>" class="flex items-center gap-3 group">
                                <div class="w-16 h-16 rounded-lg bg-slate-100 overflow-hidden flex-shrink-0 border border-slate-200">
                                    <img src="<?= htmlspecialchars($popImg); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                </div>
                                <div class="space-y-1">
                                    <h4 class="text-xs font-bold text-brand-darkNavy group-hover:text-cyan-600 transition-colors line-clamp-2 leading-snug">
                                        <?= htmlspecialchars($pop['judul']); ?>
                                    </h4>
                                    <span class="text-[10px] text-slate-400 block">
                                        <i class="fa-regular fa-calendar mr-1"></i><?= formatTanggalIndo($pop['tanggal_publikasi']); ?>
                                    </span>
                                </div>
                            </a>
                            <?php endforeach; else: ?>
                            <p class="text-xs text-slate-400 italic">Belum ada berita terpopuler.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Widget: Agenda Mendatang -->
                    <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 space-y-4 reveal-section delay-100">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-calendar-days text-cyan-600"></i>
                                <h3 class="text-sm font-bold text-brand-darkNavy uppercase tracking-wider">Agenda Terdekat</h3>
                            </div>
                            <a href="agenda.php" class="text-[10px] font-bold text-cyan-600 hover:underline">Lihat Semua</a>
                        </div>

                        <div class="space-y-3">
                            <?php if (!empty($agendaList)): foreach ($agendaList as $agd): ?>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-3">
                                <div class="w-12 h-12 rounded-lg bg-cyan-600 text-white flex flex-col items-center justify-center font-bold flex-shrink-0">
                                    <span class="text-[9px] uppercase"><?= date('M', strtotime($agd['tanggal_mulai'])); ?></span>
                                    <span class="text-sm leading-none"><?= date('d', strtotime($agd['tanggal_mulai'])); ?></span>
                                </div>
                                <div class="overflow-hidden">
                                    <h5 class="text-xs font-bold text-brand-darkNavy truncate"><?= htmlspecialchars($agd['judul_agenda']); ?></h5>
                                    <p class="text-[10px] text-slate-500 mt-0.5 truncate"><i class="fa-solid fa-location-dot mr-1 text-cyan-600"></i><?= htmlspecialchars($agd['lokasi'] ?: 'Lingkungan SMAN 21'); ?></p>
                                </div>
                            </div>
                            <?php endforeach; else: ?>
                            <p class="text-xs text-slate-400 italic text-center py-2">Belum ada agenda terjadwal.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Widget: Banner AI Assistant -->
                    <div class="p-6 rounded-2xl bg-gradient-to-br from-brand-darkNavy to-slate-900 text-white space-y-4 shadow-xl border border-slate-700 reveal-section delay-200 text-center">
                        <div class="w-12 h-12 rounded-full bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 flex items-center justify-center mx-auto text-xl">
                            <i class="fa-solid fa-robot"></i>
                        </div>
                        <h4 class="text-sm font-bold text-white">Butuh Informasi Cepat?</h4>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Tanyakan langsung seputar pengumuman PPDB, pendaftaran, ekstrakurikuler, atau kurikulum kepada S21 AI Assistant.
                        </p>
                        <button onclick="toggleAiPanel()" class="w-full py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow">
                            Tanya AI Assistant
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
                        <li><a href="../prestasi/index.php" class="hover:text-cyan-400 transition-colors">Galeri Prestasi Siswa</a></li>
                        <li><a href="../fasilitas/index.php" class="hover:text-cyan-400 transition-colors">Sarana & Fasilitas</a></li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Layanan Informasi</h3>
                    <ul class="space-y-2 text-[11px]">
                        <li><a href="ppdb.php" class="hover:text-cyan-400 transition-colors">Informasi SPMB / PPDB</a></li>
                        <li><a href="berita.php" class="text-cyan-400 font-bold">Berita & Pengumuman</a></li>
                        <li><a href="agenda.php" class="hover:text-cyan-400 transition-colors">Agenda Kalender Akademik</a></li>
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
                    <span class="text-[10px] text-cyan-400 block font-semibold">Context: Berita & Informasi Sekolah</span>
                </div>
            </div>
            <button onclick="toggleAiPanel()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="chatBox" class="flex-1 p-4 overflow-y-auto space-y-4 bg-slate-50 text-xs">
            <div class="p-3.5 rounded-xl bg-white border border-slate-200 text-slate-700 shadow-sm leading-relaxed space-y-2">
                <p class="font-bold text-brand-darkNavy">Halo! Saya S21 AI Assistant. 👋</p>
                <p>Kamu sedang berada di halaman <strong>Berita Sekolah</strong>. Ada pengumuman, berita prestasi, atau info PPDB yang ingin kamu ketahui?</p>
                <div class="pt-2 flex flex-wrap gap-1.5">
                    <button onclick="quickAsk('Apa berita PPDB terbaru?')" class="px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 text-[10px] font-semibold transition-colors">
                        📰 Info PPDB
                    </button>
                    <button onclick="quickAsk('Berita prestasi sekolah terbaru')" class="px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 text-[10px] font-semibold transition-colors">
                        🏆 Prestasi Siswa
                    </button>
                    <button onclick="quickAsk('Agenda kegiatan bulan ini')" class="px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 text-[10px] font-semibold transition-colors">
                        📅 Agenda Sekolah
                    </button>
                </div>
            </div>
        </div>

        <div class="p-3 bg-white border-t border-slate-200">
            <form onsubmit="submitAiChat(event)" class="flex gap-2">
                <input type="text" id="aiInput" placeholder="Cari berita atau tanyakan sesuatu..." class="flex-1 px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50">
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

        // 3. AI PANEL TOGGLE & CHAT AJAX
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
            loadingMsg.innerText = 'Mencari informasi berita...';
            chatBox.appendChild(loadingMsg);
            chatBox.scrollTop = chatBox.scrollHeight;

            fetch('berita.php?action=ai_query', {
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
                    linkHtml = `<div class="pt-1"><a href="${data.related_link}" class="text-cyan-600 font-bold hover:underline inline-flex items-center gap-1">Buka Artikel Berita <i class="fa-solid fa-arrow-right text-[9px]"></i></a></div>`;
                }

                aiMsg.innerHTML = `<div>${data.answer}</div><div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider pt-1 border-t border-slate-100">Sumber: ${data.source}</div>${linkHtml}`;
                chatBox.appendChild(aiMsg);
                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(() => {
                loadingMsg.innerText = 'Maaf, terjadi masalah koneksi ke peladen.';
            });
        }
    </script>
</body>
</html>
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
    $jam = date('H:i', $time);
    return "$d $m $y • $jam WIB";
}

// =============================================================================
// FETCH DETAIL BERITA UTAMA & INCREMENT VIEW COUNT
// =============================================================================
$slug = trim($_GET['slug'] ?? $_GET['id'] ?? '');
$berita = null;

if (!empty($slug) && $conn) {
    try {
        // Cari berdasarkan slug atau ID
        $stmtDetail = $conn->prepare("SELECT * FROM berita WHERE (slug = :slug OR id = :id) AND status = 'published' LIMIT 1");
        $stmtDetail->execute([':slug' => $slug, ':id' => $slug]);
        $berita = $stmtDetail->fetch();

        // Increment jumlah pembaca (views count)
        if ($berita) {
            $stmtUpdateView = $conn->prepare("UPDATE berita SET jumlah_dilihat = jumlah_dilihat + 1 WHERE id = :id");
            $stmtUpdateView->execute([':id' => $berita['id']]);
            $berita['jumlah_dilihat'] += 1; // Sync data lokal
        }
    } catch (PDOException $e) {}
}

// Fallback jika database belum ada data atau URL slug kosongan
if (!$berita) {
    $berita = [
        'id' => 0,
        'judul' => 'Pengumuman Pelaksanaan PPDB SMAN 21 Jakarta Tahun Ajaran 2026/2027',
        'slug' => 'pengumuman-ppdb-sman-21-jakarta-2026-2027',
        'ringkasan' => 'SMAN 21 Jakarta secara resmi membuka tahapan sosialisasi dan pendaftaran Penerimaan Peserta Didik Baru (PPDB) T.A. 2026/2027 secara online.',
        'isi' => '
            <p><strong>SMA NEGERI 21 JAKARTA</strong> — Dalam rangka menyambut Tahun Ajaran Baru 2026/2027, SMAN 21 Jakarta mengumumkan secara resmi dimulainya tahapan sosialisasi serta persiapan sistem Penerimaan Peserta Didik Baru (PPDB) terintegrasi.</p>
            <p>Sistem penerimaan calon peserta didik baru tahun ini berfokus pada transparansi, kesetaraan akses pendidikan, serta efisiensi berbasis teknologi digital modern.</p>
            <h3>Jalur Pendaftaran yang Tersedia:</h3>
            <ul>
                <li><strong>Jalur Prestasi Akademik & Non-Akademik:</strong> Diperuntukkan bagi calon siswa berprestasi di tingkat Kota, Provinsi, maupun Nasional.</li>
                <li><strong>Jalur Afirmasi:</strong> Memberikan kesempatan khusus bagi calon peserta didik dari keluarga kurang mampu secara ekonomi.</li>
                <li><strong>Jalur Zonasi:</strong> Memprioritaskan calon peserta didik berdasarkan jarak domisili terdekat dengan sekolah.</li>
                <li><strong>Jalur Perpindahan Tugas Orang Tua / Wali:</strong> Menampung calon peserta didik yang mengikuti domisili dinas orang tua.</li>
            </ul>
            <p>Seluruh proses verifikasi dokumen dapat diakses secara langsung melalui portal resmi SPMB SMAN 21 Jakarta atau menghubungi sekretariat panitia PPDB pada jam kerja layanan sekolah.</p>
        ',
        'thumbnail' => '../uploads/tempat/gedungA1.jpg',
        'gambar_utama' => '../uploads/tempat/gedungA1.jpg',
        'penulis' => 'Humas SMAN 21',
        'kategori' => 'PPDB',
        'tanggal_publikasi' => date('Y-m-d H:i:s'),
        'jumlah_dilihat' => 128
    ];
}

// =============================================================================
// ENDPOINT AJAX UNTUK S21 AI ASSISTANT (CONTEXT-AWARE BERITA DETAIL)
// =============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'ai_query') {
    header('Content-Type: application/json; charset=utf-8');
    $userQuery = trim($_POST['query'] ?? $_GET['q'] ?? '');
    
    if (empty($userQuery)) {
        echo json_encode(['status' => 'error', 'message' => 'Pertanyaan tidak boleh kosong.']);
        exit;
    }

    $responseAnswer = "";
    $sumberInfo = "Artikel Berita: " . $berita['judul'];
    $relatedLink = "";

    $qLower = strtolower($userQuery);

    // AI menjawab sesuai artikel yang sedang dibuka
    if (strpos($qLower, 'ringkas') !== false || strpos($qLower, 'rangkum') !== false || strpos($qLower, 'isi') !== false) {
        $responseAnswer = "Ringkasan Artikel: " . $berita['ringkasan'];
    } elseif (strpos($qLower, 'penulis') !== false || strpos($qLower, 'siapa') !== false) {
        $responseAnswer = "Artikel ini ditulis oleh " . $berita['penulis'] . " pada tanggal " . formatTanggalIndo($berita['tanggal_publikasi']) . ".";
    } elseif (strpos($qLower, 'kategori') !== false) {
        $responseAnswer = "Artikel ini masuk dalam kategori " . $berita['kategori'] . " SMAN 21 Jakarta.";
    } else {
        if ($conn) {
            $stmtKnw = $conn->prepare("SELECT * FROM ai_knowledge WHERE status_aktif = 1 ORDER BY prioritas DESC");
            $stmtKnw->execute();
            $knowledges = $stmtKnw->fetchAll();

            $matched = false;
            foreach ($knowledges as $k) {
                if (strpos($qLower, strtolower($k['pertanyaan'])) !== false) {
                    $responseAnswer = $k['jawaban'];
                    $sumberInfo = $k['sumber_informasi'] ?: "Database AI SMAN 21";
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $responseAnswer = "Mengenai berita \"" . $berita['judul'] . "\": " . $berita['ringkasan'] . " Silakan baca selengkapnya pada isi artikel di halaman ini.";
            }
        } else {
            $responseAnswer = "Mengenai berita ini: " . $berita['ringkasan'];
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
// FETCH DATA PENGATURAN SEKOLAH & SIDEBAR BERITA TERKAIT
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

// Fetch Berita Terkait & Terpopuler
$relatedBerita = [];
$popularBerita = [];
$agendaList = [];

if ($conn) {
    try {
        // Berita Terkait (Kategori sama, ID berbeda)
        $stmtRel = $conn->prepare("SELECT * FROM berita WHERE status='published' AND kategori = :kat AND id != :id ORDER BY id DESC LIMIT 3");
        $stmtRel->execute([':kat' => $berita['kategori'], ':id' => $berita['id']]);
        $relatedBerita = $stmtRel->fetchAll();

        // If empty related, fallback to latest news
        if (empty($relatedBerita)) {
            $stmtLatest = $conn->prepare("SELECT * FROM berita WHERE status='published' AND id != :id ORDER BY id DESC LIMIT 3");
            $stmtLatest->execute([':id' => $berita['id']]);
            $relatedBerita = $stmtLatest->fetchAll();
        }

        // Berita Populer
        $stmtPop = $conn->query("SELECT * FROM berita WHERE status='published' ORDER BY jumlah_dilihat DESC, id DESC LIMIT 4");
        if ($stmtPop) $popularBerita = $stmtPop->fetchAll();

        // Agenda Terdekat
        $stmtAgd = $conn->query("SELECT * FROM agenda WHERE status='published' ORDER BY tanggal_mulai ASC LIMIT 3");
        if ($stmtAgd) $agendaList = $stmtAgd->fetchAll();

    } catch (PDOException $e) {}
}

$mainImg = formatBeritaImg($berita['gambar_utama'] ?: $berita['thumbnail']);
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($berita['judul']); ?> — SMAN 21 JAKARTA</title>
    <meta name="description" content="<?= htmlspecialchars(mb_strimwidth($berita['ringkasan'] ?: strip_tags($berita['isi']), 0, 160, '...')); ?>">
    
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

        /* Styling Format HTML Isi Berita dari Admin / TinyMCE */
        .article-body {
            color: #334155;
            font-size: 0.95rem;
            line-height: 1.85;
        }
        .article-body p {
            margin-bottom: 1.25rem;
        }
        .article-body h2 {
            font-size: 1.35rem;
            font-weight: 800;
            color: #061224;
            margin-top: 1.75rem;
            margin-bottom: 0.75rem;
        }
        .article-body h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0B1E36;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .article-body strong, .article-body b {
            font-weight: 700;
            color: #061224;
        }
        .article-body ul {
            list-style-type: disc;
            padding-left: 1.5rem;
            margin-bottom: 1.25rem;
        }
        .article-body ol {
            list-style-type: decimal;
            padding-left: 1.5rem;
            margin-bottom: 1.25rem;
        }
        .article-body li {
            margin-bottom: 0.4rem;
        }
        .article-body blockquote {
            border-left: 4px solid #00A8CC;
            padding-left: 1rem;
            font-style: italic;
            color: #475569;
            background: #F1F5F9;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            border-radius: 0 8px 8px 0;
            margin-bottom: 1.25rem;
        }
        .article-body img {
            max-width: 100%;
            height: auto;
            border-radius: 0.75rem;
            margin: 1.25rem 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
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
                                <a href="berita.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 font-bold transition-colors">Berita Sekolah</a>
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
    <!-- 🏙️ BREADCRUMB & ARTICLE HEADER                                        -->
    <!-- ===================================================================== -->
    <section class="bg-brand-darkNavy text-white py-12 sm:py-16 relative overflow-hidden">
        <div class="absolute inset-0 opacity-15 bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($mainImg); ?>');"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-brand-darkNavy via-brand-navy/95 to-brand-darkNavy"></div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 space-y-4">
            
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs text-slate-400 flex-wrap">
                <a href="../index.php" class="hover:text-cyan-400 transition-colors">Beranda</a>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-600"></i>
                <a href="berita.php" class="hover:text-cyan-400 transition-colors">Informasi</a>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-600"></i>
                <a href="berita.php" class="hover:text-cyan-400 transition-colors">Berita</a>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-600"></i>
                <span class="text-cyan-400 font-bold truncate max-w-[180px] sm:max-w-xs"><?= htmlspecialchars($berita['judul']); ?></span>
            </nav>

            <div class="space-y-3 reveal-section is-visible">
                <span class="inline-block px-3 py-1 rounded bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 text-[11px] font-bold uppercase tracking-wider">
                    <?= htmlspecialchars($berita['kategori'] ?? 'Umum'); ?>
                </span>

                <h1 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white leading-snug sm:leading-tight">
                    <?= htmlspecialchars($berita['judul']); ?>
                </h1>

                <!-- Article Meta Info -->
                <div class="flex flex-wrap items-center gap-4 sm:gap-6 pt-2 text-xs text-slate-300 border-t border-slate-800/80">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 flex items-center justify-center font-bold text-xs">
                            <i class="fa-solid fa-user-pen text-[10px]"></i>
                        </div>
                        <span>Oleh: <strong><?= htmlspecialchars($berita['penulis'] ?: 'Admin SMAN 21'); ?></strong></span>
                    </div>

                    <div class="flex items-center gap-1.5 text-slate-400">
                        <i class="fa-regular fa-calendar text-cyan-400"></i>
                        <span><?= formatTanggalIndo($berita['tanggal_publikasi']); ?></span>
                    </div>

                    <div class="flex items-center gap-1.5 text-slate-400">
                        <i class="fa-regular fa-eye text-cyan-400"></i>
                        <span><?= (int)$berita['jumlah_dilihat']; ?> kali dibaca</span>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📖 MAIN ARTICLE CONTENT & SIDEBAR                                     -->
    <!-- ===================================================================== -->
    <section class="py-14 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-10">
                
                <!-- ARTICLE CONTENT (8 COLS) -->
                <main class="lg:col-span-8 space-y-8">
                    
                    <div class="school-card p-6 sm:p-10 rounded-2xl bg-white border border-slate-200 space-y-8 reveal-section">
                        
                        <!-- Featured Main Image -->
                        <div class="relative rounded-xl overflow-hidden bg-slate-100 shadow-sm border border-slate-200 aspect-video">
                            <img src="<?= htmlspecialchars($mainImg); ?>" alt="<?= htmlspecialchars($berita['judul']); ?>" class="w-full h-full object-cover">
                        </div>

                        <!-- Ringkasan / Lead Paragraph -->
                        <?php if (!empty($berita['ringkasan'])): ?>
                        <div class="p-4 sm:p-5 rounded-xl bg-cyan-50/70 border-l-4 border-cyan-600 text-slate-700 text-sm font-medium leading-relaxed italic">
                            "<?= htmlspecialchars($berita['ringkasan']); ?>"
                        </div>
                        <?php endif; ?>

                        <!-- Full Content Text (Render HTML Admin) -->
                        <div class="article-body font-normal">
                            <?= $berita['isi']; ?>
                        </div>

                        <!-- Share Buttons Section -->
                        <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <span class="text-xs font-bold text-brand-darkNavy uppercase tracking-wider flex items-center gap-2">
                                <i class="fa-solid fa-share-nodes text-cyan-600"></i> Bagikan Artikel Ini:
                            </span>

                            <div class="flex items-center gap-2">
                                <a href="https://api.whatsapp.com/send?text=<?= urlencode($berita['judul'] . " - " . $currentUrl); ?>" target="_blank" class="px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-2 shadow transition-all">
                                    <i class="fa-brands fa-whatsapp text-sm"></i> WhatsApp
                                </a>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($currentUrl); ?>" target="_blank" class="px-3.5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-2 shadow transition-all">
                                    <i class="fa-brands fa-facebook text-sm"></i> Facebook
                                </a>
                                <button onclick="copyArticleLink()" class="px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-2 border border-slate-200 transition-all">
                                    <i class="fa-regular fa-copy text-sm"></i> Salin Tautan
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- Navigation to Back to All News -->
                    <div class="flex items-center justify-between pt-2">
                        <a href="berita.php" class="px-5 py-2.5 rounded-xl bg-white border border-slate-300 hover:border-cyan-600 hover:text-cyan-600 text-slate-700 text-xs font-bold transition-all shadow-sm flex items-center gap-2">
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>Kembali ke Daftar Berita</span>
                        </a>

                        <button onclick="toggleAiPanel()" class="px-5 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold shadow transition-all flex items-center gap-2">
                            <i class="fa-solid fa-circle-question"></i>
                            <span>Tanyakan Berita Ini ke AI</span>
                        </button>
                    </div>

                </main>

                <!-- SIDEBAR WIDGETS (4 COLS) -->
                <aside class="lg:col-span-4 space-y-8">
                    
                    <!-- Widget: Profil Penulis / Informasi Sekolah -->
                    <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 space-y-4 reveal-section">
                        <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                            <img src="<?= $logoPath; ?>" alt="Logo SMAN 21" class="w-10 h-10 object-contain">
                            <div>
                                <h3 class="text-xs font-bold text-brand-darkNavy uppercase">SMAN 21 Jakarta</h3>
                                <span class="text-[10px] text-cyan-600 font-semibold block">Pusat Informasi Resmi</span>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Dipublikasikan oleh Humas SMAN 21 Jakarta sebagai bentuk keterbukaan informasi publik dan akuntabilitas pendidikan sekolah.
                        </p>
                    </div>

                    <!-- Widget: Berita Terkait / Lainnya -->
                    <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 space-y-4 reveal-section delay-100">
                        <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                            <i class="fa-solid fa-newspaper text-cyan-600"></i>
                            <h3 class="text-sm font-bold text-brand-darkNavy uppercase tracking-wider">Berita Terkait</h3>
                        </div>

                        <div class="space-y-4">
                            <?php if (!empty($relatedBerita)): foreach ($relatedBerita as $rel): 
                                $relImg = formatBeritaImg($rel['thumbnail'] ?: $rel['gambar_utama']);
                            ?>
                            <a href="berita-detail.php?slug=<?= htmlspecialchars($rel['slug']); ?>" class="flex items-center gap-3 group block">
                                <div class="w-16 h-16 rounded-lg bg-slate-100 overflow-hidden flex-shrink-0 border border-slate-200">
                                    <img src="<?= htmlspecialchars($relImg); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[9px] text-cyan-600 font-bold uppercase tracking-wider block"><?= htmlspecialchars($rel['kategori'] ?? 'Umum'); ?></span>
                                    <h4 class="text-xs font-bold text-brand-darkNavy group-hover:text-cyan-600 transition-colors line-clamp-2 leading-snug">
                                        <?= htmlspecialchars($rel['judul']); ?>
                                    </h4>
                                </div>
                            </a>
                            <?php endforeach; else: ?>
                            <p class="text-xs text-slate-400 italic">Belum ada berita terkait lainnya.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Widget: Berita Terpopuler -->
                    <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 space-y-4 reveal-section delay-200">
                        <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                            <i class="fa-solid fa-fire text-cyan-600"></i>
                            <h3 class="text-sm font-bold text-brand-darkNavy uppercase tracking-wider">Paling Sering Dibaca</h3>
                        </div>

                        <div class="space-y-3">
                            <?php if (!empty($popularBerita)): foreach ($popularBerita as $popIndex => $pop): ?>
                            <a href="berita-detail.php?slug=<?= htmlspecialchars($pop['slug']); ?>" class="flex items-start gap-3 group">
                                <span class="w-6 h-6 rounded bg-slate-100 text-slate-600 font-extrabold text-xs flex items-center justify-center flex-shrink-0 group-hover:bg-cyan-600 group-hover:text-white transition-colors">
                                    <?= $popIndex + 1; ?>
                                </span>
                                <div class="space-y-0.5">
                                    <h4 class="text-xs font-bold text-brand-darkNavy group-hover:text-cyan-600 transition-colors line-clamp-2 leading-snug">
                                        <?= htmlspecialchars($pop['judul']); ?>
                                    </h4>
                                    <span class="text-[10px] text-slate-400 block"><?= (int)$pop['jumlah_dilihat']; ?> views</span>
                                </div>
                            </a>
                            <?php endforeach; else: ?>
                            <p class="text-xs text-slate-400 italic">Belum ada daftar berita populer.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Widget: Upcoming Agenda -->
                    <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 space-y-4 reveal-section delay-300">
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

                </aside>

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
                    <span class="text-[10px] text-cyan-400 block font-semibold">Context: Berita Detail</span>
                </div>
            </div>
            <button onclick="toggleAiPanel()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="chatBox" class="flex-1 p-4 overflow-y-auto space-y-4 bg-slate-50 text-xs">
            <div class="p-3.5 rounded-xl bg-white border border-slate-200 text-slate-700 shadow-sm leading-relaxed space-y-2">
                <p class="font-bold text-brand-darkNavy">Halo! Saya S21 AI Assistant. 👋</p>
                <p>Kamu sedang membaca berita: <strong>"<?= htmlspecialchars($berita['judul']); ?>"</strong>.</p>
                <p>Ada hal spesifik yang ingin kamu tanyakan mengenai isi artikel berita ini?</p>
                <div class="pt-2 flex flex-wrap gap-1.5">
                    <button onclick="quickAsk('Rangkum isi berita ini')" class="px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 text-[10px] font-semibold transition-colors">
                        📝 Rangkum Artikel
                    </button>
                    <button onclick="quickAsk('Siapa penulis artikel ini?')" class="px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 text-[10px] font-semibold transition-colors">
                        👤 Info Penulis
                    </button>
                    <button onclick="quickAsk('Info PPDB terbaru')" class="px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 text-[10px] font-semibold transition-colors">
                        🎓 Tanya PPDB
                    </button>
                </div>
            </div>
        </div>

        <div class="p-3 bg-white border-t border-slate-200">
            <form onsubmit="submitAiChat(event)" class="flex gap-2">
                <input type="text" id="aiInput" placeholder="Tanyakan seputar artikel ini..." class="flex-1 px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50">
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

        // 3. COPY LINK TO CLIPBOARD
        function copyArticleLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('Tautan artikel berhasil disalin ke papan klip!');
            }).catch(() => {
                alert('Gagal menyalin tautan.');
            });
        }

        // 4. AI PANEL TOGGLE & CHAT AJAX
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
            loadingMsg.innerText = 'Analisis artikel oleh AI...';
            chatBox.appendChild(loadingMsg);
            chatBox.scrollTop = chatBox.scrollHeight;

            fetch('berita-detail.php?slug=<?= urlencode($berita['slug']); ?>&action=ai_query', {
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
                    linkHtml = `<div class="pt-1"><a href="${data.related_link}" class="text-cyan-600 font-bold hover:underline inline-flex items-center gap-1">Pelajari Lebih Lanjut <i class="fa-solid fa-arrow-right text-[9px]"></i></a></div>`;
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
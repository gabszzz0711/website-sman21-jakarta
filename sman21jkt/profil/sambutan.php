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
// ENDPOINT AJAX UNTUK S21 AI ASSISTANT (CONTEXT-AWARE: SAMBUTAN KEPALA SEKOLAH)
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
    $sumberInfo = "Database Informasi Sambutan Kepala SMAN 21";
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
            if (strpos($qLower, 'kepala sekolah') !== false || strpos($qLower, 'kepsek') !== false || strpos($qLower, 'pimpinan') !== false) {
                $stmtSam = $conn->query("SELECT * FROM sambutan_kepala_sekolah WHERE status_aktif=1 LIMIT 1");
                $samData = $stmtSam ? $stmtSam->fetch() : null;
                $namaKepsek = $samData['nama_kepala_sekolah'] ?? "Mutia, M.Pd";
                $jabatanKepsek = $samData['jabatan'] ?? "Kepala Sekolah SMAN 21 Jakarta";
                $responseAnswer = "Kepala SMAN 21 Jakarta saat ini adalah " . htmlspecialchars($namaKepsek) . " (" . htmlspecialchars($jabatanKepsek) . "). Beliau berkomitmen memajukan mutu pendidikan berbasis karakter dan teknologi.";
                $sumberInfo = "Profil Pimpinan Sekolah";
                $matched = true;
            } elseif (strpos($qLower, 'sambutan') !== false || strpos($qLower, 'pesan') !== false) {
                $stmtSam = $conn->query("SELECT judul, isi_sambutan FROM sambutan_kepala_sekolah WHERE status_aktif=1 LIMIT 1");
                $samData = $stmtSam ? $stmtSam->fetch() : null;
                $isiPlain = strip_tags($samData['isi_sambutan'] ?? '');
                $responseAnswer = "Ringkasan Sambutan: \"" . (mb_substr($isiPlain, 0, 220) ?: "Selamat datang di website resmi SMAN 21 Jakarta. Kami siap mencetak generasi berkarakter Pancasila dan berteknologi.") . "...\"";
                $sumberInfo = "Sambutan Kepala Sekolah";
                $matched = true;
            }
        }

        if (!$matched) {
            $responseAnswer = $fallbackMsg;
            $sumberInfo = "Sistem Verifikasi SMAN 21";
        }

    } else {
        if (strpos(strtolower($userQuery), 'kepala sekolah') !== false) {
            $responseAnswer = "Kepala SMAN 21 Jakarta adalah Mutia, M.Pd. Beliau memimpin SMAN 21 Jakarta menuju sekolah unggulan berkarakter Pancasila dan berteknologi modern.";
            $sumberInfo = "Profil SMAN 21";
        } else {
            $responseAnswer = "Maaf, informasi tersebut belum tersedia dalam basis data resmi SMAN 21 Jakarta. Silakan tanyakan seputar Profil, Kepala Sekolah, Visi Misi, atau Program Sekolah.";
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
// FETCH DATA RESMI SEKOLAH & SAMBUTAN DARI DATABASE
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
    'banner_bg' => '../uploads/tempat/gedungA.jpg'
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

// 2. Fetch Data Sambutan Kepala Sekolah
$sambutan = [
    'nama_kepala_sekolah' => 'Mutia, M.Pd',
    'jabatan' => 'Kepala Sekolah SMAN 21 Jakarta',
    'judul' => 'Sambutan Kepala SMAN 21 Jakarta',
    'isi_sambutan' => '<p><strong>Assalamu’alaikum Warahmatullahi Wabarakatuh,</strong><br>Salam sejahtera bagi kita semua.</p><p>Selamat datang di situs web resmi SMA Negeri 21 Jakarta. Puji dan syukur kita panjatkan ke hadirat Tuhan Yang Maha Esa atas rahmat dan karunia-Nya sehingga website portal informasi resmi ini dapat terus dikembangkan sebagai wujud transparansi, komunikasi, dan pelayanan publik secara digital.</p><p>Di era transformasi teknologi yang bergerak sangat cepat, SMAN 21 Jakarta berkomitmen penuh menghadirkan ekosistem pendidikan menengah yang tidak hanya berorientasi pada capaian akademik unggul, tetapi juga membentuk integritas karakter Profil Pelajar Pancasila, kepekaan lingkungan (Adiwiyata), serta kesiapan menghadapi tantangan global berbasis penguasaan sains dan kecerdasan artifisial.</p><p>Kami mempercayai bahwa setiap peserta didik memiliki keunikan dan potensi emas. Melalui kolaborasi yang solid antara pendidik profesional, staf kependidikan, komite sekolah, alumni, serta orang tua murid, SMAN 21 Jakarta terus berinovasi mewujudkan ruang belajar yang aman, ramah anak, inklusif, dan menginspirasi.</p><p>Semoga platform digital ini dapat menjadi jembatan informasi yang bermanfaat bagi seluruh warga sekolah, calon peserta didik baru, alumni, maupun masyarakat luas.</p><p><strong>Wassalamu’alaikum Warahmatullahi Wabarakatuh.</strong></p>',
    'foto' => '../uploads/sambutan/kepsek.jpg',
    'tanggal_update' => date('Y-m-d')
];

if ($conn) {
    try {
        $stmtSam = $conn->query("SELECT * FROM sambutan_kepala_sekolah WHERE status_aktif=1 LIMIT 1");
        if ($stmtSam && $rowSam = $stmtSam->fetch()) {
            if (!empty($rowSam['nama_kepala_sekolah'])) $sambutan['nama_kepala_sekolah'] = $rowSam['nama_kepala_sekolah'];
            if (!empty($rowSam['jabatan'])) $sambutan['jabatan'] = $rowSam['jabatan'];
            if (!empty($rowSam['judul'])) $sambutan['judul'] = $rowSam['judul'];
            if (!empty($rowSam['isi_sambutan'])) $sambutan['isi_sambutan'] = $rowSam['isi_sambutan'];
            if (!empty($rowSam['tanggal_update'])) $sambutan['tanggal_update'] = $rowSam['tanggal_update'];
            
            if (!empty($rowSam['foto'])) {
                $fotoVal = trim($rowSam['foto']);
                if (strpos($fotoVal, 'http://') === 0 || strpos($fotoVal, 'https://') === 0) {
                    $sambutan['foto'] = $fotoVal;
                } elseif (strpos($fotoVal, 'uploads/') === 0) {
                    $sambutan['foto'] = '../' . $fotoVal;
                } else {
                    $sambutan['foto'] = '../uploads/sambutan/' . ltrim($fotoVal, '/');
                }
            }
        }
    } catch (PDOException $e) {
        // Fallback default
    }
}

$logoPath = $sekolah['logo'];
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sambutan Kepala Sekolah — SMA NEGERI 21 JAKARTA</title>
    <meta name="description" content="Sambutan Resmi Kepala SMA Negeri 21 Jakarta. Komitmen pendidikan berkualitas, karakter Pancasila, dan inovasi teknologi modern.">
    
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
                            amber: '#D97706',
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

        /* Smooth Reveal Animation Observer */
        .reveal-section {
            opacity: 0;
            transform: translateY(24px) scale(0.99);
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
            box-shadow: 0 16px 32px -6px rgba(15, 23, 42, 0.08);
            border-color: #CBD5E1;
        }

        .hero-overlay {
            background: linear-gradient(135deg, rgba(6, 18, 36, 0.94) 0%, rgba(11, 30, 54, 0.88) 60%, rgba(15, 23, 42, 0.92) 100%);
        }

        /* 📝 STYLING TINYMCE HTML RESPONSIVE */
        .sambutan-content {
            color: #334155;
            font-size: 0.95rem;
            line-height: 1.85;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        @media (min-width: 640px) {
            .sambutan-content { font-size: 1.02rem; line-height: 1.9; }
        }
        .sambutan-content p {
            margin-bottom: 1.25rem;
        }
        .sambutan-content p:last-child {
            margin-bottom: 0;
        }
        .sambutan-content strong, .sambutan-content b {
            font-weight: 700;
            color: #061224;
        }
        .sambutan-content em, .sambutan-content i {
            font-style: italic;
        }
        .sambutan-content ul {
            list-style-type: disc;
            padding-left: 1.25rem;
            margin-bottom: 1.25rem;
        }
        .sambutan-content ol {
            list-style-type: decimal;
            padding-left: 1.25rem;
            margin-bottom: 1.25rem;
        }
        .sambutan-content blockquote {
            border-left: 4px solid #00A8CC;
            padding: 0.85rem 1rem;
            margin: 1.5rem 0;
            font-style: italic;
            color: #0F172A;
            background: #F0F9FF;
            border-top-right-radius: 0.75rem;
            border-bottom-right-radius: 0.75rem;
        }
        .sambutan-content img {
            max-width: 100% !important;
            height: auto !important;
            border-radius: 0.75rem;
            margin: 1rem 0;
        }
        .sambutan-content table {
            display: block;
            width: 100% !important;
            overflow-x: auto;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #061224; }
        ::-webkit-scrollbar-thumb { background: #00A8CC; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #0086A3; }
    </style>
</head>
<body class="relative min-h-screen text-slate-800 bg-slate-50 selection:bg-cyan-500 selection:text-white max-w-full overflow-x-hidden">

    <!-- ===================================================================== -->
    <!-- 🧭 TOP BAR (DESKTOP & TABLET)                                         -->
    <!-- ===================================================================== -->
    <div class="bg-brand-darkNavy text-slate-300 text-xs py-2 border-b border-slate-800 hidden md:block">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <div class="flex items-center gap-4 lg:gap-6 text-[11px] lg:text-xs">
                <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-location-dot text-cyan-400"></i>Pulogadung, Jakarta Timur</span>
                <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-phone text-cyan-400"></i><?= htmlspecialchars($sekolah['telepon']); ?></span>
                <span class="hidden lg:inline-flex items-center gap-1.5"><i class="fa-solid fa-envelope text-cyan-400"></i><?= htmlspecialchars($sekolah['email']); ?></span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[10px] uppercase font-bold text-cyan-400 bg-cyan-500/10 px-2.5 py-0.5 rounded border border-cyan-500/20">Akreditasi A</span>
                <a href="<?= htmlspecialchars($sekolah['instagram']); ?>" target="_blank" aria-label="Instagram" class="hover:text-cyan-400 transition-colors p-1"><i class="fa-brands fa-instagram"></i></a>
                <a href="<?= htmlspecialchars($sekolah['youtube']); ?>" target="_blank" aria-label="YouTube" class="hover:text-cyan-400 transition-colors p-1"><i class="fa-brands fa-youtube"></i></a>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- 🏛️ MAIN NAVIGATION HEADER                                            -->
    <!-- ===================================================================== -->
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                
                <!-- Brand Logo & Title -->
                <a href="../index.php" class="flex items-center gap-3 group">
                    <img src="<?= $logoPath; ?>" alt="SMAN 21 Jakarta Logo" class="h-9 w-9 sm:h-11 sm:w-11 object-contain group-hover:scale-105 transition-transform">
                    <div>
                        <span class="text-sm sm:text-base lg:text-lg font-bold text-brand-darkNavy block leading-tight group-hover:text-cyan-600 transition-colors">
                            SMAN 21 JAKARTA
                        </span>
                        <span class="text-[9px] sm:text-[10px] tracking-wider text-slate-500 block uppercase font-semibold">
                            Official School Portal
                        </span>
                    </div>
                </a>

                <!-- Desktop Navigation Menu -->
                <nav class="hidden lg:flex items-center gap-1 font-semibold text-xs text-slate-700">
                    <a href="../index.php" class="px-3 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 transition-all">Beranda</a>

                    <!-- Dropdown Profil -->
                    <div class="relative group">
                        <button class="px-3 py-2 rounded-lg text-cyan-600 bg-cyan-50 font-bold border border-cyan-200 flex items-center gap-1.5 transition-all">
                            <span>Profil</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full left-0 w-56 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Tentang Sekolah</a>
                                <a href="sejarah.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Sejarah Sekolah</a>
                                <a href="visi-misi.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Visi & Misi</a>
                                <a href="sambutan.php" class="block px-3 py-2 rounded-lg text-cyan-600 font-bold bg-cyan-50/60 transition-colors">Sambutan Kepala Sekolah</a>
                            </div>
                        </div>
                    </div>

                    <!-- Dropdown Akademik -->
                    <div class="relative group">
                        <button class="px-3 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 flex items-center gap-1.5 transition-all">
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

                    <!-- Dropdown Kegiatan -->
                    <div class="relative group">
                        <button class="px-3 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 flex items-center gap-1.5 transition-all">
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

                    <a href="../prestasi/index.php" class="px-3 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 transition-all">Prestasi</a>
                    <a href="../fasilitas/index.php" class="px-3 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 transition-all">Fasilitas</a>

                    <!-- Dropdown Informasi -->
                    <div class="relative group">
                        <button class="px-3 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 flex items-center gap-1.5 transition-all">
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

                    <a href="../informasi/ppdb.php" class="ml-2 px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs flex items-center gap-2 shadow-sm transition-all">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>SPMB Online</span>
                    </a>
                </nav>

                <!-- Mobile & Tablet Menu Toggle Button -->
                <button onclick="toggleMobileMenu()" aria-label="Buka Menu" class="lg:hidden p-2.5 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200 transition-colors focus:outline-none">
                    <i id="mobileMenuIcon" class="fa-solid fa-bars text-lg block"></i>
                </button>
            </div>
        </div>

        <!-- Mobile & Tablet Navigation Overlay & Drawer -->
        <div id="mobileMenuBackdrop" onclick="toggleMobileMenu()" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[45] hidden lg:hidden transition-opacity"></div>
        <div id="mobileMenu" class="hidden lg:hidden bg-white border-t border-slate-200 px-5 py-6 space-y-4 max-h-[82vh] overflow-y-auto relative z-[50] shadow-2xl">
            <a href="../index.php" class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 text-slate-800 font-bold text-xs">
                <span><i class="fa-solid fa-house text-cyan-600 mr-2"></i>Beranda</span>
                <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
            </a>

            <!-- Mobile Accordion: Profil -->
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <button onclick="toggleAccordion('accProfil', 'iconProfil')" class="w-full flex items-center justify-between p-3 bg-cyan-50/50 text-cyan-700 font-bold text-xs text-left">
                    <span><i class="fa-solid fa-user-tie text-cyan-600 mr-2"></i>Profil Sekolah</span>
                    <i id="iconProfil" class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"></i>
                </button>
                <div id="accProfil" class="hidden px-3 py-2 space-y-1 bg-white border-t border-slate-100 text-xs">
                    <a href="index.php" class="block py-2 px-2 rounded hover:bg-slate-50 text-slate-700">Overview Profil</a>
                    <a href="sejarah.php" class="block py-2 px-2 rounded hover:bg-slate-50 text-slate-700">Sejarah Sekolah</a>
                    <a href="visi-misi.php" class="block py-2 px-2 rounded hover:bg-slate-50 text-slate-700">Visi & Misi</a>
                    <a href="sambutan.php" class="block py-2 px-2 rounded bg-cyan-50 font-bold text-cyan-600">Sambutan Kepala Sekolah</a>
                </div>
            </div>

            <!-- Mobile Accordion: Akademik -->
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <button onclick="toggleAccordion('accAkademik', 'iconAkademik')" class="w-full flex items-center justify-between p-3 bg-slate-50 text-slate-700 font-semibold text-xs text-left">
                    <span><i class="fa-solid fa-book-open text-cyan-600 mr-2"></i>Akademik</span>
                    <i id="iconAkademik" class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"></i>
                </button>
                <div id="accAkademik" class="hidden px-3 py-2 space-y-1 bg-white border-t border-slate-100 text-xs">
                    <a href="../akademik/index.php" class="block py-2 px-2 rounded hover:bg-slate-50 text-slate-700">Overview Akademik</a>
                    <a href="../akademik/program.php" class="block py-2 px-2 rounded hover:bg-slate-50 text-slate-700">Program Pendidikan</a>
                    <a href="../akademik/guru.php" class="block py-2 px-2 rounded hover:bg-slate-50 text-slate-700">Daftar Guru & Staf</a>
                    <a href="../akademik/pemeringkatan.php" class="block py-2 px-2 rounded hover:bg-slate-50 text-slate-700">Pemeringkatan Jurusan</a>
                    <a href="../akademik/keterserapan-ptn.php" class="block py-2 px-2 rounded hover:bg-slate-50 text-slate-700">Keterserapan PTN</a>
                </div>
            </div>

            <!-- Mobile Accordion: Kegiatan & Informasi -->
            <div class="grid grid-cols-2 gap-2 pt-1">
                <a href="../prestasi/index.php" class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold text-xs text-center block">Prestasi</a>
                <a href="../fasilitas/index.php" class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold text-xs text-center block">Fasilitas</a>
            </div>

            <a href="../informasi/ppdb.php" class="block w-full py-3 rounded-xl bg-cyan-600 text-white font-bold text-xs text-center shadow-md">
                <i class="fa-solid fa-graduation-cap mr-2"></i>SPMB / PPDB Online
            </a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 🏛️ HERO BANNER SAMBUTAN (RESPONSIVE BANNER)                          -->
    <!-- ===================================================================== -->
    <section class="relative bg-brand-darkNavy text-white py-14 sm:py-20 lg:py-24 overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-1000 scale-105" 
             style="background-image: url('<?= htmlspecialchars($sekolah['banner_bg']); ?>');">
        </div>
        <div class="absolute inset-0 hero-overlay"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl space-y-3 sm:space-y-4 reveal-section">
                <!-- Breadcrumb Navigation -->
                <div class="flex flex-wrap items-center gap-2 text-[10px] sm:text-xs text-cyan-400 font-semibold tracking-wide uppercase">
                    <a href="../index.php" class="hover:underline">BERANDA</a>
                    <i class="fa-solid fa-chevron-right text-[8px]"></i>
                    <a href="index.php" class="hover:underline">PROFIL SEKOLAH</a>
                    <i class="fa-solid fa-chevron-right text-[8px]"></i>
                    <span class="text-white">SAMBUTAN KEPALA SEKOLAH</span>
                </div>

                <h1 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight leading-tight">
                    Sambutan <span class="text-cyan-400">Kepala Sekolah</span>
                </h1>
                
                <p class="text-slate-200 text-xs sm:text-sm lg:text-base leading-relaxed max-w-2xl">
                    Pesan, arahan, dan komitmen kepemimpinan SMA Negeri 21 Jakarta dalam menghadirkan pendidikan unggul, berkarakter Pancasila, dan berwawasan teknologi.
                </p>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🏫 UTAMA: FOTO KEPSEK & ISI SAMBUTAN LENGKAP                         -->
    <!-- ===================================================================== -->
    <section class="py-10 sm:py-16 lg:py-20 bg-white border-b border-slate-200 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
                
                <!-- Sidebar / Card Kepsek (Left Column - Fix Sticky on Mobile) -->
                <div class="lg:col-span-5 reveal-section lg:sticky lg:top-28">
                    <div class="relative max-w-md mx-auto lg:max-w-none">
                        <div class="absolute -inset-2 rounded-3xl bg-gradient-to-r from-cyan-500/30 to-brand-navy/30 opacity-70 blur-xl -z-10"></div>
                        
                        <div class="school-card p-5 sm:p-7 rounded-2xl relative z-10 bg-white border border-slate-200/90 shadow-xl">
                            
                            <!-- Wrapper Foto Kepsek -->
                            <div class="relative w-full aspect-[3/4] sm:aspect-[4/5] max-h-[380px] sm:max-h-none rounded-xl overflow-hidden mb-5 bg-gradient-to-br from-slate-100 to-cyan-50 border border-slate-200 p-1.5 flex items-center justify-center shadow-inner">
                                <img src="<?= htmlspecialchars($sambutan['foto']); ?>" 
                                     alt="Foto Kepala Sekolah" 
                                     class="w-full h-full object-cover rounded-lg drop-shadow-md hover:scale-105 transition-transform duration-500"
                                     onerror="this.onerror=null; this.src='<?= $logoPath; ?>'; this.className='max-h-full max-w-full object-contain p-4';">
                                
                                <div class="absolute bottom-3 left-3 right-3 bg-brand-darkNavy/80 backdrop-blur-md px-3 py-1.5 rounded-lg border border-white/10 text-white text-[10px] sm:text-xs flex items-center justify-between">
                                    <span class="font-semibold text-cyan-300"><i class="fa-solid fa-shield-halved mr-1"></i>Kepemimpinan Resmi</span>
                                    <span class="text-slate-300">SMAN 21</span>
                                </div>
                            </div>

                            <div class="text-center sm:text-left">
                                <h2 class="text-lg sm:text-xl font-extrabold text-brand-darkNavy leading-snug">
                                    <?= htmlspecialchars($sambutan['nama_kepala_sekolah']); ?>
                                </h2>
                                <p class="text-xs font-bold text-cyan-600 uppercase tracking-wider mt-1">
                                    <?= htmlspecialchars($sambutan['jabatan']); ?>
                                </p>
                            </div>

                            <div class="my-5 border-t border-slate-100"></div>

                            <!-- Kutipan Singkat -->
                            <div class="p-3.5 sm:p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                                <span class="text-cyan-600 text-base block"><i class="fa-solid fa-quote-left"></i></span>
                                <p class="text-xs text-slate-600 italic leading-relaxed">
                                    "Membentuk generasi unggul berprestasi, berakar pada nilai Pancasila, serta siap menjadi pemimpin berwawasan masa depan."
                                </p>
                            </div>

                            <!-- Navigasi Cepat Profil -->
                            <div class="pt-5 space-y-2">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2">Halaman Terkait</span>
                                <a href="index.php" class="w-full py-2.5 px-3.5 rounded-lg bg-slate-100 hover:bg-cyan-50 hover:text-cyan-700 text-slate-700 text-xs font-semibold flex items-center justify-between transition-colors">
                                    <span>Tentang Sekolah</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </a>
                                <a href="visi-misi.php" class="w-full py-2.5 px-3.5 rounded-lg bg-slate-100 hover:bg-cyan-50 hover:text-cyan-700 text-slate-700 text-xs font-semibold flex items-center justify-between transition-colors">
                                    <span>Visi & Misi Sekolah</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </a>
                                <a href="sejarah.php" class="w-full py-2.5 px-3.5 rounded-lg bg-slate-100 hover:bg-cyan-50 hover:text-cyan-700 text-slate-700 text-xs font-semibold flex items-center justify-between transition-colors">
                                    <span>Sejarah SMAN 21</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </a>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Isi Sambutan Lengkap (Right Column) -->
                <div class="lg:col-span-7 space-y-6 sm:space-y-8 reveal-section delay-200">
                    
                    <div class="space-y-2.5 border-b border-slate-200 pb-5">
                        <div class="inline-flex items-center gap-2 text-cyan-800 text-[11px] font-bold uppercase tracking-widest bg-cyan-100/90 px-3 py-1 rounded-md border border-cyan-300">
                            <i class="fa-solid fa-scroll text-cyan-600"></i>
                            <span>PESAN RESMI PIMPINAN</span>
                        </div>

                        <h2 class="text-xl sm:text-3xl lg:text-4xl font-extrabold text-brand-darkNavy leading-tight">
                            <?= htmlspecialchars($sambutan['judul']); ?>
                        </h2>
                        
                        <p class="text-[11px] sm:text-xs text-slate-400 font-medium flex items-center gap-2 pt-1">
                            <span><i class="fa-regular fa-clock text-cyan-600 mr-1"></i>Pembaruan: <?= date('d F Y', strtotime($sambutan['tanggal_update'])); ?></span>
                            <span>•</span>
                            <span><i class="fa-solid fa-building-columns text-cyan-600 mr-1"></i>SMAN 21 Jakarta</span>
                        </p>
                    </div>

                    <!-- Render HTML Teks Sambutan Lengkap TinyMCE -->
                    <div class="sambutan-content font-normal">
                        <?= $sambutan['isi_sambutan']; ?>
                    </div>

                    <!-- Tanda Tangan / Penutup -->
                    <div class="pt-6 border-t border-slate-200 flex items-center justify-between gap-4">
                        <div>
                            <span class="text-xs text-slate-400 uppercase tracking-wider block font-semibold">Hormat Kami,</span>
                            <span class="text-sm sm:text-base font-bold text-brand-darkNavy mt-1 block"><?= htmlspecialchars($sambutan['nama_kepala_sekolah']); ?></span>
                            <span class="text-xs text-cyan-600 font-semibold block"><?= htmlspecialchars($sambutan['jabatan']); ?></span>
                        </div>
                        <img src="<?= $logoPath; ?>" alt="Logo Sekolah" class="h-12 w-12 sm:h-16 sm:w-16 object-contain opacity-40">
                    </div>

                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🎯 SECTION 3 PILAR KOMITMEN KEPEMIMPINAN SEKOLAH                      -->
    <!-- ===================================================================== -->
    <section class="py-12 sm:py-16 lg:py-20 bg-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-14 space-y-2 reveal-section">
                <span class="text-xs font-bold uppercase tracking-widest text-cyan-600">FOKUS UTAMA</span>
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-brand-darkNavy">Pilar Komitmen Pendidikan</h2>
                <p class="text-xs sm:text-sm text-slate-500">Prinsip dasar yang diterapkan kepemimpinan sekolah dalam mewujudkan SMAN 21 Jakarta unggulan.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
                
                <div class="school-card p-6 sm:p-8 rounded-2xl bg-white border border-slate-200 space-y-3.5 reveal-section delay-100">
                    <div class="w-12 h-12 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <h3 class="text-base font-extrabold text-brand-darkNavy">Keunggulan Akademik</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Meningkatkan kualitas pembelajaran melalui Kurikulum Merdeka, sains terapan, serta bimbingan intensif persiapan PTN Favorit.
                    </p>
                </div>

                <div class="school-card p-6 sm:p-8 rounded-2xl bg-white border border-slate-200 space-y-3.5 reveal-section delay-200">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid fa-shield-heart"></i>
                    </div>
                    <h3 class="text-base font-extrabold text-brand-darkNavy">Karakter Pelajar Pancasila</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Menanamkan kedisiplinan, integritas moral, kepedulian lingkungan, serta budaya belajar tanpa perundungan (No Bullying).
                    </p>
                </div>

                <div class="school-card p-6 sm:p-8 rounded-2xl bg-white border border-slate-200 space-y-3.5 reveal-section delay-300">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid fa-microchip"></i>
                    </div>
                    <h3 class="text-base font-extrabold text-brand-darkNavy">Inovasi Digital & AI</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Mengintegrasikan teknologi informasi, koding, kecerdasan artifisial, dan layanan digital modern dalam administrasi sekolah.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🤖 BANNER AI ASSISTANT PROFIL                                         -->
    <!-- ===================================================================== -->
    <section class="py-12 sm:py-16 bg-slate-900 text-white relative overflow-hidden">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 reveal-section">
            <div class="p-6 sm:p-10 lg:p-12 rounded-2xl bg-gradient-to-r from-brand-darkNavy to-slate-800 border border-slate-700 text-center space-y-4 shadow-2xl">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center mx-auto text-xl sm:text-2xl border border-cyan-500/30">
                    <i class="fa-solid fa-circle-question"></i>
                </div>
                <h2 class="text-xl sm:text-3xl font-extrabold text-white">
                    Ingin Bertanya Langsung tentang SMAN 21 Jakarta?
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                    S21 AI Assistant siap membantu Anda menjawab pertanyaan seputar Profil Kepala Sekolah, Kurikulum, Akademik, PPDB, dan Fasilitas secara otomatis.
                </p>
                <div class="pt-2">
                    <button onclick="toggleAiPanel()" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-lg transition-all inline-flex items-center justify-center gap-2.5 uppercase tracking-wider">
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
    <footer class="bg-brand-darkNavy text-slate-400 text-xs py-10 sm:py-12 border-t border-slate-800 max-w-full overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <img src="<?= $logoPath; ?>" alt="Logo SMAN 21" class="h-9 w-9 object-contain">
                        <span class="font-extrabold text-white text-sm sm:text-base">SMAN 21 JAKARTA</span>
                    </div>
                    <p class="text-[11px] leading-relaxed text-slate-400">
                        Sekolah Menengah Atas Berprestasi, Berkarakter Pancasila, Berbudaya Lingkungan, dan Berbasis Teknologi Modern.
                    </p>
                </div>

                <div>
                    <h4 class="text-white font-bold mb-3 text-xs sm:text-sm">Navigasi Profil</h4>
                    <ul class="space-y-2 text-[11px]">
                        <li><a href="index.php" class="hover:text-cyan-400 transition-colors">Tentang Sekolah</a></li>
                        <li><a href="sejarah.php" class="hover:text-cyan-400 transition-colors">Sejarah Sekolah</a></li>
                        <li><a href="visi-misi.php" class="hover:text-cyan-400 transition-colors">Visi & Misi</a></li>
                        <li><a href="sambutan.php" class="text-cyan-400 font-bold">Sambutan Kepala Sekolah</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-bold mb-3 text-xs sm:text-sm">Layanan Informasi</h4>
                    <ul class="space-y-2 text-[11px]">
                        <li><a href="../akademik/index.php" class="hover:text-cyan-400 transition-colors">Akademik & Guru</a></li>
                        <li><a href="../prestasi/index.php" class="hover:text-cyan-400 transition-colors">Prestasi Siswa</a></li>
                        <li><a href="../informasi/ppdb.php" class="hover:text-cyan-400 transition-colors">PPDB Info</a></li>
                        <li><a href="../kontak/index.php" class="hover:text-cyan-400 transition-colors">Kontak Resmi</a></li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <h4 class="text-white font-bold text-xs sm:text-sm">Media Sosial</h4>
                    <div class="flex gap-2">
                        <a href="<?= htmlspecialchars($sekolah['instagram']); ?>" target="_blank" aria-label="Instagram" class="w-8 h-8 rounded bg-slate-800 border border-slate-700 flex items-center justify-center hover:text-cyan-400 transition-colors"><i class="fa-brands fa-instagram"></i></a>
                        <a href="<?= htmlspecialchars($sekolah['youtube']); ?>" target="_blank" aria-label="YouTube" class="w-8 h-8 rounded bg-slate-800 border border-slate-700 flex items-center justify-center hover:text-cyan-400 transition-colors"><i class="fa-brands fa-youtube"></i></a>
                    </div>
                </div>

            </div>

            <div class="pt-6 border-t border-slate-800 flex flex-col sm:flex-row justify-between items-center text-[10px] text-slate-500 gap-2">
                <p>&copy; <?= date('Y'); ?> SMA Negeri 21 Jakarta. Hak Cipta Dilindungi Undang-Undang.</p>
                <p>Portal Resmi Informasi SMA Negeri 21 Jakarta</p>
            </div>
        </div>
    </footer>

    <!-- ===================================================================== -->
    <!-- 💬 AI ASSISTANT DRAWER PANEL                                          -->
    <!-- ===================================================================== -->
    <div id="aiPanel" class="fixed inset-y-0 right-0 z-[9990] w-full sm:w-96 max-w-full bg-white shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col border-l border-slate-200">
        <div class="p-3.5 bg-brand-darkNavy text-white flex items-center justify-between border-b border-slate-800">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-cyan-500/20 text-cyan-400 flex items-center justify-center border border-cyan-500/30">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-white">S21 AI Assistant</h3>
                    <span class="text-[9px] text-cyan-400 block font-semibold">Pusat Layanan Informasi SMAN 21</span>
                </div>
            </div>
            <button onclick="toggleAiPanel()" class="w-7 h-7 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>

        <div id="chatBox" class="flex-1 p-3.5 overflow-y-auto space-y-3 bg-slate-50 text-xs">
            <div class="p-3 rounded-xl bg-white border border-slate-200 text-slate-700 shadow-sm leading-relaxed space-y-1">
                <p class="font-bold text-brand-darkNavy">Halo! Saya S21 AI Assistant. 👋</p>
                <p>Ada yang bisa saya bantu terkait **Sambutan & Profil Kepala Sekolah** SMAN 21 Jakarta?</p>
            </div>
        </div>

        <div class="p-3 bg-white border-t border-slate-200">
            <form onsubmit="submitAiChat(event)" class="flex gap-2">
                <input type="text" id="aiInput" placeholder="Tanyakan seputar SMAN 21..." class="flex-1 px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50">
                <button type="submit" class="px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs transition-colors">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Floating Chat Button -->
    <button onclick="toggleAiPanel()" aria-label="Tanyakan AI" class="fixed bottom-5 right-5 z-[9000] w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-cyan-600 hover:bg-cyan-500 text-white shadow-2xl border-2 border-white flex items-center justify-center text-lg sm:text-xl transition-all hover:scale-110 active:scale-95 group">
        <i class="fa-solid fa-comments"></i>
        <span class="hidden sm:block absolute right-full mr-3 bg-brand-darkNavy text-white text-[10px] font-bold px-2.5 py-1 rounded shadow whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity">
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
                rootMargin: '0px 0px -40px 0px',
                threshold: 0.08
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

        // 2. MOBILE MENU & ACCORDION TOGGLE
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const backdrop = document.getElementById('mobileMenuBackdrop');
            const icon = document.getElementById('mobileMenuIcon');
            
            if (menu) {
                const isHidden = menu.classList.contains('hidden');
                if (isHidden) {
                    menu.classList.remove('hidden');
                    if (backdrop) backdrop.classList.remove('hidden');
                    if (icon) icon.className = 'fa-solid fa-xmark text-lg block';
                } else {
                    menu.classList.add('hidden');
                    if (backdrop) backdrop.classList.add('hidden');
                    if (icon) icon.className = 'fa-solid fa-bars text-lg block';
                }
            }
        }

        function toggleAccordion(accId, iconId) {
            const acc = document.getElementById(accId);
            const icon = document.getElementById(iconId);
            if (acc) {
                acc.classList.toggle('hidden');
            }
            if (icon) {
                icon.classList.toggle('rotate-180');
            }
        }

        // 3. AI ASSISTANT PANEL TOGGLE & CHAT AJAX
        function toggleAiPanel() {
            const panel = document.getElementById('aiPanel');
            if (panel) panel.classList.toggle('translate-x-full');
        }

        function submitAiChat(e) {
            e.preventDefault();
            const input = document.getElementById('aiInput');
            const q = input.value.trim();
            if (!q) return;

            const chatBox = document.getElementById('chatBox');
            
            // Render User Bubble
            const userMsg = document.createElement('div');
            userMsg.className = 'p-2.5 rounded-xl bg-cyan-600 text-white ml-auto max-w-[85%] text-xs font-medium shadow-sm';
            userMsg.innerText = q;
            chatBox.appendChild(userMsg);

            input.value = '';
            chatBox.scrollTop = chatBox.scrollHeight;

            // Render Loading Indicator
            const loadingMsg = document.createElement('div');
            loadingMsg.className = 'p-2.5 rounded-xl bg-white border border-slate-200 text-slate-500 max-w-[85%] text-xs italic shadow-sm';
            loadingMsg.innerText = 'Mencari jawaban resmi...';
            chatBox.appendChild(loadingMsg);
            chatBox.scrollTop = chatBox.scrollHeight;

            fetch('sambutan.php?action=ai_query', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'query=' + encodeURIComponent(q)
            })
            .then(res => res.json())
            .then(data => {
                loadingMsg.remove();
                const aiMsg = document.createElement('div');
                aiMsg.className = 'p-3 rounded-xl bg-white border border-slate-200 text-slate-800 max-w-[90%] text-xs shadow-sm space-y-1.5';
                
                let linkHtml = '';
                if (data.related_link) {
                    linkHtml = `<div class="pt-1"><a href="${data.related_link}" class="text-cyan-600 font-bold hover:underline inline-flex items-center gap-1">Pelajari lebih lanjut <i class="fa-solid fa-arrow-right text-[9px]"></i></a></div>`;
                }

                aiMsg.innerHTML = `<div>${data.answer}</div><div class="text-[9px] text-slate-400 font-semibold uppercase tracking-wider pt-1 border-t border-slate-100">Sumber: ${data.source}</div>${linkHtml}`;
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
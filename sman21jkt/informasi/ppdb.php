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
// HELPER FORMATTING & TANGGAL
// =============================================================================
function formatTanggalIndoSimple($dateStr) {
    if (empty($dateStr) || $dateStr === '0000-00-00') return '-';
    $time = strtotime($dateStr);
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
// FETCH DATA PENGATURAN SEKOLAH & PPDB DARI DATABASE
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

// Fetch Data PPDB dari Database
$ppdbData = null;
if ($conn) {
    try {
        $stmtPpdb = $conn->query("SELECT * FROM ppdb ORDER BY id DESC LIMIT 1");
        if ($stmtPpdb) {
            $ppdbData = $stmtPpdb->fetch();
        }
    } catch (PDOException $e) {}
}

// Fallback jika belum ada data PPDB di Database
if (!$ppdbData) {
    $ppdbData = [
        'id' => 1,
        'judul' => 'Penerimaan Peserta Didik Baru (PPDB / SPMB) T.A. 2026/2027',
        'deskripsi' => 'Selamat datang di Pusat Informasi Resmi PPDB SMAN 21 Jakarta. Kami berkomitmen menyelenggarakan seleksi penerimaan siswa baru yang transparan, akuntabel, objektif, dan bebas dari segala bentuk pemungutan biaya.',
        'tahun_ajaran' => '2026/2027',
        'tanggal_mulai' => '2026-06-15',
        'tanggal_selesai' => '2026-07-10',
        'persyaratan' => "1. Lulusan SMP/MTs/Sederajat bersertifikat resmi.\n2. Usia maksimal 21 tahun pada tanggal 1 Juli tahun berjalan.\n3. Memiliki Kartu Keluarga (KK) DKI Jakarta / Luar DKI sesuai ketentuan perundang-undangan.\n4. Memiliki Nilai Rapor Semester 1 sampai 5 yang telah terverifikasi.",
        'alur_pendaftaran' => "1. Pengajuan Akun & Verifikasi Dokumen Utama secara Online.\n2. Pemilihan Sekolah Tujuan (SMAN 21 Jakarta).\n3. Seleksi Otomatis Sistem berdasarkan Jalur (Prestasi / Zonasi / Afirmasi / PTO).\n4. Pengumuman Kelulusan Resmi secara Online.\n5. Lapor Diri & Daftar Ulang Siswa Baru.",
        'dokumen' => "1. Fotokopi Ijazah / Surat Keterangan Lulus (SKL) resmi.\n2. Kartu Keluarga (KK) Asli & Fotokopi.\n3. Akta Kelahiran Asli & Fotokopi.\n4. Buku Rapor Semester 1 - 5 Asli & Fotokopi.\n5. Pasfoto Berwarna Terbaru Ukuran 3x4 (2 Lembar).\n6. Sertifikat / Piagam Kejuaraan Lomba (Khusus Jalur Prestasi).",
        'link_pendaftaran' => 'https://ppdb.jakarta.go.id',
        'kontak' => '(021) 4892346 / Call Center PPDB DKI: 1500102',
        'status' => 'buka'
    ];
}

// =============================================================================
// ENDPOINT AJAX UNTUK S21 AI ASSISTANT (CONTEXT: PPDB & SPMB)
// =============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'ai_query') {
    header('Content-Type: application/json; charset=utf-8');
    $userQuery = trim($_POST['query'] ?? $_GET['q'] ?? '');
    
    if (empty($userQuery)) {
        echo json_encode(['status' => 'error', 'message' => 'Pertanyaan tidak boleh kosong.']);
        exit;
    }

    $responseAnswer = "";
    $sumberInfo = "Informasi Resmi PPDB SMAN 21 Jakarta";
    $relatedLink = "";

    $qLower = strtolower($userQuery);

    if (strpos($qLower, 'syarat') !== false || strpos($qLower, 'persyaratan') !== false) {
        $responseAnswer = "Persyaratan umum PPDB SMAN 21 Jakarta T.A. " . htmlspecialchars($ppdbData['tahun_ajaran']) . ": " . str_replace("\n", " ", $ppdbData['persyaratan']);
        $relatedLink = "#persyaratan";
    } elseif (strpos($qLower, 'alur') !== false || strpos($qLower, 'cara') !== false || strpos($qLower, 'daftar') !== false) {
        $responseAnswer = "Tahapan alur pendaftaran PPDB: " . str_replace("\n", " ", $ppdbData['alur_pendaftaran']) . " Pendaftaran resmi dilakukan melalui portal " . htmlspecialchars($ppdbData['link_pendaftaran'] ?: 'https://ppdb.jakarta.go.id');
        $relatedLink = "#alur";
    } elseif (strpos($qLower, 'dokumen') !== false || strpos($qLower, 'berkas') !== false) {
        $responseAnswer = "Dokumen yang wajib disiapkan calon peserta didik: " . str_replace("\n", " ", $ppdbData['dokumen']);
        $relatedLink = "#dokumen";
    } elseif (strpos($qLower, 'jadwal') !== false || strpos($qLower, 'kapan') !== false || strpos($qLower, 'tanggal') !== false) {
        $responseAnswer = "Jadwal pendaftaran PPDB T.A. " . htmlspecialchars($ppdbData['tahun_ajaran']) . " dibuka mulai tanggal " . formatTanggalIndoSimple($ppdbData['tanggal_mulai']) . " hingga " . formatTanggalIndoSimple($ppdbData['tanggal_selesai']) . ". Status saat ini: " . strtoupper($ppdbData['status']) . ".";
        $relatedLink = "#jadwal";
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
                $responseAnswer = "Penerimaan Peserta Didik Baru (PPDB) SMAN 21 Jakarta T.A. " . htmlspecialchars($ppdbData['tahun_ajaran']) . " dilaksanakan secara transparan melalui jalur Prestasi, Zonasi, Afirmasi, dan PTO. Anda dapat memeriksa rincian lengkapnya pada halaman ini.";
            }
        } else {
            $responseAnswer = "Untuk informasi seputar alur, syarat, serta jadwal PPDB SMAN 21 Jakarta T.A. " . htmlspecialchars($ppdbData['tahun_ajaran']) . ", silakan periksa rincian lengkap di halaman ini.";
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
    <title>Informasi PPDB / SPMB — SMAN 21 JAKARTA</title>
    <meta name="description" content="Informasi Resmi Penerimaan Peserta Didik Baru (PPDB / SPMB) SMA Negeri 21 Jakarta. Persyaratan, Jalur Seleksi, Alur Pendaftaran, dan Jadwal Lengkap.">
    
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
                                <a href="berita.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Berita Sekolah</a>
                                <a href="agenda.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Agenda & Kalender</a>
                                <a href="ppdb.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 font-bold transition-colors">PPDB Info</a>
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

        <div id="mobileMenu" class="hidden lg:hidden bg-white border-t border-slate-200 px-6 py-6 space-y-4 max-h-[80vh] overflow-y-auto">
            <a href="../index.php" class="block text-xs font-semibold text-slate-700">Beranda</a>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-cyan-600 font-bold tracking-wider">Informasi</span>
                <a href="berita.php" class="block pl-3 py-1 text-xs text-slate-700">Berita Sekolah</a>
                <a href="agenda.php" class="block pl-3 py-1 text-xs text-slate-700">Agenda & Kalender</a>
                <a href="ppdb.php" class="block pl-3 py-1 text-xs font-bold text-cyan-600">PPDB Info</a>
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
                <a href="berita.php" class="hover:text-cyan-400 transition-colors">Informasi</a>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-600"></i>
                <span class="text-cyan-400 font-bold">Informasi PPDB</span>
            </nav>

            <div class="max-w-3xl mx-auto space-y-4 reveal-section is-visible">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 text-xs font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-graduation-cap text-cyan-400"></i>
                    <span>SPMB / PPDB TAHUN AJARAN <?= htmlspecialchars($ppdbData['tahun_ajaran']); ?></span>
                </div>

                <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight">
                    Penerimaan Peserta Didik Baru
                </h1>

                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed max-w-2xl mx-auto">
                    Portal Informasi Resmi Penerimaan Siswa Baru SMAN 21 Jakarta. Seleksi dilaksanakan secara obyektif, transparan, akuntabel, dan bebas biaya.
                </p>
            </div>

            <div class="pt-2 flex flex-wrap items-center justify-center gap-3 reveal-section delay-100">
                <?php if ($ppdbData['status'] === 'buka'): ?>
                <span class="px-3.5 py-1.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span>Pendaftaran Resmi Dibuka</span>
                </span>
                <?php elseif ($ppdbData['status'] === 'persiapan'): ?>
                <span class="px-3.5 py-1.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-clock"></i>
                    <span>Tahap Persiapan / Sosialisasi</span>
                </span>
                <?php else: ?>
                <span class="px-3.5 py-1.5 rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/40 text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-lock"></i>
                    <span>Pendaftaran Ditutup</span>
                </span>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📄 HERO OVERVIEW & CARD UTAMA                                          -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            
            <!-- Main Info Banner -->
            <div class="school-card p-6 sm:p-10 rounded-2xl bg-white border border-slate-200 shadow-xl reveal-section">
                <div class="grid lg:grid-cols-12 gap-8 items-center">
                    
                    <div class="lg:col-span-8 space-y-5">
                        <div class="flex items-center gap-2 text-cyan-600 font-extrabold text-xs uppercase tracking-widest">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>INFORMASI RESMI SPMB SMAN 21 JAKARTA</span>
                        </div>

                        <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-darkNavy leading-snug">
                            <?= htmlspecialchars($ppdbData['judul']); ?>
                        </h2>

                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            <?= nl2br(htmlspecialchars($ppdbData['deskripsi'])); ?>
                        </p>

                        <div class="grid sm:grid-cols-2 gap-4 pt-2 text-xs">
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-cyan-100 text-cyan-700 flex items-center justify-center text-lg flex-shrink-0 font-bold">
                                    <i class="fa-regular fa-calendar-days"></i>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Jadwal Pelaksanaan</span>
                                    <span class="font-bold text-slate-800"><?= formatTanggalIndoSimple($ppdbData['tanggal_mulai']); ?> - <?= formatTanggalIndoSimple($ppdbData['tanggal_selesai']); ?></span>
                                </div>
                            </div>

                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-cyan-100 text-cyan-700 flex items-center justify-center text-lg flex-shrink-0 font-bold">
                                    <i class="fa-solid fa-headset"></i>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Layanan Bantuan / Helpdesk</span>
                                    <span class="font-bold text-slate-800 truncate block max-w-[200px]"><?= htmlspecialchars($ppdbData['kontak'] ?: '(021) 4892346'); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 flex flex-wrap items-center gap-4">
                            <?php if (!empty($ppdbData['link_pendaftaran'])): ?>
                            <a href="<?= htmlspecialchars($ppdbData['link_pendaftaran']); ?>" target="_blank" class="px-7 py-3 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs shadow-md transition-all flex items-center gap-2.5">
                                <span>PORTAL PPDB JAKARTA</span>
                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                            </a>
                            <?php endif; ?>
                            <a href="#persyaratan" class="px-6 py-3 rounded-lg bg-brand-darkNavy hover:bg-slate-800 text-white font-bold text-xs shadow transition-all flex items-center gap-2">
                                <span>Lihat Persyaratan Berkas</span>
                                <i class="fa-solid fa-chevron-down text-[10px]"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Side Card Illustration / Badge -->
                    <div class="lg:col-span-4 bg-gradient-to-br from-brand-darkNavy to-slate-900 text-white p-6 sm:p-8 rounded-xl space-y-6 border border-slate-800 shadow-inner">
                        <div class="flex items-center gap-3">
                            <img src="<?= $logoPath; ?>" alt="Logo SMAN 21" class="h-12 w-12 object-contain">
                            <div>
                                <h3 class="text-sm font-bold text-white">SMAN 21 JAKARTA</h3>
                                <span class="text-[10px] text-cyan-400 font-semibold uppercase block">Sekolah Ramah Anak</span>
                            </div>
                        </div>

                        <div class="space-y-3 border-t border-slate-800 pt-4 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Status Sekolah</span>
                                <span class="font-bold text-white">Negeri</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Akreditasi</span>
                                <span class="font-bold text-cyan-400">A (Unggul)</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Sistem Seleksi</span>
                                <span class="font-bold text-white">Online Terpusat</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Biaya Pendaftaran</span>
                                <span class="font-bold text-emerald-400">100% Gratis</span>
                            </div>
                        </div>

                        <div class="p-3 rounded-lg bg-white/5 border border-white/10 text-[11px] text-slate-300 leading-relaxed">
                            <i class="fa-solid fa-shield-halved text-cyan-400 mr-1.5"></i>
                            Bebas pungutan liar, perundungan, dan kejahatan senioritas.
                        </div>
                    </div>

                </div>
            </div>

            <!-- ===================================================================== -->
            <!-- 🎯 JALUR SELEKSI PPDB SMAN 21 JAKARTA                                 -->
            <!-- ===================================================================== -->
            <div class="space-y-6">
                <div class="text-center max-w-2xl mx-auto space-y-2 reveal-section">
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-600">JALUR PENERIMAAN</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-darkNavy">Skema & Jalur Seleksi PPDB</h2>
                    <p class="text-xs sm:text-sm text-slate-500">SMAN 21 Jakarta menerima siswa baru melalui 4 jalur utama sesuai juknis PPDB Provinsi DKI Jakarta.</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 space-y-4 reveal-section delay-100">
                        <div class="w-12 h-12 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center text-xl font-bold">
                            <i class="fa-solid fa-trophy"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-cyan-600 uppercase tracking-wider block">Jalur 1</span>
                            <h3 class="text-base font-bold text-brand-darkNavy">Jalur Prestasi</h3>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Apresiasi bagi calon peserta didik yang memiliki capaian prestasi Akademik (Rapor & OSN) maupun Non-Akademik (Seni, Olahraga, Pramuka).
                        </p>
                        <span class="inline-block px-2.5 py-1 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">Kuota: ± 20%</span>
                    </div>

                    <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 space-y-4 reveal-section delay-200">
                        <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-xl font-bold">
                            <i class="fa-solid fa-heart-circle-check"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider block">Jalur 2</span>
                            <h3 class="text-base font-bold text-brand-darkNavy">Jalur Afirmasi</h3>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Diperuntukkan bagi siswa dari keluarga ekonomi kurang mampu penerima KJP Plus/PIP, penyandang disabilitas, serta anak panti/tenaga kesehatan.
                        </p>
                        <span class="inline-block px-2.5 py-1 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">Kuota: ± 25%</span>
                    </div>

                    <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 space-y-4 reveal-section delay-300">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold">
                            <i class="fa-solid fa-map-location-dot"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Jalur 3</span>
                            <h3 class="text-base font-bold text-brand-darkNavy">Jalur Zonasi</h3>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Seleksi berbasis jarak kedekatan domisili RT/RW calon peserta didik baru dengan lokasi kampus SMAN 21 Jakarta di Kayu Putih, Pulogadung.
                        </p>
                        <span class="inline-block px-2.5 py-1 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">Kuota: ± 50%</span>
                    </div>

                    <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 space-y-4 reveal-section delay-300">
                        <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl font-bold">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-purple-600 uppercase tracking-wider block">Jalur 4</span>
                            <h3 class="text-base font-bold text-brand-darkNavy">Pindah Tugas Orang Tua</h3>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Diperuntukkan bagi calon peserta didik yang orang tua/walinya mengalami perpindahan tugas dinas resmi dari luar DKI Jakarta.
                        </p>
                        <span class="inline-block px-2.5 py-1 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">Kuota: ± 5%</span>
                    </div>

                </div>
            </div>

            <!-- ===================================================================== -->
            <!-- 📋 TAB PERSYARATAN & DOKUMEN                                          -->
            <!-- ===================================================================== -->
            <div id="persyaratan" class="grid lg:grid-cols-12 gap-8 items-start pt-6">
                
                <div class="lg:col-span-6 school-card p-6 sm:p-8 rounded-2xl bg-white border border-slate-200 space-y-5 reveal-section">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <div class="w-10 h-10 rounded-lg bg-cyan-100 text-cyan-700 flex items-center justify-center text-lg font-bold">
                            <i class="fa-solid fa-list-check"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-brand-darkNavy">Persyaratan Umum Pendaftaran</h3>
                            <span class="text-[11px] text-slate-500">Ketentuan resmi calon siswa baru</span>
                        </div>
                    </div>

                    <div class="text-xs text-slate-700 space-y-3 leading-relaxed">
                        <?php 
                        $syaratArr = explode("\n", $ppdbData['persyaratan']);
                        foreach ($syaratArr as $sy) {
                            $syClean = trim($sy);
                            if (empty($syClean)) continue;
                        ?>
                        <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                            <i class="fa-solid fa-circle-check text-cyan-600 text-sm mt-0.5 flex-shrink-0"></i>
                            <span><?= htmlspecialchars($syClean); ?></span>
                        </div>
                        <?php } ?>
                    </div>
                </div>

                <div id="dokumen" class="lg:col-span-6 school-card p-6 sm:p-8 rounded-2xl bg-white border border-slate-200 space-y-5 reveal-section delay-200">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <div class="w-10 h-10 rounded-lg bg-cyan-100 text-cyan-700 flex items-center justify-center text-lg font-bold">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-brand-darkNavy">Dokumen Persyaratan Wajib</h3>
                            <span class="text-[11px] text-slate-500">Berkas administrasi yang diunggah/disiapkan</span>
                        </div>
                    </div>

                    <div class="text-xs text-slate-700 space-y-3 leading-relaxed">
                        <?php 
                        $docArr = explode("\n", $ppdbData['dokumen']);
                        foreach ($docArr as $dc) {
                            $dcClean = trim($dc);
                            if (empty($dcClean)) continue;
                        ?>
                        <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                            <i class="fa-solid fa-folder-open text-amber-500 text-sm mt-0.5 flex-shrink-0"></i>
                            <span><?= htmlspecialchars($dcClean); ?></span>
                        </div>
                        <?php } ?>
                    </div>
                </div>

            </div>

            <!-- ===================================================================== -->
            <!-- 🔄 ALUR TAHAPAN PENDAFTARAN                                            -->
            <!-- ===================================================================== -->
            <div id="alur" class="school-card p-6 sm:p-10 rounded-2xl bg-white border border-slate-200 space-y-8 reveal-section">
                <div class="text-center max-w-2xl mx-auto space-y-2">
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-600">ALUR PPDB</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-darkNavy">Tahapan Prosedur Pendaftaran</h2>
                    <p class="text-xs sm:text-sm text-slate-500">Ikuti langkah-langkah pendaftaran online terpadu berikut ini secara urut.</p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    
                    <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 space-y-3 relative group">
                        <div class="w-8 h-8 rounded-full bg-cyan-600 text-white flex items-center justify-center font-extrabold text-xs">1</div>
                        <h4 class="text-xs font-bold text-brand-darkNavy">Pengajuan Akun</h4>
                        <p class="text-[11px] text-slate-500 leading-relaxed">Mengisi formulir data diri & unggah berkas awal di portal PPDB DKI Jakarta.</p>
                    </div>

                    <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 space-y-3 relative group">
                        <div class="w-8 h-8 rounded-full bg-cyan-600 text-white flex items-center justify-center font-extrabold text-xs">2</div>
                        <h4 class="text-xs font-bold text-brand-darkNavy">Verifikasi Berkas</h4>
                        <p class="text-[11px] text-slate-500 leading-relaxed">Tim verifikator memvalidasi dokumen dan mencetak Token PIN pendaftaran.</p>
                    </div>

                    <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 space-y-3 relative group">
                        <div class="w-8 h-8 rounded-full bg-cyan-600 text-white flex items-center justify-center font-extrabold text-xs">3</div>
                        <h4 class="text-xs font-bold text-brand-darkNavy">Aktivasi PIN & Pilih Sekolah</h4>
                        <p class="text-[11px] text-slate-500 leading-relaxed">Aktivasi akun menggunakan Token PIN, lalu memilih SMAN 21 Jakarta.</p>
                    </div>

                    <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 space-y-3 relative group">
                        <div class="w-8 h-8 rounded-full bg-cyan-600 text-white flex items-center justify-center font-extrabold text-xs">4</div>
                        <h4 class="text-xs font-bold text-brand-darkNavy">Seleksi Real-Time</h4>
                        <p class="text-[11px] text-slate-500 leading-relaxed">Pantau hasil pemeringkatan jurnal seleksi secara transparan di website.</p>
                    </div>

                    <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 space-y-3 relative group">
                        <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-extrabold text-xs">5</div>
                        <h4 class="text-xs font-bold text-brand-darkNavy">Pengumuman & Lapor Diri</h4>
                        <p class="text-[11px] text-slate-500 leading-relaxed">Calon peserta didik yang diterima wajib melakukan Lapor Diri secara online.</p>
                    </div>

                </div>
            </div>

            <!-- ===================================================================== -->
            <!-- ❓ FAQ SEPUTAR PPDB SMAN 21                                           -->
            <!-- ===================================================================== -->
            <div class="space-y-6">
                <div class="text-center max-w-2xl mx-auto space-y-2 reveal-section">
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-600">FAQ PPDB</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-darkNavy">Pertanyaan Sering Diajukan</h2>
                </div>

                <div class="max-w-3xl mx-auto space-y-3 reveal-section delay-100">
                    
                    <details class="group bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-sm transition-all [&_summary::-webkit-details-marker]:hidden">
                        <summary class="flex items-center justify-between cursor-pointer font-bold text-xs sm:text-sm text-brand-darkNavy">
                            <span>Apakah pendaftaran PPDB SMAN 21 Jakarta dipungut biaya?</span>
                            <span class="ml-1.5 flex-shrink-0 rounded-full bg-slate-100 p-1.5 text-slate-900 group-open:-rotate-180 transition-transform">
                                <i class="fa-solid fa-chevron-down text-xs"></i>
                            </span>
                        </summary>
                        <p class="mt-3 text-xs text-slate-600 leading-relaxed border-t border-slate-100 pt-3">
                            Sama sekali tidak. Seluruh proses pendaftaran SPMB/PPDB di SMAN 21 Jakarta diselenggarakan 100% Gratis dan bebas pungutan liar sesuai regulasi Dinas Pendidikan DKI Jakarta.
                        </p>
                    </details>

                    <details class="group bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-sm transition-all [&_summary::-webkit-details-marker]:hidden">
                        <summary class="flex items-center justify-between cursor-pointer font-bold text-xs sm:text-sm text-brand-darkNavy">
                            <span>Bagaimana jika berkas pendaftaran saya ditolak saat verifikasi?</span>
                            <span class="ml-1.5 flex-shrink-0 rounded-full bg-slate-100 p-1.5 text-slate-900 group-open:-rotate-180 transition-transform">
                                <i class="fa-solid fa-chevron-down text-xs"></i>
                            </span>
                        </summary>
                        <p class="mt-3 text-xs text-slate-600 leading-relaxed border-t border-slate-100 pt-3">
                            Sistem akan memberikan alasan penolakan secara spesifik (misalnya foto kurang jelas/salah dokumen). Anda diperbolehkan mengunggah ulang perbaikan berkas selama masa pendaftaran masih dibuka.
                        </p>
                    </details>

                    <details class="group bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-sm transition-all [&_summary::-webkit-details-marker]:hidden">
                        <summary class="flex items-center justify-between cursor-pointer font-bold text-xs sm:text-sm text-brand-darkNavy">
                            <span>Di mana lokasi Posko / Helpdesk Layanan Informasi PPDB SMAN 21?</span>
                            <span class="ml-1.5 flex-shrink-0 rounded-full bg-slate-100 p-1.5 text-slate-900 group-open:-rotate-180 transition-transform">
                                <i class="fa-solid fa-chevron-down text-xs"></i>
                            </span>
                        </summary>
                        <p class="mt-3 text-xs text-slate-600 leading-relaxed border-t border-slate-100 pt-3">
                            Posko PPDB luring berada di Ruang Pelayanan Informasi Kampus SMAN 21 Jakarta (Jl. Tanah Mas Raya No.1, Kayu Putih, Pulogadung) yang melayani setiap hari kerja Senin - Jumat jam 08.00 - 15.00 WIB.
                        </p>
                    </details>

                </div>
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
                    Masih Bingung Persyaratan atau Alur PPDB?
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                    S21 AI Assistant dapat menjawab pertanyaan khusus mengenai Jalur Prestasi, Zonasi, Afirmasi, serta verifikasi berkas PPDB secara otomatis.
                </p>
                <div class="pt-2">
                    <button onclick="toggleAiPanel()" class="px-7 py-3 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-lg transition-all inline-flex items-center gap-2.5 uppercase tracking-wider">
                        <i class="fa-solid fa-comments text-sm"></i>
                        <span>TANYAKAN SEPUTAR PPDB KE AI</span>
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
                        <li><a href="ppdb.php" class="text-cyan-400 font-bold">Informasi SPMB / PPDB</a></li>
                        <li><a href="berita.php" class="hover:text-cyan-400 transition-colors">Berita & Pengumuman</a></li>
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
                    <span class="text-[10px] text-cyan-400 block font-semibold">Context: Informasi PPDB / SPMB</span>
                </div>
            </div>
            <button onclick="toggleAiPanel()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="chatBox" class="flex-1 p-4 overflow-y-auto space-y-4 bg-slate-50 text-xs">
            <div class="p-3.5 rounded-xl bg-white border border-slate-200 text-slate-700 shadow-sm leading-relaxed space-y-2">
                <p class="font-bold text-brand-darkNavy">Halo! Saya S21 AI Assistant. 👋</p>
                <p>🎓 Kamu sedang melihat **Informasi PPDB**. Ada yang ingin kamu tanyakan mengenai syarat, alur, jadwal, atau berkas pendaftaran?</p>
                <div class="pt-2 flex flex-wrap gap-1.5">
                    <button onclick="quickAsk('Persyaratan pendaftaran PPDB')" class="px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 text-[10px] font-semibold transition-colors">
                        📋 Syarat PPDB
                    </button>
                    <button onclick="quickAsk('Alur dan cara mendaftar')" class="px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 text-[10px] font-semibold transition-colors">
                        🔄 Alur Pendaftaran
                    </button>
                    <button onclick="quickAsk('Dokumen wajib yang disiapkan')" class="px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 text-[10px] font-semibold transition-colors">
                        📁 Berkas Wajib
                    </button>
                </div>
            </div>
        </div>

        <div class="p-3 bg-white border-t border-slate-200">
            <form onsubmit="submitAiChat(event)" class="flex gap-2">
                <input type="text" id="aiInput" placeholder="Tanyakan seputar PPDB SMAN 21..." class="flex-1 px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50">
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
            loadingMsg.innerText = 'Mengecek basis data PPDB SMAN 21...';
            chatBox.appendChild(loadingMsg);
            chatBox.scrollTop = chatBox.scrollHeight;

            fetch('ppdb.php?action=ai_query', {
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
                    linkHtml = `<div class="pt-1"><a href="${data.related_link}" class="text-cyan-600 font-bold hover:underline inline-flex items-center gap-1">Lihat Bagian Terkait <i class="fa-solid fa-arrow-down text-[9px]"></i></a></div>`;
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
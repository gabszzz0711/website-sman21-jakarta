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
    $sumberInfo = "Basis Informasi SMAN 21 Jakarta";
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
            if (strpos($qLower, 'ptn') !== false || strpos($qLower, 'kuliah') !== false || strpos($qLower, 'alumni') !== false || strpos($qLower, 'snbp') !== false || strpos($qLower, 'snbt') !== false) {
                $responseAnswer = "Tingkat keterserapan alumni SMAN 21 Jakarta di Perguruan Tinggi Negeri (PTN) mencapai lebih dari 85% setiap tahunnya, tersebar di UI, ITB, UGM, IPB, Unpad, ITS, dan PTN/Kedinasan favorit lainnya.";
                $sumberInfo = "Data Akademik SMAN 21";
                $relatedLink = "keterserapan-ptn.php";
                $matched = true;
            } elseif (strpos($qLower, 'ppdb') !== false || strpos($qLower, 'daftar') !== false) {
                $stmtPpdb = $conn->query("SELECT * FROM ppdb WHERE status='buka' LIMIT 1");
                if ($ppdbData = $stmtPpdb->fetch()) {
                    $responseAnswer = "Penerimaan Peserta Didik Baru (PPDB) T.A. " . htmlspecialchars($ppdbData['tahun_ajaran']) . " saat ini statusnya BUKA. " . htmlspecialchars($ppdbData['deskripsi']);
                    $sumberInfo = "Informasi PPDB Resmi";
                    $relatedLink = "../informasi/ppdb.php";
                    $matched = true;
                }
            } elseif (strpos($qLower, 'alamat') !== false || strpos($qLower, 'kontak') !== false || strpos($qLower, 'lokasi') !== false) {
                $stmtKtk = $conn->query("SELECT * FROM kontak_sekolah LIMIT 1");
                if ($ktkData = $stmtKtk->fetch()) {
                    $responseAnswer = "SMAN 21 Jakarta berlokasi di " . ($ktkData['alamat'] ?: "Jl. Tanah Mas Raya No.1, Kayu Putih, Pulogadung, Jakarta Timur") . ". Telepon: " . ($ktkData['telepon'] ?: "(021) 4892346") . ".";
                    $sumberInfo = "Kontak Sekolah";
                    $relatedLink = "../kontak/index.php";
                    $matched = true;
                }
            }
        }

        if (!$matched) {
            $responseAnswer = $fallbackMsg;
            $sumberInfo = "Sistem Verifikasi SMAN 21";
        }

    } else {
        $responseAnswer = "Lebih dari 85% lulusan SMAN 21 Jakarta diterima di Perguruan Tinggi Negeri (PTN) ternama seperti UI, ITB, UGM, IPB, Unpad, dan ITS melalui jalur SNBP, SNBT, Kedinasan, dan Mandiri.";
        $sumberInfo = "Sistem SMAN 21";
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
// FETCH DATA RESMI SEKOLAH & DATA KETERSERAPAN PTN DARI DATABASE
// =============================================================================

// 1. Data Sekolah
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

// 2. Data Keterserapan PTN
$ptnData = [];
if ($conn) {
    try {
        $stmtPtn = $conn->query("SELECT * FROM keterserapan_ptn ORDER BY tahun DESC");
        if ($stmtPtn) $ptnData = $stmtPtn->fetchAll();
    } catch (PDOException $e) {
        $ptnData = [];
    }
}

if (empty($ptnData)) {
    $ptnData = [
        [
            'id' => 1,
            'tahun' => '2025',
            'jumlah_lulusan' => 290,
            'snbp' => 80,
            'snbt' => 152,
            'mandiri' => 32,
            'kedinasan' => 13,
            'ptln' => 3,
            'keterangan' => 'Peningkatan signifikan kelulusan SNBP di Universitas Indonesia dan ITB.'
        ],
        [
            'id' => 2,
            'tahun' => '2024',
            'jumlah_lulusan' => 285,
            'snbp' => 72,
            'snbt' => 148,
            'mandiri' => 36,
            'kedinasan' => 12,
            'ptln' => 2,
            'keterangan' => 'Capaian tertinggi pada Fakultas Kedokteran dan Teknik UI/UGM.'
        ],
        [
            'id' => 3,
            'tahun' => '2023',
            'jumlah_lulusan' => 280,
            'snbp' => 65,
            'snbt' => 140,
            'mandiri' => 35,
            'kedinasan' => 15,
            'ptln' => 1,
            'keterangan' => 'Sebanyak 90% alumni melanjutkan ke Perguruan Tinggi Negeri & Kedinasan.'
        ]
    ];
}

// 3. Data Alumni Sukses / Testimonial Alumni
$alumniList = [];
if ($conn) {
    try {
        $stmtAlumni = $conn->query("SELECT * FROM alumni WHERE status_tampil = 1 ORDER BY tahun_lulus DESC LIMIT 4");
        if ($stmtAlumni) $alumniList = $stmtAlumni->fetchAll();
    } catch (PDOException $e) {
        $alumniList = [];
    }
}

if (empty($alumniList)) {
    $alumniList = [
        [
            'nama_alumni' => 'Aditya Pratama, S.T.',
            'tahun_lulus' => '2021',
            'jurusan_program' => 'MIPA',
            'universitas_instansi' => 'Institut Teknologi Bandung (ITB)',
            'profesi' => 'Software Engineer & AI Specialist',
            'cerita' => 'Sistem bimbingan akademik dan atmosfer kompetitif yang sehat di SMAN 21 Jakarta membentuk fondasi berpikir kritis dan disiplin saya selama kuliah di ITB.',
            'foto' => '../uploads/alumni/alumni1.jpg'
        ],
        [
            'nama_alumni' => 'Nabila Putri, S.Ked.',
            'tahun_lulus' => '2020',
            'jurusan_program' => 'MIPA',
            'universitas_instansi' => 'Universitas Indonesia (UI)',
            'profesi' => 'Dokter Muda FK UI',
            'cerita' => 'Dukungan penuh para guru dan pemeringkatan berkala di SMAN 21 sangat membantu strategi saya menembus Fakultas Kedokteran UI melalui jalur SNBP.',
            'foto' => '../uploads/alumni/alumni2.jpg'
        ],
        [
            'nama_alumni' => 'Rizky Ramadhan, S.E.',
            'tahun_lulus' => '2022',
            'jurusan_program' => 'IPS',
            'universitas_instansi' => 'Universitas Gadjah Mada (UGM)',
            'profesi' => 'Financial Analyst',
            'cerita' => 'Pengembangan minat bakat serta organisasi di SMAN 21 membuat saya percaya diri bersaing dan berprestasi hingga tingkat nasional di UGM.',
            'foto' => '../uploads/alumni/alumni3.jpg'
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
    <title>Keterserapan PTN Alumni — SMAN 21 Jakarta</title>
    <meta name="description" content="Data resmi rekapitulasi keterserapan alumni SMAN 21 Jakarta di Perguruan Tinggi Negeri (PTN), PTN Favorit (UI, ITB, UGM, IPB, Unpad, ITS), Kedinasan, dan Luar Negeri.">
    
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
            box-shadow: 0 14px 30px -4px rgba(15, 23, 42, 0.08);
            border-color: #CBD5E1;
        }

        .ptn-badge {
            background: linear-gradient(135deg, rgba(0, 168, 204, 0.1) 0%, rgba(11, 30, 54, 0.05) 100%);
            border: 1px solid rgba(0, 168, 204, 0.2);
        }

        /* Continuous Smooth Infinite Marquee Animation (Kiri ke Kanan) */
        @keyframes marquee-right {
            0% { transform: translateX(-50%); }
            100% { transform: translateX(0%); }
        }

        .animate-marquee {
            display: flex;
            width: max-content;
            animation: marquee-right 28s linear infinite;
        }

        .animate-marquee.paused {
            animation-play-state: paused !important;
        }

        ::-webkit-scrollbar { width: 7px; }
        ::-webkit-scrollbar-track { background: #061224; }
        ::-webkit-scrollbar-thumb { background: #00A8CC; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #0086A3; }
    </style>
</head>
<body class="relative min-h-screen text-slate-800 bg-slate-50 selection:bg-cyan-500 selection:text-white max-w-full overflow-x-hidden">

    <!-- ===================================================================== -->
    <!-- 🧭 TOPBAR & GLOBAL HEADER                                              -->
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
                                <a href="pemeringkatan.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Pemeringkatan Jurusan</a>
                                <a href="keterserapan-ptn.php" class="block px-3 py-2 rounded-lg text-cyan-600 font-bold bg-slate-50 transition-colors">Keterserapan PTN</a>
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
                <a href="pemeringkatan.php" class="block pl-3 py-1 text-xs text-slate-700">Pemeringkatan</a>
                <a href="keterserapan-ptn.php" class="block pl-3 py-1 text-xs text-cyan-600 font-bold">Keterserapan PTN</a>
            </div>
            <a href="../prestasi/index.php" class="block text-xs font-semibold text-slate-700">Prestasi</a>
            <a href="../kegiatan/index.php" class="block text-xs font-semibold text-slate-700">Kegiatan & Ekskul</a>
            <a href="../informasi/ppdb.php" class="block text-xs font-semibold text-slate-700">PPDB Info</a>
            <a href="../kontak/index.php" class="block text-xs font-semibold text-slate-700">Kontak</a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 🎓 PAGE HERO BANNER (PERBAIKAN PATH BACKGROUND IMAGE HERO)           -->
    <!-- ===================================================================== -->
    <section class="relative bg-brand-darkNavy text-white py-16 sm:py-24 border-b border-slate-800 overflow-hidden bg-cover bg-center bg-no-repeat" style="background-image: url('../uploads/banners/banner1.png');">
        <!-- Overlay Gradien Gelap untuk keterbacaan teks -->
        <div class="absolute inset-0 bg-gradient-to-r from-brand-darkNavy/95 via-brand-darkNavy/90 to-brand-darkNavy/95 z-10"></div>
        <div class="absolute inset-0 bg-[radial-gradient(#00A8CC_1px,transparent_1px)] [background-size:24px_24px] opacity-15 z-10"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20">
            <div class="max-w-3xl space-y-4 reveal-section is-visible">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-cyan-500/20 border border-cyan-400/30 text-cyan-300 text-[11px] sm:text-xs font-bold tracking-wide uppercase backdrop-blur-sm">
                    <i class="fa-solid fa-chart-line text-cyan-400"></i>
                    <span>CAPAIAN AKADEMIK & REKAM JEJAK ALUMNI</span>
                </div>
                
                <h1 class="text-3xl sm:text-5xl font-extrabold text-white leading-tight">
                    Keterserapan Alumni di <span class="text-cyan-400">Perguruan Tinggi Negeri</span>
                </h1>
                
                <p class="text-xs sm:text-base text-slate-300 font-normal leading-relaxed max-w-2xl">
                    Rekapitulasi resmi tingkat kelulusan dan keberhasilan lulusan SMA Negeri 21 Jakarta menembus PTN Favorit (UI, ITB, UGM, IPB, Unpad, ITS) serta Perguruan Tinggi Kedinasan melalui jalur SNBP, SNBT, dan Mandiri.
                </p>

                <!-- Breadcrumb -->
                <div class="pt-2 flex items-center gap-2 text-xs text-slate-400 font-medium">
                    <a href="../index.php" class="hover:text-cyan-400 transition-colors">Beranda</a>
                    <i class="fa-solid fa-chevron-right text-[9px] text-slate-600"></i>
                    <span>Akademik</span>
                    <i class="fa-solid fa-chevron-right text-[9px] text-slate-600"></i>
                    <span class="text-cyan-400 font-semibold">Keterserapan PTN</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📈 RINGKASAN STATISTIK UTAMA                                          -->
    <!-- ===================================================================== -->
    <section class="relative z-30 -mt-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            
            <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 reveal-section delay-100 flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-lg bg-cyan-100 text-cyan-700 flex items-center justify-center mb-3">
                        <i class="fa-solid fa-award text-lg"></i>
                    </div>
                    <span class="text-3xl sm:text-4xl font-extrabold text-brand-darkNavy block">85%+</span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 mt-1">
                        Diterima PTN Favorit
                    </h3>
                </div>
                <p class="text-[11px] text-slate-500 mt-2">Rata-rata kelulusan tahunan di universitas negeri bereputasi tinggi.</p>
            </div>

            <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 reveal-section delay-200 flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center mb-3">
                        <i class="fa-solid fa-star text-lg"></i>
                    </div>
                    <?php 
                        $latestYearData = $ptnData[0] ?? ['snbp' => 80];
                    ?>
                    <span class="text-3xl sm:text-4xl font-extrabold text-emerald-600 block"><?= $latestYearData['snbp']; ?> Siswa</span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 mt-1">
                        Lulus SNBP (Prestasi)
                    </h3>
                </div>
                <p class="text-[11px] text-slate-500 mt-2">Masuk PTN tanpa tes berdasarkan nilai rapor & prestasi unggulan.</p>
            </div>

            <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 reveal-section delay-300 flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center mb-3">
                        <i class="fa-solid fa-pen-to-square text-lg"></i>
                    </div>
                    <span class="text-3xl sm:text-4xl font-extrabold text-blue-700 block"><?= $latestYearData['snbt']; ?> Siswa</span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 mt-1">
                        Lulus SNBT (UTBK)
                    </h3>
                </div>
                <p class="text-[11px] text-slate-500 mt-2">Lolos seleksi nasional berdasarkan tes UTBK berbasis komputer.</p>
            </div>

            <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 reveal-section delay-400 flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center mb-3">
                        <i class="fa-solid fa-building-columns text-lg"></i>
                    </div>
                    <span class="text-3xl sm:text-4xl font-extrabold text-purple-700 block"><?= ($latestYearData['mandiri'] ?? 32) + ($latestYearData['kedinasan'] ?? 13); ?> Siswa</span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 mt-1">
                        Mandiri & Kedinasan
                    </h3>
                </div>
                <p class="text-[11px] text-slate-500 mt-2">Sekolah Tinggi Kedinasan, PTN Mandiri, dan Beasiswa Luar Negeri.</p>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📊 GRAFIK & ANALISIS DATA KETERSERAPAN                               -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-8 items-start">
                
                <!-- Chart 1: Trend Keterserapan PTN -->
                <div class="lg:col-span-7 reveal-section">
                    <div class="school-card p-6 sm:p-8 rounded-2xl bg-white border border-slate-200 shadow-md space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-widest text-cyan-600">GRAFIK AKADEMIK</span>
                                <h2 class="text-lg font-extrabold text-brand-darkNavy">Tren Kelulusan PTN per Tahun</h2>
                            </div>
                            <span class="text-[10px] font-bold text-cyan-800 bg-cyan-50 px-2.5 py-1 rounded border border-cyan-200 w-fit">
                                Terverifikasi Tim Kurikulum
                            </span>
                        </div>

                        <div class="relative w-full h-80">
                            <canvas id="ptnTrendChart"></canvas>
                        </div>

                        <p class="text-xs text-slate-500 leading-relaxed pt-2 border-t border-slate-100">
                            * Data merepresentasikan seluruh lulusan angkatan yang terdaftar dan terkonfirmasi masuk PTN, PTKIN, dan Perguruan Tinggi Kedinasan.
                        </p>
                    </div>
                </div>

                <!-- Chart 2: Komposisi Jalur Masuk (Doughnut Chart) -->
                <div class="lg:col-span-5 reveal-section delay-200">
                    <div class="school-card p-6 sm:p-8 rounded-2xl bg-white border border-slate-200 shadow-md space-y-6">
                        <div class="border-b border-slate-100 pb-4">
                            <span class="text-xs font-bold uppercase tracking-widest text-cyan-600">DISTRIBUSI JALUR</span>
                            <h2 class="text-lg font-extrabold text-brand-darkNavy">Komposisi Jalur Penerimaan</h2>
                            <p class="text-[11px] text-slate-500">Persentase kelulusan berdasarkan jalur seleksi terbaru.</p>
                        </div>

                        <div class="relative w-full h-64 flex items-center justify-center">
                            <canvas id="ptnPieChart"></canvas>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-xs pt-2">
                            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-[#00A8CC] block"></span>
                                <span class="font-bold text-slate-700">SNBP (Prestasi)</span>
                            </div>
                            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-[#0284C7] block"></span>
                                <span class="font-bold text-slate-700">SNBT (UTBK)</span>
                            </div>
                            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-[#0B1E36] block"></span>
                                <span class="font-bold text-slate-700">Mandiri PTN</span>
                            </div>
                            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-[#10B981] block"></span>
                                <span class="font-bold text-slate-700">Kedinasan & LN</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📜 TABEL REKAPITULASI DETAIL KETERSERAPAN PER TAHUN                   -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4 reveal-section">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-600 bg-cyan-50 px-3 py-1 rounded border border-cyan-200">
                        TABEL DATA AKADEMIK
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-darkNavy mt-2">
                        Rekapitulasi Kelulusan per Tahun Ajaran
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500">Rincian kuantitatif distribusi lulusan SMAN 21 Jakarta.</p>
                </div>

                <div class="flex items-center gap-2">
                    <input type="text" id="tableSearchInput" onkeyup="filterTable()" placeholder="Cari tahun ajaran..." class="px-4 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-cyan-600 bg-slate-50 w-full sm:w-64">
                </div>
            </div>

            <div class="school-card rounded-2xl border border-slate-200 overflow-hidden shadow-lg reveal-section delay-100">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse" id="ptnTable">
                        <thead>
                            <tr class="bg-brand-darkNavy text-white font-bold uppercase tracking-wider text-[11px] border-b border-slate-800">
                                <th class="p-4 sm:p-5">Tahun Lulus</th>
                                <th class="p-4 sm:p-5">Jumlah Lulusan</th>
                                <th class="p-4 sm:p-5 text-cyan-400">SNBP (Prestasi)</th>
                                <th class="p-4 sm:p-5 text-blue-400">SNBT (UTBK)</th>
                                <th class="p-4 sm:p-5">Jalur Mandiri</th>
                                <th class="p-4 sm:p-5 text-emerald-400">Kedinasan & LN</th>
                                <th class="p-4 sm:p-5">Total Diterima</th>
                                <th class="p-4 sm:p-5">Persentase PTN</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white font-medium text-slate-700">
                            <?php foreach ($ptnData as $row): 
                                $jml = intval($row['jumlah_lulusan'] ?: 1);
                                $snbp = intval($row['snbp'] ?: 0);
                                $snbt = intval($row['snbt'] ?: 0);
                                $mandiri = intval($row['mandiri'] ?: 0);
                                $kedinasan = intval($row['kedinasan'] ?: 0) + intval($row['ptln'] ?? 0);
                                $totalDiterima = $snbp + $snbt + $mandiri + $kedinasan;
                                $persen = round(($totalDiterima / $jml) * 100, 1);
                            ?>
                            <tr class="hover:bg-cyan-50/40 transition-colors">
                                <td class="p-4 sm:p-5 font-bold text-brand-darkNavy text-sm">
                                    T.A. <?= htmlspecialchars($row['tahun']); ?>
                                </td>
                                <td class="p-4 sm:p-5 text-slate-600">
                                    <?= $jml; ?> Siswa
                                </td>
                                <td class="p-4 sm:p-5 font-extrabold text-cyan-700 bg-cyan-50/50">
                                    <?= $snbp; ?> Siswa
                                </td>
                                <td class="p-4 sm:p-5 font-extrabold text-blue-700 bg-blue-50/50">
                                    <?= $snbt; ?> Siswa
                                </td>
                                <td class="p-4 sm:p-5">
                                    <?= $mandiri; ?> Siswa
                                </td>
                                <td class="p-4 sm:p-5 font-bold text-emerald-700 bg-emerald-50/50">
                                    <?= $kedinasan; ?> Siswa
                                </td>
                                <td class="p-4 sm:p-5 font-extrabold text-brand-darkNavy text-sm">
                                    <?= $totalDiterima; ?> Siswa
                                </td>
                                <td class="p-4 sm:p-5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        <?= $persen; ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🏛️ DESTINASI PTN FAVORIT ALUMNI (PERBAIKAN PATH LOGO KAMPUS)          -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-slate-50 border-b border-slate-200 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-2xl mx-auto mb-12 space-y-2 reveal-section">
                <span class="text-xs font-bold uppercase tracking-widest text-cyan-600">PERGURUAN TINGGI FAVORIT</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy">Destinasi Utama Alumni SMAN 21</h2>
                <p class="text-xs sm:text-sm text-slate-500">Alumni SMAN 21 Jakarta tersebar di berbagai Universitas Negeri terbaik di Indonesia.</p>
                <div class="pt-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-cyan-100 text-cyan-800 text-[11px] font-semibold border border-cyan-200">
                        <i class="fa-solid fa-circle-info text-cyan-600"></i>
                        <span>Klik salah satu logo kampus di bawah untuk memberhentikan / menjalankan animasi</span>
                    </span>
                </div>
            </div>

            <!-- Horizontal Continuous Marquee Container -->
            <div class="relative w-full overflow-hidden py-4">
                <!-- Efek Blur/Gradien Gradual di Samping Kiri & Kanan -->
                <div class="absolute left-0 top-0 bottom-0 w-16 bg-gradient-to-r from-slate-50 to-transparent z-10 pointer-events-none"></div>
                <div class="absolute right-0 top-0 bottom-0 w-16 bg-gradient-to-l from-slate-50 to-transparent z-10 pointer-events-none"></div>

                <div id="marqueeTrack" class="animate-marquee flex gap-6">
                    
                    <!-- ===== SET LOGO KAMPUS 1 ===== -->
                    <div class="school-card p-5 rounded-2xl text-center space-y-3 bg-white border border-slate-200 w-56 flex-shrink-0 cursor-pointer transition-all hover:scale-105 logo-card select-none">
                        <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto p-2">
                            <img src="../uploads/logo-kampus/ui.png" alt="Univ. Indonesia" class="max-h-full max-w-full object-contain">
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-brand-darkNavy">Univ. Indonesia</h3>
                            <p class="text-[10px] text-slate-500 mt-0.5">Depok & Jakarta</p>
                        </div>
                    </div>

                    <div class="school-card p-5 rounded-2xl text-center space-y-3 bg-white border border-slate-200 w-56 flex-shrink-0 cursor-pointer transition-all hover:scale-105 logo-card select-none">
                        <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto p-2">
                            <img src="../uploads/logo-kampus/itb.png" alt="Inst. Teknologi Bandung" class="max-h-full max-w-full object-contain">
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-brand-darkNavy">Inst. Teknologi Bandung</h3>
                            <p class="text-[10px] text-slate-500 mt-0.5">Bandung & Jatinangor</p>
                        </div>
                    </div>

                    <div class="school-card p-5 rounded-2xl text-center space-y-3 bg-white border border-slate-200 w-56 flex-shrink-0 cursor-pointer transition-all hover:scale-105 logo-card select-none">
                        <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto p-2">
                            <img src="../uploads/logo-kampus/ugm.png" alt="Univ. Gadjah Mada" class="max-h-full max-w-full object-contain">
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-brand-darkNavy">Univ. Gadjah Mada</h3>
                            <p class="text-[10px] text-slate-500 mt-0.5">Yogyakarta</p>
                        </div>
                    </div>

                    <div class="school-card p-5 rounded-2xl text-center space-y-3 bg-white border border-slate-200 w-56 flex-shrink-0 cursor-pointer transition-all hover:scale-105 logo-card select-none">
                        <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto p-2">
                            <img src="../uploads/logo-kampus/ipb.png" alt="IPB University" class="max-h-full max-w-full object-contain">
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-brand-darkNavy">IPB University</h3>
                            <p class="text-[10px] text-slate-500 mt-0.5">Bogor</p>
                        </div>
                    </div>

                    <div class="school-card p-5 rounded-2xl text-center space-y-3 bg-white border border-slate-200 w-56 flex-shrink-0 cursor-pointer transition-all hover:scale-105 logo-card select-none">
                        <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto p-2">
                            <img src="../uploads/logo-kampus/unpad.png" alt="Univ. Padjadjaran" class="max-h-full max-w-full object-contain">
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-brand-darkNavy">Univ. Padjadjaran</h3>
                            <p class="text-[10px] text-slate-500 mt-0.5">Jatinangor & Bandung</p>
                        </div>
                    </div>

                    <div class="school-card p-5 rounded-2xl text-center space-y-3 bg-white border border-slate-200 w-56 flex-shrink-0 cursor-pointer transition-all hover:scale-105 logo-card select-none">
                        <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto p-2">
                            <img src="../uploads/logo-kampus/its.png" alt="Inst. Teknologi Sepuluh Nopember" class="max-h-full max-w-full object-contain">
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-brand-darkNavy">Inst. Teknologi Sepuluh</h3>
                            <p class="text-[10px] text-slate-500 mt-0.5">Surabaya</p>
                        </div>
                    </div>

                    <!-- ===== SET LOGO KAMPUS 2 (DUPLIKASI UNTUK SEAMLESS CONTINUOUS LOOP) ===== -->
                    <div class="school-card p-5 rounded-2xl text-center space-y-3 bg-white border border-slate-200 w-56 flex-shrink-0 cursor-pointer transition-all hover:scale-105 logo-card select-none">
                        <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto p-2">
                            <img src="../uploads/logo-kampus/ui.png" alt="Univ. Indonesia" class="max-h-full max-w-full object-contain">
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-brand-darkNavy">Univ. Indonesia</h3>
                            <p class="text-[10px] text-slate-500 mt-0.5">Depok & Jakarta</p>
                        </div>
                    </div>

                    <div class="school-card p-5 rounded-2xl text-center space-y-3 bg-white border border-slate-200 w-56 flex-shrink-0 cursor-pointer transition-all hover:scale-105 logo-card select-none">
                        <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto p-2">
                            <img src="../uploads/logo-kampus/itb.png" alt="Inst. Teknologi Bandung" class="max-h-full max-w-full object-contain">
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-brand-darkNavy">Inst. Teknologi Bandung</h3>
                            <p class="text-[10px] text-slate-500 mt-0.5">Bandung & Jatinangor</p>
                        </div>
                    </div>

                    <div class="school-card p-5 rounded-2xl text-center space-y-3 bg-white border border-slate-200 w-56 flex-shrink-0 cursor-pointer transition-all hover:scale-105 logo-card select-none">
                        <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto p-2">
                            <img src="../uploads/logo-kampus/ugm.png" alt="Univ. Gadjah Mada" class="max-h-full max-w-full object-contain">
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-brand-darkNavy">Univ. Gadjah Mada</h3>
                            <p class="text-[10px] text-slate-500 mt-0.5">Yogyakarta</p>
                        </div>
                    </div>

                    <div class="school-card p-5 rounded-2xl text-center space-y-3 bg-white border border-slate-200 w-56 flex-shrink-0 cursor-pointer transition-all hover:scale-105 logo-card select-none">
                        <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto p-2">
                            <img src="../uploads/logo-kampus/ipb.png" alt="IPB University" class="max-h-full max-w-full object-contain">
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-brand-darkNavy">IPB University</h3>
                            <p class="text-[10px] text-slate-500 mt-0.5">Bogor</p>
                        </div>
                    </div>

                    <div class="school-card p-5 rounded-2xl text-center space-y-3 bg-white border border-slate-200 w-56 flex-shrink-0 cursor-pointer transition-all hover:scale-105 logo-card select-none">
                        <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto p-2">
                            <img src="../uploads/logo-kampus/unpad.png" alt="Univ. Padjadjaran" class="max-h-full max-w-full object-contain">
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-brand-darkNavy">Univ. Padjadjaran</h3>
                            <p class="text-[10px] text-slate-500 mt-0.5">Jatinangor & Bandung</p>
                        </div>
                    </div>

                    <div class="school-card p-5 rounded-2xl text-center space-y-3 bg-white border border-slate-200 w-56 flex-shrink-0 cursor-pointer transition-all hover:scale-105 logo-card select-none">
                        <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto p-2">
                            <img src="../uploads/logo-kampus/its.png" alt="Inst. Teknologi Sepuluh Nopember" class="max-h-full max-w-full object-contain">
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-brand-darkNavy">Inst. Teknologi Sepuluh</h3>
                            <p class="text-[10px] text-slate-500 mt-0.5">Surabaya</p>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🌟 KISAH SUKSES & TESTIMONIAL ALUMNI                                 -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-4 reveal-section">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-600">JEJAK LANGKAH ALUMNI</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-darkNavy mt-1">
                        Kisah Sukses Lulusan SMAN 21
                    </h2>
                </div>
                <a href="../alumni/index.php" class="text-xs font-bold text-cyan-600 hover:underline flex items-center gap-1.5">
                    <span>Lihat Direktori Alumni</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <?php $aIdx = 1; foreach ($alumniList as $alumni): ?>
                <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 space-y-4 flex flex-col justify-between reveal-section delay-<?= $aIdx * 100; ?>">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-cyan-100 text-cyan-700 flex items-center justify-center font-bold text-base border border-cyan-200 flex-shrink-0">
                                <i class="fa-solid fa-user-graduate"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-brand-darkNavy"><?= htmlspecialchars($alumni['nama_alumni']); ?></h3>
                                <p class="text-[11px] text-cyan-600 font-semibold">Lulusan <?= htmlspecialchars($alumni['tahun_lulus']); ?> • <?= htmlspecialchars($alumni['jurusan_program'] ?? 'MIPA'); ?></p>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-[11px] font-semibold text-brand-darkNavy">
                            <i class="fa-solid fa-building-columns text-cyan-600 mr-1.5"></i>
                            <?= htmlspecialchars($alumni['universitas_instansi']); ?>
                        </div>

                        <p class="text-xs text-slate-600 italic leading-relaxed">
                            "<?= htmlspecialchars($alumni['cerita']); ?>"
                        </p>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500 font-medium">
                        <span>Profesi: <?= htmlspecialchars($alumni['profesi'] ?: 'Profesional'); ?></span>
                        <i class="fa-solid fa-circle-check text-emerald-500"></i>
                    </div>
                </div>
                <?php $aIdx++; endforeach; ?>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📢 CTA PERSIAPAN PPDB & AKADEMIK                                      -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-brand-darkNavy text-white relative overflow-hidden">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 reveal-section">
            <div class="p-8 sm:p-12 rounded-2xl bg-gradient-to-r from-brand-darkNavy via-brand-navy to-slate-900 border border-slate-700 text-center space-y-5 shadow-2xl">
                <span class="px-3.5 py-1 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-400/30 text-[11px] font-bold uppercase tracking-wider">
                    FUTURE STARTS HERE
                </span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-white leading-tight">
                    Bersiap Menjadi Bagian dari <br class="hidden sm:inline"> SMA Negeri 21 Jakarta
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                    Bergabunglah bersama kami dan wujudkan cita-cita menembus Perguruan Tinggi Negeri favorit melalui pendidikan unggul, inovatif, dan berkarakter Pancasila.
                </p>
                <div class="pt-3 flex flex-wrap items-center justify-center gap-4">
                    <a href="../informasi/ppdb.php" class="px-7 py-3.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-lg transition-all uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>Informasi SPMB / PPDB</span>
                    </a>
                    <a href="../kontak/index.php" class="px-7 py-3.5 rounded-lg bg-white/10 hover:bg-white/20 text-white font-bold text-xs border border-white/20 transition-all uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-envelope"></i>
                        <span>Konsultasi Akademik</span>
                    </a>
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
                        <li><a href="keterserapan-ptn.php" class="hover:text-cyan-400 transition-colors font-bold text-cyan-400">Keterserapan PTN Alumni</a></li>
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
                <p>Ada yang ingin Anda tanyakan seputar Keterserapan PTN, SNBP, SNBT, Kurikulum, atau PPDB SMAN 21 Jakarta?</p>
            </div>
        </div>

        <div class="p-3 bg-white border-t border-slate-200">
            <form onsubmit="submitAiChat(event)" class="flex gap-2">
                <input type="text" id="aiInput" placeholder="Tanyakan seputar PTN & SMAN 21..." class="flex-1 px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50">
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

        // 3. TABLE SEARCH FILTER
        function filterTable() {
            const input = document.getElementById('tableSearchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('ptnTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let td = tr[i].getElementsByTagName('td')[0];
                if (td) {
                    let txtValue = td.textContent || td.innerText;
                    if (txtValue.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        // 4. CHART.JS VISUALIZATIONS
        const ptnRawData = <?= json_encode($ptnData); ?>;
        
        // Sort ascending for chart chronological display
        const sortedData = [...ptnRawData].reverse();
        const years = sortedData.map(item => 'T.A. ' + item.tahun);
        const snbpData = sortedData.map(item => parseInt(item.snbp || 0));
        const snbtData = sortedData.map(item => parseInt(item.snbt || 0));
        const mandiriData = sortedData.map(item => parseInt(item.mandiri || 0));
        const kedinasanData = sortedData.map(item => parseInt(item.kedinasan || 0) + parseInt(item.ptln || 0));

        // Chart 1: Bar Chart
        const ctx1 = document.getElementById('ptnTrendChart');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: years,
                    datasets: [
                        { label: 'SNBP (Prestasi)', data: snbpData, backgroundColor: '#00A8CC', borderRadius: 5 },
                        { label: 'SNBT (UTBK)', data: snbtData, backgroundColor: '#0284C7', borderRadius: 5 },
                        { label: 'Mandiri', data: mandiriData, backgroundColor: '#0B1E36', borderRadius: 5 },
                        { label: 'Kedinasan & LN', data: kedinasanData, backgroundColor: '#10B981', borderRadius: 5 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { font: { family: 'Plus Jakarta Sans', size: 11 } } }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { grid: { color: '#F1F5F9' } }
                    }
                }
            });
        }

        // Chart 2: Doughnut Chart (Latest Year)
        const ctx2 = document.getElementById('ptnPieChart');
        if (ctx2 && ptnRawData.length > 0) {
            const latest = ptnRawData[0];
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['SNBP', 'SNBT', 'Mandiri', 'Kedinasan'],
                    datasets: [{
                        data: [
                            parseInt(latest.snbp || 0),
                            parseInt(latest.snbt || 0),
                            parseInt(latest.mandiri || 0),
                            parseInt(latest.kedinasan || 0) + parseInt(latest.ptln || 0)
                        ],
                        backgroundColor: ['#00A8CC', '#0284C7', '#0B1E36', '#10B981']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    cutout: '70%'
                }
            });
        }

        // 5. PAUSE / PLAY MARQUEE ANIMASI SAAT KLIK LOGO KAMPUS
        document.querySelectorAll('.logo-card').forEach(card => {
            card.addEventListener('click', function() {
                const track = document.getElementById('marqueeTrack');
                if (track) {
                    track.classList.toggle('paused');
                }
            });
        });

        // 6. AI ASSISTANT PANEL TOGGLE & CHAT AJAX
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

            fetch('keterserapan-ptn.php?action=ai_query', {
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
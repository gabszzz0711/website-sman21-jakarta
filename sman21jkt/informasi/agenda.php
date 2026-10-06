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
function formatAgendaImg($img, $default = '../uploads/tempat/gedungA1.jpg') {
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
    return '../uploads/agenda/' . ltrim($img, '/');
}

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

function getEventStatusBadge($tglMulai, $tglSelesai) {
    $today = date('Y-m-d');
    $start = $tglMulai;
    $end = !empty($tglSelesai) ? $tglSelesai : $tglMulai;

    if ($today < $start) {
        return '<span class="px-2.5 py-1 rounded-full bg-cyan-100 text-cyan-800 text-[10px] font-bold uppercase tracking-wider border border-cyan-200"><i class="fa-regular fa-clock mr-1"></i>Mendatang</span>';
    } elseif ($today >= $start && $today <= $end) {
        return '<span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase tracking-wider border border-emerald-200 animate-pulse"><i class="fa-solid fa-circle-play mr-1"></i>Sedang Berlangsung</span>';
    } else {
        return '<span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold uppercase tracking-wider border border-slate-200"><i class="fa-solid fa-check-double mr-1"></i>Selesai</span>';
    }
}

// =============================================================================
// FETCH DATA PENGATURAN SEKOLAH & AGENDA
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

// Fetch Agenda dari Database
$agendaList = [];
if ($conn) {
    try {
        $stmtAgd = $conn->query("SELECT * FROM agenda WHERE status = 'published' ORDER BY tanggal_mulai ASC");
        if ($stmtAgd) {
            $agendaList = $stmtAgd->fetchAll();
        }
    } catch (PDOException $e) {}
}

// Fallback Data jika Database belum terisi
if (empty($agendaList)) {
    $agendaList = [
        [
            'id' => 101,
            'judul_agenda' => 'Sosialisasi & Pendaftaran PPDB T.A. 2026/2027',
            'slug' => 'sosialisasi-ppdb-2026-2027',
            'deskripsi' => 'Pengarahan alur pendaftaran siswa baru secara online serta verifikasi dokumen pendaftaran bersama tim panitia PPDB SMAN 21 Jakarta.',
            'tanggal_mulai' => '2026-10-10',
            'tanggal_selesai' => '2026-10-15',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '14:00:00',
            'lokasi' => 'Aula Utama & Daring (Zoom Meetings)',
            'gambar' => '../uploads/tempat/gedungA1.jpg',
            'link' => 'ppdb.php',
            'status' => 'published'
        ],
        [
            'id' => 102,
            'judul_agenda' => 'Penilaian Tengah Semester (PTS) Ganjil',
            'slug' => 'penilaian-tengah-semester-ganjil-2026',
            'deskripsi' => 'Pelaksanaan asesmen sumatif tengah semester ganjil berbasis Komputer & Smartphone (CBT Portal SMAN 21).',
            'tanggal_mulai' => '2026-10-20',
            'tanggal_selesai' => '2026-10-28',
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '12:30:00',
            'lokasi' => 'Ruang Kelas SMAN 21 Jakarta',
            'gambar' => '../uploads/tempat/gedungA.jpg',
            'link' => '',
            'status' => 'published'
        ],
        [
            'id' => 103,
            'judul_agenda' => 'Peringatan Hari Sumpah Pemuda & Gelar Karya P5',
            'slug' => 'peringatan-sumpah-pemuda-gelar-karya-p5',
            'deskripsi' => 'Upacara bendera peringatan Hari Sumpah Pemuda dilanjutkan Pameran Proyek Penguatan Profil Pelajar Pancasila (P5) dan Pentas Seni Kreatif.',
            'tanggal_mulai' => '2026-10-28',
            'tanggal_selesai' => '2026-10-28',
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '15:00:00',
            'lokasi' => 'Lapangan Olahraga & Hall Utama SMAN 21',
            'gambar' => '',
            'link' => '',
            'status' => 'published'
        ]
    ];
}

// =============================================================================
// ENDPOINT AJAX UNTUK S21 AI ASSISTANT (CONTEXT: AGENDA & KALENDER)
// =============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'ai_query') {
    header('Content-Type: application/json; charset=utf-8');
    $userQuery = trim($_POST['query'] ?? $_GET['q'] ?? '');
    
    if (empty($userQuery)) {
        echo json_encode(['status' => 'error', 'message' => 'Pertanyaan tidak boleh kosong.']);
        exit;
    }

    $responseAnswer = "";
    $sumberInfo = "Kalender & Agenda SMAN 21 Jakarta";
    $relatedLink = "";

    $qLower = strtolower($userQuery);

    if (strpos($qLower, 'terdekat') !== false || strpos($qLower, 'bulan ini') !== false || strpos($qLower, 'jadwal') !== false || strpos($qLower, 'agenda') !== false) {
        $nextAgd = $agendaList[0] ?? null;
        if ($nextAgd) {
            $responseAnswer = "Agenda mendatang terdekat di SMAN 21 Jakarta adalah: \"" . $nextAgd['judul_agenda'] . "\" yang dijadwalkan pada " . formatTanggalIndoSimple($nextAgd['tanggal_mulai']) . " di " . ($nextAgd['lokasi'] ?: 'Lingkungan Sekolah') . ". " . $nextAgd['deskripsi'];
        } else {
            $responseAnswer = "Saat ini belum ada jadwal agenda besar baru yang diinput dalam kalender akademik sekolah.";
        }
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
                $responseAnswer = "Anda dapat melihat daftar seluruh kalender akademik, jadwal ujian, serta acara sekolah SMAN 21 Jakarta langsung pada tabel/kartu agenda di halaman ini.";
            }
        } else {
            $responseAnswer = "Silakan periksa kartu agenda sekolah di halaman ini untuk jadwal acara, ujian, dan kegiatan mendatang.";
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
    <title>Agenda & Kalender Akademik — SMAN 21 JAKARTA</title>
    <meta name="description" content="Jadwal Resmi Agenda Kegiatan, Kalender Akademik, Ujian, dan Acara Sekolah SMA Negeri 21 Jakarta.">
    
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
                                <a href="berita.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Berita Sekolah</a>
                                <a href="agenda.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 font-bold transition-colors">Agenda & Kalender</a>
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

        <div id="mobileMenu" class="hidden lg:hidden bg-white border-t border-slate-200 px-6 py-6 space-y-4 max-h-[80vh] overflow-y-auto">
            <a href="../index.php" class="block text-xs font-semibold text-slate-700">Beranda</a>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-cyan-600 font-bold tracking-wider">Informasi</span>
                <a href="berita.php" class="block pl-3 py-1 text-xs text-slate-700">Berita Sekolah</a>
                <a href="agenda.php" class="block pl-3 py-1 text-xs font-bold text-cyan-600">Agenda & Kalender</a>
                <a href="ppdb.php" class="block pl-3 py-1 text-xs text-slate-700">PPDB Info</a>
            </div>
            <a href="../prestasi/index.php" class="block text-xs font-semibold text-slate-700">Prestasi</a>
            <a href="../kegiatan/index.php" class="block text-xs font-semibold text-slate-700">Kegiatan & Ekskul</a>
            <a href="../kontak/index.php" class="block text-xs font-semibold text-slate-700">Kontak</a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 🏙️ BANNER & FILTER HEADER                                             -->
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
                <span class="text-cyan-400 font-bold">Agenda & Kalender</span>
            </nav>

            <div class="max-w-3xl mx-auto space-y-3 reveal-section is-visible">
                <span class="inline-block px-3.5 py-1 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 text-xs font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-calendar-days mr-1.5"></i> KALENDER AKADEMIK & ACARA
                </span>
                <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight">
                    Agenda Resmi SMAN 21 Jakarta
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed max-w-2xl mx-auto">
                    Pantau seluruh jadwal kegiatan belajar mengajar, pelaksanaan asesmen/ujian, agenda rapat orang tua, serta event ekstrakurikuler terbaru.
                </p>
            </div>

            <!-- Search & Quick Filter Bar -->
            <div class="max-w-2xl mx-auto pt-4 reveal-section delay-100">
                <div class="p-2 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 shadow-xl flex flex-col sm:flex-row items-center gap-2">
                    <div class="relative flex-1 w-full">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="searchAgendaInput" onkeyup="filterAgendaList()" placeholder="Cari nama agenda / kegiatan..." class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-white text-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-cyan-500">
                    </div>
                    <div class="w-full sm:w-auto flex items-center gap-2">
                        <select id="filterStatusSelect" onchange="filterAgendaList()" class="w-full sm:w-auto px-3.5 py-2.5 rounded-xl bg-slate-800 text-white text-xs border border-slate-700 focus:outline-none focus:border-cyan-500 font-medium">
                            <option value="all">Semua Status</option>
                            <option value="mendatang">Mendatang</option>
                            <option value="sedang">Sedang Berlangsung</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📅 MAIN AGENDA DISPLAY                                                -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            
            <!-- Highlight Next Upcoming Event Banner -->
            <?php if (!empty($agendaList[0])): 
                $firstAgd = $agendaList[0];
                $firstImg = formatAgendaImg($firstAgd['gambar']);
            ?>
            <div class="school-card p-6 sm:p-8 rounded-2xl bg-gradient-to-br from-white to-cyan-50/50 border border-cyan-200 shadow-xl reveal-section">
                <div class="grid lg:grid-cols-12 gap-8 items-center">
                    
                    <div class="lg:col-span-4 relative rounded-xl overflow-hidden aspect-[4/3] bg-slate-100 border border-slate-200 shadow-sm">
                        <img src="<?= htmlspecialchars($firstImg); ?>" alt="<?= htmlspecialchars($firstAgd['judul_agenda']); ?>" class="w-full h-full object-cover">
                        <div class="absolute top-3 left-3">
                            <?= getEventStatusBadge($firstAgd['tanggal_mulai'], $firstAgd['tanggal_selesai']); ?>
                        </div>
                    </div>

                    <div class="lg:col-span-8 space-y-4">
                        <div class="flex items-center gap-2 text-cyan-700 font-extrabold text-xs uppercase tracking-widest">
                            <i class="fa-solid fa-star text-amber-500"></i>
                            <span>AGENDA UTAMA TERDEKAT</span>
                        </div>

                        <h2 class="text-xl sm:text-3xl font-extrabold text-brand-darkNavy leading-snug">
                            <?= htmlspecialchars($firstAgd['judul_agenda']); ?>
                        </h2>

                        <div class="grid sm:grid-cols-2 gap-3 text-xs text-slate-600 bg-white p-4 rounded-xl border border-slate-200/80">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded bg-cyan-100 text-cyan-700 flex items-center justify-center flex-shrink-0 font-bold">
                                    <i class="fa-regular fa-calendar-check"></i>
                                </div>
                                <div>
                                    <span class="font-bold text-slate-800 block">Tanggal Pelaksanaan</span>
                                    <span><?= formatTanggalIndoSimple($firstAgd['tanggal_mulai']); ?><?= !empty($firstAgd['tanggal_selesai']) && $firstAgd['tanggal_selesai'] !== $firstAgd['tanggal_mulai'] ? ' - ' . formatTanggalIndoSimple($firstAgd['tanggal_selesai']) : ''; ?></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded bg-cyan-100 text-cyan-700 flex items-center justify-center flex-shrink-0 font-bold">
                                    <i class="fa-solid fa-clock"></i>
                                </div>
                                <div>
                                    <span class="font-bold text-slate-800 block">Waktu / Jam</span>
                                    <span><?= !empty($firstAgd['jam_mulai']) ? date('H:i', strtotime($firstAgd['jam_mulai'])) . ' WIB' : '07.30 WIB - Selesai'; ?></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 sm:col-span-2">
                                <div class="w-8 h-8 rounded bg-cyan-100 text-cyan-700 flex items-center justify-center flex-shrink-0 font-bold">
                                    <i class="fa-solid fa-location-dot"></i>
                                </div>
                                <div>
                                    <span class="font-bold text-slate-800 block">Lokasi Tempat</span>
                                    <span><?= htmlspecialchars($firstAgd['lokasi'] ?: 'Kampus SMAN 21 Jakarta'); ?></span>
                                </div>
                            </div>
                        </div>

                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            <?= htmlspecialchars($firstAgd['deskripsi']); ?>
                        </p>

                        <div class="pt-2 flex flex-wrap items-center gap-3">
                            <button onclick='openAgendaModal(<?= json_encode($firstAgd); ?>)' class="px-6 py-2.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs shadow-md transition-all flex items-center gap-2">
                                <span>Rincian Lengkap</span>
                                <i class="fa-solid fa-circle-info text-[10px]"></i>
                            </button>
                            <?php if (!empty($firstAgd['link'])): ?>
                            <a href="<?= htmlspecialchars($firstAgd['link']); ?>" class="px-6 py-2.5 rounded-lg bg-brand-darkNavy hover:bg-slate-800 text-white font-bold text-xs shadow transition-all flex items-center gap-2">
                                <span>Tautan Terkait</span>
                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
            <?php endif; ?>

            <!-- Grid Agenda Cards -->
            <div class="space-y-6">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <h3 class="text-lg font-extrabold text-brand-darkNavy">
                        Daftar Kalender Agenda (<span id="countAgenda"><?= count($agendaList); ?></span>)
                    </h3>
                    <span class="text-xs text-slate-500 font-medium">Diurutkan berdasarkan tanggal pelaksanaan</span>
                </div>

                <div id="agendaGrid" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($agendaList as $aIndex => $agd): 
                        $aImg = formatAgendaImg($agd['gambar']);
                        $statusBadge = getEventStatusBadge($agd['tanggal_mulai'], $agd['tanggal_selesai']);
                        $isPast = date('Y-m-d') > ($agd['tanggal_selesai'] ?: $agd['tanggal_mulai']);
                        $statusText = $isPast ? 'selesai' : (date('Y-m-d') < $agd['tanggal_mulai'] ? 'mendatang' : 'sedang');
                    ?>
                    <div class="agenda-item school-card rounded-2xl p-6 bg-white border border-slate-200 flex flex-col justify-between space-y-5 reveal-section delay-<?= ($aIndex % 3 + 1) * 100; ?>" 
                         data-title="<?= htmlspecialchars(strtolower($agd['judul_agenda'])); ?>"
                         data-status="<?= $statusText; ?>">
                        
                        <div class="space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="w-14 h-14 rounded-xl bg-cyan-50 text-cyan-700 border border-cyan-200 flex flex-col items-center justify-center flex-shrink-0 font-bold shadow-sm">
                                    <span class="text-[10px] uppercase tracking-wider"><?= date('M', strtotime($agd['tanggal_mulai'])); ?></span>
                                    <span class="text-lg leading-none mt-0.5"><?= date('d', strtotime($agd['tanggal_mulai'])); ?></span>
                                </div>
                                <div class="text-right">
                                    <?= $statusBadge; ?>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <h4 class="text-sm font-bold text-brand-darkNavy leading-snug line-clamp-2 hover:text-cyan-600 transition-colors">
                                    <?= htmlspecialchars($agd['judul_agenda']); ?>
                                </h4>
                                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                    <?= htmlspecialchars($agd['deskripsi']); ?>
                                </p>
                            </div>
                        </div>

                        <div class="space-y-3 pt-3 border-t border-slate-100 text-[11px] text-slate-600">
                            <div class="flex items-center gap-2">
                                <i class="fa-regular fa-clock text-cyan-600 w-4 text-center"></i>
                                <span><?= !empty($agd['jam_mulai']) ? date('H:i', strtotime($agd['jam_mulai'])) . ' WIB' : '07.30 WIB'; ?></span>
                            </div>

                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-location-dot text-cyan-600 w-4 text-center"></i>
                                <span class="truncate"><?= htmlspecialchars($agd['lokasi'] ?: 'Lingkungan SMAN 21'); ?></span>
                            </div>

                            <button onclick='openAgendaModal(<?= json_encode($agd); ?>)' class="w-full mt-2 py-2 rounded-lg bg-slate-100 hover:bg-cyan-600 hover:text-white text-slate-700 font-bold text-xs transition-colors flex items-center justify-center gap-1.5">
                                <span>Detail Acara</span>
                                <i class="fa-solid fa-chevron-right text-[9px]"></i>
                            </button>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>

                <div id="noAgendaAlert" class="hidden school-card p-12 rounded-2xl text-center text-slate-500 text-xs bg-white">
                    <i class="fa-solid fa-calendar-xmark text-4xl text-slate-300 mb-3 block"></i>
                    <p class="font-bold text-slate-700 text-sm">Tidak ada agenda yang cocok</p>
                    <p class="mt-1">Coba ubah kata kunci pencarian atau filter status agenda Anda.</p>
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
                    Butuh Kepastian Jadwal Ujian atau Kegiatan?
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                    S21 AI Assistant dapat membantu mengecek jadwal kalender akademik, libur sekolah, serta agenda penerimaan siswa baru secara otomatis.
                </p>
                <div class="pt-2">
                    <button onclick="toggleAiPanel()" class="px-7 py-3 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-lg transition-all inline-flex items-center gap-2.5 uppercase tracking-wider">
                        <i class="fa-solid fa-comments text-sm"></i>
                        <span>TANYAKAN AGENDA KE AI</span>
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
                        <li><a href="ppdb.php" class="hover:text-cyan-400 transition-colors">Informasi SPMB / PPDB</a></li>
                        <li><a href="berita.php" class="hover:text-cyan-400 transition-colors">Berita & Pengumuman</a></li>
                        <li><a href="agenda.php" class="text-cyan-400 font-bold">Agenda Kalender Akademik</a></li>
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
    <!-- 🪟 AGENDA DETAIL MODAL                                              -->
    <!-- ===================================================================== -->
    <div id="agendaModal" class="fixed inset-0 z-[9995] bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 opacity-0 pointer-events-none transition-all duration-300">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-8 space-y-5 shadow-2xl relative border border-slate-200 transform scale-95 transition-transform duration-300">
            <button onclick="closeAgendaModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="space-y-2">
                <span id="modalBadge" class="inline-block"></span>
                <h3 id="modalTitle" class="text-lg font-extrabold text-brand-darkNavy leading-snug"></h3>
            </div>

            <div class="space-y-2.5 text-xs text-slate-600 bg-slate-50 p-4 rounded-xl border border-slate-200">
                <div class="flex items-center gap-2">
                    <i class="fa-regular fa-calendar-check text-cyan-600 w-4 text-center"></i>
                    <span id="modalDate" class="font-bold text-slate-800"></span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fa-regular fa-clock text-cyan-600 w-4 text-center"></i>
                    <span id="modalTime"></span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-location-dot text-cyan-600 w-4 text-center"></i>
                    <span id="modalLocation"></span>
                </div>
            </div>

            <div class="space-y-1">
                <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Deskripsi Kegiatan</span>
                <p id="modalDesc" class="text-xs text-slate-700 leading-relaxed"></p>
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <button onclick="closeAgendaModal()" class="px-5 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs transition-colors">
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
                    <span class="text-[10px] text-cyan-400 block font-semibold">Context: Agenda & Kalender</span>
                </div>
            </div>
            <button onclick="toggleAiPanel()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="chatBox" class="flex-1 p-4 overflow-y-auto space-y-4 bg-slate-50 text-xs">
            <div class="p-3.5 rounded-xl bg-white border border-slate-200 text-slate-700 shadow-sm leading-relaxed space-y-2">
                <p class="font-bold text-brand-darkNavy">Halo! Saya S21 AI Assistant. 👋</p>
                <p>Saya siap membantu memberikan informasi mengenai kalender akademik, jadwal ujian, dan acara sekolah terdekat SMAN 21 Jakarta.</p>
                <div class="pt-2 flex flex-wrap gap-1.5">
                    <button onclick="quickAsk('Agenda terdekat bulan ini')" class="px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 text-[10px] font-semibold transition-colors">
                        📅 Agenda Terdekat
                    </button>
                    <button onclick="quickAsk('Jadwal ujian sekolah')" class="px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 text-[10px] font-semibold transition-colors">
                        📝 Jadwal Ujian
                    </button>
                    <button onclick="quickAsk('Informasi PPDB terbaru')" class="px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 text-[10px] font-semibold transition-colors">
                        🎓 Info PPDB
                    </button>
                </div>
            </div>
        </div>

        <div class="p-3 bg-white border-t border-slate-200">
            <form onsubmit="submitAiChat(event)" class="flex gap-2">
                <input type="text" id="aiInput" placeholder="Tanyakan seputar agenda sekolah..." class="flex-1 px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50">
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

        // 3. FILTER AGENDA LIST BY SEARCH & STATUS
        function filterAgendaList() {
            const q = document.getElementById('searchAgendaInput').value.toLowerCase().trim();
            const status = document.getElementById('filterStatusSelect').value;
            const items = document.querySelectorAll('.agenda-item');
            let visibleCount = 0;

            items.forEach(item => {
                const title = item.getAttribute('data-title');
                const itemStatus = item.getAttribute('data-status');

                const matchQuery = !q || title.includes(q);
                const matchStatus = status === 'all' || itemStatus === status;

                if (matchQuery && matchStatus) {
                    item.classList.remove('hidden');
                    visibleCount++;
                } else {
                    item.classList.add('hidden');
                }
            });

            document.getElementById('countAgenda').innerText = visibleCount;
            const alertBox = document.getElementById('noAgendaAlert');
            if (visibleCount === 0) {
                alertBox.classList.remove('hidden');
            } else {
                alertBox.classList.add('hidden');
            }
        }

        // 4. MODAL DETAIL CONTROLLER
        function openAgendaModal(agd) {
            document.getElementById('modalTitle').innerText = agd.judul_agenda || 'Agenda Sekolah';
            document.getElementById('modalDesc').innerText = agd.deskripsi || 'Tidak ada deskripsi rinci.';
            document.getElementById('modalLocation').innerText = agd.lokasi || 'Lingkungan SMAN 21 Jakarta';
            document.getElementById('modalTime').innerText = (agd.jam_mulai ? agd.jam_mulai.substring(0, 5) : '07:30') + ' WIB';
            document.getElementById('modalDate').innerText = agd.tanggal_mulai + (agd.tanggal_selesai && agd.tanggal_selesai !== agd.tanggal_mulai ? ' s.d. ' + agd.tanggal_selesai : '');

            const modal = document.getElementById('agendaModal');
            modal.classList.remove('opacity-0', 'pointer-events-none');
            modal.firstElementChild.classList.remove('scale-95');
            modal.firstElementChild.classList.add('scale-100');
        }

        function closeAgendaModal() {
            const modal = document.getElementById('agendaModal');
            modal.classList.add('opacity-0', 'pointer-events-none');
            modal.firstElementChild.classList.remove('scale-100');
            modal.firstElementChild.classList.add('scale-95');
        }

        // 5. AI ASSISTANT PANEL TOGGLE & CHAT AJAX
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
            loadingMsg.innerText = 'Memeriksa kalender akademik...';
            chatBox.appendChild(loadingMsg);
            chatBox.scrollTop = chatBox.scrollHeight;

            fetch('agenda.php?action=ai_query', {
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
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

// Helper perbaikan path gambar
function formatImgPath($img, $default = '../uploads/logo/sman21jakarta.png') {
    if (empty($img)) return $default;
    $img = trim($img);
    if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
        return $img;
    }
    if (strpos($img, 'uploads/') === 0) {
        return '../' . $img;
    }
    return '../uploads/' . ltrim($img, '/');
}

// =============================================================================
// ENDPOINT AJAX UNTUK S21 AI ASSISTANT (CONTEXT-AWARE: TENAGA PENDIDIK & GURU)
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
    $sumberInfo = "Direktori Guru SMAN 21 Jakarta";
    $relatedLink = "";

    if ($conn) {
        $qLower = strtolower($userQuery);
        $matched = false;

        // Cek pertanyaan spesifik seputar guru dari database
        if (strpos($qLower, 'kepala sekolah') !== false) {
            $stmtKepsek = $conn->query("SELECT nama_guru, jabatan FROM guru WHERE jabatan LIKE '%Kepala Sekolah%' LIMIT 1");
            if ($rowK = $stmtKepsek->fetch()) {
                $responseAnswer = "Kepala SMAN 21 Jakarta saat ini adalah **" . htmlspecialchars($rowK['nama_guru']) . "**. Beliau memimpin komitmen peningkatan mutu akademis dan karakter Pelajar Pancasila.";
                $sumberInfo = "Struktur Pimpinan SMAN 21";
                $relatedLink = "guru.php";
                $matched = true;
            }
        } elseif (strpos($qLower, 'guru') !== false || strpos($qLower, 'pengajar') !== false || strpos($qLower, 'staf') !== false) {
            // Cek pencarian nama mata pelajaran spesifik
            $stmtSearchT = $conn->prepare("SELECT subject, teacher_name FROM teachers WHERE LOWER(subject) LIKE :kw OR LOWER(teacher_name) LIKE :kw ORDER BY subject_order ASC");
            $kw = "%" . $qLower . "%";
            $stmtSearchT->execute(['kw' => $kw]);
            $foundTeachers = $stmtSearchT->fetchAll();

            if (!empty($foundTeachers)) {
                $teachersList = [];
                $subjName = $foundTeachers[0]['subject'];
                foreach ($foundTeachers as $ft) {
                    $teachersList[] = $ft['teacher_name'];
                }
                $responseAnswer = "Berikut adalah pengajar mata pelajaran **" . htmlspecialchars($subjName) . "** di SMAN 21 Jakarta: " . implode(", ", $teachersList) . ".";
                $sumberInfo = "Database Tenaga Pendidik";
                $relatedLink = "guru.php";
                $matched = true;
            }
        }

        // Cek Knowledge Base AI
        if (!$matched) {
            $stmtAiCfg = $conn->query("SELECT pesan_tidak_tersedia FROM ai_pengaturan LIMIT 1");
            $aiCfg = $stmtAiCfg ? $stmtAiCfg->fetch() : null;
            $fallbackMsg = $aiCfg['pesan_tidak_tersedia'] ?? "Maaf, informasi tersebut belum tersedia dalam basis data resmi SMAN 21 Jakarta.";

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
                $responseAnswer = $fallbackMsg;
                $sumberInfo = "Sistem Verifikasi SMAN 21";
            }
        }

    } else {
        $responseAnswer = "SMAN 21 Jakarta didukung oleh lebih dari 50 tenaga pendidik dan kependidikan profesional tersertifikasi yang berdedikasi mendampingi siswa.";
        $sumberInfo = "Informasi Umum SMAN 21";
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
// FETCH DATA RESMI SEKOLAH & GURU DARI DATABASE
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
    'logo' => '../uploads/logo/sman21jakarta.png',
    'hero_bg' => '../uploads/tempat/gedungA1.jpg'
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

// 2. Fetch Pimpinan Sekolah (Kepala Sekolah & Wakil & Staf) dari tabel `guru`
$pimpinanList = [];
if ($conn) {
    try {
        $stmtPimpinan = $conn->query("SELECT g.id, g.nama_guru, g.jabatan, m.nama_mapel 
                                      FROM guru g 
                                      LEFT JOIN mapel m ON g.mapel_id = m.id 
                                      WHERE g.mapel_id IN (1, 2, 4, 6) OR g.jabatan LIKE '%Kepala%' OR g.jabatan LIKE '%Wakil%'
                                      ORDER BY g.mapel_id ASC, g.id ASC");
        if ($stmtPimpinan) $pimpinanList = $stmtPimpinan->fetchAll();
    } catch (PDOException $e) {
        $pimpinanList = [];
    }
}

if (empty($pimpinanList)) {
    $pimpinanList = [
        ['nama_guru' => 'Mutia, M.Pd', 'jabatan' => 'Kepala Sekolah SMAN 21 Jakarta', 'nama_mapel' => 'Kepala Sekolah'],
        ['nama_guru' => 'Muhammad Irfan Septiano, S.Pd', 'jabatan' => 'Wakil Kepala Sekolah Bidang Akademik', 'nama_mapel' => 'Wakil Bidang Akademik'],
        ['nama_guru' => 'Suyamti, M.Pd', 'jabatan' => 'Wakil Kepala Sekolah Bidang Kesiswaan & Humas', 'nama_mapel' => 'Wakil Bidang Kesiswaan & Humas'],
        ['nama_guru' => 'Ika Rahmawatie, S.Pd', 'jabatan' => 'Wakil Kepala Sekolah Bidang Sarpras & Administrasi', 'nama_mapel' => 'Wakil Bidang Sarpras & Administrasi']
    ];
}

// 3. Fetch Data Guru Mata Pelajaran dari tabel `teachers`
$teachersList = [];
$mapelCategories = [];

if ($conn) {
    try {
        $stmtT = $conn->query("SELECT * FROM teachers ORDER BY subject_order ASC, teacher_name ASC");
        if ($stmtT) {
            $teachersList = $stmtT->fetchAll();
            foreach ($teachersList as $t) {
                $subj = trim($t['subject']);
                if (!in_array($subj, $mapelCategories)) {
                    $mapelCategories[] = $subj;
                }
            }
        }
    } catch (PDOException $e) {
        $teachersList = [];
    }
}

// Fallback jika database belum terisi tabel `teachers`
if (empty($teachersList)) {
    $teachersList = [
        ['subject' => 'MATEMATIKA', 'teacher_name' => 'Drs. Saksi Ginting', 'subject_order' => 1],
        ['subject' => 'MATEMATIKA', 'teacher_name' => 'Nani Kusmiyati, M.Pd', 'subject_order' => 1],
        ['subject' => 'MATEMATIKA', 'teacher_name' => 'Alfredo, S.P', 'subject_order' => 1],
        ['subject' => 'MATEMATIKA', 'teacher_name' => 'Dian Fitriyani, S.Si, M.Pd', 'subject_order' => 1],
        ['subject' => 'FISIKA', 'teacher_name' => 'Jundan Iskandar, M.Pd', 'subject_order' => 2],
        ['subject' => 'FISIKA', 'teacher_name' => 'Shahnaz Nadia, M.Pd', 'subject_order' => 2],
        ['subject' => 'BIOLOGI', 'teacher_name' => 'M. Irfan Septiano, S.Pd', 'subject_order' => 3],
        ['subject' => 'BIOLOGI', 'teacher_name' => 'Sofia Cesara, S.Pd', 'subject_order' => 3],
        ['subject' => 'KIMIA', 'teacher_name' => 'Asep Supriadi, M.Pd', 'subject_order' => 4],
        ['subject' => 'KIMIA', 'teacher_name' => 'Ika Rakhmawatie S.Pd', 'subject_order' => 4],
        ['subject' => 'KODING & KA', 'teacher_name' => 'Sutarmin, S.T', 'subject_order' => 5],
        ['subject' => 'BAHASA INDONESIA', 'teacher_name' => 'Mutmainah, M.Pd', 'subject_order' => 6],
        ['subject' => 'BAHASA INDONESIA', 'teacher_name' => 'Suyamti, M.Pd', 'subject_order' => 6],
        ['subject' => 'BAHASA INGGRIS', 'teacher_name' => 'Th. Prima Anggarini, S.Pd', 'subject_order' => 7],
        ['subject' => 'BAHASA INGGRIS', 'teacher_name' => 'Selvia Gumay, M.Pd', 'subject_order' => 7],
        ['subject' => 'SOSIOLOGI', 'teacher_name' => 'Sisri Priantini, S.Pd', 'subject_order' => 8],
        ['subject' => 'EKONOMI', 'teacher_name' => 'Tri Yatmi, S.Pd', 'subject_order' => 9],
        ['subject' => 'GEOGRAFI', 'teacher_name' => 'Musngidah, S.Pd', 'subject_order' => 10],
        ['subject' => 'PENDIDIKAN AGAMA', 'teacher_name' => 'Suherman, M.Pd', 'subject_order' => 11]
    ];
    $mapelCategories = ['MATEMATIKA', 'FISIKA', 'BIOLOGI', 'KIMIA', 'KODING & KA', 'BAHASA INDONESIA', 'BAHASA INGGRIS', 'SOSIOLOGI', 'EKONOMI', 'GEOGRAFI', 'PENDIDIKAN AGAMA'];
}

$logoPath = $sekolah['logo'];
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Daftar Guru & Staf Pendidik — SMA NEGERI 21 JAKARTA</title>
    <meta name="description" content="Direktori Resmi Guru dan Tenaga Pendidik SMAN 21 Jakarta. Pengajar berpengalaman, tersertifikasi, dan berdedikasi tinggi di bidang akademis & sains digital.">
    
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
                            amber: '#D97706',
                            emerald: '#059669',
                            purple: '#7C3AED',
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

        .reveal-section {
            opacity: 0;
            transform: translateY(30px) scale(0.99);
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
            transform: translateY(-5px);
            box-shadow: 0 14px 30px -4px rgba(15, 23, 42, 0.1);
            border-color: #CBD5E1;
        }

        .hero-overlay {
            background: linear-gradient(135deg, rgba(6, 18, 36, 0.92) 0%, rgba(11, 30, 54, 0.85) 60%, rgba(15, 23, 42, 0.88) 100%);
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

                    <!-- Dropdown Profil -->
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

                    <!-- Dropdown Akademik (ACTIVE) -->
                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-lg text-cyan-600 bg-cyan-50 font-bold border border-cyan-200 flex items-center gap-1.5 transition-all">
                            <span>Akademik</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full left-0 w-60 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Overview Akademik</a>
                                <a href="program.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Program Pendidikan</a>
                                <a href="guru.php" class="block px-3 py-2 rounded-lg text-cyan-600 font-bold bg-cyan-50/50 transition-colors">Daftar Guru & Staf</a>
                                <a href="pemeringkatan.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Pemeringkatan Jurusan</a>
                                <a href="keterserapan-ptn.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Keterserapan PTN</a>
                            </div>
                        </div>
                    </div>

                    <!-- Dropdown Kegiatan -->
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

                    <!-- Dropdown Informasi -->
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
            <a href="../index.php" class="block text-slate-700 font-semibold text-xs">Beranda</a>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-cyan-600 font-bold tracking-wider">Akademik</span>
                <a href="index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Akademik</a>
                <a href="program.php" class="block pl-3 py-1 text-xs text-slate-700">Program Pendidikan</a>
                <a href="guru.php" class="block pl-3 py-1 text-xs text-cyan-600 font-bold">Daftar Guru & Staf</a>
                <a href="pemeringkatan.php" class="block pl-3 py-1 text-xs text-slate-700">Pemeringkatan Jurusan</a>
                <a href="keterserapan-ptn.php" class="block pl-3 py-1 text-xs text-slate-700">Keterserapan PTN</a>
            </div>
            <a href="../profil/index.php" class="block text-xs font-semibold text-slate-700">Profil</a>
            <a href="../prestasi/index.php" class="block text-xs font-semibold text-slate-700">Prestasi</a>
            <a href="../kegiatan/index.php" class="block text-xs font-semibold text-slate-700">Kegiatan & Ekskul</a>
            <a href="../informasi/ppdb.php" class="block text-xs font-semibold text-slate-700">PPDB Info</a>
            <a href="../kontak/index.php" class="block text-xs font-semibold text-slate-700">Kontak</a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 🎓 HERO BANNER DIREKTORI GURU & STAF                                  -->
    <!-- ===================================================================== -->
    <section class="relative bg-brand-darkNavy text-white py-20 lg:py-28 overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-1000 scale-105" 
             style="background-image: url('<?= htmlspecialchars($sekolah['hero_bg']); ?>');">
        </div>
        <div class="absolute inset-0 hero-overlay"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl space-y-4 reveal-section">
                <!-- Breadcrumb -->
                <div class="flex items-center gap-2 text-xs text-cyan-400 font-semibold tracking-wide uppercase">
                    <a href="../index.php" class="hover:underline">BERANDA</a>
                    <i class="fa-solid fa-chevron-right text-[9px]"></i>
                    <a href="index.php" class="hover:underline">AKADEMIK</a>
                    <i class="fa-solid fa-chevron-right text-[9px]"></i>
                    <span class="text-white">DAFTAR GURU & STAF</span>
                </div>

                <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight">
                    Tenaga Pendidik <br><span class="text-cyan-400">& Kependidikan</span>
                </h1>
                
                <p class="text-slate-200 text-sm sm:text-base leading-relaxed">
                    Guru-guru profesional, tersertifikasi, dan berdedikasi tinggi di SMAN 21 Jakarta yang siap membimbing siswa dalam meraih keunggulan akademis, keterampilan digital, dan karakter Pancasila.
                </p>

                <!-- Badges -->
                <div class="pt-2 flex flex-wrap gap-2.5">
                    <span class="px-3 py-1 rounded-full bg-cyan-500/20 border border-cyan-400/30 text-cyan-300 text-xs font-bold flex items-center gap-2">
                        <i class="fa-solid fa-user-check"></i> 100% Kualifikasi S1/S2
                    </span>
                    <span class="px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-bold flex items-center gap-2">
                        <i class="fa-solid fa-certificate"></i> Guru Tersertifikasi
                    </span>
                    <span class="px-3 py-1 rounded-full bg-purple-500/20 border border-purple-400/30 text-purple-300 text-xs font-bold flex items-center gap-2">
                        <i class="fa-solid fa-chalkboard-user"></i> Pembimbing Olimpiade & PTN
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📊 STATISTIK TENAGA PENDIDIK                                          -->
    <!-- ===================================================================== -->
    <section class="py-8 bg-white border-b border-slate-200 relative z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 reveal-section">
                    <span class="text-2xl sm:text-3xl font-extrabold text-brand-darkNavy block">60+</span>
                    <span class="text-[11px] sm:text-xs text-slate-500 font-semibold uppercase tracking-wider">Guru & Staf Pendidik</span>
                </div>

                <div class="p-4 rounded-xl bg-cyan-50/60 border border-cyan-200/60 reveal-section delay-100">
                    <span class="text-2xl sm:text-3xl font-extrabold text-cyan-700 block">95%+</span>
                    <span class="text-[11px] sm:text-xs text-cyan-800 font-semibold uppercase tracking-wider">Tersertifikasi Pendidik</span>
                </div>

                <div class="p-4 rounded-xl bg-purple-50/60 border border-purple-200/60 reveal-section delay-200">
                    <span class="text-2xl sm:text-3xl font-extrabold text-purple-700 block">100%</span>
                    <span class="text-[11px] sm:text-xs text-purple-800 font-semibold uppercase tracking-wider">Lulusan PTN Terkemuka</span>
                </div>

                <div class="p-4 rounded-xl bg-emerald-50/60 border border-emerald-200/60 reveal-section delay-300">
                    <span class="text-2xl sm:text-3xl font-extrabold text-emerald-700 block">10+ Thn</span>
                    <span class="text-[11px] sm:text-xs text-emerald-800 font-semibold uppercase tracking-wider">Pengalaman Mengajar</span>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 👑 SECTION 1: UNTURAN PIMPINAN & MANAJEMEN SEKOLAH                     -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            
            <div class="text-center max-w-2xl mx-auto space-y-2 reveal-section">
                <span class="text-xs font-bold uppercase tracking-widest text-cyan-600 bg-cyan-50 px-3 py-1 rounded border border-cyan-200">
                    STRUKTUR ORGANISASI
                </span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy">Pimpinan & Manajemen Sekolah</h2>
                <p class="text-xs sm:text-sm text-slate-500">
                    Tim manajemen pimpinan SMAN 21 Jakarta yang memandu arah kebijakan akademis, kesiswaan, dan tata kelola sekolah.
                </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php $pImpIdx = 1; foreach ($pimpinanList as $pimpinan): 
                    $isKepsek = strpos(strtolower($pimpinan['jabatan']), 'kepala sekolah') !== false;
                ?>
                <div class="school-card rounded-2xl p-6 bg-white border border-slate-200 space-y-4 text-center flex flex-col items-center justify-between reveal-section delay-<?= $pImpIdx * 100; ?>">
                    <div class="w-full space-y-3">
                        <div class="w-20 h-20 rounded-full mx-auto flex items-center justify-center text-2xl font-bold shadow-md <?= $isKepsek ? 'bg-cyan-600 text-white' : 'bg-slate-100 text-brand-darkNavy border border-slate-200'; ?>">
                            <i class="fa-solid <?= $isKepsek ? 'fa-user-tie' : 'fa-chalkboard-user'; ?>"></i>
                        </div>
                        <div>
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider <?= $isKepsek ? 'bg-cyan-100 text-cyan-800' : 'bg-slate-100 text-slate-600'; ?>">
                                <?= htmlspecialchars($pimpinan['nama_mapel'] ?? 'Pimpinan'); ?>
                            </span>
                            <h3 class="text-base font-extrabold text-brand-darkNavy mt-2 leading-snug">
                                <?= htmlspecialchars($pimpinan['nama_guru']); ?>
                            </h3>
                            <p class="text-xs text-slate-500 mt-1 font-medium">
                                <?= htmlspecialchars($pimpinan['jabatan']); ?>
                            </p>
                        </div>
                    </div>

                    <div class="w-full pt-3 border-t border-slate-100 text-[11px] font-semibold text-cyan-600 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-shield-check text-emerald-500"></i>
                        <span>Tim Manajemen Resmi</span>
                    </div>
                </div>
                <?php $pImpIdx++; endforeach; ?>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🔍 SECTION 2: DIREKTORI GURU MATA PELAJARAN                          -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 reveal-section">
                <div class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-widest text-emerald-700 bg-emerald-50 px-3 py-1 rounded border border-emerald-200">
                        DIREKTORI PENGAJAR
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy">Guru Mata Pelajaran</h2>
                    <p class="text-xs sm:text-sm text-slate-500">
                        Cari pengajar berdasarkan nama atau kelompok mata pelajaran Kurikulum Merdeka.
                    </p>
                </div>

                <!-- Live Search Box -->
                <div class="w-full md:w-80 relative">
                    <input type="text" id="teacherSearch" onkeyup="filterTeachers()" placeholder="Cari nama guru atau mata pelajaran..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50 shadow-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                </div>
            </div>

            <!-- Filter Category Tabs -->
            <div class="flex flex-wrap items-center gap-2 pt-2 pb-4 border-b border-slate-200 overflow-x-auto reveal-section">
                <button onclick="filterCategory('ALL')" class="cat-btn px-4 py-2 rounded-lg text-xs font-bold bg-cyan-600 text-white shadow-sm transition-all" data-cat="ALL">
                    Semua Pengajar
                </button>
                <?php foreach ($mapelCategories as $catName): ?>
                <button onclick="filterCategory('<?= htmlspecialchars($catName); ?>')" class="cat-btn px-3.5 py-2 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all" data-cat="<?= htmlspecialchars($catName); ?>">
                    <?= htmlspecialchars($catName); ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Grid Cards Guru -->
            <div id="teacherGrid" class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php $tIdx = 1; foreach ($teachersList as $teacher): 
                    $subjStr = trim($teacher['subject']);
                    $nameStr = trim($teacher['teacher_name']);
                ?>
                <div class="teacher-card school-card rounded-2xl p-5 bg-white border border-slate-200 flex items-start gap-4 transition-all reveal-section" 
                     data-subject="<?= htmlspecialchars($subjStr); ?>" 
                     data-name="<?= strtolower(htmlspecialchars($nameStr)); ?>">
                    
                    <div class="w-12 h-12 rounded-xl bg-cyan-50 border border-cyan-200 text-cyan-700 flex items-center justify-center font-bold text-lg flex-shrink-0">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>

                    <div class="space-y-1 min-w-0 flex-1">
                        <span class="px-2 py-0.5 rounded bg-cyan-100 text-cyan-800 text-[10px] font-bold tracking-wider uppercase inline-block">
                            <?= htmlspecialchars($subjStr); ?>
                        </span>
                        <h3 class="text-xs sm:text-sm font-extrabold text-brand-darkNavy leading-snug truncate" title="<?= htmlspecialchars($nameStr); ?>">
                            <?= htmlspecialchars($nameStr); ?>
                        </h3>
                        <p class="text-[11px] text-slate-500 font-medium">
                            Guru Pengajar SMAN 21
                        </p>
                    </div>

                </div>
                <?php $tIdx++; endforeach; ?>
            </div>

            <!-- Empty Search State -->
            <div id="noTeacherFound" class="hidden p-12 text-center bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                <i class="fa-solid fa-user-slash text-3xl text-slate-400"></i>
                <h4 class="text-sm font-bold text-slate-700">Tidak ada pengajar yang cocok</h4>
                <p class="text-xs text-slate-500">Coba ubah kata kunci pencarian atau pilih filter kategori mata pelajaran yang lain.</p>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🤖 BANNER AI ASSISTANT                                                 -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-slate-900 text-white relative overflow-hidden">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 reveal-section">
            <div class="p-8 sm:p-12 rounded-2xl bg-gradient-to-r from-brand-darkNavy to-slate-800 border border-slate-700 text-center space-y-5 shadow-2xl">
                <div class="w-14 h-14 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center mx-auto text-2xl border border-cyan-500/30">
                    <i class="fa-solid fa-circle-question"></i>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white">
                    Tanyakan Pengajar & Mapel via AI Assistant
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                    S21 AI Assistant siap membantu memberikan informasi mengenai Pengajar Mata Pelajaran, Struktur Pimpinan, dan Guru Pembimbing Olimpiade SMAN 21 Jakarta.
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
    <!-- 🦶 FOOTER RESMI SEKOLAH                                              -->
    <!-- ===================================================================== -->
    <footer class="bg-brand-darkNavy text-slate-400 text-xs py-12 border-t border-slate-800 max-w-full overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <img src="<?= $logoPath; ?>" alt="Logo SMAN 21" class="h-10 w-10 object-contain">
                        <span class="font-extrabold text-white text-base">SMAN 21 JAKARTA</span>
                    </div>
                    <p class="text-[11px] leading-relaxed text-slate-400">
                        Sekolah Menengah Atas Berprestasi, Berkarakter Pancasila, Berbudaya Lingkungan, dan Berbasis Teknologi Modern.
                    </p>
                </div>

                <div>
                    <h4 class="text-white font-bold mb-3">Navigasi Akademik</h4>
                    <ul class="space-y-2 text-[11px]">
                        <li><a href="index.php" class="hover:text-cyan-400 transition-colors">Overview Akademik</a></li>
                        <li><a href="program.php" class="hover:text-cyan-400 transition-colors">Program Pendidikan</a></li>
                        <li><a href="guru.php" class="text-cyan-400 font-bold">Daftar Guru & Staf</a></li>
                        <li><a href="pemeringkatan.php" class="hover:text-cyan-400 transition-colors">Pemeringkatan Jurusan</a></li>
                        <li><a href="keterserapan-ptn.php" class="hover:text-cyan-400 transition-colors">Keterserapan PTN</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-bold mb-3">Layanan Informasi</h4>
                    <ul class="space-y-2 text-[11px]">
                        <li><a href="../profil/index.php" class="hover:text-cyan-400 transition-colors">Profil Sekolah</a></li>
                        <li><a href="../prestasi/index.php" class="hover:text-cyan-400 transition-colors">Prestasi Siswa</a></li>
                        <li><a href="../informasi/ppdb.php" class="hover:text-cyan-400 transition-colors">PPDB Info</a></li>
                        <li><a href="../kontak/index.php" class="hover:text-cyan-400 transition-colors">Kontak Resmi</a></li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <h4 class="text-white font-bold">Media Sosial</h4>
                    <div class="flex gap-2">
                        <a href="<?= htmlspecialchars($sekolah['instagram']); ?>" target="_blank" class="w-8 h-8 rounded bg-slate-800 border border-slate-700 flex items-center justify-center hover:text-cyan-400 transition-colors"><i class="fa-brands fa-instagram"></i></a>
                        <a href="<?= htmlspecialchars($sekolah['youtube']); ?>" target="_blank" class="w-8 h-8 rounded bg-slate-800 border border-slate-700 flex items-center justify-center hover:text-cyan-400 transition-colors"><i class="fa-brands fa-youtube"></i></a>
                    </div>
                </div>

            </div>

            <div class="pt-8 border-t border-slate-800 flex flex-col sm:flex-row justify-between items-center text-[10px] text-slate-500">
                <p>&copy; <?= date('Y'); ?> SMA Negeri 21 Jakarta. Hak Cipta Dilindungi Undang-Undang.</p>
                <p class="mt-2 sm:mt-0">Portal Resmi Informasi SMA Negeri 21 Jakarta</p>
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
                <p>Kamu sedang melihat **Daftar Guru & Staf**. Ada yang ingin ditanyakan seputar pengajar mata pelajaran, wakil kepala sekolah, atau pembimbing olimpiade?</p>
            </div>
        </div>

        <div class="p-3 bg-white border-t border-slate-200">
            <form onsubmit="submitAiChat(event)" class="flex gap-2">
                <input type="text" id="aiInput" placeholder="Tanyakan seputar Guru SMAN 21..." class="flex-1 px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50">
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
        let currentCategory = 'ALL';

        // 1. SCROLL REVEAL OBSERVER
        document.addEventListener('DOMContentLoaded', () => {
            const observerOptions = {
                root: null,
                rootMargin: '0px 0px -50px 0px',
                threshold: 0.12
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

        // 2. MOBILE MENU TOGGLE
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            if (menu) menu.classList.toggle('hidden');
        }

        // 3. FILTERING PENCAIRAN GURU & KATEGORI
        function filterTeachers() {
            const searchVal = document.getElementById('teacherSearch').value.toLowerCase().trim();
            const cards = document.querySelectorAll('.teacher-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const name = card.getAttribute('data-name') || '';
                const subject = card.getAttribute('data-subject') || '';

                const matchesSearch = name.includes(searchVal) || subject.toLowerCase().includes(searchVal);
                const matchesCategory = (currentCategory === 'ALL' || subject === currentCategory);

                if (matchesSearch && matchesCategory) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            const noFound = document.getElementById('noTeacherFound');
            if (visibleCount === 0) {
                noFound.classList.remove('hidden');
            } else {
                noFound.classList.add('hidden');
            }
        }

        function filterCategory(catName) {
            currentCategory = catName;

            // Update tab styles
            document.querySelectorAll('.cat-btn').forEach(btn => {
                const btnCat = btn.getAttribute('data-cat');
                if (btnCat === catName) {
                    btn.className = 'cat-btn px-4 py-2 rounded-lg text-xs font-bold bg-cyan-600 text-white shadow-sm transition-all';
                } else {
                    btn.className = 'cat-btn px-3.5 py-2 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all';
                }
            });

            filterTeachers();
        }

        // 4. AI ASSISTANT PANEL TOGGLE & CHAT AJAX
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
            loadingMsg.innerText = 'Mencari direktori pengajar...';
            chatBox.appendChild(loadingMsg);
            chatBox.scrollTop = chatBox.scrollHeight;

            fetch('guru.php?action=ai_query', {
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

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
            if (strpos($qLower, 'ppdb') !== false || strpos($qLower, 'daftar') !== false) {
                $responseAnswer = "Informasi PPDB/SPMB SMAN 21 Jakarta dapat diakses melalui menu Informasi > PPDB Info.";
                $sumberInfo = "Informasi PPDB Resmi";
                $relatedLink = "../informasi/ppdb.php";
                $matched = true;
            } elseif (strpos($qLower, 'alamat') !== false || strpos($qLower, 'kontak') !== false || strpos($qLower, 'lokasi') !== false) {
                $responseAnswer = "SMAN 21 Jakarta berlokasi di Jl. Tanah Mas Raya No.1, Kayu Putih, Pulogadung, Jakarta Timur. Telepon: (021) 4892346.";
                $sumberInfo = "Kontak Sekolah";
                $relatedLink = "index.php";
                $matched = true;
            }
        }

        if (!$matched) {
            $responseAnswer = $fallbackMsg;
            $sumberInfo = "Sistem Verifikasi SMAN 21";
        }

    } else {
        $responseAnswer = "SMAN 21 Jakarta berlokasi di Jl. Tanah Mas Raya No.1, Kayu Putih, Pulogadung, Jakarta Timur. Silakan ajukan pertanyaan seputar Profil, Akademik, PPDB, atau Kontak Sekolah.";
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
// PROSES PENGIRIMAN PESAN KONTAK / ASPIRASI
// =============================================================================
$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_pesan'])) {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $subjek = trim($_POST['subjek'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');

    if (!empty($nama) && !empty($email) && !empty($pesan)) {
        if ($conn) {
            try {
                // Mencoba simpan pesan jika tabel pesan_kontak tersedia
                $stmtIns = $conn->prepare("INSERT INTO pesan_kontak (nama, email, telepon, subjek, pesan, tanggal_kirim) VALUES (:nama, :email, :telepon, :subjek, :pesan, NOW())");
                $stmtIns->execute([
                    ':nama' => $nama,
                    ':email' => $email,
                    ':telepon' => $telepon,
                    ':subjek' => $subjek,
                    ':pesan' => $pesan
                ]);
            } catch (PDOException $e) {
                // Abaikan jika tabel belum terbuat
            }
        }
        $successMsg = "Terima kasih, <strong>" . htmlspecialchars($nama) . "</strong>! Pesan dan pertanyaan Anda berhasil dikirim ke Humas SMAN 21 Jakarta. Tim kami akan merespons melalui email/telepon Anda.";
    } else {
        $errorMsg = "Mohon lengkapi Nama, Email, dan Isi Pesan Anda.";
    }
}

// =============================================================================
// FETCH DATA SEKOLAH DARI DATABASE
// =============================================================================
$sekolah = [
    'nama_sekolah' => 'SMAN 21 Jakarta',
    'slogan' => 'Where Future Begins • Unggul, Berkarakter, Berteknologi',
    'akreditasi' => 'A',
    'alamat' => 'Jl. Tanah Mas Raya No.1, RT.3/RW.1, Kayu Putih, Kec. Pulo Gadung, Kota Jakarta Timur, DKI Jakarta 13210',
    'telepon' => '(021) 4892346',
    'email' => 'info@sman21jkt.sch.id',
    'instagram' => 'https://instagram.com/sman21jakarta',
    'youtube' => 'https://youtube.com/sman21jkt',
    'logo' => '../uploads/logo/sman21jakarta.png',
    'jam_operasional' => 'Senin - Jumat: 06.30 - 15.00 WIB'
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
    <title>Kontak & Lokasi — SMA NEGERI 21 JAKARTA</title>
    <meta name="description" content="Hubungi dan kunjungi SMA Negeri 21 Jakarta. Dapatkan informasi alamat resmi, nomor telepon, email, serta kirim pertanyaan atau layanan informasi.">
    
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
                            redHover: '#B91C1C',
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

        /* ANIMASI REVEAL SMOOTH ON SCROLL */
        .reveal-section {
            opacity: 0;
            transform: translateY(28px) scale(0.99);
            transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
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

        .contact-hero-overlay {
            background: linear-gradient(135deg, rgba(6, 18, 36, 0.94) 0%, rgba(11, 30, 54, 0.88) 60%, rgba(15, 23, 42, 0.92) 100%);
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
                    <img src="<?= $logoPath; ?>" alt="SMAN 21 Jakarta Logo" class="h-11 w-11 object-contain group-hover:scale-105 transition-transform" onerror="this.onerror=null; this.src='../uploads/logo/sman21jakarta.png';">
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
                                <a href="../gallery/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Galeri Foto</a>
                                <a href="index.php" class="block px-3 py-2 rounded-lg bg-cyan-50 text-cyan-700 font-bold transition-colors">Kontak & Lokasi</a>
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

        <!-- Mobile Drawer Menu -->
        <div id="mobileMenu" class="hidden lg:hidden bg-white border-t border-slate-200 px-6 py-6 space-y-4 max-h-[80vh] overflow-y-auto">
            <a href="../index.php" class="block text-slate-700 font-semibold text-xs">Beranda</a>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Profil</span>
                <a href="../profil/index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Profil</a>
                <a href="../profil/sejarah.php" class="block pl-3 py-1 text-xs text-slate-700">Sejarah</a>
                <a href="../profil/visi-misi.php" class="block pl-3 py-1 text-xs text-slate-700">Visi & Misi</a>
                <a href="../profil/sambutan.php" class="block pl-3 py-1 text-xs text-slate-700">Sambutan Kepala Sekolah</a>
            </div>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Akademik</span>
                <a href="../akademik/index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Akademik</a>
                <a href="../akademik/guru.php" class="block pl-3 py-1 text-xs text-slate-700">Daftar Guru</a>
                <a href="../akademik/keterserapan-ptn.php" class="block pl-3 py-1 text-xs text-slate-700">Keterserapan PTN</a>
            </div>
            <a href="../prestasi/index.php" class="block text-xs font-semibold text-slate-700">Prestasi</a>
            <a href="../kegiatan/index.php" class="block text-xs font-semibold text-slate-700">Kegiatan & Ekskul</a>
            <a href="../informasi/ppdb.php" class="block text-xs font-semibold text-slate-700">PPDB Info</a>
            <a href="index.php" class="block text-xs font-bold text-cyan-600">Kontak & Lokasi</a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 🏢 HERO BANNER PAGE HEADER KONTAK                                      -->
    <!-- ===================================================================== -->
    <section class="relative bg-brand-darkNavy text-white py-20 lg:py-24 overflow-hidden max-w-full">
        <div class="absolute inset-0 bg-cover bg-center opacity-20" style="background-image: url('../uploads/tempat/gedungA1.jpg');"></div>
        <div class="absolute inset-0 contact-hero-overlay"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <div class="max-w-3xl mx-auto space-y-4 reveal-section">
                
                <!-- Breadcrumbs -->
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-cyan-500/20 border border-cyan-400/30 text-cyan-300 text-xs font-semibold">
                    <a href="../index.php" class="hover:underline">Beranda</a>
                    <i class="fa-solid fa-chevron-right text-[9px] opacity-70"></i>
                    <span>Informasi</span>
                    <i class="fa-solid fa-chevron-right text-[9px] opacity-70"></i>
                    <span class="text-white font-bold">Kontak & Lokasi</span>
                </div>

                <h1 class="text-3xl sm:text-5xl font-extrabold text-white leading-tight tracking-tight">
                    Hubungi & Kunjungi <br>
                    <span class="text-cyan-400">SMAN 21 Jakarta</span>
                </h1>

                <p class="text-xs sm:text-base text-slate-300 font-normal leading-relaxed max-w-2xl mx-auto">
                    Pusat layanan informasi publik, konsultasi penerimaan siswa baru (PPDB/SPMB), hubungan masyarakat, serta peta lokasi kampus SMAN 21 Jakarta.
                </p>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📞 CARDS INFORMASI KONTAK UTAMA                                       -->
    <!-- ===================================================================== -->
    <section class="relative z-20 -mt-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- Card Alamat -->
            <div class="school-card p-6 rounded-2xl border-t-4 border-cyan-500 flex flex-col justify-between reveal-section delay-100">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <h3 class="text-sm font-extrabold text-brand-darkNavy uppercase tracking-wider">Alamat Sekolah</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        <?= htmlspecialchars($sekolah['alamat']); ?>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 mt-4">
                    <span class="text-[11px] font-bold text-cyan-600 flex items-center gap-1.5">
                        <i class="fa-solid fa-map-pin text-[10px]"></i> Jakarta Timur, DKI Jakarta
                    </span>
                </div>
            </div>

            <!-- Card Telepon & WhatsApp -->
            <div class="school-card p-6 rounded-2xl border-t-4 border-blue-600 flex flex-col justify-between reveal-section delay-200">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid fa-phone-volume"></i>
                    </div>
                    <h3 class="text-sm font-extrabold text-brand-darkNavy uppercase tracking-wider">Telepon & Fax</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Telepon Kantor: <strong class="text-slate-800"><?= htmlspecialchars($sekolah['telepon']); ?></strong><br>
                        Layanan Humas: <strong class="text-slate-800">(021) 4892346</strong>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 mt-4">
                    <a href="tel:0214892346" class="text-[11px] font-bold text-blue-600 hover:underline flex items-center gap-1.5">
                        <i class="fa-solid fa-headset text-[10px]"></i> Hubungi Sekarang
                    </a>
                </div>
            </div>

            <!-- Card Email Resmi -->
            <div class="school-card p-6 rounded-2xl border-t-4 border-emerald-600 flex flex-col justify-between reveal-section delay-300">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid fa-envelope-open-text"></i>
                    </div>
                    <h3 class="text-sm font-extrabold text-brand-darkNavy uppercase tracking-wider">Email Surat</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Email Utama: <strong class="text-slate-800"><?= htmlspecialchars($sekolah['email']); ?></strong><br>
                        Surat Kedinasan / MOU
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 mt-4">
                    <a href="mailto:<?= htmlspecialchars($sekolah['email']); ?>" class="text-[11px] font-bold text-emerald-600 hover:underline flex items-center gap-1.5">
                        <i class="fa-solid fa-paper-plane text-[10px]"></i> Kirim Email
                    </a>
                </div>
            </div>

            <!-- Card Jam Operasional -->
            <div class="school-card p-6 rounded-2xl border-t-4 border-purple-600 flex flex-col justify-between reveal-section delay-400">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <h3 class="text-sm font-extrabold text-brand-darkNavy uppercase tracking-wider">Jam Pelayanan</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        <?= htmlspecialchars($sekolah['jam_operasional']); ?><br>
                        <span class="text-red-500 font-medium">Sabtu - Minggu: Libur</span>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 mt-4">
                    <span class="text-[11px] font-bold text-purple-600 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-info text-[10px]"></i> Pelayanan Tata Usaha
                    </span>
                </div>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- ✉️ FORM PESAN & PETA LOKASI INTERAKTIF                                -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-slate-50 border-b border-slate-200 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-12 items-start">
                
                <!-- Form Layanan Informasi & Aspirasi -->
                <div class="lg:col-span-7 reveal-section">
                    <div class="school-card p-6 sm:p-10 rounded-2xl bg-white border border-slate-200 shadow-xl space-y-6">
                        
                        <div>
                            <span class="text-xs font-bold uppercase tracking-widest text-cyan-600 bg-cyan-50 px-3 py-1 rounded border border-cyan-200">
                                FORMULIR LAYANAN & ASPIRASI
                            </span>
                            <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-darkNavy mt-2">
                                Kirim Pertanyaan atau Pesan
                            </h2>
                            <p class="text-xs text-slate-500 mt-1">
                                Silakan isi formulir di bawah ini. Tim Humas SMAN 21 Jakarta akan merespons pesan Anda secepatnya.
                            </p>
                        </div>

                        <?php if (!empty($successMsg)): ?>
                        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-start gap-3 shadow-sm">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-lg mt-0.5"></i>
                            <div><?= $successMsg; ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($errorMsg)): ?>
                        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs flex items-start gap-3 shadow-sm">
                            <i class="fa-solid fa-circle-xmark text-red-600 text-lg mt-0.5"></i>
                            <div><?= $errorMsg; ?></div>
                        </div>
                        <?php endif; ?>

                        <form action="index.php" method="POST" class="space-y-4">
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-brand-darkNavy mb-1.5">
                                        Nama Lengkap <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="nama" required placeholder="Contoh: Budi Santoso" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50/50 transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-brand-darkNavy mb-1.5">
                                        Email Aktif <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" name="email" required placeholder="nama@email.com" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50/50 transition-all">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-brand-darkNavy mb-1.5">
                                        No. Telepon / WhatsApp
                                    </label>
                                    <input type="tel" name="telepon" placeholder="0812xxxxxxxx" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50/50 transition-all">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-brand-darkNavy mb-1.5">
                                        Kategori Pertanyaan
                                    </label>
                                    <select name="subjek" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50/50 transition-all">
                                        <option value="Informasi PPDB / SPMB">Informasi PPDB / SPMB</option>
                                        <option value="Informasi Kurikulum & Akademik">Informasi Kurikulum & Akademik</option>
                                        <option value="Layanan Tata Usaha & Surat">Layanan Tata Usaha & Surat</option>
                                        <option value="Kerjasama / MOU / Undangan">Kerjasama / MOU / Undangan</option>
                                        <option value="Saran & Masukan">Saran & Masukan</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-brand-darkNavy mb-1.5">
                                    Isi Pesan / Pertanyaan <span class="text-red-500">*</span>
                                </label>
                                <textarea name="pesan" rows="5" required placeholder="Tuliskan pertanyaan atau keluhan Anda secara rinci di sini..." class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50/50 transition-all"></textarea>
                            </div>

                            <button type="submit" name="submit_pesan" class="w-full py-3.5 px-6 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs uppercase tracking-wider shadow-lg hover:shadow-cyan-600/30 transition-all flex items-center justify-center gap-2">
                                <i class="fa-solid fa-paper-plane"></i>
                                <span>KIRIM PESAN SEKARANG</span>
                            </button>

                        </form>

                    </div>
                </div>

                <!-- Google Maps & Aksesibilitas -->
                <div class="lg:col-span-5 space-y-6 reveal-section delay-200">
                    
                    <div class="school-card p-2 rounded-2xl bg-white border border-slate-200 shadow-lg overflow-hidden">
                        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-bold text-brand-darkNavy uppercase">Peta Poin Google Maps</h3>
                                <p class="text-[11px] text-slate-500">SMAN 21 Jakarta • Kayu Putih, Pulogadung</p>
                            </div>
                            <a href="https://maps.google.com/?q=SMA+Negeri+21+Jakarta" target="_blank" class="px-3 py-1 rounded bg-cyan-50 text-cyan-600 font-bold text-[10px] hover:bg-cyan-100 transition-colors">
                                Buka Google Maps <i class="fa-solid fa-arrow-up-right-from-square text-[8px] ml-1"></i>
                            </a>
                        </div>
                        <div class="w-full h-80 rounded-xl overflow-hidden">
                            <iframe 
                                class="w-full h-full border-0"
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.602283183597!2d106.8770143!3d-6.1839542!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f4e242a5bb01%3A0x6b44319409890251!2sSMA%20Negeri%2021%20Jakarta!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                    </div>

                    <!-- Panduan Rute Transportasi -->
                    <div class="school-card p-6 rounded-2xl bg-white border border-slate-200 space-y-4">
                        <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                            <div class="w-9 h-9 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center text-base">
                                <i class="fa-solid fa-bus-simple"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-brand-darkNavy uppercase">Panduan Rute Transportasi</h3>
                                <p class="text-[10px] text-slate-500">Akses mudah menuju lokasi sekolah</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-xs text-slate-600">
                            <div class="flex items-start gap-2.5">
                                <i class="fa-solid fa-train-subway text-cyan-600 mt-1"></i>
                                <div>
                                    <strong class="text-slate-800 block">LRT Jakarta:</strong>
                                    Stasiun LRT Kayu Putih / Boulevard Selatan (Lanjut angkutan umum/ojek ±5 menit).
                                </div>
                            </div>

                            <div class="flex items-start gap-2.5">
                                <i class="fa-solid fa-bus text-cyan-600 mt-1"></i>
                                <div>
                                    <strong class="text-slate-800 block">Transjakarta Busway:</strong>
                                    Halte Kayu Putih / Pemuda Rawamangun, kemudian melanjutkan ke arah Jl. Tanah Mas Raya.
                                </div>
                            </div>

                            <div class="flex items-start gap-2.5">
                                <i class="fa-solid fa-car text-cyan-600 mt-1"></i>
                                <div>
                                    <strong class="text-slate-800 block">Kendaraan Pribadi:</strong>
                                    Akses via Jalan Raya Jenderal Ahmad Yani / Jalan Pemuda Pulogadung. Area parkir aman & teratur.
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- ❓ SECTION FAQ - PERTANYAAN POPULER KONTAK                             -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-white border-b border-slate-200 overflow-hidden">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            
            <div class="text-center space-y-2 mb-12 reveal-section">
                <span class="text-xs font-bold uppercase tracking-widest text-cyan-600 bg-cyan-50 px-3 py-1 rounded border border-cyan-200">FREQUENTLY ASKED QUESTIONS</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-darkNavy">Pertanyaan yang Sering Diajukan</h2>
                <p class="text-xs text-slate-500">Informasi ringkas seputar kunjungan dan kontak administrasi SMAN 21 Jakarta.</p>
            </div>

            <div class="space-y-4 reveal-section delay-100">
                
                <div class="school-card rounded-xl border border-slate-200 overflow-hidden">
                    <button onclick="toggleFaq('faq1')" class="w-full p-5 text-left font-bold text-xs sm:text-sm text-brand-darkNavy flex items-center justify-between gap-4 bg-slate-50/50 hover:bg-slate-100 transition-colors">
                        <span>Bagaimana cara mengajukan permohonan legalisir ijazah atau surat keterangan?</span>
                        <i id="faq1-icon" class="fa-solid fa-chevron-down text-cyan-600 transition-transform"></i>
                    </button>
                    <div id="faq1" class="hidden p-5 border-t border-slate-200 text-xs text-slate-600 leading-relaxed bg-white">
                        Pemohon dapat datang langsung ke Loket Tata Usaha SMAN 21 Jakarta pada jam kerja operasional (Senin - Jumat 07.30 - 14.30 WIB) dengan membawa fotokopi ijazah asli dan dokumen identitas.
                    </div>
                </div>

                <div class="school-card rounded-xl border border-slate-200 overflow-hidden">
                    <button onclick="toggleFaq('faq2')" class="w-full p-5 text-left font-bold text-xs sm:text-sm text-brand-darkNavy flex items-center justify-between gap-4 bg-slate-50/50 hover:bg-slate-100 transition-colors">
                        <span>Apakah orang tua calon siswa bisa berkunjung untuk melihat fasilitas sekolah?</span>
                        <i id="faq2-icon" class="fa-solid fa-chevron-down text-cyan-600 transition-transform"></i>
                    </button>
                    <div id="faq2" class="hidden p-5 border-t border-slate-200 text-xs text-slate-600 leading-relaxed bg-white">
                        Bisa. Kunjungan open house / konsultasi dapat dilakukan dengan melapor terlebih dahulu di pos keamanan gerbang utama sekolah pada hari kerja.
                    </div>
                </div>

                <div class="school-card rounded-xl border border-slate-200 overflow-hidden">
                    <button onclick="toggleFaq('faq3')" class="w-full p-5 text-left font-bold text-xs sm:text-sm text-brand-darkNavy flex items-center justify-between gap-4 bg-slate-50/50 hover:bg-slate-100 transition-colors">
                        <span>Ke manakah saya harus menghubungi terkait informasi pendaftaran siswa baru (PPDB)?</span>
                        <i id="faq3-icon" class="fa-solid fa-chevron-down text-cyan-600 transition-transform"></i>
                    </button>
                    <div id="faq3" class="hidden p-5 border-t border-slate-200 text-xs text-slate-600 leading-relaxed bg-white">
                        Anda dapat mengakses portal Informasi PPDB pada menu <a href="../informasi/ppdb.php" class="text-cyan-600 font-bold hover:underline">PPDB Info</a>, menanyakan via S21 AI Assistant di pojok bawah kanan, atau menghubungi Panitia PPDB di loket sekolah.
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
                    <i class="fa-solid fa-circle-question"></i>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white">
                    Punya Pertanyaan Langsung?
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                    S21 AI Assistant siap menjawab secara otomatis pertanyaan seputar Syarat PPDB, Kurikulum, Daftar Guru, Pemeringkatan, dan Fasilitas Sekolah.
                </p>
                <div class="pt-2">
                    <button onclick="toggleAiPanel()" class="px-7 py-3 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-lg transition-all inline-flex items-center gap-2.5 uppercase tracking-wider">
                        <i class="fa-solid fa-comments text-sm"></i>
                        <span>TANYAKAN SEKARANG VIA AI</span>
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
                        <img src="<?= $logoPath; ?>" alt="Logo SMAN 21" class="h-10 w-10 object-contain" onerror="this.onerror=null; this.src='../uploads/logo/sman21jakarta.png';">
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
                        <li><a href="../informasi/agenda.php" class="hover:text-cyan-400 transition-colors">Agenda Kalender Akademik</a></li>
                        <li><a href="../akademik/keterserapan-ptn.php" class="hover:text-cyan-400 transition-colors">Keterserapan PTN Alumni</a></li>
                        <li><a href="index.php" class="hover:text-cyan-400 transition-colors">Kontak & Pusat Bantuan</a></li>
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
                <p>Ada yang bisa saya bantu terkait Lokasi, Kontak, SPMB/PPDB, Kurikulum, atau Kegiatan SMAN 21 Jakarta?</p>
            </div>
        </div>

        <div class="p-3 bg-white border-t border-slate-200">
            <form onsubmit="submitAiChat(event)" class="flex gap-2">
                <input type="text" id="aiInput" placeholder="Tanyakan seputar SMAN 21..." class="flex-1 px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:border-cyan-600 text-xs bg-slate-50">
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

        // 3. FAQ ACCORDION TOGGLE
        function toggleFaq(id) {
            const content = document.getElementById(id);
            const icon = document.getElementById(id + '-icon');
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.classList.add('rotate-180');
            } else {
                content.classList.add('hidden');
                icon.classList.remove('rotate-180');
            }
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
            loadingMsg.innerText = 'Mencari jawaban resmi...';
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
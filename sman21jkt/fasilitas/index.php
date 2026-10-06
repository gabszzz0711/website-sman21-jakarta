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

// Path basis relatif untuk file di dalam subfolder fasilitas/
$base = '../';
$logoPath = $base . 'uploads/logo/sman21jakarta.png';

// =============================================================================
// HELPER FORMAT GAMBAR FASILITAS
// =============================================================================
function formatFasilitasImg($img, $base = '../') {
    $default = $base . 'uploads/tempat/gedungA1.jpg';
    if (empty($img)) return $default;
    $img = trim($img);
    
    if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
        return $img;
    }
    
    if (strpos($img, 'uploads/') === 0) {
        return $base . ltrim($img, '/');
    }
    
    if (strpos($img, '../uploads/') === 0) {
        return $img;
    }
    
    return $base . 'uploads/fasilitas/' . ltrim($img, '/');
}

// =============================================================================
// FETCH DATA SEKOLAH DARI DATABASE
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
    'hero_bg' => $base . 'uploads/tempat/gedungA1.jpg'
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

// =============================================================================
// FETCH DATA FASILITAS DARI DATABASE
// =============================================================================
$fasilitas = [];
if ($conn) {
    try {
        $stmtFas = $conn->query("SELECT * FROM fasilitas WHERE status_aktif = 1 ORDER BY urutan ASC, id DESC");
        if ($stmtFas) {
            $fasilitas = $stmtFas->fetchAll();
        }
    } catch (PDOException $e) {
        try {
            $stmtFas = $conn->query("SELECT * FROM fasilitas ORDER BY id DESC");
            if ($stmtFas) {
                $fasilitas = $stmtFas->fetchAll();
            }
        } catch (PDOException $ex) {
            $fasilitas = [];
        }
    }
}

// Data Fallback jika Database belum terisi
if (empty($fasilitas)) {
    $fasilitas = [
        [
            'id' => 1,
            'nama_fasilitas' => 'Ruang Kelas Digital & Interactive Smart TV',
            'kategori' => 'Ruang Belajar',
            'deskripsi' => 'Setiap ruang kelas dilengkapi dengan pendingin ruangan (AC), layar Proyektor/Interactive Smart Board, jaringan Wi-Fi sekolah, serta pencahayaan alami standar nasional.',
            'gambar' => $base . 'uploads/tempat/gedungA.jpg',
            'kondisi' => 'Sangat Baik',
            'kapasitas' => '36 Siswa / Kelas',
            'fitur' => 'AC 2 PK, Smart Board 75 Inch, Akses LAN & Wi-Fi High Speed, Sound System'
        ],
        [
            'id' => 2,
            'nama_fasilitas' => 'Laboratorium Komputer & AI Center',
            'kategori' => 'Laboratorium',
            'deskripsi' => 'Laboratorium komputer modern dengan spesifikasi PC generasi terbaru untuk menunjang pembelajaran Koding, Pemrograman Web, Multimedia, dan Asesmen Berbasis Komputer.',
            'gambar' => $base . 'uploads/tempat/gedungA1.jpg',
            'kondisi' => 'Sangat Baik',
            'kapasitas' => '40 Unit PC',
            'fitur' => 'PC Intel Core i7 / 16GB RAM, Fiber Optic Internet 1 Gbps, Server CBT Mandiri'
        ],
        [
            'id' => 3,
            'nama_fasilitas' => 'Laboratorium IPA Terpadu (Fisika, Kimia, Biologi)',
            'kategori' => 'Laboratorium',
            'deskripsi' => 'Ruang laboratorium sains dengan peralatan praktikum lengkap, mikroskop digital, lemari asam laboratorium, serta instrumen pengukuran eksperimen terkini.',
            'gambar' => $base . 'uploads/tempat/gedungA.jpg',
            'kondisi' => 'Baik',
            'kapasitas' => '40 Siswa',
            'fitur' => 'Mikroskop Digital, Alat Praktikum Kimia/Fisika, Safety Shower, Kit Saintek'
        ],
        [
            'id' => 4,
            'nama_fasilitas' => 'Perpustakaan Digital & Reading Lounge',
            'kategori' => 'Penunjang & Digital',
            'deskripsi' => 'Perpustakaan nyaman ber-AC yang mengintegrasikan koleksi ribuan buku fisik dan katalog E-Book digital, ruang baca mandiri, serta corner diskusi kelompok.',
            'gambar' => $base . 'uploads/tempat/gedungA1.jpg',
            'kondisi' => 'Sangat Baik',
            'kapasitas' => '80 Pengunjung',
            'fitur' => 'E-Library Kios, AC, Sofa Santai, Akses OPAC Komputer, Ruang Podcast'
        ],
        [
            'id' => 5,
            'nama_fasilitas' => 'Lapangan Olahraga Serbaguna Indoor & Outdoor',
            'kategori' => 'Olahraga',
            'deskripsi' => 'Fasilitas lapangan standar kompetisi untuk olahraga Basket, Futsal, Voli, dan Bulutangkis. Dilengkapi dengan tribun penonton dan penerangan malam.',
            'gambar' => $base . 'uploads/tempat/gedungA.jpg',
            'kondisi' => 'Baik',
            'kapasitas' => '500+ Penonton',
            'fitur' => 'Lap. Basket Vinyl, Lap. Futsal, Ring Basket Portable Standard FIBA, LED Lighting'
        ],
        [
            'id' => 6,
            'nama_fasilitas' => 'Aula Serbaguna SMAN 21',
            'kategori' => 'Sarana Umum',
            'deskripsi' => 'Gedung pertemuan utama yang luas dan megah untuk penyelenggaraan seminar, wisuda, pertunjukan seni budaya, dan pertemuan wali murid.',
            'gambar' => $base . 'uploads/tempat/gedungA1.jpg',
            'kondisi' => 'Sangat Baik',
            'kapasitas' => '700 Orang',
            'fitur' => 'Stage Pertunjukan, Sound System Line Array, Panggung Portable, Full AC'
        ],
        [
            'id' => 7,
            'nama_fasilitas' => 'Masjid Nurul Ilmi SMAN 21',
            'kategori' => 'Sarana Umum',
            'deskripsi' => 'Masjid sekolah dua lantai yang bersih, sejuk, dan nyaman untuk kegiatan shalat berjamaah, kajian keagamaan, serta kegiatan Rohis.',
            'gambar' => $base . 'uploads/tempat/gedungA.jpg',
            'kondisi' => 'Sangat Baik',
            'kapasitas' => '600 Jamaah',
            'fitur' => 'Sound System Masjid, Karpet Tebal, Area Wudhu Pria/Wanita Terpisah, Full AC'
        ],
        [
            'id' => 8,
            'nama_fasilitas' => 'Kantin Sehat & Clean Food Court',
            'kategori' => 'Penunjang & Digital',
            'deskripsi' => 'Pusat kuliner sekolah yang higienis, menyediakan ragam makanan dan minuman sehat yang telah terverifikasi standar nutrisinya.',
            'gambar' => $base . 'uploads/tempat/gedungA1.jpg',
            'kondisi' => 'Sangat Baik',
            'kapasitas' => '200 Tempat Duduk',
            'fitur' => 'Sistem Pembayaran Cashless/QRIS, Tempat Cuci Tangan Air Mengalir, Meja Ergonomis'
        ],
        [
            'id' => 9,
            'nama_fasilitas' => 'Ruang UKS & Klinik Kesehatan Sekolah',
            'kategori' => 'Sarana Umum',
            'deskripsi' => 'Ruang Usaha Kesehatan Sekolah yang dilengkapi dengan tempat tidur medis, obat-obatan P3K lengkap, serta bekerja sama dengan Puskesmas setempat.',
            'gambar' => $base . 'uploads/tempat/gedungA.jpg',
            'kondisi' => 'Baik',
            'kapasitas' => '6 Tempat Tidur',
            'fitur' => 'Tabung Oksigen, Alat Cek Tensi & Gula Darah, Timbangan Medis, Petugas PMR Standby'
        ]
    ];
}

// Ambil Kategori Unik
$categoriesList = ['Semua'];
foreach ($fasilitas as $fItem) {
    $kat = !empty($fItem['kategori']) ? trim($fItem['kategori']) : 'Umum';
    if (!in_array($kat, $categoriesList)) {
        $categoriesList[] = $kat;
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Fasilitas & Sarana Prasarana — SMAN 21 JAKARTA</title>
    <meta name="description" content="Eksplorasi fasilitas, sarana, dan prasarana modern di SMAN 21 Jakarta. Laboratorium Komputer, Kelas Digital, Lapangan Olahraga, dan Perpustakaan Berteknologi.">
    
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
            transform: translateY(-6px);
            box-shadow: 0 16px 32px -4px rgba(15, 23, 42, 0.12);
            border-color: #CBD5E1;
        }

        .hero-sub-overlay {
            background: linear-gradient(135deg, rgba(6, 18, 36, 0.92) 0%, rgba(11, 30, 54, 0.85) 100%);
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
                
                <a href="<?= $base; ?>index.php" class="flex items-center gap-3.5 group">
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
                    <a href="<?= $base; ?>index.php" class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 transition-all">Beranda</a>

                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 flex items-center gap-1.5 transition-all">
                            <span>Profil</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full left-0 w-56 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="<?= $base; ?>profil/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Tentang Sekolah</a>
                                <a href="<?= $base; ?>profil/sejarah.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Sejarah Sekolah</a>
                                <a href="<?= $base; ?>profil/visi-misi.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Visi & Misi</a>
                                <a href="<?= $base; ?>profil/sambutan.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Sambutan Kepala Sekolah</a>
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
                                <a href="<?= $base; ?>akademik/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Overview Akademik</a>
                                <a href="<?= $base; ?>akademik/program.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Program Pendidikan</a>
                                <a href="<?= $base; ?>akademik/guru.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Daftar Guru & Staf</a>
                                <a href="<?= $base; ?>akademik/pemeringkatan.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Pemeringkatan Jurusan</a>
                                <a href="<?= $base; ?>akademik/keterserapan-ptn.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Keterserapan PTN</a>
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
                                <a href="<?= $base; ?>kegiatan/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Semua Kegiatan</a>
                                <a href="<?= $base; ?>kegiatan/ekstrakurikuler.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Ekstrakurikuler</a>
                            </div>
                        </div>
                    </div>

                    <a href="<?= $base; ?>prestasi/index.php" class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 transition-all">Prestasi</a>
                    
                    <a href="index.php" class="px-3.5 py-2 rounded-lg text-cyan-600 bg-cyan-50 font-bold border border-cyan-200 transition-all">Fasilitas</a>

                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-lg hover:text-cyan-600 hover:bg-slate-100 flex items-center gap-1.5 transition-all">
                            <span>Informasi</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="absolute top-full right-0 w-52 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200">
                            <div class="bg-white rounded-xl p-2 shadow-xl border border-slate-200">
                                <a href="<?= $base; ?>informasi/berita.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Berita Sekolah</a>
                                <a href="<?= $base; ?>informasi/agenda.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Agenda & Kalender</a>
                                <a href="<?= $base; ?>informasi/ppdb.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">PPDB Info</a>
                                <a href="<?= $base; ?>gallery/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Galeri Foto</a>
                                <a href="<?= $base; ?>kontak/index.php" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-cyan-600 transition-colors">Kontak & Lokasi</a>
                            </div>
                        </div>
                    </div>

                    <a href="<?= $base; ?>informasi/ppdb.php" class="ml-2 px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs flex items-center gap-2 shadow-sm transition-all">
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
            <a href="<?= $base; ?>index.php" class="block text-xs font-semibold text-slate-700">Beranda</a>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Profil</span>
                <a href="<?= $base; ?>profil/index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Profil</a>
                <a href="<?= $base; ?>profil/sejarah.php" class="block pl-3 py-1 text-xs text-slate-700">Sejarah</a>
                <a href="<?= $base; ?>profil/visi-misi.php" class="block pl-3 py-1 text-xs text-slate-700">Visi & Misi</a>
                <a href="<?= $base; ?>profil/sambutan.php" class="block pl-3 py-1 text-xs text-slate-700">Sambutan Kepala Sekolah</a>
            </div>
            <div class="space-y-1">
                <span class="text-[11px] uppercase text-slate-400 font-bold tracking-wider">Akademik</span>
                <a href="<?= $base; ?>akademik/index.php" class="block pl-3 py-1 text-xs text-slate-700">Overview Akademik</a>
                <a href="<?= $base; ?>akademik/guru.php" class="block pl-3 py-1 text-xs text-slate-700">Daftar Guru</a>
                <a href="<?= $base; ?>akademik/keterserapan-ptn.php" class="block pl-3 py-1 text-xs text-slate-700">Keterserapan PTN</a>
            </div>
            <a href="<?= $base; ?>prestasi/index.php" class="block text-xs font-semibold text-slate-700">Prestasi</a>
            <a href="index.php" class="block text-cyan-600 font-bold text-xs">Fasilitas</a>
            <a href="<?= $base; ?>kegiatan/index.php" class="block text-xs font-semibold text-slate-700">Kegiatan & Ekskul</a>
            <a href="<?= $base; ?>informasi/ppdb.php" class="block text-xs font-semibold text-slate-700">PPDB Info</a>
            <a href="<?= $base; ?>kontak/index.php" class="block text-xs font-semibold text-slate-700">Kontak</a>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 🖼️ HERO PAGE BANNER                                                 -->
    <!-- ===================================================================== -->
    <section class="relative bg-brand-darkNavy text-white py-16 sm:py-24 border-b border-slate-800 bg-cover bg-center overflow-hidden" 
             style="background-image: url('<?= htmlspecialchars($sekolah['hero_bg']); ?>');">
        <div class="absolute inset-0 hero-sub-overlay"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl space-y-4 reveal-section">
                
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-cyan-500/20 border border-cyan-400/30 text-cyan-300 text-xs font-bold uppercase tracking-wider backdrop-blur-sm">
                    <i class="fa-solid fa-building-columns text-cyan-400"></i>
                    <span>SARANA & PRASARANA SEKOLAH</span>
                </div>

                <h1 class="text-3xl sm:text-5xl font-extrabold text-white leading-tight">
                    Fasilitas Modern & <br><span class="text-cyan-400">Lingkungan Belajar Digital</span>
                </h1>

                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed max-w-2xl">
                    SMAN 21 Jakarta menyediakan sarana pendukung pembelajaran berstandar tinggi untuk mendukung eksplorasi sains, akademik, seni, hingga olahraga berprestasi.
                </p>

                <!-- Breadcrumb -->
                <div class="pt-2 flex items-center gap-2 text-xs text-slate-400 font-medium">
                    <a href="<?= $base; ?>index.php" class="hover:text-cyan-400 transition-colors">Beranda</a>
                    <i class="fa-solid fa-chevron-right text-[9px] text-slate-600"></i>
                    <span class="text-cyan-400 font-bold">Fasilitas</span>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🔍 FILTER CATEGORIES & SEARCH BAR                                     -->
    <!-- ===================================================================== -->
    <section class="py-8 bg-white border-b border-slate-200 sticky top-20 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                
                <!-- Category Tabs -->
                <div class="flex items-center gap-2 overflow-x-auto pb-2 lg:pb-0 scrollbar-none text-xs font-bold">
                    <?php foreach ($categoriesList as $idx => $kat): ?>
                    <button onclick="filterCategory('<?= htmlspecialchars($kat); ?>', this)" 
                            class="cat-btn px-4 py-2.5 rounded-xl border whitespace-nowrap transition-all duration-200 <?= $idx === 0 ? 'bg-cyan-600 text-white border-cyan-600 shadow-sm' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100 hover:text-slate-900'; ?>">
                        <?= htmlspecialchars($kat); ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <!-- Live Search Box -->
                <div class="relative w-full lg:w-80">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="searchInput" onkeyup="searchFasilitas()" placeholder="Cari nama fasilitas..." 
                           class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-xs focus:outline-none focus:border-cyan-600 focus:bg-white transition-all">
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 🏛️ FASILITAS GRID SHOWCASE                                           -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-slate-50 min-h-[500px]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div id="fasilitasGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($fasilitas as $index => $item): 
                    $fNama = $item['nama_fasilitas'] ?? $item['nama'] ?? 'Fasilitas SMAN 21';
                    $fKat = !empty($item['kategori']) ? $item['kategori'] : 'Umum';
                    $fDesk = $item['deskripsi'] ?? 'Fasilitas penunjang pembelajaran berkualitas di SMAN 21 Jakarta.';
                    $fRawImg = $item['gambar'] ?? $item['foto'] ?? $item['thumbnail'] ?? '';
                    $fImg = formatFasilitasImg($fRawImg, $base);
                    $fKondisi = $item['kondisi'] ?? 'Sangat Baik';
                    $fKapasitas = $item['kapasitas'] ?? 'Standar Kelas';
                    $fFitur = $item['fitur'] ?? 'Terawat & Bebas Gangguan';
                ?>
                <div class="fasilitas-card school-card rounded-2xl overflow-hidden flex flex-col justify-between bg-white border border-slate-200 reveal-section delay-<?= ($index % 3 + 1) * 100; ?>" 
                     data-category="<?= htmlspecialchars($fKat); ?>" 
                     data-title="<?= strtolower(htmlspecialchars($fNama)); ?>">
                    
                    <div>
                        <!-- Image Container -->
                        <div class="relative aspect-[16/10] overflow-hidden bg-slate-100 group">
                            <img src="<?= htmlspecialchars($fImg); ?>" 
                                 alt="<?= htmlspecialchars($fNama); ?>" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                 onerror="this.onerror=null; this.src='<?= $logoPath; ?>'; this.className='w-1/2 h-1/2 object-contain m-auto p-4 opacity-50';">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            
                            <span class="absolute top-3 left-3 px-3 py-1 rounded-full bg-brand-darkNavy/80 text-cyan-300 text-[10px] font-bold uppercase tracking-wider backdrop-blur-md border border-cyan-500/30">
                                <?= htmlspecialchars($fKat); ?>
                            </span>

                            <span class="absolute bottom-3 right-3 px-2.5 py-0.5 rounded bg-emerald-500 text-white text-[10px] font-bold shadow-sm">
                                <i class="fa-solid fa-circle-check text-[9px] mr-1"></i><?= htmlspecialchars($fKondisi); ?>
                            </span>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6 space-y-3">
                            <h3 class="text-base font-extrabold text-brand-darkNavy line-clamp-1 leading-snug">
                                <?= htmlspecialchars($fNama); ?>
                            </h3>
                            <p class="text-xs text-slate-600 line-clamp-3 leading-relaxed">
                                <?= htmlspecialchars($fDesk); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Card Footer -->
                    <div class="px-6 pb-6 pt-2 border-t border-slate-100 flex items-center justify-between mt-auto">
                        <div class="text-[11px] text-slate-500">
                            <i class="fa-solid fa-users text-cyan-600 mr-1"></i>
                            <span><?= htmlspecialchars($fKapasitas); ?></span>
                        </div>
                        <button onclick="openModal(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8'); ?>)" 
                                class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-cyan-600 hover:text-white text-slate-700 font-bold text-xs transition-all flex items-center gap-1.5">
                            <span>Detail</span>
                            <i class="fa-solid fa-arrow-right text-[9px]"></i>
                        </button>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>

            <!-- Empty State Search Result -->
            <div id="noResults" class="hidden py-16 text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-slate-200 text-slate-400 flex items-center justify-center mx-auto text-2xl">
                    <i class="fa-solid fa-building-circle-xmark"></i>
                </div>
                <h3 class="text-base font-bold text-slate-700">Fasilitas Tidak Ditemukan</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">Coba masukkan kata kunci lain atau pilih kategori fasilitas yang tersedia di atas.</p>
                <button onclick="resetFilters()" class="px-4 py-2 rounded-lg bg-cyan-600 text-white font-bold text-xs hover:bg-cyan-500 transition-colors">
                    Reset Pencarian
                </button>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 💡 HIGHLIGHT / VIRTUAL SPOTLIGHT                                      -->
    <!-- ===================================================================== -->
    <section class="py-20 bg-white border-y border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-12 items-center">
                
                <div class="lg:col-span-6 space-y-6 reveal-section">
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-600 bg-cyan-50 px-3 py-1 rounded border border-cyan-200">
                        KEUNGGULAN LINGKUNGAN BELAJAR
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-darkNavy leading-tight">
                        Komitmen Menjaga Kenyamanan & Kebersihan Sarana
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Seluruh fasilitas di SMAN 21 Jakarta dikelola secara berkala dengan jadwal perawatan rutin untuk memastikan para siswa dapat belajar, berinovasi, dan berolahraga dalam kondisi terbaik.
                    </p>

                    <div class="grid sm:grid-cols-2 gap-4 pt-2">
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                            <i class="fa-solid fa-wifi text-cyan-600 text-lg"></i>
                            <h4 class="text-xs font-bold text-brand-darkNavy">High Speed Internet</h4>
                            <p class="text-[11px] text-slate-500">Jaringan Fiber Optic Wi-Fi di seluruh area sekolah.</p>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                            <i class="fa-solid fa-video text-cyan-600 text-lg"></i>
                            <h4 class="text-xs font-bold text-brand-darkNavy">Pengawasan CCTV 24 Jam</h4>
                            <p class="text-[11px] text-slate-500">Keamanan terjamin dengan kamera pengawas digital.</p>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                            <i class="fa-solid fa-leaf text-cyan-600 text-lg"></i>
                            <h4 class="text-xs font-bold text-brand-darkNavy">Sekolah Adiwiyata</h4>
                            <p class="text-[11px] text-slate-500">Lingkungan hijau, asri, hemat energi, dan ramah lingkungan.</p>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                            <i class="fa-solid fa-wheelchair text-cyan-600 text-lg"></i>
                            <h4 class="text-xs font-bold text-brand-darkNavy">Ramah Inklusi</h4>
                            <p class="text-[11px] text-slate-500">Aksesibel untuk seluruh civitas akademika.</p>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-6 reveal-section delay-200">
                    <div class="relative rounded-2xl overflow-hidden shadow-2xl border border-slate-200 aspect-[4/3] group">
                        <img src="<?= htmlspecialchars($sekolah['hero_bg']); ?>" alt="SMAN 21 Campus" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-brand-darkNavy/90 via-brand-darkNavy/30 to-transparent p-8 flex flex-col justify-end">
                            <span class="text-xs font-bold text-cyan-400 uppercase tracking-widest mb-1">SMAN 21 JAKARTA CAMPUS</span>
                            <h3 class="text-xl font-bold text-white">Lingkungan Akademik Asri & Berteknologi</h3>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- 📞 CTA VISIT SEKOLAH                                                  -->
    <!-- ===================================================================== -->
    <section class="py-16 bg-brand-darkNavy text-white relative overflow-hidden">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 reveal-section text-center space-y-6">
            <h2 class="text-2xl sm:text-4xl font-extrabold">Ingin Melihat Langsung Fasilitas SMAN 21?</h2>
            <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                Kami menyambut hangat kunjungan calon peserta didik baru dan orang tua murid pada jam kerja sekolah.
            </p>
            <div class="flex flex-wrap justify-center gap-4 pt-2">
                <a href="<?= $base; ?>kontak/index.php" class="px-7 py-3 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg transition-all flex items-center gap-2">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>LOKASI & JADWAL KUNJUNGAN</span>
                </a>
                <a href="<?= $base; ?>informasi/ppdb.php" class="px-7 py-3 rounded-lg bg-white/10 hover:bg-white/20 text-white font-bold text-xs uppercase tracking-wider border border-white/20 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span>INFO SPMB / PPDB</span>
                </a>
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
                        <li><a href="<?= $base; ?>profil/index.php" class="hover:text-cyan-400 transition-colors">Profil Sekolah</a></li>
                        <li><a href="<?= $base; ?>akademik/index.php" class="hover:text-cyan-400 transition-colors">Kurikulum & Akademik</a></li>
                        <li><a href="<?= $base; ?>akademik/guru.php" class="hover:text-cyan-400 transition-colors">Daftar Guru & Staf</a></li>
                        <li><a href="<?= $base; ?>prestasi/index.php" class="hover:text-cyan-400 transition-colors">Galeri Prestasi Siswa</a></li>
                        <li><a href="index.php" class="text-cyan-400 font-bold">Sarana & Fasilitas</a></li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Layanan Informasi</h3>
                    <ul class="space-y-2 text-[11px]">
                        <li><a href="<?= $base; ?>informasi/ppdb.php" class="hover:text-cyan-400 transition-colors">Informasi SPMB / PPDB</a></li>
                        <li><a href="<?= $base; ?>informasi/berita.php" class="hover:text-cyan-400 transition-colors">Berita & Pengumuman</a></li>
                        <li><a href="<?= $base; ?>informasi/agenda.php" class="hover:text-cyan-400 transition-colors">Agenda Kalender Akademik</a></li>
                        <li><a href="<?= $base; ?>akademik/keterserapan-ptn.php" class="hover:text-cyan-400 transition-colors">Keterserapan PTN Alumni</a></li>
                        <li><a href="<?= $base; ?>kontak/index.php" class="hover:text-cyan-400 transition-colors">Kontak & Pusat Bantuan</a></li>
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
                    <a href="<?= $base; ?>admin/login.php" class="hover:text-cyan-400 transition-colors"><i class="fa-solid fa-lock text-[10px] mr-1"></i>Portal Admin</a>
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
                <p>Ada yang bisa saya bantu terkait Fasilitas, Laboratorium, SPMB/PPDB, atau Informasi SMAN 21 Jakarta?</p>
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
    <!-- 🔍 MODAL DETAIL FASILITAS                                            -->
    <!-- ===================================================================== -->
    <div id="fasilitasModal" class="fixed inset-0 z-[9995] bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4 transition-opacity">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200 flex flex-col">
            
            <div class="relative aspect-[16/9] bg-slate-100">
                <img id="modalImg" src="" alt="Fasilitas Detail" class="w-full h-full object-cover">
                <button onclick="closeModal()" class="absolute top-4 right-4 w-9 h-9 rounded-full bg-black/60 text-white hover:bg-black flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <span id="modalCategory" class="absolute bottom-4 left-4 px-3 py-1 rounded-full bg-brand-darkNavy text-cyan-300 text-[10px] font-bold uppercase tracking-wider">
                    KATEGORI
                </span>
            </div>

            <div class="p-6 space-y-4">
                <div>
                    <h2 id="modalTitle" class="text-xl font-extrabold text-brand-darkNavy">Nama Fasilitas</h2>
                    <p id="modalKondisi" class="text-xs font-bold text-emerald-600 mt-1"><i class="fa-solid fa-circle-check mr-1"></i>Kondisi: Baik</p>
                </div>

                <div class="space-y-2">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Deskripsi Fasilitas</h4>
                    <p id="modalDesc" class="text-xs text-slate-600 leading-relaxed">Deskripsi lengkap fasilitas...</p>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Kapasitas / Daya Tampung</span>
                        <span id="modalKapasitas" class="text-xs font-bold text-slate-800">36 Siswa</span>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Spesifikasi & Fitur</span>
                        <span id="modalFitur" class="text-xs font-bold text-slate-800">AC, Smart TV, Wi-Fi</span>
                    </div>
                </div>

                <div class="pt-4 text-right">
                    <button onclick="closeModal()" class="px-5 py-2.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs transition-colors">
                        Tutup
                    </button>
                </div>
            </div>

        </div>
    </div>

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

        // 3. CATEGORY & SEARCH FILTER
        let activeCategory = 'Semua';

        function filterCategory(cat, btn) {
            activeCategory = cat;
            
            document.querySelectorAll('.cat-btn').forEach(b => {
                b.className = 'cat-btn px-4 py-2.5 rounded-xl border whitespace-nowrap transition-all duration-200 bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100 hover:text-slate-900';
            });
            btn.className = 'cat-btn px-4 py-2.5 rounded-xl border whitespace-nowrap transition-all duration-200 bg-cyan-600 text-white border-cyan-600 shadow-sm';

            applyFilters();
        }

        function searchFasilitas() {
            applyFilters();
        }

        function applyFilters() {
            const query = document.getElementById('searchInput').value.toLowerCase().trim();
            const cards = document.querySelectorAll('.fasilitas-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const cardCat = card.getAttribute('data-category');
                const cardTitle = card.getAttribute('data-title');

                const matchCat = (activeCategory === 'Semua') || (cardCat === activeCategory);
                const matchQuery = !query || cardTitle.includes(query);

                if (matchCat && matchQuery) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            const noRes = document.getElementById('noResults');
            if (visibleCount === 0) {
                noRes.classList.remove('hidden');
            } else {
                noRes.classList.add('hidden');
            }
        }

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            const firstCatBtn = document.querySelector('.cat-btn');
            if (firstCatBtn) filterCategory('Semua', firstCatBtn);
        }

        // 4. MODAL DETAIL CONTROLLER
        function openModal(data) {
            const base = '<?= $base; ?>';
            let imgSrc = data.gambar || data.foto || data.thumbnail || '';
            
            if (!imgSrc || imgSrc === '') {
                imgSrc = base + 'uploads/tempat/gedungA1.jpg';
            } else if (!imgSrc.startsWith('http') && !imgSrc.startsWith('../')) {
                if (imgSrc.startsWith('uploads/')) {
                    imgSrc = base + imgSrc;
                } else {
                    imgSrc = base + 'uploads/fasilitas/' + imgSrc;
                }
            }

            document.getElementById('modalImg').src = imgSrc;
            document.getElementById('modalTitle').innerText = data.nama_fasilitas || data.nama || 'Fasilitas SMAN 21';
            document.getElementById('modalCategory').innerText = data.kategori || 'Umum';
            document.getElementById('modalDesc').innerText = data.deskripsi || 'Fasilitas terawat dan mendukung kegiatan belajar mengajar.';
            document.getElementById('modalKondisi').innerHTML = '<i class="fa-solid fa-circle-check mr-1"></i>Kondisi: ' + (data.kondisi || 'Sangat Baik');
            document.getElementById('modalKapasitas').innerText = data.kapasitas || 'Standar Kelas';
            document.getElementById('modalFitur').innerText = data.fitur || 'Lengkap & Terawat';

            const modal = document.getElementById('fasilitasModal');
            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('fasilitasModal').classList.add('hidden');
        }

        // 5. AI ASSISTANT PANEL TOGGLE & CHAT AJAX
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

            fetch('<?= $base; ?>index.php?action=ai_query', {
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
                    linkHtml = `<div class="pt-1"><a href="${'<?= $base; ?>' + data.related_link}" class="text-cyan-600 font-bold hover:underline inline-flex items-center gap-1">Pelajari lebih lanjut <i class="fa-solid fa-arrow-right text-[9px]"></i></a></div>`;
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
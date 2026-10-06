🏫 SMA Negeri 21 Jakarta — Official Web Profile \& Context-Aware AI Assistant



Karya Kompetisi INCEPTION Vol. 2 Tahun 2026



Kategori: Pengembangan Website Profil Sekolah dengan Chatbot AI



Penyelenggara: Divisi Riset dan Teknologi Program Studi Informatika Universitas Trisakti



📌 Deskripsi Proyek



Website Profil SMA Negeri 21 Jakarta dikembangkan sebagai media informasi digital modern, responsif, dan interaktif yang mengintegrasikan Context-Aware AI Assistant sebagai fitur utama.



Sistem ini dirancang murni (custom source code) tanpa menggunakan Content Management System (CMS) siap pakai seperti WordPress, Wix, atau Google Sites. Website menghadirkan pengalaman visual cinematic dan glassmorphic yang nyaman bagi pengunjung publik (orang tua, calon peserta didik, siswa, dan alumni) serta menyediakan Panel Admin CMS yang intuitif bagi pengelola sekolah untuk memperbarui data dan mengelola basis pengetahuan (Knowledge Base) AI secara real-time.



🚀 Fitur Utama Berdasarkan Role



Sistem terbagi secara terstruktur menjadi dua role utama: Public/Guest (Pengunjung) dan Admin (Pengelola Sistem).



🌐 1. Role Pengunjung / Publik (Public Guest)



Tampilan publik berfokus pada kemudahan navigasi, visualisasi data sekolah, serta akses informasi cepat melalui navigasi intuitif dan floating AI assistant.



Cinematic Landing Page (index.php): Halaman utama berbasis overview yang mencakup Hero Section, Quick Access, About, Program Unggulan, Highlight Prestasi, Berita Terkini, Galeri, hingga Kontak \& Lokasi.



Profil Sekolah (/profil/): Informasi mendalam tentang Sejarah, Visi \& Misi, serta Sambutan Kepala Sekolah.



Akademik \& Keterserapan (/akademik/): Informasi Program Pendidikan, Daftar Guru \& Mata Pelajaran, Pemeringkatan Siswa, serta Data Keterserapan Alumni di PTN.



Prestasi \& Kegiatan (/prestasi/, /kegiatan/): Katalog prestasi akademik/non-akademik dengan filter kategori, serta detail kegiatan sekolah \& ekstrakurikuler.



Fasilitas \& Galeri (/fasilitas/, /gallery/): Tampilan visual fasilitas sekolah dan galeri kegiatan dengan tata letak grid/masonry.



Informasi, Agenda \& PPDB (/informasi/): Pengumuman berita, kalender agenda interaktif, dan portal informasi Penerimaan Peserta Didik Baru (PPDB).



Portal Alumni \& Kontak (/alumni/, /kontak/): Rekam jejak alumni (Alumni Stories) dan peta interaktif beserta saluran kontak resmi.



🤖 Context-Aware AI Assistant (S21 AI Assistant)



Global Floating Toggle: Tombol berbentuk pill-button modern di sudut kanan bawah dengan animasi soft-glow (berubah menjadi bottom sheet di perangkat mobile).



Penyesuaian Konteks Halaman (Context-Aware): AI otomatis memahami halaman yang sedang dibuka pengguna. Jika pengguna berada di halaman /akademik/, AI secara proaktif menawarkan quick prompt terkait kurikulum atau data guru.



Deep Link Integration: Jawaban AI menyertakan tautan langsung (deep link) menuju halaman terkait di dalam website.



Penanganan Pertanyaan Tidak Tersedia (Fallback Handling): Jika informasi tidak ditemukan dalam basis data, AI secara jujur menyampaikan keterbatasan informasi tanpa mengarang (hallucination), sesuai pedoman penilaian kompetisi.



Rujukan Sumber Data: Menyertakan label sumber data resmi (Source/Reference) pada setiap jawaban yang diberikan.



🔐 2. Role Administrator (Admin Panel)



Panel khusus untuk mengelola seluruh konten dinamis website dan mengontrol perilaku AI tanpa perlu menyentuh kode program.



Dashboard Ringkasan (admin/dashboard.php): Ringkasan statistik konten (jumlah berita, agenda, prestasi, fasilitas) dan log aktivitas terbaru.



Manajemen Konten Profil \& Akademik: Modul CRUD (Create, Read, Update, Delete) untuk Visi-Misi, Sejarah, Sambutan, Program Sekolah, Data Guru, Mapel, dan Keterserapan PTN.



Manajemen Informasi \& Publikasi: Pengelolaan Berita (sistem drafting/publish), Agenda Kalender, Informasi PPDB, Prestasi, dan Galeri Foto/Video.



Manajemen Portal Alumni \& Fasilitas: Pendataan alumni, testimoni, serta manajemen inventaris fasilitas sekolah.



Pusat Kendali AI (admin/ai-assistant/):



Knowledge Base Manager (knowledge.php): Menambah, mengedit, dan menghapus pertanyaan, jawaban, kata kunci (keywords), dan kategori data untuk AI.



Kategori AI (kategori.php): Pengelompokan basis pengetahuan (Profil, Akademik, PPDB, Fasilitas, dll).



Pengaturan Parameter AI (pengaturan.php): Mengatur system prompt, nama asisten, pesan pembuka (welcome message), dan fallback message.



Pengaturan Identitas Sekolah: Manajemen nama sekolah, logo, favicon, akreditasi, peta lokasi, serta kontak sosmed.



🛠️ Teknologi yang Digunakan



Language \& Core Logic: PHP 8.2+ (Pure Custom Code, No Framework/CMS Boilerplate)



Database: MySQL / MariaDB



Frontend \& UI: HTML5, Tailwind CSS, JavaScript (Vanilla JS ES6+)



UI Components: Glassmorphism CSS, Inter Icons / FontAwesome



AI Engine: Gemini API / OpenAI API Integration via Secure Server-Side Proxy



Version Control: Git \& GitHub



📂 Struktur Direktori Proyek



sman21jkt/

├── admin/                         # Panel Pengelola (CMS Admin)

│   ├── index.php                  # Redirect / Login Auth

│   ├── dashboard.php              # Dashboard Utama Admin

│   ├── login.php / logout.php     # Otentikasi Admin

│   ├── pengaturan/                # Pengaturan Sekolah, Kontak \& AI

│   ├── profil/                    # CRUD Profil \& Visi Misi

│   ├── akademik/                  # CRUD Program, Guru, Mapel, PTN

│   ├── prestasi/                  # CRUD Data Prestasi

│   ├── kegiatan/                  # CRUD Kegiatan \& Ekskul

│   ├── fasilitas/                 # CRUD Fasilitas

│   ├── gallery/                   # CRUD Galeri

│   ├── informasi/                 # CRUD Berita, Agenda, PPDB

│   ├── alumni/                    # CRUD Data Alumni

│   └── ai-assistant/              # Manajemen Knowledge Base AI \& Prompt

│

├── profil/                        # Halaman Publik Profil Sekolah

├── akademik/                      # Halaman Publik Akademik \& PTN

├── prestasi/                      # Halaman Publik Prestasi

├── kegiatan/                      # Halaman Publik Kegiatan \& Ekskul

├── fasilitas/                     # Halaman Publik Fasilitas

├── informasi/                     # Halaman Publik Berita, Agenda, PPDB

├── alumni/                        # Halaman Publik Stories Alumni

├── gallery/                       # Halaman Publik Galeri Foto/Video

├── kontak/                        # Halaman Publik Kontak \& Peta

│

├── api/                            # Halaman Koneksi API AI Server

├── config/                        # Database Connection \& Environment Config

├── index.php                      # Homepage Utama (Public Overview)

└── README.md                      # Dokumentasi Proyek





💻 Panduan Instalasi \& Penggunaan Lokal



📋 Prasyarat Sistem



Web Server (XAMPP / Laragon / Nginx / Apache dengan PHP >= 8.1)



MySQL Database Server



Composer (opsional, jika menggunakan pustaka pihak ketiga)



Kunci API AI (Google Gemini API Key / OpenAI API Key)



⚙️ Langkah Instalasi



Clone Repositori



git clone https://github.com/username/sman21jkt.git

cd sman21jkt





Persiapan Database



Buka phpMyAdmin atau database client pilihan Anda.



Buat database baru dengan nama sman21jkt\_db.



Import file SQL yang disediakan pada direktori database/sman21jkt.sql.



Konfigurasi Environment Variable



Duplikat file .env.example menjadi .env:



cp .env.example .env





Buka file .env dan sesuaikan kredensial database serta API key Anda:



DB\_HOST=localhost

DB\_PORT=3306

DB\_NAME=sman21jkt\_db

DB\_USER=root

DB\_PASS=



AI\_PROVIDER=gemini

AI\_API\_KEY=YOUR\_GEMINI\_API\_KEY\_HERE

AI\_MODEL=gemini-1.5-flash





Jalankan Aplikasi



Jika menggunakan XAMPP/Laragon, pindahkan folder proyek ke htdocs atau www, lalu akses melalui browser:



http://localhost/sman21jkt





Atau gunakan PHP Built-in Server dari terminal:



php -S localhost:8000





Akses via browser: http://localhost:8000



Akses Panel Admin



URL Admin: http://localhost:8000/admin/login.php



Default Credential (dapat diubah melalui DB/Admin Panel):



Username: admin21



Password: Admin21#2026



🔒 Keamanan \& Praktik Terbaik (Security Notice)



Sesuai dengan Ketentuan Pedoman INCEPTION 2026:



Proteksi Credential: API Key, kredensial database, dan data sensitif dikelola menggunakan file .env yang terisolasi dari repositori Git (.gitignore).



Sistem Autentikasi: Sandi akun admin dienkripsi menggunakan algoritma password\_hash() (Bcrypt).



Pencegahan SQL Injection: Seluruh query interaksi database menggunakan Prepared Statements / Parameterized Queries.



Sanitasi Output: Menghindari potensi Cross-Site Scripting (XSS) dengan sanitasi input/output pada form admin dan panel chat.



🏆 Penilaian Kompetisi \& Relevansi Guidebook



Proyek ini disusun dengan mengacu pada indikator penilaian utama INCEPTION Vol. 2 2026:



Implementasi Chatbot AI (Bobot 30%): Chatbot terintegrasi penuh, responsif, context-aware, menangani pertanyaan yang tidak tersedia dengan bijak, serta memiliki performa yang stabil.



Kualitas \& Fungsionalitas Website (Bobot 25%): Struktur menu lengkap (Profil, Akademik, Prestasi, Fasilitas, PPDB, Alumni), navigasi jernih, dan fitur bekerja 100%.



UI/UX \& Responsivitas (Bobot 20%): Tampilan modern, rapi pada resolusi Desktop maupun Mobile.



Kualitas Teknis \& Deployment (Bobot 10%): Kode bersih, aman (no hardcoded API keys), dan terstruktur rapi.



👥 Tim Pengembang



Sekolah: SMA Negeri 21 Jakarta



Ketua Tim: GABRIEL ADRIEL MANIK



Anggota:


1. Pamimpin Efraim P.P


2\. Gilbert Christofer T.S

3. Rifat Syadinamora H

4. Jati Samudra G



Guru Pendamping: Sutarmin



Dibuat penuh dedikasi untuk mendukung Transformasi Digital Pendidikan Indonesia pada INCEPTION Vol. 2 Tahun 2026.


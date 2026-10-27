# Minima Interactive Audio - Platform Jelajah Musik Multimedia Berbasis Web

Minima Interactive Audio adalah aplikasi web multimedia interaktif yang dirancang untuk memberikan pengalaman eksplorasi musik digital secara menyeluruh. Aplikasi ini memadukan empat elemen utama sistem multimedia, yaitu teks, audio, video, dan objek 3D, dalam satu kesatuan sistem informasi berbasis Laravel dan Three.js.

---

## Maksud dan Tujuan

### 1. Maksud Pengembangan
Aplikasi ini dikembangkan untuk menghadirkan media apresiasi musik digital yang tidak hanya berfungsi sebagai pemutar audio biasa, melainkan platform multimedia komprehensif. Pengguna dapat menikmati alunan musik, mengamati representasi visual instrumen analog 3D, menyaksikan video klip resmi, serta membaca latar belakang komposisi dan analisis makna karya musik.

### 2. Tujuan Pengembangan
- Mengintegrasikan empat pilar multimedia (Teks, Audio, Video, dan Objek 3D) secara harmonis dalam arsitektur Model-View-Controller (MVC).
- Memberikan simulasi pemutar piringan hitam (vinyl turntable) 3D interaktif yang dapat berputar secara real-time mengikuti alur pemutaran musik.
- Menyediakan katalog musik yang luas dengan navigasi multi-halaman, filter genre, filter dekade era, serta pencarian otomatis (live autocomplete).
- Memfasilitasi interaksi komunitas pendengar melalui fitur pemberian ulasan teks dan penilaian rating bintang yang tersimpan permanen di database.
- Menyelesaikan proyek akhir mata kuliah Sistem Multimedia dengan standar antarmuka modern, responsif, dan ramah pengguna.

---

## Penerapan 4 Pilar Sistem Multimedia

Aplikasi Minima secara ketat menerapkan seluruh pilar sistem multimedia sebagai berikut:

| No | Elemen Multimedia | Implementasi dalam Aplikasi |
| :--- | :--- | :--- |
| 1 | Teks | Metadata lagu (judul, artis, album, tahun rilis, genre, durasi, rating), deskripsi biografi musisi, analisis makna lirik/komposisi lagu, ulasan pendengar, dan panduan antarmuka. |
| 2 | Audio | Pemutar audio interaktif pada bilah bawah (bottom audio bar), sistem antrean lagu, visualizer bar durasi, serta audio fallback berbasis YouTube stream dan Web Audio API. |
| 3 | Video | Pemutar video klip musik (Music Video / MV) resmi beresolusi tinggi menggunakan jendela modal terintegrasi YouTube IFrame API. |
| 4 | Objek 3D | Simulasi piringan hitam (vinyl record) dan turntable 3D berbasis WebGL / Three.js yang berputar dinamis dengan jarum tonearm mekanis serta kendali rotasi bebas 360 derajat. |

---

## Fitur Utama Aplikasi

### 1. Simulasi Piringan Hitam 3D (Three.js Vinyl Turntable)
- Objek piringan hitam 3D dirender langsung menggunakan WebGL dan Three.js.
- Piringan berputar otomatis dengan kecepatan proporsional saat musik sedang dimainkan dan berhenti secara halus saat musik dijeda.
- Jarum pemutar (tonearm) bergerak mendekati piringan hitam secara mekanis saat pemutaran dimulai.
- Pengguna dapat memutar sudut pandang meja putar (turntable) secara bebas 360 derajat menggunakan klik dan seret mouse (drag and drop).

### 2. Pemutar Audio Terintegrasi (Persistent Bottom Audio Player)
- Terletak di bagian bawah layar secara permanen sehingga musik tetap berjalan tanpa terputus saat pengguna berpindah menu.
- Kontrol pemutaran lengkap: Putar (Play), Jeda (Pause), Lagu Sebelumnya (Previous), Lagu Selanjutnya (Next), Penggeser Progres Durasi (Seekbar), serta Pengatur Volume Suara (Mute/Unmute).
- Integrasi otomatis penanganan sumber audio: jika fail audio lokal tidak tersedia, pemutar otomatis beralih ke stream audio daring secara mulus.

### 3. Pemutar Video Klip Musik (MV Player Modal)
- Pemutaran video klip resmi beresolusi tinggi langsung di dalam aplikasi melalui jendela modal video khusus.
- Tidak mengalihkan pengguna keluar dari aplikasi sehingga pengalaman tetap fokus dan terpadu.

### 4. Katalog Musik Multi-Halaman (Pagination System)
- Menampilkan seluruh koleksi 200 lagu dari berbagai era dan genre musik dunia.
- Dilengkapi sistem navigasi multi-halaman bernomor (1, 2, 3, 4, ...) yang dinamis dan bergeser otomatis saat menuju halaman berikutnya.
- Transisi pemuatan antar-halaman dilengkapi animasi kartu yang halus dan responsif.

### 5. Filter dan Pengurutan Cerdas
- Filter Genre: Menyaring katalog berdasarkan kategori Rock, Pop, Jazz, Metal, R&B, dan genre lainnya.
- Filter Dekade: Menyaring lagu berdasarkan era rilis (1960-an, 1970-an, 1980-an, 1990-an, 2000-an, 2010-an, dan 2020-an).
- Pengurutan: Pilihan urutan berdasarkan rating tertinggi, abjad nama lagu (A-Z atau Z-A), dan tahun rilis terbaru.

### 6. Pencarian Cerdas dengan Rekomendasi Langsung (Live Autocomplete)
- Kotak pencarian mendeteksi setiap ketukan huruf secara langsung dengan teknik debounce 180ms.
- Menampilkan daftar rekomendasi musisi dan lagu secara instan sebelum pengguna menekan tombol cari.
- Mendukung navigasi keyboard (panah atas, panah bawah, dan enter) untuk memilih rekomendasi.

### 7. Detail Komposisi dan Analisis Lagu Instan (0ms Delay)
- Menampilkan rincian lengkap lagu meliputi kover album, tahun rilis, genre, durasi, rating rata-rata komunitas, serta narasi latar belakang dan analisis makna lagu.
- Menggunakan sistem cache memori lokal sehingga jendela modal langsung terbuka seketika tanpa jeda pemuatan atau kedipan tampilan.
- Penutupan modal dilengkapi animasi keluar (closing animation) yang halus saat menekan tombol silang, area luar backdrop, maupun tombol Escape.

### 8. Sistem Ulasan dan Penilaian Bintang (CRUD Reviews)
- Pengguna dapat memberikan ulasan teks mendalam dan rating 1 sampai 5 bintang pada setiap lagu.
- Rating bintang interaktif dengan efek penyorotan visual (hover preview) yang menyala sesuai urutan bintang yang disorot kursor.
- Seluruh data ulasan tersimpan secara permanen pada database (bukan data tiruan atau dummy).
- Pengguna dapat melihat riwayat ulasan yang telah dibuat, melakukan penyuntingan ulasan, serta menghapus ulasan melalui tab Ulasan Saya.

### 9. Manajemen Playlist Musik Pribadi (CRUD Playlists)
- Membuat daftar putar (playlist) baru dengan nama dan deskripsi pilihan.
- Menambahkan lagu ke dalam playlist secara praktis melalui menu katalog atau modal detail lagu.
- Membuka halaman detail playlist untuk mendengarkan lagu-lagu koleksi, menghapus lagu dari playlist, atau menghapus playlist secara keseluruhan.

### 10. Antarmuka Modern dan Transisi Halus (Smooth Transitions)
- Setiap perpindahan menu (Beranda, Telusuri, Playlist, Ulasan, Tentang) dilengkapi animasi transisi visual yang halus pada kartu dan komponen antarmuka.
- Desain bergaya gelap (dark mode) dengan aksen warna hijau zamrud (emerald) dan sentuhan glassmorphism yang nyaman di mata.

---

## Fungsi Sistem

1. **Fungsi Eksplorasi Musik**: Memudahkan pencarian, penemuan, dan penyaringan ribuan referensi karya musik dari era klasik hingga kontemporer.
2. **Fungsi Apresiasi Karya**: Menyediakan ruang telaah makna lagu dan komposisi audio bagi pecinta musik dan akademisi.
3. **Fungsi Interaksi Pengguna**: Wadah pemberian ulasan, kritik musik, dan diskusi penilaian lagu yang transparan.
4. **Fungsi Personalisasi**: Mengelompokkan lagu favorit ke dalam playlist khusus yang dapat dikelola kapan saja.

---

## Arsitektur dan Struktur Proyek

Aplikasi ini dibangun menggunakan arsitektur Model-View-Controller (MVC) bawaan framework Laravel:

```text
minima-interactive-audio/
|-- app/
|   |-- Http/
|   |   |-- Controllers/
|   |   |   |-- MusicController.php       # Logika beranda, katalog, filter, pencarian, dan API lagu
|   |   |   |-- PlaylistController.php    # Logika CRUD pengelolaan playlist pengguna
|   |   |   `-- ReviewController.php      # Logika CRUD ulasan dan penilaian bintang
|   |-- Models/
|   |   |-- Artist.php                    # Model entitas musisi dan biografi
|   |   |-- Music.php                     # Model entitas lagu, relasi ke review dan playlist
|   |   |-- Playlist.php                  # Model entitas playlist pengguna
|   |   `-- Review.php                    # Model entitas ulasan dan rating pengguna
|-- database/
|   |-- migrations/                       # Skema tabel database (musics, artists, playlists, reviews)
|   `-- seeders/
|       |-- DatabaseSeeder.php            # Skrip pengisian data otomatis
|       `-- data.json                     # Dataset 200 lagu lengkap beserta artis dan metadata
|-- public/
|   |-- audio/                            # Berkas audio musik lokal
|   |-- image/                            # Berkas gambar kover album dan foto artis
|   |-- js/
|   |   `-- minima-app.js                 # Logika JavaScript, Three.js 3D Vinyl, Audio Player, Modal, dan AJAX
|   `-- style.css                         # Berkas gaya kustom CSS, animasi keyframe, dan efek visual
|-- resources/
|   `-- views/
|       |-- layouts/
|       |   `-- app.blade.php             # Tata letak utama, kanvas 3D, bottom player, dan navigasi
|       `-- music/
|           `-- index.blade.php           # Tampilan terpadu seluruh menu aplikasi
`-- routes/
    `-- web.php                           # Definisi rute antarmuka dan endpoint API internal
```

---

## Teknologi yang Digunakan

- **Bahasa Pemrograman**: PHP (Versi 8.2 atau lebih tinggi) dan JavaScript (ES6+).
- **Framework Web**: Laravel 11.
- **Basis Data**: SQLite (Default, ringan dan tanpa konfigurasi tambahan) atau MySQL.
- **Grafika 3D**: Three.js (WebGL 3D Engine).
- **Tata Letak & Gaya**: Tailwind CSS dan Vanilla CSS kustom (Hardware-accelerated animations).
- **Integrasi Multimedia**: HTML5 Audio API, Web Audio API (Synthesizer fallback), dan YouTube IFrame API.
- **Paket Dependensi**: Composer.

---

## Prasyarat Sistem

Sebelum melakukan pemasangan, pastikan perangkat Anda telah terpasang perangkat lunak berikut:

1. **PHP**: Versi 8.2 atau yang lebih baru (beserta ekstensi pdo, pdo_sqlite, mbstring, openssl, tokenizer, xml, curl).
2. **Composer**: Versi 2.x (Manajer dependensi PHP).
3. **Web Server**: Laragon, XAMPP, atau PHP Built-in Server bawaan terminal.
4. **Git**: Untuk mengunduh dan menyinkronkan repositori.
5. **Web Browser Modern**: Google Chrome, Microsoft Edge, Mozilla Firefox, atau browser lain yang mendukung WebGL.

---

## Panduan Pemasangan dan Instalasi

Ikuti langkah-langkah di bawah ini untuk menjalankan aplikasi di lingkungan lokal (Local Development):

### 1. Kloning Repositori
Buka terminal (Git Bash, Command Prompt, atau PowerShell), kemudian unduh repositori ini:

```bash
git clone https://github.com/Benaya-Bagas-Christianto/minima-interactive-audio.git
```

### 2. Masuk ke Direktori Proyek
```bash
cd minima-interactive-audio
```

### 3. Pasang Dependensi PHP (Composer)
Jalankan perintah berikut untuk mengunduh seluruh dependensi framework Laravel:

```bash
composer install
```

### 4. Konfigurasi Berkas Environment
Salin berkas contoh `.env.example` menjadi `.env`:

- Di Windows PowerShell / CMD:
  ```powershell
  copy .env.example .env
  ```
- Di Linux / macOS:
  ```bash
  cp .env.example .env
  ```

### 5. Buat Application Key
Jalankan pembuatan kunci enkripsi aplikasi Laravel:

```bash
php artisan key:generate
```

### 6. Persiapkan Basis Data dan Jalankan Migrasi
Secara default, proyek ini menggunakan SQLite yang sangat mudah dan tidak memerlukan pembuatan akun database terpisah:

1. Buat berkas database SQLite di dalam folder `database/` jika belum ada:
   - Di Windows PowerShell:
     ```powershell
     New-Item -ItemType File -Path database\database.sqlite -Force
     ```
   - Di Linux / macOS / Git Bash:
     ```bash
     touch database/database.sqlite
     ```

2. Jalankan migrasi tabel beserta pengisian data awal (seeding 200 lagu):
   ```bash
   php artisan migrate:fresh --seed
   ```

*Catatan: Jika Anda ingin menggunakan MySQL (misalnya lewat Laragon atau XAMPP), sesuaikan konfigurasi `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=minima_db`, `DB_USERNAME=root`, dan `DB_PASSWORD=` pada berkas `.env`, lalu jalankan perintah migrasi di atas.*

### 7. Menjalankan Server Lokal

#### Opsi A: Menggunakan Artisan Server (Rekomendasi Cepat)
Jalankan perintah berikut pada terminal:

```bash
php artisan serve
```

Setelah server aktif, buka peramban web dan akses alamat:
```text
http://127.0.0.1:8000
```

#### Opsi B: Menggunakan Laragon Virtual Host
Jika menggunakan Laragon:
1. Pindahkan folder proyek ke `C:\laragon\www\minima-interactive-audio` atau `D:\laragon\www\minima-interactive-audio`.
2. Buka aplikasi Laragon, lalu klik tombol **Start All**.
3. Buka peramban dan akses alamat:
   ```text
   http://minima-interactive-audio.test
   ```

---

## Panduan Penggunaan Singkat

1. **Memutar Musik**: Klik ikon play pada kover lagu untuk langsung memutar lagu pada bilah audio bawah.
2. **Memutar di Piringan 3D**: Klik tombol 3D pada kover lagu untuk mengaktifkan pemutar piringan hitam 3D. Seret kursor mouse pada piringan hitam untuk mengubah sudut pandang meja putar.
3. **Melihat Detail dan Analisis**: Klik tombol "Detail & Analisis" pada lagu untuk membaca telaah makna, komposisi, serta ulasan pendengar.
4. **Memberi Ulasan dan Rating**: Buka modal detail lagu, sorot dan klik bintang penilaian (1-5), tuliskan komentar Anda, lalu klik "Kirim Ulasan".
5. **Membuat dan Mengisi Playlist**: Masuk ke menu "Koleksi Playlist", buat playlist baru, lalu tambahkan lagu yang Anda sukai ke dalam playlist tersebut.
6. **Menonton Video Klip**: Pada detail lagu, klik tombol "Tonton Video Klip Musik (MV)" untuk memutar video musik YouTube terkait.

---

## Tim Pengembang

Proyek ini dikembangkan oleh Kelompok 2 (Kelas 17.3A.27) untuk pemenuhan Tugas Proyek Akhir Mata Kuliah Sistem Multimedia:

1. Paul Siagian (NIM: 17240668)
2. Arla Raesa Isalim (NIM: 17240410)
3. Muhammad Surya Triabadi (NIM: 17240151)
4. Al Muzamil Sahri Ramadhan (NIM: 17240641)
5. Elrado Keni Sierra (NIM: 17240475)

---

## Lisensi

Proyek ini dirilis untuk keperluan edukasi dan pembelajaran di bawah lisensi terbuka [MIT License](LICENSE).

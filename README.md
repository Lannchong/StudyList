# StudyList — School Task Manager Website

**StudyList** adalah aplikasi web To Do List modern yang dirancang khusus untuk mengelola tugas dan aktivitas belajar siswa di sekolah. Dibangun menggunakan PHP Native dan PDO yang terhubung langsung ke database MySQL sungguhan, dengan frontend HTML5, CSS3 murni, dan JavaScript modern yang responsif.

---

## Fitur Utama

- **Dashboard Statistik Realtime**: Menampilkan total tugas, tugas aktif, dan tugas selesai yang dihitung langsung dari query database MySQL (`COUNT(*)`).
- **Kalender Tugas Bulanan Interaktif**:
  - Tampilan bulan dan tahun dengan tombol navigasi bulan sebelumnya, bulan berikutnya, dan tombol "Hari Ini".
  - Susunan hari Senin sampai Minggu sesuai kalender akademis.
  - Penanda tanggal hari ini (*highlight today*).
  - Indikator warna status tugas pada setiap tanggal: biru untuk aktif/belum selesai, hijau untuk tugas tuntas.
  - Badge jumlah tugas pada tanggal yang memiliki tenggat waktu pengumpulan.
  - Panel detail interaktif: klik tanggal manapun untuk melihat seluruh tugas pada tanggal tersebut beserta tombol edit dan hapus.
  - Integrasi formulir tambah tugas: klik tombol tambah pada kalender otomatis mengisi tanggal tenggat di formulir modal.
- **CRUD Lengkap & Terverifikasi ke MySQL**:
  - **Create**: Tambah tugas baru dengan nama tugas, pilihan mata pelajaran dinamis dari tabel `subjects` MySQL, deskripsi, tanggal pengumpulan, prioritas (*Low*, *Medium*, *High*), dan status (*Pending*, *In Progress*, *Completed*).
  - **Read**: Menampilkan daftar tugas dengan informasi lengkap, relasi nama mata pelajaran menggunakan SQL JOIN, label prioritas, badge status, dan format tenggat pengumpulan ramah pengguna.
  - **Update**: Memperbarui data tugas dengan drawer samping interaktif atau halaman khusus form prefilled; perubahan mata pelajaran otomatis memperbarui `subject_id` foreign key di MySQL.
  - **Delete**: Menghapus tugas dengan modal konfirmasi aman berbasis request POST.
- **Mata Pelajaran Dinamis**: Pilihan mata pelajaran (Matematika, Bahasa Indonesia, Bahasa Inggris, Informatika, Pendidikan Pancasila, Produktif Kejuruan, PJOK, Seni Budaya, Sejarah) diambil langsung dari tabel `subjects` di MySQL, bukan hardcoded.
- **Pencarian & Filter Lengkap**:
  - Pencarian teks realtime berdasarkan nama dan deskripsi tugas.
  - Filter berdasarkan mata pelajaran dari database.
  - Filter berdasarkan status (*Pending*, *In Progress*, *Completed*).
  - Filter berdasarkan prioritas (*Low*, *Medium*, *High*).
  - Kategori navigasi: **Ringkasan**, **Semua Tugas**, **Kalender**, **Hari Ini** (tenggat hari ini), dan **Selesai** (tugas tuntas).
- **Desain Editorial Study Planner**: Menggunakan palet warm cream (`#F7F6F0`), forest green (`#27483D`), soft sage (`#E5ECE5`), white, dan charcoal dengan tipografi Plus Jakarta Sans, navigasi horizontal modern, panel drawer samping kanan, serta layout yang sepenuhnya responsif untuk laptop, tablet, dan smartphone.
- **Keamanan Terjamin**: Menggunakan PDO *Prepared Statements* untuk mencegah SQL Injection, validasi input di server, serta *output sanitization* menggunakan `htmlspecialchars()`.

---

## Struktur Folder Project

```text
schoollist/
│
├── assets/
│   ├── css/
│   │   └── style.css            # Desain CSS murni modern, responsif & kalender
│   └── js/
│       └── script.js            # Interaksi modal, engine kalender & filter
│
├── config/
│   └── database.php             # Koneksi PDO MySQL, no-cache header & helper
│
├── database/
│   └── school_todolist_db.sql   # Skema DB, foreign key & 9 data mapel awal
│
├── includes/
│   ├── header.php               # Header HTML, navigasi atas & flash message
│   ├── navbar.php               # Navigasi horizontal (Ringkasan, Semua Tugas, Kalender, dll.)
│   └── footer.php               # Right Drawer Tambah/Edit, Modal Hapus & script footer
│
├── scripts/
│   └── dev.js                   # Node.js runner untuk PHP built-in server
│
├── index.php                    # Dashboard utama, kalender & daftar tugas
├── create.php                   # Pemrosesan penambahan tugas baru ke MySQL
├── update.php                   # Pemrosesan pembaruan tugas
├── delete.php                   # Pemrosesan penghapusan tugas (POST only)
├── package.json                 # Konfigurasi npm script ("npm run dev")
└── README.md                    # Dokumentasi lengkap panduan aplikasi
```

---

## Panduan Menjalankan Aplikasi

Ikuti 5 langkah mudah berikut untuk menjalankan website StudyList:

### Langkah 1: Menyalakan MySQL melalui XAMPP
1. Buka aplikasi **XAMPP Control Panel** (atau Laragon).
2. Pada baris **MySQL**, klik tombol **Start** hingga indikator berwarna hijau dan port `3306` aktif.
*(Catatan: Layanan Apache di XAMPP tidak wajib dinyalakan karena kita menggunakan PHP built-in server via port 8000).*

### Langkah 2: Mengimpor Database melalui phpMyAdmin
1. Buka browser dan akses phpMyAdmin di:
   ```text
   http://localhost/phpmyadmin
   ```
2. Klik tab **Import** pada menu navigasi atas.
3. Klik tombol **Choose File** / **Browse...** lalu pilih file:
   ```text
   database/school_todolist_db.sql
   ```
4. Gulir ke bawah dan klik tombol **Import** (atau **Go**).
5. Database baru bernama `school_todolist_db` akan otomatis terbuat beserta 2 tabel berelasi:
   - `subjects`: 9 data mata pelajaran awal (termasuk Sejarah).
   - `tasks`: data contoh tugas sekolah dengan tenggat waktu bervariasi (`CURDATE()`, `DATE_ADD()`, `DATE_SUB()`).
6. Anda dapat memverifikasi isi tabel melalui menu **Browse** pada tabel `subjects` dan `tasks`.

### Langkah 3: Membuka Terminal pada Folder Project
Buka terminal (Command Prompt, PowerShell, atau VS Code Terminal) lalu arahkan ke direktori project:
```bash
cd "d:\Todolist Sekolah"
```

### Langkah 4: Menjalankan npm run dev
Jalankan perintah berikut di terminal:
```bash
npm run dev
```

Script akan secara otomatis:
- Mendeteksi PHP di komputer Anda (dari environment variable `PHP_BINARY`, `C:\xampp\php\php.exe`, `D:\xampp\php\php.exe`, atau PATH sistem).
- Menjalankan PHP built-in development server pada alamat `127.0.0.1:8000`.
- Menangani spasi pada nama folder direktori secara aman.
- Menampilkan pesan informasi status server.

### Langkah 5: Membuka Website di Browser
Buka browser favorit Anda (Chrome, Edge, Firefox) dan akses alamat:
```text
http://127.0.0.1:8000
```

Untuk menghentikan server, tekan tombol **Ctrl + C** di terminal.

---

## Skema Database (`school_todolist_db`)

Database dirancang dengan relasi integritas referensial:

```
┌──────────────────────────┐          ┌─────────────────────────────────────────┐
│         subjects         │          │                  tasks                  │
├──────────────────────────┤          ├─────────────────────────────────────────┤
│ id (INT UNSIGNED PK)     │◄────┐    │ id (INT UNSIGNED PK)                    │
│ subject_name (VARCHAR)   │     │    │ subject_id (INT UNSIGNED FK) ───────────┘
│ created_at (TIMESTAMP)   │     │    │ title (VARCHAR 150)                     │
└──────────────────────────┘     │    │ description (TEXT)                      │
                                 └─── │ due_date (DATE)                         │
                                      │ priority (ENUM 'low','medium','high')   │
                                      │ status (ENUM 'pending',...)             │
                                      │ created_at (TIMESTAMP)                  │
                                      │ updated_at (TIMESTAMP)                  │
                                      └─────────────────────────────────────────┘
```

- **Foreign Key Constraint**:
  `CONSTRAINT fk_tasks_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON UPDATE CASCADE ON DELETE RESTRICT`

---

## Skenario Pengujian Database & Fitur

Untuk memastikan sinkronisasi data antara website dan MySQL:

1. **Tambah Tugas**: Tambahkan tugas bernama `Kalkulus` dengan mata pelajaran `Matematika`.
2. **Verifikasi MySQL**: Periksa tabel `tasks` di phpMyAdmin, pastikan kolom `title` bernilai `Kalkulus` dan `subject_id` adalah ID Matematika (nilai: `1`).
3. **Muat Ulang (Refresh)**: Refresh halaman website, pastikan tugas tetap tampil dengan badge Matematika.
4. **Edit Tugas**: Ubah nama tugas menjadi `Kalkulus Lanjut` dan ubah mata pelajaran ke `Informatika`.
5. **Verifikasi Perubahan**: Periksa di MySQL, pastikan `title` dan `subject_id` telah diperbarui ke ID Informatika.
6. **Kalender**: Buka menu **Calendar**, tanggal tugas akan memiliki penanda lingkaran warna dan tampil di panel samping saat tanggal diklik.
7. **Hapus Tugas**: Klik tombol Hapus (ikon tempat sampah), konfirmasi pada modal. Periksa di phpMyAdmin bahwa data tugas telah terhapus permanen dari MySQL.
8. **Statistik**: Kartu statistik Total, Aktif, dan Selesai selalu cocok dengan hasil query `SELECT COUNT(*) FROM tasks`.

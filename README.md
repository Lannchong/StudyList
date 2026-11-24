# StudyList — School Task Manager

**StudyList** adalah aplikasi web pencatat dan pengelola tugas sekolah yang dibangun menggunakan PHP Native dan database MySQL dengan antarmuka bertema **Blue Academic Planner**.

Aplikasi ini dirancang untuk memudahkan siswa mencatat, memantau tenggat waktu (*deadline*), dan mengatur prioritas belajar secara teratur, baik dalam mode terang (*Light Mode*) maupun mode gelap (*Dark Mode*).

---

## A. Tentang StudyList

* **Apa itu StudyList?**: Aplikasi to-do list tugas sekolah yang terhubung langsung ke database MySQL sungguhan menggunakan koneksi PHP PDO.
* **Tujuan Website**: Membantu siswa mengelola jadwal dan tugas akademik agar tidak ada tugas yang terlewat atau terlambat dikumpulkan.
* **Kegunaan Website**: Memberikan ruang belajar digital yang rapi, modern, dan bebas distraksi dengan informasi status, prioritas, mata pelajaran, serta kalender tugas bulanan.
* **Konsep Desain**: **Blue Academic Planner** — perpaduan palet biru modern, clean, dan profesional tanpa warna hijau, memberikan kenyamanan mata saat digunakan dalam durasi belajar yang lama.

---

## B. Fitur Utama

1. **Tambah Tugas**: Menambahkan tugas baru lengkap dengan nama, mata pelajaran, tenggat waktu, prioritas, status, dan deskripsi melalui panel drawer samping kanan.
2. **Lihat Tugas**: Menampilkan seluruh tugas dalam susunan baris meja (*horizontal row*) yang rapi dan terstruktur.
3. **Edit Tugas**: Memperbarui informasi tugas yang sudah tersimpan melalui form drawer edit.
4. **Hapus Tugas**: Menghapus tugas yang sudah selesai atau dibatalkan dengan modal konfirmasi aman berbasis request POST.
5. **Status Pengerjaan**: Membagi progres tugas menjadi *Belum Dikerjakan*, *Sedang Dikerjakan*, dan *Selesai* (dengan efek coret teks halus).
6. **Tingkat Prioritas**: Mengelompokkan urgensi pengerjaan tugas (*Rendah*, *Sedang*, dan *Tinggi*).
7. **Mata Pelajaran Dinamis**: Pilihan mata pelajaran (Matematika, Bahasa Indonesia, Informatika, dll.) diambil langsung dari database MySQL.
8. **Pencarian & Filter**: Mencari tugas berdasarkan kata kunci dan menyaring daftar berdasarkan mata pelajaran, status, prioritas, atau tab ringkas (*Semua*, *Aktif*, *Selesai*).
9. **Kalender Tugas**: Menampilkan jadwal tugas bulanan interaktif lengkap dengan penanda tanggal dan panel rincian tugas per hari.
10. **Dark Mode & Light Mode**: Tombol pengganti tema pada navbar (ikon Matahari & Bulan) dengan perubahan instan tanpa reload dan tersimpan di `localStorage`.
11. **Drag & Drop Pengurutan Tugas**: Mengatur posisi tugas dengan menarik ikon grip di sebelah kiri tugas pada halaman Semua Tugas. Urutan tersimpan permanen ke database MySQL (`tasks.sort_order`) melalui AJAX transaction.
12. **Jam Pengumpulan (Deadline Time)**: Menentukan jam tenggat tugas (format 24 jam `HH.MM WIB`). Terintegrasi dengan perhitungan status keterlambatan (*overdue*) berbasis zona waktu Asia/Jakarta dan pengurutan kronologis di panel detail kalender.

---

## C. Identitas Desain & Palet Warna

StudyList menggunakan konsep **Blue Academic Planner** yang konsisten di seluruh elemen antarmuka:

### Light Mode
* **Background**: `#F5F8FF` (Biru es terang lembut)
* **Surface / Card**: `#FFFFFF` (Putih bersih)
* **Primary Blue**: `#2563EB` (Biru akademik utama)
* **Primary Hover**: `#1D4ED8` (Biru tua saat hover)
* **Soft Blue**: `#DBEAFE` (Aksen biru lembut)
* **Border**: `#E2E8F0` (Garis tepi halus)
* **Text Primary**: `#172033` (Teks judul tajam)
* **Text Secondary**: `#64748B` (Teks pendukung abu-abu)

### Dark Mode
* **Background Utama**: `#0B1220` (Midnight navy pekat nyaman di mata)
* **Surface / Card**: `#172338` (Kartu biru gelap kontras tinggi)
* **Secondary Surface**: `#1E2C43` (Area kontrol, input form & header sekunder)
* **Border**: `#34445C` (Garis pemisah rapi dan jelas)
* **Text Primary**: `#F8FAFC` (Teks judul putih jernih)
* **Text Secondary**: `#CBD5E1` (Teks pendukung abu-abu terang)
* **Primary Blue**: `#60A5FA` (Biru terang fokus tinggi)
* **Accent Blue**: `#3B82F6` (Biru tombol aksi utama)

---

## D. Teknologi yang Digunakan

* **HTML5**: Menyusun struktur semantik antarmuka website.
* **CSS3**: Mengatur tata letak (Flexbox & Grid), animasi drawer, palet warna Blue Academic Planner, serta tema Dark Mode & Light Mode tanpa framework luar.
* **JavaScript (ES6+)**: Menangani interaksi buka-tutup drawer, modal konfirmasi, pergantian tema, auto-submit filter, dan engine kalender.
* **PHP Native 8.x**: Memproses logika aplikasi, validasi form di server, dan sanitasi output HTML (anti-XSS).
* **PDO (PHP Data Objects)**: Lapisan koneksi database yang aman dengan *Prepared Statements* untuk mencegah serangan SQL Injection.
* **MySQL**: Menyimpan seluruh data mata pelajaran dan daftar tugas dalam tabel berelasi.
* **Node.js & npm**: Menjalankan development server lokal melalui perintah `npm run dev` (`scripts/dev.js`).

---

## E. Struktur Folder Project

```text
schoollist/
│
├── assets/
│   ├── css/
│   │   └── style.css            # Styling visual Blue Academic Planner, Dark Mode & responsive
│   └── js/
│       └── script.js            # Interaksi drawer, modal hapus, tema & kalender
│
├── config/
│   └── database.php             # Koneksi PHP ke database MySQL (PDO) & fungsi helper
│
├── database/
│   ├── migration_sort_order_due_time.sql # Skrip migrasi kolom sort_order & due_time
│   └── school_todolist_db.sql   # Skema database, tabel subjects & tasks, data awal
│
├── includes/
│   ├── header.php               # Tag <head>, script deteksi tema, dan notifikasi
│   ├── navbar.php               # Navigasi 4 menu, tombol tema & tombol tambah tugas
│   └── footer.php               # Markup drawer tambah/edit, modal hapus, load script
│
├── scripts/
│   └── dev.js                   # Runner server PHP built-in untuk npm run dev
│
├── create.php                   # Menambahkan tugas baru ke MySQL
├── delete.php                   # Menghapus tugas dari MySQL (POST)
├── index.php                    # Halaman utama (daftar tugas & kalender)
├── reorder.php                  # Endpoint AJAX untuk menyimpan urutan drag & drop (POST)
├── package.json                 # Konfigurasi npm script ("dev": "node scripts/dev.js")
├── README.md                    # Dokumentasi panduan project
└── update.php                   # Memperbarui data tugas di MySQL (POST)
└── update.php                   # Memperbarui data tugas di MySQL
```

---

## F. Database MySQL (`school_todolist_db`)

Database terdiri dari 2 tabel utama yang saling berelasi:

1. **`subjects` (Mata Pelajaran)**:
   * `id`: Kunci utama (*Primary Key*, Auto Increment).
   * `subject_name`: Nama mata pelajaran (unik, misal: Matematika, Bahasa Inggris, Informatika).
   * `created_at`: Waktu data dibuat.

2. **`tasks` (Daftar Tugas)**:
   * `id`: Kunci utama (*Primary Key*, Auto Increment).
   * `subject_id`: Kunci asing (*Foreign Key*) yang terhubung ke `subjects.id`.
   * `title`: Nama tugas (wajib diisi).
   * `description`: Catatan instruksi tugas (opsional).
   * `due_date`: Tanggal pengumpulan tugas (`YYYY-MM-DD`).
   * `priority`: Tingkat prioritas (`low`, `medium`, `high`).
   * `status`: Status pengerjaan (`pending`, `in_progress`, `completed`).
   * `created_at` & `updated_at`: Waktu pencatatan dan pembaruan data.

> **Hubungan Relasi**: Kolom `tasks.subject_id` berelasi langsung dengan `subjects.id` dengan aturan `ON UPDATE CASCADE` dan `ON DELETE RESTRICT`. Artinya, setiap tugas wajib memiliki mata pelajaran yang sah di database.

---

## G. File Penting dan Fungsi Kodenya

Berikut adalah daftar file utama dan perannya dalam menghubungkan website dengan database:

| File | Fungsi Utama | Hubungan dengan Database |
| :--- | :--- | :--- |
| **`config/database.php`** | **Menghubungkan PHP dengan MySQL** | Membuka koneksi `new PDO()`, mengatur charset UTF-8, menangani error koneksi, dan menyediakan fungsi sanitasi data. |
| **`index.php`** | Mengambil dan menampilkan data tugas | Menjalankan query `SELECT ... JOIN subjects` untuk menampilkan daftar tugas, menghitung total statistik, dan menyaring filter pencarian. |
| **`create.php`** | Menambahkan tugas ke database | Memvalidasi input form dari drawer lalu mengeksekusi query `INSERT INTO tasks`. |
| **`update.php`** | Memperbarui data tugas | Menerima data editan lalu mengeksekusi query `UPDATE tasks SET ... WHERE id = ?`. |
| **`delete.php`** | Menghapus tugas dari database | Menerima ID tugas via POST lalu mengeksekusi query `DELETE FROM tasks WHERE id = ?`. |
| **`assets/js/script.js`** | Mengatur interaksi website | Menangani buka-tutup drawer, modal hapus, filter, kalender, dan toggle Dark/Light Mode. |
| **`assets/css/style.css`** | Mengatur tampilan website | Mendefinisikan warna tema (Light & Dark Mode), layout tabel horizontal, drawer, dan responsive. |

---

## H. Cara Menjalankan Website

Ikuti 5 langkah mudah berikut untuk menjalankan StudyList di komputer lokal:

### 1. Menyalakan MySQL di XAMPP atau Laragon
Buka **XAMPP Control Panel** (atau Laragon), lalu klik **Start** pada baris **MySQL** hingga port `3306` aktif.

### 2. Mengimpor Database melalui phpMyAdmin
* Buka browser dan akses `http://localhost/phpmyadmin`.
* Klik tab **Import**.
* Pilih file database pada project:
  ```text
  database/school_todolist_db.sql
  ```
* Klik tombol **Import** (atau **Go**). Database `school_todolist_db` akan terbuat secara otomatis.

### 3. Masuk ke Folder Project
Buka terminal (Command Prompt, PowerShell, atau terminal VS Code), lalu arahkan ke direktori project:
```bash
cd "d:\Todolist Sekolah"
```

### 4. Menjalankan npm run dev
Jalankan perintah berikut:
```bash
npm run dev
```
Script akan mendeteksi PHP dan mengaktifkan web server lokal di port 8000.

### 5. Membuka Website di Browser
Buka browser dan akses alamat:
```text
http://127.0.0.1:8000
```
Untuk mematikan web server lokal, tekan `Ctrl + C` pada jendela terminal.

---

## I. Contoh Alur CRUD

1. **CREATE (Tambah Tugas)**:
   Pengguna mengklik tombol **+ Tambah Tugas** -> mengisi form pada drawer samping -> data dikirim via POST ke `create.php` -> PHP memvalidasi input -> query `INSERT INTO tasks` dijalankan -> halaman kembali ke daftar tugas dengan notifikasi sukses.

2. **READ (Melihat Tugas)**:
   Ketika membuka `index.php`, query `SELECT tasks.*, subjects.subject_name FROM tasks JOIN subjects ...` dijalankan -> seluruh baris tugas ditampilkan lengkap dengan badge prioritas, status, dan tanggal tenggat.

3. **UPDATE (Edit Tugas)**:
   Pengguna mengklik ikon pensil pada baris tugas -> form drawer edit terisi otomatis dengan data tugas terkait -> pengguna mengubah data dan mengklik **Simpan Perubahan** -> dikirim ke `update.php` -> query `UPDATE tasks SET ... WHERE id = ?` dijalankan di MySQL.

4. **DELETE (Hapus Tugas)**:
   Pengguna mengklik ikon tempat sampah -> popup konfirmasi muncul menampilkan judul tugas -> pengguna mengonfirmasi -> dikirim ke `delete.php` via POST -> query `DELETE FROM tasks WHERE id = ?` menghapus baris dari MySQL secara permanen.

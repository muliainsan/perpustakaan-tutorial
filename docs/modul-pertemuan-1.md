# Modul Praktikum Pertemuan 1: Fondasi Basis Data, Migrasi, dan Seeder

---

## 1. Tujuan Pembelajaran

Setelah menyelesaikan praktikum pertemuan 1 ini, mahasiswa diharapkan mampu:
1. Menjelaskan perbedaan fundamental antara akses basis data murni (*Native PDO*) dengan *Database Abstraction Layer* pada framework modern.
2. Merancang dan mengimplementasikan skema basis data relasional (*One-to-Many*, *Many-to-Many*, *Composite Primary/Unique Key*) menggunakan **Laravel Migrations**.
3. Menerapkan aturan integritas referensial basis data (*foreign key constraints*: `restrict` dan `cascade`) dengan benar.
4. Membuat dan mengeksekusi **Model Factories** dan **Database Seeders** yang bersifat deterministik serta idempoten untuk menyiapkan data pengujian awal.
5. Menguji kebenaran skema dan data awal menggunakan automated test berbasis **PHPUnit / Laravel Feature Test**.

---

## 2. Prasyarat & Perangkat

Sebelum memulai praktikum, pastikan lingkungan kerja telah siap:
- **Sistem Operasi:** macOS / Linux / Windows
- **PHP:** Versi 8.3 atau lebih baru (`php -v`)
- **Composer:** Versi 2.x (`composer -V`)
- **Basis Data:** MySQL / MariaDB (MAMP atau MySQL Standar) dengan database `perpus` telah dibuat
- **Editor:** Visual Studio Code / Cursor / PHPStorm
- **Akses Terminal:** Bash / Zsh

---

## 3. Ringkasan Teori

### 3.1 Mengapa Bukan PDO Murni?
PHP Data Objects (PDO) adalah antarmuka bawaan PHP untuk berkomunikasi dengan basis data. Kelebihannya adalah dukungan *prepared statements* dengan *parameter binding* yang mencegah celah keamanan **SQL Injection**. Namun, pada aplikasi skala menengah-besar, penulisan query SQL mentah menggunakan PDO murni menimbulkan masalah:
- Kode berulang (*boilerplate*) untuk operasi dasar CRUD.
- Pemetaan data baris (array) ke model objek dilakukan manual.
- Hubungan antartabel (JOIN) harus ditulis manual dan rawan kekeliruan sintaksis.
- Tidak adanya pelacakan versi skema (*schema version control*).

Laravel menyelesaikan tantangan ini melalui **Migrations** (sebagai *version control* skema database) dan **Eloquent ORM** (sebagai representasi objek dari tabel).

### 3.2 Integritas Referensial: Restrict vs Cascade
Dalam basis data relasional:
- **`ON DELETE RESTRICT`:** Mencegah penghapusan record induk jika masih ada record anak yang merujuk padanya. Contoh: Kategori tidak boleh dihapus jika masih menaungi buku; Pengguna tidak boleh dihapus jika memiliki riwayat peminjaman.
- **`ON DELETE CASCADE`:** Menghapus otomatis record anak jika record induk dihapus. Contoh: Menghapus buku akan menghapus entri relasi di tabel pivot `author_book`.

---

## 4. Langkah Praktikum Berurutan

### Langkah 1: Memahami Native PDO melalui Folder `pdo-demo/`
Buka terminal di root proyek dan jalankan skrip demo PDO:
```bash
php pdo-demo/index.php
```
Pelajari berkas [pdo-demo/index.php](file:///Users/insanmulia/Documents/developer/laravel/perpustakaan/pdo-demo/index.php). Amati bagaimana parameter binding dilakukan dengan `:title` dan `:category_id` untuk mencegah SQL Injection, serta bagaimana transaksi atomik dikelola dengan `$pdo->beginTransaction()`.

---

### Langkah 2: Mengonfigurasi Basis Data pada `.env`
Buka berkas `.env` dan pastikan kredensial basis data mengarah ke database `perpus`:
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=perpus
DB_USERNAME=root
DB_PASSWORD=root
DB_SOCKET=/Applications/MAMP/tmp/mysql/mysql.sock
```

---

### Langkah 3: Menyesuaikan Migrasi Tabel `users`
Perbarui berkas migrasi bawaan di `database/migrations/0001_01_01_000000_create_users_table.php` agar mendukung peran perpustakaan (`role`), kode anggota (`member_code`), dan nomor telepon (`phone`):
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->enum('role', ['admin', 'petugas', 'anggota'])->default('anggota');
    $table->string('member_code')->nullable()->unique();
    $table->string('phone')->nullable();
    $table->rememberToken();
    $table->timestamps();
});
```

---

### Langkah 4: Membuat Migrasi Entitas Master & Relasi
Buat migrasi untuk tabel `categories`, `authors`, `books`, `author_book`, `loans`, dan `loan_items`:

1. **`categories`:**
   ```php
   Schema::create('categories', function (Blueprint $table) {
       $table->id();
       $table->string('name')->unique();
       $table->timestamps();
   });
   ```

2. **`authors`:**
   ```php
   Schema::create('authors', function (Blueprint $table) {
       $table->id();
       $table->string('name');
       $table->timestamps();
   });
   ```

3. **`books`:**
   ```php
   Schema::create('books', function (Blueprint $table) {
       $table->id();
       $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
       $table->string('title');
       $table->string('isbn')->nullable()->unique();
       $table->string('publisher')->nullable();
       $table->smallInteger('year')->nullable();
       $table->unsignedInteger('stock')->default(0);
       $table->timestamps();
   });
   ```

4. **`author_book` (Tabel Pivot Many-to-Many):**
   ```php
   Schema::create('author_book', function (Blueprint $table) {
       $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
       $table->foreignId('author_id')->constrained('authors')->onDelete('cascade');
       $table->primary(['book_id', 'author_id']);
   });
   ```

5. **`loans`:**
   ```php
   Schema::create('loans', function (Blueprint $table) {
       $table->id();
       $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
       $table->date('loaned_at');
       $table->date('due_date');
       $table->date('returned_at')->nullable();
       $table->decimal('fine_amount', 10, 2)->default(0);
       $table->enum('fine_status', ['none', 'unpaid', 'paid'])->default('none');
       $table->timestamps();
   });
   ```

6. **`loan_items`:**
   ```php
   Schema::create('loan_items', function (Blueprint $table) {
       $table->id();
       $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
       $table->foreignId('book_id')->constrained('books')->onDelete('restrict');
       $table->unique(['loan_id', 'book_id']);
       $table->timestamps();
   });
   ```

---

### Langkah 5: Membuat Berkas Konfigurasi Bisnis Perpustakaan
Buat berkas `config/library.php` untuk memusatkan parameter aturan bisnis aplikasi:
```php
<?php

return [
    'loan_days'    => 7,      // Batas durasi peminjaman (hari)
    'max_books'    => 3,      // Kuota maksimal buku per peminjaman
    'fine_per_day' => 1000,   // Denda harian keterlambatan per buku (Rp)
    'fine_max'     => 50000,  // Plafon denda maksimal per buku (Rp)
];
```

---

### Langkah 6: Membuat Database Seeder
Lengkapi berkas `database/seeders/DatabaseSeeder.php` untuk mengisi:
- **1 Admin:** `admin@perpus.test`
- **1 Petugas:** `petugas@perpus.test`
- **5 Anggota:** `anggota1@perpus.test` s.d. `anggota5@perpus.test`
- **5 Kategori:** Pemrograman, Sains, Sastra, Sejarah, Bisnis
- **10 Penulis:** Nama-nama penulis buku referensi
- **40 Buku:** Buku yang terdistribusi ke masing-masing kategori dan terhubung ke minimal satu penulis
- **Sampel Peminjaman:** 3 transaksi peminjaman (aktif, selesai tepat waktu, dan terlambat/berdenda)

---

### Langkah 7: Eksekusi Migrasi dan Seeding
Jalankan perintah berikut di terminal:
```bash
php artisan migrate:fresh --seed
```
Pastikan seluruh tabel berhasil dibuat dan proses seeding selesai tanpa galat.

---

### Langkah 8: Menguji dengan Feature Test
Jalankan automated test suite untuk memverifikasi keutuhan data:
```bash
php artisan test
```

---

## 5. Titik Pengecekan (*Checkpoint*)

| Komponen | Indikator Keberhasilan | Perintah Pengecekan |
|---|---|---|
| **Demo Native PDO** | Berhasil create, read, update, dan delete buku uji coba | `php pdo-demo/index.php` |
| **Migrasi Skema** | Seluruh 9 tabel tercatat dengan status `[Ran]` | `php artisan migrate:status` |
| **Jumlah Pengguna** | Tepat 7 pengguna (1 admin, 1 petugas, 5 anggota) | `php artisan tinker --execute="echo App\Models\User::count();"` |
| **Jumlah Buku** | Tepat 40 buku terdaftar | `php artisan tinker --execute="echo App\Models\Book::count();"` |
| **Jumlah Kategori** | Tepat 5 kategori terdaftar | `php artisan tinker --execute="echo App\Models\Category::count();"` |
| **Jumlah Penulis** | Tepat 10 penulis terdaftar | `php artisan tinker --execute="echo App\Models\Author::count();"` |
| **Feature Test** | Seluruh pengujian (5 tests) berstatus **PASSED (Hijau)** | `php artisan test` |

---

## 6. Troubleshooting

### 1. Galat: `SQLSTATE[HY000] [2002] Connection refused` atau `No such file or directory`
- **Penyebab:** MySQL belum aktif atau port/socket berbeda dari konfigurasi default.
- **Solusi:** Jika menggunakan MAMP, pastikan server MySQL MAMP berstatus hijau dan tambahkan `DB_SOCKET=/Applications/MAMP/tmp/mysql/mysql.sock` pada `.env`. Jika menggunakan MySQL standar, gunakan `DB_PORT=3306` dan kosongkan `DB_SOCKET`.

### 2. Galat: `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry`
- **Penyebab:** Seeder dijalankan berulang tanpa membersihkan data lama.
- **Solusi:** Gunakan `php artisan migrate:fresh --seed` atau gunakan fungsi `firstOrCreate()` pada `DatabaseSeeder.php` agar seeder bersifat idempoten.

### 3. Galat: `Cannot delete or update a parent row: a foreign key constraint fails`
- **Penyebab:** Mencoba menghapus baris kategori yang masih dirujuk oleh tabel `books` berkat aturan `onDelete('restrict')`.
- **Solusi:** Ini adalah perilaku yang diharapkan! Hapus terlebih dahulu buku-buku di dalam kategori tersebut sebelum menghapus kategori induknya.

---

## 7. Tugas Mandiri

1. **Latihan 1 (Eksplorasi Skema):** Tambahkan kolom `edition` (String, nullable) pada tabel `books`. Buat migrasi baru menggunakan perintah `php artisan make:migration add_edition_to_books_table --table=books`.
2. **Latihan 2 (Seeder Lanjutan):** Tambahkan factory state pada `UserFactory` untuk menghasilkan anggota dengan status nomor telepon terverifikasi.
3. **Tantangan Praktikum:** Buat query native PDO mandiri di dalam skrip PHP terpisah yang menghitung total estimasi denda dari seluruh transaksi peminjaman yang berstatus belum kembali (`returned_at IS NULL`) dan telah melewati tanggal jatuh tempo (`due_date < CURDATE()`).

---

## 8. Rubrik Penilaian

| Kriteria | Bobot | Deskripsi Kinerja Maksimal |
|---|---|---|
| **Ketepatan Skema & Migrasi** | 30% | Seluruh tabel, tipe data, indeks, dan batasan foreign key (`restrict` & `cascade`) terpasang sesuai spesifikasi. |
| **Kualitas Seeder & Factory** | 25% | Seeder bersifat idempoten dan menghasilkan kuantitas data yang tepat (7 users, 5 kategori, 10 penulis, 40 buku, sampel peminjaman). |
| **Pemahaman Native PDO** | 20% | Mampu menjelaskan peran prepared statement dan perbandingannya dengan Eloquent ORM. |
| **Pengujian Otomatis (*Testing*)** | 15% | Feature Test dibuat dengan baik dan 100% lulus (hijau). |
| **Kerapian & Kepatuhan Konvensi** | 10% | Komentar edukatif berbahasa Indonesia dan penamaan kode standar Laravel (PSR-12). |


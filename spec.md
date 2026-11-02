# Proyek Praktikum: Aplikasi Perpustakaan dengan Laravel

Dokumen ini adalah spesifikasi lengkap dan aturan kerja terpadu untuk AI coding agent (Google Antigravity/Cursor/Windsurf). Bangun proyek **bertahap per pertemuan** (4 pertemuan) dan hasilkan juga **modul tutorial** untuk mahasiswa pada setiap pertemuan. Seluruh keputusan desain telah ditetapkan langsung pada dokumen ini tanpa perlu membuat berkas catatan keputusan terpisah.

---

## 1. Konteks dan Tujuan

- Proyek ini adalah bahan **praktikum mata kuliah pemrograman web** untuk mahasiswa.
- Satu proyek (aplikasi perpustakaan) dibangun bertahap selama 4 pertemuan. Hasil tiap pertemuan menjadi dasar pertemuan berikutnya.
- Proyek memiliki **dua antarmuka di atas logika yang sama**: web app (Blade, berbasis sesi) dan REST API (JSON, berbasis token). Keduanya wajib lengkap, teruji, dan terdokumentasi.
- Cakupan materi:
  1. Perancangan basis data relasional: hubungan tabel, kunci, aturan integritas.
  2. PHP dan MySQL: SQL serta CRUD dengan PDO/MySQLi (dasar pemahaman).
  3. Konsep MVC.
  4. Laravel: routing, controller, Blade, model, migration, Eloquent ORM.
  5. Rancangan endpoint API dan integrasi frontend-backend.
  6. Keamanan web dasar: autentikasi, otorisasi, sesi, proteksi input/output.
  7. Pengujian fitur utama (Unit & Feature Test) dan peninjauan kode.

---

## 2. Aturan Kerja untuk AI Agent

1. **Sederhana dan edukatif:** Kode harus mudah dipahami mahasiswa pemula. Hindari abstraksi berlebihan (tanpa *repository pattern*, tanpa *service container* kustom, tanpa paket pihak ketiga di luar yang ditentukan).
2. **Komentar berbahasa Indonesia** pada blok kode esensial pembelajaran. Nama variabel, tabel, kolom, method, dan class tetap menggunakan Bahasa Inggris.
3. **Kerjakan per pertemuan secara berurutan.** Dilarang mengimplementasikan fitur pertemuan berikutnya sebelum tahap berjalan selesai dan terverifikasi.
4. Sebelum membuat tiap commit, jalankan `php artisan migrate:fresh --seed` dan `php artisan test`. Keduanya wajib lulus (hijau).
5. Riwayat Git wajib terdiri dari **tepat 5 commit** pada branch `main` dengan tag yang sesuai:
   - **Commit 1:** `init: proyek Laravel baru` (Tag: `proyek-awal`)
   - **Commit 2:** `pertemuan-1: fondasi basis data dan persiapan laravel` (Tag: `pertemuan-1`)
   - **Commit 3:** `pertemuan-2: crud dengan mvc laravel` (Tag: `pertemuan-2`)
   - **Commit 4:** `pertemuan-3: peminjaman denda dan integrasi api` (Tag: `pertemuan-3`)
   - **Commit 5:** `pertemuan-4: keamanan autentikasi pengujian dan review kode` (Tag: `pertemuan-4`)
   *(Jika terjadi kekeliruan, gunakan `git commit --amend` atau rebase interaktif agar total commit tetap 5).*
6. **Laravel 13 & PHP 8.3+:** Gunakan framework versi `laravel/framework:^13.0`.
7. **Tanpa Berkas Catatan Keputusan Terpisah:** Seluruh keputusan arsitektur dan teknis telah dibakukan langsung pada Bagian 5, 6, dan 7 dokumen ini. Agen tidak perlu membuat `docs/catatan-keputusan.md`.
8. **Keamanan Kredensial:** Jangan simpan rahasia / credential di repo; gunakan `.env.example`.
9. **Kepatuhan Database:** Jangan mengganti MySQL/MariaDB dengan SQLite secara diam-diam. Laporkan status verifikasi apa adanya jika lingkungan belum mendukung.

---

## 3. Spesifikasi Teknologi

| Komponen | Pilihan |
|---|---|
| Framework | Laravel 13 (`laravel/framework:^13.0`) |
| Bahasa | PHP 8.3 atau lebih baru |
| Basis Data | MySQL / MariaDB (Driver `mysql`) |
| Frontend Web | Blade Templates + Tailwind CSS (Vite), tanpa CDN |
| JavaScript Frontend | Vanilla JS (`fetch`), tanpa framework JavaScript eksternal |
| Autentikasi Web | Manual via `Auth` facade & session (tanpa Breeze/starter kit) |
| Autentikasi API | Laravel Sanctum (Token Personal via Bearer Header) |
| Testing Engine | PHPUnit bawaan Laravel (Feature & Unit Test pada database MySQL terpisah) |

---

## 4. Rancangan Basis Data & Integritas

### 4.1 Skema Tabel

**`users`**
- `id` (PK, BigInt Increment)
- `name` (String)
- `email` (String, Unique)
- `password` (String)
- `role` (Enum: `'admin'`, `'petugas'`, `'anggota'`, default `'anggota'`)
- `member_code` (String, Nullable, Unique)
- `phone` (String, Nullable)
- `timestamps`

**`categories`**
- `id` (PK, BigInt Increment)
- `name` (String, Unique)
- `timestamps`

**`authors`**
- `id` (PK, BigInt Increment)
- `name` (String)
- `timestamps`

**`books`**
- `id` (PK, BigInt Increment)
- `category_id` (FK -> `categories.id`, `onDelete('restrict')`)
- `title` (String)
- `isbn` (String, Nullable, Unique)
- `publisher` (String, Nullable)
- `year` (SmallInteger, Nullable)
- `stock` (UnsignedInteger, Default: 0)
- `timestamps`

**`author_book`** (Pivot Table Many-to-Many)
- `book_id` (FK -> `books.id`, `onDelete('cascade')`)
- `author_id` (FK -> `authors.id`, `onDelete('cascade')`)
- Primary Key Gabungan: `['book_id', 'author_id']`

**`loans`**
- `id` (PK, BigInt Increment)
- `user_id` (FK -> `users.id`, `onDelete('restrict')`)
- `loaned_at` (Date)
- `due_date` (Date)
- `returned_at` (Date, Nullable)
- `fine_amount` (Decimal: 10, 2, Default: 0)
- `fine_status` (Enum: `'none'`, `'unpaid'`, `'paid'`, Default `'none'`)
- `timestamps`

**`loan_items`**
- `id` (PK, BigInt Increment)
- `loan_id` (FK -> `loans.id`, `onDelete('cascade')`)
- `book_id` (FK -> `books.id`, `onDelete('restrict')`)
- Unique Key Gabungan: `['loan_id', 'book_id']`
- `timestamps`

### 4.2 Aturan Integritas Wajib
- **Restrict Delete:** Kategori tidak boleh dihapus jika memiliki relasi buku. Buku tidak boleh dihapus jika pernah dicatat dalam peminjaman (`loan_items`). Tangkap exception relasi (`QueryException`) dan kirimkan respon ramah/status HTTP 409.
- **Pencegahan Stok Negatif:** Validasi dilakukan di aplikasi dan dilindungi transaksi database dengan `lockForUpdate()`.

---

## 5. Keputusan Desain & Aturan Bisnis Terpadu

Semua konfigurasi batasan operasional didefinisikan pada `config/library.php`:

```php
return [
    'loan_days'    => 7,      // Lama waktu peminjaman (hari)
    'max_books'    => 3,      // Maksimal buku dalam 1 transaksi
    'fine_per_day' => 1000,   // Denda per buku per hari keterlambatan (Rp)
    'fine_max'     => 50000,  // Plafon denda maksimal per buku (Rp)
];
```

### 5.1 Arsitektur Logika Tunggal (*Fat Model / Thin Controller*)
Agar tidak ada duplikasi kode antara Web Controller dan API Controller:
1. **Peminjaman (`Loan::borrow(int $userId, array $bookIds): Loan`):**
   - Validasi batas peminjaman (1 s.d. 3 buku) dan ketiadaan pinjaman overdue/denda unpaid.
   - Menggunakan `DB::transaction()` dan `Book::whereIn('id', $bookIds)->lockForUpdate()->get()`.
   - Pastikan seluruh buku yang diminta berstatus stok $> 0$. Jika ada stok kosong, lempar `ValidationException` atau *domain exception* terukur.
   - Buat record pada `loans` dan `loan_items`, lalu kurangi stok masing-masing buku secara serentak.
2. **Pengembalian (`$loan->returnBooks(?string $returnedAt = null): void`):**
   - Tanggal kembali default adalah hari ini jika parameter null.
   - Kembalikan stok buku (`$book->increment('stock')`).
   - Panggil `$this->hitungDenda()` dan perbarui `fine_amount` serta `fine_status`.
3. **Kalkulasi Denda (`$loan->hitungDenda(): float`):**
   - `hari_terlambat = max(0, selisih_hari(returned_at, due_date))`
   - `denda_per_buku = min(hari_terlambat * fine_per_day, fine_max)`
   - `fine_amount = denda_per_buku * jumlah_buku`
   - Jika `fine_amount > 0` maka `fine_status = 'unpaid'`, jika 0 maka `'none'`.
4. **Pelunasan Denda (`$loan->markFinePaid(): void`):**
   - Mengubah `fine_status` menjadi `'paid'`.

---

## 6. Hak Akses dan Matriks Otorisasi

| Aksi | Admin | Petugas | Anggota |
|---|---|---|---|
| Kelola Data Master (Kategori, Penulis, Buku) | Ya | Ya | Tidak |
| Kelola Data Pengguna & Ubah Role | Ya | Tidak | Tidak |
| Catat Peminjaman Anggota | Ya | Ya | Tidak (Hanya via API untuk dirinya) |
| Catat Pengembalian & Pelunasan Denda | Ya | Ya | Tidak |
| Akses Katalog Publik | Ya | Ya | Ya |
| Riwayat Peminjaman | Semua User | Semua User | Hanya Milik Sendiri |

Implementasikan otorisasi menggunakan Policy Laravel (`LoanPolicy`, `BookPolicy`, dll.) dan Middleware peran (`EnsureUserHasRole`). Pencegahan IDOR (*Insecure Direct Object Reference*) wajib diverifikasi lewat automated test.

---

## 7. Rute & Spesifikasi API/Web

### 7.1 Web (Blade)
- `/` : Katalog publik dengan interaktivitas Vanilla JS `fetch`.
- `/login`, `/register`, `/logout` : Autentikasi sesi manual tanpa starter kit.
- `/admin/categories`, `/admin/authors`, `/admin/books` : Manajemen data master (CRUD).
- `/admin/users` : Khusus role `admin` (update role user).
- `/loans` : Form peminjaman & monitoring (Petugas & Admin).
- `/my/loans` : Riwayat peminjaman khusus Anggota yang sedang login.

### 7.2 REST API (`routes/api.php`)
- **Format Respons Galat:** Konfigurasi `bootstrap/app.php` agar setiap rute ber-prefix `/api/*` selalu mengembalikan respons format JSON `{"message": "...", "errors": {...}}` meskipun header `Accept: application/json` tidak disertakan oleh client.
- **Standar Endpoint:**
  - `GET /api/books` : Pagination standar (`data`, `links`, `meta`), parameter filter: `search`, `category_id`, `available=1`, `per_page` (max 50).
  - `GET /api/books/{id}` : Detail buku (200 / 404).
  - `GET /api/categories`, `GET /api/authors` : Master referensi.
  - `GET /api/loans`, `GET /api/loans/{id}` : Riwayat & detail peminjaman.
  - `POST /api/loans` : Submit pinjaman baru.
  - `POST /api/loans/{id}/return` : Eksekusi pengembalian.
  - `POST /api/loans/{id}/pay-fine` : Pelunasan denda.
  - `POST /api/login`, `POST /api/logout`, `GET /api/me` : Sanctum Token Authentication.

---

## 8. Rencana Kerja Bertahap (Commit & Milestone)

### Commit 1: Inisialisasi Proyek (`proyek-awal`)
1. Setup instalasi Laravel 13 (`laravel/laravel:^13.0`).
2. Konfigurasi `.env.example` ke driver MySQL dengan nama database `perpustakaan`.
3. Buat dokumentasi `README.md` awal (panduan instalasi & dependensi).
4. Buat skrip verifikasi otomatis `scripts/verifikasi-tag.sh`.
5. Eksekusi `scripts/verifikasi-tag.sh proyek-awal`. Buat commit dan tag `proyek-awal`.

### Pertemuan 1: Basis Data & Migration (`pertemuan-1`)
1. Buat folder `pdo-demo/` berisi script CRUD mandiri native PDO tabel `books`.
2. Buat skema ERD format Mermaid di `docs/erd.md`.
3. Tulis seluruh migration sesuai Bagian 4.1.
4. Buat Seeder dan Factory: 1 Admin, 1 Petugas, 5 Anggota, 5 Kategori, 10 Penulis, 40 Buku, dan sampel peminjaman.
5. Setup `config/library.php` dan database testing `perpustakaan_test` di `phpunit.xml`.
6. Tulis modul panduan mahasiswa `docs/modul-pertemuan-1.md`.
7. Luluskan `scripts/verifikasi-tag.sh pertemuan-1`, buat commit dan tag `pertemuan-1`.

### Pertemuan 2: Fondasi MVC & Blade UI (`pertemuan-2`)
1. Buat Model (`Category`, `Author`, `Book`, `Loan`, `LoanItem`) beserta relasi Eloquent lengkap.
2. Buat Form Request (`StoreBookRequest`, `UpdateBookRequest`, dsb.) untuk validasi.
3. Buat Controller Resource & Blade View untuk entitas Kategori, Penulis, dan Buku.
4. Terapkan eager loading (`with()`) untuk mengeliminasi potensi query N+1.
5. Buat penanganan exception ramah saat penghapusan foreign key terbentur `restrict`.
6. Rute admin sementara ditandai `// TODO P4: lindungi dengan middleware peran`.
7. Tulis Feature Test CRUD dan tulis modul `docs/modul-pertemuan-2.md`.
8. Luluskan verifikasi tag, buat commit dan tag `pertemuan-2`.

### Pertemuan 3: Transaksi Bisnis & Integrasi API (`pertemuan-3`)
1. Terapkan logika domain transaksi peminjaman, pengembalian, dan denda pada Model `Loan`.
2. Pasang routing API (`php artisan install:api`) dan bangun controller API di bawah `app/Http/Controllers/Api/` dengan API Resource standar (`BookResource`, `LoanResource`, dll.).
3. Ubah halaman katalog publik `/` agar mengonsumsi data dari `/api/books` via Vanilla JS `fetch` (debounce search, status loading, error alert).
4. Buat Unit Test kalkulasi denda dan Feature Test API endpoint.
5. Buat dokumentasi interaktif `docs/api.md` dan koleksi `docs/api.http`.
6. Tulis modul tutorial mahasiswa `docs/modul-pertemuan-3.md`.
7. Luluskan verifikasi tag, buat commit dan tag `pertemuan-3`.

### Pertemuan 4: Keamanan, Autentikasi & Polish (`pertemuan-4`)
1. Bangun sistem autentikasi sesi manual (Login, Register, Logout) tanpa library pihak ketiga.
2. Pasang Sanctum untuk autentikasi API (`/api/login`, `/api/logout`, `/api/me`).
3. Terapkan Policy, Gate, dan middleware peran (`role`). Bersihkan seluruh komentar `// TODO P4`.
4. Bangun proteksi menyeluruh: CSRF, sanitasi XSS, SQL Injection prevention via PDO binding.
5. Buat halaman admin user management (`/admin/users`) dan halaman error kustom 403 & 404.
6. Buat instrumen evaluasi mahasiswa `docs/checklist-review-kode.md` dan modul `docs/modul-pertemuan-4.md`.
7. Pastikan pengujian test suite 100% hijau pada database MySQL.
8. Luluskan verifikasi final, buat commit dan tag `pertemuan-4`.

---

## 9. Struktur Folder Akhir yang Diharapkan

```
perpustakaan/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   ├── Auth/
│   │   │   └── ...
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Models/
│   └── Policies/
├── config/
│   └── library.php
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── docs/
│   ├── api.http
│   ├── api.md
│   ├── checklist-review-kode.md
│   ├── erd.md
│   ├── modul-pertemuan-1.md
│   ├── modul-pertemuan-2.md
│   ├── modul-pertemuan-3.md
│   └── modul-pertemuan-4.md
├── pdo-demo/
├── resources/
│   └── views/
├── routes/
│   ├── api.php
│   └── web.php
├── scripts/
│   └── verifikasi-tag.sh
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env.example
├── phpunit.xml
└── README.md
```

---

## 10. Spesifikasi Modul Tutorial Mahasiswa (`docs/modul-pertemuan-N.md`)

Setiap modul praktikum wajib disusun dengan urutan struktur baku:
1. **Tujuan Pembelajaran:** 3-5 sasaran kompetensi teknis yang terukur.
2. **Prasyarat & Perangkat:** Konfigurasi env dan tool yang diperlukan.
3. **Ringkasan Teori:** Landasan konsep ringkas (maksimal 1 halaman).
4. **Langkah Praktikum Berurutan:** Kode/perintah lengkap dengan penjelasan fungsional.
5. **Titik Pengecekan (*Checkpoint*):** Indikator validasi keberhasilan langkah.
6. **Troubleshooting:** Kesalahan umum mahasiswa dan solusinya.
7. **Tugas Mandiri:** 2-3 latihan terapan (termasuk 1 soal eksplorasi/tantangan).
8. **Rubrik Penilaian:** Kriteria penilaian berbasis performa kode.

---

## 11. Skrip Gerbang Verifikasi Bersih (`scripts/verifikasi-tag.sh`)

Skrip ini wajib memvalidasi kode dari salinan bersih (clone sementara) sebelum commit disetujui:

```bash
#!/usr/bin/env bash
set -e

TAG=${1:-"proyek-awal"}
TMP_DIR=$(mktemp -d)
REPO_DIR=$(pwd)

echo "=== Memulai Verifikasi Bersih untuk Tag: $TAG ==="

# 1. Cek PHP 8.3+
PHP_VERSION=$(php -r 'echo PHP_VERSION_ID;')
if [ "$PHP_VERSION" -lt 80300 ]; then
    echo "Galat: PHP 8.3 atau lebih baru diperlukan."
    exit 1
fi

# 2. Clone salinan bersih repositori lokal
git clone "$REPO_DIR" "$TMP_DIR/repo"
cd "$TMP_DIR/repo"
git checkout "$TAG"

# 3. Dependensi Composer & Node
composer install --no-interaction --prefer-dist
if [ -f "package.json" ]; then
    npm ci
    npm run build
fi

# 4. Konfigurasi Environment & Key
cp .env.example .env
sed -i 's/DB_DATABASE=.*/DB_DATABASE=perpustakaan_verifikasi/' .env
php artisan key:generate

# 5. Migrasi & Testing
if [ "$TAG" != "proyek-awal" ]; then
    php artisan migrate:fresh --seed
else
    php artisan migrate:fresh
fi

php artisan test

# 6. Uji Asap Berbasis Tag
php artisan serve --port=8181 &
SERVER_PID=$!
sleep 2

cleanup() {
    kill $SERVER_PID || true
    rm -rf "$TMP_DIR"
}
trap cleanup EXIT

case "$TAG" in
    "proyek-awal")
        curl -s -f http://127.0.0.1:8181/ > /dev/null
        ;;
    "pertemuan-1")
        curl -s -f http://127.0.0.1:8181/ > /dev/null
        ;;
    "pertemuan-2")
        curl -s -f http://127.0.0.1:8181/admin/books > /dev/null
        ;;
    "pertemuan-3")
        curl -s -f http://127.0.0.1:8181/api/books | grep -q '"data"'
        ;;
    "pertemuan-4")
        # Pastikan tidak ada TODO tertinggal
        ! grep -rn "// TODO P4" app/ routes/
        ;;
esac

echo "=== Verifikasi Tag $TAG Berhasil dan Lolos Syarat! ==="
```
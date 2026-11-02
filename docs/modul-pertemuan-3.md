# Modul Praktikum Pertemuan 3: Transaksi Peminjaman, Denda, dan Integrasi REST API

---

## 1. Tujuan Pembelajaran

Setelah menyelesaikan praktikum pertemuan 3 ini, mahasiswa diharapkan mampu:
1. Memahami dan mengimplementasikan pola arsitektur **Fat Model / Thin Controller** untuk memusatkan aturan transaksi bisnis perpustakaan.
2. Mengamankan integritas data stok fisik buku dari bahaya *race condition* menggunakan transaksi atomik (`DB::transaction`) dan *pessimistic locking* (`lockForUpdate`).
3. Merancang logika kalkulasi denda keterlambatan buku secara deterministik berbasis konfigurasi (`config/library.php`).
4. Menginstalasi dan mengonfigurasi lapisan **REST API** pada Laravel menggunakan *API Resources* (`JsonResource`) dan rute `/api/*`.
5. Memastikan respon galat pada API konsisten selalu berformat JSON melalui penyesuaian penanganan exception di `bootstrap/app.php`.
6. Menghubungkan antarmuka web Blade ke REST API menggunakan **Vanilla JavaScript** (`fetch`), teknik *debounce*, dan penanganan status asinkron (*loading*, *empty*, *error*).
7. Menguji logika matematika denda secara mendalam menggunakan **PHPUnit Unit Test** dan memvalidasi rute API menggunakan **Feature Test**.

---

## 2. Prasyarat & Perangkat

Sebelum memulai praktikum:
- Telah menyelesaikan tahapan **Pertemuan 2** (skema basis data, model, dan CRUD data master aktif).
- Perangkat pengujian API aktif: Ekstensi REST Client pada VS Code / Cursor (untuk mengeksekusi `docs/api.http`) atau Postman/cURL.
- Basis data `perpus` siap digunakan.

---

## 3. Ringkasan Teori

### 3.1 Pola Arsitektur Logika Tunggal (*Fat Model / Thin Controller*)
Dalam aplikasi yang menyediakan dua antarmuka berbeda (aplikasi Web Blade dan REST API), penulisan logika peminjaman di dalam Controller akan memicu **duplikasi kode (*code duplication*)**. Jika aturan denda atau durasi pinjaman berubah, pengembang harus memperbarui dua tempat terpisah. Solusinya adalah memindahkan seluruh logika transaksi ke dalam Model (`Loan::borrow()`, `$loan->returnBooks()`, `$loan->hitungDenda()`). Controller hanya bertugas memvalidasi request HTTP dan memanggil method domain tersebut.

### 3.2 Menangani Race Condition dengan Pessimistic Locking
Ketika dua pengguna meminjam 1 eksemplar buku terakhir di detik yang sama, kueri biasa dapat membaca stok $= 1$ untuk keduanya, lalu mengurangi stok dua kali sehingga stok menjadi $-1$. Untuk mencegah hal ini, kueri pembacaan stok wajib menggunakan `lockForUpdate()` dalam blok `DB::transaction()`:
```sql
SELECT * FROM books WHERE id = 1 FOR UPDATE;
```
Perintah ini mengunci baris data di level database mesin InnoDB hingga transaksi selesai di-`commit` atau di-`rollback`.

### 3.3 Transformasi Data dengan API Resource
Eloquent Model mengekspos seluruh kolom database termasuk data privat yang tidak semestinya dikirim ke publik. Kelas `JsonResource` bertindak sebagai lapisan transformasi (*presentation layer*) yang menyaring, memformat tanggal (ISO 8601), menyertakan relasi secara bersyarat (`whenLoaded`), dan membentuk struktur JSON standar.

---

## 4. Langkah Praktikum Berurutan

### Langkah 1: Memasang Routing API Laravel
Jalankan perintah instalasi API bawaan Laravel:
```bash
php artisan install:api
```
Perintah ini akan mendaftarkan rute `routes/api.php` dan memasang paket Laravel Sanctum.

---

### Langkah 2: Mengonfigurasi Format Respon Galat JSON Global
Buka `bootstrap/app.php` dan pastikan konfigurasi exception menangani prefix `/api/*` secara otomatis:
```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->shouldRenderJsonWhen(
        fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
    );
})
```
Hal ini memastikan saat terjadi error validasi atau 404 pada rute API, Laravel selalu mengembalikan format JSON `{"message": "...", "errors": {...}}` alih-alih redirect halaman HTML.

---

### Langkah 3: Mengimplementasikan Logika Transaksi pada Model `Loan`
Buka `app/Models/Loan.php` dan tuliskan logika bisnis transaksi atomik:
```php
public static function borrow(int $userId, array $bookIds): Loan
{
    // Validasi batas kuota dan pinjaman overdue/denda tertunggak
    // ...
    return DB::transaction(function () use ($userId, $uniqueBookIds, $loanDays) {
        $books = Book::whereIn('id', $uniqueBookIds)->lockForUpdate()->get();
        // Validasi stok > 0
        // Buat loan & loan_items, lalu $book->decrement('stock')
    });
}
```

Implementasikan kalkulasi denda:
```php
public function hitungDenda(): float
{
    $finePerDay = (float) config('library.fine_per_day', 1000);
    $fineMax = (float) config('library.fine_max', 50000);

    $returnDate = Carbon::parse($this->returned_at ?: now()->toDateString());
    $dueDate = Carbon::parse($this->due_date);

    $hariTerlambat = $returnDate->gt($dueDate) ? $dueDate->diffInDays($returnDate) : 0;
    $dendaPerBuku = min($hariTerlambat * $finePerDay, $fineMax);
    $jumlahBuku = $this->loanItems()->count() ?: 1;

    return (float) ($dendaPerBuku * $jumlahBuku);
}
```

---

### Langkah 4: Membuat API Resources
Buat berkas resource untuk standardisasi output JSON:
- `BookResource`
- `CategoryResource`
- `AuthorResource`
- `LoanResource`

---

### Langkah 5: Membangun API Controllers & Rute
Bangun controller di `app/Http/Controllers/Api/` dan daftarkan pada `routes/api.php`:
```php
Route::get('/books', [BookController::class, 'index']);
Route::get('/books/{id}', [BookController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/authors', [AuthorController::class, 'index']);

// TODO P4: auth:sanctum
Route::get('/loans', [LoanController::class, 'index']);
Route::get('/loans/{id}', [LoanController::class, 'show']);
Route::post('/loans', [LoanController::class, 'store']);
Route::post('/loans/{id}/return', [LoanController::class, 'returnBooks']);
Route::post('/loans/{id}/pay-fine', [LoanController::class, 'payFine']);
```

---

### Langkah 6: Menghubungkan Frontend Blade dengan Vanilla JS Fetch
Buka `resources/views/catalog/index.blade.php`. Tambahkan logika pemanggilan asinkron ke `/api/books`:
- Pasang *debounce timer* (350ms) pada input pencarian agar API tidak dibanjiri request di setiap ketukan huruf (*keystroke*).
- Kelola visibilitas elemen `#loading-spinner`, `#books-grid`, `#empty-state`, dan `#error-container`.
- Buat fungsi escape HTML untuk memproteksi antarmuka dari celah **Cross-Site Scripting (XSS)** saat merender data JSON ke DOM.

---

## 5. Titik Pengecekan (*Checkpoint*)

| Fitur | Indikator Keberhasilan | Uji Verifikasi |
|---|---|---|
| **API Katalog Buku** | Mengembalikan status 200 dengan struktur `{"data": [...], "meta": {...}}` | `GET /api/books` via `docs/api.http` |
| **Galat JSON Tanpa Header** | Mengembalikan status 422 JSON meskipun tanpa header `Accept` | Kirim POST invalid ke `/api/loans` |
| **Transaksi Peminjaman** | Stok buku berkurang 1 dan tanggal due_date otomatis 7 hari ke depan | `POST /api/loans` via `docs/api.http` |
| **Kalkulasi Denda** | Pengembalian lewat batas menghasilkan denda Rp 1.000/hari/buku | `POST /api/loans/{id}/return` |
| **Interaktivitas Frontend** | Mengetik di kolom cari menampilkan spinner lalu memperbarui kartu buku secara langsung tanpa reload halaman | Buka browser di `http://127.0.0.1:8000/` |
| **Automated Testing** | 36 unit dan feature test berstatus **PASSED (100% Hijau)** | `php artisan test` |

---

## 6. Troubleshooting

### 1. Galat: `Route [api/books] not defined` atau 404 pada URL API
- **Penyebab:** Berkas `routes/api.php` belum didaftarkan di `bootstrap/app.php`.
- **Solusi:** Pastikan rute API dipetakan pada `Application::configure(...)` di `bootstrap/app.php` dengan `api: __DIR__.'/../routes/api.php'`.

### 2. Galat: Respons API mengembalikan HTML Login bukan JSON saat Error
- **Penyebab:** Metode `shouldRenderJsonWhen` di `bootstrap/app.php` belum memeriksa kondisi `$request->is('api/*')`.
- **Solusi:** Pastikan callback mengembalikan `true` jika path diawali dengan `api/*`.

### 3. Masalah: Nilai Denda Tidak Sesuai
- **Penyebab:** Perhitungan selisih hari menghasilkan angka negatif saat buku kembali lebih awal dari jatuh tempo.
- **Solusi:** Gunakan kondisi `$returnDate->gt($dueDate)` atau fungsi `max(0, $selisih)` untuk memastikan keterlambatan tidak bernilai negatif.

---

## 7. Tugas Mandiri

1. **Latihan 1 (Filter Tambahan API):** Tambahkan parameter filter `year` pada `GET /api/books` untuk memfilter buku berdasarkan tahun terbit tertentu.
2. **Latihan 2 (Pembaruan UI):** Pada halaman katalog `/`, tambahkan tombol cepat "*Pinjam Buku Ini*" di setiap kartu buku yang memanggil `POST /api/loans` via modal interaktif.
3. **Tantangan Praktikum:** Buat endpoint statistik perpustakaan baru pada `GET /api/stats` yang mengembalikan ringkasan: total buku, total eksemplar stok tersedia, jumlah peminjaman aktif, dan total denda belum dibayar.

---

## 8. Rubrik Penilaian

| Kriteria | Bobot | Deskripsi Kinerja Maksimal |
|---|---|---|
| **Penerapan Fat Model & Transaksi Atomik** | 25% | Logika transaksi terpusat di Model `Loan`, terlindungi `DB::transaction` dan `lockForUpdate`. |
| **Akurasi Perhitungan Denda** | 20% | Kalkulasi denda tepat di seluruh skenario (tepat waktu, telat, plafon denda, dan multi-buku). |
| **Kepatuhan Arsitektur REST API** | 20% | Standar HTTP status code tepat (200, 201, 404, 422), format JSON konsisten, dan pemanfaatan `JsonResource`. |
| **Integrasi Frontend Asinkron (Fetch & Debounce)** | 15% | Interaktivitas katalog berjalan mulus dengan debounce, penanganan status loading, dan sanitasi DOM. |
| **Automated Testing & Dokumentasi** | 20% | Unit test dan feature test 100% lulus; dokumentasi `docs/api.md` dan `docs/api.http` lengkap. |

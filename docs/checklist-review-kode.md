# Instrumen Evaluasi & Checklist Review Kode (Code Review)

Dokumen ini merupakan panduan evaluasi baku (*code review checklist*) bagi dosen, asisten praktikum, dan mahasiswa untuk mengaudit kualitas, keamanan, dan arsitektur kode pada aplikasi Sistem Perpustakaan Laravel 13.

---

## 1. Keamanan Aplikasi (*Security Hardening*)

| No | Komponen Pemeriksaan | Standar yang Diharapkan | Status |
|---|---|---|:---:|
| 1.1 | **CSRF Protection** | Semua form HTML dengan method POST/PUT/PATCH/DELETE menyertakan direktif `@csrf`. Endpoint API non-sesi tidak rentan terhadap serangan pemalsuan permintaan antar-situs. | [ ] |
| 1.2 | **Pencegahan XSS (*Cross-Site Scripting*)** | Seluruh data dinamis dari pengguna dicetak menggunakan sintaks Blade aman `{{ $data }}` (otomatis `htmlspecialchars`). Hindari `{!! $data !!}` kecuali konten HTML yang telah disanitasi ketat. | [ ] |
| 1.3 | **Pencegahan SQL Injection** | Seluruh query interaksi database menggunakan query builder Eloquent atau PDO parameter binding (`?` atau `:param`). Tidak ada string konkatenasi langsung variabel input ke dalam query SQL. | [ ] |
| 1.4 | **Pencegahan Mass Assignment** | Setiap model mendefinisikan array `$fillable` yang ketat. Kolom sensitif seperti `role`, `id`, atau token tidak boleh dimasukkan ke dalam `$fillable` secara sembarangan, atau diisi secara manual pada controller. | [ ] |
| 1.5 | **Pencegahan Session Hijacking & Fixation** | Memanggil `$request->session()->regenerate()` tepat setelah autentikasi berhasil, dan `$request->session()->invalidate()` serta `$request->session()->regenerateToken()` saat logout. | [ ] |
| 1.6 | **Pencegahan IDOR (*Insecure Direct Object Reference*)** | Endpoint peminjaman dan data privat (`/my/loans`, `/api/loans/{id}`) divalidasi menggunakan Policy atau verifikasi kepemilikan user ID: Anggota tidak boleh melihat data transaksi anggota lain. | [ ] |
| 1.7 | **Proteksi Brute Force (Rate Limiting)** | Endpoint login (`/login` dan `/api/login`) dibatasi menggunakan `Illuminate\Support\Facades\RateLimiter` (maksimal 5 percobaan gagal per menit). | [ ] |
| 1.8 | **Penyimpanan Kredensial Aman** | Kata sandi selalu di-hash menggunakan algoritma Bcrypt via `Hash::make()`. Tidak ada kata sandi berbentuk teks polos (*plaintext*) yang tersimpan di basis data. | [ ] |

---

## 2. Arsitektur Kode & Pola Desain (*Architecture & MVC*)

| No | Komponen Pemeriksaan | Standar yang Diharapkan | Status |
|---|---|---|:---:|
| 2.1 | **Thin Controller, Fat Model / Domain Logic** | Controller hanya bertindak sebagai pengatur lalu lintas HTTP (validasi, pemanggilan service/model, dan pengembalian respons/view). Logika bisnis inti (seperti `Loan::borrow`, `returnBooks`, `hitungDenda`) berada di dalam Model. | [ ] |
| 2.2 | **Form Request Validation** | Validasi input yang kompleks dipisahkan ke kelas `FormRequest` tersendiri dengan pesan galat berbahasa Indonesia yang informatif. | [ ] |
| 2.3 | **Eliminasi Masalah Query N+1** | Pemanggilan data berelasi pada view atau API wajib memanfaatkan *eager loading* (`with(['category', 'authors'])`). | [ ] |
| 2.4 | **Transaksional Basis Data Atomik** | Operasi yang melibatkan multi-tabel (peminjaman buku, pemotongan stok, pembuatan `loan_items`) dibungkus dalam `DB::transaction()` dengan isolasi `lockForUpdate()` untuk mencegah kondisi *race condition*. | [ ] |
| 2.5 | **RESTful API & JSON Resource** | Seluruh endpoint `/api/*` mengembalikan format JSON konsisten menggunakan `JsonResource`, dengan kode status HTTP yang semantik (200, 201, 401, 403, 404, 409, 422, 429). | [ ] |

---

## 3. Integritas Basis Data & Migrasi

| No | Komponen Pemeriksaan | Standar yang Diharapkan | Status |
|---|---|---|:---:|
| 3.1 | **Aturan Integritas Kunci Asing (*Foreign Keys*)** | Menetapkan aturan `restrict` pada relasi penting (kategori ke buku, buku ke item peminjaman) agar data transaksi tidak hilang karena ketidaksengajaan. | [ ] |
| 3.2 | **Penanganan Exception Restrict** | Aplikasi menangani `QueryException` integritas referensial dan memberikan pesan edukatif (flash message pada web atau status 409 Conflict pada API), bukan *crash* 500. | [ ] |
| 3.3 | **Idempotensi Seeder & Factory** | Perintah `php artisan migrate:fresh --seed` dapat dijalankan berulang kali secara deterministik menghasilkan data uji yang konsisten tanpa galat integritas. | [ ] |

---

## 4. Kualitas Pengujian Otomatis (*Automated Testing*)

| No | Komponen Pemeriksaan | Standar yang Diharapkan | Status |
|---|---|---|:---:|
| 4.1 | **Cakupan Unit Test** | Menguji seluruh skenario kalkulasi logika bisnis (misal: denda normal, denda terlambat beberapa hari, batas denda maksimal). | [ ] |
| 4.2 | **Cakupan Feature Test** | Menguji skenario positif (happy path) dan negatif (unauthorized 401, forbidden 403, conflict 409, validation error 422, throttled 429). | [ ] |
| 4.3 | **Isolasi Transaksi Pengujian** | Menggunakan `DatabaseTransactions` agar pengujian otomatis tidak mencemari basis data utama `perpus`. | [ ] |
| 4.4 | **100% Green Suite** | Seluruh suite tes lulus tanpa galat (`php artisan test`). | [ ] |

---

## 5. Kerapian Kode (*Clean Code & Maintenance*)

| No | Komponen Pemeriksaan | Standar yang Diharapkan | Status |
|---|---|---|:---:|
| 5.1 | **Pembersihan Catatan Kerja / TODO** | Tidak ada komentar `TODO` atau artefak debug sementara yang tertinggal dalam repositori sebelum rilis final. | [ ] |
| 5.2 | **Kesesuaian Standar PSR-12** | Format indentasi, penamaan kelas, method, dan deklarasi tipe data (*type hinting*) konsisten dan rapi. | [ ] |
| 5.3 | **Dokumentasi Terintegrasi** | Dokumentasi API (`docs/api.md`), koleksi HTTP (`docs/api.http`), dan modul pertemuan (`docs/modul-pertemuan-*.md`) tersedia dan mutakhir. | [ ] |


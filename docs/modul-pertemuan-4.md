# Modul Pertemuan 4: Keamanan, Autentikasi, Otorisasi, dan Pengujian Menyeluruh

---

## 1. Tujuan Pembelajaran
Setelah menyelesaikan modul praktikum ini, mahasiswa diharapkan mampu:
1. Mengimplementasikan sistem autentikasi sesi manual tanpa paket eksternal (Login, Register, Logout) lengkap dengan proteksi *Brute Force* (Rate Limiting) dan pencegahan *Session Fixation*.
2. Membangun sistem otorisasi multi-peran (*Role-Based Access Control*) menggunakan Custom Middleware dan Laravel Policy untuk mencegah celah *Insecure Direct Object Reference* (IDOR).
3. Mengamankan REST API menggunakan token bearer Laravel Sanctum dan memetakan hak akses endpoint sesuai peran pengguna.
4. Mengidentifikasi serta memitigasi lima kerentanan web utama: CSRF, XSS, SQL Injection, Mass Assignment, dan Session Hijacking.
5. Menyusun *automated feature test* komprehensif untuk memvalidasi aspek fungsional dan keamanan sistem secara menyeluruh.

---

## 2. Prasyarat & Perangkat
- PHP 8.3 atau lebih baru dengan ekstensi `pdo_mysql`, `bcmath`, `curl`, `mbstring`.
- MySQL Server (MAMP / port 8889 atau 3306) aktif dengan basis data `perpus`.
- Repositori proyek berada pada status tag `pertemuan-3`.
- Composer dan Node.js / NPM.
- Ekstensi REST Client pada VS Code atau Postman untuk pengujian API.

---

## 3. Ringkasan Teori: Analisis Kerentanan Web & Mitigasi Laravel

Keamanan aplikasi web bukan sekadar fitur tambahan, melainkan prinsip desain yang harus diterapkan di setiap lapisan sistem.

### 3.1 Cross-Site Request Forgery (CSRF)
CSRF memaksa peramban pengguna yang telah terautentikasi untuk mengirimkan instruksi berbahaya tanpa sepengetahuan pengguna.
- **Kode Rentan:**
  ```html
  <!-- Form HTML tanpa token verifikasi origin -->
  <form action="/admin/books/1" method="POST">
      <input type="hidden" name="_method" value="DELETE">
      <button type="submit">Hapus Buku</button>
  </form>
  ```
- **Kode Aman (Laravel):**
  ```html
  <form action="{{ route('admin.books.destroy', $book) }}" method="POST">
      @csrf
      @method('DELETE')
      <button type="submit">Hapus Buku</button>
  </form>
  ```
  *Penjelasan:* Direktif `@csrf` menyisipkan token acak berbasis sesi yang diverifikasi oleh middleware `VerifyCsrfToken` Laravel.

---

### 3.2 Cross-Site Scripting (XSS)
XSS terjadi ketika input teks berbahaya dari pengguna yang memuat kode JavaScript dijalankan langsung oleh peramban pengguna lain.
- **Kode Rentan:**
  ```html
  <!-- Mencetak langsung teks tanpa sanitasi entitas HTML -->
  <div class="user-bio">{!! $user->bio !!}</div>
  ```
- **Kode Aman (Laravel):**
  ```html
  <!-- Otomatis di-escape oleh engine Blade (htmlspecialchars) -->
  <div class="user-bio">{{ $user->bio }}</div>
  ```

---

### 3.3 SQL Injection (SQLi)
Penyerang menyisipkan fragmen sintaks SQL ke dalam input formulir untuk memanipulasi struktur query basis data.
- **Kode Rentan:**
  ```php
  // Konkatenasi langsung string input ke query SQL
  $email = $_POST['email'];
  $user = DB::select("SELECT * FROM users WHERE email = '$email'");
  ```
- **Kode Aman (Laravel):**
  ```php
  // Menggunakan Parameter Binding PDO via Query Builder / Eloquent
  $user = User::where('email', $request->input('email'))->first();
  // Atau dengan raw binding:
  $user = DB::select("SELECT * FROM users WHERE email = :email", ['email' => $request->input('email')]);
  ```

---

### 3.4 Mass Assignment Vulnerability
Terjadi ketika aplikasi meneruskan seluruh array `$request->all()` langsung ke method pembuatan model tanpa filter kolom.
- **Kode Rentan:**
  ```php
  // Penyerang dapat menyisipkan payload: {"name": "Budi", "role": "admin"}
  User::create($request->all());
  ```
- **Kode Aman (Laravel):**
  ```php
  // Filter ketat melalui validasi dan pembatasan properti $fillable pada Model
  $validated = $request->validate([
      'name'     => 'required|string',
      'email'    => 'required|email|unique:users',
      'password' => 'required|min:8',
  ]);
  $validated['role'] = 'anggota'; // Tetapkan peran secara eksplisit di backend
  User::create($validated);
  ```

---

### 3.5 Session Hijacking & Session Fixation
Penyerang memanfaatkan identifier sesi yang statis sebelum dan sesudah proses login untuk mengambil alih hak akses akun korban.
- **Kode Rentan:**
  ```php
  if (Auth::attempt($credentials)) {
      // Tidak meregenerasi ID sesi setelah login sukses
      return redirect('/dashboard');
  }
  ```
- **Kode Aman (Laravel):**
  ```php
  if (Auth::attempt($credentials)) {
      // Menghasilkan ID sesi baru dan menghapus asosiasi sesi sebelumnya
      $request->session()->regenerate();
      return redirect()->intended('/dashboard');
  }
  ```

---

## 4. Langkah Praktikum Berurutan

### Langkah 1: Membangun Middleware Peran Multi-Peran
1. Buat file middleware `app/Http/Middleware/EnsureUserHasRole.php`.
2. Daftarkan alias middleware `'role'` pada konfigurasi container di `bootstrap/app.php`:
   ```php
   ->withMiddleware(function (Middleware $middleware): void {
       $middleware->alias([
           'role' => \App\Http\Middleware\EnsureUserHasRole::class,
       ]);
   })
   ```

### Langkah 2: Mengimplementasikan Controller Autentikasi Sesi Manual
1. Buat `app/Http/Controllers/Auth/LoginController.php` dengan fitur pembatasan rate limiter (maksimal 5 percobaan per menit) dan `$request->session()->regenerate()`.
2. Buat `app/Http/Controllers/Auth/RegisterController.php` dengan pembuatan kode anggota unik `MBR-xxxxx` dan penugasan peran `'anggota'` secara statis.
3. Buat view Blade untuk formulir login dan registrasi di `resources/views/auth/login.blade.php` dan `register.blade.php`.

### Langkah 3: Mengamankan REST API dengan Laravel Sanctum
1. Konfigurasikan Model `User` agar menyertakan trait `Laravel\Sanctum\HasApiTokens`.
2. Buat controller `app/Http/Controllers/Api/AuthController.php` yang menyediakan method `login()`, `logout()`, dan `me()`.
3. Buka file `routes/api.php` dan kelompokkan rute transaksi peminjaman serta manajemen data master ke dalam grup `middleware('auth:sanctum')`.
4. Tambahkan validasi otorisasi pada `LoanController::show()` agar anggota tidak dapat mengakses data pinjaman milik anggota lain (pencegahan IDOR).

### Langkah 4: Menangani Integritas Referensial dengan Status HTTP 409
Pada controller API (`CategoryController`, `BookController`), tangkap `QueryException` saat aksi penghapusan ditolak oleh *foreign key constraint* basis data:
```php
try {
    $category->delete();
    return response()->json(['message' => 'Kategori berhasil dihapus.']);
} catch (QueryException $e) {
    return response()->json([
        'message' => 'Kategori tidak dapat dihapus karena masih digunakan oleh buku terkait.',
        'error'   => 'Conflict'
    ], 409);
}
```

### Langkah 5: Mengembangkan Halaman Manajemen Pengguna Khusus Admin
1. Buat controller `app/Http/Controllers/Admin/UserController.php` dengan method `index()` dan `updateRole()`.
2. Pasang proteksi middleware `['auth', 'role:admin']` pada rute `/admin/users`.
3. Buat view `resources/views/admin/users/index.blade.php` lengkap dengan pagination dan form pengubahan peran.

### Langkah 6: Mengembangkan Halaman Error Kustom 403 & 404
Buat halaman respons kesalahan ramah pengguna di `resources/views/errors/403.blade.php` dan `resources/views/errors/404.blade.php`.

---

## 5. Titik Pengecekan (*Checkpoint*)
Jalankan pengujian bertahap berikut untuk memastikan keamanan terpasang dengan benar:
- [x] Percobaan login gagal sebanyak 6 kali berturut-turut menghasilkan respons HTTP status `429 Too Many Requests`.
- [x] Pengguna tamu (*guest*) yang mencoba mengakses `/admin/books` otomatis dialihkan ke halaman `/login`.
- [x] Pengguna dengan peran `anggota` yang mengakses `/admin/books` menerima status `403 Forbidden`.
- [x] Panggilan `GET /api/me` tanpa menyertakan Bearer token menghasilkan respons JSON `401 Unauthorized`.
- [x] Panggilan `GET /api/loans/{id_milik_orang_lain}` dengan token anggota menghasilkan respons JSON `403 Forbidden` (IDOR dicegah).
- [x] Penghapusan kategori yang masih memiliki buku terkait melalui `DELETE /api/categories/{id}` menghasilkan status `409 Conflict`.
- [x] Seluruh komentar `// TODO P4` telah dibersihkan dari direktori `app/` dan `routes/`.

---

## 6. Troubleshooting
1. **Galat `Route [login] not defined`:**
   *Penyebab:* Middleware otorisasi mengarahkan pengguna tamu ke route bernama `login`, namun route belum diberi nama `->name('login')`.
   *Solusi:* Pastikan deklarasi route login di `routes/web.php` memiliki `->name('login')`.
2. **Token Sanctum Selalu Menghasilkan 401 Unauthenticated:**
   *Penyebab:* Header HTTP client tidak menyertakan prefix `Bearer ` atau header `Accept: application/json` hilang.
   *Solusi:* Format header yang benar adalah: `Authorization: Bearer 1|xyz...`
3. **Admin tidak sengaja mencabut hak akses dirinya sendiri:**
   *Penyebab:* Tidak ada validasi *self-demotion* pada controller manajemen peran.
   *Solusi:* Tambahkan pengecekan `$user->id === $request->user()->id` pada `UserController::updateRole()`.

---

## 7. Tugas Mandiri
1. **Audit Riwayat Akses (*Audit Log*):** Tambahkan pencatatan log pada file `storage/logs/laravel.log` menggunakan `Log::info()` setiap kali seorang pengguna berhasil login, logout, atau mengubah peran pengguna lain.
2. **Kustomisasi Kedaluwarsa Token Sanctum:** Konfigurasikan masa berlaku (*expiration*) token Sanctum menjadi 120 menit pada file konfigurasi `config/sanctum.php`.
3. **Soal Eksplorasi / Tantangan (Keamanan Lanjutan):** Implementasikan fitur "Force Logout" pada halaman admin, di mana admin dapat membatalkan seluruh sesi dan token Sanctum aktif milik pengguna tertentu secara paksa jika terdeteksi aktivitas mencurigakan.

---

## 8. Rubrik Penilaian
| Kriteria Evaluasi | Bobot | Deskripsi Indikator Keberhasilan |
|---|---|---|
| **Keamanan Autentikasi & Sesi** | 25% | Regenerasi sesi terpasang, rate limiting berfungsi (429), dan password ter-hash aman. |
| **Otorisasi Multi-Peran & Mitigasi IDOR** | 25% | Hak akses Admin, Petugas, dan Anggota terisolasi ketat; IDOR pada peminjaman berhasil dicegah. |
| **Integrasi API Sanctum & Status Code** | 20% | Endpoint API terproteksi token Bearer; respons error mengembalikan status semantik (401, 403, 409). |
| **Automated Testing Suite (100% Green)** | 20% | Pengujian fungsional dan keamanan berhasil melewati seluruh unit dan feature test tanpa kegagalan. |
| **Kerapian Kode & Review Kepatuhan** | 10% | Bebas dari komentar TODO sementara, tata kelola arsitektur MVC bersih, dan dokumentasi lengkap. |


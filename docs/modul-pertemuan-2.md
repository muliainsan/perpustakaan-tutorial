# Modul Praktikum Pertemuan 2: CRUD dengan Arsitektur MVC Laravel

---

## 1. Tujuan Pembelajaran

Setelah menyelesaikan praktikum pertemuan 2 ini, mahasiswa diharapkan mampu:
1. Memahami dan menerapkan pola arsitektur **Model-View-Controller (MVC)** dalam pengembangan web dengan framework Laravel.
2. Membangun operasi **CRUD (Create, Read, Update, Delete)** lengkap untuk entitas master (*Category*, *Author*, *Book*) menggunakan *Resource Controller*.
3. Mencegah permasalahan performa kueri **N+1 Problem** dengan menerapkan teknik *Eager Loading* (`with()`).
4. Menerapkan pemisahan logika validasi input formulir menggunakan **Form Request Validation** dengan pesan kesalahan kustom berbahasa Indonesia.
5. Mengelola integritas referensial basis data dengan menangkap `QueryException` (*restrict delete*) dan menampilkan pesan umpan balik (*flash message*) yang ramah pengguna.
6. Menyusun antarmuka responsif menggunakan **Blade Templating Engine** dan **Tailwind CSS**.

---

## 2. Prasyarat & Perangkat

Sebelum memulai praktikum:
- Telah menyelesaikan tahapan **Pertemuan 1** (migrasi dan data seeder aktif pada database `perpus`).
- Lingkungan kerja lokal aktif: PHP >= 8.3, Composer 2.x, Node.js & NPM, serta MySQL MAMP.
- Server pengembangan telah dikompilasi asetnya melalui perintah `npm run build`.

---

## 3. Ringkasan Teori

### 3.1 Konsep Arsitektur MVC pada Laravel
- **Model (`app/Models/`):** Bertanggung jawab atas representasi entitas data, aturan integritas, dan relasi antar-tabel (*Eloquent ORM*).
- **View (`resources/views/`):** Bertanggung jawab atas presentasi antarmuka pengguna menggunakan sintaks Blade (*layout inheritance*, komponen, dan direktif `@if`, `@foreach`).
- **Controller (`app/Http/Controllers/`):** Bertindak sebagai pengatur alur kerja (*orchestrator*), menerima HTTP Request dari rute, meminta data ke Model, dan mengirimkannya ke View.

### 3.2 Masalah Kueri N+1 (*N+1 Query Problem*)
Masalah N+1 terjadi saat aplikasi mengeksekusi 1 kueri untuk mengambil $N$ data induk, lalu mengeksekusi $N$ kueri tambahan di dalam perulangan untuk mengambil data relasinya:
```php
// CONTOH SALAH (Memicu N+1 Queries: 1 kueri buku + 40 kueri kategori terpisah!)
$books = Book::all();
foreach ($books as $book) {
    echo $book->category->name; 
}

// CONTOH BENAR (Eager Loading: Hanya 2 kueri total!)
$books = Book::with(['category', 'authors'])->get();
```

### 3.3 Validasi Terpusat: Form Request
Menghindari penulisan validasi di dalam controller (*Fat Controller*) dengan memindahkannya ke kelas *Form Request*. Hal ini menjamin prinsip *Single Responsibility* dan membuat controller tetap ramping (*Thin Controller*).

---

## 4. Langkah Praktikum Berurutan

### Langkah 1: Membangun Layout Utama Blade
Buat berkas layout dasar di `resources/views/layouts/perpustakaan.blade.php`:
```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Sistem Perpustakaan' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">
    <!-- Navbar Navigasi -->
    ...
    <!-- Wadah Flash Message (success & error) -->
    @if (session('success')) ... @endif
    @if (session('error')) ... @endif

    <!-- Konten Halaman Dinamis -->
    <main class="flex-grow max-w-7xl mx-auto px-4 py-8">
        @yield('content')
    </main>
</body>
</html>
```

---

### Langkah 2: Membuat Form Request Validation
Gunakan artisan untuk menghasilkan form request:
```bash
php artisan make:request StoreBookRequest
php artisan make:request UpdateBookRequest
```
Contoh aturan validasi pada `app/Http/Requests/StoreBookRequest.php`:
```php
public function rules(): array
{
    return [
        'category_id'  => ['required', 'exists:categories,id'],
        'title'        => ['required', 'string', 'max:255'],
        'isbn'         => ['nullable', 'string', 'max:30', 'unique:books,isbn'],
        'stock'        => ['required', 'integer', 'min:0'],
        'author_ids'   => ['required', 'array', 'min:1'],
        'author_ids.*' => ['exists:authors,id'],
    ];
}

public function messages(): array
{
    return [
        'category_id.required' => 'Kategori buku wajib dipilih.',
        'title.required'       => 'Judul buku wajib diisi.',
        'stock.min'            => 'Stok buku tidak boleh bernilai negatif.',
        'author_ids.required'  => 'Pilih minimal satu penulis untuk buku ini.',
    ];
}
```

---

### Langkah 3: Membuat Resource Controller Buku dengan Eager Loading
Pada `app/Http/Controllers/BookController.php`, terapkan eager loading `with()`:
```php
public function index(Request $request): View
{
    $books = Book::with(['category', 'authors'])
        ->when($request->filled('search'), function ($q) use ($request) {
            $q->where('title', 'like', "%{$request->search}%");
        })
        ->latest()
        ->paginate(10)
        ->withQueryString();

    $categories = Category::orderBy('name')->get();
    return view('admin.books.index', compact('books', 'categories'));
}
```

---

### Langkah 4: Menangani Integritas Referensial (*Restrict Delete*)
Saat menghapus kategori atau buku, tangkap `QueryException` untuk mencegah error layar putih (*HTTP 500*):
```php
public function destroy(Category $category): RedirectResponse
{
    try {
        $category->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dihapus.');
    } catch (\Illuminate\Database\QueryException $e) {
        return redirect()->route('admin.categories.index')->with('error', 'Kategori tidak dapat dihapus karena masih memuat buku.');
    }
}
```

---

### Langkah 5: Mendefinisikan Rute Web
Buka `routes/web.php` dan daftarkan rute katalog publik dan CRUD data master:
```php
// Rute katalog publik server-rendered
Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');

// TODO P4: lindungi dengan middleware peran
Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('authors', AuthorController::class)->except(['show']);
    Route::resource('books', BookController::class);
});
```

---

### Langkah 6: Kompilasi Aset Frontend
Jalankan Vite untuk mengompilasi CSS dan JS:
```bash
npm run build
```

---

## 5. Titik Pengecekan (*Checkpoint*)

| Fitur | Kriteria Keberhasilan | Uji Verifikasi |
|---|---|---|
| **Katalog Publik** | Menampilkan daftar kartu buku dengan pagination dan filter pencarian | Buka browser di `http://127.0.0.1:8000/` |
| **CRUD Kategori** | Berhasil menambah, mengedit, dan melihat daftar kategori | Akses `http://127.0.0.1:8000/admin/categories` |
| **Proteksi Restrict Kategori** | Menghapus kategori yang memiliki buku memunculkan notifikasi merah ramah pengguna | Klik "Hapus" pada kategori bertrik buku |
| **CRUD Buku** | Buku baru tersimpan lengkap dengan relasi tabel pivot `author_book` | Tambah buku baru di `admin/books/create` |
| **Bebas Masalah N+1** | Query teroptimasi menjadi 2 kueri SQL menggunakan `with()` | Diperiksa via Feature Test |
| **Automated Testing** | 20 unit & feature test lulus (100% hijau) | `php artisan test` |

---

## 6. Troubleshooting

### 1. Galat: `Attempt to read property "name" on null` pada Blade
- **Penyebab:** Relasi kategori atau penulis bernilai `null` karena data foreign key tidak cocok.
- **Solusi:** Pastikan form create mewajibkan `category_id` yang valid melalui aturan `exists:categories,id` pada Form Request.

### 2. Galat: `SQLSTATE[23000]: Integrity constraint violation: 1451`
- **Penyebab:** Perintah `$category->delete()` dijalankan tanpa blok `try ... catch (\Illuminate\Database\QueryException $e)`.
- **Solusi:** Bungkus perintah hapus dengan `try-catch` dan kembalikan pesan flash error ke session.

### 3. Tampilan Halaman Berantakan / Tanpa Styling
- **Penyebab:** Aset Vite belum dikompilasi.
- **Solusi:** Jalankan `npm run build` di terminal untuk membangun bundel CSS Tailwind.

---

## 7. Tugas Mandiri

1. **Latihan 1 (Fitur Sortir):** Tambahkan opsi sortir berdasarkan judul (`A-Z`, `Z-A`) dan tahun terbit (`Terbaru`, `Terlama`) pada halaman katalog publik `/`.
2. **Latihan 2 (Pencarian Multi-Kriteria):** Kembangkan filter pencarian admin buku agar mendukung pencarian spesifik berdasarkan penerbit (`publisher`).
3. **Tantangan Praktikum:** Buat komponen Blade parsial kustom (`resources/views/components/badge.blade.php`) untuk menampilkan status stok buku secara dinamis (hijau jika stok > 5, kuning jika 1-5, merah jika habis).

---

## 8. Rubrik Penilaian

| Kriteria | Bobot | Deskripsi Kinerja Maksimal |
|---|---|---|
| **Penerapan Konsep MVC** | 25% | Pemisahan yang bersih antara Model, View Blade, dan Resource Controller. |
| **Validasi Form Request** | 20% | Form Request lengkap dengan pesan validasi berbahasa Indonesia yang jelas. |
| **Optimasi Eager Loading** | 20% | Bebas dari masalah query N+1 pada seluruh pembacaan relasi buku, kategori, dan penulis. |
| **Penanganan Error Restrict** | 15% | Integritas referensial terjaga dengan penanganan exception ramah pengguna. |
| **Kualitas Antarmuka UI** | 10% | Tampilan rapi, responsif, menggunakan Tailwind CSS via Vite tanpa CDN. |
| **Automated Testing** | 10% | Feature Test komprehensif menguji skenario positif dan negatif secara tuntas. |


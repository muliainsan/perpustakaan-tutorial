# Dokumentasi REST API - Sistem Perpustakaan

Dokumentasi ini menjelaskan standar antarmuka pemograman aplikasi (*Application Programming Interface*) berbasis REST untuk sistem perpustakaan.

---

## 1. Standar Format Respons

Seluruh endpoint pada prefix `/api/*` secara otomatis mengembalikan respons berformat `application/json`, termasuk penanganan galat (*exception handling*), terlepas dari apakah client menyertakan header `Accept: application/json` atau tidak.

### Struktur Respons Sukses (Koleksi / Pagination)
```json
{
  "data": [
    {
      "id": 1,
      "title": "Clean Code",
      "isbn": "978-01-32350-88-4",
      "publisher": "Prentice Hall",
      "year": 2008,
      "stock": 5,
      "is_available": true,
      "category": {
        "id": 1,
        "name": "Pemrograman & Teknologi"
      },
      "authors": [
        {
          "id": 1,
          "name": "Robert C. Martin"
        }
      ],
      "created_at": "2026-11-02T07:00:00.000000Z"
    }
  ],
  "links": {
    "first": "http://127.0.0.1:8000/api/books?page=1",
    "last": "http://127.0.0.1:8000/api/books?page=4",
    "prev": null,
    "next": "http://127.0.0.1:8000/api/books?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 4,
    "per_page": 10,
    "to": 10,
    "total": 40
  }
}
```

### Struktur Respons Galat Validasi (HTTP 422)
```json
{
  "message": "Pilih minimal satu buku untuk dipinjam.",
  "errors": {
    "book_ids": [
      "Pilih minimal satu buku untuk dipinjam."
    ]
  }
}
```

---

## 2. Ringkasan Endpoint API

| Method | Endpoint | Deskripsi | Otorisasi (P4) |
|---|---|---|---|
| `GET` | `/api/books` | Mengambil daftar katalog buku (paginated & filterable) | Publik |
| `GET` | `/api/books/{id}` | Mengambil informasi detail satu buku | Publik |
| `GET` | `/api/categories` | Mengambil daftar seluruh kategori | Publik |
| `GET` | `/api/authors` | Mengambil daftar seluruh penulis | Publik |
| `GET` | `/api/loans` | Riwayat transaksi peminjaman | Token Sanctum |
| `GET` | `/api/loans/{id}` | Detail transaksi peminjaman spesifik | Token Sanctum |
| `POST` | `/api/loans` | Membuat transaksi peminjaman baru | Token Sanctum |
| `POST` | `/api/loans/{id}/return` | Memproses pengembalian buku & kalkulasi denda | Token Sanctum |
| `POST` | `/api/loans/{id}/pay-fine` | Mencatat pelunasan denda keterlambatan | Token Sanctum |

---

## 3. Rincian Endpoint

### 3.1 `GET /api/books`
Mengambil koleksi buku dengan parameter query:
- `search` *(string, opsional)*: Kata kunci judul, nomor ISBN, atau nama penulis.
- `category_id` *(integer, opsional)*: Filter berdasarkan ID kategori.
- `available` *(boolean 0/1, opsional)*: Jika bernilai `1`, hanya menampilkan buku dengan stok $> 0$.
- `per_page` *(integer, opsional)*: Jumlah data per halaman (default: 10, maks: 50).
- `page` *(integer, opsional)*: Nomor halaman.

### 3.2 `POST /api/loans`
Membuat transaksi peminjaman baru melalui domain model.
- **Request Body (JSON):**
  ```json
  {
    "user_id": 3,
    "book_ids": [1, 2]
  }
  ```
- **Aturan Bisnis Validasi:**
  - `user_id` wajib ada dan terdaftar pada tabel `users`.
  - `book_ids` berupa array minimal 1 dan maksimal 3 buku (`config/library.php`).
  - Pengguna tidak boleh memiliki denda `unpaid` atau pinjaman aktif yang melewati jatuh tempo (*overdue*).
  - Seluruh buku yang dipinjam wajib memiliki stok fisik $> 0$.
- **Respons (HTTP 201 Created):**
  Mengembalikan data resource transaksi peminjaman baru dan otomatis mengurangi stok fisik buku terkait.

### 3.3 `POST /api/loans/{id}/return`
Mengembalikan buku yang dipinjam dan secara otomatis mengalkulasi denda keterlambatan.
- **Request Body (JSON, opsional):**
  ```json
  {
    "returned_at": "2026-05-15"
  }
  ```
  *(Jika `returned_at` diabaikan, sistem menggunakan tanggal hari ini).*
- **Efek Samping:**
  - Stok fisik seluruh buku pada peminjaman ini dikembalikan (+1).
  - Jika `returned_at > due_date`, denda dihitung berdasarkan rumus:
    $$\text{denda} = \min(\text{hari\_terlambat} \times \text{fine\_per\_day}, \text{fine\_max}) \times \text{jumlah\_buku}$$
  - `fine_status` menjadi `'unpaid'` jika denda $> 0$, atau `'none'` jika tepat waktu.

### 3.4 `POST /api/loans/{id}/pay-fine`
Mencatat pelunasan denda keterlambatan buku.
- **Validasi:** Transaksi harus berstatus `fine_status === 'unpaid'`.
- **Respons (HTTP 200 OK):** Mengubah `fine_status` menjadi `'paid'`.

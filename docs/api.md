# Dokumentasi REST API - Sistem Perpustakaan

Dokumentasi ini menjelaskan standar antarmuka pemrograman aplikasi (*Application Programming Interface*) berbasis REST untuk sistem perpustakaan dengan autentikasi Laravel Sanctum dan kontrol akses berbasis peran (*Role-Based Access Control*).

---

## 1. Standar Format Respons & Autentikasi

### 1.1 Header Wajib
Untuk mengakses endpoint yang dilindungi, sertakan token autentikasi pada header:
```http
Authorization: Bearer <token_sanctum_anda>
Accept: application/json
Content-Type: application/json
```
*Catatan:* Seluruh respons di bawah prefix `/api/*` dijamin selalu berformat `application/json`, termasuk pada skenario galat validasi, kegagalan autentikasi (401), pelanggaran otorisasi (403), konflik integritas (409), maupun pembatasan laju permintaan (429).

---

## 2. Matriks Endpoint API

| Method | Endpoint | Deskripsi | Otorisasi & Peran |
|---|---|---|---|
| `POST` | `/api/login` | Autentikasi dan penerbitan Bearer token (Rate limit: 5 req/min) | Publik |
| `POST` | `/api/logout` | Mencabut (*revoke*) token Sanctum aktif | `auth:sanctum` |
| `GET` | `/api/me` | Mengambil data profil pengguna yang login | `auth:sanctum` |
| `GET` | `/api/books` | Katalog buku berhalaman dengan filter pencarian | Publik |
| `GET` | `/api/books/{id}` | Detail satu buku | Publik |
| `GET` | `/api/categories` | Daftar seluruh kategori buku | Publik |
| `GET` | `/api/categories/{id}` | Detail satu kategori buku | Publik |
| `GET` | `/api/authors` | Daftar seluruh penulis | Publik |
| `GET` | `/api/authors/{id}` | Detail satu penulis | Publik |
| `GET` | `/api/loans` | Daftar peminjaman (Anggota: milik sendiri; Admin/Petugas: semua) | `auth:sanctum` |
| `GET` | `/api/loans/{id}` | Detail peminjaman (Proteksi IDOR: 403 bila bukan miliknya) | `auth:sanctum` |
| `POST` | `/api/loans` | Pengajuan pinjaman baru | `auth:sanctum` |
| `POST` | `/api/loans/{id}/return` | Pengembalian buku & kalkulasi denda otomatis | `role:admin,petugas` |
| `POST` | `/api/loans/{id}/pay-fine` | Pelunasan denda keterlambatan | `role:admin,petugas` |
| `POST` | `/api/categories` | Tambah kategori baru | `role:admin,petugas` |
| `PUT` | `/api/categories/{id}` | Perbarui data kategori | `role:admin,petugas` |
| `DELETE` | `/api/categories/{id}` | Hapus kategori (409 bila terkait buku) | `role:admin,petugas` |
| `POST` | `/api/authors` | Tambah penulis baru | `role:admin,petugas` |
| `PUT` | `/api/authors/{id}` | Perbarui data penulis | `role:admin,petugas` |
| `DELETE` | `/api/authors/{id}` | Hapus penulis | `role:admin,petugas` |
| `POST` | `/api/books` | Tambah buku baru ke koleksi | `role:admin,petugas` |
| `PUT` | `/api/books/{id}` | Perbarui data buku | `role:admin,petugas` |
| `DELETE` | `/api/books/{id}` | Hapus buku (409 bila terkait pinjaman aktif) | `role:admin,petugas` |

---

## 3. Rincian Endpoint Utama

### 3.1 Autentikasi Pengguna: `POST /api/login`
- **Rate Limit:** Maksimal 5 permintaan gagal per menit (mengembalikan status HTTP 429 jika terlampaui).
- **Request Body:**
  ```json
  {
    "email": "admin@perpus.test",
    "password": "password"
  }
  ```
- **Respons Sukses (HTTP 200 OK):**
  ```json
  {
    "message": "Login berhasil.",
    "token": "1|abcdef123456...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Administrator",
      "email": "admin@perpus.test",
      "role": "admin",
      "member_code": null
    }
  }
  ```

---

### 3.2 Profil Pengguna: `GET /api/me`
- **Header:** `Authorization: Bearer <token>`
- **Respons Sukses (HTTP 200 OK):**
  ```json
  {
    "data": {
      "id": 1,
      "name": "Administrator",
      "email": "admin@perpus.test",
      "role": "admin",
      "member_code": null,
      "phone": null
    }
  }
  ```

---

### 3.3 Transaksi Peminjaman: `POST /api/loans`
- **Header:** `Authorization: Bearer <token>`
- **Request Body:**
  ```json
  {
    "user_id": 3,
    "book_ids": [1, 2]
  }
  ```
- *Catatan Otorisasi:* Anggota hanya dapat meminjam untuk dirinya sendiri (`user_id` otomatis diisi sesuai identitas token).
- **Respons Sukses (HTTP 201 Created):**
  ```json
  {
    "data": {
      "id": 15,
      "user_id": 3,
      "loaned_at": "2026-11-02",
      "due_date": "2026-11-09",
      "returned_at": null,
      "fine_amount": 0,
      "fine_status": "none",
      "items": [
        { "id": 1, "title": "Clean Code", "isbn": "978-01-32350-88-4" }
      ]
    }
  }
  ```

---

### 3.4 Pencegahan IDOR: `GET /api/loans/{id}`
- Jika anggota `A` mencoba mengakses detail pinjaman milik anggota `B`:
- **Respons Galat (HTTP 403 Forbidden):**
  ```json
  {
    "message": "Akses ditolak. Anda tidak memiliki izin untuk melihat riwayat pinjaman pengguna lain."
  }
  ```

---

### 3.5 Penanganan Konflik Integritas Basis Data: `DELETE /api/categories/{id}`
- Jika kategori masih menaungi buku pada basis data (aturan FK `restrict`):
- **Respons Galat (HTTP 409 Conflict):**
  ```json
  {
    "message": "Kategori tidak dapat dihapus karena masih digunakan oleh buku terkait.",
    "error": "Conflict"
  }
  ```

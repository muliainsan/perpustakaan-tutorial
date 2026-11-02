# Modul Demo: CRUD Native PDO vs Laravel Eloquent ORM

Folder ini berisi demonstrasi kode manipulasi basis data tabel `books` pada database `perpus` menggunakan **PHP Data Objects (PDO)** murni tanpa framework, sebagai fondasi pemahaman sebelum mempelajari Query Builder dan Eloquent ORM di Laravel.

---

## 1. Mengapa Mempelajari Native PDO Terlebih Dahulu?

1. **Memahami Abstraksi di Balik Framework:** Laravel Eloquent tidak berbicara langsung dengan MySQL secara ajaib; di lapisan terdalamnya, Laravel mengonfigurasi PDO instance dan menyiapkan *prepared statements*.
2. **Keamanan SQL Injection:** Mahasiswa memahami secara langsung mengapa penggabungan string query (`"SELECT * FROM users WHERE email = '$email'"`) sangat berbahaya dan bagaimana PDO *parameter binding* melindungi aplikasi.
3. **Mengapresiasi Kemudahan Laravel:** Menyadari berapa banyak *boilerplate code* (penulisan query berulang, pemetaan kolom ke objek manual, pengelolaan timestamps) yang dihemat oleh Eloquent ORM.

---

## 2. Tabel Perbandingan Cepat: Native PDO vs Laravel Eloquent

| Operasi | Native PDO (PHP Murni) | Laravel Eloquent ORM |
|---|---|---|
| **Koneksi Database** | `new PDO("mysql:host=...;dbname=...", $user, $pass)` | Otomatis via `.env` & `config/database.php` |
| **Pencegahan SQLi** | Wajib manual: `$stmt = $pdo->prepare(...)` + `$stmt->execute([...])` | Otomatis via PDO binding di balik Eloquent |
| **Insert Data** | `INSERT INTO books (...) VALUES (...)` manual | `Book::create($validatedData)` |
| **Timestamps** | Mengisi `created_at` & `updated_at` manual | Diisi dan diperbarui otomatis oleh model |
| **Ambil Satu Data** | `$stmt->fetch(PDO::FETCH_ASSOC)` | `Book::find($id)` atau `Book::findOrFail($id)` |
| **Update Data** | `UPDATE books SET stock = :stock WHERE id = :id` | `$book->update(['stock' => 10])` |
| **Delete Data** | `DELETE FROM books WHERE id = :id` | `$book->delete()` |
| **Relasi Tabel** | Menulis `JOIN` SQL manual yang panjang | `$book->category->name`, `$book->authors` |
| **Transaksi Atomik** | `$pdo->beginTransaction()`, `$pdo->commit()` | `DB::transaction(fn() => ...)` |

---

## 3. Cara Menjalankan Demo

Pastikan server MySQL MAMP aktif dan basis data `perpus` telah dimigrasi, lalu jalankan:

```bash
php pdo-demo/index.php
```

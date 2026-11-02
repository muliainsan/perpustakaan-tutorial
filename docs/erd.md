# Entity Relationship Diagram (ERD) - Sistem Perpustakaan

Dokumen ini mendokumentasikan skema perancangan basis data relasional untuk aplikasi sistem informasi perpustakaan, aturan integritas referensial, serta relasi antartabel.

---

## 1. Diagram Relasi Tabel (Mermaid ERD)

```mermaid
erDiagram
    USERS ||--o{ LOANS : "meminjam (1:N)"
    CATEGORIES ||--o{ BOOKS : "mengelompokkan (1:N)"
    BOOKS ||--|{ AUTHOR_BOOK : "memiliki (1:N)"
    AUTHORS ||--|{ AUTHOR_BOOK : "menulis (1:N)"
    LOANS ||--|{ LOAN_ITEMS : "memuat (1:N)"
    BOOKS ||--o{ LOAN_ITEMS : "tercatat_di (1:N)"

    USERS {
        bigint id PK "Auto Increment"
        string name
        string email UK "Unique"
        string password
        enum role "'admin', 'petugas', 'anggota'"
        string member_code UK "Nullable, Unique"
        string phone "Nullable"
        timestamp created_at
        timestamp updated_at
    }

    CATEGORIES {
        bigint id PK "Auto Increment"
        string name UK "Unique"
        timestamp created_at
        timestamp updated_at
    }

    AUTHORS {
        bigint id PK "Auto Increment"
        string name
        timestamp created_at
        timestamp updated_at
    }

    BOOKS {
        bigint id PK "Auto Increment"
        bigint category_id FK "categories.id (restrict)"
        string title
        string isbn UK "Nullable, Unique"
        string publisher "Nullable"
        smallint year "Nullable"
        unsigned_int stock "Default: 0"
        timestamp created_at
        timestamp updated_at
    }

    AUTHOR_BOOK {
        bigint book_id PK_FK "books.id (cascade)"
        bigint author_id PK_FK "authors.id (cascade)"
    }

    LOANS {
        bigint id PK "Auto Increment"
        bigint user_id FK "users.id (restrict)"
        date loaned_at
        date due_date
        date returned_at "Nullable"
        decimal fine_amount "Precision 10, Scale 2, Default: 0"
        enum fine_status "'none', 'unpaid', 'paid', Default: 'none'"
        timestamp created_at
        timestamp updated_at
    }

    LOAN_ITEMS {
        bigint id PK "Auto Increment"
        bigint loan_id FK "loans.id (cascade)"
        bigint book_id FK "books.id (restrict)"
        timestamp created_at
        timestamp updated_at
    }
```

---

## 2. Rincian Relasi & Kardinalitas

1. **`categories` -> `books` (One-to-Many):**
   - Satu kategori dapat mengelompokkan banyak buku.
   - Foreign Key: `books.category_id` merujuk ke `categories.id`.
   - On Delete: `RESTRICT` (Kategori tidak boleh dihapus jika masih ada buku di dalamnya).

2. **`books` <-> `authors` (Many-to-Many via `author_book`):**
   - Satu buku dapat ditulis oleh lebih dari satu penulis, dan satu penulis dapat menulis banyak buku.
   - Pivot Table: `author_book` dengan Primary Key gabungan `['book_id', 'author_id']`.
   - On Delete: `CASCADE` (Jika buku atau penulis dihapus, baris pivot terkait ikut dihapus otomatis).

3. **`users` -> `loans` (One-to-Many):**
   - Pengguna (anggota) dapat melakukan banyak transaksi peminjaman.
   - Foreign Key: `loans.user_id` merujuk ke `users.id`.
   - On Delete: `RESTRICT` (User tidak boleh dihapus jika pernah memiliki riwayat peminjaman).

4. **`loans` -> `loan_items` (One-to-Many):**
   - Satu transaksi peminjaman dapat memuat 1 hingga 3 buku.
   - Foreign Key: `loan_items.loan_id` merujuk ke `loans.id`.
   - On Delete: `CASCADE` (Jika transaksi peminjaman dihapus, item rincian peminjaman ikut terhapus).

5. **`books` -> `loan_items` (One-to-Many):**
   - Satu buku dapat dicatat dalam riwayat item peminjaman.
   - Foreign Key: `loan_items.book_id` merujuk ke `books.id`.
   - Unique Composite Key: `['loan_id', 'book_id']` (Mencegah buku yang sama dicatat dua kali dalam satu transaksi).
   - On Delete: `RESTRICT` (Buku yang pernah dipinjam tidak boleh dihapus demi menjaga integritas data riwayat).

---

## 3. Aturan Integritas & Konkurensi

1. **Pencegahan Penghapusan Data Terkait (*Restrict Delete*):**
   - Operasi penghapusan data induk yang terikat data anak akan menghasilkan `QueryException` (MySQL Error 1451). Sistem aplikasi menangkap exception ini dan mengembalikan respon HTTP 409 (Conflict) yang informatif.
2. **Pencegahan Stok Negatif (*Pessimistic Locking*):**
   - Saat peminjaman dilakukan, pembacaan stok buku dilindungi dengan `lockForUpdate()` dalam blok `DB::transaction()` untuk mencegah *race condition* jika beberapa peminjam meminjam buku yang sama secara bersamaan.

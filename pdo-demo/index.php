<?php
/**
 * Demo CRUD Native PDO - Tabel Books (Database: perpus)
 * 
 * Modul Edukatif: Memahami cara kerja native PHP Data Objects (PDO)
 * sebelum beralih ke Query Builder dan Eloquent ORM di Laravel.
 * 
 * Jalankan file ini melalui terminal:
 * php pdo-demo/index.php
 */

echo "=========================================================\n";
echo " DEMO CRUD NATIVE PDO VS LARAVEL ELOQUENT ORM (TABEL BOOKS)\n";
echo "=========================================================\n\n";

// -----------------------------------------------------------------------------
// 1. KONEKSI KE BASIS DATA DENGAN PDO
// -----------------------------------------------------------------------------
// Di Laravel, koneksi ini dikelola secara otomatis melalui config/database.php
// dan berkas .env (DB_CONNECTION, DB_HOST, DB_DATABASE, dll).
// Pada PHP native, kita wajib membuat instance PDO secara manual dengan DSN (Data Source Name).

$host = '127.0.0.1';
$port = 8889; // Sesuaikan dengan port MySQL (3306 atau 8889 pada MAMP)
$dbName = 'perpus';
$username = 'root';
$password = 'root';
$socket = '/Applications/MAMP/tmp/mysql/mysql.sock';

try {
    // Mendukung koneksi via UNIX socket MAMP atau fallback ke TCP/IP host & port
    if (file_exists($socket)) {
        $dsn = "mysql:unix_socket={$socket};dbname={$dbName};charset=utf8mb4";
    } else {
        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Melempar PDOException jika ada galat query
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Hasil query berupa array asosiatif
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Gunakan native prepared statement untuk keamanan ekstra
    ];

    $pdo = new PDO($dsn, $username, $password, $options);
    echo "[1/6] [Koneksi Sukses] Terhubung ke basis data '{$dbName}'.\n\n";
} catch (PDOException $e) {
    die("[Galat Koneksi] Gagal terhubung ke basis data: " . $e->getMessage() . "\n");
}

// Ambil salah satu kategori yang ada untuk pengujian relasi foreign key
$catStmt = $pdo->query("SELECT id, name FROM categories LIMIT 1");
$category = $catStmt->fetch();
if (!$category) {
    die("[Peringatan] Tabel categories kosong. Jalankan 'php artisan migrate:fresh --seed' terlebih dahulu.\n");
}
$categoryId = $category['id'];

// -----------------------------------------------------------------------------
// 2. CREATE (INSERT DATA BUKU BARU)
// -----------------------------------------------------------------------------
// Native PDO:
// Wajib menuliskan SQL mentah dengan placeholder (:parameter) untuk mencegah SQL Injection,
// kemudian mengeksekusi dengan array parameter.
//
// Bandingkan dengan Laravel Eloquent:
// $book = Book::create([
//     'category_id' => $categoryId,
//     'title'       => 'Mastering PHP 8 & Laravel 13',
//     'isbn'        => '978-602-00000-1-1',
//     'publisher'   => 'Informatika Edu',
//     'year'        => 2026,
//     'stock'       => 10,
// ]);
// (Eloquent otomatis menangani timestamps created_at/updated_at dan mengembalikan model instance)

echo "[2/6] [CREATE / INSERT]\n";
$insertSql = "INSERT INTO books (category_id, title, isbn, publisher, year, stock, created_at, updated_at) 
              VALUES (:category_id, :title, :isbn, :publisher, :year, :stock, :created_at, :updated_at)";

$testIsbn = '978-99-99999-99-9';
// Hapus jika data uji sebelumnya masih ada
$pdo->prepare("DELETE FROM books WHERE isbn = :isbn")->execute([':isbn' => $testIsbn]);

$insertStmt = $pdo->prepare($insertSql);
$now = date('Y-m-d H:i:s');
$insertStmt->execute([
    ':category_id' => $categoryId,
    ':title'       => 'Mastering PHP 8 & Laravel 13 (Native PDO Demo)',
    ':isbn'        => $testIsbn,
    ':publisher'   => 'Informatika Press',
    ':year'        => 2026,
    ':stock'       => 10,
    ':created_at'  => $now,
    ':updated_at'  => $now,
]);

$newBookId = (int) $pdo->lastInsertId();
echo "-> Buku baru berhasil ditambahkan dengan ID: {$newBookId}\n";
echo "   Catatan: Pada PDO kita wajib mengisi created_at manual dan memanggil lastInsertId().\n\n";

// -----------------------------------------------------------------------------
// 3. READ (MENAMPILKAN DATA)
// -----------------------------------------------------------------------------
// Native PDO:
// Menggunakan prepare() dan execute() lalu fetch() atau fetchAll().
//
// Bandingkan dengan Laravel Eloquent:
// $book = Book::find($newBookId);
// $allBooks = Book::where('category_id', $categoryId)->get();
// (Eloquent otomatis memetakan kolom ke atribut objek dan mendukung Eager Loading relasi: Book::with('category')->get())

echo "[3/6] [READ / SELECT]\n";
$selectSql = "SELECT b.*, c.name AS category_name 
              FROM books b 
              JOIN categories c ON b.category_id = c.id 
              WHERE b.id = :id";
$selectStmt = $pdo->prepare($selectSql);
$selectStmt->execute([':id' => $newBookId]);
$bookData = $selectStmt->fetch();

echo "-> Data Buku Ditemukan:\n";
echo "   - Judul     : " . $bookData['title'] . "\n";
echo "   - ISBN      : " . $bookData['isbn'] . "\n";
echo "   - Kategori  : " . $bookData['category_name'] . "\n";
echo "   - Stok Awal : " . $bookData['stock'] . "\n\n";

// -----------------------------------------------------------------------------
// 4. UPDATE (MEMPERBARUI DATA)
// -----------------------------------------------------------------------------
// Native PDO:
// Menulis query UPDATE manual dan mengisi updated_at secara eksplisit.
//
// Bandingkan dengan Laravel Eloquent:
// $book->update(['stock' => 15]); // updated_at terisi otomatis!

echo "[4/6] [UPDATE]\n";
$updateSql = "UPDATE books SET stock = :stock, updated_at = :updated_at WHERE id = :id";
$updateStmt = $pdo->prepare($updateSql);
$newStock = 15;
$updateStmt->execute([
    ':stock'      => $newStock,
    ':updated_at' => date('Y-m-d H:i:s'),
    ':id'         => $newBookId,
]);

echo "-> Stok buku ID {$newBookId} berhasil diperbarui menjadi: {$newStock}\n";
echo "   Jumlah baris yang terpengaruh (rowCount): " . $updateStmt->rowCount() . "\n\n";

// -----------------------------------------------------------------------------
// 5. TRANSAKSI DATABASE (TRANSACTION & LOCKING)
// -----------------------------------------------------------------------------
// Native PDO:
// $pdo->beginTransaction(), $pdo->commit(), $pdo->rollBack()
//
// Bandingkan dengan Laravel:
// DB::transaction(function () use ($bookId) {
//     $book = Book::where('id', $bookId)->lockForUpdate()->first();
//     $book->decrement('stock');
// });

echo "[5/6] [DATABASE TRANSACTION & ATOMICITY]\n";
try {
    $pdo->beginTransaction();

    // Simulasi peminjaman: kunci baris dengan FOR UPDATE agar race condition tercegah
    $lockStmt = $pdo->prepare("SELECT id, stock FROM books WHERE id = :id FOR UPDATE");
    $lockStmt->execute([':id' => $newBookId]);
    $currentBook = $lockStmt->fetch();

    if ($currentBook['stock'] <= 0) {
        throw new Exception("Stok buku habis!");
    }

    // Kurangi stok sebesar 1
    $deductStmt = $pdo->prepare("UPDATE books SET stock = stock - 1 WHERE id = :id");
    $deductStmt->execute([':id' => $newBookId]);

    $pdo->commit();
    echo "-> Transaksi berhasil: Stok berhasil dikurangi secara atomik via lockForUpdate (FOR UPDATE).\n\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "-> Transaksi gagal dan di-rollback: " . $e->getMessage() . "\n\n";
}

// -----------------------------------------------------------------------------
// 6. DELETE (MENGHAPUS DATA UJI)
// -----------------------------------------------------------------------------
// Native PDO:
// Query DELETE dengan parameter binding.
//
// Bandingkan dengan Laravel:
// $book->delete();

echo "[6/6] [DELETE]\n";
$deleteStmt = $pdo->prepare("DELETE FROM books WHERE id = :id");
$deleteStmt->execute([':id' => $newBookId]);

echo "-> Buku uji coba dengan ID {$newBookId} telah dibersihkan (dihapus).\n";
echo "   Status: Selesai dengan sukses!\n";
echo "=========================================================\n";


<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    /**
     * Pastikan DatabaseSeeder menghasilkan jumlah data yang sesuai dengan spesifikasi.
     */
    public function test_database_seeder_populates_expected_record_counts(): void
    {
        // Jalankan seeder
        $this->seed(DatabaseSeeder::class);

        // 1. Verifikasi Pengguna (1 Admin, 1 Petugas, 5 Anggota)
        $this->assertEquals(7, User::count(), 'Total user harus tepat 7.');
        $this->assertEquals(1, User::where('role', 'admin')->count(), 'Admin harus berjumlah 1.');
        $this->assertEquals(1, User::where('role', 'petugas')->count(), 'Petugas harus berjumlah 1.');
        $this->assertEquals(5, User::where('role', 'anggota')->count(), 'Anggota harus berjumlah 5.');

        // 2. Verifikasi Kategori (5 Kategori)
        $this->assertEquals(5, Category::count(), 'Kategori harus berjumlah tepat 5.');

        // 3. Verifikasi Penulis (10 Penulis)
        $this->assertEquals(10, Author::count(), 'Penulis harus berjumlah tepat 10.');

        // 4. Verifikasi Buku (40 Buku)
        $this->assertEquals(40, Book::count(), 'Buku harus berjumlah tepat 40.');

        // 5. Verifikasi Relasi Many-to-Many Buku dan Penulis
        $pivotCount = DB::table('author_book')->count();
        $this->assertGreaterThanOrEqual(40, $pivotCount, 'Setiap buku harus terhubung ke penulis pada tabel pivot.');

        // 6. Verifikasi Transaksi Peminjaman dan Item Peminjaman
        $this->assertGreaterThanOrEqual(3, Loan::count(), 'Sampel peminjaman minimal 3 transaksi.');
        $this->assertGreaterThanOrEqual(3, LoanItem::count(), 'Item peminjaman minimal 3 item.');
    }

    /**
     * Pastikan seluruh buku terhubung ke kategori yang valid dan memiliki minimal satu penulis.
     */
    public function test_seeded_books_have_valid_relations(): void
    {
        $this->seed(DatabaseSeeder::class);

        $books = Book::with(['category', 'authors'])->get();
        $this->assertCount(40, $books);

        foreach ($books as $book) {
            $this->assertNotNull($book->category, "Buku {$book->title} harus memiliki kategori.");
            $this->assertGreaterThan(0, $book->authors->count(), "Buku {$book->title} harus memiliki minimal 1 penulis.");
        }
    }

    /**
     * Pastikan aturan integritas referensial (onDelete restrict) bekerja pada kategori.
     */
    public function test_foreign_key_restrict_prevents_category_deletion_when_books_exist(): void
    {
        $this->seed(DatabaseSeeder::class);

        $categoryWithBooks = Category::has('books')->first();
        $this->assertNotNull($categoryWithBooks);

        $this->expectException(QueryException::class);
        $categoryWithBooks->delete();
    }
}

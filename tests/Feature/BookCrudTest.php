<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\User;
use Tests\TestCase;

class BookCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::firstOrCreate(['email' => 'admin@perpus.test'], [
            'name'     => 'Administrator',
            'password' => bcrypt('password'),
            'role'     => 'admin',
        ]);
        $this->actingAs($admin);
    }
    public function test_public_catalog_can_be_viewed(): void
    {
        $response = $this->get(route('catalog.index'));

        $response->assertStatus(200);
        $response->assertSee('Katalog Buku Perpustakaan');
    }

    public function test_can_view_admin_books_index(): void
    {
        $response = $this->get(route('admin.books.index'));

        $response->assertStatus(200);
        $response->assertSee('Kelola Koleksi Buku');
    }

    public function test_can_view_admin_books_create_form(): void
    {
        $response = $this->get(route('admin.books.create'));

        $response->assertStatus(200);
        $response->assertSee('Tambah Buku Baru');
    }

    public function test_can_store_new_book_with_authors(): void
    {
        $category = Category::first();
        $author1 = Author::first();
        $author2 = Author::skip(1)->first();

        $title = 'Buku Pengujian Feature Test ' . uniqid();
        $isbn = '978-' . rand(10, 99) . '-' . rand(10000, 99999) . '-0';

        $response = $this->post(route('admin.books.store'), [
            'category_id' => $category->id,
            'title'       => $title,
            'isbn'        => $isbn,
            'publisher'   => 'Penerbit Uji Coba',
            'year'        => 2025,
            'stock'       => 7,
            'author_ids'  => [$author1->id, $author2->id],
        ]);

        $response->assertRedirect(route('admin.books.index'));
        $response->assertSessionHas('success', 'Buku berhasil ditambahkan.');

        $this->assertDatabaseHas('books', [
            'title' => $title,
            'isbn'  => $isbn,
            'stock' => 7,
        ]);

        $book = Book::where('isbn', $isbn)->first();
        $this->assertNotNull($book);
        $this->assertCount(2, $book->authors);
    }

    public function test_store_validation_requires_authors_and_title(): void
    {
        $response = $this->post(route('admin.books.store'), [
            'title' => '',
            'stock' => -1,
            'author_ids' => [],
        ]);

        $response->assertSessionHasErrors(['title', 'category_id', 'stock', 'author_ids']);
    }

    public function test_can_show_and_update_book(): void
    {
        $category = Category::first();
        $author = Author::first();

        $book = Book::create([
            'category_id' => $category->id,
            'title'       => 'Buku Awal ' . uniqid(),
            'isbn'        => '978-' . rand(10, 99) . '-' . rand(10000, 99999) . '-1',
            'stock'       => 5,
        ]);
        $book->authors()->attach($author->id);

        // Show
        $showResponse = $this->get(route('admin.books.show', $book));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($book->title);

        // Update
        $newTitle = 'Judul Buku Telah Diperbarui ' . uniqid();
        $authors = Author::take(2)->pluck('id')->toArray();

        $updateResponse = $this->put(route('admin.books.update', $book), [
            'category_id' => $book->category_id,
            'title'       => $newTitle,
            'isbn'        => $book->isbn,
            'publisher'   => $book->publisher,
            'year'        => $book->year,
            'stock'       => 20,
            'author_ids'  => $authors,
        ]);

        $updateResponse->assertRedirect(route('admin.books.index'));
        $updateResponse->assertSessionHas('success', 'Buku berhasil diperbarui.');

        $this->assertDatabaseHas('books', [
            'id'    => $book->id,
            'title' => $newTitle,
            'stock' => 20,
        ]);
    }

    public function test_can_delete_book_without_loans(): void
    {
        $category = Category::first();
        $author = Author::first();

        $book = Book::create([
            'category_id' => $category->id,
            'title'       => 'Buku Tanpa Peminjaman ' . uniqid(),
            'stock'       => 5,
        ]);
        $book->authors()->attach($author->id);

        $response = $this->delete(route('admin.books.destroy', $book));

        $response->assertRedirect(route('admin.books.index'));
        $response->assertSessionHas('success', 'Buku berhasil dihapus.');

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_restrict_prevents_deleting_book_with_loans_and_flashes_friendly_error(): void
    {
        // Cari buku yang pernah dipinjam (memiliki loan_items)
        $loanItem = LoanItem::first();
        $this->assertNotNull($loanItem);
        $book = Book::find($loanItem->book_id);
        $this->assertNotNull($book);

        $response = $this->delete(route('admin.books.destroy', $book));

        $response->assertRedirect(route('admin.books.index'));
        $response->assertSessionHas('error', 'Buku tidak dapat dihapus karena pernah tercatat dalam transaksi peminjaman.');

        // Pastikan buku masih ada di database
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LoanTransactionTest extends TestCase
{
    public function test_user_can_borrow_books_and_stocks_are_decremented(): void
    {
        $user = User::factory()->create(['role' => 'anggota']);
        $category = Category::first();

        $book1 = Book::create([
            'category_id' => $category->id,
            'title'       => 'Buku Pinjam 1 ' . uniqid(),
            'stock'       => 5,
        ]);

        $book2 = Book::create([
            'category_id' => $category->id,
            'title'       => 'Buku Pinjam 2 ' . uniqid(),
            'stock'       => 3,
        ]);

        $loan = Loan::borrow($user->id, [$book1->id, $book2->id]);

        $this->assertInstanceOf(Loan::class, $loan);
        $this->assertEquals($user->id, $loan->user_id);
        $this->assertEquals(now()->toDateString(), $loan->loaned_at->toDateString());
        $this->assertEquals(now()->addDays(config('library.loan_days', 7))->toDateString(), $loan->due_date->toDateString());
        $this->assertEquals(2, $loan->loanItems()->count());

        // Pastikan stok fisik berkurang tepat 1 untuk setiap buku
        $this->assertEquals(4, $book1->fresh()->stock);
        $this->assertEquals(2, $book2->fresh()->stock);
    }

    public function test_borrow_fails_if_exceeds_max_books_limit(): void
    {
        $user = User::factory()->create(['role' => 'anggota']);
        $maxLimit = config('library.max_books', 3);

        $category = Category::first();
        $bookIds = [];
        for ($i = 0; $i < $maxLimit + 1; $i++) {
            $book = Book::create([
                'category_id' => $category->id,
                'title'       => 'Buku Limit ' . uniqid(),
                'stock'       => 5,
            ]);
            $bookIds[] = $book->id;
        }

        $this->expectException(ValidationException::class);
        Loan::borrow($user->id, $bookIds);
    }

    public function test_borrow_fails_if_book_stock_is_empty(): void
    {
        $user = User::factory()->create(['role' => 'anggota']);
        $category = Category::first();

        $emptyBook = Book::create([
            'category_id' => $category->id,
            'title'       => 'Buku Habis Stok ' . uniqid(),
            'stock'       => 0,
        ]);

        $this->expectException(ValidationException::class);
        Loan::borrow($user->id, [$emptyBook->id]);
    }

    public function test_borrow_fails_if_user_has_unpaid_fine(): void
    {
        $user = User::factory()->create(['role' => 'anggota']);
        $category = Category::first();

        // Buat pinjaman terdahulu dengan denda unpaid
        Loan::factory()->create([
            'user_id'     => $user->id,
            'fine_amount' => 5000,
            'fine_status' => 'unpaid',
        ]);

        $book = Book::create([
            'category_id' => $category->id,
            'title'       => 'Buku Tes ' . uniqid(),
            'stock'       => 5,
        ]);

        $this->expectException(ValidationException::class);
        Loan::borrow($user->id, [$book->id]);
    }

    public function test_return_books_restores_stock_and_calculates_fine_if_delayed(): void
    {
        $user = User::factory()->create(['role' => 'anggota']);
        $category = Category::first();

        $book = Book::create([
            'category_id' => $category->id,
            'title'       => 'Buku Kembali ' . uniqid(),
            'stock'       => 4,
        ]);

        $loan = Loan::borrow($user->id, [$book->id]);
        $this->assertEquals(3, $book->fresh()->stock);

        // Simulasi pengembalian terlambat 4 hari
        $dueDate = $loan->due_date;
        $delayedReturnDate = $dueDate->copy()->addDays(4)->toDateString();

        $loan->returnBooks($delayedReturnDate);

        // Stok bertambah kembali
        $this->assertEquals(4, $book->fresh()->stock);

        // Denda terhitung (4 hari x 1000 x 1 buku = 4000)
        $this->assertEquals(4000.00, (float) $loan->fresh()->fine_amount);
        $this->assertEquals('unpaid', $loan->fresh()->fine_status);

        // Pelunasan denda
        $loan->fresh()->markFinePaid();
        $this->assertEquals('paid', $loan->fresh()->fine_status);
    }
}

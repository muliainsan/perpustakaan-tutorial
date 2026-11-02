<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\User;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    public function test_get_api_books_returns_paginated_json(): void
    {
        $response = $this->getJson('/api/books');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'isbn',
                    'publisher',
                    'year',
                    'stock',
                    'is_available',
                    'category',
                    'authors',
                    'created_at',
                ],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta'  => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total'],
        ]);
    }

    public function test_get_api_books_filters_by_search_and_availability(): void
    {
        $book = Book::first();
        $this->assertNotNull($book);

        // Filter pencarian judul
        $response = $this->getJson('/api/books?search=' . urlencode(substr($book->title, 0, 8)));
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));

        // Filter ketersediaan stok
        $availableResponse = $this->getJson('/api/books?available=1');
        $availableResponse->assertStatus(200);
        foreach ($availableResponse->json('data') as $item) {
            $this->assertGreaterThan(0, $item['stock']);
            $this->assertTrue($item['is_available']);
        }
    }

    public function test_get_api_book_detail(): void
    {
        $book = Book::first();

        $response = $this->getJson("/api/books/{$book->id}");
        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $book->id);
        $response->assertJsonPath('data.title', $book->title);

        // Buku tidak ada (404)
        $notFoundResponse = $this->getJson('/api/books/999999');
        $notFoundResponse->assertStatus(404);
    }

    public function test_get_api_categories_and_authors(): void
    {
        $catResponse = $this->getJson('/api/categories');
        $catResponse->assertStatus(200);
        $this->assertCount(5, $catResponse->json('data'));

        $authResponse = $this->getJson('/api/authors');
        $authResponse->assertStatus(200);
        $this->assertCount(10, $authResponse->json('data'));
    }

    public function test_api_loan_transaction_lifecycle(): void
    {
        $user = User::factory()->create(['role' => 'anggota']);
        $category = Category::first();

        $book = Book::create([
            'category_id' => $category->id,
            'title'       => 'Buku API Test ' . uniqid(),
            'stock'       => 5,
        ]);

        // 1. Submit Pinjaman Baru (POST /api/loans)
        $borrowResponse = $this->postJson('/api/loans', [
            'user_id'  => $user->id,
            'book_ids' => [$book->id],
        ]);

        $borrowResponse->assertStatus(201);
        $loanId = $borrowResponse->json('data.id');
        $this->assertNotNull($loanId);
        $this->assertEquals(4, $book->fresh()->stock);

        // 2. Tampilkan Detail Pinjaman (GET /api/loans/{id})
        $detailResponse = $this->getJson("/api/loans/{$loanId}");
        $detailResponse->assertStatus(200);
        $detailResponse->assertJsonPath('data.user_id', $user->id);

        // 3. Pengembalian Buku Terlambat (POST /api/loans/{id}/return)
        $loan = Loan::find($loanId);
        $returnDate = $loan->due_date->copy()->addDays(2)->toDateString();

        $returnResponse = $this->postJson("/api/loans/{$loanId}/return", [
            'returned_at' => $returnDate,
        ]);

        $returnResponse->assertStatus(200);
        $this->assertEquals(5, $book->fresh()->stock);
        $this->assertEquals(2000, (float) $returnResponse->json('data.fine_amount'));
        $this->assertEquals('unpaid', $returnResponse->json('data.fine_status'));

        // 4. Pelunasan Denda (POST /api/loans/{id}/pay-fine)
        $payFineResponse = $this->postJson("/api/loans/{$loanId}/pay-fine");
        $payFineResponse->assertStatus(200);
        $this->assertEquals('paid', $payFineResponse->json('data.fine_status'));
    }

    public function test_api_error_responses_always_return_json_without_accept_header(): void
    {
        // Kirim request HTTP POST biasa tanpa header Accept: application/json
        $response = $this->call('POST', '/api/loans', [], [], [], [
            'HTTP_ACCEPT' => '*/*',
        ]);

        // Verifikasi bahwa status adalah 422 Unprocessable Entity
        $response->assertStatus(422);

        // Verifikasi respons dikembalikan dalam format application/json
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));

        // Verifikasi format struktur galat Laravel
        $this->assertArrayHasKey('message', $response->json());
        $this->assertArrayHasKey('errors', $response->json());
    }
}

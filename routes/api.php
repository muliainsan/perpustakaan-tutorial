<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuthorController as ApiAuthorController;
use App\Http\Controllers\Api\BookController as ApiBookController;
use App\Http\Controllers\Api\CategoryController as ApiCategoryController;
use App\Http\Controllers\Api\LoanController as ApiLoanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API Routes (Pertemuan 4: Keamanan & Sanctum Token Auth)
|--------------------------------------------------------------------------
*/

// Autentikasi API Publik
Route::post('/login', [AuthController::class, 'login']);

// Master Data Referensi & Katalog Publik API (Bebas Akses)
Route::get('/books', [ApiBookController::class, 'index']);
Route::get('/books/{id}', [ApiBookController::class, 'show']);
Route::get('/categories', [ApiCategoryController::class, 'index']);
Route::get('/categories/{id}', [ApiCategoryController::class, 'show']);
Route::get('/authors', [ApiAuthorController::class, 'index']);
Route::get('/authors/{id}', [ApiAuthorController::class, 'show']);

// Endpoint Terproteksi Sanctum (Harus Menyertakan Bearer Token)
Route::middleware('auth:sanctum')->group(function () {
    // Sesi Pengguna
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Peminjaman (Anggota hanya pinjaman sendiri, Petugas/Admin semua pinjaman)
    Route::get('/loans', [ApiLoanController::class, 'index']);
    Route::get('/loans/{id}', [ApiLoanController::class, 'show']);
    Route::post('/loans', [ApiLoanController::class, 'store']);

    // Operasional & Master Data (Hanya Petugas & Admin)
    Route::middleware('role:admin,petugas')->group(function () {
        Route::post('/loans/{id}/return', [ApiLoanController::class, 'returnBooks']);
        Route::post('/loans/{id}/pay-fine', [ApiLoanController::class, 'payFine']);

        // CRUD API Master Data Kategori
        Route::post('/categories', [ApiCategoryController::class, 'store']);
        Route::put('/categories/{id}', [ApiCategoryController::class, 'update']);
        Route::delete('/categories/{id}', [ApiCategoryController::class, 'destroy']);

        // CRUD API Master Data Penulis
        Route::post('/authors', [ApiAuthorController::class, 'store']);
        Route::put('/authors/{id}', [ApiAuthorController::class, 'update']);
        Route::delete('/authors/{id}', [ApiAuthorController::class, 'destroy']);

        // CRUD API Master Data Buku
        Route::post('/books', [ApiBookController::class, 'store']);
        Route::put('/books/{id}', [ApiBookController::class, 'update']);
        Route::delete('/books/{id}', [ApiBookController::class, 'destroy']);
    });
});

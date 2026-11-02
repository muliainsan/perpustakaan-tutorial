<?php

use App\Http\Controllers\Api\AuthorController as ApiAuthorController;
use App\Http\Controllers\Api\BookController as ApiBookController;
use App\Http\Controllers\Api\CategoryController as ApiCategoryController;
use App\Http\Controllers\Api\LoanController as ApiLoanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API Routes (Pertemuan 3)
|--------------------------------------------------------------------------
*/

// Master Data Referensi & Katalog Publik API
Route::get('/books', [ApiBookController::class, 'index']);
Route::get('/books/{id}', [ApiBookController::class, 'show']);
Route::get('/categories', [ApiCategoryController::class, 'index']);
Route::get('/authors', [ApiAuthorController::class, 'index']);

// Transaksi Peminjaman, Pengembalian, dan Denda
// TODO P4: auth:sanctum
Route::get('/loans', [ApiLoanController::class, 'index']);
Route::get('/loans/{id}', [ApiLoanController::class, 'show']);
Route::post('/loans', [ApiLoanController::class, 'store']);
Route::post('/loans/{id}/return', [ApiLoanController::class, 'returnBooks']);
Route::post('/loans/{id}/pay-fine', [ApiLoanController::class, 'payFine']);

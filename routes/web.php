<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\Member\MyLoanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Pertemuan 4: Keamanan & Otorisasi Lengkap)
|--------------------------------------------------------------------------
*/

// Katalog Publik
Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');

// Rute Autentikasi Pengguna (Tamu / Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// Logout (Wajib Terautentikasi)
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Halaman Khusus Anggota Terautentikasi
Route::middleware('auth')->group(function () {
    Route::get('/my/loans', [MyLoanController::class, 'index'])->name('member.loans');
});

// Halaman Monitoring & Manajemen Data Master (Khusus Petugas & Admin)
Route::middleware(['auth', 'role:admin,petugas'])->group(function () {
    Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('authors', AuthorController::class)->except(['show']);
        Route::resource('books', BookController::class);
    });
});

// Halaman Manajemen Pengguna (Khusus Admin)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.update-role');
});

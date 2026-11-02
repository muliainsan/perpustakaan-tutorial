<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

// Rute katalog publik server-rendered
Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');

// TODO P4: lindungi dengan middleware peran
Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('authors', AuthorController::class)->except(['show']);
    Route::resource('books', BookController::class);
});

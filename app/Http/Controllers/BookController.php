<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * Tampilkan daftar koleksi buku untuk admin (dengan filter & pagination).
     * Eager loading 'category' dan 'authors' digunakan untuk mengeliminasi potensi query N+1.
     */
    public function index(Request $request): View
    {
        $books = Book::with(['category', 'authors'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('isbn', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->input('category_id'));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        return view('admin.books.index', compact('books', 'categories'));
    }

    /**
     * Tampilkan form pembuatan buku baru.
     */
    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        $authors = Author::orderBy('name')->get();

        return view('admin.books.create', compact('categories', 'authors'));
    }

    /**
     * Simpan buku baru ke basis data beserta relasi penulisnya (pivot author_book).
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $book = Book::create([
            'category_id' => $validated['category_id'],
            'title'       => $validated['title'],
            'isbn'        => $validated['isbn'] ?? null,
            'publisher'   => $validated['publisher'] ?? null,
            'year'        => $validated['year'] ?? null,
            'stock'       => $validated['stock'],
        ]);

        $book->authors()->sync($validated['author_ids']);

        return redirect()
            ->route('admin.books.index')
            ->with('success', 'Buku berhasil ditambahkan.');
    }

    /**
     * Tampilkan detail informasi satu buku.
     */
    public function show(Book $book): View
    {
        $book->load(['category', 'authors']);

        return view('admin.books.show', compact('book'));
    }

    /**
     * Tampilkan form pengeditan buku.
     */
    public function edit(Book $book): View
    {
        $book->load('authors');
        $categories = Category::orderBy('name')->get();
        $authors = Author::orderBy('name')->get();

        return view('admin.books.edit', compact('book', 'categories', 'authors'));
    }

    /**
     * Perbarui data buku dan sinkronkan relasi penulisnya.
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $validated = $request->validated();

        $book->update([
            'category_id' => $validated['category_id'],
            'title'       => $validated['title'],
            'isbn'        => $validated['isbn'] ?? null,
            'publisher'   => $validated['publisher'] ?? null,
            'year'        => $validated['year'] ?? null,
            'stock'       => $validated['stock'],
        ]);

        $book->authors()->sync($validated['author_ids']);

        return redirect()
            ->route('admin.books.index')
            ->with('success', 'Buku berhasil diperbarui.');
    }

    /**
     * Hapus buku dari basis data dengan proteksi referensial (restrict).
     */
    public function destroy(Book $book): RedirectResponse
    {
        try {
            $book->delete();

            return redirect()
                ->route('admin.books.index')
                ->with('success', 'Buku berhasil dihapus.');
        } catch (QueryException $e) {
            // Error MySQL 1451: Foreign key constraint fails (restrict)
            return redirect()
                ->route('admin.books.index')
                ->with('error', 'Buku tidak dapat dihapus karena pernah tercatat dalam transaksi peminjaman.');
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Models\Author;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuthorController extends Controller
{
    /**
     * Tampilkan daftar seluruh penulis.
     */
    public function index(): View
    {
        $authors = Author::withCount('books')
            ->orderBy('name')
            ->paginate(10);

        return view('admin.authors.index', compact('authors'));
    }

    /**
     * Tampilkan form pembuatan penulis baru.
     */
    public function create(): View
    {
        return view('admin.authors.create');
    }

    /**
     * Simpan penulis baru ke basis data.
     */
    public function store(StoreAuthorRequest $request): RedirectResponse
    {
        Author::create($request->validated());

        return redirect()
            ->route('admin.authors.index')
            ->with('success', 'Penulis berhasil ditambahkan.');
    }

    /**
     * Tampilkan form untuk mengedit penulis.
     */
    public function edit(Author $author): View
    {
        return view('admin.authors.edit', compact('author'));
    }

    /**
     * Perbarui data penulis di basis data.
     */
    public function update(UpdateAuthorRequest $request, Author $author): RedirectResponse
    {
        $author->update($request->validated());

        return redirect()
            ->route('admin.authors.index')
            ->with('success', 'Penulis berhasil diperbarui.');
    }

    /**
     * Hapus penulis dari basis data.
     */
    public function destroy(Author $author): RedirectResponse
    {
        try {
            $author->delete();

            return redirect()
                ->route('admin.authors.index')
                ->with('success', 'Penulis berhasil dihapus.');
        } catch (QueryException $e) {
            return redirect()
                ->route('admin.authors.index')
                ->with('error', 'Penulis tidak dapat dihapus karena terkait dengan data lain.');
        }
    }
}

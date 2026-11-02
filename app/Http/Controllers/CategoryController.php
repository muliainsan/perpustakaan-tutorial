<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Tampilkan daftar seluruh kategori.
     */
    public function index(): View
    {
        $categories = Category::withCount('books')
            ->orderBy('name')
            ->paginate(10);

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Tampilkan form pembuatan kategori baru.
     */
    public function create(): View
    {
        return view('admin.categories.create');
    }

    /**
     * Simpan kategori baru ke basis data.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    /**
     * Tampilkan form untuk mengedit kategori.
     */
    public function edit(Category $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Perbarui data kategori di basis data.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Hapus kategori dari basis data dengan proteksi referensial (restrict).
     */
    public function destroy(Category $category): RedirectResponse
    {
        try {
            $category->delete();

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Kategori berhasil dihapus.');
        } catch (QueryException $e) {
            // Error MySQL 1451: Foreign key constraint fails (restrict)
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Kategori tidak dapat dihapus karena masih memuat buku.');
        }
    }
}

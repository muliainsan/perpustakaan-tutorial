<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * Tampilkan katalog publik server-rendered pada rute GET /.
     * Eager loading 'category' dan 'authors' digunakan untuk efisiensi query.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $categoryId = $request->input('category_id');

        $books = Book::with(['category', 'authors'])
            ->when($request->filled('search'), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('isbn', 'like', "%{$search}%")
                      ->orWhereHas('authors', function ($authorQuery) use ($search) {
                          $authorQuery->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        return view('catalog.index', compact('books', 'categories'));
    }
}


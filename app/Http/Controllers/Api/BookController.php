<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    /**
     * Tampilkan katalog buku berhalaman dengan filter pencarian dan ketersediaan.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->input('per_page', 10), 50);

        $books = Book::with(['category', 'authors'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('isbn', 'like', "%{$search}%")
                      ->orWhereHas('authors', function ($authorQuery) use ($search) {
                          $authorQuery->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->input('category_id'));
            })
            ->when($request->boolean('available'), function ($query) {
                $query->where('stock', '>', 0);
            })
            ->latest()
            ->paginate($perPage);

        return BookResource::collection($books);
    }

    /**
     * Tampilkan detail satu buku spesifik.
     */
    public function show(int $id): BookResource
    {
        $book = Book::with(['category', 'authors'])->findOrFail($id);

        return new BookResource($book);
    }
}

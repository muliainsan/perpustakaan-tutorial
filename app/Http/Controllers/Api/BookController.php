<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
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

    /**
     * Simpan entri buku baru beserta relasi penulis (Admin/Petugas).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id'  => ['required', 'integer', 'exists:categories,id'],
            'title'        => ['required', 'string', 'max:255'],
            'isbn'         => ['nullable', 'string', 'max:20', 'unique:books,isbn'],
            'publisher'    => ['nullable', 'string', 'max:150'],
            'year'         => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'stock'        => ['required', 'integer', 'min:0'],
            'description'  => ['nullable', 'string'],
            'author_ids'   => ['nullable', 'array'],
            'author_ids.*' => ['integer', 'exists:authors,id'],
        ], [
            'category_id.required' => 'Kategori buku wajib dipilih.',
            'category_id.exists'   => 'Kategori yang dipilih tidak valid.',
            'title.required'       => 'Judul buku wajib diisi.',
            'isbn.unique'          => 'Nomor ISBN sudah terdaftar.',
            'stock.required'       => 'Jumlah stok buku wajib diisi.',
            'stock.min'            => 'Jumlah stok minimal 0.',
        ]);

        $book = Book::create($validated);

        if ($request->has('author_ids')) {
            $book->authors()->sync($request->input('author_ids', []));
        }

        return (new BookResource($book->load(['category', 'authors'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Perbarui data buku (Admin/Petugas).
     */
    public function update(Request $request, int $id): BookResource
    {
        $book = Book::findOrFail($id);

        $validated = $request->validate([
            'category_id'  => ['required', 'integer', 'exists:categories,id'],
            'title'        => ['required', 'string', 'max:255'],
            'isbn'         => ['nullable', 'string', 'max:20', 'unique:books,isbn,' . $id],
            'publisher'    => ['nullable', 'string', 'max:150'],
            'year'         => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'stock'        => ['required', 'integer', 'min:0'],
            'description'  => ['nullable', 'string'],
            'author_ids'   => ['nullable', 'array'],
            'author_ids.*' => ['integer', 'exists:authors,id'],
        ], [
            'category_id.required' => 'Kategori buku wajib dipilih.',
            'category_id.exists'   => 'Kategori yang dipilih tidak valid.',
            'title.required'       => 'Judul buku wajib diisi.',
            'isbn.unique'          => 'Nomor ISBN sudah terdaftar.',
            'stock.required'       => 'Jumlah stok buku wajib diisi.',
            'stock.min'            => 'Jumlah stok minimal 0.',
        ]);

        $book->update($validated);

        if ($request->has('author_ids')) {
            $book->authors()->sync($request->input('author_ids', []));
        }

        return new BookResource($book->fresh(['category', 'authors']));
    }

    /**
     * Hapus buku dari katalog (Protected against active loan item restrict).
     */
    public function destroy(int $id): JsonResponse
    {
        $book = Book::findOrFail($id);

        try {
            $book->delete();

            return response()->json([
                'message' => 'Buku berhasil dihapus.',
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Buku tidak dapat dihapus karena masih terkait dengan data transaksi peminjaman.',
                'error'   => 'Conflict',
            ], 409);
        }
    }
}

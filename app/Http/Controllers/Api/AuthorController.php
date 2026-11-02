<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthorResource;
use App\Models\Author;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuthorController extends Controller
{
    /**
     * Tampilkan seluruh data penulis.
     */
    public function index(): AnonymousResourceCollection
    {
        $authors = Author::withCount('books')->orderBy('name')->get();

        return AuthorResource::collection($authors);
    }

    /**
     * Tampilkan detail data satu penulis.
     */
    public function show(int $id): AuthorResource
    {
        $author = Author::withCount('books')->findOrFail($id);

        return new AuthorResource($author);
    }

    /**
     * Tambah penulis baru (Admin/Petugas).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'bio'   => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama penulis wajib diisi.',
            'email.email'   => 'Format email tidak valid.',
        ]);

        $author = Author::create($validated);

        return (new AuthorResource($author))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Perbarui data penulis (Admin/Petugas).
     */
    public function update(Request $request, int $id): AuthorResource
    {
        $author = Author::findOrFail($id);

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'bio'   => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama penulis wajib diisi.',
            'email.email'   => 'Format email tidak valid.',
        ]);

        $author->update($validated);

        return new AuthorResource($author);
    }

    /**
     * Hapus data penulis.
     */
    public function destroy(int $id): JsonResponse
    {
        $author = Author::findOrFail($id);

        try {
            $author->delete();

            return response()->json([
                'message' => 'Penulis berhasil dihapus.',
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Penulis tidak dapat dihapus karena masih terkait data lain.',
                'error'   => 'Conflict',
            ], 409);
        }
    }
}

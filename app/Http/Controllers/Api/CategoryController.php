<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Tampilkan seluruh kategori buku.
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::withCount('books')->orderBy('name')->get();

        return CategoryResource::collection($categories);
    }

    /**
     * Tampilkan detail satu kategori.
     */
    public function show(int $id): CategoryResource
    {
        $category = Category::withCount('books')->findOrFail($id);

        return new CategoryResource($category);
    }

    /**
     * Tambah kategori baru (Admin/Petugas).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique'   => 'Nama kategori sudah digunakan.',
        ]);

        $category = Category::create([
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Perbarui data kategori (Admin/Petugas).
     */
    public function update(Request $request, int $id): CategoryResource
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:categories,name,' . $id],
            'description' => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique'   => 'Nama kategori sudah digunakan.',
        ]);

        $category->update([
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        return new CategoryResource($category);
    }

    /**
     * Hapus kategori (Protected by foreign key restrict).
     */
    public function destroy(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        try {
            $category->delete();

            return response()->json([
                'message' => 'Kategori berhasil dihapus.',
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Kategori tidak dapat dihapus karena masih digunakan oleh buku terkait.',
                'error'   => 'Conflict',
            ], 409);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    /**
     * Tampilkan seluruh data master kategori.
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::withCount('books')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }
}

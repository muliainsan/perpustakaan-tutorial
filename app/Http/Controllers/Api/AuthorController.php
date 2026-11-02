<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthorResource;
use App\Models\Author;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuthorController extends Controller
{
    /**
     * Tampilkan seluruh data master penulis.
     */
    public function index(): AnonymousResourceCollection
    {
        $authors = Author::withCount('books')
            ->orderBy('name')
            ->get();

        return AuthorResource::collection($authors);
    }
}

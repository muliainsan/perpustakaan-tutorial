<?php

namespace App\Models;

use Database\Factories\AuthorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends Model
{
    /** @use HasFactory<AuthorFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Relasi many-to-many ke buku melalui author_book.
     */
    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'author_book');
    }
}

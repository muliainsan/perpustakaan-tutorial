<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'title',
        'isbn',
        'publisher',
        'year',
        'stock',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'stock' => 'integer',
        ];
    }

    /**
     * Relasi ke kategori buku (Many-to-One).
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relasi many-to-many ke penulis melalui author_book.
     */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'author_book');
    }

    /**
     * Relasi ke item peminjaman.
     */
    public function loanItems(): HasMany
    {
        return $this->hasMany(LoanItem::class);
    }
}


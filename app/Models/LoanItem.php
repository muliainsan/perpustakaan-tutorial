<?php

namespace App\Models;

use Database\Factories\LoanItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanItem extends Model
{
    /** @use HasFactory<LoanItemFactory> */
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'book_id',
    ];

    /**
     * Relasi ke transaksi peminjaman.
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Relasi ke buku yang dipinjam.
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}

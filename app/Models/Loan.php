<?php

namespace App\Models;

use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'loaned_at',
        'due_date',
        'returned_at',
        'fine_amount',
        'fine_status',
    ];

    protected function casts(): array
    {
        return [
            'loaned_at' => 'date',
            'due_date' => 'date',
            'returned_at' => 'date',
            'fine_amount' => 'decimal:2',
        ];
    }

    /**
     * Relasi ke peminjam / pengguna.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke item peminjaman.
     */
    public function loanItems(): HasMany
    {
        return $this->hasMany(LoanItem::class);
    }

    /**
     * Relasi ke buku melalui tabel pivot loan_items.
     */
    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'loan_items');
    }
}

<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            'loaned_at'   => 'date',
            'due_date'    => 'date',
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

    /**
     * Logika Bisnis: Pinjam Buku (Fat Model)
     * Menggunakan DB::transaction dan pessimistic locking (lockForUpdate) untuk mencegah race condition.
     *
     * @param int $userId
     * @param array<int> $bookIds
     * @return Loan
     * @throws ValidationException
     */
    public static function borrow(int $userId, array $bookIds): Loan
    {
        $maxBooks = config('library.max_books', 3);
        $loanDays = config('library.loan_days', 7);

        // 1. Validasi batas kuota buku per transaksi (1 s.d. max_books)
        $uniqueBookIds = array_unique($bookIds);
        $count = count($uniqueBookIds);

        if ($count < 1 || $count > $maxBooks) {
            throw ValidationException::withMessages([
                'book_ids' => ["Jumlah buku yang dipinjam harus antara 1 sampai {$maxBooks} judul buku."],
            ]);
        }

        // 2. Validasi ketiadaan pinjaman overdue atau denda unpaid pada peminjam
        $hasUnpaidFines = self::where('user_id', $userId)
            ->where('fine_status', 'unpaid')
            ->exists();

        if ($hasUnpaidFines) {
            throw ValidationException::withMessages([
                'user_id' => ['Pengguna masih memiliki tanggungan denda keterlambatan yang belum dibayar.'],
            ]);
        }

        $hasOverdueLoans = self::where('user_id', $userId)
            ->whereNull('returned_at')
            ->where('due_date', '<', now()->toDateString())
            ->exists();

        if ($hasOverdueLoans) {
            throw ValidationException::withMessages([
                'user_id' => ['Pengguna masih memiliki pinjaman buku yang telah melewati batas jatuh tempo (overdue).'],
            ]);
        }

        // 3. Eksekusi transaksi atomik dengan pessimistic locking
        return DB::transaction(function () use ($userId, $uniqueBookIds, $loanDays) {
            // Kunci baris buku yang akan dipinjam untuk mencegah race condition
            $books = Book::whereIn('id', $uniqueBookIds)->lockForUpdate()->get();

            if ($books->count() !== count($uniqueBookIds)) {
                throw ValidationException::withMessages([
                    'book_ids' => ['Salah satu atau lebih buku yang dipilih tidak ditemukan dalam sistem.'],
                ]);
            }

            // Periksa ketersediaan stok masing-masing buku
            foreach ($books as $book) {
                if ($book->stock <= 0) {
                    throw ValidationException::withMessages([
                        'book_ids' => ["Stok untuk buku '{$book->title}' sedang kosong dan tidak dapat dipinjam."],
                    ]);
                }
            }

            // Buat record transaksi peminjaman
            $loan = self::create([
                'user_id'     => $userId,
                'loaned_at'   => now()->toDateString(),
                'due_date'    => now()->addDays($loanDays)->toDateString(),
                'returned_at' => null,
                'fine_amount' => 0,
                'fine_status' => 'none',
            ]);

            // Buat detail item peminjaman dan kurangi stok buku
            foreach ($books as $book) {
                LoanItem::create([
                    'loan_id' => $loan->id,
                    'book_id' => $book->id,
                ]);

                $book->decrement('stock');
            }

            return $loan->load(['loanItems.book', 'user']);
        });
    }

    /**
     * Logika Bisnis: Pengembalian Buku
     * Mengembalikan stok buku ke inventaris dan menghitung denda keterlambatan jika ada.
     *
     * @param string|null $returnedAt Format Y-m-d
     * @return void
     * @throws ValidationException
     */
    public function returnBooks(?string $returnedAt = null): void
    {
        if ($this->returned_at !== null) {
            throw ValidationException::withMessages([
                'loan' => ['Buku pada transaksi peminjaman ini telah dikembalikan sebelumnya.'],
            ]);
        }

        $returnedDate = $returnedAt ?: now()->toDateString();

        DB::transaction(function () use ($returnedDate) {
            // Muat relasi item dan buku jika belum dimuat
            $this->loadMissing('loanItems.book');

            // Kembalikan stok fisik setiap buku
            foreach ($this->loanItems as $item) {
                if ($item->book) {
                    $item->book->increment('stock');
                }
            }

            $this->returned_at = $returnedDate;

            // Hitung denda berdasarkan tanggal kembali
            $fineAmount = $this->hitungDenda();
            $this->fine_amount = $fineAmount;
            $this->fine_status = $fineAmount > 0 ? 'unpaid' : 'none';
            $this->save();
        });
    }

    /**
     * Logika Bisnis: Kalkulasi Denda Keterlambatan
     * Rumus:
     * hari_terlambat = max(0, selisih_hari(returned_at, due_date))
     * denda_per_buku = min(hari_terlambat * fine_per_day, fine_max)
     * fine_amount    = denda_per_buku * jumlah_buku
     *
     * @return float
     */
    public function hitungDenda(): float
    {
        $finePerDay = (float) config('library.fine_per_day', 1000);
        $fineMax = (float) config('library.fine_max', 50000);

        $returnDate = Carbon::parse($this->returned_at ?: now()->toDateString());
        $dueDate = Carbon::parse($this->due_date);

        // Hanya dikenakan denda jika tanggal kembali melewati due_date
        if ($returnDate->gt($dueDate)) {
            $hariTerlambat = $dueDate->diffInDays($returnDate);
        } else {
            $hariTerlambat = 0;
        }

        $dendaPerBuku = min($hariTerlambat * $finePerDay, $fineMax);

        // Hitung total denda untuk seluruh buku yang dipinjam dalam transaksi ini
        $jumlahBuku = $this->loanItems()->count();
        if ($jumlahBuku === 0) {
            $jumlahBuku = 1;
        }

        return (float) ($dendaPerBuku * $jumlahBuku);
    }

    /**
     * Periksa apakah buku sudah dikembalikan.
     */
    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }

    /**
     * Periksa apakah peminjaman telah melewati batas jatuh tempo (terlambat).
     */
    public function isOverdue(): bool
    {
        $compareDate = $this->returned_at ? Carbon::parse($this->returned_at) : now();
        $dueDate = Carbon::parse($this->due_date);

        return $compareDate->gt($dueDate);
    }

    /**
     * Logika Bisnis: Pelunasan Denda
     */
    public function markFinePaid(): void
    {
        if ($this->fine_status === 'unpaid') {
            $this->fine_status = 'paid';
            $this->save();
        }
    }
}

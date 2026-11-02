<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoanResource;
use App\Models\Loan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LoanController extends Controller
{
    /**
     * Tampilkan riwayat dan daftar transaksi peminjaman.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->input('per_page', 10), 50);

        $loans = Loan::with(['user', 'loanItems.book'])
            ->when($request->filled('user_id'), function ($query) use ($request) {
                $query->where('user_id', $request->input('user_id'));
            })
            ->when($request->filled('fine_status'), function ($query) use ($request) {
                $query->where('fine_status', $request->input('fine_status'));
            })
            ->when($request->input('status') === 'active', function ($query) {
                $query->whereNull('returned_at');
            })
            ->when($request->input('status') === 'returned', function ($query) {
                $query->whereNotNull('returned_at');
            })
            ->latest()
            ->paginate($perPage);

        return LoanResource::collection($loans);
    }

    /**
     * Tampilkan detail transaksi peminjaman spesifik.
     */
    public function show(int $id): LoanResource
    {
        $loan = Loan::with(['user', 'loanItems.book'])->findOrFail($id);

        return new LoanResource($loan);
    }

    /**
     * Buat transaksi peminjaman baru melalui Domain Model Loan::borrow().
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'    => ['required', 'integer', 'exists:users,id'],
            'book_ids'   => ['required', 'array', 'min:1'],
            'book_ids.*' => ['required', 'integer', 'exists:books,id'],
        ], [
            'user_id.required'    => 'ID pengguna peminjam wajib disertakan.',
            'user_id.exists'      => 'Pengguna tidak ditemukan dalam sistem.',
            'book_ids.required'   => 'Daftar ID buku yang dipinjam wajib diisi.',
            'book_ids.array'      => 'Format book_ids harus berupa array.',
            'book_ids.min'        => 'Pilih minimal satu buku untuk dipinjam.',
            'book_ids.*.exists'   => 'Salah satu ID buku tidak valid.',
        ]);

        $loan = Loan::borrow((int) $validated['user_id'], $validated['book_ids']);

        return (new LoanResource($loan))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Proses pengembalian buku dan kalkulasi denda otomatis.
     */
    public function returnBooks(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'returned_at' => ['nullable', 'date', 'date_format:Y-m-d'],
        ], [
            'returned_at.date_format' => 'Format tanggal kembali harus Y-m-d.',
        ]);

        $loan = Loan::with('loanItems.book')->findOrFail($id);
        $loan->returnBooks($validated['returned_at'] ?? null);

        return response()->json([
            'message' => 'Buku berhasil dikembalikan.',
            'data'    => new LoanResource($loan->fresh(['user', 'loanItems.book'])),
        ]);
    }

    /**
     * Catat pelunasan denda keterlambatan peminjaman.
     */
    public function payFine(int $id): JsonResponse
    {
        $loan = Loan::findOrFail($id);

        if ($loan->fine_status !== 'unpaid') {
            return response()->json([
                'message' => 'Transaksi ini tidak memiliki denda tertunggak yang perlu dilunasi.',
                'data'    => new LoanResource($loan),
            ], 422);
        }

        $loan->markFinePaid();

        return response()->json([
            'message' => 'Denda keterlambatan berhasil dilunasi.',
            'data'    => new LoanResource($loan->fresh(['user', 'loanItems.book'])),
        ]);
    }
}

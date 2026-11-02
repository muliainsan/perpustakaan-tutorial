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
     * Anggota hanya dapat melihat peminjaman miliknya sendiri (Pencegahan IDOR).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->input('per_page', 10), 50);
        $user = $request->user();

        $loans = Loan::with(['user', 'loanItems.book'])
            ->when($user && $user->role === 'anggota', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }, function ($query) use ($request) {
                if ($request->filled('user_id')) {
                    $query->where('user_id', $request->input('user_id'));
                }
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
     * Tampilkan detail transaksi peminjaman spesifik dengan pencegahan IDOR.
     */
    public function show(Request $request, int $id): LoanResource|JsonResponse
    {
        $loan = Loan::with(['user', 'loanItems.book'])->findOrFail($id);
        $user = $request->user();

        // Validasi Otorisasi IDOR: Anggota tidak boleh melihat pinjaman milik anggota lain
        if ($user && $user->role === 'anggota' && $loan->user_id !== $user->id) {
            return response()->json([
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk melihat riwayat pinjaman pengguna lain.'
            ], 403);
        }

        return new LoanResource($loan);
    }

    /**
     * Buat transaksi peminjaman baru melalui Domain Model Loan::borrow().
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'user_id'    => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'book_ids'   => ['required', 'array', 'min:1'],
            'book_ids.*' => ['required', 'integer', 'exists:books,id'],
        ], [
            'user_id.exists'      => 'Pengguna tidak ditemukan dalam sistem.',
            'book_ids.required'   => 'Daftar ID buku yang dipinjam wajib diisi.',
            'book_ids.array'      => 'Format book_ids harus berupa array.',
            'book_ids.min'        => 'Pilih minimal satu buku untuk dipinjam.',
            'book_ids.*.exists'   => 'Salah satu ID buku tidak valid.',
        ]);

        $borrowerId = $user ? $user->id : ($validated['user_id'] ?? null);

        if ($user && in_array($user->role, ['admin', 'petugas'], true) && isset($validated['user_id'])) {
            $borrowerId = (int) $validated['user_id'];
        } elseif ($user && $user->role === 'anggota' && isset($validated['user_id']) && (int) $validated['user_id'] !== $user->id) {
            return response()->json([
                'message' => 'Akses ditolak. Anggota hanya dapat meminjam atas nama sendiri.'
            ], 403);
        }

        if (!$borrowerId) {
            return response()->json([
                'message' => 'ID pengguna peminjam wajib disertakan.'
            ], 422);
        }

        $loan = Loan::borrow((int) $borrowerId, $validated['book_ids']);

        return (new LoanResource($loan))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Proses pengembalian buku dan kalkulasi denda otomatis (Petugas/Admin).
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
     * Catat pelunasan denda keterlambatan peminjaman (Petugas/Admin).
     */
    public function payFine(int $id): JsonResponse
    {
        $loan = Loan::findOrFail($id);
        $loan->markFinePaid();

        return response()->json([
            'message' => 'Denda keterlambatan berhasil dilunasi.',
            'data'    => new LoanResource($loan->fresh(['user', 'loanItems.book'])),
        ]);
    }
}

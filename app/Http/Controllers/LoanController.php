<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanController extends Controller
{
    /**
     * Tampilkan monitoring seluruh transaksi peminjaman perpustakaan (Admin/Petugas).
     */
    public function index(Request $request): View
    {
        $loans = Loan::with(['user', 'loanItems.book'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('member_code', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                if ($request->input('status') === 'active') {
                    $query->whereNull('returned_at');
                } elseif ($request->input('status') === 'returned') {
                    $query->whereNotNull('returned_at');
                }
            })
            ->latest()
            ->paginate(15);

        return view('loans.index', compact('loans'));
    }
}


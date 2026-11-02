<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyLoanController extends Controller
{
    /**
     * Tampilkan riwayat dan daftar pinjaman aktif milik anggota yang sedang login.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $loans = Loan::with(['loanItems.book'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        return view('member.loans', compact('loans', 'user'));
    }
}


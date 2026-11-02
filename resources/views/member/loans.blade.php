@extends('layouts.perpustakaan', ['title' => 'Riwayat Pinjaman Saya'])

@section('content')
<div class="space-y-6">
    <!-- Profil Anggota -->
    <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 rounded-xl shadow-md p-6 text-white">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-xs uppercase tracking-wider bg-indigo-500/40 text-indigo-100 px-2.5 py-0.5 rounded-full font-mono">
                    {{ $user->member_code ?? 'ANGGOTA' }}
                </span>
                <h1 class="text-2xl font-bold mt-2">{{ $user->name }}</h1>
                <p class="text-sm text-indigo-200">{{ $user->email }} @if($user->phone) • {{ $user->phone }} @endif</p>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 text-center sm:text-right border border-white/10">
                <div class="text-2xl font-extrabold">{{ $loans->total() }}</div>
                <div class="text-xs text-indigo-200">Total Transaksi Pinjaman</div>
            </div>
        </div>
    </div>

    <!-- Riwayat Transaksi -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-bold text-gray-800">Daftar Peminjaman Buku</h2>
            <span class="text-xs text-gray-500">Maksimal {{ config('library.max_books', 3) }} buku per peminjaman</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Kode Transaksi</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Buku Dipinjam</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Tgl Pinjam</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Jatuh Tempo</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Status Kembali</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Denda</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($loans as $loan)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-xs text-gray-600">
                                #LOAN-{{ str_pad($loan->id, 5, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-6 py-4">
                                <ul class="list-disc list-inside space-y-1 text-xs text-gray-800">
                                    @foreach ($loan->loanItems as $item)
                                        <li>
                                            <span class="font-semibold">{{ $item->book->title ?? 'Buku Dihapus' }}</span>
                                            @if($item->book && $item->book->isbn)
                                                <span class="text-gray-400">({{ $item->book->isbn }})</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600">
                                {{ $loan->loaned_at ? $loan->loaned_at->format('d M Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs">
                                <span class="{{ $loan->isOverdue() && !$loan->isReturned() ? 'text-rose-600 font-bold' : 'text-gray-600' }}">
                                    {{ $loan->due_date ? $loan->due_date->format('d M Y') : '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($loan->isReturned())
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">
                                        Dikembalikan ({{ $loan->returned_at->format('d/m/Y') }})
                                    </span>
                                @elseif ($loan->isOverdue())
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-rose-100 text-rose-800">
                                        Terlambat
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Sedang Dipinjam
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs">
                                @if ($loan->fine_amount > 0)
                                    <div class="font-semibold {{ $loan->fine_status === 'paid' ? 'text-emerald-600' : 'text-rose-600' }}">
                                        Rp {{ number_format($loan->fine_amount, 0, ',', '.') }}
                                    </div>
                                    <div class="text-[11px] {{ $loan->fine_status === 'paid' ? 'text-emerald-500' : 'text-rose-500' }}">
                                        ({{ $loan->fine_status === 'paid' ? 'Lunas' : 'Belum Lunas' }})
                                    </div>
                                @else
                                    <span class="text-gray-400">Rp 0</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                Anda belum memiliki riwayat peminjaman buku.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($loans->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $loans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection


@extends('layouts.perpustakaan', ['title' => 'Monitoring Transaksi Peminjaman'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Monitoring Peminjaman</h1>
            <p class="text-sm text-gray-500">Kelola dan pantau seluruh sirkulasi buku dan denda keterlambatan anggota.</p>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('loans.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-grow">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama peminjam atau kode anggota..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div class="w-full sm:w-48">
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Sedang Dipinjam</option>
                    <option value="returned" {{ request('status') === 'returned' ? 'selected' : '' }}>Sudah Kembali</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                Filter
            </button>
            @if(request()->anyFilled(['search', 'status']))
                <a href="{{ route('loans.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition text-center">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Tabel Peminjaman -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Kode</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Peminjam</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Buku Dipinjam</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Tgl Pinjam</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Jatuh Tempo</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Status</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Denda</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($loans as $loan)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-xs text-gray-600">
                                #LOAN-{{ str_pad($loan->id, 5, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-semibold text-gray-900">{{ $loan->user->name ?? 'Pengguna Dihapus' }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ $loan->user->member_code ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <ul class="list-disc list-inside space-y-1 text-xs text-gray-800">
                                    @foreach ($loan->loanItems as $item)
                                        <li>{{ $item->book->title ?? 'Buku Dihapus' }}</li>
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
                                        Kembali
                                    </span>
                                @elseif ($loan->isOverdue())
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-rose-100 text-rose-800">
                                        Terlambat
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs">
                                @if ($loan->fine_amount > 0)
                                    <span class="font-semibold {{ $loan->fine_status === 'paid' ? 'text-emerald-600' : 'text-rose-600' }}">
                                        Rp {{ number_format($loan->fine_amount, 0, ',', '.') }}
                                    </span>
                                    <span class="text-[11px] block {{ $loan->fine_status === 'paid' ? 'text-emerald-500' : 'text-rose-500' }}">
                                        ({{ $loan->fine_status === 'paid' ? 'Lunas' : 'Belum Lunas' }})
                                    </span>
                                @else
                                    <span class="text-gray-400">Rp 0</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                Tidak ada transaksi peminjaman yang sesuai.
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


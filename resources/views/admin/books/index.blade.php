@extends('layouts.perpustakaan')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Kelola Koleksi Buku</h1>
            <p class="text-sm text-gray-500 mt-1">Daftar inventaris buku, stok fisik, dan relasi penulis.</p>
        </div>
        <div>
            <a href="{{ route('admin.books.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Buku Baru
            </a>
        </div>
    </div>

    <!-- Filter & Pencarian Buku -->
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
        <form action="{{ route('admin.books.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <div class="sm:col-span-6">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari judul atau ISBN..." 
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
            </div>
            <div class="sm:col-span-4">
                <select name="category_id" class="w-full px-4 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (string) request('category_id') === (string) $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2 flex space-x-2">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 rounded-lg text-sm transition">
                    Filter
                </button>
                @if(request()->filled('search') || request()->filled('category_id'))
                    <a href="{{ route('admin.books.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm flex items-center justify-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold">#</th>
                        <th class="px-6 py-3 text-left font-semibold">Judul Buku & ISBN</th>
                        <th class="px-6 py-3 text-left font-semibold">Kategori</th>
                        <th class="px-6 py-3 text-left font-semibold">Penulis</th>
                        <th class="px-6 py-3 text-center font-semibold">Stok</th>
                        <th class="px-6 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($books as $index => $book)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-gray-500">{{ $books->firstItem() + $index }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.books.show', $book) }}" class="font-medium text-gray-900 hover:text-indigo-600">
                                    {{ $book->title }}
                                </a>
                                <div class="text-xs text-gray-400 font-mono mt-0.5">ISBN: {{ $book->isbn ?: '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    {{ $book->category->name }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600 text-xs">
                                {{ $book->authors->pluck('name')->join(', ') ?: '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if ($book->stock > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">
                                        {{ $book->stock }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700">
                                        Habis
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('admin.books.show', $book) }}" class="text-gray-600 hover:text-gray-900 font-medium text-xs">
                                    Detail
                                </a>
                                <a href="{{ route('admin.books.edit', $book) }}" class="text-indigo-600 hover:text-indigo-900 font-medium text-xs">
                                    Edit
                                </a>
                                <form action="{{ route('admin.books.destroy', $book) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menghapus buku ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-900 font-medium text-xs">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                Belum ada data buku yang sesuai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($books->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $books->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@extends('layouts.perpustakaan')

@section('content')
<div class="space-y-8">
    <!-- Hero / Header Section -->
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-2xl p-8 text-white shadow-lg">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Katalog Buku Perpustakaan</h1>
        <p class="mt-2 text-indigo-100 max-w-2xl text-base sm:text-lg">
            Temukan berbagai koleksi buku ilmu pengetahuan, pemrograman, sastra, dan referensi akademik terbaik.
        </p>

        <!-- Form Pencarian & Filter Server-Rendered -->
        <form action="{{ route('catalog.index') }}" method="GET" class="mt-6 grid grid-cols-1 sm:grid-cols-12 gap-3">
            <div class="sm:col-span-6">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari judul buku, ISBN, atau nama penulis..." 
                    class="w-full px-4 py-2.5 rounded-lg text-gray-900 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-300 text-sm shadow-sm"
                >
            </div>
            <div class="sm:col-span-4">
                <select 
                    name="category_id" 
                    class="w-full px-4 py-2.5 rounded-lg text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 text-sm shadow-sm"
                >
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (string) request('category_id') === (string) $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2 flex space-x-2">
                <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-medium px-4 py-2.5 rounded-lg text-sm transition shadow-sm flex items-center justify-center">
                    Cari
                </button>
                @if(request()->filled('search') || request()->filled('category_id'))
                    <a href="{{ route('catalog.index') }}" class="bg-indigo-900 hover:bg-indigo-950 text-white px-3 py-2.5 rounded-lg text-sm flex items-center justify-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Hasil Koleksi Buku -->
    @if ($books->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach ($books as $book)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition flex flex-col overflow-hidden">
                    <div class="p-5 flex-grow flex flex-col">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                                {{ $book->category->name }}
                            </span>
                            @if ($book->stock > 0)
                                <span class="text-xs font-semibold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700">
                                    Stok: {{ $book->stock }}
                                </span>
                            @else
                                <span class="text-xs font-semibold px-2 py-0.5 rounded bg-rose-50 text-rose-700">
                                    Stok Habis
                                </span>
                            @endif
                        </div>

                        <h2 class="font-bold text-gray-900 text-base leading-snug line-clamp-2 hover:text-indigo-600 transition">
                            {{ $book->title }}
                        </h2>

                        <p class="text-xs text-gray-500 mt-2">
                            Penulis: 
                            <span class="text-gray-700 font-medium">
                                {{ $book->authors->pluck('name')->join(', ') ?: '-' }}
                            </span>
                        </p>

                        <div class="mt-auto pt-4 border-t border-gray-100 text-xs text-gray-400 flex justify-between items-center">
                            <span>{{ $book->publisher ?? 'Penerbit -' }} ({{ $book->year ?? '-' }})</span>
                            <span class="font-mono text-gray-500">{{ $book->isbn }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $books->links() }}
        </div>
    @else
        <div class="bg-white rounded-xl p-12 text-center border border-gray-200">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-3 text-lg font-medium text-gray-900">Buku Tidak Ditemukan</h3>
            <p class="mt-1 text-sm text-gray-500">Tidak ada buku yang sesuai dengan kriteria pencarian atau filter yang Anda pilih.</p>
            <div class="mt-6">
                <a href="{{ route('catalog.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                    Lihat Semua Buku
                </a>
            </div>
        </div>
    @endif
</div>
@endsection

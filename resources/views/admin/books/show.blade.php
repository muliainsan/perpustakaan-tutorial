@extends('layouts.perpustakaan')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.books.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 flex items-center">
                &larr; Kembali ke Daftar Buku
            </a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $book->title }}</h1>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('admin.books.edit', $book) }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg shadow-sm transition">
                Edit Buku
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden p-6 sm:p-8 space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Kategori</span>
                <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">
                    {{ $book->category->name }}
                </span>
            </div>

            <div>
                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Ketersediaan Stok</span>
                <span class="mt-1 inline-flex items-center text-sm font-semibold {{ $book->stock > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $book->stock > 0 ? "Tersedia ({$book->stock} eksemplar)" : 'Stok Habis' }}
                </span>
            </div>

            <div>
                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">ISBN</span>
                <span class="mt-1 block text-sm font-mono text-gray-800">{{ $book->isbn ?: '-' }}</span>
            </div>

            <div>
                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Penerbit & Tahun</span>
                <span class="mt-1 block text-sm text-gray-800">{{ $book->publisher ?: '-' }} ({{ $book->year ?: '-' }})</span>
            </div>
        </div>

        <div class="pt-6 border-t border-gray-100">
            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Penulis Buku</span>
            <div class="flex flex-wrap gap-2">
                @forelse ($book->authors as $author)
                    <span class="inline-flex items-center px-3 py-1 rounded-md text-sm font-medium bg-gray-100 text-gray-800 border border-gray-200">
                        {{ $author->name }}
                    </span>
                @empty
                    <span class="text-sm text-gray-500">- Tidak ada data penulis -</span>
                @endforelse
            </div>
        </div>

        <div class="pt-6 border-t border-gray-100 text-xs text-gray-400 flex justify-between">
            <span>Dibuat: {{ $book->created_at->format('d M Y H:i') }}</span>
            <span>Terakhir Diperbarui: {{ $book->updated_at->format('d M Y H:i') }}</span>
        </div>
    </div>
</div>
@endsection

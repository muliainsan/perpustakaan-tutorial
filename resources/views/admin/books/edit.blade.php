@extends('layouts.perpustakaan')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sm:p-8">
        <div class="mb-6">
            <h1 class="text-xl font-bold text-gray-900">Edit Data Buku</h1>
            <p class="text-sm text-gray-500 mt-1">Perbarui informasi bibliografis atau jumlah stok buku.</p>
        </div>

        <form action="{{ route('admin.books.update', $book) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Judul Buku -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Judul Buku <span class="text-rose-500">*</span></label>
                <input 
                    type="text" 
                    name="title" 
                    id="title" 
                    value="{{ old('title', $book->title) }}" 
                    required 
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-4 py-2.5 border"
                >
                @error('title')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Kategori Buku -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Kategori <span class="text-rose-500">*</span></label>
                    <select 
                        name="category_id" 
                        id="category_id" 
                        required 
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-4 py-2.5 border"
                    >
                        <option value="">Pilih Kategori...</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string) old('category_id', $book->category_id) === (string) $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- ISBN -->
                <div>
                    <label for="isbn" class="block text-sm font-medium text-gray-700">ISBN</label>
                    <input 
                        type="text" 
                        name="isbn" 
                        id="isbn" 
                        value="{{ old('isbn', $book->isbn) }}" 
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-4 py-2.5 border"
                    >
                    @error('isbn')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Penulis (Relasi Many-to-Many) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Penulis Buku (Pilih minimal 1) <span class="text-rose-500">*</span></label>
                @php
                    $selectedAuthors = old('author_ids', $book->authors->pluck('id')->toArray());
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto p-3 border border-gray-200 rounded-lg bg-gray-50">
                    @foreach ($authors as $author)
                        <label class="flex items-center space-x-2 text-sm text-gray-700 hover:text-indigo-600 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="author_ids[]" 
                                value="{{ $author->id }}"
                                {{ in_array($author->id, $selectedAuthors) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            >
                            <span>{{ $author->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('author_ids')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Penerbit -->
                <div>
                    <label for="publisher" class="block text-sm font-medium text-gray-700">Penerbit</label>
                    <input 
                        type="text" 
                        name="publisher" 
                        id="publisher" 
                        value="{{ old('publisher', $book->publisher) }}" 
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-4 py-2.5 border"
                    >
                    @error('publisher')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tahun Terbit -->
                <div>
                    <label for="year" class="block text-sm font-medium text-gray-700">Tahun Terbit</label>
                    <input 
                        type="number" 
                        name="year" 
                        id="year" 
                        value="{{ old('year', $book->year) }}" 
                        min="1000" 
                        max="{{ date('Y') + 1 }}"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-4 py-2.5 border"
                    >
                    @error('year')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Stok -->
                <div>
                    <label for="stock" class="block text-sm font-medium text-gray-700">Stok Fisik <span class="text-rose-500">*</span></label>
                    <input 
                        type="number" 
                        name="stock" 
                        id="stock" 
                        value="{{ old('stock', $book->stock) }}" 
                        min="0" 
                        required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-4 py-2.5 border"
                    >
                    @error('stock')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100">
                <a href="{{ route('admin.books.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg shadow-sm transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

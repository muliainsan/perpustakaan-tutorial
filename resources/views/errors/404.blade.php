@extends('layouts.perpustakaan', ['title' => '404 - Halaman Tidak Ditemukan'])

@section('content')
<div class="max-w-md mx-auto my-16 text-center">
    <div class="inline-flex items-center justify-center w-20 h-20 bg-amber-100 text-amber-600 rounded-full mb-6">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>
    <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">404</h1>
    <h2 class="text-xl font-bold text-gray-800 mt-2">Halaman Tidak Ditemukan</h2>
    <p class="text-sm text-gray-600 mt-2">
        Halaman atau sumber daya yang Anda cari tidak tersedia atau telah dipindahkan.
    </p>

    <div class="mt-8 flex justify-center gap-4">
        <a href="{{ route('catalog.index') }}" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg shadow-sm transition">
            Kembali ke Katalog
        </a>
    </div>
</div>
@endsection


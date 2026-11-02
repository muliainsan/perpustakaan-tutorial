@extends('layouts.perpustakaan', ['title' => '403 - Akses Ditolak'])

@section('content')
<div class="max-w-md mx-auto my-16 text-center">
    <div class="inline-flex items-center justify-center w-20 h-20 bg-rose-100 text-rose-600 rounded-full mb-6">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m11-3.5a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>
    <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">403</h1>
    <h2 class="text-xl font-bold text-gray-800 mt-2">Akses Ditolak</h2>
    <p class="text-sm text-gray-600 mt-2">
        {{ $exception->getMessage() ?: 'Maaf, Anda tidak memiliki izin peran yang sesuai untuk mengakses halaman ini.' }}
    </p>

    <div class="mt-8 flex justify-center gap-4">
        <a href="{{ route('catalog.index') }}" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg shadow-sm transition">
            Kembali ke Katalog
        </a>
    </div>
</div>
@endsection


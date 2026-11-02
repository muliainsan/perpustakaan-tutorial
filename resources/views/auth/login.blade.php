@extends('layouts.perpustakaan', ['title' => 'Masuk ke Sistem'])

@section('content')
<div class="max-w-md mx-auto my-12">
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-indigo-100 text-indigo-600 rounded-full mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Masuk ke Akun</h1>
            <p class="text-sm text-gray-500 mt-1">Masukkan email dan kata sandi Anda untuk melanjutkan</p>
        </div>

        <form action="{{ route('login') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Alamat Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none transition {{ $errors->has('email') ? 'border-rose-500 bg-rose-50' : 'border-gray-300' }}"
                       placeholder="nama@email.com">
                @error('email')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="flex justify-between items-center mb-1">
                    <label for="password" class="block text-sm font-semibold text-gray-700">Kata Sandi</label>
                </div>
                <input type="password" name="password" id="password" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none transition {{ $errors->has('password') ? 'border-rose-500 bg-rose-50' : 'border-gray-300' }}"
                       placeholder="••••••••">
                @error('password')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center space-x-2 text-sm text-gray-600 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                    <span>Ingat saya</span>
                </label>
            </div>

            <button type="submit"
                    class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm transition">
                Masuk
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-gray-100 text-center">
            <p class="text-sm text-gray-600">
                Belum memiliki akun anggota?
                <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Daftar sekarang</a>
            </p>
        </div>
    </div>
</div>
@endsection


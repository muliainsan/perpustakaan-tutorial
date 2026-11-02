@extends('layouts.perpustakaan', ['title' => 'Pendaftaran Anggota'])

@section('content')
<div class="max-w-md mx-auto my-10">
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Daftar Anggota Baru</h1>
            <p class="text-sm text-gray-500 mt-1">Lengkapi formulir berikut untuk menjadi anggota perpustakaan</p>
        </div>

        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none transition {{ $errors->has('name') ? 'border-rose-500 bg-rose-50' : 'border-gray-300' }}"
                       placeholder="Misal: Andi Prasetyo">
                @error('name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Alamat Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none transition {{ $errors->has('email') ? 'border-rose-500 bg-rose-50' : 'border-gray-300' }}"
                       placeholder="andi@example.test">
                @error('email')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1">Nomor Telepon (Opsional)</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none transition border-gray-300"
                       placeholder="081234567890">
                @error('phone')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Kata Sandi (Min. 8 karakter)</label>
                <input type="password" name="password" id="password" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none transition {{ $errors->has('password') ? 'border-rose-500 bg-rose-50' : 'border-gray-300' }}"
                       placeholder="••••••••">
                @error('password')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1">Konfirmasi Kata Sandi</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none transition border-gray-300"
                       placeholder="••••••••">
            </div>

            <button type="submit"
                    class="w-full mt-2 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg shadow-sm transition">
                Daftar Sekarang
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-gray-100 text-center">
            <p class="text-sm text-gray-600">
                Sudah memiliki akun?
                <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Masuk di sini</a>
            </p>
        </div>
    </div>
</div>
@endsection


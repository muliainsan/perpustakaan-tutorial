<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Beranda' }} - Sistem Perpustakaan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col font-sans antialiased">
    <!-- Header / Navbar Navigasi -->
    <header class="bg-indigo-700 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <a href="{{ route('catalog.index') }}" class="flex items-center space-x-2 font-bold text-xl tracking-tight hover:text-indigo-100">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Perpustakaan</span>
                    </a>
                </div>

                <nav class="hidden md:flex items-center space-x-1">
                    <a href="{{ route('catalog.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('catalog.index') ? 'bg-indigo-900 text-white' : 'text-indigo-100 hover:bg-indigo-600' }}">
                        Katalog
                    </a>

                    @auth
                        @if (in_array(auth()->user()->role, ['admin', 'petugas'], true))
                            <a href="{{ route('admin.books.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('admin.books.*') ? 'bg-indigo-900 text-white' : 'text-indigo-100 hover:bg-indigo-600' }}">
                                Kelola Buku
                            </a>
                            <a href="{{ route('admin.categories.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('admin.categories.*') ? 'bg-indigo-900 text-white' : 'text-indigo-100 hover:bg-indigo-600' }}">
                                Kategori
                            </a>
                            <a href="{{ route('admin.authors.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('admin.authors.*') ? 'bg-indigo-900 text-white' : 'text-indigo-100 hover:bg-indigo-600' }}">
                                Penulis
                            </a>
                            <a href="{{ route('loans.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('loans.index') ? 'bg-indigo-900 text-white' : 'text-indigo-100 hover:bg-indigo-600' }}">
                                Peminjaman
                            </a>
                        @endif

                        @if (auth()->user()->role === 'admin')
                            <a href="{{ route('admin.users.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('admin.users.*') ? 'bg-indigo-900 text-white' : 'text-indigo-100 hover:bg-indigo-600' }}">
                                Pengguna
                            </a>
                        @endif

                        @if (auth()->user()->role === 'anggota')
                            <a href="{{ route('member.loans') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('member.loans') ? 'bg-indigo-900 text-white' : 'text-indigo-100 hover:bg-indigo-600' }}">
                                Pinjaman Saya
                            </a>
                        @endif
                    @endauth
                </nav>

                <div class="flex items-center space-x-3">
                    @guest
                        <a href="{{ route('login') }}" class="text-sm font-medium text-indigo-100 hover:text-white px-3 py-1.5 rounded-lg hover:bg-indigo-600 transition">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}" class="text-sm font-medium bg-white text-indigo-700 hover:bg-indigo-50 px-3.5 py-1.5 rounded-lg shadow-sm transition">
                            Daftar
                        </a>
                    @else
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ auth()->user()->role === 'admin' ? 'bg-purple-800 text-purple-200' : (auth()->user()->role === 'petugas' ? 'bg-blue-800 text-blue-200' : 'bg-emerald-800 text-emerald-200') }}">
                                {{ ucfirst(auth()->user()->role) }}
                            </span>
                            <span class="text-sm font-medium text-white hidden sm:inline">
                                {{ auth()->user()->name }}
                            </span>
                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-xs bg-indigo-800 hover:bg-indigo-900 text-indigo-200 hover:text-white px-2.5 py-1.5 rounded border border-indigo-600 transition">
                                    Keluar
                                </button>
                            </form>
                        </div>
                    @endguest
                </div>
            </div>
        </div>

        <!-- Mobile navigation menu -->
        <div class="md:hidden border-t border-indigo-800 px-4 pt-2 pb-3 space-y-1">
            <a href="{{ route('catalog.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('catalog.index') ? 'bg-indigo-900 text-white' : 'text-indigo-100 hover:bg-indigo-600' }}">
                Katalog Publik
            </a>

            @auth
                @if (in_array(auth()->user()->role, ['admin', 'petugas'], true))
                    <a href="{{ route('admin.books.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.books.*') ? 'bg-indigo-900 text-white' : 'text-indigo-100 hover:bg-indigo-600' }}">
                        Kelola Buku
                    </a>
                    <a href="{{ route('admin.categories.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.categories.*') ? 'bg-indigo-900 text-white' : 'text-indigo-100 hover:bg-indigo-600' }}">
                        Kelola Kategori
                    </a>
                    <a href="{{ route('admin.authors.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.authors.*') ? 'bg-indigo-900 text-white' : 'text-indigo-100 hover:bg-indigo-600' }}">
                        Kelola Penulis
                    </a>
                    <a href="{{ route('loans.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('loans.index') ? 'bg-indigo-900 text-white' : 'text-indigo-100 hover:bg-indigo-600' }}">
                        Peminjaman
                    </a>
                @endif

                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('admin.users.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.users.*') ? 'bg-indigo-900 text-white' : 'text-indigo-100 hover:bg-indigo-600' }}">
                        Manajemen Pengguna
                    </a>
                @endif

                @if (auth()->user()->role === 'anggota')
                    <a href="{{ route('member.loans') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('member.loans') ? 'bg-indigo-900 text-white' : 'text-indigo-100 hover:bg-indigo-600' }}">
                        Pinjaman Saya
                    </a>
                @endif
            @endauth
        </div>
    </header>

    <!-- Konten Utama -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Flash Message: Sukses -->
        @if (session('success'))
            <div class="mb-6 flex items-center p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-md text-emerald-800 shadow-sm">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <div class="text-sm font-medium">{{ session('success') }}</div>
            </div>
        @endif

        <!-- Flash Message: Galat / Restrict Error -->
        @if (session('error'))
            <div class="mb-6 flex items-center p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-md text-rose-800 shadow-sm">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <div class="text-sm font-medium">{{ session('error') }}</div>
            </div>
        @endif

        <!-- Alert Kesalahan Validasi Form -->
        @if ($errors->any())
            <div class="mb-6 p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-md text-amber-900 shadow-sm">
                <div class="font-semibold text-sm mb-1">Terdapat kesalahan pada input Anda:</div>
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-sm text-gray-500">
            &copy; {{ date('Y') }} Sistem Informasi Perpustakaan. Dikembangkan untuk Praktikum Pemrograman Web.
        </div>
    </footer>
</body>
</html>

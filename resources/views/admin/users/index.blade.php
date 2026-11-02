@extends('layouts.perpustakaan', ['title' => 'Manajemen Pengguna'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manajemen Pengguna</h1>
            <p class="text-sm text-gray-500">Kelola akun dan tetapkan peran (Admin, Petugas, Anggota) pengguna sistem.</p>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-grow">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari berdasarkan nama, email, atau kode anggota..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div class="w-full sm:w-48">
                <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">Semua Peran</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="petugas" {{ request('role') === 'petugas' ? 'selected' : '' }}>Petugas</option>
                    <option value="anggota" {{ request('role') === 'anggota' ? 'selected' : '' }}>Anggota</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                Cari
            </button>
            @if(request()->anyFilled(['search', 'role']))
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition text-center">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Tabel Pengguna -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Pengguna</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Kode Anggota</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Peran Saat Ini</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Ubah Peran</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Terdaftar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($users as $user)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-semibold text-gray-900">{{ $user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $user->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600 font-mono text-xs">
                                {{ $user->member_code ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($user->role === 'admin')
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 border border-purple-200">
                                        Admin
                                    </span>
                                @elseif ($user->role === 'petugas')
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 border border-blue-200">
                                        Petugas
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        Anggota
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form action="{{ route('admin.users.update-role', $user) }}" method="POST" class="inline-flex items-center space-x-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" class="px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500">
                                        <option value="anggota" {{ $user->role === 'anggota' ? 'selected' : '' }}>Anggota</option>
                                        <option value="petugas" {{ $user->role === 'petugas' ? 'selected' : '' }}>Petugas</option>
                                        <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                    </select>
                                    <button type="submit" class="px-3 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded text-xs font-medium border border-indigo-200 transition">
                                        Simpan
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                {{ $user->created_at->format('d M Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                Tidak ada data pengguna yang sesuai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection


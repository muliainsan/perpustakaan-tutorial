@extends('layouts.perpustakaan')

@section('content')
<div class="space-y-8">
    <!-- Hero / Header Section -->
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-2xl p-8 text-white shadow-lg">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Katalog Buku Perpustakaan</h1>
                <p class="mt-2 text-indigo-100 max-w-2xl text-base sm:text-lg">
                    Jelajahi koleksi buku secara interaktif didukung antarmuka REST API &amp; Vanilla JS.
                </p>
            </div>
            <div class="hidden sm:block">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-400 text-indigo-950">
                    <span class="w-2 h-2 mr-1.5 bg-emerald-950 rounded-full animate-pulse"></span>
                    Live REST API
                </span>
            </div>
        </div>

        <!-- Filter & Search Controls (Vanilla JS Fetch) -->
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-12 gap-3">
            <div class="sm:col-span-6">
                <div class="relative">
                    <input 
                        type="text" 
                        id="search-input" 
                        placeholder="Ketik untuk mencari judul, ISBN, atau penulis (debounce)..." 
                        class="w-full pl-10 pr-4 py-2.5 rounded-lg text-gray-900 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-300 text-sm shadow-sm"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
            <div class="sm:col-span-4">
                <select 
                    id="category-filter" 
                    class="w-full px-4 py-2.5 rounded-lg text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 text-sm shadow-sm"
                >
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2 flex items-center justify-between sm:justify-end space-x-2">
                <label class="flex items-center space-x-2 text-xs font-medium text-indigo-100 cursor-pointer select-none">
                    <input type="checkbox" id="available-filter" class="rounded text-indigo-900 focus:ring-indigo-300">
                    <span>Hanya Stok Ada</span>
                </label>
                <button 
                    id="reset-btn" 
                    class="bg-indigo-900 hover:bg-indigo-950 text-xs px-3 py-2 rounded-lg text-white font-medium transition"
                    title="Reset Filter"
                >
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Alert Galat Jaringan / API -->
    <div id="error-container" class="hidden p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-md text-rose-800 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span id="error-message" class="text-sm font-medium">Gagal memuat katalog buku dari API.</span>
            </div>
            <button id="retry-btn" class="text-xs bg-rose-100 hover:bg-rose-200 text-rose-800 px-3 py-1 rounded font-medium transition">
                Coba Lagi
            </button>
        </div>
    </div>

    <!-- Status Loading Spinner / Skeleton -->
    <div id="loading-spinner" class="py-12 flex flex-col items-center justify-center space-y-3">
        <div class="w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
        <p class="text-sm font-medium text-gray-500">Memuat data buku dari /api/books...</p>
    </div>

    <!-- Grid Kontainer Buku -->
    <div id="books-grid" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <!-- Kartu buku akan disisipkan di sini oleh Vanilla JS -->
    </div>

    <!-- Tampilan Kosong -->
    <div id="empty-state" class="hidden bg-white rounded-xl p-12 text-center border border-gray-200">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="mt-3 text-lg font-medium text-gray-900">Buku Tidak Ditemukan</h3>
        <p class="mt-1 text-sm text-gray-500">Tidak ada buku yang sesuai dengan filter pencarian Anda saat ini.</p>
    </div>

    <!-- Kontrol Pagination API -->
    <div id="pagination-container" class="hidden bg-white px-4 py-3 rounded-xl border border-gray-200 flex items-center justify-between shadow-sm">
        <div class="text-xs text-gray-600" id="pagination-info">
            Menampilkan data buku...
        </div>
        <div class="flex space-x-2">
            <button 
                id="prev-page-btn" 
                class="px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition"
            >
                &larr; Sebelumnya
            </button>
            <button 
                id="next-page-btn" 
                class="px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition"
            >
                Berikutnya &rarr;
            </button>
        </div>
    </div>
</div>

<!-- Logika Interaktivitas Frontend (Vanilla JS Fetch & Debounce) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    let currentPage = 1;
    let debounceTimer = null;
    let lastMeta = null;

    const searchInput = document.getElementById('search-input');
    const categoryFilter = document.getElementById('category-filter');
    const availableFilter = document.getElementById('available-filter');
    const resetBtn = document.getElementById('reset-btn');
    const retryBtn = document.getElementById('retry-btn');

    const loadingSpinner = document.getElementById('loading-spinner');
    const booksGrid = document.getElementById('books-grid');
    const emptyState = document.getElementById('empty-state');
    const errorContainer = document.getElementById('error-container');
    const errorMessage = document.getElementById('error-message');
    const paginationContainer = document.getElementById('pagination-container');
    const paginationInfo = document.getElementById('pagination-info');
    const prevPageBtn = document.getElementById('prev-page-btn');
    const nextPageBtn = document.getElementById('next-page-btn');

    /**
     * Mengambil data dari REST API endpoint /api/books
     */
    async function fetchBooks() {
        showLoading();

        const params = new URLSearchParams({
            page: currentPage,
            per_page: 12
        });

        const search = searchInput.value.trim();
        if (search) params.append('search', search);

        const categoryId = categoryFilter.value;
        if (categoryId) params.append('category_id', categoryId);

        if (availableFilter.checked) {
            params.append('available', '1');
        }

        try {
            const response = await fetch(`/api/books?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status} ${response.statusText}`);
            }

            const json = await response.json();
            renderBooks(json.data || []);
            renderPagination(json.meta || null);
        } catch (error) {
            showError(error.message || 'Terjadi kesalahan saat menghubungi API.');
        }
    }

    /**
     * Render daftar kartu buku ke DOM
     */
    function renderBooks(books) {
        loadingSpinner.classList.add('hidden');
        errorContainer.classList.add('hidden');

        if (!books || books.length === 0) {
            booksGrid.classList.add('hidden');
            emptyState.classList.remove('hidden');
            paginationContainer.classList.add('hidden');
            return;
        }

        emptyState.classList.add('hidden');
        booksGrid.classList.remove('hidden');
        booksGrid.innerHTML = '';

        books.forEach(book => {
            const authorsText = book.authors && book.authors.length > 0 
                ? book.authors.map(a => a.name).join(', ') 
                : '-';

            const categoryName = book.category ? book.category.name : 'Umum';

            const stockBadge = book.stock > 0
                ? `<span class="text-xs font-semibold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700">Stok: ${book.stock}</span>`
                : `<span class="text-xs font-semibold px-2 py-0.5 rounded bg-rose-50 text-rose-700">Stok Habis</span>`;

            const card = document.createElement('div');
            card.className = 'bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition flex flex-col overflow-hidden';
            card.innerHTML = `
                <div class="p-5 flex-grow flex flex-col">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                            ${escapeHtml(categoryName)}
                        </span>
                        ${stockBadge}
                    </div>

                    <h2 class="font-bold text-gray-900 text-base leading-snug line-clamp-2 hover:text-indigo-600 transition">
                        ${escapeHtml(book.title)}
                    </h2>

                    <p class="text-xs text-gray-500 mt-2">
                        Penulis: 
                        <span class="text-gray-700 font-medium">${escapeHtml(authorsText)}</span>
                    </p>

                    <div class="mt-auto pt-4 border-t border-gray-100 text-xs text-gray-400 flex justify-between items-center">
                        <span>${escapeHtml(book.publisher || 'Penerbit -')} (${book.year || '-'})</span>
                        <span class="font-mono text-gray-500">${escapeHtml(book.isbn || '-')}</span>
                    </div>
                </div>
            `;

            booksGrid.appendChild(card);
        });
    }

    /**
     * Render kontrol pagination
     */
    function renderPagination(meta) {
        lastMeta = meta;
        if (!meta || meta.total <= meta.per_page) {
            paginationContainer.classList.add('hidden');
            return;
        }

        paginationContainer.classList.remove('hidden');
        paginationInfo.textContent = `Menampilkan ${meta.from || 0} - ${meta.to || 0} dari total ${meta.total} buku (Halaman ${meta.current_page} dari ${meta.last_page})`;

        prevPageBtn.disabled = meta.current_page <= 1;
        nextPageBtn.disabled = meta.current_page >= meta.last_page;
    }

    function showLoading() {
        loadingSpinner.classList.remove('hidden');
        errorContainer.classList.add('hidden');
    }

    function showError(msg) {
        loadingSpinner.classList.add('hidden');
        booksGrid.classList.add('hidden');
        emptyState.classList.add('hidden');
        paginationContainer.classList.add('hidden');

        errorMessage.textContent = msg;
        errorContainer.classList.remove('hidden');
    }

    function escapeHtml(string) {
        if (!string) return '';
        const div = document.createElement('div');
        div.textContent = string;
        return div.innerHTML;
    }

    // Debounce pencarian (menunda pemanggilan API 350ms setelah ketikan terakhir)
    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            currentPage = 1;
            fetchBooks();
        }, 350);
    });

    categoryFilter.addEventListener('change', function () {
        currentPage = 1;
        fetchBooks();
    });

    availableFilter.addEventListener('change', function () {
        currentPage = 1;
        fetchBooks();
    });

    resetBtn.addEventListener('click', function () {
        searchInput.value = '';
        categoryFilter.value = '';
        availableFilter.checked = false;
        currentPage = 1;
        fetchBooks();
    });

    retryBtn.addEventListener('click', function () {
        fetchBooks();
    });

    prevPageBtn.addEventListener('click', function () {
        if (currentPage > 1) {
            currentPage--;
            fetchBooks();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    nextPageBtn.addEventListener('click', function () {
        if (lastMeta && currentPage < lastMeta.last_page) {
            currentPage++;
            fetchBooks();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    // Panggil pertama kali saat halaman dimuat
    fetchBooks();
});
</script>
@endsection

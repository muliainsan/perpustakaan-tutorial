#!/usr/bin/env bash
set -e

TAG=${1:-"proyek-awal"}
TMP_DIR=$(mktemp -d)
REPO_DIR=$(pwd)

echo "=== Memulai Verifikasi Bersih untuk Tag: $TAG ==="

# 1. Cek PHP 8.3+
PHP_VERSION=$(php -r 'echo PHP_VERSION_ID;')
if [ "$PHP_VERSION" -lt 80300 ]; then
    echo "Galat: PHP 8.3 atau lebih baru diperlukan."
    exit 1
fi

# 2. Clone salinan bersih repositori lokal
git clone "$REPO_DIR" "$TMP_DIR/repo"
cd "$TMP_DIR/repo"
git checkout "$TAG"

# 3. Dependensi Composer & Node
composer install --no-interaction --prefer-dist
if command -v npm >/dev/null 2>&1 && [ -f "package.json" ]; then
    if [ -f "package-lock.json" ]; then
        npm ci
    else
        npm install
    fi
    npm run build
fi

# 4. Konfigurasi Environment & Key
cp .env.example .env
# Database langsung menggunakan konfigurasi perpus di .env.example
php artisan key:generate

# 5. Migrasi & Testing
if [ "$TAG" != "proyek-awal" ]; then
    php artisan migrate:fresh --seed
else
    php artisan migrate:fresh
fi

php artisan test

# 6. Uji Asap Berbasis Tag
php artisan serve --port=8181 &
SERVER_PID=$!
sleep 2

cleanup() {
    kill $SERVER_PID 2>/dev/null || true
    rm -rf "$TMP_DIR"
}
trap cleanup EXIT

case "$TAG" in
    "proyek-awal")
        curl -s -f http://127.0.0.1:8181/ > /dev/null
        ;;
    "pertemuan-1")
        curl -s -f http://127.0.0.1:8181/ > /dev/null
        php artisan tinker --execute='
            assert(\App\Models\Category::count() === 5, "Categories count mismatch");
            assert(\App\Models\Author::count() === 10, "Authors count mismatch");
            assert(\App\Models\Book::count() === 40, "Books count mismatch");
            assert(\App\Models\User::count() === 7, "Users count mismatch");
            assert(\App\Models\Loan::count() >= 3, "Loans count mismatch");
            echo "Verifikasi hitungan data seeder pertemuan-1: BERHASIL\n";
        '
        ;;
    "pertemuan-2")
        curl -s -f http://127.0.0.1:8181/ | grep -q "Katalog Buku Perpustakaan"
        curl -s -f http://127.0.0.1:8181/admin/books | grep -q "Kelola Koleksi Buku"
        curl -s -f http://127.0.0.1:8181/admin/categories | grep -q "Kelola Kategori Buku"
        curl -s -f http://127.0.0.1:8181/admin/authors | grep -q "Kelola Data Penulis"
        echo "Uji asap pertemuan-2 (Katalog, Admin Books, Categories, Authors): BERHASIL"
        ;;
    "pertemuan-3")
        curl -s -f http://127.0.0.1:8181/ > /dev/null
        curl -s -f http://127.0.0.1:8181/api/books | grep -q '"data"'
        curl -s -f http://127.0.0.1:8181/api/categories | grep -q '"data"'
        curl -s -f http://127.0.0.1:8181/api/authors | grep -q '"data"'
        curl -s -f http://127.0.0.1:8181/api/loans | grep -q '"data"'
        echo "Uji asap pertemuan-3 (API books, categories, authors, loans): BERHASIL"
        ;;
    "pertemuan-4")
        # 1. Pastikan tidak ada TODO tertinggal di app/ dan routes/
        ! grep -rn "// TODO P4" app/ routes/
        # 2. Uji asap akses web publik dan form auth
        curl -s -f http://127.0.0.1:8181/ | grep -q "Katalog"
        curl -s -f http://127.0.0.1:8181/login | grep -q "Masuk ke Akun"
        curl -s -f http://127.0.0.1:8181/register | grep -q "Daftar Anggota Baru"
        # 3. Uji proteksi rute admin terhadap tamu (302 redirect)
        ADMIN_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8181/admin/books)
        if [ "$ADMIN_STATUS" -ne 302 ]; then
            echo "Galat: Akses tamu ke /admin/books seharusnya mengembalikan HTTP 302 redirect, didapat $ADMIN_STATUS"
            exit 1
        fi
        # 4. Uji proteksi endpoint API tanpa token (401 JSON)
        API_ME_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8181/api/me)
        if [ "$API_ME_STATUS" -ne 401 ]; then
            echo "Galat: Akses tanpa token ke /api/me seharusnya mengembalikan HTTP 401, didapat $API_ME_STATUS"
            exit 1
        fi
        echo "Uji asap pertemuan-4 (Pembersihan TODO, Auth Web, Proteksi 302 Admin, Proteksi 401 API): BERHASIL"
        ;;
esac

echo "=== Verifikasi Tag $TAG Berhasil dan Lolos Syarat! ==="


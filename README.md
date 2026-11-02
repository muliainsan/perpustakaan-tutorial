# Proyek Praktikum: Aplikasi Perpustakaan

Aplikasi sistem informasi perpustakaan berbasis web dan REST API yang dibangun bertahap menggunakan framework Laravel 13 untuk keperluan praktikum mata kuliah Pemrograman Web.

---

## Spesifikasi & Persyaratan Sistem

- **Bahasa Pemrograman:** PHP >= 8.3
- **Framework:** Laravel 13 (`laravel/framework: ^13.0`)
- **Basis Data:** MySQL / MariaDB
  - Database: `perpus`
  - Host: `127.0.0.1`
  - Port: `3306` (atau `8889` sesuai konfigurasi MAMP)
  - Username: `root`
  - Password: `root`
- **Package Manager:** Composer 2.x & NPM (Node.js >= 18)
- **Frontend Assets:** Vite + Tailwind CSS

---

## Langkah Instalasi dari Nol

Ikuti panduan langkah demi langkah di bawah ini untuk menginstal dan menjalankan proyek di lingkungan lokal:

### 1. Clone Repositori
```bash
git clone https://github.com/muliainsan/perpustakaan-tutorial.git perpustakaan
cd perpustakaan
```

### 2. Salin Berkas Lingkungan (.env)
```bash
cp .env.example .env
```
Pastikan pengaturan basis data pada `.env` telah sesuai:
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=perpus
DB_USERNAME=root
DB_PASSWORD=root
DB_SOCKET=/Applications/MAMP/tmp/mysql/mysql.sock
```
*(Catatan: Sesuaikan `DB_PORT` ke `3306` dan kosongkan `DB_SOCKET` jika menggunakan instalasi MySQL standar selain MAMP).*

### 3. Install Dependensi PHP via Composer
```bash
composer install
```

### 4. Buat Application Key
```bash
php artisan key:generate
```

### 5. Siapkan Basis Data MySQL
Pastikan server MySQL telah aktif, lalu buat basis data `perpus`:
```sql
CREATE DATABASE IF NOT EXISTS perpus;
```

### 6. Jalankan Migrasi Basis Data
```bash
php artisan migrate
```

### 7. Install & Kompilasi Aset Frontend
```bash
npm install
npm run build
```

### 8. Jalankan Server Pengembangan
```bash
php artisan serve
```
Aplikasi dapat diakses melalui browser di tautan: `http://127.0.0.1:8000`

---

## Menjalankan Pengujian & Verifikasi

### Menjalankan Unit & Feature Test
```bash
php artisan test
```

### Menjalankan Skrip Gerbang Verifikasi Tag
Proyek ini dilengkapi skrip verifikasi otomatis per tahapan milestone (tag):
```bash
./scripts/verifikasi-tag.sh proyek-awal
```

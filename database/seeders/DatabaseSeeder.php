<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Pengguna (1 Admin, 1 Petugas, 5 Anggota)
        $admin = User::firstOrCreate(
            ['email' => 'admin@perpus.test'],
            [
                'name' => 'Admin Perpustakaan',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'member_code' => null,
                'phone' => '081234567890',
            ]
        );

        $petugas = User::firstOrCreate(
            ['email' => 'petugas@perpus.test'],
            [
                'name' => 'Petugas Perpustakaan',
                'password' => Hash::make('password'),
                'role' => 'petugas',
                'member_code' => null,
                'phone' => '081234567891',
            ]
        );

        $anggotaList = [];
        for ($i = 1; $i <= 5; $i++) {
            $anggotaList[] = User::firstOrCreate(
                ['email' => "anggota{$i}@perpus.test"],
                [
                    'name' => "Anggota Perpustakaan {$i}",
                    'password' => Hash::make('password'),
                    'role' => 'anggota',
                    'member_code' => sprintf('MBR-%05d', $i),
                    'phone' => sprintf('08129876543%d', $i),
                ]
            );
        }

        // 2. Seed Kategori (5 Kategori)
        $categoryNames = [
            'Pemrograman & Teknologi',
            'Sains & Matematika',
            'Sastra & Fiksi',
            'Sejarah & Budaya',
            'Bisnis & Manajemen',
        ];

        $categories = [];
        foreach ($categoryNames as $catName) {
            $categories[] = Category::firstOrCreate(['name' => $catName]);
        }

        // 3. Seed Penulis (10 Penulis)
        $authorNames = [
            'Robert C. Martin',
            'Martin Fowler',
            'Kent Beck',
            'Erich Gamma',
            'Andrew Hunt',
            'David Thomas',
            'Donald Knuth',
            'Linus Torvalds',
            'Pramoedya Ananta Toer',
            'Andrea Hirata',
        ];

        $authors = [];
        foreach ($authorNames as $authName) {
            $authors[] = Author::firstOrCreate(['name' => $authName]);
        }

        // 4. Seed Buku (40 Buku)
        $bookTitles = [
            // Kategori 1: Pemrograman (8 buku)
            'Clean Code: A Handbook of Agile Software Craftsmanship',
            'Refactoring: Improving the Design of Existing Code',
            'Test Driven Development: By Example',
            'Design Patterns: Elements of Reusable Object-Oriented Software',
            'The Pragmatic Programmer: Your Journey To Mastery',
            'The Art of Computer Programming: Fundamental Algorithms',
            'Just for Fun: The Story of an Accidental Revolutionary',
            'Domain-Driven Design: Tackling Complexity in the Heart of Software',
            // Kategori 2: Sains & Matematika (8 buku)
            'A Brief History of Time: From the Big Bang to Black Holes',
            'The Selfish Gene: 40th Anniversary edition',
            'Cosmos: A Personal Voyage',
            'What If?: Serious Scientific Answers to Absurd Hypothetical Questions',
            'Sapiens: A Brief History of Humankind',
            'The Elegant Universe: Superstrings, Hidden Dimensions, and the Quest for the Ultimate Theory',
            'Gödel, Escher, Bach: An Eternal Golden Braid',
            'Mathematical Beauty: An Introduction to Discrete Mathematics',
            // Kategori 3: Sastra & Fiksi (8 buku)
            'Bumi Manusia',
            'Anak Semua Bangsa',
            'Jejak Langkah',
            'Rumah Kaca',
            'Laskar Pelangi',
            'Sang Pemimpi',
            'Edensor',
            'Maryamah Karpov',
            // Kategori 4: Sejarah & Budaya (8 buku)
            'Sejarah Nasional Indonesia: Jaman Kuno',
            'Nusantara: A History of the East Indian Archipelago',
            'Guns, Germs, and Steel: The Fates of Human Societies',
            'Pemberontakan Petani Banten 1888',
            'Indonesia Menggugat: Pidato Pembelaan Bung Karno',
            'Catatan Seorang Demonstran',
            'Runtuhnya Hindia Belanda',
            'Asia Tenggara dalam Kurun Niaga 1450-1680',
            // Kategori 5: Bisnis & Manajemen (8 buku)
            'The Lean Startup: How Constant Innovation Creates Radically Successful Businesses',
            'Zero to One: Notes on Startups, or How to Build the Future',
            'Good to Great: Why Some Companies Make the Leap and Others Do not',
            'Atomic Habits: An Easy & Proven Way to Build Good Habits',
            'Principles: Life and Work',
            'The Hard Thing About Hard Things: Building a Business When There Are No Easy Answers',
            'Start with Why: How Great Leaders Inspire Everyone to Take Action',
            'Deep Work: Rules for Focused Success in a Distracted World',
        ];

        $books = [];
        $publishers = ['Gramedia Pustaka Utama', 'Mizan', 'O\'Reilly Media', 'Addison-Wesley', 'Prentice Hall', 'Bentang Pustaka'];

        foreach ($bookTitles as $index => $title) {
            $catIndex = intdiv($index, 8); // 8 buku per kategori
            $category = $categories[$catIndex];
            $isbn = sprintf('978-%02d-%05d-%03d-%d', $catIndex + 1, $index * 13 + 101, 100 + $index, $index % 10);

            $book = Book::firstOrCreate(
                ['isbn' => $isbn],
                [
                    'category_id' => $category->id,
                    'title'       => $title,
                    'publisher'   => $publishers[$index % count($publishers)],
                    'year'        => 2010 + ($index % 15),
                    'stock'       => 5 + ($index % 10),
                ]
            );

            // Hubungkan buku ke 1-2 penulis secara deterministik
            $author1 = $authors[$index % count($authors)];
            $author2 = $authors[($index + 3) % count($authors)];
            
            $book->authors()->syncWithoutDetaching(array_unique([$author1->id, $author2->id]));
            $books[] = $book;
        }

        // 5. Seed Sampel Peminjaman (3 Peminjaman jika belum ada)
        if (Loan::count() === 0) {
            // Peminjaman 1: Masih aktif berjalan (oleh Anggota 1)
            $loan1 = Loan::create([
                'user_id' => $anggotaList[0]->id,
                'loaned_at' => now()->subDays(3)->format('Y-m-d'),
                'due_date' => now()->addDays(4)->format('Y-m-d'),
                'returned_at' => null,
                'fine_amount' => 0,
                'fine_status' => 'none',
            ]);
            LoanItem::create(['loan_id' => $loan1->id, 'book_id' => $books[0]->id]);
            LoanItem::create(['loan_id' => $loan1->id, 'book_id' => $books[1]->id]);

            // Peminjaman 2: Sudah dikembalikan tepat waktu (oleh Anggota 2)
            $loan2 = Loan::create([
                'user_id' => $anggotaList[1]->id,
                'loaned_at' => now()->subDays(14)->format('Y-m-d'),
                'due_date' => now()->subDays(7)->format('Y-m-d'),
                'returned_at' => now()->subDays(8)->format('Y-m-d'),
                'fine_amount' => 0,
                'fine_status' => 'none',
            ]);
            LoanItem::create(['loan_id' => $loan2->id, 'book_id' => $books[8]->id]);

            // Peminjaman 3: Sudah dikembalikan terlambat dan ada denda (oleh Anggota 3)
            $loan3 = Loan::create([
                'user_id' => $anggotaList[2]->id,
                'loaned_at' => now()->subDays(20)->format('Y-m-d'),
                'due_date' => now()->subDays(13)->format('Y-m-d'),
                'returned_at' => now()->subDays(10)->format('Y-m-d'),
                'fine_amount' => 6000.00,
                'fine_status' => 'unpaid',
            ]);
            LoanItem::create(['loan_id' => $loan3->id, 'book_id' => $books[16]->id]);
            LoanItem::create(['loan_id' => $loan3->id, 'book_id' => $books[17]->id]);
        }
    }
}

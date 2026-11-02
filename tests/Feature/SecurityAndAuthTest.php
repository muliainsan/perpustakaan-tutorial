<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityAndAuthTest extends TestCase
{
    /**
     * Uji alur login sesi manual berhasil dan regenerasi sesi untuk cegah Session Fixation.
     */
    public function test_user_can_login_with_valid_credentials_and_session_is_regenerated(): void
    {
        $user = User::factory()->create([
            'email'    => 'testlogin@perpus.test',
            'password' => Hash::make('password123'),
            'role'     => 'anggota',
        ]);

        $response = $this->post(route('login'), [
            'email'    => 'testlogin@perpus.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('catalog.index'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Uji login gagal dengan kredensial salah.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'wrongcred@perpus.test',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'email'    => 'wrongcred@perpus.test',
            'password' => 'passwordsalah',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Uji proteksi Brute Force / Rate Limiter pada login web (maksimal 5 percobaan).
     */
    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        RateLimiter::clear('throttle-test@perpus.test|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email'    => 'throttle-test@perpus.test',
                'password' => 'salah' . $i,
            ]);
        }

        // Percobaan ke-6 harus ditolak dengan status HTTP 429
        $response = $this->post(route('login'), [
            'email'    => 'throttle-test@perpus.test',
            'password' => 'salahlagi',
        ]);

        $response->assertStatus(429);
        $response->assertSessionHasErrors('email');
    }

    /**
     * Uji logout aman: membatalkan sesi dan menghapus autentikasi.
     */
    public function test_user_can_logout_and_session_is_invalidated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('catalog.index'));
        $this->assertGuest();
    }

    /**
     * Uji registrasi anggota baru: pencegahan Mass Assignment role dan generate member_code otomatis.
     */
    public function test_user_registration_creates_anggota_and_ignores_role_manipulation(): void
    {
        $response = $this->post(route('register'), [
            'name'                  => 'Mahasiswa Baru',
            'email'                 => 'mhsbaru@perpus.test',
            'password'              => 'katasandi123',
            'password_confirmation' => 'katasandi123',
            'phone'                 => '081299998888',
            'role'                  => 'admin', // Percobaan manipulasi Mass Assignment
        ]);

        $response->assertRedirect(route('catalog.index'));

        $user = User::where('email', 'mhsbaru@perpus.test')->first();
        $this->assertNotNull($user);
        $this->assertEquals('anggota', $user->role); // Wajib tetap anggota
        $this->assertNotNull($user->member_code);
        $this->assertStringStartsWith('MBR-', $user->member_code);
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Uji otorisasi rute web: Tamu diarahkan ke login saat mengakses rute admin.
     */
    public function test_guest_is_redirected_to_login_when_accessing_admin(): void
    {
        $response = $this->get(route('admin.books.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Uji otorisasi rute web: Anggota ditolak 403 saat mengakses area admin/petugas.
     */
    public function test_anggota_cannot_access_admin_routes(): void
    {
        $anggota = User::factory()->create(['role' => 'anggota']);

        $response = $this->actingAs($anggota)->get(route('admin.books.index'));
        $response->assertStatus(403);

        $catResponse = $this->actingAs($anggota)->get(route('admin.categories.index'));
        $catResponse->assertStatus(403);
    }

    /**
     * Uji otorisasi rute web: Petugas dapat mengakses CRUD data master, tetapi ditolak pada manajemen user.
     */
    public function test_petugas_can_access_books_but_not_user_management(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);

        $booksResponse = $this->actingAs($petugas)->get(route('admin.books.index'));
        $booksResponse->assertStatus(200);

        $usersResponse = $this->actingAs($petugas)->get(route('admin.users.index'));
        $usersResponse->assertStatus(403);
    }

    /**
     * Uji halaman monitoring peminjaman /loans dan halaman riwayat anggota /my/loans.
     */
    public function test_admin_and_petugas_can_view_loans_monitoring_page(): void
    {
        $admin = User::firstOrCreate(['email' => 'admin@perpus.test'], [
            'name'     => 'Administrator',
            'password' => bcrypt('password'),
            'role'     => 'admin',
        ]);
        $response = $this->actingAs($admin)->get(route('loans.index'));
        $response->assertStatus(200);
        $response->assertSee('Monitoring Peminjaman');

        $petugas = User::factory()->create(['role' => 'petugas']);
        $pResponse = $this->actingAs($petugas)->get(route('loans.index'));
        $pResponse->assertStatus(200);
    }

    public function test_anggota_cannot_access_loans_monitoring_page_but_can_access_my_loans(): void
    {
        $anggota = User::factory()->create(['role' => 'anggota']);

        $response = $this->actingAs($anggota)->get(route('loans.index'));
        $response->assertStatus(403);

        $myResponse = $this->actingAs($anggota)->get(route('member.loans'));
        $myResponse->assertStatus(200);
        $myResponse->assertSee('Daftar Peminjaman Buku');
    }

    /**
     * Uji otorisasi rute web: Admin dapat mengakses dan mengubah peran pengguna.
     */
    public function test_admin_can_access_user_management_and_update_role(): void
    {
        $admin = User::firstOrCreate(['email' => 'admin@perpus.test'], [
            'name'     => 'Administrator',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        $targetUser = User::factory()->create(['role' => 'anggota']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertStatus(200);

        $patchResponse = $this->actingAs($admin)->patch(route('admin.users.update-role', $targetUser), [
            'role' => 'petugas',
        ]);

        $patchResponse->assertRedirect();
        $this->assertEquals('petugas', $targetUser->fresh()->role);
    }

    /**
     * Uji API Sanctum: Tanpa token mengembalikan status 401 JSON.
     */
    public function test_api_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/me');
        $response->assertStatus(401);

        $loansResponse = $this->getJson('/api/loans');
        $loansResponse->assertStatus(401);
    }

    /**
     * Uji endpoint API login dan issue Sanctum Bearer token.
     */
    public function test_api_login_returns_sanctum_token(): void
    {
        $user = User::factory()->create([
            'email'    => 'apilogin@perpus.test',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'apilogin@perpus.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'token',
            'token_type',
            'user' => ['id', 'name', 'email', 'role'],
        ]);

        $token = $response->json('token');

        // Gunakan token pada /api/me
        $meResponse = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/me');
        $meResponse->assertStatus(200);
        $meResponse->assertJsonPath('data.email', 'apilogin@perpus.test');

        // Logout API via token
        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)->postJson('/api/logout');
        $logoutResponse->assertStatus(200);
    }

    /**
     * Uji proteksi rate limit pada API login (maksimal 5 request/menit).
     */
    public function test_api_login_rate_limited(): void
    {
        RateLimiter::clear('api-login:apithrottle@perpus.test|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email'    => 'apithrottle@perpus.test',
                'password' => 'salah',
            ]);
        }

        $response = $this->postJson('/api/login', [
            'email'    => 'apithrottle@perpus.test',
            'password' => 'salah',
        ]);

        $response->assertStatus(429);
    }

    /**
     * Uji pencegahan IDOR (Insecure Direct Object Reference) pada peminjaman anggota via API.
     */
    public function test_idor_prevention_anggota_cannot_view_other_user_loan(): void
    {
        $userA = User::factory()->create(['role' => 'anggota']);
        $userB = User::factory()->create(['role' => 'anggota']);

        $category = Category::first();
        $book = Book::create([
            'category_id' => $category->id,
            'title'       => 'Buku IDOR Test ' . uniqid(),
            'stock'       => 5,
        ]);

        $loanUserB = Loan::borrow($userB->id, [$book->id]);

        // User A mencoba melihat detail pinjaman User B melalui GET /api/loans/{id}
        Sanctum::actingAs($userA);

        $response = $this->getJson("/api/loans/{$loanUserB->id}");
        $response->assertStatus(403);

        // User B mengakses pinjamannya sendiri harus berhasil (200)
        Sanctum::actingAs($userB);
        $selfResponse = $this->getJson("/api/loans/{$loanUserB->id}");
        $selfResponse->assertStatus(200);
    }

    /**
     * Uji otorisasi API: Anggota ditolak 403 saat mencoba mengembalikan buku atau memanipulasi master data.
     */
    public function test_anggota_forbidden_from_loan_operations_and_master_crud_api(): void
    {
        $anggota = User::factory()->create(['role' => 'anggota']);
        $category = Category::first();
        $book = Book::create([
            'category_id' => $category->id,
            'title'       => 'Buku Auth API ' . uniqid(),
            'stock'       => 5,
        ]);

        $loan = Loan::borrow($anggota->id, [$book->id]);

        Sanctum::actingAs($anggota);

        // Anggota mencoba eksekusi pengembalian buku via API
        $returnResponse = $this->postJson("/api/loans/{$loan->id}/return");
        $returnResponse->assertStatus(403);

        // Anggota mencoba menambah kategori via API
        $createCatResponse = $this->postJson('/api/categories', [
            'name' => 'Kategori Ilegal',
        ]);
        $createCatResponse->assertStatus(403);
    }

    /**
     * Uji integritas referensial API (Status 409 Conflict saat FK restrict).
     */
    public function test_api_delete_category_returns_409_when_has_associated_books(): void
    {
        $admin = User::firstOrCreate(['email' => 'admin@perpus.test'], [
            'name'     => 'Administrator',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);
        Sanctum::actingAs($admin);

        $categoryWithBooks = Category::has('books')->first();
        $this->assertNotNull($categoryWithBooks);

        $response = $this->deleteJson("/api/categories/{$categoryWithBooks->id}");

        $response->assertStatus(409);
        $response->assertJsonPath('error', 'Conflict');
        $this->assertDatabaseHas('categories', ['id' => $categoryWithBooks->id]);
    }
}


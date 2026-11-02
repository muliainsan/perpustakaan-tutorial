<?php

namespace Tests\Unit;

use App\Models\Loan;
use App\Models\LoanItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LoanFineCalculationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Skenario 1: Pengembalian tepat waktu (denda = 0, fine_status = none).
     */
    public function test_fine_is_zero_when_returned_on_time_or_earlier(): void
    {
        $loan = new Loan([
            'loaned_at'   => '2026-05-01',
            'due_date'    => '2026-05-08',
            'returned_at' => '2026-05-07',
        ]);

        $this->assertEquals(0, $loan->hitungDenda());
    }

    /**
     * Skenario 2: Pengembalian tepat pada tanggal jatuh tempo (denda = 0).
     */
    public function test_fine_is_zero_when_returned_exactly_on_due_date(): void
    {
        $loan = new Loan([
            'loaned_at'   => '2026-05-01',
            'due_date'    => '2026-05-08',
            'returned_at' => '2026-05-08',
        ]);

        $this->assertEquals(0, $loan->hitungDenda());
    }

    /**
     * Skenario 3: Keterlambatan normal (3 hari terlambat x Rp 1.000 x 1 buku = Rp 3.000).
     */
    public function test_fine_calculation_for_regular_delay(): void
    {
        $loan = Loan::factory()->create([
            'due_date'    => '2026-05-08',
            'returned_at' => '2026-05-11', // 3 hari terlambat
        ]);
        LoanItem::factory()->create(['loan_id' => $loan->id]);

        $expectedFine = 3 * config('library.fine_per_day', 1000) * 1;
        $this->assertEquals($expectedFine, $loan->hitungDenda());
    }

    /**
     * Skenario 4: Keterlambatan dengan banyak buku (2 buku x 4 hari terlambat x Rp 1.000 = Rp 8.000).
     */
    public function test_fine_calculation_multiplied_by_book_count(): void
    {
        $loan = Loan::factory()->create([
            'due_date'    => '2026-05-08',
            'returned_at' => '2026-05-12', // 4 hari terlambat
        ]);
        LoanItem::factory()->count(2)->create(['loan_id' => $loan->id]);

        $expectedFine = 4 * config('library.fine_per_day', 1000) * 2;
        $this->assertEquals($expectedFine, $loan->hitungDenda());
    }

    /**
     * Skenario 5: Plafon denda maksimal (fine_max Rp 50.000 per buku).
     * Jika terlambat 60 hari, denda per buku mencapai batas plafon Rp 50.000.
     */
    public function test_fine_calculation_caps_at_maximum_fine_limit(): void
    {
        $loan = Loan::factory()->create([
            'due_date'    => '2026-01-01',
            'returned_at' => '2026-03-02', // 60 hari terlambat (60 x 1.000 = 60.000 -> plafon 50.000)
        ]);
        LoanItem::factory()->count(2)->create(['loan_id' => $loan->id]);

        $maxFinePerBook = (float) config('library.fine_max', 50000);
        $expectedTotalFine = $maxFinePerBook * 2; // 2 buku x 50.000 = 100.000

        $this->assertEquals($expectedTotalFine, $loan->hitungDenda());
    }
}

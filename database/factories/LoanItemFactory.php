<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Loan;
use App\Models\LoanItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanItem>
 */
class LoanItemFactory extends Factory
{
    protected $model = LoanItem::class;

    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'book_id' => Book::factory(),
        ];
    }
}


<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        $loanedAt = fake()->dateTimeBetween('-30 days', '-10 days');
        $dueDate = (clone $loanedAt)->modify('+7 days');

        return [
            'user_id' => User::factory(),
            'loaned_at' => $loanedAt->format('Y-m-d'),
            'due_date' => $dueDate->format('Y-m-d'),
            'returned_at' => null,
            'fine_amount' => 0,
            'fine_status' => 'none',
        ];
    }
}


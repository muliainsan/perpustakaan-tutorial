<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'title' => fake()->sentence(3),
            'isbn' => fake()->unique()->isbn13(),
            'publisher' => fake()->company(),
            'year' => fake()->numberBetween(1990, (int) date('Y')),
            'stock' => fake()->numberBetween(1, 15),
        ];
    }
}


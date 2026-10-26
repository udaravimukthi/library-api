<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Book> */
class BookFactory extends Factory
{
    // Default values for a fake book
    public function definition(): array
    {
        $copies = fake()->numberBetween(1, 5);

        return [
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'isbn' => fake()->unique()->isbn13(),   // unique: no duplicate ISBN errors
            'total_copies' => $copies,
            'available_copies' => $copies,          // new book: all copies on the shelf
        ];
    }

    // State: a book with every copy already borrowed
    public function unavailable(): static
    {
        return $this->state(fn () => [
            'total_copies' => 1,
            'available_copies' => 0,
        ]);
    }
}

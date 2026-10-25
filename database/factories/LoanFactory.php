<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Loan> */
class LoanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),       // creates a book automatically if none is given
            'member_id' => Member::factory(),   // creates a member automatically if none is given
            'borrowed_at' => now(),
            'due_at' => now()->addDays(14),
            'returned_at' => null,              // default: still borrowed (active)
        ];
    }

    // State: due date already passed, not returned
    public function overdue(int $days = 3): static
    {
        return $this->state(fn () => [
            'borrowed_at' => now()->subDays(14 + $days),
            'due_at' => now()->subDays($days),
        ]);
    }

    // State: already returned
    public function returned(): static
    {
        return $this->state(fn () => [
            'returned_at' => now(),
        ]);
    }
}
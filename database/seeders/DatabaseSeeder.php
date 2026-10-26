<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Sample data so a reviewer can try the API straight away
    public function run(): void
    {
        // Known books with fixed ids, so the README examples work
        $cleanCode = Book::factory()->create([
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
            'isbn' => '9780132350884',
            'total_copies' => 3,
            'available_copies' => 3,
        ]);

        Book::factory()->create([
            'title' => 'Refactoring',
            'author' => 'Martin Fowler',
            'isbn' => '9780134757599',
            'total_copies' => 1,
            'available_copies' => 1,
        ]);

        Book::factory(10)->create();   // 10 random books

        $demo = Member::factory()->create([
            'name' => 'Demo Member',
            'email' => 'demo@example.com',
        ]);

        $late = Member::factory()->create([
            'name' => 'Late Member',
            'email' => 'late@example.com',
        ]);

        Member::factory(3)->create();  // 3 random members

        // Some loan history so GET /members/{id}/loans has something to show
        Loan::factory()->for($demo)->for($cleanCode)->returned()->create();

        // A member with an overdue book: they can't borrow (shows the overdue rule)
        Loan::factory()->for($late)->for($cleanCode)->overdue(5)->create();
        $cleanCode->decrement('available_copies');   // that copy is still out
    }
}

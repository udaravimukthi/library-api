<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LoanApiTest extends TestCase
{
    use RefreshDatabase;

    // Small helper so each test stays short
    private function borrow(Member $member, Book $book)
    {
        return $this->postJson('/api/loans', ['member_id' => $member->id, 'book_id' => $book->id]);
    }

    public function test_a_member_can_borrow_a_book(): void
    {
        Carbon::setTestNow('2026-10-01 10:00:00');   // freeze the clock
        $member = Member::factory()->create();
        $book = Book::factory()->create(['total_copies' => 2, 'available_copies' => 2]);

        $this->borrow($member, $book)
            ->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.due_at', Carbon::parse('2026-10-15 10:00:00')->toIso8601String());

        $this->assertSame(1, $book->fresh()->available_copies);   // fresh() = reload from DB
    }

    public function test_cannot_borrow_when_no_copies_are_available(): void
    {
        $book = Book::factory()->unavailable()->create();

        $this->borrow(Member::factory()->create(), $book)
            ->assertUnprocessable()
            ->assertJsonPath('error', 'book_unavailable');

        $this->assertDatabaseCount('loans', 0);   // nothing was created
    }

    public function test_cannot_have_more_than_three_active_loans(): void
    {
        $member = Member::factory()->create();
        Loan::factory()->count(LoanService::MAX_ACTIVE_LOANS)->for($member)->create();
        $book = Book::factory()->create();

        $this->borrow($member, $book)
            ->assertUnprocessable()
            ->assertJsonPath('error', 'loan_limit_reached');

        $this->assertSame($book->total_copies, $book->fresh()->available_copies);   // copies unchanged
    }

    public function test_returned_loans_do_not_count_towards_the_limit(): void
    {
        $member = Member::factory()->create();
        Loan::factory()->count(2)->for($member)->create();               // 2 active
        Loan::factory()->count(5)->for($member)->returned()->create();   // 5 old ones

        $this->borrow($member, Book::factory()->create())->assertCreated();
    }

    public function test_member_with_overdue_book_cannot_borrow(): void
    {
        $member = Member::factory()->create();
        Loan::factory()->for($member)->overdue()->create();

        $this->borrow($member, Book::factory()->create())
            ->assertUnprocessable()
            ->assertJsonPath('error', 'member_has_overdue_loans');
    }

    public function test_borrow_validates_ids(): void
    {
        $this->postJson('/api/loans', ['member_id' => 999, 'book_id' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['member_id', 'book_id']);
    }

    public function test_a_book_can_be_returned(): void
    {
        $book = Book::factory()->create(['total_copies' => 1, 'available_copies' => 0]);
        $loan = Loan::factory()->for($book)->create();

        $this->postJson("/api/loans/{$loan->id}/return")
            ->assertOk()
            ->assertJsonPath('data.status', 'returned');

        $this->assertNotNull($loan->fresh()->returned_at);
        $this->assertSame(1, $book->fresh()->available_copies);
    }

    public function test_a_loan_cannot_be_returned_twice(): void
    {
        $book = Book::factory()->create(['total_copies' => 1, 'available_copies' => 1]);
        $loan = Loan::factory()->for($book)->returned()->create();

        $this->postJson("/api/loans/{$loan->id}/return")
            ->assertUnprocessable()
            ->assertJsonPath('error', 'loan_already_returned');

        $this->assertSame(1, $book->fresh()->available_copies);   // NOT increased again
    }

    public function test_returning_unknown_loan_returns_404(): void
    {
        $this->postJson('/api/loans/999/return')->assertNotFound();
    }

    public function test_late_return_shows_fine(): void
    {
        Carbon::setTestNow('2026-10-20 10:00:00');   // frozen, so the day count is exact
        $book = Book::factory()->create(['available_copies' => 0, 'total_copies' => 1]);
        $loan = Loan::factory()->for($book)->overdue(days: 4)->create();

        $this->postJson("/api/loans/{$loan->id}/return")
            ->assertOk()
            ->assertJsonPath('data.days_overdue', 4)
            ->assertJsonPath('data.fine', 4 * Loan::FINE_PER_DAY);
    }
}

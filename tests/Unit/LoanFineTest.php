<?php

namespace Tests\Unit;

use App\Models\Loan;
use Illuminate\Support\Carbon;
use Tests\TestCase;

// No HTTP and nothing saved: just the fine calculation on the model
class LoanFineTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();   // unfreeze the clock after each test
        parent::tearDown();
    }

    private function loan(string $due, ?string $returned = null): Loan
    {
        return new Loan([   // new, not create(): not saved to the database
            'borrowed_at' => Carbon::parse($due)->subDays(14),
            'due_at' => Carbon::parse($due),
            'returned_at' => $returned ? Carbon::parse($returned) : null,
        ]);
    }

    public function test_no_fine_when_returned_on_time(): void
    {
        $loan = $this->loan('2026-10-15 10:00', returned: '2026-10-15 09:00');

        $this->assertSame(0, $loan->fine());
        $this->assertFalse($loan->isOverdue());
    }

    public function test_fine_is_charged_per_started_day(): void
    {
        // 2 days and 1 hour late counts as 3 started days
        $loan = $this->loan('2026-10-15 10:00', returned: '2026-10-17 11:00');

        $this->assertSame(3, $loan->daysOverdue());
        $this->assertSame(3 * Loan::FINE_PER_DAY, $loan->fine());
    }

    public function test_unreturned_loan_is_overdue_and_fined_up_to_now(): void
    {
        Carbon::setTestNow('2026-10-20 10:00');
        $loan = $this->loan('2026-10-15 10:00');

        $this->assertTrue($loan->isOverdue());
        $this->assertSame(5 * Loan::FINE_PER_DAY, $loan->fine());
    }
}

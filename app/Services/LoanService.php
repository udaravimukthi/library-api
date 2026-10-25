<?php

namespace App\Services;

use App\Exceptions\BookUnavailableException;
use App\Exceptions\LoanAlreadyReturnedException;
use App\Exceptions\LoanLimitReachedException;
use App\Exceptions\MemberHasOverdueLoansException;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

class LoanService
{
    public const MAX_ACTIVE_LOANS = 3;

    public const LOAN_PERIOD_DAYS = 14;

    public function borrow(int $memberId, int $bookId): Loan
    {
        return DB::transaction(function () use ($memberId, $bookId) {
            // 1. Lock both rows. Another request touching them must wait until we finish.
            $member = Member::lockForUpdate()->findOrFail($memberId);
            $book = Book::lockForUpdate()->findOrFail($bookId);

            // 2. Check the rules
            if ($member->hasOverdueLoans()) {
                throw new MemberHasOverdueLoansException;
            }

            if ($member->activeLoansCount() >= self::MAX_ACTIVE_LOANS) {
                throw new LoanLimitReachedException;
            }

            if (! $book->isAvailable()) {
                throw new BookUnavailableException;
            }

            // 3. All rules passed: take one copy and create the loan
            $book->decrement('available_copies');

            return Loan::create([
                'book_id' => $book->id,
                'member_id' => $member->id,
                'borrowed_at' => now(),
                'due_at' => now()->addDays(self::LOAN_PERIOD_DAYS),
            ]);
        });
    }

    public function return(Loan $loan): Loan
    {
        return DB::transaction(function () use ($loan) {
            // Re-read with a lock: two "return" clicks at once must not add 2 copies back
            $loan = Loan::lockForUpdate()->findOrFail($loan->id);

            if ($loan->isReturned()) {
                throw new LoanAlreadyReturnedException;
            }

            $loan->update(['returned_at' => now()]);
            Book::whereKey($loan->book_id)->increment('available_copies');

            return $loan;
        });
    }
}
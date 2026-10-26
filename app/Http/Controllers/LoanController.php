<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanRequest;
use App\Http\Resources\LoanResource;
use App\Models\Loan;
use App\Services\LoanService;

class LoanController extends Controller
{
    // Laravel's service container creates LoanService and passes it in
    public function __construct(private readonly LoanService $loans) {}

    // POST /api/loans
    public function store(StoreLoanRequest $request): LoanResource
    {
        $loan = $this->loans->borrow(
            memberId: $request->integer('member_id'),
            bookId: $request->integer('book_id'),
        );

        return new LoanResource($loan->load('book'));   // 201 (new model)
    }

    // POST /api/loans/{loan}/return
    public function returnBook(Loan $loan): LoanResource
    {
        return new LoanResource($this->loans->return($loan)->load('book'));   // 200
    }
}

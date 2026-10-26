<?php

namespace App\Exceptions;

class LoanLimitReachedException extends LoanRuleException
{
    protected $message = 'Member already has the maximum number of active loans.';

    public function errorCode(): string
    {
        return 'loan_limit_reached';
    }
}

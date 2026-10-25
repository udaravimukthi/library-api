<?php

namespace App\Exceptions;

class LoanAlreadyReturnedException extends LoanRuleException
{
    protected $message = 'This loan has already been returned.';

    public function errorCode(): string
    {
        return 'loan_already_returned';
    }
}
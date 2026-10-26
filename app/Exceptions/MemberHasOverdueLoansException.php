<?php

namespace App\Exceptions;

class MemberHasOverdueLoansException extends LoanRuleException
{
    protected $message = 'Member has overdue books and cannot borrow until they are returned.';

    public function errorCode(): string
    {
        return 'member_has_overdue_loans';
    }
}

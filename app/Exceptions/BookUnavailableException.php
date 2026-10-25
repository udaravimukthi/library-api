<?php

namespace App\Exceptions;

class BookUnavailableException extends LoanRuleException
{
    protected $message = 'This book has no available copies.';

    public function errorCode(): string
    {
        return 'book_unavailable';
    }
}
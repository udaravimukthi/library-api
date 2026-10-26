<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

abstract class LoanRuleException extends Exception
{
    abstract public function errorCode(): string;

    // Laravel calls this automatically when the exception isn't caught
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => $this->errorCode(),
        ], 422);
    }
}

<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientFundsException extends RuntimeException
{
    public static function forAmount(string $amount, string $currency): self
    {
        return new self("Insufficient wallet balance to cover {$amount} {$currency}.");
    }
}

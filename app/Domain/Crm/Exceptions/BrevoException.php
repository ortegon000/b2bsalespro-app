<?php

namespace App\Domain\Crm\Exceptions;

use RuntimeException;

class BrevoException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $retryable = false)
    {
        parent::__construct($message);
    }
}

<?php

namespace App\Exceptions;

use Exception;

/**
 * Base service exception class
 */
class ServiceException extends Exception
{
    protected string $errorCode;
    protected array $context;

    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null, string $errorCode = '', array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode ?: 'SERVICE_ERROR';
        $this->context = $context;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}

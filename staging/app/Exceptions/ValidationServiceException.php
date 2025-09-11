<?php

namespace App\Exceptions;

/**
 * Exception for validation-related service errors
 */
class ValidationServiceException extends ServiceException
{
    protected array $validationErrors;

    public function __construct(string $message = '', array $validationErrors = [], int $code = 0, ?\Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, 'VALIDATION_SERVICE_ERROR', $context);
        $this->validationErrors = $validationErrors;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}

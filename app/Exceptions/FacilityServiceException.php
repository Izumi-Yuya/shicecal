<?php

namespace App\Exceptions;

/**
 * Exception for facility-related service errors
 */
class FacilityServiceException extends ServiceException
{
    public function __construct(string $message = '', int $code = 0, ?\Exception $previous = null, string $errorCode = 'FACILITY_SERVICE_ERROR', array $context = [])
    {
        parent::__construct($message, $code, $previous, $errorCode, $context);
    }
}

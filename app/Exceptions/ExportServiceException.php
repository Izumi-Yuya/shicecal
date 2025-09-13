<?php

namespace App\Exceptions;

/**
 * Exception for export-related service errors
 */
class ExportServiceException extends ServiceException
{
    public function __construct(string $message = '', int $code = 0, ?\Exception $previous = null, string $errorCode = 'EXPORT_SERVICE_ERROR', array $context = [])
    {
        parent::__construct($message, $code, $previous, $errorCode, $context);
    }
}

<?php

namespace App\Exceptions;

/**
 * Exception for lifeline equipment service errors
 */
class LifelineEquipmentServiceException extends ServiceException
{
    public function __construct(string $message = '', int $code = 0, ?\Exception $previous = null, string $errorCode = 'LIFELINE_EQUIPMENT_SERVICE_ERROR', array $context = [])
    {
        parent::__construct($message, $code, $previous, $errorCode, $context);
    }
}
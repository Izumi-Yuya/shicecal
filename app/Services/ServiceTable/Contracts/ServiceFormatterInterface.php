<?php

namespace App\Services\ServiceTable\Contracts;

/**
 * Interface for service formatting strategies
 */
interface ServiceFormatterInterface
{
    /**
     * Format service for display
     */
    public function format($service): array;

    /**
     * Check if this formatter can handle the service
     */
    public function canFormat($service): bool;
}

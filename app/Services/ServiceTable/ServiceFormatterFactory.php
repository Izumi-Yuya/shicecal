<?php

namespace App\Services\ServiceTable;

use App\Services\ServiceTable\Contracts\ServiceFormatterInterface;
use App\Services\ServiceTable\Formatters\StandardServiceFormatter;

/**
 * Factory for creating service formatters
 */
class ServiceFormatterFactory
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Create appropriate formatter for service
     */
    public function createFormatter($service): ServiceFormatterInterface
    {
        // For now, we only have standard formatter
        // In the future, we could add specialized formatters for different service types
        return new StandardServiceFormatter($this->config);
    }

    /**
     * Get all available formatters
     */
    public function getAvailableFormatters(): array
    {
        return [
            'standard' => StandardServiceFormatter::class,
            // Future formatters can be added here
            // 'premium' => PremiumServiceFormatter::class,
            // 'legacy' => LegacyServiceFormatter::class,
        ];
    }
}
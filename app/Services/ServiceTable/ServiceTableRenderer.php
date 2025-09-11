<?php

namespace App\Services\ServiceTable;

use App\Services\ServiceTable\Contracts\ServiceFormatterInterface;
use App\Services\ServiceTable\Contracts\ServiceTableConfigInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Handles service table rendering logic
 * Single responsibility: Transform service data for display
 */
class ServiceTableRenderer
{
    public function __construct(
        private ServiceTableConfigInterface $config,
        private ServiceFormatterInterface $formatter
    ) {}

    /**
     * Prepare services data for table display
     *
     * @param  Collection  $services  Collection of service objects
     * @return array Formatted display data
     *
     * @throws InvalidArgumentException When services collection is invalid
     */
    public function prepareServicesForDisplay(Collection $services): array
    {
        try {
            $this->validateServicesCollection($services);

            $maxServices = $this->config->getMaxServices();
            $showEmptyRows = $this->config->shouldShowEmptyRows();

            if ($services->isEmpty()) {
                return $this->createEmptyDisplayData($maxServices, $showEmptyRows);
            }

            return $this->createDisplayData($services, $maxServices, $showEmptyRows);
        } catch (\Exception $e) {
            Log::error('Service table rendering failed', [
                'error' => $e->getMessage(),
                'services_count' => $services->count(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return safe fallback data
            return $this->createEmptyDisplayData(1, false);
        }
    }

    /**
     * Format service for display
     *
     * @param  mixed  $service  Service object or null
     * @return array Formatted service data
     */
    public function formatService($service): array
    {
        if (! $this->hasValidServiceData($service)) {
            return $this->formatter->formatEmpty();
        }

        try {
            return $this->formatter->format($service);
        } catch (\Exception $e) {
            Log::warning('Service formatting failed', [
                'service_id' => $service->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return $this->formatter->formatEmpty();
        }
    }

    /**
     * Check if service has valid data
     *
     * @param  mixed  $service  Service object to validate
     * @return bool True if service has valid data
     */
    public function hasValidServiceData($service): bool
    {
        return $service !== null &&
               is_object($service) &&
               property_exists($service, 'service_type') &&
               ! empty(trim($service->service_type ?? ''));
    }

    /**
     * Validate services collection
     *
     * @throws InvalidArgumentException
     */
    private function validateServicesCollection(Collection $services): void
    {
        if (! $services instanceof Collection) {
            throw new InvalidArgumentException('Services must be a Collection instance');
        }

        // Validate each service in the collection
        $services->each(function ($service, $index) {
            if ($service !== null && ! is_object($service)) {
                throw new InvalidArgumentException("Service at index {$index} must be an object or null");
            }
        });
    }

    private function createEmptyDisplayData(int $maxServices, bool $showEmptyRows): array
    {
        return [
            'services' => collect([null]),
            'hasData' => false,
            'templateRowsNeeded' => $showEmptyRows ? max(0, $maxServices - 1) : 0,
        ];
    }

    private function createDisplayData(Collection $services, int $maxServices, bool $showEmptyRows): array
    {
        $displayServices = $services->take($maxServices);
        $templateRowsNeeded = $showEmptyRows ? max(0, $maxServices - $displayServices->count()) : 0;

        return [
            'services' => $displayServices,
            'hasData' => true,
            'templateRowsNeeded' => $templateRowsNeeded,
        ];
    }
}

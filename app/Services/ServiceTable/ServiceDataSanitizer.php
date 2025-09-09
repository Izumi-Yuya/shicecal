<?php

namespace App\Services\ServiceTable;

use Illuminate\Support\Str;

/**
 * Service for sanitizing and validating service table data
 * Prevents XSS and ensures data integrity
 */
class ServiceDataSanitizer
{
    /**
     * Sanitize service type for display
     */
    public function sanitizeServiceType(?string $serviceType): string
    {
        if (empty($serviceType)) {
            return '';
        }
        
        // Remove potentially dangerous characters
        $sanitized = strip_tags($serviceType);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        
        // Limit length to prevent layout issues
        $maxLength = config('service-table.validation.max_service_name_length', 100);
        $sanitized = Str::limit($sanitized, $maxLength);
        
        return $sanitized;
    }
    
    /**
     * Validate and format date for display
     */
    public function sanitizeDate($date): string
    {
        if (!$date) {
            return '';
        }
        
        try {
            // Ensure it's a Carbon instance
            if (!$date instanceof \Carbon\Carbon) {
                $date = \Carbon\Carbon::parse($date);
            }
            
            // Use configured date format
            $format = config('service-table.display.date_format', 'Y年m月d日');
            return $date->format($format);
            
        } catch (\Exception $e) {
            // Log the error but don't expose it to the user
            \Log::warning('Invalid date in service table', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            
            return '';
        }
    }
    
    /**
     * Sanitize all service data for display
     */
    public function sanitizeServiceData(object $service): object
    {
        return (object) [
            'service_type' => $this->sanitizeServiceType($service->service_type ?? null),
            'renewal_start_date' => $service->renewal_start_date ?? null,
            'renewal_end_date' => $service->renewal_end_date ?? null,
            'formatted_start_date' => $this->sanitizeDate($service->renewal_start_date ?? null),
            'formatted_end_date' => $this->sanitizeDate($service->renewal_end_date ?? null),
        ];
    }
    
    /**
     * Validate service data structure
     */
    public function validateServiceStructure(object $service): bool
    {
        $requiredFields = config('service-table.validation.required_fields', ['service_type']);
        
        foreach ($requiredFields as $field) {
            if (!property_exists($service, $field)) {
                return false;
            }
        }
        
        return true;
    }
}
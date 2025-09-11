<?php

use App\Helpers\FacilityFormHelper;
use App\Models\Facility;

if (! function_exists('facility_breadcrumbs')) {
    /**
     * Generate breadcrumbs for facility forms
     */
    function facility_breadcrumbs(string $currentPage, ?Facility $facility = null, array $additionalCrumbs = []): array
    {
        return FacilityFormHelper::generateBreadcrumbs($currentPage, $facility, $additionalCrumbs);
    }
}

if (! function_exists('section_icon')) {
    /**
     * Get icon class for a section type
     */
    function section_icon(string $sectionType): string
    {
        return FacilityFormHelper::getSectionIcon($sectionType);
    }
}

if (! function_exists('section_color')) {
    /**
     * Get color class for a section type
     */
    function section_color(string $sectionType): string
    {
        return FacilityFormHelper::getSectionColor($sectionType);
    }
}

if (! function_exists('section_config')) {
    /**
     * Generate form section configuration
     */
    function section_config(string $sectionType, ?string $customTitle = null, ?string $customIcon = null, ?string $customColor = null): array
    {
        return FacilityFormHelper::getSectionConfig($sectionType, $customTitle, $customIcon, $customColor);
    }
}

if (! function_exists('facility_form_config')) {
    /**
     * Get facility form configuration value
     *
     * @param  mixed  $default
     * @return mixed
     */
    function facility_form_config(string $key, $default = null)
    {
        return config("facility-form.{$key}", $default);
    }
}

if (! function_exists('form_validation_message')) {
    /**
     * Get form validation message
     */
    function form_validation_message(string $key, ?string $default = null): string
    {
        return config("facility-form.validation_messages.{$key}", $default ?? $key);
    }
}

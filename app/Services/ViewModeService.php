<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

/**
 * Service for managing user view mode preferences
 * Implements the Service Layer pattern for better separation of concerns
 */
class ViewModeService
{
    const VIEW_PREFERENCE_KEY = 'facility_basic_info_view_mode';
    const DEFAULT_VIEW_MODE = 'card';
    
    const VIEW_MODES = [
        'card' => 'カード形式',
        'table' => 'テーブル形式'
    ];

    /**
     * Set user's view mode preference
     */
    public function setViewMode(string $viewMode): void
    {
        // Sanitize input
        $viewMode = trim(strtolower($viewMode));
        
        if (!$this->isValidViewMode($viewMode)) {
            throw new \InvalidArgumentException("Invalid view mode: {$viewMode}");
        }

        // Additional security check for session hijacking
        if (!$this->isSessionSecure()) {
            throw new \RuntimeException("Insecure session detected");
        }

        Session::put(self::VIEW_PREFERENCE_KEY, $viewMode);
    }

    /**
     * Get current view mode preference
     */
    public function getViewMode(): string
    {
        return Session::get(self::VIEW_PREFERENCE_KEY, self::DEFAULT_VIEW_MODE);
    }

    /**
     * Check if view mode is valid
     */
    public function isValidViewMode(string $viewMode): bool
    {
        return array_key_exists($viewMode, self::VIEW_MODES);
    }

    /**
     * Get all available view modes
     */
    public function getAvailableViewModes(): array
    {
        return self::VIEW_MODES;
    }

    /**
     * Get display name for view mode
     */
    public function getViewModeDisplayName(string $viewMode): ?string
    {
        return self::VIEW_MODES[$viewMode] ?? null;
    }

    /**
     * Check if session is secure
     */
    private function isSessionSecure(): bool
    {
        // Check if session has required security attributes
        return Session::isStarted() && 
               !empty(Session::getId()) && 
               strlen(Session::getId()) >= 32; // Minimum session ID length
    }

    /**
     * Sanitize view mode input
     */
    public function sanitizeViewMode(string $viewMode): string
    {
        return trim(strtolower(strip_tags($viewMode)));
    }
}
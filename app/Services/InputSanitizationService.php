<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Service for consistent input sanitization across the application
 */
class InputSanitizationService
{
    /**
     * Sanitize string input
     */
    public static function sanitizeString(?string $input, int $maxLength = 255): ?string
    {
        if ($input === null) {
            return null;
        }

        // Remove null bytes and control characters
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        // Trim whitespace
        $sanitized = trim($sanitized);
        
        // Limit length
        if (mb_strlen($sanitized, 'UTF-8') > $maxLength) {
            $sanitized = mb_substr($sanitized, 0, $maxLength, 'UTF-8');
        }
        
        return $sanitized === '' ? null : $sanitized;
    }

    /**
     * Sanitize email input
     */
    public static function sanitizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $sanitized = self::sanitizeString($email, 320); // RFC 5321 limit
        
        // Basic email format validation
        if ($sanitized && !filter_var($sanitized, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        
        return $sanitized;
    }

    /**
     * Sanitize phone number input
     */
    public static function sanitizePhoneNumber(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        // Remove all non-digit characters except + and -
        $sanitized = preg_replace('/[^\d\+\-\(\)\s]/', '', $phone);
        $sanitized = trim($sanitized);
        
        return $sanitized === '' ? null : $sanitized;
    }

    /**
     * Sanitize URL input
     */
    public static function sanitizeUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $sanitized = self::sanitizeString($url, 2048);
        
        // Add protocol if missing
        if ($sanitized && !preg_match('/^https?:\/\//', $sanitized)) {
            $sanitized = 'https://' . $sanitized;
        }
        
        // Validate URL format
        if ($sanitized && !filter_var($sanitized, FILTER_VALIDATE_URL)) {
            return null;
        }
        
        return $sanitized;
    }

    /**
     * Sanitize filename for safe storage
     */
    public static function sanitizeFilename(?string $filename): ?string
    {
        if ($filename === null) {
            return null;
        }

        // Remove path separators and dangerous characters
        $sanitized = preg_replace('/[\/\\\\:*?"<>|]/', '_', $filename);
        
        // Remove null bytes and control characters
        $sanitized = preg_replace('/[\x00-\x1F\x7F]/', '', $sanitized);
        
        // Prevent directory traversal
        $sanitized = str_replace(['../', '.\\', '..\\'], '', $sanitized);
        
        // Trim and limit length
        $sanitized = trim($sanitized);
        if (mb_strlen($sanitized, 'UTF-8') > 255) {
            $extension = pathinfo($sanitized, PATHINFO_EXTENSION);
            $name = pathinfo($sanitized, PATHINFO_FILENAME);
            $maxNameLength = 250 - mb_strlen($extension, 'UTF-8') - 1;
            $sanitized = mb_substr($name, 0, $maxNameLength, 'UTF-8') . '.' . $extension;
        }
        
        return $sanitized === '' ? null : $sanitized;
    }

    /**
     * Sanitize numeric input
     */
    public static function sanitizeNumeric($input, bool $allowFloat = false): ?float
    {
        if ($input === null || $input === '') {
            return null;
        }

        if (is_numeric($input)) {
            return $allowFloat ? (float) $input : (int) $input;
        }

        // Try to extract numeric value from string
        $cleaned = preg_replace('/[^\d\.\-]/', '', (string) $input);
        
        if ($cleaned === '' || !is_numeric($cleaned)) {
            return null;
        }

        return $allowFloat ? (float) $cleaned : (int) $cleaned;
    }

    /**
     * Sanitize array of strings
     */
    public static function sanitizeStringArray(?array $input, int $maxLength = 255): array
    {
        if ($input === null) {
            return [];
        }

        return array_filter(
            array_map(
                fn($item) => self::sanitizeString($item, $maxLength),
                $input
            ),
            fn($item) => $item !== null
        );
    }

    /**
     * Sanitize HTML content (for rich text fields)
     */
    public static function sanitizeHtml(?string $html, array $allowedTags = ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li']): ?string
    {
        if ($html === null) {
            return null;
        }

        // Remove script tags and dangerous attributes
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/on\w+="[^"]*"/i', '', $html);
        $html = preg_replace('/javascript:/i', '', $html);
        
        // Strip tags except allowed ones
        $allowedTagsString = '<' . implode('><', $allowedTags) . '>';
        $sanitized = strip_tags($html, $allowedTagsString);
        
        return trim($sanitized) === '' ? null : $sanitized;
    }

    /**
     * Sanitize postal code (Japanese format)
     */
    public static function sanitizePostalCode(?string $postalCode): ?string
    {
        if ($postalCode === null) {
            return null;
        }

        // Remove all non-digit characters except hyphen
        $sanitized = preg_replace('/[^\d\-]/', '', $postalCode);
        
        // Format as XXX-XXXX if it's 7 digits
        if (preg_match('/^(\d{3})(\d{4})$/', $sanitized, $matches)) {
            $sanitized = $matches[1] . '-' . $matches[2];
        }
        
        // Validate Japanese postal code format
        if (!preg_match('/^\d{3}-\d{4}$/', $sanitized)) {
            return null;
        }
        
        return $sanitized;
    }
}
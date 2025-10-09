<?php

namespace App\Services\Security;

use Illuminate\Support\Str;

/**
 * File Sanitizer Service
 * Provides secure file name and path sanitization
 */
class FileSanitizer
{
    /**
     * Dangerous file extensions that should never be allowed
     */
    private const DANGEROUS_EXTENSIONS = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps',
        'js', 'html', 'htm', 'xhtml',
        'exe', 'bat', 'cmd', 'com', 'pif', 'scr',
        'vbs', 'vbe', 'jse', 'ws', 'wsf', 'wsc', 'wsh',
        'ps1', 'ps1xml', 'ps2', 'ps2xml', 'psc1', 'psc2',
        'msh', 'msh1', 'msh2', 'mshxml', 'msh1xml', 'msh2xml'
    ];

    /**
     * Characters that should be removed from file names
     */
    private const DANGEROUS_CHARS = [
        '<', '>', ':', '"', '|', '?', '*', '\\', '/',
        "\0", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07",
        "\x08", "\x09", "\x0a", "\x0b", "\x0c", "\x0d", "\x0e", "\x0f",
        "\x10", "\x11", "\x12", "\x13", "\x14", "\x15", "\x16", "\x17",
        "\x18", "\x19", "\x1a", "\x1b", "\x1c", "\x1d", "\x1e", "\x1f"
    ];

    /**
     * Reserved Windows file names
     */
    private const RESERVED_NAMES = [
        'CON', 'PRN', 'AUX', 'NUL',
        'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9',
        'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'
    ];

    /**
     * Sanitize filename for safe storage and download
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove null bytes and control characters
        $sanitized = $this->removeControlCharacters($filename);
        
        // Remove dangerous characters
        $sanitized = $this->removeDangerousCharacters($sanitized);
        
        // Prevent directory traversal
        $sanitized = $this->preventDirectoryTraversal($sanitized);
        
        // Handle reserved names
        $sanitized = $this->handleReservedNames($sanitized);
        
        // Limit length
        $sanitized = $this->limitLength($sanitized);
        
        // Ensure filename is not empty after sanitization
        if (empty(trim($sanitized))) {
            $sanitized = $this->generateFallbackName();
        }
        
        // Validate extension
        $sanitized = $this->validateExtension($sanitized);
        
        return $sanitized;
    }

    /**
     * Remove control characters and null bytes
     */
    private function removeControlCharacters(string $filename): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/', '', $filename);
    }

    /**
     * Remove dangerous characters
     */
    private function removeDangerousCharacters(string $filename): string
    {
        return str_replace(self::DANGEROUS_CHARS, '_', $filename);
    }

    /**
     * Prevent directory traversal attacks
     */
    private function preventDirectoryTraversal(string $filename): string
    {
        // Remove directory traversal patterns
        $patterns = ['../', '.\\', '..\\', '../', '..\\\\'];
        return str_replace($patterns, '', $filename);
    }

    /**
     * Handle Windows reserved names
     */
    private function handleReservedNames(string $filename): string
    {
        $nameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);
        
        if (in_array(strtoupper($nameWithoutExtension), self::RESERVED_NAMES)) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            return 'file_' . $nameWithoutExtension . ($extension ? '.' . $extension : '');
        }
        
        return $filename;
    }

    /**
     * Limit filename length to prevent filesystem issues
     */
    private function limitLength(string $filename): string
    {
        $maxLength = 255;
        
        if (mb_strlen($filename, 'UTF-8') <= $maxLength) {
            return $filename;
        }
        
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        
        $maxNameLength = $maxLength - mb_strlen($extension, 'UTF-8') - 1;
        $truncatedName = mb_substr($name, 0, $maxNameLength, 'UTF-8');
        
        return $truncatedName . ($extension ? '.' . $extension : '');
    }

    /**
     * Generate fallback name if sanitization results in empty string
     */
    private function generateFallbackName(): string
    {
        return 'document_' . time() . '.pdf';
    }

    /**
     * Validate file extension against dangerous extensions
     */
    private function validateExtension(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($extension, self::DANGEROUS_EXTENSIONS)) {
            // Replace dangerous extension with .txt
            $nameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);
            return $nameWithoutExtension . '.txt';
        }
        
        return $filename;
    }

    /**
     * Sanitize file path to prevent path traversal
     */
    public function sanitizePath(string $path): string
    {
        // Normalize path separators
        $path = str_replace('\\', '/', $path);
        
        // Remove multiple consecutive slashes
        $path = preg_replace('/\/+/', '/', $path);
        
        // Remove leading slash
        $path = ltrim($path, '/');
        
        // Split path into segments and sanitize each
        $segments = explode('/', $path);
        $sanitizedSegments = [];
        
        foreach ($segments as $segment) {
            // Skip empty segments and directory traversal attempts
            if (empty($segment) || $segment === '.' || $segment === '..') {
                continue;
            }
            
            // Sanitize segment as filename
            $sanitizedSegment = $this->sanitizeFilename($segment);
            if (!empty($sanitizedSegment)) {
                $sanitizedSegments[] = $sanitizedSegment;
            }
        }
        
        return implode('/', $sanitizedSegments);
    }

    /**
     * Check if file extension is allowed
     */
    public function isAllowedExtension(string $filename, array $allowedExtensions = []): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Check against dangerous extensions
        if (in_array($extension, self::DANGEROUS_EXTENSIONS)) {
            return false;
        }
        
        // Check against allowed extensions if provided
        if (!empty($allowedExtensions)) {
            return in_array($extension, array_map('strtolower', $allowedExtensions));
        }
        
        return true;
    }

    /**
     * Generate secure unique filename
     */
    public function generateUniqueFilename(string $originalName, string $directory = ''): string
    {
        $sanitizedName = $this->sanitizeFilename($originalName);
        $extension = pathinfo($sanitizedName, PATHINFO_EXTENSION);
        $nameWithoutExtension = pathinfo($sanitizedName, PATHINFO_FILENAME);
        
        // Add timestamp and random string for uniqueness
        $timestamp = time();
        $randomString = Str::random(8);
        
        $uniqueName = $nameWithoutExtension . '_' . $timestamp . '_' . $randomString;
        
        if ($extension) {
            $uniqueName .= '.' . $extension;
        }
        
        return $uniqueName;
    }
}
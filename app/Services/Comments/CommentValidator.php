<?php

namespace App\Services\Comments;

/**
 * Validator for comment content and sections
 * Implements Single Responsibility Principle
 */
class CommentValidator
{
    /**
     * Validate section name
     */
    public function validateSection(string $section): void
    {
        $validSections = array_keys(config('comments.sections', []));
        
        if (!in_array($section, $validSections)) {
            throw new \InvalidArgumentException("Invalid comment section: {$section}");
        }
    }
    
    /**
     * Validate comment content
     */
    public function validateContent(string $content): void
    {
        $minLength = config('comments.validation.min_length', 1);
        $maxLength = config('comments.validation.max_length', 500);
        
        // Trim and sanitize content
        $trimmedContent = trim($content);
        
        // Check minimum length
        if (strlen($trimmedContent) < $minLength) {
            throw new \InvalidArgumentException("Comment content too short");
        }
        
        // Check maximum length
        if (strlen($content) > $maxLength) {
            throw new \InvalidArgumentException("Comment content too long");
        }
        
        // Check for malicious content patterns
        $this->validateContentSecurity($trimmedContent);
    }
    
    /**
     * Validate content for security issues
     */
    private function validateContentSecurity(string $content): void
    {
        $dangerousPatterns = $this->getDangerousPatterns();
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new \InvalidArgumentException("Invalid content detected");
            }
        }
        
        // Check for control characters and potential injection
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $content)) {
            throw new \InvalidArgumentException("Invalid characters in content");
        }
        
        // Additional check for SQL injection patterns
        $sqlPatterns = $this->getSqlInjectionPatterns();
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new \InvalidArgumentException("Invalid content detected");
            }
        }
    }
    
    /**
     * Get dangerous content patterns
     */
    private function getDangerousPatterns(): array
    {
        return [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', // Script tags
            '/<(iframe|object|embed|form|input|button)\b/i', // Dangerous HTML elements
            '/javascript:/i', // JavaScript protocol
            '/on\w+\s*=/i', // Event handlers (onclick, onload, etc.)
            '/data:text\/html/i', // Data URLs with HTML
            '/vbscript:/i', // VBScript protocol
        ];
    }
    
    /**
     * Get SQL injection patterns
     */
    private function getSqlInjectionPatterns(): array
    {
        return [
            '/(\bunion\b|\bselect\b|\binsert\b|\bupdate\b|\bdelete\b|\bdrop\b)/i',
            '/(\bor\b|\band\b)\s+\d+\s*=\s*\d+/i', // OR 1=1, AND 1=1
        ];
    }
}
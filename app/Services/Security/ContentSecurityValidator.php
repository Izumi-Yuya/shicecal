<?php

namespace App\Services\Security;

use InvalidArgumentException;
use Illuminate\Support\Facades\Log;

/**
 * Content Security Validator
 * 
 * Provides comprehensive content validation to prevent XSS, injection attacks,
 * and other security vulnerabilities in user-generated content.
 */
class ContentSecurityValidator
{
    private array $config;
    
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }
    
    /**
     * Validate content for security issues
     * 
     * @param string $content Content to validate
     * @param array $options Additional validation options
     * @throws InvalidArgumentException When content fails validation
     */
    public function validate(string $content, array $options = []): void
    {
        $options = array_merge($this->config, $options);
        
        // Basic length validation
        $this->validateLength($content, $options);
        
        // Character validation
        $this->validateCharacters($content, $options);
        
        // Pattern-based security validation
        $this->validateSecurityPatterns($content, $options);
        
        // Additional custom validations
        if (isset($options['custom_validators'])) {
            $this->runCustomValidators($content, $options['custom_validators']);
        }
        
        Log::debug('Content security validation passed', [
            'content_length' => strlen($content),
            'validation_options' => array_keys($options)
        ]);
    }
    
    /**
     * Sanitize content while preserving safe formatting
     * 
     * @param string $content Content to sanitize
     * @param array $options Sanitization options
     * @return string Sanitized content
     */
    public function sanitize(string $content, array $options = []): string
    {
        $options = array_merge($this->config, $options);
        
        // Remove null bytes and control characters
        $content = $this->removeControlCharacters($content);
        
        // Normalize whitespace
        if ($options['normalize_whitespace'] ?? true) {
            $content = $this->normalizeWhitespace($content);
        }
        
        // Remove dangerous patterns
        $content = $this->removeDangerousPatterns($content, $options);
        
        // Apply additional sanitization
        if ($options['html_purify'] ?? false) {
            $content = $this->purifyHtml($content);
        }
        
        return trim($content);
    }
    
    /**
     * Validate content length
     */
    private function validateLength(string $content, array $options): void
    {
        $minLength = $options['min_length'] ?? 1;
        $maxLength = $options['max_length'] ?? 500;
        
        $trimmedLength = strlen(trim($content));
        
        if ($trimmedLength < $minLength) {
            throw new InvalidArgumentException("Content too short (minimum: {$minLength} characters)");
        }
        
        if (strlen($content) > $maxLength) {
            throw new InvalidArgumentException("Content too long (maximum: {$maxLength} characters)");
        }
    }
    
    /**
     * Validate character content
     */
    private function validateCharacters(string $content, array $options): void
    {
        // Check for control characters
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $content)) {
            throw new InvalidArgumentException("Invalid control characters detected");
        }
        
        // Check for suspicious Unicode characters
        if ($options['check_unicode'] ?? true) {
            $this->validateUnicodeCharacters($content);
        }
        
        // Check encoding
        if (!mb_check_encoding($content, 'UTF-8')) {
            throw new InvalidArgumentException("Invalid character encoding");
        }
    }
    
    /**
     * Validate against security patterns
     */
    private function validateSecurityPatterns(string $content, array $options): void
    {
        $patterns = $this->getSecurityPatterns($options);
        
        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $content)) {
                Log::warning('Security pattern detected in content', [
                    'pattern_name' => $name,
                    'pattern' => $pattern,
                    'content_preview' => substr($content, 0, 100)
                ]);
                
                throw new InvalidArgumentException("Potentially dangerous content detected: {$name}");
            }
        }
    }
    
    /**
     * Get security validation patterns
     */
    private function getSecurityPatterns(array $options): array
    {
        $patterns = [
            'script_tags' => '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            'dangerous_html' => '/<(iframe|object|embed|form|input|button|meta|link)\b/i',
            'javascript_protocol' => '/javascript:/i',
            'vbscript_protocol' => '/vbscript:/i',
            'data_urls' => '/data:(?:text\/html|application\/)/i',
            'event_handlers' => '/on\w+\s*=/i',
            'style_expressions' => '/expression\s*\(/i',
            'import_statements' => '/@import/i',
        ];
        
        // SQL injection patterns (defense in depth)
        if ($options['check_sql_injection'] ?? true) {
            $patterns = array_merge($patterns, [
                'sql_keywords' => '/(\bunion\b|\bselect\b|\binsert\b|\bupdate\b|\bdelete\b|\bdrop\b|\balter\b)/i',
                'sql_injection' => '/(\bor\b|\band\b)\s+\d+\s*=\s*\d+/i',
                'sql_comments' => '/(\/\*|\*\/|--|\#)/i',
            ]);
        }
        
        // LDAP injection patterns
        if ($options['check_ldap_injection'] ?? true) {
            $patterns['ldap_injection'] = '/[()&|!*]/';
        }
        
        // Command injection patterns
        if ($options['check_command_injection'] ?? true) {
            $patterns['command_injection'] = '/[;&|`$(){}[\]]/';
        }
        
        return $patterns;
    }
    
    /**
     * Validate Unicode characters for suspicious content
     */
    private function validateUnicodeCharacters(string $content): void
    {
        // Check for right-to-left override characters (can be used for spoofing)
        if (preg_match('/[\x{202D}\x{202E}\x{2066}\x{2067}\x{2068}]/u', $content)) {
            throw new InvalidArgumentException("Suspicious Unicode directional characters detected");
        }
        
        // Check for zero-width characters (can be used for hiding content)
        if (preg_match('/[\x{200B}\x{200C}\x{200D}\x{FEFF}]/u', $content)) {
            throw new InvalidArgumentException("Zero-width characters detected");
        }
    }
    
    /**
     * Remove control characters
     */
    private function removeControlCharacters(string $content): string
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);
    }
    
    /**
     * Normalize whitespace
     */
    private function normalizeWhitespace(string $content): string
    {
        // Replace multiple whitespace with single space
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Normalize line endings
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        
        return $content;
    }
    
    /**
     * Remove dangerous patterns
     */
    private function removeDangerousPatterns(string $content, array $options): string
    {
        $patterns = $this->getSecurityPatterns($options);
        
        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }
        
        return $content;
    }
    
    /**
     * Purify HTML content (placeholder for HTML Purifier integration)
     */
    private function purifyHtml(string $content): string
    {
        // This would integrate with HTML Purifier or similar library
        // For now, strip all HTML tags
        return strip_tags($content);
    }
    
    /**
     * Run custom validators
     */
    private function runCustomValidators(string $content, array $validators): void
    {
        foreach ($validators as $validator) {
            if (is_callable($validator)) {
                $validator($content);
            }
        }
    }
    
    /**
     * Get default configuration
     */
    private function getDefaultConfig(): array
    {
        return [
            'min_length' => 1,
            'max_length' => 500,
            'check_unicode' => true,
            'check_sql_injection' => true,
            'check_ldap_injection' => false,
            'check_command_injection' => false,
            'normalize_whitespace' => true,
            'html_purify' => false,
        ];
    }
}
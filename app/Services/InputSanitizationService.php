<?php

namespace App\Services;

class InputSanitizationService
{
    protected array $dangerousPatterns = [
        '/(?:javascript|data|vbscript|file|about):/gi',
        '/on\w+\s*=\s*["\']/gi',
        '/<script[^>]*>.*?<\/script>/gis',
        '/<iframe[^>]*>.*?<\/iframe>/gis',
        '/expression\s*\(/gi',
    ];

    /**
     * Sanitize input based on context
     */
    public function sanitize(string $input, array $options = []): string
    {
        $context = $options['context'] ?? null;
        $maxLength = $options['maxLength'] ?? 1000;

        // Basic sanitization
        $sanitized = $this->basicSanitization($input, $maxLength);

        // Context-specific sanitization
        if ($context) {
            $sanitized = $this->contextSpecificSanitization($sanitized, $context);
        }

        return $sanitized;
    }

    /**
     * Basic sanitization for all inputs
     */
    protected function basicSanitization(string $input, int $maxLength): string
    {
        // Length check for DoS prevention
        if (strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }

        // Remove null bytes and control characters
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);

        // Remove dangerous patterns
        foreach ($this->dangerousPatterns as $pattern) {
            $input = preg_replace($pattern, '', $input);
        }

        // Normalize Unicode
        if (function_exists('normalizer_normalize')) {
            $input = normalizer_normalize($input, Normalizer::FORM_C);
        }

        return trim($input);
    }

    /**
     * Context-specific sanitization
     */
    protected function contextSpecificSanitization(string $input, string $context): string
    {
        return match ($context) {
            'currency' => $this->sanitizeCurrency($input),
            'email' => $this->sanitizeEmail($input),
            'phone' => $this->sanitizePhone($input),
            'postal_code' => $this->sanitizePostalCode($input),
            'url' => $this->sanitizeUrl($input),
            'numeric' => $this->sanitizeNumeric($input),
            default => $input
        };
    }

    protected function sanitizeCurrency(string $input): string
    {
        return preg_replace('/[^0-9,.\u00A5\u20AC\u0024]/', '', $input);
    }

    protected function sanitizeEmail(string $input): string
    {
        return filter_var($input, FILTER_SANITIZE_EMAIL) ?: '';
    }

    protected function sanitizePhone(string $input): string
    {
        return preg_replace('/[^0-9\-\(\)\s\+]/', '', $input);
    }

    protected function sanitizePostalCode(string $input): string
    {
        return preg_replace('/[^0-9\-]/', '', $input);
    }

    protected function sanitizeUrl(string $input): string
    {
        $sanitized = filter_var($input, FILTER_SANITIZE_URL);

        // Ensure safe protocol
        if ($sanitized && ! preg_match('/^https?:\/\//', $sanitized)) {
            $sanitized = 'http://'.$sanitized;
        }

        return $sanitized ?: '';
    }

    protected function sanitizeNumeric(string $input): string
    {
        return preg_replace('/[^0-9.\-]/', '', $input);
    }
}

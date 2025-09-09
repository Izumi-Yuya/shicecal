<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Centralized validation service for enhanced security
 */
class ValidationService
{
    // Security patterns
    private const SCRIPT_PATTERN = '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi';
    private const DANGEROUS_HTML_PATTERN = '/<(iframe|object|embed|form|input|textarea|select|button)\b/i';
    private const SQL_INJECTION_PATTERN = '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION)\b)/i';
    private const XSS_PATTERN = '/(javascript:|vbscript:|onload=|onerror=|onclick=)/i';

    /**
     * Validate comment content with security checks
     */
    public function validateCommentContent(string $content, array $options = []): void
    {
        $rules = [
            'content' => [
                'required',
                'string',
                'min:' . ($options['min_length'] ?? 1),
                'max:' . ($options['max_length'] ?? 500),
            ]
        ];

        $validator = Validator::make(['content' => $content], $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Security validation
        $this->validateContentSecurity($content);
    }

    /**
     * Validate facility data with comprehensive checks
     */
    public function validateFacilityData(array $data): array
    {
        $rules = [
            'company_name' => 'required|string|max:255',
            'facility_name' => 'required|string|max:255',
            'office_code' => 'required|string|max:50|regex:/^[A-Z0-9]+$/',
            'email' => 'nullable|email|max:255',
            'website_url' => 'nullable|url|max:255',
            'phone_number' => 'nullable|string|max:20|regex:/^[\d\-\(\)\+\s]+$/',
            'postal_code' => 'nullable|string|size:7|regex:/^\d{7}$/',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Additional security validation for text fields
        $textFields = ['company_name', 'facility_name', 'address', 'building_name'];
        foreach ($textFields as $field) {
            if (isset($data[$field])) {
                $this->validateTextFieldSecurity($data[$field], $field);
            }
        }

        return $validator->validated();
    }

    /**
     * Validate view mode parameter
     */
    public function validateViewMode(string $viewMode): void
    {
        $allowedModes = ['card', 'table'];
        
        if (!in_array($viewMode, $allowedModes, true)) {
            throw new \InvalidArgumentException("Invalid view mode: {$viewMode}");
        }
    }

    /**
     * Validate file upload security
     */
    public function validateFileUpload(\Illuminate\Http\UploadedFile $file, array $options = []): void
    {
        $allowedMimes = $options['allowed_mimes'] ?? ['pdf', 'jpg', 'jpeg', 'png'];
        $maxSize = $options['max_size'] ?? 10240; // 10MB in KB

        $rules = [
            'file' => [
                'required',
                'file',
                'mimes:' . implode(',', $allowedMimes),
                'max:' . $maxSize,
            ]
        ];

        $validator = Validator::make(['file' => $file], $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Additional security checks
        $this->validateFileContent($file);
    }

    /**
     * Sanitize user input for safe display
     */
    public function sanitizeForDisplay(string $input): string
    {
        // Remove potentially dangerous content
        $sanitized = strip_tags($input);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        
        return trim($sanitized);
    }

    /**
     * Validate content for security issues
     */
    private function validateContentSecurity(string $content): void
    {
        $patterns = [
            self::SCRIPT_PATTERN => 'Script tags not allowed',
            self::DANGEROUS_HTML_PATTERN => 'Dangerous HTML elements not allowed',
            self::SQL_INJECTION_PATTERN => 'SQL injection patterns detected',
            self::XSS_PATTERN => 'XSS patterns detected',
        ];

        foreach ($patterns as $pattern => $message) {
            if (preg_match($pattern, $content)) {
                throw new \InvalidArgumentException($message);
            }
        }

        // Check for excessive special characters
        if (preg_match('/[<>"\'\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $content)) {
            throw new \InvalidArgumentException('Invalid characters in content');
        }
    }

    /**
     * Validate text field security
     */
    private function validateTextFieldSecurity(string $value, string $fieldName): void
    {
        // Check for script injection
        if (preg_match(self::SCRIPT_PATTERN, $value)) {
            throw new \InvalidArgumentException("Script content not allowed in {$fieldName}");
        }

        // Check for SQL injection patterns
        if (preg_match(self::SQL_INJECTION_PATTERN, $value)) {
            throw new \InvalidArgumentException("SQL patterns not allowed in {$fieldName}");
        }
    }

    /**
     * Validate file content for security
     */
    private function validateFileContent(\Illuminate\Http\UploadedFile $file): void
    {
        // Check file signature matches extension
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file->getPathname());
        finfo_close($finfo);

        $extension = strtolower($file->getClientOriginalExtension());
        $expectedMimes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
        ];

        if (isset($expectedMimes[$extension]) && $mimeType !== $expectedMimes[$extension]) {
            throw new \InvalidArgumentException('File content does not match extension');
        }

        // Check for embedded scripts in PDFs (basic check)
        if ($extension === 'pdf') {
            $content = file_get_contents($file->getPathname());
            if (strpos($content, '/JavaScript') !== false || strpos($content, '/JS') !== false) {
                throw new \InvalidArgumentException('PDF contains JavaScript');
            }
        }
    }
}
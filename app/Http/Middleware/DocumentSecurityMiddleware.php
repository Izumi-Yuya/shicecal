<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Document Security Middleware
 * 
 * Provides comprehensive security controls for document management operations:
 * - Authentication verification
 * - Rate limiting for file operations
 * - Path traversal attack prevention
 * - CSRF protection verification
 * - Suspicious activity detection and logging
 * 
 * Requirements: 8.4, 9.1, 9.2, 9.3
 */
class DocumentSecurityMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Authentication Check (Requirement 9.1)
        if (!Auth::check()) {
            $this->logSecurityEvent('unauthenticated_access_attempt', $request);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '認証が必要です。'
                ], 401);
            }
            
            return redirect()->route('login');
        }

        // 2. Rate Limiting (Requirement 8.4)
        $this->enforceRateLimit($request);

        // 3. Path Traversal Protection (Requirement 8.4)
        if ($this->hasPathTraversalAttempt($request)) {
            $this->logSecurityEvent('path_traversal_attempt', $request, [
                'suspicious_parameters' => $this->getSuspiciousParameters($request)
            ]);
            
            abort(400, '不正なパラメータが検出されました。');
        }

        // 4. CSRF Protection for State-Changing Operations (Requirement 8.4)
        if ($this->isStateChangingOperation($request) && !$this->hasValidCsrfToken($request)) {
            $this->logSecurityEvent('csrf_token_missing', $request);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'セキュリティトークンが無効です。'
                ], 419);
            }
            
            abort(419, 'セキュリティトークンが無効です。');
        }

        // 5. Suspicious Activity Detection
        if ($this->detectSuspiciousActivity($request)) {
            $this->logSecurityEvent('suspicious_activity_detected', $request, [
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'referer' => $request->header('referer')
            ]);
        }

        // 6. File Operation Security Checks
        if ($this->isFileOperation($request)) {
            $this->performFileOperationSecurityChecks($request);
        }

        return $next($request);
    }

    /**
     * Enforce rate limiting for document operations.
     */
    protected function enforceRateLimit(Request $request): void
    {
        $user = Auth::user();
        $key = 'document_operations:' . $user->id;
        
        // Different limits for different operations
        $limits = [
            'upload' => ['attempts' => 20, 'decay' => 60], // 20 uploads per minute
            'download' => ['attempts' => 100, 'decay' => 60], // 100 downloads per minute
            'folder_operations' => ['attempts' => 30, 'decay' => 60], // 30 folder operations per minute
            'default' => ['attempts' => 50, 'decay' => 60], // 50 general operations per minute
        ];

        $operationType = $this->getOperationType($request);
        $limit = $limits[$operationType] ?? $limits['default'];

        $rateLimitKey = $key . ':' . $operationType;

        if (RateLimiter::tooManyAttempts($rateLimitKey, $limit['attempts'])) {
            $this->logSecurityEvent('rate_limit_exceeded', $request, [
                'operation_type' => $operationType,
                'limit' => $limit['attempts'],
                'decay' => $limit['decay']
            ]);

            $seconds = RateLimiter::availableIn($rateLimitKey);
            
            if ($request->expectsJson()) {
                response()->json([
                    'success' => false,
                    'message' => 'リクエストが多すぎます。しばらく待ってから再試行してください。',
                    'retry_after' => $seconds
                ], 429)->send();
                exit;
            }
            
            abort(429, 'リクエストが多すぎます。しばらく待ってから再試行してください。');
        }

        RateLimiter::hit($rateLimitKey, $limit['decay']);
    }

    /**
     * Check for path traversal attempts in request parameters.
     */
    protected function hasPathTraversalAttempt(Request $request): bool
    {
        $suspiciousPatterns = [
            '/\.\.\//',           // ../
            '/\.\.\\\\/',         // ..\
            '/\.\.\%2F/',         // ..%2F (URL encoded /)
            '/\.\.\%5C/',         // ..%5C (URL encoded \)
            '/\%2E\%2E\%2F/',     // %2E%2E%2F (URL encoded ../)
            '/\%2E\%2E\%5C/',     // %2E%2E%5C (URL encoded ..\)
            '/\/etc\//',          // /etc/
            '/\/proc\//',         // /proc/
            '/\/var\//',          // /var/
            '/C:\\\\/i',          // C:\ (Windows paths)
        ];

        $allInput = $request->all();
        $inputString = json_encode($allInput);

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $inputString)) {
                return true;
            }
        }

        // Check URL path
        $path = $request->getPathInfo();
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get suspicious parameters from request.
     */
    protected function getSuspiciousParameters(Request $request): array
    {
        $suspicious = [];
        $allInput = $request->all();

        foreach ($allInput as $key => $value) {
            if (is_string($value) && (
                strpos($value, '../') !== false ||
                strpos($value, '..\\') !== false ||
                strpos($value, '/etc/') !== false ||
                strpos($value, '/proc/') !== false ||
                preg_match('/C:\\\\/i', $value)
            )) {
                $suspicious[$key] = $value;
            }
        }

        return $suspicious;
    }

    /**
     * Check if the request is a state-changing operation.
     */
    protected function isStateChangingOperation(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']);
    }

    /**
     * Check if request has valid CSRF token.
     */
    protected function hasValidCsrfToken(Request $request): bool
    {
        // For API requests, we might use different token validation
        if ($request->expectsJson() && $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return $request->hasHeader('X-CSRF-TOKEN') || $request->has('_token');
        }

        return $request->has('_token');
    }

    /**
     * Detect suspicious activity patterns.
     */
    protected function detectSuspiciousActivity(Request $request): bool
    {
        $suspiciousIndicators = [
            // Unusual user agents
            'user_agent_suspicious' => $this->hasSuspiciousUserAgent($request),
            
            // Rapid sequential requests from same IP
            'rapid_requests' => $this->hasRapidRequests($request),
            
            // Unusual parameter patterns
            'unusual_parameters' => $this->hasUnusualParameters($request),
            
            // Missing expected headers
            'missing_headers' => $this->hasMissingExpectedHeaders($request),
        ];

        return array_sum($suspiciousIndicators) >= 2; // Threshold for suspicious activity
    }

    /**
     * Check for suspicious user agent patterns.
     */
    protected function hasSuspiciousUserAgent(Request $request): bool
    {
        $userAgent = $request->userAgent();
        
        if (empty($userAgent)) {
            return true;
        }

        $suspiciousPatterns = [
            '/curl/i',
            '/wget/i',
            '/python/i',
            '/bot/i',
            '/crawler/i',
            '/scanner/i',
            '/sqlmap/i',
            '/nikto/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for rapid sequential requests.
     */
    protected function hasRapidRequests(Request $request): bool
    {
        $ip = $request->ip();
        $key = 'rapid_requests:' . $ip;
        
        // More than 10 requests in 10 seconds is considered rapid
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return true;
        }
        
        RateLimiter::hit($key, 10);
        return false;
    }

    /**
     * Check for unusual parameter patterns.
     */
    protected function hasUnusualParameters(Request $request): bool
    {
        $allInput = $request->all();
        
        foreach ($allInput as $key => $value) {
            if (is_string($value)) {
                // Check for SQL injection patterns
                if (preg_match('/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bDELETE\b|\bDROP\b)/i', $value)) {
                    return true;
                }
                
                // Check for XSS patterns
                if (preg_match('/<script|javascript:|on\w+\s*=/i', $value)) {
                    return true;
                }
                
                // Check for command injection patterns
                if (preg_match('/[;&|`$(){}[\]\\\\]/', $value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check for missing expected headers.
     */
    protected function hasMissingExpectedHeaders(Request $request): bool
    {
        // For AJAX requests, expect certain headers
        if ($request->expectsJson()) {
            return !$request->hasHeader('X-Requested-With');
        }

        return false;
    }

    /**
     * Get operation type for rate limiting.
     */
    protected function getOperationType(Request $request): string
    {
        $path = $request->path();
        $method = $request->method();

        if (strpos($path, '/files') !== false && $method === 'POST') {
            return 'upload';
        }

        if (strpos($path, '/download') !== false) {
            return 'download';
        }

        if (strpos($path, '/folders') !== false) {
            return 'folder_operations';
        }

        return 'default';
    }

    /**
     * Check if request is a file operation.
     */
    protected function isFileOperation(Request $request): bool
    {
        $path = $request->path();
        return strpos($path, '/files') !== false || strpos($path, '/folders') !== false;
    }

    /**
     * Perform additional security checks for file operations.
     */
    protected function performFileOperationSecurityChecks(Request $request): void
    {
        // Check for file upload security
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                if ($this->isExecutableFile($file)) {
                    $this->logSecurityEvent('executable_file_upload_attempt', $request, [
                        'filename' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType()
                    ]);
                    
                    abort(400, '実行可能ファイルのアップロードは禁止されています。');
                }
            }
        }

        // Check for suspicious file names in parameters
        $allInput = $request->all();
        foreach ($allInput as $key => $value) {
            if (is_string($value) && $this->hasSuspiciousFileName($value)) {
                $this->logSecurityEvent('suspicious_filename_detected', $request, [
                    'parameter' => $key,
                    'value' => $value
                ]);
            }
        }
    }

    /**
     * Check if uploaded file is executable.
     */
    protected function isExecutableFile($file): bool
    {
        $dangerousExtensions = [
            'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar',
            'php', 'asp', 'aspx', 'jsp', 'py', 'rb', 'pl', 'sh', 'bash'
        ];

        $extension = strtolower($file->getClientOriginalExtension());
        return in_array($extension, $dangerousExtensions);
    }

    /**
     * Check for suspicious file names.
     */
    protected function hasSuspiciousFileName(string $filename): bool
    {
        $suspiciousPatterns = [
            '/\.(exe|bat|cmd|com|pif|scr|vbs|js|jar|php|asp|aspx|jsp)$/i',
            '/^(con|prn|aux|nul|com[1-9]|lpt[1-9])(\.|$)/i',
            '/[\x00-\x1f\x7f]/',
            '/[<>:"|?*]/',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $filename)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log security events for monitoring and audit.
     */
    protected function logSecurityEvent(string $eventType, Request $request, array $additionalData = []): void
    {
        $logData = [
            'event_type' => $eventType,
            'user_id' => Auth::id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString(),
            'session_id' => $request->session()->getId(),
        ];

        // Add additional context data
        if (!empty($additionalData)) {
            $logData['additional_data'] = $additionalData;
        }

        // Log to security channel
        Log::channel('security')->warning('Document Security Event', $logData);

        // For critical events, also log to main log
        $criticalEvents = [
            'path_traversal_attempt',
            'executable_file_upload_attempt',
            'rate_limit_exceeded',
            'suspicious_activity_detected'
        ];

        if (in_array($eventType, $criticalEvents)) {
            Log::warning('Critical Document Security Event: ' . $eventType, $logData);
        }
    }
}
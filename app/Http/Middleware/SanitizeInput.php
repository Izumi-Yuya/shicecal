<?php

namespace App\Http\Middleware;

use App\Services\InputSanitizationService;
use Closure;
use Illuminate\Http\Request;

class SanitizeInput
{
    protected $sanitizer;

    public function __construct(InputSanitizationService $sanitizer)
    {
        $this->sanitizer = $sanitizer;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Only sanitize for specific routes that handle user input
        if ($this->shouldSanitize($request)) {
            $sanitized = $this->sanitizeRequestData($request->all());
            $request->merge($sanitized);
        }

        return $next($request);
    }

    /**
     * Determine if the request should be sanitized
     */
    protected function shouldSanitize(Request $request): bool
    {
        $sanitizeRoutes = [
            'facilities.land-info.update',
            'facilities.basic-info.update',
            'comments.store',
            'comments.update',
        ];

        return in_array($request->route()?->getName(), $sanitizeRoutes);
    }

    /**
     * Sanitize request data recursively
     */
    protected function sanitizeRequestData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeRequestData($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizer->sanitize($value, [
                    'context' => $this->getFieldContext($key),
                    'maxLength' => $this->getMaxLength($key),
                ]);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Get sanitization context for field
     */
    protected function getFieldContext(string $field): ?string
    {
        $contexts = [
            'purchase_price' => 'currency',
            'monthly_rent' => 'currency',
            'management_company_email' => 'email',
            'owner_email' => 'email',
            'management_company_phone' => 'phone',
            'owner_phone' => 'phone',
            'management_company_postal_code' => 'postal_code',
            'owner_postal_code' => 'postal_code',
            'management_company_url' => 'url',
            'owner_url' => 'url',
        ];

        return $contexts[$field] ?? null;
    }

    /**
     * Get maximum length for field
     */
    protected function getMaxLength(string $field): int
    {
        $lengths = [
            'notes' => 1000,
            'management_company_notes' => 1000,
            'owner_notes' => 1000,
            'management_company_name' => 30,
            'owner_name' => 30,
            'management_company_address' => 30,
            'owner_address' => 30,
        ];

        return $lengths[$field] ?? 255;
    }
}

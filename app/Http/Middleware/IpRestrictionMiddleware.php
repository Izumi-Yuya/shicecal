<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SystemSetting;

class IpRestrictionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip IP restriction in local environment
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        // Get allowed IPs from system settings
        $allowedIps = $this->getAllowedIps();
        
        // If no IP restrictions are configured, allow all
        if (empty($allowedIps)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        // Check if client IP is in allowed list
        if (!$this->isIpAllowed($clientIp, $allowedIps)) {
            abort(403, 'アクセスが許可されていないIPアドレスです。');
        }

        return $next($request);
    }

    /**
     * Get allowed IP addresses from system settings
     *
     * @return array
     */
    protected function getAllowedIps(): array
    {
        try {
            $setting = SystemSetting::where('key', 'allowed_ips')->first();
            
            if (!$setting || empty($setting->value)) {
                return [];
            }

            $ips = json_decode($setting->value, true);
            return is_array($ips) ? $ips : [];
        } catch (\Exception $e) {
            // If there's an error reading settings, allow access to prevent lockout
            return [];
        }
    }

    /**
     * Check if IP is allowed
     *
     * @param string $clientIp
     * @param array $allowedIps
     * @return bool
     */
    protected function isIpAllowed(string $clientIp, array $allowedIps): bool
    {
        foreach ($allowedIps as $allowedIp) {
            if ($this->matchIp($clientIp, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Match IP address against pattern (supports CIDR notation)
     *
     * @param string $clientIp
     * @param string $pattern
     * @return bool
     */
    protected function matchIp(string $clientIp, string $pattern): bool
    {
        // Exact match
        if ($clientIp === $pattern) {
            return true;
        }

        // CIDR notation support
        if (strpos($pattern, '/') !== false) {
            list($subnet, $mask) = explode('/', $pattern);
            
            if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $clientLong = ip2long($clientIp);
                $subnetLong = ip2long($subnet);
                $maskLong = -1 << (32 - (int)$mask);
                
                return ($clientLong & $maskLong) === ($subnetLong & $maskLong);
            }
        }

        // Wildcard support (e.g., 192.168.1.*)
        if (strpos($pattern, '*') !== false) {
            $pattern = str_replace('*', '.*', $pattern);
            return preg_match('/^' . $pattern . '$/', $clientIp);
        }

        return false;
    }
}

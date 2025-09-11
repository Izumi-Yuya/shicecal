<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Check if user has the required role
        if ($user->role !== $role) {
            // Return JSON response for API routes
            if ($request->expectsJson() || $request->is('*/api/*')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            abort(403, 'この機能にアクセスする権限がありません。');
        }

        return $next($request);
    }
}

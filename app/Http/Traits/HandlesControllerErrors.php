<?php

namespace App\Http\Traits;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Centralized error handling for controllers
 */
trait HandlesControllerErrors
{
    /**
     * Handle exceptions with consistent logging and response format
     */
    protected function handleException(
        \Exception $exception,
        Request $request,
        string $operation = 'unknown',
        array $context = []
    ): JsonResponse|\Illuminate\Http\RedirectResponse {
        
        // Build log context
        $logContext = array_merge([
            'operation' => $operation,
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ], $context);

        // Handle specific exception types
        if ($exception instanceof AuthorizationException) {
            return $this->handleAuthorizationException($exception, $request, $logContext);
        }

        if ($exception instanceof ValidationException) {
            return $this->handleValidationException($exception, $request, $logContext);
        }

        // Handle general exceptions
        return $this->handleGeneralException($exception, $request, $logContext);
    }

    /**
     * Handle authorization exceptions
     */
    private function handleAuthorizationException(
        AuthorizationException $exception,
        Request $request,
        array $logContext
    ): JsonResponse|\Illuminate\Http\RedirectResponse {
        
        Log::warning('Authorization failed', array_merge($logContext, [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
        ]));

        $message = 'この操作を実行する権限がありません。';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'error_code' => 'AUTHORIZATION_FAILED'
            ], 403);
        }

        return redirect()->back()
            ->with('error', $message)
            ->withInput();
    }

    /**
     * Handle validation exceptions
     */
    private function handleValidationException(
        ValidationException $exception,
        Request $request,
        array $logContext
    ): JsonResponse|\Illuminate\Http\RedirectResponse {
        
        Log::info('Validation failed', array_merge($logContext, [
            'errors' => $exception->errors(),
        ]));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => '入力データに問題があります。',
                'errors' => $exception->errors(),
                'error_code' => 'VALIDATION_FAILED'
            ], 422);
        }

        return redirect()->back()
            ->withErrors($exception->validator)
            ->withInput();
    }

    /**
     * Handle general exceptions
     */
    private function handleGeneralException(
        \Exception $exception,
        Request $request,
        array $logContext
    ): JsonResponse|\Illuminate\Http\RedirectResponse {
        
        Log::error('Controller exception occurred', array_merge($logContext, [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]));

        $message = app()->environment('production') 
            ? 'システムエラーが発生しました。しばらく時間をおいて再度お試しください。'
            : $exception->getMessage();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'error_code' => 'SYSTEM_ERROR'
            ], 500);
        }

        return redirect()->back()
            ->with('error', $message)
            ->withInput();
    }

    /**
     * Log successful operations for audit trail
     */
    protected function logSuccess(string $operation, array $context = []): void
    {
        Log::info("Operation successful: {$operation}", array_merge([
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
        ], $context));
    }
}
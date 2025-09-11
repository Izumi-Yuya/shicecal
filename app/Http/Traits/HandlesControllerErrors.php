<?php

namespace App\Http\Traits;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

trait HandlesControllerErrors
{
    /**
     * Handle exceptions with appropriate logging and response
     *
     * @return JsonResponse|RedirectResponse
     */
    protected function handleException(Exception $e, string $context = '')
    {
        // Log the error with context
        Log::error("Controller Error [{$context}]: ".$e->getMessage(), [
            'exception' => $e,
            'user_id' => auth()->id(),
            'request_url' => request()->url(),
            'request_method' => request()->method(),
            'request_data' => request()->except(['password', 'password_confirmation']),
            'stack_trace' => $e->getTraceAsString(),
        ]);

        // Return JSON response for API requests
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getErrorMessage($e),
                'error_code' => $this->getErrorCode($e),
            ], $this->getHttpStatusCode($e));
        }

        // Return redirect response for web requests
        return back()->with('error', $this->getErrorMessage($e));
    }

    /**
     * Get appropriate error code for the exception
     */
    protected function getErrorCode(Exception $e): string
    {
        return match (get_class($e)) {
            ValidationException::class => 'VALIDATION_ERROR',
            AuthorizationException::class => 'AUTHORIZATION_ERROR',
            AuthenticationException::class => 'AUTHENTICATION_ERROR',
            ModelNotFoundException::class => 'NOT_FOUND',
            default => 'GENERAL_ERROR'
        };
    }

    /**
     * Get user-friendly error message
     */
    protected function getErrorMessage(Exception $e): string
    {
        return match (get_class($e)) {
            ValidationException::class => 'バリデーションエラーが発生しました。',
            AuthorizationException::class => 'この操作を実行する権限がありません。',
            AuthenticationException::class => 'ログインが必要です。',
            ModelNotFoundException::class => '指定されたデータが見つかりません。',
            default => 'エラーが発生しました。しばらく時間をおいて再度お試しください。'
        };
    }

    /**
     * Get appropriate HTTP status code for the exception
     */
    protected function getHttpStatusCode(Exception $e): int
    {
        return match (get_class($e)) {
            ValidationException::class => 422,
            AuthorizationException::class => 403,
            AuthenticationException::class => 401,
            ModelNotFoundException::class => 404,
            default => 500
        };
    }

    /**
     * Handle validation errors specifically
     *
     * @return JsonResponse|RedirectResponse
     */
    protected function handleValidationException(ValidationException $e, string $context = '')
    {
        Log::warning("Validation Error [{$context}]: ".$e->getMessage(), [
            'errors' => $e->errors(),
            'user_id' => auth()->id(),
            'request_url' => request()->url(),
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラーが発生しました。',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_ERROR',
            ], 422);
        }

        return back()->withErrors($e->errors())->withInput();
    }

    /**
     * Handle authorization errors specifically
     *
     * @return JsonResponse|RedirectResponse
     */
    protected function handleAuthorizationException(AuthorizationException $e, string $context = '')
    {
        Log::warning("Authorization Error [{$context}]: ".$e->getMessage(), [
            'user_id' => auth()->id(),
            'request_url' => request()->url(),
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'この操作を実行する権限がありません。',
                'error_code' => 'AUTHORIZATION_ERROR',
            ], 403);
        }

        return back()->with('error', 'この操作を実行する権限がありません。');
    }
}

<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;

trait HandlesApiResponses
{
    /**
     * Handle successful response
     */
    protected function successResponse(string $message, $data = null, int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Handle error response
     */
    protected function errorResponse(string $message, $errors = null, int $status = 400, string $errorCode = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        // Add debug information in non-production environments
        if (!app()->environment('production') && request()->has('debug')) {
            $response['debug'] = [
                'request_id' => request()->header('X-Request-ID', uniqid()),
                'user_id' => auth()->id(),
                'endpoint' => request()->fullUrl(),
            ];
        }

        return response()->json($response, $status);
    }

    /**
     * Handle exception with proper logging and response
     */
    protected function handleException(\Exception $e, Request $request, array $context = []): JsonResponse
    {
        // Log the exception with context
        Log::error('Controller exception occurred', array_merge([
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'user_id' => auth()->id(),
            'request_url' => $request->fullUrl(),
            'request_method' => $request->method(),
        ], $context));

        // Handle specific exception types
        if ($e instanceof AuthorizationException) {
            return $this->errorResponse('この操作を実行する権限がありません。', null, 403);
        }

        if ($e instanceof ValidationException) {
            return $this->errorResponse('入力データに問題があります。', $e->errors(), 422);
        }

        // Generic error response
        $message = app()->environment('production') 
            ? 'システムエラーが発生しました。' 
            : $e->getMessage();

        return $this->errorResponse($message, null, 500);
    }

    /**
     * Handle mixed JSON/HTML response
     */
    protected function mixedResponse(Request $request, string $successMessage, string $errorMessage, $data = null, string $redirectRoute = null)
    {
        if ($request->expectsJson()) {
            return $data 
                ? $this->successResponse($successMessage, $data)
                : $this->successResponse($successMessage);
        }

        $redirect = $redirectRoute ? redirect()->route($redirectRoute) : redirect()->back();
        
        return $data 
            ? $redirect->with('success', $successMessage)->with('data', $data)
            : $redirect->with('success', $successMessage);
    }
}
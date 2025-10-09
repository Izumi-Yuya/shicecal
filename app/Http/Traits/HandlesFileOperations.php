<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Trait for handling file operations with consistent error handling
 */
trait HandlesFileOperations
{
    /**
     * Handle file operation with consistent error handling and logging
     */
    protected function handleFileOperation(
        callable $operation,
        Request $request,
        string $operationType,
        array $context = []
    ): JsonResponse|\Illuminate\Http\RedirectResponse {
        
        try {
            $result = $operation();
            
            // Log successful operation
            Log::info("File operation successful: {$operationType}", array_merge([
                'user_id' => auth()->id(),
                'timestamp' => now()->toISOString(),
            ], $context));
            
            return $result;
            
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->handleAuthorizationError($request, $operationType, $context);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->handleValidationError($request, $e, $context);
        } catch (Exception $e) {
            return $this->handleGeneralError($request, $e, $operationType, $context);
        }
    }

    /**
     * Handle authorization errors
     */
    private function handleAuthorizationError(
        Request $request,
        string $operationType,
        array $context
    ): JsonResponse|\Illuminate\Http\RedirectResponse {
        
        Log::warning("Authorization failed for file operation: {$operationType}", array_merge([
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
        ], $context));

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
     * Handle validation errors
     */
    private function handleValidationError(
        Request $request,
        \Illuminate\Validation\ValidationException $e,
        array $context
    ): JsonResponse|\Illuminate\Http\RedirectResponse {
        
        Log::info('File operation validation failed', array_merge([
            'errors' => $e->errors(),
            'user_id' => auth()->id(),
        ], $context));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => '入力データに問題があります。',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_FAILED'
            ], 422);
        }

        return redirect()->back()
            ->withErrors($e->validator)
            ->withInput();
    }

    /**
     * Handle general errors
     */
    private function handleGeneralError(
        Request $request,
        Exception $e,
        string $operationType,
        array $context
    ): JsonResponse|\Illuminate\Http\RedirectResponse {
        
        Log::error("File operation failed: {$operationType}", array_merge([
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'user_id' => auth()->id(),
        ], $context));

        $message = app()->environment('production') 
            ? 'システムエラーが発生しました。しばらく時間をおいて再度お試しください。'
            : $e->getMessage();

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
     * Validate file upload requirements
     */
    protected function validateFileUpload($file, array $allowedTypes = ['pdf'], int $maxSizeKB = 10240): void
    {
        if (!$file) {
            throw new Exception('ファイルが選択されていません。');
        }

        // Check file size
        if ($file->getSize() > $maxSizeKB * 1024) {
            throw new Exception("ファイルサイズは{$maxSizeKB}KB以下にしてください。");
        }

        // Check file type
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedTypes)) {
            $allowedTypesStr = implode(', ', $allowedTypes);
            throw new Exception("許可されているファイル形式: {$allowedTypesStr}");
        }

        // Check MIME type
        $mimeType = $file->getClientMimeType();
        $allowedMimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        ];

        $expectedMimeType = $allowedMimeTypes[$extension] ?? null;
        if ($expectedMimeType && $mimeType !== $expectedMimeType) {
            throw new Exception('ファイル形式が正しくありません。');
        }
    }
}
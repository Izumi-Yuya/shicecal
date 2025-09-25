<?php

namespace App\Services\Traits;

use App\Exceptions\ServiceException;
use Exception;
use Illuminate\Support\Facades\Log;

trait HandlesServiceErrors
{
    /**
     * Log error with service context.
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error($message, array_merge($context, [
            'service' => static::class,
            'user_id' => auth()->id() ?? null,
            'timestamp' => now()->toISOString(),
        ]));
    }

    /**
     * Log warning with service context.
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, array_merge($context, [
            'service' => static::class,
            'user_id' => auth()->id() ?? null,
            'timestamp' => now()->toISOString(),
        ]));
    }

    /**
     * Log info with service context.
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($message, array_merge($context, [
            'service' => static::class,
            'user_id' => auth()->id() ?? null,
            'timestamp' => now()->toISOString(),
        ]));
    }

    /**
     * Throw service-specific exception.
     *
     * @throws ServiceException
     */
    protected function throwServiceException(string $message, int $code = 0, array $context = []): never
    {
        $exceptionClass = $this->getServiceExceptionClass();

        // Log the error before throwing
        $this->logError("Service Exception: {$message}", array_merge($context, [
            'exception_class' => $exceptionClass,
            'code' => $code,
        ]));

        throw new $exceptionClass($message, $code, null, '', $context);
    }

    /**
     * Handle and re-throw exceptions with service context.
     *
     * @throws ServiceException
     */
    protected function handleAndRethrowException(Exception $e, string $operation, array $context = []): never
    {
        $message = "Service operation failed [{$operation}]: ".$e->getMessage();

        $this->logError($message, array_merge($context, [
            'original_exception' => get_class($e),
            'original_message' => $e->getMessage(),
            'operation' => $operation,
            'stack_trace' => $e->getTraceAsString(),
        ]));

        $exceptionClass = $this->getServiceExceptionClass();
        throw new $exceptionClass($message, $e->getCode(), $e, '', $context);
    }

    /**
     * Validate required parameters and throw exception if missing.
     *
     * @throws ServiceException
     */
    protected function validateRequiredParams(array $params, array $required, string $operation = ''): void
    {
        $missing = [];

        foreach ($required as $param) {
            if (! array_key_exists($param, $params) || $params[$param] === null) {
                $missing[] = $param;
            }
        }

        if (! empty($missing)) {
            $message = 'Missing required parameters: '.implode(', ', $missing);
            if ($operation) {
                $message .= " for operation: {$operation}";
            }

            $this->throwServiceException($message, 400, [
                'missing_params' => $missing,
                'operation' => $operation,
                'provided_params' => array_keys($params),
            ]);
        }
    }

    /**
     * Execute operation with error handling.
     *
     * @throws ServiceException
     */
    protected function executeWithErrorHandling(callable $operation, string $operationName, array $context = []): mixed
    {
        try {
            $this->logInfo("Starting operation: {$operationName}", $context);

            $result = $operation();

            $this->logInfo("Completed operation: {$operationName}", $context);

            return $result;
        } catch (ServiceException $e) {
            // Re-throw service exceptions as-is
            throw $e;
        } catch (Exception $e) {
            // Handle and re-throw other exceptions
            $this->handleAndRethrowException($e, $operationName, $context);
        }
    }

    /**
     * Get the service-specific exception class.
     * Must be implemented by each service using this trait.
     */
    abstract protected function getServiceExceptionClass(): string;
}

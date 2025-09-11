<?php

namespace Tests\Unit\Traits;

use App\Exceptions\ServiceException;
use App\Http\Traits\HandlesControllerErrors;
use App\Services\Traits\HandlesServiceErrors;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ErrorHandlingTraitsTest extends TestCase
{
    public function test_controller_error_trait_methods_exist()
    {
        $trait = new class
        {
            use HandlesControllerErrors;

            public function test_get_error_code($exception)
            {
                return $this->getErrorCode($exception);
            }

            public function test_get_error_message($exception)
            {
                return $this->getErrorMessage($exception);
            }

            public function test_get_http_status_code($exception)
            {
                return $this->getHttpStatusCode($exception);
            }
        };

        // Test error codes
        $this->assertEquals('VALIDATION_ERROR', $trait->testGetErrorCode(new ValidationException(validator([]))));
        $this->assertEquals('AUTHORIZATION_ERROR', $trait->testGetErrorCode(new AuthorizationException));
        $this->assertEquals('NOT_FOUND', $trait->testGetErrorCode(new ModelNotFoundException));
        $this->assertEquals('GENERAL_ERROR', $trait->testGetErrorCode(new \Exception));

        // Test HTTP status codes
        $this->assertEquals(422, $trait->testGetHttpStatusCode(new ValidationException(validator([]))));
        $this->assertEquals(403, $trait->testGetHttpStatusCode(new AuthorizationException));
        $this->assertEquals(404, $trait->testGetHttpStatusCode(new ModelNotFoundException));
        $this->assertEquals(500, $trait->testGetHttpStatusCode(new \Exception));

        // Test error messages (should be in Japanese)
        $this->assertStringContainsString('バリデーションエラー', $trait->testGetErrorMessage(new ValidationException(validator([]))));
        $this->assertStringContainsString('権限がありません', $trait->testGetErrorMessage(new AuthorizationException));
        $this->assertStringContainsString('見つかりません', $trait->testGetErrorMessage(new ModelNotFoundException));
        $this->assertStringContainsString('エラーが発生しました', $trait->testGetErrorMessage(new \Exception));
    }

    public function test_service_error_trait_methods_exist()
    {
        $trait = new class
        {
            use HandlesServiceErrors;

            protected function getServiceExceptionClass(): string
            {
                return ServiceException::class;
            }

            public function test_validate_required_params($params, $required, $operation = '')
            {
                return $this->validateRequiredParams($params, $required, $operation);
            }

            public function test_execute_with_error_handling($operation, $operationName, $context = [])
            {
                return $this->executeWithErrorHandling($operation, $operationName, $context);
            }
        };

        // Test validateRequiredParams - should not throw when all params present
        $trait->testValidateRequiredParams(['name' => 'test', 'email' => 'test@example.com'], ['name', 'email']);
        $this->assertTrue(true); // If we get here, no exception was thrown

        // Test validateRequiredParams - should throw when params missing
        $this->expectException(ServiceException::class);
        $trait->testValidateRequiredParams(['name' => 'test'], ['name', 'email']);
    }

    public function test_service_error_trait_execute_with_error_handling()
    {
        $trait = new class
        {
            use HandlesServiceErrors;

            protected function getServiceExceptionClass(): string
            {
                return ServiceException::class;
            }

            public function test_execute_with_error_handling($operation, $operationName, $context = [])
            {
                return $this->executeWithErrorHandling($operation, $operationName, $context);
            }
        };

        // Test successful operation
        $result = $trait->testExecuteWithErrorHandling(function () {
            return 'success';
        }, 'test_operation');

        $this->assertEquals('success', $result);

        // Test operation that throws exception
        $this->expectException(ServiceException::class);
        $trait->testExecuteWithErrorHandling(function () {
            throw new \Exception('Test error');
        }, 'test_operation');
    }
}

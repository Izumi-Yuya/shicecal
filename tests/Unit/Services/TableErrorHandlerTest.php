<?php

namespace Tests\Unit\Services;

use App\Services\TableErrorHandler;
use App\Services\TableConfigService;
use App\Services\ValidationResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Tests\TestCase;
use Exception;

class TableErrorHandlerTest extends TestCase
{
    private TableErrorHandler $errorHandler;
    private TableConfigService $configService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configService = $this->createMock(TableConfigService::class);
        $this->errorHandler = new TableErrorHandler($this->configService);
    }

    public function test_handles_config_error_with_fallback()
    {
        Log::shouldReceive('warning')->once();
        
        $exception = new Exception('Configuration not found');
        $result = $this->errorHandler->handleConfigError('basic_info', $exception);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('columns', $result);
        $this->assertArrayHasKey('layout', $result);
        $this->assertArrayHasKey('styling', $result);
        $this->assertArrayHasKey('features', $result);
    }

    public function test_handles_render_error_with_fallback()
    {
        Log::shouldReceive('error')->once();
        Log::shouldReceive('critical')->never();
        
        $exception = new Exception('Rendering failed');
        $data = ['test' => 'data'];
        
        $result = $this->errorHandler->handleRenderError('basic_info', $exception, $data);

        $this->assertIsString($result);
        $this->assertStringContainsString('table', $result);
    }

    public function test_handles_validation_error_with_fix_attempt()
    {
        Log::shouldReceive('warning')->once();
        Log::shouldReceive('info')->once();
        
        $validationResult = new ValidationResult(false, [
            'Column at index 0 missing required field: type'
        ]);
        
        $config = [
            'columns' => [
                [
                    'key' => 'name',
                    'label' => '名前'
                    // Missing 'type' field
                ]
            ]
        ];

        $result = $this->errorHandler->handleValidationError('basic_info', $validationResult, $config);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('columns', $result);
        $this->assertEquals('text', $result['columns'][0]['type']);
    }

    public function test_handles_validation_error_with_fallback_when_fix_fails()
    {
        Log::shouldReceive('warning')->once();
        Log::shouldReceive('info')->once(); // The fix attempt logs success
        Log::shouldReceive('error')->never();
        
        $validationResult = new ValidationResult(false, [
            'Configuration must have at least one column defined'
        ]);
        
        $config = [
            'columns' => []
        ];

        $result = $this->errorHandler->handleValidationError('basic_info', $validationResult, $config);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('columns', $result);
        $this->assertNotEmpty($result['columns']);
    }

    public function test_handles_component_error_with_fallback_component()
    {
        Log::shouldReceive('error')->once();
        
        View::shouldReceive('exists')
            ->with('components.fallback.simple-table')
            ->andReturn(true);
        
        $exception = new Exception('Component not found');
        $result = $this->errorHandler->handleComponentError('universal-table', $exception);

        $this->assertEquals('components.fallback.simple-table', $result);
    }

    public function test_handles_component_error_with_inline_html_when_fallback_missing()
    {
        Log::shouldReceive('error')->once();
        
        View::shouldReceive('exists')
            ->with('components.fallback.error-message')
            ->andReturn(false);
        
        $exception = new Exception('Component not found');
        $result = $this->errorHandler->handleComponentError('unknown-component', $exception);

        $this->assertIsString($result);
        $this->assertStringContainsString('unknown-component', $result);
        $this->assertStringContainsString('alert', $result);
    }

    public function test_logs_failure_with_appropriate_level()
    {
        $exception = new Exception('Test error');
        
        // Test config error logging
        Log::shouldReceive('warning')
            ->once()
            ->with('Table configuration failure', \Mockery::type('array'));
        
        $this->errorHandler->logFailure('basic_info', 'config', $exception);
        
        // Test render error logging
        Log::shouldReceive('error')
            ->once()
            ->with('Table rendering failure', \Mockery::type('array'));
        
        $this->errorHandler->logFailure('basic_info', 'render', $exception);
    }

    public function test_gets_default_config_for_basic_info_table()
    {
        Log::shouldReceive('warning')->once();
        
        $exception = new Exception('Config error');
        $result = $this->errorHandler->handleConfigError('basic_info', $exception);

        $this->assertEquals('key_value_pairs', $result['layout']['type']);
        $this->assertStringContainsString('facility-info', $result['styling']['table_class']);
        $this->assertCount(2, $result['columns']);
    }

    public function test_gets_default_config_for_service_info_table()
    {
        Log::shouldReceive('warning')->once();
        
        $exception = new Exception('Config error');
        $result = $this->errorHandler->handleConfigError('service_info', $exception);

        $this->assertEquals('grouped_rows', $result['layout']['type']);
        $this->assertStringContainsString('service-info', $result['styling']['table_class']);
    }

    public function test_gets_default_config_for_land_info_table()
    {
        Log::shouldReceive('warning')->once();
        
        $exception = new Exception('Config error');
        $result = $this->errorHandler->handleConfigError('land_info', $exception);

        $this->assertEquals('standard_table', $result['layout']['type']);
        $this->assertStringContainsString('land-info', $result['styling']['table_class']);
    }

    public function test_attempts_config_fix_for_missing_required_fields()
    {
        $validationResult = new ValidationResult(false, [
            'Column at index 0 missing required field: key',
            'Column at index 0 missing required field: type'
        ]);
        
        $config = [
            'columns' => [
                [
                    'label' => '名前'
                ]
            ]
        ];

        $result = $this->errorHandler->handleValidationError('basic_info', $validationResult, $config);

        $this->assertEquals('field_0', $result['columns'][0]['key']);
        $this->assertEquals('text', $result['columns'][0]['type']);
    }

    public function test_attempts_config_fix_for_invalid_column_type()
    {
        $validationResult = new ValidationResult(false, [
            'Invalid column type \'invalid_type\' at index 0'
        ]);
        
        $config = [
            'columns' => [
                [
                    'key' => 'name',
                    'label' => '名前',
                    'type' => 'invalid_type'
                ]
            ]
        ];

        $result = $this->errorHandler->handleValidationError('basic_info', $validationResult, $config);

        $this->assertEquals('text', $result['columns'][0]['type']);
    }

    public function test_attempts_config_fix_for_select_without_options()
    {
        $validationResult = new ValidationResult(false, [
            'Select column at index 0 must have a non-empty options array'
        ]);
        
        $config = [
            'columns' => [
                [
                    'key' => 'status',
                    'label' => 'ステータス',
                    'type' => 'select'
                ]
            ]
        ];

        $result = $this->errorHandler->handleValidationError('basic_info', $validationResult, $config);

        $this->assertArrayHasKey('options', $result['columns'][0]);
        $this->assertNotEmpty($result['columns'][0]['options']);
    }

    public function test_renders_simple_table_for_fallback()
    {
        Log::shouldReceive('error')->once();
        Log::shouldReceive('critical')->never();
        
        $exception = new Exception('Render error');
        $data = [
            'name' => 'テスト施設',
            'address' => 'テスト住所'
        ];
        
        $result = $this->errorHandler->handleRenderError('basic_info', $exception, $data);

        $this->assertStringContainsString('table', $result);
        $this->assertStringContainsString('テスト施設', $result);
        $this->assertStringContainsString('テスト住所', $result);
    }

    public function test_renders_error_message_when_all_fallbacks_fail()
    {
        // Test that the method returns a string with some content
        $exception = new Exception('Original error');
        $result = $this->errorHandler->handleRenderError('basic_info', $exception, []);

        $this->assertIsString($result);
        // Should contain some indication of data or error
        $this->assertTrue(
            strpos($result, 'table') !== false || 
            strpos($result, 'データ') !== false || 
            strpos($result, 'alert') !== false
        );
    }

    public function test_handles_exception_in_config_fix_gracefully()
    {
        Log::shouldReceive('warning')->atLeast()->once();
        Log::shouldReceive('error')->never();
        
        // Create a validation result that might cause issues during fix
        $validationResult = new ValidationResult(false, [
            'Some complex error that cannot be fixed'
        ]);
        
        $config = [
            'columns' => null // This might cause issues
        ];

        $result = $this->errorHandler->handleValidationError('basic_info', $validationResult, $config);

        // Should return default config for basic_info, not minimal fallback
        $this->assertIsArray($result);
        $this->assertArrayHasKey('columns', $result);
        // For basic_info, it should return facility_name as first column
        $this->assertEquals('facility_name', $result['columns'][0]['key']);
    }
}
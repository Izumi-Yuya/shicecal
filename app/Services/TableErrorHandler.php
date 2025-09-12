<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Exception;
use Throwable;

/**
 * Error handler for table configuration and rendering
 * 
 * This service handles errors that occur during table configuration
 * loading and rendering, providing fallback mechanisms to ensure
 * the application continues to function.
 */
class TableErrorHandler
{
    /**
     * @var TableConfigService
     */
    private TableConfigService $configService;

    /**
     * Create a new error handler instance
     *
     * @param TableConfigService $configService
     */
    public function __construct(TableConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Handle configuration loading errors
     *
     * @param string $tableType The table type that failed to load
     * @param Exception $exception The exception that occurred
     * @return array The fallback configuration
     */
    public function handleConfigError(string $tableType, Exception $exception): array
    {
        Log::warning("Table configuration error for {$tableType}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'table_type' => $tableType
        ]);

        // Try to get default configuration for the table type
        try {
            return $this->getDefaultConfigForType($tableType);
        } catch (Exception $e) {
            Log::error("Failed to get default configuration for {$tableType}", [
                'error' => $e->getMessage(),
                'original_error' => $exception->getMessage()
            ]);
            
            // Return minimal fallback configuration
            return $this->getMinimalFallbackConfig($tableType);
        }
    }

    /**
     * Handle table rendering errors
     *
     * @param string $tableType The table type that failed to render
     * @param Exception $exception The exception that occurred
     * @param array $data The data that was being rendered
     * @return string The fallback HTML content
     */
    public function handleRenderError(string $tableType, Exception $exception, array $data = []): string
    {
        Log::error("Table rendering error for {$tableType}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'table_type' => $tableType,
            'data_count' => count($data)
        ]);

        try {
            // Try to render with original table components
            return $this->renderFallbackTable($tableType, $data);
        } catch (Exception $e) {
            Log::critical("Fallback table rendering also failed for {$tableType}", [
                'error' => $e->getMessage(),
                'original_error' => $exception->getMessage()
            ]);
            
            // Return minimal error message
            return $this->renderErrorMessage($tableType, $exception);
        }
    }

    /**
     * Handle validation errors with graceful degradation
     *
     * @param string $tableType The table type
     * @param ValidationResult $validationResult The validation result
     * @param array $config The invalid configuration
     * @return array The corrected or fallback configuration
     */
    public function handleValidationError(string $tableType, ValidationResult $validationResult, array $config): array
    {
        Log::warning("Table configuration validation failed for {$tableType}", [
            'errors' => $validationResult->getErrors(),
            'table_type' => $tableType
        ]);

        try {
            // Try to fix common validation issues
            $fixedConfig = $this->attemptConfigFix($config, $validationResult);
            
            // Re-validate the fixed configuration
            $validator = app(TableConfigValidator::class);
            $revalidationResult = $validator->validate($fixedConfig);
            
            if ($revalidationResult->isValid()) {
                Log::info("Successfully fixed configuration for {$tableType}");
                return $fixedConfig;
            }
            
            // If fix didn't work, fall back to default
            Log::warning("Configuration fix failed for {$tableType}, using default configuration");
            return $this->getDefaultConfigForType($tableType);
            
        } catch (Exception $e) {
            Log::error("Error handling validation failure for {$tableType}", [
                'error' => $e->getMessage()
            ]);
            
            return $this->getMinimalFallbackConfig($tableType);
        }
    }

    /**
     * Handle component loading errors
     *
     * @param string $componentName The component that failed to load
     * @param Exception $exception The exception that occurred
     * @return string The fallback component path or content
     */
    public function handleComponentError(string $componentName, Exception $exception): string
    {
        Log::error("Component loading error for {$componentName}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'component' => $componentName
        ]);

        // Map to fallback components
        $fallbackComponents = [
            'universal-table' => 'components.fallback.simple-table',
            'table-comment-wrapper' => 'components.fallback.basic-wrapper',
            'table-comment-section' => 'components.fallback.basic-comments'
        ];

        $fallbackComponent = $fallbackComponents[$componentName] ?? 'components.fallback.error-message';

        // Check if fallback component exists
        if (View::exists($fallbackComponent)) {
            return $fallbackComponent;
        }

        // Return inline HTML as last resort
        return $this->getInlineFallbackHtml($componentName);
    }

    /**
     * Log configuration and rendering failures for monitoring
     *
     * @param string $tableType The table type
     * @param string $errorType The type of error (config, render, validation)
     * @param Exception $exception The exception
     * @param array $context Additional context
     */
    public function logFailure(string $tableType, string $errorType, Exception $exception, array $context = []): void
    {
        $logData = [
            'table_type' => $tableType,
            'error_type' => $errorType,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'context' => $context
        ];

        // Log at different levels based on error type
        switch ($errorType) {
            case 'config':
                Log::warning("Table configuration failure", $logData);
                break;
            case 'render':
                Log::error("Table rendering failure", $logData);
                break;
            case 'validation':
                Log::warning("Table validation failure", $logData);
                break;
            case 'component':
                Log::error("Table component failure", $logData);
                break;
            default:
                Log::error("Table system failure", $logData);
        }

        // Send to monitoring system if configured
        if (config('app.monitoring_enabled', false)) {
            $this->sendToMonitoring($logData);
        }
    }

    /**
     * Get default configuration for a specific table type
     *
     * @param string $tableType The table type
     * @return array The default configuration
     */
    private function getDefaultConfigForType(string $tableType): array
    {
        $baseConfig = [
            'columns' => [],
            'layout' => [
                'type' => 'standard_table',
                'show_headers' => true,
                'responsive_breakpoint' => 'md'
            ],
            'styling' => [
                'table_class' => 'table table-bordered',
                'header_class' => 'bg-light',
                'empty_value_class' => 'text-muted'
            ],
            'features' => [
                'comments' => false,
                'sorting' => false,
                'filtering' => false
            ]
        ];

        // Add basic columns based on table type
        switch ($tableType) {
            case TableConfigService::BASIC_INFO_TABLE:
                $baseConfig['columns'] = [
                    [
                        'key' => 'facility_name',
                        'label' => '施設名',
                        'type' => 'text',
                        'width' => '50%'
                    ],
                    [
                        'key' => 'address',
                        'label' => '住所',
                        'type' => 'text',
                        'width' => '50%'
                    ]
                ];
                $baseConfig['layout']['type'] = 'key_value_pairs';
                $baseConfig['styling']['table_class'] = 'table table-bordered facility-info';
                break;

            case TableConfigService::SERVICE_INFO_TABLE:
                $baseConfig['columns'] = [
                    [
                        'key' => 'service_type',
                        'label' => 'サービス種類',
                        'type' => 'text',
                        'width' => '100%'
                    ]
                ];
                $baseConfig['layout']['type'] = 'grouped_rows';
                $baseConfig['styling']['table_class'] = 'table table-bordered service-info';
                break;

            case TableConfigService::LAND_INFO_TABLE:
                $baseConfig['columns'] = [
                    [
                        'key' => 'ownership_type',
                        'label' => '所有形態',
                        'type' => 'text',
                        'width' => '50%'
                    ],
                    [
                        'key' => 'site_area_sqm',
                        'label' => '敷地面積',
                        'type' => 'text',
                        'width' => '50%'
                    ]
                ];
                $baseConfig['styling']['table_class'] = 'table table-bordered land-info';
                break;
        }

        return $baseConfig;
    }

    /**
     * Get minimal fallback configuration
     *
     * @param string $tableType The table type
     * @return array The minimal configuration
     */
    private function getMinimalFallbackConfig(string $tableType): array
    {
        return [
            'columns' => [
                [
                    'key' => 'fallback_data',
                    'label' => 'データ',
                    'type' => 'text',
                    'width' => '100%'
                ]
            ],
            'layout' => [
                'type' => 'standard_table',
                'show_headers' => true
            ],
            'styling' => [
                'table_class' => 'table table-bordered table-fallback',
                'header_class' => 'bg-warning text-dark',
                'empty_value_class' => 'text-muted'
            ],
            'features' => [
                'comments' => false,
                'sorting' => false,
                'filtering' => false
            ]
        ];
    }

    /**
     * Render fallback table using original components
     *
     * @param string $tableType The table type
     * @param array $data The data to render
     * @return string The rendered HTML
     */
    private function renderFallbackTable(string $tableType, array $data): string
    {
        // Map to original table components
        $originalComponents = [
            TableConfigService::BASIC_INFO_TABLE => 'facilities.basic-info.partials.original-table',
            TableConfigService::SERVICE_INFO_TABLE => 'facilities.services.partials.original-table',
            TableConfigService::LAND_INFO_TABLE => 'facilities.land-info.partials.original-table'
        ];

        $component = $originalComponents[$tableType] ?? null;

        if ($component && View::exists($component)) {
            return view($component, compact('data'))->render();
        }

        // Render simple HTML table as last resort
        return $this->renderSimpleTable($data);
    }

    /**
     * Render simple HTML table
     *
     * @param array $data The data to render
     * @return string The rendered HTML
     */
    private function renderSimpleTable(array $data): string
    {
        if (empty($data)) {
            return '<div class="alert alert-info">データがありません。</div>';
        }

        $html = '<table class="table table-bordered table-fallback">';
        $html .= '<thead class="bg-warning text-dark">';
        $html .= '<tr><th>項目</th><th>値</th></tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($data as $key => $value) {
            $displayKey = is_string($key) ? $key : "項目 {$key}";
            $displayValue = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
            $html .= "<tr><td>{$displayKey}</td><td>{$displayValue}</td></tr>";
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    /**
     * Render error message
     *
     * @param string $tableType The table type
     * @param Exception $exception The exception
     * @return string The error message HTML
     */
    private function renderErrorMessage(string $tableType, Exception $exception): string
    {
        $errorId = uniqid('table_error_');
        
        return sprintf(
            '<div class="alert alert-danger" role="alert">
                <h5>テーブル表示エラー</h5>
                <p>%s テーブルの表示中にエラーが発生しました。</p>
                <small class="text-muted">エラーID: %s</small>
            </div>',
            $tableType,
            $errorId
        );
    }

    /**
     * Attempt to fix common configuration issues
     *
     * @param array $config The invalid configuration
     * @param ValidationResult $validationResult The validation result
     * @return array The potentially fixed configuration
     */
    private function attemptConfigFix(array $config, ValidationResult $validationResult): array
    {
        $fixedConfig = $config;
        $errors = $validationResult->getErrors();

        foreach ($errors as $error) {
            // Fix missing columns
            if (strpos($error, 'must have at least one column') !== false) {
                $fixedConfig['columns'] = [
                    [
                        'key' => 'default_field',
                        'label' => 'データ',
                        'type' => 'text'
                    ]
                ];
            }

            // Fix missing required fields in columns
            if (preg_match('/Column at index (\d+) missing required field: (\w+)/', $error, $matches)) {
                $index = (int)$matches[1];
                $field = $matches[2];
                
                if (isset($fixedConfig['columns'][$index])) {
                    switch ($field) {
                        case 'key':
                            $fixedConfig['columns'][$index]['key'] = 'field_' . $index;
                            break;
                        case 'label':
                            $fixedConfig['columns'][$index]['label'] = 'フィールド ' . ($index + 1);
                            break;
                        case 'type':
                            $fixedConfig['columns'][$index]['type'] = 'text';
                            break;
                    }
                }
            }

            // Fix invalid column types
            if (preg_match('/Invalid column type \'(\w+)\' at index (\d+)/', $error, $matches)) {
                $index = (int)$matches[2];
                if (isset($fixedConfig['columns'][$index])) {
                    $fixedConfig['columns'][$index]['type'] = 'text';
                }
            }

            // Fix select columns without options
            if (preg_match('/Select column at index (\d+) must have/', $error, $matches)) {
                $index = (int)$matches[1];
                if (isset($fixedConfig['columns'][$index])) {
                    $fixedConfig['columns'][$index]['options'] = [
                        'default' => 'デフォルト'
                    ];
                }
            }
        }

        return $fixedConfig;
    }

    /**
     * Get inline fallback HTML for components
     *
     * @param string $componentName The component name
     * @return string The inline HTML
     */
    private function getInlineFallbackHtml(string $componentName): string
    {
        return sprintf(
            '<div class="alert alert-warning">
                <strong>コンポーネントエラー:</strong> %s の読み込みに失敗しました。
            </div>',
            $componentName
        );
    }

    /**
     * Send error data to monitoring system
     *
     * @param array $logData The log data
     */
    private function sendToMonitoring(array $logData): void
    {
        // Implementation would depend on monitoring system
        // This is a placeholder for external monitoring integration
        try {
            // Example: Send to external monitoring service
            // MonitoringService::send('table_error', $logData);
        } catch (Exception $e) {
            Log::warning('Failed to send error to monitoring system', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
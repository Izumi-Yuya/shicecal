<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Facility;
use App\Models\FacilityService;
use App\Services\TableConfigService;
use App\Services\TableDataFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ServiceInfoTableStandardizationTest extends TestCase
{
    use RefreshDatabase;

    protected TableConfigService $configService;
    protected TableDataFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configService = app(TableConfigService::class);
        $this->formatter = app(TableDataFormatter::class);
    }

    public function test_service_info_table_config_is_loaded_correctly()
    {
        $config = $this->configService->getTableConfig('service_info');
        
        $this->assertIsArray($config);
        $this->assertEquals('service_table', $config['layout']['type']);
        $this->assertTrue($config['features']['comments']);
        
        // Verify key columns exist
        $columnKeys = array_column($config['columns'], 'key');
        $this->assertContains('service_type', $columnKeys);
        $this->assertContains('renewal_start_date', $columnKeys);
        $this->assertContains('renewal_end_date', $columnKeys);
        $this->assertContains('period_separator', $columnKeys);
    }

    public function test_service_info_table_data_formatting()
    {
        $facility = Facility::factory()->create();
        
        $service1 = FacilityService::factory()->create([
            'facility_id' => $facility->id,
            'service_type' => '介護サービス',
            'renewal_start_date' => '2023-01-01',
            'renewal_end_date' => '2023-12-31'
        ]);
        
        $service2 = FacilityService::factory()->create([
            'facility_id' => $facility->id,
            'service_type' => '医療サービス',
            'renewal_start_date' => '2023-06-01',
            'renewal_end_date' => '2024-05-31'
        ]);

        $services = collect([$service1, $service2]);
        
        // Prepare service data like the standardized table does
        $serviceData = $services->map(function($service) {
            return [
                'service_type' => $service->service_type ?? '',
                'renewal_start_date' => $service->renewal_start_date,
                'period_separator' => '〜',
                'renewal_end_date' => $service->renewal_end_date,
            ];
        })->toArray();

        $config = $this->configService->getTableConfig('service_info');
        $formattedData = $this->formatter->formatTableData($serviceData, $config);

        // Test service type formatting
        $this->assertEquals('介護サービス', $formattedData[0]['service_type']);
        $this->assertEquals('医療サービス', $formattedData[1]['service_type']);
        
        // Test date formatting
        $this->assertMatchesRegularExpression('/2023年\d{1,2}月\d{1,2}日/', $formattedData[0]['renewal_start_date']);
        $this->assertMatchesRegularExpression('/2023年\d{1,2}月\d{1,2}日/', $formattedData[0]['renewal_end_date']);
        
        // Test static separator
        $this->assertEquals('〜', $formattedData[0]['period_separator']);
        $this->assertEquals('〜', $formattedData[1]['period_separator']);
    }

    public function test_service_info_table_handles_empty_services()
    {
        $facility = Facility::factory()->create();
        $services = collect(); // Empty collection

        // Prepare empty service data like the standardized table does
        $serviceData = [];
        if ($services->isEmpty()) {
            $serviceData = [
                [
                    'service_type' => '',
                    'renewal_start_date' => null,
                    'period_separator' => '〜',
                    'renewal_end_date' => null,
                ]
            ];
        }

        $config = $this->configService->getTableConfig('service_info');
        $formattedData = $this->formatter->formatTableData($serviceData, $config);

        // Test that empty values return null (will be handled by view as empty)
        $this->assertNull($formattedData[0]['service_type']);
        $this->assertNull($formattedData[0]['renewal_start_date']);
        $this->assertNull($formattedData[0]['renewal_end_date']);
        
        // Test that static separator is still present
        $this->assertEquals('〜', $formattedData[0]['period_separator']);
    }

    public function test_service_info_standardized_table_view_can_be_rendered()
    {
        $facility = Facility::factory()->create();
        
        $service = FacilityService::factory()->create([
            'facility_id' => $facility->id,
            'service_type' => 'テストサービス',
            'renewal_start_date' => '2023-01-01',
            'renewal_end_date' => '2023-12-31'
        ]);

        $services = collect([$service]);

        $view = view('facilities.services.partials.standardized-table', compact('services'));
        $html = $view->render();

        // Verify the view renders without errors
        $this->assertStringContainsString('サービス種類', $html);
        $this->assertStringContainsString('service-info-table', $html);
        $this->assertStringContainsString('universal-table-wrapper', $html);
        $this->assertStringContainsString('テストサービス', $html);
    }
}
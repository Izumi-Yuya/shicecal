<?php

namespace Tests\Unit\Services;

use App\Models\FacilityService;
use App\Services\ServiceTableService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTableServiceTest extends TestCase
{
    use RefreshDatabase;

    private ServiceTableService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ServiceTableService();
    }

    /**
     * Test service preparation with empty collection
     * @test
     */
    public function it_prepares_empty_services_correctly()
    {
        $result = $this->service->prepareServicesForDisplay(collect());

        $this->assertFalse($result['hasData']);
        $this->assertCount(1, $result['services']);
        $this->assertNull($result['services']->first());
        $this->assertGreaterThanOrEqual(0, $result['templateRowsNeeded']);
    }

    /**
     * Test service preparation with valid services
     * @test
     */
    public function it_prepares_valid_services_correctly()
    {
        $services = collect([
            FacilityService::factory()->make(['service_type' => 'Test Service 1']),
            FacilityService::factory()->make(['service_type' => 'Test Service 2']),
        ]);

        $result = $this->service->prepareServicesForDisplay($services);

        $this->assertTrue($result['hasData']);
        $this->assertCount(2, $result['services']);
        $this->assertGreaterThanOrEqual(0, $result['templateRowsNeeded']);
    }

    /**
     * Test service data validation
     * @test
     */
    public function it_validates_service_data_correctly()
    {
        // Valid service
        $validService = FacilityService::factory()->make(['service_type' => 'Valid Service']);
        $this->assertTrue($this->service->hasValidServiceData($validService));

        // Invalid services
        $this->assertFalse($this->service->hasValidServiceData(null));
        $this->assertFalse($this->service->hasValidServiceData((object)[]));
        $this->assertFalse($this->service->hasValidServiceData(
            FacilityService::factory()->make(['service_type' => ''])
        ));
        $this->assertFalse($this->service->hasValidServiceData(
            FacilityService::factory()->make(['service_type' => '   '])
        ));
    }

    /**
     * Test service formatting for display
     * @test
     */
    public function it_formats_service_for_display_correctly()
    {
        // Valid service with dates
        $service = FacilityService::factory()->make([
            'service_type' => 'Test Service',
            'renewal_start_date' => Carbon::parse('2023-04-01'),
            'renewal_end_date' => Carbon::parse('2029-03-31'),
        ]);

        $result = $this->service->formatServiceForDisplay($service);

        $this->assertTrue($result['has_data']);
        $this->assertEquals('Test Service', $result['service_type']);
        $this->assertStringContainsString('2023年04月01日', $result['period']);
        $this->assertStringContainsString('2029年03月31日', $result['period']);

        // Invalid service
        $result = $this->service->formatServiceForDisplay(null);

        $this->assertFalse($result['has_data']);
        $this->assertEquals('未設定', $result['service_type']);
        $this->assertEquals('未設定', $result['period']);
    }

    /**
     * Test service period formatting
     * @test
     */
    public function it_formats_service_periods_correctly()
    {
        // Both dates present
        $service = FacilityService::factory()->make([
            'renewal_start_date' => Carbon::parse('2023-04-01'),
            'renewal_end_date' => Carbon::parse('2029-03-31'),
        ]);
        $result = $this->service->formatServiceForDisplay($service);
        $this->assertEquals('2023年04月01日 〜 2029年03月31日', $result['period']);

        // Only start date
        $service = FacilityService::factory()->make([
            'renewal_start_date' => Carbon::parse('2023-04-01'),
            'renewal_end_date' => null,
        ]);
        $result = $this->service->formatServiceForDisplay($service);
        $this->assertEquals('2023年04月01日 〜', $result['period']);

        // Only end date
        $service = FacilityService::factory()->make([
            'renewal_start_date' => null,
            'renewal_end_date' => Carbon::parse('2029-03-31'),
        ]);
        $result = $this->service->formatServiceForDisplay($service);
        $this->assertEquals('〜 2029年03月31日', $result['period']);

        // No dates
        $service = FacilityService::factory()->make([
            'renewal_start_date' => null,
            'renewal_end_date' => null,
        ]);
        $result = $this->service->formatServiceForDisplay($service);
        $this->assertEquals('未設定', $result['period']);
    }

    /**
     * Test configuration access
     * @test
     */
    public function it_provides_configuration_access()
    {
        $config = $this->service->getTableConfig();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('display', $config);
        $this->assertArrayHasKey('columns', $config);
        $this->assertArrayHasKey('styling', $config);

        $columns = $this->service->getColumnConfig();
        $this->assertIsArray($columns);
        $this->assertArrayHasKey('service_type', $columns);

        $styling = $this->service->getStylingConfig();
        $this->assertIsArray($styling);
        $this->assertArrayHasKey('empty_value_text', $styling);
    }

    /**
     * Test CSS generation
     * @test
     */
    public function it_generates_css_correctly()
    {
        $css = $this->service->generateColumnCss();
        
        $this->assertIsString($css);
        $this->assertStringContainsString('.service-info .col-service_type', $css);
        $this->assertStringContainsString('width:', $css);
        $this->assertStringContainsString('@media (max-width: 768px)', $css);
    }

    /**
     * Test service limit enforcement
     * @test
     */
    public function it_enforces_service_limits()
    {
        // Create more services than the configured maximum
        $services = collect();
        for ($i = 0; $i < 15; $i++) {
            $services->push(FacilityService::factory()->make([
                'service_type' => "Service {$i}"
            ]));
        }

        $result = $this->service->prepareServicesForDisplay($services);

        // Should be limited to max_services from config
        $maxServices = config('service-table.display.max_services', 10);
        $this->assertLessThanOrEqual($maxServices, $result['services']->count());
    }
}
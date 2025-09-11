<?php

namespace Tests\Unit\Services\ServiceTable;

use App\Models\FacilityService;
use App\Services\ServiceTable\Contracts\ServiceFormatterInterface;
use App\Services\ServiceTable\Contracts\ServiceTableConfigInterface;
use App\Services\ServiceTable\ServiceTableRenderer;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class ServiceTableRendererTest extends TestCase
{
    private ServiceTableRenderer $renderer;

    private ServiceTableConfigInterface $config;

    private ServiceFormatterInterface $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = Mockery::mock(ServiceTableConfigInterface::class);
        $this->formatter = Mockery::mock(ServiceFormatterInterface::class);
        $this->renderer = new ServiceTableRenderer($this->config, $this->formatter);
    }

    /** @test */
    public function it_prepares_empty_services_for_display(): void
    {
        // Arrange
        $services = collect();
        $this->config->shouldReceive('getMaxServices')->andReturn(5);
        $this->config->shouldReceive('shouldShowEmptyRows')->andReturn(true);

        // Act
        $result = $this->renderer->prepareServicesForDisplay($services);

        // Assert
        $this->assertFalse($result['hasData']);
        $this->assertEquals(4, $result['templateRowsNeeded']);
        $this->assertCount(1, $result['services']);
        $this->assertNull($result['services']->first());
    }

    /** @test */
    public function it_prepares_services_with_data_for_display(): void
    {
        // Arrange
        $services = collect([
            FacilityService::factory()->make(['service_type' => 'Service 1']),
            FacilityService::factory()->make(['service_type' => 'Service 2']),
        ]);
        $this->config->shouldReceive('getMaxServices')->andReturn(5);
        $this->config->shouldReceive('shouldShowEmptyRows')->andReturn(true);

        // Act
        $result = $this->renderer->prepareServicesForDisplay($services);

        // Assert
        $this->assertTrue($result['hasData']);
        $this->assertEquals(3, $result['templateRowsNeeded']);
        $this->assertCount(2, $result['services']);
    }

    /** @test */
    public function it_validates_service_data_correctly(): void
    {
        // Arrange & Act & Assert
        $validService = (object) ['service_type' => 'Valid Service'];
        $this->assertTrue($this->renderer->hasValidServiceData($validService));

        $emptyService = (object) ['service_type' => ''];
        $this->assertFalse($this->renderer->hasValidServiceData($emptyService));

        $nullService = null;
        $this->assertFalse($this->renderer->hasValidServiceData($nullService));
    }

    /** @test */
    public function it_formats_service_using_formatter(): void
    {
        // Arrange
        $service = FacilityService::factory()->make();
        $expectedFormat = ['formatted' => 'data'];
        $this->formatter->shouldReceive('format')->with($service)->andReturn($expectedFormat);

        // Act
        $result = $this->renderer->formatService($service);

        // Assert
        $this->assertEquals($expectedFormat, $result);
    }

    /** @test */
    public function it_handles_formatter_exceptions_gracefully(): void
    {
        // Arrange
        $service = FacilityService::factory()->make();
        $this->formatter->shouldReceive('format')
            ->with($service)
            ->andThrow(new \Exception('Formatting failed'));
        $this->formatter->shouldReceive('formatEmpty')
            ->andReturn(['empty' => 'data']);

        // Act
        $result = $this->renderer->formatService($service);

        // Assert
        $this->assertEquals(['empty' => 'data'], $result);
    }

    /** @test */
    public function it_handles_invalid_services_collection(): void
    {
        // Arrange
        $this->config->shouldReceive('getMaxServices')->andReturn(5);
        $this->config->shouldReceive('shouldShowEmptyRows')->andReturn(false);

        // Create collection with invalid service
        $services = collect(['invalid_service']);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->renderer->prepareServicesForDisplay($services);
    }

    /** @test */
    public function it_limits_services_to_max_configured(): void
    {
        // Arrange
        $services = collect([
            FacilityService::factory()->make(['service_type' => 'Service 1']),
            FacilityService::factory()->make(['service_type' => 'Service 2']),
            FacilityService::factory()->make(['service_type' => 'Service 3']),
        ]);
        $this->config->shouldReceive('getMaxServices')->andReturn(2);
        $this->config->shouldReceive('shouldShowEmptyRows')->andReturn(false);

        // Act
        $result = $this->renderer->prepareServicesForDisplay($services);

        // Assert
        $this->assertTrue($result['hasData']);
        $this->assertCount(2, $result['services']);
        $this->assertEquals(0, $result['templateRowsNeeded']);
    }

    /** @test */
    public function it_validates_service_with_missing_properties(): void
    {
        // Arrange & Act & Assert
        $serviceWithoutType = (object) ['other_property' => 'value'];
        $this->assertFalse($this->renderer->hasValidServiceData($serviceWithoutType));

        $nonObjectService = 'string_service';
        $this->assertFalse($this->renderer->hasValidServiceData($nonObjectService));

        $arrayService = ['service_type' => 'Array Service'];
        $this->assertFalse($this->renderer->hasValidServiceData($arrayService));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

<?php

namespace Tests\Unit\Services\ServiceTable;

use App\Services\ServiceTable\ServiceTableViewHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ServiceTableViewHelperTest extends TestCase
{
    private ServiceTableViewHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = new ServiceTableViewHelper;
    }

    /**
     * Test prepareServiceTableData with empty collection
     *
     * @test
     */
    public function it_prepares_empty_service_data_correctly()
    {
        $services = collect();
        $result = $this->helper->prepareServiceTableData($services);

        $this->assertEquals(0, $result['serviceCount']);
        $this->assertEquals(1, $result['displayCount']); // At least 1 for header
        $this->assertEquals(10, $result['emptyRowsCount']); // Default max - 0 services
        $this->assertFalse($result['hasServices']);
        $this->assertInstanceOf(Collection::class, $result['services']);
    }

    /**
     * Test prepareServiceTableData with services
     *
     * @test
     */
    public function it_prepares_service_data_with_services_correctly()
    {
        $services = collect([
            (object) ['service_type' => 'Service 1'],
            (object) ['service_type' => 'Service 2'],
        ]);

        $result = $this->helper->prepareServiceTableData($services, 5);

        $this->assertEquals(2, $result['serviceCount']);
        $this->assertEquals(2, $result['displayCount']);
        $this->assertEquals(3, $result['emptyRowsCount']); // 5 max - 2 services
        $this->assertTrue($result['hasServices']);
        $this->assertEquals(5, $result['maxServices']);
    }

    /**
     * Test formatServiceDate with valid date
     *
     * @test
     */
    public function it_formats_service_date_correctly()
    {
        $date = Carbon::parse('2023-04-15');
        $formatted = $this->helper->formatServiceDate($date);

        $this->assertEquals('2023年4月15日', $formatted);
    }

    /**
     * Test formatServiceDate with null date
     *
     * @test
     */
    public function it_handles_null_service_date()
    {
        $formatted = $this->helper->formatServiceDate(null);
        $this->assertEquals('', $formatted);
    }

    /**
     * Test getServiceRowClasses for first row
     *
     * @test
     */
    public function it_generates_correct_css_classes_for_first_row()
    {
        $classes = $this->helper->getServiceRowClasses(0);
        $this->assertStringContainsString('first-service-row', $classes);
    }

    /**
     * Test getServiceRowClasses for empty row
     *
     * @test
     */
    public function it_generates_correct_css_classes_for_empty_row()
    {
        $classes = $this->helper->getServiceRowClasses(1, true);
        $this->assertStringContainsString('template-row', $classes);
    }

    /**
     * Test getServiceRowClasses for regular row
     *
     * @test
     */
    public function it_generates_correct_css_classes_for_regular_row()
    {
        $classes = $this->helper->getServiceRowClasses(2);
        $this->assertIsString($classes);
        $this->assertStringNotContainsString('first-service-row', $classes);
        $this->assertStringNotContainsString('template-row', $classes);
    }

    /**
     * Test edge case with max services equal to service count
     *
     * @test
     */
    public function it_handles_edge_case_when_services_equal_max()
    {
        $services = collect([
            (object) ['service_type' => 'Service 1'],
            (object) ['service_type' => 'Service 2'],
        ]);

        $result = $this->helper->prepareServiceTableData($services, 2);

        $this->assertEquals(2, $result['serviceCount']);
        $this->assertEquals(0, $result['emptyRowsCount']);
        $this->assertTrue($result['hasServices']);
    }

    /**
     * Test edge case with more services than max
     *
     * @test
     */
    public function it_handles_edge_case_when_services_exceed_max()
    {
        $services = collect([
            (object) ['service_type' => 'Service 1'],
            (object) ['service_type' => 'Service 2'],
            (object) ['service_type' => 'Service 3'],
        ]);

        $result = $this->helper->prepareServiceTableData($services, 2);

        $this->assertEquals(3, $result['serviceCount']);
        $this->assertEquals(0, $result['emptyRowsCount']); // No empty rows when exceeding max
        $this->assertTrue($result['hasServices']);
    }
}

<?php

namespace Tests\Unit\Services;

use App\Services\FacilityService;
use PHPUnit\Framework\TestCase;

class LandCalculationServiceTest extends TestCase
{
    private LandCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LandCalculationService();
    }

    /** @test */
    public function it_calculates_unit_price_correctly()
    {
        // Test normal calculation
        $unitPrice = $this->service->calculateUnitPrice(10000000, 100.0);
        $this->assertEquals(100000, $unitPrice);

        // Test with decimal values
        $unitPrice = $this->service->calculateUnitPrice(15000000, 89.05);
        $this->assertEquals(168445, $unitPrice); // Rounded

        // Test with small area
        $unitPrice = $this->service->calculateUnitPrice(5000000, 25.5);
        $this->assertEquals(196078, $unitPrice);
    }

    /** @test */
    public function it_returns_null_for_invalid_unit_price_inputs()
    {
        // Zero purchase price
        $this->assertNull($this->service->calculateUnitPrice(0, 100.0));

        // Negative purchase price
        $this->assertNull($this->service->calculateUnitPrice(-1000000, 100.0));

        // Zero area
        $this->assertNull($this->service->calculateUnitPrice(10000000, 0));

        // Negative area
        $this->assertNull($this->service->calculateUnitPrice(10000000, -50.0));
    }

    /** @test */
    public function it_calculates_contract_period_correctly()
    {
        // Test exact years
        $period = $this->service->calculateContractPeriod('2020-01-01', '2025-01-01');
        $this->assertEquals('5年', $period);

        // Test years and months
        $period = $this->service->calculateContractPeriod('2020-01-01', '2025-06-01');
        $this->assertEquals('5年5ヶ月', $period);

        // Test only months
        $period = $this->service->calculateContractPeriod('2020-01-01', '2020-07-01');
        $this->assertEquals('6ヶ月', $period);

        // Test less than a month
        $period = $this->service->calculateContractPeriod('2020-01-01', '2020-01-15');
        $this->assertEquals('0ヶ月', $period);

        // Test complex period
        $period = $this->service->calculateContractPeriod('2020-03-15', '2023-11-20');
        $this->assertEquals('3年8ヶ月', $period);
    }

    /** @test */
    public function it_returns_empty_string_for_invalid_contract_period_inputs()
    {
        // End date before start date
        $this->assertEquals('', $this->service->calculateContractPeriod('2025-01-01', '2020-01-01'));

        // Same dates
        $this->assertEquals('', $this->service->calculateContractPeriod('2020-01-01', '2020-01-01'));

        // Invalid date format
        $this->assertEquals('', $this->service->calculateContractPeriod('invalid-date', '2025-01-01'));
        $this->assertEquals('', $this->service->calculateContractPeriod('2020-01-01', 'invalid-date'));
    }

    /** @test */
    public function it_formats_currency_with_commas()
    {
        // Test large numbers
        $this->assertEquals('10,000,000', $this->service->formatCurrency(10000000));
        $this->assertEquals('1,234,567', $this->service->formatCurrency(1234567));
        $this->assertEquals('500,000', $this->service->formatCurrency(500000));

        // Test small numbers
        $this->assertEquals('1,000', $this->service->formatCurrency(1000));
        $this->assertEquals('100', $this->service->formatCurrency(100));
        $this->assertEquals('1', $this->service->formatCurrency(1));

        // Test zero
        $this->assertEquals('', $this->service->formatCurrency(0));

        // Test decimal (should be rounded to integer)
        $this->assertEquals('1,235', $this->service->formatCurrency(1234.56));
    }

    /** @test */
    public function it_formats_area_with_units()
    {
        // Test sqm formatting
        $this->assertEquals('290.00㎡', $this->service->formatArea(290.0, 'sqm'));
        $this->assertEquals('89.05㎡', $this->service->formatArea(89.05, 'sqm'));
        $this->assertEquals('1,234.56㎡', $this->service->formatArea(1234.56, 'sqm'));

        // Test tsubo formatting
        $this->assertEquals('89.05坪', $this->service->formatArea(89.05, 'tsubo'));
        $this->assertEquals('100.00坪', $this->service->formatArea(100.0, 'tsubo'));
        $this->assertEquals('1,234.56坪', $this->service->formatArea(1234.56, 'tsubo'));

        // Test zero area
        $this->assertEquals('', $this->service->formatArea(0, 'sqm'));
        $this->assertEquals('', $this->service->formatArea(0, 'tsubo'));
    }

    /** @test */
    public function it_throws_exception_for_invalid_area_unit()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid unit: invalid. Use 'sqm' or 'tsubo'.");

        $this->service->formatArea(100.0, 'invalid');
    }

    /** @test */
    public function it_formats_japanese_dates()
    {
        // Test various date formats
        $this->assertEquals('2000年12月12日', $this->service->formatJapaneseDate('2000-12-12'));
        $this->assertEquals('2025年1月1日', $this->service->formatJapaneseDate('2025-01-01'));
        $this->assertEquals('2023年6月15日', $this->service->formatJapaneseDate('2023-06-15'));

        // Test with time (should ignore time part)
        $this->assertEquals('2020年3月5日', $this->service->formatJapaneseDate('2020-03-05 14:30:00'));

        // Test empty input
        $this->assertEquals('', $this->service->formatJapaneseDate(''));

        // Test invalid date
        $this->assertEquals('', $this->service->formatJapaneseDate('invalid-date'));
        $this->assertEquals('', $this->service->formatJapaneseDate('2020-13-01')); // Invalid month
    }

    /** @test */
    public function it_converts_full_width_to_half_width_numbers()
    {
        // Test full-width numbers
        $this->assertEquals('1234567890', $this->service->convertToHalfWidth('１２３４５６７８９０'));

        // Test mixed content
        $this->assertEquals('abc123def', $this->service->convertToHalfWidth('abc１２３def'));

        // Test already half-width
        $this->assertEquals('1234567890', $this->service->convertToHalfWidth('1234567890'));

        // Test empty string
        $this->assertEquals('', $this->service->convertToHalfWidth(''));
    }

    /** @test */
    public function it_formats_postal_codes()
    {
        // Test valid formats
        $this->assertEquals('123-4567', $this->service->formatPostalCode('1234567'));
        $this->assertEquals('123-4567', $this->service->formatPostalCode('123-4567'));
        $this->assertEquals('123-4567', $this->service->formatPostalCode('１２３４５６７')); // Full-width

        // Test with extra characters
        $this->assertEquals('123-4567', $this->service->formatPostalCode('123-4567 '));
        $this->assertEquals('123-4567', $this->service->formatPostalCode('〒123-4567'));

        // Test invalid formats
        $this->assertNull($this->service->formatPostalCode('12345')); // Too short
        $this->assertNull($this->service->formatPostalCode('12345678')); // Too long
        $this->assertNull($this->service->formatPostalCode('abc-defg')); // Non-numeric
        $this->assertNull($this->service->formatPostalCode('')); // Empty
    }

    /** @test */
    public function it_formats_phone_numbers()
    {
        // Test various valid formats
        $this->assertEquals('03-1234-5678', $this->service->formatPhoneNumber('0312345678'));
        $this->assertEquals('03-1234-5678', $this->service->formatPhoneNumber('03-1234-5678'));
        $this->assertEquals('090-1234-5678', $this->service->formatPhoneNumber('09012345678'));
        $this->assertEquals('0120-123-4567', $this->service->formatPhoneNumber('01201234567'));

        // Test with full-width numbers
        $this->assertEquals('03-1234-5678', $this->service->formatPhoneNumber('０３１２３４５６７８'));

        // Test with extra characters
        $this->assertEquals('03-1234-5678', $this->service->formatPhoneNumber('03-1234-5678 '));

        // Test invalid formats
        $this->assertNull($this->service->formatPhoneNumber('123')); // Too short
        $this->assertNull($this->service->formatPhoneNumber('123456789012345')); // Too long
        $this->assertNull($this->service->formatPhoneNumber('abc-defg-hijk')); // Non-numeric
        $this->assertNull($this->service->formatPhoneNumber('')); // Empty
    }
}

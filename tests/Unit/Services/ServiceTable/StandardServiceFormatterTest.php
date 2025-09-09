<?php

namespace Tests\Unit\Services\ServiceTable;

use App\Services\ServiceTable\Formatters\StandardServiceFormatter;
use Carbon\Carbon;
use Tests\TestCase;

class StandardServiceFormatterTest extends TestCase
{
    private StandardServiceFormatter $formatter;
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = [
            'styling' => [
                'empty_value_text' => '未設定'
            ],
            'display' => [
                'date_format' => 'Y年m月d日'
            ],
            'validation' => [
                'max_service_name_length' => 100
            ]
        ];
        
        $this->formatter = new StandardServiceFormatter($this->config);
    }

    /**
     * Test formatting service with complete data
     * @test
     */
    public function it_formats_service_with_complete_data()
    {
        $service = (object) [
            'service_type' => '介護保険サービス',
            'renewal_start_date' => Carbon::parse('2023-04-01'),
            'renewal_end_date' => Carbon::parse('2029-03-31'),
        ];

        $result = $this->formatter->format($service);

        $this->assertEquals('介護保険サービス', $result['service_type']);
        $this->assertEquals('2023年04月01日 〜 2029年03月31日', $result['period']);
        $this->assertTrue($result['has_data']);
        $this->assertEquals('service-standard', $result['css_class']);
    }

    /**
     * Test formatting service with only start date
     * @test
     */
    public function it_formats_service_with_only_start_date()
    {
        $service = (object) [
            'service_type' => '障害福祉サービス',
            'renewal_start_date' => Carbon::parse('2023-04-01'),
            'renewal_end_date' => null,
        ];

        $result = $this->formatter->format($service);

        $this->assertEquals('2023年04月01日 〜', $result['period']);
    }

    /**
     * Test formatting service with only end date
     * @test
     */
    public function it_formats_service_with_only_end_date()
    {
        $service = (object) [
            'service_type' => '地域密着型サービス',
            'renewal_start_date' => null,
            'renewal_end_date' => Carbon::parse('2029-03-31'),
        ];

        $result = $this->formatter->format($service);

        $this->assertEquals('〜 2029年03月31日', $result['period']);
    }

    /**
     * Test formatting service with no dates
     * @test
     */
    public function it_formats_service_with_no_dates()
    {
        $service = (object) [
            'service_type' => 'テストサービス',
            'renewal_start_date' => null,
            'renewal_end_date' => null,
        ];

        $result = $this->formatter->format($service);

        $this->assertEquals('未設定', $result['period']);
    }

    /**
     * Test formatting null service
     * @test
     */
    public function it_formats_null_service()
    {
        $result = $this->formatter->format(null);

        $this->assertEquals('未設定', $result['service_type']);
        $this->assertEquals('未設定', $result['period']);
        $this->assertFalse($result['has_data']);
        $this->assertEquals('service-empty', $result['css_class']);
    }

    /**
     * Test formatting service with empty service type
     * @test
     */
    public function it_formats_service_with_empty_service_type()
    {
        $service = (object) [
            'service_type' => '',
            'renewal_start_date' => Carbon::parse('2023-04-01'),
            'renewal_end_date' => Carbon::parse('2029-03-31'),
        ];

        $result = $this->formatter->format($service);

        $this->assertEquals('未設定', $result['service_type']);
        $this->assertFalse($result['has_data']);
    }

    /**
     * Test text sanitization
     * @test
     */
    public function it_sanitizes_service_type_text()
    {
        $service = (object) [
            'service_type' => '<script>alert("xss")</script>介護保険サービス',
            'renewal_start_date' => Carbon::parse('2023-04-01'),
            'renewal_end_date' => Carbon::parse('2029-03-31'),
        ];

        $result = $this->formatter->format($service);

        $this->assertEquals('alert("xss")介護保険サービス', $result['service_type']);
        $this->assertStringNotContainsString('<script>', $result['service_type']);
    }

    /**
     * Test long text truncation
     * @test
     */
    public function it_truncates_long_service_type()
    {
        $longServiceType = str_repeat('あ', 150); // 150 characters
        
        $service = (object) [
            'service_type' => $longServiceType,
            'renewal_start_date' => null,
            'renewal_end_date' => null,
        ];

        $result = $this->formatter->format($service);

        $this->assertLessThanOrEqual(103, mb_strlen($result['service_type'])); // 100 + '...'
        $this->assertStringEndsWith('...', $result['service_type']);
    }

    /**
     * Test canFormat method
     * @test
     */
    public function it_correctly_identifies_formattable_services()
    {
        // Valid service
        $validService = (object) ['service_type' => '介護保険サービス'];
        $this->assertTrue($this->formatter->canFormat($validService));

        // Invalid services
        $this->assertFalse($this->formatter->canFormat(null));
        $this->assertFalse($this->formatter->canFormat((object) []));
        $this->assertFalse($this->formatter->canFormat((object) ['service_type' => '']));
        $this->assertFalse($this->formatter->canFormat((object) ['service_type' => '   ']));
    }
}
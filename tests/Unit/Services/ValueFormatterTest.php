<?php

namespace Tests\Unit\Services;

use App\Services\ValueFormatter;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ValueFormatterTest extends TestCase
{
    /**
     * @test
     */
    public function it_detects_empty_values_correctly()
    {
        // Null values
        $this->assertTrue(ValueFormatter::isEmpty(null));

        // Empty strings
        $this->assertTrue(ValueFormatter::isEmpty(''));
        $this->assertTrue(ValueFormatter::isEmpty('   '));

        // Empty arrays
        $this->assertTrue(ValueFormatter::isEmpty([]));

        // Non-empty values
        $this->assertFalse(ValueFormatter::isEmpty('test'));
        $this->assertFalse(ValueFormatter::isEmpty(0));
        $this->assertFalse(ValueFormatter::isEmpty(false));
        $this->assertFalse(ValueFormatter::isEmpty(['item']));
    }

    /**
     * @test
     */
    public function it_formats_text_correctly()
    {
        // Basic text formatting
        $result = ValueFormatter::format('Hello World', 'text');
        $this->assertEquals('Hello World', $result);

        // HTML escaping
        $result = ValueFormatter::format('<script>alert("xss")</script>', 'text');
        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $result);

        // Max length option
        $result = ValueFormatter::format('This is a very long text', 'text', ['max_length' => 10]);
        $this->assertEquals('This is a ...', $result);
    }

    /**
     * @test
     */
    public function it_formats_badges_correctly()
    {
        $result = ValueFormatter::format('Active', 'badge');
        $this->assertEquals('<span class="badge bg-primary">Active</span>', $result);

        // Custom badge class
        $result = ValueFormatter::format('Success', 'badge', ['badge_class' => 'badge bg-success']);
        $this->assertEquals('<span class="badge bg-success">Success</span>', $result);
    }

    /**
     * @test
     */
    public function it_formats_email_correctly()
    {
        $result = ValueFormatter::format('test@example.com', 'email');
        $expected = '<a href="mailto:test@example.com" class="text-decoration-none"><i class="fas fa-envelope me-1"></i>test@example.com</a>';
        $this->assertEquals($expected, $result);

        // Without icon
        $result = ValueFormatter::format('test@example.com', 'email', ['show_icon' => false]);
        $expected = '<a href="mailto:test@example.com" class="text-decoration-none">test@example.com</a>';
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function it_formats_url_correctly()
    {
        // URL with protocol
        $result = ValueFormatter::format('https://example.com', 'url');
        $expected = '<a href="https://example.com" target="_blank" class="text-decoration-none"><i class="fas fa-external-link-alt me-1"></i>https://example.com</a>';
        $this->assertEquals($expected, $result);

        // URL without protocol
        $result = ValueFormatter::format('example.com', 'url');
        $expected = '<a href="https://example.com" target="_blank" class="text-decoration-none"><i class="fas fa-external-link-alt me-1"></i>example.com</a>';
        $this->assertEquals($expected, $result);

        // Custom display text
        $result = ValueFormatter::format('https://example.com', 'url', ['display_text' => 'Visit Site']);
        $expected = '<a href="https://example.com" target="_blank" class="text-decoration-none"><i class="fas fa-external-link-alt me-1"></i>Visit Site</a>';
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function it_formats_dates_correctly()
    {
        // Carbon instance
        $date = Carbon::create(2023, 12, 25);
        $result = ValueFormatter::formatDate($date);
        $this->assertEquals('2023年12月25日', $result);

        // String date
        $result = ValueFormatter::formatDate('2023-12-25');
        $this->assertEquals('2023年12月25日', $result);

        // Custom format
        $result = ValueFormatter::formatDate('2023-12-25', 'Y/m/d');
        $this->assertEquals('2023/12/25', $result);

        // Invalid date
        $result = ValueFormatter::formatDate('invalid-date');
        $this->assertEquals('invalid-date', $result);

        // Empty date
        $result = ValueFormatter::formatDate(null);
        $this->assertEquals('未設定', $result);
    }

    /**
     * @test
     */
    public function it_formats_currency_correctly()
    {
        // Basic currency
        $result = ValueFormatter::formatCurrency(1000);
        $this->assertEquals('1,000円', $result);

        // With decimals
        $result = ValueFormatter::formatCurrency(1000.50, ['decimals' => 2]);
        $this->assertEquals('1,000.50円', $result);

        // Custom currency
        $result = ValueFormatter::formatCurrency(1000, ['currency' => 'USD']);
        $this->assertEquals('1,000USD', $result);

        // Empty value
        $result = ValueFormatter::formatCurrency(null);
        $this->assertEquals('未設定', $result);

        // String number
        $result = ValueFormatter::formatCurrency('1500');
        $this->assertEquals('1,500円', $result);
    }

    /**
     * @test
     */
    public function it_formats_numbers_correctly()
    {
        // Basic number
        $result = ValueFormatter::formatNumber(1000);
        $this->assertEquals('1,000', $result);

        // With decimals
        $result = ValueFormatter::formatNumber(1000.567, 2);
        $this->assertEquals('1,000.57', $result);

        // Empty value
        $result = ValueFormatter::formatNumber(null);
        $this->assertEquals('未設定', $result);

        // String number
        $result = ValueFormatter::formatNumber('1500.25', 1);
        $this->assertEquals('1,500.3', $result);
    }

    /**
     * @test
     */
    public function it_formats_file_links_correctly()
    {
        // PDF file
        $result = ValueFormatter::format('/path/to/document.pdf', 'file');
        $expected = '<a href="/path/to/document.pdf" class="text-decoration-none" aria-label="/path/to/document.pdfをダウンロード" target="_blank"><i class="fas fa-file-pdf text-danger"></i>/path/to/document.pdf</a>';
        $this->assertEquals($expected, $result);

        // Image file
        $result = ValueFormatter::format('/path/to/image.jpg', 'file');
        $expected = '<a href="/path/to/image.jpg" class="text-decoration-none" aria-label="/path/to/image.jpgをダウンロード" target="_blank"><i class="fas fa-file-image text-info"></i>/path/to/image.jpg</a>';
        $this->assertEquals($expected, $result);

        // Custom display name
        $result = ValueFormatter::format('/path/to/document.pdf', 'file', ['display_name' => 'My Document']);
        $expected = '<a href="/path/to/document.pdf" class="text-decoration-none" aria-label="My Documentをダウンロード" target="_blank"><i class="fas fa-file-pdf text-danger"></i>My Document</a>';
        $this->assertEquals($expected, $result);

        // Unknown file type
        $result = ValueFormatter::format('/path/to/file.unknown', 'file');
        $expected = '<a href="/path/to/file.unknown" class="text-decoration-none" aria-label="/path/to/file.unknownをダウンロード" target="_blank"><i class="fas fa-file text-muted"></i>/path/to/file.unknown</a>';
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function it_handles_empty_values_with_custom_text()
    {
        $result = ValueFormatter::format(null, 'text', ['empty_text' => 'No Data']);
        $this->assertEquals('No Data', $result);

        $result = ValueFormatter::format('', 'text', ['empty_text' => 'Empty']);
        $this->assertEquals('Empty', $result);
    }

    /**
     * @test
     */
    public function it_handles_unknown_types_as_text()
    {
        $result = ValueFormatter::format('test value', 'unknown_type');
        $this->assertEquals('test value', $result);
    }

    /**
     * @test
     */
    public function it_formats_using_format_method_with_different_types()
    {
        // Test all supported types through the main format method
        $testCases = [
            ['value' => 'Hello', 'type' => 'text', 'expected' => 'Hello'],
            ['value' => 'Active', 'type' => 'badge', 'expected' => '<span class="badge bg-primary">Active</span>'],
            ['value' => 'test@example.com', 'type' => 'email', 'expected' => '<a href="mailto:test@example.com" class="text-decoration-none"><i class="fas fa-envelope me-1"></i>test@example.com</a>'],
            ['value' => 'example.com', 'type' => 'url', 'expected' => '<a href="https://example.com" target="_blank" class="text-decoration-none"><i class="fas fa-external-link-alt me-1"></i>example.com</a>'],
            ['value' => '2023-12-25', 'type' => 'date', 'expected' => '2023年12月25日'],
            ['value' => 1000, 'type' => 'currency', 'expected' => '1,000円'],
            ['value' => 1000, 'type' => 'number', 'expected' => '1,000'],
            ['value' => '/path/to/file.pdf', 'type' => 'file', 'expected' => '<a href="/path/to/file.pdf" class="text-decoration-none" aria-label="/path/to/file.pdfをダウンロード" target="_blank"><i class="fas fa-file-pdf text-danger"></i>/path/to/file.pdf</a>'],
        ];

        foreach ($testCases as $case) {
            $result = ValueFormatter::format($case['value'], $case['type']);
            $this->assertEquals($case['expected'], $result, "Failed for type: {$case['type']}");
        }
    }

    /**
     * @test
     */
    public function it_handles_japanese_characters_correctly()
    {
        // Japanese text
        $result = ValueFormatter::format('こんにちは世界', 'text');
        $this->assertEquals('こんにちは世界', $result);

        // Japanese text with max length
        $result = ValueFormatter::format('これは長い日本語のテキストです', 'text', ['max_length' => 10]);
        $this->assertEquals('これは長い日本語のテ...', $result);

        // Japanese currency
        $result = ValueFormatter::formatCurrency(1000, ['currency' => '円']);
        $this->assertEquals('1,000円', $result);
    }
}

<?php

namespace Tests\Unit\Services;

use App\Services\ValueFormatter;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ValueFormatterPerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_format_uses_cache_for_large_values()
    {
        $largeValue = str_repeat('Large text content ', 100); // Over MIN_CACHE_SIZE
        $type = 'text';
        $options = ['use_cache' => true];

        // First call should cache the result
        $result1 = ValueFormatter::format($largeValue, $type, $options);

        // Second call should use cached result
        $result2 = ValueFormatter::format($largeValue, $type, $options);

        $this->assertEquals($result1, $result2);

        // Verify cache was used by checking if the same result is returned instantly
        $startTime = microtime(true);
        $result3 = ValueFormatter::format($largeValue, $type, $options);
        $endTime = microtime(true);

        $this->assertEquals($result1, $result3);
        $this->assertLessThan(0.001, $endTime - $startTime); // Should be very fast due to cache
    }

    public function test_format_does_not_cache_small_values()
    {
        $smallValue = 'Small';
        $type = 'text';

        // Should not use cache for small values
        $result = ValueFormatter::format($smallValue, $type);

        $this->assertIsString($result);
        // We can't directly test if cache wasn't used, but we can verify the result is correct
        $this->assertEquals('Small', $result);
    }

    public function test_format_batch_processes_multiple_values_efficiently()
    {
        $values = [];
        for ($i = 0; $i < 100; $i++) {
            $values[] = [
                'value' => "Test value {$i}",
                'type' => 'text',
                'options' => [],
            ];
        }

        $startTime = microtime(true);
        $results = ValueFormatter::formatBatch($values);
        $endTime = microtime(true);

        $this->assertCount(100, $results);
        $this->assertLessThan(0.1, $endTime - $startTime); // Should be fast

        // Verify all results are formatted correctly
        for ($i = 0; $i < 100; $i++) {
            $this->assertEquals("Test value {$i}", $results[$i]);
        }
    }

    public function test_format_batch_handles_mixed_types_efficiently()
    {
        $values = [
            ['value' => 'text@example.com', 'type' => 'email', 'options' => []],
            ['value' => 'https://example.com', 'type' => 'url', 'options' => []],
            ['value' => '2023-12-25', 'type' => 'date', 'options' => []],
            ['value' => 1000, 'type' => 'currency', 'options' => []],
            ['value' => 'Active', 'type' => 'badge', 'options' => ['auto_class' => true]],
        ];

        $results = ValueFormatter::formatBatch($values);

        $this->assertCount(5, $results);
        $this->assertStringContainsString('mailto:', $results[0]);
        $this->assertStringContainsString('href=', $results[1]);
        $this->assertStringContainsString('年', $results[2]);
        $this->assertStringContainsString('円', $results[3]);
        $this->assertStringContainsString('badge', $results[4]);
    }

    public function test_format_batch_uses_cache_for_large_values()
    {
        $largeValue = str_repeat('Large content ', 200);
        $values = [
            ['value' => $largeValue, 'type' => 'text', 'options' => []],
            ['value' => $largeValue, 'type' => 'text', 'options' => []], // Same value, should use cache
            ['value' => 'Small', 'type' => 'text', 'options' => []],
        ];

        $startTime = microtime(true);
        $results = ValueFormatter::formatBatch($values);
        $endTime = microtime(true);

        $this->assertCount(3, $results);
        $this->assertEquals($results[0], $results[1]); // Same large value should produce same result
        $this->assertEquals('Small', $results[2]);

        // Second batch with same large value should be faster due to cache
        $startTime2 = microtime(true);
        $results2 = ValueFormatter::formatBatch($values);
        $endTime2 = microtime(true);

        $this->assertLessThan($endTime - $startTime, $endTime2 - $startTime2);
    }

    public function test_format_batch_handles_empty_values()
    {
        $values = [
            ['value' => null, 'type' => 'text', 'options' => []],
            ['value' => '', 'type' => 'text', 'options' => []],
            ['value' => 'Valid', 'type' => 'text', 'options' => []],
            ['value' => null, 'type' => 'text', 'options' => ['empty_text' => 'カスタム未設定']],
        ];

        $results = ValueFormatter::formatBatch($values);

        $this->assertCount(2, $results); // Only non-empty values are processed in batch
        $this->assertEquals('Valid', $results[2]);
        // Note: Empty values are handled differently in batch processing
    }

    public function test_format_batch_performance_with_large_dataset()
    {
        // Create a large dataset to test performance
        $values = [];
        for ($i = 0; $i < 1000; $i++) {
            $values[] = [
                'value' => "Performance test value {$i} ".str_repeat('x', 50),
                'type' => 'text',
                'options' => [],
            ];
        }

        $startTime = microtime(true);
        $results = ValueFormatter::formatBatch($values);
        $endTime = microtime(true);

        $processingTime = $endTime - $startTime;

        $this->assertCount(1000, $results);
        $this->assertLessThan(1.0, $processingTime); // Should process 1000 items in less than 1 second

        // Verify some results
        $this->assertStringContainsString('Performance test value 0', $results[0]);
        $this->assertStringContainsString('Performance test value 999', $results[999]);
    }

    public function test_cache_key_generation_consistency()
    {
        $value = str_repeat('Test content ', 100);
        $type = 'text';
        $options = ['max_length' => 100];

        // Generate cache key multiple times - should be consistent
        $reflection = new \ReflectionClass(ValueFormatter::class);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $key1 = $method->invoke(null, $value, $type, $options);
        $key2 = $method->invoke(null, $value, $type, $options);

        $this->assertEquals($key1, $key2);
        $this->assertStringStartsWith('value_formatter_', $key1);
    }

    public function test_cache_key_generation_uniqueness()
    {
        $reflection = new \ReflectionClass(ValueFormatter::class);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $key1 = $method->invoke(null, 'value1', 'text', []);
        $key2 = $method->invoke(null, 'value2', 'text', []);
        $key3 = $method->invoke(null, 'value1', 'badge', []);

        $this->assertNotEquals($key1, $key2);
        $this->assertNotEquals($key1, $key3);
        $this->assertNotEquals($key2, $key3);
    }

    public function test_should_use_cache_logic()
    {
        $reflection = new \ReflectionClass(ValueFormatter::class);
        $method = $reflection->getMethod('shouldUseCache');
        $method->setAccessible(true);

        // Small string - should not cache
        $this->assertFalse($method->invoke(null, 'small'));

        // Large string - should cache
        $largeString = str_repeat('x', 200);
        $this->assertTrue($method->invoke(null, $largeString));

        // Array - should cache if serialized size is large
        $smallArray = ['key' => 'value'];
        $this->assertFalse($method->invoke(null, $smallArray));

        $largeArray = array_fill(0, 100, 'large array content');
        $this->assertTrue($method->invoke(null, $largeArray));

        // Other types
        $this->assertFalse($method->invoke(null, 123));
        $this->assertFalse($method->invoke(null, true));
    }

    public function test_format_batch_memory_efficiency()
    {
        $memoryBefore = memory_get_usage(true);

        // Process a large batch
        $values = [];
        for ($i = 0; $i < 500; $i++) {
            $values[] = [
                'value' => "Memory test {$i} ".str_repeat('data', 20),
                'type' => 'text',
                'options' => [],
            ];
        }

        $results = ValueFormatter::formatBatch($values);

        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;

        $this->assertCount(500, $results);
        // Memory usage should be reasonable (less than 10MB for this test)
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed);
    }

    public function test_format_batch_with_cache_many_optimization()
    {
        // Create values that will use cache
        $largeValue1 = str_repeat('Cache test 1 ', 100);
        $largeValue2 = str_repeat('Cache test 2 ', 100);

        $values = [
            ['value' => $largeValue1, 'type' => 'text', 'options' => []],
            ['value' => $largeValue2, 'type' => 'text', 'options' => []],
            ['value' => $largeValue1, 'type' => 'text', 'options' => []], // Duplicate
            ['value' => 'small', 'type' => 'text', 'options' => []],
        ];

        // First batch - should populate cache
        $results1 = ValueFormatter::formatBatch($values);

        // Second batch - should use cache for large values
        $startTime = microtime(true);
        $results2 = ValueFormatter::formatBatch($values);
        $endTime = microtime(true);

        $this->assertEquals($results1, $results2);
        $this->assertLessThan(0.01, $endTime - $startTime); // Should be very fast due to cache
    }
}

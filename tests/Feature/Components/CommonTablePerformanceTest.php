<?php

namespace Tests\Feature\Components;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Facility;
use App\Models\User;
use App\Services\CommonTablePerformanceOptimizer;

/**
 * CommonTableパフォーマンステスト
 * 
 * レンダリング性能、メモリ使用量、キャッシュ効果のテスト
 * 要件: 設計書のパフォーマンス要件
 */
class CommonTablePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $facilities;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        // パフォーマンステスト用のファシリティを作成
        $this->facilities = Facility::factory()->count(10)->create();
    }

    /**
     * @test
     * 小規模データのレンダリング性能テスト
     */
    public function test_performance_小規模データレンダリング性能()
    {
        $smallData = $this->generateTestData(10);

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $view = View::make('components.common-table', [
            'data' => $smallData,
            'title' => '小規模データテスト'
        ]);

        $rendered = $view->render();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $renderTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;

        // 小規模データの性能要件
        $this->assertLessThan(0.5, $renderTime, '小規模データのレンダリング時間が0.5秒を超えています');
        $this->assertLessThan(5 * 1024 * 1024, $memoryUsage, '小規模データのメモリ使用量が5MBを超えています');

        // データが正しく表示されることを確認
        $this->assertStringContainsString('項目0', $rendered);
        $this->assertStringContainsString('項目9', $rendered);

        Log::info('Small Data Performance Test', [
            'rows' => 10,
            'render_time' => $renderTime,
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2)
        ]);
    }

    /**
     * @test
     * 中規模データのレンダリング性能テスト
     */
    public function test_performance_中規模データレンダリング性能()
    {
        $mediumData = $this->generateTestData(50);

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $view = View::make('components.common-table', [
            'data' => $mediumData,
            'title' => '中規模データテスト'
        ]);

        $rendered = $view->render();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $renderTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;

        // 中規模データの性能要件
        $this->assertLessThan(1.5, $renderTime, '中規模データのレンダリング時間が1.5秒を超えています');
        $this->assertLessThan(15 * 1024 * 1024, $memoryUsage, '中規模データのメモリ使用量が15MBを超えています');

        // データが正しく表示されることを確認
        $this->assertStringContainsString('項目0', $rendered);
        $this->assertStringContainsString('項目49', $rendered);

        Log::info('Medium Data Performance Test', [
            'rows' => 50,
            'render_time' => $renderTime,
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2)
        ]);
    }

    /**
     * @test
     * 大規模データのレンダリング性能テスト
     */
    public function test_performance_大規模データレンダリング性能()
    {
        $largeData = $this->generateTestData(100);

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $view = View::make('components.common-table', [
            'data' => $largeData,
            'title' => '大規模データテスト'
        ]);

        $rendered = $view->render();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $renderTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;

        // 大規模データの性能要件
        $this->assertLessThan(3.0, $renderTime, '大規模データのレンダリング時間が3秒を超えています');
        $this->assertLessThan(30 * 1024 * 1024, $memoryUsage, '大規模データのメモリ使用量が30MBを超えています');

        // データが正しく表示されることを確認
        $this->assertStringContainsString('項目0', $rendered);
        $this->assertStringContainsString('項目99', $rendered);

        Log::info('Large Data Performance Test', [
            'rows' => 100,
            'render_time' => $renderTime,
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2)
        ]);
    }

    /**
     * @test
     * 超大規模データのレンダリング性能テスト
     */
    public function test_performance_超大規模データレンダリング性能()
    {
        $extraLargeData = $this->generateTestData(200);

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $view = View::make('components.common-table', [
            'data' => $extraLargeData,
            'title' => '超大規模データテスト'
        ]);

        $rendered = $view->render();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $renderTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;

        // 超大規模データの性能要件
        $this->assertLessThan(5.0, $renderTime, '超大規模データのレンダリング時間が5秒を超えています');
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsage, '超大規模データのメモリ使用量が50MBを超えています');

        // データが正しく表示されることを確認
        $this->assertStringContainsString('項目0', $rendered);
        $this->assertStringContainsString('項目199', $rendered);

        Log::info('Extra Large Data Performance Test', [
            'rows' => 200,
            'render_time' => $renderTime,
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2)
        ]);
    }

    /**
     * @test
     * 複雑なデータ構造のレンダリング性能テスト
     */
    public function test_performance_複雑なデータ構造レンダリング性能()
    {
        $complexData = $this->generateComplexTestData(50);

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $view = View::make('components.common-table', [
            'data' => $complexData,
            'title' => '複雑なデータ構造テスト'
        ]);

        $rendered = $view->render();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $renderTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;

        // 複雑なデータ構造の性能要件
        $this->assertLessThan(2.0, $renderTime, '複雑なデータ構造のレンダリング時間が2秒を超えています');
        $this->assertLessThan(20 * 1024 * 1024, $memoryUsage, '複雑なデータ構造のメモリ使用量が20MBを超えています');

        // 複雑な構造が正しく表示されることを確認
        $this->assertStringContainsString('rowspan', $rendered);
        $this->assertStringContainsString('colspan', $rendered);
        $this->assertStringContainsString('mailto:', $rendered);
        $this->assertStringContainsString('https://', $rendered);

        Log::info('Complex Data Performance Test', [
            'rows' => count($complexData),
            'render_time' => $renderTime,
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2)
        ]);
    }

    /**
     * @test
     * キャッシュ効果のパフォーマンステスト
     */
    public function test_performance_キャッシュ効果()
    {
        $cacheableData = $this->generateTestData(50);
        $cacheKey = 'performance_test_' . md5(json_encode($cacheableData));

        // キャッシュをクリア
        Cache::forget($cacheKey);

        // 1回目のレンダリング（キャッシュなし）
        $startTime1 = microtime(true);
        $view1 = View::make('components.common-table', [
            'data' => $cacheableData,
            'title' => 'キャッシュパフォーマンステスト',
            'enableCache' => true,
            'cacheKey' => $cacheKey
        ]);
        $rendered1 = $view1->render();
        $endTime1 = microtime(true);
        $renderTime1 = $endTime1 - $startTime1;

        // 2回目のレンダリング（キャッシュあり）
        $startTime2 = microtime(true);
        $view2 = View::make('components.common-table', [
            'data' => $cacheableData,
            'title' => 'キャッシュパフォーマンステスト',
            'enableCache' => true,
            'cacheKey' => $cacheKey
        ]);
        $rendered2 = $view2->render();
        $endTime2 = microtime(true);
        $renderTime2 = $endTime2 - $startTime2;

        // 3回目のレンダリング（キャッシュあり）
        $startTime3 = microtime(true);
        $view3 = View::make('components.common-table', [
            'data' => $cacheableData,
            'title' => 'キャッシュパフォーマンステスト',
            'enableCache' => true,
            'cacheKey' => $cacheKey
        ]);
        $rendered3 = $view3->render();
        $endTime3 = microtime(true);
        $renderTime3 = $endTime3 - $startTime3;

        // 全て同じ内容が表示される
        $this->assertEquals($rendered1, $rendered2);
        $this->assertEquals($rendered2, $rendered3);

        // キャッシュ効果の確認（通常は2回目以降が高速）
        $cacheEffective = ($renderTime2 < $renderTime1) && ($renderTime3 < $renderTime1);

        Log::info('Cache Performance Test', [
            'first_render_time' => $renderTime1,
            'second_render_time' => $renderTime2,
            'third_render_time' => $renderTime3,
            'cache_effective' => $cacheEffective,
            'improvement_ratio' => $renderTime1 > 0 ? ($renderTime1 - $renderTime2) / $renderTime1 : 0
        ]);

        // キャッシュが設定されていることを確認
        $this->assertTrue(Cache::has($cacheKey));
    }

    /**
     * @test
     * 並行レンダリングのパフォーマンステスト
     */
    public function test_performance_並行レンダリング()
    {
        $testData = $this->generateTestData(30);
        $renderTimes = [];

        // 複数回のレンダリングを実行
        for ($i = 0; $i < 5; $i++) {
            $startTime = microtime(true);
            
            $view = View::make('components.common-table', [
                'data' => $testData,
                'title' => "並行テスト{$i}"
            ]);
            
            $rendered = $view->render();
            
            $endTime = microtime(true);
            $renderTimes[] = $endTime - $startTime;

            // データが正しく表示されることを確認
            $this->assertStringContainsString("並行テスト{$i}", $rendered);
            $this->assertStringContainsString('項目0', $rendered);
        }

        $avgRenderTime = array_sum($renderTimes) / count($renderTimes);
        $maxRenderTime = max($renderTimes);
        $minRenderTime = min($renderTimes);

        // 並行処理での性能要件
        $this->assertLessThan(1.0, $avgRenderTime, '平均レンダリング時間が1秒を超えています');
        $this->assertLessThan(2.0, $maxRenderTime, '最大レンダリング時間が2秒を超えています');

        Log::info('Concurrent Rendering Performance Test', [
            'iterations' => count($renderTimes),
            'avg_render_time' => $avgRenderTime,
            'max_render_time' => $maxRenderTime,
            'min_render_time' => $minRenderTime,
            'render_times' => $renderTimes
        ]);
    }

    /**
     * @test
     * メモリリークテスト
     */
    public function test_performance_メモリリーク()
    {
        $testData = $this->generateTestData(20);
        $memoryUsages = [];

        // 複数回のレンダリングでメモリ使用量を測定
        for ($i = 0; $i < 10; $i++) {
            $startMemory = memory_get_usage();
            
            $view = View::make('components.common-table', [
                'data' => $testData,
                'title' => "メモリテスト{$i}"
            ]);
            
            $rendered = $view->render();
            
            $endMemory = memory_get_usage();
            $memoryUsages[] = $endMemory - $startMemory;

            // ガベージコレクションを実行
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        $avgMemoryUsage = array_sum($memoryUsages) / count($memoryUsages);
        $maxMemoryUsage = max($memoryUsages);
        $minMemoryUsage = min($memoryUsages);

        // メモリリークの確認（使用量が大幅に増加していないこと）
        $memoryVariation = $maxMemoryUsage - $minMemoryUsage;
        $this->assertLessThan(10 * 1024 * 1024, $memoryVariation, 'メモリ使用量の変動が10MBを超えています（メモリリークの可能性）');

        Log::info('Memory Leak Test', [
            'iterations' => count($memoryUsages),
            'avg_memory_usage_mb' => round($avgMemoryUsage / 1024 / 1024, 2),
            'max_memory_usage_mb' => round($maxMemoryUsage / 1024 / 1024, 2),
            'min_memory_usage_mb' => round($minMemoryUsage / 1024 / 1024, 2),
            'memory_variation_mb' => round($memoryVariation / 1024 / 1024, 2)
        ]);
    }

    /**
     * @test
     * データベースクエリ最適化テスト
     */
    public function test_performance_データベースクエリ最適化()
    {
        // データベースクエリログを有効化
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facilities->first()));

        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $totalQueryTime = array_sum(array_column($queries, 'time'));

        // クエリ最適化の確認
        $this->assertLessThan(20, $queryCount, 'データベースクエリ数が20を超えています');
        $this->assertLessThan(1000, $totalQueryTime, 'データベースクエリ時間が1000msを超えています');

        Log::info('Database Query Optimization Test', [
            'query_count' => $queryCount,
            'total_query_time_ms' => $totalQueryTime,
            'queries' => array_map(function($query) {
                return [
                    'sql' => $query['query'],
                    'time' => $query['time']
                ];
            }, $queries)
        ]);

        DB::disableQueryLog();
    }

    /**
     * @test
     * レスポンシブレンダリングのパフォーマンステスト
     */
    public function test_performance_レスポンシブレンダリング()
    {
        $responsiveData = $this->generateTestData(40);

        // デスクトップ向けレンダリング
        $startTime1 = microtime(true);
        $view1 = View::make('components.common-table', [
            'data' => $responsiveData,
            'title' => 'レスポンシブテスト',
            'responsive' => true,
            'viewportWidth' => 1200
        ]);
        $rendered1 = $view1->render();
        $endTime1 = microtime(true);
        $desktopRenderTime = $endTime1 - $startTime1;

        // モバイル向けレンダリング
        $startTime2 = microtime(true);
        $view2 = View::make('components.common-table', [
            'data' => $responsiveData,
            'title' => 'レスポンシブテスト',
            'responsive' => true,
            'viewportWidth' => 375
        ]);
        $rendered2 = $view2->render();
        $endTime2 = microtime(true);
        $mobileRenderTime = $endTime2 - $startTime2;

        // レスポンシブレンダリングの性能要件
        $this->assertLessThan(1.5, $desktopRenderTime, 'デスクトップレンダリング時間が1.5秒を超えています');
        $this->assertLessThan(1.5, $mobileRenderTime, 'モバイルレンダリング時間が1.5秒を超えています');

        // レスポンシブ要素が含まれることを確認
        $this->assertStringContainsString('table-responsive', $rendered1);
        $this->assertStringContainsString('table-responsive', $rendered2);
        $this->assertStringContainsString('data-mobile-optimized', $rendered2);

        Log::info('Responsive Rendering Performance Test', [
            'desktop_render_time' => $desktopRenderTime,
            'mobile_render_time' => $mobileRenderTime
        ]);
    }

    /**
     * テストデータ生成ヘルパー
     */
    private function generateTestData(int $rowCount): array
    {
        $data = [];
        
        for ($i = 0; $i < $rowCount; $i++) {
            $data[] = [
                'type' => 'standard',
                'cells' => [
                    ['label' => "項目{$i}", 'value' => "値{$i}", 'type' => 'text'],
                    ['label' => "メール{$i}", 'value' => "test{$i}@example.com", 'type' => 'email'],
                ]
            ];
            
            // 10行ごとにURLリンクを追加
            if ($i % 10 === 0) {
                $data[] = [
                    'type' => 'single',
                    'cells' => [
                        ['label' => "URL{$i}", 'value' => "https://example{$i}.com", 'type' => 'url', 'colspan' => 2],
                    ]
                ];
            }
        }
        
        return $data;
    }

    /**
     * 複雑なテストデータ生成ヘルパー
     */
    private function generateComplexTestData(int $groupCount): array
    {
        $data = [];
        
        for ($i = 0; $i < $groupCount; $i++) {
            // グループヘッダー
            $data[] = [
                'type' => 'grouped',
                'cells' => [
                    [
                        'label' => "グループ{$i}",
                        'value' => null,
                        'type' => 'text',
                        'rowspan' => 3,
                        'label_colspan' => 1
                    ]
                ]
            ];
            
            // グループ内のデータ
            $data[] = [
                'type' => 'standard',
                'cells' => [
                    ['label' => '名前', 'value' => "名前{$i}", 'type' => 'text'],
                ]
            ];
            
            $data[] = [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'メール', 'value' => "group{$i}@example.com", 'type' => 'email'],
                ]
            ];
            
            $data[] = [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'URL', 'value' => "https://group{$i}.example.com", 'type' => 'url'],
                ]
            ];
            
            // 数値データ
            $data[] = [
                'type' => 'standard',
                'cells' => [
                    ['label' => '金額', 'value' => ($i + 1) * 100000, 'type' => 'currency'],
                    ['label' => '日付', 'value' => '2023-' . str_pad(($i % 12) + 1, 2, '0', STR_PAD_LEFT) . '-01', 'type' => 'date'],
                ]
            ];
        }
        
        return $data;
    }
}
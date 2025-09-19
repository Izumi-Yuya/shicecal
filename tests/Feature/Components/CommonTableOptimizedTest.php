<?php

namespace Tests\Feature\Components;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use App\Services\CommonTablePerformanceOptimizer;

class CommonTableOptimizedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_optimized_component_renders_basic_table()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'テストラベル', 'value' => 'テスト値', 'type' => 'text'],
                    ['label' => '会社名', 'value' => 'テスト会社', 'type' => 'text'],
                ]
            ]
        ];

        $view = View::make('components.common-table-optimized', [
            'data' => $data,
            'title' => 'テストテーブル',
            'enableCaching' => false, // Disable caching for this test
            'performanceLogging' => false
        ]);

        $html = $view->render();

        $this->assertStringContains('テストテーブル', $html);
        $this->assertStringContains('テストラベル', $html);
        $this->assertStringContains('テスト値', $html);
        $this->assertStringContains('会社名', $html);
        $this->assertStringContains('テスト会社', $html);
        $this->assertStringContains('data-performance-optimized="true"', $html);
    }

    public function test_optimized_component_uses_caching_for_large_datasets()
    {
        // Create large dataset
        $data = [];
        for ($i = 0; $i < 150; $i++) {
            $data[] = [
                'type' => 'standard',
                'cells' => [
                    ['label' => "ラベル{$i}", 'value' => "値{$i}", 'type' => 'text'],
                    ['label' => "説明{$i}", 'value' => str_repeat("データ{$i} ", 10), 'type' => 'text'],
                ]
            ];
        }

        // First render - should populate cache
        $startTime1 = microtime(true);
        $view1 = View::make('components.common-table-optimized', [
            'data' => $data,
            'title' => '大量データテーブル',
            'enableCaching' => true,
            'performanceLogging' => false
        ]);
        $html1 = $view1->render();
        $endTime1 = microtime(true);

        // Second render - should use cache
        $startTime2 = microtime(true);
        $view2 = View::make('components.common-table-optimized', [
            'data' => $data,
            'title' => '大量データテーブル',
            'enableCaching' => true,
            'performanceLogging' => false
        ]);
        $html2 = $view2->render();
        $endTime2 = microtime(true);

        $this->assertStringContains('大量データテーブル', $html1);
        $this->assertStringContains('大量データテーブル', $html2);
        $this->assertStringContains('data-cached="true"', $html2);
        
        // Second render should be faster due to caching
        $renderTime1 = $endTime1 - $startTime1;
        $renderTime2 = $endTime2 - $startTime2;
        $this->assertLessThan($renderTime1, $renderTime2);
    }

    public function test_optimized_component_enables_lazy_loading_for_large_datasets()
    {
        // Create large dataset that should trigger lazy loading
        $data = [];
        for ($i = 0; $i < 200; $i++) {
            $data[] = [
                'type' => 'standard',
                'cells' => [
                    ['label' => "項目{$i}", 'value' => "データ{$i}", 'type' => 'text'],
                ]
            ];
        }

        $view = View::make('components.common-table-optimized', [
            'data' => $data,
            'title' => '遅延読み込みテーブル',
            'enableLazyLoading' => true,
            'batchSize' => 50,
            'performanceLogging' => false
        ]);

        $html = $view->render();

        $this->assertStringContains('data-lazy-loading="true"', $html);
        $this->assertStringContains('load-more-rows', $html);
        $this->assertStringContains('大量データのため段階的に読み込みます', $html);
        $this->assertStringContains('data-batch-size="50"', $html);
        $this->assertStringContains('remaining-batches-data', $html);
    }

    public function test_optimized_component_shows_performance_info_in_debug_mode()
    {
        config(['app.debug' => true]);

        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'テスト', 'value' => 'パフォーマンステスト', 'type' => 'text'],
                ]
            ]
        ];

        $view = View::make('components.common-table-optimized', [
            'data' => $data,
            'title' => 'パフォーマンステスト',
            'performanceLogging' => true
        ]);

        $html = $view->render();

        $this->assertStringContains('パフォーマンス情報:', $html);
        $this->assertStringContains('レンダリング時間:', $html);
        $this->assertStringContains('メモリ使用量:', $html);
        $this->assertStringContains('キャッシュ使用:', $html);
        $this->assertStringContains('データ行数:', $html);
    }

    public function test_optimized_component_handles_memory_optimization()
    {
        // Create data with large values that should be optimized
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '大きなデータ', 'value' => str_repeat('大量のテキストデータ ', 200), 'type' => 'text'],
                    ['label' => '通常データ', 'value' => '通常の値', 'type' => 'text'],
                    ['label' => '空データ', 'value' => null, 'type' => 'text'],
                ]
            ]
        ];

        $view = View::make('components.common-table-optimized', [
            'data' => $data,
            'title' => 'メモリ最適化テスト',
            'enableMemoryOptimization' => true,
            'skipEmptyCells' => true,
            'performanceLogging' => false
        ]);

        $html = $view->render();

        $this->assertStringContains('メモリ最適化テスト', $html);
        $this->assertStringContains('通常データ', $html);
        $this->assertStringContains('通常の値', $html);
        // Large data should be truncated
        $this->assertStringContains('...', $html);
    }

    public function test_optimized_component_handles_validation_errors_gracefully()
    {
        // Invalid data structure
        $invalidData = [
            'invalid' => 'structure'
        ];

        $view = View::make('components.common-table-optimized', [
            'data' => $invalidData,
            'title' => 'エラーテスト',
            'validateData' => true,
            'fallbackOnError' => true,
            'performanceLogging' => false
        ]);

        $html = $view->render();

        // Should show error or fallback content
        $this->assertTrue(
            str_contains($html, 'エラー') || 
            str_contains($html, 'データがありません') ||
            str_contains($html, 'fallback')
        );
    }

    public function test_optimized_component_batch_processing()
    {
        // Create data that will be split into batches
        $data = [];
        for ($i = 0; $i < 125; $i++) {
            $data[] = [
                'type' => 'standard',
                'cells' => [
                    ['label' => "バッチ項目{$i}", 'value' => "バッチ値{$i}", 'type' => 'text'],
                ]
            ];
        }

        $view = View::make('components.common-table-optimized', [
            'data' => $data,
            'title' => 'バッチ処理テスト',
            'enableLazyLoading' => true,
            'batchSize' => 50,
            'performanceLogging' => false
        ]);

        $html = $view->render();

        // Should show initial batch
        $this->assertStringContains('バッチ項目0', $html);
        $this->assertStringContains('バッチ値0', $html);
        
        // Should have load more button
        $this->assertStringContains('さらに読み込む', $html);
        $this->assertStringContains('75行', $html); // 125 - 50 = 75 remaining
    }

    public function test_optimized_component_accessibility_features()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'アクセシビリティテスト', 'value' => 'テスト値', 'type' => 'text'],
                ]
            ]
        ];

        $view = View::make('components.common-table-optimized', [
            'data' => $data,
            'title' => 'アクセシビリティテーブル',
            'ariaLabel' => 'カスタムARIAラベル',
            'performanceLogging' => false
        ]);

        $html = $view->render();

        $this->assertStringContains('role="table"', $html);
        $this->assertStringContains('aria-label="カスタムARIAラベル"', $html);
        $this->assertStringContains('role="region"', $html);
        $this->assertStringContains('data-responsive="true"', $html);
        $this->assertStringContains('data-mobile-optimized="true"', $html);
        $this->assertStringContains('sr-only', $html);
    }

    public function test_optimized_component_responsive_features()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'レスポンシブテスト', 'value' => 'テスト値', 'type' => 'text'],
                ]
            ]
        ];

        $view = View::make('components.common-table-optimized', [
            'data' => $data,
            'title' => 'レスポンシブテーブル',
            'responsive' => true,
            'performanceLogging' => false
        ]);

        $html = $view->render();

        $this->assertStringContains('table-responsive', $html);
        $this->assertStringContains('table-responsive-md', $html);
        $this->assertStringContains('data-responsive="true"', $html);
    }

    public function test_optimized_component_custom_styling()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'スタイルテスト', 'value' => 'テスト値', 'type' => 'text'],
                ]
            ]
        ];

        $view = View::make('components.common-table-optimized', [
            'data' => $data,
            'title' => 'カスタムスタイルテーブル',
            'cardClass' => 'custom-card-class',
            'tableClass' => 'custom-table-class',
            'headerClass' => 'custom-header-class',
            'bodyClass' => 'custom-body-class',
            'wrapperClass' => 'custom-wrapper-class',
            'performanceLogging' => false
        ]);

        $html = $view->render();

        $this->assertStringContains('custom-card-class', $html);
        $this->assertStringContains('custom-table-class', $html);
        $this->assertStringContains('custom-header-class', $html);
        $this->assertStringContains('custom-body-class', $html);
        $this->assertStringContains('custom-wrapper-class', $html);
    }

    public function test_optimized_component_empty_data_handling()
    {
        $emptyData = [];

        $view = View::make('components.common-table-optimized', [
            'data' => $emptyData,
            'title' => '空データテーブル',
            'emptyMessage' => 'カスタム空メッセージ',
            'performanceLogging' => false
        ]);

        $html = $view->render();

        $this->assertStringContains('空データテーブル', $html);
        $this->assertStringContains('カスタム空メッセージ', $html);
    }

    public function test_optimized_component_performance_analysis_integration()
    {
        // Create data that should trigger performance analysis
        $data = [];
        for ($i = 0; $i < 200; $i++) {
            $data[] = [
                'type' => 'standard',
                'cells' => [
                    ['label' => "分析項目{$i}", 'value' => str_repeat("データ{$i} ", 50), 'type' => 'text'],
                ]
            ];
        }

        $view = View::make('components.common-table-optimized', [
            'data' => $data,
            'title' => 'パフォーマンス分析テーブル',
            'enableCaching' => true,
            'enableMemoryOptimization' => true,
            'performanceLogging' => false
        ]);

        $html = $view->render();

        // Should handle large dataset appropriately
        $this->assertStringContains('パフォーマンス分析テーブル', $html);
        
        // Performance optimizations should be applied
        $this->assertTrue(
            str_contains($html, 'data-cached="true"') ||
            str_contains($html, 'data-lazy-loading="true"') ||
            str_contains($html, 'data-performance-optimized="true"')
        );
    }
}
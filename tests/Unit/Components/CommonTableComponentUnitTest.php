<?php

namespace Tests\Unit\Components;

use Tests\TestCase;
use Illuminate\View\Component;
use Illuminate\Support\Facades\View;
use App\Services\CommonTableValidator;
use App\Services\CommonTableErrorHandler;

/**
 * CommonTableコンポーネント単体テスト
 * 
 * コンポーネントのプロパティ処理とレンダリングロジックのテスト
 * 要件: 設計書のテスト戦略
 */
class CommonTableComponentUnitTest extends TestCase
{
    /**
     * @test
     * コンポーネントのデフォルトプロパティ
     */
    public function test_component_デフォルトプロパティが正しく設定される()
    {
        $view = View::make('components.common-table', [
            'data' => []
        ]);

        $this->assertStringContainsString('facility-info-card detail-card-improved mb-3', $view->render());
        $this->assertStringContainsString('table table-bordered facility-basic-info-table-clean', $view->render());
    }

    /**
     * @test
     * 空データの処理
     */
    public function test_component_空データで適切なメッセージを表示()
    {
        $view = View::make('components.common-table', [
            'data' => [],
            'emptyMessage' => 'カスタム空メッセージ'
        ]);

        $rendered = $view->render();
        $this->assertStringContainsString('カスタム空メッセージ', $rendered);
    }

    /**
     * @test
     * タイトル付きコンポーネント
     */
    public function test_component_タイトル付きでヘッダーが表示される()
    {
        $view = View::make('components.common-table', [
            'data' => [],
            'title' => 'テストタイトル'
        ]);

        $rendered = $view->render();
        $this->assertStringContainsString('テストタイトル', $rendered);
        $this->assertStringContainsString('card-header', $rendered);
    }

    /**
     * @test
     * ヘッダーなしコンポーネント
     */
    public function test_component_ヘッダーなしで表示される()
    {
        $view = View::make('components.common-table', [
            'data' => [],
            'title' => 'テストタイトル',
            'showHeader' => false
        ]);

        $rendered = $view->render();
        $this->assertStringNotContainsString('card-header', $rendered);
    }

    /**
     * @test
     * レスポンシブテーブルの設定
     */
    public function test_component_レスポンシブ設定が適用される()
    {
        // レスポンシブON
        $view = View::make('components.common-table', [
            'data' => [],
            'responsive' => true
        ]);

        $rendered = $view->render();
        $this->assertStringContainsString('table-responsive', $rendered);
        $this->assertStringContainsString('data-responsive="true"', $rendered);

        // レスポンシブOFF
        $view = View::make('components.common-table', [
            'data' => [],
            'responsive' => false
        ]);

        $rendered = $view->render();
        $this->assertStringContainsString('data-responsive="false"', $rendered);
    }

    /**
     * @test
     * カスタムCSSクラスの適用
     */
    public function test_component_カスタムCSSクラスが適用される()
    {
        $view = View::make('components.common-table', [
            'data' => [],
            'cardClass' => 'custom-card-class',
            'tableClass' => 'custom-table-class',
            'headerClass' => 'custom-header-class',
            'bodyClass' => 'custom-body-class',
            'wrapperClass' => 'custom-wrapper-class',
            'title' => 'テスト'
        ]);

        $rendered = $view->render();
        $this->assertStringContainsString('custom-card-class', $rendered);
        $this->assertStringContainsString('custom-table-class', $rendered);
        $this->assertStringContainsString('custom-header-class', $rendered);
        $this->assertStringContainsString('custom-body-class', $rendered);
        $this->assertStringContainsString('custom-wrapper-class', $rendered);
    }

    /**
     * @test
     * アクセシビリティ属性の設定
     */
    public function test_component_アクセシビリティ属性が設定される()
    {
        $view = View::make('components.common-table', [
            'data' => [],
            'title' => 'テストテーブル',
            'ariaLabel' => 'カスタムARIAラベル'
        ]);

        $rendered = $view->render();
        $this->assertStringContainsString('aria-label="カスタムARIAラベル"', $rendered);
        $this->assertStringContainsString('role="table"', $rendered);
        $this->assertStringContainsString('role="region"', $rendered);
    }

    /**
     * @test
     * デフォルトARIAラベルの生成
     */
    public function test_component_デフォルトARIAラベルが生成される()
    {
        $view = View::make('components.common-table', [
            'data' => [],
            'title' => 'テストテーブル'
        ]);

        $rendered = $view->render();
        $this->assertStringContainsString('テストテーブルの詳細情報', $rendered);
    }

    /**
     * @test
     * テーブル属性の処理
     */
    public function test_component_テーブル属性が適用される()
    {
        $view = View::make('components.common-table', [
            'data' => [],
            'tableAttributes' => [
                'data-test' => 'test-value',
                'id' => 'custom-table-id'
            ]
        ]);

        $rendered = $view->render();
        $this->assertStringContainsString('data-test="test-value"', $rendered);
        $this->assertStringContainsString('id="custom-table-id"', $rendered);
    }

    /**
     * @test
     * 有効なデータでの行レンダリング
     */
    public function test_component_有効なデータで行が表示される()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'テストラベル', 'value' => 'テスト値', 'type' => 'text']
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $data
        ]);

        $rendered = $view->render();
        $this->assertStringContainsString('テストラベル', $rendered);
        $this->assertStringContainsString('テスト値', $rendered);
    }

    /**
     * @test
     * バリデーション無効化
     */
    public function test_component_バリデーション無効化が動作する()
    {
        // 無効なデータでもバリデーションを無効にすれば表示される
        $invalidData = [
            [
                'type' => 'invalid_type',
                'cells' => [
                    ['label' => 'テスト', 'value' => '値', 'type' => 'invalid_cell_type']
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $invalidData,
            'validateData' => false
        ]);

        $rendered = $view->render();
        $this->assertStringContainsString('テスト', $rendered);
        $this->assertStringContainsString('値', $rendered);
    }

    /**
     * @test
     * バリデーション警告の表示
     */
    public function test_component_バリデーション警告が表示される()
    {
        // 警告を発生させるデータ（大きなcolspan）
        $warningData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'テスト', 'value' => '値', 'type' => 'text', 'colspan' => 8]
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $warningData,
            'showValidationWarnings' => true
        ]);

        $rendered = $view->render();
        $this->assertStringContainsString('警告', $rendered);
        $this->assertStringContainsString('alert-warning', $rendered);
    }

    /**
     * @test
     * エラー時のフォールバック表示
     */
    public function test_component_エラー時にフォールバック表示される()
    {
        // バリデーションエラーを発生させるデータ
        $errorData = [
            [
                'type' => 'unsupported_type',
                'cells' => [
                    ['label' => 'テスト', 'value' => '値', 'type' => 'unsupported_cell_type']
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $errorData,
            'fallbackOnError' => true
        ]);

        $rendered = $view->render();
        // エラー表示またはフォールバック表示が含まれることを確認
        $this->assertTrue(
            strpos($rendered, 'データの形式に問題があります') !== false ||
            strpos($rendered, 'データを表示できませんでした') !== false
        );
    }

    /**
     * @test
     * クリーンボディクラスの適用
     */
    public function test_component_クリーンボディクラスが適用される()
    {
        // クリーンボディON
        $view = View::make('components.common-table', [
            'data' => [],
            'cleanBody' => true
        ]);

        $rendered = $view->render();
        $this->assertStringContainsString('card-body-clean', $rendered);

        // クリーンボディOFF
        $view = View::make('components.common-table', [
            'data' => [],
            'cleanBody' => false
        ]);

        $rendered = $view->render();
        $this->assertStringNotContainsString('card-body-clean', $rendered);
    }

    /**
     * @test
     * スクリーンリーダー用の説明
     */
    public function test_component_スクリーンリーダー用説明が含まれる()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'テスト', 'value' => '値', 'type' => 'text']
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $data,
            'title' => 'テストテーブル'
        ]);

        $rendered = $view->render();
        $this->assertStringContainsString('sr-only', $rendered);
        $this->assertStringContainsString('1行のデータが含まれています', $rendered);
    }

    /**
     * @test
     * テーブルキャプションの生成
     */
    public function test_component_テーブルキャプションが生成される()
    {
        $view = View::make('components.common-table', [
            'data' => [],
            'title' => 'テストテーブル'
        ]);

        $rendered = $view->render();
        $this->assertStringContainsString('<caption class="sr-only">テストテーブルの詳細情報</caption>', $rendered);
    }

    /**
     * @test
     * モバイル最適化属性
     */
    public function test_component_モバイル最適化属性が設定される()
    {
        $view = View::make('components.common-table', [
            'data' => []
        ]);

        $rendered = $view->render();
        $this->assertStringContainsString('data-mobile-optimized="true"', $rendered);
    }

    /**
     * @test
     * 複数行データの処理
     */
    public function test_component_複数行データが正しく処理される()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'ラベル1', 'value' => '値1', 'type' => 'text']
                ]
            ],
            [
                'type' => 'single',
                'cells' => [
                    ['label' => 'ラベル2', 'value' => '値2', 'type' => 'text', 'colspan' => 2]
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $data
        ]);

        $rendered = $view->render();
        $this->assertStringContainsString('ラベル1', $rendered);
        $this->assertStringContainsString('値1', $rendered);
        $this->assertStringContainsString('ラベル2', $rendered);
        $this->assertStringContainsString('値2', $rendered);
    }

    /**
     * @test
     * 無効な行データのスキップ
     */
    public function test_component_無効な行データがスキップされる()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '有効なデータ', 'value' => '値', 'type' => 'text']
                ]
            ],
            'invalid_row_data',
            [
                'type' => 'standard',
                'cells' => []  // 空のセル配列
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $data,
            'validateData' => false  // バリデーションを無効にして構造チェックのみ
        ]);

        $rendered = $view->render();
        $this->assertStringContainsString('有効なデータ', $rendered);
        // 無効なデータや空のセルはスキップされる
    }
}
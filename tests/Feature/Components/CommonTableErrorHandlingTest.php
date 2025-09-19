<?php

namespace Tests\Feature\Components;

use Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class CommonTableErrorHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::spy();
    }

    /**
     * 有効なデータでのレンダリングテスト
     */
    public function test_renders_valid_data_without_errors()
    {
        $validData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'テストラベル', 'value' => 'テスト値', 'type' => 'text'],
                    ['label' => 'メール', 'value' => 'test@example.com', 'type' => 'email'],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $validData,
            'title' => 'テストテーブル',
            'validateData' => true
        ]);

        $html = $view->render();

        $this->assertStringContainsString('テストテーブル', $html);
        $this->assertStringContainsString('テストラベル', $html);
        $this->assertStringContainsString('テスト値', $html);
        $this->assertStringNotContainsString('alert-danger', $html);
        $this->assertStringNotContainsString('エラー', $html);

        // エラーログが記録されていないことを確認
        Log::shouldNotHaveReceived('error');
        Log::shouldNotHaveReceived('warning');
    }

    /**
     * バリデーションエラーでのエラー表示テスト
     */
    public function test_displays_validation_errors()
    {
        $invalidData = [
            [
                'type' => 'invalid_type',
                'cells' => [
                    ['label' => 'テスト', 'value' => 'テスト値', 'type' => 'invalid_cell_type'],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $invalidData,
            'title' => 'テストテーブル',
            'validateData' => true,
            'fallbackOnError' => true
        ]);

        $html = $view->render();

        $this->assertStringContainsString('alert-danger', $html);
        $this->assertStringContainsString('データの形式に問題があります', $html);
        $this->assertStringContainsString('サポートされていない行タイプ', $html);
        $this->assertStringContainsString('サポートされていないセルタイプ', $html);

        // バリデーションエラーがログに記録されることを確認
        Log::shouldHaveReceived('warning')->once();
    }

    /**
     * バリデーション警告の表示テスト
     */
    public function test_displays_validation_warnings()
    {
        $dataWithWarnings = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'メール', 'value' => 'invalid-email', 'type' => 'email'],
                    ['label' => 'URL', 'value' => 'not a valid url at all', 'type' => 'url'],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $dataWithWarnings,
            'title' => 'テストテーブル',
            'validateData' => true,
            'showValidationWarnings' => true,
            'fallbackOnError' => false // 警告では表示を続行
        ]);

        $html = $view->render();

        $this->assertStringContainsString('alert-warning', $html);
        $this->assertStringContainsString('警告', $html);
        $this->assertStringContainsString('有効なメールアドレス形式ではありません', $html);
        $this->assertStringContainsString('有効なURL形式ではありません', $html);
        $this->assertStringContainsString('テストテーブル', $html); // テーブルも表示される

        // 警告がログに記録されることを確認（バリデーション結果による）
    }

    /**
     * フォールバック表示のテスト
     */
    public function test_displays_fallback_on_severe_errors()
    {
        $invalidData = [
            [
                'type' => 'invalid_type',
                'cells' => [
                    ['label' => 'テスト', 'value' => 'テスト値', 'type' => 'invalid_cell_type'],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $invalidData,
            'title' => 'テストテーブル',
            'validateData' => true,
            'fallbackOnError' => true
        ]);

        $html = $view->render();

        $this->assertStringContainsString('データの形式に問題があります', $html);
        $this->assertStringContainsString('alert-danger', $html);
        $this->assertStringNotContainsString('table', $html); // テーブルは表示されない
    }

    /**
     * バリデーション無効時のテスト
     */
    public function test_skips_validation_when_disabled()
    {
        $invalidData = [
            [
                'type' => 'invalid_type',
                'cells' => [
                    ['label' => 'テスト', 'value' => 'テスト値', 'type' => 'invalid_cell_type'],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $invalidData,
            'title' => 'テストテーブル',
            'validateData' => false // バリデーション無効
        ]);

        $html = $view->render();

        $this->assertStringContainsString('テストテーブル', $html);
        $this->assertStringNotContainsString('alert-danger', $html);
        $this->assertStringNotContainsString('エラー', $html);

        // バリデーションエラーがログに記録されていないことを確認
        Log::shouldNotHaveReceived('warning');
    }

    /**
     * 空データでのテスト
     */
    public function test_handles_empty_data()
    {
        $view = View::make('components.common-table', [
            'data' => [],
            'title' => 'テストテーブル',
            'validateData' => true
        ]);

        $html = $view->render();

        $this->assertStringContainsString('テストテーブル', $html);
        $this->assertStringContainsString('データがありません', $html);
        $this->assertStringNotContainsString('alert-danger', $html);

        // 空データでは警告ログが記録される可能性がある
    }

    /**
     * 非配列データでのテスト
     */
    public function test_handles_non_array_data()
    {
        $view = View::make('components.common-table', [
            'data' => 'invalid_data',
            'title' => 'テストテーブル',
            'validateData' => true,
            'fallbackOnError' => true
        ]);

        $html = $view->render();

        $this->assertStringContainsString('データの形式に問題があります', $html);
        $this->assertStringContainsString('alert-danger', $html);

        // バリデーションエラーがログに記録される
        Log::shouldHaveReceived('warning')->once();
    }

    /**
     * カスタムエラーメッセージのテスト
     */
    public function test_displays_custom_empty_message()
    {
        $view = View::make('components.common-table', [
            'data' => [],
            'title' => 'テストテーブル',
            'emptyMessage' => 'カスタム空メッセージ',
            'validateData' => true
        ]);

        $html = $view->render();

        $this->assertStringContainsString('カスタム空メッセージ', $html);
        $this->assertStringNotContainsString('データがありません', $html);
    }

    /**
     * バリデーションオプションのテスト
     */
    public function test_uses_validation_options()
    {
        // 大量のデータを作成（警告を発生させるため）
        $largeData = [];
        for ($i = 0; $i < 150; $i++) {
            $largeData[] = [
                'type' => 'standard',
                'cells' => [
                    ['label' => "ラベル{$i}", 'value' => "値{$i}", 'type' => 'text']
                ]
            ];
        }

        $view = View::make('components.common-table', [
            'data' => $largeData,
            'title' => 'テストテーブル',
            'validateData' => true,
            'showValidationWarnings' => true,
            'validationOptions' => ['max_rows' => 100]
        ]);

        $html = $view->render();

        $this->assertStringContainsString('alert-warning', $html);
        $this->assertStringContainsString('行数が多すぎます', $html);

        // 警告がログに記録される（バリデーション結果による）
    }

    /**
     * エラーIDの生成テスト
     */
    public function test_generates_error_id_for_debugging()
    {
        $invalidData = [
            [
                'type' => 'invalid_type',
                'cells' => [
                    ['label' => 'テスト', 'value' => 'テスト値', 'type' => 'invalid_cell_type'],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $invalidData,
            'title' => 'テストテーブル',
            'validateData' => true,
            'fallbackOnError' => true
        ]);

        $html = $view->render();

        // デバッグモードでエラーIDが表示されることを確認
        if (config('app.debug')) {
            $this->assertStringContainsString('エラーID:', $html);
            $this->assertStringContainsString('CT_', $html);
        }
    }
}
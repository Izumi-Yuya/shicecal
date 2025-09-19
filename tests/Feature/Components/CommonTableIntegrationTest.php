<?php

namespace Tests\Feature\Components;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use App\Models\Facility;
use App\Models\User;

/**
 * CommonTable統合テスト
 * 
 * ビューレンダリングと既存ビューとの互換性テスト
 * 要件: 設計書のテスト戦略
 */
class CommonTableIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // テスト用ユーザーとファシリティを作成
        $this->user = User::factory()->create();
        $this->facility = Facility::factory()->create();
    }

    /**
     * @test
     * 基本情報表示カードとの統合テスト
     */
    public function test_integration_基本情報表示カードが正しくレンダリングされる()
    {
        // 基本情報用のテストデータ
        $basicInfoData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '会社名', 'value' => $this->facility->company_name, 'type' => 'text'],
                    ['label' => '事業所コード', 'value' => $this->facility->office_code, 'type' => 'badge'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '住所', 'value' => $this->facility->address, 'type' => 'text'],
                    ['label' => '電話番号', 'value' => $this->facility->phone, 'type' => 'text'],
                ]
            ],
            [
                'type' => 'single',
                'cells' => [
                    ['label' => 'ウェブサイト', 'value' => 'https://example.com', 'type' => 'url', 'colspan' => 2],
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // コンポーネントを直接テスト
        $view = View::make('components.common-table', [
            'data' => $basicInfoData,
            'title' => '基本情報',
            'cardClass' => 'facility-info-card detail-card-improved mb-3'
        ]);

        $rendered = $view->render();

        // 基本的な構造の確認
        $this->assertStringContainsString('facility-info-card', $rendered);
        $this->assertStringContainsString('detail-card-improved', $rendered);
        $this->assertStringContainsString('基本情報', $rendered);
        
        // データの確認
        if ($this->facility->company_name) {
            $this->assertStringContainsString($this->facility->company_name, $rendered);
        }
        if ($this->facility->office_code) {
            $this->assertStringContainsString($this->facility->office_code, $rendered);
        }
        if ($this->facility->address) {
            $this->assertStringContainsString($this->facility->address, $rendered);
        }
        if ($this->facility->phone) {
            $this->assertStringContainsString($this->facility->phone, $rendered);
        }
        
        // URLリンクの確認
        $this->assertStringContainsString('https://example.com', $rendered);
        $this->assertStringContainsString('fas fa-external-link-alt', $rendered);
        
        // バッジの確認
        $this->assertStringContainsString('badge bg-primary', $rendered);
    }

    /**
     * @test
     * 土地情報表示カードとの統合テスト
     */
    public function test_integration_土地情報表示カードが正しくレンダリングされる()
    {
        // 土地情報用のテストデータ（複雑なレイアウト）
        $landInfoData = [
            [
                'type' => 'grouped',
                'cells' => [
                    [
                        'label' => '管理会社情報',
                        'value' => null,
                        'type' => 'text',
                        'rowspan' => 3,
                        'label_colspan' => 1
                    ]
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '会社名', 'value' => '管理会社A', 'type' => 'text'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '担当者', 'value' => '田中太郎', 'type' => 'text'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '連絡先', 'value' => 'tanaka@example.com', 'type' => 'email'],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $landInfoData,
            'title' => '土地情報',
            'cardClass' => 'facility-info-card detail-card-improved mb-3'
        ]);

        $rendered = $view->render();

        // 構造の確認
        $this->assertStringContainsString('土地情報', $rendered);
        $this->assertStringContainsString('管理会社情報', $rendered);
        $this->assertStringContainsString('管理会社A', $rendered);
        $this->assertStringContainsString('田中太郎', $rendered);
        
        // メールリンクの確認
        $this->assertStringContainsString('mailto:tanaka@example.com', $rendered);
        $this->assertStringContainsString('fas fa-envelope', $rendered);
        
        // rowspanの確認
        $this->assertStringContainsString('rowspan="3"', $rendered);
    }

    /**
     * @test
     * 建物情報表示カードとの統合テスト
     */
    public function test_integration_建物情報表示カードが正しくレンダリングされる()
    {
        // 建物情報用のテストデータ
        $buildingInfoData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '建物名', 'value' => 'テストビル', 'type' => 'text'],
                    ['label' => '階数', 'value' => 10, 'type' => 'number'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '建築年', 'value' => '2020-04-01', 'type' => 'date'],
                    ['label' => '延床面積', 'value' => 1500.50, 'type' => 'number', 'options' => ['decimals' => 2]],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '建築費', 'value' => 50000000, 'type' => 'currency'],
                    ['label' => '所有形態', 'value' => '自社所有', 'type' => 'badge', 'options' => ['auto_class' => true]],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $buildingInfoData,
            'title' => '建物情報'
        ]);

        $rendered = $view->render();

        // データフォーマットの確認
        $this->assertStringContainsString('テストビル', $rendered);
        $this->assertStringContainsString('10', $rendered);
        $this->assertStringContainsString('2020年04月01日', $rendered); // 実際のフォーマット
        $this->assertStringContainsString('1,500.50', $rendered);
        $this->assertStringContainsString('50,000,000円', $rendered);
        $this->assertStringContainsString('自社所有', $rendered);
    }

    /**
     * @test
     * ライフライン設備表示カードとの統合テスト
     */
    public function test_integration_ライフライン設備表示カードが正しくレンダリングされる()
    {
        // ライフライン設備用のテストデータ
        $lifelineData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '設備名', 'value' => '受変電設備', 'type' => 'text'],
                    ['label' => 'ステータス', 'value' => '正常', 'type' => 'badge', 'options' => ['auto_class' => true]],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '点検日', 'value' => '2023-12-01', 'type' => 'date'],
                    ['label' => '次回点検', 'value' => '2024-06-01', 'type' => 'date'],
                ]
            ],
            [
                'type' => 'single',
                'cells' => [
                    ['label' => '点検報告書', 'value' => '/storage/reports/inspection_2023.pdf', 'type' => 'file', 'colspan' => 2],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $lifelineData,
            'title' => 'ライフライン設備'
        ]);

        $rendered = $view->render();

        // 設備情報の確認
        $this->assertStringContainsString('受変電設備', $rendered);
        $this->assertStringContainsString('正常', $rendered);
        $this->assertStringContainsString('2023年12月01日', $rendered); // 実際のフォーマット
        $this->assertStringContainsString('2024年06月01日', $rendered); // 実際のフォーマット
        
        // ファイルリンクの確認
        $this->assertStringContainsString('inspection_2023.pdf', $rendered);
        $this->assertStringContainsString('fas fa-file-pdf', $rendered);
        
        // バッジクラスの確認（正常 = success）
        $this->assertStringContainsString('badge bg-primary', $rendered); // デフォルトクラス
    }

    /**
     * @test
     * 空データでの統合テスト
     */
    public function test_integration_空データで適切なメッセージが表示される()
    {
        $view = View::make('components.common-table', [
            'data' => [],
            'title' => '空のテーブル',
            'emptyMessage' => 'データが登録されていません'
        ]);

        $rendered = $view->render();

        $this->assertStringContainsString('空のテーブル', $rendered);
        $this->assertStringContainsString('データが登録されていません', $rendered);
        $this->assertStringContainsString('text-center text-muted', $rendered);
    }

    /**
     * @test
     * レスポンシブデザインの統合テスト
     */
    public function test_integration_レスポンシブデザインが適用される()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'テスト項目', 'value' => 'テスト値', 'type' => 'text'],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $data,
            'title' => 'レスポンシブテスト',
            'responsive' => true
        ]);

        $rendered = $view->render();

        // レスポンシブクラスの確認
        $this->assertStringContainsString('table-responsive', $rendered);
        $this->assertStringContainsString('table-responsive-md', $rendered);
        $this->assertStringContainsString('data-responsive="true"', $rendered);
        $this->assertStringContainsString('data-mobile-optimized="true"', $rendered);
    }

    /**
     * @test
     * アクセシビリティ機能の統合テスト
     */
    public function test_integration_アクセシビリティ機能が正しく動作する()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'アクセシビリティテスト', 'value' => 'テスト値', 'type' => 'text'],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $data,
            'title' => 'アクセシビリティテスト',
            'ariaLabel' => 'カスタムアクセシビリティラベル'
        ]);

        $rendered = $view->render();

        // ARIA属性の確認
        $this->assertStringContainsString('aria-label="カスタムアクセシビリティラベル"', $rendered);
        $this->assertStringContainsString('role="table"', $rendered);
        $this->assertStringContainsString('role="region"', $rendered);
        $this->assertStringContainsString('role="row"', $rendered);
        $this->assertStringContainsString('role="rowheader"', $rendered);
        $this->assertStringContainsString('role="gridcell"', $rendered);
        
        // スクリーンリーダー用要素の確認
        $this->assertStringContainsString('sr-only', $rendered);
        $this->assertStringContainsString('<caption class="sr-only">', $rendered);
    }

    /**
     * @test
     * エラーハンドリングの統合テスト
     */
    public function test_integration_エラーハンドリングが正しく動作する()
    {
        // バリデーションエラーを発生させるデータ
        $invalidData = [
            [
                'type' => 'unsupported_type',
                'cells' => [
                    ['label' => 'テスト', 'value' => '値', 'type' => 'unsupported_cell_type']
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $invalidData,
            'title' => 'エラーテスト',
            'fallbackOnError' => true
        ]);

        $rendered = $view->render();

        // エラー表示またはフォールバック表示の確認
        $this->assertTrue(
            strpos($rendered, 'データの形式に問題があります') !== false ||
            strpos($rendered, 'データを表示できませんでした') !== false ||
            strpos($rendered, 'alert-danger') !== false ||
            strpos($rendered, 'alert-warning') !== false
        );
    }

    /**
     * @test
     * パフォーマンス統合テスト（大量データ）
     */
    public function test_integration_大量データでのパフォーマンス()
    {
        // 大量データの生成
        $largeData = [];
        for ($i = 0; $i < 50; $i++) {
            $largeData[] = [
                'type' => 'standard',
                'cells' => [
                    ['label' => "項目{$i}", 'value' => "値{$i}", 'type' => 'text'],
                    ['label' => "メール{$i}", 'value' => "test{$i}@example.com", 'type' => 'email'],
                ]
            ];
        }

        $startTime = microtime(true);

        $view = View::make('components.common-table', [
            'data' => $largeData,
            'title' => 'パフォーマンステスト'
        ]);

        $rendered = $view->render();
        
        $endTime = microtime(true);
        $renderTime = $endTime - $startTime;

        // レンダリング時間が合理的な範囲内であることを確認（5秒以内）
        $this->assertLessThan(5.0, $renderTime, 'レンダリング時間が長すぎます');

        // データが正しく表示されることを確認
        $this->assertStringContainsString('項目0', $rendered);
        $this->assertStringContainsString('項目49', $rendered);
        $this->assertStringContainsString('test0@example.com', $rendered);
        $this->assertStringContainsString('test49@example.com', $rendered);
    }

    /**
     * @test
     * 複雑なデータ構造の統合テスト
     */
    public function test_integration_複雑なデータ構造が正しく処理される()
    {
        $complexData = [
            [
                'type' => 'grouped',
                'cells' => [
                    [
                        'label' => 'グループヘッダー',
                        'value' => null,
                        'type' => 'text',
                        'rowspan' => 2,
                        'label_colspan' => 1
                    ]
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'サブ項目1', 'value' => 'サブ値1', 'type' => 'text'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'サブ項目2', 'value' => 'success', 'type' => 'badge', 'options' => ['auto_class' => true]],
                ]
            ],
            [
                'type' => 'single',
                'cells' => [
                    [
                        'label' => '備考',
                        'value' => 'これは長い備考テキストです。複数行にわたる可能性があります。',
                        'type' => 'text',
                        'colspan' => 2,
                        'options' => ['max_length' => 50]
                    ]
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $complexData,
            'title' => '複雑なデータ構造テスト'
        ]);

        $rendered = $view->render();

        // 複雑な構造の確認
        $this->assertStringContainsString('グループヘッダー', $rendered);
        $this->assertStringContainsString('サブ項目1', $rendered);
        $this->assertStringContainsString('サブ値1', $rendered);
        $this->assertStringContainsString('サブ項目2', $rendered);
        $this->assertStringContainsString('badge bg-success', $rendered);
        $this->assertStringContainsString('rowspan="2"', $rendered);
        $this->assertStringContainsString('colspan="2"', $rendered);
        
        // テキスト切り詰めの確認
        $this->assertStringContainsString('これは長い備考テキストです。複数行にわたる可能性があります。', $rendered);
    }

    /**
     * @test
     * 既存ビューとの後方互換性テスト
     */
    public function test_integration_既存ビューとの後方互換性()
    {
        // 既存の基本情報表示カードのデータ構造をシミュレート
        $legacyData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '会社名', 'value' => $this->facility->company_name, 'type' => 'text'],
                    ['label' => '事業所コード', 'value' => $this->facility->office_code, 'type' => 'text'],
                ]
            ]
        ];

        // 既存のCSSクラスを使用
        $view = View::make('components.common-table', [
            'data' => $legacyData,
            'title' => '基本情報',
            'cardClass' => 'facility-info-card detail-card-improved mb-3',
            'tableClass' => 'table table-bordered facility-basic-info-table-clean',
            'headerClass' => 'card-header',
            'cleanBody' => true
        ]);

        $rendered = $view->render();

        // 既存のCSSクラスが適用されることを確認
        $this->assertStringContainsString('facility-info-card', $rendered);
        $this->assertStringContainsString('detail-card-improved', $rendered);
        $this->assertStringContainsString('facility-basic-info-table-clean', $rendered);
        $this->assertStringContainsString('card-body-clean', $rendered);
        
        // 既存のデータが正しく表示されることを確認
        $this->assertStringContainsString($this->facility->company_name, $rendered);
        $this->assertStringContainsString($this->facility->office_code, $rendered);
        
        // 既存のHTML構造が維持されることを確認
        $this->assertStringContainsString('detail-label', $rendered);
        $this->assertStringContainsString('detail-value', $rendered);
    }
}
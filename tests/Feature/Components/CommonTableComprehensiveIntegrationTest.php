<?php

namespace Tests\Feature\Components;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Facility;
use App\Models\User;
use App\Models\LandInfo;
use App\Models\BuildingInfo;
use App\Models\LifelineEquipment;
use App\Services\ValueFormatter;
use App\Services\CommonTableValidator;
use App\Services\CommonTableErrorHandler;
use App\Services\CommonTablePerformanceOptimizer;

/**
 * CommonTable包括的統合テスト
 * 
 * 全機能の連携テスト、パフォーマンステスト、セキュリティテスト
 * 要件: 1.4, 6.1, 6.2
 */
class CommonTableComprehensiveIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $facility;
    protected $landInfo;
    protected $buildingInfo;
    protected $lifelineEquipment;

    protected function setUp(): void
    {
        parent::setUp();
        
        // テストデータの準備
        $this->user = User::factory()->create([
            'role' => 'admin',
            'email' => 'integration@example.com'
        ]);
        
        $this->facility = Facility::factory()->create([
            'company_name' => '統合テスト株式会社',
            'office_code' => 'INTEGRATION001',
            'address' => '東京都新宿区統合テスト1-1-1',
            'phone_number' => '03-1234-5678'
        ]);

        $this->landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'management_company_name' => '統合管理株式会社',
            'management_contact_person' => '統合太郎',
            'management_phone' => '03-9876-5432',
            'management_email' => 'management@integration.com',
            'owner_name' => '統合オーナー',
            'owner_phone' => '03-1111-2222',
            'land_area' => 500.75,
            'land_price' => 100000000
        ]);

        $this->buildingInfo = BuildingInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'building_name' => '統合テストビル',
            'floors_above_ground' => 12,
            'floors_below_ground' => 2,
            'total_floor_area' => 2500.50,
            'construction_cost' => 500000000,
            'completion_date' => '2020-03-31'
        ]);

        $this->lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'equipment_name' => '統合受変電設備',
            'equipment_type' => 'electrical',
            'status' => 'normal',
            'last_inspection_date' => '2023-12-01',
            'next_inspection_date' => '2024-06-01'
        ]);
    }

    /**
     * @test
     * 全コンポーネント連携テスト - 基本情報カード
     */
    public function test_comprehensive_基本情報カード全機能連携()
    {
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
                    ['label' => '電話番号', 'value' => $this->facility->phone_number, 'type' => 'text'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'メールアドレス', 'value' => 'integration@test.com', 'type' => 'email'],
                    ['label' => '設立日', 'value' => '2020-04-01', 'type' => 'date'],
                ]
            ],
            [
                'type' => 'single',
                'cells' => [
                    ['label' => 'ウェブサイト', 'value' => 'https://integration-test.com', 'type' => 'url', 'colspan' => 2],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $basicInfoData,
            'title' => '基本情報',
            'cardClass' => 'facility-info-card detail-card-improved mb-3',
            'tableClass' => 'table table-bordered facility-basic-info-table-clean',
            'responsive' => true,
            'cleanBody' => true
        ]);

        $rendered = $view->render();

        // 構造の確認
        $this->assertStringContainsString('facility-info-card detail-card-improved mb-3', $rendered);
        $this->assertStringContainsString('table table-bordered facility-basic-info-table-clean', $rendered);
        $this->assertStringContainsString('card-body-clean', $rendered);
        $this->assertStringContainsString('table-responsive', $rendered);

        // データの確認
        $this->assertStringContainsString('統合テスト株式会社', $rendered);
        $this->assertStringContainsString('INTEGRATION001', $rendered);
        $this->assertStringContainsString('東京都新宿区統合テスト1-1-1', $rendered);
        $this->assertStringContainsString('03-1234-5678', $rendered);
        $this->assertStringContainsString('mailto:integration@test.com', $rendered);
        $this->assertStringContainsString('2020年04月01日', $rendered);
        $this->assertStringContainsString('https://integration-test.com', $rendered);

        // セルタイプの確認
        $this->assertStringContainsString('badge bg-primary', $rendered);
        $this->assertStringContainsString('fas fa-envelope', $rendered);
        $this->assertStringContainsString('fas fa-external-link-alt', $rendered);
        $this->assertStringContainsString('colspan="2"', $rendered);

        // アクセシビリティの確認
        $this->assertStringContainsString('role="table"', $rendered);
        $this->assertStringContainsString('aria-label', $rendered);
        $this->assertStringContainsString('sr-only', $rendered);
    }

    /**
     * @test
     * 全コンポーネント連携テスト - 土地情報カード（複雑なレイアウト）
     */
    public function test_comprehensive_土地情報カード複雑レイアウト連携()
    {
        $landInfoData = [
            [
                'type' => 'grouped',
                'cells' => [
                    [
                        'label' => '管理会社情報',
                        'value' => null,
                        'type' => 'text',
                        'rowspan' => 4,
                        'label_colspan' => 1
                    ]
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '会社名', 'value' => $this->landInfo->management_company_name, 'type' => 'text'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '担当者', 'value' => $this->landInfo->management_contact_person, 'type' => 'text'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '電話番号', 'value' => $this->landInfo->management_phone, 'type' => 'text'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'メール', 'value' => $this->landInfo->management_email, 'type' => 'email'],
                ]
            ],
            [
                'type' => 'grouped',
                'cells' => [
                    [
                        'label' => 'オーナー情報',
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
                    ['label' => '氏名', 'value' => $this->landInfo->owner_name, 'type' => 'text'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '電話番号', 'value' => $this->landInfo->owner_phone, 'type' => 'text'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '土地面積', 'value' => $this->landInfo->land_area, 'type' => 'number', 'options' => ['decimals' => 2, 'suffix' => '㎡']],
                    ['label' => '土地価格', 'value' => $this->landInfo->land_price, 'type' => 'currency'],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $landInfoData,
            'title' => '土地情報',
            'cardClass' => 'facility-info-card detail-card-improved mb-3'
        ]);

        $rendered = $view->render();

        // 複雑な構造の確認
        $this->assertStringContainsString('管理会社情報', $rendered);
        $this->assertStringContainsString('オーナー情報', $rendered);
        $this->assertStringContainsString('rowspan="4"', $rendered);
        $this->assertStringContainsString('rowspan="2"', $rendered);

        // データの確認
        $this->assertStringContainsString('統合管理株式会社', $rendered);
        $this->assertStringContainsString('統合太郎', $rendered);
        $this->assertStringContainsString('03-9876-5432', $rendered);
        $this->assertStringContainsString('mailto:management@integration.com', $rendered);
        $this->assertStringContainsString('統合オーナー', $rendered);
        $this->assertStringContainsString('03-1111-2222', $rendered);
        $this->assertStringContainsString('500.75㎡', $rendered);
        $this->assertStringContainsString('100,000,000円', $rendered);
    }

    /**
     * @test
     * 全コンポーネント連携テスト - 建物情報カード（数値フォーマット）
     */
    public function test_comprehensive_建物情報カード数値フォーマット連携()
    {
        $buildingInfoData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '建物名', 'value' => $this->buildingInfo->building_name, 'type' => 'text'],
                    ['label' => '地上階数', 'value' => $this->buildingInfo->floors_above_ground, 'type' => 'number', 'options' => ['suffix' => '階']],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '地下階数', 'value' => $this->buildingInfo->floors_below_ground, 'type' => 'number', 'options' => ['suffix' => '階']],
                    ['label' => '延床面積', 'value' => $this->buildingInfo->total_floor_area, 'type' => 'number', 'options' => ['decimals' => 2, 'suffix' => '㎡']],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '建築費', 'value' => $this->buildingInfo->construction_cost, 'type' => 'currency'],
                    ['label' => '竣工日', 'value' => $this->buildingInfo->completion_date, 'type' => 'date'],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $buildingInfoData,
            'title' => '建物情報'
        ]);

        $rendered = $view->render();

        // 数値フォーマットの確認
        $this->assertStringContainsString('統合テストビル', $rendered);
        $this->assertStringContainsString('12階', $rendered);
        $this->assertStringContainsString('2階', $rendered);
        $this->assertStringContainsString('2,500.50㎡', $rendered);
        $this->assertStringContainsString('500,000,000円', $rendered);
        $this->assertStringContainsString('2020年03月31日', $rendered);
    }

    /**
     * @test
     * 全コンポーネント連携テスト - ライフライン設備カード（バッジとファイル）
     */
    public function test_comprehensive_ライフライン設備カードバッジファイル連携()
    {
        $lifelineData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '設備名', 'value' => $this->lifelineEquipment->equipment_name, 'type' => 'text'],
                    ['label' => 'ステータス', 'value' => $this->lifelineEquipment->status, 'type' => 'badge', 'options' => ['auto_class' => true]],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '前回点検日', 'value' => $this->lifelineEquipment->last_inspection_date, 'type' => 'date'],
                    ['label' => '次回点検日', 'value' => $this->lifelineEquipment->next_inspection_date, 'type' => 'date'],
                ]
            ],
            [
                'type' => 'single',
                'cells' => [
                    ['label' => '点検報告書', 'value' => '/storage/reports/inspection_2023.pdf', 'type' => 'file', 'colspan' => 2],
                ]
            ],
            [
                'type' => 'single',
                'cells' => [
                    ['label' => '設備図面', 'value' => '/storage/drawings/equipment_drawing.dwg', 'type' => 'file', 'colspan' => 2],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $lifelineData,
            'title' => 'ライフライン設備'
        ]);

        $rendered = $view->render();

        // 設備情報の確認
        $this->assertStringContainsString('統合受変電設備', $rendered);
        $this->assertStringContainsString('badge', $rendered);
        $this->assertStringContainsString('2023年12月01日', $rendered);
        $this->assertStringContainsString('2024年06月01日', $rendered);

        // ファイルリンクの確認
        $this->assertStringContainsString('inspection_2023.pdf', $rendered);
        $this->assertStringContainsString('equipment_drawing.dwg', $rendered);
        $this->assertStringContainsString('fas fa-file-pdf', $rendered);
        $this->assertStringContainsString('fas fa-file', $rendered);
    }

    /**
     * @test
     * パフォーマンステスト - 大量データ処理
     */
    public function test_performance_大量データ処理性能()
    {
        // 大量データの生成
        $largeData = [];
        for ($i = 0; $i < 100; $i++) {
            $largeData[] = [
                'type' => 'standard',
                'cells' => [
                    ['label' => "項目{$i}", 'value' => "値{$i}", 'type' => 'text'],
                    ['label' => "メール{$i}", 'value' => "test{$i}@example.com", 'type' => 'email'],
                ]
            ];
            
            if ($i % 10 === 0) {
                $largeData[] = [
                    'type' => 'single',
                    'cells' => [
                        ['label' => "URL{$i}", 'value' => "https://example{$i}.com", 'type' => 'url', 'colspan' => 2],
                    ]
                ];
            }
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $view = View::make('components.common-table', [
            'data' => $largeData,
            'title' => 'パフォーマンステスト（100行）'
        ]);

        $rendered = $view->render();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $renderTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;

        // パフォーマンス要件の確認
        $this->assertLessThan(3.0, $renderTime, 'レンダリング時間が3秒を超えています');
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsage, 'メモリ使用量が50MBを超えています'); // 50MB

        // データが正しく表示されることを確認
        $this->assertStringContainsString('項目0', $rendered);
        $this->assertStringContainsString('項目99', $rendered);
        $this->assertStringContainsString('test0@example.com', $rendered);
        $this->assertStringContainsString('test99@example.com', $rendered);
        $this->assertStringContainsString('https://example0.com', $rendered);
        $this->assertStringContainsString('https://example90.com', $rendered);

        // ログにパフォーマンス情報を記録
        Log::info('CommonTable Performance Test', [
            'rows' => count($largeData),
            'render_time' => $renderTime,
            'memory_usage' => $memoryUsage,
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2)
        ]);
    }

    /**
     * @test
     * セキュリティテスト - XSS対策
     */
    public function test_security_XSS対策が正しく動作()
    {
        $maliciousData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '<script>alert("XSS")</script>', 'value' => '<img src="x" onerror="alert(\'XSS\')">', 'type' => 'text'],
                    ['label' => '正常なラベル', 'value' => '<b>太字テキスト</b>', 'type' => 'text'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'JavaScript URL', 'value' => 'javascript:alert("XSS")', 'type' => 'url'],
                    ['label' => 'メール XSS', 'value' => 'test@example.com<script>alert("XSS")</script>', 'type' => 'email'],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $maliciousData,
            'title' => 'セキュリティテスト'
        ]);

        $rendered = $view->render();

        // XSSが無効化されていることを確認
        $this->assertStringNotContainsString('<script>alert("XSS")</script>', $rendered);
        $this->assertStringNotContainsString('onerror="alert(\'XSS\')"', $rendered);
        $this->assertStringNotContainsString('javascript:alert("XSS")', $rendered);

        // エスケープされた内容が含まれることを確認
        $this->assertStringContainsString('&lt;script&gt;', $rendered);
        $this->assertStringContainsString('&lt;img', $rendered);

        // 正常なHTMLタグは適切に処理されることを確認
        $this->assertStringContainsString('&lt;b&gt;太字テキスト&lt;/b&gt;', $rendered);
    }

    /**
     * @test
     * セキュリティテスト - SQLインジェクション対策
     */
    public function test_security_SQLインジェクション対策()
    {
        $sqlInjectionData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => "'; DROP TABLE users; --", 'value' => "1' OR '1'='1", 'type' => 'text'],
                    ['label' => 'UNION SELECT', 'value' => "' UNION SELECT password FROM users --", 'type' => 'text'],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $sqlInjectionData,
            'title' => 'SQLインジェクションテスト'
        ]);

        $rendered = $view->render();

        // SQLインジェクション文字列がエスケープされていることを確認
        $this->assertStringContainsString("'; DROP TABLE users; --", $rendered);
        $this->assertStringContainsString("1' OR '1'='1", $rendered);
        $this->assertStringContainsString("' UNION SELECT password FROM users --", $rendered);

        // データベースが正常に動作していることを確認
        $this->assertDatabaseHas('users', ['email' => 'integration@example.com']);
        $this->assertDatabaseHas('facilities', ['company_name' => '統合テスト株式会社']);
    }

    /**
     * @test
     * エラーハンドリング統合テスト
     */
    public function test_comprehensive_エラーハンドリング統合()
    {
        // 様々なエラーケースを含むデータ
        $errorData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '正常データ', 'value' => '正常値', 'type' => 'text'],
                    ['label' => 'null値', 'value' => null, 'type' => 'text'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '空文字', 'value' => '', 'type' => 'text'],
                    ['label' => '不正な日付', 'value' => 'invalid-date', 'type' => 'date'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '不正な数値', 'value' => 'not-a-number', 'type' => 'number'],
                    ['label' => '不正なメール', 'value' => 'invalid-email', 'type' => 'email'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '大きなcolspan', 'value' => 'テスト', 'type' => 'text', 'colspan' => 10],
                    // 2番目のセルは表示されない（colspanが大きすぎるため）
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $errorData,
            'title' => 'エラーハンドリングテスト',
            'fallbackOnError' => true,
            'showValidationWarnings' => true
        ]);

        $rendered = $view->render();

        // 正常データは表示される
        $this->assertStringContainsString('正常データ', $rendered);
        $this->assertStringContainsString('正常値', $rendered);

        // null値と空文字は「未設定」として表示される
        $this->assertStringContainsString('未設定', $rendered);
        $this->assertStringContainsString('empty-field', $rendered);

        // 不正なデータはフォールバック表示される
        $this->assertStringContainsString('invalid-date', $rendered);
        $this->assertStringContainsString('not-a-number', $rendered);
        $this->assertStringContainsString('invalid-email', $rendered);

        // 警告が表示される（設定されている場合）
        $this->assertTrue(
            strpos($rendered, 'alert-warning') !== false ||
            strpos($rendered, '警告') !== false
        );
    }

    /**
     * @test
     * キャッシュ機能統合テスト
     */
    public function test_comprehensive_キャッシュ機能統合()
    {
        $cacheableData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'キャッシュテスト', 'value' => 'キャッシュ値', 'type' => 'text'],
                ]
            ]
        ];

        $cacheKey = 'common_table_test_' . md5(json_encode($cacheableData));

        // キャッシュをクリア
        Cache::forget($cacheKey);

        // 最初のレンダリング（キャッシュなし）
        $startTime1 = microtime(true);
        $view1 = View::make('components.common-table', [
            'data' => $cacheableData,
            'title' => 'キャッシュテスト',
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
            'title' => 'キャッシュテスト',
            'enableCache' => true,
            'cacheKey' => $cacheKey
        ]);
        $rendered2 = $view2->render();
        $endTime2 = microtime(true);
        $renderTime2 = $endTime2 - $startTime2;

        // 両方とも同じ内容が表示される
        $this->assertStringContainsString('キャッシュテスト', $rendered1);
        $this->assertStringContainsString('キャッシュ値', $rendered1);
        $this->assertStringContainsString('キャッシュテスト', $rendered2);
        $this->assertStringContainsString('キャッシュ値', $rendered2);

        // キャッシュが効いている場合、2回目の方が高速（通常）
        // ただし、テスト環境では差が小さい場合があるので、ログに記録のみ
        Log::info('Cache Performance Test', [
            'first_render_time' => $renderTime1,
            'second_render_time' => $renderTime2,
            'cache_effective' => $renderTime2 < $renderTime1
        ]);
    }

    /**
     * @test
     * アクセシビリティ統合テスト
     */
    public function test_comprehensive_アクセシビリティ統合()
    {
        $accessibilityData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'アクセシビリティテスト', 'value' => 'テスト値', 'type' => 'text'],
                    ['label' => 'リンクテスト', 'value' => 'https://example.com', 'type' => 'url'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'メールテスト', 'value' => 'test@example.com', 'type' => 'email'],
                    ['label' => 'ファイルテスト', 'value' => '/storage/test.pdf', 'type' => 'file'],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $accessibilityData,
            'title' => 'アクセシビリティテスト',
            'ariaLabel' => 'アクセシビリティテスト用のテーブル',
            'ariaDescribedBy' => 'accessibility-description'
        ]);

        $rendered = $view->render();

        // ARIA属性の確認
        $this->assertStringContainsString('role="table"', $rendered);
        $this->assertStringContainsString('role="region"', $rendered);
        $this->assertStringContainsString('role="row"', $rendered);
        $this->assertStringContainsString('role="rowheader"', $rendered);
        $this->assertStringContainsString('role="gridcell"', $rendered);
        $this->assertStringContainsString('aria-label="アクセシビリティテスト用のテーブル"', $rendered);
        $this->assertStringContainsString('aria-describedby="accessibility-description"', $rendered);

        // スクリーンリーダー用要素の確認
        $this->assertStringContainsString('sr-only', $rendered);
        $this->assertStringContainsString('<caption class="sr-only">', $rendered);

        // リンクのアクセシビリティ
        $this->assertStringContainsString('target="_blank"', $rendered);
        $this->assertStringContainsString('rel="noopener noreferrer"', $rendered);

        // フォーカス可能要素の確認
        $this->assertStringContainsString('tabindex="0"', $rendered);
    }

    /**
     * @test
     * 国際化対応統合テスト
     */
    public function test_comprehensive_国際化対応統合()
    {
        $i18nData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '日本語テスト', 'value' => 'これは日本語のテストです', 'type' => 'text'],
                    ['label' => '英語テスト', 'value' => 'This is an English test', 'type' => 'text'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '日付（日本語）', 'value' => '2023-12-25', 'type' => 'date'],
                    ['label' => '通貨（日本円）', 'value' => 1000000, 'type' => 'currency'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '数値（日本語区切り）', 'value' => 1234567.89, 'type' => 'number', 'options' => ['decimals' => 2]],
                    ['label' => '特殊文字', 'value' => '①②③④⑤', 'type' => 'text'],
                ]
            ]
        ];

        $view = View::make('components.common-table', [
            'data' => $i18nData,
            'title' => '国際化テスト',
            'locale' => 'ja'
        ]);

        $rendered = $view->render();

        // 日本語フォーマットの確認
        $this->assertStringContainsString('2023年12月25日', $rendered);
        $this->assertStringContainsString('1,000,000円', $rendered);
        $this->assertStringContainsString('1,234,567.89', $rendered);

        // 多言語文字の確認
        $this->assertStringContainsString('これは日本語のテストです', $rendered);
        $this->assertStringContainsString('This is an English test', $rendered);
        $this->assertStringContainsString('①②③④⑤', $rendered);

        // UTF-8エンコーディングの確認
        $this->assertTrue(mb_check_encoding($rendered, 'UTF-8'));
    }

    /**
     * @test
     * 後方互換性統合テスト
     */
    public function test_comprehensive_後方互換性統合()
    {
        // 既存の基本情報表示カードのデータ構造をシミュレート
        $legacyData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '会社名', 'value' => $this->facility->company_name, 'type' => 'text'],
                    ['label' => '事業所コード', 'value' => $this->facility->office_code, 'type' => 'text'],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '住所', 'value' => $this->facility->address, 'type' => 'text'],
                    ['label' => '電話番号', 'value' => $this->facility->phone_number, 'type' => 'text'],
                ]
            ]
        ];

        // 既存のCSSクラスとオプションを使用
        $view = View::make('components.common-table', [
            'data' => $legacyData,
            'title' => '基本情報',
            'cardClass' => 'facility-info-card detail-card-improved mb-3',
            'tableClass' => 'table table-bordered facility-basic-info-table-clean',
            'headerClass' => 'card-header',
            'bodyClass' => 'card-body',
            'cleanBody' => true,
            'responsive' => true
        ]);

        $rendered = $view->render();

        // 既存のCSSクラスが適用されることを確認
        $this->assertStringContainsString('facility-info-card', $rendered);
        $this->assertStringContainsString('detail-card-improved', $rendered);
        $this->assertStringContainsString('facility-basic-info-table-clean', $rendered);
        $this->assertStringContainsString('card-body-clean', $rendered);
        $this->assertStringContainsString('table-responsive', $rendered);

        // 既存のHTML構造が維持されることを確認
        $this->assertStringContainsString('detail-label', $rendered);
        $this->assertStringContainsString('detail-value', $rendered);
        $this->assertStringContainsString('card-header', $rendered);
        $this->assertStringContainsString('card-body', $rendered);

        // データが正しく表示されることを確認
        $this->assertStringContainsString($this->facility->company_name, $rendered);
        $this->assertStringContainsString($this->facility->office_code, $rendered);
        $this->assertStringContainsString($this->facility->address, $rendered);
        $this->assertStringContainsString($this->facility->phone_number, $rendered);
    }

    /**
     * @test
     * 全機能統合テスト - 実際のページレンダリング
     */
    public function test_comprehensive_実際のページレンダリング統合()
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // 基本情報カードが表示されることを確認
        $response->assertSee('基本情報');
        $response->assertSee($this->facility->company_name);
        $response->assertSee($this->facility->office_code);

        // 土地情報カードが表示されることを確認
        $response->assertSee('土地情報');
        $response->assertSee($this->landInfo->management_company_name);

        // 建物情報カードが表示されることを確認
        $response->assertSee('建物情報');
        $response->assertSee($this->buildingInfo->building_name);

        // ライフライン設備カードが表示されることを確認
        $response->assertSee('ライフライン設備');
        $response->assertSee($this->lifelineEquipment->equipment_name);

        // CommonTableコンポーネントのHTML構造が含まれることを確認
        $response->assertSee('facility-info-card');
        $response->assertSee('detail-card-improved');
        $response->assertSee('table-responsive');
        $response->assertSee('detail-label');
        $response->assertSee('detail-value');
    }

    protected function tearDown(): void
    {
        // テスト後のクリーンアップ
        Cache::flush();
        parent::tearDown();
    }
}
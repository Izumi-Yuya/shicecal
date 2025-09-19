<?php

namespace Tests\Feature\Components;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CommonTableCardWrapperIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_integrates_card_wrapper_with_common_table()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '会社名', 'value' => 'テスト会社', 'type' => 'text'],
                    ['label' => '事業所コード', 'value' => 'TEST001', 'type' => 'badge'],
                ]
            ]
        ];

        $view = $this->blade(
            '<x-common-table :data="$data" title="基本情報" />',
            ['data' => $data]
        );

        // カードラッパーの構造を確認
        $view->assertSee('facility-info-card');
        $view->assertSee('detail-card-improved');
        $view->assertSee('card-header');
        $view->assertSee('基本情報');
        
        // テーブルコンテンツを確認
        $view->assertSee('会社名');
        $view->assertSee('テスト会社');
        $view->assertSee('事業所コード');
        $view->assertSee('TEST001');
    }

    /** @test */
    public function it_works_without_title_and_header()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'ラベル', 'value' => '値', 'type' => 'text'],
                ]
            ]
        ];

        $view = $this->blade(
            '<x-common-table :data="$data" :showHeader="false" />',
            ['data' => $data]
        );

        // ヘッダーがないことを確認
        $view->assertDontSee('card-header');
        
        // カードとテーブルは存在することを確認
        $view->assertSee('facility-info-card');
        $view->assertSee('ラベル');
        $view->assertSee('値');
    }

    /** @test */
    public function it_applies_custom_card_classes_through_wrapper()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'テスト', 'value' => 'データ', 'type' => 'text'],
                ]
            ]
        ];

        $view = $this->blade(
            '<x-common-table 
                :data="$data" 
                title="カスタムタイトル"
                cardClass="custom-card-class"
                headerClass="custom-header-class" />',
            ['data' => $data]
        );

        $view->assertSee('custom-card-class');
        $view->assertSee('custom-header-class');
        $view->assertSee('カスタムタイトル');
    }

    /** @test */
    public function it_maintains_accessibility_features_in_integration()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'アクセシビリティ', 'value' => 'テスト', 'type' => 'text'],
                ]
            ]
        ];

        $view = $this->blade(
            '<x-common-table 
                :data="$data" 
                title="アクセシビリティテスト"
                ariaLabel="テストテーブル" />',
            ['data' => $data]
        );

        $view->assertSee('role="region"', false);
        $view->assertSee('aria-label="テストテーブル"', false);
        $view->assertSeeInOrder(['aria-labelledby=', 'id='], false);
    }

    /** @test */
    public function it_handles_complex_table_data_with_card_wrapper()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '会社名', 'value' => 'テスト会社', 'type' => 'text'],
                    ['label' => 'メール', 'value' => 'test@example.com', 'type' => 'email'],
                ]
            ],
            [
                'type' => 'single',
                'cells' => [
                    ['label' => 'URL', 'value' => 'https://example.com', 'type' => 'url', 'colspan' => 3],
                ]
            ]
        ];

        $view = $this->blade(
            '<x-common-table :data="$data" title="複雑なデータ" />',
            ['data' => $data]
        );

        // カードラッパーの構造
        $view->assertSee('facility-info-card');
        $view->assertSee('複雑なデータ');
        
        // 複雑なデータの表示
        $view->assertSee('テスト会社');
        $view->assertSee('test@example.com');
        $view->assertSee('https://example.com');
        $view->assertSee('colspan="3"', false);
    }
}
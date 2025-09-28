<?php

namespace Tests\Feature\Components;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommonTableAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test ARIA attributes are properly set
     */
    public function test_aria_attributes_are_properly_set()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'テストラベル', 'value' => 'テスト値', 'type' => 'text'],
                    ['label' => '空フィールド', 'value' => null, 'type' => 'text'],
                ],
            ],
        ];

        $html = $this->blade(
            '<x-common-table :data="$data" title="アクセシビリティテスト" />',
            ['data' => $data]
        );

        // テーブルのrole属性
        $html->assertSee('role="table"', false);

        // aria-label属性
        $html->assertSee('aria-label="アクセシビリティテストの詳細情報"', false);

        // region role for table container
        $html->assertSee('role="region"', false);

        // スクリーンリーダー用の説明
        $html->assertSee('class="sr-only"', false);
        $html->assertSee('アクセシビリティテストの詳細情報テーブル', false);

        // テーブルキャプション
        $html->assertSee('<caption class="sr-only">アクセシビリティテストの詳細情報</caption>', false);
    }

    /**
     * Test row ARIA attributes
     */
    public function test_row_aria_attributes()
    {
        $data = [
            [
                'type' => 'grouped',
                'cells' => [
                    ['label' => 'グループラベル', 'value' => 'グループ値', 'type' => 'text'],
                ],
            ],
            [
                'type' => 'single',
                'cells' => [
                    ['label' => '単一ラベル', 'value' => '単一値', 'type' => 'text'],
                ],
            ],
        ];

        $html = $this->blade(
            '<x-common-table :data="$data" />',
            ['data' => $data]
        );

        // 行のrole属性
        $html->assertSee('role="row"', false);

        // 行タイプ別のaria-label
        $html->assertSee('aria-label="グループラベルの情報行"', false);
        $html->assertSee('aria-label="単一ラベルの情報行"', false);

        // データ属性
        $html->assertSee('data-row-type="grouped"', false);
        $html->assertSee('data-row-type="single"', false);
    }

    /**
     * Test cell ARIA attributes
     */
    public function test_cell_aria_attributes()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'メールアドレス', 'value' => 'test@example.com', 'type' => 'email'],
                    ['label' => 'ウェブサイト', 'value' => 'https://example.com', 'type' => 'url'],
                    ['label' => '空項目', 'value' => null, 'type' => 'text'],
                ],
            ],
        ];

        $html = $this->blade(
            '<x-common-table :data="$data" />',
            ['data' => $data]
        );

        // ラベルセルの属性
        $html->assertSee('scope="row"', false);
        $html->assertSee('role="rowheader"', false);

        // 値セルの属性
        $html->assertSee('role="gridcell"', false);

        // セルタイプ別のaria-label
        $html->assertSee('aria-label="メールアドレス: test@example.com"', false);
        $html->assertSee('aria-label="ウェブサイト: https://example.com"', false);
        $html->assertSee('aria-label="未設定の項目"', false);

        // データ属性
        $html->assertSee('data-cell-type="email"', false);
        $html->assertSee('data-cell-type="url"', false);
        $html->assertSee('data-is-empty="true"', false);
        $html->assertSee('data-is-empty="false"', false);
    }

    /**
     * Test responsive attributes
     */
    public function test_responsive_attributes()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'テスト', 'value' => 'レスポンシブ', 'type' => 'text'],
                ],
            ],
        ];

        $html = $this->blade(
            '<x-common-table :data="$data" :responsive="true" />',
            ['data' => $data]
        );

        // レスポンシブクラス
        $html->assertSee('table-responsive table-responsive-md', false);

        // レスポンシブデータ属性
        $html->assertSee('data-responsive="true"', false);
        $html->assertSee('data-mobile-optimized="true"', false);
    }

    /**
     * Test fallback component accessibility
     */
    public function test_fallback_component_accessibility()
    {
        $html = $this->blade(
            '<x-common-table.fallback title="エラーテスト" message="テストエラーメッセージ" :showRetry="true" />',
            []
        );

        // Alert role
        $html->assertSee('role="alert"', false);

        // Live region
        $html->assertSee('aria-live="polite"', false);

        // aria-hidden for decorative icons
        $html->assertSee('aria-hidden="true"', false);

        // スクリーンリーダー用の説明
        $html->assertSee('class="sr-only"', false);
        $html->assertSee('データの表示に問題が発生しました', false);
    }

    /**
     * Test keyboard navigation support
     */
    public function test_keyboard_navigation_support()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'フォーカステスト', 'value' => 'キーボードナビゲーション', 'type' => 'text'],
                ],
            ],
        ];

        $html = $this->blade(
            '<x-common-table :data="$data" />',
            ['data' => $data]
        );

        // CSS classes for focus styles should be present in the rendered HTML
        $html->assertSee('detail-label', false);
        $html->assertSee('detail-value', false);

        // The HTML structure should support keyboard navigation
        // (Focus styles are handled by CSS)
    }

    /**
     * Test screen reader content
     */
    public function test_screen_reader_content()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'スクリーンリーダー', 'value' => 'テスト', 'type' => 'text'],
                ],
            ],
        ];

        $html = $this->blade(
            '<x-common-table :data="$data" title="スクリーンリーダーテスト" />',
            ['data' => $data]
        );

        // スクリーンリーダー専用コンテンツ
        $html->assertSee('class="sr-only"', false);
        $html->assertSee('スクリーンリーダーテストの詳細情報テーブル', false);
        $html->assertSee('1行のデータが含まれています', false);
    }

    /**
     * Test no-JS fallback data structure
     */
    public function test_no_js_fallback_with_data()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'No-JSテスト', 'value' => 'フォールバック', 'type' => 'text'],
                    ['label' => '空項目', 'value' => null, 'type' => 'text'],
                ],
            ],
        ];

        $html = $this->blade(
            '<x-common-table.fallback title="No-JSテスト" :data="$data" />',
            ['data' => $data]
        );

        // 基本的なテーブル構造
        $html->assertSee('role="table"', false);
        $html->assertSee('scope="row"', false);

        // データの表示
        $html->assertSee('No-JSテスト', false);
        $html->assertSee('フォールバック', false);
        $html->assertSee('未設定', false);

        // 空フィールドクラス
        $html->assertSee('empty-field', false);
    }

    /**
     * Test high contrast mode compatibility
     */
    public function test_high_contrast_mode_compatibility()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'ハイコントラスト', 'value' => 'テスト', 'type' => 'text'],
                ],
            ],
        ];

        $html = $this->blade(
            '<x-common-table :data="$data" />',
            ['data' => $data]
        );

        // 基本的なクラスが存在することを確認
        // (ハイコントラストスタイルはCSSで処理)
        $html->assertSee('detail-label', false);
        $html->assertSee('detail-value', false);
        $html->assertSee('facility-basic-info-table-clean', false);
    }
}

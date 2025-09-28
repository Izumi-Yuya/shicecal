<?php

namespace Tests\Feature\Components;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommonTableMainTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test basic CommonTable component rendering
     * 要件: 1.1, 1.2, 2.1
     */
    public function test_common_table_renders_with_basic_data()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'テストラベル', 'value' => 'テスト値', 'type' => 'text'],
                    ['label' => '会社名', 'value' => '株式会社テスト', 'type' => 'text'],
                ],
            ],
        ];

        $view = $this->blade(
            '<x-common-table :data="$data" title="テストテーブル" />',
            ['data' => $data]
        );

        $view->assertSee('テストテーブル');
        $view->assertSee('テストラベル');
        $view->assertSee('テスト値');
        $view->assertSee('会社名');
        $view->assertSee('株式会社テスト');
    }

    /**
     * Test CommonTable with empty data
     * 要件: 1.1, 1.2
     */
    public function test_common_table_handles_empty_data()
    {
        $view = $this->blade(
            '<x-common-table :data="[]" title="空のテーブル" />',
            []
        );

        $view->assertSee('空のテーブル');
        $view->assertSee('データがありません');
    }

    /**
     * Test CommonTable without title
     * 要件: 1.1, 1.2
     */
    public function test_common_table_renders_without_title()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'ラベル', 'value' => '値', 'type' => 'text'],
                ],
            ],
        ];

        $view = $this->blade(
            '<x-common-table :data="$data" />',
            ['data' => $data]
        );

        $view->assertSee('ラベル');
        $view->assertSee('値');
        $view->assertDontSee('card-header');
    }

    /**
     * Test CommonTable with multiple row types
     * 要件: 2.1
     */
    public function test_common_table_handles_multiple_row_types()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '標準ラベル', 'value' => '標準値', 'type' => 'text'],
                ],
            ],
            [
                'type' => 'single',
                'cells' => [
                    ['label' => '単一ラベル', 'value' => '単一値', 'type' => 'text', 'colspan' => 2],
                ],
            ],
            [
                'type' => 'grouped',
                'cells' => [
                    ['label' => 'グループラベル', 'value' => 'グループ値', 'type' => 'text', 'rowspan' => 2],
                ],
            ],
        ];

        $view = $this->blade(
            '<x-common-table :data="$data" title="複合テーブル" />',
            ['data' => $data]
        );

        $view->assertSee('標準ラベル');
        $view->assertSee('標準値');
        $view->assertSee('単一ラベル');
        $view->assertSee('単一値');
        $view->assertSee('グループラベル');
        $view->assertSee('グループ値');
    }

    /**
     * Test CommonTable CSS classes application
     * 要件: 1.2, 1.3
     */
    public function test_common_table_applies_css_classes()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'ラベル', 'value' => '値', 'type' => 'text'],
                ],
            ],
        ];

        $view = $this->blade(
            '<x-common-table :data="$data" title="CSSテスト" cardClass="custom-card" tableClass="custom-table" />',
            ['data' => $data]
        );

        $view->assertSee('custom-card', false);
        $view->assertSee('custom-table', false);
    }

    /**
     * Test CommonTable responsive functionality
     * 要件: 3.4
     */
    public function test_common_table_responsive_functionality()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'ラベル', 'value' => '値', 'type' => 'text'],
                ],
            ],
        ];

        // レスポンシブ有効
        $view = $this->blade(
            '<x-common-table :data="$data" :responsive="true" />',
            ['data' => $data]
        );
        $view->assertSee('table-responsive', false);

        // レスポンシブ無効
        $view = $this->blade(
            '<x-common-table :data="$data" :responsive="false" />',
            ['data' => $data]
        );
        $view->assertDontSee('table-responsive');
    }

    /**
     * Test CommonTable with invalid data structure
     * 要件: 1.1, 1.2
     */
    public function test_common_table_handles_invalid_data()
    {
        // 無効なデータ構造
        $invalidData = [
            'invalid_structure',
            ['cells' => null], // cellsがnull
            ['type' => 'standard'], // cellsが存在しない
        ];

        $view = $this->blade(
            '<x-common-table :data="$data" title="無効データテスト" />',
            ['data' => $invalidData]
        );

        // エラーが発生せずに表示される
        $view->assertSee('無効データテスト');
        // 無効なデータは無視されるが、空のtbodyが残る
        $view->assertSee('<tbody>', false);
    }

    /**
     * Test CommonTable data array processing
     * 要件: 1.1, 2.1
     */
    public function test_common_table_processes_data_array_correctly()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'ラベル1', 'value' => '値1', 'type' => 'text'],
                    ['label' => 'ラベル2', 'value' => '値2', 'type' => 'badge'],
                ],
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'ラベル3', 'value' => '値3', 'type' => 'email'],
                ],
            ],
        ];

        $view = $this->blade(
            '<x-common-table :data="$data" />',
            ['data' => $data]
        );

        // 全ての行とセルが正しく処理される
        $view->assertSee('ラベル1');
        $view->assertSee('値1');
        $view->assertSee('ラベル2');
        $view->assertSee('値2');
        $view->assertSee('ラベル3');
        $view->assertSee('値3');
    }

    /**
     * Test CommonTable row component calls
     * 要件: 2.1
     */
    public function test_common_table_calls_row_components()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'テストラベル', 'value' => 'テスト値', 'type' => 'text'],
                ],
            ],
        ];

        $view = $this->blade(
            '<x-common-table :data="$data" />',
            ['data' => $data]
        );

        // 行コンポーネントが呼び出されていることを確認
        $view->assertSee('data-row-type="standard"', false);
        $view->assertSee('standard-row', false);
    }

    /**
     * Test CommonTable with cleanBody option
     * 要件: 7.2
     */
    public function test_common_table_clean_body_option()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'ラベル', 'value' => '値', 'type' => 'text'],
                ],
            ],
        ];

        // cleanBody有効
        $view = $this->blade(
            '<x-common-table :data="$data" :cleanBody="true" />',
            ['data' => $data]
        );
        $view->assertSee('card-body-clean', false);

        // cleanBody無効
        $view = $this->blade(
            '<x-common-table :data="$data" :cleanBody="false" />',
            ['data' => $data]
        );
        $view->assertDontSee('card-body-clean');
    }
}

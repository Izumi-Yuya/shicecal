<?php

namespace Tests\Feature\Components;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommonTableSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test responsive table functionality
     * 要件: 3.4
     */
    public function test_responsive_table_functionality()
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
     * Test CSS class customization functionality
     * 要件: 7.2
     */
    public function test_css_class_customization()
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
            '<x-common-table 
                :data="$data" 
                title="カスタムテーブル"
                cardClass="custom-card-class"
                tableClass="custom-table-class"
                headerClass="custom-header-class"
                bodyClass="custom-body-class"
                wrapperClass="custom-wrapper-class"
            />',
            ['data' => $data]
        );

        $view->assertSee('custom-card-class', false);
        $view->assertSee('custom-table-class', false);
        $view->assertSee('custom-header-class', false);
        $view->assertSee('custom-body-class', false);
        $view->assertSee('custom-wrapper-class', false);
    }

    /**
     * Test table styling application
     * 要件: 7.3
     */
    public function test_table_styling_application()
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
            '<x-common-table :data="$data" title="スタイルテスト" />',
            ['data' => $data]
        );

        // デフォルトのスタイリングクラスが適用される
        $view->assertSee('facility-info-card', false);
        $view->assertSee('detail-card-improved', false);
        $view->assertSee('table-bordered', false);
        $view->assertSee('facility-basic-info-table-clean', false);
        $view->assertSee('card-body-clean', false);
    }

    /**
     * Test custom empty message
     * 要件: 7.2
     */
    public function test_custom_empty_message()
    {
        $view = $this->blade(
            '<x-common-table :data="[]" emptyMessage="カスタム空メッセージ" />',
            []
        );

        $view->assertSee('カスタム空メッセージ');
        $view->assertDontSee('データがありません');
    }

    /**
     * Test header display control
     * 要件: 7.2
     */
    public function test_header_display_control()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'ラベル', 'value' => '値', 'type' => 'text'],
                ],
            ],
        ];

        // ヘッダー表示
        $view = $this->blade(
            '<x-common-table :data="$data" title="テストタイトル" :showHeader="true" />',
            ['data' => $data]
        );
        $view->assertSee('テストタイトル');

        // ヘッダー非表示
        $view = $this->blade(
            '<x-common-table :data="$data" title="テストタイトル" :showHeader="false" />',
            ['data' => $data]
        );
        $view->assertDontSee('テストタイトル');
    }

    /**
     * Test table attributes
     * 要件: 7.2
     */
    public function test_table_attributes()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'ラベル', 'value' => '値', 'type' => 'text'],
                ],
            ],
        ];

        $attributes = [
            'data-test' => 'test-value',
            'id' => 'custom-table-id',
        ];

        $view = $this->blade(
            '<x-common-table :data="$data" :tableAttributes="$attributes" />',
            ['data' => $data, 'attributes' => $attributes]
        );

        $view->assertSee('data-test="test-value"', false);
        $view->assertSee('id="custom-table-id"', false);
    }

    /**
     * Test ARIA label for accessibility
     * 要件: 7.2
     */
    public function test_aria_label_accessibility()
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
            '<x-common-table :data="$data" ariaLabel="施設情報テーブル" />',
            ['data' => $data]
        );

        $view->assertSee('aria-label="施設情報テーブル"', false);
        $view->assertSee('role="table"', false);
    }

    /**
     * Test clean body option
     * 要件: 7.2
     */
    public function test_clean_body_option()
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
        $view->assertSee('card-body', false);
    }

    /**
     * Test combined custom classes
     * 要件: 7.2
     */
    public function test_combined_custom_classes()
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
            '<x-common-table 
                :data="$data" 
                :cleanBody="true"
                bodyClass="additional-body-class"
                wrapperClass="additional-wrapper-class"
                :responsive="true"
            />',
            ['data' => $data]
        );

        // 基本クラスと追加クラスが両方適用される
        $view->assertSee('card-body-clean', false);
        $view->assertSee('additional-body-class', false);
        $view->assertSee('table-responsive', false);
        $view->assertSee('additional-wrapper-class', false);
    }

    /**
     * Test data validation with enhanced settings
     * 要件: 7.2
     */
    public function test_data_validation_with_enhanced_settings()
    {
        // 有効なデータと無効なデータの混合
        $mixedData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '有効ラベル', 'value' => '有効値', 'type' => 'text'],
                ],
            ],
            'invalid_data',
            [
                'type' => 'standard',
                'cells' => null, // 無効なcells
            ],
            [
                'type' => 'standard',
                'cells' => [], // 空のcells
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '別の有効ラベル', 'value' => '別の有効値', 'type' => 'text'],
                ],
            ],
        ];

        $view = $this->blade(
            '<x-common-table :data="$data" title="混合データテスト" />',
            ['data' => $mixedData]
        );

        // 有効なデータのみが表示される
        $view->assertSee('有効ラベル');
        $view->assertSee('有効値');
        $view->assertSee('別の有効ラベル');
        $view->assertSee('別の有効値');

        // 無効なデータは無視される
        $view->assertDontSee('データがありません');
    }

    /**
     * Test row index and key passing
     * 要件: 7.2
     */
    public function test_row_index_and_key_passing()
    {
        $data = [
            [
                'type' => 'standard',
                'key' => 'row-1',
                'cells' => [
                    ['label' => 'ラベル1', 'value' => '値1', 'type' => 'text'],
                ],
            ],
            [
                'type' => 'standard',
                'key' => 'row-2',
                'cells' => [
                    ['label' => 'ラベル2', 'value' => '値2', 'type' => 'text'],
                ],
            ],
        ];

        $view = $this->blade(
            '<x-common-table :data="$data" />',
            ['data' => $data]
        );

        // 行のキーとインデックスが正しく渡される
        $view->assertSee('data-row-key="row-1"', false);
        $view->assertSee('data-row-key="row-2"', false);
        $view->assertSee('data-row-index="0"', false);
        $view->assertSee('data-row-index="1"', false);
    }
}

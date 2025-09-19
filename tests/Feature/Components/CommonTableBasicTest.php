<?php

namespace Tests\Feature\Components;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CommonTableBasicTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_render_empty_common_table_component()
    {
        $view = $this->blade('<x-common-table />');
        
        $view->assertSee('データがありません');
        $view->assertSeeInOrder(['table', 'tbody'], false);
    }

    /** @test */
    public function it_can_render_common_table_with_title()
    {
        $view = $this->blade('<x-common-table title="テストタイトル" />');
        
        $view->assertSee('テストタイトル');
        $view->assertSee('データがありません');
    }

    /** @test */
    public function it_can_render_common_table_with_basic_data()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'テストラベル', 'value' => 'テスト値', 'type' => 'text'],
                ]
            ]
        ];

        $view = $this->blade('<x-common-table :data="$data" />', ['data' => $data]);
        
        $view->assertSee('テストラベル');
        $view->assertSee('テスト値');
        $view->assertDontSee('データがありません');
    }

    /** @test */
    public function it_handles_empty_values_correctly()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '空フィールド', 'value' => null, 'type' => 'text'],
                ]
            ]
        ];

        $view = $this->blade('<x-common-table :data="$data" />', ['data' => $data]);
        
        $view->assertSee('空フィールド');
        $view->assertSee('未設定');
        $view->assertSee('empty-field');
    }

    /** @test */
    public function it_applies_correct_css_classes()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'ラベル', 'value' => '値', 'type' => 'text'],
                ]
            ]
        ];

        $view = $this->blade('<x-common-table :data="$data" />', ['data' => $data]);
        
        $view->assertSee('detail-label');
        $view->assertSee('detail-value');
        $view->assertSee('facility-basic-info-table-clean');
    }

    /** @test */
    public function it_supports_different_cell_types()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'バッジ', 'value' => 'テストバッジ', 'type' => 'badge'],
                    ['label' => 'メール', 'value' => 'test@example.com', 'type' => 'email'],
                    ['label' => 'URL', 'value' => 'https://example.com', 'type' => 'url'],
                ]
            ]
        ];

        $view = $this->blade('<x-common-table :data="$data" />', ['data' => $data]);
        
        $view->assertSee('badge bg-primary');
        $view->assertSee('mailto:test@example.com');
        $view->assertSee('https://example.com');
        $view->assertSee('fas fa-envelope');
        $view->assertSee('fas fa-external-link-alt');
    }
}
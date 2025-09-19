<?php

namespace Tests\Feature\Components;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CommonTableRowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_standard_row_with_label_value_pairs()
    {
        $cells = [
            ['label' => '会社名', 'value' => 'テスト会社', 'type' => 'text'],
            ['label' => '事業所コード', 'value' => 'TEST001', 'type' => 'badge'],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="standard" />', [
            'cells' => $cells
        ]);

        $view->assertSee('会社名');
        $view->assertSee('テスト会社');
        $view->assertSee('事業所コード');
        $view->assertSee('TEST001');
        $view->assertSee('data-row-type="standard"', false);
    }

    /** @test */
    public function it_renders_single_row_with_colspan()
    {
        $cells = [
            ['label' => 'URL', 'value' => 'https://example.com', 'type' => 'url', 'colspan' => 3],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="single" />', [
            'cells' => $cells
        ]);

        $view->assertSee('https://example.com');
        $view->assertSee('data-row-type="single"', false);
        $view->assertSee('colspan="3"', false);
    }

    /** @test */
    public function it_renders_grouped_row_with_rowspan()
    {
        $cells = [
            ['label' => 'グループラベル', 'value' => '値1', 'type' => 'text', 'rowspan' => 2],
            ['label' => 'サブラベル1', 'value' => 'サブ値1', 'type' => 'text'],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="grouped" />', [
            'cells' => $cells
        ]);

        $view->assertSee('グループラベル');
        $view->assertSee('値1');
        $view->assertSee('サブラベル1');
        $view->assertSee('サブ値1');
        $view->assertSee('data-row-type="grouped"', false);
        $view->assertSee('rowspan="2"', false);
    }

    /** @test */
    public function it_handles_empty_cells_gracefully()
    {
        $cells = [];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="standard" />', [
            'cells' => $cells
        ]);

        $view->assertSee('セルデータがありません');
    }

    /** @test */
    public function it_handles_invalid_cell_data()
    {
        $cells = [
            'invalid_cell_data',
            ['label' => '有効なセル', 'value' => 'テスト値', 'type' => 'text'],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="standard" />', [
            'cells' => $cells
        ]);

        $view->assertSee('無効なセルデータ');
        $view->assertSee('有効なセル');
        $view->assertSee('テスト値');
    }

    /** @test */
    public function it_validates_row_type_and_falls_back_to_standard()
    {
        $cells = [
            ['label' => 'テストラベル', 'value' => 'テスト値', 'type' => 'text'],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="invalid_type" />', [
            'cells' => $cells
        ]);

        $view->assertSee('data-row-type="standard"', false);
    }

    /** @test */
    public function it_applies_correct_css_classes_for_row_types()
    {
        $cells = [
            ['label' => 'テスト', 'value' => '値', 'type' => 'text'],
        ];

        // Standard row
        $standardView = $this->blade('<x-common-table.row :cells="$cells" type="standard" />', [
            'cells' => $cells
        ]);
        $standardView->assertSee('class="standard-row"', false);

        // Grouped row
        $groupedView = $this->blade('<x-common-table.row :cells="$cells" type="grouped" />', [
            'cells' => $cells
        ]);
        $groupedView->assertSee('class="grouped-row"', false);

        // Single row
        $singleView = $this->blade('<x-common-table.row :cells="$cells" type="single" />', [
            'cells' => $cells
        ]);
        $singleView->assertSee('class="single-row"', false);
    }

    /** @test */
    public function it_includes_accessibility_attributes()
    {
        $cells = [
            ['label' => 'テストラベル', 'value' => 'テスト値', 'type' => 'text'],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="standard" rowIndex="1" />', [
            'cells' => $cells
        ]);

        $view->assertSee('role="row"', false);
        $view->assertSee('aria-label="標準情報行"', false);
        $view->assertSee('data-row-index="1"', false);
    }

    /** @test */
    public function it_handles_multiple_column_layouts()
    {
        $cells = [
            ['label' => 'ラベル1', 'value' => '値1', 'type' => 'text', 'colspan' => 2],
            ['label' => 'ラベル2', 'value' => '値2', 'type' => 'text', 'colspan' => 1],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="standard" />', [
            'cells' => $cells
        ]);

        $view->assertSee('ラベル1');
        $view->assertSee('値1');
        $view->assertSee('ラベル2');
        $view->assertSee('値2');
        $view->assertSee('colspan="2"', false);
    }

    /** @test */
    public function it_handles_empty_and_null_values_correctly()
    {
        $cells = [
            ['label' => '空の値', 'value' => '', 'type' => 'text'],
            ['label' => 'null値', 'value' => null, 'type' => 'text'],
            ['label' => 'ゼロ値', 'value' => '0', 'type' => 'text'],
            ['label' => '数値ゼロ', 'value' => 0, 'type' => 'number'],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="standard" />', [
            'cells' => $cells
        ]);

        $view->assertSee('空の値');
        $view->assertSee('null値');
        $view->assertSee('ゼロ値');
        $view->assertSee('数値ゼロ');
        // ゼロ値は空として扱われないことを確認
        $view->assertSee('0');
    }

    /** @test */
    public function it_renders_single_row_with_label_and_value_combined()
    {
        $cells = [
            ['label' => 'ウェブサイト', 'value' => 'https://example.com', 'type' => 'url', 'colspan' => 2],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="single" />', [
            'cells' => $cells
        ]);

        // single行では、ラベルと値が1つのセルに結合される
        $view->assertSee('https://example.com');
        $view->assertSee('data-row-type="single"', false);
        $view->assertSee('colspan="2"', false);
    }

    /** @test */
    public function it_handles_complex_grouped_data_structure()
    {
        $cells = [
            [
                'label' => '管理会社情報', 
                'value' => '株式会社テスト管理', 
                'type' => 'text', 
                'rowspan' => 3,
                'label_colspan' => 1
            ],
            ['label' => '担当者', 'value' => '田中太郎', 'type' => 'text'],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="grouped" />', [
            'cells' => $cells
        ]);

        $view->assertSee('管理会社情報');
        $view->assertSee('株式会社テスト管理');
        $view->assertSee('担当者');
        $view->assertSee('田中太郎');
        $view->assertSee('rowspan="3"', false);
    }
}
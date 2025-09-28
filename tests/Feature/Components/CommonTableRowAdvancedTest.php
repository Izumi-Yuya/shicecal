<?php

namespace Tests\Feature\Components;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommonTableRowAdvancedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_handles_complex_multi_level_grouping()
    {
        $cells = [
            [
                'label' => '管理会社',
                'value' => '株式会社ABC管理',
                'type' => 'text',
                'rowspan' => 4,
                'label_colspan' => 1,
            ],
            [
                'label' => '代表者',
                'value' => '田中太郎',
                'type' => 'text',
                'rowspan' => 1,
            ],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="grouped" rowIndex="0" />', [
            'cells' => $cells,
        ]);

        // グループラベルが正しくrowspanで表示される
        $view->assertSee('管理会社');
        $view->assertSee('株式会社ABC管理');
        $view->assertSee('rowspan="4"', false);

        // サブ項目が正しく表示される
        $view->assertSee('代表者');
        $view->assertSee('田中太郎');

        // 行タイプが正しく設定される
        $view->assertSee('data-row-type="grouped"', false);
        $view->assertSee('class="grouped-row"', false);
    }

    /** @test */
    public function it_handles_single_row_with_complex_content()
    {
        $cells = [
            [
                'label' => '備考',
                'value' => 'この施設は重要な設備を含んでいるため、特別な管理が必要です。',
                'type' => 'text',
                'colspan' => 4,
            ],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="single" />', [
            'cells' => $cells,
        ]);

        // ラベルと値が結合されて表示される
        $view->assertSee('備考: この施設は重要な設備を含んでいるため、特別な管理が必要です。');
        $view->assertSee('colspan="4"', false);
        $view->assertSee('data-row-type="single"', false);
    }

    /** @test */
    public function it_handles_mixed_colspan_and_rowspan()
    {
        $cells = [
            [
                'label' => '所在地情報',
                'value' => '東京都渋谷区',
                'type' => 'text',
                'rowspan' => 2,
                'colspan' => 1,
            ],
            [
                'label' => '詳細住所',
                'value' => '1-1-1 テストビル',
                'type' => 'text',
                'colspan' => 2,
            ],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="grouped" />', [
            'cells' => $cells,
        ]);

        $view->assertSee('所在地情報');
        $view->assertSee('東京都渋谷区');
        $view->assertSee('詳細住所');
        $view->assertSee('1-1-1 テストビル');
        $view->assertSee('rowspan="2"', false);
        $view->assertSee('colspan="2"', false);
    }

    /** @test */
    public function it_validates_and_sanitizes_span_values()
    {
        $cells = [
            [
                'label' => 'テスト',
                'value' => '値',
                'type' => 'text',
                'rowspan' => 15, // 最大10を超える値
                'colspan' => 20,  // 最大12を超える値
            ],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="standard" />', [
            'cells' => $cells,
        ]);

        // 値が適切に制限されることを確認
        $view->assertSee('rowspan="10"', false); // 最大値に制限
        $view->assertSee('colspan="12"', false); // 最大値に制限
    }

    /** @test */
    public function it_handles_empty_values_in_grouped_rows()
    {
        $cells = [
            [
                'label' => '管理情報',
                'value' => '', // 空の値
                'type' => 'text',
                'rowspan' => 2,
            ],
            [
                'label' => '担当者',
                'value' => null, // null値
                'type' => 'text',
            ],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="grouped" />', [
            'cells' => $cells,
        ]);

        $view->assertSee('管理情報');
        $view->assertSee('担当者');
        // 空値が適切に処理されることを確認
        $view->assertSee('未設定'); // デフォルトの空値表示
    }

    /** @test */
    public function it_applies_correct_css_classes_for_different_scenarios()
    {
        // 標準行
        $standardCells = [['label' => 'テスト', 'value' => '値', 'type' => 'text']];
        $standardView = $this->blade('<x-common-table.row :cells="$cells" type="standard" />', [
            'cells' => $standardCells,
        ]);
        $standardView->assertSee('class="standard-row"', false);

        // グループ行（複数セル）
        $groupedCells = [
            ['label' => 'グループ', 'value' => '値1', 'type' => 'text', 'rowspan' => 2],
            ['label' => 'サブ', 'value' => '値2', 'type' => 'text'],
        ];
        $groupedView = $this->blade('<x-common-table.row :cells="$cells" type="grouped" />', [
            'cells' => $groupedCells,
        ]);
        $groupedView->assertSee('class="grouped-row"', false);

        // 単一行
        $singleCells = [['label' => '単一', 'value' => '値', 'type' => 'text', 'colspan' => 3]];
        $singleView = $this->blade('<x-common-table.row :cells="$cells" type="single" />', [
            'cells' => $singleCells,
        ]);
        $singleView->assertSee('class="single-row"', false);
    }

    /** @test */
    public function it_handles_accessibility_attributes_for_complex_structures()
    {
        $cells = [
            [
                'label' => '重要情報',
                'value' => '機密データ',
                'type' => 'text',
                'rowspan' => 3,
                'class' => 'important-data',
            ],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="grouped" rowIndex="5" key="important-row" />', [
            'cells' => $cells,
        ]);

        // アクセシビリティ属性の確認
        $view->assertSee('role="row"', false);
        $view->assertSee('aria-label="グループ化された情報行"', false);
        $view->assertSee('data-row-index="5"', false);
        $view->assertSee('data-row-key="important-row"', false);

        // カスタムクラスが適用されることを確認
        $view->assertSee('important-data');
    }

    /** @test */
    public function it_handles_special_cell_types_in_different_row_types()
    {
        // URL型セルを含むグループ行
        $cells = [
            [
                'label' => 'ウェブサイト',
                'value' => 'https://example.com',
                'type' => 'url',
                'rowspan' => 1,
            ],
            [
                'label' => 'メール',
                'value' => 'test@example.com',
                'type' => 'email',
            ],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="grouped" />', [
            'cells' => $cells,
        ]);

        // URL型とemail型が正しく処理されることを確認
        $view->assertSee('https://example.com');
        $view->assertSee('test@example.com');
        $view->assertSee('data-cell-type="url"', false);
        $view->assertSee('data-cell-type="email"', false);
    }

    /** @test */
    public function it_provides_debugging_information()
    {
        $cells = [
            ['label' => 'デバッグテスト', 'value' => 'テスト値', 'type' => 'text'],
        ];

        $view = $this->blade('<x-common-table.row :cells="$cells" type="standard" rowIndex="10" />', [
            'cells' => $cells,
        ]);

        // デバッグ用の属性が含まれることを確認
        $view->assertSee('data-row-index="10"', false);
        $view->assertSee('data-row-type="standard"', false);
        $view->assertSee('data-cell-key="cell-standard-label-10-0"', false);
        $view->assertSee('data-cell-key="cell-standard-value-10-0"', false);
    }
}

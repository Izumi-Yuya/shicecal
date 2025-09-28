<?php

namespace Tests\Feature\Components;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommonTableCellTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_label_cell_correctly()
    {
        $view = $this->blade(
            '<x-common-table.cell :isLabel="true" label="テストラベル" />'
        );

        $view->assertSee('テストラベル');
        $view->assertSee('detail-label');
        $view->assertSee('scope="row"', false);
        $view->assertSee('role="rowheader"', false);
    }

    /** @test */
    public function it_renders_value_cell_with_text_type()
    {
        $view = $this->blade(
            '<x-common-table.cell value="テスト値" type="text" />'
        );

        $view->assertSee('テスト値');
        $view->assertSee('detail-value');
        $view->assertSee('data-cell-type="text"', false);
    }

    /** @test */
    public function it_handles_empty_values_correctly()
    {
        $view = $this->blade(
            '<x-common-table.cell value="" type="text" />'
        );

        $view->assertSee('未設定');
        $view->assertSee('empty-field');
        $view->assertSee('data-is-empty="true"', false);
    }

    /** @test */
    public function it_applies_colspan_and_rowspan_attributes()
    {
        $view = $this->blade(
            '<x-common-table.cell value="テスト" :colspan="2" :rowspan="3" />'
        );

        $view->assertSee('colspan="2"', false);
        $view->assertSee('rowspan="3"', false);
    }

    /** @test */
    public function it_validates_colspan_and_rowspan_limits()
    {
        $view = $this->blade(
            '<x-common-table.cell value="テスト" :colspan="15" :rowspan="15" />'
        );

        // Should be limited to max values
        $view->assertSee('colspan="12"', false);
        $view->assertSee('rowspan="10"', false);
    }

    /** @test */
    public function it_handles_badge_type_correctly()
    {
        $view = $this->blade(
            '<x-common-table.cell value="アクティブ" type="badge" />'
        );

        $view->assertSee('badge');
        $view->assertSee('アクティブ');
    }

    /** @test */
    public function it_handles_email_type_correctly()
    {
        $view = $this->blade(
            '<x-common-table.cell value="test@example.com" type="email" />'
        );

        $view->assertSee('mailto:test@example.com');
        $view->assertSee('fa-envelope');
    }

    /** @test */
    public function it_handles_url_type_correctly()
    {
        $view = $this->blade(
            '<x-common-table.cell value="https://example.com" type="url" />'
        );

        $view->assertSee('href="https://example.com"', false);
        $view->assertSee('fa-external-link-alt');
    }

    /** @test */
    public function it_handles_currency_type_correctly()
    {
        $view = $this->blade(
            '<x-common-table.cell value="1000000" type="currency" />'
        );

        $view->assertSee('1,000,000円');
    }

    /** @test */
    public function it_handles_date_type_correctly()
    {
        $view = $this->blade(
            '<x-common-table.cell value="2024-01-15" type="date" />'
        );

        $view->assertSee('2024年01月15日');
    }

    /** @test */
    public function it_applies_custom_css_classes()
    {
        $view = $this->blade(
            '<x-common-table.cell value="テスト" class="custom-class" />'
        );

        $view->assertSee('custom-class');
        $view->assertSee('detail-value');
    }

    /** @test */
    public function it_handles_invalid_cell_types()
    {
        $view = $this->blade(
            '<x-common-table.cell value="テスト" type="invalid-type" />'
        );

        // Should default to text type
        $view->assertSee('data-cell-type="text"', false);
    }

    /** @test */
    public function it_sets_aria_attributes_for_accessibility()
    {
        $view = $this->blade(
            '<x-common-table.cell :isLabel="true" label="アクセシブルラベル" />'
        );

        $view->assertSee('aria-label="アクセシブルラベル"', false);
    }

    /** @test */
    public function it_handles_key_attribute_for_relationships()
    {
        $view = $this->blade(
            '<x-common-table.cell value="テスト値" key="test-key" />'
        );

        $view->assertSee('data-cell-key="test-key"', false);
        $view->assertSee('aria-describedby="label-test-key"', false);
    }
}

<?php

namespace Tests\Feature\Components;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommonTableCardWrapperTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_card_wrapper_with_title()
    {
        $view = $this->blade(
            '<x-common-table.card-wrapper title="テストタイトル">
                <div class="test-content">テストコンテンツ</div>
            </x-common-table.card-wrapper>'
        );

        $view->assertSee('テストタイトル');
        $view->assertSee('テストコンテンツ');
        $view->assertSeeInOrder(['card-header', 'card-title']);
    }

    /** @test */
    public function it_renders_card_wrapper_without_title()
    {
        $view = $this->blade(
            '<x-common-table.card-wrapper>
                <div class="test-content">テストコンテンツ</div>
            </x-common-table.card-wrapper>'
        );

        $view->assertSee('テストコンテンツ');
        $view->assertDontSee('card-header');
    }

    /** @test */
    public function it_applies_default_css_classes()
    {
        $view = $this->blade(
            '<x-common-table.card-wrapper title="テスト">
                <div>コンテンツ</div>
            </x-common-table.card-wrapper>'
        );

        $view->assertSee('facility-info-card');
        $view->assertSee('detail-card-improved');
        $view->assertSee('mb-3');
        $view->assertSee('card-header');
        $view->assertSee('card-title mb-0');
    }

    /** @test */
    public function it_applies_custom_css_classes()
    {
        $view = $this->blade(
            '<x-common-table.card-wrapper 
                title="テスト" 
                cardClass="custom-card-class"
                headerClass="custom-header-class"
                titleClass="custom-title-class">
                <div>コンテンツ</div>
            </x-common-table.card-wrapper>'
        );

        $view->assertSee('custom-card-class');
        $view->assertSee('custom-header-class');
        $view->assertSee('custom-title-class');
    }

    /** @test */
    public function it_supports_custom_title_tag()
    {
        $view = $this->blade(
            '<x-common-table.card-wrapper title="テスト" titleTag="h3">
                <div>コンテンツ</div>
            </x-common-table.card-wrapper>'
        );

        $view->assertSee('<h3 class="card-title mb-0">テスト</h3>', false);
    }

    /** @test */
    public function it_handles_show_header_false()
    {
        $view = $this->blade(
            '<x-common-table.card-wrapper title="テスト" :showHeader="false">
                <div>コンテンツ</div>
            </x-common-table.card-wrapper>'
        );

        $view->assertDontSee('card-header');
        $view->assertDontSee('テスト');
        $view->assertSee('コンテンツ');
    }

    /** @test */
    public function it_applies_accessibility_attributes()
    {
        $view = $this->blade(
            '<x-common-table.card-wrapper title="テスト" ariaLabel="テストカード">
                <div>コンテンツ</div>
            </x-common-table.card-wrapper>'
        );

        $view->assertSee('role="region"', false);
        $view->assertSee('aria-label="テストカード"', false);
        $view->assertSeeInOrder(['aria-labelledby=', 'id='], false);
    }

    /** @test */
    public function it_applies_custom_attributes()
    {
        $view = $this->blade(
            '<x-common-table.card-wrapper 
                title="テスト"
                :cardAttributes="[\'data-test\' => \'card-value\']"
                :headerAttributes="[\'data-header\' => \'header-value\']">
                <div>コンテンツ</div>
            </x-common-table.card-wrapper>'
        );

        $view->assertSee('data-test="card-value"', false);
        $view->assertSee('data-header="header-value"', false);
    }

    /** @test */
    public function it_integrates_with_existing_facility_info_card_classes()
    {
        $view = $this->blade(
            '<x-common-table.card-wrapper title="基本情報">
                <div class="card-body card-body-clean">
                    <div class="table-responsive">
                        <table class="table table-bordered facility-basic-info-table-clean">
                            <tbody>
                                <tr>
                                    <td class="detail-label">ラベル</td>
                                    <td class="detail-value">値</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-common-table.card-wrapper>'
        );

        // 既存のクラスとの統合を確認
        $view->assertSee('facility-info-card');
        $view->assertSee('detail-card-improved');
        $view->assertSee('card-body-clean');
        $view->assertSee('facility-basic-info-table-clean');
        $view->assertSee('detail-label');
        $view->assertSee('detail-value');
    }

    /** @test */
    public function it_generates_unique_ids_for_accessibility()
    {
        $view1 = $this->blade(
            '<x-common-table.card-wrapper title="テスト1">
                <div>コンテンツ1</div>
            </x-common-table.card-wrapper>'
        );

        $view2 = $this->blade(
            '<x-common-table.card-wrapper title="テスト2">
                <div>コンテンツ2</div>
            </x-common-table.card-wrapper>'
        );

        // 両方のビューでIDが生成されることを確認
        $view1->assertSeeInOrder(['aria-labelledby=', 'id='], false);
        $view2->assertSeeInOrder(['aria-labelledby=', 'id='], false);

        // 実際のHTMLを取得してIDが異なることを確認
        $html1 = (string) $view1;
        $html2 = (string) $view2;

        preg_match('/id="([^"]+)"/', $html1, $matches1);
        preg_match('/id="([^"]+)"/', $html2, $matches2);

        $this->assertNotEmpty($matches1);
        $this->assertNotEmpty($matches2);
        $this->assertNotEquals($matches1[1], $matches2[1]);
    }
}

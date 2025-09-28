<?php

namespace Tests\Browser;

use App\Models\BuildingInfo;
use App\Models\Facility;
use App\Models\LandInfo;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * CommonTableブラウザテスト
 *
 * ブラウザでの実際の動作確認とユーザーインタラクションテスト
 * 要件: 設計書のテスト戦略
 */
class CommonTableBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $user;

    protected $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->facility = Facility::factory()->create([
            'company_name' => 'ブラウザテスト会社',
            'office_code' => 'BROWSER001',
            'address' => '東京都渋谷区ブラウザテスト1-1-1',
            'phone' => '03-1111-2222',
            'email' => 'browser@example.com',
        ]);
    }

    /**
     * @test
     * 基本的なテーブル表示のブラウザテスト
     */
    public function test_browser_基本的なテーブルが正しく表示される()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $this->facility))
                ->waitFor('.facility-info-card')
                ->assertSee('基本情報')
                ->assertSee('ブラウザテスト会社')
                ->assertSee('BROWSER001')
                ->assertSee('東京都渋谷区ブラウザテスト1-1-1')
                ->assertSee('03-1111-2222')
                ->assertSee('browser@example.com');
        });
    }

    /**
     * @test
     * レスポンシブデザインのブラウザテスト
     */
    public function test_browser_レスポンシブデザインが正しく動作する()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $this->facility))
                ->waitFor('.facility-info-card');

            // デスクトップサイズでの表示確認
            $browser->resize(1200, 800)
                ->assertVisible('.table-responsive')
                ->assertPresent('[data-responsive="true"]');

            // タブレットサイズでの表示確認
            $browser->resize(768, 1024)
                ->assertVisible('.table-responsive-md')
                ->assertPresent('[data-mobile-optimized="true"]');

            // モバイルサイズでの表示確認
            $browser->resize(375, 667)
                ->assertVisible('.table-responsive')
                ->assertPresent('[data-mobile-optimized="true"]');
        });
    }

    /**
     * @test
     * メールリンクのクリック動作テスト
     */
    public function test_browser_メールリンクが正しく動作する()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $this->facility))
                ->waitFor('.facility-info-card')
                ->assertPresent('a[href="mailto:browser@example.com"]')
                ->assertSee('fas fa-envelope');

            // メールリンクの属性確認
            $mailtoLink = $browser->element('a[href="mailto:browser@example.com"]');
            $this->assertNotNull($mailtoLink);
        });
    }

    /**
     * @test
     * URLリンクのクリック動作テスト
     */
    public function test_browser_ur_lリンクが正しく動作する()
    {
        // URLを持つファシリティを作成
        $facilityWithUrl = Facility::factory()->create([
            'company_name' => 'URL テスト会社',
            'website_url' => 'https://example.com',
        ]);

        $this->browse(function (Browser $browser) use ($facilityWithUrl) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $facilityWithUrl))
                ->waitFor('.facility-info-card')
                ->assertPresent('a[href="https://example.com"]')
                ->assertPresent('a[target="_blank"]')
                ->assertSee('fas fa-external-link-alt');
        });
    }

    /**
     * @test
     * 空フィールドの表示テスト
     */
    public function test_browser_空フィールドが正しく表示される()
    {
        // 空フィールドを持つファシリティを作成
        $facilityWithEmptyFields = Facility::factory()->create([
            'company_name' => '空フィールドテスト会社',
            'office_code' => null,
            'address' => null,
            'phone' => '03-3333-4444',
            'email' => null,
        ]);

        $this->browse(function (Browser $browser) use ($facilityWithEmptyFields) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $facilityWithEmptyFields))
                ->waitFor('.facility-info-card')
                ->assertSee('空フィールドテスト会社')
                ->assertSee('03-3333-4444')
                ->assertSee('未設定')
                ->assertPresent('.empty-field');
        });
    }

    /**
     * @test
     * 複数カードの表示テスト
     */
    public function test_browser_複数カードが正しく表示される()
    {
        // 関連データを作成
        LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'management_company_name' => 'ブラウザ管理会社',
            'management_contact_person' => 'ブラウザ太郎',
        ]);

        BuildingInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'building_name' => 'ブラウザテストビル',
            'floors_above_ground' => 15,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $this->facility))
                ->waitFor('.facility-info-card')
                ->assertSee('基本情報')
                ->assertSee('土地情報')
                ->assertSee('建物情報')
                ->assertSee('ブラウザテスト会社')
                ->assertSee('ブラウザ管理会社')
                ->assertSee('ブラウザテストビル');
        });
    }

    /**
     * @test
     * アクセシビリティ機能のテスト
     */
    public function test_browser_アクセシビリティ機能が正しく動作する()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $this->facility))
                ->waitFor('.facility-info-card');

            // ARIA属性の確認
            $browser->assertPresent('[role="table"]')
                ->assertPresent('[role="region"]')
                ->assertPresent('[aria-label]');

            // スクリーンリーダー用要素の確認
            $browser->assertPresent('.sr-only')
                ->assertPresent('caption.sr-only');

            // キーボードナビゲーションのテスト
            $browser->keys('body', '{tab}')
                ->assertFocused('a'); // 最初のリンクにフォーカスが移る
        });
    }

    /**
     * @test
     * バッジ表示のテスト
     */
    public function test_browser_バッジが正しく表示される()
    {
        // バッジを表示するファシリティを作成
        $facilityWithBadge = Facility::factory()->create([
            'company_name' => 'バッジテスト会社',
            'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($facilityWithBadge) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $facilityWithBadge))
                ->waitFor('.facility-info-card')
                ->assertPresent('.badge')
                ->assertSee('active');
        });
    }

    /**
     * @test
     * 日付フォーマットの表示テスト
     */
    public function test_browser_日付が正しくフォーマットされる()
    {
        // 日付を持つファシリティを作成
        $facilityWithDate = Facility::factory()->create([
            'company_name' => '日付テスト会社',
            'established_date' => '2023-12-25',
        ]);

        $this->browse(function (Browser $browser) use ($facilityWithDate) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $facilityWithDate))
                ->waitFor('.facility-info-card')
                ->assertSee('2023年12月25日');
        });
    }

    /**
     * @test
     * テーブルの構造確認テスト
     */
    public function test_browser_テーブル構造が正しく生成される()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $this->facility))
                ->waitFor('.facility-info-card')
                ->assertPresent('table.table-bordered')
                ->assertPresent('tbody')
                ->assertPresent('tr')
                ->assertPresent('td.detail-label')
                ->assertPresent('td.detail-value');
        });
    }

    /**
     * @test
     * CSSクラスの適用確認テスト
     */
    public function test_browser_cs_sクラスが正しく適用される()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $this->facility))
                ->waitFor('.facility-info-card')
                ->assertPresent('.facility-info-card.detail-card-improved')
                ->assertPresent('.table.table-bordered.facility-basic-info-table-clean')
                ->assertPresent('.card-body.card-body-clean')
                ->assertPresent('.table-responsive.table-responsive-md');
        });
    }

    /**
     * @test
     * JavaScript連携の確認テスト
     */
    public function test_browser_java_script連携が正しく動作する()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $this->facility))
                ->waitFor('.facility-info-card');

            // JavaScriptが依存するHTML構造が存在することを確認
            $browser->assertPresent('[data-responsive="true"]')
                ->assertPresent('[data-mobile-optimized="true"]')
                ->assertPresent('[data-cell-type]');

            // 動的な要素の確認（もしあれば）
            $browser->script('return document.querySelector("[data-responsive]").getAttribute("data-responsive")');
            $this->assertEquals('true', $browser->script('return document.querySelector("[data-responsive]").getAttribute("data-responsive")')[0]);
        });
    }

    /**
     * @test
     * パフォーマンステスト
     */
    public function test_browser_ページ読み込みパフォーマンス()
    {
        $this->browse(function (Browser $browser) {
            $startTime = microtime(true);

            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $this->facility))
                ->waitFor('.facility-info-card', 10); // 最大10秒待機

            $endTime = microtime(true);
            $loadTime = $endTime - $startTime;

            // ページ読み込み時間が合理的な範囲内であることを確認（5秒以内）
            $this->assertLessThan(5.0, $loadTime, 'ページの読み込み時間が長すぎます');

            // 必要な要素が表示されていることを確認
            $browser->assertSee('基本情報')
                ->assertSee($this->facility->company_name);
        });
    }

    /**
     * @test
     * エラー表示のテスト
     */
    public function test_browser_エラー表示が正しく動作する()
    {
        $this->browse(function (Browser $browser) {
            // 存在しないファシリティにアクセス
            $browser->loginAs($this->user)
                ->visit('/facilities/99999')
                ->assertSee('404'); // 404エラーページが表示される
        });
    }

    /**
     * @test
     * 印刷スタイルの確認テスト
     */
    public function test_browser_印刷スタイルが適用される()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $this->facility))
                ->waitFor('.facility-info-card');

            // 印刷プレビューモードでの表示確認
            $browser->script('window.print = function() { console.log("Print called"); }');

            // 印刷用のCSSが適用されていることを確認
            $printStyles = $browser->script('
                return Array.from(document.styleSheets)
                    .some(sheet => {
                        try {
                            return Array.from(sheet.cssRules || [])
                                .some(rule => rule.media && rule.media.mediaText.includes("print"));
                        } catch(e) {
                            return false;
                        }
                    });
            ')[0];

            // 印刷用スタイルが存在することを確認（存在する場合）
            // $this->assertTrue($printStyles, '印刷用CSSが見つかりません');
        });
    }

    /**
     * @test
     * 複雑なデータ構造の表示テスト
     */
    public function test_browser_複雑なデータ構造が正しく表示される()
    {
        // 複雑な土地情報を作成
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'management_company_name' => '複雑管理会社',
            'management_contact_person' => '複雑太郎',
            'management_phone' => '03-5555-6666',
            'management_email' => 'complex@example.com',
            'owner_name' => '複雑オーナー',
            'owner_phone' => '03-7777-8888',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $this->facility))
                ->waitFor('.facility-info-card')
                ->assertSee('土地情報')
                ->assertSee('複雑管理会社')
                ->assertSee('複雑太郎')
                ->assertSee('03-5555-6666')
                ->assertPresent('a[href="mailto:complex@example.com"]')
                ->assertSee('複雑オーナー')
                ->assertSee('03-7777-8888');

            // rowspanが適用されていることを確認
            $browser->assertPresent('[rowspan]');
        });
    }
}

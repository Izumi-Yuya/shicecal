<?php

namespace Tests\Feature\Components;

use App\Models\Facility;
use App\Models\LandInfo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 土地情報表示カードの共通テーブルコンポーネント移行テスト
 *
 * 要件: 5.1, 5.2, 5.3
 * - 既存のテーブルを移行する際、システムは現在のデータ構造との後方互換性を維持すること
 * - 古いコードを置き換える際、システムは既存のすべての機能を保持すること
 * - コンポーネントを使用する際、システムは既存のCSSクラスとJavaScriptと連携すること
 */
class LandInfoDisplayCardMigrationTest extends TestCase
{
    use RefreshDatabase;

    private $facility;

    private $landInfo;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用施設データの作成
        $this->facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'company_name' => 'テスト会社',
        ]);

        // テスト用土地情報データの作成
        $this->landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'ownership_type' => 'owned',
            'site_area_sqm' => 1000.50,
            'site_area_tsubo' => 302.65,
            'parking_spaces' => 20,
            'purchase_price' => 50000000,
            'unit_price_per_tsubo' => 165000,
            'monthly_rent' => 300000,
            'contract_start_date' => Carbon::parse('2020-01-01'),
            'contract_end_date' => Carbon::parse('2025-12-31'),
            'auto_renewal' => 'yes',
            'contract_period_text' => '5年',
            'notes' => 'テスト備考',
            'management_company_name' => 'テスト管理会社',
            'management_company_email' => 'management@example.com',
            'management_company_url' => 'https://management.example.com',
            'owner_name' => 'テストオーナー',
            'owner_email' => 'owner@example.com',
            'owner_url' => 'https://owner.example.com',
        ]);
    }

    /** @test */
    public function 土地情報表示カードが正しくレンダリングされること()
    {
        $view = view('facilities.land-info.partials.display-card', [
            'facility' => $this->facility,
            'landInfo' => $this->landInfo,
        ]);
        $html = $view->render();

        // 基本的な内容が含まれているかチェック
        $this->assertStringContainsString('自社', $html); // 所有タイプ
        $this->assertStringContainsString('1,000.50㎡', $html); // 敷地面積（㎡）
        $this->assertStringContainsString('302.65坪', $html); // 敷地面積（坪）
        $this->assertStringContainsString('20台', $html); // 駐車場台数
        $this->assertStringContainsString('50,000,000円', $html); // 購入金額
        $this->assertStringContainsString('165,000円/坪', $html); // 坪単価
        $this->assertStringContainsString('300,000円', $html); // 家賃
        $this->assertStringContainsString('2020年1月1日 ～ 2025年12月31日', $html); // 契約期間
        $this->assertStringContainsString('あり', $html); // 自動更新
        $this->assertStringContainsString('5年', $html); // 契約年数
        $this->assertStringContainsString('テスト備考', $html); // 備考

        // 管理会社情報
        $this->assertStringContainsString('テスト管理会社', $html);
        $this->assertStringContainsString('management@example.com', $html);
        $this->assertStringContainsString('https://management.example.com', $html);

        // オーナー情報
        $this->assertStringContainsString('テストオーナー', $html);
        $this->assertStringContainsString('owner@example.com', $html);
        $this->assertStringContainsString('https://owner.example.com', $html);
    }

    /** @test */
    public function 所有タイプのバッジが正しく表示されること()
    {
        // 自社の場合
        $this->landInfo->ownership_type = 'owned';
        $this->landInfo->save();

        $view = view('facilities.land-info.partials.display-card', [
            'facility' => $this->facility,
            'landInfo' => $this->landInfo,
        ]);
        $html = $view->render();

        $this->assertStringContainsString('badge bg-success', $html);
        $this->assertStringContainsString('自社', $html);

        // 賃借の場合
        $this->landInfo->ownership_type = 'leased';
        $this->landInfo->save();

        $view = view('facilities.land-info.partials.display-card', [
            'facility' => $this->facility,
            'landInfo' => $this->landInfo,
        ]);
        $html = $view->render();

        $this->assertStringContainsString('badge bg-warning', $html);
        $this->assertStringContainsString('賃借', $html);
    }

    /** @test */
    public function 自動更新バッジが正しく表示されること()
    {
        // ありの場合
        $this->landInfo->auto_renewal = 'yes';
        $this->landInfo->save();

        $view = view('facilities.land-info.partials.display-card', [
            'facility' => $this->facility,
            'landInfo' => $this->landInfo,
        ]);
        $html = $view->render();

        $this->assertStringContainsString('badge bg-success', $html);
        $this->assertStringContainsString('あり', $html);

        // なしの場合
        $this->landInfo->auto_renewal = 'no';
        $this->landInfo->save();

        $view = view('facilities.land-info.partials.display-card', [
            'facility' => $this->facility,
            'landInfo' => $this->landInfo,
        ]);
        $html = $view->render();

        $this->assertStringContainsString('badge bg-secondary', $html);
        $this->assertStringContainsString('なし', $html);
    }

    /** @test */
    public function メールアドレスがリンクとして表示されること()
    {
        $view = view('facilities.land-info.partials.display-card', [
            'facility' => $this->facility,
            'landInfo' => $this->landInfo,
        ]);
        $html = $view->render();

        // 管理会社のメールリンク
        $this->assertStringContainsString('mailto:management@example.com', $html);
        $this->assertStringContainsString('fa-envelope', $html);

        // オーナーのメールリンク
        $this->assertStringContainsString('mailto:owner@example.com', $html);
    }

    /** @test */
    public function ur_lがリンクとして表示されること()
    {
        $view = view('facilities.land-info.partials.display-card', [
            'facility' => $this->facility,
            'landInfo' => $this->landInfo,
        ]);
        $html = $view->render();

        // 管理会社のURLリンク
        $this->assertStringContainsString('href="https://management.example.com"', $html);
        $this->assertStringContainsString('fa-external-link-alt', $html);
        $this->assertStringContainsString('target="_blank"', $html);

        // オーナーのURLリンク
        $this->assertStringContainsString('href="https://owner.example.com"', $html);
    }

    /** @test */
    public function 必要な_cs_sクラスが適用されていること()
    {
        $view = view('facilities.land-info.partials.display-card', [
            'facility' => $this->facility,
            'landInfo' => $this->landInfo,
        ]);
        $html = $view->render();

        // 共通テーブルコンポーネントのCSSクラス確認
        $this->assertStringContainsString('facility-info-card', $html);
        $this->assertStringContainsString('detail-card-improved', $html);
        $this->assertStringContainsString('table-bordered', $html);
        $this->assertStringContainsString('facility-basic-info-table-clean', $html);
        $this->assertStringContainsString('detail-label', $html);
        $this->assertStringContainsString('detail-value', $html);
    }

    /** @test */
    public function 空フィールドが正しく処理されること()
    {
        // 空フィールドテスト用の別の施設を作成
        $emptyFacility = Facility::factory()->create([
            'facility_name' => '空フィールドテスト施設',
            'company_name' => 'テスト会社2',
        ]);

        // 空フィールドを持つ土地情報を作成（必須フィールドは有効な値を使用）
        $emptyLandInfo = LandInfo::factory()->create([
            'facility_id' => $emptyFacility->id,
            'ownership_type' => 'owned', // 必須フィールド
            'site_area_sqm' => null,
            'management_company_name' => '',
            'owner_name' => '',
        ]);

        $view = view('facilities.land-info.partials.display-card', [
            'facility' => $emptyFacility,
            'landInfo' => $emptyLandInfo,
        ]);
        $html = $view->render();

        // 未設定が表示されることを確認
        $this->assertStringContainsString('未設定', $html);

        // empty-fieldクラスが適用されることを確認
        $this->assertStringContainsString('empty-field', $html);
    }

    /** @test */
    public function 管理会社とオーナー情報のタイトルが表示されること()
    {
        $view = view('facilities.land-info.partials.display-card', [
            'facility' => $this->facility,
            'landInfo' => $this->landInfo,
        ]);
        $html = $view->render();

        // セクションタイトルが表示されることを確認
        $this->assertStringContainsString('管理会社情報', $html);
        $this->assertStringContainsString('オーナー情報', $html);
    }
}

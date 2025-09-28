<?php

namespace Tests\Feature\Components;

use App\Models\BuildingInfo;
use App\Models\Facility;
use App\Models\LandInfo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CommonTable既存ビュー互換性テスト
 *
 * 既存ビューとの互換性とマイグレーション後の動作確認
 * 要件: 設計書のテスト戦略
 */
class CommonTableViewCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->facility = Facility::factory()->create([
            'company_name' => 'テスト会社',
            'office_code' => 'TEST001',
            'address' => '東京都渋谷区テスト1-1-1',
            'phone_number' => '03-1234-5678',
        ]);
    }

    /**
     * @test
     * 基本情報表示カードの互換性テスト
     */
    public function test_compatibility_基本情報表示カードが正しく動作する()
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // 基本情報が表示されることを確認
        $response->assertSee('基本情報');
        $response->assertSee($this->facility->company_name);
        $response->assertSee($this->facility->office_code);
        $response->assertSee($this->facility->address);
        $response->assertSee($this->facility->phone_number);

        // 既存のCSSクラスが適用されることを確認
        $response->assertSee('facility-info-card');
        $response->assertSee('detail-card-improved');
        $response->assertSee('facility-basic-info-table-clean');
    }

    /**
     * @test
     * 土地情報表示カードの互換性テスト
     */
    public function test_compatibility_土地情報表示カードが正しく動作する()
    {
        // 土地情報を作成
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'management_company_name' => '管理会社テスト',
            'management_contact_person' => '田中太郎',
            'management_phone' => '03-9876-5432',
            'owner_name' => 'オーナーテスト',
            'owner_phone' => '03-5555-1111',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // 土地情報が表示されることを確認
        $response->assertSee('土地情報');
        $response->assertSee('管理会社テスト');
        $response->assertSee('田中太郎');
        $response->assertSee('03-9876-5432');
        $response->assertSee('オーナーテスト');
        $response->assertSee('03-5555-1111');
    }

    /**
     * @test
     * 建物情報表示カードの互換性テスト
     */
    public function test_compatibility_建物情報表示カードが正しく動作する()
    {
        // 建物情報を作成
        $buildingInfo = BuildingInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'building_name' => 'テストビル',
            'floors_above_ground' => 10,
            'floors_below_ground' => 2,
            'total_floor_area' => 1500.50,
            'construction_date' => '2020-04-01',
            'ownership_type' => 'owned',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // 建物情報が表示されることを確認
        $response->assertSee('建物情報');
        $response->assertSee('テストビル');
        $response->assertSee('10');
        $response->assertSee('2');
        $response->assertSee('1,500.50');
        $response->assertSee('2020年4月1日');
    }

    /**
     * @test
     * 空フィールドの表示互換性テスト
     */
    public function test_compatibility_空フィールドが正しく表示される()
    {
        // 一部のフィールドを空にしたファシリティを作成
        $facilityWithEmptyFields = Facility::factory()->create([
            'company_name' => 'テスト会社',
            'office_code' => null,
            'address' => null,
            'phone_number' => '03-1234-5678',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $facilityWithEmptyFields));

        $response->assertStatus(200);

        // 空フィールドが「未設定」として表示されることを確認
        $response->assertSee('未設定');

        // empty-fieldクラスが適用されることを確認
        $response->assertSee('empty-field');

        // 有効なデータは正常に表示されることを確認
        $response->assertSee('テスト会社');
        $response->assertSee('03-1234-5678');
    }

    /**
     * @test
     * レスポンシブデザインの互換性テスト
     */
    public function test_compatibility_レスポンシブデザインが正しく動作する()
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // レスポンシブクラスが適用されることを確認
        $response->assertSee('table-responsive');
        $response->assertSee('data-responsive="true"');
        $response->assertSee('data-mobile-optimized="true"');
    }

    /**
     * @test
     * JavaScriptセレクターの互換性テスト
     */
    public function test_compatibility_java_script用の_htm_l構造が維持される()
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // 既存のJavaScriptが依存するHTML構造が維持されることを確認
        $response->assertSee('detail-label');
        $response->assertSee('detail-value');
        $response->assertSee('facility-info-card');
        $response->assertSee('card-body');

        // テーブル構造が維持されることを確認
        $response->assertSee('<table');
        $response->assertSee('<tbody>');
        $response->assertSee('<tr');
        $response->assertSee('<td');
    }

    /**
     * @test
     * CSSスタイリングの互換性テスト
     */
    public function test_compatibility_既存_cs_sスタイルが正しく適用される()
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // 既存のCSSクラスが適用されることを確認
        $response->assertSee('facility-basic-info-table-clean');
        $response->assertSee('card-body-clean');
        $response->assertSee('detail-card-improved');

        // Bootstrapクラスが適用されることを確認
        $response->assertSee('table-bordered');
        $response->assertSee('mb-3');
    }

    /**
     * @test
     * アクセシビリティ機能の互換性テスト
     */
    public function test_compatibility_アクセシビリティ機能が正しく動作する()
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // ARIA属性が適用されることを確認
        $response->assertSee('role="table"');
        $response->assertSee('role="region"');
        $response->assertSee('aria-label');

        // スクリーンリーダー用要素が含まれることを確認
        $response->assertSee('sr-only');
        $response->assertSee('<caption');
    }

    /**
     * @test
     * データフォーマットの互換性テスト
     */
    public function test_compatibility_データフォーマットが正しく動作する()
    {
        // 様々なデータタイプを含むファシリティを作成
        $facility = Facility::factory()->create([
            'company_name' => 'フォーマットテスト会社',
            'phone_number' => '03-1234-5678',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $facility));

        $response->assertStatus(200);

        // 電話番号が正しく表示されることを確認
        $response->assertSee('03-1234-5678');
    }

    /**
     * @test
     * エラーハンドリングの互換性テスト
     */
    public function test_compatibility_エラーハンドリングが正しく動作する()
    {
        // 存在しないファシリティへのアクセス
        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', 99999));

        $response->assertStatus(404);
    }

    /**
     * @test
     * パフォーマンスの互換性テスト
     */
    public function test_compatibility_パフォーマンスが許容範囲内である()
    {
        // 複数の関連データを持つファシリティを作成
        $facility = Facility::factory()->create();
        LandInfo::factory()->create(['facility_id' => $facility->id]);
        BuildingInfo::factory()->create(['facility_id' => $facility->id]);

        $startTime = microtime(true);

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $facility));

        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        $response->assertStatus(200);

        // レスポンス時間が合理的な範囲内であることを確認（3秒以内）
        $this->assertLessThan(3.0, $responseTime, 'ページの読み込み時間が長すぎます');
    }

    /**
     * @test
     * 複数カードの表示互換性テスト
     */
    public function test_compatibility_複数カードが正しく表示される()
    {
        // 全ての関連データを持つファシリティを作成
        $facility = Facility::factory()->create([
            'company_name' => '総合テスト会社',
            'office_code' => 'COMP001',
        ]);

        LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'management_company_name' => '管理会社',
        ]);

        BuildingInfo::factory()->create([
            'facility_id' => $facility->id,
            'building_name' => 'テスト建物',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $facility));

        $response->assertStatus(200);

        // 全てのカードが表示されることを確認
        $response->assertSee('基本情報');
        $response->assertSee('土地情報');
        $response->assertSee('建物情報');

        // 各カードのデータが正しく表示されることを確認
        $response->assertSee('総合テスト会社');
        $response->assertSee('COMP001');
        $response->assertSee('管理会社');
        $response->assertSee('テスト建物');

        // カード間の区切りが正しく表示されることを確認
        $response->assertSee('mb-3');
    }

    /**
     * @test
     * 権限による表示制御の互換性テスト
     */
    public function test_compatibility_権限による表示制御が正しく動作する()
    {
        // 閲覧権限のみのユーザーを作成
        $viewerUser = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($viewerUser)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // データは表示されるが編集リンクは表示されないことを確認
        $response->assertSee($this->facility->company_name);
        $response->assertDontSee('編集');
    }

    /**
     * @test
     * 国際化対応の互換性テスト
     */
    public function test_compatibility_日本語表示が正しく動作する()
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // 日本語ラベルが正しく表示されることを確認
        $response->assertSee('会社名');
        $response->assertSee('事業所コード');
        $response->assertSee('住所');
        $response->assertSee('電話番号');

        // 空フィールドの日本語表示を確認
        $response->assertSee('未設定');
    }
}

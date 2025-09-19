<?php

namespace Tests\Feature\Components;

use Tests\TestCase;
use App\Models\Facility;
use App\Models\FacilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * 基本情報表示カードの共通テーブルコンポーネント移行テスト
 * 
 * 要件: 5.1, 5.2, 5.3
 * - 既存のテーブルを移行する際、システムは現在のデータ構造との後方互換性を維持すること
 * - 古いコードを置き換える際、システムは既存のすべての機能を保持すること
 * - コンポーネントを使用する際、システムは既存のCSSクラスとJavaScriptと連携すること
 */
class BasicInfoDisplayCardMigrationTest extends TestCase
{
    use RefreshDatabase;

    private $facility;

    protected function setUp(): void
    {
        parent::setUp();
        
        // テスト用施設データの作成
        $this->facility = Facility::factory()->create([
            'company_name' => 'テスト会社',
            'office_code' => 'TEST001',
            'facility_name' => 'テスト施設',
            'designation_number' => 'DES123',
            'postal_code' => '1234567',
            'opening_date' => Carbon::parse('2020-01-01'),
            'address' => '東京都渋谷区1-1-1',
            'building_name' => 'テストビル',
            'building_structure' => 'RC造',
            'phone_number' => '03-1234-5678',
            'building_floors' => 5,
            'fax_number' => '03-1234-5679',
            'paid_rooms_count' => 10,
            'toll_free_number' => '0120-123-456',
            'ss_rooms_count' => 2,
            'email' => 'test@example.com',
            'capacity' => 50,
            'website_url' => 'https://example.com',
        ]);

        // サービス情報の作成
        FacilityService::factory()->create([
            'facility_id' => $this->facility->id,
            'service_type' => '介護サービス',
            'renewal_start_date' => '2024-01-01',
            'renewal_end_date' => '2024-12-31',
        ]);

        FacilityService::factory()->create([
            'facility_id' => $this->facility->id,
            'service_type' => 'デイサービス',
            'renewal_start_date' => '2024-06-01',
            'renewal_end_date' => '2025-05-31',
        ]);
    }

    /** @test */
    public function 基本情報表示カードが正しくレンダリングされること()
    {
        $response = $this->get("/facilities/{$this->facility->id}");
        
        $response->assertStatus(200);
        
        // 基本情報の表示確認
        $response->assertSee('テスト会社');
        $response->assertSee('TEST001');
        $response->assertSee('テスト施設');
        $response->assertSee('DES123');
        $response->assertSee('123-4567'); // formatted_postal_code
        $response->assertSee('2020年1月1日'); // 開設日
        $response->assertSee('東京都渋谷区1-1-1'); // full_address
        $response->assertSee('4年'); // 開設年数（2020年から現在まで）
        $response->assertSee('テストビル');
        $response->assertSee('RC造');
        $response->assertSee('03-1234-5678');
        $response->assertSee('5階');
        $response->assertSee('03-1234-5679');
        $response->assertSee('10室');
        $response->assertSee('0120-123-456');
        $response->assertSee('2室');
        $response->assertSee('test@example.com');
        $response->assertSee('50名');
        $response->assertSee('https://example.com');
    }

    /** @test */
    public function サービス情報が正しくレンダリングされること()
    {
        $response = $this->get("/facilities/{$this->facility->id}");
        
        $response->assertStatus(200);
        
        // サービス情報の表示確認
        $response->assertSee('介護サービス');
        $response->assertSee('デイサービス');
        $response->assertSee('2024年1月1日 〜 2024年12月31日');
        $response->assertSee('2024年6月1日 〜 2025年5月31日');
    }

    /** @test */
    public function 必要なCSSクラスが適用されていること()
    {
        $response = $this->get("/facilities/{$this->facility->id}");
        
        $response->assertStatus(200);
        
        // 共通テーブルコンポーネントのCSSクラス確認
        $response->assertSee('facility-info-card');
        $response->assertSee('detail-card-improved');
        $response->assertSee('table-bordered');
        $response->assertSee('facility-basic-info-table-clean');
        $response->assertSee('detail-label');
        $response->assertSee('detail-value');
    }

    /** @test */
    public function 空フィールドが正しく処理されること()
    {
        // 空フィールドを持つ施設を作成
        $emptyFacility = Facility::factory()->create([
            'company_name' => null,
            'office_code' => null,
            'facility_name' => 'テスト施設のみ',
            'email' => null,
            'website_url' => null,
        ]);

        $response = $this->get("/facilities/{$emptyFacility->id}");
        
        $response->assertStatus(200);
        
        // 空フィールドの表示確認
        $response->assertSee('未設定');
        
        // empty-fieldクラスが適用されることを確認（HTMLソースで確認）
        $content = $response->getContent();
        $this->assertStringContainsString('empty-field', $content);
    }

    /** @test */
    public function メールアドレスがリンクとして表示されること()
    {
        $response = $this->get("/facilities/{$this->facility->id}");
        
        $response->assertStatus(200);
        
        // メールリンクの確認
        $response->assertSee('mailto:test@example.com', false);
        $response->assertSee('fas fa-envelope', false);
    }

    /** @test */
    public function URLがリンクとして表示されること()
    {
        $response = $this->get("/facilities/{$this->facility->id}");
        
        $response->assertStatus(200);
        
        // URLリンクの確認
        $response->assertSee('href="https://example.com"', false);
        $response->assertSee('target="_blank"', false);
        $response->assertSee('fas fa-external-link-alt', false);
    }

    /** @test */
    public function 事業所コードがバッジとして表示されること()
    {
        $response = $this->get("/facilities/{$this->facility->id}");
        
        $response->assertStatus(200);
        
        // バッジの確認
        $response->assertSee('badge bg-primary', false);
        $response->assertSee('TEST001');
    }

    /** @test */
    public function 日付が日本語フォーマットで表示されること()
    {
        $response = $this->get("/facilities/{$this->facility->id}");
        
        $response->assertStatus(200);
        
        // 日本語日付フォーマットの確認
        $response->assertSee('2020年1月1日');
    }

    /** @test */
    public function データセクション属性が保持されること()
    {
        $response = $this->get("/facilities/{$this->facility->id}");
        
        $response->assertStatus(200);
        
        // data-section属性の確認
        $response->assertSee('data-section="facility_services"', false);
    }

    /** @test */
    public function サービスがない場合の表示が正しいこと()
    {
        // サービスのない施設を作成
        $facilityWithoutServices = Facility::factory()->create([
            'facility_name' => 'サービスなし施設',
        ]);

        $response = $this->get("/facilities/{$facilityWithoutServices->id}");
        
        $response->assertStatus(200);
        
        // サービス種類と有効期限が「未設定」として表示されることを確認
        $content = $response->getContent();
        
        // サービス種類の行で「未設定」が表示されることを確認
        $this->assertStringContainsString('サービス種類', $content);
        $this->assertStringContainsString('有効期限', $content);
    }

    /** @test */
    public function レスポンシブテーブルクラスが適用されること()
    {
        $response = $this->get("/facilities/{$this->facility->id}");
        
        $response->assertStatus(200);
        
        // table-responsiveクラスの確認
        $response->assertSee('table-responsive', false);
    }

    /** @test */
    public function テーブルスタイル属性が正しく設定されること()
    {
        $response = $this->get("/facilities/{$this->facility->id}");
        
        $response->assertStatus(200);
        
        // テーブルのスタイル属性確認
        $response->assertSee('--bs-table-cell-padding-x: 0', false);
        $response->assertSee('--bs-table-cell-padding-y: 0', false);
        $response->assertSee('margin-bottom: 0', false);
    }
}
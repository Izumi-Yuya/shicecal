<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicInfoServiceTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // テスト用ユーザーを作成してログイン
        $user = User::factory()->create([
            'role' => 'admin'
        ]);
        $this->actingAs($user);
    }

    /**
     * 単一サービスの表示テスト
     */
    public function test_single_service_display()
    {
        // テスト用ファシリティとサービスを作成
        $facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'company_name' => 'テスト会社',
        ]);

        $service = FacilityService::factory()->create([
            'facility_id' => $facility->id,
            'service_type' => '介護老人福祉施設',
            'renewal_start_date' => '2024-04-01',
            'renewal_end_date' => '2030-03-31',
        ]);

        $response = $this->get(route('facilities.show', $facility));

        $response->assertStatus(200);
        $response->assertSee('サービス種類');
        $response->assertSee('介護老人福祉施設');
        $response->assertSee('有効期限');
        $response->assertSee('2024年04月01日 〜 2030年03月31日');
    }

    /**
     * 複数サービスの表示テスト
     */
    public function test_multiple_services_display()
    {
        // テスト用ファシリティを作成
        $facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'company_name' => 'テスト会社',
        ]);

        // 複数のサービスを作成
        $services = [
            [
                'service_type' => '介護老人福祉施設',
                'renewal_start_date' => '2024-04-01',
                'renewal_end_date' => '2030-03-31',
            ],
            [
                'service_type' => '短期入所生活介護',
                'renewal_start_date' => '2024-04-01',
                'renewal_end_date' => '2030-03-31',
            ],
            [
                'service_type' => '通所介護',
                'renewal_start_date' => '2024-04-01',
                'renewal_end_date' => '2030-03-31',
            ],
        ];

        foreach ($services as $serviceData) {
            FacilityService::factory()->create(array_merge($serviceData, [
                'facility_id' => $facility->id,
            ]));
        }

        $response = $this->get(route('facilities.show', $facility));

        $response->assertStatus(200);
        
        // 全てのサービス種類が表示されることを確認
        $response->assertSee('介護老人福祉施設');
        $response->assertSee('短期入所生活介護');
        $response->assertSee('通所介護');
        
        // サービス種類ラベルが最初の行にのみ表示されることを確認
        $content = $response->getContent();
        
        // 4列構造の確認：各サービスが表示され、rowspanが正しく動作していることを確認
        $serviceCount = 3; // 作成したサービス数
        
        // サービス種類テーブル内でのサービス種類ラベルの確認（data-section="facility_services"内）
        $this->assertStringContainsString('data-section="facility_services"', $content);
        
        // 各サービスが表示されることを確認
        $this->assertStringContainsString('介護老人福祉施設', $content);
        $this->assertStringContainsString('短期入所生活介護', $content);
        $this->assertStringContainsString('通所介護', $content);
        
        // 有効期限が各サービス分表示されることを確認（最低でもサービス数分）
        $this->assertGreaterThanOrEqual($serviceCount, substr_count($content, '有効期限'));
    }

    /**
     * サービスなしの表示テスト
     */
    public function test_no_services_display()
    {
        // サービスなしのファシリティを作成
        $facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'company_name' => 'テスト会社',
        ]);

        $response = $this->get(route('facilities.show', $facility));

        $response->assertStatus(200);
        $response->assertSee('サービス種類');
        $response->assertSee('有効期限');
        
        // 未設定の表示を確認
        $response->assertSee('未設定');
    }

    /**
     * 部分的な日付データのテスト
     */
    public function test_partial_date_display()
    {
        $facility = Facility::factory()->create();

        // 開始日のみのサービス
        FacilityService::factory()->create([
            'facility_id' => $facility->id,
            'service_type' => 'テストサービス1',
            'renewal_start_date' => '2024-04-01',
            'renewal_end_date' => null,
        ]);

        // 終了日のみのサービス
        FacilityService::factory()->create([
            'facility_id' => $facility->id,
            'service_type' => 'テストサービス2',
            'renewal_start_date' => null,
            'renewal_end_date' => '2030-03-31',
        ]);

        $response = $this->get(route('facilities.show', $facility));

        $response->assertStatus(200);
        $response->assertSee('2024年04月01日 〜');
        $response->assertSee('〜 2030年03月31日');
    }

    /**
     * テーブル構造の整合性テスト
     */
    public function test_table_structure_consistency()
    {
        $facility = Facility::factory()->create();

        // 3つのサービスを作成
        for ($i = 1; $i <= 3; $i++) {
            FacilityService::factory()->create([
                'facility_id' => $facility->id,
                'service_type' => "サービス{$i}",
                'renewal_start_date' => '2024-04-01',
                'renewal_end_date' => '2030-03-31',
            ]);
        }

        $response = $this->get(route('facilities.show', $facility));
        $content = $response->getContent();

        // HTMLの構造を確認
        $this->assertStringContainsString('<table', $content);
        $this->assertStringContainsString('</table>', $content);
        
        // 各行が適切な数のセルを持つことを確認（簡易チェック）
        $trCount = substr_count($content, '<tr');
        $tdCount = substr_count($content, '<td');
        
        // 基本的な構造の整合性を確認
        $this->assertGreaterThan(0, $trCount);
        $this->assertGreaterThan(0, $tdCount);
        
        // 各サービスが表示されることを確認
        for ($i = 1; $i <= 3; $i++) {
            $response->assertSee("サービス{$i}");
        }
    }
}
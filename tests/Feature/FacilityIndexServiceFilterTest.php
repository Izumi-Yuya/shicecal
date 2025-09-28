<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacilityIndexServiceFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用ユーザーを作成してログイン
        $user = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($user);
    }

    /**
     * 部門フィルターの表示テスト
     */
    public function test_section_filter_display()
    {
        // テスト用ファシリティとサービスを作成
        $facility1 = Facility::factory()->create(['facility_name' => 'テスト施設1']);
        $facility2 = Facility::factory()->create(['facility_name' => 'テスト施設2']);

        FacilityService::factory()->create([
            'facility_id' => $facility1->id,
            'section' => '有料老人ホーム',
        ]);

        FacilityService::factory()->create([
            'facility_id' => $facility2->id,
            'section' => 'デイサービスセンター',
        ]);

        $response = $this->get(route('facilities.index'));

        $response->assertStatus(200);

        // 部門フィルターのラベルが正しく表示されることを確認
        $response->assertSee('部門');

        // 部門のオプションが表示されることを確認
        $response->assertSee('有料老人ホーム');
        $response->assertSee('デイサービスセンター');

        // 「すべての部門」オプションが表示されることを確認
        $response->assertSee('すべての部門');
    }

    /**
     * 部門フィルターの機能テスト
     */
    public function test_section_filter_functionality()
    {
        // テスト用ファシリティとサービスを作成
        $facility1 = Facility::factory()->create(['facility_name' => 'テスト施設1']);
        $facility2 = Facility::factory()->create(['facility_name' => 'テスト施設2']);
        $facility3 = Facility::factory()->create(['facility_name' => 'テスト施設3']);

        FacilityService::factory()->create([
            'facility_id' => $facility1->id,
            'section' => '有料老人ホーム',
        ]);

        FacilityService::factory()->create([
            'facility_id' => $facility2->id,
            'section' => 'デイサービスセンター',
        ]);

        FacilityService::factory()->create([
            'facility_id' => $facility3->id,
            'section' => '有料老人ホーム',
        ]);

        // 「有料老人ホーム」でフィルター
        $response = $this->get(route('facilities.index', ['section' => '有料老人ホーム']));

        $response->assertStatus(200);
        $response->assertSee('テスト施設1');
        $response->assertSee('テスト施設3');
        $response->assertDontSee('テスト施設2');

        // 「デイサービスセンター」でフィルター
        $response = $this->get(route('facilities.index', ['section' => 'デイサービスセンター']));

        $response->assertStatus(200);
        $response->assertSee('テスト施設2');
        $response->assertDontSee('テスト施設1');
        $response->assertDontSee('テスト施設3');
    }

    /**
     * フィルター選択状態の保持テスト
     */
    public function test_section_filter_selection_persistence()
    {
        // テスト用ファシリティとサービスを作成
        $facility = Facility::factory()->create(['facility_name' => 'テスト施設']);
        FacilityService::factory()->create([
            'facility_id' => $facility->id,
            'section' => '有料老人ホーム',
        ]);

        $response = $this->get(route('facilities.index', ['section' => '有料老人ホーム']));

        $response->assertStatus(200);

        // 選択されたオプションがselectedになっていることを確認
        $content = $response->getContent();
        $this->assertStringContainsString('value="有料老人ホーム"', $content);
        $this->assertStringContainsString('selected>', $content);
    }

    /**
     * 複数フィルターの組み合わせテスト
     */
    public function test_multiple_filters_combination()
    {
        // テスト用ファシリティとサービスを作成
        $facility1 = Facility::factory()->create([
            'facility_name' => 'テスト施設1',
            'office_code' => '01001', // 北海道
        ]);

        $facility2 = Facility::factory()->create([
            'facility_name' => 'テスト施設2',
            'office_code' => '13001', // 東京都
        ]);

        FacilityService::factory()->create([
            'facility_id' => $facility1->id,
            'section' => '有料老人ホーム',
        ]);

        FacilityService::factory()->create([
            'facility_id' => $facility2->id,
            'section' => '有料老人ホーム',
        ]);

        // 部門とキーワードの組み合わせフィルター
        $response = $this->get(route('facilities.index', [
            'section' => '有料老人ホーム',
            'keyword' => 'テスト施設1',
        ]));

        $response->assertStatus(200);
        $response->assertSee('テスト施設1');
        $response->assertDontSee('テスト施設2');
    }

    /**
     * サービスなしの施設の表示テスト
     */
    public function test_facilities_without_services()
    {
        // サービスなしの施設を作成
        $facility = Facility::factory()->create(['facility_name' => 'サービスなし施設']);

        // フィルターなしの場合は表示される
        $response = $this->get(route('facilities.index'));
        $response->assertStatus(200);
        $response->assertSee('サービスなし施設');

        // 部門フィルターを適用した場合は表示されない
        $response = $this->get(route('facilities.index', ['section' => '有料老人ホーム']));
        $response->assertStatus(200);
        $response->assertDontSee('サービスなし施設');
    }

    /**
     * 存在しない部門でのフィルターテスト
     */
    public function test_nonexistent_section_filter()
    {
        // テスト用ファシリティを作成
        $facility = Facility::factory()->create(['facility_name' => 'テスト施設']);
        FacilityService::factory()->create([
            'facility_id' => $facility->id,
            'section' => '有料老人ホーム',
        ]);

        // 存在しない部門でフィルター
        $response = $this->get(route('facilities.index', ['section' => '存在しない部門']));

        $response->assertStatus(200);
        $response->assertDontSee('テスト施設');

        // 検索結果が0件であることを確認
        $content = $response->getContent();
        $this->assertStringContainsString('0件の施設が見つかりました', $content);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\MaintenanceHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RepairHistoryIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Facility $facility;
    private User $adminUser;
    private User $editorUser;
    private User $approverUser;
    private User $viewerUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test facility
        $this->facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'office_code' => 'TEST001',
        ]);

        // Create users with different roles
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->editorUser = User::factory()->create(['role' => 'editor']);
        $this->approverUser = User::factory()->create(['role' => 'approver']);
        $this->viewerUser = User::factory()->create(['role' => 'viewer']);

        // Create test maintenance history data
        $this->createTestMaintenanceHistories();
    }

    private function createTestMaintenanceHistories(): void
    {
        // 外装 - 防水
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'maintenance_date' => '2024-01-15',
            'contractor' => '防水工事株式会社',
            'content' => '屋上防水工事',
            'cost' => 1500000,
            'contact_person' => '田中太郎',
            'phone_number' => '03-1234-5678',
            'classification' => '大規模修繕',
            'notes' => '10年保証付き',
            'warranty_period_years' => 10,
            'warranty_start_date' => '2024-01-15',
            'warranty_end_date' => '2034-01-15',
            'created_by' => $this->adminUser->id,
        ]);

        // 外装 - 塗装
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'painting',
            'maintenance_date' => '2024-02-20',
            'contractor' => '塗装工業株式会社',
            'content' => '外壁塗装工事',
            'cost' => 800000,
            'contact_person' => '佐藤花子',
            'phone_number' => '03-2345-6789',
            'classification' => '定期修繕',
            'notes' => '耐候性塗料使用',
            'created_by' => $this->editorUser->id,
        ]);

        // 内装リニューアル
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'interior',
            'subcategory' => 'renovation',
            'maintenance_date' => '2024-03-10',
            'contractor' => '内装リフォーム株式会社',
            'content' => '1階フロア全面リニューアル',
            'cost' => 2500000,
            'contact_person' => '鈴木一郎',
            'phone_number' => '03-3456-7890',
            'classification' => 'リニューアル工事',
            'notes' => 'バリアフリー対応',
            'created_by' => $this->adminUser->id,
        ]);

        // 内装・意匠
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'interior',
            'subcategory' => 'design',
            'maintenance_date' => '2024-04-05',
            'contractor' => '意匠デザイン株式会社',
            'content' => 'エントランス意匠改修',
            'cost' => 600000,
            'contact_person' => '高橋美咲',
            'phone_number' => '03-4567-8901',
            'classification' => '意匠改修',
            'notes' => 'LED照明導入',
            'created_by' => $this->editorUser->id,
        ]);

        // その他 - 改修工事
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'other',
            'subcategory' => 'renovation_work',
            'maintenance_date' => '2024-05-12',
            'contractor' => '総合建設株式会社',
            'content' => '給排水設備改修工事',
            'cost' => 1200000,
            'contact_person' => '山田次郎',
            'phone_number' => '03-5678-9012',
            'classification' => '設備改修',
            'notes' => '配管全面交換',
            'created_by' => $this->adminUser->id,
        ]);
    }

    /**
     * Test 1: 施設詳細画面での修繕履歴タブ表示テスト
     */
    public function test_repair_history_tab_display_in_facility_show_page()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('facilities.show', $this->facility));

        $response->assertOk();
        
        // 修繕履歴タブが表示されることを確認
        $response->assertSee('修繕履歴');
        $response->assertSee('外装');
        $response->assertSee('内装リニューアル');
        $response->assertSee('その他');
        
        // 修繕履歴データが表示されることを確認
        $response->assertSee('防水工事株式会社');
        $response->assertSee('塗装工業株式会社');
        $response->assertSee('内装リフォーム株式会社');
        $response->assertSee('意匠デザイン株式会社');
        $response->assertSee('総合建設株式会社');
        
        // 金額が適切にフォーマットされて表示されることを確認
        $response->assertSee('1,500,000');
        $response->assertSee('800,000');
        $response->assertSee('2,500,000');
        
        // 保証期間情報が表示されることを確認（防水のみ）
        $response->assertSee('10年保証付き');
        $response->assertSee('2034年01月15日');
    }

    /**
     * Test 2: データ作成・更新の一連の流れテスト
     */
    public function test_complete_data_creation_and_update_flow()
    {
        $this->actingAs($this->adminUser);

        // Step 1: 編集画面にアクセス
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));
        $response->assertOk();
        $response->assertSee('外装修繕履歴編集');

        // Step 2: 新しいデータを追加
        $newData = [
            '_token' => csrf_token(),
            'histories' => [
                [
                    'maintenance_date' => '2024-06-15',
                    'contractor' => '新規防水工事株式会社',
                    'content' => '新規防水工事',
                    'subcategory' => 'waterproof',
                    'cost' => 900000,
                    'contact_person' => '新田太郎',
                    'phone_number' => '03-9999-0000',
                    'classification' => '緊急修繕',
                    'notes' => '雨漏り対応',
                    'warranty_period_years' => 5,
                    'warranty_start_date' => '2024-06-15',
                    'warranty_end_date' => '2029-06-15',
                ],
            ],
        ];

        $response = $this->withSession(['_token' => csrf_token()])
            ->put(route('facilities.repair-history.update', [$this->facility, 'exterior']), $newData);

        $response->assertRedirect(route('facilities.show', $this->facility));
        $response->assertSessionHas('success', '修繕履歴が更新されました。');

        // Step 3: データが正しく保存されたことを確認
        $this->assertDatabaseHas('maintenance_histories', [
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'contractor' => '新規防水工事株式会社',
            'content' => '新規防水工事',
            'cost' => 900000,
            'warranty_period_years' => 5,
            'created_by' => $this->adminUser->id,
        ]);

        // Step 4: 詳細画面で新しいデータが表示されることを確認
        $response = $this->get(route('facilities.show', $this->facility));
        $response->assertOk();
        $response->assertSee('新規防水工事株式会社');
        $response->assertSee('新規防水工事');
        $response->assertSee('900,000');
        $response->assertSee('雨漏り対応');

        // Step 5: 既存データの更新
        $existingHistory = MaintenanceHistory::where([
            'facility_id' => $this->facility->id,
            'contractor' => '新規防水工事株式会社',
        ])->first();

        $updateData = [
            '_token' => csrf_token(),
            'histories' => [
                [
                    'id' => $existingHistory->id,
                    'maintenance_date' => '2024-06-20',
                    'contractor' => '更新防水工事株式会社',
                    'content' => '更新防水工事',
                    'subcategory' => 'waterproof',
                    'cost' => 950000,
                    'contact_person' => '更新田太郎',
                    'phone_number' => '03-8888-0000',
                    'classification' => '緊急修繕（更新）',
                    'notes' => '雨漏り対応完了',
                    'warranty_period_years' => 7,
                    'warranty_start_date' => '2024-06-20',
                    'warranty_end_date' => '2031-06-20',
                ],
            ],
        ];

        $response = $this->withSession(['_token' => csrf_token()])
            ->put(route('facilities.repair-history.update', [$this->facility, 'exterior']), $updateData);

        $response->assertRedirect(route('facilities.show', $this->facility));

        // Step 6: 更新されたデータを確認
        $this->assertDatabaseHas('maintenance_histories', [
            'id' => $existingHistory->id,
            'contractor' => '更新防水工事株式会社',
            'content' => '更新防水工事',
            'cost' => 950000,
            'warranty_period_years' => 7,
        ]);
    }

    /**
     * Test 3: 複数ユーザーでの権限のテスト
     */
    public function test_multiple_user_permission_scenarios()
    {
        // Admin user - 全権限
        $this->actingAs($this->adminUser);
        
        $response = $this->get(route('facilities.show', $this->facility));
        $response->assertOk();
        $response->assertSee('編集');
        
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));
        $response->assertOk();

        // Editor user - 編集権限あり
        $this->actingAs($this->editorUser);
        
        $response = $this->get(route('facilities.show', $this->facility));
        $response->assertOk();
        $response->assertSee('編集');
        
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));
        $response->assertOk();

        // Approver user - 編集権限なし
        $this->actingAs($this->approverUser);
        
        $response = $this->get(route('facilities.show', $this->facility));
        $response->assertOk();
        $response->assertDontSee('編集');
        
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));
        $response->assertForbidden();

        // Viewer user - 閲覧のみ
        $this->actingAs($this->viewerUser);
        
        $response = $this->get(route('facilities.show', $this->facility));
        $response->assertOk();
        $response->assertDontSee('編集');
        
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));
        $response->assertForbidden();

        // Unauthenticated user - アクセス不可
        $this->app['auth']->logout();
        
        $response = $this->get(route('facilities.show', $this->facility));
        $response->assertRedirect(route('login'));
        
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test 4: タブ切り替え機能とデータ表示の整合性テスト
     */
    public function test_tab_switching_and_data_consistency()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('facilities.show', $this->facility));
        $response->assertOk();

        // 外装タブのデータ確認
        $response->assertSee('防水工事株式会社');
        $response->assertSee('塗装工業株式会社');
        $response->assertSee('屋上防水工事');
        $response->assertSee('外壁塗装工事');
        
        // 内装リニューアルタブのデータ確認
        $response->assertSee('内装リフォーム株式会社');
        $response->assertSee('意匠デザイン株式会社');
        $response->assertSee('1階フロア全面リニューアル');
        $response->assertSee('エントランス意匠改修');
        
        // その他タブのデータ確認
        $response->assertSee('総合建設株式会社');
        $response->assertSee('給排水設備改修工事');
        
        // 日付順ソートの確認（最新が上）
        $content = $response->getContent();
        $pos1 = strpos($content, '2024-05-12'); // その他の最新
        $pos2 = strpos($content, '2024-04-05'); // 内装の最新
        $pos3 = strpos($content, '2024-03-10'); // 内装の古い
        $pos4 = strpos($content, '2024-02-20'); // 外装の最新
        $pos5 = strpos($content, '2024-01-15'); // 外装の古い
        
        // 各カテゴリ内で日付順になっていることを確認
        $this->assertTrue($pos2 < $pos3); // 内装: 新しい日付が先
        $this->assertTrue($pos4 < $pos5); // 外装: 新しい日付が先
    }

    /**
     * Test 5: エラーハンドリングとバリデーションの統合テスト
     */
    public function test_error_handling_and_validation_integration()
    {
        $this->actingAs($this->adminUser);

        // 無効なデータでの更新テスト
        $invalidData = [
            '_token' => csrf_token(),
            'histories' => [
                [
                    'maintenance_date' => 'invalid-date',
                    'contractor' => '', // 必須フィールドが空
                    'content' => '',
                    'subcategory' => 'invalid-subcategory',
                    'cost' => -1000, // 負の値
                    'warranty_period_years' => 100, // 範囲外
                ],
            ],
        ];

        $response = $this->withSession(['_token' => csrf_token()])
            ->put(route('facilities.repair-history.update', [$this->facility, 'exterior']), $invalidData);

        $response->assertSessionHasErrors([
            'histories.0.maintenance_date',
            'histories.0.contractor',
            'histories.0.content',
            'histories.0.subcategory',
            'histories.0.cost',
            'histories.0.warranty_period_years',
        ]);

        // 存在しない施設でのアクセステスト
        $nonExistentFacility = Facility::factory()->make(['id' => 99999]);
        
        $response = $this->get(route('facilities.repair-history.edit', [$nonExistentFacility, 'exterior']));
        $response->assertNotFound();

        // 無効なカテゴリでのアクセステスト
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'invalid-category']));
        $response->assertNotFound();
    }

    /**
     * Test 6: データの整合性と関連性テスト
     */
    public function test_data_integrity_and_relationships()
    {
        $this->actingAs($this->adminUser);

        // 施設との関連性確認
        $histories = MaintenanceHistory::where('facility_id', $this->facility->id)->get();
        $this->assertCount(5, $histories);

        foreach ($histories as $history) {
            $this->assertEquals($this->facility->id, $history->facility_id);
            $this->assertNotNull($history->facility);
            $this->assertEquals($this->facility->facility_name, $history->facility->facility_name);
        }

        // 作成者との関連性確認
        foreach ($histories as $history) {
            $this->assertNotNull($history->creator);
            $this->assertContains($history->creator->role, ['admin', 'editor']);
        }

        // カテゴリ別データ数確認
        $exteriorCount = MaintenanceHistory::byCategory('exterior')
            ->where('facility_id', $this->facility->id)
            ->count();
        $this->assertEquals(2, $exteriorCount);

        $interiorCount = MaintenanceHistory::byCategory('interior')
            ->where('facility_id', $this->facility->id)
            ->count();
        $this->assertEquals(2, $interiorCount);

        $otherCount = MaintenanceHistory::byCategory('other')
            ->where('facility_id', $this->facility->id)
            ->count();
        $this->assertEquals(1, $otherCount);
    }

    /**
     * Test 7: パフォーマンスと大量データテスト
     */
    public function test_performance_with_large_dataset()
    {
        $this->actingAs($this->adminUser);

        // 大量のテストデータを作成
        MaintenanceHistory::factory()->count(50)->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'created_by' => $this->adminUser->id,
        ]);

        MaintenanceHistory::factory()->count(30)->create([
            'facility_id' => $this->facility->id,
            'category' => 'interior',
            'subcategory' => 'renovation',
            'created_by' => $this->adminUser->id,
        ]);

        MaintenanceHistory::factory()->count(20)->create([
            'facility_id' => $this->facility->id,
            'category' => 'other',
            'subcategory' => 'renovation_work',
            'created_by' => $this->adminUser->id,
        ]);

        // パフォーマンステスト（レスポンス時間測定）
        $startTime = microtime(true);
        
        $response = $this->get(route('facilities.show', $this->facility));
        
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        $response->assertOk();
        
        // レスポンス時間が2秒以内であることを確認
        $this->assertLessThan(2.0, $responseTime, 'Response time should be less than 2 seconds');

        // データが正しく表示されることを確認
        $response->assertSee('修繕履歴');
        
        // 総データ数確認
        $totalCount = MaintenanceHistory::where('facility_id', $this->facility->id)->count();
        $this->assertEquals(105, $totalCount); // 5 (initial) + 100 (added)
    }
}
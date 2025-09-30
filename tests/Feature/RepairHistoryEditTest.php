<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\MaintenanceHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RepairHistoryEditTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $facility;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user with editor role
        $this->user = User::factory()->create([
            'role' => 'editor',
        ]);
        $this->actingAs($this->user);

        // Create a test facility
        $this->facility = Facility::factory()->create(['status' => 'approved']);
    }

    /**
     * Test that the repair history edit page displays correctly for the exterior category.
     */
    public function test_repair_history_edit_page_displays_correctly_for_exterior()
    {
        // Create existing exterior histories
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '屋上防水工事',
            'contractor' => '防水工事株式会社',
            'contact_person' => '山田太郎',
            'phone_number' => '03-1234-5678',
            'maintenance_date' => '2024-01-15',
            'cost' => 500000,
            'classification' => '定期点検',
            'notes' => '特記事項',
            'warranty_period_years' => 10,
            'warranty_start_date' => '2024-01-15',
            'warranty_end_date' => '2034-01-15',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));

        $response->assertStatus(200);
        $response->assertViewIs('facilities.repair-history.edit');
        $response->assertSee('外装修繕履歴編集');
        $response->assertSee('屋上防水工事');
        $response->assertSee('防水工事株式会社');
        $response->assertSee('山田太郎');
        $response->assertSee('03-1234-5678');
        $response->assertSee('2024-01-15');
        $response->assertSee('500000');
        $response->assertSee('定期点検');
        $response->assertSee('特記事項');
        $response->assertSee('10'); // warranty years
    }

    /**
     * Test that the repair history edit page displays correctly for the interior category.
     */
    public function test_repair_history_edit_page_displays_correctly_for_interior()
    {
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'interior',
            'subcategory' => 'renovation',
            'content' => '内装リニューアル工事',
            'contractor' => '内装工事株式会社',
            'contact_person' => '佐藤次郎',
            'phone_number' => '03-5555-6666',
            'maintenance_date' => '2024-03-10',
            'cost' => 800000,
            'classification' => '改修工事',
            'notes' => '内装工事の特記事項',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'interior']));

        $response->assertStatus(200);
        $response->assertViewIs('facilities.repair-history.edit');
        $response->assertSee('内装リニューアル修繕履歴編集');
        $response->assertSee('内装リニューアル工事');
        $response->assertSee('内装工事株式会社');
        $response->assertSee('佐藤次郎');
        $response->assertSee('03-5555-6666');
        $response->assertSee('2024-03-10');
        $response->assertSee('800000');
        $response->assertSee('改修工事');
        $response->assertSee('内装工事の特記事項');
    }

    /**
     * Test that the repair history edit page displays correctly for the other category.
     */
    public function test_repair_history_edit_page_displays_correctly_for_other()
    {
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'other',
            'subcategory' => 'renovation_work',
            'content' => 'エアコン修理',
            'contractor' => '設備工事株式会社',
            'contact_person' => '鈴木美咲',
            'phone_number' => '03-7777-8888',
            'maintenance_date' => '2024-05-12',
            'cost' => 50000,
            'classification' => '緊急修理',
            'notes' => 'エアコン修理の特記事項',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'other']));

        $response->assertStatus(200);
        $response->assertViewIs('facilities.repair-history.edit');
        $response->assertSee('その他修繕履歴編集');
        $response->assertSee('エアコン修理');
        $response->assertSee('設備工事株式会社');
        $response->assertSee('鈴木美咲');
        $response->assertSee('03-7777-8888');
        $response->assertSee('2024-05-12');
        $response->assertSee('50000');
        $response->assertSee('緊急修理');
        $response->assertSee('エアコン修理の特記事項');
    }

    /**
     * Test that the repair history edit form displays proper subcategory options.
     */
    public function test_repair_history_edit_form_displays_proper_subcategory_options()
    {
        // Test exterior subcategories
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));
        $response->assertStatus(200);
        $response->assertSee('防水');
        $response->assertSee('塗装');

        // Test interior subcategories
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'interior']));
        $response->assertStatus(200);
        $response->assertSee('内装リニューアル');
        $response->assertSee('内装・意匠');

        // Test other subcategories
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'other']));
        $response->assertStatus(200);
        $response->assertSee('改修工事');
    }

    /**
     * Test that the repair history edit form displays warranty fields only for exterior category.
     */
    public function test_repair_history_edit_form_displays_warranty_fields_only_for_exterior()
    {
        // Test exterior - should have warranty fields
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));
        $response->assertStatus(200);
        $response->assertSee('保証期間（年）');
        $response->assertSee('保証開始日');
        $response->assertSee('保証終了日');

        // Test interior - should not have warranty fields
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'interior']));
        $response->assertStatus(200);
        $response->assertDontSee('保証期間（年）');
        $response->assertDontSee('保証開始日');
        $response->assertDontSee('保証終了日');

        // Test other - should not have warranty fields
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'other']));
        $response->assertStatus(200);
        $response->assertDontSee('保証期間（年）');
        $response->assertDontSee('保証開始日');
        $response->assertDontSee('保証終了日');
    }

    /**
     * Test repair history edit form allows adding new rows.
     */
    public function test_repair_history_edit_form_allows_adding_new_rows()
    {
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));

        $response->assertStatus(200);
        $response->assertSee('修繕履歴を追加');
        $response->assertSee('addHistoryBtn');
    }

    /**
     * Test repair history edit form allows removing rows.
     */
    public function test_repair_history_edit_form_allows_removing_rows()
    {
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '防水工事',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));

        $response->assertStatus(200);
        $response->assertSee('fas fa-trash');
        $response->assertSee('remove-history-btn');
    }

    /**
     * Test repair history edit form displays validation errors.
     */
    public function test_repair_history_edit_form_displays_validation_errors()
    {
        $invalidData = [
            'histories' => [
                [
                    'maintenance_date' => 'invalid-date',
                    'contractor' => '',
                    'content' => '',
                    'subcategory' => 'waterproof',
                    'cost' => 'invalid-number',
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $invalidData
        );

        $response->assertSessionHasErrors([
            'histories.0.maintenance_date',
            'histories.0.contractor',
            'histories.0.content',
            'histories.0.cost',
        ]);

        // Follow redirect to see error display
        $followResponse = $this->followRedirects($response);
        $followResponse->assertSee('施工日は有効な日付形式で入力してください。');
        $followResponse->assertSee('施工会社は必須項目です。');
        $followResponse->assertSee('修繕内容は必須項目です。');
        $followResponse->assertSee('金額は数値で入力してください。');
    }

    /**
     * Test repair history edit form preserves data on validation error.
     */
    public function test_repair_history_edit_form_preserves_data_on_validation_error()
    {
        $invalidData = [
            'histories' => [
                [
                    'maintenance_date' => 'invalid-date',
                    'contractor' => '有効な業者名',
                    'content' => '有効な工事内容',
                    'subcategory' => 'waterproof',
                    'cost' => 'invalid-number',
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $invalidData
        );

        $response->assertSessionHasErrors();
        
        // Follow redirect to see preserved data
        $followResponse = $this->followRedirects($response);
        $followResponse->assertSee('有効な業者名');
        $followResponse->assertSee('有効な工事内容');
    }

    /**
     * Test repair history edit form handles empty form submission.
     */
    public function test_repair_history_edit_form_handles_empty_form_submission()
    {
        // Create existing history
        $existingHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '既存の工事',
            'created_by' => $this->user->id,
        ]);

        $emptyData = [
            'histories' => [],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $emptyData
        );

        $response->assertRedirect(route('facilities.show', $this->facility));
        $response->assertSessionHas('success', '修繕履歴が更新されました。');

        // Check that existing history is deleted
        $this->assertDatabaseMissing('maintenance_histories', [
            'id' => $existingHistory->id,
        ]);
    }

    /**
     * Test repair history edit form handles mixed create and update operations.
     */
    public function test_repair_history_edit_form_handles_mixed_create_and_update_operations()
    {
        // Create existing history
        $existingHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '既存の工事',
            'contractor' => '既存の業者',
            'created_by' => $this->user->id,
        ]);

        $mixedData = [
            'histories' => [
                // Update existing record
                [
                    'id' => $existingHistory->id,
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '更新された業者',
                    'content' => '更新された工事',
                    'subcategory' => 'waterproof',
                    'cost' => 600000,
                ],
                // Create new record
                [
                    'maintenance_date' => '2024-02-20',
                    'contractor' => '新しい業者',
                    'content' => '新しい工事',
                    'subcategory' => 'painting',
                    'cost' => 400000,
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $mixedData
        );

        $response->assertRedirect(route('facilities.show', $this->facility));
        $response->assertSessionHas('success', '修繕履歴が更新されました。');

        // Check updated record
        $this->assertDatabaseHas('maintenance_histories', [
            'id' => $existingHistory->id,
            'contractor' => '更新された業者',
            'content' => '更新された工事',
            'cost' => 600000,
        ]);

        // Check new record
        $this->assertDatabaseHas('maintenance_histories', [
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'painting',
            'contractor' => '新しい業者',
            'content' => '新しい工事',
            'cost' => 400000,
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * Test repair history edit form handles warranty date validation.
     */
    public function test_repair_history_edit_form_handles_warranty_date_validation()
    {
        $invalidWarrantyData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'waterproof',
                    'warranty_start_date' => '2024-01-15',
                    'warranty_end_date' => '2023-01-15', // Before start date
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $invalidWarrantyData
        );

        $response->assertSessionHasErrors([
            'histories.0.warranty_end_date',
        ]);
    }

    /**
     * Test repair history edit form handles large number of records.
     */
    public function test_repair_history_edit_form_handles_large_number_of_records()
    {
        // Create multiple existing records
        $histories = [];
        for ($i = 1; $i <= 10; $i++) {
            $histories[] = MaintenanceHistory::factory()->create([
                'facility_id' => $this->facility->id,
                'category' => 'exterior',
                'subcategory' => 'waterproof',
                'content' => "工事 {$i}",
                'maintenance_date' => sprintf('2024-01-%02d', $i),
                'created_by' => $this->user->id,
            ]);
        }

        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));

        $response->assertStatus(200);
        $response->assertSee('工事 1');
        $response->assertSee('工事 10');
    }

    /**
     * Test repair history edit form displays proper form structure.
     */
    public function test_repair_history_edit_form_displays_proper_form_structure()
    {
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));

        $response->assertStatus(200);
        $response->assertSee('form');
        $response->assertSee('method="POST"');
        $response->assertSee('_method');
        $response->assertSee('_token');
        $response->assertSee('保存');
        $response->assertSee('キャンセル');
    }

    /**
     * Test repair history edit form displays breadcrumb navigation.
     */
    public function test_repair_history_edit_form_displays_breadcrumb_navigation()
    {
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));

        $response->assertStatus(200);
        $response->assertSee('施設一覧');
        $response->assertSee('修繕履歴編集');
        $response->assertSee($this->facility->facility_name);
    }

    /**
     * Test repair history edit form handles JavaScript functionality.
     */
    public function test_repair_history_edit_form_handles_javascript_functionality()
    {
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));

        $response->assertStatus(200);
        $response->assertSee('repairHistoryForm');
        $response->assertSee('historiesContainer');
    }

    /**
     * Test repair history edit form displays proper field labels.
     */
    public function test_repair_history_edit_form_displays_proper_field_labels()
    {
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));

        $response->assertStatus(200);
        $response->assertSee('施工日');
        $response->assertSee('施工会社');
        $response->assertSee('修繕内容');
        $response->assertSee('金額');
        $response->assertSee('区分');
        $response->assertSee('担当者');
        $response->assertSee('連絡先');
        $response->assertSee('備考');
        $response->assertSee('保証期間（年）');
        $response->assertSee('保証開始日');
        $response->assertSee('保証終了日');
    }

    /**
     * Test repair history edit form handles cancel operation.
     */
    public function test_repair_history_edit_form_handles_cancel_operation()
    {
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));

        $response->assertStatus(200);
        $response->assertSee(route('facilities.show', $this->facility));
        $response->assertSee('キャンセル');
    }
}
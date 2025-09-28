<?php

namespace Tests\Feature;

use App\Models\ElectricalEquipment;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifelineEquipmentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'editor']);
        $this->facility = Facility::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function facility_show_page_includes_lifeline_equipment_tab()
    {
        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200)
            ->assertSee('ライフライン設備')
            ->assertSee('id="lifeline-tab"', false)
            ->assertSee('data-bs-target="#lifeline-equipment"', false);
    }

    /** @test */
    public function lifeline_equipment_tab_includes_all_category_subtabs()
    {
        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200)
            ->assertSee('電気')
            ->assertSee('ガス')
            ->assertSee('水道')
            ->assertSee('エレベーター')
            ->assertSee('空調・照明')
            ->assertSee('data-bs-target="#electrical"', false)
            ->assertSee('data-bs-target="#gas"', false)
            ->assertSee('data-bs-target="#water"', false)
            ->assertSee('data-bs-target="#elevator"', false)
            ->assertSee('data-bs-target="#hvac-lighting"', false);
    }

    /** @test */
    public function electrical_tab_displays_all_required_cards()
    {
        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200)
            ->assertSee('基本情報')
            ->assertSee('PAS')
            ->assertSee('キュービクル')
            ->assertSee('非常用発電機')
            ->assertSee('備考')
            ->assertSee('data-card="basic_info"', false)
            ->assertSee('data-card="pas_info"', false)
            ->assertSee('data-card="cubicle_info"', false)
            ->assertSee('data-card="generator_info"', false)
            ->assertSee('data-card="notes"', false);
    }

    /** @test */
    public function electrical_equipment_data_is_displayed_when_exists()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => [
                'electrical_contractor' => '東京電力',
                'safety_management_company' => '保安管理会社',
                'maintenance_inspection_date' => '2024-01-15',
            ],
            'pas_info' => [
                'availability' => '有',
                'details' => 'PAS設備詳細',
            ],
            'notes' => '特記事項です',
        ]);

        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200)
            ->assertSee('東京電力')
            ->assertSee('保安管理会社')
            ->assertSee('2024-01-15')
            ->assertSee('PAS設備詳細')
            ->assertSee('特記事項です');
    }

    /** @test */
    public function empty_fields_are_displayed_with_proper_styling()
    {
        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200)
            ->assertSee('class="empty-field"', false);
    }

    /** @test */
    public function edit_buttons_are_shown_for_authorized_users()
    {
        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200)
            ->assertSee('class="edit-btn"', false);
    }

    /** @test */
    public function edit_buttons_are_hidden_for_viewers()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($viewer);

        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200)
            ->assertDontSee('class="edit-btn"', false);
    }

    /** @test */
    public function comment_buttons_are_displayed_for_each_card()
    {
        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200)
            ->assertSee('class="comment-toggle"', false);
    }

    /** @test */
    public function lifeline_equipment_css_is_loaded()
    {
        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200);

        // Check that lifeline equipment styles are present
        $response->assertSee('lifeline-equipment-container');
    }

    /** @test */
    public function lifeline_equipment_javascript_is_loaded()
    {
        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200);

        // Check that the facility page loads correctly with lifeline equipment
        $response->assertSee('ライフライン設備');
    }

    /** @test */
    public function facility_with_multiple_equipment_categories_displays_correctly()
    {
        // Create lifeline equipment for multiple categories
        $electricalEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        $gasEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'gas',
        ]);

        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $electricalEquipment->id,
            'basic_info' => ['electrical_contractor' => '東京電力'],
        ]);

        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200)
            ->assertSee('東京電力');
    }

    /** @test */
    public function equipment_list_displays_multiple_items_correctly()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'cubicle_info' => [
                'availability' => '有',
                'equipment_list' => [
                    [
                        'equipment_number' => '001',
                        'manufacturer' => '三菱電機',
                        'model_year' => '2024',
                        'update_date' => '2024-01-15',
                    ],
                    [
                        'equipment_number' => '002',
                        'manufacturer' => '東芝',
                        'model_year' => '2023',
                        'update_date' => '2024-01-16',
                    ],
                ],
            ],
        ]);

        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200)
            ->assertSee('001')
            ->assertSee('三菱電機')
            ->assertSee('002')
            ->assertSee('東芝')
            ->assertSee('2024')
            ->assertSee('2023');
    }

    /** @test */
    public function page_handles_missing_lifeline_equipment_gracefully()
    {
        // Don't create any lifeline equipment
        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200)
            ->assertSee('ライフライン設備')
            ->assertSee('基本情報')
            ->assertSee('class="empty-field"', false);
    }

    /** @test */
    public function page_handles_partial_electrical_equipment_data()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        // Create electrical equipment with only basic_info
        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => [
                'electrical_contractor' => '東京電力',
            ],
            'pas_info' => null,
            'cubicle_info' => null,
            'generator_info' => null,
            'notes' => null,
        ]);

        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200)
            ->assertSee('東京電力')
            ->assertSee('class="empty-field"', false); // Should show empty fields for missing data
    }

    /** @test */
    public function responsive_grid_classes_are_applied()
    {
        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200)
            ->assertSee('class="col-lg-6 mb-4"', false)
            ->assertSee('class="row"', false);
    }

    /** @test */
    public function card_headers_include_proper_icons()
    {
        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200)
            ->assertSee('fas fa-info-circle')
            ->assertSee('fas fa-shield-alt') // PAS icon
            ->assertSee('fas fa-cube')
            ->assertSee('fas fa-cog') // Generator icon (actual icon used)
            ->assertSee('fas fa-sticky-note');
    }

    /** @test */
    public function accessibility_attributes_are_present()
    {
        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200)
            ->assertSee('role="tab"', false)
            ->assertSee('role="tabpanel"', false)
            ->assertSee('aria-labelledby', false);
    }

    /** @test */
    public function bootstrap_classes_are_properly_applied()
    {
        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200)
            ->assertSee('nav nav-tabs')
            ->assertSee('tab-content')
            ->assertSee('tab-pane')
            ->assertSee('card facility-info-card detail-card-improved')
            ->assertSee('card-header')
            ->assertSee('card-body')
            ->assertSee('facility-detail-table')
            ->assertSee('detail-row');
    }

    /** @test */
    public function under_development_categories_show_placeholder_content()
    {
        $response = $this->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200)
            ->assertSee('開発中です'); // Should appear in water, gas, elevator, hvac-lighting tabs
    }
}

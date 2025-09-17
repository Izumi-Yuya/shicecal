<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifelineEquipmentTabTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'role' => 'admin'
        ]);
        
        // Create test facility
        $this->facility = Facility::factory()->create([
            'facility_name' => 'Test Facility',
            'office_code' => 'TEST001'
        ]);
    }

    public function test_lifeline_equipment_tab_is_rendered()
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        
        // Check that the lifeline equipment tab is present
        $response->assertSee('ライフライン設備');
        $response->assertSee('id="lifeline-tab"', false);
        $response->assertSee('data-bs-target="#lifeline-equipment"', false);
    }

    public function test_lifeline_equipment_sub_tabs_are_rendered()
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        
        // Check that all 5 sub-tabs are present
        $response->assertSee('電気');
        $response->assertSee('ガス');
        $response->assertSee('水道');
        $response->assertSee('エレベーター');
        $response->assertSee('空調・照明');
        
        // Check sub-tab navigation structure
        $response->assertSee('id="lifelineSubTabs"', false);
        $response->assertSee('id="electrical-tab"', false);
        $response->assertSee('id="gas-tab"', false);
        $response->assertSee('id="water-tab"', false);
        $response->assertSee('id="elevator-tab"', false);
        $response->assertSee('id="hvac-lighting-tab"', false);
    }

    public function test_electrical_equipment_cards_are_rendered()
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        
        // Check that electrical equipment cards are present
        $response->assertSee('基本情報');
        $response->assertSee('PAS');
        $response->assertSee('キュービクル');
        $response->assertSee('非常用発電機');
        $response->assertSee('備考');
        
        // Check card structure
        $response->assertSee('facility-info-card detail-card-improved', false);
        $response->assertSee('comment-toggle', false);
    }

    public function test_other_equipment_categories_show_development_status()
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        
        // Check that other categories show development status
        $response->assertSee('詳細仕様は開発中です');
        $response->assertSee('基本的なカード構造が準備されています');
    }

    public function test_lifeline_equipment_styles_and_scripts_are_included()
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        
        // Check that the inline CSS and JS are included
        $response->assertSee('lifeline-equipment-container');
        $response->assertSee('Lifeline equipment tab activated');
    }
}
<?php

namespace Tests\Browser;

use App\Models\ElectricalEquipment;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LifelineEquipmentBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'editor',
            'email' => 'test@example.com',
        ]);
        
        $this->facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'status' => 'approved',
        ]);
    }

    /** @test */
    public function user_can_navigate_to_lifeline_equipment_tab()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->waitFor('#lifeline-tab')
                ->assertSee('ライフライン設備')
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->assertVisible('#lifeline-equipment');
        });
    }

    /** @test */
    public function user_can_navigate_between_equipment_category_tabs()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#lifeline-equipment', function ($browser) {
                    // Test electrical tab (default active)
                    $browser->assertVisible('#electrical')
                        ->assertSee('基本情報')
                        ->assertSee('PAS')
                        ->assertSee('キュービクル')
                        ->assertSee('非常用発電機')
                        ->assertSee('備考');

                    // Test gas tab
                    $browser->click('[data-bs-target="#gas"]')
                        ->waitFor('#gas')
                        ->assertVisible('#gas')
                        ->assertSee('ガス設備');

                    // Test water tab
                    $browser->click('[data-bs-target="#water"]')
                        ->waitFor('#water')
                        ->assertVisible('#water')
                        ->assertSee('水道設備');

                    // Test elevator tab
                    $browser->click('[data-bs-target="#elevator"]')
                        ->waitFor('#elevator')
                        ->assertVisible('#elevator')
                        ->assertSee('エレベーター設備');

                    // Test HVAC lighting tab
                    $browser->click('[data-bs-target="#hvac-lighting"]')
                        ->waitFor('#hvac-lighting')
                        ->assertVisible('#hvac-lighting')
                        ->assertSee('空調・照明設備');

                    // Return to electrical tab
                    $browser->click('[data-bs-target="#electrical"]')
                        ->waitFor('#electrical')
                        ->assertVisible('#electrical');
                });
        });
    }

    /** @test */
    public function user_can_edit_electrical_basic_info_card()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#electrical', function ($browser) {
                    // Click edit button for basic info card
                    $browser->click('[data-card="basic_info"] .edit-btn')
                        ->waitFor('[data-card="basic_info"] .edit-form')
                        ->assertVisible('[data-card="basic_info"] .edit-form')
                        
                        // Fill in form fields
                        ->type('input[name="electrical_contractor"]', '東京電力')
                        ->type('input[name="safety_management_company"]', '保安管理会社')
                        ->type('input[name="maintenance_inspection_date"]', '2024-01-15')
                        
                        // Save the form
                        ->click('[data-card="basic_info"] .save-btn')
                        ->waitFor('[data-card="basic_info"] .display-content')
                        ->assertVisible('[data-card="basic_info"] .display-content')
                        
                        // Verify saved data is displayed
                        ->assertSee('東京電力')
                        ->assertSee('保安管理会社')
                        ->assertSee('2024-01-15');
                });
        });
    }

    /** @test */
    public function user_can_edit_pas_info_card()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#electrical', function ($browser) {
                    // Click edit button for PAS card
                    $browser->click('[data-card="pas_info"] .edit-btn')
                        ->waitFor('[data-card="pas_info"] .edit-form')
                        ->assertVisible('[data-card="pas_info"] .edit-form')
                        
                        // Select availability
                        ->select('select[name="availability"]', '有')
                        ->waitFor('textarea[name="details"]')
                        ->type('textarea[name="details"]', 'PAS設備詳細情報')
                        ->type('input[name="update_date"]', '2024-01-15')
                        
                        // Save the form
                        ->click('[data-card="pas_info"] .save-btn')
                        ->waitFor('[data-card="pas_info"] .display-content')
                        ->assertVisible('[data-card="pas_info"] .display-content')
                        
                        // Verify saved data is displayed
                        ->assertSee('有')
                        ->assertSee('PAS設備詳細情報')
                        ->assertSee('2024-01-15');
                });
        });
    }

    /** @test */
    public function user_can_manage_cubicle_equipment_list()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#electrical', function ($browser) {
                    // Click edit button for cubicle card
                    $browser->click('[data-card="cubicle_info"] .edit-btn')
                        ->waitFor('[data-card="cubicle_info"] .edit-form')
                        ->assertVisible('[data-card="cubicle_info"] .edit-form')
                        
                        // Select availability
                        ->select('select[name="availability"]', '有')
                        ->waitFor('.equipment-list-container')
                        
                        // Add first equipment
                        ->click('.add-equipment-btn')
                        ->waitFor('.equipment-item:first-child')
                        ->within('.equipment-item:first-child', function ($browser) {
                            $browser->type('input[name="equipment_number"]', '001')
                                ->type('input[name="manufacturer"]', '三菱電機')
                                ->type('input[name="model_year"]', '2024')
                                ->type('input[name="update_date"]', '2024-01-15');
                        })
                        
                        // Add second equipment
                        ->click('.add-equipment-btn')
                        ->waitFor('.equipment-item:nth-child(2)')
                        ->within('.equipment-item:nth-child(2)', function ($browser) {
                            $browser->type('input[name="equipment_number"]', '002')
                                ->type('input[name="manufacturer"]', '東芝')
                                ->type('input[name="model_year"]', '2023')
                                ->type('input[name="update_date"]', '2024-01-16');
                        })
                        
                        // Save the form
                        ->click('[data-card="cubicle_info"] .save-btn')
                        ->waitFor('[data-card="cubicle_info"] .display-content')
                        ->assertVisible('[data-card="cubicle_info"] .display-content')
                        
                        // Verify saved data is displayed
                        ->assertSee('有')
                        ->assertSee('001')
                        ->assertSee('三菱電機')
                        ->assertSee('002')
                        ->assertSee('東芝');
                });
        });
    }

    /** @test */
    public function user_can_remove_equipment_from_list()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#electrical', function ($browser) {
                    // Click edit button for generator card
                    $browser->click('[data-card="generator_info"] .edit-btn')
                        ->waitFor('[data-card="generator_info"] .edit-form')
                        ->select('select[name="availability"]', '有')
                        ->waitFor('.equipment-list-container')
                        
                        // Add two equipment items
                        ->click('.add-equipment-btn')
                        ->waitFor('.equipment-item:first-child')
                        ->within('.equipment-item:first-child', function ($browser) {
                            $browser->type('input[name="equipment_number"]', '001')
                                ->type('input[name="manufacturer"]', 'ヤンマー');
                        })
                        
                        ->click('.add-equipment-btn')
                        ->waitFor('.equipment-item:nth-child(2)')
                        ->within('.equipment-item:nth-child(2)', function ($browser) {
                            $browser->type('input[name="equipment_number"]', '002')
                                ->type('input[name="manufacturer"]', 'デンヨー');
                        })
                        
                        // Remove first equipment
                        ->click('.equipment-item:first-child .remove-equipment-btn')
                        ->waitUntilMissing('.equipment-item:nth-child(2)')
                        
                        // Verify only second equipment remains (now first)
                        ->within('.equipment-item:first-child', function ($browser) {
                            $browser->assertInputValue('input[name="equipment_number"]', '002')
                                ->assertInputValue('input[name="manufacturer"]', 'デンヨー');
                        });
                });
        });
    }

    /** @test */
    public function user_can_edit_notes_card()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#electrical', function ($browser) {
                    // Click edit button for notes card
                    $browser->click('[data-card="notes"] .edit-btn')
                        ->waitFor('[data-card="notes"] .edit-form')
                        ->assertVisible('[data-card="notes"] .edit-form')
                        
                        // Fill in notes
                        ->type('textarea[name="notes"]', '電気設備に関する特記事項です。')
                        
                        // Save the form
                        ->click('[data-card="notes"] .save-btn')
                        ->waitFor('[data-card="notes"] .display-content')
                        ->assertVisible('[data-card="notes"] .display-content')
                        
                        // Verify saved data is displayed
                        ->assertSee('電気設備に関する特記事項です。');
                });
        });
    }

    /** @test */
    public function user_can_cancel_editing()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#electrical', function ($browser) {
                    // Click edit button for basic info card
                    $browser->click('[data-card="basic_info"] .edit-btn')
                        ->waitFor('[data-card="basic_info"] .edit-form')
                        ->assertVisible('[data-card="basic_info"] .edit-form')
                        
                        // Fill in some data
                        ->type('input[name="electrical_contractor"]', '東京電力')
                        
                        // Cancel editing
                        ->click('[data-card="basic_info"] .cancel-btn')
                        ->waitFor('[data-card="basic_info"] .display-content')
                        ->assertVisible('[data-card="basic_info"] .display-content')
                        
                        // Verify form is hidden and data is not saved
                        ->assertMissing('[data-card="basic_info"] .edit-form')
                        ->assertDontSee('東京電力');
                });
        });
    }

    /** @test */
    public function form_validation_works_correctly()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#electrical', function ($browser) {
                    // Click edit button for basic info card
                    $browser->click('[data-card="basic_info"] .edit-btn')
                        ->waitFor('[data-card="basic_info"] .edit-form')
                        
                        // Enter invalid date (future date)
                        ->type('input[name="maintenance_inspection_date"]', '2025-12-31')
                        
                        // Try to save
                        ->click('[data-card="basic_info"] .save-btn')
                        ->waitFor('.error-message')
                        ->assertSee('点検実施日は今日以前の日付を入力してください')
                        
                        // Form should still be visible
                        ->assertVisible('[data-card="basic_info"] .edit-form');
                });
        });
    }

    /** @test */
    public function user_can_view_existing_data()
    {
        // Create existing data
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
            'status' => 'active',
        ]);

        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => [
                'electrical_contractor' => '関西電力',
                'safety_management_company' => '関西電気保安協会',
                'maintenance_inspection_date' => '2024-01-15',
            ],
            'pas_info' => [
                'availability' => '有',
                'details' => '既存PAS情報',
                'update_date' => '2024-01-15',
            ],
            'notes' => '既存の備考情報',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#electrical', function ($browser) {
                    // Verify existing data is displayed
                    $browser->assertSee('関西電力')
                        ->assertSee('関西電気保安協会')
                        ->assertSee('2024-01-15')
                        ->assertSee('有')
                        ->assertSee('既存PAS情報')
                        ->assertSee('既存の備考情報');
                });
        });
    }

    /** @test */
    public function viewer_role_cannot_edit_equipment_data()
    {
        $viewer = User::factory()->create([
            'role' => 'viewer',
            'email' => 'viewer@example.com',
        ]);

        $this->browse(function (Browser $browser) use ($viewer) {
            $browser->loginAs($viewer)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#electrical', function ($browser) {
                    // Edit buttons should not be visible for viewers
                    $browser->assertMissing('[data-card="basic_info"] .edit-btn')
                        ->assertMissing('[data-card="pas_info"] .edit-btn')
                        ->assertMissing('[data-card="cubicle_info"] .edit-btn')
                        ->assertMissing('[data-card="generator_info"] .edit-btn')
                        ->assertMissing('[data-card="notes"] .edit-btn');
                });
        });
    }

    /** @test */
    public function responsive_design_works_on_mobile()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->resize(375, 667) // iPhone SE size
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#lifeline-equipment', function ($browser) {
                    // Verify tabs are responsive
                    $browser->assertVisible('[data-bs-target="#electrical"]')
                        ->assertVisible('[data-bs-target="#gas"]')
                        ->assertVisible('[data-bs-target="#water"]');
                })
                ->within('#electrical', function ($browser) {
                    // Verify cards stack vertically on mobile
                    $browser->assertVisible('[data-card="basic_info"]')
                        ->assertVisible('[data-card="pas_info"]')
                        ->assertVisible('[data-card="cubicle_info"]');
                });
        });
    }

    /** @test */
    public function accessibility_features_work_correctly()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#lifeline-equipment', function ($browser) {
                    // Check ARIA attributes
                    $browser->assertAttribute('[data-bs-target="#electrical"]', 'role', 'tab')
                        ->assertAttribute('#electrical', 'role', 'tabpanel');
                })
                ->within('#electrical', function ($browser) {
                    // Check form labels and accessibility
                    $browser->click('[data-card="basic_info"] .edit-btn')
                        ->waitFor('[data-card="basic_info"] .edit-form')
                        ->assertPresent('label[for="electrical_contractor"]')
                        ->assertPresent('label[for="safety_management_company"]')
                        ->assertPresent('label[for="maintenance_inspection_date"]');
                });
        });
    }

    /** @test */
    public function user_can_navigate_all_equipment_categories_comprehensively()
    {
        // Create test data for all categories
        $categories = ['electrical', 'water', 'gas', 'elevator', 'hvac_lighting'];
        
        foreach ($categories as $category) {
            $lifelineEquipment = LifelineEquipment::factory()->create([
                'facility_id' => $this->facility->id,
                'category' => $category,
                'status' => 'active',
            ]);

            switch ($category) {
                case 'electrical':
                    ElectricalEquipment::factory()->create([
                        'lifeline_equipment_id' => $lifelineEquipment->id,
                        'basic_info' => ['electrical_contractor' => '東京電力'],
                    ]);
                    break;
                case 'gas':
                    \App\Models\GasEquipment::factory()->create([
                        'lifeline_equipment_id' => $lifelineEquipment->id,
                        'basic_info' => ['gas_supplier' => '東京ガス'],
                    ]);
                    break;
                case 'water':
                    \App\Models\WaterEquipment::factory()->create([
                        'lifeline_equipment_id' => $lifelineEquipment->id,
                        'basic_info' => ['water_supplier' => '東京都水道局'],
                    ]);
                    break;
                case 'elevator':
                    \App\Models\ElevatorEquipment::factory()->create([
                        'lifeline_equipment_id' => $lifelineEquipment->id,
                        'basic_info' => ['manufacturer' => '三菱電機'],
                    ]);
                    break;
                case 'hvac_lighting':
                    \App\Models\HvacLightingEquipment::factory()->create([
                        'lifeline_equipment_id' => $lifelineEquipment->id,
                        'basic_info' => ['hvac_system' => 'セントラル空調'],
                    ]);
                    break;
            }
        }

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment');

            // Test each category tab
            $tabTests = [
                ['electrical', '東京電力'],
                ['gas', '東京ガス'],
                ['water', '東京都水道局'],
                ['elevator', '三菱電機'],
                ['hvac-lighting', 'セントラル空調']
            ];

            foreach ($tabTests as [$tabId, $expectedContent]) {
                $browser->click("[data-bs-target=\"#{$tabId}\"]")
                    ->waitFor("#{$tabId}")
                    ->assertVisible("#{$tabId}")
                    ->assertSee($expectedContent)
                    ->pause(300); // Small pause between tab switches
            }
        });
    }

    /** @test */
    public function user_can_perform_bulk_equipment_operations()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#electrical', function ($browser) {
                    // Edit multiple cards in sequence
                    $cards = ['basic_info', 'pas_info', 'notes'];
                    
                    foreach ($cards as $cardType) {
                        $browser->click("[data-card=\"{$cardType}\"] .edit-btn")
                            ->waitFor("[data-card=\"{$cardType}\"] .edit-form");
                        
                        switch ($cardType) {
                            case 'basic_info':
                                $browser->type('input[name="electrical_contractor"]', '関西電力');
                                break;
                            case 'pas_info':
                                $browser->select('select[name="availability"]', '有')
                                    ->type('textarea[name="details"]', 'PAS詳細');
                                break;
                            case 'notes':
                                $browser->type('textarea[name="notes"]', '備考情報');
                                break;
                        }
                        
                        $browser->click("[data-card=\"{$cardType}\"] .save-btn")
                            ->waitFor("[data-card=\"{$cardType}\"] .display-content");
                    }
                    
                    // Verify all data was saved
                    $browser->assertSee('関西電力')
                        ->assertSee('有')
                        ->assertSee('PAS詳細')
                        ->assertSee('備考情報');
                });
        });
    }

    /** @test */
    public function user_can_handle_complex_equipment_lists()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#electrical', function ($browser) {
                    // Test cubicle equipment with multiple items
                    $browser->click('[data-card="cubicle_info"] .edit-btn')
                        ->waitFor('[data-card="cubicle_info"] .edit-form')
                        ->select('select[name="availability"]', '有')
                        ->waitFor('.equipment-list-container');
                    
                    // Add 3 equipment items
                    $equipmentData = [
                        ['001', '三菱電機', '2024'],
                        ['002', '東芝', '2023'],
                        ['003', 'パナソニック', '2022']
                    ];
                    
                    foreach ($equipmentData as $index => [$number, $manufacturer, $year]) {
                        $browser->click('.add-equipment-btn')
                            ->waitFor(".equipment-item:nth-child(" . ($index + 1) . ")")
                            ->within(".equipment-item:nth-child(" . ($index + 1) . ")", function ($browser) use ($number, $manufacturer, $year) {
                                $browser->type('input[name="equipment_number"]', $number)
                                    ->type('input[name="manufacturer"]', $manufacturer)
                                    ->type('input[name="model_year"]', $year);
                            });
                    }
                    
                    // Remove middle item
                    $browser->click('.equipment-item:nth-child(2) .remove-equipment-btn')
                        ->pause(500); // Wait for removal animation
                    
                    // Verify remaining items
                    $browser->within('.equipment-item:nth-child(1)', function ($browser) {
                        $browser->assertInputValue('input[name="equipment_number"]', '001')
                            ->assertInputValue('input[name="manufacturer"]', '三菱電機');
                    })
                    ->within('.equipment-item:nth-child(2)', function ($browser) {
                        $browser->assertInputValue('input[name="equipment_number"]', '003')
                            ->assertInputValue('input[name="manufacturer"]', 'パナソニック');
                    });
                    
                    // Save and verify
                    $browser->click('[data-card="cubicle_info"] .save-btn')
                        ->waitFor('[data-card="cubicle_info"] .display-content')
                        ->assertSee('001')
                        ->assertSee('三菱電機')
                        ->assertSee('003')
                        ->assertSee('パナソニック')
                        ->assertDontSee('東芝'); // Should not see removed item
                });
        });
    }

    /** @test */
    public function user_can_handle_form_validation_errors()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#electrical', function ($browser) {
                    // Test validation on basic info card
                    $browser->click('[data-card="basic_info"] .edit-btn')
                        ->waitFor('[data-card="basic_info"] .edit-form')
                        
                        // Enter invalid data
                        ->type('input[name="electrical_contractor"]', '') // Empty required field
                        ->type('input[name="maintenance_inspection_date"]', '2025-12-31') // Future date
                        
                        // Try to save
                        ->click('[data-card="basic_info"] .save-btn')
                        ->waitFor('.validation-errors')
                        ->assertSee('電気契約会社は必須です')
                        ->assertSee('点検実施日は今日以前の日付を入力してください')
                        
                        // Form should remain open
                        ->assertVisible('[data-card="basic_info"] .edit-form')
                        
                        // Fix validation errors
                        ->type('input[name="electrical_contractor"]', '東京電力')
                        ->type('input[name="maintenance_inspection_date"]', '2024-01-15')
                        
                        // Save should now work
                        ->click('[data-card="basic_info"] .save-btn')
                        ->waitFor('[data-card="basic_info"] .display-content')
                        ->assertSee('東京電力')
                        ->assertSee('2024-01-15');
                });
        });
    }

    /** @test */
    public function user_can_use_keyboard_navigation()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#lifeline-equipment', function ($browser) {
                    // Test tab navigation with keyboard
                    $browser->keys('[data-bs-target="#electrical"]', ['{tab}'])
                        ->assertFocused('[data-bs-target="#gas"]')
                        ->keys('[data-bs-target="#gas"]', ['{enter}'])
                        ->waitFor('#gas')
                        ->assertVisible('#gas')
                        
                        // Navigate back with keyboard
                        ->keys('[data-bs-target="#gas"]', ['{shift}', '{tab}'])
                        ->assertFocused('[data-bs-target="#electrical"]')
                        ->keys('[data-bs-target="#electrical"]', ['{enter}'])
                        ->waitFor('#electrical')
                        ->assertVisible('#electrical');
                });
        });
    }

    /** @test */
    public function user_can_handle_network_errors_gracefully()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#electrical', function ($browser) {
                    // Simulate network error by using invalid endpoint
                    $browser->script([
                        'window.originalFetch = window.fetch;',
                        'window.fetch = function() { return Promise.reject(new Error("Network error")); };'
                    ]);
                    
                    $browser->click('[data-card="basic_info"] .edit-btn')
                        ->waitFor('[data-card="basic_info"] .edit-form')
                        ->type('input[name="electrical_contractor"]', '東京電力')
                        ->click('[data-card="basic_info"] .save-btn')
                        ->waitFor('.error-message')
                        ->assertSee('保存中にエラーが発生しました')
                        
                        // Form should remain open for retry
                        ->assertVisible('[data-card="basic_info"] .edit-form');
                    
                    // Restore normal fetch
                    $browser->script(['window.fetch = window.originalFetch;']);
                });
        });
    }

    /** @test */
    public function user_can_work_with_japanese_text_input()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#electrical', function ($browser) {
                    $browser->click('[data-card="notes"] .edit-btn')
                        ->waitFor('[data-card="notes"] .edit-form')
                        
                        // Test Japanese text input
                        ->type('textarea[name="notes"]', 
                            '電気設備に関する特記事項：' . 
                            '年次点検は毎年3月に実施予定。' .
                            '緊急連絡先は施設管理者（田中）まで。' .
                            '設備更新予定：令和7年度'
                        )
                        
                        ->click('[data-card="notes"] .save-btn')
                        ->waitFor('[data-card="notes"] .display-content')
                        ->assertSee('電気設備に関する特記事項')
                        ->assertSee('年次点検は毎年3月に実施予定')
                        ->assertSee('緊急連絡先は施設管理者（田中）まで')
                        ->assertSee('設備更新予定：令和7年度');
                });
        });
    }

    /** @test */
    public function user_can_handle_concurrent_editing_scenarios()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#electrical', function ($browser) {
                    // Start editing basic info
                    $browser->click('[data-card="basic_info"] .edit-btn')
                        ->waitFor('[data-card="basic_info"] .edit-form')
                        ->type('input[name="electrical_contractor"]', '東京電力');
                    
                    // Start editing another card while first is still open
                    $browser->click('[data-card="pas_info"] .edit-btn')
                        ->waitFor('[data-card="pas_info"] .edit-form')
                        ->select('select[name="availability"]', '有');
                    
                    // Both forms should be open
                    $browser->assertVisible('[data-card="basic_info"] .edit-form')
                        ->assertVisible('[data-card="pas_info"] .edit-form');
                    
                    // Save both forms
                    $browser->click('[data-card="basic_info"] .save-btn')
                        ->waitFor('[data-card="basic_info"] .display-content')
                        ->click('[data-card="pas_info"] .save-btn')
                        ->waitFor('[data-card="pas_info"] .display-content')
                        
                        // Verify both saved successfully
                        ->assertSee('東京電力')
                        ->assertSee('有');
                });
        });
    }

    /** @test */
    public function user_can_navigate_with_screen_reader_support()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment')
                ->within('#lifeline-equipment', function ($browser) {
                    // Check ARIA labels and descriptions
                    $browser->assertAttribute('[data-bs-target="#electrical"]', 'aria-label', '電気設備タブ')
                        ->assertAttribute('#electrical', 'aria-labelledby', 'electrical-tab')
                        ->assertAttribute('#electrical', 'aria-describedby', 'electrical-description');
                })
                ->within('#electrical', function ($browser) {
                    // Check form accessibility
                    $browser->click('[data-card="basic_info"] .edit-btn')
                        ->waitFor('[data-card="basic_info"] .edit-form')
                        ->assertAttribute('input[name="electrical_contractor"]', 'aria-required', 'true')
                        ->assertAttribute('input[name="electrical_contractor"]', 'aria-describedby', 'electrical-contractor-help')
                        ->assertPresent('#electrical-contractor-help');
                });
        });
    }

    /** @test */
    public function performance_is_acceptable_with_large_datasets()
    {
        // Create equipment with large datasets
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        // Create equipment with many items
        $largeEquipmentList = [];
        for ($i = 1; $i <= 20; $i++) {
            $largeEquipmentList[] = [
                'equipment_number' => sprintf('%03d', $i),
                'manufacturer' => "メーカー{$i}",
                'model_year' => (string)(2020 + ($i % 5)),
                'update_date' => '2024-01-15',
            ];
        }

        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'cubicle_info' => [
                'availability' => '有',
                'equipment_list' => $largeEquipmentList,
            ],
        ]);

        $this->browse(function (Browser $browser) {
            $startTime = microtime(true);
            
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#lifeline-tab')
                ->waitFor('#lifeline-equipment', 10) // 10 second timeout
                ->within('#electrical', function ($browser) {
                    // Verify large dataset loads
                    $browser->assertSee('メーカー1')
                        ->assertSee('メーカー20')
                        ->assertSee('001')
                        ->assertSee('020');
                });
            
            $loadTime = microtime(true) - $startTime;
            
            // Assert reasonable performance (less than 5 seconds)
            $this->assertLessThan(5.0, $loadTime, 'Large dataset should load within 5 seconds');
        });
    }
}
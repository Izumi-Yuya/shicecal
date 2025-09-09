<?php

namespace Tests\Browser;

use App\Models\Facility;
use App\Models\LandInfo;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LandInfoBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
    }

    public function test_ownership_type_controls_form_sections()
    {
        $user = User::factory()->create(['role' => 'editor']);
        $facility = Facility::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $facility) {
            $browser->loginAs($user)
                ->visit("/facilities/{$facility->id}")
                ->click('#land-tab')
                ->waitFor('#land-info-form')
                ->select('ownership_type', 'owned')
                ->waitFor('#ownedSection')
                ->assertVisible('#ownedSection')
                ->assertNotVisible('#leasedSection')
                ->assertNotVisible('#managementSection')
                ->assertNotVisible('#ownerSection')
                ->select('ownership_type', 'leased')
                ->waitFor('#leasedSection')
                ->assertNotVisible('#ownedSection')
                ->assertVisible('#leasedSection')
                ->assertVisible('#managementSection')
                ->assertVisible('#ownerSection')
                ->select('ownership_type', 'owned_rental')
                ->waitFor('#leasedSection')
                ->assertNotVisible('#ownedSection')
                ->assertVisible('#leasedSection')
                ->assertNotVisible('#managementSection')
                ->assertNotVisible('#ownerSection');
        });
    }

    public function test_automatic_unit_price_calculation()
    {
        $user = User::factory()->create(['role' => 'editor']);
        $facility = Facility::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $facility) {
            $browser->loginAs($user)
                ->visit("/facilities/{$facility->id}")
                ->click('#land-tab')
                ->waitFor('#land-info-form')
                ->select('ownership_type', 'owned')
                ->waitFor('#ownedSection')
                ->type('purchase_price', '10000000')
                ->type('site_area_tsubo', '100')
                ->pause(500) // Wait for calculation
                ->assertInputValue('unit_price_display', '100,000');
        });
    }

    public function test_automatic_contract_period_calculation()
    {
        $user = User::factory()->create(['role' => 'editor']);
        $facility = Facility::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $facility) {
            $browser->loginAs($user)
                ->visit("/facilities/{$facility->id}")
                ->click('#land-tab')
                ->waitFor('#land-info-form')
                ->select('ownership_type', 'leased')
                ->waitFor('#leasedSection')
                ->type('contract_start_date', '2020-01-01')
                ->type('contract_end_date', '2025-06-01')
                ->pause(500) // Wait for calculation
                ->assertInputValue('contract_period_display', '5年5ヶ月');
        });
    }

    public function test_currency_formatting()
    {
        $user = User::factory()->create(['role' => 'editor']);
        $facility = Facility::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $facility) {
            $browser->loginAs($user)
                ->visit("/facilities/{$facility->id}")
                ->click('#land-tab')
                ->waitFor('#land-info-form')
                ->select('ownership_type', 'owned')
                ->waitFor('#ownedSection')
                ->type('purchase_price', '1000000')
                ->click('body') // Trigger blur event
                ->pause(200)
                ->assertInputValue('purchase_price', '1,000,000');
        });
    }

    public function test_form_validation_display()
    {
        $user = User::factory()->create(['role' => 'editor']);
        $facility = Facility::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $facility) {
            $browser->loginAs($user)
                ->visit("/facilities/{$facility->id}")
                ->click('#land-tab')
                ->waitFor('#land-info-form')
                ->press('保存')
                ->waitFor('.alert-danger')
                ->assertSee('所有形態を選択してください');
        });
    }

    public function test_file_upload_interface()
    {
        $user = User::factory()->create(['role' => 'editor']);
        $facility = Facility::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $facility) {
            $browser->loginAs($user)
                ->visit("/facilities/{$facility->id}")
                ->click('#land-tab')
                ->waitFor('#land-info-form')
                ->select('ownership_type', 'leased')
                ->waitFor('#leasedSection')
                ->assertVisible('input[name="lease_contracts[]"]')
                ->assertVisible('input[name="property_register"]')
                ->assertAttribute('input[name="lease_contracts[]"]', 'accept', '.pdf')
                ->assertAttribute('input[name="property_register"]', 'accept', '.pdf');
        });
    }

    public function test_full_width_number_conversion()
    {
        $user = User::factory()->create(['role' => 'editor']);
        $facility = Facility::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $facility) {
            $browser->loginAs($user)
                ->visit("/facilities/{$facility->id}")
                ->click('#land-tab')
                ->waitFor('#land-info-form')
                ->type('parking_spaces', '１２３')
                ->click('body') // Trigger blur event
                ->pause(200)
                ->assertInputValue('parking_spaces', '123');
        });
    }

    public function test_phone_number_formatting()
    {
        $user = User::factory()->create(['role' => 'editor']);
        $facility = Facility::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $facility) {
            $browser->loginAs($user)
                ->visit("/facilities/{$facility->id}")
                ->click('#land-tab')
                ->waitFor('#land-info-form')
                ->select('ownership_type', 'leased')
                ->waitFor('#managementSection')
                ->type('management_company_phone', '0312345678')
                ->click('body') // Trigger blur event
                ->pause(200)
                ->assertInputValue('management_company_phone', '03-1234-5678');
        });
    }

    public function test_postal_code_formatting()
    {
        $user = User::factory()->create(['role' => 'editor']);
        $facility = Facility::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $facility) {
            $browser->loginAs($user)
                ->visit("/facilities/{$facility->id}")
                ->click('#land-tab')
                ->waitFor('#land-info-form')
                ->select('ownership_type', 'leased')
                ->waitFor('#managementSection')
                ->type('management_company_postal_code', '1234567')
                ->click('body') // Trigger blur event
                ->pause(200)
                ->assertInputValue('management_company_postal_code', '123-4567');
        });
    }

    public function test_save_and_display_land_info()
    {
        $user = User::factory()->create(['role' => 'editor']);
        $facility = Facility::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $facility) {
            $browser->loginAs($user)
                ->visit("/facilities/{$facility->id}")
                ->click('#land-tab')
                ->waitFor('#land-info-form')
                ->select('ownership_type', 'owned')
                ->waitFor('#ownedSection')
                ->type('parking_spaces', '50')
                ->type('site_area_sqm', '290.50')
                ->type('site_area_tsubo', '87.85')
                ->type('purchase_price', '15000000')
                ->type('notes', 'テスト用の備考です。')
                ->press('保存')
                ->waitFor('.alert-success')
                ->assertSee('土地情報を保存しました')
                ->refresh()
                ->click('#land-tab')
                ->waitFor('#land-info-display')
                ->assertSee('自社')
                ->assertSee('50台')
                ->assertSee('290.50㎡')
                ->assertSee('87.85坪')
                ->assertSee('15,000,000円')
                ->assertSee('テスト用の備考です。');
        });
    }

    public function test_edit_existing_land_info()
    {
        $user = User::factory()->create(['role' => 'editor']);
        $facility = Facility::factory()->create();
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'ownership_type' => 'owned',
            'purchase_price' => 10000000,
            'site_area_tsubo' => 100.0,
        ]);

        $this->browse(function (Browser $browser) use ($user, $facility) {
            $browser->loginAs($user)
                ->visit("/facilities/{$facility->id}")
                ->click('#land-tab')
                ->waitFor('#land-info-display')
                ->click('.edit-land-info-btn')
                ->waitFor('#land-info-form')
                ->assertSelected('ownership_type', 'owned')
                ->assertInputValue('purchase_price', '10,000,000')
                ->clear('purchase_price')
                ->type('purchase_price', '12000000')
                ->press('保存')
                ->waitFor('.alert-success')
                ->assertSee('土地情報を更新しました');
        });
    }

    public function test_approval_workflow_interface()
    {
        // Enable approval workflow
        config(['facility.approval_enabled' => true]);

        $editor = User::factory()->create(['role' => 'editor']);
        $approver = User::factory()->create(['role' => 'approver']);
        $facility = Facility::factory()->create();

        // Editor creates land info
        $this->browse(function (Browser $browser) use ($editor, $facility) {
            $browser->loginAs($editor)
                ->visit("/facilities/{$facility->id}")
                ->click('#land-tab')
                ->waitFor('#land-info-form')
                ->select('ownership_type', 'owned')
                ->waitFor('#ownedSection')
                ->type('purchase_price', '10000000')
                ->press('保存')
                ->waitFor('.alert-info')
                ->assertSee('承認待ち');
        });

        // Approver reviews and approves
        $this->browse(function (Browser $browser) use ($approver, $facility) {
            $browser->loginAs($approver)
                ->visit("/facilities/{$facility->id}")
                ->click('#land-tab')
                ->waitFor('.approval-section')
                ->assertSee('承認待ち')
                ->click('.approve-btn')
                ->waitFor('.alert-success')
                ->assertSee('承認しました');
        });
    }

    public function test_responsive_design()
    {
        $user = User::factory()->create(['role' => 'editor']);
        $facility = Facility::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $facility) {
            $browser->loginAs($user)
                ->resize(375, 667) // Mobile size
                ->visit("/facilities/{$facility->id}")
                ->click('#land-tab')
                ->waitFor('#land-info-form')
                ->assertVisible('#land-info-form')
                ->resize(768, 1024) // Tablet size
                ->assertVisible('#land-info-form')
                ->resize(1920, 1080) // Desktop size
                ->assertVisible('#land-info-form');
        });
    }

    public function test_tab_navigation()
    {
        $user = User::factory()->create(['role' => 'editor']);
        $facility = Facility::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $facility) {
            $browser->loginAs($user)
                ->visit("/facilities/{$facility->id}")
                ->assertVisible('#basic-info')
                ->assertNotVisible('#land-info')
                ->click('#land-tab')
                ->waitFor('#land-info')
                ->assertNotVisible('#basic-info')
                ->assertVisible('#land-info')
                ->click('#basic-tab')
                ->waitFor('#basic-info')
                ->assertVisible('#basic-info')
                ->assertNotVisible('#land-info');
        });
    }

    public function test_draft_save_functionality()
    {
        $user = User::factory()->create(['role' => 'editor']);
        $facility = Facility::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $facility) {
            $browser->loginAs($user)
                ->visit("/facilities/{$facility->id}")
                ->click('#land-tab')
                ->waitFor('#land-info-form')
                ->select('ownership_type', 'owned')
                ->type('purchase_price', '5000000')
                ->press('下書き保存')
                ->waitFor('.alert-info')
                ->assertSee('下書きを保存しました')
                ->refresh()
                ->click('#land-tab')
                ->waitFor('#land-info-form')
                ->assertSelected('ownership_type', 'owned')
                ->assertInputValue('purchase_price', '5,000,000');
        });
    }
}

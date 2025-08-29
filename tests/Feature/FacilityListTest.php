<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacilityListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create([
            'role' => 'editor'
        ]);
    }

    /** @test */
    public function it_displays_facility_list_page()
    {
        $response = $this->actingAs($this->user)
                         ->get(route('facilities.index'));

        $response->assertStatus(200);
        $response->assertViewIs('facilities.index');
        $response->assertSee('施設一覧');
        $response->assertSee('新規登録');
        $response->assertSee('検索・絞り込み');
    }

    /** @test */
    public function it_displays_facilities_in_list()
    {
        // Create test facilities
        $facility1 = Facility::factory()->approved()->create([
            'facility_name' => 'テスト施設1',
            'office_code' => '1234-5678',
            'company_name' => 'テスト会社1',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $facility2 = Facility::factory()->pendingApproval()->create([
            'facility_name' => 'テスト施設2',
            'office_code' => '9876-5432',
            'company_name' => 'テスト会社2',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
                         ->get(route('facilities.index'));

        $response->assertStatus(200);
        $response->assertSee('テスト施設1');
        $response->assertSee('テスト施設2');
        $response->assertSee('1234-5678');
        $response->assertSee('9876-5432');
        $response->assertSee('テスト会社1');
        $response->assertSee('テスト会社2');
    }

    /** @test */
    public function it_shows_correct_status_badges()
    {
        $approvedFacility = Facility::factory()->approved()->create([
            'facility_name' => '承認済み施設',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $pendingFacility = Facility::factory()->pendingApproval()->create([
            'facility_name' => '承認待ち施設',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $draftFacility = Facility::factory()->draft()->create([
            'facility_name' => '下書き施設',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
                         ->get(route('facilities.index'));

        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee('承認待ち');
        $response->assertSee('下書き');
    }

    /** @test */
    public function it_can_search_by_facility_name()
    {
        Facility::factory()->create([
            'facility_name' => 'デイサービス太郎',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        Facility::factory()->create([
            'facility_name' => 'グループホーム花子',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
                         ->get(route('facilities.index', ['search_name' => 'デイサービス']));

        $response->assertStatus(200);
        $response->assertSee('デイサービス太郎');
        $response->assertDontSee('グループホーム花子');
    }

    /** @test */
    public function it_can_search_by_office_code()
    {
        Facility::factory()->create([
            'office_code' => '1111-2222',
            'facility_name' => '施設A',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        Facility::factory()->create([
            'office_code' => '3333-4444',
            'facility_name' => '施設B',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
                         ->get(route('facilities.index', ['search_office_code' => '1111']));

        $response->assertStatus(200);
        $response->assertSee('施設A');
        $response->assertDontSee('施設B');
    }

    /** @test */
    public function it_can_search_by_address()
    {
        Facility::factory()->create([
            'address' => '東京都渋谷区',
            'facility_name' => '東京施設',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        Facility::factory()->create([
            'address' => '大阪府大阪市',
            'facility_name' => '大阪施設',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
                         ->get(route('facilities.index', ['search_address' => '東京']));

        $response->assertStatus(200);
        $response->assertSee('東京施設');
        $response->assertDontSee('大阪施設');
    }

    /** @test */
    public function it_shows_empty_state_when_no_facilities()
    {
        $response = $this->actingAs($this->user)
                         ->get(route('facilities.index'));

        $response->assertStatus(200);
        $response->assertSee('施設が見つかりませんでした');
        $response->assertSee('新しい施設を登録してください');
    }

    /** @test */
    public function it_paginates_facilities()
    {
        // Create 25 facilities (more than the 20 per page limit)
        Facility::factory()->count(25)->create([
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
                         ->get(route('facilities.index'));

        $response->assertStatus(200);
        // Should show pagination links
        $response->assertSee('次へ');
    }

    /** @test */
    public function it_shows_action_buttons_for_each_facility()
    {
        $facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
                         ->get(route('facilities.index'));

        $response->assertStatus(200);
        // Check for view and edit buttons
        $response->assertSee(route('facilities.show', $facility));
        $response->assertSee(route('facilities.edit', $facility));
    }
}
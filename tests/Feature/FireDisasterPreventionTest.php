<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\SecurityDisasterEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FireDisasterPreventionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'admin',
            'facility_ids' => null,
        ]);
        
        $this->facility = Facility::factory()->create();
    }

    /** @test */
    public function user_can_view_fire_disaster_prevention_tab()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.show', $this->facility) . '#fire-disaster');

        $response->assertOk();
        $response->assertSee('消防・防災');
        $response->assertSee('基本情報');
        $response->assertSee('消防');
        $response->assertSee('防災');
    }

    /** @test */
    public function user_can_access_fire_disaster_prevention_edit_page()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.security-disaster.edit', $this->facility) . '#fire-disaster-edit');

        $response->assertOk();
        $response->assertSee('消防・防災');
        $response->assertSee('ハザードマップ（PDF）');
        $response->assertSee('避難経路（PDF）');
        $response->assertSee('防火管理者');
        $response->assertSee('実地訓練実施日');
        $response->assertSee('騎乗訓練実施日');
        $response->assertSee('備蓄品（PDF）');
    }

    /** @test */
    public function user_can_update_fire_disaster_prevention_basic_info()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $hazardMapFile = UploadedFile::fake()->create('hazard_map.pdf', 1024, 'application/pdf');
        $evacuationRouteFile = UploadedFile::fake()->create('evacuation_route.pdf', 1024, 'application/pdf');

        $response = $this->put(route('facilities.security-disaster.update', $this->facility), [
            'active_sub_tab' => 'fire-disaster',
            'fire_disaster_prevention' => [
                'basic_info' => [
                    'hazard_map_pdf' => $hazardMapFile,
                    'evacuation_route_pdf' => $evacuationRouteFile,
                ],
                'notes' => 'テスト備考',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // データベースに保存されているか確認
        $lifelineEquipment = LifelineEquipment::where('facility_id', $this->facility->id)
            ->where('category', 'security_disaster')
            ->first();

        $this->assertNotNull($lifelineEquipment);
        
        $securityDisasterEquipment = $lifelineEquipment->securityDisasterEquipment;
        $this->assertNotNull($securityDisasterEquipment);
        
        $fireDisasterData = $securityDisasterEquipment->fire_disaster_prevention;
        $this->assertNotNull($fireDisasterData);
        $this->assertEquals('テスト備考', $fireDisasterData['notes']);
        $this->assertNotNull($fireDisasterData['basic_info']['hazard_map_pdf_name']);
        $this->assertNotNull($fireDisasterData['basic_info']['evacuation_route_pdf_name']);

        // ファイルが保存されているか確認
        $files = Storage::disk('public')->files('fire-disaster/hazard-maps');
        $this->assertNotEmpty($files);
        
        $files = Storage::disk('public')->files('fire-disaster/evacuation-routes');
        $this->assertNotEmpty($files);
    }
}
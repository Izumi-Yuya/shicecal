<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElevatorEquipmentImplementationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->facility = Facility::factory()->create();
    }

    /** @test */
    public function it_can_display_elevator_equipment_edit_page()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.lifeline-equipment.edit', [$this->facility, 'elevator']));

        $response->assertStatus(200);
        $response->assertViewIs('facilities.lifeline-equipment.elevator-edit');
        $response->assertViewHas('facility', $this->facility);
        $response->assertViewHas('category', 'elevator');
    }

    /** @test */
    public function it_can_update_elevator_equipment_with_valid_data()
    {
        $this->actingAs($this->user);

        $data = [
            'basic_info' => [
                'availability' => '有',
                'elevators' => [
                    [
                        'manufacturer' => '三菱電機',
                        'type' => 'ロープ式',
                        'model_year' => 2020,
                        'update_date' => '2024-01-15',
                    ],
                    [
                        'manufacturer' => '東芝エレベータ',
                        'type' => '油圧式',
                        'model_year' => 2018,
                        'update_date' => '2023-12-10',
                    ],
                ],
            ],
            'notes' => 'エレベータのテスト備考',
        ];

        $response = $this->put(route('facilities.lifeline-equipment.update', [$this->facility, 'elevator']), $data);

        $response->assertRedirect(route('facilities.show', $this->facility).'#elevator');
        $response->assertSessionHas('success', 'ライフライン設備情報を更新しました。');

        // データベースに保存されているか確認
        $elevatorEquipment = $this->facility->fresh()->getElevatorEquipment();
        $this->assertNotNull($elevatorEquipment);
        $this->assertEquals('有', $elevatorEquipment->basic_info['availability']);
        $this->assertCount(2, $elevatorEquipment->basic_info['elevators']);
        $this->assertEquals('三菱電機', $elevatorEquipment->basic_info['elevators'][0]['manufacturer']);
        $this->assertEquals('ロープ式', $elevatorEquipment->basic_info['elevators'][0]['type']);
        $this->assertEquals('エレベータのテスト備考', $elevatorEquipment->notes);
    }

    /** @test */
    public function it_can_update_elevator_equipment_with_custom_type()
    {
        $this->actingAs($this->user);

        $data = [
            'basic_info' => [
                'availability' => '有',
                'elevators' => [
                    [
                        'manufacturer' => 'フジテック',
                        'type' => 'リニアモーター式',
                        'model_year' => 2022,
                        'update_date' => '2024-02-20',
                    ],
                ],
            ],
            'notes' => 'カスタム種類のテスト',
        ];

        $response = $this->put(route('facilities.lifeline-equipment.update', [$this->facility, 'elevator']), $data);

        $response->assertRedirect(route('facilities.show', $this->facility).'#elevator');

        $elevatorEquipment = $this->facility->fresh()->getElevatorEquipment();
        $this->assertEquals('リニアモーター式', $elevatorEquipment->basic_info['elevators'][0]['type']);
    }

    /** @test */
    public function it_can_update_elevator_equipment_with_no_elevators()
    {
        $this->actingAs($this->user);

        $data = [
            'basic_info' => [
                'availability' => '無',
                'elevators' => [],
            ],
            'notes' => 'エレベータなしのテスト',
        ];

        $response = $this->put(route('facilities.lifeline-equipment.update', [$this->facility, 'elevator']), $data);

        $response->assertRedirect(route('facilities.show', $this->facility).'#elevator');

        $elevatorEquipment = $this->facility->fresh()->getElevatorEquipment();
        $this->assertEquals('無', $elevatorEquipment->basic_info['availability']);
        $this->assertEmpty($elevatorEquipment->basic_info['elevators']);
    }

    /** @test */
    public function it_validates_elevator_equipment_data()
    {
        $this->actingAs($this->user);

        $data = [
            'basic_info' => [
                'availability' => '有',
                'elevators' => [
                    [
                        'manufacturer' => str_repeat('a', 256), // Too long
                        'type' => 'ロープ式',
                        'model_year' => 1800, // Too old
                        'update_date' => '2025-12-31', // Future date
                    ],
                ],
            ],
            'notes' => str_repeat('a', 2001), // Too long
        ];

        $response = $this->put(route('facilities.lifeline-equipment.update', [$this->facility, 'elevator']), $data);

        $response->assertSessionHasErrors([
            'basic_info.elevators.0.manufacturer',
            'basic_info.elevators.0.model_year',
            'basic_info.elevators.0.update_date',
            'notes',
        ]);
    }
}

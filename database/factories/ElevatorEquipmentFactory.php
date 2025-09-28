<?php

namespace Database\Factories;

use App\Models\ElevatorEquipment;
use App\Models\LifelineEquipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ElevatorEquipment>
 */
class ElevatorEquipmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ElevatorEquipment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $hasElevator = $this->faker->boolean(80); // 80% chance of having elevator

        return [
            'lifeline_equipment_id' => LifelineEquipment::factory(),
            'basic_info' => [
                'availability' => $hasElevator ? '有' : '無',
                'elevators' => $hasElevator ? [
                    [
                        'manufacturer' => $this->faker->randomElement(['三菱電機', '東芝エレベータ', '日立ビルシステム', 'オーチス', 'フジテック']),
                        'type' => $this->faker->randomElement(['ロープ式', '油圧式']),
                        'model_year' => $this->faker->numberBetween(2000, 2024),
                        'update_date' => $this->faker->date(),
                    ],
                ] : [],
            ],
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Create an elevator equipment with no elevators.
     */
    public function withoutElevator()
    {
        return $this->state(function (array $attributes) {
            return [
                'basic_info' => [
                    'availability' => '無',
                    'elevators' => [],
                ],
                'notes' => null,
            ];
        });
    }

    /**
     * Create an elevator equipment with multiple elevators.
     */
    public function withMultipleElevators($count = 2)
    {
        return $this->state(function (array $attributes) use ($count) {
            $elevators = [];
            for ($i = 0; $i < $count; $i++) {
                $elevators[] = [
                    'manufacturer' => $this->faker->randomElement(['三菱電機', '東芝エレベータ', '日立ビルシステム', 'オーチス', 'フジテック']),
                    'type' => $this->faker->randomElement(['ロープ式', '油圧式']),
                    'model_year' => $this->faker->numberBetween(2000, 2024),
                    'update_date' => $this->faker->date(),
                ];
            }

            return [
                'basic_info' => [
                    'availability' => '有',
                    'elevators' => $elevators,
                ],
            ];
        });
    }

    /**
     * Create an elevator equipment with custom type.
     */
    public function withCustomType($customType = 'カスタム式')
    {
        return $this->state(function (array $attributes) use ($customType) {
            return [
                'basic_info' => [
                    'availability' => '有',
                    'elevators' => [
                        [
                            'manufacturer' => '三菱電機',
                            'type' => $customType,
                            'model_year' => 2020,
                            'update_date' => '2024-01-15',
                        ],
                    ],
                ],
            ];
        });
    }
}

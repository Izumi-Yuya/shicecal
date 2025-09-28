<?php

namespace Database\Factories;

use App\Models\ElectricalEquipment;
use App\Models\LifelineEquipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ElectricalEquipment>
 */
class ElectricalEquipmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ElectricalEquipment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lifeline_equipment_id' => LifelineEquipment::factory()->electrical(),
            'basic_info' => [
                'electrical_contractor' => $this->faker->company(),
                'safety_management_company' => $this->faker->company().'株式会社',
                'maintenance_inspection_date' => $this->faker->date(),
                'inspection_report_pdf' => $this->faker->optional()->word().'.pdf',
            ],
            'pas_info' => [
                'availability' => $this->faker->randomElement(['有', '無']),
                'update_date' => $this->faker->date(),
            ],
            'cubicle_info' => [
                'availability' => $this->faker->randomElement(['有', '無']),
                'equipment_list' => $this->faker->optional()->randomElements([
                    [
                        'manufacturer' => '三菱電機',
                        'model_year' => $this->faker->year(),
                        'update_date' => $this->faker->date(),
                    ],
                    [
                        'manufacturer' => '東芝',
                        'model_year' => $this->faker->year(),
                        'update_date' => $this->faker->date(),
                    ],
                ], $this->faker->numberBetween(0, 2)),
            ],
            'generator_info' => [
                'availability' => $this->faker->randomElement(['有', '無']),
                'availability_details' => $this->faker->optional()->sentence(),
                'equipment_list' => $this->faker->optional()->randomElements([
                    [
                        'manufacturer' => 'ヤンマー',
                        'model_year' => $this->faker->year(),
                        'update_date' => $this->faker->date(),
                    ],
                    [
                        'manufacturer' => 'デンヨー',
                        'model_year' => $this->faker->year(),
                        'update_date' => $this->faker->date(),
                    ],
                ], $this->faker->numberBetween(0, 2)),
            ],
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    /**
     * Indicate that the electrical equipment has PAS.
     */
    public function withPas(): static
    {
        return $this->state(fn (array $attributes) => [
            'pas_info' => array_merge($attributes['pas_info'] ?? [], [
                'availability' => '有',
            ]),
        ]);
    }

    /**
     * Indicate that the electrical equipment has cubicle.
     */
    public function withCubicle(): static
    {
        return $this->state(fn (array $attributes) => [
            'cubicle_info' => array_merge($attributes['cubicle_info'] ?? [], [
                'availability' => '有',
                'equipment_list' => [
                    [
                        'manufacturer' => '三菱電機',
                        'model_year' => '2020',
                        'update_date' => '2024-03-15',
                    ],
                ],
            ]),
        ]);
    }

    /**
     * Indicate that the electrical equipment has generator.
     */
    public function withGenerator(): static
    {
        return $this->state(fn (array $attributes) => [
            'generator_info' => array_merge($attributes['generator_info'] ?? [], [
                'availability' => '有',
                'equipment_list' => [
                    [
                        'manufacturer' => 'ヤンマー',
                        'model_year' => '2021',
                        'update_date' => '2024-03-15',
                    ],
                ],
            ]),
        ]);
    }
}

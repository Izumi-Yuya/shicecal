<?php

namespace Database\Factories;

use App\Models\SecurityDisasterEquipment;
use App\Models\LifelineEquipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SecurityDisasterEquipmentFactory extends Factory
{
    protected $model = SecurityDisasterEquipment::class;

    public function definition(): array
    {
        return [
            'lifeline_equipment_id' => LifelineEquipment::factory(),
            'basic_info' => [
                'security_company' => $this->faker->company() . '警備',
                'disaster_prevention_company' => $this->faker->company() . '防災',
                'last_inspection_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                'next_inspection_date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            ],
            'security_systems' => [
                'surveillance_cameras' => [
                    'installed' => $this->faker->boolean(80),
                    'count' => $this->faker->numberBetween(4, 20),
                    'manufacturer' => $this->faker->randomElement(['パナソニック', 'キヤノン', 'ソニー']),
                ],
                'access_control' => [
                    'installed' => $this->faker->boolean(70),
                    'type' => $this->faker->randomElement(['カードキー', '暗証番号', '生体認証']),
                ],
                'alarm_system' => [
                    'installed' => $this->faker->boolean(90),
                    'type' => $this->faker->randomElement(['機械警備', '人的警備']),
                ],
            ],
            'disaster_prevention' => [
                'fire_detection' => [
                    'smoke_detectors' => $this->faker->numberBetween(5, 30),
                    'heat_detectors' => $this->faker->numberBetween(2, 15),
                ],
                'fire_suppression' => [
                    'sprinkler_system' => $this->faker->boolean(70),
                    'fire_extinguishers' => $this->faker->numberBetween(5, 20),
                ],
            ],
            'emergency_equipment' => [
                'backup_power' => [
                    'generator' => $this->faker->boolean(60),
                    'ups_systems' => $this->faker->boolean(80),
                ],
                'first_aid' => [
                    'first_aid_kits' => $this->faker->numberBetween(3, 10),
                    'aed_units' => $this->faker->numberBetween(1, 3),
                ],
            ],
            'fire_disaster_prevention' => [
                'basic_info' => [
                    'hazard_map_pdf_name' => $this->faker->optional()->word() . '.pdf',
                    'hazard_map_pdf_path' => $this->faker->optional()->filePath(),
                    'evacuation_route_pdf_name' => $this->faker->optional()->word() . '.pdf',
                    'evacuation_route_pdf_path' => $this->faker->optional()->filePath(),
                ],
                'fire_prevention' => [
                    'fire_manager' => $this->faker->name(),
                    'training_date' => $this->faker->optional()->date(),
                    'training_report_pdf_name' => $this->faker->optional()->word() . '.pdf',
                    'training_report_pdf_path' => $this->faker->optional()->filePath(),
                    'inspection_company' => $this->faker->company(),
                    'inspection_date' => $this->faker->optional()->date(),
                    'inspection_report_pdf_name' => $this->faker->optional()->word() . '.pdf',
                    'inspection_report_pdf_path' => $this->faker->optional()->filePath(),
                ],
                'disaster_prevention' => [
                    'practical_training_date' => $this->faker->optional()->date(),
                    'practical_training_report_pdf_name' => $this->faker->optional()->word() . '.pdf',
                    'practical_training_report_pdf_path' => $this->faker->optional()->filePath(),
                    'riding_training_date' => $this->faker->optional()->date(),
                    'riding_training_report_pdf_name' => $this->faker->optional()->word() . '.pdf',
                    'riding_training_report_pdf_path' => $this->faker->optional()->filePath(),
                    'emergency_supplies_pdf_name' => $this->faker->optional()->word() . '.pdf',
                    'emergency_supplies_pdf_path' => $this->faker->optional()->filePath(),
                ],
                'notes' => $this->faker->optional()->paragraph(),
            ],
            'notes' => $this->faker->optional(0.7)->paragraph(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
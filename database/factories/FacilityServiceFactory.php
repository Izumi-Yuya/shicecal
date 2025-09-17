<?php

namespace Database\Factories;

use App\Models\Facility;
use App\Models\FacilityService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FacilityService>
 */
class FacilityServiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FacilityService::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $serviceTypes = [
            '介護付有料老人ホーム',
            'デイサービス',
            'ショートステイ',
            '訪問介護',
            '居宅介護支援',
            'グループホーム',
            '小規模多機能型居宅介護',
            '看護小規模多機能型居宅介護',
            '定期巡回・随時対応型訪問介護看護',
            '夜間対応型訪問介護',
        ];

        $startDate = $this->faker->dateTimeBetween('-2 years', '+1 year');
        $endDate = $this->faker->dateTimeBetween($startDate, '+5 years');

        return [
            'facility_id' => Facility::factory(),
            'service_type' => $this->faker->randomElement($serviceTypes),
            'renewal_start_date' => $startDate,
            'renewal_end_date' => $endDate,
        ];
    }

    /**
     * Create a service with specific dates
     */
    public function withDates(string $startDate, string $endDate): static
    {
        return $this->state(fn (array $attributes) => [
            'renewal_start_date' => $startDate,
            'renewal_end_date' => $endDate,
        ]);
    }

    /**
     * Create an expired service
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'renewal_start_date' => $this->faker->dateTimeBetween('-3 years', '-1 year'),
            'renewal_end_date' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    /**
     * Create a service expiring soon
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'renewal_start_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'renewal_end_date' => $this->faker->dateTimeBetween('now', '+6 months'),
        ]);
    }

    /**
     * Create a service with a specific type
     */
    public function ofType(string $serviceType): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => $serviceType,
        ]);
    }
}

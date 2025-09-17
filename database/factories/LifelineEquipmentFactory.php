<?php

namespace Database\Factories;

use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LifelineEquipment>
 */
class LifelineEquipmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LifelineEquipment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'facility_id' => Facility::factory(),
            'category' => $this->faker->randomElement(array_keys(LifelineEquipment::CATEGORIES)),
            'status' => $this->faker->randomElement(array_keys(LifelineEquipment::STATUSES)),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    /**
     * Indicate that the lifeline equipment is for electrical category.
     */
    public function electrical(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'electrical',
        ]);
    }

    /**
     * Indicate that the lifeline equipment is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the lifeline equipment is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the lifeline equipment is pending approval.
     */
    public function pendingApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_approval',
        ]);
    }

    /**
     * Indicate that the lifeline equipment is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }
}
<?php

namespace Database\Factories;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Facility>
 */
class FacilityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Facility::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'company_name' => $this->faker->company(),
            'office_code' => strtoupper($this->faker->bothify('??###')),
            'designation_number' => $this->faker->numerify('####-####-####'),
            'facility_name' => $this->faker->company() . '施設',
            'postal_code' => $this->faker->postcode(),
            'address' => $this->faker->address(),
            'phone_number' => $this->faker->phoneNumber(),
            'fax_number' => $this->faker->phoneNumber(),
            'status' => $this->faker->randomElement(['draft', 'pending_approval', 'approved']),
            'approved_at' => null,
            'approved_by' => null,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the facility is approved.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function approved()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'approved',
                'approved_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                'approved_by' => User::factory(),
            ];
        });
    }

    /**
     * Indicate that the facility is pending approval.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function pendingApproval()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending_approval',
                'approved_at' => null,
                'approved_by' => null,
            ];
        });
    }

    /**
     * Indicate that the facility is a draft.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function draft()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'draft',
                'approved_at' => null,
                'approved_by' => null,
            ];
        });
    }
}
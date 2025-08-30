<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MaintenanceSearchFavorite>
 */
class MaintenanceSearchFavoriteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => $this->faker->words(3, true),
            'facility_id' => $this->faker->boolean(70) ? \App\Models\Facility::factory() : null,
            'start_date' => $this->faker->boolean(60) ? $this->faker->date() : null,
            'end_date' => $this->faker->boolean(60) ? $this->faker->date() : null,
            'search_content' => $this->faker->boolean(50) ? $this->faker->words(2, true) : null,
        ];
    }
}

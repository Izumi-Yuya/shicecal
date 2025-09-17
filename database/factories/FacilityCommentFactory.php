<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FacilityComment>
 */
class FacilityCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $sections = ['basic_info', 'contact_info', 'building_info', 'facility_info', 'services'];

        return [
            'facility_id' => \App\Models\Facility::factory(),
            'user_id' => \App\Models\User::factory(),
            'section' => $this->faker->randomElement($sections),
            'comment' => $this->faker->paragraph(),
        ];
    }
}

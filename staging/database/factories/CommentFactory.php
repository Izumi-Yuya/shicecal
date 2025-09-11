<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $fieldNames = [
            'company_name',
            'office_code',
            'designation_number',
            'facility_name',
            'postal_code',
            'address',
            'phone_number',
            'fax_number',
            'general',
            null,
        ];

        return [
            'facility_id' => Facility::factory(),
            'field_name' => $this->faker->randomElement($fieldNames),
            'content' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'resolved']),
            'posted_by' => User::factory(),
            'assigned_to' => User::factory(),
            'resolved_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the comment is pending.
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'resolved_at' => null,
            ];
        });
    }

    /**
     * Indicate that the comment is in progress.
     */
    public function inProgress()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'in_progress',
                'resolved_at' => null,
            ];
        });
    }

    /**
     * Indicate that the comment is resolved.
     */
    public function resolved()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'resolved',
                'resolved_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }
}

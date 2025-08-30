<?php

namespace Database\Factories;

use App\Models\AnnualConfirmation;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnnualConfirmation>
 */
class AnnualConfirmationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $requestedAt = $this->faker->dateTimeBetween('-6 months', 'now');
        $status = $this->faker->randomElement(['pending', 'confirmed', 'discrepancy_reported', 'resolved']);
        
        $respondedAt = null;
        $resolvedAt = null;
        $discrepancyDetails = null;
        
        if (in_array($status, ['confirmed', 'discrepancy_reported', 'resolved'])) {
            $respondedAt = $this->faker->dateTimeBetween($requestedAt, 'now');
        }
        
        if ($status === 'discrepancy_reported' || $status === 'resolved') {
            $discrepancyDetails = $this->faker->paragraph();
        }
        
        if ($status === 'resolved') {
            $resolvedAt = $this->faker->dateTimeBetween($respondedAt, 'now');
        }

        return [
            'confirmation_year' => $this->faker->numberBetween(2022, 2025),
            'facility_id' => Facility::factory(),
            'requested_by' => User::factory()->state(['role' => 'admin']),
            'facility_manager_id' => User::factory()->state(['role' => 'viewer']),
            'status' => $status,
            'discrepancy_details' => $discrepancyDetails,
            'requested_at' => $requestedAt,
            'responded_at' => $respondedAt,
            'resolved_at' => $resolvedAt,
        ];
    }

    /**
     * Indicate that the confirmation is pending.
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'discrepancy_details' => null,
                'responded_at' => null,
                'resolved_at' => null,
            ];
        });
    }

    /**
     * Indicate that the confirmation is confirmed.
     */
    public function confirmed()
    {
        return $this->state(function (array $attributes) {
            $respondedAt = $this->faker->dateTimeBetween($attributes['requested_at'], 'now');
            
            return [
                'status' => 'confirmed',
                'discrepancy_details' => null,
                'responded_at' => $respondedAt,
                'resolved_at' => null,
            ];
        });
    }

    /**
     * Indicate that a discrepancy was reported.
     */
    public function discrepancyReported()
    {
        return $this->state(function (array $attributes) {
            $respondedAt = $this->faker->dateTimeBetween($attributes['requested_at'], 'now');
            
            return [
                'status' => 'discrepancy_reported',
                'discrepancy_details' => $this->faker->paragraph(),
                'responded_at' => $respondedAt,
                'resolved_at' => null,
            ];
        });
    }

    /**
     * Indicate that the discrepancy was resolved.
     */
    public function resolved()
    {
        return $this->state(function (array $attributes) {
            $respondedAt = $this->faker->dateTimeBetween($attributes['requested_at'], 'now');
            $resolvedAt = $this->faker->dateTimeBetween($respondedAt, 'now');
            
            return [
                'status' => 'resolved',
                'discrepancy_details' => $this->faker->paragraph(),
                'responded_at' => $respondedAt,
                'resolved_at' => $resolvedAt,
            ];
        });
    }

    /**
     * Set a specific year for the confirmation.
     */
    public function forYear(int $year)
    {
        return $this->state(function (array $attributes) use ($year) {
            return [
                'confirmation_year' => $year,
            ];
        });
    }
}
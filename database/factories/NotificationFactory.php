<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $types = ['comment_posted', 'comment_status_changed', 'facility_updated', 'approval_required'];

        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement($types),
            'title' => $this->faker->sentence(),
            'message' => $this->faker->paragraph(),
            'data' => [
                'comment_id' => $this->faker->numberBetween(1, 100),
                'facility_id' => $this->faker->numberBetween(1, 50),
            ],
            'is_read' => $this->faker->boolean(30), // 30% chance of being read
            'read_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 week', 'now'),
            'email_sent' => $this->faker->boolean(80), // 80% chance email was sent
            'email_sent_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate that the notification is unread.
     */
    public function unread()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_read' => false,
                'read_at' => null,
            ];
        });
    }

    /**
     * Indicate that the notification is read.
     */
    public function read()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_read' => true,
                'read_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }

    /**
     * Indicate that the email was sent.
     */
    public function emailSent()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_sent' => true,
                'email_sent_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }
}

<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $actions = ['create', 'update', 'delete', 'view', 'download', 'upload', 'export_csv', 'export_pdf', 'approve', 'reject'];
        $targetTypes = ['facility', 'user', 'file', 'comment', 'maintenance_history', 'system_setting'];

        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement($actions),
            'target_type' => $this->faker->randomElement($targetTypes),
            'target_id' => $this->faker->optional()->numberBetween(1, 100),
            'description' => $this->faker->sentence(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Create a login activity log.
     */
    public function login()
    {
        return $this->state(function (array $attributes) {
            return [
                'action' => 'login',
                'target_type' => 'user',
                'target_id' => $attributes['user_id'],
                'description' => 'ユーザーがログインしました',
            ];
        });
    }

    /**
     * Create a logout activity log.
     */
    public function logout()
    {
        return $this->state(function (array $attributes) {
            return [
                'action' => 'logout',
                'target_type' => 'user',
                'target_id' => $attributes['user_id'],
                'description' => 'ユーザーがログアウトしました',
            ];
        });
    }

    /**
     * Create a facility creation activity log.
     */
    public function facilityCreated()
    {
        return $this->state(function (array $attributes) {
            return [
                'action' => 'create',
                'target_type' => 'facility',
                'description' => '施設を作成しました',
            ];
        });
    }

    /**
     * Create a facility update activity log.
     */
    public function facilityUpdated()
    {
        return $this->state(function (array $attributes) {
            return [
                'action' => 'update',
                'target_type' => 'facility',
                'description' => '施設を更新しました',
            ];
        });
    }

    /**
     * Create a CSV export activity log.
     */
    public function csvExported()
    {
        return $this->state(function (array $attributes) {
            return [
                'action' => 'export_csv',
                'target_type' => 'facility',
                'target_id' => null,
                'description' => 'CSV出力を実行しました',
            ];
        });
    }

    /**
     * Create a PDF export activity log.
     */
    public function pdfExported()
    {
        return $this->state(function (array $attributes) {
            return [
                'action' => 'export_pdf',
                'target_type' => 'facility',
                'target_id' => null,
                'description' => 'PDF出力を実行しました',
            ];
        });
    }
}

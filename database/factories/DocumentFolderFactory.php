<?php

namespace Database\Factories;

use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentFolder>
 */
class DocumentFolderFactory extends Factory
{
    protected $model = DocumentFolder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            '契約書類',
            '図面',
            '点検記録',
            '保守契約',
            '清掃契約',
            '建築図面',
            '設備図面',
            '電気設備',
            'ガス設備',
            '水道設備',
            '年次点検',
            '月次点検',
            '緊急対応',
        ]);

        return [
            'facility_id' => Facility::factory(),
            'parent_id' => null,
            'name' => $name,
            'path' => $name,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Create a folder with a parent
     */
    public function withParent(DocumentFolder $parent): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            $name = $attributes['name'];
            return [
                'facility_id' => $parent->facility_id,
                'parent_id' => $parent->id,
                'path' => $parent->path . '/' . $name,
                'created_by' => $parent->created_by,
            ];
        });
    }

    /**
     * Create a root folder (no parent)
     */
    public function root(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'parent_id' => null,
                'path' => $attributes['name'],
            ];
        });
    }

    /**
     * Create a folder for a specific facility
     */
    public function forFacility(Facility $facility): static
    {
        return $this->state(function (array $attributes) use ($facility) {
            return [
                'facility_id' => $facility->id,
            ];
        });
    }

    /**
     * Create a folder created by a specific user
     */
    public function createdBy(User $user): static
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'created_by' => $user->id,
            ];
        });
    }
}
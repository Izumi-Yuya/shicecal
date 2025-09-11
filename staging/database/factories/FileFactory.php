<?php

namespace Database\Factories;

use App\Models\Facility;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\File>
 */
class FileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = File::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'facility_id' => Facility::factory(),
            'original_name' => $this->faker->word().'.pdf',
            'file_path' => 'facilities/'.$this->faker->numberBetween(1, 100).'/'.$this->faker->uuid().'.pdf',
            'file_size' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
            'mime_type' => 'application/pdf',
            'land_document_type' => null,
            'uploaded_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the file is a land document.
     */
    public function landDocument(string $documentType = 'lease_contract'): static
    {
        return $this->state(fn (array $attributes) => [
            'land_document_type' => $documentType,
        ]);
    }

    /**
     * Indicate that the file is a lease contract.
     */
    public function leaseContract(): static
    {
        return $this->landDocument('lease_contract');
    }

    /**
     * Indicate that the file is a property register.
     */
    public function propertyRegister(): static
    {
        return $this->landDocument('property_register');
    }

    /**
     * Indicate that the file is other land document.
     */
    public function otherLandDocument(): static
    {
        return $this->landDocument('other');
    }

    /**
     * Set a specific file size.
     */
    public function withSize(int $bytes): static
    {
        return $this->state(fn (array $attributes) => [
            'file_size' => $bytes,
        ]);
    }

    /**
     * Set a specific MIME type.
     */
    public function withMimeType(string $mimeType): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => $mimeType,
        ]);
    }

    /**
     * Set a specific original name.
     */
    public function withOriginalName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'original_name' => $name,
        ]);
    }
}

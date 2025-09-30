<?php

namespace Database\Factories;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentFile>
 */
class DocumentFileFactory extends Factory
{
    protected $model = DocumentFile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png'];
        $extension = $this->faker->randomElement($extensions);
        
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
        ];

        $fileNames = [
            'pdf' => ['契約書', '点検報告書', '図面', '仕様書', 'マニュアル'],
            'doc' => ['報告書', '議事録', '提案書', '計画書'],
            'docx' => ['報告書', '議事録', '提案書', '計画書'],
            'xls' => ['管理表', '一覧表', '集計表', 'データ'],
            'xlsx' => ['管理表', '一覧表', '集計表', 'データ'],
            'jpg' => ['写真', '画像', '図'],
            'png' => ['スクリーンショット', '図表', 'アイコン'],
        ];

        $baseName = $this->faker->randomElement($fileNames[$extension]);
        $originalName = $baseName . '_' . $this->faker->date('Ymd') . '.' . $extension;
        $storedName = $this->faker->uuid() . '.' . $extension;
        $filePath = 'documents/facility_' . $this->faker->numberBetween(1, 100) . '/' . $storedName;

        return [
            'facility_id' => Facility::factory(),
            'folder_id' => null,
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'file_path' => $filePath,
            'file_size' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
            'mime_type' => $mimeTypes[$extension],
            'file_extension' => $extension,
            'uploaded_by' => User::factory(),
        ];
    }

    /**
     * Create a file in a specific folder
     */
    public function inFolder(DocumentFolder $folder): static
    {
        return $this->state(function (array $attributes) use ($folder) {
            $storedName = $attributes['stored_name'];
            $filePath = 'documents/facility_' . $folder->facility_id . '/folder_' . $folder->id . '/' . $storedName;
            
            return [
                'facility_id' => $folder->facility_id,
                'folder_id' => $folder->id,
                'file_path' => $filePath,
            ];
        });
    }

    /**
     * Create a file for a specific facility
     */
    public function forFacility(Facility $facility): static
    {
        return $this->state(function (array $attributes) use ($facility) {
            $storedName = $attributes['stored_name'];
            $filePath = 'documents/facility_' . $facility->id . '/root/' . $storedName;
            
            return [
                'facility_id' => $facility->id,
                'file_path' => $filePath,
            ];
        });
    }

    /**
     * Create a file uploaded by a specific user
     */
    public function uploadedBy(User $user): static
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'uploaded_by' => $user->id,
            ];
        });
    }

    /**
     * Create a PDF file
     */
    public function pdf(): static
    {
        return $this->state(function (array $attributes) {
            $baseName = $this->faker->randomElement(['契約書', '点検報告書', '図面', '仕様書']);
            $originalName = $baseName . '_' . $this->faker->date('Ymd') . '.pdf';
            $storedName = $this->faker->uuid() . '.pdf';
            
            return [
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'mime_type' => 'application/pdf',
                'file_extension' => 'pdf',
            ];
        });
    }

    /**
     * Create an image file
     */
    public function image(): static
    {
        return $this->state(function (array $attributes) {
            $extension = $this->faker->randomElement(['jpg', 'png']);
            $baseName = $this->faker->randomElement(['写真', '画像', '図']);
            $originalName = $baseName . '_' . $this->faker->date('Ymd') . '.' . $extension;
            $storedName = $this->faker->uuid() . '.' . $extension;
            
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
            ];
            
            return [
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'mime_type' => $mimeTypes[$extension],
                'file_extension' => $extension,
            ];
        });
    }

    /**
     * Create a document file (Word, Excel)
     */
    public function document(): static
    {
        return $this->state(function (array $attributes) {
            $extension = $this->faker->randomElement(['doc', 'docx', 'xls', 'xlsx']);
            $baseNames = [
                'doc' => ['報告書', '議事録', '提案書'],
                'docx' => ['報告書', '議事録', '提案書'],
                'xls' => ['管理表', '一覧表', '集計表'],
                'xlsx' => ['管理表', '一覧表', '集計表'],
            ];
            
            $mimeTypes = [
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
            
            $baseName = $this->faker->randomElement($baseNames[$extension]);
            $originalName = $baseName . '_' . $this->faker->date('Ymd') . '.' . $extension;
            $storedName = $this->faker->uuid() . '.' . $extension;
            
            return [
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'mime_type' => $mimeTypes[$extension],
                'file_extension' => $extension,
            ];
        });
    }

    /**
     * Create a small file
     */
    public function small(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'file_size' => $this->faker->numberBetween(1024, 102400), // 1KB to 100KB
            ];
        });
    }

    /**
     * Create a large file
     */
    public function large(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'file_size' => $this->faker->numberBetween(5242880, 52428800), // 5MB to 50MB
            ];
        });
    }
}
<?php

namespace Database\Factories;

use App\Models\FacilityBasic;
use App\Models\FacilityInfo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FacilityBasic>
 */
class FacilityBasicFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FacilityBasic::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $serviceTypes = [
            '介護付有料老人ホーム',
            'デイサービス',
            'ショートステイ',
            '訪問介護',
            '居宅介護支援',
            'グループホーム',
            '小規模多機能型居宅介護',
            '看護小規模多機能型居宅介護',
            '定期巡回・随時対応型訪問介護看護',
            '夜間対応型訪問介護',
        ];

        $sections = [
            '有料老人ホーム',
            'グループホーム',
            'デイサービスセンター',
            '訪問看護ステーション',
            'ヘルパーステーション',
            'ケアプランセンター',
            '他（事務所など）',
        ];

        $buildingStructures = [
            '鉄筋コンクリート造',
            '鉄骨造',
            '木造',
            '鉄骨鉄筋コンクリート造',
            '軽量鉄骨造',
        ];

        $openingDate = $this->faker->dateTimeBetween('-10 years', '-1 year');
        $designationRenewalDate = $this->faker->dateTimeBetween('now', '+2 years');
        
        // Calculate years in operation
        $yearsInOperation = (new \DateTime())->diff(new \DateTime($openingDate))->y;

        return [
            'facility_id' => FacilityInfo::factory(),
            
            // 運営情報
            'opening_date' => $openingDate,
            'years_in_operation' => $yearsInOperation,
            'designation_renewal_date' => $designationRenewalDate,
            
            // 建物情報
            'building_structure' => $this->faker->randomElement($buildingStructures),
            'building_floors' => $this->faker->numberBetween(1, 10),
            
            // 定員・部屋数
            'paid_rooms_count' => $this->faker->numberBetween(10, 100),
            'ss_rooms_count' => $this->faker->numberBetween(0, 20),
            'capacity' => $this->faker->numberBetween(20, 150),
            
            // サービス情報（複数選択可能）
            'service_types' => $this->faker->randomElements($serviceTypes, $this->faker->numberBetween(1, 3)),
            
            // 部門
            'section' => $this->faker->randomElement($sections),
            
            // システム管理
            'status' => $this->faker->randomElement(['draft', 'pending_approval', 'approved']),
            'approved_at' => $this->faker->optional(0.7)->dateTimeBetween('-6 months', 'now'),
            'approved_by' => User::factory(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the basic info is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the basic info is pending approval.
     */
    public function pendingApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_approval',
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Indicate that the basic info is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Create basic info with specific service types
     */
    public function withServiceTypes(array $serviceTypes): static
    {
        return $this->state(fn (array $attributes) => [
            'service_types' => $serviceTypes,
        ]);
    }

    /**
     * Create basic info with specific section
     */
    public function withSection(string $section): static
    {
        return $this->state(fn (array $attributes) => [
            'section' => $section,
        ]);
    }

    /**
     * Create basic info for a large facility
     */
    public function largeFacility(): static
    {
        return $this->state(fn (array $attributes) => [
            'building_floors' => $this->faker->numberBetween(5, 15),
            'paid_rooms_count' => $this->faker->numberBetween(80, 200),
            'ss_rooms_count' => $this->faker->numberBetween(10, 30),
            'capacity' => $this->faker->numberBetween(100, 250),
        ]);
    }

    /**
     * Create basic info for a small facility
     */
    public function smallFacility(): static
    {
        return $this->state(fn (array $attributes) => [
            'building_floors' => $this->faker->numberBetween(1, 3),
            'paid_rooms_count' => $this->faker->numberBetween(5, 30),
            'ss_rooms_count' => $this->faker->numberBetween(0, 5),
            'capacity' => $this->faker->numberBetween(10, 40),
        ]);
    }

    /**
     * Create basic info with recent opening
     */
    public function recentlyOpened(): static
    {
        $openingDate = $this->faker->dateTimeBetween('-2 years', 'now');
        $yearsInOperation = (new \DateTime())->diff(new \DateTime($openingDate))->y;
        
        return $this->state(fn (array $attributes) => [
            'opening_date' => $openingDate,
            'years_in_operation' => $yearsInOperation,
        ]);
    }

    /**
     * Create basic info with designation renewal due soon
     */
    public function renewalDueSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'designation_renewal_date' => $this->faker->dateTimeBetween('now', '+6 months'),
        ]);
    }
}
<?php

namespace Database\Factories;

use App\Models\Facility;
use App\Models\MaintenanceHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MaintenanceHistory>
 */
class MaintenanceHistoryFactory extends Factory
{
    /**
     * The name of this factory's corresponding model.
     *
     * @var string
     */
    protected $model = MaintenanceHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $categories = ['exterior', 'interior', 'other'];
        $subcategories = [
            'exterior' => ['waterproof', 'painting'],
            'interior' => ['renovation', 'design'],
            'other' => ['renovation_work']
        ];

        $maintenanceTypes = [
            'exterior' => [
                'waterproof' => ['屋上防水工事', 'ベランダ防水工事', '外壁防水工事', '地下防水工事'],
                'painting' => ['外壁塗装工事', '屋根塗装工事', '鉄部塗装工事', '防錆塗装工事']
            ],
            'interior' => [
                'renovation' => ['内装リニューアル工事', 'フロア改修工事', '天井改修工事', '壁面改修工事'],
                'design' => ['内装デザイン変更', '照明設備更新', '床材変更工事', '壁紙張替え工事']
            ],
            'other' => [
                'renovation_work' => ['エアコン修理', '照明器具交換', '水道管修理', '電気設備点検', 'ドア修理', '給湯器交換', '換気扇清掃', '配管工事']
            ]
        ];

        $contractors = [
            '株式会社山田工務店',
            '田中電気工事',
            '佐藤建設',
            '鈴木設備',
            '高橋塗装',
            '渡辺リフォーム',
            '伊藤工業',
            '中村建築',
            '小林電設',
            '加藤住宅設備',
        ];

        $contactPersons = [
            '山田太郎',
            '田中花子',
            '佐藤次郎',
            '鈴木美咲',
            '高橋健一',
            '渡辺由美',
            '伊藤正男',
            '中村恵子',
            '小林大輔',
            '加藤真理',
        ];





        $category = $this->faker->randomElement($categories);
        $subcategory = $this->faker->randomElement($subcategories[$category]);
        $maintenanceType = $this->faker->randomElement($maintenanceTypes[$category][$subcategory]);

        $maintenanceDate = $this->faker->dateTimeBetween('-2 years', 'now');
        
        $data = [
            'facility_id' => Facility::factory(),
            'maintenance_date' => $maintenanceDate->format('Y-m-d'),
            'contractor' => $this->faker->optional(0.7)->randomElement($contractors),
            'content' => $maintenanceType,
            'cost' => $this->faker->optional(0.6)->randomFloat(2, 10000, 1000000),
            'category' => $category,
            'subcategory' => $subcategory,
            'contact_person' => $this->faker->optional(0.6)->randomElement($contactPersons),
            'phone_number' => $this->faker->optional(0.6)->phoneNumber,

            'notes' => $this->faker->optional(0.4)->sentence(15),
            'created_by' => User::factory(),
        ];

        // Add warranty period information for waterproofing work
        if ($category === 'exterior' && $subcategory === 'waterproof') {
            $warrantyYears = $this->faker->randomElement([5, 10, 15, 20]);
            $warrantyStartDate = $maintenanceDate;
            $warrantyEndDate = clone $warrantyStartDate;
            $warrantyEndDate->modify("+{$warrantyYears} years");

            $data['warranty_period_years'] = $warrantyYears;
            $data['warranty_start_date'] = $warrantyStartDate->format('Y-m-d');
            $data['warranty_end_date'] = $warrantyEndDate->format('Y-m-d');
        }

        return $data;
    }

    /**
     * Indicate that the maintenance was expensive.
     * Sets the cost to a high value between 100,000 and 1,000,000 yen.
     * 
     * @return static
     */
    public function expensive()
    {
        return $this->state(function () {
            return [
                'cost' => $this->faker->randomFloat(2, 100000, 1000000),
            ];
        });
    }

    /**
     * Indicate that the maintenance was recent.
     * Sets the maintenance date within the last 30 days.
     * 
     * @return static
     */
    public function recent()
    {
        return $this->state(function () {
            return [
                'maintenance_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            ];
        });
    }

    /**
     * Indicate that the maintenance has no cost information.
     * 
     * @return static
     */
    public function noCost()
    {
        return $this->state(function () {
            return [
                'cost' => null,
            ];
        });
    }

    /**
     * Indicate that the maintenance has no contractor information.
     * 
     * @return static
     */
    public function noContractor()
    {
        return $this->state(function () {
            return [
                'contractor' => null,
            ];
        });
    }

    /**
     * Create exterior maintenance (waterproof or painting).
     * 
     * @return static
     */
    public function exterior()
    {
        return $this->state(function () {
            $subcategory = $this->faker->randomElement(['waterproof', 'painting']);
            $maintenanceDate = $this->faker->dateTimeBetween('-2 years', 'now');
            
            $data = [
                'category' => 'exterior',
                'subcategory' => $subcategory,
                'maintenance_date' => $maintenanceDate->format('Y-m-d'),
            ];

            // Add warranty period information for waterproofing work
            if ($subcategory === 'waterproof') {
                $warrantyYears = $this->faker->randomElement([5, 10, 15, 20]);
                $warrantyStartDate = $maintenanceDate;
                $warrantyEndDate = clone $warrantyStartDate;
                $warrantyEndDate->modify("+{$warrantyYears} years");

                $data['warranty_period_years'] = $warrantyYears;
                $data['warranty_start_date'] = $warrantyStartDate->format('Y-m-d');
                $data['warranty_end_date'] = $warrantyEndDate->format('Y-m-d');
            }

            return $data;
        });
    }

    /**
     * Create interior maintenance.
     * 
     * @return static
     */
    public function interior()
    {
        return $this->state(function () {
            return [
                'category' => 'interior',
                'subcategory' => $this->faker->randomElement(['renovation', 'design']),
            ];
        });
    }

    /**
     * Create other maintenance.
     * 
     * @return static
     */
    public function other()
    {
        return $this->state(function () {
            return [
                'category' => 'other',
                'subcategory' => 'renovation_work',
            ];
        });
    }
}

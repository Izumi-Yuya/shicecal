<?php

namespace Tests\Unit\Services;

use App\Http\Controllers\CsvExportController;
use App\Models\Facility;
use App\Models\LandInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class CsvExportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;

    protected $reflection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new CsvExportController(app('App\Services\ActivityLogService'));
        $this->reflection = new ReflectionClass($this->controller);
    }

    public function test_get_available_fields_includes_land_info_fields()
    {
        $method = $this->reflection->getMethod('getAvailableFields');
        $method->setAccessible(true);

        $fields = $method->invoke($this->controller);

        // Check facility fields are included
        $this->assertArrayHasKey('company_name', $fields);
        $this->assertArrayHasKey('facility_name', $fields);
        $this->assertEquals('会社名', $fields['company_name']);
        $this->assertEquals('施設名', $fields['facility_name']);

        // Check land info fields are included
        $this->assertArrayHasKey('land_ownership_type', $fields);
        $this->assertArrayHasKey('land_parking_spaces', $fields);
        $this->assertArrayHasKey('land_site_area_sqm', $fields);
        $this->assertArrayHasKey('land_purchase_price', $fields);
        $this->assertArrayHasKey('land_monthly_rent', $fields);
        $this->assertArrayHasKey('land_management_company_name', $fields);
        $this->assertArrayHasKey('land_owner_name', $fields);

        $this->assertEquals('土地所有形態', $fields['land_ownership_type']);
        $this->assertEquals('敷地内駐車場台数', $fields['land_parking_spaces']);
        $this->assertEquals('敷地面積(㎡)', $fields['land_site_area_sqm']);
        $this->assertEquals('購入金額', $fields['land_purchase_price']);
        $this->assertEquals('家賃', $fields['land_monthly_rent']);
        $this->assertEquals('管理会社名', $fields['land_management_company_name']);
        $this->assertEquals('オーナー名', $fields['land_owner_name']);
    }

    public function test_get_land_info_fields_returns_correct_mapping()
    {
        $method = $this->reflection->getMethod('getLandInfoFields');
        $method->setAccessible(true);

        $fields = $method->invoke($this->controller);

        $expectedFields = [
            'land_ownership_type' => '土地所有形態',
            'land_parking_spaces' => '敷地内駐車場台数',
            'land_site_area_sqm' => '敷地面積(㎡)',
            'land_site_area_tsubo' => '敷地面積(坪数)',
            'land_purchase_price' => '購入金額',
            'land_unit_price_per_tsubo' => '坪単価',
            'land_monthly_rent' => '家賃',
            'land_contract_start_date' => '契約開始日',
            'land_contract_end_date' => '契約終了日',
            'land_contract_period_text' => '契約年数',
            'land_auto_renewal' => '自動更新の有無',
            'land_management_company_name' => '管理会社名',
            'land_owner_name' => 'オーナー名',
            'land_notes' => '土地備考',
            'land_status' => '土地情報ステータス',
            'land_approved_at' => '土地情報承認日時',
        ];

        foreach ($expectedFields as $key => $label) {
            $this->assertArrayHasKey($key, $fields);
            $this->assertEquals($label, $fields[$key]);
        }
    }

    public function test_get_field_value_handles_facility_fields()
    {
        $facility = Facility::factory()->make([
            'facility_name' => 'Test Facility',
            'company_name' => 'Test Company',
            'status' => 'approved',
        ]);

        $method = $this->reflection->getMethod('getFieldValue');
        $method->setAccessible(true);

        $this->assertEquals('Test Facility', $method->invoke($this->controller, $facility, 'facility_name'));
        $this->assertEquals('Test Company', $method->invoke($this->controller, $facility, 'company_name'));
        $this->assertEquals('承認済み', $method->invoke($this->controller, $facility, 'status'));
    }

    public function test_get_field_value_handles_land_info_fields()
    {
        $landInfo = LandInfo::factory()->make([
            'ownership_type' => 'owned',
            'parking_spaces' => 50,
            'site_area_sqm' => 1000.50,
            'site_area_tsubo' => 302.65,
            'purchase_price' => 50000000,
            'unit_price_per_tsubo' => 165000,
            'monthly_rent' => null,
            'contract_start_date' => '2020-01-01',
            'contract_end_date' => '2025-12-31',
            'auto_renewal' => 'yes',
            'notes' => 'Test notes',
            'status' => 'approved',
        ]);

        $facility = Facility::factory()->make();
        $facility->setRelation('landInfo', $landInfo);

        $method = $this->reflection->getMethod('getFieldValue');
        $method->setAccessible(true);

        $this->assertEquals('自社', $method->invoke($this->controller, $facility, 'land_ownership_type'));
        $this->assertEquals('50', $method->invoke($this->controller, $facility, 'land_parking_spaces'));
        $this->assertEquals('1,000.50', $method->invoke($this->controller, $facility, 'land_site_area_sqm'));
        $this->assertEquals('302.65', $method->invoke($this->controller, $facility, 'land_site_area_tsubo'));
        $this->assertEquals('50,000,000', $method->invoke($this->controller, $facility, 'land_purchase_price'));
        $this->assertEquals('165,000', $method->invoke($this->controller, $facility, 'land_unit_price_per_tsubo'));
        $this->assertEquals('', $method->invoke($this->controller, $facility, 'land_monthly_rent'));
        $this->assertEquals('2020/01/01', $method->invoke($this->controller, $facility, 'land_contract_start_date'));
        $this->assertEquals('2025/12/31', $method->invoke($this->controller, $facility, 'land_contract_end_date'));
        $this->assertEquals('あり', $method->invoke($this->controller, $facility, 'land_auto_renewal'));
        $this->assertEquals('Test notes', $method->invoke($this->controller, $facility, 'land_notes'));
        $this->assertEquals('承認済み', $method->invoke($this->controller, $facility, 'land_status'));
    }

    public function test_get_field_value_handles_null_land_info()
    {
        $facility = Facility::factory()->make();
        $facility->setRelation('landInfo', null);

        $method = $this->reflection->getMethod('getFieldValue');
        $method->setAccessible(true);

        $this->assertEquals('', $method->invoke($this->controller, $facility, 'land_ownership_type'));
        $this->assertEquals('', $method->invoke($this->controller, $facility, 'land_parking_spaces'));
        $this->assertEquals('', $method->invoke($this->controller, $facility, 'land_notes'));
    }

    public function test_get_ownership_type_label_returns_correct_japanese()
    {
        $method = $this->reflection->getMethod('getOwnershipTypeLabel');
        $method->setAccessible(true);

        $this->assertEquals('自社', $method->invoke($this->controller, 'owned'));
        $this->assertEquals('賃借', $method->invoke($this->controller, 'leased'));
        $this->assertEquals('自社（賃貸）', $method->invoke($this->controller, 'owned_rental'));
        $this->assertEquals('', $method->invoke($this->controller, null));
        $this->assertEquals('unknown', $method->invoke($this->controller, 'unknown'));
    }

    public function test_get_auto_renewal_label_returns_correct_japanese()
    {
        $method = $this->reflection->getMethod('getAutoRenewalLabel');
        $method->setAccessible(true);

        $this->assertEquals('あり', $method->invoke($this->controller, 'yes'));
        $this->assertEquals('なし', $method->invoke($this->controller, 'no'));
        $this->assertEquals('', $method->invoke($this->controller, null));
        $this->assertEquals('unknown', $method->invoke($this->controller, 'unknown'));
    }

    public function test_get_land_info_field_value_handles_different_ownership_types()
    {
        // Test owned property
        $ownedLandInfo = LandInfo::factory()->make([
            'ownership_type' => 'owned',
            'purchase_price' => 75000000,
            'unit_price_per_tsubo' => 200000,
            'monthly_rent' => null,
        ]);

        $method = $this->reflection->getMethod('getLandInfoFieldValue');
        $method->setAccessible(true);

        $this->assertEquals('自社', $method->invoke($this->controller, $ownedLandInfo, 'land_ownership_type'));
        $this->assertEquals('75,000,000', $method->invoke($this->controller, $ownedLandInfo, 'land_purchase_price'));
        $this->assertEquals('200,000', $method->invoke($this->controller, $ownedLandInfo, 'land_unit_price_per_tsubo'));
        $this->assertEquals('', $method->invoke($this->controller, $ownedLandInfo, 'land_monthly_rent'));

        // Test leased property
        $leasedLandInfo = LandInfo::factory()->make([
            'ownership_type' => 'leased',
            'monthly_rent' => 500000,
            'purchase_price' => null,
            'auto_renewal' => 'no',
        ]);

        $this->assertEquals('賃借', $method->invoke($this->controller, $leasedLandInfo, 'land_ownership_type'));
        $this->assertEquals('500,000', $method->invoke($this->controller, $leasedLandInfo, 'land_monthly_rent'));
        $this->assertEquals('', $method->invoke($this->controller, $leasedLandInfo, 'land_purchase_price'));
        $this->assertEquals('なし', $method->invoke($this->controller, $leasedLandInfo, 'land_auto_renewal'));
    }
}

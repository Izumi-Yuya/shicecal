<?php

namespace Tests\Unit\Helpers;

use App\Helpers\FacilityFormHelper;
use App\Models\Facility;
use Tests\TestCase;

class FacilityFormHelperTest extends TestCase
{
    public function test_generates_basic_breadcrumbs()
    {
        $breadcrumbs = FacilityFormHelper::generateBreadcrumbs('テストページ');

        $this->assertCount(2, $breadcrumbs);
        $this->assertEquals('ホーム', $breadcrumbs[0]['title']);
        $this->assertEquals('facilities.index', $breadcrumbs[0]['route']);
        $this->assertFalse($breadcrumbs[0]['active']);

        $this->assertEquals('テストページ', $breadcrumbs[1]['title']);
        $this->assertTrue($breadcrumbs[1]['active']);
    }

    public function test_generates_breadcrumbs_with_facility()
    {
        $facility = new Facility(['id' => 1, 'name' => 'テスト施設']);
        $breadcrumbs = FacilityFormHelper::generateBreadcrumbs('編集ページ', $facility);

        $this->assertCount(3, $breadcrumbs);
        $this->assertEquals('ホーム', $breadcrumbs[0]['title']);
        $this->assertEquals('施設詳細', $breadcrumbs[1]['title']);
        $this->assertEquals('facilities.show', $breadcrumbs[1]['route']);
        $this->assertEquals([$facility], $breadcrumbs[1]['params']);
        $this->assertEquals('編集ページ', $breadcrumbs[2]['title']);
        $this->assertTrue($breadcrumbs[2]['active']);
    }

    public function test_gets_section_icon()
    {
        $icon = FacilityFormHelper::getSectionIcon('basic_info');
        $this->assertEquals('fas fa-info-circle', $icon);

        $unknownIcon = FacilityFormHelper::getSectionIcon('unknown_section');
        $this->assertEquals('fas fa-cog', $unknownIcon);
    }

    public function test_gets_section_color()
    {
        $color = FacilityFormHelper::getSectionColor('basic_info');
        $this->assertEquals('primary', $color);

        $unknownColor = FacilityFormHelper::getSectionColor('unknown_section');
        $this->assertEquals('primary', $unknownColor);
    }

    public function test_gets_section_config()
    {
        $config = FacilityFormHelper::getSectionConfig('basic_info');

        $this->assertArrayHasKey('title', $config);
        $this->assertArrayHasKey('icon', $config);
        $this->assertArrayHasKey('color', $config);

        $this->assertEquals('基本情報', $config['title']);
        $this->assertEquals('fas fa-info-circle', $config['icon']);
        $this->assertEquals('primary', $config['color']);
    }

    public function test_gets_section_config_with_custom_values()
    {
        $config = FacilityFormHelper::getSectionConfig(
            'basic_info',
            'カスタムタイトル',
            'fas fa-custom',
            'success'
        );

        $this->assertEquals('カスタムタイトル', $config['title']);
        $this->assertEquals('fas fa-custom', $config['icon']);
        $this->assertEquals('success', $config['color']);
    }

    public function test_gets_facility_card_data()
    {
        $facility = new Facility;
        $facility->name = 'テスト施設';
        $facility->address = 'テスト住所';
        $facility->type = 'テストタイプ';
        $facility->prefecture = '東京都';
        $facility->city = '渋谷区';

        $cardData = FacilityFormHelper::getFacilityCardData($facility);

        $this->assertEquals('テスト施設', $cardData['name']);
        $this->assertEquals('テスト住所', $cardData['address']);
        $this->assertEquals('テストタイプ', $cardData['type']);
        $this->assertEquals('東京都', $cardData['prefecture']);
        $this->assertEquals('渋谷区', $cardData['city']);
    }

    public function test_validates_section_types()
    {
        $this->assertTrue(FacilityFormHelper::isValidSectionType('basic_info'));
        $this->assertTrue(FacilityFormHelper::isValidSectionType('land_info'));
        $this->assertFalse(FacilityFormHelper::isValidSectionType('invalid_section'));
    }

    public function test_gets_available_section_types()
    {
        $types = FacilityFormHelper::getAvailableSectionTypes();

        $this->assertIsArray($types);
        $this->assertContains('basic_info', $types);
        $this->assertContains('land_info', $types);
        $this->assertContains('documents', $types);
    }

    public function test_gets_common_validation_rules()
    {
        $defaultRules = FacilityFormHelper::getCommonValidationRules();
        $this->assertArrayHasKey('name', $defaultRules);
        $this->assertArrayHasKey('address', $defaultRules);

        $landRules = FacilityFormHelper::getCommonValidationRules('land_info');
        $this->assertArrayHasKey('land_area', $landRules);
        $this->assertArrayHasKey('building_area', $landRules);
    }
}

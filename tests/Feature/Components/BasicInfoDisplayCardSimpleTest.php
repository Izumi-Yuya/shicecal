<?php

namespace Tests\Feature\Components;

use Tests\TestCase;
use App\Models\Facility;
use App\Models\FacilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * 基本情報表示カードの簡単な移行テスト
 */
class BasicInfoDisplayCardSimpleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 基本情報表示カードコンポーネントがレンダリングされること()
    {
        // 最小限のテストデータ
        $facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'company_name' => 'テスト会社',
            'office_code' => 'TEST001',
        ]);

        // ビューのレンダリングテスト
        $view = view('facilities.basic-info.partials.display-card', compact('facility'));
        $html = $view->render();

        // 基本的な内容が含まれているかチェック
        $this->assertStringContainsString('テスト施設', $html);
        $this->assertStringContainsString('テスト会社', $html);
        $this->assertStringContainsString('TEST001', $html);
        
        // 共通テーブルコンポーネントが使用されているかチェック
        $this->assertStringContainsString('facility-info-card', $html);
        $this->assertStringContainsString('detail-label', $html);
        $this->assertStringContainsString('detail-value', $html);
    }

    /** @test */
    public function 空フィールドが未設定として表示されること()
    {
        // 空フィールドを持つ施設（必須フィールドは空文字列を使用）
        $facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'company_name' => '',
            'office_code' => '',
            'email' => '',
        ]);

        $view = view('facilities.basic-info.partials.display-card', compact('facility'));
        $html = $view->render();

        // 未設定が表示されることを確認
        $this->assertStringContainsString('未設定', $html);
        
        // empty-fieldクラスが適用されることを確認
        $this->assertStringContainsString('empty-field', $html);
    }

    /** @test */
    public function バッジが正しく表示されること()
    {
        $facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'office_code' => 'BADGE001',
        ]);

        $view = view('facilities.basic-info.partials.display-card', compact('facility'));
        $html = $view->render();

        // バッジクラスが含まれることを確認
        $this->assertStringContainsString('badge', $html);
        $this->assertStringContainsString('BADGE001', $html);
    }

    /** @test */
    public function メールアドレスがリンクとして表示されること()
    {
        $facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'email' => 'test@example.com',
        ]);

        $view = view('facilities.basic-info.partials.display-card', compact('facility'));
        $html = $view->render();

        // メールリンクが含まれることを確認
        $this->assertStringContainsString('mailto:test@example.com', $html);
        $this->assertStringContainsString('fa-envelope', $html);
    }

    /** @test */
    public function URLがリンクとして表示されること()
    {
        $facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'website_url' => 'https://example.com',
        ]);

        $view = view('facilities.basic-info.partials.display-card', compact('facility'));
        $html = $view->render();

        // URLリンクが含まれることを確認
        $this->assertStringContainsString('href="https://example.com"', $html);
        $this->assertStringContainsString('fa-external-link-alt', $html);
        $this->assertStringContainsString('target="_blank"', $html);
    }
}
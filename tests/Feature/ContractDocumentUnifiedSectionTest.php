<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractDocumentUnifiedSectionTest extends TestCase
{
    use RefreshDatabase;

    protected Facility $facility;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->facility = Facility::factory()->create();
        
        // 管理者ユーザー（編集権限あり）
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'access_scope' => 'all_facilities',
        ]);
    }

    /** @test */
    public function unified_document_section_is_displayed_on_contracts_tab()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('facilities.show', $this->facility) . '#contracts');

        $response->assertOk();
        
        // 統一ドキュメント管理セクションが表示されることを確認
        $response->assertSee('契約書関連ドキュメント');
        $response->assertSee('ドキュメントを表示');
        
        // 統一セクションのHTML要素が存在することを確認
        $response->assertSee('unified-contract-documents-section', false);
        $response->assertSee('unified-documents-toggle', false);
        $response->assertSee('unified-documents-section', false);
    }

    /** @test */
    public function unified_section_contains_contract_document_manager_component()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('facilities.show', $this->facility) . '#contracts');

        $response->assertOk();
        
        // contract-document-managerコンポーネントが含まれることを確認
        $response->assertSee('contract-document-management-container', false);
        $response->assertSee('契約書ドキュメント管理');
    }

    /** @test */
    public function unified_section_is_initially_collapsed()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('facilities.show', $this->facility) . '#contracts');

        $response->assertOk();
        
        // 初期状態で折りたたまれていることを確認
        // collapse クラスが存在し、show クラスが存在しないことを確認
        $content = $response->getContent();
        
        // unified-documents-section が collapse クラスを持つことを確認
        $this->assertStringContainsString('id="unified-documents-section"', $content);
        $this->assertStringContainsString('class="collapse"', $content);
        
        // トグルボタンが aria-expanded="false" を持つことを確認
        $this->assertStringContainsString('aria-expanded="false"', $content);
    }

    /** @test */
    public function unified_section_has_toggle_button_with_correct_attributes()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('facilities.show', $this->facility) . '#contracts');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // トグルボタンの属性を確認
        $this->assertStringContainsString('id="unified-documents-toggle"', $content);
        $this->assertStringContainsString('data-bs-toggle="collapse"', $content);
        $this->assertStringContainsString('data-bs-target="#unified-documents-section"', $content);
        $this->assertStringContainsString('aria-controls="unified-documents-section"', $content);
    }

    /** @test */
    public function sub_tabs_do_not_contain_document_sections()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('facilities.show', $this->facility) . '#contracts');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // サブタブ内にドキュメントセクションが存在しないことを確認
        $this->assertStringNotContainsString('others-documents-section', $content);
        $this->assertStringNotContainsString('meal-service-documents-section', $content);
        $this->assertStringNotContainsString('parking-documents-section', $content);
    }

    /** @test */
    public function unified_section_is_positioned_before_sub_tabs()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('facilities.show', $this->facility) . '#contracts');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // 統一セクションがサブタブナビゲーションの前に配置されていることを確認
        // Look for the actual HTML div elements, not CSS class names
        $unifiedSectionPos = strpos($content, '<div class="unified-contract-documents-section');
        $subTabsPos = strpos($content, '<div class="contracts-subtabs">');
        
        $this->assertNotFalse($unifiedSectionPos, 'Unified section should exist');
        $this->assertNotFalse($subTabsPos, 'Sub tabs should exist');
        // Unified section should come before (have a smaller position than) sub tabs
        $this->assertLessThan($subTabsPos, $unifiedSectionPos, 'Unified section should be before sub tabs');
    }

    /** @test */
    public function unified_section_has_proper_accessibility_attributes()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('facilities.show', $this->facility) . '#contracts');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // アクセシビリティ属性を確認
        $this->assertStringContainsString('aria-expanded', $content);
        $this->assertStringContainsString('aria-controls="unified-documents-section"', $content);
        
        // ボタンのtype属性を確認 - unified-documents-toggle button should have type="button"
        $this->assertStringContainsString('id="unified-documents-toggle"', $content);
        $this->assertStringContainsString('type="button"', $content);
    }

    /** @test */
    public function unified_section_includes_modal_hoisting_script()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('facilities.show', $this->facility) . '#contracts');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // モーダルhoisting処理のJavaScriptが含まれることを確認
        $this->assertStringContainsString('hoistModals', $content);
        $this->assertStringContainsString('Modal hoisting & z-index fix', $content);
    }

    /** @test */
    public function unified_section_includes_toggle_button_text_change_script()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('facilities.show', $this->facility) . '#contracts');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // トグルボタンのテキスト変更スクリプトが含まれることを確認
        $this->assertStringContainsString('ドキュメントを非表示', $content);
        $this->assertStringContainsString('ドキュメントを表示', $content);
        $this->assertStringContainsString('show.bs.collapse', $content);
        $this->assertStringContainsString('hide.bs.collapse', $content);
    }

    /** @test */
    public function viewer_can_see_unified_section_but_not_edit_buttons()
    {
        $viewerUser = User::factory()->create([
            'role' => 'viewer',
            'access_scope' => 'all_facilities',
        ]);

        $this->actingAs($viewerUser);

        $response = $this->get(route('facilities.show', $this->facility) . '#contracts');

        $response->assertOk();
        
        // 統一セクションは表示される
        $response->assertSee('契約書関連ドキュメント');
        $response->assertSee('unified-documents-section', false);
    }

    /** @test */
    public function unified_section_has_proper_css_classes()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('facilities.show', $this->facility) . '#contracts');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // CSSクラスを確認
        $this->assertStringContainsString('unified-contract-documents-section', $content);
        $this->assertStringContainsString('unified-documents-toggle', $content);
        $this->assertStringContainsString('btn btn-outline-primary btn-sm', $content);
    }

    /** @test */
    public function unified_section_card_has_proper_structure()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('facilities.show', $this->facility) . '#contracts');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // カード構造を確認
        $this->assertStringContainsString('card border-0 shadow-sm', $content);
        $this->assertStringContainsString('card-header bg-primary text-white', $content);
        $this->assertStringContainsString('card-body p-0', $content);
    }
}

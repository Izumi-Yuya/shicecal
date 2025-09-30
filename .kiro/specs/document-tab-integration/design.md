# 設計書

## 概要

この設計書では、施設詳細画面のドキュメントタブを既存の完全なドキュメント管理システムと統合する方法を定義します。現在のプレースホルダー実装を、既存のDocumentController、JavaScriptモジュール、UIコンポーネントを活用した完全な機能実装に置き換えます。

## アーキテクチャ

### 現在の状況分析

**既存の実装:**
- 完全なドキュメント管理システムが `/facilities/{id}/documents` で利用可能
- DocumentController with full CRUD operations
- DocumentUploadManager, DocumentFileManager JavaScript modules
- Complete UI with modals, file operations, folder management
- CSS styling and responsive design

**現在のドキュメントタブ:**
- Basic placeholder implementation with sample data
- Alert messages saying "next task will implement this"
- No actual API integration
- Simplified UI without full functionality

### 統合アプローチ

**Option 1: Iframe Embedding (却下)**
- 理由: セキュリティ制限、スタイリング問題、ユーザー体験の悪化

**Option 2: AJAX Content Loading (採用)**
- 既存のドキュメント管理HTMLを動的に読み込み
- 既存のJavaScriptモジュールを再利用
- タブ内でのシームレスな統合

**Option 3: Component Refactoring (将来の改善)**
- 長期的にはBladeコンポーネント化を検討
- 現在は既存システムの活用を優先

## コンポーネントと インターフェース

### 1. Frontend Integration Layer

#### FacilityDocumentTabManager
```javascript
class FacilityDocumentTabManager {
    constructor(facilityId) {
        this.facilityId = facilityId;
        this.isLoaded = false;
        this.documentManager = null;
        this.uploadManager = null;
        this.fileManager = null;
    }
    
    async loadDocumentInterface() {
        // Load document management HTML via AJAX
        // Initialize existing JavaScript modules
        // Set up event listeners
    }
    
    initializeModules() {
        // Initialize DocumentUploadManager
        // Initialize DocumentFileManager  
        // Initialize DocumentManager
    }
    
    handleTabSwitch() {
        // Preserve state when switching tabs
        // Clean up resources if needed
    }
}
```

### 2. Backend Integration Points

#### Enhanced DocumentController Methods
```php
// Add method for tab-specific content loading
public function getTabContent(Facility $facility): JsonResponse
{
    // Return HTML content optimized for tab embedding
    // Include necessary CSS/JS module references
    // Provide initialization data
}

// Modify existing methods to support tab context
public function index(Facility $facility, Request $request): View|JsonResponse
{
    if ($request->wantsJson() && $request->has('tab_mode')) {
        return $this->getTabContent($facility);
    }
    
    // Existing full-page implementation
    return view('facilities.documents.index', $data);
}
```

### 3. UI Component Integration

#### Tab Content Structure
```html
<div class="documents-container">
    <!-- Loading State -->
    <div id="documentsLoading" class="documents-loading">
        <div class="text-center py-5">
            <div class="spinner-border" role="status"></div>
            <p class="mt-2">ドキュメントを読み込んでいます...</p>
        </div>
    </div>
    
    <!-- Document Management Interface -->
    <div id="documentsInterface" class="documents-interface d-none">
        <!-- Content loaded via AJAX -->
    </div>
    
    <!-- Error State -->
    <div id="documentsError" class="documents-error d-none">
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="errorMessage"></span>
        </div>
        <button class="btn btn-primary" onclick="retryLoadDocuments()">
            <i class="fas fa-redo me-1"></i>再試行
        </button>
    </div>
</div>
```

## データモデル

### 既存モデルの活用

**DocumentFolder Model:**
- 既存の実装をそのまま使用
- 追加の変更は不要

**DocumentFile Model:**
- 既存の実装をそのまま使用
- 追加の変更は不要

**Facility Model:**
- 既存のリレーションシップを活用
- `documentFolders()` および `documentFiles()` relations

### データフロー

```
1. User clicks Documents tab
2. FacilityDocumentTabManager.loadDocumentInterface()
3. AJAX request to DocumentController.getTabContent()
4. Controller returns HTML + initialization data
5. Tab content is populated with document interface
6. JavaScript modules are initialized
7. Document management functionality is fully available
```

## エラーハンドリング

### エラー分類と対応

**1. Network Errors**
```javascript
catch (error) {
    if (error instanceof TypeError && error.message.includes('fetch')) {
        this.showError('ネットワークエラーが発生しました。インターネット接続を確認してください。');
    }
}
```

**2. Authorization Errors**
```javascript
if (response.status === 403) {
    this.showError('このドキュメントにアクセスする権限がありません。');
}
```

**3. Server Errors**
```javascript
if (response.status >= 500) {
    this.showError('サーバーエラーが発生しました。しばらく時間をおいて再試行してください。');
}
```

**4. Content Loading Errors**
```javascript
if (!response.ok) {
    this.showError('ドキュメント管理インターフェースの読み込みに失敗しました。');
}
```

### エラー回復機能

**Retry Mechanism:**
- 自動リトライ（最大3回）
- 手動リトライボタン
- Exponential backoff

**Graceful Degradation:**
- 基本的なファイル一覧表示
- 外部ページへのリンク提供
- エラー状態の明確な表示

## テスト戦略

### 1. Unit Tests

**FacilityDocumentTabManager Tests:**
```javascript
describe('FacilityDocumentTabManager', () => {
    test('should initialize with correct facility ID', () => {
        const manager = new FacilityDocumentTabManager(123);
        expect(manager.facilityId).toBe(123);
        expect(manager.isLoaded).toBe(false);
    });
    
    test('should load document interface successfully', async () => {
        // Mock successful AJAX response
        // Verify HTML content is loaded
        // Verify modules are initialized
    });
    
    test('should handle network errors gracefully', async () => {
        // Mock network error
        // Verify error message is displayed
        // Verify retry functionality
    });
});
```

### 2. Integration Tests

**Tab Integration Tests:**
```php
class DocumentTabIntegrationTest extends TestCase
{
    public function test_document_tab_loads_successfully()
    {
        $facility = Facility::factory()->create();
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->getJson("/facilities/{$facility->id}/documents?tab_mode=1");
            
        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'html',
            'css_files',
            'js_modules'
        ]);
    }
    
    public function test_document_tab_respects_permissions()
    {
        // Test with user without document access
        // Verify appropriate error response
    }
}
```

### 3. Browser Tests

**End-to-End Tab Functionality:**
```php
class DocumentTabBrowserTest extends DuskTestCase
{
    public function test_user_can_access_documents_via_tab()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documentsInterface')
                ->assertSee('ドキュメント管理')
                ->assertSee('ファイルアップロード');
        });
    }
    
    public function test_document_operations_work_in_tab()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documentsInterface')
                ->click('#createFolderBtn')
                ->waitFor('#createFolderModal')
                ->type('#folderName', 'Test Folder')
                ->click('#createFolderSubmit')
                ->waitUntilMissing('#createFolderModal')
                ->assertSee('Test Folder');
        });
    }
}
```

## セキュリティ考慮事項

### 1. Authentication & Authorization

**Existing Policy Integration:**
- DocumentPolicy の既存実装を活用
- タブコンテキストでも同じ権限チェック
- CSRF トークンの適切な処理

### 2. Content Security

**XSS Prevention:**
- 既存のBlade テンプレートエスケープを活用
- JavaScript での動的コンテンツ生成時のサニタイゼーション
- CSP ヘッダーの適切な設定

**CSRF Protection:**
- 既存のLaravel CSRF 保護を活用
- AJAX リクエストでのトークン送信
- メタタグからのトークン取得

### 3. File Security

**Existing Security Measures:**
- FileHandlingService のセキュリティ機能を活用
- ファイルタイプ検証
- パストラバーサル攻撃対策

## パフォーマンス最適化

### 1. Lazy Loading Strategy

**Tab Content Loading:**
```javascript
// Only load when tab is first accessed
if (!this.isLoaded && tabId === 'documents') {
    await this.loadDocumentInterface();
    this.isLoaded = true;
}
```

**Module Loading:**
```javascript
// Dynamic import of heavy modules
const { DocumentUploadManager } = await import('/js/modules/document-upload.js');
const { DocumentFileManager } = await import('/js/modules/document-file-manager.js');
```

### 2. Caching Strategy

**HTML Content Caching:**
- Browser cache for static assets
- Session storage for tab state
- Memory cache for frequently accessed data

**API Response Caching:**
- Cache folder contents for short periods
- Invalidate cache on modifications
- Use ETags for conditional requests

### 3. Resource Optimization

**CSS Loading:**
- Load document-specific CSS only when needed
- Use existing shared styles where possible
- Minimize additional CSS overhead

**JavaScript Optimization:**
- Reuse existing module instances where possible
- Clean up event listeners on tab switch
- Debounce expensive operations

## 実装フェーズ

### Phase 1: Core Integration (Priority: High)
1. Create FacilityDocumentTabManager class
2. Modify DocumentController for tab support
3. Implement AJAX content loading
4. Basic error handling

### Phase 2: Full Feature Integration (Priority: High)
1. Initialize existing JavaScript modules
2. Implement file upload functionality
3. Implement folder management
4. Full UI integration

### Phase 3: Polish & Optimization (Priority: Medium)
1. Performance optimizations
2. Enhanced error handling
3. Comprehensive testing
4. Documentation updates

### Phase 4: Advanced Features (Priority: Low)
1. State persistence across tab switches
2. Advanced caching strategies
3. Progressive enhancement
4. Accessibility improvements

## 既存システムとの互換性

### Backward Compatibility
- 既存の `/facilities/{id}/documents` ページは変更なし
- 既存のAPI エンドポイントは変更なし
- 既存のJavaScript モジュールは変更なし

### Forward Compatibility
- 将来のBladeコンポーネント化に対応
- モジュラー設計で拡張性を確保
- 設定可能な統合オプション

## 設定とカスタマイゼーション

### Configuration Options
```php
// config/facility-document.php
'tab_integration' => [
    'enabled' => env('DOCUMENT_TAB_INTEGRATION', true),
    'lazy_loading' => env('DOCUMENT_TAB_LAZY_LOADING', true),
    'cache_duration' => env('DOCUMENT_TAB_CACHE_DURATION', 300), // 5 minutes
    'max_retries' => env('DOCUMENT_TAB_MAX_RETRIES', 3),
]
```

### Customization Points
- CSS スタイルのオーバーライド
- JavaScript イベントハンドラーの拡張
- エラーメッセージのカスタマイゼーション
- ローディング状態のカスタマイゼーション
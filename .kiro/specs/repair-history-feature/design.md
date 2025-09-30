# 修繕履歴機能 設計書

## 概要

施設管理システムに修繕履歴タブを追加し、外装、内装リニューアル、その他の修繕履歴を管理する機能を設計する。既存の`maintenance_histories`テーブルを拡張し、防災・防犯設備の実装パターンを参考にしたタブ形式のUIを実装する。

## アーキテクチャ

### 全体構成
```
┌─────────────────────────────────────────────────────────────┐
│                    Facility Show Page                      │
├─────────────────────────────────────────────────────────────┤
│ 基本情報 │ 資産情報 │ ライフライン設備 │ 防災・防犯 │ 修繕履歴 │
├─────────────────────────────────────────────────────────────┤
│                      修繕履歴タブ                           │
│  ┌─────────┬─────────────────┬─────────┐                │
│  │  外装   │  内装リニューアル  │  その他  │                │
│  └─────────┴─────────────────┴─────────┘                │
│                                                             │
│  外装: 防水テーブル(左) | 塗装テーブル(右)                   │
│  内装: 内装リニューアル情報 + 内装・意匠履歴テーブル         │
│  その他: 改修工事履歴テーブル                               │
└─────────────────────────────────────────────────────────────┘
```

### MVC構成
- **Model**: MaintenanceHistory (既存テーブル拡張)
- **Controller**: RepairHistoryController (新規作成)
- **View**: resources/views/facilities/repair-history/ (新規ディレクトリ)

## コンポーネントとインターフェース

### 1. データベース設計

#### 既存テーブル分析
```sql
-- 既存のmaintenance_historiesテーブル
CREATE TABLE maintenance_histories (
    id BIGINT UNSIGNED PRIMARY KEY,
    facility_id BIGINT UNSIGNED,
    maintenance_date DATE,
    content TEXT,
    cost DECIMAL(10,2) NULL,
    contractor VARCHAR(255) NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 追加が必要なカラム
画像から確認した必要フィールドと既存フィールドの比較：

**既存フィールド:**
- facility_id ✓
- maintenance_date ✓ (施工日)
- content ✓ (修繕内容)
- cost ✓ (金額)
- contractor ✓ (施工会社)

**不足フィールド:**
- category (外装/内装/その他の分類)
- subcategory (防水/塗装/内装リニューアル/意匠/改修工事)
- contact_person (担当者)
- phone_number (連絡先)
- classification (区分)
- notes (備考)
- warranty_period_years (保証期間年数 - 防水のみ)
- warranty_start_date (保証開始日)
- warranty_end_date (保証終了日)

#### マイグレーションファイル設計
```sql
-- 2025_09_29_105830_add_repair_history_fields_to_maintenance_histories_table.php
ALTER TABLE maintenance_histories ADD COLUMN category VARCHAR(50) DEFAULT 'other';
ALTER TABLE maintenance_histories ADD COLUMN subcategory VARCHAR(50) NULL;
ALTER TABLE maintenance_histories ADD COLUMN contact_person VARCHAR(255) NULL;
ALTER TABLE maintenance_histories ADD COLUMN phone_number VARCHAR(20) NULL;
ALTER TABLE maintenance_histories ADD COLUMN classification VARCHAR(100) NULL;
ALTER TABLE maintenance_histories ADD COLUMN notes TEXT NULL;
ALTER TABLE maintenance_histories ADD COLUMN warranty_period_years INT NULL;
ALTER TABLE maintenance_histories ADD COLUMN warranty_start_date DATE NULL;
ALTER TABLE maintenance_histories ADD COLUMN warranty_end_date DATE NULL;
```

### 2. モデル設計

#### MaintenanceHistory モデル拡張
```php
class MaintenanceHistory extends Model
{
    const CATEGORIES = [
        'exterior' => '外装',
        'interior' => '内装リニューアル', 
        'other' => 'その他'
    ];

    const SUBCATEGORIES = [
        'exterior' => [
            'waterproof' => '防水',
            'painting' => '塗装'
        ],
        'interior' => [
            'renovation' => '内装リニューアル',
            'design' => '内装・意匠'
        ],
        'other' => [
            'renovation_work' => '改修工事'
        ]
    ];

    protected $fillable = [
        'facility_id', 'maintenance_date', 'content', 'cost', 'contractor',
        'category', 'subcategory', 'contact_person', 'phone_number', 
        'classification', 'notes', 'warranty_period_years', 
        'warranty_start_date', 'warranty_end_date', 'created_by'
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
        'cost' => 'decimal:2'
    ];

    // リレーション
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // スコープ
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeBySubcategory($query, $subcategory)
    {
        return $query->where('subcategory', $subcategory);
    }

    public function scopeOrderByDate($query, $direction = 'desc')
    {
        return $query->orderBy('maintenance_date', $direction);
    }
}
```

### 3. コントローラー設計

#### RepairHistoryController
```php
class RepairHistoryController extends Controller
{
    public function index(Facility $facility)
    {
        $this->authorize('view', [MaintenanceHistory::class, $facility]);
        
        // カテゴリ別にデータを取得
        $exteriorHistory = $facility->maintenanceHistories()
            ->byCategory('exterior')
            ->orderByDate()
            ->get()
            ->groupBy('subcategory');
            
        $interiorHistory = $facility->maintenanceHistories()
            ->byCategory('interior')
            ->orderByDate()
            ->get();
            
        $otherHistory = $facility->maintenanceHistories()
            ->byCategory('other')
            ->orderByDate()
            ->get();

        return view('facilities.repair-history.index', compact(
            'facility', 'exteriorHistory', 'interiorHistory', 'otherHistory'
        ));
    }

    public function edit(Facility $facility, string $category)
    {
        $this->authorize('update', [MaintenanceHistory::class, $facility]);
        
        $histories = $facility->maintenanceHistories()
            ->byCategory($category)
            ->orderByDate()
            ->get();

        return view('facilities.repair-history.edit', compact(
            'facility', 'category', 'histories'
        ));
    }

    public function update(Request $request, Facility $facility, string $category)
    {
        $this->authorize('update', [MaintenanceHistory::class, $facility]);
        
        $validated = $request->validate($this->getValidationRules($category));
        
        // データ更新処理
        $this->updateRepairHistory($facility, $category, $validated);
        
        return redirect()
            ->route('facilities.show', $facility)
            ->with('success', '修繕履歴が更新されました。');
    }

    private function getValidationRules(string $category): array
    {
        $baseRules = [
            '*.maintenance_date' => 'required|date',
            '*.contractor' => 'required|string|max:255',
            '*.content' => 'required|string',
            '*.cost' => 'nullable|numeric|min:0',
            '*.contact_person' => 'nullable|string|max:255',
            '*.phone_number' => 'nullable|string|max:20',
            '*.classification' => 'nullable|string|max:100',
            '*.notes' => 'nullable|string'
        ];

        if ($category === 'exterior') {
            $baseRules['*.warranty_period_years'] = 'nullable|integer|min:1|max:50';
            $baseRules['*.warranty_start_date'] = 'nullable|date';
            $baseRules['*.warranty_end_date'] = 'nullable|date|after_or_equal:*.warranty_start_date';
        }

        return $baseRules;
    }
}
```

### 4. ビュー設計

#### ディレクトリ構造
```
resources/views/facilities/repair-history/
├── index.blade.php          # メイン表示画面
├── edit.blade.php           # 編集画面
├── partials/
│   ├── exterior-tab.blade.php      # 外装タブ内容
│   ├── interior-tab.blade.php      # 内装タブ内容
│   ├── other-tab.blade.php         # その他タブ内容
│   └── repair-history-table.blade.php  # 共通テーブルコンポーネント
```

#### メインビュー (index.blade.php)
```blade
<!-- 修繕履歴サブタブ -->
<div class="repair-history-container">
    <!-- サブタブナビゲーション -->
    <ul class="nav nav-tabs repair-history-subtabs mb-4" id="repairHistoryTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="exterior-tab" data-bs-toggle="tab" 
                    data-bs-target="#exterior" type="button" role="tab">
                <i class="fas fa-building me-2"></i>外装
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="interior-tab" data-bs-toggle="tab" 
                    data-bs-target="#interior" type="button" role="tab">
                <i class="fas fa-paint-brush me-2"></i>内装リニューアル
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="other-tab" data-bs-toggle="tab" 
                    data-bs-target="#other" type="button" role="tab">
                <i class="fas fa-tools me-2"></i>その他
            </button>
        </li>
    </ul>

    <!-- サブタブコンテンツ -->
    <div class="tab-content" id="repairHistoryTabContent">
        <!-- 外装タブ -->
        <div class="tab-pane fade show active" id="exterior" role="tabpanel">
            @include('facilities.repair-history.partials.exterior-tab')
        </div>

        <!-- 内装リニューアルタブ -->
        <div class="tab-pane fade" id="interior" role="tabpanel">
            @include('facilities.repair-history.partials.interior-tab')
        </div>

        <!-- その他タブ -->
        <div class="tab-pane fade" id="other" role="tabpanel">
            @include('facilities.repair-history.partials.other-tab')
        </div>
    </div>
</div>
```

## データモデル

### MaintenanceHistory テーブル構造
```sql
CREATE TABLE maintenance_histories (
    id BIGINT UNSIGNED PRIMARY KEY,
    facility_id BIGINT UNSIGNED NOT NULL,
    maintenance_date DATE NOT NULL,
    content TEXT NOT NULL,
    cost DECIMAL(10,2) NULL,
    contractor VARCHAR(255) NULL,
    category VARCHAR(50) DEFAULT 'other',
    subcategory VARCHAR(50) NULL,
    contact_person VARCHAR(255) NULL,
    phone_number VARCHAR(20) NULL,
    classification VARCHAR(100) NULL,
    notes TEXT NULL,
    warranty_period_years INT NULL,
    warranty_start_date DATE NULL,
    warranty_end_date DATE NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_facility_category (facility_id, category),
    INDEX idx_maintenance_date (maintenance_date),
    INDEX idx_category_subcategory (category, subcategory)
);
```

### データ分類
```php
// カテゴリ分類
'exterior' => [
    'waterproof' => '防水',
    'painting' => '塗装'
]

'interior' => [
    'renovation' => '内装リニューアル',
    'design' => '内装・意匠'
]

'other' => [
    'renovation_work' => '改修工事'
]
```

## エラーハンドリング

### バリデーションエラー
- 必須フィールドの未入力
- 日付形式の不正
- 数値フィールドの形式エラー
- 文字数制限超過

### 権限エラー
- 閲覧権限なし (403)
- 編集権限なし (403)
- 施設アクセス権限なし (404)

### データエラー
- 施設が存在しない (404)
- 修繕履歴データが存在しない (404)
- データベース接続エラー (500)

## テスト戦略

### 単体テスト
- MaintenanceHistoryモデルのメソッドテスト
- RepairHistoryControllerのアクションテスト
- バリデーションルールのテスト

### 機能テスト
- 修繕履歴表示機能のテスト
- 編集機能のテスト
- 権限チェックのテスト
- タブ切り替え機能のテスト

### 統合テスト
- 施設詳細画面での修繕履歴タブ表示
- データの作成・更新・削除の一連の流れ
- 複数ユーザーでの権限テスト

## セキュリティ考慮事項

### 認証・認可
- 既存の施設管理権限システムとの統合
- ポリシーベースの認可チェック
- CSRF保護の実装

### データ保護
- SQLインジェクション対策
- XSS対策
- 入力データのサニタイゼーション

### ログ・監査
- 修繕履歴の作成・更新・削除のログ記録
- ユーザーアクションの追跡
- エラーログの適切な記録

## パフォーマンス考慮事項

### データベース最適化
- 適切なインデックスの設定
- N+1問題の回避
- ページネーション実装（必要に応じて）

### フロントエンド最適化
- 遅延読み込みの実装
- キャッシュ戦略
- レスポンシブデザインの最適化

## 実装フェーズ

### フェーズ1: データベース拡張
1. マイグレーションファイル作成
2. シーダーファイル作成
3. モデル拡張

### フェーズ2: バックエンド実装
1. コントローラー作成
2. ルート定義
3. ポリシー実装

### フェーズ3: フロントエンド実装
1. ビューファイル作成
2. CSS/JavaScript実装
3. レスポンシブ対応

### フェーズ4: テスト・統合
1. 単体テストの実装
2. 機能テストの実装
3. 統合テスト・デバッグ
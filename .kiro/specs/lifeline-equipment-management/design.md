# Design Document

## Overview

ライフライン・設備管理機能は、既存のShise-Cal施設管理システムの施設詳細画面に新しいタブとして統合されます。既存のタブ（基本、土地、建物）に「ライフライン設備」タブを追加し、既存のUIパターン、デザインシステム、権限管理を完全に活用します。

**設計原則:**
- 既存のUIパターンとの完全な統合
- ユーザーの学習コストを最小化
- 一貫したユーザーエクスペリエンス
- 既存のコメント機能、権限管理、アクティビティログとの統合

**カテゴリ構成:**
- **電気**: 基本情報、PAS、キュービクル、非常用発電機、備考（詳細仕様定義済み）
- **ガス**: 基本的なカード構造（カード内容は開発中）
- **水道**: 基本的なカード構造（カード内容は開発中）
- **エレベーター**: 基本的なカード構造（カード内容は開発中）
- **空調・照明**: 基本的なカード構造（カード内容は開発中）

## Architecture

### UI Integration Pattern
既存の施設詳細画面（`resources/views/facilities/show.blade.php`）に統合：

```
施設詳細画面
├── 既存タブナビゲーション (nav nav-tabs)
│   ├── 基本タブ
│   ├── 土地タブ  
│   ├── 建物タブ
│   └── ライフライン設備タブ ← 新規追加
│       └── サブタブナビゲーション (nav nav-tabs)
│           ├── 電気タブ
│           ├── ガスタブ
│           ├── 水道タブ
│           ├── エレベータータブ
│           └── 空調・照明タブ
```

### Data Model Integration
既存のFacilityモデルとの関係：

```
Facility (1) -----> (1) LifelineEquipment
                    |
                    +---> (1) ElectricalEquipment (詳細実装済み)
                    +---> (1) GasEquipment (基本構造のみ)
                    +---> (1) WaterEquipment (基本構造のみ)
                    +---> (1) ElevatorEquipment (基本構造のみ)
                    +---> (1) HvacLightingEquipment (基本構造のみ)
```

### MVC Architecture
既存のLaravelパターンに従った実装：

**Controllers**: 
- 既存の `FacilityController` に統合
- `LifelineEquipmentController` (API エンドポイント用)

**Views**: 
- `resources/views/facilities/show.blade.php` に統合
- `resources/views/facilities/lifeline-equipment/` (パーシャルビュー)

### Database Schema
既存の施設管理データベースに新しいテーブルを追加：

```sql
-- メインライフライン設備テーブル
lifeline_equipment (
    id, facility_id, category, status, created_at, updated_at, 
    created_by, updated_by, approved_by, approved_at
)

-- 電気設備詳細（詳細実装済み）
electrical_equipment (
    id, lifeline_equipment_id, basic_info, pas_info, cubicle_info, 
    generator_info, notes, created_at, updated_at
)

-- 他のカテゴリ（基本構造のみ、詳細は開発中）
-- gas_equipment, water_equipment, elevator_equipment, hvac_lighting_equipment
-- 各テーブルの詳細フィールドは後で定義
```

## Components and Interfaces

### UI Component Integration

#### 1. 既存タブシステムへの統合
```blade
<!-- resources/views/facilities/show.blade.php への追加 -->
<li class="nav-item" role="presentation">
    <button class="nav-link" id="lifeline-tab" data-bs-toggle="tab" 
            data-bs-target="#lifeline-equipment" type="button" role="tab">
        <i class="fas fa-plug me-2"></i>ライフライン設備
    </button>
</li>
```

#### 2. ライフライン設備タブコンテンツ
```blade
<!-- resources/views/facilities/lifeline-equipment/index.blade.php -->
<div class="tab-pane fade" id="lifeline-equipment" role="tabpanel">
    <!-- サブタブナビゲーション (既存パターン使用) -->
    <ul class="nav nav-tabs" id="lifelineSubTabs">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" 
                    data-bs-target="#electrical">電気</button>
        </li>
        <!-- 他のサブタブ... -->
    </ul>
    
    <!-- サブタブコンテンツ -->
    <div class="tab-content">
        <div class="tab-pane fade show active" id="electrical">
            @include('facilities.lifeline-equipment.electrical')
        </div>
        <!-- 他のカテゴリ（基本構造のみ）... -->
    </div>
</div>
```

#### 3. 電気設備カード（既存パターン使用）
```blade
<!-- resources/views/facilities/lifeline-equipment/electrical.blade.php -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle text-primary me-2"></i>基本情報
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle">
                    <i class="fas fa-comment"></i>
                    <span class="comment-count">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <!-- 既存パターンの detail-row 使用 -->
                </div>
            </div>
        </div>
    </div>
    <!-- 他のカード... -->
</div>
```

### Backend Integration

#### 1. 既存FacilityControllerへの統合
```php
// app/Http/Controllers/FacilityController.php への追加
class FacilityController extends Controller
{
    public function show(Facility $facility)
    {
        // 既存のロジック...
        
        // ライフライン設備データの読み込み
        $lifelineEquipment = $facility->lifelineEquipment;
        $electricalEquipment = $lifelineEquipment?->electricalEquipment;
        
        return view('facilities.show', compact(
            'facility', 'lifelineEquipment', 'electricalEquipment'
            // 他の既存変数...
        ));
    }
}
```

#### 2. LifelineEquipmentController (API用)
```php
class LifelineEquipmentController extends Controller
{
    public function show(Facility $facility, $category)
    {
        // 既存の権限チェックパターンを使用
        $this->authorize('view', $facility);
        
        // カテゴリ別設備情報取得
    }
    
    public function update(Request $request, Facility $facility, $category)
    {
        // 既存の権限チェックパターンを使用
        $this->authorize('update', $facility);
        
        // 既存のアクティビティログパターンを使用
        activity()->performedOn($facility)->log('lifeline_equipment_updated');
    }
}
```

### API Endpoints

#### RESTful API Design (既存パターンに従う)
```
GET    /facilities/{facility}/lifeline-equipment/{category}         # カテゴリ別設備情報取得
PUT    /facilities/{facility}/lifeline-equipment/{category}         # 設備情報更新
```

#### Route Definition
```php
// routes/web.php への追加
Route::middleware(['auth'])->group(function () {
    Route::get('/facilities/{facility}/lifeline-equipment/{category}', 
        [LifelineEquipmentController::class, 'show'])
        ->name('facilities.lifeline-equipment.show');
    
    Route::put('/facilities/{facility}/lifeline-equipment/{category}', 
        [LifelineEquipmentController::class, 'update'])
        ->name('facilities.lifeline-equipment.update');
});
```

## Data Models

### LifelineEquipment Model
```php
class LifelineEquipment extends Model
{
    protected $fillable = [
        'facility_id', 'category', 'status', 'created_by', 
        'updated_by', 'approved_by', 'approved_at'
    ];
    
    protected $casts = [
        'approved_at' => 'datetime'
    ];
    
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
    
    public function electricalEquipment()
    {
        return $this->hasOne(ElectricalEquipment::class);
    }
    
    // 他の設備タイプとのリレーション...
}
```

### ElectricalEquipment Model
```php
class ElectricalEquipment extends Model
{
    protected $fillable = [
        'lifeline_equipment_id', 'basic_info', 'pas_info', 
        'cubicle_info', 'generator_info', 'notes'
    ];
    
    protected $casts = [
        'basic_info' => 'array',
        'pas_info' => 'array',
        'cubicle_info' => 'array',
        'generator_info' => 'array'
    ];
    
    public function lifelineEquipment()
    {
        return $this->belongsTo(LifelineEquipment::class);
    }
}
```

### Data Structure Examples

#### 電気設備データ構造
```json
{
  "basic_info": {
    "electrical_contractor": "東京電力",
    "safety_management_company": "○○保安管理株式会社",
    "maintenance_inspection_date": "2024-03-15",
    "inspection_report_pdf": "electrical_inspection_report_2024.pdf"
  },
  "pas_info": {
    "availability": "有",
    "update_date": "2023-09-15"
  },
  "cubicle_info": {
    "availability": "有",
    "equipment_list": [
      {
        "equipment_number": "1",
        "manufacturer": "三菱電機",
        "model_year": "2020",
        "update_date": "2024-03-15"
      },
      {
        "equipment_number": "2", 
        "manufacturer": "東芝",
        "model_year": "2019",
        "update_date": "2024-03-15"
      }
    ]
  },
  "generator_info": {
    "availability": "有",
    "availability_details": "",
    "equipment_list": [
      {
        "equipment_number": "1",
        "manufacturer": "ヤンマー",
        "model_year": "2021",
        "update_date": "2024-03-15"
      },
      {
        "equipment_number": "2",
        "manufacturer": "デンヨー", 
        "model_year": "2020",
        "update_date": "2024-03-15"
      }
    ]
  },
  "notes": "特記事項なし"
}
```

## Error Handling

### Validation Rules
各設備カテゴリとカードタイプに応じた専用バリデーションルール：

```php
class LifelineEquipmentValidationService
{
    public function getValidationRules(string $category, string $cardType): array
    {
        return match($category) {
            'electrical' => $this->getElectricalValidationRules($cardType),
            'gas' => $this->getGasValidationRules($cardType),
            'water' => $this->getWaterValidationRules($cardType),
            'elevator' => $this->getElevatorValidationRules($cardType),
            'hvac_lighting' => $this->getHvacLightingValidationRules($cardType),
            default => []
        };
    }
}
```

### Error Response Format
```json
{
  "success": false,
  "message": "入力内容に誤りがあります",
  "errors": {
    "basic_info.capacity_kw": ["容量は数値で入力してください"],
    "pas_info.control_panels": ["制御盤情報は必須です"]
  },
  "category": "electrical",
  "card_type": "basic_info"
}
```

## Testing Strategy

### Unit Tests
- モデルのリレーションシップテスト
- バリデーションルールテスト
- サービスクラスのビジネスロジックテスト

### Feature Tests
- API エンドポイントテスト
- 権限管理テスト
- データ整合性テスト

### Integration Tests
- フロントエンド・バックエンド統合テスト
- 既存施設管理システムとの統合テスト

### Browser Tests
- タブナビゲーション機能テスト
- カード編集・保存機能テスト
- レスポンシブデザインテスト

### Test Structure
```
tests/
├── Unit/
│   ├── Models/
│   │   ├── LifelineEquipmentTest.php
│   │   ├── ElectricalEquipmentTest.php
│   │   └── ...
│   └── Services/
│       └── LifelineEquipmentServiceTest.php
├── Feature/
│   ├── LifelineEquipmentControllerTest.php
│   ├── LifelineEquipmentApiTest.php
│   └── LifelineEquipmentAuthorizationTest.php
└── Browser/
    └── LifelineEquipmentBrowserTest.php
```

## Security Considerations

### Authorization
既存の施設管理権限システムを拡張：

```php
class LifelineEquipmentPolicy
{
    public function view(User $user, Facility $facility): bool
    {
        return $user->canViewFacility($facility);
    }
    
    public function update(User $user, Facility $facility): bool
    {
        return $user->canEditFacility($facility);
    }
    
    public function approve(User $user, Facility $facility): bool
    {
        return $user->canApproveFacilityChanges($facility);
    }
}
```

### Data Protection
- 設備情報の暗号化（機密性の高いデータ）
- アクセスログの記録
- データ変更履歴の追跡

### Input Sanitization
- XSS攻撃防止
- SQLインジェクション防止
- ファイルアップロード制限（将来の拡張用）

## Performance Optimization

### Database Optimization
- 適切なインデックス設定
- N+1クエリ問題の回避
- データベース正規化

### Caching Strategy
- 設備情報のキャッシュ
- APIレスポンスキャッシュ
- 静的アセットキャッシュ

### Frontend Optimization
- 遅延読み込み（Lazy Loading）
- コンポーネントの再利用
- バンドルサイズの最適化

## Integration Points

### Existing System Integration
1. **Facility Model**: 既存のFacilityモデルとのリレーション
2. **User Permissions**: 既存の権限システムの拡張
3. **Activity Logging**: 既存のアクティビティログシステムとの統合
4. **Comment System**: 既存のコメントシステムとの統合
5. **Export System**: 既存のエクスポート機能への設備情報追加

### Future Extensions
- メンテナンススケジュール自動化
- IoTセンサーデータ統合
- 予防保全アラートシステム
- エネルギー効率分析機能
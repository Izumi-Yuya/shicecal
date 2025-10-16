# 契約書管理システム実装ガイドライン

統一された契約書管理システムの実装と運用に関する包括的なガイドライン

このドキュメントは、施設管理システムにおける契約書管理機能の標準化された実装方法を定義します。

## 基本原則
- **統一性**: ContractServiceを使用して一貫した契約書処理を実装
- **セキュリティファースト**: すべての契約書操作において認証・認可チェックを実装
- **バリデーションは必須**: 契約書データの検証を必ず実行
- **エラーハンドリング**: 適切なエラーメッセージとログ出力の実装
- **アクティビティログ**: すべての契約書操作をActivityLogServiceで記録
- **ポリシーベース認可**: ContractPolicyを使用した権限管理

## 統一された契約書処理システム

### ContractServiceの使用
すべての契約書処理はContractServiceを通して行う：

```php
// サービス層での使用例
public function __construct(ContractService $contractService)
{
    $this->contractService = $contractService;
}

// 契約書データ取得
$contract = $this->contractService->getContract($facility);

// 契約書データ更新
$contract = $this->contractService->createOrUpdateContract($facility, $data, $user);

// 表示用データ整形
$contractsData = $this->contractService->formatContractDataForDisplay($contract);
```

### 対応機能
- **その他契約書**: 保守契約、清掃契約、警備契約等の管理
- **給食契約書**: 給食サービス契約の管理（今後実装）
- **駐車場契約書**: 駐車場契約の管理（今後実装）

### データベース構造
- **テーブル**: `facility_contracts`
- **リレーション**: 1つの施設につき1つの契約書レコード
- **その他契約書**: 個別カラム（`others_*`）で詳細管理
- **給食契約書**: 個別カラム（`meal_service_*`）で詳細管理
- **駐車場契約書**: 個別カラム（`parking_*`）で詳細管理

## コントローラー実装パターン

### 1. 基本構造
```php
class ContractsController extends Controller
{
    protected ContractService $contractService;
    protected ActivityLogService $activityLogService;

    public function __construct(ContractService $contractService, ActivityLogService $activityLogService)
    {
        $this->contractService = $contractService;
        $this->activityLogService = $activityLogService;
    }
}
```

### 2. 編集画面表示
```php
public function edit(Facility $facility)
{
    try {
        // ポリシーベース認可
        $this->authorize('update', [FacilityContract::class, $facility]);

        // サービス経由でデータ取得
        $contract = $this->contractService->getContract($facility);
        $contractsData = [];
        
        if ($contract) {
            $contractsData = $this->contractService->formatContractDataForDisplay($contract);
        }

        return view('facilities.contracts.edit', compact('facility', 'contractsData'));

    } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
        return redirect()->route('facilities.show', $facility)
            ->with('error', 'この施設の契約書を編集する権限がありません。');
    }
}
```

### 3. データ更新処理
```php
public function update(ContractRequest $request, Facility $facility)
{
    try {
        // ポリシーベース認可
        $this->authorize('update', [FacilityContract::class, $facility]);

        $validated = $request->validated();
        $user = auth()->user();

        // サービス経由でデータ更新
        $contract = $this->contractService->createOrUpdateContract($facility, $validated, $user);

        // アクティビティログ記録
        $this->activityLogService->logFacilityUpdated(
            $facility->id,
            $facility->facility_name . ' - 契約書',
            $request
        );

        // JSON/HTML両対応のレスポンス
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => '契約書を更新しました。',
                'contract' => $contract,
            ]);
        }

        return redirect()
            ->route('facilities.show', $facility)
            ->with('success', '契約書を更新しました。')
            ->with('activeTab', 'contracts')
            ->with('activeSubTab', $request->input('active_sub_tab', 'others'));

    } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
        // 認可エラー処理
    } catch (\Exception $e) {
        // システムエラー処理
    }
}
```

## バリデーション実装

### ContractRequestの使用
```php
class ContractRequest extends FormRequest
{
    public function authorize()
    {
        return true; // ポリシーで認可チェック
    }

    public function rules()
    {
        return [
            // その他契約書
            'others.company_name' => ['nullable', 'string', 'max:255'],
            'others.contract_type' => ['nullable', 'string', 'max:255'],
            'others.contract_content' => ['nullable', 'string', 'max:2000'],
            'others.auto_renewal' => ['nullable', 'string', 'in:あり,なし,条件付き'],
            'others.contract_start_date' => ['nullable', 'date'],
            'others.contract_end_date' => ['nullable', 'date', 'after_or_equal:others.contract_start_date'],
            'others.amount' => ['nullable', 'integer', 'min:0'],
            // その他のフィールド...
        ];
    }

    public function messages()
    {
        return [
            'others.company_name.max' => '会社名は255文字以内で入力してください。',
            'others.contract_end_date.after_or_equal' => '契約終了日は契約開始日以降の日付を入力してください。',
            // その他のメッセージ...
        ];
    }
}
```

## ポリシー実装

### ContractPolicyの使用
```php
class ContractPolicy
{
    public function view(User $user, $facility): bool
    {
        return $user->canViewFacility($facility->id ?? $facility);
    }

    public function update(User $user, $facility): bool
    {
        return $user->canEditFacility($facility->id ?? $facility);
    }

    public function create(User $user, $facility): bool
    {
        return $user->canEditFacility($facility->id ?? $facility);
    }
}
```

## モデル実装

### FacilityContractモデル
```php
class FacilityContract extends Model
{
    protected $fillable = [
        'facility_id',
        'others_company_name',
        'others_contract_type',
        // その他のフィールド...
    ];

    protected $casts = [
        'others_contract_start_date' => 'date',
        'others_contract_end_date' => 'date',
        'others_amount' => 'integer',
        'meal_service_contract_start_date' => 'date',
        'meal_service_management_fee' => 'integer',
        'meal_service_breakfast_price' => 'integer',
        'meal_service_lunch_price' => 'integer',
        'meal_service_dinner_price' => 'integer',
        'meal_service_snack_price' => 'integer',
        'meal_service_event_meal_price' => 'integer',
        'meal_service_staff_meal_price' => 'integer',
        'parking_contract_start_date' => 'date',
        'parking_contract_end_date' => 'date',
        'parking_spaces' => 'integer',
        'parking_price_per_space' => 'integer',
    ];

    // アクセサー
    public function getOthersDataAttribute(): array
    {
        return [
            'company_name' => $this->others_company_name,
            'contract_type' => $this->others_contract_type,
            // その他のフィールド...
        ];
    }

    // 更新メソッド
    public function updateOthersData(array $data): void
    {
        $this->update([
            'others_company_name' => $data['company_name'] ?? null,
            'others_contract_type' => $data['contract_type'] ?? null,
            // その他のフィールド...
        ]);
    }
}
```

## ビュー実装

### 表示画面での使用
```blade
@php
    $contractService = app(\App\Services\ContractService::class);
    $contract = $contractService->getContract($facility);
    $contractsData = [];
    
    if ($contract) {
        $contractsData = $contractService->formatContractDataForDisplay($contract);
    }
@endphp

@include('facilities.contracts.index', ['facility' => $facility, 'contractsData' => $contractsData])
```

### サブタブ管理
```javascript
// アクティブサブタブの追跡
const activeSubTabField = document.getElementById('activeSubTabField');

subTabs.forEach(tab => {
    tab.addEventListener('shown.bs.tab', function(event) {
        const targetId = event.target.getAttribute('data-bs-target');
        let subTabName = 'others';
        
        if (targetId === '#meal-service-edit') {
            subTabName = 'meal-service';
        } else if (targetId === '#parking-edit') {
            subTabName = 'parking';
        }
        
        if (activeSubTabField) {
            activeSubTabField.value = subTabName;
        }
    });
});
```

## エラーハンドリング

### 統一されたエラー処理
```php
try {
    // 処理実行
} catch (\Illuminate\Auth\Access\AuthorizationException $e) {
    // 認可エラー
    if ($request->expectsJson()) {
        return response()->json(['success' => false, 'message' => '権限がありません。'], 403);
    }
    return redirect()->back()->with('error', '権限がありません。');
} catch (\Illuminate\Validation\ValidationException $e) {
    // バリデーションエラー
    if ($request->expectsJson()) {
        return response()->json(['success' => false, 'errors' => $e->errors()], 422);
    }
    return back()->withErrors($e->validator)->withInput();
} catch (\Exception $e) {
    // システムエラー
    Log::error('Contract operation failed', ['error' => $e->getMessage()]);
    if ($request->expectsJson()) {
        return response()->json(['success' => false, 'message' => 'システムエラー'], 500);
    }
    return back()->with('error', 'システムエラーが発生しました。');
}
```

## テスト実装

### 機能テスト例
```php
/** @test */
public function user_can_update_contract_information()
{
    $facility = Facility::factory()->create();
    $user = User::factory()->create();
    
    $this->actingAs($user);
    
    $response = $this->put(route('facilities.contracts.update', $facility), [
        'others' => [
            'company_name' => 'テスト会社',
            'contract_type' => '保守契約',
            'contract_content' => 'テスト契約内容',
        ],
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHas('success');
    
    $this->assertDatabaseHas('facility_contracts', [
        'facility_id' => $facility->id,
        'others_company_name' => 'テスト会社',
    ]);
}
```

## 共通エラーパターンと対処法

### 1. 認可エラー
- **原因**: ユーザーに適切な権限がない
- **対処**: ポリシーの確認とユーザー権限の検証

### 2. バリデーションエラー
- **原因**: 入力データが検証ルールに適合しない
- **対処**: ContractRequestのルール確認と適切なエラーメッセージ表示

### 3. データベースエラー
- **原因**: データベース接続問題、制約違反
- **対処**: ログ確認とデータベース状態の検証

## 実装チェックリスト

### コントローラー
- [ ] ContractServiceの依存性注入
- [ ] ActivityLogServiceの依存性注入
- [ ] ポリシーベース認可の実装
- [ ] JSON/HTMLレスポンス対応
- [ ] 適切なエラーハンドリング
- [ ] アクティビティログの記録

### サービス
- [ ] 統一されたデータ取得メソッド
- [ ] トランザクション処理
- [ ] 適切なログ出力
- [ ] データ整形メソッド

### モデル
- [ ] 適切なfillableプロパティ
- [ ] キャスト設定
- [ ] アクセサーメソッド
- [ ] 更新メソッド

### ビュー
- [ ] サービス経由でのデータ取得
- [ ] 統一されたコンポーネント使用
- [ ] サブタブ管理JavaScript
- [ ] エラー表示処理

### テスト
- [ ] 機能テストの実装
- [ ] 認可テスト
- [ ] バリデーションテスト
- [ ] エラーハンドリングテスト

このガイドラインに従うことで、セキュアで保守性の高い契約書管理機能を実装できます。
# ファイルアップロード・ダウンロード実装ガイドライン

統一されたファイル処理システムの実装と運用に関する包括的なガイドライン

このドキュメントは、施設管理システムにおけるファイル処理機能の標準化された実装方法を定義します。

## 基本原則
- **統一性**: FileHandlingServiceを使用して一貫したファイル処理を実装
- **セキュリティファースト**: すべてのファイル操作において認証・認可チェックを実装
- **バリデーションは必須**: ファイルタイプ、サイズ、拡張子の検証を必ず実行
- **エラーハンドリング**: 適切なエラーメッセージとログ出力の実装
- **ファイル名の一意性保証**: ファイル名の重複を避けるため、タイムスタンプを付与
- **コンポーネント活用**: 統一されたBladeコンポーネントを使用

## 統一されたファイル処理システム

### FileHandlingServiceの使用
すべてのファイル処理はFileHandlingServiceを通して行う：

```php
// サービス層での使用例
public function __construct(FileHandlingService $fileHandlingService)
{
    $this->fileHandlingService = $fileHandlingService;
}

// ファイルアップロード
$result = $this->fileHandlingService->uploadFile($file, $directory, 'pdf');

// ファイルダウンロード
return $this->fileHandlingService->downloadFile($path, $filename);

// ファイル表示データ生成
$fileData = $this->fileHandlingService->generateFileDisplayData($data, $category, $facility);
```

### 対応機能
- **ライフライン設備**: 点検報告書PDFのアップロード・ダウンロード
  - 電気設備: 点検報告書PDF
  - ガス設備: 点検報告書PDF
  - 水道設備: 点検報告書PDF
  - エレベーター設備: 点検報告書PDF
  - 空調・照明設備: 点検報告書PDF
- **土地情報**: 賃貸借契約書・覚書PDF、謄本PDFのアップロード・ダウンロード

### 統一されたBladeコンポーネント

#### ファイル表示コンポーネント
```blade
<x-file-display 
    :fileData="$fileData"
    label="ファイル名"
    :showLabel="true"
    downloadText="ダウンロード"
    size="sm"
    style="button"
/>
```

#### ファイルアップロードコンポーネント
```blade
<x-file-upload 
    name="file_field"
    label="ファイル"
    fileType="pdf"
    :currentFile="$currentFileData"
    :required="false"
    :showRemoveOption="true"
    removeFieldName="delete_file_field"
/>
```

## ファイルアップロード実装パターン

### 1. コントローラーでの基本処理
```php
public function update(Request $request, Facility $facility, string $category)
{
    try {
        // 認証・認可チェック
        $this->authorize('update', [LifelineEquipment::class, $facility]);
        
        // サービス層に処理を委譲
        $result = $this->lifelineEquipmentService->updateEquipmentData(
            $facility,
            $category,
            $request->all(),
            auth()->id()
        );
        
        // レスポンス処理
        if (!$result['success']) {
            return back()->withErrors($result['errors'])->withInput();
        }
        
        return redirect()->back()->with('success', 'ファイルのアップロードが完了しました。');
        
    } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
        abort(403, 'ファイルをアップロードする権限がありません。');
    }
}
```

### 2. サービス層でのファイル処理

#### 統一されたファイルアップロード処理
```php
// FileHandlingServiceを使用した統一処理
private function handleFileUpload(UploadedFile $file, string $directory): ?array
{
    try {
        $result = $this->fileHandlingService->uploadFile($file, $directory, 'pdf');
        
        return [
            'filename' => $result['filename'],
            'path' => $result['path'],
            'stored_filename' => $result['stored_filename'],
        ];
    } catch (Exception $e) {
        Log::error('File upload failed', [
            'error' => $e->getMessage(),
            'file_name' => $file->getClientOriginalName(),
            'directory' => $directory,
        ]);
        
        throw new Exception('ファイルのアップロードに失敗しました: ' . $e->getMessage());
    }
}
```

#### ライフライン設備での実装例
```php
// 電気設備のファイル処理
private function processBasicInfo(array $basicInfo, array $allData = [], array $existingData = []): array
{
    $processedData = [
        'electrical_contractor' => trim($basicInfo['electrical_contractor'] ?? ''),
        'safety_management_company' => trim($basicInfo['safety_management_company'] ?? ''),
        'maintenance_inspection_date' => $basicInfo['maintenance_inspection_date'] ?? null,
    ];

    // ファイルアップロード処理
    if (isset($allData['inspection_report_file']) && $allData['inspection_report_file'] instanceof UploadedFile) {
        // 既存ファイル削除
        if (isset($existingData['inspection']['inspection_report_pdf_path'])) {
            $this->fileHandlingService->deleteFile($existingData['inspection']['inspection_report_pdf_path']);
        }
        
        $uploadResult = $this->handleFileUpload($allData['inspection_report_file'], 'electrical/inspection-reports');
        if ($uploadResult) {
            $processedData['inspection'] = [
                'inspection_report_pdf' => $uploadResult['filename'],
                'inspection_report_pdf_path' => $uploadResult['path'],
            ];
        }
    } elseif (isset($existingData['inspection'])) {
        // 既存ファイル情報を保持
        $processedData['inspection'] = $existingData['inspection'];
    }

    // ファイル削除処理
    if (isset($allData['remove_inspection_report']) && $allData['remove_inspection_report'] === '1') {
        if (isset($existingData['inspection']['inspection_report_pdf_path'])) {
            $this->fileHandlingService->deleteFile($existingData['inspection']['inspection_report_pdf_path']);
        }
        $processedData['inspection'] = [
            'inspection_report_pdf' => null,
            'inspection_report_pdf_path' => null,
        ];
    }

    return $processedData;
}
```

### 3. フォームでのファイル入力
```html
<form method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
    <div class="mb-3">
        <label for="inspection_report_pdf_file" class="form-label">点検報告書PDF</label>
        <input type="file" 
               class="form-control @error('basic_info.inspection_report_pdf_file') is-invalid @enderror" 
               id="inspection_report_pdf_file" 
               name="basic_info[inspection_report_pdf_file]"
               accept=".pdf">
        @error('basic_info.inspection_report_pdf_file')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <button type="submit" class="btn btn-primary">アップロード</button>
</form>
```

## ファイルダウンロード実装パターン

### 1. ダウンロードコントローラーメソッド
```php
public function downloadFile(Facility $facility, string $category, string $type)
{
    try {
        // 認証・認可チェック
        $this->authorize('view', [LifelineEquipment::class, $facility]);

        // カテゴリ検証
        $normalizedCategory = str_replace('-', '_', $category);
        if (!array_key_exists($normalizedCategory, LifelineEquipment::CATEGORIES)) {
            abort(404, 'Invalid equipment category');
        }

        // 設備データ取得
        $lifelineEquipment = $facility->getLifelineEquipmentByCategory($normalizedCategory);
        if (!$lifelineEquipment) {
            abort(404, 'Equipment not found');
        }

        // カテゴリ別の設備データ取得
        $equipmentData = $this->getEquipmentDataByCategory($lifelineEquipment, $normalizedCategory);
        if (!$equipmentData) {
            abort(404, 'Equipment data not found');
        }

        // ファイルパスとファイル名の取得
        $filePath = null;
        $fileName = null;
        $basicInfo = $equipmentData->basic_info ?? [];

        switch ($type) {
            case 'inspection_report':
                $filePath = $basicInfo['inspection']['inspection_report_pdf_path'] ?? null;
                $fileName = $basicInfo['inspection']['inspection_report_pdf'] ?? null;
                break;
            case 'hvac_inspection_report':
                $filePath = $basicInfo['hvac']['inspection_report_path'] ?? null;
                $fileName = $basicInfo['hvac']['inspection_report_filename'] ?? null;
                break;
            default:
                abort(404, '指定されたファイルタイプが無効です。');
        }

        if (!$filePath) {
            abort(404, 'ファイルが見つかりません。');
        }

        // FileHandlingServiceを使用してダウンロード
        return $this->fileHandlingService->downloadFile($filePath, $fileName);

    } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
        abort(403, 'この施設のファイルをダウンロードする権限がありません。');
    } catch (Exception $e) {
        Log::error('File download failed', [
            'facility_id' => $facility->id,
            'category' => $category,
            'type' => $type,
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
        ]);

        abort(500, 'ファイルのダウンロードに失敗しました。');
    }
}
```

### 2. ダウンロードリンクの表示

#### 統一されたBladeコンポーネントの使用
```blade
{{-- ファイル表示コンポーネント --}}
<x-file-display 
    :fileData="$inspectionReportFileData"
    label="点検報告書"
    :showLabel="true"
    downloadText="ダウンロード"
    size="sm"
    style="button"
/>
```

#### 従来の直接リンク表示
```blade
@if(!empty($basicInfo['inspection']['inspection_report_pdf']))
    <div class="mb-3">
        <label class="form-label">現在のファイル</label>
        <div>
            <a href="{{ route('facilities.lifeline-equipment.download-file', [$facility, $category, 'inspection_report']) }}" 
               class="btn btn-outline-primary btn-sm">
                <i class="fas fa-download"></i> {{ $basicInfo['inspection']['inspection_report_pdf'] }}
            </a>
        </div>
    </div>
@endif
```

## バリデーションルール

### 1. リクエストバリデーション
```php
// コントローラーまたはFormRequestクラスで
$rules = [
    'basic_info.inspection_report_pdf_file' => [
        'nullable',
        'file',
        'mimes:pdf',
        'max:10240', // 10MB in KB
    ],
];

$messages = [
    'basic_info.inspection_report_pdf_file.mimes' => 'PDFファイルのみアップロード可能です。',
    'basic_info.inspection_report_pdf_file.max' => 'ファイルサイズは10MB以下にしてください。',
];
```

### 2. サービス層での追加検証
```php
// MIMEタイプの厳密チェック
if (!in_array($file->getClientMimeType(), ['application/pdf'])) {
    throw new Exception('PDFファイルのみアップロード可能です。');
}

// ファイル拡張子チェック
$allowedExtensions = ['pdf'];
if (!in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
    throw new Exception('許可されていないファイル形式です。');
}
```

## ストレージ設定

### 1. ディスク設定 (config/filesystems.php)
```php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
        'throw' => false,
    ],
],
```

### 2. ディレクトリ構造
```
storage/app/public/
├── electrical/
│   └── inspection-reports/
├── elevator/
│   └── inspection-reports/
├── gas/
│   └── inspection-reports/
├── water/
│   └── inspection-reports/
├── hvac/
│   └── inspection-reports/
└── land_documents/
    ├── lease_contracts/
    └── registry/
```

## セキュリティ考慮事項

### 1. 認証・認可
- すべてのファイル操作で認証状態を確認
- ポリシーベースの認可チェックを実装
- ファイルアクセス権限の適切な制御

### 2. ファイル検証
- MIMEタイプとファイル拡張子の両方をチェック
- ファイルサイズ制限の実装
- 悪意のあるファイル名の無害化

### 3. エラーハンドリング
- 詳細なエラー情報の適切なログ出力
- ユーザーフレンドリーなエラーメッセージ
- セキュリティ情報の漏えい防止

## テスト実装

### 1. ファイルアップロードテスト
```php
/** @test */
public function user_can_upload_pdf_inspection_report()
{
    Storage::fake('public');
    
    $this->actingAs($this->user);
    
    $pdfFile = UploadedFile::fake()->create('inspection_report.pdf', 1024, 'application/pdf');
    
    $response = $this->put(route('facilities.lifeline-equipment.update', [$this->facility, 'electrical']), [
        'basic_info' => [
            'inspection_report_pdf_file' => $pdfFile,
        ],
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHas('success');
    
    // ファイル保存確認
    $files = Storage::disk('public')->files('electrical/inspection-reports');
    $this->assertNotEmpty($files);
}
```

### 2. ファイルダウンロードテスト
```php
/** @test */
public function user_can_download_uploaded_pdf_inspection_report()
{
    Storage::fake('public');
    
    $this->actingAs($this->user);
    
    // テストファイル作成
    $pdfContent = 'fake pdf content';
    $filename = 'test_inspection_report.pdf';
    $path = 'electrical/inspection-reports/' . $filename;
    
    Storage::disk('public')->put($path, $pdfContent);
    
    $response = $this->get(route('facilities.lifeline-equipment.download', [
        $this->facility, 
        'electrical', 
        'inspection_report.pdf'
    ]));
    
    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
}
```

## 共通エラーパターンと対処法

### 1. ファイルアップロード失敗
- **原因**: ファイルサイズ制限、MIMEタイプ不一致、権限不足
- **対処**: 適切なバリデーションとエラーメッセージの表示

### 2. ファイルダウンロード失敗
- **原因**: ファイル不存在、権限不足、パス不正
- **対処**: 存在確認と認可チェックの実装

### 3. ストレージ権限エラー
- **原因**: ディレクトリ権限、シンボリックリンク未作成
- **対処**: `php artisan storage:link` の実行

## 実装チェックリスト

### アップロード機能
- [ ] 認証・認可チェックの実装
- [ ] ファイルタイプ・サイズバリデーション
- [ ] 一意なファイル名生成
- [ ] 適切なディレクトリへの保存
- [ ] エラーハンドリングとログ出力
- [ ] テストケースの作成
- [ ] FileHandlingServiceの使用
- [ ] 統一されたファイル処理メソッドの実装

### ダウンロード機能
- [ ] 認証・認可チェックの実装
- [ ] ファイル存在確認
- [ ] 適切なレスポンスヘッダー設定
- [ ] エラーハンドリング
- [ ] セキュリティ考慮（パストラバーサル対策）
- [ ] テストケースの作成
- [ ] 統一されたダウンロードルートの使用

### データ構造の統一
- [ ] ファイル情報の一貫した保存形式
- [ ] ビューファイルでの統一されたファイル表示
- [ ] バリデーションルールの統一
- [ ] ファイル削除機能の実装

## 土地情報ファイル処理の実装例

### 1. 土地情報コントローラーでのファイル処理
```php
// FacilityController.php
private function handlePdfUploads(Request $request, LandInfo $landInfo): void
{
    try {
        // ファイル削除処理
        if ($request->input('delete_lease_contract_pdf')) {
            if ($landInfo->lease_contract_pdf_path) {
                $this->fileHandlingService->deleteFile($landInfo->lease_contract_pdf_path);
                $landInfo->update([
                    'lease_contract_pdf_path' => null,
                    'lease_contract_pdf_name' => null,
                ]);
            }
        }

        // 賃貸借契約書PDFアップロード
        if ($request->hasFile('lease_contract_pdf')) {
            $file = $request->file('lease_contract_pdf');
            
            // 既存ファイル削除
            if ($landInfo->lease_contract_pdf_path) {
                $this->fileHandlingService->deleteFile($landInfo->lease_contract_pdf_path);
            }

            // 新しいファイルアップロード
            $uploadResult = $this->fileHandlingService->uploadFile(
                $file, 
                'land_documents/lease_contracts', 
                'pdf'
            );

            if ($uploadResult['success']) {
                $landInfo->update([
                    'lease_contract_pdf_path' => $uploadResult['path'],
                    'lease_contract_pdf_name' => $uploadResult['filename'],
                ]);
            }
        }
    } catch (\Exception $e) {
        Log::error('Land info PDF upload failed', [
            'facility_id' => $landInfo->facility_id,
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
        ]);
        throw $e;
    }
}
```

### 2. 土地情報ダウンロード処理
```php
public function downloadLandInfoPdf(Facility $facility, string $type)
{
    try {
        $this->authorize('view', [LandInfo::class, $facility]);

        $landInfo = $facility->landInfo;
        if (!$landInfo) {
            abort(404, '土地情報が見つかりません。');
        }

        $filePath = null;
        $fileName = null;

        switch ($type) {
            case 'lease_contract':
                $filePath = $landInfo->lease_contract_pdf_path;
                $fileName = $landInfo->lease_contract_pdf_name;
                break;
            case 'registry':
                $filePath = $landInfo->registry_pdf_path;
                $fileName = $landInfo->registry_pdf_name;
                break;
            default:
                abort(404, '指定されたファイルタイプが無効です。');
        }

        if (!$filePath || !$this->fileHandlingService->fileExists($filePath)) {
            abort(404, 'ファイルが見つかりません。');
        }

        return $this->fileHandlingService->downloadFile($filePath, $fileName);

    } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
        abort(403, 'このファイルにアクセスする権限がありません。');
    }
}
```

### 3. 土地情報ビューでのファイル表示
```blade
{{-- 編集フォーム --}}
<x-file-upload 
    name="lease_contract_pdf"
    label="賃貸借契約書・覚書"
    fileType="pdf"
    :currentFile="$leaseContractFileData"
    :required="false"
    :showRemoveOption="true"
    removeFieldName="delete_lease_contract_pdf"
/>

<x-file-upload 
    name="registry_pdf"
    label="謄本"
    fileType="pdf"
    :currentFile="$registryFileData"
    :required="false"
    :showRemoveOption="true"
    removeFieldName="delete_registry_pdf"
/>
```

### 4. 土地情報バリデーションルール
```php
// ValidationRuleService.php
'lease_contract_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'], // 10MB
'registry_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'], // 10MB
'delete_lease_contract_pdf' => ['nullable', 'boolean'],
'delete_registry_pdf' => ['nullable', 'boolean'],
```

### 5. ValueFormatterでのfile_display対応
```php
// ValueFormatter.php
private static function formatFileDisplay($value, array $options = []): string
{
    if (self::isEmpty($value) || !is_array($value)) {
        return '未設定';
    }

    $filename = $value['filename'] ?? '';
    $downloadUrl = $options['download_url'] ?? $value['download_url'] ?? '';
    $icon = $value['icon'] ?? 'fas fa-file';
    $color = $value['color'] ?? 'text-muted';
    $exists = $value['exists'] ?? true;

    if (!$exists) {
        return sprintf(
            '<span class="text-muted"><i class="%s %s me-1"></i>%s <small>(ファイルが見つかりません)</small></span>',
            $icon, $color, htmlspecialchars($filename, ENT_QUOTES, 'UTF-8')
        );
    }

    return sprintf(
        '<a href="%s" class="text-decoration-none" target="_blank"><i class="%s %s me-1"></i>%s</a>',
        htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8'),
        $icon, $color, htmlspecialchars($filename, ENT_QUOTES, 'UTF-8')
    );
}
```

## 修正履歴と統一化

### 2024年12月 - ライフライン設備ファイル処理の統一化
- **問題**: 電気設備のファイル処理が他のカテゴリと異なる実装になっていた
- **修正内容**:
  - 電気設備の`processBasicInfo`メソッドを他のカテゴリと統一
  - `handlePdfUpload`を`handleFileUpload`に統一
  - `handleInspectionReportUpload`メソッドを削除し、統一された処理に変更
  - データ構造を`inspection`サブ配列に統一
  - バリデーションルールを新しいフィールド名に対応
  - ビューファイルを新しいデータ構造とルートに対応
  - **リダイレクト処理の修正**: 編集ページから保存後、詳細画面に遷移するように修正

### 統一されたファイル処理パターン
1. **ファイルアップロード**: `handleFileUpload`メソッドでFileHandlingServiceを使用
2. **データ構造**: `basic_info.inspection.inspection_report_pdf`形式で統一
3. **ダウンロードルート**: `facilities.lifeline-equipment.download-file`で統一
4. **バリデーション**: `inspection_report_file`フィールド名で統一
5. **ファイル削除**: `remove_inspection_report`チェックボックスで統一

このガイドラインに従うことで、セキュアで信頼性の高いファイルアップロード・ダウンロード機能を実装できます。
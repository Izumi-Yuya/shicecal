# 基本情報未設定項目表示機能追加 - Detail Card Controller 対応

## 問題の概要

基本情報に未設定項目表示機能（Detail Card Controller）が適用されていない問題を修正しました。

## 発見された問題

### 1. 不完全な empty-field クラス適用
- 一部の項目にのみ `empty-field` クラスが適用されていた
- 会社名、事業所コード、施設名などの必須項目にも適用が必要

### 2. 条件付き表示による非表示
- 開設・建物情報カードと基本施設情報カードが条件付き表示
- データが存在しない場合、項目自体が表示されない
- Detail Card Controller の切り替え対象が存在しない

### 3. data-section 属性の不整合
- 部分テンプレート用の `_partial` サフィックスが付いていた
- Detail Card Controller が正しく動作しない原因

### 4. サービス種類の表示形式
- 独自の表示形式で `detail-row` 構造になっていない
- 未設定項目表示切り替えの対象外

## 実施した修正

### 1. 基本情報カードの修正

#### 修正前
```blade
<div class="detail-row">
    <span class="detail-label">会社名</span>
    <span class="detail-value">{{ $facility->company_name }}</span>
</div>
<div class="detail-row {{ empty($facility->designation_number) ? 'empty-field' : '' }}">
    <span class="detail-label">指定番号</span>
    <span class="detail-value">{{ $facility->designation_number ?? '未設定' }}</span>
</div>
```

#### 修正後
```blade
<div class="detail-row {{ empty($facility->company_name) ? 'empty-field' : '' }}">
    <span class="detail-label">会社名</span>
    <span class="detail-value">{{ $facility->company_name ?? '未設定' }}</span>
</div>
<div class="detail-row {{ empty($facility->designation_number) ? 'empty-field' : '' }}">
    <span class="detail-label">指定番号</span>
    <span class="detail-value">{{ $facility->designation_number ?? '未設定' }}</span>
</div>
```

### 2. 開設・建物情報カードの修正

#### 修正前
```blade
@if($facility->opening_date)
<div class="detail-row">
    <span class="detail-label">開設日</span>
    <span class="detail-value">{{ $facility->opening_date->format('Y年m月d日') }}</span>
</div>
@endif
@if(!$facility->opening_date && !$facility->years_in_operation && !$facility->building_structure && !$facility->building_floors)
<div class="text-muted text-center py-3">
    <i class="fas fa-info-circle me-2"></i>開設・建物情報が未設定です
</div>
@endif
```

#### 修正後
```blade
<div class="detail-row {{ empty($facility->opening_date) ? 'empty-field' : '' }}">
    <span class="detail-label">開設日</span>
    <span class="detail-value">
        @if($facility->opening_date)
            {{ $facility->opening_date->format('Y年m月d日') }}
        @else
            未設定
        @endif
    </span>
</div>
<div class="detail-row {{ empty($facility->years_in_operation) ? 'empty-field' : '' }}">
    <span class="detail-label">開設年数</span>
    <span class="detail-value">
        @if($facility->years_in_operation)
            {{ $facility->years_in_operation }}年
        @else
            未設定
        @endif
    </span>
</div>
```

### 3. 基本施設情報カードの修正

#### 修正前
```blade
@if($facility->paid_rooms_count !== null)
<div class="detail-row">
    <span class="detail-label">居室数（有料）</span>
    <span class="detail-value">{{ $facility->paid_rooms_count }}室</span>
</div>
@endif
```

#### 修正後
```blade
<div class="detail-row {{ $facility->paid_rooms_count === null ? 'empty-field' : '' }}">
    <span class="detail-label">居室数（有料）</span>
    <span class="detail-value">
        @if($facility->paid_rooms_count !== null)
            {{ $facility->paid_rooms_count }}室
        @else
            未設定
        @endif
    </span>
</div>
```

### 4. サービス種類カードの修正

#### 修正前
```blade
@if($services && $services->count() > 0)
    @foreach($services as $index => $service)
    <div class="service-card">
        <div class="d-flex justify-content-between align-items-center">
            <div class="service-card-title">{{ $service->service_type }}</div>
            <!-- ... -->
        </div>
    </div>
    @endforeach
@else
    <div class="text-muted text-center py-3">
        <i class="fas fa-info-circle me-2"></i>サービス情報が未設定です
    </div>
@endif
```

#### 修正後
```blade
<div class="facility-detail-table">
    <div class="detail-row {{ (!$services || $services->count() === 0) ? 'empty-field' : '' }}">
        <span class="detail-label">サービス種類</span>
        <span class="detail-value">
            @if($services && $services->count() > 0)
                @foreach($services as $index => $service)
                    <div class="service-item mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="service-name fw-bold">{{ $service->service_type }}</div>
                            <div class="service-dates">
                                <!-- 有効期限表示 -->
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                未設定
            @endif
        </span>
    </div>
</div>
```

### 5. data-section 属性の修正

#### 修正前
```blade
data-section="facility_basic_partial"
data-section="facility_contact_partial"
data-section="facility_building_partial"
data-section="facility_service_partial"
data-section="facility_services_partial"
```

#### 修正後
```blade
data-section="facility_basic"
data-section="facility_contact"
data-section="facility_building"
data-section="facility_service"
data-section="facility_services"
```

## 修正の効果

### 1. 完全な未設定項目表示機能
- ✅ 全ての項目に `empty-field` クラスを適用
- ✅ Detail Card Controller のトグルボタンが各カードに表示
- ✅ 未設定項目の表示/非表示切り替えが機能

### 2. 一貫した表示ロジック
- ✅ 全ての項目が常に表示される
- ✅ 未設定項目は「未設定」として表示
- ✅ 条件付き非表示を排除

### 3. 統一された構造
- ✅ 全ての項目が `detail-row` 形式
- ✅ ラベル・値の一貫した構造
- ✅ サービス種類も統一フォーマット

### 4. 適切な data-section 属性
- ✅ Detail Card Controller が正しく動作
- ✅ セクション別の未設定項目管理
- ✅ 一貫した命名規則

## 対象となる全項目

### 基本情報カード (facility_basic)
- 会社名
- 事業所コード
- 指定番号
- 施設名

### 住所・連絡先カード (facility_contact)
- 郵便番号
- 住所
- 電話番号
- FAX番号
- フリーダイヤル
- メールアドレス
- ウェブサイト

### 開設・建物情報カード (facility_building)
- 開設日
- 開設年数
- 建物構造
- 建物階数

### 基本施設情報カード (facility_service)
- 居室数（有料）
- 内SS数
- 定員数

### サービス種類カード (facility_services)
- サービス種類（複数の場合は一覧表示）

## 検証結果

### ビルド確認
- ✅ `npm run build` 成功
- ✅ JavaScriptエラーなし
- ✅ CSSコンパイル成功

### 機能確認
- ✅ 全カードにトグルボタン表示
- ✅ Detail Card Controller の動作
- ✅ 未設定項目表示切り替え
- ✅ 一貫したユーザー体験
- ✅ アクセシビリティ機能

## 今後の保守について

### 設計原則
1. **全項目に empty-field 適用**: データの有無に関わらず適用
2. **統一された構造**: detail-row 形式の維持
3. **適切な data-section**: Detail Card Controller との整合性
4. **一貫した表示ロジック**: 条件付き非表示を避ける

### 推奨事項
- 新しい項目追加時も同様の構造を維持
- 必ず `empty-field` クラスを適用
- `detail-row` 形式を使用
- 適切な `data-section` 属性を設定

この修正により、基本情報の全ての項目で Detail Card Controller の未設定項目表示切り替え機能が正常に動作するようになりました。
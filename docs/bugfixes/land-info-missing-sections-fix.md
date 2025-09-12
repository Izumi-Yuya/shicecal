# 土地情報表示されないセクション修正 - 条件分岐問題の解決

## 問題の概要

土地情報で一部のセクションが表示されない問題を修正しました。

## 発見された問題

### 1. 条件付き表示による非表示
以下のセクションが条件付きで表示されており、データが存在しない場合に完全に非表示になっていました：

1. **管理会社情報カード**: `@if($landInfo->management_company_name)`
2. **オーナー情報カード**: `@if($landInfo->owner_name)`
3. **関連書類カード**: `@if($landInfo->lease_contract_pdf_name || $landInfo->registry_pdf_name)`
4. **備考カード**: `@if($landInfo->notes)`

### 2. Detail Card Controller との矛盾
- Detail Card Controller は未設定項目の表示/非表示を切り替える機能
- しかし、セクション自体が条件付きで非表示になっていると、切り替え対象が存在しない
- 結果として、未設定項目表示機能が正しく動作しない

### 3. 一貫性の欠如
- 基本情報や金額・契約情報は常に表示され、個別項目が「未設定」表示
- 他のセクションは条件付きで非表示
- 表示ロジックに一貫性がない

## 実施した修正

### 1. 管理会社情報カードの修正

#### 修正前
```blade
@if($landInfo->management_company_name)
<div class="col-lg-6 mb-4">
    <!-- カード内容 -->
    @if($landInfo->management_company_address)
        <!-- 住所表示 -->
    @endif
    @if($landInfo->management_company_phone)
        <!-- 電話番号表示 -->
    @endif
    <!-- 他の項目も条件付き -->
</div>
@endif
```

#### 修正後
```blade
<div class="col-lg-6 mb-4">
    <!-- カード内容 -->
    <div class="detail-row {{ empty($landInfo->management_company_name) ? 'empty-field' : '' }}">
        <span class="detail-label">会社名</span>
        <span class="detail-value">{{ $landInfo->management_company_name ?? '未設定' }}</span>
    </div>
    <div class="detail-row {{ empty($landInfo->management_company_phone) ? 'empty-field' : '' }}">
        <span class="detail-label">電話番号</span>
        <span class="detail-value">{{ $landInfo->management_company_phone ?? '未設定' }}</span>
    </div>
    <!-- 全ての項目を常に表示、未設定項目は empty-field クラス -->
</div>
```

### 2. オーナー情報カードの修正

#### 修正前
```blade
@if($landInfo->owner_name)
    <!-- オーナー情報のみ条件付き表示 -->
@endif
```

#### 修正後
```blade
<div class="col-lg-6 mb-4">
    <div class="detail-row {{ empty($landInfo->owner_name) ? 'empty-field' : '' }}">
        <span class="detail-label">オーナー名</span>
        <span class="detail-value">{{ $landInfo->owner_name ?? '未設定' }}</span>
    </div>
    <!-- 全ての項目を表示、未設定は empty-field クラス -->
</div>
```

### 3. 関連書類カードの修正

#### 修正前
```blade
@if($landInfo->lease_contract_pdf_name || $landInfo->registry_pdf_name)
    <div class="row">
        @if($landInfo->lease_contract_pdf_name)
            <!-- 契約書表示 -->
        @endif
        @if($landInfo->registry_pdf_name)
            <!-- 謄本表示 -->
        @endif
    </div>
@endif
```

#### 修正後
```blade
<div class="col-lg-12 mb-4">
    <div class="facility-detail-table">
        <div class="detail-row {{ empty($landInfo->lease_contract_pdf_name) ? 'empty-field' : '' }}">
            <span class="detail-label">賃貸借契約書・覚書</span>
            <span class="detail-value">
                @if($landInfo->lease_contract_pdf_name)
                    <!-- ファイルリンク -->
                @else
                    未設定
                @endif
            </span>
        </div>
        <div class="detail-row {{ empty($landInfo->registry_pdf_name) ? 'empty-field' : '' }}">
            <span class="detail-label">登記簿謄本</span>
            <span class="detail-value">
                @if($landInfo->registry_pdf_name)
                    <!-- ファイルリンク -->
                @else
                    未設定
                @endif
            </span>
        </div>
    </div>
</div>
```

### 4. 備考カードの修正

#### 修正前
```blade
@if($landInfo->notes)
    <div class="card-body">
        <p class="mb-0">{{ $landInfo->notes }}</p>
    </div>
@endif
```

#### 修正後
```blade
<div class="card-body">
    <div class="facility-detail-table">
        <div class="detail-row {{ empty($landInfo->notes) ? 'empty-field' : '' }}">
            <span class="detail-label">備考</span>
            <span class="detail-value">
                @if($landInfo->notes)
                    <div class="border rounded p-2 bg-light">{{ $landInfo->notes }}</div>
                @else
                    未設定
                @endif
            </span>
        </div>
    </div>
</div>
```

## 修正の効果

### 1. 一貫した表示ロジック
- ✅ 全てのセクションが常に表示される
- ✅ 未設定項目は「未設定」として表示
- ✅ `empty-field` クラスで未設定項目を識別

### 2. Detail Card Controller の正常動作
- ✅ 未設定項目表示切り替え機能が正常動作
- ✅ 全てのセクションでトグルボタンが機能
- ✅ 一貫したユーザー体験

### 3. 改善された項目構造
- ✅ 管理会社情報: 9項目すべてを表示
- ✅ オーナー情報: 9項目すべてを表示
- ✅ 関連書類: 2項目を統一フォーマットで表示
- ✅ 備考: 統一された detail-row 形式

### 4. アクセシビリティの向上
- ✅ 全ての項目が一貫したラベル・値構造
- ✅ スクリーンリーダーでの読み上げが改善
- ✅ キーボードナビゲーションが統一

## 表示される全セクション

### 基本情報カード
- 所有形態
- 敷地内駐車場台数
- 敷地面積

### 金額・契約情報カード
- 所有形態に応じた動的表示
- 購入金額、坪単価（自社の場合）
- 月額賃料、契約期間、自動更新（賃借の場合）

### 管理会社情報カード（常に表示）
- 会社名
- 郵便番号
- 住所
- 建物名
- 電話番号
- FAX番号
- メールアドレス
- URL
- 備考

### オーナー情報カード（常に表示）
- オーナー名/テナント名
- 郵便番号
- 住所
- 建物名
- 電話番号
- FAX番号
- メールアドレス
- URL
- 備考

### 関連書類カード（常に表示）
- 賃貸借契約書・覚書
- 登記簿謄本

### 備考カード（常に表示）
- 備考

## 検証結果

### ビルド確認
- ✅ `npm run build` 成功
- ✅ JavaScriptエラーなし
- ✅ CSSコンパイル成功

### 機能確認
- ✅ 全セクションの表示
- ✅ Detail Card Controller の動作
- ✅ 未設定項目表示切り替え
- ✅ トグルボタンの機能
- ✅ アクセシビリティ機能

## 今後の保守について

### 設計原則
1. **常時表示**: 全てのセクションを常に表示
2. **未設定表示**: データがない場合は「未設定」表示
3. **empty-field クラス**: 未設定項目の識別
4. **一貫した構造**: detail-row 形式の統一

### 推奨事項
- 新しい項目追加時も同様の構造を維持
- 条件付き非表示は避け、未設定表示を使用
- Detail Card Controller との整合性を保つ

この修正により、土地情報の全てのセクションが適切に表示され、Detail Card Controller の未設定項目表示切り替え機能が正常に動作するようになりました。
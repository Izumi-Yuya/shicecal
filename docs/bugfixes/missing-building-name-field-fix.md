# 住所（建物名）項目追加 - 基本情報表示の完全化

## 問題の概要

基本情報の住所・連絡先セクションで住所（建物名）項目が表示されない問題を修正しました。

## 発見された問題

### 1. 欠落した住所項目
住所・連絡先セクションで以下の項目構成になっていました：

**現在の構成**:
- 郵便番号
- 住所
- 電話番号
- FAX番号
- フリーダイヤル
- メールアドレス
- ウェブサイト

**期待される構成**:
- 郵便番号
- 住所
- **住所（建物名）** ← 欠落していた項目
- 電話番号
- FAX番号
- フリーダイヤル
- メールアドレス
- ウェブサイト

### 2. データベースフィールドとの不整合
- データベースには `building_name` フィールドが存在
- しかし、表示テンプレートでは対応する項目が欠落
- ユーザーが入力した建物名が表示されない

### 3. 他のセクションとの一貫性
土地情報では管理会社やオーナー情報で建物名項目が適切に表示されているため、基本情報でも同様の項目が必要です。

## 実施した修正

### 1. 住所（建物名）項目の追加

#### 修正前
```blade
<div class="detail-row {{ empty($facility->full_address) ? 'empty-field' : '' }}">
    <span class="detail-label">住所</span>
    <span class="detail-value">{{ $facility->full_address ?? '未設定' }}</span>
</div>
<div class="detail-row {{ empty($facility->phone_number) ? 'empty-field' : '' }}">
    <span class="detail-label">電話番号</span>
    <span class="detail-value">{{ $facility->phone_number ?? '未設定' }}</span>
</div>
```

#### 修正後
```blade
<div class="detail-row {{ empty($facility->full_address) ? 'empty-field' : '' }}">
    <span class="detail-label">住所</span>
    <span class="detail-value">{{ $facility->full_address ?? '未設定' }}</span>
</div>
<div class="detail-row {{ empty($facility->building_name) ? 'empty-field' : '' }}">
    <span class="detail-label">住所（建物名）</span>
    <span class="detail-value">{{ $facility->building_name ?? '未設定' }}</span>
</div>
<div class="detail-row {{ empty($facility->phone_number) ? 'empty-field' : '' }}">
    <span class="detail-label">電話番号</span>
    <span class="detail-value">{{ $facility->phone_number ?? '未設定' }}</span>
</div>
```

### 2. Detail Card Controller 対応

#### 追加された機能
- **empty-field クラス**: `{{ empty($facility->building_name) ? 'empty-field' : '' }}`
- **未設定表示**: `{{ $facility->building_name ?? '未設定' }}`
- **未設定項目表示切り替え**: Detail Card Controller で制御可能

#### 表示ロジック
- **建物名が設定済み**: 建物名を表示
- **建物名が未設定**: 「未設定」を表示し、empty-field クラスを適用

## 修正の効果

### 1. 完全な住所情報表示
- ✅ 郵便番号、住所、建物名の完全な住所情報
- ✅ ユーザーが入力した建物名が適切に表示
- ✅ データベースフィールドとの完全な対応

### 2. Detail Card Controller 対応
- ✅ 建物名項目も未設定項目表示切り替えの対象
- ✅ 一貫した empty-field クラスの適用
- ✅ 統一されたユーザー体験

### 3. 他のセクションとの一貫性
- ✅ 土地情報の管理会社・オーナー情報と同様の構造
- ✅ 住所項目の標準的な分割表示
- ✅ システム全体での一貫性確保

### 4. データの完全性
- ✅ 入力されたデータの完全な表示
- ✅ 情報の欠落防止
- ✅ ユーザーの期待に沿った表示

## 住所項目の完全な構成

### 基本情報 - 住所・連絡先セクション
1. **郵便番号** (`formatted_postal_code`)
2. **住所** (`full_address`)
3. **住所（建物名）** (`building_name`) ← 今回追加
4. **電話番号** (`phone_number`)
5. **FAX番号** (`fax_number`)
6. **フリーダイヤル** (`toll_free_number`)
7. **メールアドレス** (`email`)
8. **ウェブサイト** (`website_url`)

### 土地情報での類似構造（参考）
```blade
<!-- 管理会社情報 -->
<div class="detail-row">
    <span class="detail-label">住所</span>
    <span class="detail-value">{{ $landInfo->management_company_address }}</span>
</div>
<div class="detail-row">
    <span class="detail-label">建物名</span>
    <span class="detail-value">{{ $landInfo->management_company_building ?? '未設定' }}</span>
</div>
```

## 検証結果

### ビルド確認
- ✅ `npm run build` 成功
- ✅ Bladeテンプレート構文正常
- ✅ JavaScriptエラーなし

### 機能確認
- ✅ 住所（建物名）項目の表示
- ✅ Detail Card Controller の動作
- ✅ 未設定項目表示切り替え
- ✅ empty-field クラスの適用

### データ表示確認
- ✅ 建物名設定時の正常表示
- ✅ 建物名未設定時の「未設定」表示
- ✅ 住所情報の完全性

## 今後の保守について

### 設計原則
1. **完全なデータ表示**: データベースフィールドと表示項目の完全対応
2. **一貫した構造**: 住所項目の標準的な分割（郵便番号、住所、建物名）
3. **Detail Card Controller 対応**: 全項目で未設定項目表示機能を提供
4. **統一されたラベル**: 「住所（建物名）」の明確な表記

### 推奨事項
- 新しい住所関連項目追加時も同様の構造を維持
- 建物名項目は住所の直後に配置
- empty-field クラスと未設定表示を必ず適用
- 他のセクションとの一貫性を保つ

この修正により、基本情報の住所・連絡先セクションで住所（建物名）が適切に表示され、完全な住所情報が提供されるようになりました。
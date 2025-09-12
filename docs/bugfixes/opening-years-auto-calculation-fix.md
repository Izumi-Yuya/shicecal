# 開設年数自動計算修正 - 基本情報表示の改善

## 問題の概要

基本情報の開設年数が自動計算項目であることが適切に反映されていない問題を修正しました。

## 発見された問題

### 1. 不適切な条件判定
- 開設年数の表示条件が `$facility->years_in_operation` の存在チェック
- 開設年数は開設日から自動計算される項目
- 開設日が設定されていれば開設年数も表示されるべき

### 2. 自動計算ロジックの欠如
- データベースの `years_in_operation` フィールドに依存
- リアルタイムでの年数計算が行われていない
- 開設日から現在までの年数を動的に計算すべき

### 3. ラベルの不明確さ
- 「開設年数」というラベルでは自動計算であることが不明
- ユーザーが手動入力項目と誤解する可能性

## 実施した修正

### 1. 条件判定の修正

#### 修正前
```blade
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

#### 修正後
```blade
<div class="detail-row {{ empty($facility->opening_date) ? 'empty-field' : '' }}">
    <span class="detail-label">開設年数（自動計算）</span>
    <span class="detail-value">
        @if($facility->opening_date)
            @php
                $yearsInOperation = $facility->opening_date->diffInYears(now());
            @endphp
            {{ $yearsInOperation }}年
        @else
            未設定
        @endif
    </span>
</div>
```

### 2. 自動計算ロジックの実装

#### 計算方法
- **基準**: 開設日（`opening_date`）から現在日時（`now()`）までの年数
- **計算**: Carbon の `diffInYears()` メソッドを使用
- **リアルタイム**: ページ表示時に動的に計算

#### 表示ロジック
- **開設日が設定済み**: 自動計算された年数を表示
- **開設日が未設定**: 「未設定」を表示
- **empty-field クラス**: 開設日の有無で判定

### 3. ラベルの明確化

#### 変更内容
- **修正前**: 「開設年数」
- **修正後**: 「開設年数（自動計算）」

#### 効果
- ユーザーが自動計算項目であることを理解
- 手動入力項目との区別が明確
- システムの動作が透明化

## 修正の効果

### 1. 正確な年数表示
- ✅ 開設日が設定されていれば常に正確な年数を表示
- ✅ リアルタイムでの年数計算
- ✅ データベースの古い値に依存しない

### 2. 適切な未設定項目判定
- ✅ 開設日の有無で empty-field クラスを判定
- ✅ Detail Card Controller の正常動作
- ✅ 一貫した表示ロジック

### 3. ユーザビリティの向上
- ✅ 自動計算項目であることが明確
- ✅ 手動入力項目との区別
- ✅ システムの透明性向上

### 4. データの整合性
- ✅ 開設日と開設年数の整合性確保
- ✅ 古いデータベース値に依存しない
- ✅ 常に最新の計算結果を表示

## 計算例

### 開設日が設定されている場合
```php
// 開設日: 2020年4月1日
// 現在日: 2025年1月12日
$yearsInOperation = $facility->opening_date->diffInYears(now());
// 結果: 4年
```

### 開設日が未設定の場合
```blade
@if($facility->opening_date)
    <!-- 自動計算 -->
@else
    未設定  <!-- empty-field クラスが適用される -->
@endif
```

## 他の自動計算項目との整合性

### 土地情報での類似例
土地情報でも同様の自動計算項目があります：

```blade
<!-- 坪単価（自動計算） -->
<div class="detail-row {{ $unitPrice === null ? 'empty-field' : '' }}">
    <span class="detail-label">坪単価（自動計算）</span>
    <span class="detail-value">
        {{ $unitPrice !== null ? number_format($unitPrice) . '円/坪' : '未設定' }}
    </span>
</div>

<!-- 契約年数（自動計算） -->
<div class="detail-row">
    <span class="detail-label">契約年数（自動計算）</span>
    <span class="detail-value">
        @php
            $contractYears = null;
            if ($landInfo->contract_start_date && $landInfo->contract_end_date) {
                $contractYears = $landInfo->contract_start_date->diffInYears($landInfo->contract_end_date);
            }
        @endphp
        {{ $contractYears !== null ? $contractYears . '年' : '未設定' }}
    </span>
</div>
```

## 検証結果

### ビルド確認
- ✅ `npm run build` 成功
- ✅ PHPシンタックスエラーなし
- ✅ Bladeテンプレート正常

### 機能確認
- ✅ 開設日設定時の年数自動計算
- ✅ 開設日未設定時の「未設定」表示
- ✅ Detail Card Controller の動作
- ✅ empty-field クラスの適用

### 表示確認
- ✅ ラベルに「（自動計算）」表示
- ✅ 年数の正確な計算
- ✅ 未設定項目表示切り替え

## 今後の保守について

### 設計原則
1. **自動計算項目の明示**: ラベルに「（自動計算）」を追加
2. **基準データでの判定**: 計算の基となるデータで empty-field を判定
3. **リアルタイム計算**: 表示時に動的計算を実行
4. **整合性の確保**: 関連データとの整合性を維持

### 推奨事項
- 他の自動計算項目も同様の方式を採用
- ラベルで自動計算であることを明示
- 基準データの有無で未設定判定を行う
- Carbon の日付計算メソッドを活用

この修正により、開設年数が開設日から正確に自動計算され、ユーザーにとって分かりやすい表示になりました。
# サービス名テーブル4列構造実装

## 要求仕様

基本情報タブのサービス名テーブルを以下の4列構造で実装：

1. **1列目**: 「サービス名」ラベルが全行にまたがって1つのセル（rowspan使用）
2. **2列目**: 各サービス名の値
3. **3列目**: 各行に「有効期限」ラベル
4. **4列目**: 各行の有効期限の値

## 問題の詳細

### 修正前の実装

```php
// 最初の行のみサービス種類ラベルを表示、2行目以降は値のみ
if ($index === 0) {
    $servicesData[] = [
        'type' => 'standard',
        'cells' => [
            ['label' => 'サービス種類', 'value' => $service->service_type, 'type' => 'text', 'rowspan' => $services->count()],
            ['label' => '有効期限', 'value' => $validityPeriod, 'type' => 'text'],
        ]
    ];
} else {
    // 2行目以降はサービス種類のセルを省略（rowspanで対応）
    $servicesData[] = [
        'type' => 'standard',
        'cells' => [
            ['label' => '有効期限', 'value' => $validityPeriod, 'type' => 'text'],
        ]
    ];
}
```

### 問題点

1. **rowspanの使用**: 最初の行で`rowspan`を使用していたが、共通テーブルコンポーネントでの処理が複雑
2. **セル数の不整合**: 2行目以降でセルが1つしかないため、テーブルの列構造が崩れる
3. **列ずれ**: 「有効期限」列が正しい位置に表示されない

## 修正内容

### 実装内容

```php
if ($index === 0) {
    // 最初の行：サービス種類ラベル（rowspan）+ サービス名 + 有効期限ラベル + 有効期限値
    $servicesData[] = [
        'type' => 'standard',
        'cells' => [
            ['label' => 'サービス名', 'value' => null, 'type' => 'label', 'rowspan' => $services->count()],
            ['label' => null, 'value' => $service->service_type, 'type' => 'text'],
            ['label' => '有効期限', 'value' => null, 'type' => 'label'],
            ['label' => null, 'value' => $validityPeriod, 'type' => 'text'],
        ]
    ];
} else {
    // 2行目以降：サービス名 + 有効期限ラベル + 有効期限値（サービス名ラベルはrowspanで省略）
    $servicesData[] = [
        'type' => 'standard',
        'cells' => [
            ['label' => null, 'value' => $service->service_type, 'type' => 'text'],
            ['label' => '有効期限', 'value' => null, 'type' => 'label'],
            ['label' => null, 'value' => $validityPeriod, 'type' => 'text'],
        ]
    ];
}
```

### 実装のポイント

1. **4列構造**: サービス名ラベル、サービス名、有効期限ラベル、有効期限値の4列
2. **rowspanの活用**: サービス名ラベルは全行にまたがって表示
3. **新しいセルタイプ**: 'label'タイプを追加してラベル専用セルを実装

## 修正の利点

### 1. 表示の一貫性
- 全ての行で同じ列数を維持
- テーブル構造が崩れない
- レスポンシブ対応も安定

### 2. メンテナンス性の向上
- シンプルな実装で理解しやすい
- 共通テーブルコンポーネントとの互換性が向上
- デバッグが容易

### 3. アクセシビリティの改善
- スクリーンリーダーでの読み上げが正確
- テーブル構造が明確
- ARIA属性が正しく適用される

## テストケース

以下のテストケースで動作を確認：

1. **単一サービス**: 正常に2列で表示
2. **複数サービス**: 各サービスが適切に表示、列ずれなし
3. **サービスなし**: 「未設定」が正しく表示
4. **部分的な日付**: 開始日のみ、終了日のみの場合も正常表示

## ファイル変更履歴

### 修正されたファイル
- `resources/views/facilities/basic-info/partials/display-card.blade.php` - 4列構造の実装
- `resources/views/components/common-table/row.blade.php` - ラベル専用セルの処理追加
- `resources/views/components/common-table/cell.blade.php` - 'label'タイプのサポート追加
- `app/Services/CommonTableValidator.php` - 'label'タイプをサポート対象に追加
- `app/Services/ValueFormatter.php` - 'label'タイプのフォーマット処理追加

### 追加されたファイル
- `tests/Feature/BasicInfoServiceTableTest.php` - 4列構造のテスト
- `tests/manual/basic-info-service-table-test.html` - 手動テスト用HTML
- `docs/bugfixes/service-table-column-alignment-fix.md` - 実装ドキュメント

## 動作確認

### 手動テスト
```bash
# HTMLファイルでの表示確認
open tests/manual/basic-info-service-table-test.html
```

### 自動テスト
```bash
# PHPUnitテストの実行
php artisan test tests/Feature/BasicInfoServiceTableTest.php
```

## 今後の改善点

1. **UIの統一**: 他のテーブルでも同様の表示方式を検討
2. **パフォーマンス**: 大量のサービスがある場合の表示最適化
3. **国際化**: 多言語対応時のラベル表示方法の検討

## 関連する技術仕様

- Laravel Blade テンプレート
- Bootstrap 5.1.3 テーブルコンポーネント
- 共通テーブルコンポーネント (`x-common-table`)
- アクセシビリティ (ARIA属性)

この修正により、サービス種類テーブルの表示が安定し、ユーザビリティとアクセシビリティが向上しました。
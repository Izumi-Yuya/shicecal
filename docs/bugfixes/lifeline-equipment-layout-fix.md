# ライフライン設備レイアウト修正

## 概要
ライフライン設備のレイアウトが他の詳細画面のレイアウトと異なっていた問題を修正しました。

## 修正内容

### 1. メインレイアウトの統一 (`resources/views/facilities/lifeline-equipment/index.blade.php`)
- ライフライン設備全体を他の詳細画面と同様のカード構造で囲むように修正
- 統一されたカードヘッダーとコメント機能を追加
- `facility-info-card detail-card-improved` クラスを適用

### 2. ガス設備レイアウトの修正 (`resources/views/facilities/lifeline-equipment/gas.blade.php`)
- 他の詳細画面と同じカード構造に統一
- 各カードに適切な `data-section` 属性を追加
- コメントセクションを各カードに統合
- `detail-row` と `detail-value` の構造を統一
- 空フィールドの表示を「未設定」に統一

### 3. 水道設備レイアウトの修正 (`resources/views/facilities/lifeline-equipment/water.blade.php`)
- ガス設備と同様の修正を適用
- 編集機能を削除し、表示専用に変更
- 4つのカード構造に統一（基本情報、備考、設備詳細、メンテナンス履歴）

### 4. エレベーター設備レイアウトの修正 (`resources/views/facilities/lifeline-equipment/elevator.blade.php`)
- 同様のカード構造に統一
- コメント機能を各カードに追加
- アクセシビリティ属性を追加

### 5. 空調・照明設備レイアウトの修正 (`resources/views/facilities/lifeline-equipment/hvac-lighting.blade.php`)
- 編集機能を削除し、表示専用に変更
- 4つのカード構造に統一
- 日付フィールドの表示形式を統一

## 統一されたレイアウト特徴

### カード構造
- `facility-info-card detail-card-improved` クラスの使用
- 統一されたカードヘッダー（アイコン + タイトル + コメントボタン）
- `data-section` 属性による識別

### 詳細表示
- `facility-detail-table` クラスの使用
- `detail-row` と `detail-label`、`detail-value` の構造
- 空フィールドには `empty-field` クラスと「未設定」表示

### コメント機能
- 各カードにコメントトグルボタン
- 統一されたコメントセクション構造
- アクセシビリティ対応

### アイコンとカラー
- 各設備タイプに適切なアイコンとカラーを設定
- 基本情報: `text-primary`
- 備考: `text-warning`
- 設備詳細: `text-info`
- メンテナンス履歴: `text-success`

## 影響範囲
- ライフライン設備の全タブ（電気、ガス、水道、エレベーター、空調・照明）
- 既存のCSS（`resources/css/pages/lifeline-equipment.css`）は変更不要
- JavaScript機能は既存のものを継続使用

## テスト項目
1. ライフライン設備タブの表示確認
2. 各サブタブ（電気、ガス、水道、エレベーター、空調・照明）の表示確認
3. コメント機能の動作確認
4. レスポンシブデザインの確認
5. 他の詳細画面との一貫性確認

## 備考
- 電気設備は既に適切なレイアウトだったため、大きな変更は不要
- 開発中の機能については「詳細仕様は開発中です」メッセージを表示
- 編集機能は将来的に統一されたパターンで実装予定
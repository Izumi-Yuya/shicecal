# 契約書タブへのドキュメント管理統合 - 実装サマリー

## 概要

契約書タブ（給食、駐車場、その他）にドキュメント管理機能を統合しました。既存の修繕履歴やライフライン設備と同じパターンを使用し、一貫性のあるUIとUXを提供します。

## 実装日

2025年10月16日

## 実装内容

### 1. contracts/index.blade.phpの更新

#### 追加された機能

各契約書サブタブ（給食、駐車場、その他）に以下のドキュメント管理セクションを追加：

- **ドキュメント表示ボタン**: 折りたたみ可能なドキュメント管理セクションを表示
- **カテゴリ別カラーテーマ**:
  - その他契約書: プライマリ（青）
  - 給食契約書: サクセス（緑）
  - 駐車場契約書: インフォ（水色）
- **contract-document-managerコンポーネント**: 各カテゴリに対応したドキュメント管理UI

#### モーダルhoisting処理

折りたたみ領域内のモーダルが正しく表示されるよう、以下の処理を実装：

```javascript
// モーダルをbody直下に移動
function hoistModals(container) {
    if (!container) return;
    container.querySelectorAll('.modal').forEach(function(modal) {
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    });
}

// 各ドキュメントセクションに対して実行
const documentSections = [
    document.getElementById('others-documents-section'),
    document.getElementById('meal-service-documents-section'),
    document.getElementById('parking-documents-section')
];

documentSections.forEach(function(section) {
    if (section) {
        hoistModals(section);
        section.addEventListener('shown.bs.collapse', function() {
            hoistModals(section);
        });
    }
});
```

#### z-index管理

モーダルとバックドロップのz-indexを強制設定：

```javascript
// モーダル表示時
document.addEventListener('show.bs.modal', function(ev) {
    var modalEl = ev.target;
    if (modalEl) {
        modalEl.style.zIndex = '2010';
    }
    setTimeout(function() {
        var backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(function(bd) {
            bd.style.zIndex = '2000';
        });
    }, 0);
});
```

### 2. CSSスタイルの追加

#### 新規ファイル: `resources/css/contract-document-management.css`

既存の`lifeline-document-management.css`を参考に、契約書ドキュメント管理専用のスタイルを作成：

**主な機能:**

1. **ドキュメントボタンスタイル**
   - ホバー効果
   - フォーカス管理
   - トランジション効果

2. **カテゴリ別カラーテーマ**
   ```css
   #others-documents-section .card-header {
     background: linear-gradient(135deg, #007bff, #0056b3) !important;
   }
   
   #meal-service-documents-section .card-header {
     background: linear-gradient(135deg, #28a745, #1e7e34) !important;
   }
   
   #parking-documents-section .card-header {
     background: linear-gradient(135deg, #17a2b8, #138496) !important;
   }
   ```

3. **モーダルスタッキング修正**
   ```css
   #others-documents-section,
   #meal-service-documents-section,
   #parking-documents-section {
     overflow: visible;
   }
   
   .modal-backdrop {
     z-index: 2000 !important;
   }
   
   .modal {
     z-index: 2010 !important;
   }
   ```

4. **レスポンシブ対応**
   - モバイル表示の最適化
   - タブレット表示の調整
   - アクセシビリティ対応

5. **アニメーション効果**
   - スライドダウンアニメーション
   - ローディング状態の表示

#### Vite設定の更新

`vite.config.js`に新しいCSSファイルを追加：

```javascript
input: [
    'resources/css/contract-document-management.css',
    // ... 他のファイル
]
```

#### facilities/show.blade.phpの更新

契約書タブで使用するため、CSSファイルを読み込み：

```blade
@vite([
    'resources/css/pages/facilities.css',
    'resources/css/water-equipment.css',
    'resources/css/contract-document-management.css',
    'resources/js/modules/facilities.js'
])
```

### 3. JavaScriptの読み込み設定

#### app-unified.jsの更新

ContractDocumentManagerをインポートし、グローバルに公開：

```javascript
// Import ContractDocumentManager
import ContractDocumentManager from './modules/ContractDocumentManager.js';

// グローバルに公開（ボタンクリックハンドラーで使用）
window.LifelineDocumentManager = LifelineDocumentManager;
window.MaintenanceDocumentManager = MaintenanceDocumentManager;
window.ContractDocumentManager = ContractDocumentManager;
```

#### 初期化処理

contract-document-manager.blade.phpコンポーネント内で自動初期化：

```javascript
(function() {
    function initManager() {
        if (typeof ContractDocumentManager !== 'undefined') {
            const facilityId = {{ $facility->id }};
            const category = '{{ $category }}';
            const managerKey = 'contractDocManager_' + category;
            
            if (window[managerKey]) {
                console.log(`Manager for ${category} already exists`);
                return;
            }
            
            new ContractDocumentManager(facilityId, category);
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initManager);
    } else {
        initManager();
    }
})();
```

## ファイル変更一覧

### 新規作成
- `resources/css/contract-document-management.css` - 契約書ドキュメント管理専用CSS

### 更新
- `resources/views/facilities/contracts/index.blade.php` - ドキュメント管理セクション追加、モーダルhoisting処理追加
- `resources/js/app-unified.js` - ContractDocumentManagerのインポートとグローバル公開
- `vite.config.js` - 新しいCSSファイルの追加
- `resources/views/facilities/show.blade.php` - CSSファイルの読み込み追加

## 実装パターン

### 既存実装との一貫性

修繕履歴タブ（`repair-history/index.blade.php`）と同じパターンを採用：

1. **ドキュメント表示ボタン**: 折りたたみ可能なセクション
2. **カテゴリ別カラーテーマ**: 視覚的な区別
3. **モーダルhoisting**: 折りたたみ領域内のモーダル問題の解決
4. **z-index管理**: モーダルとバックドロップの適切な表示

### コンポーネント再利用

既存の`contract-document-manager.blade.php`コンポーネントを使用：

```blade
<x-contract-document-manager 
    :facility="$facility" 
    category="others"
    categoryName="その他契約書"
/>
```

## テスト項目

### 機能テスト

- [ ] 各契約書サブタブでドキュメント管理セクションが表示される
- [ ] ドキュメント表示ボタンをクリックすると、セクションが展開/折りたたまれる
- [ ] フォルダ作成ボタンが動作する
- [ ] ファイルアップロードボタンが動作する
- [ ] モーダルが正しく表示される（背面に隠れない）
- [ ] モーダルを閉じた後、余分なバックドロップが残らない
- [ ] 表示モード切替（リスト/グリッド）が動作する
- [ ] 検索機能が動作する

### UIテスト

- [ ] カテゴリ別のカラーテーマが正しく適用される
- [ ] ボタンのホバー効果が動作する
- [ ] アニメーション効果が滑らかに動作する
- [ ] レスポンシブデザインが正しく動作する（モバイル、タブレット）

### アクセシビリティテスト

- [ ] キーボードナビゲーションが動作する
- [ ] スクリーンリーダーで適切に読み上げられる
- [ ] フォーカス管理が適切に動作する
- [ ] ARIAラベルが適切に設定されている

## 既知の問題

なし

## 今後の改善点

1. **パフォーマンス最適化**
   - 大量のファイル表示時のパフォーマンス改善
   - 遅延読み込みの実装

2. **機能拡張**
   - ドラッグ&ドロップでのファイルアップロード
   - ファイルプレビュー機能
   - 一括操作機能

3. **ユーザビリティ向上**
   - ファイルアップロード進行状況の詳細表示
   - より詳細なエラーメッセージ

## 参考資料

- [モーダル実装ガイドライン](.kiro/steering/modal-implementation-guide.md)
- [ライフライン設備ドキュメント管理](../lifeline-equipment/document-management-guide.md)
- [修繕履歴ドキュメント管理](../maintenance-history/document-management-implementation.md)
- [カテゴリ分離実装ガイド](./category-implementation-guide.md)

## 関連タスク

- Task 7.1: contracts/index.blade.phpを更新 ✅
- Task 7.2: CSSスタイルを追加 ✅
- Task 7.3: JavaScriptの読み込みを設定 ✅

## 実装者メモ

- 既存の動作しているパターン（修繕履歴）を完全にコピーして使用
- カスタム実装は避け、標準的なBootstrapモーダルパターンを使用
- モーダルhoisting処理は必須（折りたたみ領域内のモーダル問題を解決）
- z-index管理も必須（モーダルとバックドロップの適切な表示）
- カテゴリ別のカラーテーマで視覚的な区別を実現

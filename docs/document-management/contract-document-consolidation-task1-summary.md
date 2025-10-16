# 契約書ドキュメント統合 - タスク1完了サマリー

## 実装日
2025年10月16日

## 完了したタスク

### タスク1: Bladeビューファイルの修正
契約書タブ表示画面（index.blade.php）に統一ドキュメント管理セクションを追加し、各サブタブのドキュメントセクションを削除しました。

#### サブタスク1.1: 統一ドキュメント管理セクションの追加 ✅
- `resources/views/facilities/contracts/index.blade.php`のサブタブナビゲーション直前に統一ドキュメント管理セクションを追加
- 折りたたみ可能なセクションとして実装（初期状態は折りたたまれている）
- 展開/折りたたみボタンを配置
- `contract-document-manager`コンポーネントを埋め込み

#### サブタスク1.2: サブタブ内ドキュメントセクションの削除 ✅
- その他契約書タブの`#others-documents-section`を削除
- 給食契約書タブの`#meal-service-documents-section`を削除
- 駐車場契約書タブの`#parking-documents-section`を削除
- 各セクションの折りたたみボタンとトグル処理を削除

## 実装内容

### 1. 統一ドキュメント管理セクションの追加

サブタブナビゲーションの直前に以下のセクションを追加しました：

```blade
<!-- 統一ドキュメント管理セクション -->
<div class="unified-contract-documents-section mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="fas fa-folder text-primary me-2"></i>契約書関連ドキュメント
        </h5>
        <button type="button" 
                class="btn btn-outline-primary btn-sm unified-documents-toggle" 
                id="unified-documents-toggle"
                data-bs-toggle="collapse" 
                data-bs-target="#unified-documents-section" 
                aria-expanded="false" 
                aria-controls="unified-documents-section">
            <i class="fas fa-folder-open me-1"></i>
            <span>ドキュメントを表示</span>
        </button>
    </div>
    
    <div class="collapse" id="unified-documents-section">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="fas fa-folder-open me-2"></i>契約書ドキュメント管理
                </h6>
            </div>
            <div class="card-body p-0">
                <x-contract-document-manager 
                    :facility="$facility" 
                    categoryName="契約書"
                />
            </div>
        </div>
    </div>
</div>
```

### 2. サブタブ内ドキュメントセクションの削除

以下の3つのドキュメント管理セクションを完全に削除しました：

1. **その他契約書タブ**: `#others-documents-section`とその折りたたみボタン
2. **給食契約書タブ**: `#meal-service-documents-section`とその折りたたみボタン
3. **駐車場契約書タブ**: `#parking-documents-section`とその折りたたみボタン

### 3. JavaScriptの更新

#### 追加した機能
- 統一ドキュメントセクションの折りたたみボタンテキスト変更機能
  - 展開時: "ドキュメントを非表示"
  - 折りたたみ時: "ドキュメントを表示"
- 統一セクション用のモーダルhoisting処理

#### 削除した機能
- 個別サブタブ用のモーダルhoisting処理（3つのセクション分）

```javascript
// 統一ドキュメントセクションの折りたたみボタンテキスト変更
const unifiedToggleBtn = document.getElementById('unified-documents-toggle');
const unifiedSection = document.getElementById('unified-documents-section');

if (unifiedToggleBtn && unifiedSection) {
    unifiedSection.addEventListener('show.bs.collapse', function() {
        const icon = unifiedToggleBtn.querySelector('i');
        const text = unifiedToggleBtn.querySelector('span');
        if (icon) icon.className = 'fas fa-folder me-1';
        if (text) text.textContent = 'ドキュメントを非表示';
    });
    
    unifiedSection.addEventListener('hide.bs.collapse', function() {
        const icon = unifiedToggleBtn.querySelector('i');
        const text = unifiedToggleBtn.querySelector('span');
        if (icon) icon.className = 'fas fa-folder-open me-1';
        if (text) text.textContent = 'ドキュメントを表示';
    });
}

// Hoist modals for unified document section
const unifiedDocumentSection = document.getElementById('unified-documents-section');

if (unifiedDocumentSection) {
    // Initial hoisting
    hoistModals(unifiedDocumentSection);
    
    // Hoist on collapse shown
    unifiedDocumentSection.addEventListener('shown.bs.collapse', function() {
        hoistModals(unifiedDocumentSection);
    });
}
```

## 変更されたファイル

1. `resources/views/facilities/contracts/index.blade.php`
   - 統一ドキュメント管理セクションを追加
   - 3つのサブタブ内ドキュメントセクションを削除
   - JavaScriptを更新

## 要件との対応

### Requirement 1.1 ✅
WHEN User が契約書タブを表示する時、THE System SHALL サブタブナビゲーションの上部に統一されたドキュメント管理セクションを表示する

### Requirement 1.2 ✅
THE System SHALL 各サブタブ内の個別ドキュメント管理セクションを削除する

### Requirement 1.4 ✅
THE System SHALL ドキュメント管理セクションを折りたたみ可能な形式で提供する

### Requirement 1.5 ✅
THE System SHALL ドキュメント管理セクションの初期状態を折りたたまれた状態にする

### Requirement 4.1 ✅
THE System SHALL ドキュメント管理セクションをサブタブナビゲーションの直前に配置する

### Requirement 4.2 ✅
THE System SHALL ドキュメント管理セクションに明確な見出しとアイコンを表示する

## 検証方法

### 視覚的確認
1. 契約書タブを開く
2. サブタブナビゲーションの上部に「契約書関連ドキュメント」セクションが表示されることを確認
3. 初期状態で折りたたまれていることを確認
4. 「ドキュメントを表示」ボタンをクリックして展開できることを確認
5. 展開時にボタンテキストが「ドキュメントを非表示」に変わることを確認
6. 各サブタブ（給食、駐車場、その他）内にドキュメントセクションが表示されないことを確認

### コード確認
```bash
# 統一セクションの存在確認
grep -n "unified-documents-section" resources/views/facilities/contracts/index.blade.php

# 個別セクションが削除されたことを確認（結果なしが正常）
grep -n "others-documents-section\|meal-service-documents-section\|parking-documents-section" resources/views/facilities/contracts/index.blade.php
```

## 次のステップ

タスク1が完了しました。次は以下のタスクに進みます：

- **タスク2**: Bladeコンポーネントの修正
  - `contract-document-manager`コンポーネントを単一インスタンスとして動作するように修正
  - カテゴリ固有のID接尾辞を削除
  - カテゴリ属性を削除

- **タスク3**: JavaScriptクラスの修正
  - `ContractDocumentManager`クラスを単一インスタンスとして動作するように修正

## 注意事項

- 既存のドキュメントデータは変更していません（データベースマイグレーション不要）
- 既存のAPIエンドポイントは変更していません
- モーダルz-index問題に対応するため、モーダルhoisting処理を実装しています
- 折りたたみ領域に`overflow: visible`を設定する必要があります（CSS修正で対応予定）

## 関連ドキュメント

- 要件定義: `.kiro/specs/contract-document-consolidation/requirements.md`
- 設計書: `.kiro/specs/contract-document-consolidation/design.md`
- タスクリスト: `.kiro/specs/contract-document-consolidation/tasks.md`

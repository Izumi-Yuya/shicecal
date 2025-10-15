# LifelineDocumentManager uniqueId修正

## 問題

防犯・防災タブのドキュメント管理で以下のエラーが発生：
```
[LifelineDoc] tbody not found: #document-list-body-security_disaster
```

## 原因

`LifelineDocumentManager.js`で、DOM要素のIDを生成する際に`this.category`を使用していたが、防犯・防災タブでは`subcategory`パラメータ（`camera_lock`と`fire_disaster`）を使用して`uniqueId`を生成していた。

例：
- コンポーネントは`uniqueId = "security_disaster_camera_lock"`を生成
- JavaScriptは`#document-list-body-security_disaster`を検索
- 実際のIDは`#document-list-body-security_disaster_camera_lock`

## 修正内容

### 1. renderListViewとrenderGridViewの修正

`category`パラメータを削除し、`this.uniqueId`を使用するように変更：

```javascript
// 修正前
renderListView(category, data) {
    const tbody = container.querySelector(`#document-list-body-${category}`);
}

// 修正後
renderListView(data) {
    const tbody = container.querySelector(`#document-list-body-${this.uniqueId}`);
}
```

### 2. renderFolderRowとrenderFileRowの修正

グローバルインスタンス参照を`this.uniqueId`に変更：

```javascript
// 修正前
const category = this.category;
onclick="window.lifelineDocManager_${category}.navigateToFolder(${folder.id})"

// 修正後
const uniqueId = this.uniqueId;
onclick="window.lifelineDocManager_${uniqueId}.navigateToFolder(${folder.id})"
```

### 3. DOM要素ID検索の一括修正

`getElementById`と`querySelector`で`this.category`を使用している箇所を`this.uniqueId`に一括置換：

```bash
sed -i 's/getElementById(`\([^`]*\)-\${this\.category}`)/getElementById(`\1-${this.uniqueId}`)/g' LifelineDocumentManager.js
sed -i 's/querySelector(`#\([^`]*\)-\${this\.category}`)/querySelector(`#\1-${this.uniqueId}`)/g' LifelineDocumentManager.js
```

## 影響範囲

### 修正されたメソッド
- `renderListView()` - tbodyの検索
- `renderGridView()` - grid bodyの検索
- `renderFolderRow()` - フォルダ行のHTML生成
- `renderFileRow()` - ファイル行のHTML生成
- `renderFolderCard()` - フォルダカードのHTML生成
- `renderFileCard()` - ファイルカードのHTML生成
- `setupEventListeners()` - イベントリスナーの設定
- `setupModalEventListeners()` - モーダルイベントリスナーの設定
- その他多数のDOM要素検索箇所

### 影響を受けるカテゴリ
- **電気設備** (`electrical`) - 単一カテゴリ、影響なし
- **ガス設備** (`gas`) - 単一カテゴリ、影響なし
- **水道設備** (`water`) - 単一カテゴリ、影響なし
- **エレベーター** (`elevator`) - 単一カテゴリ、影響なし
- **空調・照明** (`hvac_lighting`) - 単一カテゴリ、影響なし
- **防犯・防災** (`security_disaster`) - サブカテゴリあり、修正により動作するようになる
  - `security_disaster_camera_lock` (防犯カメラ・電子錠)
  - `security_disaster_fire_disaster` (消防・防災)

## テスト項目

### 防犯・防災タブ
- [ ] 防犯カメラ・電子錠ドキュメント管理モーダルが開く
- [ ] フォルダ作成ができる
- [ ] ファイルアップロードができる
- [ ] ファイル一覧が表示される
- [ ] ファイルダウンロードができる
- [ ] ファイル削除ができる
- [ ] 消防・防災ドキュメント管理モーダルが開く
- [ ] 同様の操作が正常に動作する

### 他のライフライン設備タブ
- [ ] 電気設備のドキュメント管理が正常に動作する
- [ ] ガス設備のドキュメント管理が正常に動作する
- [ ] 水道設備のドキュメント管理が正常に動作する
- [ ] エレベーターのドキュメント管理が正常に動作する
- [ ] 空調・照明のドキュメント管理が正常に動作する

## 注意事項

- `this.category`はAPI URLの生成（`this.apiCategory`経由）で引き続き使用される
- `this.uniqueId`はDOM要素のID生成とグローバルインスタンス参照に使用される
- サブカテゴリがない場合、`uniqueId`は`category`と同じ値になる

## 関連ファイル

- `resources/js/modules/LifelineDocumentManager.js` - メインの修正ファイル
- `resources/views/components/lifeline-document-manager.blade.php` - コンポーネント定義
- `resources/views/facilities/security-disaster/index.blade.php` - 防犯・防災タブ

## 追加修正（app-unified.js）

### 問題
`LifelineDocumentManager.js`を修正しても、`app-unified.js`で`category`のみを使って初期化していたため、エラーが継続。

### 修正内容
`app-unified.js`の初期化ロジックを修正：

```javascript
// 修正前
const category = container.dataset.lifelineCategory;
const managerKey = `lifelineDocumentManager_${category}`;
const manager = new LifelineDocumentManager(facilityId, category);

// 修正後
const category = container.dataset.lifelineCategory;
const subcategory = container.dataset.subcategory;
const uniqueId = subcategory ? `${category}_${subcategory}` : category;
const managerKey = `lifelineDocumentManager_${uniqueId}`;
const manager = new LifelineDocumentManager(facilityId, category, uniqueId);
```

### 影響
- コンテナの`data-subcategory`属性を読み取って`uniqueId`を生成
- グローバルインスタンス参照も`uniqueId`を使用
- 防犯・防災タブで正しく動作するようになる

## 追加修正2（重複インスタンス防止）

### 問題
コンポーネントの`@push('scripts')`と`app-unified.js`の両方で初期化しようとするため、重複インスタンスの警告が表示される。

### 修正内容
`app-unified.js`でグローバルインスタンスもチェックするように修正：

```javascript
// 修正前
if (!this.modules[managerKey]) {
  const manager = new LifelineDocumentManager(facilityId, category, uniqueId);
  this.modules[managerKey] = manager;
}

// 修正後
const existingManager = this.modules[managerKey] || window[globalKey];
if (!existingManager) {
  const manager = new LifelineDocumentManager(facilityId, category, uniqueId);
  this.modules[managerKey] = manager;
  window[globalKey] = manager;
} else {
  // 既存のインスタンスを使用
  if (!this.modules[managerKey] && window[globalKey]) {
    this.modules[managerKey] = window[globalKey];
  }
}
```

### 影響
- コンポーネントが先に初期化した場合、`app-unified.js`は既存のインスタンスを使用
- 重複インスタンスの作成を防止
- 警告メッセージは表示されるが、正常に動作する

## 修正日

2025年10月15日（初回）
2025年10月15日（app-unified.js追加修正）
2025年10月15日（重複インスタンス防止）

# JavaScript アーキテクチャ

## ファイル構造

### メインアプリケーションファイル

```
resources/js/
├── app.js                          # メインエントリーポイント（Viteでビルド）
├── app-unified.js                  # 統合アプリケーションコア
└── modules/                        # 機能別モジュール
    ├── DocumentManager.js          # ドキュメント管理
    ├── LifelineDocumentManager.js  # ライフライン設備ドキュメント管理
    ├── FacilityManager.js          # 施設管理
    ├── CsvDownloadManager.js       # CSVエクスポート
    └── ...
```

## ファイルの役割

### 1. app.js
**役割**: メインエントリーポイント
- `app-unified.js`をインポートして拡張
- レガシーコードとの互換性を提供
- 個別モジュールを統合

**使用場所**: すべてのページで読み込まれる

```javascript
import { ShiseCalApp } from './app-unified.js';

class ExtendedApplication extends ShiseCalApp {
  // 追加機能を実装
}
```

### 2. app-unified.js
**役割**: 統合アプリケーションコア
- `ShiseCalApp`クラス - メインアプリケーションクラス
- `AppUtils` - 共通ユーティリティ
- `ApiClient` - API通信クライアント
- 各種マネージャーの初期化

**主要クラス**:
```javascript
class ShiseCalApp {
  async init()                              // アプリケーション初期化
  initializeFacilityFeatures()              // 施設機能初期化
  initializeDocumentManagement()            // ドキュメント管理初期化
  initializeLifelineDocumentManagement()    // ライフライン設備初期化
  initializeLifelineDocumentToggles()       // トグルボタン初期化
}
```

## モジュール構造

### ドキュメント管理
```
DocumentManager.js              # 施設ドキュメント管理
LifelineDocumentManager.js      # ライフライン設備ドキュメント管理
```

### 施設管理
```
FacilityManager.js              # 施設CRUD操作
facility-form-layout.js         # フォームレイアウト
facility-view-toggle.js         # 表示切替
```

### エクスポート
```
ExportManager.js                # エクスポート管理
CsvDownloadManager.js           # CSVダウンロード
```

### ライフライン設備
```
lifeline-equipment.js           # ライフライン設備管理
```

## 初期化フロー

```
1. DOMContentLoaded
   ↓
2. app.js: ExtendedApplication.init()
   ↓
3. app-unified.js: ShiseCalApp.init()
   ↓
4. ページ固有の機能初期化
   - initializeFacilityFeatures()
   - initializeDocumentManagement()
   - initializeLifelineDocumentManagement()
   ↓
5. グローバル機能初期化
   - initializeGlobalFeatures()
   - initializeTooltips()
   - initializeAccessibilityFeatures()
```

## ライフライン設備ドキュメント管理の初期化

### タイミング

1. **ページ読み込み時**
   ```javascript
   initializeFacilityFeatures() {
     this.initializeLifelineDocumentManagement(facilityId);
     this.initializeLifelineDocumentToggles();
   }
   ```

2. **タブ切り替え時**
   ```javascript
   lifelineTab.addEventListener('shown.bs.tab', () => {
     this.initializeLifelineDocumentManagement(facilityId);
   });
   ```

3. **ドキュメントボタンクリック時**
   ```javascript
   documentSection.addEventListener('shown.bs.collapse', () => {
     this.initializeLifelineDocumentManagers();
   });
   ```

### データフロー

```
ユーザー操作
   ↓
LifelineDocumentManager
   ↓
API呼び出し (fetch)
   ↓
LifelineDocumentController
   ↓
LifelineDocumentService
   ↓
データベース
```

## グローバルオブジェクト

### window.shiseCalApp
メインアプリケーションインスタンス
```javascript
window.shiseCalApp = new ShiseCalApp();
```

### window.ShiseCal (レガシー)
後方互換性のためのAPI
```javascript
window.ShiseCal = {
  config: AppConfig,
  utils: AppUtils,
  api: ApiClient,
  // ...
};
```

## ベストプラクティス

### 1. 新機能の追加
- `modules/`ディレクトリに新しいモジュールを作成
- `app-unified.js`で初期化メソッドを追加
- 必要に応じて`app.js`で拡張

### 2. 既存機能の修正
- 該当するモジュールファイルを直接編集
- `app-unified.js`の初期化ロジックは変更しない

### 3. デバッグ
- ブラウザコンソールで`window.shiseCalApp`を確認
- `console.log`で初期化状態を追跡
- `window.shiseCalApp.modules`で各モジュールの状態を確認

## トラブルシューティング

### 問題: モジュールが初期化されない
**確認事項**:
1. `app-unified.js`で初期化メソッドが呼ばれているか
2. DOM要素が存在するか（`data-*`属性など）
3. コンソールエラーがないか

### 問題: イベントリスナーが動作しない
**確認事項**:
1. `this`コンテキストが正しいか
2. イベントリスナーが重複登録されていないか
3. DOM要素が動的に生成される場合、タイミングが適切か

### 問題: APIリクエストが失敗する
**確認事項**:
1. CSRFトークンが正しく設定されているか
2. ルートが正しく定義されているか
3. 認証・認可が適切か

## 今後の改善

### 短期
- [ ] TypeScript化の検討
- [ ] テストカバレッジの向上
- [ ] パフォーマンス最適化

### 長期
- [ ] Vue.js/Reactへの移行検討
- [ ] WebSocketによるリアルタイム更新
- [ ] PWA対応

# ローカル接続トラブルシューティングガイド

## 現在の状況確認

### ✅ 正常に動作している項目
1. **データベース**: SQLiteデータベースが正常に作成され、シードデータも投入済み
2. **マイグレーション**: 全てのマイグレーションが正常に実行済み
3. **ルート設定**: AuthControllerとルートが正常に設定済み
4. **アセット**: CSS/JSファイルが正常にビルド済み

### 🔧 確認が必要な項目

## トラブルシューティング手順

### 1. 基本的な接続確認

#### サーバー起動
```bash
# キャッシュクリア
php artisan config:clear
php artisan route:clear
php artisan view:clear

# サーバー起動
php artisan serve --host=127.0.0.1 --port=8000
```

#### ブラウザでアクセス
- URL: `http://127.0.0.1:8000`
- または: `http://localhost:8000`

### 2. 権限とファイル確認

#### ストレージ権限
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

#### データベースファイル権限
```bash
chmod 664 database/database.sqlite
```

### 3. 環境設定確認

#### .env設定
```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=/Users/izumiisamunari/Desktop/shicecal/database/database.sqlite
```

### 4. ポート競合の確認

#### 使用中のポートを確認
```bash
lsof -i :8000
```

#### 別のポートを使用
```bash
php artisan serve --host=127.0.0.1 --port=8001
php artisan serve --host=127.0.0.1 --port=8002
```

### 5. ログの確認

#### Laravelログ
```bash
tail -f storage/logs/laravel.log
```

#### エラーログの場所
- `storage/logs/laravel.log`
- ブラウザの開発者ツール（Console/Network）

### 6. テストユーザーでのログイン

#### 管理者ユーザー
- **Email**: `admin@shisecal.example.com`
- **Password**: `password`

#### 一般ユーザー
- **Email**: `sakurai@shisecal.example.com`
- **Password**: `password`

### 7. Vite開発サーバー（オプション）

#### 開発環境での使用
```bash
# ターミナル1: Vite開発サーバー
npm run dev

# ターミナル2: Laravel開発サーバー
php artisan serve --host=127.0.0.1 --port=8000
```

## よくある問題と解決方法

### 問題1: "Connection refused" エラー
**原因**: サーバーが起動していない、またはポートが競合している
**解決方法**: 
1. サーバーが起動しているか確認
2. 別のポートを使用
3. ファイアウォール設定を確認

### 問題2: "404 Not Found" エラー
**原因**: ルートが正しく設定されていない
**解決方法**: 
1. `php artisan route:clear`
2. `php artisan route:list` でルート確認
3. `.htaccess` ファイルの確認

### 問題3: "500 Internal Server Error"
**原因**: PHP エラー、権限問題、設定エラー
**解決方法**: 
1. `storage/logs/laravel.log` を確認
2. ストレージ権限を修正
3. `.env` 設定を確認

### 問題4: CSSやJSが読み込まれない
**原因**: アセットのビルドまたはパスの問題
**解決方法**: 
1. `npm run build`
2. `public/build/` ディレクトリの確認
3. ブラウザキャッシュのクリア

### 問題5: データベース接続エラー
**原因**: データベースファイルの権限またはパスの問題
**解決方法**: 
1. データベースファイルの存在確認
2. ファイル権限の修正
3. `.env` のパス確認

## デバッグ用コマンド

### システム情報確認
```bash
php --version
php artisan --version
npm --version
```

### Laravel設定確認
```bash
php artisan about
php artisan env
```

### データベース確認
```bash
php artisan migrate:status
php artisan db:show
```

### ルート確認
```bash
php artisan route:list
```

## 緊急時の対処法

### 完全リセット
```bash
# キャッシュクリア
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# データベースリセット
php artisan migrate:fresh --seed

# アセットリビルド
npm run build

# サーバー起動
php artisan serve --host=127.0.0.1 --port=8000
```

## サポート情報

### 動作環境
- **PHP**: 8.2+
- **Laravel**: 9.x
- **Node.js**: 16+
- **SQLite**: 3.x

### 確認済みブラウザ
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## 次のステップ

1. 上記の手順を順番に実行
2. エラーメッセージを記録
3. ログファイルを確認
4. 必要に応じて設定を調整

問題が解決しない場合は、具体的なエラーメッセージとログの内容を確認してください。
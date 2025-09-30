# ドキュメント管理システム セキュリティ設定確認項目

## 概要

このチェックリストは、ドキュメント管理システムのセキュリティ設定を確認するための包括的なガイドです。定期的な監査とセキュリティ強化のために使用してください。

## 1. アクセス制御

### 1.1 認証システム

#### ✅ 基本認証設定
- [ ] セッション管理が適切に設定されている
- [ ] パスワードポリシーが実装されている
- [ ] ログイン試行回数制限が設定されている
- [ ] セッションタイムアウトが適切に設定されている
- [ ] CSRF保護が有効になっている

```php
// config/session.php
'lifetime' => 120, // 2時間
'expire_on_close' => true,
'encrypt' => true,
'http_only' => true,
'same_site' => 'strict',
```

#### ✅ 権限管理
- [ ] ロールベースアクセス制御（RBAC）が実装されている
- [ ] 最小権限の原則が適用されている
- [ ] 権限の継承が適切に設定されている
- [ ] ゲストアクセスが制限されている

```php
// DocumentPolicy確認
public function view(User $user, $facility): bool
{
    return $user->canViewFacility($facility->id ?? $facility);
}

public function update(User $user, $facility): bool
{
    return $user->canEditFacility($facility->id ?? $facility);
}
```

### 1.2 API セキュリティ

#### ✅ API認証
- [ ] API トークン認証が実装されている
- [ ] レート制限が設定されている
- [ ] API バージョニングが実装されている
- [ ] 不要なAPIエンドポイントが無効化されている

```php
// config/sanctum.php
'expiration' => 525600, // 1年
'middleware' => [
    'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
    'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
],
```

## 2. ファイルセキュリティ

### 2.1 ファイルアップロード制限

#### ✅ ファイル検証
- [ ] ファイル形式制限が実装されている
- [ ] ファイルサイズ制限が設定されている
- [ ] MIMEタイプ検証が実装されている
- [ ] ファイル内容検証が実装されている
- [ ] 悪意のあるファイル検出機能が有効

```php
// app/Rules/SecureFileUpload.php
public function passes($attribute, $value)
{
    // MIMEタイプ検証
    $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array($value->getMimeType(), $allowedMimes)) {
        return false;
    }

    // ファイル内容検証
    $fileContent = file_get_contents($value->getPathname());
    if (strpos($fileContent, '<?php') !== false) {
        return false;
    }

    // ファイルサイズ制限
    if ($value->getSize() > 10 * 1024 * 1024) { // 10MB
        return false;
    }

    return true;
}
```

#### ✅ ファイル保存セキュリティ
- [ ] アップロードディレクトリが実行不可に設定されている
- [ ] ファイル名のサニタイズが実装されている
- [ ] 一意なファイル名生成が実装されている
- [ ] パストラバーサル攻撃対策が実装されている

```bash
# .htaccess (Apache)
<Files ~ "\.(php|pl|py|jsp|asp|sh|cgi)$">
    Order allow,deny
    Deny from all
</Files>

# 実行権限削除
chmod -x storage/app/public/documents/*
```

### 2.2 ファイルアクセス制御

#### ✅ 直接アクセス防止
- [ ] ファイルへの直接アクセスが制限されている
- [ ] 認証チェックを経由したダウンロードが実装されている
- [ ] ファイルパスの暗号化が実装されている
- [ ] 一時的なアクセストークンが使用されている

```php
// ファイルダウンロード時の認証チェック
public function downloadFile(DocumentFile $file)
{
    $this->authorize('view', [DocumentFile::class, $file->facility]);
    
    if (!Storage::exists($file->file_path)) {
        abort(404, 'ファイルが見つかりません。');
    }
    
    return Storage::download($file->file_path, $file->original_name);
}
```

## 3. データベースセキュリティ

### 3.1 データベース接続

#### ✅ 接続セキュリティ
- [ ] データベース接続が暗号化されている
- [ ] 専用データベースユーザーが使用されている
- [ ] 最小権限でのデータベースアクセス
- [ ] 接続プールが適切に設定されている

```env
# .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=facility_management
DB_USERNAME=doc_mgmt_user
DB_PASSWORD=secure_password
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

#### ✅ SQLインジェクション対策
- [ ] Eloquent ORMまたはPrepared Statementが使用されている
- [ ] 生のSQLクエリが適切にエスケープされている
- [ ] ユーザー入力の検証が実装されている
- [ ] データベースエラーメッセージが適切に処理されている

```php
// 安全なクエリ例
$files = DocumentFile::where('facility_id', $facilityId)
    ->where('original_name', 'LIKE', '%' . $searchTerm . '%')
    ->get();

// 危険な例（使用禁止）
// $files = DB::select("SELECT * FROM document_files WHERE facility_id = $facilityId");
```

### 3.2 データ暗号化

#### ✅ 保存時暗号化
- [ ] 機密データが暗号化されて保存されている
- [ ] 暗号化キーが適切に管理されている
- [ ] データベースレベルの暗号化が有効
- [ ] バックアップデータが暗号化されている

```php
// Laravel暗号化の使用
use Illuminate\Support\Facades\Crypt;

// 暗号化して保存
$encryptedData = Crypt::encryptString($sensitiveData);

// 復号化
$decryptedData = Crypt::decryptString($encryptedData);
```

## 4. ネットワークセキュリティ

### 4.1 HTTPS設定

#### ✅ SSL/TLS設定
- [ ] HTTPS通信が強制されている
- [ ] 有効なSSL証明書が設定されている
- [ ] TLS 1.2以上が使用されている
- [ ] HSTS（HTTP Strict Transport Security）が有効

```nginx
# nginx.conf
server {
    listen 443 ssl http2;
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
}
```

#### ✅ セキュリティヘッダー
- [ ] Content Security Policy (CSP) が設定されている
- [ ] X-Frame-Options が設定されている
- [ ] X-Content-Type-Options が設定されている
- [ ] X-XSS-Protection が設定されている

```php
// app/Http/Middleware/SecurityHeaders.php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    $response->headers->set('X-Frame-Options', 'DENY');
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-XSS-Protection', '1; mode=block');
    $response->headers->set('Content-Security-Policy', "default-src 'self'");
    
    return $response;
}
```

### 4.2 ファイアウォール設定

#### ✅ ネットワーク制限
- [ ] 不要なポートが閉じられている
- [ ] 管理インターフェースへのアクセスが制限されている
- [ ] DDoS攻撃対策が実装されている
- [ ] 地理的アクセス制限が設定されている（必要に応じて）

```bash
# iptables設定例
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j ACCEPT
iptables -A INPUT -p tcp --dport 22 -s 192.168.1.0/24 -j ACCEPT
iptables -A INPUT -j DROP
```

## 5. ログ・監査

### 5.1 アクティビティログ

#### ✅ ログ記録
- [ ] すべてのファイル操作がログに記録されている
- [ ] ユーザーアクセスがログに記録されている
- [ ] 権限変更がログに記録されている
- [ ] システムエラーがログに記録されている

```php
// ActivityLogService使用例
$this->activityLogService->logDocumentUploaded(
    $facility,
    $documentFile,
    $user,
    ['file_size' => $file->getSize(), 'mime_type' => $file->getMimeType()]
);

$this->activityLogService->logDocumentAccessed(
    $facility,
    $documentFile,
    $user,
    ['action' => 'download', 'ip_address' => $request->ip()]
);
```

#### ✅ ログ保護
- [ ] ログファイルが改ざん防止されている
- [ ] ログの定期的なバックアップが実行されている
- [ ] ログアクセスが制限されている
- [ ] ログローテーションが設定されている

```bash
# logrotate設定
/var/log/document-management/*.log {
    daily
    missingok
    rotate 90
    compress
    notifempty
    create 640 www-data adm
    postrotate
        systemctl reload nginx
    endscript
}
```

### 5.2 セキュリティ監視

#### ✅ 異常検知
- [ ] 不正アクセス試行の検知が実装されている
- [ ] 異常なファイルアップロードの検知が実装されている
- [ ] 権限昇格の試行が検知されている
- [ ] データ漏洩の兆候が監視されている

```php
// 異常検知例
class SecurityMonitor
{
    public function detectSuspiciousActivity($user, $action, $resource)
    {
        // 短時間での大量アクセス
        $recentActions = ActivityLog::where('causer_id', $user->id)
            ->where('created_at', '>', now()->subMinutes(5))
            ->count();
            
        if ($recentActions > 50) {
            $this->triggerSecurityAlert('High frequency access', $user, $action);
        }
        
        // 権限外リソースへのアクセス試行
        if (!$user->canAccess($resource)) {
            $this->triggerSecurityAlert('Unauthorized access attempt', $user, $resource);
        }
    }
}
```

## 6. バックアップ・復旧

### 6.1 バックアップセキュリティ

#### ✅ バックアップ保護
- [ ] バックアップデータが暗号化されている
- [ ] バックアップアクセスが制限されている
- [ ] オフサイトバックアップが実装されている
- [ ] バックアップの整合性チェックが実行されている

```bash
# 暗号化バックアップ
gpg --cipher-algo AES256 --compress-algo 1 --s2k-cipher-algo AES256 \
    --s2k-digest-algo SHA512 --s2k-mode 3 --s2k-count 65536 \
    --symmetric --output backup_encrypted.gpg backup_file.tar.gz
```

#### ✅ 復旧セキュリティ
- [ ] 復旧プロセスが文書化されている
- [ ] 復旧テストが定期的に実行されている
- [ ] 復旧時の権限確認が実装されている
- [ ] 復旧ログが記録されている

## 7. 設定ファイルセキュリティ

### 7.1 環境設定

#### ✅ 設定ファイル保護
- [ ] .envファイルがWebアクセス不可に設定されている
- [ ] 機密情報がハードコードされていない
- [ ] 設定ファイルの権限が適切に設定されている
- [ ] デバッグモードが本番環境で無効になっている

```bash
# ファイル権限設定
chmod 600 .env
chown www-data:www-data .env

# .htaccess
<Files ".env">
    Order allow,deny
    Deny from all
</Files>
```

```php
// config/app.php
'debug' => env('APP_DEBUG', false), // 本番環境ではfalse
'env' => env('APP_ENV', 'production'),
```

### 7.2 キー管理

#### ✅ 暗号化キー
- [ ] アプリケーションキーが適切に生成されている
- [ ] キーローテーションが実装されている
- [ ] キーが安全に保存されている
- [ ] キーアクセスが制限されている

```bash
# キー生成
php artisan key:generate

# キーローテーション（必要に応じて）
php artisan key:generate --show
# 新しいキーを.envに設定後
php artisan config:cache
```

## 8. 脆弱性対策

### 8.1 一般的な脆弱性

#### ✅ OWASP Top 10対策
- [ ] インジェクション攻撃対策
- [ ] 認証の不備対策
- [ ] 機密データ露出対策
- [ ] XML外部エンティティ（XXE）対策
- [ ] アクセス制御の不備対策
- [ ] セキュリティ設定ミス対策
- [ ] クロスサイトスクリプティング（XSS）対策
- [ ] 安全でないデシリアライゼーション対策
- [ ] 既知の脆弱性のあるコンポーネント使用対策
- [ ] 不十分なログ記録と監視対策

#### ✅ ファイルアップロード固有の脆弱性
- [ ] ファイル実行防止
- [ ] パストラバーサル攻撃防止
- [ ] ファイル上書き攻撃防止
- [ ] ファイルインクルージョン攻撃防止

```php
// パストラバーサル対策
private function sanitizeFilePath($path)
{
    // 危険な文字列を除去
    $path = str_replace(['../', '..\\', '../', '..\\'], '', $path);
    $path = preg_replace('/[^a-zA-Z0-9\-_\/\.]/', '', $path);
    
    // 絶対パスの確認
    $realPath = realpath($path);
    $allowedPath = realpath(storage_path('app/public/documents'));
    
    if (strpos($realPath, $allowedPath) !== 0) {
        throw new SecurityException('Invalid file path');
    }
    
    return $path;
}
```

## 9. 定期監査チェックリスト

### 9.1 月次チェック
- [ ] アクセスログの確認
- [ ] エラーログの確認
- [ ] ユーザー権限の確認
- [ ] 不要なファイルの削除
- [ ] セキュリティパッチの適用状況確認

### 9.2 四半期チェック
- [ ] 脆弱性スキャンの実行
- [ ] ペネトレーションテストの実行
- [ ] バックアップ復旧テスト
- [ ] セキュリティポリシーの見直し
- [ ] インシデント対応計画の確認

### 9.3 年次チェック
- [ ] 包括的なセキュリティ監査
- [ ] 第三者によるセキュリティ評価
- [ ] 災害復旧計画の見直し
- [ ] セキュリティ教育の実施
- [ ] コンプライアンス要件の確認

## 10. インシデント対応

### 10.1 インシデント検知

#### ✅ 検知システム
- [ ] リアルタイム監視が実装されている
- [ ] 自動アラートが設定されている
- [ ] エスカレーション手順が定義されている
- [ ] インシデント分類が明確になっている

### 10.2 対応手順

#### ✅ 初期対応
1. **インシデント確認**
   - [ ] インシデントの性質を特定
   - [ ] 影響範囲の評価
   - [ ] 緊急度の判定

2. **封じ込め**
   - [ ] 攻撃の停止
   - [ ] システムの隔離
   - [ ] 証拠の保全

3. **根絶**
   - [ ] 脆弱性の修正
   - [ ] マルウェアの除去
   - [ ] システムの強化

4. **復旧**
   - [ ] システムの復元
   - [ ] 動作確認
   - [ ] 監視強化

5. **事後対応**
   - [ ] インシデント報告書作成
   - [ ] 再発防止策の実装
   - [ ] 手順の見直し

## セキュリティチェック実行スクリプト

```bash
#!/bin/bash
# security-check.sh

echo "=== Document Management Security Check ==="
echo "Date: $(date)"
echo ""

# 1. ファイル権限チェック
echo "1. File Permissions Check"
echo "-------------------------"
find storage/app/public/documents -type f ! -perm 644 -ls
find storage/app/public/documents -type d ! -perm 755 -ls

# 2. 設定ファイルチェック
echo ""
echo "2. Configuration Files Check"
echo "----------------------------"
if [ -f .env ]; then
    PERM=$(stat -c "%a" .env)
    if [ "$PERM" != "600" ]; then
        echo "WARNING: .env file permissions are $PERM (should be 600)"
    else
        echo "OK: .env file permissions are correct"
    fi
fi

# 3. ログファイルチェック
echo ""
echo "3. Log Files Check"
echo "------------------"
if [ -f storage/logs/laravel.log ]; then
    ERRORS=$(grep -c "ERROR" storage/logs/laravel.log)
    echo "Error count in laravel.log: $ERRORS"
    
    if [ "$ERRORS" -gt 100 ]; then
        echo "WARNING: High error count detected"
    fi
fi

# 4. 不審なファイルチェック
echo ""
echo "4. Suspicious Files Check"
echo "-------------------------"
find storage/app/public/documents -name "*.php" -o -name "*.sh" -o -name "*.exe"

# 5. データベース接続チェック
echo ""
echo "5. Database Connection Check"
echo "----------------------------"
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection: OK';"

echo ""
echo "=== Security Check Completed ==="
```

---

**最終更新日**: 2024年12月
**バージョン**: 1.0
**作成者**: セキュリティチーム

**注意**: このチェックリストは定期的に見直し、最新のセキュリティ脅威に対応するよう更新してください。
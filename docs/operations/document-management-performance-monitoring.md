# ドキュメント管理システム パフォーマンス監視ガイド

## 概要

このガイドは、ドキュメント管理システムのパフォーマンス監視に関する包括的な手順を説明します。システムの健全性を維持し、パフォーマンス問題を早期に検出・対処するための監視項目と手順を定義します。

## 監視対象項目

### 1. システムリソース監視

#### 1.1 CPU使用率
- **正常範囲**: 平均70%以下
- **警告レベル**: 80%以上が5分継続
- **危険レベル**: 90%以上が1分継続

#### 1.2 メモリ使用率
- **正常範囲**: 80%以下
- **警告レベル**: 85%以上
- **危険レベル**: 95%以上

#### 1.3 ディスクI/O
- **正常範囲**: 使用率80%以下
- **警告レベル**: 使用率90%以上
- **危険レベル**: 使用率95%以上

#### 1.4 ネットワーク
- **正常範囲**: 帯域使用率70%以下
- **警告レベル**: 帯域使用率85%以上
- **危険レベル**: パケットロス1%以上

### 2. アプリケーション監視

#### 2.1 レスポンス時間
- **ファイル一覧表示**: 2秒以下
- **ファイルアップロード**: 10秒以下（10MB）
- **ファイルダウンロード**: 5秒以下（10MB）
- **フォルダ作成**: 1秒以下

#### 2.2 スループット
- **同時アップロード**: 10ファイル/分
- **同時ダウンロード**: 50ファイル/分
- **フォルダ操作**: 100操作/分

#### 2.3 エラー率
- **正常範囲**: 1%以下
- **警告レベル**: 5%以上
- **危険レベル**: 10%以上

### 3. データベース監視

#### 3.1 接続数
- **正常範囲**: 最大接続数の70%以下
- **警告レベル**: 最大接続数の85%以上
- **危険レベル**: 最大接続数の95%以上

#### 3.2 クエリ実行時間
- **正常範囲**: 平均1秒以下
- **警告レベル**: 平均3秒以上
- **危険レベル**: 平均5秒以上

#### 3.3 スロークエリ
- **正常範囲**: 1時間あたり10件以下
- **警告レベル**: 1時間あたり50件以上
- **危険レベル**: 1時間あたり100件以上

### 4. ストレージ監視

#### 4.1 ディスク使用量
- **正常範囲**: 80%以下
- **警告レベル**: 85%以上
- **危険レベル**: 95%以上

#### 4.2 ファイル数
- **フォルダあたり**: 1000ファイル以下推奨
- **施設あたり**: 10000ファイル以下推奨

#### 4.3 ファイルサイズ分布
- **小ファイル（1MB以下）**: 全体の60%以下
- **中ファイル（1-10MB）**: 全体の35%以下
- **大ファイル（10MB以上）**: 全体の5%以下

## 監視ツール設定

### 1. システム監視（Nagios）

#### 1.1 基本設定

```bash
# /etc/nagios/conf.d/document-management.cfg

# ホスト定義
define host {
    use                     linux-server
    host_name               doc-mgmt-server
    alias                   Document Management Server
    address                 192.168.1.100
}

# CPU監視
define service {
    use                     generic-service
    host_name               doc-mgmt-server
    service_description     CPU Load
    check_command           check_nrpe!check_load
    normal_check_interval   5
    retry_check_interval    1
}

# メモリ監視
define service {
    use                     generic-service
    host_name               doc-mgmt-server
    service_description     Memory Usage
    check_command           check_nrpe!check_memory
    normal_check_interval   5
    retry_check_interval    1
}

# ディスク監視
define service {
    use                     generic-service
    host_name               doc-mgmt-server
    service_description     Document Storage Space
    check_command           check_nrpe!check_disk_documents
    normal_check_interval   10
    retry_check_interval    2
}

# アプリケーション監視
define service {
    use                     generic-service
    host_name               doc-mgmt-server
    service_description     Document Upload Function
    check_command           check_http_post!/facilities/1/documents/files
    normal_check_interval   10
    retry_check_interval    2
}
```

#### 1.2 NRPE設定

```bash
# /etc/nagios/nrpe.cfg

# CPU負荷チェック
command[check_load]=/usr/lib/nagios/plugins/check_load -w 5,4,3 -c 10,8,6

# メモリ使用量チェック
command[check_memory]=/usr/lib/nagios/plugins/check_memory -w 80 -c 95

# ドキュメントストレージチェック
command[check_disk_documents]=/usr/lib/nagios/plugins/check_disk -w 15% -c 5% -p /var/www/html/storage/app/public/documents

# データベース接続チェック
command[check_mysql_documents]=/usr/lib/nagios/plugins/check_mysql -H localhost -u nagios -p password -d facility_management
```

### 2. アプリケーション監視（Zabbix）

#### 2.1 テンプレート設定

```json
{
    "zabbix_export": {
        "version": "5.0",
        "templates": [
            {
                "template": "Document Management System",
                "name": "Document Management System",
                "groups": [
                    {"name": "Applications"}
                ],
                "items": [
                    {
                        "name": "Document Upload Response Time",
                        "key": "web.test.rspcode[document_upload]",
                        "type": "SIMPLE",
                        "value_type": "FLOAT",
                        "units": "s",
                        "delay": "60s"
                    },
                    {
                        "name": "Active Document Sessions",
                        "key": "proc.num[php-fpm,www-data]",
                        "type": "ZABBIX_AGENT",
                        "value_type": "UNSIGNED_INT",
                        "delay": "60s"
                    },
                    {
                        "name": "Document Storage Usage",
                        "key": "vfs.fs.size[/var/www/html/storage/app/public/documents,pused]",
                        "type": "ZABBIX_AGENT",
                        "value_type": "FLOAT",
                        "units": "%",
                        "delay": "300s"
                    }
                ],
                "triggers": [
                    {
                        "expression": "{Document Management System:web.test.rspcode[document_upload].avg(300)}>5",
                        "name": "Document upload response time is too high",
                        "priority": "WARNING"
                    },
                    {
                        "expression": "{Document Management System:vfs.fs.size[/var/www/html/storage/app/public/documents,pused].last()}>85",
                        "name": "Document storage usage is high",
                        "priority": "WARNING"
                    }
                ]
            }
        ]
    }
}
```

### 3. データベース監視

#### 3.1 MySQL監視スクリプト

```bash
#!/bin/bash
# mysql-performance-monitor.sh

MYSQL_USER="monitor"
MYSQL_PASS="monitor_password"
MYSQL_HOST="localhost"
LOG_FILE="/var/log/mysql-performance.log"

# 接続数監視
CONNECTIONS=$(mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" -h"$MYSQL_HOST" -e "SHOW STATUS LIKE 'Threads_connected';" | awk 'NR==2 {print $2}')
MAX_CONNECTIONS=$(mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" -h"$MYSQL_HOST" -e "SHOW VARIABLES LIKE 'max_connections';" | awk 'NR==2 {print $2}')
CONNECTION_USAGE=$((CONNECTIONS * 100 / MAX_CONNECTIONS))

echo "$(date): Connections: $CONNECTIONS/$MAX_CONNECTIONS ($CONNECTION_USAGE%)" >> "$LOG_FILE"

# スロークエリ監視
SLOW_QUERIES=$(mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" -h"$MYSQL_HOST" -e "SHOW STATUS LIKE 'Slow_queries';" | awk 'NR==2 {print $2}')
echo "$(date): Slow queries: $SLOW_QUERIES" >> "$LOG_FILE"

# ドキュメント関連テーブルサイズ
FOLDERS_SIZE=$(mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" -h"$MYSQL_HOST" -e "SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size in MB' FROM information_schema.tables WHERE table_schema='facility_management' AND table_name='document_folders';" | awk 'NR==2 {print $1}')
FILES_SIZE=$(mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" -h"$MYSQL_HOST" -e "SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size in MB' FROM information_schema.tables WHERE table_schema='facility_management' AND table_name='document_files';" | awk 'NR==2 {print $1}')

echo "$(date): Table sizes - Folders: ${FOLDERS_SIZE}MB, Files: ${FILES_SIZE}MB" >> "$LOG_FILE"

# アラート判定
if [ "$CONNECTION_USAGE" -gt 85 ]; then
    echo "ALERT: High database connection usage: $CONNECTION_USAGE%" | mail -s "Database Alert" admin@example.com
fi
```

#### 3.2 クエリパフォーマンス分析

```sql
-- スロークエリ分析
SELECT 
    query_time,
    lock_time,
    rows_sent,
    rows_examined,
    sql_text
FROM mysql.slow_log 
WHERE sql_text LIKE '%document_%'
ORDER BY query_time DESC 
LIMIT 10;

-- インデックス使用状況
SELECT 
    table_name,
    index_name,
    cardinality,
    sub_part,
    packed,
    nullable,
    index_type
FROM information_schema.statistics 
WHERE table_schema = 'facility_management' 
AND table_name IN ('document_folders', 'document_files');

-- テーブル統計情報
SELECT 
    table_name,
    table_rows,
    data_length,
    index_length,
    (data_length + index_length) as total_size
FROM information_schema.tables 
WHERE table_schema = 'facility_management' 
AND table_name IN ('document_folders', 'document_files');
```

### 4. アプリケーション監視

#### 4.1 Laravel監視コマンド

```php
<?php
// app/Console/Commands/DocumentPerformanceMonitor.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\DocumentFolder;
use App\Models\DocumentFile;

class DocumentPerformanceMonitor extends Command
{
    protected $signature = 'documents:performance-monitor';
    protected $description = 'Monitor document management system performance';

    public function handle()
    {
        $this->info('Starting performance monitoring...');
        
        // データベースパフォーマンス
        $this->monitorDatabase();
        
        // ストレージパフォーマンス
        $this->monitorStorage();
        
        // アプリケーションパフォーマンス
        $this->monitorApplication();
        
        $this->info('Performance monitoring completed.');
    }

    private function monitorDatabase()
    {
        $this->info('Monitoring database performance...');
        
        // 接続数確認
        $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value;
        $maxConnections = DB::select("SHOW VARIABLES LIKE 'max_connections'")[0]->Value;
        $connectionUsage = ($connections / $maxConnections) * 100;
        
        $this->line("Database connections: {$connections}/{$maxConnections} ({$connectionUsage}%)");
        
        // テーブルサイズ確認
        $foldersCount = DocumentFolder::count();
        $filesCount = DocumentFile::count();
        
        $this->line("Records - Folders: {$foldersCount}, Files: {$filesCount}");
        
        // スロークエリ確認
        $slowQueries = DB::select("SHOW STATUS LIKE 'Slow_queries'")[0]->Value;
        $this->line("Slow queries: {$slowQueries}");
        
        // 警告判定
        if ($connectionUsage > 80) {
            $this->warn("WARNING: High database connection usage: {$connectionUsage}%");
        }
    }

    private function monitorStorage()
    {
        $this->info('Monitoring storage performance...');
        
        $documentsPath = storage_path('app/public/documents');
        
        if (is_dir($documentsPath)) {
            // ディスク使用量
            $totalSize = $this->getDirectorySize($documentsPath);
            $this->line("Total storage usage: " . $this->formatBytes($totalSize));
            
            // ファイル数統計
            $fileCount = $this->getFileCount($documentsPath);
            $this->line("Total files: {$fileCount}");
            
            // 大きなファイルの検出
            $largeFiles = $this->findLargeFiles($documentsPath, 10 * 1024 * 1024); // 10MB以上
            if (count($largeFiles) > 0) {
                $this->warn("Large files detected: " . count($largeFiles));
                foreach (array_slice($largeFiles, 0, 5) as $file) {
                    $this->line("  - " . basename($file) . " (" . $this->formatBytes(filesize($file)) . ")");
                }
            }
        }
    }

    private function monitorApplication()
    {
        $this->info('Monitoring application performance...');
        
        // メモリ使用量
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        
        $this->line("Memory usage: " . $this->formatBytes($memoryUsage));
        $this->line("Memory peak: " . $this->formatBytes($memoryPeak));
        
        // 実行時間測定（サンプルクエリ）
        $start = microtime(true);
        DocumentFolder::with('files')->take(10)->get();
        $queryTime = (microtime(true) - $start) * 1000;
        
        $this->line("Sample query time: {$queryTime}ms");
        
        if ($queryTime > 1000) {
            $this->warn("WARNING: Slow query detected: {$queryTime}ms");
        }
    }

    private function getDirectorySize($directory)
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            $size += $file->getSize();
        }
        
        return $size;
    }

    private function getFileCount($directory)
    {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }
        
        return $count;
    }

    private function findLargeFiles($directory, $sizeThreshold)
    {
        $largeFiles = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getSize() > $sizeThreshold) {
                $largeFiles[] = $file->getPathname();
            }
        }
        
        return $largeFiles;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
```

#### 4.2 パフォーマンスログ分析

```bash
#!/bin/bash
# analyze-performance-logs.sh

LOG_FILE="/var/log/nginx/access.log"
REPORT_FILE="/tmp/document-performance-report.txt"
DATE=$(date +%Y-%m-%d)

echo "Document Management Performance Report - $DATE" > "$REPORT_FILE"
echo "================================================" >> "$REPORT_FILE"

# ドキュメント関連リクエストの抽出
echo "1. Request Statistics" >> "$REPORT_FILE"
echo "---------------------" >> "$REPORT_FILE"

# 総リクエスト数
TOTAL_REQUESTS=$(grep "/documents" "$LOG_FILE" | wc -l)
echo "Total document requests: $TOTAL_REQUESTS" >> "$REPORT_FILE"

# レスポンス時間分析
echo "" >> "$REPORT_FILE"
echo "2. Response Time Analysis" >> "$REPORT_FILE"
echo "-------------------------" >> "$REPORT_FILE"

# 平均レスポンス時間（Nginxログの最後のフィールドがレスポンス時間の場合）
AVG_RESPONSE_TIME=$(grep "/documents" "$LOG_FILE" | awk '{print $NF}' | awk '{sum+=$1; count++} END {print sum/count}')
echo "Average response time: ${AVG_RESPONSE_TIME}s" >> "$REPORT_FILE"

# 遅いリクエスト（5秒以上）
SLOW_REQUESTS=$(grep "/documents" "$LOG_FILE" | awk '$NF > 5 {print}' | wc -l)
echo "Slow requests (>5s): $SLOW_REQUESTS" >> "$REPORT_FILE"

# エラー率
echo "" >> "$REPORT_FILE"
echo "3. Error Analysis" >> "$REPORT_FILE"
echo "-----------------" >> "$REPORT_FILE"

ERROR_4XX=$(grep "/documents" "$LOG_FILE" | grep " 4[0-9][0-9] " | wc -l)
ERROR_5XX=$(grep "/documents" "$LOG_FILE" | grep " 5[0-9][0-9] " | wc -l)
ERROR_RATE=$(echo "scale=2; ($ERROR_4XX + $ERROR_5XX) * 100 / $TOTAL_REQUESTS" | bc)

echo "4xx errors: $ERROR_4XX" >> "$REPORT_FILE"
echo "5xx errors: $ERROR_5XX" >> "$REPORT_FILE"
echo "Error rate: ${ERROR_RATE}%" >> "$REPORT_FILE"

# 最も頻繁なエンドポイント
echo "" >> "$REPORT_FILE"
echo "4. Most Frequent Endpoints" >> "$REPORT_FILE"
echo "---------------------------" >> "$REPORT_FILE"

grep "/documents" "$LOG_FILE" | awk '{print $7}' | sort | uniq -c | sort -nr | head -10 >> "$REPORT_FILE"

# レポート出力
cat "$REPORT_FILE"

# アラート判定
if (( $(echo "$ERROR_RATE > 5" | bc -l) )); then
    echo "ALERT: High error rate detected: ${ERROR_RATE}%" | mail -s "Performance Alert" admin@example.com
fi

if (( $(echo "$AVG_RESPONSE_TIME > 3" | bc -l) )); then
    echo "ALERT: High average response time: ${AVG_RESPONSE_TIME}s" | mail -s "Performance Alert" admin@example.com
fi
```

## パフォーマンス最適化

### 1. データベース最適化

#### 1.1 インデックス最適化

```sql
-- 使用頻度の高いクエリ用インデックス
CREATE INDEX idx_document_files_facility_folder ON document_files(facility_id, folder_id);
CREATE INDEX idx_document_files_created_at ON document_files(created_at DESC);
CREATE INDEX idx_document_folders_facility_parent ON document_folders(facility_id, parent_id);

-- 複合インデックス
CREATE INDEX idx_document_files_search ON document_files(facility_id, original_name, created_at);

-- 不要なインデックスの削除
-- DROP INDEX idx_unused_index ON document_files;
```

#### 1.2 クエリ最適化

```php
// N+1問題の解決
$folders = DocumentFolder::with(['files', 'children'])
    ->where('facility_id', $facilityId)
    ->where('parent_id', $parentId)
    ->get();

// ページネーション実装
$files = DocumentFile::where('facility_id', $facilityId)
    ->orderBy('created_at', 'desc')
    ->paginate(50);

// 集計クエリの最適化
$stats = DocumentFile::selectRaw('
        COUNT(*) as total_files,
        SUM(file_size) as total_size,
        AVG(file_size) as avg_size
    ')
    ->where('facility_id', $facilityId)
    ->first();
```

### 2. アプリケーション最適化

#### 2.1 キャッシュ戦略

```php
// config/cache.php
'stores' => [
    'documents' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'prefix' => 'doc_cache',
    ],
],

// フォルダ構造のキャッシュ
$folderStructure = Cache::store('documents')->remember(
    "folder_structure_{$facilityId}_{$folderId}",
    3600, // 1時間
    function () use ($facilityId, $folderId) {
        return $this->getFolderContents($facilityId, $folderId);
    }
);

// ファイル統計のキャッシュ
$stats = Cache::store('documents')->remember(
    "facility_stats_{$facilityId}",
    7200, // 2時間
    function () use ($facilityId) {
        return $this->calculateStorageStats($facilityId);
    }
);
```

#### 2.2 非同期処理

```php
// ファイル処理の非同期化
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessDocumentUpload implements ShouldQueue
{
    use Queueable, SerializesModels, InteractsWithQueue;

    public function handle()
    {
        // ファイル処理
        // サムネイル生成
        // ウイルススキャン
        // メタデータ抽出
    }
}

// 使用例
ProcessDocumentUpload::dispatch($file, $facility, $folder);
```

### 3. フロントエンド最適化

#### 3.1 遅延読み込み

```javascript
// 仮想スクロール実装
class VirtualScroll {
    constructor(container, itemHeight, renderItem) {
        this.container = container;
        this.itemHeight = itemHeight;
        this.renderItem = renderItem;
        this.visibleItems = [];
        this.scrollTop = 0;
        
        this.init();
    }
    
    init() {
        this.container.addEventListener('scroll', this.onScroll.bind(this));
        this.render();
    }
    
    onScroll() {
        this.scrollTop = this.container.scrollTop;
        this.render();
    }
    
    render() {
        const containerHeight = this.container.clientHeight;
        const startIndex = Math.floor(this.scrollTop / this.itemHeight);
        const endIndex = Math.min(
            startIndex + Math.ceil(containerHeight / this.itemHeight) + 1,
            this.items.length
        );
        
        // 表示アイテムの更新
        this.updateVisibleItems(startIndex, endIndex);
    }
}
```

#### 3.2 プリロード戦略

```javascript
// ファイルプレビューのプリロード
class DocumentPreloader {
    constructor() {
        this.preloadCache = new Map();
        this.preloadQueue = [];
    }
    
    preloadFile(fileId, priority = 'low') {
        if (this.preloadCache.has(fileId)) {
            return Promise.resolve(this.preloadCache.get(fileId));
        }
        
        const preloadPromise = this.fetchFilePreview(fileId);
        this.preloadCache.set(fileId, preloadPromise);
        
        return preloadPromise;
    }
    
    async fetchFilePreview(fileId) {
        const response = await fetch(`/documents/files/${fileId}/preview`);
        const blob = await response.blob();
        return URL.createObjectURL(blob);
    }
}
```

## アラート設定

### 1. しきい値設定

```yaml
# alerts.yml
alerts:
  system:
    cpu_usage:
      warning: 80
      critical: 90
    memory_usage:
      warning: 85
      critical: 95
    disk_usage:
      warning: 85
      critical: 95
  
  application:
    response_time:
      warning: 3.0
      critical: 5.0
    error_rate:
      warning: 5.0
      critical: 10.0
    upload_failure_rate:
      warning: 10.0
      critical: 20.0
  
  database:
    connection_usage:
      warning: 80
      critical: 90
    slow_query_count:
      warning: 50
      critical: 100
    query_time:
      warning: 2.0
      critical: 5.0
```

### 2. 通知設定

```php
// config/notifications.php
'channels' => [
    'email' => [
        'driver' => 'mail',
        'from' => 'alerts@example.com',
        'to' => ['admin@example.com', 'dev@example.com'],
    ],
    'slack' => [
        'driver' => 'slack',
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
        'channel' => '#alerts',
    ],
],

'alert_rules' => [
    'high_cpu' => [
        'channels' => ['email', 'slack'],
        'threshold' => 80,
        'duration' => 300, // 5分
    ],
    'slow_response' => [
        'channels' => ['slack'],
        'threshold' => 3.0,
        'duration' => 60, // 1分
    ],
],
```

## レポート生成

### 1. 日次レポート

```bash
#!/bin/bash
# daily-performance-report.sh

DATE=$(date +%Y-%m-%d)
REPORT_FILE="/reports/daily_performance_$DATE.html"

cat > "$REPORT_FILE" << EOF
<!DOCTYPE html>
<html>
<head>
    <title>Daily Performance Report - $DATE</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .warning { color: orange; }
        .critical { color: red; }
    </style>
</head>
<body>
    <h1>Document Management System - Daily Performance Report</h1>
    <h2>Date: $DATE</h2>
    
    <h3>System Metrics</h3>
    <table>
        <tr><th>Metric</th><th>Value</th><th>Status</th></tr>
EOF

# システムメトリクス取得
CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | sed 's/%us,//')
MEMORY_USAGE=$(free | grep Mem | awk '{printf "%.1f", $3/$2 * 100.0}')
DISK_USAGE=$(df -h /var/www/html/storage | awk 'NR==2 {print $5}' | sed 's/%//')

echo "        <tr><td>CPU Usage</td><td>${CPU_USAGE}%</td><td>$([ ${CPU_USAGE%.*} -gt 80 ] && echo '<span class="warning">WARNING</span>' || echo 'OK')</td></tr>" >> "$REPORT_FILE"
echo "        <tr><td>Memory Usage</td><td>${MEMORY_USAGE}%</td><td>$([ ${MEMORY_USAGE%.*} -gt 85 ] && echo '<span class="warning">WARNING</span>' || echo 'OK')</td></tr>" >> "$REPORT_FILE"
echo "        <tr><td>Disk Usage</td><td>${DISK_USAGE}%</td><td>$([ $DISK_USAGE -gt 85 ] && echo '<span class="warning">WARNING</span>' || echo 'OK')</td></tr>" >> "$REPORT_FILE"

cat >> "$REPORT_FILE" << EOF
    </table>
    
    <h3>Application Metrics</h3>
    <p>Generated at: $(date)</p>
</body>
</html>
EOF

echo "Daily report generated: $REPORT_FILE"
```

### 2. 週次レポート

```php
<?php
// app/Console/Commands/WeeklyPerformanceReport.php

class WeeklyPerformanceReport extends Command
{
    protected $signature = 'documents:weekly-report';
    
    public function handle()
    {
        $startDate = now()->subWeek()->startOfWeek();
        $endDate = now()->subWeek()->endOfWeek();
        
        $report = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'uploads' => $this->getUploadStats($startDate, $endDate),
            'downloads' => $this->getDownloadStats($startDate, $endDate),
            'storage' => $this->getStorageStats(),
            'performance' => $this->getPerformanceStats($startDate, $endDate),
        ];
        
        $this->generateReport($report);
    }
    
    private function getUploadStats($startDate, $endDate)
    {
        return [
            'total_uploads' => DocumentFile::whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_size' => DocumentFile::whereBetween('created_at', [$startDate, $endDate])->sum('file_size'),
            'avg_file_size' => DocumentFile::whereBetween('created_at', [$startDate, $endDate])->avg('file_size'),
            'by_type' => DocumentFile::whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('file_extension')
                ->selectRaw('file_extension, count(*) as count')
                ->get(),
        ];
    }
}
```

---

**最終更新日**: 2024年12月
**バージョン**: 1.0
**作成者**: システム管理チーム
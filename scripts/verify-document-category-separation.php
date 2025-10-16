#!/usr/bin/env php
<?php

/**
 * ドキュメントカテゴリ分離の検証スクリプト
 * 
 * 使用方法:
 * php scripts/verify-document-category-separation.php [facility_id]
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Facility;
use App\Models\DocumentFolder;
use App\Models\DocumentFile;
use Illuminate\Support\Facades\DB;

// コマンドライン引数から施設IDを取得
$facilityId = $argv[1] ?? null;

if (!$facilityId) {
    echo "使用方法: php scripts/verify-document-category-separation.php [facility_id]\n";
    exit(1);
}

// 施設の存在確認
$facility = Facility::find($facilityId);
if (!$facility) {
    echo "エラー: 施設ID {$facilityId} が見つかりません。\n";
    exit(1);
}

echo "========================================\n";
echo "ドキュメントカテゴリ分離検証\n";
echo "========================================\n";
echo "施設ID: {$facility->id}\n";
echo "施設名: {$facility->facility_name}\n";
echo "========================================\n\n";

// 1. フォルダのカテゴリ別集計
echo "【1. フォルダのカテゴリ別集計】\n";
$folderStats = DB::table('document_folders')
    ->select(
        DB::raw("CASE 
            WHEN category IS NULL THEN 'メインドキュメント'
            WHEN category LIKE 'lifeline_%' THEN 'ライフライン設備'
            WHEN category LIKE 'maintenance_%' THEN '修繕履歴'
            ELSE 'その他'
        END as category_type"),
        'category',
        DB::raw('COUNT(*) as count')
    )
    ->where('facility_id', $facilityId)
    ->groupBy('category')
    ->orderBy('category_type')
    ->orderBy('category')
    ->get();

if ($folderStats->isEmpty()) {
    echo "  フォルダが見つかりません。\n";
} else {
    foreach ($folderStats as $stat) {
        $categoryDisplay = $stat->category ?? 'NULL (メインドキュメント)';
        echo "  {$stat->category_type}: {$categoryDisplay} - {$stat->count}個\n";
    }
}
echo "\n";

// 2. ファイルのカテゴリ別集計
echo "【2. ファイルのカテゴリ別集計】\n";
$fileStats = DB::table('document_files')
    ->select(
        DB::raw("CASE 
            WHEN category IS NULL THEN 'メインドキュメント'
            WHEN category LIKE 'lifeline_%' THEN 'ライフライン設備'
            WHEN category LIKE 'maintenance_%' THEN '修繕履歴'
            ELSE 'その他'
        END as category_type"),
        'category',
        DB::raw('COUNT(*) as count')
    )
    ->where('facility_id', $facilityId)
    ->groupBy('category')
    ->orderBy('category_type')
    ->orderBy('category')
    ->get();

if ($fileStats->isEmpty()) {
    echo "  ファイルが見つかりません。\n";
} else {
    foreach ($fileStats as $stat) {
        $categoryDisplay = $stat->category ?? 'NULL (メインドキュメント)';
        echo "  {$stat->category_type}: {$categoryDisplay} - {$stat->count}個\n";
    }
}
echo "\n";

// 3. カテゴリ別の詳細情報
echo "【3. カテゴリ別の詳細情報】\n\n";

// 3.1 メインドキュメント
echo "  3.1 メインドキュメント (category IS NULL)\n";
$mainFolders = DocumentFolder::main()
    ->where('facility_id', $facilityId)
    ->get();
echo "    フォルダ数: {$mainFolders->count()}\n";
if ($mainFolders->isNotEmpty()) {
    foreach ($mainFolders as $folder) {
        echo "      - {$folder->name} (ID: {$folder->id})\n";
    }
}

$mainFiles = DocumentFile::main()
    ->where('facility_id', $facilityId)
    ->get();
echo "    ファイル数: {$mainFiles->count()}\n";
if ($mainFiles->isNotEmpty()) {
    foreach ($mainFiles as $file) {
        echo "      - {$file->original_name} (ID: {$file->id})\n";
    }
}
echo "\n";

// 3.2 ライフライン設備
echo "  3.2 ライフライン設備 (category LIKE 'lifeline_%')\n";
$lifelineCategories = ['electrical', 'gas', 'water', 'elevator', 'hvac_lighting'];
foreach ($lifelineCategories as $category) {
    $categoryValue = "lifeline_{$category}";
    $folders = DocumentFolder::lifeline($category)
        ->where('facility_id', $facilityId)
        ->get();
    
    echo "    {$categoryValue}: {$folders->count()}個のフォルダ\n";
    if ($folders->isNotEmpty()) {
        foreach ($folders as $folder) {
            echo "      - {$folder->name} (ID: {$folder->id})\n";
        }
    }
}
echo "\n";

// 3.3 修繕履歴
echo "  3.3 修繕履歴 (category LIKE 'maintenance_%')\n";
$maintenanceCategories = ['exterior', 'interior', 'summer_condensation', 'other'];
foreach ($maintenanceCategories as $category) {
    $categoryValue = "maintenance_{$category}";
    $folders = DocumentFolder::maintenance($category)
        ->where('facility_id', $facilityId)
        ->get();
    
    echo "    {$categoryValue}: {$folders->count()}個のフォルダ\n";
    if ($folders->isNotEmpty()) {
        foreach ($folders as $folder) {
            echo "      - {$folder->name} (ID: {$folder->id})\n";
        }
    }
}
echo "\n";

// 4. カテゴリ不一致の検出
echo "【4. カテゴリ不一致の検出】\n";
$inconsistencies = [];

// フォルダとファイルのカテゴリ不一致をチェック
$filesWithFolders = DocumentFile::where('facility_id', $facilityId)
    ->whereNotNull('folder_id')
    ->with('folder')
    ->get();

foreach ($filesWithFolders as $file) {
    if ($file->folder && $file->category !== $file->folder->category) {
        $inconsistencies[] = [
            'type' => 'ファイル-フォルダ不一致',
            'file_id' => $file->id,
            'file_name' => $file->original_name,
            'file_category' => $file->category ?? 'NULL',
            'folder_id' => $file->folder->id,
            'folder_name' => $file->folder->name,
            'folder_category' => $file->folder->category ?? 'NULL',
        ];
    }
}

if (empty($inconsistencies)) {
    echo "  ✓ カテゴリ不一致は検出されませんでした。\n";
} else {
    echo "  ✗ カテゴリ不一致が検出されました:\n";
    foreach ($inconsistencies as $issue) {
        echo "    - {$issue['type']}\n";
        echo "      ファイル: {$issue['file_name']} (ID: {$issue['file_id']}, category: {$issue['file_category']})\n";
        echo "      フォルダ: {$issue['folder_name']} (ID: {$issue['folder_id']}, category: {$issue['folder_category']})\n";
    }
}
echo "\n";

// 5. インデックスの使用確認
echo "【5. インデックスの使用確認】\n";
echo "  メインドキュメントクエリ:\n";
$explainMain = DB::select("EXPLAIN SELECT * FROM document_folders WHERE facility_id = ? AND category IS NULL", [$facilityId]);
foreach ($explainMain as $row) {
    $rowArray = (array) $row;
    echo "    key: " . ($rowArray['key'] ?? 'なし') . "\n";
    echo "    possible_keys: " . ($rowArray['possible_keys'] ?? 'なし') . "\n";
}
echo "\n";

echo "  ライフライン設備クエリ:\n";
$explainLifeline = DB::select("EXPLAIN SELECT * FROM document_folders WHERE facility_id = ? AND category = 'lifeline_electrical'", [$facilityId]);
foreach ($explainLifeline as $row) {
    $rowArray = (array) $row;
    echo "    key: " . ($rowArray['key'] ?? 'なし') . "\n";
    echo "    possible_keys: " . ($rowArray['possible_keys'] ?? 'なし') . "\n";
}
echo "\n";

// 6. 検証結果サマリー
echo "========================================\n";
echo "検証結果サマリー\n";
echo "========================================\n";

$totalFolders = DocumentFolder::where('facility_id', $facilityId)->count();
$totalFiles = DocumentFile::where('facility_id', $facilityId)->count();
$mainFoldersCount = DocumentFolder::main()->where('facility_id', $facilityId)->count();
$lifelineFoldersCount = DocumentFolder::where('facility_id', $facilityId)
    ->where('category', 'like', 'lifeline_%')->count();
$maintenanceFoldersCount = DocumentFolder::where('facility_id', $facilityId)
    ->where('category', 'like', 'maintenance_%')->count();

echo "総フォルダ数: {$totalFolders}\n";
echo "  - メインドキュメント: {$mainFoldersCount}\n";
echo "  - ライフライン設備: {$lifelineFoldersCount}\n";
echo "  - 修繕履歴: {$maintenanceFoldersCount}\n";
echo "総ファイル数: {$totalFiles}\n";
echo "カテゴリ不一致: " . (empty($inconsistencies) ? "なし ✓" : count($inconsistencies) . "件 ✗") . "\n";
echo "\n";

if (empty($inconsistencies) && $totalFolders > 0) {
    echo "✓ カテゴリ分離が正しく機能しています。\n";
} elseif ($totalFolders === 0) {
    echo "⚠ フォルダが作成されていません。手動でフォルダを作成して再度検証してください。\n";
} else {
    echo "✗ カテゴリ分離に問題があります。上記の詳細を確認してください。\n";
}

echo "========================================\n";

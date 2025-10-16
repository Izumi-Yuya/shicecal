#!/usr/bin/env php
<?php

/**
 * 契約書ドキュメント管理実装検証スクリプト
 * 
 * このスクリプトは、契約書のモーダルベースドキュメント管理システムが
 * 正しく実装されているかを検証します。
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class ContractDocumentImplementationVerifier
{
    private array $results = [];
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "\n";
        echo "==============================================\n";
        echo "  契約書ドキュメント管理実装検証\n";
        echo "==============================================\n\n";

        $this->verifyBladeComponent();
        $this->verifyJavaScriptManager();
        $this->verifyController();
        $this->verifyService();
        $this->verifyRoutes();
        $this->verifyViewUsage();
        $this->verifyAppUnifiedImport();
        $this->verifyDatabaseTables();

        $this->printSummary();
    }

    private function verifyBladeComponent(): void
    {
        echo "📄 Bladeコンポーネントの確認...\n";

        $componentPath = base_path('resources/views/components/contract-document-manager.blade.php');
        
        if (File::exists($componentPath)) {
            $content = File::get($componentPath);
            
            // 必須要素の確認
            $requiredElements = [
                'document-management' => 'メインコンテナ',
                'create-folder-modal-contracts' => 'フォルダ作成モーダル',
                'upload-file-modal-contracts' => 'ファイルアップロードモーダル',
                'rename-modal-contracts' => '名前変更モーダル',
                'properties-modal-contracts' => 'プロパティモーダル',
                'context-menu-contracts' => 'コンテキストメニュー',
                'breadcrumb-nav-contracts' => 'パンくずナビゲーション',
            ];

            $allFound = true;
            foreach ($requiredElements as $id => $name) {
                if (strpos($content, $id) === false) {
                    $this->fail("  ❌ {$name} ({$id}) が見つかりません");
                    $allFound = false;
                }
            }

            if ($allFound) {
                $this->pass("  ✅ Bladeコンポーネントが正しく実装されています");
            }
        } else {
            $this->fail("  ❌ Bladeコンポーネントが見つかりません: {$componentPath}");
        }

        echo "\n";
    }

    private function verifyJavaScriptManager(): void
    {
        echo "📜 JavaScriptマネージャーの確認...\n";

        $jsPath = base_path('resources/js/modules/ContractDocumentManager.js');
        
        if (File::exists($jsPath)) {
            $content = File::get($jsPath);
            
            // 必須メソッドの確認
            $requiredMethods = [
                'constructor' => 'コンストラクタ',
                'init' => '初期化',
                'setupLazyLoading' => '遅延ロード',
                'loadDocuments' => 'ドキュメント読み込み',
                'renderDocuments' => 'ドキュメント表示',
                'handleCreateFolder' => 'フォルダ作成',
                'handleUploadFile' => 'ファイルアップロード',
                'handleRename' => '名前変更',
                'handleDelete' => '削除',
                'showContextMenu' => 'コンテキストメニュー',
                'handleSearch' => '検索',
                'handleRetry' => '再試行',
            ];

            $allFound = true;
            foreach ($requiredMethods as $method => $name) {
                if (strpos($content, $method) === false) {
                    $this->fail("  ❌ {$name}メソッド ({$method}) が見つかりません");
                    $allFound = false;
                }
            }

            if ($allFound) {
                $this->pass("  ✅ JavaScriptマネージャーが正しく実装されています");
            }

            // グローバル公開の確認
            if (strpos($content, 'window.ContractDocumentManager') !== false) {
                $this->pass("  ✅ グローバルに公開されています");
            } else {
                $this->fail("  ❌ グローバルに公開されていません");
            }

            // ES6エクスポートの確認
            if (strpos($content, 'export default ContractDocumentManager') !== false) {
                $this->pass("  ✅ ES6モジュールとしてエクスポートされています");
            } else {
                $this->fail("  ❌ ES6モジュールとしてエクスポートされていません");
            }
        } else {
            $this->fail("  ❌ JavaScriptマネージャーが見つかりません: {$jsPath}");
        }

        echo "\n";
    }

    private function verifyController(): void
    {
        echo "🎮 コントローラーの確認...\n";

        $controllerPath = base_path('app/Http/Controllers/ContractDocumentController.php');
        
        if (File::exists($controllerPath)) {
            $content = File::get($controllerPath);
            
            // 必須メソッドの確認
            $requiredMethods = [
                'index' => 'ドキュメント一覧',
                'uploadFile' => 'ファイルアップロード',
                'downloadFile' => 'ファイルダウンロード',
                'deleteFile' => 'ファイル削除',
                'createFolder' => 'フォルダ作成',
                'renameFolder' => 'フォルダ名変更',
                'deleteFolder' => 'フォルダ削除',
                'renameFile' => 'ファイル名変更',
            ];

            $allFound = true;
            foreach ($requiredMethods as $method => $name) {
                if (strpos($content, "function {$method}") === false) {
                    $this->fail("  ❌ {$name}メソッド ({$method}) が見つかりません");
                    $allFound = false;
                }
            }

            if ($allFound) {
                $this->pass("  ✅ コントローラーが正しく実装されています");
            }

            // トレイトの使用確認
            if (strpos($content, 'use HandlesApiResponses') !== false) {
                $this->pass("  ✅ HandlesApiResponsesトレイトを使用しています");
            } else {
                $this->fail("  ❌ HandlesApiResponsesトレイトを使用していません");
            }
        } else {
            $this->fail("  ❌ コントローラーが見つかりません: {$controllerPath}");
        }

        echo "\n";
    }

    private function verifyService(): void
    {
        echo "⚙️  サービスの確認...\n";

        $servicePath = base_path('app/Services/ContractDocumentService.php');
        
        if (File::exists($servicePath)) {
            $content = File::get($servicePath);
            
            // 必須メソッドの確認
            $requiredMethods = [
                'getCategoryDocuments' => 'ドキュメント取得',
                'uploadCategoryFile' => 'ファイルアップロード',
                'createCategoryFolder' => 'フォルダ作成',
            ];

            $allFound = true;
            foreach ($requiredMethods as $method => $name) {
                if (strpos($content, "function {$method}") === false) {
                    $this->fail("  ❌ {$name}メソッド ({$method}) が見つかりません");
                    $allFound = false;
                }
            }

            if ($allFound) {
                $this->pass("  ✅ サービスが正しく実装されています");
            }
        } else {
            $this->fail("  ❌ サービスが見つかりません: {$servicePath}");
        }

        echo "\n";
    }

    private function verifyRoutes(): void
    {
        echo "🛣️  ルートの確認...\n";

        $requiredRoutes = [
            'facilities.contract-documents.index' => 'GET',
            'facilities.contract-documents.upload' => 'POST',
            'facilities.contract-documents.download-file' => 'GET',
            'facilities.contract-documents.delete-file' => 'DELETE',
            'facilities.contract-documents.rename-file' => 'PUT',
            'facilities.contract-documents.create-folder' => 'POST',
            'facilities.contract-documents.rename-folder' => 'PUT',
            'facilities.contract-documents.delete-folder' => 'DELETE',
        ];

        $allFound = true;
        foreach ($requiredRoutes as $routeName => $method) {
            if (Route::has($routeName)) {
                $this->pass("  ✅ {$routeName} ({$method})");
            } else {
                $this->fail("  ❌ {$routeName} ({$method}) が見つかりません");
                $allFound = false;
            }
        }

        if ($allFound) {
            echo "  ✅ すべてのルートが正しく定義されています\n";
        }

        echo "\n";
    }

    private function verifyViewUsage(): void
    {
        echo "👁️  ビューでの使用確認...\n";

        $viewPath = base_path('resources/views/facilities/contracts/index.blade.php');
        
        if (File::exists($viewPath)) {
            $content = File::get($viewPath);
            
            // コンポーネントの使用確認
            if (strpos($content, '<x-contract-document-manager') !== false) {
                $this->pass("  ✅ コンポーネントが使用されています");
            } else {
                $this->fail("  ❌ コンポーネントが使用されていません");
            }

            // 折りたたみセクションの確認
            if (strpos($content, 'unified-documents-section') !== false) {
                $this->pass("  ✅ 折りたたみセクションが実装されています");
            } else {
                $this->fail("  ❌ 折りたたみセクションが実装されていません");
            }

            // トグルボタンの確認
            if (strpos($content, 'unified-documents-toggle') !== false) {
                $this->pass("  ✅ トグルボタンが実装されています");
            } else {
                $this->fail("  ❌ トグルボタンが実装されていません");
            }
        } else {
            $this->fail("  ❌ ビューファイルが見つかりません: {$viewPath}");
        }

        echo "\n";
    }

    private function verifyAppUnifiedImport(): void
    {
        echo "📦 app-unified.jsでのインポート確認...\n";

        $appUnifiedPath = base_path('resources/js/app-unified.js');
        
        if (File::exists($appUnifiedPath)) {
            $content = File::get($appUnifiedPath);
            
            // インポート文の確認
            if (strpos($content, "import ContractDocumentManager from './modules/ContractDocumentManager.js'") !== false) {
                $this->pass("  ✅ ContractDocumentManagerがインポートされています");
            } else {
                $this->fail("  ❌ ContractDocumentManagerがインポートされていません");
            }

            // グローバル公開の確認
            if (strpos($content, 'window.ContractDocumentManager = ContractDocumentManager') !== false) {
                $this->pass("  ✅ グローバルに公開されています");
            } else {
                $this->fail("  ❌ グローバルに公開されていません");
            }
        } else {
            $this->fail("  ❌ app-unified.jsが見つかりません: {$appUnifiedPath}");
        }

        echo "\n";
    }

    private function verifyDatabaseTables(): void
    {
        echo "🗄️  データベーステーブルの確認...\n";

        try {
            // document_foldersテーブルの確認
            if (\Schema::hasTable('document_folders')) {
                $this->pass("  ✅ document_foldersテーブルが存在します");
                
                $requiredColumns = ['id', 'facility_id', 'category', 'parent_id', 'name', 'created_by'];
                $allColumnsExist = true;
                
                foreach ($requiredColumns as $column) {
                    if (!\Schema::hasColumn('document_folders', $column)) {
                        $this->fail("    ❌ カラム '{$column}' が見つかりません");
                        $allColumnsExist = false;
                    }
                }
                
                if ($allColumnsExist) {
                    $this->pass("  ✅ 必要なカラムがすべて存在します");
                }
            } else {
                $this->fail("  ❌ document_foldersテーブルが見つかりません");
            }

            // document_filesテーブルの確認
            if (\Schema::hasTable('document_files')) {
                $this->pass("  ✅ document_filesテーブルが存在します");
                
                $requiredColumns = ['id', 'facility_id', 'category', 'folder_id', 'original_name', 'stored_name', 'file_path', 'uploaded_by'];
                $allColumnsExist = true;
                
                foreach ($requiredColumns as $column) {
                    if (!\Schema::hasColumn('document_files', $column)) {
                        $this->fail("    ❌ カラム '{$column}' が見つかりません");
                        $allColumnsExist = false;
                    }
                }
                
                if ($allColumnsExist) {
                    $this->pass("  ✅ 必要なカラムがすべて存在します");
                }
            } else {
                $this->fail("  ❌ document_filesテーブルが見つかりません");
            }
        } catch (\Exception $e) {
            $this->fail("  ❌ データベース接続エラー: " . $e->getMessage());
        }

        echo "\n";
    }

    private function pass(string $message): void
    {
        echo $message . "\n";
        $this->passed++;
    }

    private function fail(string $message): void
    {
        echo $message . "\n";
        $this->failed++;
    }

    private function printSummary(): void
    {
        echo "==============================================\n";
        echo "  検証結果サマリー\n";
        echo "==============================================\n\n";

        $total = $this->passed + $this->failed;
        $percentage = $total > 0 ? round(($this->passed / $total) * 100, 2) : 0;

        echo "✅ 成功: {$this->passed}\n";
        echo "❌ 失敗: {$this->failed}\n";
        echo "📊 成功率: {$percentage}%\n\n";

        if ($this->failed === 0) {
            echo "🎉 すべての検証に合格しました！\n";
            echo "契約書のモーダルベースドキュメント管理システムは正しく実装されています。\n\n";
            exit(0);
        } else {
            echo "⚠️  いくつかの検証に失敗しました。\n";
            echo "上記のエラーメッセージを確認して、必要な修正を行ってください。\n\n";
            exit(1);
        }
    }
}

// 検証実行
$verifier = new ContractDocumentImplementationVerifier();
$verifier->run();

#!/usr/bin/env php
<?php

/**
 * 契約書ドキュメント管理機能の検証スクリプト
 * 
 * このスクリプトは契約書ドキュメント管理機能の実装を検証します。
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Facility;
use App\Models\User;
use App\Models\DocumentFolder;
use App\Models\DocumentFile;
use App\Services\ContractDocumentService;
use Illuminate\Support\Facades\DB;

class ContractDocumentVerifier
{
    private $results = [];
    private $errors = [];
    private $warnings = [];

    public function run()
    {
        echo "\n";
        echo "==============================================\n";
        echo "  契約書ドキュメント管理機能 検証スクリプト\n";
        echo "==============================================\n\n";

        $this->verifyDatabaseStructure();
        $this->verifyModels();
        $this->verifyService();
        $this->verifyController();
        $this->verifyRoutes();
        $this->verifyViews();
        $this->verifyJavaScript();
        $this->verifyCSS();
        $this->verifyCategorySeparation();
        $this->verifyDocumentation();

        $this->printResults();
    }

    private function verifyDatabaseStructure()
    {
        echo "📊 データベース構造の検証...\n";

        try {
            // document_foldersテーブルの確認
            $folderColumns = DB::select("SHOW COLUMNS FROM document_folders");
            $hasCategoryColumn = collect($folderColumns)->contains('Field', 'category');
            
            if ($hasCategoryColumn) {
                $this->addResult('✓ document_foldersテーブルにcategoryカラムが存在');
            } else {
                $this->addError('✗ document_foldersテーブルにcategoryカラムが存在しません');
            }

            // document_filesテーブルの確認
            $fileColumns = DB::select("SHOW COLUMNS FROM document_files");
            $hasCategoryColumn = collect($fileColumns)->contains('Field', 'category');
            
            if ($hasCategoryColumn) {
                $this->addResult('✓ document_filesテーブルにcategoryカラムが存在');
            } else {
                $this->addError('✗ document_filesテーブルにcategoryカラムが存在しません');
            }

            // インデックスの確認
            $folderIndexes = DB::select("SHOW INDEX FROM document_folders WHERE Column_name = 'category'");
            if (count($folderIndexes) > 0) {
                $this->addResult('✓ document_folders.categoryにインデックスが存在');
            } else {
                $this->addWarning('⚠ document_folders.categoryにインデックスがありません（パフォーマンス最適化推奨）');
            }

        } catch (\Exception $e) {
            $this->addError('✗ データベース構造の検証エラー: ' . $e->getMessage());
        }
    }

    private function verifyModels()
    {
        echo "\n📦 モデルの検証...\n";

        try {
            // DocumentFolderモデルのスコープ確認
            if (method_exists(DocumentFolder::class, 'scopeContracts')) {
                $this->addResult('✓ DocumentFolderモデルにcontracts()スコープが存在');
                
                // スコープの動作確認
                $testQuery = DocumentFolder::contracts()->toSql();
                if (str_contains($testQuery, "category") && str_contains($testQuery, "contracts")) {
                    $this->addResult('✓ contracts()スコープが正しく動作');
                } else {
                    $this->addError('✗ contracts()スコープのクエリが不正');
                }
            } else {
                $this->addError('✗ DocumentFolderモデルにcontracts()スコープが存在しません');
            }

            // DocumentFileモデルのスコープ確認
            if (method_exists(DocumentFile::class, 'scopeContracts')) {
                $this->addResult('✓ DocumentFileモデルにcontracts()スコープが存在');
                
                // スコープの動作確認
                $testQuery = DocumentFile::contracts()->toSql();
                if (str_contains($testQuery, "category") && str_contains($testQuery, "contracts")) {
                    $this->addResult('✓ contracts()スコープが正しく動作');
                } else {
                    $this->addError('✗ contracts()スコープのクエリが不正');
                }
            } else {
                $this->addError('✗ DocumentFileモデルにcontracts()スコープが存在しません');
            }

        } catch (\Exception $e) {
            $this->addError('✗ モデルの検証エラー: ' . $e->getMessage());
        }
    }

    private function verifyService()
    {
        echo "\n🔧 サービスクラスの検証...\n";

        try {
            // ContractDocumentServiceの存在確認
            if (class_exists('App\Services\ContractDocumentService')) {
                $this->addResult('✓ ContractDocumentServiceクラスが存在');

                $service = app(ContractDocumentService::class);

                // 必須メソッドの確認
                $requiredMethods = [
                    'getOrCreateCategoryRootFolder',
                    'getCategoryDocuments',
                    'uploadCategoryFile',
                    'createCategoryFolder',
                    'getCategoryStats',
                    'searchCategoryFiles',
                ];

                foreach ($requiredMethods as $method) {
                    if (method_exists($service, $method)) {
                        $this->addResult("✓ {$method}()メソッドが存在");
                    } else {
                        $this->addError("✗ {$method}()メソッドが存在しません");
                    }
                }

                // 定数の確認
                if (defined('App\Services\ContractDocumentService::CATEGORY')) {
                    $category = ContractDocumentService::CATEGORY;
                    if ($category === 'contracts') {
                        $this->addResult('✓ CATEGORYが正しく定義されています');
                    } else {
                        $this->addError("✗ CATEGORYの値が不正です: {$category}");
                    }
                } else {
                    $this->addError('✗ CATEGORY定数が定義されていません');
                }

            } else {
                $this->addError('✗ ContractDocumentServiceクラスが存在しません');
            }

        } catch (\Exception $e) {
            $this->addError('✗ サービスの検証エラー: ' . $e->getMessage());
        }
    }

    private function verifyController()
    {
        echo "\n🎮 コントローラーの検証...\n";

        try {
            // ContractDocumentControllerの存在確認
            if (class_exists('App\Http\Controllers\ContractDocumentController')) {
                $this->addResult('✓ ContractDocumentControllerクラスが存在');

                $controller = new \App\Http\Controllers\ContractDocumentController(
                    app(ContractDocumentService::class),
                    app(\App\Services\DocumentService::class)
                );

                // 必須メソッドの確認
                $requiredMethods = [
                    'index',
                    'uploadFile',
                    'createFolder',
                    'downloadFile',
                    'deleteFile',
                    'deleteFolder',
                    'renameFile',
                    'renameFolder',
                ];

                foreach ($requiredMethods as $method) {
                    if (method_exists($controller, $method)) {
                        $this->addResult("✓ {$method}()メソッドが存在");
                    } else {
                        $this->addError("✗ {$method}()メソッドが存在しません");
                    }
                }

            } else {
                $this->addError('✗ ContractDocumentControllerクラスが存在しません');
            }

        } catch (\Exception $e) {
            $this->addError('✗ コントローラーの検証エラー: ' . $e->getMessage());
        }
    }

    private function verifyRoutes()
    {
        echo "\n🛣️  ルートの検証...\n";

        try {
            $routes = \Illuminate\Support\Facades\Route::getRoutes();

            $requiredRoutes = [
                'facilities.contract-documents.index' => 'GET',
                'facilities.contract-documents.upload' => 'POST',
                'facilities.contract-documents.folders.store' => 'POST',
                'facilities.contract-documents.files.download' => 'GET',
                'facilities.contract-documents.files.destroy' => 'DELETE',
                'facilities.contract-documents.folders.destroy' => 'DELETE',
                'facilities.contract-documents.files.rename' => 'PATCH',
                'facilities.contract-documents.folders.rename' => 'PATCH',
            ];

            foreach ($requiredRoutes as $routeName => $method) {
                $route = $routes->getByName($routeName);
                if ($route) {
                    $this->addResult("✓ ルート '{$routeName}' が存在 ({$method})");
                } else {
                    $this->addError("✗ ルート '{$routeName}' が存在しません");
                }
            }

        } catch (\Exception $e) {
            $this->addError('✗ ルートの検証エラー: ' . $e->getMessage());
        }
    }

    private function verifyViews()
    {
        echo "\n👁️  ビューファイルの検証...\n";

        $requiredViews = [
            'resources/views/components/contract-document-manager.blade.php',
            'resources/views/facilities/contracts/index.blade.php',
        ];

        foreach ($requiredViews as $view) {
            if (file_exists(base_path($view))) {
                $this->addResult("✓ ビューファイル '{$view}' が存在");
                
                // コンポーネントの使用確認
                if ($view === 'resources/views/facilities/contracts/index.blade.php') {
                    $content = file_get_contents(base_path($view));
                    if (str_contains($content, '<x-contract-document-manager')) {
                        $this->addResult('✓ contract-document-managerコンポーネントが使用されています');
                    } else {
                        $this->addError('✗ contract-document-managerコンポーネントが使用されていません');
                    }
                }
            } else {
                $this->addError("✗ ビューファイル '{$view}' が存在しません");
            }
        }
    }

    private function verifyJavaScript()
    {
        echo "\n📜 JavaScriptファイルの検証...\n";

        $jsFile = 'resources/js/modules/ContractDocumentManager.js';
        
        if (file_exists(base_path($jsFile))) {
            $this->addResult("✓ JavaScriptファイル '{$jsFile}' が存在");
            
            $content = file_get_contents(base_path($jsFile));
            
            // クラス定義の確認
            if (str_contains($content, 'class ContractDocumentManager')) {
                $this->addResult('✓ ContractDocumentManagerクラスが定義されています');
            } else {
                $this->addError('✗ ContractDocumentManagerクラスが定義されていません');
            }
            
            // 必須メソッドの確認
            $requiredMethods = [
                'loadDocuments',
                'handleCreateFolder',
                'handleUploadFile',
                'handleRename',
                'handleDelete',
                'handleSearch',
            ];
            
            foreach ($requiredMethods as $method) {
                if (str_contains($content, $method)) {
                    $this->addResult("✓ {$method}()メソッドが実装されています");
                } else {
                    $this->addError("✗ {$method}()メソッドが実装されていません");
                }
            }
            
            // app-unified.jsでの読み込み確認
            $appUnified = 'resources/js/app-unified.js';
            if (file_exists(base_path($appUnified))) {
                $appContent = file_get_contents(base_path($appUnified));
                if (str_contains($appContent, 'ContractDocumentManager')) {
                    $this->addResult('✓ app-unified.jsでContractDocumentManagerが読み込まれています');
                } else {
                    $this->addWarning('⚠ app-unified.jsでContractDocumentManagerが読み込まれていません');
                }
            }
            
        } else {
            $this->addError("✗ JavaScriptファイル '{$jsFile}' が存在しません");
        }
    }

    private function verifyCSS()
    {
        echo "\n🎨 CSSファイルの検証...\n";

        $cssFile = 'resources/css/contract-document-management.css';
        
        if (file_exists(base_path($cssFile))) {
            $this->addResult("✓ CSSファイル '{$cssFile}' が存在");
            
            // app-unified.cssでの読み込み確認
            $appCss = 'resources/css/app-unified.css';
            if (file_exists(base_path($appCss))) {
                $content = file_get_contents(base_path($appCss));
                if (str_contains($content, 'contract-document-management.css')) {
                    $this->addResult('✓ app-unified.cssでCSSファイルが読み込まれています');
                } else {
                    $this->addWarning('⚠ app-unified.cssでCSSファイルが読み込まれていません');
                }
            }
            
        } else {
            $this->addError("✗ CSSファイル '{$cssFile}' が存在しません");
        }
    }

    private function verifyCategorySeparation()
    {
        echo "\n🔒 カテゴリ分離の検証...\n";

        try {
            // テストデータの作成
            $facility = Facility::first();
            
            if (!$facility) {
                $this->addWarning('⚠ テスト用の施設データが存在しません（カテゴリ分離のテストをスキップ）');
                return;
            }

            // 契約書カテゴリのフォルダ数を確認
            $contractFolders = DocumentFolder::where('facility_id', $facility->id)
                ->where('category', 'contracts')
                ->count();
            
            $this->addResult("✓ 契約書カテゴリのフォルダ数: {$contractFolders}");

            // 他のカテゴリのフォルダが混在していないか確認
            $otherFolders = DocumentFolder::where('facility_id', $facility->id)
                ->where('category', '!=', 'contracts')
                ->whereNotNull('category')
                ->count();
            
            if ($otherFolders > 0) {
                $this->addResult("✓ 他のカテゴリのフォルダが正しく分離されています（{$otherFolders}件）");
            }

            // スコープの動作確認
            $scopedFolders = DocumentFolder::contracts()
                ->where('facility_id', $facility->id)
                ->count();
            
            if ($scopedFolders === $contractFolders) {
                $this->addResult('✓ contracts()スコープが正しく動作しています');
            } else {
                $this->addError("✗ contracts()スコープの動作が不正です（期待: {$contractFolders}, 実際: {$scopedFolders}）");
            }

        } catch (\Exception $e) {
            $this->addError('✗ カテゴリ分離の検証エラー: ' . $e->getMessage());
        }
    }

    private function verifyDocumentation()
    {
        echo "\n📚 ドキュメントの検証...\n";

        $requiredDocs = [
            'docs/document-management/contract-document-user-guide.md',
            'docs/document-management/contract-document-developer-guide.md',
            'docs/document-management/contract-document-api-reference.md',
        ];

        foreach ($requiredDocs as $doc) {
            if (file_exists(base_path($doc))) {
                $this->addResult("✓ ドキュメント '{$doc}' が存在");
            } else {
                $this->addWarning("⚠ ドキュメント '{$doc}' が存在しません");
            }
        }
    }

    private function addResult($message)
    {
        $this->results[] = $message;
        echo "  {$message}\n";
    }

    private function addError($message)
    {
        $this->errors[] = $message;
        echo "  {$message}\n";
    }

    private function addWarning($message)
    {
        $this->warnings[] = $message;
        echo "  {$message}\n";
    }

    private function printResults()
    {
        echo "\n";
        echo "==============================================\n";
        echo "  検証結果サマリー\n";
        echo "==============================================\n\n";

        echo "✓ 成功: " . count($this->results) . "件\n";
        echo "✗ エラー: " . count($this->errors) . "件\n";
        echo "⚠ 警告: " . count($this->warnings) . "件\n\n";

        if (count($this->errors) > 0) {
            echo "【エラー詳細】\n";
            foreach ($this->errors as $error) {
                echo "  {$error}\n";
            }
            echo "\n";
        }

        if (count($this->warnings) > 0) {
            echo "【警告詳細】\n";
            foreach ($this->warnings as $warning) {
                echo "  {$warning}\n";
            }
            echo "\n";
        }

        if (count($this->errors) === 0) {
            echo "🎉 すべての検証が成功しました！\n\n";
            exit(0);
        } else {
            echo "❌ エラーが検出されました。上記の内容を確認してください。\n\n";
            exit(1);
        }
    }
}

// 検証実行
$verifier = new ContractDocumentVerifier();
$verifier->run();

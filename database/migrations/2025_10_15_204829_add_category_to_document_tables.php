<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ドキュメント管理システムの分離を強化するため、categoryカラムを追加
     * 
     * カテゴリ値:
     * - null: メインドキュメント管理
     * - 'lifeline_electrical': 電気設備
     * - 'lifeline_gas': ガス設備
     * - 'lifeline_water': 水道設備
     * - 'lifeline_elevator': エレベーター設備
     * - 'lifeline_hvac_lighting': 空調・照明設備
     * - 'maintenance_exterior': 外装修繕
     * - 'maintenance_interior': 内装修繕
     * - 'maintenance_summer_condensation': 夏季結露
     * - 'maintenance_other': その他修繕
     *
     * @return void
     */
    public function up()
    {
        // document_foldersテーブルにcategoryカラムを追加
        Schema::table('document_folders', function (Blueprint $table) {
            $table->string('category', 50)->nullable()->after('facility_id')
                ->comment('ドキュメントカテゴリ (null=メイン, lifeline_*, maintenance_*)');
            
            // パフォーマンス最適化のためのインデックス
            $table->index(['facility_id', 'category'], 'idx_folders_facility_category');
        });

        // document_filesテーブルにcategoryカラムを追加
        Schema::table('document_files', function (Blueprint $table) {
            $table->string('category', 50)->nullable()->after('facility_id')
                ->comment('ドキュメントカテゴリ (null=メイン, lifeline_*, maintenance_*)');
            
            // パフォーマンス最適化のためのインデックス
            $table->index(['facility_id', 'category'], 'idx_files_facility_category');
        });

        // 既存データのカテゴリを設定
        $this->migrateExistingData();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('document_folders', function (Blueprint $table) {
            $table->dropIndex('idx_folders_facility_category');
            $table->dropColumn('category');
        });

        Schema::table('document_files', function (Blueprint $table) {
            $table->dropIndex('idx_files_facility_category');
            $table->dropColumn('category');
        });
    }

    /**
     * 既存データのカテゴリを設定
     */
    private function migrateExistingData()
    {
        // ライフライン設備のフォルダを識別
        $lifelineMapping = [
            '電気設備' => 'lifeline_electrical',
            'ガス設備' => 'lifeline_gas',
            '水道設備' => 'lifeline_water',
            'エレベーター設備' => 'lifeline_elevator',
            '空調・照明設備' => 'lifeline_hvac_lighting',
        ];

        foreach ($lifelineMapping as $folderName => $category) {
            // フォルダを更新
            DB::table('document_folders')
                ->where('name', $folderName)
                ->whereNull('parent_id')
                ->update(['category' => $category]);

            // 該当フォルダとその子孫フォルダのIDを取得
            $folderIds = $this->getDescendantFolderIds($folderName);

            if (!empty($folderIds)) {
                // 子孫フォルダを更新
                DB::table('document_folders')
                    ->whereIn('id', $folderIds)
                    ->update(['category' => $category]);

                // 関連ファイルを更新
                DB::table('document_files')
                    ->whereIn('folder_id', $folderIds)
                    ->update(['category' => $category]);
            }
        }

        // 修繕履歴のフォルダを識別
        $maintenanceMapping = [
            '外装' => 'maintenance_exterior',
            '内装' => 'maintenance_interior',
            '夏季結露' => 'maintenance_summer_condensation',
        ];

        foreach ($maintenanceMapping as $folderName => $category) {
            // フォルダを更新
            DB::table('document_folders')
                ->where('name', $folderName)
                ->whereNull('parent_id')
                ->update(['category' => $category]);

            // 該当フォルダとその子孫フォルダのIDを取得
            $folderIds = $this->getDescendantFolderIds($folderName);

            if (!empty($folderIds)) {
                // 子孫フォルダを更新
                DB::table('document_folders')
                    ->whereIn('id', $folderIds)
                    ->update(['category' => $category]);

                // 関連ファイルを更新
                DB::table('document_files')
                    ->whereIn('folder_id', $folderIds)
                    ->update(['category' => $category]);
            }
        }

        // カテゴリが設定されていないフォルダのファイルは、メインドキュメントとして扱う（categoryはnullのまま）
    }

    /**
     * 指定されたフォルダ名の子孫フォルダIDを取得
     */
    private function getDescendantFolderIds(string $rootFolderName): array
    {
        $rootFolders = DB::table('document_folders')
            ->where('name', $rootFolderName)
            ->whereNull('parent_id')
            ->pluck('id')
            ->toArray();

        if (empty($rootFolders)) {
            return [];
        }

        $allIds = $rootFolders;
        $currentIds = $rootFolders;

        // 再帰的に子孫フォルダを取得
        while (!empty($currentIds)) {
            $childIds = DB::table('document_folders')
                ->whereIn('parent_id', $currentIds)
                ->pluck('id')
                ->toArray();

            if (empty($childIds)) {
                break;
            }

            $allIds = array_merge($allIds, $childIds);
            $currentIds = $childIds;
        }

        return $allIds;
    }
};

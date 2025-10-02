{{--
Common Table Layout Component - Performance Optimized Version
パフォーマンス最適化版の再利用可能なテーブルレイアウトコンポーネント

Usage:
<x-common-table-optimized 
    :data="$tableData" 
    title="基本情報" 
    :responsive="true"
    :enable-caching="true"
    :batch-size="50"
/>
--}}

@props([
    'data' => [],           // テーブルデータ配列
    'title' => null,        // カードタイトル（オプション）
    'cardClass' => 'facility-info-card detail-card-improved mb-3',
    'tableClass' => 'table table-bordered facility-basic-info-table-clean',
    'responsive' => true,   // レスポンシブテーブルの有効/無効
    'cleanBody' => true,    // card-body-cleanクラスの適用
    'headerClass' => 'card-header', // カードヘッダーのCSSクラス
    'bodyClass' => null,    // カードボディの追加CSSクラス
    'wrapperClass' => null, // テーブルラッパーの追加CSSクラス
    'emptyMessage' => 'データがありません', // 空データ時のメッセージ
    'showHeader' => true,   // ヘッダー表示の制御
    'tableAttributes' => [], // テーブル要素の追加属性
    'ariaLabel' => null,    // アクセシビリティ用のARIAラベル
    'validateData' => true, // データバリデーションの有効/無効
    'showValidationWarnings' => false, // バリデーション警告の表示
    'fallbackOnError' => true, // エラー時のフォールバック表示
    'validationOptions' => [], // バリデーションオプション
    // パフォーマンス最適化オプション
    'enableCaching' => true, // キャッシュの有効/無効
    'batchSize' => 50,      // バッチ処理のサイズ
    'enableMemoryOptimization' => true, // メモリ最適化の有効/無効
    'skipEmptyCells' => false, // 空セルのスキップ
    'enableLazyLoading' => false, // 遅延読み込みの有効/無効
    'performanceLogging' => false, // パフォーマンスログの有効/無効
])

@php
    use App\Services\CommonTableValidator;
    use App\Services\CommonTableErrorHandler;
    use App\Services\CommonTablePerformanceOptimizer;
    use App\Services\ValueFormatter;
    
    $renderStartTime = microtime(true);
    $memoryBefore = memory_get_usage(true);
    
    $renderError = null;
    $validationResult = null;
    $validData = [];
    $hasValidData = false;
    $performanceStats = [];
    $cacheKey = null;
    $usedCache = false;
    
    try {
        // パフォーマンス分析
        $performanceAnalysis = CommonTablePerformanceOptimizer::analyzePerformanceNeeds($data, [
            'enable_caching' => $enableCaching,
            'batch_size' => $batchSize
        ]);
        
        // キャッシュキーの生成（キャッシュが有効な場合）
        if ($enableCaching && $performanceAnalysis['needs_optimization']) {
            $cacheOptions = [
                'title' => $title,
                'card_class' => $cardClass,
                'table_class' => $tableClass,
                'responsive' => $responsive,
                'clean_body' => $cleanBody
            ];
            $cacheKey = CommonTablePerformanceOptimizer::generateCacheKey($data, $cacheOptions);
            
            // キャッシュから取得を試行
            $cachedResult = CommonTablePerformanceOptimizer::getCachedFormattedData($cacheKey);
            if ($cachedResult !== null) {
                $validData = $cachedResult['data'];
                $hasValidData = $cachedResult['has_valid_data'];
                $usedCache = true;
                
                if ($performanceLogging) {
                    \Log::info('CommonTable used cached data', [
                        'cache_key' => $cacheKey,
                        'data_rows' => count($validData)
                    ]);
                }
            }
        }
        
        // キャッシュがない場合の処理
        if (!$usedCache) {
            // データバリデーションの実行
            if ($validateData) {
                $validationResult = CommonTableValidator::validateTableData($data, $validationOptions);
                
                if (!$validationResult['valid']) {
                    $errorData = CommonTableErrorHandler::handleValidationErrors($validationResult, [
                        'title' => $title,
                        'data_count' => is_array($data) ? count($data) : 0
                    ]);
                    
                    if ($fallbackOnError) {
                        $renderError = $errorData;
                    }
                }
            }
            
            // データの最適化処理
            if (!$renderError) {
                $rawData = is_array($data) ? $data : [];
                
                if ($enableMemoryOptimization && CommonTablePerformanceOptimizer::isLargeDataset($rawData)) {
                    $optimizationOptions = [
                        'skip_empty_cells' => $skipEmptyCells,
                        'enable_data_truncation' => true
                    ];
                    $validData = CommonTablePerformanceOptimizer::optimizeDataForMemory($rawData, $optimizationOptions);
                } else {
                    $validData = $rawData;
                }
                
                // データの存在確認
                foreach ($validData as $rowData) {
                    if (is_array($rowData) && isset($rowData['cells']) && is_array($rowData['cells']) && !empty($rowData['cells'])) {
                        $hasValidData = true;
                        break;
                    }
                }
                
                // キャッシュに保存（条件を満たす場合）
                if ($enableCaching && $cacheKey && $performanceAnalysis['needs_optimization']) {
                    $cacheData = [
                        'data' => $validData,
                        'has_valid_data' => $hasValidData,
                        'generated_at' => now()->toISOString()
                    ];
                    CommonTablePerformanceOptimizer::cacheFormattedData($cacheKey, $cacheData);
                }
            }
        }
        
        // UI要素の設定
        $baseBodyClass = $cleanBody ? 'card-body card-body-clean' : 'card-body';
        $finalBodyClass = $bodyClass ? $baseBodyClass . ' ' . $bodyClass : $baseBodyClass;
        
        $baseWrapperClass = $responsive ? 'table-responsive table-responsive-md' : '';
        $finalWrapperClass = $wrapperClass ? $baseWrapperClass . ' ' . $wrapperClass : $baseWrapperClass;
        
        // テーブル属性の処理
        $tableAttrs = is_array($tableAttributes) ? $tableAttributes : [];
        
        // アクセシビリティ属性の設定
        if ($ariaLabel) {
            $tableAttrs['aria-label'] = $ariaLabel;
        } else {
            $tableAttrs['aria-label'] = $title ? $title . 'の詳細情報' : '詳細情報テーブル';
        }
        if (!isset($tableAttrs['role'])) {
            $tableAttrs['role'] = 'table';
        }
        
        // パフォーマンス属性
        $tableAttrs['data-responsive'] = $responsive ? 'true' : 'false';
        $tableAttrs['data-mobile-optimized'] = 'true';
        $tableAttrs['data-performance-optimized'] = 'true';
        $tableAttrs['data-cached'] = $usedCache ? 'true' : 'false';
        
    } catch (\Exception $e) {
        $errorData = CommonTableErrorHandler::handleRenderingError($e, $validData, [
            'title' => $title,
            'validate_data' => $validateData,
            'fallback_on_error' => $fallbackOnError,
            'performance_mode' => true
        ]);
        
        if ($fallbackOnError) {
            $renderError = $errorData;
        }
    }
    
    // パフォーマンス統計の収集
    $renderEndTime = microtime(true);
    $memoryAfter = memory_get_usage(true);
    $renderTime = $renderEndTime - $renderStartTime;
    $memoryUsed = $memoryAfter - $memoryBefore;
    
    if ($performanceLogging) {
        $performanceStats = CommonTablePerformanceOptimizer::collectRenderingStats($validData, $renderTime, $memoryUsed);
        $performanceStats['used_cache'] = $usedCache;
        $performanceStats['cache_key'] = $cacheKey;
        
        \Log::info('CommonTable performance stats', $performanceStats);
    }
@endphp

{{-- パフォーマンス情報の表示（デバッグモード時） --}}
@if(config('app.debug') && $performanceLogging)
    <div class="alert alert-info small mb-2" role="alert">
        <strong>パフォーマンス情報:</strong>
        レンダリング時間: {{ round($renderTime * 1000, 2) }}ms |
        メモリ使用量: {{ round($memoryUsed / 1024, 2) }}KB |
        キャッシュ使用: {{ $usedCache ? 'あり' : 'なし' }} |
        データ行数: {{ count($validData) }}
        @if(isset($performanceStats['performance_score']))
            | スコア: {{ $performanceStats['performance_score'] }}
        @endif
    </div>
@endif

{{-- エラー表示 --}}
@if($renderError)
    @if($renderError['type'] === 'validation' && !empty($renderError['errors']))
        <x-common-table.error 
            :message="$renderError['user_message']"
            :errors="$renderError['errors']"
            :showDetails="$renderError['show_details']"
            :errorId="$renderError['error_id']"
        />
    @else
        <x-common-table.fallback 
            :title="$title"
            :message="$renderError['user_message']"
            :showRetry="true"
            :cardClass="$cardClass"
        />
    @endif
@elseif($validationResult && $showValidationWarnings && !empty($validationResult['warnings']))
    <div class="alert alert-warning" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>警告</strong>
        </div>
        <div class="mt-2">
            <ul class="mb-0">
                @foreach($validationResult['warnings'] as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

{{-- メインテーブルコンテンツ --}}
@if(!$renderError)
    @if($enableLazyLoading && CommonTablePerformanceOptimizer::isLargeDataset($validData))
        {{-- 遅延読み込み対応版 --}}
        <div class="{{ $cardClass }}" data-lazy-loading="true">
            @if($title && $showHeader)
                <div class="{{ $headerClass }}">
                    <h5 class="card-title mb-0">{{ $title }}</h5>
                    <small class="text-muted">大量データのため段階的に読み込みます</small>
                </div>
            @endif
            <div class="{{ $finalBodyClass }}">
                <div class="{{ $finalWrapperClass }}" role="region" aria-label="データテーブル">
                    <div id="lazy-table-container" data-batch-size="{{ $batchSize }}">
                        {{-- 初期バッチのみレンダリング --}}
                        @php
                            $batches = CommonTablePerformanceOptimizer::splitDataIntoBatches($validData, $batchSize);
                            $initialBatch = $batches[0] ?? [];
                        @endphp
                        
                        <table class="{{ $tableClass }}" @foreach($tableAttrs as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach>
                            @if($title)
                                <caption class="sr-only">{{ $title }}の詳細情報</caption>
                            @endif
                            <tbody id="table-body-lazy">
                                @foreach($initialBatch as $rowIndex => $rowData)
                                    @if(is_array($rowData) && isset($rowData['cells']) && is_array($rowData['cells']) && !empty($rowData['cells']))
                                        <x-common-table.row 
                                            :cells="$rowData['cells']" 
                                            :type="$rowData['type'] ?? 'standard'"
                                            :rowIndex="$rowIndex"
                                            :key="$rowData['key'] ?? null"
                                        />
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                        
                        @if(count($batches) > 1)
                            <div class="text-center mt-3">
                                <button class="btn btn-outline-primary" id="load-more-rows" 
                                        data-total-batches="{{ count($batches) }}" 
                                        data-current-batch="1">
                                    <i class="fas fa-chevron-down me-1"></i>
                                    さらに読み込む ({{ count($validData) - count($initialBatch) }}行)
                                </button>
                            </div>
                            
                            {{-- 残りのバッチデータをJSONとして埋め込み --}}
                            <script type="application/json" id="remaining-batches-data">
                                @json(array_slice($batches, 1))
                            </script>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- 通常版（最適化済み） --}}
        @if($title && $showHeader)
            <x-common-table.card-wrapper :title="$title" :cardClass="$cardClass" :headerClass="$headerClass">
                <div class="{{ $finalBodyClass }}">
                    <div class="{{ $finalWrapperClass }}" role="region" aria-label="データテーブル">
                        @if($title)
                            <div class="sr-only">
                                {{ $title }}の詳細情報テーブル。{{ count($validData) }}行のデータが含まれています。
                            </div>
                        @endif
                        
                        <table class="{{ $tableClass }}" @foreach($tableAttrs as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach>
                            @if($title)
                                <caption class="sr-only">{{ $title }}の詳細情報</caption>
                            @endif
                            <tbody>
                                @if($hasValidData)
                                    @foreach($validData as $rowIndex => $rowData)
                                        @if(is_array($rowData) && isset($rowData['cells']) && is_array($rowData['cells']) && !empty($rowData['cells']))
                                            <x-common-table.row 
                                                :cells="$rowData['cells']" 
                                                :type="$rowData['type'] ?? 'standard'"
                                                :rowIndex="$rowIndex"
                                                :key="$rowData['key'] ?? null"
                                            />
                                        @endif
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="text-center text-muted p-4" colspan="2">
                                            {{ $emptyMessage }}
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-common-table.card-wrapper>
        @else
            <div class="{{ $cardClass }}">
                @if($title && $showHeader)
                    <div class="{{ $headerClass }}">
                        <h5 class="card-title mb-0">{{ $title }}</h5>
                    </div>
                @endif
                <div class="{{ $finalBodyClass }}">
                    <div class="{{ $finalWrapperClass }}" role="region" aria-label="データテーブル">
                        @if($title)
                            <div class="sr-only">
                                {{ $title }}の詳細情報テーブル。{{ count($validData) }}行のデータが含まれています。
                            </div>
                        @endif
                        
                        <table class="{{ $tableClass }}" @foreach($tableAttrs as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach>
                            @if($title)
                                <caption class="sr-only">{{ $title }}の詳細情報</caption>
                            @endif
                            <tbody>
                                @if($hasValidData)
                                    @foreach($validData as $rowIndex => $rowData)
                                        @if(is_array($rowData) && isset($rowData['cells']) && is_array($rowData['cells']) && !empty($rowData['cells']))
                                            <x-common-table.row 
                                                :cells="$rowData['cells']" 
                                                :type="$rowData['type'] ?? 'standard'"
                                                :rowIndex="$rowIndex"
                                                :key="$rowData['key'] ?? null"
                                            />
                                        @endif
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="text-center text-muted p-4" colspan="2">
                                            {{ $emptyMessage }}
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endif
@endif

{{-- 遅延読み込み用JavaScript --}}
@if($enableLazyLoading && !$renderError)
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const loadMoreBtn = document.getElementById('load-more-rows');
        const tableBody = document.getElementById('table-body-lazy');
        const batchesData = document.getElementById('remaining-batches-data');
        
        if (loadMoreBtn && tableBody && batchesData) {
            let currentBatch = 1;
            let batches;
            
            try {
                batches = JSON.parse(batchesData.textContent);
            } catch (e) {
                console.error('Failed to parse batch data:', e);
                return;
            }
            
            loadMoreBtn.addEventListener('click', function() {
                if (currentBatch < batches.length) {
                    const batch = batches[currentBatch];
                    const fragment = document.createDocumentFragment();
                    
                    batch.forEach((rowData, index) => {
                        if (rowData.cells && Array.isArray(rowData.cells)) {
                            const row = document.createElement('tr');
                            
                            rowData.cells.forEach(cell => {
                                const td = document.createElement('td');
                                
                                if (cell.label) {
                                    td.className = 'detail-label';
                                    td.textContent = cell.label;
                                } else {
                                    td.className = 'detail-value';
                                    if (cell.value === null || cell.value === '') {
                                        td.className += ' empty-field';
                                        td.textContent = '未設定';
                                    } else {
                                        td.innerHTML = cell.formatted_value || cell.value;
                                    }
                                }
                                
                                if (cell.colspan > 1) {
                                    td.colSpan = cell.colspan;
                                }
                                if (cell.rowspan > 1) {
                                    td.rowSpan = cell.rowspan;
                                }
                                
                                row.appendChild(td);
                            });
                            
                            fragment.appendChild(row);
                        }
                    });
                    
                    tableBody.appendChild(fragment);
                    currentBatch++;
                    
                    // ボタンテキストの更新
                    const remainingRows = batches.slice(currentBatch).reduce((total, batch) => total + batch.length, 0);
                    if (remainingRows > 0) {
                        loadMoreBtn.innerHTML = `<i class="fas fa-chevron-down me-1"></i>さらに読み込む (${remainingRows}行)`;
                    } else {
                        loadMoreBtn.style.display = 'none';
                    }
                }
            });
        }
    });
    </script>
    @endpush
@endif
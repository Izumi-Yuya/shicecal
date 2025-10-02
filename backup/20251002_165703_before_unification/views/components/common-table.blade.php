{{--
Common Table Layout Component
再利用可能なテーブルレイアウトコンポーネント

Usage:
<x-common-table 
    :data="$tableData" 
    title="基本情報" 
    :responsive="true" 
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
])

@php
    use App\Services\CommonTableValidator;
    use App\Services\CommonTableErrorHandler;
    
    $renderError = null;
    $validationResult = null;
    $validData = [];
    $hasValidData = false;
    
    try {
        // データバリデーションの実行（元のデータで実行）
        if ($validateData) {
            $validationResult = CommonTableValidator::validateTableData($data, $validationOptions);
            
            // バリデーションエラーがある場合の処理
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
        
        // データ構造の基本処理（バリデーション後）
        $validData = is_array($data) ? $data : [];
        
        // カードボディのクラス設定
        $baseBodyClass = $cleanBody ? 'card-body card-body-clean' : 'card-body';
        $finalBodyClass = $bodyClass ? $baseBodyClass . ' ' . $bodyClass : $baseBodyClass;
        
        // テーブルラッパーのクラス設定（レスポンシブ対応強化）
        $baseWrapperClass = $responsive ? 'table-responsive table-responsive-md' : '';
        $finalWrapperClass = $wrapperClass ? $baseWrapperClass . ' ' . $wrapperClass : $baseWrapperClass;
        
        // テーブル属性の処理
        $tableAttrs = is_array($tableAttributes) ? $tableAttributes : [];
        
        // クラス属性の処理（既存のtableClassと統合）
        $finalTableClass = $tableClass;
        if (isset($tableAttrs['class'])) {
            $finalTableClass = $tableClass . ' ' . $tableAttrs['class'];
            unset($tableAttrs['class']); // 重複を避けるため削除
        }
        
        // アクセシビリティ属性の設定（レスポンシブ対応強化）
        if ($ariaLabel) {
            $tableAttrs['aria-label'] = $ariaLabel;
        } else {
            $tableAttrs['aria-label'] = $title ? $title . 'の詳細情報' : '詳細情報テーブル';
        }
        if (!isset($tableAttrs['role'])) {
            $tableAttrs['role'] = 'table';
        }
        
        // レスポンシブ対応のためのデータ属性
        $tableAttrs['data-responsive'] = $responsive ? 'true' : 'false';
        $tableAttrs['data-mobile-optimized'] = 'true';
        
        // データの存在確認
        if (!$renderError) {
            foreach ($validData as $rowData) {
                if (is_array($rowData) && isset($rowData['cells']) && is_array($rowData['cells']) && !empty($rowData['cells'])) {
                    $hasValidData = true;
                    break;
                }
            }
        }
        
    } catch (\Exception $e) {
        // レンダリングエラーの処理
        $errorData = CommonTableErrorHandler::handleRenderingError($e, $validData, [
            'title' => $title,
            'validate_data' => $validateData,
            'fallback_on_error' => $fallbackOnError
        ]);
        
        if ($fallbackOnError) {
            $renderError = $errorData;
        }
    }
@endphp

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
    {{-- バリデーション警告の表示 --}}
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

{{-- カードラッパーの開始 --}}
@if(!$renderError && $title && $showHeader)
    <x-common-table.card-wrapper :title="$title" :cardClass="$cardClass" :headerClass="$headerClass">
        <div class="{{ $finalBodyClass }}">
            {{-- テーブルコンテナ（アクセシビリティ強化） --}}
            <div class="{{ $finalWrapperClass }}" role="region" aria-label="データテーブル">
                {{-- スクリーンリーダー用のテーブル説明 --}}
                @if($title)
                    <div class="sr-only">
                        {{ $title }}の詳細情報テーブル。{{ count($validData) }}行のデータが含まれています。
                    </div>
                @endif
                
                <table class="{{ $tableClass }}" @foreach($tableAttrs as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach>
                    {{-- テーブルキャプション（アクセシビリティ向上） --}}
                    @if($title)
                        <caption class="sr-only">{{ $title }}の詳細情報</caption>
                    @endif
                    <tbody>
                        {{-- データ行の処理 --}}
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
                            {{-- 空データの場合のフォールバック --}}
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
@elseif(!$renderError)
    <div class="{{ $cardClass }}">
        @if($title && $showHeader)
            <div class="{{ $headerClass }}">
                <h5 class="card-title mb-0">{{ $title }}</h5>
            </div>
        @endif
        <div class="{{ $finalBodyClass }}">
            {{-- テーブルコンテナ（アクセシビリティ強化） --}}
            <div class="{{ $finalWrapperClass }}" role="region" aria-label="データテーブル">
                {{-- スクリーンリーダー用のテーブル説明 --}}
                @if($title)
                    <div class="sr-only">
                        {{ $title }}の詳細情報テーブル。{{ count($validData) }}行のデータが含まれています。
                    </div>
                @endif
                
                <table class="{{ $tableClass }}" @foreach($tableAttrs as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach>
                    {{-- テーブルキャプション（アクセシビリティ向上） --}}
                    @if($title)
                        <caption class="sr-only">{{ $title }}の詳細情報</caption>
                    @endif
                    <tbody>
                        {{-- データ行の処理 --}}
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
                            {{-- 空データの場合のフォールバック --}}
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
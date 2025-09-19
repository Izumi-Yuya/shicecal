{{--
Card Wrapper Component for Common Table
共通テーブル用カードラッパーコンポーネント

Usage:
<x-common-table.card-wrapper title="基本情報" cardClass="facility-info-card">
    <!-- Table content -->
</x-common-table.card-wrapper>
--}}

@props([
    'title' => null,        // カードタイトル
    'cardClass' => 'facility-info-card detail-card-improved mb-3', // カードCSSクラス
    'headerClass' => 'card-header', // ヘッダーCSSクラス
    'showHeader' => true,   // ヘッダー表示の制御
    'headerAttributes' => [], // ヘッダー要素の追加属性
    'cardAttributes' => [], // カード要素の追加属性
    'titleTag' => 'h5',     // タイトルのHTMLタグ
    'titleClass' => 'card-title mb-0', // タイトルのCSSクラス
    'ariaLabel' => null,    // アクセシビリティ用のARIAラベル
])

@php
    // 属性の処理
    $cardAttrs = is_array($cardAttributes) ? $cardAttributes : [];
    $headerAttrs = is_array($headerAttributes) ? $headerAttributes : [];
    
    // アクセシビリティ属性の設定
    if ($ariaLabel) {
        $cardAttrs['aria-label'] = $ariaLabel;
    }
    if (!isset($cardAttrs['role'])) {
        $cardAttrs['role'] = 'region';
    }
    
    // タイトルが存在する場合のARIA属性
    if ($title && $showHeader) {
        $titleId = 'card-title-' . uniqid();
        $cardAttrs['aria-labelledby'] = $titleId;
        $headerAttrs['id'] = $titleId;
    }
@endphp

<div class="{{ $cardClass }}" @foreach($cardAttrs as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach>
    @if($title && $showHeader)
        <div class="{{ $headerClass }}" @foreach($headerAttrs as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach>
            <{{ $titleTag }} class="{{ $titleClass }}">{{ $title }}</{{ $titleTag }}>
        </div>
    @endif
    
    {{ $slot }}
</div>
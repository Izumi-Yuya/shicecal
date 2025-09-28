{{--
Cell Component
セルコンポーネント - 個別セルの内容とスタイリング処理
--}}

@props([
    'label' => null,        // ラベルテキスト
    'value' => null,        // 値
    'type' => 'text',       // セルタイプ
    'colspan' => 1,         // カラムスパン
    'rowspan' => 1,         // ロースパン
    'isLabel' => false,     // ラベルセルかどうか
    'isEmpty' => null,      // 空フィールドかどうか（nullの場合は自動判定）
    'class' => '',          // 追加CSSクラス
    'key' => null,          // セルの一意キー
    'options' => [],        // フォーマッターオプション
    'groupNumber' => null,  // グループ番号（grouped行タイプ用）
])

@php
    use App\Services\ValueFormatter;
    
    // プロパティのバリデーション
    $colspan = max(1, min(12, (int) $colspan)); // 最大12カラム
    $rowspan = max(1, min(10, (int) $rowspan)); // 最大10行
    
    // サポートされるセルタイプの検証
    $supportedTypes = ['text', 'badge', 'email', 'url', 'date', 'currency', 'number', 'file', 'file_display', 'label'];
    $type = in_array($type, $supportedTypes) ? $type : 'text';
    
    // 表示値の決定
    $displayValue = $isLabel ? $label : $value;
    
    // 空値判定（明示的に指定されていない場合は自動判定）
    if ($isEmpty === null) {
        $isEmpty = ValueFormatter::isEmpty($displayValue);
    }
    
    // セルの基本クラス設定
    $baseClass = $isLabel ? 'detail-label' : 'detail-value';
    
    // 空フィールドクラスの条件付き適用（値セルのみ）
    $emptyClass = (!$isLabel && $isEmpty) ? 'empty-field' : '';
    
    // 最終的なクラス文字列
    $cellClass = trim($baseClass . ' ' . $emptyClass . ' ' . $class);
    
    // セル属性の設定
    $attributes = [];
    if ($colspan > 1) $attributes['colspan'] = $colspan;
    if ($rowspan > 1) $attributes['rowspan'] = $rowspan;
    if ($key) $attributes['data-cell-key'] = $key;
    
    // ARIA属性の設定（アクセシビリティ向上）
    if ($isLabel) {
        $attributes['scope'] = 'row';
        $attributes['role'] = 'rowheader';
        // ラベルセルにはaria-labelを追加
        if ($label) {
            $attributes['aria-label'] = $label;
            $attributes['id'] = 'label-' . ($key ?? uniqid());
        }
    } else {
        $attributes['role'] = 'gridcell';
        // 値セルにはaria-describedbyを追加（対応するラベルがある場合）
        if ($key) {
            $attributes['aria-describedby'] = 'label-' . $key;
        }
        
        // 空値の場合のアクセシビリティ対応
        if ($isEmpty) {
            $attributes['aria-label'] = '未設定の項目';
        } else {
            // セルタイプに応じたaria-labelの設定
            $ariaLabelValue = is_array($displayValue) ? ($displayValue['filename'] ?? 'データ') : (string) $displayValue;
            $ariaLabel = match($type) {
                'email' => 'メールアドレス: ' . $ariaLabelValue,
                'url' => 'ウェブサイト: ' . $ariaLabelValue,
                'date' => '日付: ' . $ariaLabelValue,
                'currency' => '金額: ' . $ariaLabelValue,
                'number' => '数値: ' . $ariaLabelValue,
                'file' => 'ファイル: ' . $ariaLabelValue,
                'file_display' => 'ファイル: ' . $ariaLabelValue,
                'badge' => 'ステータス: ' . $ariaLabelValue,
                'label' => 'ラベル: ' . $ariaLabelValue,
                default => $ariaLabelValue
            };
            $attributes['aria-label'] = $ariaLabel;
        }
    }
    
    // データ属性の追加（デバッグ用）
    $attributes['data-cell-type'] = $type;
    $attributes['data-is-empty'] = $isEmpty ? 'true' : 'false';
@endphp

<td class="{{ $cellClass }}" @foreach($attributes as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach style="@if($groupNumber && $isLabel) position: relative; @endif padding: 0.5rem;">
    @if($groupNumber && $isLabel)
        {{-- グループ番号の表示（grouped行タイプ用） --}}
        <div style="position: absolute; left: -30px; top: 50%; transform: translateY(-50%); z-index: 1000;">
            <span style="background: #007bff; color: white; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">{{ $groupNumber }}</span>
        </div>
    @endif
    
    @if($isLabel)
        {{-- ラベルセルの表示 --}}
        {{ $displayValue }}
    @else
        {{-- 値セルの表示（ValueFormatterを使用） --}}
        @if($isEmpty)
            {{-- 空値の場合はデフォルト表示 --}}
            {{ $options['empty_text'] ?? '未設定' }}
        @else
            {{-- ValueFormatterを使用してフォーマット --}}
            {!! ValueFormatter::format($displayValue, $type, $options) !!}
        @endif
    @endif
</td>
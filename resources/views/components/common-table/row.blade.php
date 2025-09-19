{{--
Row Component
行コンポーネント - テーブル行のレイアウト管理

サポートされる行タイプ:
- standard: 標準的なラベル-値ペアの行
- grouped: グループ化されたデータセクション（rowspan対応）
- single: 単一セルが複数カラムにまたがる行

要件: 2.1, 2.2, 2.3
--}}

@props([
    'cells' => [],          // セル配列
    'type' => 'standard',   // 'standard', 'grouped', 'single'
    'key' => null,          // 行の一意キー
    'rowIndex' => 0,        // 行インデックス（デバッグ用）
])

@php
    // セルデータの基本バリデーション
    $validCells = is_array($cells) ? $cells : [];
    
    // 行タイプのバリデーション
    $validTypes = ['standard', 'grouped', 'single'];
    $rowType = in_array($type, $validTypes) ? $type : 'standard';
    
    // 行タイプに基づくCSSクラス
    $rowClass = match($rowType) {
        'grouped' => 'grouped-row',
        'single' => 'single-row',
        default => 'standard-row'
    };
    
    // ARIA属性の設定（アクセシビリティ強化）
    $ariaLabel = match($rowType) {
        'grouped' => 'グループ化された情報行',
        'single' => '単一項目行',
        default => '標準情報行'
    };
    
    // 行の内容に基づいたより詳細なaria-label
    if (!empty($validCells)) {
        $firstCell = $validCells[0];
        if (is_array($firstCell) && isset($firstCell['label'])) {
            $ariaLabel = $firstCell['label'] . 'の情報行';
        }
    }
    
    // セル数の検証とデバッグ情報
    $cellCount = count($validCells);
    $hasMultipleCells = $cellCount > 1;
    
    // グループ化行の特別処理用フラグ
    $isGroupedWithSpan = $rowType === 'grouped' && $hasMultipleCells;
@endphp

<tr class="{{ $rowClass }}" 
    @if($key) data-row-key="{{ $key }}" @endif
    data-row-type="{{ $rowType }}"
    data-row-index="{{ $rowIndex }}"
    role="row"
    aria-label="{{ $ariaLabel }}">
    
    @forelse($validCells as $cellIndex => $cellData)
        @if(is_array($cellData))
            @php
                // セルデータの詳細バリデーション
                $hasLabel = isset($cellData['label']) && $cellData['label'] !== null && $cellData['label'] !== '';
                $hasValue = isset($cellData['value']);
                $cellType = $cellData['type'] ?? 'text';
                $colspan = max(1, intval($cellData['colspan'] ?? 1));
                $rowspan = max(1, intval($cellData['rowspan'] ?? 1));
                $cellClass = $cellData['class'] ?? '';
                
                // 値の空判定（0は有効な値として扱う）
                $isEmpty = !$hasValue || ($cellData['value'] === null || $cellData['value'] === '');
            @endphp
            
            {{-- 行タイプ別のセル処理 --}}
            @if($rowType === 'single')
                {{-- single行: ラベルと値を1つのセルに結合 --}}
                @php
                    // single行では、ラベルと値を組み合わせた表示を作成
                    $combinedValue = $cellData['value'] ?? null;
                    if ($hasLabel && $combinedValue !== null && $combinedValue !== '') {
                        // ラベル付きの値として表示
                        $combinedValue = $cellData['label'] . ': ' . $combinedValue;
                    } elseif ($hasLabel && ($combinedValue === null || $combinedValue === '')) {
                        // 値が空の場合はラベルのみ表示
                        $combinedValue = $cellData['label'];
                    }
                @endphp
                <x-common-table.cell 
                    :label="null"
                    :value="$combinedValue"
                    :type="$cellType"
                    :colspan="$colspan"
                    :rowspan="$rowspan"
                    :isLabel="false"
                    :isEmpty="$isEmpty && !$hasLabel"
                    :class="$cellClass"
                    :options="$cellData['options'] ?? []"
                    key="cell-single-{{ $rowIndex }}-{{ $cellIndex }}"
                />
            @elseif($rowType === 'grouped')
                {{-- grouped行: rowspan対応のラベル-値ペア --}}
                @if($hasLabel)
                    <x-common-table.cell 
                        :label="$cellData['label']"
                        :value="null"
                        type="label"
                        :colspan="$cellData['label_colspan'] ?? 1"
                        :rowspan="$rowspan"
                        :isLabel="true"
                        :class="$cellClass"
                        key="cell-grouped-label-{{ $rowIndex }}-{{ $cellIndex }}"
                    />
                @endif
                
                <x-common-table.cell 
                    :label="null"
                    :value="$cellData['value'] ?? null"
                    :type="$cellType"
                    :colspan="$colspan"
                    :rowspan="$rowspan"
                    :isLabel="false"
                    :isEmpty="$isEmpty"
                    :class="$cellClass"
                    :options="$cellData['options'] ?? []"
                    key="cell-grouped-value-{{ $rowIndex }}-{{ $cellIndex }}"
                />
            @else
                {{-- standard行: 標準的なラベル-値ペア --}}
                @if($cellType === 'label')
                    {{-- ラベル専用セル（rowspan対応） --}}
                    <x-common-table.cell 
                        :label="$cellData['label']"
                        :value="null"
                        type="label"
                        :colspan="$colspan"
                        :rowspan="$rowspan"
                        :isLabel="true"
                        :class="$cellClass"
                        key="cell-label-only-{{ $rowIndex }}-{{ $cellIndex }}"
                    />
                @elseif($hasLabel)
                    {{-- 従来のラベル-値ペア --}}
                    <x-common-table.cell 
                        :label="$cellData['label']"
                        :value="null"
                        type="label"
                        :colspan="$cellData['label_colspan'] ?? 1"
                        :rowspan="$rowspan"
                        :isLabel="true"
                        :class="$cellClass"
                        key="cell-standard-label-{{ $rowIndex }}-{{ $cellIndex }}"
                    />
                    
                    <x-common-table.cell 
                        :label="null"
                        :value="$cellData['value'] ?? null"
                        :type="$cellType"
                        :colspan="$colspan"
                        :rowspan="$rowspan"
                        :isLabel="false"
                        :isEmpty="$isEmpty"
                        :class="$cellClass"
                        :options="$cellData['options'] ?? []"
                        key="cell-standard-value-{{ $rowIndex }}-{{ $cellIndex }}"
                    />
                @else
                    {{-- 値のみのセル --}}
                    <x-common-table.cell 
                        :label="null"
                        :value="$cellData['value'] ?? null"
                        :type="$cellType"
                        :colspan="$colspan"
                        :rowspan="$rowspan"
                        :isLabel="false"
                        :isEmpty="$isEmpty"
                        :class="$cellClass"
                        :options="$cellData['options'] ?? []"
                        key="cell-value-only-{{ $rowIndex }}-{{ $cellIndex }}"
                    />
                @endif
            @endif
        @else
            {{-- 無効なセルデータの場合の警告 --}}
            <td class="text-center text-warning" role="gridcell">
                <small>無効なセルデータ (インデックス: {{ $cellIndex }})</small>
            </td>
        @endif
    @empty
        {{-- 空セルの場合のフォールバック --}}
        <td class="text-center text-muted" role="gridcell" colspan="2">
            <em>セルデータがありません</em>
        </td>
    @endforelse
</tr>
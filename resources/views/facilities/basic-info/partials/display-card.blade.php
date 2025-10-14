@php
    // 基本情報テーブルデータの構築
    $basicInfoData = [
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '会社名', 'value' => $facility->company_name, 'type' => 'text'],
                ['label' => '事業所コード', 'value' => $facility->office_code, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '施設名', 'value' => $facility->facility_name, 'type' => 'text', 'class' => 'fw-bold'],
                ['label' => '指定番号1', 'value' => $facility->designation_number, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '郵便番号', 'value' => $facility->formatted_postal_code, 'type' => 'text'],
                ['label' => '指定番号2', 'value' => $facility->designation_number_2, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '住所', 'value' => $facility->full_address, 'type' => 'text'],
                ['label' => '開設日', 'value' => $facility->opening_date, 'type' => 'date'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '住所（建物名）', 'value' => $facility->building_name, 'type' => 'text'],
                ['label' => '開設年数', 'value' => $facility->opening_date ? $facility->getFormattedOperationPeriod() : null, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '電話番号', 'value' => $facility->phone_number, 'type' => 'text'],
                ['label' => '建物構造', 'value' => $facility->building_structure, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'FAX番号', 'value' => $facility->fax_number, 'type' => 'text'],
                ['label' => '建物階数', 'value' => $facility->building_floors ? $facility->building_floors . '階' : null, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'フリーダイヤル', 'value' => $facility->toll_free_number, 'type' => 'text'],
                ['label' => '居室数', 'value' => $facility->paid_rooms_count !== null ? $facility->paid_rooms_count . '室' : null, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'メールアドレス', 'value' => $facility->email, 'type' => 'email'],
                ['label' => 'ショートステイ居室数', 'value' => $facility->ss_rooms_count !== null ? $facility->ss_rooms_count . '室' : null, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'URL', 'value' => $facility->website_url, 'type' => 'url'],
                ['label' => '定員数', 'value' => $facility->capacity ? $facility->capacity . '名' : null, 'type' => 'text'],
            ]
        ],
    ];
@endphp

<!-- 基本情報テーブル（共通コンポーネント使用） -->
<x-common-table 
    :data="$basicInfoData"
    :showHeader="false"
    :tableAttributes="['style' => '--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;']"
    tableClass="table table-bordered facility-basic-info-table-clean facility-unified-layout"
    bodyClass="p-0"
/>

<!-- サービス種類テーブル（新しい形式） -->
<div data-section="facility_services" class="mt-4">
    <div class="table-responsive">
        <table class="table table-bordered service-details-table">
            <thead>
                <tr class="table-light">
                    <th>サービス種類</th>
                    <th>介護保険事業所番号</th>
                    <th>保険者</th>
                    <th>指定（更新）日 〜 有効期限終了日</th>
                    <th>残月</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $services = $facility->services ?? collect();
                @endphp
                
                @if($services && $services->count() > 0)
                    @foreach($services as $service)
                        <tr>
                            <td class="service-type-cell">
                                <div class="service-type-content">{{ $service->service_type ?? '未設定' }}</div>
                                @if($service->service_type && strlen($service->service_type) > 20)
                                    <div class="expand-toggle" onclick="toggleServiceType(this)"></div>
                                @endif
                            </td>
                            <td>{{ $service->care_insurance_business_number ?? '未設定' }}</td>
                            <td>{{ $service->insurer ?? '未設定' }}</td>
                            <td>
                                @if($service->designation_date && $service->renewal_end_date)
                                    {{ $service->designation_date->format('Y年m月d日') }} 〜 {{ $service->renewal_end_date->format('Y年m月d日') }}
                                @elseif($service->designation_date)
                                    {{ $service->designation_date->format('Y年m月d日') }} 〜
                                @elseif($service->renewal_end_date)
                                    〜 {{ $service->renewal_end_date->format('Y年m月d日') }}
                                @else
                                    未設定
                                @endif
                            </td>
                            <td>
                                @if($service->remaining_months !== null)
                                    {{ $service->remaining_months }}月
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="5" class="text-center text-muted">サービス情報が登録されていません</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>


<script>
function toggleServiceType(button) {
    const cell = button.closest('td');
    const content = cell.querySelector('.service-type-content');
    
    if (content.classList.contains('expanded')) {
        // 折りたたむ
        content.classList.remove('expanded');
        cell.classList.remove('expanded');
        button.classList.remove('expanded');
    } else {
        // 展開する
        content.classList.add('expanded');
        cell.classList.add('expanded');
        button.classList.add('expanded');
    }
}

// ページ読み込み時に長いテキストのセルに展開ボタンを表示
document.addEventListener('DOMContentLoaded', function() {
    const serviceCells = document.querySelectorAll('.service-type-cell');
    
    serviceCells.forEach(function(cell) {
        const content = cell.querySelector('.service-type-content');
        if (content && content.scrollHeight > content.clientHeight) {
            // テキストが溢れている場合のみ展開ボタンを表示
            let expandButton = cell.querySelector('.expand-toggle');
            if (!expandButton) {
                expandButton = document.createElement('div');
                expandButton.className = 'expand-toggle';
                expandButton.onclick = function() { toggleServiceType(this); };
                cell.appendChild(expandButton);
            }
        }
    });
});
</script>
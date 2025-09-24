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
                ['label' => '指定番号', 'value' => $facility->designation_number, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '郵便番号', 'value' => $facility->formatted_postal_code, 'type' => 'text'],
                ['label' => '開設日', 'value' => $facility->opening_date, 'type' => 'date'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '住所', 'value' => $facility->full_address, 'type' => 'text'],
                ['label' => '開設年数', 'value' => $facility->opening_date ? $facility->opening_date->diffInYears(now()) . '年' : null, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '住所（建物名）', 'value' => $facility->building_name, 'type' => 'text'],
                ['label' => '建物構造', 'value' => $facility->building_structure, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '電話番号', 'value' => $facility->phone_number, 'type' => 'text'],
                ['label' => '建物階数', 'value' => $facility->building_floors ? $facility->building_floors . '階' : null, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'FAX番号', 'value' => $facility->fax_number, 'type' => 'text'],
                ['label' => '居室数', 'value' => $facility->paid_rooms_count !== null ? $facility->paid_rooms_count . '室' : null, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'フリーダイヤル', 'value' => $facility->toll_free_number, 'type' => 'text'],
                ['label' => '内SS数', 'value' => $facility->ss_rooms_count !== null ? $facility->ss_rooms_count . '室' : null, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'メールアドレス', 'value' => $facility->email, 'type' => 'email'],
                ['label' => '定員数', 'value' => $facility->capacity ? $facility->capacity . '名' : null, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'URL', 'value' => $facility->website_url, 'type' => 'url', 'colspan' => 3],
            ]
        ],
    ];
@endphp

<!-- 基本情報テーブル（共通コンポーネント使用） -->
<x-common-table 
    :data="$basicInfoData"
    :showHeader="false"
    :tableAttributes="['style' => '--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;']"
    bodyClass="p-0"
/>

@php
    // サービス種類テーブルデータの構築（4列構造）
    $services = $facility->services ?? collect();
    $servicesData = [];
    
    if ($services && $services->count() > 0) {
        foreach ($services as $index => $service) {
            // 有効期限の文字列を構築
            $validityPeriod = null;
            if ($service->renewal_start_date && $service->renewal_end_date) {
                $validityPeriod = \Carbon\Carbon::parse($service->renewal_start_date)->format('Y年m月d日') . ' 〜 ' . \Carbon\Carbon::parse($service->renewal_end_date)->format('Y年m月d日');
            } elseif ($service->renewal_start_date) {
                $validityPeriod = \Carbon\Carbon::parse($service->renewal_start_date)->format('Y年m月d日') . ' 〜';
            } elseif ($service->renewal_end_date) {
                $validityPeriod = '〜 ' . \Carbon\Carbon::parse($service->renewal_end_date)->format('Y年m月d日');
            }
            
            if ($index === 0) {
                // 最初の行：サービス種類ラベル（rowspan）+ サービス種類名 + 有効期限ラベル + 有効期限値
                $servicesData[] = [
                    'type' => 'standard',
                    'cells' => [
                        ['label' => 'サービス種類', 'value' => null, 'type' => 'label', 'rowspan' => $services->count()],
                        ['label' => null, 'value' => $service->service_type, 'type' => 'text'],
                        ['label' => '有効期限', 'value' => null, 'type' => 'label'],
                        ['label' => null, 'value' => $validityPeriod, 'type' => 'text'],
                    ]
                ];
            } else {
                // 2行目以降：サービス種類名 + 有効期限ラベル + 有効期限値（サービス種類ラベルはrowspanで省略）
                $servicesData[] = [
                    'type' => 'standard',
                    'cells' => [
                        ['label' => null, 'value' => $service->service_type, 'type' => 'text'],
                        ['label' => '有効期限', 'value' => null, 'type' => 'label'],
                        ['label' => null, 'value' => $validityPeriod, 'type' => 'text'],
                    ]
                ];
            }
        }
    } else {
        // サービスがない場合
        $servicesData[] = [
            'type' => 'standard',
            'cells' => [
                ['label' => 'サービス種類', 'value' => null, 'type' => 'label'],
                ['label' => null, 'value' => null, 'type' => 'text'],
                ['label' => '有効期限', 'value' => null, 'type' => 'label'],
                ['label' => null, 'value' => null, 'type' => 'text'],
            ]
        ];
    }
@endphp

<!-- サービス種類テーブル（共通コンポーネント使用） -->
<div data-section="facility_services">
    <x-common-table 
        :data="$servicesData"
        :showHeader="false"
        :tableAttributes="['style' => '--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;']"
        bodyClass="p-0"
    />
</div>


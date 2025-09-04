<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>施設情報帳票 - {{ $facility->facility_name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 18px;
            margin: 0 0 10px 0;
            font-weight: bold;
        }
        
        .header .subtitle {
            font-size: 14px;
            color: #666;
        }
        
        .info-section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            background-color: #f5f5f5;
            padding: 8px 12px;
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .info-table th,
        .info-table td {
            border: 1px solid #ddd;
            padding: 8px 12px;
            text-align: left;
            vertical-align: top;
        }
        
        .info-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            width: 30%;
        }
        
        .info-table td {
            width: 70%;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-draft {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 48px;
            color: rgba(0, 0, 0, 0.1);
            z-index: -1;
            font-weight: bold;
        }
        
        .approval-info {
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .approval-info strong {
            color: #0066cc;
        }
    </style>
</head>
<body>
    <!-- Watermark for approved documents -->
    @if($facility->status === 'approved')
        <div class="watermark">承認済</div>
    @endif

    <!-- Header -->
    <div class="header">
        <h1>施設情報帳票</h1>
        <div class="subtitle">Facility Information Report</div>
    </div>

    <!-- Approval Status -->
    @if($facility->status === 'approved')
        <div class="approval-info">
            <strong>承認情報:</strong> 
            {{ $facility->approved_at ? $facility->approved_at->format('Y年m月d日 H:i') : '' }}
            @if($facility->approver)
                （承認者: {{ $facility->approver->name }}）
            @endif
        </div>
    @endif

    <!-- Basic Information Section -->
    <div class="info-section">
        <div class="section-title">基本情報</div>
        <table class="info-table">
            <tr>
                <th>会社名</th>
                <td>{{ $facility->company_name ?? '未設定' }}</td>
            </tr>
            <tr>
                <th>事業所コード</th>
                <td>{{ $facility->office_code ?? '未設定' }}</td>
            </tr>
            <tr>
                <th>指定番号</th>
                <td>{{ $facility->designation_number ?? '未設定' }}</td>
            </tr>
            <tr>
                <th>施設名</th>
                <td>{{ $facility->facility_name ?? '未設定' }}</td>
            </tr>
            <tr>
                <th>ステータス</th>
                <td>
                    <span class="status-badge status-{{ $facility->status }}">
                        @switch($facility->status)
                            @case('approved')
                                承認済
                                @break
                            @case('pending_approval')
                                承認待ち
                                @break
                            @case('draft')
                                下書き
                                @break
                            @default
                                不明
                        @endswitch
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Contact Information Section -->
    <div class="info-section">
        <div class="section-title">連絡先情報</div>
        <table class="info-table">
            <tr>
                <th>郵便番号</th>
                <td>{{ $facility->postal_code ?? '未設定' }}</td>
            </tr>
            <tr>
                <th>住所</th>
                <td>{{ $facility->address ?? '未設定' }}</td>
            </tr>
            <tr>
                <th>電話番号</th>
                <td>{{ $facility->phone_number ?? '未設定' }}</td>
            </tr>
            <tr>
                <th>FAX番号</th>
                <td>{{ $facility->fax_number ?? '未設定' }}</td>
            </tr>
        </table>
    </div>

    <!-- Land Information Section -->
    @if($facility->landInfo)
        <div class="info-section">
            <div class="section-title">土地情報</div>
            <table class="info-table">
                <tr>
                    <th>所有形態</th>
                    <td>
                        @switch($facility->landInfo->ownership_type)
                            @case('owned')
                                自社
                                @break
                            @case('leased')
                                賃借
                                @break
                            @case('owned_rental')
                                自社（賃貸）
                                @break
                            @default
                                {{ $facility->landInfo->ownership_type ?? '未設定' }}
                        @endswitch
                    </td>
                </tr>
                @if($facility->landInfo->parking_spaces)
                    <tr>
                        <th>敷地内駐車場台数</th>
                        <td>{{ number_format($facility->landInfo->parking_spaces) }}台</td>
                    </tr>
                @endif
                @if($facility->landInfo->site_area_sqm)
                    <tr>
                        <th>敷地面積(㎡)</th>
                        <td>{{ number_format($facility->landInfo->site_area_sqm, 2) }}㎡</td>
                    </tr>
                @endif
                @if($facility->landInfo->site_area_tsubo)
                    <tr>
                        <th>敷地面積(坪数)</th>
                        <td>{{ number_format($facility->landInfo->site_area_tsubo, 2) }}坪</td>
                    </tr>
                @endif
                
                <!-- Ownership-specific information -->
                @if($facility->landInfo->ownership_type === 'owned')
                    @if($facility->landInfo->purchase_price)
                        <tr>
                            <th>購入金額</th>
                            <td>{{ number_format($facility->landInfo->purchase_price) }}円</td>
                        </tr>
                    @endif
                    @if($facility->landInfo->unit_price_per_tsubo)
                        <tr>
                            <th>坪単価</th>
                            <td>{{ number_format($facility->landInfo->unit_price_per_tsubo) }}円/坪</td>
                        </tr>
                    @endif
                @endif
                
                @if(in_array($facility->landInfo->ownership_type, ['leased', 'owned_rental']))
                    @if($facility->landInfo->monthly_rent)
                        <tr>
                            <th>家賃</th>
                            <td>{{ number_format($facility->landInfo->monthly_rent) }}円/月</td>
                        </tr>
                    @endif
                    @if($facility->landInfo->contract_start_date)
                        <tr>
                            <th>契約開始日</th>
                            <td>{{ $facility->landInfo->contract_start_date->format('Y年n月j日') }}</td>
                        </tr>
                    @endif
                    @if($facility->landInfo->contract_end_date)
                        <tr>
                            <th>契約終了日</th>
                            <td>{{ $facility->landInfo->contract_end_date->format('Y年n月j日') }}</td>
                        </tr>
                    @endif
                    @if($facility->landInfo->contract_period_text)
                        <tr>
                            <th>契約年数</th>
                            <td>{{ $facility->landInfo->contract_period_text }}</td>
                        </tr>
                    @endif
                    @if($facility->landInfo->auto_renewal)
                        <tr>
                            <th>自動更新</th>
                            <td>
                                @if($facility->landInfo->auto_renewal === 'yes')
                                    あり
                                @elseif($facility->landInfo->auto_renewal === 'no')
                                    なし
                                @else
                                    {{ $facility->landInfo->auto_renewal }}
                                @endif
                            </td>
                        </tr>
                    @endif
                @endif
                
                @if($facility->landInfo->notes)
                    <tr>
                        <th>備考</th>
                        <td>{{ $facility->landInfo->notes }}</td>
                    </tr>
                @endif
            </table>
            
            <!-- Management Company Information (for leased properties) -->
            @if($facility->landInfo->ownership_type === 'leased' && $facility->landInfo->management_company_name)
                <div class="section-title" style="margin-top: 15px;">管理会社情報</div>
                <table class="info-table">
                    <tr>
                        <th>会社名</th>
                        <td>{{ $facility->landInfo->management_company_name }}</td>
                    </tr>
                    @if($facility->landInfo->management_company_postal_code)
                        <tr>
                            <th>郵便番号</th>
                            <td>{{ $facility->landInfo->management_company_postal_code }}</td>
                        </tr>
                    @endif
                    @if($facility->landInfo->management_company_address)
                        <tr>
                            <th>住所</th>
                            <td>
                                {{ $facility->landInfo->management_company_address }}
                                @if($facility->landInfo->management_company_building)
                                    {{ $facility->landInfo->management_company_building }}
                                @endif
                            </td>
                        </tr>
                    @endif
                    @if($facility->landInfo->management_company_phone)
                        <tr>
                            <th>電話番号</th>
                            <td>{{ $facility->landInfo->management_company_phone }}</td>
                        </tr>
                    @endif
                    @if($facility->landInfo->management_company_fax)
                        <tr>
                            <th>FAX番号</th>
                            <td>{{ $facility->landInfo->management_company_fax }}</td>
                        </tr>
                    @endif
                    @if($facility->landInfo->management_company_email)
                        <tr>
                            <th>メールアドレス</th>
                            <td>{{ $facility->landInfo->management_company_email }}</td>
                        </tr>
                    @endif
                </table>
            @endif
            
            <!-- Owner Information (for leased properties) -->
            @if($facility->landInfo->ownership_type === 'leased' && $facility->landInfo->owner_name)
                <div class="section-title" style="margin-top: 15px;">オーナー情報</div>
                <table class="info-table">
                    <tr>
                        <th>氏名・会社名</th>
                        <td>{{ $facility->landInfo->owner_name }}</td>
                    </tr>
                    @if($facility->landInfo->owner_postal_code)
                        <tr>
                            <th>郵便番号</th>
                            <td>{{ $facility->landInfo->owner_postal_code }}</td>
                        </tr>
                    @endif
                    @if($facility->landInfo->owner_address)
                        <tr>
                            <th>住所</th>
                            <td>
                                {{ $facility->landInfo->owner_address }}
                                @if($facility->landInfo->owner_building)
                                    {{ $facility->landInfo->owner_building }}
                                @endif
                            </td>
                        </tr>
                    @endif
                    @if($facility->landInfo->owner_phone)
                        <tr>
                            <th>電話番号</th>
                            <td>{{ $facility->landInfo->owner_phone }}</td>
                        </tr>
                    @endif
                    @if($facility->landInfo->owner_email)
                        <tr>
                            <th>メールアドレス</th>
                            <td>{{ $facility->landInfo->owner_email }}</td>
                        </tr>
                    @endif
                </table>
            @endif
        </div>
    @endif

    <!-- System Information Section -->
    <div class="info-section">
        <div class="section-title">システム情報</div>
        <table class="info-table">
            <tr>
                <th>作成者</th>
                <td>{{ $facility->creator->name ?? '不明' }}</td>
            </tr>
            <tr>
                <th>作成日時</th>
                <td>{{ $facility->created_at ? $facility->created_at->format('Y年m月d日 H:i') : '不明' }}</td>
            </tr>
            <tr>
                <th>最終更新者</th>
                <td>{{ $facility->updater->name ?? '不明' }}</td>
            </tr>
            <tr>
                <th>最終更新日時</th>
                <td>{{ $facility->updated_at ? $facility->updated_at->format('Y年m月d日 H:i') : '不明' }}</td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>
            出力日時: {{ $generated_at->format('Y年m月d日 H:i:s') }} | 
            出力者: {{ $generated_by->name }} |
            このドキュメントは Shise-Cal システムにより自動生成されました
        </div>
        <div style="margin-top: 5px; font-size: 9px;">
            ※ このPDFは承認済み情報のみを含んでいます。改ざん防止のため、編集・印刷が制限されています。
        </div>
    </div>
</body>
</html>
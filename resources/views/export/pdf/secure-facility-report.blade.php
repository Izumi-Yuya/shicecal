<style>
body {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 11px;
    line-height: 1.4;
    color: #333;
}

.header {
    text-align: center;
    border-bottom: 2px solid #333;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.header h1 {
    font-size: 16px;
    margin: 0 0 5px 0;
    font-weight: bold;
}

.header .subtitle {
    font-size: 12px;
    color: #666;
}

.security-notice {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    padding: 8px;
    margin-bottom: 15px;
    border-radius: 3px;
}

.security-notice strong {
    color: #856404;
}

.info-section {
    margin-bottom: 20px;
}

.section-title {
    font-size: 12px;
    font-weight: bold;
    background-color: #f5f5f5;
    padding: 6px 10px;
    border-left: 3px solid #007bff;
    margin-bottom: 10px;
}

.info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
}

.info-table th,
.info-table td {
    border: 1px solid #ddd;
    padding: 6px 10px;
    text-align: left;
    vertical-align: top;
}

.info-table th {
    background-color: #f8f9fa;
    font-weight: bold;
    width: 30%;
    font-size: 10px;
}

.info-table td {
    width: 70%;
    font-size: 10px;
}

.status-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 9px;
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

.approval-info {
    background-color: #e7f3ff;
    border: 1px solid #b3d9ff;
    padding: 8px;
    border-radius: 3px;
    margin-bottom: 10px;
    font-size: 10px;
}

.approval-info strong {
    color: #0066cc;
}

.security-footer {
    margin-top: 20px;
    padding-top: 10px;
    border-top: 1px solid #ddd;
    font-size: 8px;
    color: #666;
}

.metadata-section {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 8px;
    margin-top: 15px;
    font-size: 8px;
}

.metadata-section h4 {
    font-size: 10px;
    margin: 0 0 5px 0;
    color: #495057;
}
</style>

<!-- Security Notice -->
<div class="security-notice">
    <strong>セキュリティ保護文書:</strong> 
    この文書は改ざん防止機能により保護されています。無断での編集・印刷・複製は禁止されています。
</div>

<!-- Header -->
<div class="header">
    <h1>施設情報帳票（セキュア版）</h1>
    <div class="subtitle">Secure Facility Information Report</div>
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

<!-- Document Metadata Section -->
<div class="metadata-section">
    <h4>文書メタデータ</h4>
    <table class="info-table">
        <tr>
            <th>出力日時</th>
            <td>{{ $generated_at->format('Y年m月d日 H:i:s') }}</td>
        </tr>
        <tr>
            <th>出力者</th>
            <td>{{ $generated_by->name }} ({{ $generated_by->email }})</td>
        </tr>
        <tr>
            <th>文書ID</th>
            <td>{{ hash('sha256', $facility->id . $facility->updated_at . $generated_by->id . $generated_at->format('Y-m-d')) }}</td>
        </tr>
        <tr>
            <th>セキュリティレベル</th>
            <td>保護済み（パスワード・編集制限適用）</td>
        </tr>
    </table>
</div>

<!-- Security Footer -->
<div class="security-footer">
    <div>
        <strong>重要:</strong> この文書は Shise-Cal システムにより自動生成され、セキュリティ保護が適用されています。
    </div>
    <div style="margin-top: 3px;">
        改ざん防止のため、パスワード保護・編集制限・印刷制限が設定されています。
        文書の真正性に疑問がある場合は、システム管理者にお問い合わせください。
    </div>
    <div style="margin-top: 3px;">
        文書ハッシュ: {{ hash('sha256', serialize([$facility->toArray(), $generated_at->timestamp, $generated_by->id])) }}
    </div>
</div>
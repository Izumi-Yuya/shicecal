@extends('layouts.app')

@section('title', 'システム設定')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="fas fa-cogs me-2"></i>
                    システム設定
                </h1>
                <div class="d-flex gap-2">
                    <button class="btn btn-success" onclick="saveAllSettings()">
                        <i class="fas fa-save"></i> 設定を保存
                    </button>
                    <button class="btn btn-outline-secondary" onclick="resetToDefaults()">
                        <i class="fas fa-undo"></i> デフォルトに戻す
                    </button>
                </div>
            </div>

            <!-- Settings Navigation Tabs -->
            <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                        <i class="fas fa-cog me-1"></i>一般設定
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="approval-tab" data-bs-toggle="tab" data-bs-target="#approval" type="button" role="tab">
                        <i class="fas fa-check-circle me-1"></i>承認機能
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                        <i class="fas fa-shield-alt me-1"></i>セキュリティ
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="notification-tab" data-bs-toggle="tab" data-bs-target="#notification" type="button" role="tab">
                        <i class="fas fa-bell me-1"></i>通知設定
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="export-tab" data-bs-toggle="tab" data-bs-target="#export" type="button" role="tab">
                        <i class="fas fa-download me-1"></i>出力設定
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab">
                        <i class="fas fa-tools me-1"></i>メンテナンス
                    </button>
                </li>
            </ul>

            <form id="settingsForm">
                @csrf
                <div class="tab-content" id="settingsTabContent">
                    <!-- General Settings -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">基本設定</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="app_name" class="form-label">アプリケーション名</label>
                                            <input type="text" class="form-control" id="app_name" name="app_name" 
                                                   value="{{ $settings['app_name'] ?? 'Shise-Cal' }}">
                                            <div class="form-text">システムのタイトルバーやヘッダーに表示される名前</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="app_description" class="form-label">システム説明</label>
                                            <textarea class="form-control" id="app_description" name="app_description" rows="3">{{ $settings['app_description'] ?? '' }}</textarea>
                                            <div class="form-text">ログイン画面などに表示されるシステムの説明文</div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="timezone" class="form-label">タイムゾーン</label>
                                                    <select class="form-select" id="timezone" name="timezone">
                                                        <option value="Asia/Tokyo" {{ ($settings['timezone'] ?? 'Asia/Tokyo') == 'Asia/Tokyo' ? 'selected' : '' }}>Asia/Tokyo</option>
                                                        <option value="UTC" {{ ($settings['timezone'] ?? '') == 'UTC' ? 'selected' : '' }}>UTC</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="date_format" class="form-label">日付形式</label>
                                                    <select class="form-select" id="date_format" name="date_format">
                                                        <option value="Y/m/d" {{ ($settings['date_format'] ?? 'Y/m/d') == 'Y/m/d' ? 'selected' : '' }}>YYYY/MM/DD</option>
                                                        <option value="Y-m-d" {{ ($settings['date_format'] ?? '') == 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD</option>
                                                        <option value="m/d/Y" {{ ($settings['date_format'] ?? '') == 'm/d/Y' ? 'selected' : '' }}>MM/DD/YYYY</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="pagination_per_page" class="form-label">1ページあたりの表示件数</label>
                                            <select class="form-select" id="pagination_per_page" name="pagination_per_page">
                                                <option value="10" {{ ($settings['pagination_per_page'] ?? '20') == '10' ? 'selected' : '' }}>10件</option>
                                                <option value="20" {{ ($settings['pagination_per_page'] ?? '20') == '20' ? 'selected' : '' }}>20件</option>
                                                <option value="50" {{ ($settings['pagination_per_page'] ?? '20') == '50' ? 'selected' : '' }}>50件</option>
                                                <option value="100" {{ ($settings['pagination_per_page'] ?? '20') == '100' ? 'selected' : '' }}>100件</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">システム情報</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <th>バージョン:</th>
                                                <td>{{ config('app.version', '1.0.0') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Laravel:</th>
                                                <td>{{ app()->version() }}</td>
                                            </tr>
                                            <tr>
                                                <th>PHP:</th>
                                                <td>{{ PHP_VERSION }}</td>
                                            </tr>
                                            <tr>
                                                <th>環境:</th>
                                                <td>
                                                    <span class="badge bg-{{ app()->environment('production') ? 'danger' : 'warning' }}">
                                                        {{ strtoupper(app()->environment()) }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>デバッグ:</th>
                                                <td>
                                                    <span class="badge bg-{{ config('app.debug') ? 'warning' : 'success' }}">
                                                        {{ config('app.debug') ? 'ON' : 'OFF' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Approval Settings -->
                    <div class="tab-pane fade" id="approval" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">承認機能設定</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="approval_enabled" 
                                                       name="approval_enabled" value="1" 
                                                       {{ ($settings['approval_enabled'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="approval_enabled">
                                                    <strong>承認機能を有効にする</strong>
                                                </label>
                                            </div>
                                            <div class="form-text">
                                                有効にすると、施設情報の変更時に承認プロセスが実行されます
                                            </div>
                                        </div>
                                        
                                        <div id="approvalSettings" style="display: {{ ($settings['approval_enabled'] ?? false) ? 'block' : 'none' }};">
                                            <div class="mb-3">
                                                <label for="auto_approve_minor_changes" class="form-label">軽微な変更の自動承認</label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="auto_approve_minor_changes" 
                                                           name="auto_approve_minor_changes" value="1"
                                                           {{ ($settings['auto_approve_minor_changes'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="auto_approve_minor_changes">
                                                        電話番号やFAX番号の変更は自動承認する
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="approval_timeout_days" class="form-label">承認期限（日数）</label>
                                                <input type="number" class="form-control" id="approval_timeout_days" 
                                                       name="approval_timeout_days" min="1" max="30"
                                                       value="{{ $settings['approval_timeout_days'] ?? 7 }}">
                                                <div class="form-text">承認依頼から何日後に自動的にタイムアウトするか</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="approval_reminder_days" class="form-label">リマインダー送信（日数）</label>
                                                <input type="number" class="form-control" id="approval_reminder_days" 
                                                       name="approval_reminder_days" min="1" max="10"
                                                       value="{{ $settings['approval_reminder_days'] ?? 3 }}">
                                                <div class="form-text">承認依頼から何日後にリマインダーを送信するか</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-header">
                                                <h6 class="mb-0">承認フロー概要</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="approval-flow">
                                                    <div class="flow-step">
                                                        <i class="fas fa-edit text-primary"></i>
                                                        <span>編集者が変更</span>
                                                    </div>
                                                    <div class="flow-arrow">↓</div>
                                                    <div class="flow-step">
                                                        <i class="fas fa-clock text-warning"></i>
                                                        <span>承認待ち状態</span>
                                                    </div>
                                                    <div class="flow-arrow">↓</div>
                                                    <div class="flow-step">
                                                        <i class="fas fa-user-check text-info"></i>
                                                        <span>承認者が確認</span>
                                                    </div>
                                                    <div class="flow-arrow">↓</div>
                                                    <div class="flow-step">
                                                        <i class="fas fa-check-circle text-success"></i>
                                                        <span>承認完了・反映</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Settings -->
                    <div class="tab-pane fade" id="security" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">IP制限設定</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ip_restriction_enabled" 
                                                       name="ip_restriction_enabled" value="1"
                                                       {{ ($settings['ip_restriction_enabled'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="ip_restriction_enabled">
                                                    <strong>IP制限を有効にする</strong>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div id="ipRestrictionSettings" style="display: {{ ($settings['ip_restriction_enabled'] ?? false) ? 'block' : 'none' }};">
                                            <div class="mb-3">
                                                <label for="allowed_ips" class="form-label">許可IPアドレス</label>
                                                <textarea class="form-control" id="allowed_ips" name="allowed_ips" rows="5" 
                                                          placeholder="192.168.1.0/24&#10;10.0.0.1&#10;203.0.113.0/24">{{ $settings['allowed_ips'] ?? '' }}</textarea>
                                                <div class="form-text">
                                                    1行に1つのIPアドレスまたはCIDR記法で入力してください<br>
                                                    例: 192.168.1.100 または 192.168.1.0/24
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">セッション設定</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="session_lifetime" class="form-label">セッション有効期限（分）</label>
                                            <input type="number" class="form-control" id="session_lifetime" 
                                                   name="session_lifetime" min="30" max="1440"
                                                   value="{{ $settings['session_lifetime'] ?? 120 }}">
                                            <div class="form-text">ユーザーが自動ログアウトされるまでの時間</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="force_https" 
                                                       name="force_https" value="1"
                                                       {{ ($settings['force_https'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="force_https">
                                                    HTTPS接続を強制する
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="secure_cookies" 
                                                       name="secure_cookies" value="1"
                                                       {{ ($settings['secure_cookies'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="secure_cookies">
                                                    セキュアクッキーを使用する
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="tab-pane fade" id="notification" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">通知設定</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="notification_from_email" class="form-label">送信者メールアドレス</label>
                                            <input type="email" class="form-control" id="notification_from_email" 
                                                   name="notification_from_email" 
                                                   value="{{ $settings['notification_from_email'] ?? 'noreply@example.com' }}">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="notification_from_name" class="form-label">送信者名</label>
                                            <input type="text" class="form-control" id="notification_from_name" 
                                                   name="notification_from_name" 
                                                   value="{{ $settings['notification_from_name'] ?? 'Shise-Cal システム' }}">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="email_notifications_enabled" 
                                                       name="email_notifications_enabled" value="1"
                                                       {{ ($settings['email_notifications_enabled'] ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="email_notifications_enabled">
                                                    メール通知を有効にする
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">通知タイプ</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="notify_comment_posted" 
                                                       name="notify_comment_posted" value="1"
                                                       {{ ($settings['notify_comment_posted'] ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="notify_comment_posted">
                                                    コメント投稿時
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="notify_approval_request" 
                                                       name="notify_approval_request" value="1"
                                                       {{ ($settings['notify_approval_request'] ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="notify_approval_request">
                                                    承認依頼時
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="notify_annual_confirmation" 
                                                       name="notify_annual_confirmation" value="1"
                                                       {{ ($settings['notify_annual_confirmation'] ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="notify_annual_confirmation">
                                                    年次確認依頼時
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Export Settings -->
                    <div class="tab-pane fade" id="export" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">PDF出力設定</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="pdf_password_protection" class="form-label">PDFパスワード保護</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="pdf_password_protection" 
                                                       name="pdf_password_protection" value="1"
                                                       {{ ($settings['pdf_password_protection'] ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="pdf_password_protection">
                                                    PDFにパスワード保護を適用する
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="pdf_watermark" class="form-label">透かし文字</label>
                                            <input type="text" class="form-control" id="pdf_watermark" 
                                                   name="pdf_watermark" 
                                                   value="{{ $settings['pdf_watermark'] ?? '機密' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">CSV出力設定</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="csv_encoding" class="form-label">文字エンコーディング</label>
                                            <select class="form-select" id="csv_encoding" name="csv_encoding">
                                                <option value="UTF-8-BOM" {{ ($settings['csv_encoding'] ?? 'UTF-8-BOM') == 'UTF-8-BOM' ? 'selected' : '' }}>UTF-8 with BOM</option>
                                                <option value="UTF-8" {{ ($settings['csv_encoding'] ?? '') == 'UTF-8' ? 'selected' : '' }}>UTF-8</option>
                                                <option value="Shift_JIS" {{ ($settings['csv_encoding'] ?? '') == 'Shift_JIS' ? 'selected' : '' }}>Shift_JIS</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="csv_delimiter" class="form-label">区切り文字</label>
                                            <select class="form-select" id="csv_delimiter" name="csv_delimiter">
                                                <option value="," {{ ($settings['csv_delimiter'] ?? ',') == ',' ? 'selected' : '' }}>カンマ (,)</option>
                                                <option value=";" {{ ($settings['csv_delimiter'] ?? '') == ';' ? 'selected' : '' }}>セミコロン (;)</option>
                                                <option value="\t" {{ ($settings['csv_delimiter'] ?? '') == '\t' ? 'selected' : '' }}>タブ</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Maintenance Settings -->
                    <div class="tab-pane fade" id="maintenance" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">システムメンテナンス</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="maintenance_mode" 
                                                       name="maintenance_mode" value="1"
                                                       {{ ($settings['maintenance_mode'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="maintenance_mode">
                                                    <strong>メンテナンスモード</strong>
                                                </label>
                                            </div>
                                            <div class="form-text">有効にすると管理者以外はアクセスできなくなります</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="maintenance_message" class="form-label">メンテナンスメッセージ</label>
                                            <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="3">{{ $settings['maintenance_message'] ?? 'システムメンテナンス中です。しばらくお待ちください。' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">データ管理</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="log_retention_days" class="form-label">ログ保持期間（日数）</label>
                                            <input type="number" class="form-control" id="log_retention_days" 
                                                   name="log_retention_days" min="30" max="3650"
                                                   value="{{ $settings['log_retention_days'] ?? 365 }}">
                                            <div class="form-text">この期間を過ぎたログは自動削除されます</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <button type="button" class="btn btn-warning" onclick="clearCache()">
                                                <i class="fas fa-broom"></i> キャッシュクリア
                                            </button>
                                            <div class="form-text">システムのキャッシュをクリアします</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <button type="button" class="btn btn-info" onclick="optimizeDatabase()">
                                                <i class="fas fa-database"></i> データベース最適化
                                            </button>
                                            <div class="form-text">データベースのパフォーマンスを最適化します</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
.approval-flow {
    text-align: center;
}

.flow-step {
    padding: 10px;
    margin: 5px 0;
    background: white;
    border-radius: 5px;
    border: 1px solid #dee2e6;
}

.flow-step i {
    margin-right: 8px;
}

.flow-arrow {
    font-size: 18px;
    color: #6c757d;
    margin: 5px 0;
}

.nav-tabs .nav-link {
    color: #495057;
}

.nav-tabs .nav-link.active {
    font-weight: 600;
}
</style>
@endpush

@push('scripts')
<script>
// Toggle approval settings visibility
document.getElementById('approval_enabled').addEventListener('change', function() {
    const approvalSettings = document.getElementById('approvalSettings');
    approvalSettings.style.display = this.checked ? 'block' : 'none';
});

// Toggle IP restriction settings visibility
document.getElementById('ip_restriction_enabled').addEventListener('change', function() {
    const ipRestrictionSettings = document.getElementById('ipRestrictionSettings');
    ipRestrictionSettings.style.display = this.checked ? 'block' : 'none';
});

// Save all settings
function saveAllSettings() {
    const form = document.getElementById('settingsForm');
    const formData = new FormData(form);
    
    // Show loading state
    const saveButton = event.target;
    const originalText = saveButton.innerHTML;
    saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 保存中...';
    saveButton.disabled = true;
    
    fetch('{{ route("admin.settings.update") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showAlert('設定が正常に保存されました', 'success');
        } else {
            showAlert('設定の保存に失敗しました：' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('設定の保存中にエラーが発生しました', 'danger');
    })
    .finally(() => {
        // Restore button state
        saveButton.innerHTML = originalText;
        saveButton.disabled = false;
    });
}

// Reset to defaults
function resetToDefaults() {
    if (confirm('すべての設定をデフォルト値に戻しますか？この操作は元に戻せません。')) {
        fetch('{{ route("admin.settings.reset") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showAlert('設定のリセットに失敗しました', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('設定のリセット中にエラーが発生しました', 'danger');
        });
    }
}

// Clear cache
function clearCache() {
    if (confirm('システムキャッシュをクリアしますか？')) {
        fetch('{{ route("admin.settings.clear-cache") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('キャッシュがクリアされました', 'success');
            } else {
                showAlert('キャッシュのクリアに失敗しました', 'danger');
            }
        });
    }
}

// Optimize database
function optimizeDatabase() {
    if (confirm('データベースを最適化しますか？この処理には時間がかかる場合があります。')) {
        fetch('{{ route("admin.settings.optimize-database") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('データベースが最適化されました', 'success');
            } else {
                showAlert('データベースの最適化に失敗しました', 'danger');
            }
        });
    }
}

// Show alert message
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at the top of the container
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Auto-save on certain changes (optional)
document.addEventListener('DOMContentLoaded', function() {
    const autoSaveElements = ['maintenance_mode', 'approval_enabled'];
    
    autoSaveElements.forEach(elementId => {
        const element = document.getElementById(elementId);
        if (element) {
            element.addEventListener('change', function() {
                // Auto-save critical settings
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                formData.append(elementId, this.checked ? '1' : '0');
                
                fetch('{{ route("admin.settings.update-single") }}', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(`${this.labels[0].textContent}が更新されました`, 'info');
                    }
                });
            });
        }
    });
});
</script>
@endpush
@endsection
@extends('layouts.app')

@section('title', 'PDF出力')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="fas fa-file-pdf me-2 text-danger"></i>
                    PDF出力
                </h1>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="pdfOptionsDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> 出力オプション
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="showPdfHistory()">
                                <i class="fas fa-history"></i> 出力履歴
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="showPdfSettings()">
                                <i class="fas fa-cog"></i> PDF設定
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="showPdfPreview()">
                                <i class="fas fa-eye"></i> レイアウトプレビュー
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card admin-card">
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>注意事項:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>承認済みの施設情報のみPDF出力が可能です</li>
                                    <li>複数施設を選択した場合、ZIP形式でダウンロードされます</li>
                                    <li>セキュア版PDFには改ざん防止機能（パスワード保護・編集制限）が適用されます</li>
                                    <li>標準版PDFは基本的な表示のみで、セキュリティ機能は適用されません</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-shield-alt me-2"></i>
                                        セキュリティオプション
                                    </h6>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="pdf_type" id="secure_pdf" value="secure" checked>
                                        <label class="form-check-label" for="secure_pdf">
                                            <i class="fas fa-lock me-1 text-success"></i>
                                            セキュア版PDF（推奨）
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="pdf_type" id="standard_pdf" value="standard">
                                        <label class="form-check-label" for="standard_pdf">
                                            <i class="fas fa-file-pdf me-1 text-primary"></i>
                                            標準版PDF
                                        </label>
                                    </div>
                                    <small class="form-text text-muted d-block mt-2">
                                        セキュア版PDFは改ざん防止のため、パスワード保護・編集制限・印刷制限が適用されます。
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('export.pdf.batch') }}" method="POST" id="pdfExportForm">
                        @csrf
                        <input type="hidden" name="secure" id="secureInput" value="1">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="selectAll">
                                        <i class="fas fa-check-square me-1"></i>全選択
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="deselectAll">
                                        <i class="fas fa-square me-1"></i>全解除
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="submit" class="btn btn-primary" id="exportBtn" disabled>
                                    <i class="fas fa-download me-2"></i>
                                    PDF出力
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="masterCheckbox" class="form-check-input">
                                        </th>
                                        <th>事業所コード</th>
                                        <th>施設名</th>
                                        <th>会社名</th>
                                        <th>住所</th>
                                        <th>ステータス</th>
                                        <th>最終更新日</th>
                                        <th width="100">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($facilities as $facility)
                                        <tr>
                                            <td>
                                                <input type="checkbox" 
                                                       name="facility_ids[]" 
                                                       value="{{ $facility->id }}" 
                                                       class="form-check-input facility-checkbox">
                                            </td>
                                            <td>
                                                <code>{{ $facility->office_code }}</code>
                                            </td>
                                            <td>
                                                <strong>{{ $facility->facility_name }}</strong>
                                            </td>
                                            <td>{{ $facility->company_name }}</td>
                                            <td>
                                                <small class="text-muted">
                                                    @if($facility->postal_code)
                                                        〒{{ $facility->postal_code }}<br>
                                                    @endif
                                                    {{ Str::limit($facility->address, 50) }}
                                                </small>
                                            </td>
                                            <td>
                                                @switch($facility->status)
                                                    @case('approved')
                                                        <span class="badge bg-success">承認済</span>
                                                        @break
                                                    @case('pending_approval')
                                                        <span class="badge bg-warning">承認待ち</span>
                                                        @break
                                                    @case('draft')
                                                        <span class="badge bg-secondary">下書き</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-light text-dark">不明</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $facility->updated_at ? $facility->updated_at->format('Y/m/d H:i') : '-' }}
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('export.pdf.secure', $facility) }}" 
                                                       class="btn btn-sm btn-success"
                                                       title="セキュアPDF出力">
                                                        <i class="fas fa-lock"></i>
                                                    </a>
                                                    <a href="{{ route('export.pdf.single', ['facility' => $facility, 'secure' => 0]) }}" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="標準PDF出力">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="fas fa-inbox fa-2x mb-3"></i>
                                                    <p>PDF出力可能な施設がありません。</p>
                                                    <small>承認済みの施設情報のみ表示されます。</small>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($facilities->count() > 0)
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        {{ $facilities->count() }} 件の施設が表示されています
                                    </small>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="submit" class="btn btn-primary" id="exportBtn2" disabled>
                                        <i class="fas fa-download me-2"></i>
                                        選択した施設のPDF出力
                                    </button>
                                </div>
                            </div>
                        @endif

                        <!-- Progress Modal -->
                        <div class="modal fade" id="progressModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="fas fa-cog fa-spin me-2"></i>
                                            PDF生成中
                                        </h5>
                                    </div>
                                    <div class="modal-body">
                                        <div class="progress mb-3">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                 role="progressbar" 
                                                 style="width: 0%" 
                                                 id="progressBar">
                                                0%
                                            </div>
                                        </div>
                                        <div id="progressText" class="text-center">
                                            処理を開始しています...
                                        </div>
                                        <div id="progressDetails" class="mt-3 small text-muted">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" disabled id="closeProgressBtn">
                                            閉じる
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const masterCheckbox = document.getElementById('masterCheckbox');
    const facilityCheckboxes = document.querySelectorAll('.facility-checkbox');
    const exportBtns = document.querySelectorAll('#exportBtn, #exportBtn2');
    const selectAllBtn = document.getElementById('selectAll');
    const deselectAllBtn = document.getElementById('deselectAll');

    // Master checkbox functionality
    masterCheckbox.addEventListener('change', function() {
        facilityCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateExportButtonState();
    });

    // Individual checkbox functionality
    facilityCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateMasterCheckboxState();
            updateExportButtonState();
        });
    });

    // Select all button
    selectAllBtn.addEventListener('click', function() {
        facilityCheckboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
        updateMasterCheckboxState();
        updateExportButtonState();
    });

    // Deselect all button
    deselectAllBtn.addEventListener('click', function() {
        facilityCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        updateMasterCheckboxState();
        updateExportButtonState();
    });

    function updateMasterCheckboxState() {
        const checkedCount = document.querySelectorAll('.facility-checkbox:checked').length;
        const totalCount = facilityCheckboxes.length;
        
        if (checkedCount === 0) {
            masterCheckbox.checked = false;
            masterCheckbox.indeterminate = false;
        } else if (checkedCount === totalCount) {
            masterCheckbox.checked = true;
            masterCheckbox.indeterminate = false;
        } else {
            masterCheckbox.checked = false;
            masterCheckbox.indeterminate = true;
        }
    }

    function updateExportButtonState() {
        const checkedCount = document.querySelectorAll('.facility-checkbox:checked').length;
        exportBtns.forEach(btn => {
            btn.disabled = checkedCount === 0;
        });
    }

    // Security option handling
    const secureRadio = document.getElementById('secure_pdf');
    const standardRadio = document.getElementById('standard_pdf');
    const secureInput = document.getElementById('secureInput');

    secureRadio.addEventListener('change', function() {
        if (this.checked) {
            secureInput.value = '1';
        }
    });

    standardRadio.addEventListener('change', function() {
        if (this.checked) {
            secureInput.value = '0';
        }
    });

    // Form submission with progress tracking
    document.getElementById('pdfExportForm').addEventListener('submit', function(e) {
        const checkedCount = document.querySelectorAll('.facility-checkbox:checked').length;
        
        if (checkedCount > 3) {
            // Show progress modal for batch operations
            e.preventDefault();
            showProgressModal();
            
            // Submit form via AJAX for progress tracking
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (response.ok) {
                    return response.blob();
                }
                throw new Error('Network response was not ok');
            })
            .then(blob => {
                // Create download link
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = 'facility_reports.zip';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                
                hideProgressModal();
            })
            .catch(error => {
                console.error('Error:', error);
                hideProgressModal();
                alert('PDF生成中にエラーが発生しました。');
            });
        } else {
            // Normal form submission for small batches
            exportBtns.forEach(btn => {
                btn.disabled = true;
                const pdfType = secureRadio.checked ? 'セキュア' : '標準';
                btn.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${pdfType}PDF生成中...`;
            });
        }
    });

    function showProgressModal() {
        const modal = new bootstrap.Modal(document.getElementById('progressModal'));
        modal.show();
        
        // Simulate progress for demo (in real implementation, this would poll the server)
        let progress = 0;
        const interval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 100) progress = 100;
            
            updateProgress(progress, `処理中... ${Math.round(progress)}%`);
            
            if (progress >= 100) {
                clearInterval(interval);
                updateProgress(100, '完了しました！');
                document.getElementById('closeProgressBtn').disabled = false;
            }
        }, 500);
    }

    function hideProgressModal() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('progressModal'));
        if (modal) {
            modal.hide();
        }
        
        // Reset form buttons
        exportBtns.forEach(btn => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-download me-2"></i>PDF出力';
        });
    }

    function updateProgress(percentage, text, details = '') {
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const progressDetails = document.getElementById('progressDetails');
        
        progressBar.style.width = percentage + '%';
        progressBar.textContent = Math.round(percentage) + '%';
        progressText.textContent = text;
        progressDetails.textContent = details;
    }
});
</script>
@endpush
@endsection
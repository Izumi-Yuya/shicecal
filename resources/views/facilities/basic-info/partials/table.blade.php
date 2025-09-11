{{-- Basic Info Table View Partial --}}
<div class="facility-table-view">
    <!-- テーブルヘッダー（コメント機能付き） -->
    <div class="table-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-table text-primary me-2"></i>基本情報（テーブル形式）
            </h5>
            <div class="table-view-comment-controls">
                <button class="btn btn-outline-primary btn-sm comment-toggle" 
                        data-section="basic_info" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示">
                    <i class="fas fa-comment me-1"></i>
                    コメント
                    <span class="badge bg-primary ms-1 comment-count" data-section="basic_info">0</span>
                </button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <!-- 基本情報テーブル -->
        <table class="table table-bordered facility-info">
            <tbody>
                <tr>
                    <th>会社名</th>
                    <td>
                        @if($facility->company_name)
                            {{ $facility->company_name }}
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                    <th>事業所コード</th>
                    <td>
                        @if($facility->office_code)
                            {{ $facility->office_code }}
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>施設名</th>
                    <td>
                        @if($facility->facility_name)
                            <span class="fw-bold">{{ $facility->facility_name }}</span>
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                    <th>指定番号</th>
                    <td>
                        @if($facility->designation_number)
                            {{ $facility->designation_number }}
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>郵便番号</th>
                    <td>
                        @if($facility->formatted_postal_code)
                            {{ $facility->formatted_postal_code }}
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                    <th>開設日</th>
                    <td>
                        @if($facility->opening_date)
                            {{ $facility->opening_date->format('Y年m月d日') }}
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>住所</th>
                    <td>
                        @if($facility->full_address)
                            {{ $facility->full_address }}
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                    <th>開設年数</th>
                    <td>
                        @if($facility->years_in_operation !== null)
                            {{ number_format($facility->years_in_operation) }}年
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>住所（建物名）</th>
                    <td>
                        @if($facility->building_name)
                            {{ $facility->building_name }}
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                    <th>建物構造</th>
                    <td>
                        @if($facility->building_structure)
                            {{ $facility->building_structure }}
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>電話番号</th>
                    <td>
                        @if($facility->phone_number)
                            {{ $facility->phone_number }}
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                    <th>建物階数</th>
                    <td>
                        @if($facility->building_floors !== null)
                            {{ number_format($facility->building_floors) }}階
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>FAX番号</th>
                    <td>
                        @if($facility->fax_number)
                            {{ $facility->fax_number }}
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                    <th>居室数</th>
                    <td>
                        @if($facility->paid_rooms_count !== null)
                            {{ number_format($facility->paid_rooms_count) }}室
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>フリーダイヤル</th>
                    <td>
                        @if($facility->toll_free_number)
                            {{ $facility->toll_free_number }}
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                    <th>内SS数</th>
                    <td>
                        @if($facility->ss_rooms_count !== null)
                            {{ number_format($facility->ss_rooms_count) }}室
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>メールアドレス</th>
                    <td>
                        @if($facility->email)
                            <a href="mailto:{{ $facility->email }}" class="text-decoration-none text-primary">{{ $facility->email }}</a>
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                    <th>定員数</th>
                    <td>
                        @if($facility->capacity !== null)
                            {{ number_format($facility->capacity) }}名
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>URL</th>
                    <td colspan="3">
                        @if($facility->website_url)
                            <a href="{{ $facility->website_url }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-primary">{{ $facility->website_url }}</a>
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- サービス種類テーブル -->
        @include('facilities.services.partials.table', ['services' => $facility->services ?? collect()])
    </div>

    <!-- コメントセクション -->
    <div class="comment-section mt-4 d-none" data-section="basic_info">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-comments me-2"></i>基本情報のコメント
                </h6>
            </div>
            <div class="card-body">
                <div class="comment-form mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control comment-input" 
                               placeholder="コメントを入力..." 
                               data-section="basic_info">
                        <button class="btn btn-primary comment-submit" 
                                data-section="basic_info">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
                <div class="comment-list" data-section="basic_info">
                    <!-- コメントがここに動的に追加されます -->
                </div>
            </div>
        </div>
    </div>
</div>
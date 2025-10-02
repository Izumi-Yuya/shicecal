{{--
Fallback Component
フォールバックコンポーネント - JavaScript無効時やエラー時の代替表示

要件: 7.5 (no-jsフォールバック機能)
--}}

@props([
    'title' => null,        // カードタイトル
    'message' => 'データを表示できませんでした',
    'showRetry' => false,   // 再試行ボタンの表示
    'cardClass' => 'facility-info-card detail-card-improved mb-3',
    'data' => [],           // フォールバック用の基本データ
])

<div class="{{ $cardClass }}" role="alert" aria-live="polite">
    @if($title)
        <div class="card-header">
            <h5 class="card-title mb-0">{{ $title }}</h5>
        </div>
    @endif
    
    <div class="card-body">
        @if(!empty($data))
            {{-- 基本的なテーブル表示（JavaScript不要） --}}
            <div class="table-responsive">
                <table class="table table-bordered facility-basic-info-table-clean" role="table">
                    <tbody>
                        @foreach($data as $rowData)
                            @if(is_array($rowData) && isset($rowData['cells']))
                                <tr>
                                    @foreach($rowData['cells'] as $cellData)
                                        @if(isset($cellData['label']))
                                            <td class="detail-label" scope="row">
                                                {{ $cellData['label'] }}
                                            </td>
                                            <td class="detail-value {{ empty($cellData['value']) ? 'empty-field' : '' }}">
                                                {{ $cellData['value'] ?? '未設定' }}
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            {{-- エラーメッセージ表示 --}}
            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i>
                <div>
                    <strong>表示エラー</strong><br>
                    {{ $message }}
                </div>
            </div>
            
            @if($showRetry)
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
                        <i class="fas fa-redo me-1" aria-hidden="true"></i>
                        再読み込み
                    </button>
                </div>
            @endif
        @endif
        
        {{-- スクリーンリーダー用の追加情報 --}}
        <div class="sr-only">
            @if($title)
                {{ $title }}の情報表示。
            @endif
            @if(!empty($data))
                {{ count($data) }}件の項目があります。
            @else
                データの表示に問題が発生しました。
            @endif
        </div>
    </div>
</div>
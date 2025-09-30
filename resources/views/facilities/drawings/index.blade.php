<div class="drawings-container">
    <div class="row">
        <!-- 建物図面 -->
        <div class="col-md-6">
            <h5 class="mb-3">建物図面</h5>
            <div class="table-responsive">
                <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                    <tbody>
                        @php
                            $buildingDrawingTypes = [
                                'floor_plan' => '平面図',
                                'site_plan' => '配置図',
                                'elevation' => '立面図',
                                'development' => '展開図',
                                'area_calculation' => '求積図',
                            ];
                        @endphp
                        
                        @foreach($buildingDrawingTypes as $type => $title)
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem; width: 35%;">{{ $title }}</td>
                                <td class="detail-value {{ !isset($drawingsData['building_drawings'][$type]) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if(isset($drawingsData['building_drawings'][$type]))
                                        @php $drawing = $drawingsData['building_drawings'][$type]; @endphp
                                        <a href="{{ route('facilities.drawings.download', [$facility, $type]) }}" 
                                           class="text-decoration-none" 
                                           target="_blank">
                                            <i class="fas fa-file-pdf me-1 text-danger"></i>PDF
                                            @if(!$drawing['exists'])
                                                <small class="text-muted">(ファイルが見つかりません)</small>
                                            @endif
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        
                        {{-- 追加建物図面 --}}
                        @if(isset($drawingsData['building_drawings']))
                            @foreach($drawingsData['building_drawings'] as $type => $drawing)
                                @if(!in_array($type, array_keys($buildingDrawingTypes)))
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem;">{{ $drawing['title'] }}</td>
                                        <td class="detail-value" style="padding: 0.5rem;">
                                            <a href="{{ route('facilities.drawings.download', [$facility, $type]) }}" 
                                               class="text-decoration-none" 
                                               target="_blank">
                                                <i class="fas fa-file-pdf me-1 text-danger"></i>PDF
                                                @if(!$drawing['exists'])
                                                    <small class="text-muted">(ファイルが見つかりません)</small>
                                                @endif
                                            </a>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- 設備図面 -->
        <div class="col-md-6">
            <h5 class="mb-3">設備図面</h5>
            <div class="table-responsive">
                <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                    <tbody>
                        @php
                            $equipmentDrawingTypes = [
                                'electrical_equipment' => '電気設備図面',
                                'lighting_equipment' => '電灯設備図面',
                                'hvac_equipment' => '空調設備図面',
                                'plumbing_equipment' => '給排水衛生設備図面',
                                'kitchen_equipment' => '厨房設備図面',
                            ];
                        @endphp
                        
                        @foreach($equipmentDrawingTypes as $type => $title)
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem; width: 45%;">{{ $title }}</td>
                                <td class="detail-value {{ !isset($drawingsData['equipment_drawings'][$type]) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if(isset($drawingsData['equipment_drawings'][$type]))
                                        @php $drawing = $drawingsData['equipment_drawings'][$type]; @endphp
                                        <a href="{{ route('facilities.drawings.download', [$facility, $type]) }}" 
                                           class="text-decoration-none" 
                                           target="_blank">
                                            <i class="fas fa-file-pdf me-1 text-danger"></i>PDF
                                            @if(!$drawing['exists'])
                                                <small class="text-muted">(ファイルが見つかりません)</small>
                                            @endif
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        
                        {{-- 追加設備図面 --}}
                        @if(isset($drawingsData['equipment_drawings']))
                            @foreach($drawingsData['equipment_drawings'] as $type => $drawing)
                                @if(!in_array($type, array_keys($equipmentDrawingTypes)))
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem;">{{ $drawing['title'] }}</td>
                                        <td class="detail-value" style="padding: 0.5rem;">
                                            <a href="{{ route('facilities.drawings.download', [$facility, $type]) }}" 
                                               class="text-decoration-none" 
                                               target="_blank">
                                                <i class="fas fa-file-pdf me-1 text-danger"></i>PDF
                                                @if(!$drawing['exists'])
                                                    <small class="text-muted">(ファイルが見つかりません)</small>
                                                @endif
                                            </a>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- 備考 -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                    <tbody>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem; width: 15%;">備考</td>
                            <td class="detail-value {{ (!isset($drawingsData['notes']) || empty($drawingsData['notes'])) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if(isset($drawingsData['notes']) && !empty($drawingsData['notes']))
                                    {!! nl2br(e($drawingsData['notes'])) !!}
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- 竣工時引き渡し図面 -->
    <div class="row mt-4">
        <div class="col-12">
            <h5 class="mb-3">竣工時引き渡し図面</h5>
            <div class="table-responsive">
                <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                    <tbody>
                        <!-- 施工図面一式 -->
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem; width: 15%;">施工図面一式</td>
                            <td class="detail-value {{ !isset($drawingsData['handover_drawings']['construction_drawings']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if(isset($drawingsData['handover_drawings']['construction_drawings']))
                                    @php $drawing = $drawingsData['handover_drawings']['construction_drawings']; @endphp
                                    <a href="{{ route('facilities.drawings.download', [$facility, 'construction_drawings']) }}" 
                                       class="text-decoration-none" 
                                       target="_blank">
                                        <i class="fas fa-file-pdf me-1 text-danger"></i>PDF
                                        @if(!$drawing['exists'])
                                            <small class="text-muted">(ファイルが見つかりません)</small>
                                        @endif
                                    </a>
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                        
                        <!-- 備考 -->
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">備考</td>
                            <td class="detail-value {{ (!isset($drawingsData['handover_drawings']['notes']) || empty($drawingsData['handover_drawings']['notes'])) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if(isset($drawingsData['handover_drawings']['notes']) && !empty($drawingsData['handover_drawings']['notes']))
                                    {!! nl2br(e($drawingsData['handover_drawings']['notes'])) !!}
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    
    
    <!-- 編集ボタン（データがない場合） -->
    @if((!isset($drawingsData['building_drawings']) || empty($drawingsData['building_drawings'])) && 
        (!isset($drawingsData['equipment_drawings']) || empty($drawingsData['equipment_drawings'])) && 
        (!isset($drawingsData['notes']) || empty($drawingsData['notes'])))
        <div class="row mt-4">
            <div class="col-12 text-center">
                <p class="text-muted mb-3">図面が登録されていません。</p>
                @if(auth()->user()->canEditFacility($facility->id))
                    <a href="{{ route('facilities.drawings.edit', $facility) }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>図面を登録
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
@props([
    'facility',
    'showType' => true,
    'class' => ''
])

<aside class="card facility-info-card {{ $class }}" 
       role="complementary" 
       aria-labelledby="facility-info-title">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="card-title mb-1" id="facility-info-title">
                    {{ $facility->facility_name }}
                </h5>
                <address class="text-muted mb-0" aria-label="施設の住所">
                    <i class="fas fa-map-marker-alt me-1" aria-hidden="true"></i>
                    @if($facility->prefecture || $facility->city || $facility->address)
                        {{ $facility->prefecture }}{{ $facility->city }}{{ $facility->address }}
                        @if($facility->building_name)
                            {{ $facility->building_name }}
                        @endif
                    @else
                        <span aria-label="住所が未登録です">住所未登録</span>
                    @endif
                </address>
            </div>
            @if($showType && $facility->facility_type)
                <div class="col-md-4 text-md-end">
                    <span class="badge bg-primary" 
                          role="status" 
                          aria-label="施設タイプ: {{ $facility->facility_type }}">
                        {{ $facility->facility_type }}
                    </span>
                </div>
            @endif
        </div>
    </div>
</aside>
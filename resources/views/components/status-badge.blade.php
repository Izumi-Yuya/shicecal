@props([
    'status',
    'type' => 'default'
])

@php
    $statusClasses = [
        'pending' => 'bg-warning text-dark',
        'in_progress' => 'bg-info text-white',
        'resolved' => 'bg-success text-white',
        'approved' => 'bg-success text-white',
        'rejected' => 'bg-danger text-white',
        'draft' => 'bg-secondary text-white',
        'active' => 'bg-primary text-white',
        'inactive' => 'bg-secondary text-white',
    ];
    
    $statusLabels = [
        'pending' => '未対応',
        'in_progress' => '対応中',
        'resolved' => '対応済',
        'approved' => '承認済',
        'rejected' => '差戻し',
        'draft' => '下書き',
        'active' => '有効',
        'inactive' => '無効',
    ];
    
    $class = $statusClasses[$status] ?? 'bg-secondary text-white';
    $label = $statusLabels[$status] ?? $status;
@endphp

<span class="badge {{ $class }} {{ $attributes->get('class') }}">
    {{ $label }}
</span>
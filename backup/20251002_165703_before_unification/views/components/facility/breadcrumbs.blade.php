@props(['breadcrumbs' => []])

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        @foreach($breadcrumbs as $breadcrumb)
            <li class="breadcrumb-item {{ $breadcrumb['active'] ? 'active' : '' }}" 
                @if($breadcrumb['active']) aria-current="page" @endif>
                @if(!$breadcrumb['active'] && isset($breadcrumb['route']))
                    <a href="{{ isset($breadcrumb['params']) ? route($breadcrumb['route'], $breadcrumb['params']) : route($breadcrumb['route']) }}">
                        {{ $breadcrumb['title'] }}
                    </a>
                @else
                    {{ $breadcrumb['title'] }}
                @endif
            </li>
        @endforeach
    </ol>
</nav>
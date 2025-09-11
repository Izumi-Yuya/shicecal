@props([
    'title' => '施設情報編集',
    'facility',
    'breadcrumbs' => [],
    'backRoute' => null,
    'formAction',
    'formMethod' => 'POST',
    'formId' => null
])

@extends('layouts.app')

@section('title', $title)

@section('content')
<!-- Skip navigation for accessibility -->
<a href="#main-content" class="skip-link sr-only sr-only-focusable">メインコンテンツにスキップ</a>

<div class="container-fluid facility-edit-layout">
    <!-- ヘッダーセクション -->
    <header class="d-flex justify-content-between align-items-center mb-4" role="banner">
        <div>
            <h1 class="h3 mb-0" id="page-title">{{ $title }}</h1>
            @if(count($breadcrumbs) > 0)
                <nav aria-label="パンくずリスト">
                    <ol class="breadcrumb mb-0">
                        @foreach($breadcrumbs as $breadcrumb)
                            @if($breadcrumb['active'] ?? false)
                                <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb['title'] }}</li>
                            @else
                                <li class="breadcrumb-item">
                                    <a href="{{ isset($breadcrumb['params']) ? route($breadcrumb['route'], $breadcrumb['params']) : route($breadcrumb['route']) }}"
                                       aria-label="{{ $breadcrumb['title'] }}に移動">
                                        {{ $breadcrumb['title'] }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ol>
                </nav>
            @endif
        </div>
        @if($backRoute)
            <div>
                <a href="{{ $backRoute }}" 
                   class="btn btn-outline-secondary"
                   aria-label="前のページに戻る">
                    <i class="fas fa-arrow-left me-2" aria-hidden="true"></i>戻る
                </a>
            </div>
        @endif
    </header>

    <!-- 施設情報カード -->
    @if($facility)
        <x-facility.info-card :facility="$facility" class="mb-4" />
    @endif

    <!-- フォームコンテンツ -->
    <main id="main-content" role="main">
        <form @if($formId) id="{{ $formId }}" @endif 
              action="{{ $formAction }}" 
              method="POST" 
              enctype="multipart/form-data" 
              class="facility-edit-form"
              aria-labelledby="page-title"
              novalidate>
            @csrf
            @if($formMethod !== 'POST')
                @method($formMethod)
            @endif
            
            <!-- エラー表示 -->
            <x-form.errors />

            <!-- Live region for form status announcements -->
            <div id="form-status-live-region" 
                 aria-live="polite" 
                 aria-atomic="true" 
                 class="sr-only"></div>

            <!-- メインコンテンツスロット -->
            {{ $slot }}

            <!-- フォームアクション -->
            @isset($actions)
                {{ $actions }}
            @else
                <x-form.actions 
                    :cancel-route="$backRoute" 
                    cancel-text="キャンセル"
                    submit-text="保存"
                    submit-icon="fas fa-save"
                />
            @endisset
        </form>
    </main>
</div>
@endsection
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title>{{ config('app.name', 'Shise-Cal') }} - @yield('title', 'ログイン')</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Hiragino+Kaku+Gothic+ProN:wght@400;600&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Vite Assets -->
    @vite('resources/css/auth.css')
    
    @stack('styles')
</head>
<body>
    <div id="app">
        @yield('content')
    </div>
    
    @stack('scripts')
</body>
</html>
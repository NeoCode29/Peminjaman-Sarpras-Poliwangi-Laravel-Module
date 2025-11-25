<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    
    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    {{-- Additional Styles --}}
    @stack('styles')
</head>
<body>
    <div class="app-shell">
        {{-- Sidebar --}}
        @include('layouts.partials.sidebar')

        {{-- Overlay untuk mobile --}}
        <div class="app-shell__overlay" data-sidebar-overlay></div>

        {{-- Main Content --}}
        <div class="app-shell__main">
            {{-- Page Header --}}
            @include('layouts.partials.header')

            {{-- Page Content --}}
            @yield('content')
        </div>
    </div>

    {{-- Additional Scripts --}}
    @stack('scripts')
</body>
</html>

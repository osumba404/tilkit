<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Tilkit'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen">
    @include('layouts.navigation')
    @php
        $pageWrap = ($fullWidth ?? false)
            ? 'w-full max-w-none px-4 sm:px-6 lg:px-8 xl:px-10 2xl:px-12'
            : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8';
    @endphp
    @if (isset($header))
        <header class="border-b border-tk-line/80 bg-tk-surface/90 backdrop-blur-sm">
            <div class="{{ $pageWrap }} py-7">
                {{ $header }}
            </div>
        </header>
    @endif
    @if (session('status'))
        <div class="{{ $pageWrap }} pt-5">
            <div class="tk-status-banner" role="status">
                {{ session('status') }}
            </div>
        </div>
    @endif
    <main class="{{ $pageWrap }} py-8 lg:py-10 w-full tk-page-content {{ request()->routeIs('tools.tilkit.*') ? 'tk-page-tilkit' : '' }}">
        {{ $slot ?? '' }}
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>

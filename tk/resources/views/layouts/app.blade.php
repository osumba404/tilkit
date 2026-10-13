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
<body class="font-body min-h-screen">
    @include('layouts.navigation')
    @php
        $pageWrap = ($fullWidth ?? false)
            ? 'w-full max-w-none px-4 sm:px-6 lg:px-8 xl:px-10'
            : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8';
    @endphp
    @if (isset($header))
        <header class="border-b border-tk-line bg-tk-surface">
            <div class="{{ $pageWrap }} py-6">
                {{ $header }}
            </div>
        </header>
    @endif
    @if (session('status'))
        <div class="{{ $pageWrap }} pt-4">
            <div class="rounded-md bg-tk-accent-soft border border-tk-accent/30 px-4 py-3 text-sm text-tk-ink">
                {{ session('status') }}
            </div>
        </div>
    @endif
    <main class="{{ $pageWrap }} py-8 w-full">
        {{ $slot ?? '' }}
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.theme-init')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Tilkit'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
@php
    $isTilkitPage = (bool) ($isTilkitPage ?? request()->routeIs('tools.tilkit.*'));
    $pageWrap = $isTilkitPage || ($fullWidth ?? false)
        ? 'w-full max-w-none px-4 sm:px-6 lg:px-8 xl:px-10 2xl:px-12'
        : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8';
@endphp
<body @class(['min-h-screen font-sans antialiased', 'tk-layout-tilkit' => $isTilkitPage])>
@if ($isTilkitPage)
    <div
        class="min-h-screen w-full"
        x-data="{
            pinned: false,
            hover: false,
            get open() { return this.pinned || this.hover; },
        }"
    >
        @include('layouts.tilkit-sidebar')
        <div
            class="tk-tilkit-main min-h-screen flex flex-col transition-[margin] duration-300 ease-out ml-16"
            :class="open ? 'ml-60' : 'ml-16'"
        >
@else
    @include('layouts.navigation')
@endif
    @if (isset($header))
        <header class="border-b border-tk-line/80 bg-tk-surface/90 backdrop-blur-sm">
            <div class="{{ $pageWrap }} py-7">
                {{ $header }}
            </div>
        </header>
    @endif
    <x-flash-status-toast />
    <x-flash-errors :wrapper-class="$pageWrap.' pt-5'" />
    <main class="{{ $pageWrap }} {{ $isTilkitPage ? 'py-6 lg:py-8' : 'py-8 lg:py-10' }} w-full tk-page-content {{ $isTilkitPage ? 'tk-page-tilkit' : '' }}">
        {{ $slot ?? '' }}
        @yield('content')
    </main>
@if ($isTilkitPage)
        </div>
    </div>
@endif
    @stack('scripts')
</body>
</html>

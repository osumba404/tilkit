<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Tilkit') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 py-10 sm:py-0 bg-tk-bg">
            <a href="/" class="flex items-center gap-3 mb-8 group">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-tk-accent text-white text-base font-bold shadow-tk-sm">T</span>
                <span class="tk-nav-brand text-2xl">Tilkit</span>
            </a>

            <div class="w-full sm:max-w-md tk-card px-8 py-8 shadow-tk-md">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>

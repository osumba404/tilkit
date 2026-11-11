<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.theme-init')
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $pageTitle ?? config('app.name', 'Tilkit') }}</title>

        @if (filled($metaDescription ?? null))
            @include('partials.seo-head', [
                'seoTitle' => $pageTitle ?? config('app.name', 'Tilkit'),
                'seoDescription' => $metaDescription,
                'seoOgDescription' => $metaDescription,
                'seoKeywords' => null,
                'seoCanonical' => $canonical ?? url()->current(),
                'seoRobots' => $robots ?? 'noindex, nofollow',
                'seoOgType' => 'website',
                'seoOgImage' => null,
                'seoJsonLd' => null,
            ])
        @else
            <meta name="robots" content="{{ $robots ?? 'noindex, nofollow' }}">
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="font-sans antialiased" data-tk-validation-errors="{{ $errors->any() ? '1' : '0' }}">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:top-3 focus:left-3 focus:px-4 focus:py-2 focus:rounded-lg focus:bg-tk-accent focus:text-white focus:shadow-tk-md">
            Skip to main content
        </a>
        <div class="relative min-h-screen flex flex-col sm:justify-center items-center px-4 py-10 sm:py-0 bg-tk-bg">
            <header class="mb-8" role="banner">
                <a href="{{ route('home') }}" class="flex items-center gap-3 group" aria-label="{{ config('app.name', 'Tilkit') }} home">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-tk-accent text-white text-base font-bold shadow-tk-sm" aria-hidden="true">T</span>
                    <span class="tk-nav-brand text-2xl">Tilkit</span>
                </a>
            </header>

            <div class="absolute top-4 right-4 sm:top-6 sm:right-6">
                <x-theme-toggle />
            </div>

            <main id="main-content" role="main" class="w-full sm:max-w-md tk-card px-8 py-8 shadow-tk-md">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>

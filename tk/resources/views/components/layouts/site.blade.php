@props([
    'title' => config('app.name', 'Tilkit'),
    'metaDescription' => null,
    'ogDescription' => null,
    'metaKeywords' => null,
    'canonical' => null,
    'robots' => 'index, follow',
    'ogType' => 'website',
    'ogImage' => null,
    'jsonLd' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.theme-init')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    @if ($metaDescription)
        @include('partials.seo-head', [
            'seoTitle' => $title,
            'seoDescription' => $metaDescription,
            'seoOgDescription' => $ogDescription ?? $metaDescription,
            'seoKeywords' => $metaKeywords,
            'seoCanonical' => $canonical ?? url()->current(),
            'seoRobots' => $robots,
            'seoOgType' => $ogType,
            'seoOgImage' => $ogImage,
            'seoJsonLd' => $jsonLd,
        ])
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen flex flex-col bg-tk-bg text-tk-ink font-sans antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:top-3 focus:left-3 focus:px-4 focus:py-2 focus:rounded-lg focus:bg-tk-accent focus:text-white focus:shadow-tk-md">
        Skip to main content
    </a>
    @include('layouts.navigation')
    <main id="main-content" class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10">
        {{ $slot }}
    </main>
    @include('partials.site-footer')
    @stack('scripts')
</body>
</html>
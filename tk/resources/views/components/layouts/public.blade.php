@props([
    'title' => config('app.name', 'Tilkit'),
    'metaDescription' => null,
    'ogDescription' => null,
    'canonical' => null,
    'robots' => 'noindex, nofollow',
    'ogType' => 'website',
    'ogImage' => null,
    'jsonLd' => null,
    'vite' => ['resources/css/app.css'],
    'narrow' => true,
    'centered' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.theme-init')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    @if (filled($metaDescription))
        @include('partials.seo-head', [
            'seoTitle' => $title,
            'seoDescription' => $metaDescription,
            'seoOgDescription' => $ogDescription ?? $metaDescription,
            'seoKeywords' => null,
            'seoCanonical' => $canonical ?? url()->current(),
            'seoRobots' => $robots,
            'seoOgType' => $ogType,
            'seoOgImage' => $ogImage,
            'seoJsonLd' => $jsonLd,
        ])
    @else
        <meta name="robots" content="{{ $robots }}">
        @if ($canonical)
            <link rel="canonical" href="{{ $canonical }}">
        @endif
    @endif
    @vite($vite)
    @stack('head')
</head>
<body
    @class([
        'min-h-screen bg-tk-bg text-tk-ink font-sans antialiased flex flex-col',
        'items-center justify-center px-4 py-10' => $centered,
    ])
    data-tk-validation-errors="{{ $errors->any() ? '1' : '0' }}"
>
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:top-3 focus:left-3 focus:px-4 focus:py-2 focus:rounded-lg focus:bg-tk-accent focus:text-white focus:shadow-tk-md">
        Skip to main content
    </a>

    @isset($branding)
        <header class="w-full" role="banner">
            {{ $branding }}
        </header>
    @endisset

    <main
        id="main-content"
        role="main"
        @class([
            'w-full flex-1',
            'max-w-md' => $centered,
            'mx-auto px-4 py-8 sm:py-10' => ! $centered,
            'max-w-xl' => ! $centered && $narrow,
            'max-w-3xl' => ! $centered && ! $narrow,
        ])
    >
        {{ $slot }}
    </main>

    @isset($footer)
        {{ $footer }}
    @endisset
    @stack('scripts')
</body>
</html>

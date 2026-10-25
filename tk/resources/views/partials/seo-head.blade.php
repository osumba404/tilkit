@php
    $seoTitle = $seoTitle ?? config('app.name', 'Tilkit');
    $seoDescription = $seoDescription ?? '';
    $seoOgDescription = $seoOgDescription ?? $seoDescription;
    $seoKeywords = $seoKeywords ?? null;
    $seoCanonical = $seoCanonical ?? url()->current();
    $seoRobots = $seoRobots ?? 'index, follow';
    $seoOgType = $seoOgType ?? 'website';
    $seoOgImage = $seoOgImage ?? null;
    $seoJsonLd = $seoJsonLd ?? null;
@endphp

<meta name="description" content="{{ $seoDescription }}">
@if ($seoKeywords)
<meta name="keywords" content="{{ $seoKeywords }}">
@endif
<link rel="canonical" href="{{ $seoCanonical }}">
<meta name="robots" content="{{ $seoRobots }}">

<meta property="og:site_name" content="{{ config('app.name', 'Tilkit') }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoOgDescription }}">
<meta property="og:type" content="{{ $seoOgType }}">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
@if ($seoOgImage)
<meta property="og:image" content="{{ $seoOgImage }}">
@endif

<meta name="twitter:card" content="{{ $seoOgImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoOgDescription }}">
@if ($seoOgImage)
<meta name="twitter:image" content="{{ $seoOgImage }}">
@endif

@if ($seoJsonLd)
<script type="application/ld+json">{!! json_encode($seoJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif

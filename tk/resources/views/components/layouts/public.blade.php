@props([
    'title' => config('app.name', 'Tilkit'),
    'vite' => ['resources/css/app.css'],
    'narrow' => true,
    'centered' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    @vite($vite)
    @stack('head')
</head>
<body @class([
    'min-h-screen bg-tk-bg text-tk-ink antialiased',
    'flex items-center justify-center px-4 py-10' => $centered,
])>
    {{ $branding ?? '' }}
    <div @class([
        'w-full',
        'max-w-md' => $centered,
        'mx-auto px-4 py-8 sm:py-10' => ! $centered,
        'max-w-xl' => ! $centered && $narrow,
        'max-w-3xl' => ! $centered && ! $narrow,
    ])>
        {{ $slot }}
    </div>
    {{ $footer ?? '' }}
    @stack('scripts')
</body>
</html>

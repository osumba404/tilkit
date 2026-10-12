@guest
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Field reference — Tilkit</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-body bg-tk-bg min-h-screen">
@include('layouts.navigation')
<main class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-2">Data collection field reference</h1>
    <p class="text-sm text-tk-muted mb-4">200+ catalogued collection points — searchable master catalogue.</p>
    <div class="tk-card overflow-hidden" style="height: min(80vh, 900px);">
        <iframe src="{{ asset('field-guide/field-reference.html') }}" class="w-full h-full border-0" title="Field reference"></iframe>
    </div>
</main>
</body>
</html>
@else
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold">Data collection points — field reference</h2>
            <p class="text-sm text-tk-muted">Master catalogue from the Tilkit field guide (200+ points, 16 families).</p>
        </div>
    </x-slot>

    <div class="tk-card overflow-hidden" style="height: min(80vh, 900px);">
        <iframe
            src="{{ asset('field-guide/field-reference.html') }}"
            title="Data collection field reference"
            class="w-full h-full border-0"
        ></iframe>
    </div>
</x-app-layout>
@endguest

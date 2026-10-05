<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tilkit — institutional data collection</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans bg-tk-bg text-tk-ink min-h-screen">
@include('layouts.navigation')
<section class="max-w-5xl mx-auto px-4 py-16 sm:py-24">
    <h1 class="font-serif font-bold text-4xl sm:text-5xl leading-tight max-w-2xl">Build collection tools. Analyse field data. Govern with purpose.</h1>
    <p class="mt-6 text-lg text-tk-muted max-w-2xl">Tilkit maps the full catalogue of data collection points—from XLSForm question types to sensors and channels—into a single platform. Create a tool, become its admin, and publish forms aligned with Kenya DPA governance.</p>
    <div class="mt-10 flex flex-wrap gap-4">
        @auth
            <a href="{{ route('tools.create') }}" class="tk-btn-primary">Create a tool</a>
            <a href="{{ route('dashboard') }}" class="tk-btn-secondary">Open dashboard</a>
        @else
            <a href="{{ route('register') }}" class="tk-btn-primary">Get started free</a>
            <a href="{{ route('reference') }}" class="tk-btn-secondary">Browse field reference</a>
        @endauth
    </div>
    <dl class="mt-16 grid sm:grid-cols-3 gap-6">
        <div class="tk-card p-6">
            <dt class="font-semibold">200+ collection points</dt>
            <dd class="mt-2 text-sm text-tk-muted">Sixteen families from the field reference guide—text, geo, media, clinical, aggregate, and more.</dd>
        </div>
        <div class="tk-card p-6">
            <dt class="font-semibold">Tool-scoped admin</dt>
            <dd class="mt-2 text-sm text-tk-muted">Every account can spin up a tool; the creator becomes administrator for forms, members, and exports.</dd>
        </div>
        <div class="tk-card p-6">
            <dt class="font-semibold">Analysis dashboard</dt>
            <dd class="mt-2 text-sm text-tk-muted">Trends, breakdowns, CSV export, and typed answers for query-ready reporting.</dd>
        </div>
    </dl>
</section>
</body>
</html>

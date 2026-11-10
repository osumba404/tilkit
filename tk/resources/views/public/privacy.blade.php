<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy notice - {{ $tool->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-tk-soft text-tk-ink antialiased">
    <main class="max-w-3xl mx-auto px-4 py-10">
        <h1 class="text-2xl font-semibold mb-2">Privacy notice</h1>
        <p class="text-sm text-tk-muted mb-6">{{ $tool->name }}</p>

        @if (session('status'))
            <div class="tk-callout mb-6 text-sm">{{ session('status') }}</div>
        @endif

        <div class="tk-card p-6 mb-8 prose prose-sm max-w-none text-tk-ink whitespace-pre-wrap">{{ $notice }}</div>

        <div class="tk-card p-6 mb-8">
            <h2 class="font-semibold text-lg mb-3">Personal data we collect in the indicator workbook</h2>
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase text-tk-muted">
                    <tr><th class="pb-2">Column</th><th class="pb-2">Category</th><th class="pb-2">Description</th></tr>
                </thead>
                <tbody>
                    @foreach ($inventory as $letter => $meta)
                        <tr class="border-t border-tk-line">
                            <td class="py-2 font-mono">{{ $letter }}</td>
                            <td class="py-2">{{ $meta['category'] ?? '' }}</td>
                            <td class="py-2">{{ $meta['description'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('public.tools.data-subject-request', $tool) }}" class="tk-btn-primary text-sm">Exercise your rights</a>
            @if ($tool->privacy_policy_url)
                <a href="{{ $tool->privacy_policy_url }}" class="tk-btn-secondary text-sm" target="_blank" rel="noopener">Full privacy policy</a>
            @endif
        </div>
    </main>
</body>
</html>

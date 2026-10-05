<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thank you — Tilkit</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-body bg-tk-bg min-h-screen flex items-center justify-center px-4">
    <div class="tk-card max-w-md p-8 text-center">
        <h1 class="text-2xl font-bold">Thank you</h1>
        <p class="text-tk-muted mt-3">Your response to <strong>{{ $form->title }}</strong> was recorded securely.</p>
        <div class="flex flex-col gap-2 mt-6">
            @if ($form->project?->public_collection_enabled)
                <a href="{{ route('public.tools.collect', $form->project) }}" class="tk-btn-primary inline-block">More forms in this tool</a>
            @endif
            <a href="{{ route('home') }}" class="tk-btn-secondary inline-block">Back to Tilkit</a>
        </div>
    </div>
</body>
</html>

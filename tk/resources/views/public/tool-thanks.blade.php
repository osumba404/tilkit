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
        <p class="text-tk-muted mt-3">Your responses for <strong>{{ $tool->name }}</strong> were recorded securely.</p>
        <a href="{{ route('public.tools.collect', $tool) }}" class="tk-btn-primary inline-block mt-6">Submit another</a>
    </div>
</body>
</html>

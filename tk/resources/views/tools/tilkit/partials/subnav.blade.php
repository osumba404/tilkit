@php($active = $tilkitNav ?? 'overview')
<nav class="flex flex-wrap gap-2 mb-6 text-sm border-b border-tk-line pb-4" aria-label="Weather log sections">
    <a href="{{ route('tools.tilkit.overview', $tool) }}"
       class="px-3 py-1.5 rounded-lg {{ $active === 'overview' ? 'bg-tk-accent text-white' : 'text-tk-muted hover:bg-tk-soft' }}">Home</a>
    <a href="{{ route('tools.tilkit.records.create', $tool) }}"
       class="px-3 py-1.5 rounded-lg {{ $active === 'capture' ? 'bg-tk-accent text-white' : 'text-tk-muted hover:bg-tk-soft' }}">Capture</a>
    <a href="{{ route('tools.tilkit.records.index', $tool) }}"
       class="px-3 py-1.5 rounded-lg {{ $active === 'records' ? 'bg-tk-accent text-white' : 'text-tk-muted hover:bg-tk-soft' }}">Records</a>
    <a href="{{ route('tools.tilkit.insights', $tool) }}"
       class="px-3 py-1.5 rounded-lg {{ $active === 'insights' ? 'bg-tk-accent text-white' : 'text-tk-muted hover:bg-tk-soft' }}">Analysis</a>
    <a href="{{ route('tools.tilkit.tally', $tool) }}"
       class="px-3 py-1.5 rounded-lg {{ $active === 'tally' ? 'bg-tk-accent text-white' : 'text-tk-muted hover:bg-tk-soft' }}">Tally</a>
    <a href="{{ route('tools.tilkit.data-desk', $tool) }}"
       class="px-3 py-1.5 rounded-lg {{ $active === 'desk' ? 'bg-tk-accent text-white' : 'text-tk-muted hover:bg-tk-soft' }}">Data desk</a>
    <a href="{{ route('tools.tilkit.setup', $tool) }}"
       class="px-3 py-1.5 rounded-lg {{ $active === 'setup' ? 'bg-tk-accent text-white' : 'text-tk-muted hover:bg-tk-soft' }}">Setup</a>
    @if ($capabilities->canManageTool() ?? false)
        <a href="{{ route('tools.tilkit.team', $tool) }}"
           class="px-3 py-1.5 rounded-lg {{ $active === 'team' ? 'bg-tk-accent text-white' : 'text-tk-muted hover:bg-tk-soft' }}">Team</a>
    @endif
</nav>

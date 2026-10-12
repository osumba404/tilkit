@props(['label', 'value', 'delta' => null, 'href' => null, 'footnote' => null])
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif class="tk-card p-5 block hover:border-tk-accent transition-colors">
    <p class="text-xs text-tk-muted uppercase tracking-wide">{{ $label }}</p>
    <p class="text-2xl font-semibold mt-1">{{ $value }}</p>
    @if ($delta !== null && $delta['change_pct'] !== null)
        <p class="text-sm mt-1 {{ $delta['change_pct'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
            {{ $delta['change_pct'] >= 0 ? '↑' : '↓' }} {{ abs($delta['change_pct']) }}% vs prior period
        </p>
    @endif
    @if ($footnote)
        <p class="text-xs text-tk-muted mt-2">{{ $footnote }}</p>
    @endif
</{{ $tag }}>

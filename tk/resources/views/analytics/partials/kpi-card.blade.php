@props(['label', 'value', 'delta' => null, 'href' => null, 'footnote' => null, 'valueId' => null])
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif class="tk-kpi block">
    <p class="tk-kpi-label">{{ $label }}</p>
    <p class="text-2xl font-semibold mt-2 font-display text-tk-ink" @if($valueId) id="{{ $valueId }}" @endif>{{ $value }}</p>
    @if ($delta !== null && $delta['change_pct'] !== null)
        <p class="text-sm mt-2 font-medium {{ $delta['change_pct'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
            {{ $delta['change_pct'] >= 0 ? '↑' : '↓' }} {{ abs($delta['change_pct']) }}% vs prior period
        </p>
    @endif
    @if ($footnote)
        <p class="text-xs text-tk-muted mt-2">{{ $footnote }}</p>
    @endif
</{{ $tag }}>

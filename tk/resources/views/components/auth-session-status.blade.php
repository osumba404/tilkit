@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-tk-accent']) }}>
        {{ $status }}
    </div>
@endif

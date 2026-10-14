@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-tk-ink']) }}>
    {{ $value ?? $slot }}
</label>

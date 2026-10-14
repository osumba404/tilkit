<button {{ $attributes->merge(['type' => 'submit', 'class' => 'tk-btn-primary']) }}>
    {{ $slot }}
</button>

<button {{ $attributes->merge(['type' => 'button', 'class' => 'tk-btn-secondary disabled:opacity-50']) }}>
    {{ $slot }}
</button>

@if (session('status'))
    <div
        x-data="{ show: true }"
        x-init="window.setTimeout(() => { show = false }, 4500)"
        x-show="show"
        x-cloak
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="tk-flash-toast"
        role="status"
        aria-live="polite"
    >
        <p class="tk-flash-toast-message">{{ session('status') }}</p>
        <button type="button" class="tk-flash-toast-dismiss" @click="show = false" aria-label="Dismiss">×</button>
    </div>
@endif

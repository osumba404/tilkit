@if ($tool->brand_logo_path ?? null)
    <div class="border-b border-tk-line bg-tk-surface">
        <div class="max-w-xl mx-auto px-4 py-3">
            <img
                src="{{ asset('storage/'.$tool->brand_logo_path) }}"
                alt="{{ $tool->name }} logo"
                width="160"
                height="40"
                class="h-10 w-auto object-contain"
                loading="eager"
                decoding="async"
            >
        </div>
    </div>
@endif

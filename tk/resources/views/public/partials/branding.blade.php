@if ($tool->brand_logo_path ?? null)
    <div class="border-b border-tk-line bg-white">
        <div class="max-w-xl mx-auto px-4 py-3">
            <img src="{{ asset('storage/'.$tool->brand_logo_path) }}" alt="" class="h-10 w-auto object-contain">
        </div>
    </div>
@endif

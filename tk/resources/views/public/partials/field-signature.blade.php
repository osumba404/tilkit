@php
    $params = $field->parameters ?? [];
    $allowDraw = $params['allow_draw'] ?? true;
    $allowUpload = $params['allow_upload'] ?? true;
@endphp
<div
    class="space-y-3"
    data-signature-field
    data-field-name="{{ $name }}"
    data-required="{{ $field->is_required ? '1' : '0' }}"
>
    @if ($allowDraw)
        <div data-signature-draw-panel>
            <p class="text-xs text-tk-muted mb-2">Draw your signature below.</p>
            <div class="rounded-md border border-tk-line bg-white overflow-hidden touch-none">
                <canvas
                    data-signature-canvas
                    class="block w-full h-40 cursor-crosshair"
                    width="600"
                    height="160"
                    aria-label="Signature pad"
                ></canvas>
            </div>
            <div class="flex flex-wrap gap-2 mt-2">
                <button type="button" class="tk-btn-secondary text-xs py-1.5" data-signature-clear>Clear drawing</button>
            </div>
            <input type="hidden" name="{{ $name }}_drawn" value="{{ old($name.'_drawn') }}" data-signature-drawn-input>
        </div>
    @endif

    @if ($allowDraw && $allowUpload)
        <p class="text-xs text-tk-muted text-center">— or —</p>
    @endif

    @if ($allowUpload)
        <div data-signature-upload-panel>
            <label class="text-xs text-tk-muted block mb-1" for="field-{{ $name }}">Upload or scan a signature image</label>
            <input
                id="field-{{ $name }}"
                type="file"
                name="{{ $name }}"
                accept="image/*,.pdf"
                @if($params['allow_capture'] ?? true) capture="environment" @endif
                class="w-full text-sm file:mr-3 file:tk-btn-secondary file:border-0 file:text-sm"
                data-signature-file
            >
        </div>
    @endif
</div>

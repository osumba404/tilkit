@php
    $fieldId = $fieldId ?? 'setup-program-id';
    $selectedId = $selectedId ?? ($activeProgram->id ?? null);
@endphp
@if ($programs->count() > 1)
    <div>
        <label class="text-xs text-tk-muted block mb-1" for="{{ $fieldId }}">Program / initiative</label>
        <select id="{{ $fieldId }}" name="program_id" required class="tk-input">
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected((int) $selectedId === (int) $program->id)>{{ $program->name }}</option>
            @endforeach
        </select>
        <p class="text-xs text-tk-muted mt-1">Sites and seasons are stored under this program only.</p>
    </div>
@else
    <input type="hidden" name="program_id" value="{{ $activeProgram->id }}">
    <p class="text-xs text-tk-muted">Adding to program: <strong>{{ $activeProgram->name }}</strong></p>
@endif

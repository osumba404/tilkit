@if (($tilkitPrograms ?? collect())->count() > 0)
    <div class="tk-card p-4 mb-4 flex flex-wrap gap-3 items-center justify-between text-sm">
        <div>
            <span class="text-tk-muted">Active program:</span>
            <strong>{{ $activeProgram->name ?? '-' }}</strong>
        </div>
        <form method="POST" action="{{ route('tools.tilkit.program.switch', $tool) }}" class="flex flex-wrap gap-2 items-center">
            @csrf
            <label class="sr-only" for="tilkit-program-select">Switch program</label>
            <select id="tilkit-program-select" name="program_id" class="tk-input text-sm min-w-[14rem]" onchange="this.form.submit()">
                @foreach ($tilkitPrograms as $program)
                    <option value="{{ $program->id }}" @selected(($activeProgram->id ?? null) === $program->id)>{{ $program->name }}</option>
                @endforeach
            </select>
            <a href="{{ route('tools.tilkit.setup', $tool) }}" class="tk-btn-secondary text-xs">Manage programs</a>
        </form>
    </div>
@endif

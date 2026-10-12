<div class="flex flex-wrap justify-between gap-2">
    <div>
        <p class="text-xs text-tk-accent uppercase tracking-wide">Dataset analytics</p>
        <h2 class="text-2xl font-bold">{{ $dataset->name }}</h2>
        <p class="text-sm text-tk-muted font-mono">{{ $dataset->original_filename }} · {{ number_format($dataset->row_count) }} rows · {{ $dataset->column_count }} columns</p>
    </div>
    <a href="{{ route('platform.datasets.index') }}" class="tk-btn-secondary text-sm h-fit">All datasets</a>
</div>

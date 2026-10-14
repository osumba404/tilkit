<x-page-header
    :title="$dataset->name"
    eyebrow="Dataset analytics"
    :subtitle="$dataset->original_filename.' · '.number_format($dataset->row_count).' rows · '.$dataset->column_count.' columns'"
>
    <x-slot:actions>
        <a href="{{ route('platform.datasets.index') }}" class="tk-btn-secondary text-sm">All datasets</a>
    </x-slot:actions>
</x-page-header>

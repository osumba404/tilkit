@if ($chartPayloads === [])
    <div class="tk-card p-8 text-center text-tk-muted text-sm mb-6 w-full" id="dataset-panels-empty">
        No panels yet. Use the form below to run your first visualization.
    </div>
@else
    <div class="flex flex-col gap-6 mb-6 w-full" id="dataset-panels-list">
        @foreach ($chartPayloads as $loopIndex => $chart)
            @include('analytics.partials.dataset-panel-card', [
                'chart' => $chart,
                'loopIndex' => $loopIndex,
                'dataset' => $dataset,
                'filters' => $filters,
            ])
        @endforeach
    </div>
@endif

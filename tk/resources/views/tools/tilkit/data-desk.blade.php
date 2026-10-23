<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Data desk" :subtitle="$tool->name.' · validation & quality queues'">
            <x-slot:actions>
                <a href="{{ route('tools.tilkit.overview', $tool) }}" class="tk-btn-secondary">TILKIT home</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @if (session('status'))
        <div class="tk-card p-4 mb-4 text-sm">{{ session('status') }}</div>
    @endif

    @if ($weightReviews->isNotEmpty())
        <div class="tk-card p-5 mb-6 border-amber-200">
            <h3 class="font-semibold mb-3">Climate weight reviews (CP STRONG)</h3>
            <ul class="space-y-3 text-sm">
                @foreach ($weightReviews as $review)
                    <li class="flex flex-wrap justify-between gap-2 items-center">
                        <span>{{ $review->record->indicator?->indicator_uid }} · {{ $review->record->site?->name }}</span>
                        <form method="POST" action="{{ route('tools.tilkit.weight-reviews.sign-off', [$tool, $review]) }}">
                            @csrf
                            <button type="submit" class="tk-btn-secondary text-xs">Sign off review</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach (['pending_validation' => 'Pending validation', 'pending_verify' => 'Pending verify', 'contested' => 'Contested', 'climate_review' => 'Climate STRONG', 'blank_relevance' => 'Blank relevance'] as $key => $label)
            <div class="tk-stat">
                <p class="tk-stat-label">{{ $label }}</p>
                <p class="tk-stat-value">{{ ($byQueue[$key] ?? collect())->count() }}</p>
            </div>
        @endforeach
    </div>

    <div class="tk-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-tk-soft text-xs uppercase text-tk-muted">
                <tr>
                    <th class="p-3 text-left">Indicator</th>
                    <th class="p-3 text-left">Site / season</th>
                    <th class="p-3 text-left">Queue</th>
                    <th class="p-3 text-left">CE</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($records as $row)
                    @php $rec = $row['record']; @endphp
                    <tr class="border-t border-tk-line">
                        <td class="p-3 font-mono text-xs">{{ $rec->indicator?->indicator_uid }}</td>
                        <td class="p-3">{{ $rec->site?->name }} · {{ $rec->season?->label }}</td>
                        <td class="p-3 text-xs">{{ str_replace('_', ' ', $row['queue']) }}</td>
                        <td class="p-3">{{ $row['tally_status'] }}</td>
                        <td class="p-3 text-right space-x-2">
                            <a href="{{ route('tools.tilkit.records.wizard', [$tool, $rec, 'A']) }}" class="text-tk-accent">Wizard</a>
                            <a href="{{ route('tools.tilkit.records.edit', [$tool, $rec]) }}" class="text-tk-accent">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>

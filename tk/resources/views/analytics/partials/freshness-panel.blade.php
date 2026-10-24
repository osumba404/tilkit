@props(['sources'])
<div class="tk-card p-5">
    <h3 class="font-semibold mb-3 text-sm">Data source health</h3>
    <ul class="space-y-3 text-sm">
        @foreach ($sources as $key => $src)
            <li class="flex justify-between gap-2 border-b border-tk-line pb-2 last:border-0">
                <span>{{ $src['label'] }}</span>
                <span class="text-right">
                    <span class="font-medium capitalize {{ ($src['status'] ?? '') === 'connected' || ($src['status'] ?? '') === 'active' || ($src['status'] ?? '') === 'enabled' ? 'text-emerald-700' : 'text-tk-muted' }}">{{ $src['status'] ?? '-' }}</span>
                    @if (! empty($src['updated_at']))
                        <span class="block text-xs text-tk-muted">{{ \Illuminate\Support\Carbon::parse($src['updated_at'])->diffForHumans() }}</span>
                    @endif
                </span>
            </li>
        @endforeach
    </ul>
</div>

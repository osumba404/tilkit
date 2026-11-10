@php
    /** @var \App\Models\Tilkit\TilkitIndicatorRecord $rec */
@endphp
@if ($rec->anonymized_at)
    <span class="text-xs text-tk-muted" title="Anonymized under Kenya DPA retention or erasure">Anonymized</span>
@elseif ($rec->processing_restricted)
    <span class="text-xs text-amber-800" title="Processing restricted (consent withdrawn or objection)">Restricted</span>
@elseif ($rec->consent_to_share === false)
    <span class="text-xs text-amber-800">No share</span>
@elseif ($rec->do_no_harm)
    <span class="text-xs text-amber-800">Do-no-harm</span>
@endif

<div id="tilkit-records-map" class="tk-card p-5 mb-6">
    <h3 class="tk-section-title text-base mb-3">Records on map</h3>
    <x-tilkit.gps-map-view :points="$mapPoints" height="h-72" />
</div>

<div id="tilkit-records-table">
    @include('tools.tilkit.partials.records-table')
</div>

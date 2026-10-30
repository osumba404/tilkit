<div
    class="md:col-span-2 lg:col-span-3 space-y-3 border border-tk-line rounded-lg p-4 bg-tk-soft/40"
    data-tk-record-indicator-cascade
    data-options-url="{{ $indicatorOptionsUrl }}"
>
    <p class="text-xs text-tk-muted">
        Choose category → type → sub-type → registered sign → behaviour. Options are limited to codes assigned to this site for the collection month and season.
    </p>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3">
        <div>
            <x-tilkit.workbook-field-label letter="A" />
            <select class="tk-input" data-tk-ind-cat required disabled>
                <option value="">Select site, season, and date first</option>
            </select>
        </div>
        <div>
            <x-tilkit.workbook-field-label letter="B" title="Indicator type" />
            <select class="tk-input" data-tk-ind-type required disabled>
                <option value="">—</option>
            </select>
        </div>
        <div>
            <x-tilkit.workbook-field-label letter="B" title="Sub-type (legend code)" />
            <select class="tk-input" data-tk-ind-subtype required disabled>
                <option value="">—</option>
            </select>
        </div>
        <div>
            <x-tilkit.workbook-field-label letter="C" />
            <select class="tk-input" data-tk-ind-specific required disabled>
                <option value="">—</option>
            </select>
        </div>
        <div>
            <x-tilkit.workbook-field-label letter="D" />
            <select class="tk-input" data-tk-ind-behaviour required disabled>
                <option value="">—</option>
            </select>
        </div>
        <div>
            <x-tilkit.workbook-field-label letter="AH" />
            <input type="text" class="tk-input bg-tk-soft" data-tk-ind-uid-display readonly placeholder="—">
        </div>
    </div>

    <input type="hidden" name="indicator_id" value="{{ old('indicator_id') }}" data-tk-ind-id-input required>
    <input type="hidden" name="workbook[A]" value="{{ old('workbook.A') }}" data-tk-wb-a>
    <input type="hidden" name="workbook[B]" value="{{ old('workbook.B') }}" data-tk-wb-b>
    <input type="hidden" name="workbook[C]" value="{{ old('workbook.C') }}" data-tk-wb-c>
    <input type="hidden" name="workbook[D]" value="{{ old('workbook.D') }}" data-tk-wb-d>

    <p class="text-xs text-amber-800 hidden" data-tk-ind-message></p>
</div>

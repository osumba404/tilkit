@props(['col', 'current' => ''])

@php
    $letter = $col['column'];
    $kind = \App\Support\Tilkit\WorkbookColumnRegistry::inputKind($col);
    $gps = $kind === 'gps' ? \App\Support\Tilkit\WorkbookCapturePreparer::splitGps($current) : null;
    $consent = $kind === 'consent' ? \App\Support\Tilkit\WorkbookCapturePreparer::splitConsent($current) : null;
@endphp

<div {{ $attributes->merge(['class' => 'tk-field']) }}>
    <label class="text-xs text-tk-muted block mb-1">
        <span class="font-mono text-tk-accent">{{ $letter }}</span>
        {{ $col['field'] }}
    </label>

    @switch($kind)
        @case('yes_no')
            <select name="workbook[{{ $letter }}]" class="tk-input">
                <option value="">-</option>
                <option value="1" @selected((string) $current === '1')>Yes (1)</option>
                <option value="0" @selected((string) $current === '0')>No (0)</option>
            </select>
            @break

        @case('rating')
            <select name="workbook[{{ $letter }}]" class="tk-input">
                <option value="">-</option>
                @foreach ([3 => '3 - High / good', 2 => '2 - Medium / fair', 1 => '1 - Low / poor', 0 => '0 - Not sure'] as $v => $label)
                    <option value="{{ $v }}" @selected((string) $current === (string) $v)>{{ $label }}</option>
                @endforeach
            </select>
            @break

        @case('rating_321')
            <select name="workbook[{{ $letter }}]" class="tk-input">
                <option value="">-</option>
                @foreach ([3 => '3', 2 => '2', 1 => '1'] as $v => $label)
                    <option value="{{ $v }}" @selected((string) $current === (string) $v)>{{ $label }}</option>
                @endforeach
            </select>
            @break

        @case('tercile')
            <select name="workbook[{{ $letter }}]" class="tk-input">
                <option value="">-</option>
                @foreach (\App\Enums\Tilkit\TercileForecast::cases() as $t)
                    @if ($t->isForecast())
                        <option value="{{ $t->value }}" @selected((string) $current === (string) $t->value)>{{ $t->label() }}</option>
                    @endif
                @endforeach
            </select>
            @break

        @case('validation')
            <select name="workbook[{{ $letter }}]" class="tk-input">
                <option value="">-</option>
                @foreach (\App\Enums\Tilkit\ValidationStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected((string) $current === $status->value || (string) $current === $status->excelLabel())>{{ $status->excelLabel() }}</option>
                @endforeach
            </select>
            @break

        @case('outcome')
            <select name="workbook[{{ $letter }}]" class="tk-input">
                <option value="">-</option>
                @foreach (\App\Enums\Tilkit\SeasonOutcome::cases() as $outcome)
                    <option value="{{ $outcome->value }}" @selected((string) $current === $outcome->value)>{{ ucfirst(str_replace('_', ' ', $outcome->value)) }}</option>
                @endforeach
            </select>
            @break

        @case('history_scale')
            <select name="workbook[{{ $letter }}]" class="tk-input">
                <option value="">-</option>
                @foreach ([3 => '3 - Ancient / well known', 2 => '2 - Known', 1 => '1 - New sign'] as $v => $label)
                    <option value="{{ $v }}" @selected((string) $current === (string) $v)>{{ $label }}</option>
                @endforeach
            </select>
            @break

        @case('lead_time_class')
            <select name="workbook[{{ $letter }}]" class="tk-input">
                <option value="">-</option>
                @foreach ([3 => '3 - Months ahead', 2 => '2 - Weeks ahead', 1 => '1 - Days ahead'] as $v => $label)
                    <option value="{{ $v }}" @selected((string) $current === (string) $v)>{{ $label }}</option>
                @endforeach
            </select>
            @break

        @case('consent')
            <div class="grid sm:grid-cols-3 gap-2">
                <select name="workbook[BC_level]" class="tk-input" aria-label="Consent sharing level">
                    <option value="">Level -</option>
                    @foreach ([1, 2, 3, 4] as $lvl)
                        <option value="{{ $lvl }}" @selected($consent['level'] === (string) $lvl)>Level {{ $lvl }}</option>
                    @endforeach
                </select>
                <select name="workbook[BC_share]" class="tk-input" aria-label="Consent to share">
                    <option value="">Share -</option>
                    <option value="1" @selected($consent['share'] === '1')>Yes (1)</option>
                    <option value="0" @selected($consent['share'] === '0')>No (0)</option>
                </select>
                <input type="text" name="workbook[BC_credit]" value="{{ $consent['credit'] }}" class="tk-input" placeholder="Credit / attribution">
            </div>
            @break

        @case('date')
            <input type="date" name="workbook[{{ $letter }}]" value="{{ is_scalar($current) ? $current : '' }}" class="tk-input">
            @break

        @case('integer')
            <input type="number" step="1" min="0" name="workbook[{{ $letter }}]" value="{{ is_scalar($current) ? $current : '' }}" class="tk-input">
            @break

        @case('number')
            <input type="number" step="any" name="workbook[{{ $letter }}]" value="{{ is_scalar($current) ? $current : '' }}" class="tk-input">
            @break

        @case('gps')
            <div class="grid grid-cols-2 gap-2">
                <input type="number" step="any" name="workbook[Z_lat]" value="{{ $gps['lat'] }}" class="tk-input" placeholder="Latitude" aria-label="GPS latitude">
                <input type="number" step="any" name="workbook[Z_lng]" value="{{ $gps['lng'] }}" class="tk-input" placeholder="Longitude" aria-label="GPS longitude">
            </div>
            @break

        @case('json')
            <textarea name="workbook[{{ $letter }}]" rows="3" class="tk-input font-mono text-xs" placeholder='{"crops":"+"}'>{{ is_array($current) ? json_encode($current) : (is_scalar($current) ? $current : '') }}</textarea>
            @break

        @case('textarea')
            <textarea name="workbook[{{ $letter }}]" rows="3" class="tk-input">{{ is_scalar($current) ? $current : '' }}</textarea>
            @break

        @case('readonly')
            <p class="text-sm py-2 px-3 bg-tk-soft rounded-md border border-tk-line">{{ is_scalar($current) && $current !== '' ? $current : '- (filled from site, season, or indicator)' }}</p>
            @break

        @default
            <input type="text" name="workbook[{{ $letter }}]" value="{{ is_scalar($current) ? $current : '' }}" class="tk-input">
    @endswitch
</div>

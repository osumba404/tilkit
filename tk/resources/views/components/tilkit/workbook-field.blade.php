@props(['col', 'current' => '', 'centerLat' => null, 'centerLng' => null])

@php
    $letter = $col['column'];
    $kind = \App\Support\Tilkit\WorkbookColumnRegistry::inputKind($col);
    $gps = $kind === 'gps' ? \App\Support\Tilkit\WorkbookCapturePreparer::splitGps($current) : null;
    $consent = $kind === 'consent' ? \App\Support\Tilkit\WorkbookCapturePreparer::splitConsent($current) : null;
@endphp

@php
    $listItems = $kind === 'list_text'
        ? \App\Support\Tilkit\WorkbookCapturePreparer::splitListText($current)
        : [];
    $fieldSpan = match ($kind) {
        'gps', 'consent', 'textarea', 'json', 'list_text' => ' md:col-span-2',
        default => '',
    };
@endphp
<div {{ $attributes->merge(['class' => 'tk-field'.$fieldSpan]) }}>
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
            <div class="grid sm:grid-cols-3 gap-3">
                <div>
                    <span class="text-[0.65rem] uppercase tracking-tk-label text-tk-muted block mb-1">Sharing level</span>
                    <select name="workbook[BC_level]" class="tk-input" aria-label="Consent sharing level">
                    <option value="">Choose level</option>
                    @foreach ([1, 2, 3, 4] as $lvl)
                        <option value="{{ $lvl }}" @selected($consent['level'] === (string) $lvl)>Level {{ $lvl }}</option>
                    @endforeach
                </select>
                </div>
                <div>
                    <span class="text-[0.65rem] uppercase tracking-tk-label text-tk-muted block mb-1">May share?</span>
                    <select name="workbook[BC_share]" class="tk-input" aria-label="Consent to share">
                    <option value="">Choose</option>
                    <option value="1" @selected($consent['share'] === '1')>Yes (1)</option>
                    <option value="0" @selected($consent['share'] === '0')>No (0)</option>
                </select>
                </div>
                <div>
                    <span class="text-[0.65rem] uppercase tracking-tk-label text-tk-muted block mb-1">Credit / attribution</span>
                    <input type="text" name="workbook[BC_credit]" value="{{ $consent['credit'] }}" class="tk-input" placeholder="Community or individual">
                </div>
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
            <x-tilkit.gps-map
                lat-name="workbook[Z_lat]"
                lng-name="workbook[Z_lng]"
                :lat="$gps['lat']"
                :lng="$gps['lng']"
                :center-lat="$centerLat"
                :center-lng="$centerLng"
                lat-label="GPS latitude (Z)"
                lng-label="GPS longitude (Z)"
            />
            @break

        @case('json')
            <textarea name="workbook[{{ $letter }}]" rows="3" class="tk-input font-mono text-xs" placeholder='{"crops":"+"}'>{{ is_array($current) ? json_encode($current) : (is_scalar($current) ? $current : '') }}</textarea>
            @break

        @case('list_text')
            <div
                class="space-y-2"
                x-data="{
                    items: @js($listItems),
                    add() { this.items.push(''); },
                    remove(index) { if (this.items.length > 1) { this.items.splice(index, 1); } },
                }"
            >
                <template x-for="(item, index) in items" :key="index">
                    <div class="flex gap-2 items-start">
                        <textarea
                            x-model="items[index]"
                            :name="'workbook[{{ $letter }}][]'"
                            rows="2"
                            class="tk-input flex-1 min-w-0"
                            :placeholder="{{ Js::from($letter === 'X' ? 'e.g. Crops 3+' : 'One sector or impact (code or description)') }}"
                        ></textarea>
                        <button
                            type="button"
                            class="tk-btn-secondary text-xs px-2 py-2 shrink-0"
                            @click="remove(index)"
                            :disabled="items.length <= 1"
                            :aria-label="{{ Js::from(__('Remove item')) }}"
                        >
                            {{ __('Remove') }}
                        </button>
                    </div>
                </template>
                <button type="button" class="tk-btn-secondary text-xs" @click="add()">
                    {{ __('Add item') }}
                </button>
            </div>
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

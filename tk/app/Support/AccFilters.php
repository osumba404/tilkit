<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AccFilters
{
    public function __construct(
        public int $periodDays,
        public string $compare,
        public ?int $formId,
        public ?string $status,
        public ?string $channel,
        public ?string $site,
        public ?string $season,
        public string $granularity,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $period = $request->string('period', '30')->toString();
        $periodDays = match ($period) {
            '7' => 7,
            '90' => 90,
            'ytd' => max(1, (int) Carbon::now()->startOfYear()->diffInDays(Carbon::today()) + 1),
            default => 30,
        };

        return new self(
            periodDays: $periodDays,
            compare: $request->string('compare', 'previous')->toString(),
            formId: $request->filled('form_id') ? $request->integer('form_id') : null,
            status: $request->filled('status') ? $request->string('status')->toString() : null,
            channel: $request->filled('channel') ? $request->string('channel')->toString() : null,
            site: $request->filled('site') ? $request->string('site')->toString() : null,
            season: $request->filled('season') ? $request->string('season')->toString() : null,
            granularity: $request->string('granularity', 'daily')->toString(),
        );
    }

    /** @return array<string, mixed> */
    public function toQueryArray(): array
    {
        $period = match ($this->periodDays) {
            7 => '7',
            90 => '90',
            default => $this->periodDays > 31 ? 'ytd' : '30',
        };

        return array_filter([
            'period' => $period,
            'compare' => $this->compare !== 'previous' ? $this->compare : null,
            'form_id' => $this->formId,
            'status' => $this->status,
            'channel' => $this->channel,
            'site' => $this->site,
            'season' => $this->season,
            'granularity' => $this->granularity !== 'daily' ? $this->granularity : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function periodStart(): Carbon
    {
        if ($this->periodDays > 300) {
            return Carbon::now()->startOfYear();
        }

        return Carbon::today()->subDays($this->periodDays - 1)->startOfDay();
    }

    public function periodEnd(): Carbon
    {
        return Carbon::now()->endOfDay();
    }

    public function previousPeriodStart(): Carbon
    {
        $days = $this->periodDays;

        return $this->periodStart()->copy()->subDays($days);
    }

    public function previousPeriodEnd(): Carbon
    {
        return $this->periodStart()->copy()->subSecond();
    }

    public function periodLabel(): string
    {
        return match (true) {
            $this->periodDays === 7 => 'Last 7 days',
            $this->periodDays === 90 => 'Last 90 days',
            $this->periodDays > 31 => 'Year to date',
            default => 'Last 30 days',
        };
    }
}

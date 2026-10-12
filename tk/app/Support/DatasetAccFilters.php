<?php

namespace App\Support;

use App\Models\PlatformDataset;
use Illuminate\Http\Request;

class DatasetAccFilters
{
    public function __construct(
        public ?string $filterColumn = null,
        public ?string $filterValue = null,
        public ?string $dimensionA = null,
        public ?string $dimensionB = null,
    ) {}

    public static function fromRequest(Request $request, PlatformDataset $dataset): self
    {
        $columns = $dataset->columns->pluck('name')->all();
        $pick = static fn (?string $name) => $name && in_array($name, $columns, true) ? $name : null;

        $dimensionA = $pick($request->string('dimension_a')->toString() ?: null);
        $dimensionB = $pick($request->string('dimension_b')->toString() ?: null);

        if ($dimensionA === null) {
            $dimensionA = $dataset->columns->firstWhere('inferred_type', 'category')?->name
                ?? $dataset->columns->first()?->name;
        }
        if ($dimensionB === null) {
            $dimensionB = $dataset->columns
                ->where('name', '!=', $dimensionA)
                ->firstWhere('inferred_type', 'category')?->name
                ?? $dataset->columns->where('name', '!=', $dimensionA)->first()?->name;
        }

        return new self(
            filterColumn: $pick($request->string('filter_column')->toString() ?: null),
            filterValue: $request->filled('filter_value') ? (string) $request->input('filter_value') : null,
            dimensionA: $dimensionA,
            dimensionB: $dimensionB,
        );
    }

    /** @return array<string, string> */
    public function toQueryArray(): array
    {
        return array_filter([
            'filter_column' => $this->filterColumn,
            'filter_value' => $this->filterValue,
            'dimension_a' => $this->dimensionA,
            'dimension_b' => $this->dimensionB,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function hasActiveFilter(): bool
    {
        return $this->filterColumn !== null && $this->filterValue !== null;
    }
}

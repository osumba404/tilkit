<?php

namespace App\Http\Controllers\Concerns;

use App\Models\DcProject;
use App\Support\AccFilters;
use Illuminate\Http\Request;

trait BuildsAccToolContext
{
    /** @return array{filters: AccFilters, forms: \Illuminate\Support\Collection<int, \App\Models\DcForm>} */
    protected function accToolContext(Request $request, DcProject $tool): array
    {
        $filters = AccFilters::fromRequest($request);
        $forms = $tool->forms()->where('status', 'published')->orderBy('title')->get(['id', 'title', 'code']);

        return compact('filters', 'forms');
    }
}

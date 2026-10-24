<?php

namespace App\Services\Tilkit;

use App\Models\DcProject;
use App\Models\Tilkit\TilkitProgram;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TilkitProgramContext
{
    public function programsFor(DcProject $tool): Collection
    {
        return TilkitProgram::query()
            ->where('project_id', $tool->id)
            ->orderBy('name')
            ->get();
    }

    public function active(DcProject $tool): TilkitProgram
    {
        $programs = $this->programsFor($tool);
        if ($programs->isEmpty()) {
            return $this->ensureDefault($tool);
        }

        $sessionKey = $this->sessionKey($tool);
        $selectedId = session($sessionKey);
        if ($selectedId !== null) {
            $match = $programs->firstWhere('id', (int) $selectedId);
            if ($match !== null) {
                return $match;
            }
        }

        return $programs->first();
    }

    public function setActive(DcProject $tool, int $programId): void
    {
        abort_unless(
            TilkitProgram::query()->where('project_id', $tool->id)->where('id', $programId)->exists(),
            422,
        );
        session([$this->sessionKey($tool) => $programId]);
    }

    public function ensureDefault(DcProject $tool): TilkitProgram
    {
        $existing = TilkitProgram::query()->where('project_id', $tool->id)->orderBy('id')->first();
        if ($existing !== null) {
            return $existing;
        }

        return TilkitProgram::query()->create([
            'project_id' => $tool->id,
            'name' => $tool->name,
            'description' => 'Default program for this workspace.',
        ]);
    }

    /** @return Builder<TilkitSite> */
    public function sitesQuery(DcProject $tool): Builder
    {
        $program = $this->active($tool);

        return TilkitSite::query()
            ->where('project_id', $tool->id)
            ->where('program_id', $program->id)
            ->orderBy('name');
    }

    /** @return Builder<TilkitSeason> */
    public function seasonsQuery(DcProject $tool): Builder
    {
        $program = $this->active($tool);

        return TilkitSeason::query()
            ->where('project_id', $tool->id)
            ->where('program_id', $program->id)
            ->orderBy('label');
    }

    private function sessionKey(DcProject $tool): string
    {
        return 'tilkit.active_program.'.$tool->id;
    }
}

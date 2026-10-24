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
    /** @var array<int, Collection<int, TilkitProgram>> */
    private array $programsCache = [];

    /** @var array<int, TilkitProgram> */
    private array $activeCache = [];

    public function programsFor(DcProject $tool): Collection
    {
        $key = (int) $tool->id;
        if (isset($this->programsCache[$key])) {
            return $this->programsCache[$key];
        }

        $this->programsCache[$key] = TilkitProgram::query()
            ->where('project_id', $tool->id)
            ->orderBy('name')
            ->get();

        return $this->programsCache[$key];
    }

    public function active(DcProject $tool): TilkitProgram
    {
        $key = (int) $tool->id;
        if (isset($this->activeCache[$key])) {
            return $this->activeCache[$key];
        }

        $programs = $this->programsFor($tool);
        if ($programs->isEmpty()) {
            return $this->activeCache[$key] = $this->ensureDefault($tool);
        }

        $sessionKey = $this->sessionKey($tool);
        $selectedId = session($sessionKey);
        if ($selectedId !== null) {
            $match = $programs->firstWhere('id', (int) $selectedId);
            if ($match !== null) {
                return $this->activeCache[$key] = $match;
            }
        }

        return $this->activeCache[$key] = $programs->first()
            ?? $this->ensureDefault($tool);
    }

    public function setActive(DcProject $tool, int $programId): void
    {
        abort_unless(
            TilkitProgram::query()->where('project_id', $tool->id)->where('id', $programId)->exists(),
            422,
        );
        session([$this->sessionKey($tool) => $programId]);
        unset($this->activeCache[(int) $tool->id]);
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

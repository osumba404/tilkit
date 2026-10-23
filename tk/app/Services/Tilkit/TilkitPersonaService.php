<?php

namespace App\Services\Tilkit;

use App\Enums\Tilkit\TilkitPersona;
use App\Enums\ToolMemberRole;
use App\Models\DcProject;
use App\Models\User;
use App\Support\Tilkit\WeatherTool;

class TilkitPersonaService
{
    public function personaFor(User $user, DcProject $tool): TilkitPersona
    {
        if ($user->isSuperAdmin()) {
            return TilkitPersona::Administrator;
        }

        $membership = $user->toolMembershipFor($tool);
        if ($membership?->tilkit_persona) {
            return TilkitPersona::tryFrom($membership->tilkit_persona) ?? $this->defaultPersona($membership->role);
        }

        return $this->defaultPersona($user->toolRoleFor($tool));
    }

    public function assert(User $user, DcProject $tool, callable $check): void
    {
        if (! WeatherTool::isWeatherModule($tool)) {
            return;
        }
        $persona = $this->personaFor($user, $tool);
        if (! $check($persona)) {
            abort(403, 'Your TILKIT role cannot perform this action.');
        }
    }

    private function defaultPersona(?ToolMemberRole $role): TilkitPersona
    {
        return match ($role) {
            ToolMemberRole::Admin => TilkitPersona::Administrator,
            ToolMemberRole::Viewer => TilkitPersona::Observer,
            default => TilkitPersona::WorkshopContributor,
        };
    }
}

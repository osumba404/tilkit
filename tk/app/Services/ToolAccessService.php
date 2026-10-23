<?php

namespace App\Services;

use App\Enums\ToolMemberRole;
use App\Models\DcProject;
use App\Support\Tilkit\WeatherTool;
use App\Support\ToolCapabilities;
use App\Models\DcSubmission;
use App\Models\ToolMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ToolAccessService
{
    public function createTool(User $owner, array $attributes): DcProject
    {
        if (WeatherTool::singleToolMode()) {
            abort(403, 'This deployment uses a single TILKIT weather tool; creating additional tools is disabled.');
        }

        return DB::transaction(function () use ($owner, $attributes): DcProject {
            $code = $attributes['code'] ?? Str::slug($attributes['name']);
            $code = Str::lower(Str::limit($code, 40, ''));

            $project = DcProject::create([
                'code' => $this->uniqueProjectCode($code),
                'name' => $attributes['name'],
                'module' => $attributes['module'] ?? 'data_collection',
                'purpose' => $attributes['purpose'],
                'lawful_basis' => $attributes['lawful_basis'] ?? 'consent',
                'retention_months' => $attributes['retention_months'] ?? null,
                'status' => 'active',
                'public_collection_enabled' => $attributes['public_collection_enabled'] ?? true,
                'owner_user_id' => $owner->id,
                'created_by' => $owner->id,
            ]);

            ToolMember::create([
                'project_id' => $project->id,
                'user_id' => $owner->id,
                'role' => ToolMemberRole::Admin,
            ]);

            return $project;
        });
    }

    public function assertCanManage(User $user, DcProject $project): void
    {
        $role = $user->toolRoleFor($project);
        if ($role === null || ! $role->canManageTool()) {
            abort(403, 'You do not have permission to manage this tool.');
        }
    }

    public function assertCanView(User $user, DcProject $project): void
    {
        if (! $user->canAccessTool($project)) {
            abort(403, 'You do not have access to this tool.');
        }
    }

    public function assertCanViewAnalytics(User $user, DcProject $project): void
    {
        $this->assertCanView($user, $project);
        if (! $user->toolCapabilitiesFor($project)->canViewAnalytics()) {
            abort(403, 'Analytics are available to tool administrators only.');
        }
    }

    public function assertCanExport(User $user, DcProject $project): void
    {
        $this->assertCanView($user, $project);
        if (! $user->toolCapabilitiesFor($project)->canExportData()) {
            abort(403, 'You do not have permission to export data for this tool.');
        }
    }

    public function assertCanPublishForms(User $user, DcProject $project): void
    {
        $this->assertCanView($user, $project);
        if (! $user->toolCapabilitiesFor($project)->canPublishForms()) {
            abort(403, 'You do not have permission to publish forms for this tool.');
        }
    }

    public function assertCanViewSubmission(User $user, DcProject $project, DcSubmission $submission): void
    {
        $this->assertCanView($user, $project);
        abort_unless($submission->project_id === $project->id, 404);

        $role = $user->toolRoleFor($project);
        if ($role !== null && $role->canViewAllSubmissions()) {
            return;
        }

        if ($submission->collector_id !== $user->id) {
            abort(403, 'You can only view submissions you collected.');
        }
    }

    /** @param  Builder<DcSubmission>  $query */
    public function scopeSubmissionsForUser(User $user, DcProject $project, Builder $query): Builder
    {
        $role = $user->toolRoleFor($project);
        if ($role !== null && $role->canViewAllSubmissions()) {
            return $query;
        }

        return $query->where('collector_id', $user->id);
    }

    private function uniqueProjectCode(string $base): string
    {
        $code = $base;
        $suffix = 1;
        while (DcProject::where('code', $code)->exists()) {
            $code = Str::limit($base, 36, '').'-'.$suffix;
            $suffix++;
        }

        return $code;
    }
}

<?php

namespace App\Services;

use App\Enums\ToolMemberRole;
use App\Models\DcProject;
use App\Models\ToolMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ToolAccessService
{
    public function createTool(User $owner, array $attributes): DcProject
    {
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

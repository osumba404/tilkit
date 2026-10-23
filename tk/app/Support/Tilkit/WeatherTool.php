<?php

namespace App\Support\Tilkit;

use App\Enums\ToolMemberRole;
use App\Models\DcProject;
use App\Models\ToolMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * One fixed TILKIT Weather tool per deployment. Workbook columns and community
 * data list live in config JSON — not seeded as form fields or demo rows.
 */
class WeatherTool
{
    public static function singleToolMode(): bool
    {
        return (bool) config('tilkit.weather.single_tool.enabled', true);
    }

    public static function configuredCode(): string
    {
        return (string) config('tilkit.weather.single_tool.code', 'tilkit-weather');
    }

    public static function isWeatherModule(DcProject $tool): bool
    {
        return $tool->module === 'tilkit_weather';
    }

    public static function resolve(): ?DcProject
    {
        return DcProject::query()->where('code', static::configuredCode())->first();
    }

    public static function assertCanonical(DcProject $tool): void
    {
        if (! static::singleToolMode()) {
            return;
        }

        $canonical = static::resolve();
        if ($canonical === null || $tool->getKey() !== $canonical->getKey()) {
            abort(404);
        }
    }

    public static function ensureForUser(User $owner): DcProject
    {
        $attributes = config('tilkit.weather.single_tool.project', []);

        return DB::transaction(function () use ($owner, $attributes): DcProject {
            $project = DcProject::query()->updateOrCreate(
                ['code' => static::configuredCode()],
                [
                    'name' => $attributes['name'] ?? 'TILKIT Weather Indicator Log',
                    'module' => 'tilkit_weather',
                    'purpose' => $attributes['purpose'] ?? 'Community weather indicator monitoring and seasonal tercile tally (v4 workbook).',
                    'lawful_basis' => $attributes['lawful_basis'] ?? 'consent',
                    'status' => 'active',
                    'public_collection_enabled' => (bool) ($attributes['public_collection_enabled'] ?? false),
                    'owner_user_id' => $owner->id,
                    'created_by' => $owner->id,
                ]
            );

            ToolMember::query()->updateOrCreate(
                ['project_id' => $project->id, 'user_id' => $owner->id],
                ['role' => ToolMemberRole::Admin]
            );

            return $project;
        });
    }
}

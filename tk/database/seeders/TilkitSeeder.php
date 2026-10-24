<?php

namespace Database\Seeders;

use App\Enums\PlatformRole;
use App\Enums\ToolMemberRole;
use App\Models\User;
use App\Support\Tilkit\WeatherTool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TilkitSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'superadmin@tilkit.local'],
            [
                'name' => 'Tilkit Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => 'active',
                'platform_role' => PlatformRole::SuperAdmin,
            ]
        );

        if (WeatherTool::singleToolMode()) {
            WeatherTool::ensureForUser($admin);
        }

        $demoEmail = config('tilkit.weather.demo.owner_email');
        if (is_string($demoEmail) && $demoEmail !== '') {
            $demoUser = User::query()->where('email', $demoEmail)->first();
            if ($demoUser !== null) {
                WeatherTool::ensureMember($demoUser, ToolMemberRole::Admin);
            }
        }
    }
}

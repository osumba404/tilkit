<?php

namespace Tests\Support;

use App\Models\DcProject;
use App\Models\User;
use App\Support\Tilkit\WeatherTool;

trait TilkitTestTool
{
    protected function tilkitToolFor(User $user): DcProject
    {
        config(['tilkit.weather.single_tool.enabled' => true]);

        return WeatherTool::ensureForUser($user);
    }
}

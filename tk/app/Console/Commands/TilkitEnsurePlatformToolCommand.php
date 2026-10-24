<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Tilkit\WeatherTool;
use Illuminate\Console\Command;

class TilkitEnsurePlatformToolCommand extends Command
{
    protected $signature = 'tilkit:ensure-platform-tool';

    protected $description = 'Create the single embedded TILKIT weather tool if it does not exist';

    public function handle(): int
    {
        if (! WeatherTool::singleToolMode()) {
            $this->warn('TILKIT_SINGLE_TOOL is disabled; nothing to do.');

            return self::SUCCESS;
        }

        $owner = User::query()->orderBy('id')->first();
        if ($owner === null) {
            $this->error('No users in the database.');

            return self::FAILURE;
        }

        $tool = WeatherTool::ensurePlatformTool($owner);
        $this->info("Platform weather tool ready: {$tool->name} ({$tool->code})");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Database\Seeders\PlatformShowcaseSeeder;
use Illuminate\Console\Command;

class TilkitSeedPlatformCommand extends Command
{
    protected $signature = 'tilkit:seed-platform {--force : Skip confirmation}';

    protected $description = 'Load Kenya/Ghana workbook content, team, governance, and tallies for the platform owner account';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This replaces all TILKIT weather data (users are kept). Continue?', true)) {
            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => PlatformShowcaseSeeder::class]);

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Database\Seeders\KenyaGhanaPilotSeeder;
use Illuminate\Console\Command;

class TilkitSeedKenyaGhanaCommand extends Command
{
    protected $signature = 'tilkit:seed-kenya-ghana {--force : Skip confirmation}';

    protected $description = 'Wipe TILKIT weather data (keep users), seed Kenya (MAM+OND) and Ghana (north/south rainy seasons) pilot programs, tallies, and evidence for the demo owner';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This deletes all TILKIT weather indicator records, sites, seasons, and programs. Users are kept. Continue?', true)) {
            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => KenyaGhanaPilotSeeder::class]);

        return self::SUCCESS;
    }
}

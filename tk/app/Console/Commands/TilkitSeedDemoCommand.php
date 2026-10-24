<?php

namespace App\Console\Commands;

use Database\Seeders\TilkitDemoDatasetSeeder;
use Illuminate\Console\Command;

class TilkitSeedDemoCommand extends Command
{
    protected $signature = 'tilkit:seed-demo {--force : Import even when record count is above the skip threshold}';

    protected $description = 'Seed TILKIT weather workbook demo data for the configured demo owner account';

    public function handle(): int
    {
        if ($this->option('force')) {
            config(['tilkit.weather.demo.force_import' => true]);
        }

        $this->call('db:seed', ['--class' => TilkitDemoDatasetSeeder::class]);

        return self::SUCCESS;
    }
}

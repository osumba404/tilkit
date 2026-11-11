<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TruncateDatabaseExceptUsersCommand extends Command
{
    protected $signature = 'db:truncate-except-users
                            {--force : Acknowledge destructive action (required in production)}';

    protected $description = 'Remove all rows from every table except users (and migrations).';

    /** @var list<string> */
    private const PRESERVE = [
        'users',
        'migrations',
    ];

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to run in production without --force.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('This deletes all application data except user accounts. Continue?', false)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        $driver = Schema::getConnection()->getDriverName();
        $tables = $this->tableNames();

        if ($tables === []) {
            $this->warn('No tables found.');

            return self::SUCCESS;
        }

        $truncated = 0;
        $skipped = 0;

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tables as $table) {
                if (in_array($table, self::PRESERVE, true)) {
                    $skipped++;
                    continue;
                }

                if ($driver === 'pgsql') {
                    DB::statement('TRUNCATE TABLE "'.$table.'" RESTART IDENTITY CASCADE');
                } else {
                    DB::table($table)->truncate();
                }
                $truncated++;
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $userCount = DB::table('users')->count();
        $this->info("Truncated {$truncated} table(s); preserved ".implode(', ', self::PRESERVE).".");
        $this->info("Users remaining: {$userCount}");
        $this->line('Re-run platform setup if needed, e.g. php artisan tilkit:ensure-platform-tool');

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function tableNames(): array
    {
        $database = DB::getDatabaseName();
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $rows = DB::select(
                'SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ?',
                [$database, 'BASE TABLE']
            );
        } elseif ($driver === 'pgsql') {
            $rows = DB::select(
                "SELECT tablename AS name FROM pg_catalog.pg_tables WHERE schemaname = 'public'"
            );
        } else {
            $rows = DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");
        }

        return collect($rows)
            ->pluck('name')
            ->filter(fn ($name) => is_string($name) && $name !== '')
            ->values()
            ->all();
    }
}

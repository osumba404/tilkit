<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blueprint::macro('auditColumns', function (bool $userForeignKeys = true): void {
            /** @var Blueprint $this */
            $this->timestamp('created_at')->useCurrent();
            $this->timestamp('updated_at')->nullable();
            if ($userForeignKeys) {
                $this->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $this->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            } else {
                $this->unsignedBigInteger('created_by')->nullable();
                $this->unsignedBigInteger('updated_by')->nullable();
            }
            $this->timestamp('deleted_at')->nullable();
        });
    }
}

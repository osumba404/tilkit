<?php

namespace App\Providers;

use App\Models\DcForm;
use App\Models\DcProject;
use App\Models\DcSubmission;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
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

        Route::bind('tool', function (string $value): DcProject {
            return DcProject::query()
                ->where('code', $value)
                ->orWhere('id', $value)
                ->firstOrFail();
        });

        Route::bind('form', function (string $value): DcForm {
            if (is_numeric($value)) {
                return DcForm::findOrFail($value);
            }

            return DcForm::where('code', $value)->firstOrFail();
        });

        Route::bind('submission', function (string $value): DcSubmission {
            return DcSubmission::findOrFail($value);
        });
    }
}

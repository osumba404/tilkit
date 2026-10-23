<?php

namespace App\Providers;

use App\Models\DcForm;
use App\Models\DcProject;
use App\Models\DcSubmission;
use App\Support\Tilkit\WeatherTool;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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
        View::composer(['layouts.navigation', 'layouts.app'], function ($view): void {
            $view->with('tilkitWeatherTool', WeatherTool::singleToolMode() ? WeatherTool::resolve() : null);
        });

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
            $project = DcProject::query()
                ->where('code', $value)
                ->orWhere('id', $value)
                ->firstOrFail();

            if (WeatherTool::singleToolMode()) {
                $canonical = WeatherTool::resolve();
                if ($canonical === null || $project->getKey() !== $canonical->getKey()) {
                    abort(404);
                }
            }

            return $project;
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

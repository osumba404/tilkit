<?php

namespace App\Providers;

use App\Models\DcForm;
use App\Models\DcProject;
use App\Models\DcSubmission;
use App\Services\Tilkit\TilkitProgramContext;
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
        View::composer('tools.tilkit.*', function ($view): void {
            $data = $view->getData();
            if (isset($data['tool']) && auth()->check() && ! array_key_exists('capabilities', $data)) {
                $view->with('capabilities', auth()->user()->toolCapabilitiesFor($data['tool']));
            }
            if (isset($data['tool']) && ! array_key_exists('activeProgram', $data)) {
                $programs = app(TilkitProgramContext::class);
                $view->with('activeProgram', $programs->active($data['tool']));
                $view->with('tilkitPrograms', $programs->programsFor($data['tool']));
            }
        });

        $shareWeatherToolNav = function ($view): void {
            if (! WeatherTool::singleToolMode()) {
                $view->with('tilkitWeatherTool', null);

                return;
            }

            $user = auth()->user();
            $tool = WeatherTool::resolve();
            if ($tool === null && $user !== null) {
                $tool = WeatherTool::ensurePlatformTool($user);
            }
            $view->with('tilkitWeatherTool', $tool);
        };

        View::composer('layouts.navigation', $shareWeatherToolNav);
        View::composer('layouts.tilkit-sidebar', $shareWeatherToolNav);

        View::composer('layouts.app', function ($view): void {
            $isTilkit = request()->routeIs('tools.tilkit.*');
            $view->with('isTilkitPage', $isTilkit);
            if ($isTilkit && ! array_key_exists('tool', $view->getData())) {
                $routeTool = request()->route('tool');
                if ($routeTool instanceof DcProject) {
                    $view->with('tool', $routeTool);
                }
            }
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

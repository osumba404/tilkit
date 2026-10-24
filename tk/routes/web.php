<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FieldReferenceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlatformAdminController;
use App\Http\Controllers\PlatformAnalyticsController;
use App\Http\Controllers\PlatformDatasetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\PublicSubmissionDraftController;
use App\Http\Controllers\PublicToolConsentController;
use App\Http\Controllers\PublicToolController;
use App\Http\Controllers\ToolSettingsController;
use App\Http\Controllers\ToolSubmissionReviewController;
use App\Http\Controllers\ToolAnalyticsController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\ToolFormController;
use App\Http\Controllers\ToolInvitationController;
use App\Http\Controllers\ToolSubmissionController;
use App\Http\Controllers\Api\TilkitSyncController;
use App\Http\Controllers\TilkitGeographyController;
use App\Http\Controllers\ToolTilkitAdminController;
use App\Http\Controllers\ToolTilkitWeatherController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/invitations/{token}', [ToolInvitationController::class, 'acceptForm'])->name('invitations.accept');
Route::middleware(['auth', 'verified'])->get('/invitations/{token}/complete', [ToolInvitationController::class, 'complete'])->name('invitations.complete');

Route::get('/t/{tool:code}/consent', [PublicToolConsentController::class, 'show'])->name('public.tools.consent');
Route::post('/t/{tool:code}/consent', [PublicToolConsentController::class, 'accept'])->name('public.tools.consent.accept');
Route::get('/t/{tool:code}/draft', [PublicSubmissionDraftController::class, 'show'])->name('public.tools.draft.show');
Route::post('/t/{tool:code}/draft', [PublicSubmissionDraftController::class, 'store'])->name('public.tools.draft.store');
Route::get('/t/{tool:code}', [PublicToolController::class, 'collect'])->name('public.tools.collect');
Route::post('/t/{tool:code}', [PublicToolController::class, 'submit'])->name('public.tools.submit');
Route::get('/t/{tool:code}/thanks', [PublicToolController::class, 'thanks'])->name('public.tools.thanks');
Route::get('/t/{tool:code}/qr.svg', [PublicToolController::class, 'qr'])->name('public.tools.qr');

Route::get('/f/{form:code}', [PublicFormController::class, 'show'])->name('public.forms.show');
Route::post('/f/{form:code}', [PublicFormController::class, 'submit'])->name('public.forms.submit');
Route::get('/f/{form:code}/thanks', [PublicFormController::class, 'thanks'])->name('public.forms.thanks');

Route::get('/reference', FieldReferenceController::class)->name('reference');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('tilkit.platform')->group(function () {
        Route::post('/api/tilkit/sightings', [TilkitSyncController::class, 'storeSighting'])->name('api.tilkit.sightings.store');

        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::resource('tools', ToolController::class)->only(['index', 'create', 'store', 'show']);

        Route::prefix('tools/{tool}')->name('tools.')->group(function () {
        Route::post('invitations', [ToolInvitationController::class, 'store'])->name('invitations.store');
        Route::get('tilkit', [ToolTilkitWeatherController::class, 'overview'])->name('tilkit.overview');
        Route::get('tilkit/records', [ToolTilkitAdminController::class, 'recordsIndex'])->name('tilkit.records.index');
        Route::get('tilkit/insights', [ToolTilkitAdminController::class, 'insights'])->name('tilkit.insights');
        Route::get('tilkit/team', [ToolTilkitAdminController::class, 'team'])->name('tilkit.team');
        Route::get('tilkit/records/create', [ToolTilkitWeatherController::class, 'createRecord'])->name('tilkit.records.create');
        Route::post('tilkit/records', [ToolTilkitWeatherController::class, 'storeRecord'])->name('tilkit.records.store');
        Route::get('tilkit/records/{record}', [ToolTilkitWeatherController::class, 'editRecord'])->name('tilkit.records.edit');
        Route::get('tilkit/records/{record}/wizard/{section}', [ToolTilkitWeatherController::class, 'wizard'])->name('tilkit.records.wizard');
        Route::patch('tilkit/records/{record}/wizard/{section}', [ToolTilkitWeatherController::class, 'updateWizard'])->name('tilkit.records.wizard.update');
        Route::patch('tilkit/records/{record}', [ToolTilkitWeatherController::class, 'updateRecord'])->name('tilkit.records.update');
        Route::post('tilkit/records/{record}/sightings', [ToolTilkitAdminController::class, 'storeSighting'])->name('tilkit.records.sightings.store');
        Route::get('tilkit/setup', [ToolTilkitAdminController::class, 'setup'])->name('tilkit.setup');
        Route::post('tilkit/setup/programs', [ToolTilkitAdminController::class, 'storeProgram'])->name('tilkit.setup.program');
        Route::patch('tilkit/setup/programs/{program}', [ToolTilkitAdminController::class, 'updateProgram'])->name('tilkit.setup.program.update');
        Route::post('tilkit/program', [ToolTilkitAdminController::class, 'switchProgram'])->name('tilkit.program.switch');
        Route::get('tilkit/geography/continents', [TilkitGeographyController::class, 'continents'])->name('tilkit.geography.continents');
        Route::get('tilkit/geography/countries', [TilkitGeographyController::class, 'countries'])->name('tilkit.geography.countries');
        Route::post('tilkit/setup/sites', [ToolTilkitAdminController::class, 'storeSite'])->name('tilkit.setup.site');
        Route::post('tilkit/setup/seasons', [ToolTilkitAdminController::class, 'storeSeason'])->name('tilkit.setup.season');
        Route::get('tilkit/settings', [ToolTilkitAdminController::class, 'seasonSettings'])->name('tilkit.settings');
        Route::post('tilkit/settings', [ToolTilkitAdminController::class, 'updateSeasonSettings'])->name('tilkit.settings.update');
        Route::match(['get', 'post'], 'tilkit/tally', [ToolTilkitAdminController::class, 'tally'])->name('tilkit.tally');
        Route::get('tilkit/data-desk', [ToolTilkitAdminController::class, 'dataDesk'])->name('tilkit.data-desk');
        Route::get('tilkit/export.csv', [ToolTilkitAdminController::class, 'exportCsv'])->name('tilkit.export.csv');
        Route::get('tilkit/export.json', [ToolTilkitAdminController::class, 'exportJson'])->name('tilkit.export.json');
        Route::get('tilkit/export.xlsx', [ToolTilkitAdminController::class, 'exportXlsx'])->name('tilkit.export.xlsx');
        Route::post('tilkit/weight-reviews/{review}/sign-off', [ToolTilkitAdminController::class, 'signOffWeightReview'])->name('tilkit.weight-reviews.sign-off');
        Route::post('tilkit/integration', [ToolTilkitAdminController::class, 'publishConsensus'])->name('tilkit.integration.publish');
        Route::get('tilkit/workbook', [ToolTilkitAdminController::class, 'workbookGuides'])->name('tilkit.workbook');
        Route::get('tilkit/calendar', [ToolTilkitAdminController::class, 'calendar'])->name('tilkit.calendar');
        Route::get('analytics', [ToolAnalyticsController::class, 'show'])->name('analytics');
        Route::get('analytics/explorer', [ToolAnalyticsController::class, 'explorer'])->name('analytics.explorer');
        Route::get('analytics/review', [ToolAnalyticsController::class, 'review'])->name('analytics.review');
        Route::get('analytics/reports', [ToolAnalyticsController::class, 'reports'])->name('analytics.reports');
        Route::get('analytics/field', [ToolAnalyticsController::class, 'fieldBreakdown'])->name('analytics.field');
        Route::get('export', [ToolAnalyticsController::class, 'export'])->name('export');
        Route::get('settings', [ToolSettingsController::class, 'edit'])->name('settings.edit');
        Route::patch('settings', [ToolSettingsController::class, 'update'])->name('settings.update');
        Route::get('submissions/review', [ToolSubmissionReviewController::class, 'queue'])->name('submissions.review');
        Route::patch('submissions/{submission}/review', [ToolSubmissionReviewController::class, 'update'])->name('submissions.review.update');

        Route::get('submissions', [ToolSubmissionController::class, 'index'])->name('submissions.index');
        Route::get('submissions/{submission}', [ToolSubmissionController::class, 'show'])->name('submissions.show');

        Route::get('forms/create', [ToolFormController::class, 'create'])->name('forms.create');
        Route::post('forms', [ToolFormController::class, 'store'])->name('forms.store');
        Route::get('forms/{form}/builder', [ToolFormController::class, 'builder'])->name('forms.builder');
        Route::post('forms/{form}/builder', [ToolFormController::class, 'saveBuilder'])->name('forms.builder.save');
        Route::post('forms/{form}/publish', [ToolFormController::class, 'publish'])->name('forms.publish');
        Route::post('forms/{form}/duplicate', [ToolFormController::class, 'duplicate'])->name('forms.duplicate');
        Route::post('forms/{form}/import-csv', [ToolFormController::class, 'importCsv'])->name('forms.import-csv');
        Route::get('forms/{form}/xlsform/export', [ToolFormController::class, 'exportXlsForm'])->name('forms.xlsform.export');
        Route::post('forms/{form}/xlsform/import', [ToolFormController::class, 'importXlsForm'])->name('forms.xlsform.import');
        });
    });

    Route::prefix('platform')->name('platform.')->group(function () {
        Route::get('/datasets', [PlatformDatasetController::class, 'index'])->name('datasets.index');
        Route::post('/datasets', [PlatformDatasetController::class, 'store'])->name('datasets.store');
        Route::get('/datasets/{dataset}', [PlatformDatasetController::class, 'show'])->name('datasets.show');
        Route::post('/datasets/{dataset}/panels', [PlatformDatasetController::class, 'storePanel'])->name('datasets.panels.store');
        Route::patch('/datasets/{dataset}/panels/{panel}', [PlatformDatasetController::class, 'updatePanel'])->name('datasets.panels.update');
        Route::delete('/datasets/{dataset}/panels/{panel}', [PlatformDatasetController::class, 'destroyPanel'])->name('datasets.panels.destroy');
        Route::post('/datasets/{dataset}/panels/clear', [PlatformDatasetController::class, 'clearPanels'])->name('datasets.panels.clear');
        Route::get('/datasets/{dataset}/explorer', [PlatformDatasetController::class, 'explorer'])->name('datasets.explorer');
        Route::get('/datasets/{dataset}/analyze', [PlatformDatasetController::class, 'analyze'])->name('datasets.analyze');
        Route::post('/datasets/{dataset}/analyze', [PlatformDatasetController::class, 'runAnalyze'])->name('datasets.analyze.run');
        Route::get('/datasets/{dataset}/runs/{run}/chart.png', [PlatformDatasetController::class, 'chart'])->name('datasets.chart');
    });

    Route::middleware('super_admin')->prefix('platform')->name('platform.')->group(function () {
        Route::get('/', [PlatformAdminController::class, 'index'])->name('index');
        Route::get('/analytics', [PlatformAnalyticsController::class, 'overview'])->name('analytics.overview');
        Route::get('/analytics/sources', [PlatformAnalyticsController::class, 'sources'])->name('analytics.sources');
        Route::post('/users/{user}/super-admin', [PlatformAdminController::class, 'promoteSuperAdmin'])->name('users.promote');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

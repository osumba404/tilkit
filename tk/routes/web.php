<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FieldReferenceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlatformAdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\PublicToolController;
use App\Http\Controllers\ToolAnalyticsController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\ToolFormController;
use App\Http\Controllers\ToolInvitationController;
use App\Http\Controllers\ToolSubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/invitations/{token}', [ToolInvitationController::class, 'acceptForm'])->name('invitations.accept');
Route::middleware(['auth', 'verified'])->get('/invitations/{token}/complete', [ToolInvitationController::class, 'complete'])->name('invitations.complete');

Route::get('/t/{tool:code}', [PublicToolController::class, 'collect'])->name('public.tools.collect');
Route::post('/t/{tool:code}', [PublicToolController::class, 'submit'])->name('public.tools.submit');
Route::get('/t/{tool:code}/thanks', [PublicToolController::class, 'thanks'])->name('public.tools.thanks');
Route::get('/t/{tool:code}/qr.svg', [PublicToolController::class, 'qr'])->name('public.tools.qr');

Route::get('/f/{form:code}', [PublicFormController::class, 'show'])->name('public.forms.show');
Route::post('/f/{form:code}', [PublicFormController::class, 'submit'])->name('public.forms.submit');
Route::get('/f/{form:code}/thanks', [PublicFormController::class, 'thanks'])->name('public.forms.thanks');

Route::get('/reference', FieldReferenceController::class)->name('reference');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('tools', ToolController::class)->only(['index', 'create', 'store', 'show']);

    Route::prefix('tools/{tool}')->name('tools.')->group(function () {
        Route::post('invitations', [ToolInvitationController::class, 'store'])->name('invitations.store');
        Route::get('analytics', [ToolAnalyticsController::class, 'show'])->name('analytics');
        Route::get('analytics/field', [ToolAnalyticsController::class, 'fieldBreakdown'])->name('analytics.field');
        Route::get('export', [ToolAnalyticsController::class, 'export'])->name('export');

        Route::get('submissions', [ToolSubmissionController::class, 'index'])->name('submissions.index');
        Route::get('submissions/{submission}', [ToolSubmissionController::class, 'show'])->name('submissions.show');

        Route::get('forms/create', [ToolFormController::class, 'create'])->name('forms.create');
        Route::post('forms', [ToolFormController::class, 'store'])->name('forms.store');
        Route::get('forms/{form}/builder', [ToolFormController::class, 'builder'])->name('forms.builder');
        Route::post('forms/{form}/builder', [ToolFormController::class, 'saveBuilder'])->name('forms.builder.save');
        Route::post('forms/{form}/publish', [ToolFormController::class, 'publish'])->name('forms.publish');
    });

    Route::middleware('super_admin')->prefix('platform')->name('platform.')->group(function () {
        Route::get('/', [PlatformAdminController::class, 'index'])->name('index');
        Route::post('/users/{user}/super-admin', [PlatformAdminController::class, 'promoteSuperAdmin'])->name('users.promote');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

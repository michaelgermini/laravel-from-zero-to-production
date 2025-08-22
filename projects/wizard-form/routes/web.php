<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FormController;
use App\Http\Controllers\FormStepController;
use App\Http\Controllers\FormFieldController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Routes publiques
Route::get('/', [DashboardController::class, 'index'])->name('home');

// Routes d'authentification (Laravel Breeze)
require __DIR__.'/auth.php';

// Routes protégées (authentification requise)
Route::middleware('auth')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    
    // Routes des formulaires
    Route::prefix('forms')->name('forms.')->group(function () {
        Route::get('/', [FormController::class, 'index'])->name('index');
        Route::get('/create', [FormController::class, 'create'])->name('create');
        Route::post('/', [FormController::class, 'store'])->name('store');
        Route::get('/{form}', [FormController::class, 'show'])->name('show');
        Route::get('/{form}/edit', [FormController::class, 'edit'])->name('edit');
        Route::put('/{form}', [FormController::class, 'update'])->name('update');
        Route::delete('/{form}', [FormController::class, 'destroy'])->name('destroy');
        Route::post('/{form}/duplicate', [FormController::class, 'duplicate'])->name('duplicate');
        Route::post('/{form}/publish', [FormController::class, 'publish'])->name('publish');
        Route::post('/{form}/archive', [FormController::class, 'archive'])->name('archive');
        Route::get('/{form}/statistics', [FormController::class, 'statistics'])->name('statistics');
        Route::get('/{form}/export', [FormController::class, 'export'])->name('export');
        Route::get('/{form}/settings', [FormController::class, 'settings'])->name('settings');
        Route::patch('/{form}/settings', [FormController::class, 'updateSettings'])->name('update-settings');
        Route::get('/{form}/preview', [FormController::class, 'preview'])->name('preview');
        Route::get('/{form}/share', [FormController::class, 'share'])->name('share');
    });
    
    // Routes des étapes de formulaire
    Route::prefix('forms/{form}/steps')->name('form-steps.')->group(function () {
        Route::get('/', [FormStepController::class, 'index'])->name('index');
        Route::post('/', [FormStepController::class, 'store'])->name('store');
        Route::get('/{step}', [FormStepController::class, 'show'])->name('show');
        Route::put('/{step}', [FormStepController::class, 'update'])->name('update');
        Route::delete('/{step}', [FormStepController::class, 'destroy'])->name('destroy');
        Route::post('/{step}/duplicate', [FormStepController::class, 'duplicate'])->name('duplicate');
        Route::post('/reorder', [FormStepController::class, 'reorder'])->name('reorder');
    });
    
    // Routes des champs de formulaire
    Route::prefix('forms/{form}/fields')->name('form-fields.')->group(function () {
        Route::get('/', [FormFieldController::class, 'index'])->name('index');
        Route::post('/', [FormFieldController::class, 'store'])->name('store');
        Route::get('/{field}', [FormFieldController::class, 'show'])->name('show');
        Route::put('/{field}', [FormFieldController::class, 'update'])->name('update');
        Route::delete('/{field}', [FormFieldController::class, 'destroy'])->name('destroy');
        Route::post('/{field}/duplicate', [FormFieldController::class, 'duplicate'])->name('duplicate');
        Route::post('/reorder', [FormFieldController::class, 'reorder'])->name('reorder');
    });
    
    // Routes des soumissions
    Route::prefix('submissions')->name('submissions.')->group(function () {
        Route::get('/', [FormSubmissionController::class, 'index'])->name('index');
        Route::get('/{submission}', [FormSubmissionController::class, 'show'])->name('show');
        Route::delete('/{submission}', [FormSubmissionController::class, 'destroy'])->name('destroy');
        Route::get('/{submission}/export', [FormSubmissionController::class, 'export'])->name('export');
    });
    
    // Routes des templates
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [TemplateController::class, 'index'])->name('index');
        Route::get('/{template}', [TemplateController::class, 'show'])->name('show');
        Route::post('/{template}/use', [TemplateController::class, 'useTemplate'])->name('use');
    });
    
    // Routes des intégrations
    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/', [IntegrationController::class, 'index'])->name('index');
        Route::post('/{integration}/connect', [IntegrationController::class, 'connect'])->name('connect');
        Route::delete('/{integration}/disconnect', [IntegrationController::class, 'disconnect'])->name('disconnect');
        Route::patch('/{integration}/settings', [IntegrationController::class, 'updateSettings'])->name('update-settings');
    });
    
    // Routes des rapports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/forms', [ReportController::class, 'forms'])->name('forms');
        Route::get('/submissions', [ReportController::class, 'submissions'])->name('submissions');
        Route::get('/analytics', [ReportController::class, 'analytics'])->name('analytics');
        Route::get('/export/{type}', [ReportController::class, 'export'])->name('export');
    });
    
    // Routes des paramètres
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::patch('/general', [SettingController::class, 'updateGeneral'])->name('general');
        Route::patch('/notifications', [SettingController::class, 'updateNotifications'])->name('notifications');
        Route::patch('/integrations', [SettingController::class, 'updateIntegrations'])->name('integrations');
        Route::patch('/themes', [SettingController::class, 'updateThemes'])->name('themes');
    });
});

// Routes publiques pour remplir les formulaires
Route::prefix('f')->name('forms.fill.')->group(function () {
    Route::get('/{slug}', [FormController::class, 'fill'])->name('show');
    Route::post('/{slug}', [FormController::class, 'submit'])->name('submit');
});

// Routes API pour AJAX
Route::prefix('api')->name('api.')->group(function () {
    Route::get('/forms/search', [FormController::class, 'apiSearch'])->name('forms.search');
    Route::get('/forms/{form}/steps', [FormStepController::class, 'apiIndex'])->name('steps.index');
    Route::get('/forms/{form}/fields', [FormFieldController::class, 'apiIndex'])->name('fields.index');
    Route::post('/forms/{form}/save-draft', [FormSubmissionController::class, 'saveDraft'])->name('submissions.save-draft');
    Route::get('/forms/{form}/load-draft', [FormSubmissionController::class, 'loadDraft'])->name('submissions.load-draft');
});

// Routes de fallback
Route::fallback(function () {
    return view('errors.404');
});

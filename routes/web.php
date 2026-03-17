<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UpdateController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Sites
    Route::get('/sites/create', [SiteController::class, 'create'])->name('sites.create');
    Route::post('/sites', [SiteController::class, 'store'])->name('sites.store');
    Route::get('/sites/{site}', [SiteController::class, 'show'])->name('sites.show');
    Route::delete('/sites/{site}', [SiteController::class, 'destroy'])->name('sites.destroy');

    // Site sub-pages
    Route::get('/sites/{site}/updates', [SiteController::class, 'updates'])->name('sites.updates');
    Route::get('/sites/{site}/history', [SiteController::class, 'history'])->name('sites.history');
    Route::get('/sites/{site}/health', [SiteController::class, 'healthChecks'])->name('sites.health');
    Route::get('/sites/{site}/errors', [SiteController::class, 'errors'])->name('sites.errors');
    Route::post('/sites/{site}/sync', [SiteController::class, 'sync'])->name('sites.sync');

    // Updates
    Route::post('/sites/{site}/updates/trigger', [UpdateController::class, 'triggerUpdate'])->name('updates.trigger');
    Route::post('/sites/{site}/updates/all-plugins', [UpdateController::class, 'triggerAllPluginUpdates'])->name('updates.all-plugins');
    Route::post('/sites/{site}/updates/core', [UpdateController::class, 'triggerCoreUpdate'])->name('updates.core');
    Route::get('/sites/{site}/updates/{jobId}', [UpdateController::class, 'showJob'])->name('updates.show');

    // Global update actions
    Route::post('/updates/all-sites', [UpdateController::class, 'updateAllSites'])->name('updates.all-sites');
    Route::post('/updates/toggle-auto', [UpdateController::class, 'toggleAutoUpdates'])->name('updates.toggle-auto');
});

require __DIR__.'/auth.php';

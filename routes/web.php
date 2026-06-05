<?php

use App\Http\Controllers\AppelOffreController;
use App\Http\Controllers\BugController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProspectController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', \App\Http\Middleware\EnsureTwoFactor::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/prospects', [ProspectController::class, 'index'])->name('prospects.index');
    Route::get('/prospects/{prospect}', [ProspectController::class, 'show'])->name('prospects.show');
    Route::put('/prospects/{prospect}', [ProspectController::class, 'update'])->name('prospects.update');
    Route::put('/prospects/{prospect}/scenarios', [ProspectController::class, 'saveScenario'])->name('prospects.scenarios');
    Route::post('/prospects/{prospect}/interactions', [InteractionController::class, 'store'])->name('interactions.store');

    Route::get('/appels-offres', [AppelOffreController::class, 'index'])->name('ao.index');
    Route::post('/appels-offres/refresh', [AppelOffreController::class, 'refresh'])->name('ao.refresh');
    Route::get('/appels-offres/{tender}', [AppelOffreController::class, 'show'])->name('ao.show');
    Route::put('/appels-offres/{tender}', [AppelOffreController::class, 'update'])->name('ao.update');
    Route::put('/appels-offres/{tender}/checklist', [AppelOffreController::class, 'saveChecklist'])->name('ao.checklist');
    Route::get('/appels-offres/{tender}/dossier.doc', [AppelOffreController::class, 'downloadDoc'])->name('ao.dossier.doc');
    Route::get('/appels-offres/{tender}/annexe.xlsx', [AppelOffreController::class, 'downloadXlsx'])->name('ao.dossier.xlsx');

    // --- Clients & projets ---
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');

    Route::get('/projets', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projets/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projets', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projets/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::put('/projets/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::post('/projets/{project}/taches', [ProjectController::class, 'storeTask'])->name('tasks.store');
    Route::put('/taches/{task}', [ProjectController::class, 'updateTask'])->name('tasks.update');
    Route::delete('/taches/{task}', [ProjectController::class, 'destroyTask'])->name('tasks.destroy');

    // Suivi de production : bugs déclarés par le client.
    Route::post('/projets/{project}/bugs', [BugController::class, 'store'])->name('bugs.store');
    Route::put('/bugs/{bug}', [BugController::class, 'update'])->name('bugs.update');
    Route::delete('/bugs/{bug}', [BugController::class, 'destroy'])->name('bugs.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

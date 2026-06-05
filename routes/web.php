<?php

use App\Http\Controllers\AppelOffreController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProspectController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth'])->group(function () {
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

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\AppelOffreController;
use App\Http\Controllers\BugController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContratController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ParametresController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PartenaireActivationController;
use App\Http\Controllers\PartenaireController;
use App\Http\Controllers\PortailController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProspectController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

// --- Activation d'un compte partenaire (lien signé reçu par e-mail) ---
Route::middleware('signed')->group(function () {
    Route::get('/partenaire/activation/{user}', [PartenaireActivationController::class, 'show'])
        ->name('partenaire.activation');
    Route::post('/partenaire/activation/{user}', [PartenaireActivationController::class, 'store'])
        ->name('partenaire.activation.store');
});

// --- Portail partenaire (compte role=partenaire) ---
Route::middleware(['auth', \App\Http\Middleware\EnsurePartenaire::class])->group(function () {
    Route::get('/portail', [PortailController::class, 'index'])->name('portail.index');
    Route::post('/portail/taches', [PortailController::class, 'storeTask'])->name('portail.tasks.store');
});

// Notifications in-app (badge) — accessible aux admins comme aux partenaires.
Route::post('/notifications/read/{id?}', [NotificationController::class, 'markRead'])
    ->middleware('auth')->name('notifications.read');

Route::middleware(['auth', \App\Http\Middleware\EnsureTwoFactor::class, \App\Http\Middleware\EnsureAdmin::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/prospects', [ProspectController::class, 'index'])->name('prospects.index');
    Route::delete('/prospects/purge', [ProspectController::class, 'purge'])->name('prospects.purge');
    Route::get('/prospects/{prospect}', [ProspectController::class, 'show'])->name('prospects.show');
    Route::put('/prospects/{prospect}', [ProspectController::class, 'update'])->name('prospects.update');
    Route::put('/prospects/{prospect}/scenarios', [ProspectController::class, 'saveScenario'])->name('prospects.scenarios');
    Route::put('/prospects/{prospect}/facturation', [ProspectController::class, 'updateFacturation'])->name('prospects.facturation');
    Route::put('/prospects/{prospect}/factures', [ProspectController::class, 'upsertFacture'])->name('prospects.factures.upsert');
    Route::post('/prospects/{prospect}/generate-email', [ProspectController::class, 'generateEmail'])->name('prospects.generateEmail');
    Route::post('/prospects/{prospect}/send-email', [ProspectController::class, 'sendEmail'])->name('prospects.sendEmail');
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
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::put('/clients/{client}/support-token', [ClientController::class, 'regenerateSupportToken'])->name('clients.support-token');
    // Besoins
    Route::post('/clients/{client}/besoins', [ClientController::class, 'storeBesoin'])->name('besoins.store');
    Route::put('/besoins/{besoin}', [ClientController::class, 'updateBesoin'])->name('besoins.update');
    Route::delete('/besoins/{besoin}', [ClientController::class, 'destroyBesoin'])->name('besoins.destroy');
    // Devis
    Route::post('/clients/{client}/devis', [ClientController::class, 'storeDevis'])->name('devis.store');
    Route::put('/devis/{devis}', [ClientController::class, 'updateDevis'])->name('devis.update');
    Route::delete('/devis/{devis}', [ClientController::class, 'destroyDevis'])->name('devis.destroy');
    // Factures ponctuelles
    Route::post('/clients/{client}/factures', [ClientController::class, 'storeFacture'])->name('factures.store');
    Route::put('/factures-ponctuelles/{facture}', [ClientController::class, 'updateFacture'])->name('factures.update');
    Route::delete('/factures-ponctuelles/{facture}', [ClientController::class, 'destroyFacture'])->name('factures.destroy');
    // Contrats
    Route::post('/clients/{client}/contrats', [ContratController::class, 'store'])->name('contrats.store');
    Route::put('/contrats/{contrat}', [ContratController::class, 'update'])->name('contrats.update');
    Route::delete('/contrats/{contrat}', [ContratController::class, 'destroy'])->name('contrats.destroy');
    Route::get('/contrats/{contrat}/pdf', [ContratController::class, 'pdf'])->name('contrats.pdf');
    Route::post('/contrats/{contrat}/send', [ContratController::class, 'send'])->name('contrats.send');

    // Paramètres (modèle de conditions de contrat)
    Route::get('/parametres', [ParametresController::class, 'edit'])->name('parametres.edit');
    Route::put('/parametres', [ParametresController::class, 'update'])->name('parametres.update');
    Route::get('/parametres/contrat-test.pdf', [ParametresController::class, 'testPdf'])->name('parametres.contrat-test');

    Route::get('/projets', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projets/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projets', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projets/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::put('/projets/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::post('/projets/{project}/taches', [ProjectController::class, 'storeTask'])->name('tasks.store');
    Route::put('/taches/{task}', [ProjectController::class, 'updateTask'])->name('tasks.update');
    Route::delete('/taches/{task}', [ProjectController::class, 'destroyTask'])->name('tasks.destroy');

    // --- Partenaires ---
    Route::get('/partenaires', [PartenaireController::class, 'index'])->name('partenaires.index');
    Route::get('/partenaires/create', [PartenaireController::class, 'create'])->name('partenaires.create');
    Route::post('/partenaires', [PartenaireController::class, 'store'])->name('partenaires.store');
    Route::get('/partenaires/{partenaire}', [PartenaireController::class, 'show'])->name('partenaires.show');
    Route::put('/partenaires/{partenaire}', [PartenaireController::class, 'update'])->name('partenaires.update');
    Route::delete('/partenaires/{partenaire}', [PartenaireController::class, 'destroy'])->name('partenaires.destroy');
    Route::post('/partenaires/{partenaire}/invite', [PartenaireController::class, 'resendInvite'])->name('partenaires.invite');
    Route::post('/partenaires/{partenaire}/projets', [PartenaireController::class, 'attachProject'])->name('partenaires.projets.attach');
    Route::delete('/partenaires/{partenaire}/projets/{project}', [PartenaireController::class, 'detachProject'])->name('partenaires.projets.detach');

    // Suivi de production : bugs déclarés par le client.
    Route::post('/projets/{project}/bugs', [BugController::class, 'store'])->name('bugs.store');
    Route::put('/bugs/{bug}', [BugController::class, 'update'])->name('bugs.update');
    Route::post('/bugs/{bug}/messages', [BugController::class, 'storeMessage'])->name('bugs.messages.store');
    Route::delete('/bugs/{bug}', [BugController::class, 'destroy'])->name('bugs.destroy');
    // Images d'incident (assistance) servies depuis le disque privé.
    Route::get('/bugs/images/{image}', [\App\Http\Controllers\SupportImageController::class, 'show'])->name('bugs.images.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

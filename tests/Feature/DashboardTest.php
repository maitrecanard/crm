<?php

use App\Models\Bug;
use App\Models\Partenaire;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Prospect;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/** Admin connecté (2FA désactivée pour isoler le test de la fonctionnalité). */
function adminDash(): User
{
    config(['crm.require_2fa' => false]);

    return User::create([
        'name' => 'Mathieu', 'email' => 'admin-'.uniqid().'@techcare.fr',
        'password' => bcrypt('x'), 'role' => 'admin',
    ]);
}

/** Un client (prospect est_client) porteur d'un projet. */
function projetClient(): Project
{
    $client = Prospect::create([
        'cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'localite' => 'Paris',
        'est_client' => true, 'client_depuis' => now()->toDateString(),
    ]);

    return $client->projects()->create(['titre' => 'Site ACME', 'statut' => 'en_cours']);
}

it('liste les tickets en cours et exclut les tickets livrés ou clôturés', function () {
    $projet = projetClient();

    $projet->bugs()->create(['titre' => 'Erreur 500', 'statut' => 'en_cours', 'type' => 'bug', 'gravite' => 'bloquant']);
    $projet->bugs()->create(['titre' => 'Signalé', 'statut' => 'nouveau', 'type' => 'bug', 'gravite' => 'mineur']);
    $projet->bugs()->create(['titre' => 'Déjà livré', 'statut' => 'livre', 'type' => 'bug', 'gravite' => 'majeur']);
    $projet->bugs()->create(['titre' => 'Clôturé', 'statut' => 'ferme', 'type' => 'bug', 'gravite' => 'majeur']);

    $this->actingAs(adminDash())->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('ticketsCount', 2)
            ->has('ticketsEnCours', 2)
            // Tri par gravité : le bloquant d'abord.
            ->where('ticketsEnCours.0.gravite', 'bloquant')
            ->where('ticketsEnCours.0.client', 'ACME')
            ->where('ticketsEnCours.0.reference', fn ($ref) => str_starts_with($ref, 'TIC-'))
        );
});

it('agrège les rappels des trois sources, triés par échéance croissante', function () {
    $projet = projetClient();
    $partenaire = Partenaire::create(['nom' => 'AgenceWeb', 'email' => 'p@agence.fr']);

    // Maintenance de ticket : échéance J+5.
    $projet->bugs()->create([
        'titre' => 'Maintenance mensuelle', 'statut' => 'nouveau', 'type' => 'maintenance',
        'gravite' => 'mineur', 'recurrence' => 'mensuelle', 'prochaine_echeance' => now()->addDays(5)->toDateString(),
    ]);

    // Tâche partenaire non terminée : échéance J+2 (la plus proche).
    ProjectTask::create([
        'project_id' => $projet->id, 'titre' => 'Intégrer maquette', 'statut' => 'a_faire',
        'source' => 'partenaire', 'partenaire_id' => $partenaire->id, 'echeance' => now()->addDays(2)->toDateString(),
    ]);

    // Relance prospect : échéance J+10.
    Prospect::create([
        'cle' => 'p-'.uniqid(), 'entreprise' => 'Prospect SARL', 'statut' => 'contacte',
        'prochaine_relance' => now()->addDays(10)->toDateString(),
    ]);

    // Bruit qui doit être exclu : hors horizon (J+30) et tâche partenaire déjà faite.
    Prospect::create([
        'cle' => 'p-'.uniqid(), 'entreprise' => 'Trop loin', 'statut' => 'contacte',
        'prochaine_relance' => now()->addDays(30)->toDateString(),
    ]);
    ProjectTask::create([
        'project_id' => $projet->id, 'titre' => 'Déjà faite', 'statut' => 'fait',
        'source' => 'partenaire', 'partenaire_id' => $partenaire->id, 'echeance' => now()->addDays(1)->toDateString(),
    ]);

    $this->actingAs(adminDash())->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('rappels', 3)
            ->where('rappels.0.type', 'partenaire')   // J+2
            ->where('rappels.0.meta', 'AgenceWeb')
            ->where('rappels.1.type', 'maintenance')  // J+5
            ->where('rappels.2.type', 'prospect')     // J+10
        );
});

it('affiche un dashboard vide sans erreur quand il n’y a ni ticket ni rappel', function () {
    $this->actingAs(adminDash())->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('ticketsCount', 0)
            ->has('ticketsEnCours', 0)
            ->has('rappels', 0)
        );
});

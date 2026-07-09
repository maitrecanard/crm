<?php

use App\Models\Project;
use App\Models\Prospect;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function adminProjet(): User
{
    config(['crm.require_2fa' => false]);

    return User::create(['name' => 'M', 'email' => 'pj-'.uniqid().'@t.fr', 'password' => bcrypt('x'), 'role' => 'admin']);
}

it('n’attache jamais de client à un projet interne, même si un client est envoyé', function () {
    $client = Prospect::create(['cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'est_client' => true]);

    $this->actingAs(adminProjet())->post(route('projects.store'), [
        'interne' => true, 'prospect_id' => $client->id, 'titre' => 'Outil', 'statut' => 'en_cours',
    ])->assertRedirect();

    $projet = Project::first();
    expect($projet->interne)->toBeTrue()
        ->and($projet->prospect_id)->toBeNull();
});

it('force l’absence de client au niveau du modèle (invariant)', function () {
    $client = Prospect::create(['cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'est_client' => true]);

    $projet = Project::create(['interne' => true, 'prospect_id' => $client->id, 'titre' => 'X', 'statut' => 'en_cours']);

    expect($projet->fresh()->prospect_id)->toBeNull();
});

it('crée un projet interne sans client', function () {
    $this->actingAs(adminProjet())->post(route('projects.store'), [
        'interne' => true, 'titre' => 'Outil interne', 'statut' => 'en_cours',
    ])->assertRedirect();

    $projet = Project::first();
    expect($projet->interne)->toBeTrue()
        ->and($projet->prospect_id)->toBeNull()
        ->and($projet->titre)->toBe('Outil interne');
});

it('refuse un projet non interne sans client', function () {
    $this->actingAs(adminProjet())->post(route('projects.store'), [
        'titre' => 'Sans client', 'statut' => 'cadrage',
    ])->assertSessionHasErrors('prospect_id');

    expect(Project::count())->toBe(0);
});

it('expose les projets internes au formulaire de création de ticket', function () {
    Project::create(['interne' => true, 'titre' => 'Projet interne', 'statut' => 'en_cours']);

    $this->actingAs(adminProjet())->get(route('tickets.create'))
        ->assertInertia(fn (Assert $p) => $p
            ->component('Tickets/Create')
            ->has('internalProjects', 1)
            ->where('internalProjects.0.titre', 'Projet interne'));
});

it('permet un ticket interne rattaché à un projet interne', function () {
    $projet = Project::create(['interne' => true, 'titre' => 'Projet interne', 'statut' => 'en_cours']);

    $this->actingAs(adminProjet())->post(route('tickets.store'), [
        'interne' => true, 'project_id' => $projet->id, 'type' => 'bug', 'titre' => 'Faire X', 'gravite' => 'mineur',
    ])->assertRedirect();

    expect(\App\Models\Bug::first()->project_id)->toBe($projet->id);
});

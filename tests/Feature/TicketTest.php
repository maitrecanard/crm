<?php

use App\Mail\BugStatusMail;
use App\Models\Bug;
use App\Models\BugEvent;
use App\Models\Project;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

function adminTicket(): User
{
    config(['crm.require_2fa' => false]);

    return User::create([
        'name' => 'Mathieu', 'email' => 'a-'.uniqid().'@techcare.fr',
        'password' => bcrypt('x'), 'role' => 'admin',
    ]);
}

function projetAvecClient(array $over = []): Project
{
    $client = Prospect::create(array_merge([
        'cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'email' => 'client@acme.fr',
        'est_client' => true, 'client_depuis' => now()->toDateString(),
    ], $over));

    return $client->projects()->create(['titre' => 'Site', 'statut' => 'en_cours']);
}

it('liste les tickets pour l’admin', function () {
    $projet = projetAvecClient();
    $projet->bugs()->create(['titre' => 'Bug A', 'statut' => 'nouveau', 'type' => 'bug', 'gravite' => 'majeur']);

    $this->actingAs(adminTicket())->get(route('tickets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p
            ->component('Tickets/Index')
            ->has('tickets', 1)
            ->where('tickets.0.titre', 'Bug A'));
});

it('crée un ticket depuis l’admin, notifie le client et journalise la création', function () {
    Mail::fake();
    $projet = projetAvecClient();

    $res = $this->actingAs(adminTicket())->post(route('tickets.store'), [
        'project_id' => $projet->id, 'type' => 'bug', 'titre' => 'Erreur 500', 'gravite' => 'bloquant',
    ]);

    $bug = Bug::first();
    $res->assertRedirect(route('tickets.show', $bug));

    expect($bug->source)->toBe('interne')
        ->and($bug->statut)->toBe('nouveau')
        ->and($bug->events()->where('type', 'creation')->count())->toBe(1);

    Mail::assertSent(BugStatusMail::class);
});

it('marque l’auteur « client » pour un ticket ouvert depuis le site', function () {
    $projet = projetAvecClient();
    $bug = $projet->bugs()->create([
        'titre' => 'Panne', 'statut' => 'nouveau', 'type' => 'bug', 'gravite' => 'majeur', 'source' => 'client_site',
    ]);

    expect($bug->events()->where('type', 'creation')->first()->auteur)->toBe('client');
});

it('affiche la page ticket avec son historique complet', function () {
    $projet = projetAvecClient();
    $bug = $projet->bugs()->create(['titre' => 'X', 'statut' => 'nouveau', 'type' => 'bug', 'gravite' => 'majeur']);
    $bug->messages()->create(['corps' => 'Note interne', 'interne' => true]);
    $bug->logStatut('nouveau', 'en_cours');

    $this->actingAs(adminTicket())->get(route('tickets.show', $bug))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p
            ->component('Tickets/Show')
            ->where('ticket.reference', fn ($r) => str_starts_with($r, 'TIC-'))
            // création + message + changement de statut
            ->has('historique', 3));
});

it('journalise et notifie le client lors d’un changement de statut', function () {
    Mail::fake();
    $projet = projetAvecClient();
    $bug = $projet->bugs()->create(['titre' => 'X', 'statut' => 'nouveau', 'type' => 'bug', 'gravite' => 'majeur']);

    $this->actingAs(adminTicket())->put(route('bugs.update', $bug), ['statut' => 'en_cours'])
        ->assertRedirect();

    expect($bug->fresh()->statut)->toBe('en_cours')
        ->and(BugEvent::where('bug_id', $bug->id)->where('type', 'statut')->where('nouveau_statut', 'en_cours')->count())->toBe(1);

    Mail::assertSent(BugStatusMail::class);
});

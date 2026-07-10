<?php

use App\Models\Partenaire;
use App\Models\Project;
use App\Models\Prospect;
use App\Models\User;
use App\Notifications\TicketPartenaire;
use Illuminate\Support\Facades\Notification;

function adminTP(): User
{
    config(['crm.require_2fa' => false]);

    return User::create(['name' => 'M', 'email' => 'tp-'.uniqid().'@t.fr', 'password' => bcrypt('x'), 'role' => 'admin']);
}

/** @return array{0: Project, 1: Partenaire, 2: User} projet lié à un partenaire (avec compte). */
function projetAvecPartenaire(): array
{
    $client = Prospect::create(['cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'email' => 'client@acme.fr', 'est_client' => true]);
    $projet = $client->projects()->create(['titre' => 'Site', 'statut' => 'en_cours']);
    $partenaire = Partenaire::create(['nom' => 'Agence', 'email' => 'ag-'.uniqid().'@a.fr']);
    $user = User::create([
        'name' => 'P', 'email' => 'pu-'.uniqid().'@a.fr', 'password' => bcrypt('x'),
        'role' => 'partenaire', 'partenaire_id' => $partenaire->id, 'email_verified_at' => now(),
    ]);
    $projet->partenaires()->attach($partenaire->id);

    return [$projet, $partenaire, $user];
}

it('informe le partenaire à la création d’un ticket (via TicketController)', function () {
    Notification::fake();
    [$projet, , $user] = projetAvecPartenaire();

    $this->actingAs(adminTP())->post(route('tickets.store'), [
        'project_id' => $projet->id, 'type' => 'bug', 'titre' => 'Souci', 'gravite' => 'majeur',
    ])->assertRedirect();

    Notification::assertSentTo($user, TicketPartenaire::class, fn ($n) => $n->evenement === 'nouveau');
});

it('informe le partenaire à la création depuis la fiche projet (BugController)', function () {
    Notification::fake();
    [$projet, , $user] = projetAvecPartenaire();

    $this->actingAs(adminTP())->post(route('bugs.store', $projet), [
        'type' => 'bug', 'titre' => 'Souci', 'gravite' => 'majeur',
    ])->assertRedirect();

    Notification::assertSentTo($user, TicketPartenaire::class, fn ($n) => $n->evenement === 'nouveau');
});

it('informe le partenaire à chaque changement de statut', function () {
    Notification::fake();
    [$projet, , $user] = projetAvecPartenaire();
    $bug = $projet->bugs()->create(['titre' => 'X', 'statut' => 'nouveau', 'type' => 'bug', 'gravite' => 'majeur']);

    $this->actingAs(adminTP())->put(route('bugs.update', $bug), ['statut' => 'en_cours'])->assertRedirect();

    Notification::assertSentTo($user, TicketPartenaire::class, fn ($n) => $n->evenement === 'statut');
});

it('informe le partenaire même pour un ticket interne rattaché au projet', function () {
    Notification::fake();
    [$projet, , $user] = projetAvecPartenaire();

    $this->actingAs(adminTP())->post(route('tickets.store'), [
        'interne' => true, 'project_id' => $projet->id, 'type' => 'bug', 'titre' => 'Interne', 'gravite' => 'mineur',
    ])->assertRedirect();

    Notification::assertSentTo($user, TicketPartenaire::class, fn ($n) => $n->evenement === 'nouveau');
});

it('n’informe que les partenaires liés au projet', function () {
    Notification::fake();
    [$projet, , $user] = projetAvecPartenaire();
    // Un autre partenaire, NON lié à ce projet.
    $autre = Partenaire::create(['nom' => 'Autre', 'email' => 'au-'.uniqid().'@a.fr']);
    $autreUser = User::create([
        'name' => 'Autre', 'email' => 'auu-'.uniqid().'@a.fr', 'password' => bcrypt('x'),
        'role' => 'partenaire', 'partenaire_id' => $autre->id, 'email_verified_at' => now(),
    ]);

    $this->actingAs(adminTP())->post(route('tickets.store'), [
        'project_id' => $projet->id, 'type' => 'bug', 'titre' => 'Souci', 'gravite' => 'majeur',
    ])->assertRedirect();

    Notification::assertSentTo($user, TicketPartenaire::class);
    Notification::assertNotSentTo($autreUser, TicketPartenaire::class);
});

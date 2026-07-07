<?php

use App\Models\Prospect;
use App\Models\User;
use App\Notifications\RelanceTickets;
use Illuminate\Support\Facades\Notification;

function adminRelance(): User
{
    config(['crm.require_2fa' => false]);

    return User::create(['name' => 'M', 'email' => 'rt-'.uniqid().'@t.fr', 'password' => bcrypt('x'), 'role' => 'admin']);
}

function projetPourRelance(): \App\Models\Project
{
    $client = Prospect::create(['cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'est_client' => true]);

    return $client->projects()->create(['titre' => 'Site', 'statut' => 'en_cours']);
}

it('relance les admins sur les tickets en cours, hors clôturés', function () {
    Notification::fake();
    $admin = adminRelance();
    $projet = projetPourRelance();
    $projet->bugs()->create(['titre' => 'Ouvert', 'statut' => 'en_cours', 'type' => 'bug', 'gravite' => 'bloquant']);
    $projet->bugs()->create(['titre' => 'Livré', 'statut' => 'livre', 'type' => 'bug', 'gravite' => 'majeur']);
    $projet->bugs()->create(['titre' => 'Clôturé', 'statut' => 'ferme', 'type' => 'bug', 'gravite' => 'majeur']);

    $this->artisan('crm:relances-tickets', ['--moment' => 'matin'])->assertSuccessful();

    Notification::assertSentTo($admin, RelanceTickets::class,
        fn ($n) => $n->tickets->count() === 1 && $n->moment === 'matin');
});

it('n’envoie rien en dry-run', function () {
    Notification::fake();
    adminRelance();
    projetPourRelance()->bugs()->create(['titre' => 'X', 'statut' => 'nouveau', 'type' => 'bug', 'gravite' => 'majeur']);

    $this->artisan('crm:relances-tickets', ['--dry-run' => true])->assertSuccessful();

    Notification::assertNothingSent();
});

it('n’envoie rien s’il n’y a aucun ticket en cours', function () {
    Notification::fake();
    adminRelance();

    $this->artisan('crm:relances-tickets')->assertSuccessful();

    Notification::assertNothingSent();
});

it('ne notifie pas les comptes partenaires', function () {
    Notification::fake();
    $admin = adminRelance();
    $partenaireUser = User::create([
        'name' => 'Paul', 'email' => 'pp-'.uniqid().'@a.fr', 'password' => bcrypt('x'), 'role' => 'partenaire',
    ]);
    projetPourRelance()->bugs()->create(['titre' => 'X', 'statut' => 'nouveau', 'type' => 'bug', 'gravite' => 'majeur']);

    $this->artisan('crm:relances-tickets', ['--moment' => 'soir'])->assertSuccessful();

    Notification::assertSentTo($admin, RelanceTickets::class);
    Notification::assertNotSentTo($partenaireUser, RelanceTickets::class);
});

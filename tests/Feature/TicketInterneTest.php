<?php

use App\Mail\BugStatusMail;
use App\Models\Bug;
use App\Models\BugEvent;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

function adminInterne(): User
{
    config(['crm.require_2fa' => false]);

    return User::create(['name' => 'M', 'email' => 'in-'.uniqid().'@t.fr', 'password' => bcrypt('x'), 'role' => 'admin']);
}

it('crée un ticket interne sans client ni e-mail', function () {
    Mail::fake();

    $this->actingAs(adminInterne())->post(route('tickets.store'), [
        'interne' => true, 'type' => 'bug', 'titre' => 'Refacto interne', 'gravite' => 'mineur',
    ])->assertRedirect();

    $bug = Bug::first();
    expect($bug->project_id)->toBeNull()
        ->and($bug->estInterne())->toBeTrue()
        ->and($bug->source)->toBe('interne')
        ->and($bug->reference)->toStartWith('TIC-');

    Mail::assertNothingSent();
});

it('exige un projet quand le ticket n’est pas interne', function () {
    $this->actingAs(adminInterne())->post(route('tickets.store'), [
        'type' => 'bug', 'titre' => 'Sans client', 'gravite' => 'majeur',
    ])->assertSessionHasErrors('project_id');

    expect(Bug::count())->toBe(0);
});

it('change le statut d’un ticket interne sans notifier, mais journalise', function () {
    Mail::fake();
    $bug = Bug::create(['titre' => 'X', 'statut' => 'nouveau', 'type' => 'bug', 'gravite' => 'majeur', 'source' => 'interne']);

    $this->actingAs(adminInterne())->put(route('bugs.update', $bug), ['statut' => 'en_cours'])
        ->assertRedirect();

    expect($bug->fresh()->statut)->toBe('en_cours')
        ->and(BugEvent::where('bug_id', $bug->id)->where('nouveau_statut', 'en_cours')->count())->toBe(1);
    Mail::assertNothingSent();
});

it('marque le ticket comme interne dans la liste', function () {
    // Un ticket interne + un ticket client.
    Bug::create(['titre' => 'Interne', 'statut' => 'nouveau', 'type' => 'bug', 'gravite' => 'mineur', 'source' => 'interne']);
    $client = Prospect::create(['cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'est_client' => true]);
    $client->projects()->create(['titre' => 'Site', 'statut' => 'en_cours'])
        ->bugs()->create(['titre' => 'Client', 'statut' => 'nouveau', 'type' => 'bug', 'gravite' => 'majeur']);

    $this->actingAs(adminInterne())->get(route('tickets.index'))
        ->assertInertia(fn (Assert $p) => $p
            ->has('tickets', 2)
            ->where('tickets', fn ($tickets) => collect($tickets)->firstWhere('titre', 'Interne')['interne'] === true
                && collect($tickets)->firstWhere('titre', 'Client')['interne'] === false));
});

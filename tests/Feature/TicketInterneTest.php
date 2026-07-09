<?php

use App\Models\Bug;
use App\Models\BugEvent;
use App\Models\Project;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

function adminInterne(): User
{
    config(['crm.require_2fa' => false]);

    return User::create(['name' => 'M', 'email' => 'in-'.uniqid().'@t.fr', 'password' => bcrypt('x'), 'role' => 'admin']);
}

function projetInterne(): Project
{
    $client = Prospect::create([
        'cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'email' => 'client@acme.fr',
        'est_client' => true, 'client_depuis' => now()->toDateString(),
    ]);

    return $client->projects()->create(['titre' => 'Site', 'statut' => 'en_cours']);
}

it('crée un ticket interne sans projet ni e-mail', function () {
    Mail::fake();

    $this->actingAs(adminInterne())->post(route('tickets.store'), [
        'interne' => true, 'type' => 'bug', 'titre' => 'Refacto interne', 'gravite' => 'mineur',
    ])->assertRedirect();

    $bug = Bug::first();
    expect($bug->interne)->toBeTrue()
        ->and($bug->estInterne())->toBeTrue()
        ->and($bug->project_id)->toBeNull();
    Mail::assertNothingSent();
});

it('crée un ticket interne RATTACHÉ à un projet, toujours sans e-mail', function () {
    Mail::fake();
    $projet = projetInterne();

    $this->actingAs(adminInterne())->post(route('tickets.store'), [
        'interne' => true, 'project_id' => $projet->id, 'type' => 'bug', 'titre' => 'Note interne projet', 'gravite' => 'majeur',
    ])->assertRedirect();

    $bug = Bug::first();
    expect($bug->interne)->toBeTrue()
        ->and($bug->project_id)->toBe($projet->id);   // interne ET rattaché à un projet
    Mail::assertNothingSent();
});

it('exige un projet quand le ticket n’est pas interne', function () {
    $this->actingAs(adminInterne())->post(route('tickets.store'), [
        'type' => 'bug', 'titre' => 'Sans client', 'gravite' => 'majeur',
    ])->assertSessionHasErrors('project_id');

    expect(Bug::count())->toBe(0);
});

it('ne notifie pas au changement de statut d’un ticket interne, même avec un client', function () {
    Mail::fake();
    // Ticket interne mais rattaché à un projet dont le client a un e-mail.
    $bug = projetInterne()->bugs()->create([
        'titre' => 'X', 'statut' => 'nouveau', 'type' => 'bug', 'gravite' => 'majeur', 'interne' => true,
    ]);

    $this->actingAs(adminInterne())->put(route('bugs.update', $bug), ['statut' => 'en_cours'])
        ->assertRedirect();

    expect($bug->fresh()->statut)->toBe('en_cours')
        ->and(BugEvent::where('bug_id', $bug->id)->where('nouveau_statut', 'en_cours')->count())->toBe(1);
    Mail::assertNothingSent();
});

it('marque le ticket comme interne dans la liste', function () {
    Bug::create(['titre' => 'Interne', 'statut' => 'nouveau', 'type' => 'bug', 'gravite' => 'mineur', 'source' => 'interne', 'interne' => true]);
    projetInterne()->bugs()->create(['titre' => 'Client', 'statut' => 'nouveau', 'type' => 'bug', 'gravite' => 'majeur']);

    $this->actingAs(adminInterne())->get(route('tickets.index'))
        ->assertInertia(fn (Assert $p) => $p
            ->has('tickets', 2)
            ->where('tickets', fn ($tickets) => collect($tickets)->firstWhere('titre', 'Interne')['interne'] === true
                && collect($tickets)->firstWhere('titre', 'Client')['interne'] === false));
});

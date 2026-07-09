<?php

use App\Models\Project;
use App\Models\Prospect;
use App\Models\User;

function adminChgClient(): User
{
    config(['crm.require_2fa' => false]);

    return User::create(['name' => 'M', 'email' => 'cc-'.uniqid().'@t.fr', 'password' => bcrypt('x'), 'role' => 'admin']);
}

it('corrige le client rattaché à un projet', function () {
    $c1 = Prospect::create(['cle' => 'c1-'.uniqid(), 'entreprise' => 'Mauvais', 'est_client' => true]);
    $c2 = Prospect::create(['cle' => 'c2-'.uniqid(), 'entreprise' => 'Bon', 'est_client' => true]);
    $projet = $c1->projects()->create(['titre' => 'Site', 'statut' => 'en_cours']);

    $this->actingAs(adminChgClient())->put(route('projects.client', $projet), ['prospect_id' => $c2->id])
        ->assertRedirect();

    expect($projet->fresh()->prospect_id)->toBe($c2->id);
});

it('promeut le nouveau prospect en client s’il ne l’était pas', function () {
    $c1 = Prospect::create(['cle' => 'c1-'.uniqid(), 'entreprise' => 'Actuel', 'est_client' => true]);
    $prospect = Prospect::create(['cle' => 'p-'.uniqid(), 'entreprise' => 'Nouveau', 'statut' => 'contacte']);
    $projet = $c1->projects()->create(['titre' => 'Site', 'statut' => 'en_cours']);

    $this->actingAs(adminChgClient())->put(route('projects.client', $projet), ['prospect_id' => $prospect->id])
        ->assertRedirect();

    expect($projet->fresh()->prospect_id)->toBe($prospect->id)
        ->and($prospect->fresh()->est_client)->toBeTrue();
});

it('exige un client valide', function () {
    $c1 = Prospect::create(['cle' => 'c1-'.uniqid(), 'entreprise' => 'X', 'est_client' => true]);
    $projet = $c1->projects()->create(['titre' => 'Site', 'statut' => 'en_cours']);

    $this->actingAs(adminChgClient())->put(route('projects.client', $projet), ['prospect_id' => 999999])
        ->assertSessionHasErrors('prospect_id');

    expect($projet->fresh()->prospect_id)->toBe($c1->id);
});

it('passe un projet en interne (sans client)', function () {
    $c1 = Prospect::create(['cle' => 'c1-'.uniqid(), 'entreprise' => 'Client', 'est_client' => true]);
    $projet = $c1->projects()->create(['titre' => 'Site', 'statut' => 'en_cours']);

    $this->actingAs(adminChgClient())->put(route('projects.client', $projet), ['interne' => true])
        ->assertRedirect();

    $projet->refresh();
    expect($projet->interne)->toBeTrue()
        ->and($projet->prospect_id)->toBeNull();
});

it('repasse un projet interne à un client', function () {
    $projet = Project::create(['interne' => true, 'titre' => 'Interne', 'statut' => 'en_cours']);
    $client = Prospect::create(['cle' => 'c-'.uniqid(), 'entreprise' => 'Client', 'est_client' => true]);

    $this->actingAs(adminChgClient())->put(route('projects.client', $projet), ['prospect_id' => $client->id])
        ->assertRedirect();

    $projet->refresh();
    expect($projet->interne)->toBeFalse()
        ->and($projet->prospect_id)->toBe($client->id);
});

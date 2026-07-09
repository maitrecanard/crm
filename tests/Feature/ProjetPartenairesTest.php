<?php

use App\Models\Partenaire;
use App\Models\Project;
use App\Models\Prospect;
use App\Models\User;

function adminPart(): User
{
    config(['crm.require_2fa' => false]);

    return User::create(['name' => 'M', 'email' => 'pp-'.uniqid().'@t.fr', 'password' => bcrypt('x'), 'role' => 'admin']);
}

function projetPart(): Project
{
    $client = Prospect::create(['cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'est_client' => true]);

    return $client->projects()->create(['titre' => 'Site', 'statut' => 'en_cours']);
}

it('lie plusieurs partenaires à un projet', function () {
    $projet = projetPart();
    $p1 = Partenaire::create(['nom' => 'Agence 1', 'email' => 'a1-'.uniqid().'@a.fr']);
    $p2 = Partenaire::create(['nom' => 'Agence 2', 'email' => 'a2-'.uniqid().'@a.fr']);
    $admin = adminPart();

    $this->actingAs($admin)->post(route('projects.partenaires.attach', $projet), ['partenaire_id' => $p1->id])->assertRedirect();
    $this->actingAs($admin)->post(route('projects.partenaires.attach', $projet), ['partenaire_id' => $p2->id])->assertRedirect();

    expect($projet->partenaires()->pluck('partenaires.id')->all())->toContain($p1->id)->toContain($p2->id)
        ->and($projet->partenaires()->count())->toBe(2);
});

it('ne duplique pas un partenaire déjà lié', function () {
    $projet = projetPart();
    $p1 = Partenaire::create(['nom' => 'Agence', 'email' => 'a-'.uniqid().'@a.fr']);
    $admin = adminPart();

    $this->actingAs($admin)->post(route('projects.partenaires.attach', $projet), ['partenaire_id' => $p1->id]);
    $this->actingAs($admin)->post(route('projects.partenaires.attach', $projet), ['partenaire_id' => $p1->id]);

    expect($projet->partenaires()->count())->toBe(1);
});

it('retire un partenaire du projet', function () {
    $projet = projetPart();
    $p1 = Partenaire::create(['nom' => 'Agence', 'email' => 'a-'.uniqid().'@a.fr']);
    $projet->partenaires()->attach($p1->id);

    $this->actingAs(adminPart())->delete(route('projects.partenaires.detach', [$projet, $p1]))->assertRedirect();

    expect($projet->partenaires()->count())->toBe(0);
});

it('le partenaire voit le projet lié dans son portail', function () {
    $projet = projetPart();
    $partenaire = Partenaire::create(['nom' => 'Agence', 'email' => 'a-'.uniqid().'@a.fr']);
    $user = User::create([
        'name' => 'P', 'email' => 'pu-'.uniqid().'@a.fr', 'password' => bcrypt('x'),
        'role' => 'partenaire', 'partenaire_id' => $partenaire->id, 'email_verified_at' => now(),
    ]);
    $projet->partenaires()->attach($partenaire->id);

    expect($partenaire->projects()->pluck('projects.id')->all())->toContain($projet->id);
});

it('la création de projet lie le partenaire choisi', function () {
    $client = Prospect::create(['cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'est_client' => true]);
    $partenaire = Partenaire::create(['nom' => 'Agence', 'email' => 'a-'.uniqid().'@a.fr']);

    $this->actingAs(adminPart())->post(route('projects.store'), [
        'prospect_id' => $client->id, 'partenaire_id' => $partenaire->id, 'titre' => 'X', 'statut' => 'en_cours',
    ])->assertRedirect();

    expect(Project::first()->partenaires()->count())->toBe(1);
});

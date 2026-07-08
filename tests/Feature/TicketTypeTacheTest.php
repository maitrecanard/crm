<?php

use App\Models\Bug;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function adminTache(): User
{
    config(['crm.require_2fa' => false]);

    return User::create(['name' => 'M', 'email' => 'ta-'.uniqid().'@t.fr', 'password' => bcrypt('x'), 'role' => 'admin']);
}

it('expose le type « tâche à réaliser »', function () {
    expect(Bug::TYPES)->toHaveKey('tache')
        ->and(Bug::TYPES['tache'])->toBe('Tâche à réaliser');
});

it('crée un ticket de type tâche à réaliser', function () {
    Mail::fake();
    $client = Prospect::create(['cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'email' => 'client@acme.fr', 'est_client' => true]);
    $projet = $client->projects()->create(['titre' => 'Site', 'statut' => 'en_cours']);

    $this->actingAs(adminTache())->post(route('tickets.store'), [
        'project_id' => $projet->id, 'type' => 'tache', 'titre' => 'Mettre à jour le logo', 'gravite' => 'mineur',
    ])->assertRedirect();

    expect(Bug::first()->type)->toBe('tache');
});

it('adapte le préfixe de sujet et le message client au type tâche', function () {
    $bug = new Bug(['type' => 'tache', 'statut' => 'en_cours']);

    expect($bug->subjectPrefix())->toBe('Tâche')
        ->and($bug->clientMessage())->toBe('La tâche est en cours de réalisation.');
});

<?php

use App\Models\Partenaire;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Prospect;
use App\Models\User;
use App\Notifications\ReponseTachePartenaire;
use App\Notifications\TacheAssigneePartenaire;
use Illuminate\Support\Facades\Notification;

function adminAssign(): User
{
    config(['crm.require_2fa' => false]);

    return User::create([
        'name' => 'Mathieu', 'email' => 'ad-'.uniqid().'@techcare.fr',
        'password' => bcrypt('x'), 'role' => 'admin',
    ]);
}

/** @return array{0: Partenaire, 1: User} */
function partenaireAvecCompte(): array
{
    $p = Partenaire::create(['nom' => 'AgenceWeb', 'email' => 'p-'.uniqid().'@a.fr']);
    $u = User::create([
        'name' => 'Paul', 'email' => 'pu-'.uniqid().'@a.fr',
        'password' => bcrypt('x'), 'role' => 'partenaire', 'partenaire_id' => $p->id,
    ]);

    return [$p, $u];
}

function projetAssignation(): Project
{
    $client = Prospect::create(['cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'est_client' => true]);

    return $client->projects()->create(['titre' => 'Site', 'statut' => 'en_cours']);
}

it('assigne une tâche à un partenaire (proposée) et le notifie', function () {
    Notification::fake();
    [$p, $u] = partenaireAvecCompte();
    $projet = projetAssignation();

    $this->actingAs(adminAssign())->post(route('tasks.assign', $projet), [
        'partenaire_id' => $p->id, 'titre' => 'Intégrer la maquette',
    ])->assertRedirect();

    $task = ProjectTask::first();
    expect($task->source)->toBe('assignee')
        ->and($task->statut)->toBe('proposee')
        ->and($task->partenaire_id)->toBe($p->id);

    Notification::assertSentTo($u, TacheAssigneePartenaire::class);
});

it('le partenaire accepte une tâche assignée et l’admin est notifié', function () {
    Notification::fake();
    $admin = adminAssign();
    [$p, $u] = partenaireAvecCompte();
    $task = projetAssignation()->tasks()->create([
        'titre' => 'T', 'statut' => 'proposee', 'source' => 'assignee', 'partenaire_id' => $p->id,
    ]);

    $this->actingAs($u)->post(route('portail.tasks.respond', $task), ['action' => 'accepter'])
        ->assertRedirect();

    expect($task->fresh()->statut)->toBe('a_faire');
    Notification::assertSentTo($admin, ReponseTachePartenaire::class);
});

it('le partenaire refuse : motif obligatoire, statut refusé, admin notifié', function () {
    Notification::fake();
    $admin = adminAssign();
    [$p, $u] = partenaireAvecCompte();
    $task = projetAssignation()->tasks()->create([
        'titre' => 'T', 'statut' => 'proposee', 'source' => 'assignee', 'partenaire_id' => $p->id,
    ]);

    // Sans motif : refus rejeté.
    $this->actingAs($u)->post(route('portail.tasks.respond', $task), ['action' => 'refuser'])
        ->assertSessionHasErrors('motif_refus');
    expect($task->fresh()->statut)->toBe('proposee');

    // Avec motif : refus accepté.
    $this->actingAs($u)->post(route('portail.tasks.respond', $task), [
        'action' => 'refuser', 'motif_refus' => 'Indisponible ce mois-ci',
    ])->assertRedirect();

    expect($task->fresh()->statut)->toBe('refusee')
        ->and($task->fresh()->motif_refus)->toBe('Indisponible ce mois-ci');
    Notification::assertSentTo($admin, ReponseTachePartenaire::class);
});

it('un partenaire ne peut pas répondre à la tâche d’un autre', function () {
    [$p1] = partenaireAvecCompte();
    [, $u2] = partenaireAvecCompte();
    $task = projetAssignation()->tasks()->create([
        'titre' => 'T', 'statut' => 'proposee', 'source' => 'assignee', 'partenaire_id' => $p1->id,
    ]);

    $this->actingAs($u2)->post(route('portail.tasks.respond', $task), ['action' => 'accepter'])
        ->assertForbidden();
});

it('réassigne une tâche refusée à un autre partenaire', function () {
    Notification::fake();
    [$p1] = partenaireAvecCompte();
    [$p2, $u2] = partenaireAvecCompte();
    $task = projetAssignation()->tasks()->create([
        'titre' => 'T', 'statut' => 'refusee', 'motif_refus' => 'non', 'source' => 'assignee', 'partenaire_id' => $p1->id,
    ]);

    $this->actingAs(adminAssign())->put(route('tasks.reassign', $task), ['partenaire_id' => $p2->id])
        ->assertRedirect();

    $task->refresh();
    expect($task->statut)->toBe('proposee')
        ->and($task->partenaire_id)->toBe($p2->id)
        ->and($task->motif_refus)->toBeNull();
    Notification::assertSentTo($u2, TacheAssigneePartenaire::class);
});

it('le partenaire fait progresser une tâche acceptée jusqu’à terminée', function () {
    Notification::fake();
    $admin = adminAssign();
    [$p, $u] = partenaireAvecCompte();
    $task = projetAssignation()->tasks()->create([
        'titre' => 'T', 'statut' => 'a_faire', 'source' => 'assignee', 'partenaire_id' => $p->id,
    ]);

    $this->actingAs($u)->post(route('portail.tasks.status', $task), ['statut' => 'fait'])
        ->assertRedirect();

    expect($task->fresh()->statut)->toBe('fait');
    Notification::assertSentTo($admin, ReponseTachePartenaire::class);
});

it('un partenaire ne peut pas faire progresser une tâche encore à valider', function () {
    [$p, $u] = partenaireAvecCompte();
    $task = projetAssignation()->tasks()->create([
        'titre' => 'T', 'statut' => 'proposee', 'source' => 'assignee', 'partenaire_id' => $p->id,
    ]);

    $this->actingAs($u)->post(route('portail.tasks.status', $task), ['statut' => 'en_cours'])
        ->assertForbidden();
});

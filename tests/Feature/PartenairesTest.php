<?php

use App\Mail\PartenaireInvitationMail;
use App\Models\Partenaire;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Prospect;
use App\Models\User;
use App\Notifications\RappelTachesPartenaire;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

/** Admin connecté (2FA désactivée pour isoler le test de la fonctionnalité). */
function admin(): User
{
    config(['crm.require_2fa' => false]);

    return User::create([
        'name' => 'Mathieu', 'email' => 'admin-'.uniqid().'@techcare.fr',
        'password' => bcrypt('x'), 'role' => 'admin',
    ]);
}

function clientProspect(): Prospect
{
    return Prospect::create([
        'cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'est_client' => true,
        'client_depuis' => now()->toDateString(),
    ]);
}

it('crée un partenaire, son compte et envoie l’e-mail d’activation', function () {
    Mail::fake();

    $this->actingAs(admin())->post('/partenaires', [
        'nom' => 'AgenceWeb', 'contact_nom' => 'Paul', 'email' => 'paul@agence.fr',
        'telephone' => '0102030405',
    ])->assertRedirect();

    $partenaire = Partenaire::first();
    expect($partenaire)->not->toBeNull()
        ->and($partenaire->nom)->toBe('AgenceWeb');

    $user = User::where('email', 'paul@agence.fr')->first();
    expect($user)->not->toBeNull()
        ->and($user->role)->toBe('partenaire')
        ->and($user->partenaire_id)->toBe($partenaire->id)
        ->and($user->email_verified_at)->toBeNull();   // pas encore activé

    Mail::assertSent(PartenaireInvitationMail::class, fn ($m) => $m->hasTo('paul@agence.fr'));
});

it('refuse un e-mail de partenaire déjà utilisé', function () {
    $existing = admin();

    $this->actingAs(admin())->post('/partenaires', [
        'nom' => 'X', 'email' => $existing->email,
    ])->assertSessionHasErrors('email');

    expect(Partenaire::count())->toBe(0);
});

it('active le compte via le lien signé et connecte le partenaire', function () {
    Http::fake(['https://api.pwnedpasswords.com/*' => Http::response('', 200)]);

    $partenaire = Partenaire::create(['nom' => 'A', 'email' => 'a@a.fr', 'actif' => true]);
    $user = User::create([
        'name' => 'A', 'email' => 'a@a.fr', 'password' => bcrypt('tmp'),
        'role' => 'partenaire', 'partenaire_id' => $partenaire->id,
    ]);

    $url = URL::temporarySignedRoute('partenaire.activation.store', now()->addDays(7), ['user' => $user->id]);

    $this->post($url, [
        'password' => 'Sup3rSecret!Pwd', 'password_confirmation' => 'Sup3rSecret!Pwd',
    ])->assertRedirect(route('portail.index'));

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('rejette l’activation sans signature valide', function () {
    $partenaire = Partenaire::create(['nom' => 'A', 'email' => 'a@a.fr', 'actif' => true]);
    $user = User::create([
        'name' => 'A', 'email' => 'a@a.fr', 'password' => bcrypt('tmp'),
        'role' => 'partenaire', 'partenaire_id' => $partenaire->id,
    ]);

    $this->get('/partenaire/activation/'.$user->id)->assertStatus(403);
});

it('le partenaire transmet une tâche sur son projet', function () {
    $partenaire = Partenaire::create(['nom' => 'A', 'email' => 'a@a.fr', 'actif' => true]);
    $user = User::create([
        'name' => 'A', 'email' => 'a@a.fr', 'password' => bcrypt('x'),
        'role' => 'partenaire', 'partenaire_id' => $partenaire->id, 'email_verified_at' => now(),
    ]);
    $project = Project::create([
        'prospect_id' => clientProspect()->id, 'partenaire_id' => $partenaire->id,
        'titre' => 'Site vitrine', 'statut' => 'en_cours',
    ]);

    $this->actingAs($user)->post('/portail/taches', [
        'project_id' => $project->id, 'titre' => 'Corriger le formulaire',
        'description' => 'Le champ email ne valide pas', 'echeance' => '2026-07-01',
    ])->assertRedirect();

    $task = ProjectTask::first();
    expect($task->titre)->toBe('Corriger le formulaire')
        ->and($task->source)->toBe('partenaire')
        ->and($task->partenaire_id)->toBe($partenaire->id);
});

it('le partenaire ne peut pas transmettre sur un projet qui n’est pas le sien', function () {
    $a = Partenaire::create(['nom' => 'A', 'email' => 'a@a.fr', 'actif' => true]);
    $b = Partenaire::create(['nom' => 'B', 'email' => 'b@b.fr', 'actif' => true]);
    $user = User::create([
        'name' => 'A', 'email' => 'a@a.fr', 'password' => bcrypt('x'),
        'role' => 'partenaire', 'partenaire_id' => $a->id, 'email_verified_at' => now(),
    ]);
    $autre = Project::create([
        'prospect_id' => clientProspect()->id, 'partenaire_id' => $b->id,
        'titre' => 'Autre', 'statut' => 'en_cours',
    ]);

    $this->actingAs($user)->post('/portail/taches', [
        'project_id' => $autre->id, 'titre' => 'Hack',
    ])->assertStatus(404);

    expect(ProjectTask::count())->toBe(0);
});

it('un partenaire est redirigé du back-office vers son portail', function () {
    $partenaire = Partenaire::create(['nom' => 'A', 'email' => 'a@a.fr', 'actif' => true]);
    $user = User::create([
        'name' => 'A', 'email' => 'a@a.fr', 'password' => bcrypt('x'),
        'role' => 'partenaire', 'partenaire_id' => $partenaire->id, 'email_verified_at' => now(),
    ]);

    $this->actingAs($user)->get('/partenaires')->assertRedirect(route('portail.index'));
});

it('un admin ne peut pas accéder au portail partenaire', function () {
    $this->actingAs(admin())->get('/portail')->assertStatus(403);
});

it('supprimer un partenaire supprime son compte de connexion', function () {
    $partenaire = Partenaire::create(['nom' => 'A', 'email' => 'a@a.fr', 'actif' => true]);
    User::create([
        'name' => 'A', 'email' => 'a@a.fr', 'password' => bcrypt('x'),
        'role' => 'partenaire', 'partenaire_id' => $partenaire->id,
    ]);

    $partenaire->delete();

    expect(User::where('email', 'a@a.fr')->count())->toBe(0);
});

it('la commande de rappel notifie les admins des tâches partenaire en attente', function () {
    Notification::fake();

    $admin = admin();
    $partenaire = Partenaire::create(['nom' => 'A', 'email' => 'a@a.fr', 'actif' => true]);
    $project = Project::create([
        'prospect_id' => clientProspect()->id, 'partenaire_id' => $partenaire->id,
        'titre' => 'P', 'statut' => 'en_cours',
    ]);
    // Une tâche partenaire en attente + une déjà faite (ignorée) + une interne (ignorée).
    $project->tasks()->create(['titre' => 'À faire', 'source' => 'partenaire', 'partenaire_id' => $partenaire->id, 'statut' => 'a_faire']);
    $project->tasks()->create(['titre' => 'Faite', 'source' => 'partenaire', 'partenaire_id' => $partenaire->id, 'statut' => 'fait']);
    $project->tasks()->create(['titre' => 'Interne', 'source' => 'interne', 'statut' => 'a_faire']);

    $this->artisan('crm:rappels-partenaires')->assertSuccessful();

    Notification::assertSentTo($admin, RappelTachesPartenaire::class, function ($notif) {
        return $notif->taches->count() === 1 && $notif->taches->first()->titre === 'À faire';
    });
});

it('aucun rappel envoyé quand il n’y a pas de tâche partenaire en attente', function () {
    Notification::fake();
    admin();

    $this->artisan('crm:rappels-partenaires')->assertSuccessful();

    Notification::assertNothingSent();
});

<?php

use App\Mail\BugStatusMail;
use App\Models\Bug;
use App\Models\BugEvent;
use App\Models\Project;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

function adminStatut(): User
{
    config(['crm.require_2fa' => false]);

    return User::create(['name' => 'M', 'email' => 'st-'.uniqid().'@t.fr', 'password' => bcrypt('x'), 'role' => 'admin']);
}

function projetStatut(): Project
{
    $client = Prospect::create([
        'cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'email' => 'client@acme.fr',
        'est_client' => true, 'client_depuis' => now()->toDateString(),
    ]);

    return $client->projects()->create(['titre' => 'Site', 'statut' => 'en_cours']);
}

it('accepte le statut « en attente de retour client », le journalise et notifie', function () {
    Mail::fake();
    $bug = projetStatut()->bugs()->create(['titre' => 'X', 'statut' => 'en_cours', 'type' => 'bug', 'gravite' => 'majeur']);

    $this->actingAs(adminStatut())->put(route('bugs.update', $bug), ['statut' => 'attente_client'])
        ->assertRedirect();

    expect($bug->fresh()->statut)->toBe('attente_client')
        ->and(BugEvent::where('bug_id', $bug->id)->where('nouveau_statut', 'attente_client')->count())->toBe(1);
    Mail::assertSent(BugStatusMail::class);
});

it('accepte aussi « en attente de retour fournisseur »', function () {
    Mail::fake();
    $bug = projetStatut()->bugs()->create(['titre' => 'X', 'statut' => 'en_cours', 'type' => 'bug', 'gravite' => 'majeur']);

    $this->actingAs(adminStatut())->put(route('bugs.update', $bug), ['statut' => 'attente_fournisseur'])
        ->assertRedirect();

    expect($bug->fresh()->statut)->toBe('attente_fournisseur');
});

it('la liste des tickets masque les clôturés par défaut, mais les affiche via le filtre', function () {
    $projet = projetStatut();
    $projet->bugs()->create(['titre' => 'Ouvert', 'statut' => 'attente_fournisseur', 'type' => 'bug', 'gravite' => 'majeur']);
    $projet->bugs()->create(['titre' => 'Clos', 'statut' => 'ferme', 'type' => 'bug', 'gravite' => 'majeur']);
    $admin = adminStatut();

    // Par défaut : le clôturé est masqué.
    $this->actingAs($admin)->get(route('tickets.index'))
        ->assertInertia(fn (Assert $p) => $p->has('tickets', 1)->where('tickets.0.titre', 'Ouvert'));

    // Filtre explicite « ferme » : le clôturé réapparaît.
    $this->actingAs($admin)->get(route('tickets.index', ['statut' => 'ferme']))
        ->assertInertia(fn (Assert $p) => $p->has('tickets', 1)->where('tickets.0.titre', 'Clos'));
});

it('considère les statuts d’attente comme « en cours »', function () {
    expect(Bug::OUVERTS)->toContain('attente_fournisseur')->toContain('attente_client')
        ->and(Bug::OUVERTS)->not->toContain('livre')->not->toContain('ferme');
});

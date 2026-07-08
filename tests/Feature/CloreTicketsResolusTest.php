<?php

use App\Models\BugEvent;
use App\Models\Project;
use App\Models\Prospect;
use Illuminate\Support\Facades\Mail;

function projetCloture(): Project
{
    $client = Prospect::create([
        'cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'email' => 'client@acme.fr',
        'est_client' => true, 'client_depuis' => now()->toDateString(),
    ]);

    return $client->projects()->create(['titre' => 'Site', 'statut' => 'en_cours']);
}

it('clôture les tickets résolus depuis plus de 7 jours, sans e-mail', function () {
    Mail::fake();
    $projet = projetCloture();

    $vieux = $projet->bugs()->create(['titre' => 'Vieux', 'statut' => 'livre', 'type' => 'bug', 'gravite' => 'majeur', 'resolved_at' => now()->subDays(8)]);
    $recent = $projet->bugs()->create(['titre' => 'Récent', 'statut' => 'livre', 'type' => 'bug', 'gravite' => 'majeur', 'resolved_at' => now()->subDays(3)]);
    $ouvert = $projet->bugs()->create(['titre' => 'Ouvert', 'statut' => 'en_cours', 'type' => 'bug', 'gravite' => 'majeur']);

    $this->artisan('crm:cloture-tickets')->assertSuccessful();

    expect($vieux->fresh()->statut)->toBe('ferme')      // résolu depuis 8 j -> clôturé
        ->and($recent->fresh()->statut)->toBe('livre')  // résolu depuis 3 j -> intact
        ->and($ouvert->fresh()->statut)->toBe('en_cours');

    // Aucun e-mail envoyé au client lors de la clôture automatique.
    Mail::assertNothingSent();

    // La transition est bien journalisée dans l'historique (auteur « auto »).
    expect(BugEvent::where('bug_id', $vieux->id)->where('type', 'statut')
        ->where('nouveau_statut', 'ferme')->where('auteur', 'auto')->count())->toBe(1);
});

it('ignore les tickets sans date de résolution', function () {
    Mail::fake();
    // livré mais resolved_at null (cas limite) -> non clôturé.
    $bug = projetCloture()->bugs()->create(['titre' => 'X', 'statut' => 'livre', 'type' => 'bug', 'gravite' => 'majeur']);

    $this->artisan('crm:cloture-tickets')->assertSuccessful();

    expect($bug->fresh()->statut)->toBe('livre');
});

it('respecte --jours et --dry-run', function () {
    Mail::fake();
    $bug = projetCloture()->bugs()->create(['titre' => 'X', 'statut' => 'livre', 'type' => 'bug', 'gravite' => 'majeur', 'resolved_at' => now()->subDays(5)]);

    // dry-run : rien n'est modifié même si éligible.
    $this->artisan('crm:cloture-tickets', ['--jours' => 3, '--dry-run' => true])->assertSuccessful();
    expect($bug->fresh()->statut)->toBe('livre');

    // seuil abaissé à 3 j : le ticket (résolu depuis 5 j) est clôturé.
    $this->artisan('crm:cloture-tickets', ['--jours' => 3])->assertSuccessful();
    expect($bug->fresh()->statut)->toBe('ferme');
});

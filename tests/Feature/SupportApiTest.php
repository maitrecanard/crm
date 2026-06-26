<?php

use App\Mail\BugStatusMail;
use App\Models\Bug;
use App\Models\Prospect;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

// PNG 1x1 valide (base64) pour tester l'upload d'images.
const PNG_1x1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

/**
 * Crée un client (avec token d'assistance) + un projet portant le site donné,
 * et renvoie [client, token, site]. Seul ce site est autorisé à appeler l'API.
 */
function clientAvecToken(array $over = []): array
{
    $site = $over['site'] ?? 'https://acme.example';
    unset($over['site']);

    $client = Prospect::create(array_merge([
        'cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'email' => 'client@acme.fr',
        'est_client' => true, 'client_depuis' => now()->toDateString(),
    ], $over));
    $token = $client->genererTokenSupport();

    $client->projects()->create(['titre' => 'Site', 'statut' => 'en_cours', 'url_prod' => $site]);

    return [$client, $token, $site];
}

function entete(string $token, string $site = 'https://acme.example'): array
{
    return ['X-Support-Token' => $token, 'Origin' => $site, 'Accept' => 'application/json'];
}

it('refuse l’accès sans token ou avec un token invalide', function () {
    $this->getJson('/api/support/motifs')->assertStatus(401);
    $this->getJson('/api/support/motifs', ['X-Support-Token' => 'trop-court'])->assertStatus(401);
    $this->getJson('/api/support/motifs', ['X-Support-Token' => str_repeat('a', 150)])->assertStatus(401);
});

it('expose les motifs au client authentifié', function () {
    [, $token] = clientAvecToken();

    $this->getJson('/api/support/motifs', entete($token))
        ->assertOk()
        ->assertJsonPath('motifs.0.cle', 'panne');
});

it('le client déclare un incident depuis son site', function () {
    Mail::fake();
    [$client, $token] = clientAvecToken();

    $res = $this->postJson('/api/support/tickets', [
        'motif' => 'panne', 'titre' => 'Site KO', 'description' => 'Erreur 500 partout',
    ], entete($token));

    $res->assertCreated()->assertJsonPath('ticket.statut', 'nouveau');

    $bug = Bug::first();
    expect($bug->source)->toBe('client_site')
        ->and($bug->motif)->toBe('panne')
        ->and($bug->type)->toBe('bug')
        ->and($bug->reference)->toStartWith('TIC-')
        ->and($bug->project->prospect_id)->toBe($client->id);

    Mail::assertSent(BugStatusMail::class, fn ($m) => $m->hasTo('client@acme.fr'));
});

it('accepte jusqu’à 4 images base64 et les stocke en privé', function () {
    Storage::fake('local');
    Mail::fake();
    [, $token] = clientAvecToken();

    $this->postJson('/api/support/tickets', [
        'motif' => 'affichage', 'titre' => 'Bug visuel',
        'images' => [
            ['nom' => 'capture1.png', 'data' => PNG_1x1],
            ['data' => 'data:image/png;base64,'.PNG_1x1],
        ],
    ], entete($token))->assertCreated()->assertJsonPath('ticket.images', 2);

    $bug = Bug::first();
    expect($bug->images)->toHaveCount(2);
    Storage::disk('local')->assertExists($bug->images->first()->chemin);
});

it('refuse plus de 4 images', function () {
    Mail::fake();
    [, $token] = clientAvecToken();

    $this->postJson('/api/support/tickets', [
        'motif' => 'autre', 'titre' => 'Trop',
        'images' => array_fill(0, 5, ['data' => PNG_1x1]),
    ], entete($token))->assertStatus(422);

    expect(Bug::count())->toBe(0);
});

it('rejette une image non décodable sans laisser de ticket orphelin', function () {
    Storage::fake('local');
    Mail::fake();
    [, $token] = clientAvecToken();

    $this->postJson('/api/support/tickets', [
        'motif' => 'autre', 'titre' => 'Mauvaise image',
        'images' => [['data' => 'pas-du-base64!!']],
    ], entete($token))->assertStatus(422);

    expect(Bug::count())->toBe(0);
});

it('rejette un fichier qui n’est pas une image', function () {
    Mail::fake();
    [, $token] = clientAvecToken();

    $this->postJson('/api/support/tickets', [
        'motif' => 'autre', 'titre' => 'Pas une image',
        'images' => [['data' => base64_encode('ceci est du texte')]],
    ], entete($token))->assertStatus(422);

    expect(Bug::count())->toBe(0);
});

it('cloisonne les tickets par client', function () {
    Mail::fake();
    [, $tokenA] = clientAvecToken(['entreprise' => 'A', 'email' => 'a@a.fr']);
    [, $tokenB] = clientAvecToken(['entreprise' => 'B', 'email' => 'b@b.fr']);

    $refA = $this->postJson('/api/support/tickets', ['motif' => 'panne', 'titre' => 'A'], entete($tokenA))
        ->json('ticket.reference');
    $this->postJson('/api/support/tickets', ['motif' => 'panne', 'titre' => 'B'], entete($tokenB));

    // B ne voit qu'un ticket (le sien) et ne peut pas lire celui de A.
    $this->getJson('/api/support/tickets', entete($tokenB))
        ->assertOk()->assertJsonCount(1, 'tickets');
    $this->getJson('/api/support/tickets/'.$refA, entete($tokenB))->assertStatus(404);
    // A peut lire le sien.
    $this->getJson('/api/support/tickets/'.$refA, entete($tokenA))
        ->assertOk()->assertJsonPath('ticket.reference', $refA);
});

it('refuse un site absent des projets du client', function () {
    [, $token] = clientAvecToken(['site' => 'https://autorise.fr']);

    // Bon token, mais site appelant non déclaré dans un projet.
    $this->postJson('/api/support/tickets', ['motif' => 'panne', 'titre' => 'X'],
        entete($token, 'https://pirate.fr'))->assertStatus(403);

    expect(Bug::count())->toBe(0);
});

it('refuse quand le site appelant n’est pas identifiable', function () {
    [, $token] = clientAvecToken();

    $this->postJson('/api/support/tickets', ['motif' => 'panne', 'titre' => 'X'],
        ['X-Support-Token' => $token, 'Accept' => 'application/json'])->assertStatus(403);
});

it('rattache le ticket au projet dont l’URL correspond au site appelant', function () {
    Mail::fake();
    [$client, $token] = clientAvecToken(['site' => 'https://site-a.fr']);
    // Deuxième projet avec un autre site (en preprod).
    $projetB = $client->projects()->create([
        'titre' => 'Site B', 'statut' => 'en_cours', 'url_preprod' => 'https://site-b.fr',
    ]);

    $this->postJson('/api/support/tickets', ['motif' => 'panne', 'titre' => 'Depuis B'],
        entete($token, 'https://www.site-b.fr'))->assertCreated();

    expect(Bug::first()->project_id)->toBe($projetB->id);
});

it('ne renvoie que les messages publics dans le suivi du ticket', function () {
    Mail::fake();
    [, $token] = clientAvecToken();
    $ref = $this->postJson('/api/support/tickets', ['motif' => 'panne', 'titre' => 'X'], entete($token))
        ->json('ticket.reference');
    $bug = Bug::first();
    $bug->messages()->create(['corps' => 'Public', 'interne' => false]);
    $bug->messages()->create(['corps' => 'Interne secret', 'interne' => true]);

    $this->getJson('/api/support/tickets/'.$ref, entete($token))
        ->assertOk()
        ->assertJsonCount(1, 'messages')
        ->assertJsonPath('messages.0.corps', 'Public');
});

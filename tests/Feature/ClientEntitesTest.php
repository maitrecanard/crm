<?php

use App\Models\Besoin;
use App\Models\Devis;
use App\Models\FacturePonctuelle;
use App\Models\Prospect;

function clientTest(): Prospect
{
    return Prospect::create([
        'cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'est_client' => true,
        'client_depuis' => now()->toDateString(),
    ]);
}

it('un client porte besoins, devis et factures ponctuelles', function () {
    $c = clientTest();
    $c->besoins()->create(['titre' => 'Refonte', 'statut' => 'a_traiter']);
    $c->devis()->create(['reference' => 'D-1', 'montant_ht' => 3500, 'statut' => 'envoye']);
    $c->facturesPonctuelles()->create(['reference' => 'F-1', 'montant_ht' => 1200, 'statut' => 'envoyee']);

    $c->load('besoins', 'devis', 'facturesPonctuelles');

    expect($c->besoins)->toHaveCount(1)
        ->and($c->devis)->toHaveCount(1)
        ->and($c->facturesPonctuelles)->toHaveCount(1)
        ->and((float) $c->devis->first()->montant_ht)->toBe(3500.0);
});

it('les statuts sont définis pour chaque entité', function () {
    expect(Besoin::STATUTS)->toHaveKey('a_traiter')
        ->and(Devis::STATUTS)->toHaveKeys(['envoye', 'accepte', 'refuse'])
        ->and(FacturePonctuelle::STATUTS)->toHaveKeys(['envoyee', 'payee']);
});

it('supprimer un client supprime ses besoins/devis/factures (cascade)', function () {
    $c = clientTest();
    $c->besoins()->create(['titre' => 'X']);
    $c->devis()->create(['reference' => 'D']);
    $c->facturesPonctuelles()->create(['reference' => 'F']);
    $c->delete();

    expect(Besoin::count())->toBe(0)
        ->and(Devis::count())->toBe(0)
        ->and(FacturePonctuelle::count())->toBe(0);
});

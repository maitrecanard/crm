<?php

use App\Models\FactureMensuelle;
use App\Models\Prospect;
use Carbon\Carbon;

beforeEach(function () {
    // Date figée : 11 juin 2026.
    Carbon::setTestNow(Carbon::create(2026, 6, 11, 10));
});

afterEach(function () {
    Carbon::setTestNow();
});

function clientFacture(array $over = []): Prospect
{
    return Prospect::create(array_merge([
        'cle' => 'c-'.uniqid(),
        'entreprise' => 'ACME',
        'facturation_active' => true,
        'facturation_jour' => 5,
        'facturation_debut' => '2026-03-01',
        'facturation_montant_ht' => 800,
        'facturation_libelle' => 'Maintenance',
    ], $over));
}

it('échéance = jour configuré du mois suivant', function () {
    $ech = FactureMensuelle::echeancePour(Carbon::parse('2026-02-01'), 5);
    expect($ech->toDateString())->toBe('2026-03-05');
});

it('liste les mois attendus du début au mois courant', function () {
    $labels = collect(clientFacture()->apercuFacturation())->pluck('periode')->all();
    expect($labels)->toBe(['2026-06', '2026-05', '2026-04', '2026-03']);
});

it('marque en retard les mois échus sans référence', function () {
    $apercu = collect(clientFacture()->apercuFacturation())->keyBy('periode');
    expect($apercu['2026-06']['statut'])->toBe('a_venir')   // échéance 2026-07-05 > now
        ->and($apercu['2026-05']['statut'])->toBe('en_retard')  // échéance 2026-06-05 < now
        ->and($apercu['2026-04']['statut'])->toBe('en_retard')
        ->and($apercu['2026-03']['statut'])->toBe('en_retard');
});

it('une référence saisie lève le retard du mois', function () {
    $c = clientFacture();
    expect($c->facturesEnRetard())->toHaveCount(3);

    $c->facturesMensuelles()->create([
        'periode' => '2026-03-01', 'reference' => 'F2026-03', 'envoyee_le' => '2026-04-02',
    ]);
    $c->load('facturesMensuelles');
    expect($c->facturesEnRetard())->toHaveCount(2);
});

it('client inactif : aucun mois ni retard', function () {
    $c = clientFacture(['facturation_active' => false]);
    expect($c->apercuFacturation())->toBe([])
        ->and($c->facturesEnRetard())->toBe([]);
});

it('alertesFacturation agrège les retards de tous les clients', function () {
    clientFacture(['entreprise' => 'Client A']);
    clientFacture(['entreprise' => 'Client B', 'facturation_debut' => '2026-05-01']); // 1 retard (mai)
    // Client A : mars/avril/mai = 3 retards ; Client B : mai = 1 retard → 4 au total.
    expect(Prospect::alertesFacturation())->toHaveCount(4);
});

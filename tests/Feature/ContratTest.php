<?php

use App\Models\Contrat;
use App\Models\Prospect;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;

function clientContrat(): Prospect
{
    return Prospect::create([
        'cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'email' => 'a@b.fr',
        'est_client' => true, 'client_depuis' => now()->toDateString(),
    ]);
}

it('un contrat se rattache au client avec ses conditions', function () {
    $c = clientContrat();
    $ct = $c->contrats()->create([
        'objet' => 'Dév app', 'montant_ht' => 12000, 'reference' => 'C-1',
        'conditions' => 'Article 1 — Test', 'statut' => 'brouillon',
    ]);
    expect($c->contrats()->count())->toBe(1)
        ->and($ct->conditions)->toContain('Article 1');
});

it('le modèle global de conditions est lisible et modifiable', function () {
    expect(Setting::get('contrat_conditions', 'defaut'))->toBe('defaut'); // vide -> défaut
    Setting::put('contrat_conditions', 'Mes conditions à jour');
    expect(Setting::get('contrat_conditions'))->toBe('Mes conditions à jour');
});

it('le PDF du contrat est généré', function () {
    $c = clientContrat();
    $ct = $c->contrats()->create([
        'objet' => 'Mission', 'montant_ht' => 5000, 'reference' => 'C-PDF',
        'conditions' => 'Conditions de test', 'statut' => 'brouillon',
    ]);
    $pdf = Pdf::loadView('contrats.pdf', [
        'contrat' => $ct, 'client' => $c, 'vendeur' => config('crm.vendeur'),
    ])->output();
    expect(substr($pdf, 0, 5))->toBe('%PDF-')
        ->and(strlen($pdf))->toBeGreaterThan(1000);
});

it('supprimer le client supprime ses contrats (cascade)', function () {
    $c = clientContrat();
    $c->contrats()->create(['objet' => 'X', 'conditions' => 'Y']);
    $c->delete();
    expect(Contrat::count())->toBe(0);
});

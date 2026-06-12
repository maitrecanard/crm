<?php

use App\Models\Prospect;

function p(array $over = []): Prospect
{
    return Prospect::create(array_merge([
        'cle' => 'p-'.uniqid(), 'entreprise' => 'X', 'statut' => 'a_contacter',
    ], $over));
}

it('purge supprime les non contactés et préserve le reste', function () {
    p(['statut' => 'a_contacter']);                 // supprimé
    p(['statut' => 'a_contacter']);                 // supprimé
    p(['statut' => 'contacte']);                    // gardé (déjà contacté)
    p(['statut' => 'rdv']);                         // gardé
    p(['statut' => 'a_contacter', 'est_client' => true, 'client_depuis' => now()]); // client : gardé

    $deleted = Prospect::where('statut', 'a_contacter')->where('est_client', false)->delete();

    expect($deleted)->toBe(2)
        ->and(Prospect::count())->toBe(3)
        ->and(Prospect::where('statut', 'a_contacter')->where('est_client', false)->count())->toBe(0)
        ->and(Prospect::where('est_client', true)->count())->toBe(1);
});

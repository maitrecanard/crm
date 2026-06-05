<?php

namespace App\Observers;

use App\Models\Prospect;
use App\Services\ClientPromotion;

class ProspectObserver
{
    /** Quand un prospect passe à « Gagné » : il devient client + projet auto. */
    public function saved(Prospect $prospect): void
    {
        if ($prospect->wasChanged('statut') && $prospect->statut === 'gagne') {
            ClientPromotion::promote($prospect);
        }
    }
}

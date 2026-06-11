<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturePonctuelle extends Model
{
    protected $table = 'factures_ponctuelles';

    protected $fillable = [
        'prospect_id', 'reference', 'montant_ht', 'statut', 'date_facture', 'lien', 'notes',
    ];

    protected $casts = [
        'montant_ht'   => 'decimal:2',
        'date_facture' => 'date',
    ];

    public const STATUTS = [
        'a_envoyer' => 'À envoyer',
        'envoyee'   => 'Envoyée',
        'payee'     => 'Payée',
        'impayee'   => 'Impayée',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }
}

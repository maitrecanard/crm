<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Devis extends Model
{
    protected $table = 'devis';

    protected $fillable = [
        'prospect_id', 'reference', 'montant_ht', 'statut', 'date_devis', 'lien', 'notes',
    ];

    protected $casts = [
        'montant_ht' => 'decimal:2',
        'date_devis' => 'date',
    ];

    public const STATUTS = [
        'brouillon' => 'Brouillon',
        'envoye'    => 'Envoyé',
        'accepte'   => 'Accepté',
        'refuse'    => 'Refusé',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }
}

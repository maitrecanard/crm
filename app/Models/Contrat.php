<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contrat extends Model
{
    protected $table = 'contrats';

    protected $fillable = [
        'prospect_id', 'reference', 'objet', 'montant_ht', 'conditions',
        'statut', 'date_contrat', 'envoye_le',
    ];

    protected $casts = [
        'montant_ht'   => 'decimal:2',
        'date_contrat' => 'date',
        'envoye_le'    => 'datetime',
    ];

    public const STATUTS = [
        'brouillon' => 'Brouillon',
        'envoye'    => 'Envoyé',
        'signe'     => 'Signé',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }
}

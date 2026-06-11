<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Besoin extends Model
{
    protected $fillable = ['prospect_id', 'titre', 'description', 'statut'];

    public const STATUTS = [
        'a_traiter' => 'À traiter',
        'en_cours'  => 'En cours',
        'devis'     => 'Devis envoyé',
        'traite'    => 'Traité',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }
}

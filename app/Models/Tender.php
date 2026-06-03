<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tender extends Model
{
    protected $fillable = [
        'prospect_id', 'idweb', 'objet', 'acheteur', 'departement', 'procedure',
        'date_parution', 'date_limite', 'url', 'statut', 'notes',
    ];

    protected $casts = [
        'date_parution' => 'date',
        'date_limite'   => 'datetime',
    ];

    protected $appends = ['jours_restants'];

    /** Pipeline de candidature à un appel d'offres. */
    public const STATUTS = [
        'a_etudier'  => 'À étudier',
        'go'         => 'Go (je réponds)',
        'dossier'    => 'Dossier en cours',
        'depose'     => 'Déposé',
        'gagne'      => 'Gagné',
        'perdu'      => 'Perdu',
        'abandonne'  => 'Abandonné',
        'expire'     => 'Expiré',
    ];

    public function prospect(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    /** Jours restants avant la date limite (négatif si dépassé). */
    public function getJoursRestantsAttribute(): ?int
    {
        return $this->date_limite ? (int) now()->startOfDay()->diffInDays($this->date_limite, false) : null;
    }
}

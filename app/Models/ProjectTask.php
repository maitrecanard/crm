<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTask extends Model
{
    protected $fillable = ['project_id', 'titre', 'description', 'statut', 'source', 'partenaire_id', 'echeance', 'ordre'];

    protected $casts = [
        'echeance' => 'date',
        'ordre'    => 'integer',
    ];

    public const STATUTS = [
        'a_faire'  => 'À faire',
        'en_cours' => 'En cours',
        'fait'     => 'Fait',
    ];

    public const SOURCES = [
        'interne'    => 'Interne',
        'partenaire' => 'Partenaire',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function partenaire(): BelongsTo
    {
        return $this->belongsTo(Partenaire::class);
    }

    /** Tâches transmises par un partenaire et non terminées. */
    public function scopeEnAttentePartenaire($query)
    {
        return $query->where('source', 'partenaire')->where('statut', '!=', 'fait');
    }
}

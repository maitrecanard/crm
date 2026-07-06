<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTask extends Model
{
    protected $fillable = ['project_id', 'titre', 'description', 'statut', 'motif_refus', 'source', 'partenaire_id', 'echeance', 'ordre'];

    protected $casts = [
        'echeance' => 'date',
        'ordre'    => 'integer',
    ];

    public const STATUTS = [
        'proposee' => 'Proposée',
        'refusee'  => 'Refusée',
        'a_faire'  => 'À faire',
        'en_cours' => 'En cours',
        'fait'     => 'Fait',
    ];

    /** Statuts qu'un partenaire pilote lui-même une fois la tâche acceptée. */
    public const STATUTS_PARTENAIRE = ['a_faire', 'en_cours', 'fait'];

    public const SOURCES = [
        'interne'    => 'Interne',
        'partenaire' => 'Partenaire',   // transmise PAR un partenaire (partenaire -> admin)
        'assignee'   => 'Assignée',     // assignée À un partenaire (admin -> partenaire)
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

    /** Tâches assignées à un partenaire (flux admin -> partenaire). */
    public function scopeAssignee($query)
    {
        return $query->where('source', 'assignee');
    }

    /** La tâche est-elle en attente de réponse (accept/refus) du partenaire ? */
    public function estAProposer(): bool
    {
        return $this->source === 'assignee' && $this->statut === 'proposee';
    }
}

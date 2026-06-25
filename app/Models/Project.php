<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'prospect_id', 'partenaire_id', 'tender_id', 'titre', 'description', 'statut',
        'url_prod', 'url_preprod', 'repo_git', 'hebergeur',
        'budget', 'date_debut', 'date_fin_prevue', 'date_livraison', 'notes',
    ];

    protected $casts = [
        'budget'          => 'integer',
        'date_debut'      => 'date',
        'date_fin_prevue' => 'date',
        'date_livraison'  => 'date',
    ];

    protected $appends = ['avancement'];

    /** Cycle de vie d'un projet. */
    public const STATUTS = [
        'cadrage'  => 'Cadrage',
        'en_cours' => 'En cours',
        'recette'  => 'Recette',
        'livre'    => 'Livré',
        'cloture'  => 'Clôturé',
        'suspendu' => 'Suspendu',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function partenaire(): BelongsTo
    {
        return $this->belongsTo(Partenaire::class);
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class)->orderBy('ordre')->orderBy('id');
    }

    public function bugs(): HasMany
    {
        return $this->hasMany(Bug::class)->latest();
    }

    /** % de tâches terminées (0-100). */
    public function getAvancementAttribute(): int
    {
        $total = $this->tasks_count ?? $this->tasks()->count();
        if (! $total) {
            return 0;
        }
        $faites = $this->relationLoaded('tasks')
            ? $this->tasks->where('statut', 'fait')->count()
            : $this->tasks()->where('statut', 'fait')->count();

        return (int) round($faites / $total * 100);
    }
}

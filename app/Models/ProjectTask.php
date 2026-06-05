<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTask extends Model
{
    protected $fillable = ['project_id', 'titre', 'statut', 'echeance', 'ordre'];

    protected $casts = [
        'echeance' => 'date',
        'ordre'    => 'integer',
    ];

    public const STATUTS = [
        'a_faire'  => 'À faire',
        'en_cours' => 'En cours',
        'fait'     => 'Fait',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

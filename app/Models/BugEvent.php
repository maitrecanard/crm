<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Événement d'historique d'un ticket (Bug). N'a qu'un created_at : c'est un
 * journal en append-only, jamais mis à jour.
 */
class BugEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['bug_id', 'type', 'ancien_statut', 'nouveau_statut', 'auteur'];

    public function bug(): BelongsTo
    {
        return $this->belongsTo(Bug::class);
    }
}

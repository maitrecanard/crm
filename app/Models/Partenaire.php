<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Partenaire (apporteur d'affaires / sous-traitant). On lui rattache des projets ;
 * il peut, via son compte (User role=partenaire), transmettre des tâches à réaliser.
 */
class Partenaire extends Model
{
    protected $fillable = ['nom', 'contact_nom', 'email', 'telephone', 'notes', 'actif'];

    protected $casts = ['actif' => 'boolean'];

    protected static function booted(): void
    {
        // Supprimer un partenaire supprime son compte de connexion (pas d'orphelin).
        static::deleting(function (Partenaire $partenaire) {
            $partenaire->user()->delete();
        });
    }

    /** Le compte de connexion du partenaire (un seul). */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /** Projets rattachés à ce partenaire (plusieurs partenaires possibles par projet). */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withTimestamps()->latest('projects.id');
    }

    /** Tâches transmises par ce partenaire. */
    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class)->where('source', 'partenaire');
    }

    /** Le compte a-t-il été activé (mot de passe défini par le partenaire) ? */
    public function getCompteActifAttribute(): bool
    {
        return (bool) $this->user?->email_verified_at;
    }
}

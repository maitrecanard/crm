<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bug extends Model
{
    protected $fillable = [
        'project_id', 'titre', 'description', 'statut', 'gravite', 'issue_git',
        'notifie_le', 'resolved_at',
    ];

    protected $casts = [
        'notifie_le'  => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /** Étapes du traitement d'un bug (chaque passage notifie le client). */
    public const STATUTS = [
        'nouveau'  => 'Nouveau',
        'en_cours' => 'En cours de traitement',
        'en_test'  => 'Correctif en test',
        'livre'    => 'Correctif livré',
        'ferme'    => 'Clôturé',
    ];

    public const GRAVITES = [
        'mineur'   => 'Mineur',
        'majeur'   => 'Majeur',
        'bloquant' => 'Bloquant',
    ];

    /** Message client associé à chaque statut (utilisé dans l'e-mail). */
    public const MESSAGES = [
        'nouveau'  => 'Nous avons bien reçu votre signalement et l’avons enregistré. Notre équipe va l’examiner.',
        'en_cours' => 'Votre signalement est désormais pris en charge : la correction est en cours.',
        'en_test'  => 'Le correctif a été développé et est en cours de test avant mise en ligne.',
        'livre'    => 'Le correctif a été livré et déployé. N’hésitez pas à vérifier de votre côté.',
        'ferme'    => 'Votre ticket est clôturé. Merci de votre signalement !',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

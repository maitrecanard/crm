<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bug extends Model
{
    protected $fillable = [
        'project_id', 'reference', 'type', 'source', 'motif', 'titre', 'description',
        'statut', 'gravite', 'recurrence', 'prochaine_echeance', 'issue_git',
        'notifie_le', 'resolved_at',
    ];

    protected static function booted(): void
    {
        static::created(function (Bug $bug) {
            if (empty($bug->reference)) {
                $bug->reference = sprintf('TIC-%d-%04d', $bug->created_at?->year ?? now()->year, $bug->id);
                $bug->saveQuietly();
            }

            // Premier événement de l'historique : ouverture du ticket.
            $bug->events()->create([
                'type'           => 'creation',
                'nouveau_statut' => $bug->statut,
                'auteur'         => $bug->source === 'client_site' ? 'client' : 'support',
            ]);
        });
    }

    /** Journalise un changement de statut dans l'historique du ticket. */
    public function logStatut(string $ancien, string $nouveau, string $auteur = 'support'): void
    {
        $this->events()->create([
            'type'           => 'statut',
            'ancien_statut'  => $ancien,
            'nouveau_statut' => $nouveau,
            'auteur'         => $auteur,
        ]);
    }

    protected $casts = [
        'prochaine_echeance' => 'date',
        'notifie_le'         => 'datetime',
        'resolved_at'        => 'datetime',
    ];

    /** Nature de l'intervention. */
    public const TYPES = [
        'bug'         => 'Bug',
        'maintenance' => 'Maintenance périodique',
        'evolution'   => 'Évolution',
    ];

    /**
     * Motifs proposés au client lorsqu'il déclare un incident depuis son site.
     * Chaque motif est mappé au `type` interne du ticket.
     */
    public const MOTIFS = [
        'panne'     => ['label' => 'Panne / site inaccessible', 'type' => 'bug'],
        'anomalie'  => ['label' => 'Anomalie / comportement inattendu', 'type' => 'bug'],
        'affichage' => ['label' => 'Problème d’affichage', 'type' => 'bug'],
        'evolution' => ['label' => 'Demande d’évolution', 'type' => 'evolution'],
        'question'  => ['label' => 'Question / assistance', 'type' => 'bug'],
        'autre'     => ['label' => 'Autre', 'type' => 'bug'],
    ];

    /** Type interne déduit d'un motif client (défaut : bug). */
    public static function typePourMotif(?string $motif): string
    {
        return self::MOTIFS[$motif]['type'] ?? 'bug';
    }

    /** Étapes (chaque passage notifie le client). Libellés génériques dans l'app. */
    public const STATUTS = [
        'nouveau'             => 'Ouvert',
        'en_cours'            => 'En cours',
        'attente_fournisseur' => 'En attente de retour fournisseur',
        'attente_client'      => 'En attente de retour client',
        'en_test'             => 'En vérification',
        'livre'               => 'Résolu / livré',
        'ferme'               => 'Clôturé',
    ];

    /** Étapes « en cours » : ni livré, ni clôturé (inclut les mises en attente). */
    public const OUVERTS = ['nouveau', 'en_cours', 'attente_fournisseur', 'attente_client', 'en_test'];

    public const GRAVITES = [
        'mineur'   => 'Mineur',
        'majeur'   => 'Majeur',
        'bloquant' => 'Bloquant',
    ];

    public const RECURRENCES = [
        'ponctuelle'    => 'Ponctuelle',
        'mensuelle'     => 'Mensuelle',
        'trimestrielle' => 'Trimestrielle',
        'semestrielle'  => 'Semestrielle',
        'annuelle'      => 'Annuelle',
    ];

    /** Libellé d'étape vu par le client, adapté au type. */
    public function statutLabel(): string
    {
        $maintenance = [
            'nouveau' => 'Planifiée', 'en_cours' => 'En cours',
            'attente_fournisseur' => 'En attente de retour fournisseur',
            'attente_client' => 'En attente de retour client',
            'en_test' => 'En vérification',
            'livre' => 'Terminée', 'ferme' => 'Clôturée',
        ];

        return $this->type === 'maintenance'
            ? ($maintenance[$this->statut] ?? $this->statut)
            : (self::STATUTS[$this->statut] ?? $this->statut);
    }

    /** Préfixe de sujet d'e-mail selon le type. */
    public function subjectPrefix(): string
    {
        return ['bug' => 'Support', 'maintenance' => 'Maintenance', 'evolution' => 'Évolution'][$this->type] ?? 'Support';
    }

    /** Message client associé à l'étape, adapté au type. */
    public function clientMessage(): string
    {
        $messages = [
            'bug' => [
                'nouveau'  => 'Nous avons bien reçu votre signalement et l’avons enregistré. Notre équipe va l’examiner.',
                'en_cours' => 'Votre signalement est désormais pris en charge : la correction est en cours.',
                'attente_fournisseur' => 'Le traitement se poursuit : nous sommes en attente d’un retour de notre fournisseur/partenaire.',
                'attente_client'      => 'Nous avons besoin d’un complément d’information de votre part pour avancer. Merci de nous répondre.',
                'en_test'  => 'Le correctif a été développé et est en cours de test avant mise en ligne.',
                'livre'    => 'Le correctif a été livré et déployé. N’hésitez pas à vérifier de votre côté.',
                'ferme'    => 'Votre ticket est clôturé. Merci de votre signalement !',
            ],
            'maintenance' => [
                'nouveau'  => 'Une opération de maintenance a été planifiée sur votre application.',
                'en_cours' => 'L’opération de maintenance est en cours.',
                'attente_fournisseur' => 'L’opération est en attente d’un retour de notre fournisseur/partenaire.',
                'attente_client'      => 'L’opération est en attente d’une information de votre part.',
                'en_test'  => 'Vérifications finales en cours suite à la maintenance.',
                'livre'    => 'L’opération de maintenance est terminée : tout est opérationnel.',
                'ferme'    => 'Opération de maintenance clôturée. Merci de votre confiance !',
            ],
            'evolution' => [
                'nouveau'  => 'Votre demande d’évolution a bien été enregistrée.',
                'en_cours' => 'Votre demande d’évolution est en cours de développement.',
                'attente_fournisseur' => 'Votre demande d’évolution est en attente d’un retour de notre fournisseur/partenaire.',
                'attente_client'      => 'Votre demande d’évolution est en attente d’une information de votre part.',
                'en_test'  => 'L’évolution est en cours de test avant mise en ligne.',
                'livre'    => 'L’évolution a été livrée et déployée.',
                'ferme'    => 'Demande d’évolution clôturée. Merci !',
            ],
        ];

        return $messages[$this->type][$this->statut] ?? ($messages['bug'][$this->statut] ?? '');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Ticket interne : rattaché à aucun projet/client (donc aucune notification client). */
    public function estInterne(): bool
    {
        return is_null($this->project_id);
    }

    public function messages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BugMessage::class)->orderBy('created_at');
    }

    public function events(): HasMany
    {
        return $this->hasMany(BugEvent::class)->orderBy('created_at');
    }

    public function images(): HasMany
    {
        return $this->hasMany(BugImage::class)->orderBy('id');
    }

    /** Libellé lisible du motif client (ou null si interne). */
    public function motifLabel(): ?string
    {
        return $this->motif ? (self::MOTIFS[$this->motif]['label'] ?? $this->motif) : null;
    }
}
